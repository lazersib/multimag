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

namespace acl\admin;

class main extends \acl\aclContainer {
    protected $name = "Администрирование";
    
    public function __construct() {
        $this->list = array(
            'acl' => array(
                "name" => "Привилегии",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE | \acl::DELETE
            ),
            'users' => array(
                "name" => "Пользователи",
                "mask" => \acl::VIEW | \acl::UPDATE | \acl::DELETE
            ),
            'comments' => array(
                "name" => "Комментарии",
                "mask" => \acl::VIEW | \acl::UPDATE | \acl::DELETE
            ),
            'mail' => array(
                "name" => "Настройка почтовых ящиков и алиасов",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE | \acl::DELETE
            ),
            'asynctask' => array(
                "name" => "Асинхронные задачи",
                "mask" => \acl::VIEW | \acl::APPLY
            ),            
            'accesslog' => array(
                "name" => "Журнал посещений",
                "mask" => \acl::VIEW
            ),
            'browserlog' => array(
                "name" => "Статистика броузеров",
                "mask" => \acl::VIEW
            ),
            'errorlog' => array(
                "name" => "Журнал ошибок",
                "mask" => \acl::VIEW
            ),
            'sites' => array(
                "name" => "Администрирование сайтов",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE | \acl::DELETE
            ),
        );
    }
    
}
