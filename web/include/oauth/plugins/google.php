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

class google extends \oauth\oauthplugin {
    
    function __construct() {
        parent::__construct();
        $this->server = 'google';
    }
    
    function getName() {
        return "Войти через Google";
    }
       
    function init() {
        parent::init();
        $this->client->oauth_version = "2.0";  
        $this->client->dialog_url = 'https://accounts.google.com/o/oauth2/auth?response_type=code&'
            . 'client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&scope={SCOPE}&state={STATE}&openid.realm='.$this->realm;
        $this->client->access_token_url = "https://accounts.google.com/o/oauth2/token";
        $this->client->scope = 'email profile';
    }
    
    function getUserOAuthProfile() {
        $userinfo = $openid_info = '';
        $success = $this->client->CallAPI('https://www.googleapis.com/oauth2/v3/userinfo', 
                'GET', array(), array('FailOnAccessError' => true), $userinfo);
        if(!$success) {
            throw new \Exception('Ошибка получения данных пользователя!'.$this->client->error);
        }
        $this->oauth_profile = array(
            'id' => $userinfo->sub,
            'login' => $userinfo->email,
            'email' => $userinfo->email,
            'is_verifed_email' => $userinfo->email_verified,
            'fullname' => $userinfo->name,
            'name' => $userinfo->given_name,
            'surname' => $userinfo->family_name,
            'gender' => $userinfo->gender,
            'picture' => $userinfo->picture,
            'language' => $userinfo->locale,
        );
        $success = $this->client->CallAPI('https://www.googleapis.com/plus/v1/people/me/openIdConnect', 
                'GET', array(), array('FailOnAccessError' => true), $openid_info);
        if(!$success) {
            throw new \Exception('Ошибка получения данных пользователя!'.$this->client->error);
        }
        if(isset($openid_info->openid_id)) {
            $this->oauth_profile['openid_list'] = array(
                $openid_info->openid_id
            );
        }     
        
        return $this->oauth_profile;
    }    
}
