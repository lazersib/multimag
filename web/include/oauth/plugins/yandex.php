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

// Yandex developer page: https://oauth.yandex.ru/client/new. The callback URL must be $client->redirect_uri

namespace oauth\plugins;

class yandex extends \oauth\oauthplugin {
    
    function __construct() {
        parent::__construct();
        $this->server = 'yandex';
    }
    
    function getName() {
        return "Войти через Яндекс";
    }
    
    function init() {
        parent::init();        
        $this->client->oauth_version = "2.0";
        $this->client->dialog_url = "https://oauth.yandex.ru/authorize?response_type=code&client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&state={STATE}&scope={SCOPE}";
        $this->client->access_token_url = "https://oauth.yandex.ru/token";
        $this->client->scope = '';
    }
    
    function getUserOAuthProfile() {
        $userinfo = '';
        $success = $this->client->CallAPI('https://login.yandex.ru/info?format=json&with_openid_identity=1', 
                'GET', array(), array('FailOnAccessError' => true), $userinfo);
        if(!$success) {
            throw new \Exception('Ошибка получения данных пользователя!');
        }
        $this->oauth_profile = array(
            'id' => $userinfo->id,
            'login' => $userinfo->login,
            'email' => $userinfo->default_email,
            'is_verifed_email' => 1,
            'birthday' => $userinfo->birthday,
            'fullname' => $userinfo->real_name,
            'name' => $userinfo->first_name,
            'surname' => $userinfo->last_name,
            'gender' => $userinfo->sex,
            'picture' => 'https://avatars.yandex.net/get-yapic/'.$userinfo->id.'/islands-200',
        );
        if(isset($userinfo->openid_identities)) {
            $this->oauth_profile['openid_list'] = $userinfo->openid_identities;
        }     
        
        return $this->oauth_profile;
    }
        
    function findUserRegDataOfEmail() {
        global $db;
        $s_email = $db->real_escape_string($this->oauth_profile['email']);
        $sql = "`reg_email` = '$s_email'";
        $ya_domains = array('ya.ru', 'yandex.ru', 'yandex.by', 'yandex.ua', 'yandex.kz', 'yandex.com');
        foreach($ya_domains as $d) {
            $s_email = $db->real_escape_string($this->oauth_profile['login'].'@'.$d);
            $sql .= " OR `reg_email` = '$s_email'";            
        }
        $res = $db->query("SELECT * FROM `users` WHERE ".$sql);
        if($res->num_rows) {
            $this->user_data = $res->fetch_array();
            return $this->user_data;
        }
        return false;
    }
    
}
