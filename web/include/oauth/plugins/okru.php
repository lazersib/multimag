<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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

class okru extends \oauth\oauthplugin {
    
    function __construct() {
        parent::__construct();
        $this->server = 'okru';
    }
    
    function getName() {
        return "Войти через Одноклассники";
    }
    
    function init() {
        parent::init();        
        $this->client->oauth_version = "2.0";
        $this->client->dialog_url = "http://www.odnoklassniki.ru/oauth/authorize?client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&scope={SCOPE}&response_type=code&state={STATE}";
        $this->client->access_token_url = "https://api.odnoklassniki.ru/oauth/token.do";
        $this->client->store_access_token_response = true;
        $this->client->scope = 'GET_EMAIL';
    }
    
    protected function createOAuthRegData($user_id) {
        global $db;
        settype($user_id,'int');
        $s_server = $db->real_escape_string($this->server);
        $s_token = $db->real_escape_string($this->client->access_token);
        $s_token_exp = $db->real_escape_string($this->client->access_token_expiry);
        $s_token_sec = $db->real_escape_string($this->client->refresh_token);
        
        $s_c_id = $db->real_escape_string($this->oauth_profile['id']);
        $s_c_login = $db->real_escape_string($this->oauth_profile['login']);        
        
        $db->query("INSERT INTO `users_oauth`"
            . " (`user_id`, `server`, `client_id`, `client_login`, `access_token`, `expire`, `creation`, `access_token_secret`)"
            . " VALUES"
            . " ('$user_id', '$s_server', '$s_c_id', '$s_c_login', '$s_token', '$s_token_exp', NOW(), '$s_token_sec')");
    }
    
    function getUserOAuthProfile() {
        global $CONFIG;
        $userinfo = '';
        $parameters = array(
            'method' => 'users.getCurrentUser',
            'fields' => 'user.uid,user.locale,user.first_name,user.last_name,user.name,user.gender,user.age,user.birthday,user.has_email,user.photo_id,user.url_profile,user.email,user.pic1024x768',
            'application_key' => $CONFIG['oauth'][$this->server]['public'],
        );
        ksort($parameters);
        $values = '';
        $url = 'http://api.ok.ru/fb.do?sig={signature}';
        foreach ($parameters as $key => $value) {
            $values .= $key . '=' . $value;
            $url .= '&' . $key . '=' . $value;
        }
        $values .= md5($this->client->access_token . $this->client->client_secret);
        
        $url = str_replace('{signature}',  md5($values), $url);
        $success = $this->client->CallAPI(
            $url, 'GET', array(), array('FailOnAccessError'=>true), $userinfo);
        if(!$success) {
            throw new \Exception('Ошибка получения данных пользователя!');
        }
        $this->oauth_profile = array(
            'id' => $userinfo->uid,
            'login' => 'okru'.$userinfo->uid,
            'email' => '',
            'is_verifed_email' => 0,
            'birthday' => $userinfo->birthday,
            'fullname' => $userinfo->name,
            'name' => $userinfo->first_name,
            'surname' => $userinfo->last_name,
            'gender' => $userinfo->gender,
        );
        if(isset($userinfo->email)) {
            if($userinfo->email) {
                $this->oauth_profile['email'] = $userinfo->email;
            }
        } 
        if(isset($userinfo->pic1024x768)) {
            if($userinfo->pic1024x768) {
                $this->oauth_profile['picture'] = $userinfo->pic1024x768;
            }
        }            
        return $this->oauth_profile;
    }
}
