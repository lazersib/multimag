<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
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

namespace acl\service;

class main extends \acl\aclContainer {
    protected $name = "Служебные";
    
    public function __construct() {
        $this->list = array(
            'wikipage' => array(
                "name" => "Внутренняя база знаний",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE | \acl::DELETE
            ),
            'files' => array(
                "name" => "Внутренние прикреплённые файлы",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE | \acl::DELETE
            ),
            'pricean' => array(
                "name" => "Анализатор прайсов",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE | \acl::DELETE
            ),
            'tickets' => array(
                "name" => "Задания",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE | \acl::DELETE
            ),
            'intkb' => array(
                "name" => "Внутренняя база знаний",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE | \acl::DELETE
            ),
            'callrequestlog' => array(
                "name" => "Журнал запрошенных звонков",
                "mask" => \acl::VIEW | \acl::UPDATE
            ),
            '1csync' => array(
                "name" => "Синхронизация с 1С",
                "mask" => \acl::VIEW | \acl::UPDATE | \acl::APPLY
            ),
            'cdr' => array(
                "name" => "Статистика телефонной связи",
                "mask" => \acl::VIEW |\acl::CREATE | \acl::UPDATE | \acl::DELETE
            ),
            'docservice' => array(
                "name" => "Служебные функции документов",
                "mask" => \acl::VIEW |\acl::CREATE | \acl::UPDATE | \acl::DELETE
            ),
            'scripts' => array(
                "name" => "Сценарии и операции",
                "mask" => \acl::VIEW | \acl::APPLY
            ),
            'scripts' => array(
                "name" => "Сценарии и операции",
                "mask" => \acl::VIEW | \acl::APPLY
            ),
            'sendprice' => array(
                "name" => "Управление рассылкой прайс-листов",
                "mask" => \acl::VIEW |\acl::CREATE | \acl::UPDATE | \acl::DELETE
            ),
            'images' => array(
                "name" => "Управление изображениями",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE
            ),
            'factory' => array(
                "name" => "Управление производством",
                "mask" => \acl::VIEW | \acl::UPDATE
            ),
            'doclist' => array(
                "name" => "Журнал документов",
                "mask" => \acl::VIEW
            ),
            'orders' => array(
                "name" => "Документы в работе",
                "mask" => \acl::VIEW
            ),
            'changelog' => array(
                "name" => "Список изменений в системе",
                "mask" => \acl::VIEW
            ),
        );
    }
    
}
