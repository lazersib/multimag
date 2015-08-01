<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2015, BlackLight, TND Team, http://tndproject.org
//
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU Affero General Public License as
//	published by the Free Software Foundation, either version 3 of the
//	License, or (at your option) any later version.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU Affero General Public License for more details.
//
//	You should have received a copy of the GNU Affero General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

namespace oauth;

abstract class oauthplugin {
    
    var $client;            // OAuth client object
    var $user_oauth_info;   // Данные от OAuth сервера
    var $server;            // Имя OAuth сервера
    var $oauth_reg_data;    // Данные о регистрации пользователя из OAuth
    var $openid_reg_data;   // Данные о регистрации пользователя из OpenID
    var $user_data;         // Данные о регистрации пользователя из users
    var $realm;             // URL главной сайта
    
    var $oauth_profile;     // Нормализованный профиль на основе данных от OAuth сервера
    
    function __construct() {
        
    }
    
    abstract function getName();
    
    function init() {
        global $CONFIG;
        if(!$this->isConfigured()) {
            throw new \Exception("Плагин OAuth - ".get_class()." не настроен!");
        }
        
        $this->client = new \oauth\oauth_client();
        $this->user_oauth_info = false;
        $this->oauth_reg_data = false;
	$this->client->server = $this->server;
        $this->client->Initialize();
        $proto = 'http';
        if (@$CONFIG['site']['force_https'] || @$CONFIG['site']['force_https_login'] || @$_SERVER['HTTPS']) {
            $proto =  'https';
        }
        $host = $_SERVER['HTTP_HOST'];    
        /*$altport = '';
        if($proto=='http' && $_SERVER['SERVER_PORT']!=80) {
            $altport = ':'.$_SERVER['SERVER_PORT'];
        } elseif($proto=='https' && $_SERVER['SERVER_PORT']!=443) {
            $altport = ':'.$_SERVER['SERVER_PORT'];
        }*/
        $this->realm = $proto.'://'.$host;//.$altport;
        
	$this->client->redirect_uri = $this->realm.'/oauth.php/'.$this->server.'/';

	$this->client->client_id = $CONFIG['oauth'][$this->server]['id'];
	$this->client->client_secret = $CONFIG['oauth'][$this->server]['secret'];        
    }
    
    public function isConfigured() {
        global $CONFIG;
        if(!@$CONFIG['oauth'][$this->server]['id'] || !@$CONFIG['oauth'][$this->server]['secret']) {
            return false;
        }
        return true;
    }


    public function auth() {
        $redirect_url = '';
        if ($this->client->CheckAccessToken($redirect_url)) {
            if ($redirect_url) {
                redirect($redirect_url);
            }
            if (strlen($this->client->authorization_error)) {
                $desc = html_out(request('error_description'));
                if($this->client->authorization_error == 'access_denied') {
                    throw new \Exception($desc);
                }
                throw new \Exception("Ошибка OAuth авторизации (".$this->client->authorization_error."):".$desc);
            }
            if (!strlen($this->client->access_token)) {
                throw new \Exception("Ошибка OAuth токена!");
            }            
        }
    }

    abstract function getUserOAuthProfile();
    
    function findOAuthRegData() {
        global $db;
        $s_server = $db->real_escape_string($this->server);
        $s_id = $db->real_escape_string($this->oauth_profile['id']);
        $res = $db->query("SELECT * FROM `users_oauth` WHERE `server`='$s_server' AND `client_id`='$s_id'");
        if($res->num_rows) {
            $this->oauth_reg_data = $res->fetch_array();
            return $this->oauth_reg_data;
        }
        return $this->oauth_reg_data=false;
    }
    
    function findOpenIDRegData() {
        global $db;
        if(!isset($this->oauth_profile['openid_list'])) {
            return false;
        }
        foreach($this->oauth_profile['openid_list'] as $oid) {
            $sql_oid = $db->real_escape_string($oid);
            $res = $db->query("SELECT * FROM `users_openid` WHERE `openid_identify`='$sql_oid'");
            if($res->num_rows) {
                $this->openid_reg_data = $res->fetch_array();
                return $this->openid_reg_data;
            }
        }
        return $this->openid_reg_data=false;
    }
    
    function findUserRegDataOfEmail() {
        global $db;
        if(!$this->oauth_profile['is_verifed_email']) {
            return false;
        }
        $s_email = $db->real_escape_string($this->oauth_profile['email']);
        $res = $db->query("SELECT * FROM `users` WHERE `reg_email` = '$s_email'");
        if($res->num_rows) {
            $this->user_data = $res->fetch_array();
            return $this->user_data;
        }
        return false;
    }
    
    protected function createOAuthRegData($user_id) {
        global $db;
        settype($user_id,'int');
        $res = $db->query("SELECT `worker` FROM `users_worker_info` WHERE `user_id`='$user_id' AND `worker`>0");
        if($res->num_rows) {
            throw new \Exception('Для сотрудников вход через сторонние сервисы не допустим');
        }
        
        $s_server = $db->real_escape_string($this->server);
        $s_token = $db->real_escape_string($this->client->access_token);
        $s_token_exp = $db->real_escape_string($this->client->access_token_expiry);
        
        $s_c_id = $db->real_escape_string($this->oauth_profile['id']);
        $s_c_login = $db->real_escape_string($this->oauth_profile['login']);        
        
        $db->query("INSERT INTO `users_oauth`"
            . " (`user_id`, `server`, `client_id`, `client_login`, `access_token`, `expire`, `creation`)"
            . " VALUES"
            . " ('$user_id', '$s_server', '$s_c_id', '$s_c_login', '$s_token', '$s_token_exp', NOW())");
    }
    
    public function tryLogin() {
        global $db;
        $this->getUserOAuthProfile();
        $userdata = $this->findOAuthRegData();
        if($userdata) {// Уже есть в базе
            $this->authenticate($userdata['user_id'], $this->server);
        }
        $openid_data = $this->findOpenIDRegData();
        if($openid_data) { // Зарегистрирован, но нет OAuth токена
            $db->startTransaction();
            $this->createOAuthRegData($openid_data['user_id']);
            $this->updateProfile($openid_data['user_id']);
            $db->commit();
            $this->authenticate($openid_data['user_id'], $this->server);
        }
        $email_data = $this->findUserRegDataOfEmail();
        if($email_data) {
            $db->startTransaction();
            $this->createOAuthRegData($email_data['id']);
            $this->updateProfile($email_data['id']);
            $db->commit();
            $this->authenticate($email_data['id'], $this->server);
        }
        /// Нет такого пользователя
        $login = false;
        $auth = new \authenticator();
        $logins = array(
            $this->oauth_profile['login'], 
            $this->oauth_profile['login'].'@'.$this->server,
            $this->oauth_profile['email'], 
            'u'.time(),
            'u'.rand(0,5000000),
            'u'.rand(0,15000000),
            'u'.rand(0,45000000) 
        );
        foreach($logins as $test_login) {
            if(!$auth->loadDataForLogin($test_login)) {
                $login = $test_login;
                break;
            }
        }
        if($login===false) {
            throw new Exception("Не удалось подобрать допустимый логин.");
        }
        
        $db->startTransaction();
        $user_id = $auth->createUser($login, $this->oauth_profile['email'], $this->oauth_profile['is_verifed_email'], @$this->oauth_profile['phone'], false, 
            $this->oauth_profile['fullname']);
        $this->createOAuthRegData($user_id);
        $this->updateProfile($user_id);
        $db->commit();
        $this->authenticate($user_id);
    }
    
    /// Привязать OAuth профиль к действующему аккаунту
    public function tryConnect() {
        global $db;
        $user_id = intval($_SESSION['uid']);
        if(!$user_id) {
            throw new \Exception('Вы не вошли в систему!');
        }        
        $this->getUserOAuthProfile();
        $userdata = $this->findOAuthRegData();
        if($userdata) {// Уже есть в базе
            if($userdata['user_id'] == $user_id) {
                return "Вы уже прикрепили этот профиль ранее!";
            } else {
                return "Этот профиль прикреплён к другому пользователю! При необходимости, вы можете выйти, и зайти под этим профилем.";
            }
        }
        
        $db->startTransaction();
        $auth = new \authenticator();
        $auth->loadDataForID($user_id);
        $user_data = $auth->getUserInfo();
        $this->createOAuthRegData($user_id);
        if($this->oauth_profile['email']) {            
            if(!$user_data['reg_email']) {
                $auth->setRegEmail($this->oauth_profile['email']);
                $code = $auth->getNewConfirmEmailCode();
                if($this->oauth_profile['is_verifed_email']) {
                    $auth->tryConfirmEmail($code);                    
                } else {                    
                    $auth->sendConfirmEmail($code);
                }
            } elseif($auth->isNeedConfirmEmail() && $this->oauth_profile['is_verifed_email']) {
                $auth->setRegEmail($this->oauth_profile['email']);
                $auth->tryConfirmEmail($code); 
            }
        }
        if($this->oauth_profile['fullname']) {
            if(!$user_data['real_name']) {
                $db->update('users', $user_id, 'real_name', $this->oauth_profile['fullname']);
            }
        }
        
        $this->updateProfile($user_id);
        $db->commit();
        return "Профиль успешно прекреплён!";
    }

    protected function updateProfile($user_id) {
        global $db;
        if(!$user_id) {
            throw new \Exception('ID пользователя не задан!');
        }
        
        $user_profile = array();
        $v = array('name', 'surname', 'picture', 'gender', 'language', 'birthday');
        foreach($v as $vv) {
            if(isset($this->oauth_profile[$vv])) {
                $user_profile[$vv] = $this->oauth_profile[$vv];
            }
        }
        $db->replaceKA('users_data', 'uid', $user_id, $user_profile);
    }


    protected function authenticate($user_id) {
        if(!$user_id) {
            throw new \Exception('ID пользователя не задан!');
        }
        $auth = new \authenticator();
        $auth->loadDataForID($user_id);
        if ($auth->isDisabled()) {
            throw new \Exception("Пользователь заблокирован (забанен). Причина блокировки: " . $auth->getDisabledReason() );
        }
        $auth->authenticate('oauth:'.$this->server);
        if(isset($_SESSION['last_page'])) {            
            $lp = $_SESSION['last_page'];
            unset($_SESSION['last_page']);
            redirect($lp);
        }
        else if (@$_SESSION['redir_to']) {
            redirect($_SESSION['redir_to']);
        } else {
            redirect("/user.php");
        }
    }
}
