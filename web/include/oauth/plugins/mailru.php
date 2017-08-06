<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2017, BlackLight, TND Team, http://tndproject.org
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

// Yandex developer page: https://oauth.yandex.ru/client/new. The callback URL must be $client->redirect_uri

namespace oauth\plugins;

class mailru extends \oauth\oauthplugin {
    
    function __construct() {
        parent::__construct();
        $this->server = 'mailru';
    }
    
    function getName() {
        return "Войти через Мэйл.ру";
    }
    
    function init() {
        parent::init();        
        $this->client->oauth_version = "2.0";
        $this->client->dialog_url = "https://connect.mail.ru/oauth/authorize?client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&scope={SCOPE}&response_type=code&state={STATE}";
        $this->client->access_token_url = "https://connect.mail.ru/oauth/token";
        $this->client->store_access_token_response = true;
        $this->client->scope = '';
    }
    
    protected function createOAuthRegData($user_id) {
        global $db;
        settype($user_id,'int');
        $s_server = $db->real_escape_string($this->server);
        $s_token = $db->real_escape_string($this->client->access_token);
        $s_token_exp = $db->real_escape_string($this->client->access_token_expiry);
        $s_token_sec = $db->real_escape_string($this->client->access_token_response['x_mailru_vid']);
        
        $s_c_id = $db->real_escape_string($this->oauth_profile['id']);
        $s_c_login = $db->real_escape_string($this->oauth_profile['login']);        
        
        $db->query("INSERT INTO `users_oauth`"
            . " (`user_id`, `server`, `client_id`, `client_login`, `access_token`, `expire`, `creation`, `access_token_secret`)"
            . " VALUES"
            . " ('$user_id', '$s_server', '$s_c_id', '$s_c_login', '$s_token', '$s_token_exp', NOW(), '$s_token_sec')");
    }
    
    function getUserOAuthProfile() {
        $userinfo = '';
        $parameters = array(
            'method' => 'users.getInfo',
            'uids' => $this->client->access_token_response['x_mailru_vid'],
            'app_id' => $this->client->client_id,
            'secure' => '1'
        );
        ksort($parameters);
        $values = '';
        $url = 'http://www.appsmail.ru/platform/api?sig={signature}';
        foreach ($parameters as $key => $value) {
            $values .= $key . '=' . $value;
            $url .= '&' . $key . '=' . $value;
        }
        $url = str_replace('{signature}', md5($values . $this->client->client_secret), $url);
        $success = $this->client->CallAPI(
            $url, 'GET', array(), array('FailOnAccessError'=>true), $userinfo);
        if(!$success) {
            throw new \Exception('Ошибка получения данных пользователя!');
        }
        $this->oauth_profile = array(
            'id' => $userinfo[0]->uid,
            'login' => translitIt($userinfo[0]->nick),
            'email' => $userinfo[0]->email,
            'is_verifed_email' => 1,
            'birthday' => $userinfo[0]->birthday,
            'fullname' => $userinfo[0]->first_name.' '.$userinfo[0]->last_name,
            'name' => $userinfo[0]->first_name,
            'surname' => $userinfo[0]->last_name,
            'gender' => $userinfo[0]->sex?'male':'female',            
            'link_mailru' => $userinfo[0]->link
        );
        if(isset($userinfo[0]->has_pic)) {
            if($userinfo[0]->has_pic) {
                $this->oauth_profile['picture'] = $userinfo[0]->pic;
            }
        }       
        return $this->oauth_profile;
    }
}
