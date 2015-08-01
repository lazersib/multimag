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

class vk extends \oauth\oauthplugin {
    
    function __construct() {
        parent::__construct();
        $this->server = 'vk';
    }
    
    function getName() {
        return "Войти через Вконтакте";
    }
        
    function init() {
        parent::init();        
        $this->client->oauth_version = "2.0";
        $this->client->dialog_url = "https://oauth.vk.com/authorize?client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&scope={SCOPE}&state={STATE}";
        $this->client->access_token_url = "https://oauth.vk.com/access_token";
        $this->client->scope = 'notify,wall,email,offline';
        $this->client->store_access_token_response = true;
    }
    
    function getUserOAuthProfile() {
        $userinfo = $wall = $email = '';
        if(isset($this->client->access_token_response['email'])) {
            $email = $this->client->access_token_response['email'];
        }

        $success = $this->client->CallAPI('https://api.vk.com/method/users.get', 
                'GET', array('fields'=>'sex,bdate,photo_max_orig,site,contacts,nickname'), array('FailOnAccessError' => true), $userinfo);
        if(!$success) {
            throw new \Exception('Ошибка получения данных пользователя!');
        }     
               
        $this->oauth_profile = array(
            'id' => $userinfo->response[0]->uid,
            'login' => 'id'.$userinfo->response[0]->uid,
            'email' => $email,
            'is_verifed_email' => 1,
            'birthday' => $userinfo->response[0]->bdate,
            'fullname' => $userinfo->response[0]->first_name.' '.$userinfo->response[0]->last_name,
            'name' => $userinfo->response[0]->first_name,
            'surname' => $userinfo->response[0]->last_name,
            'gender' => '',
            'picture' => $userinfo->response[0]->photo_max_orig,
        );
        if(isset($userinfo->response[0]->mobile_phone)) {
            $this->oauth_profile['phone'] = $userinfo->response[0]->mobile_phone;
        } elseif(isset($userinfo->response[0]->home_phone)) {
            $this->oauth_profile['phone'] = $userinfo->response[0]->home_phone;
        }
        switch($userinfo->response[0]->sex) {
            case 1:
                $this->oauth_profile['gender'] = 'female';
                break;
            case 2:
                $this->oauth_profile['gender'] = 'male';
                break;
        }        
        $this->oauth_profile['openid_list'] = array('http://vkontakteid.ru/'.$this->oauth_profile['login']);
        return $this->oauth_profile;
    }   
}
