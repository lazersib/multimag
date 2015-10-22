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

namespace acl\directory;

class main extends \acl\aclContainer {
    protected $name = "Справочники";
    
    public function __construct() {
        $this->list = array(
            'agent' => array(
                "name" => "Агенты",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE
            ),
            'agent.ext' => array(
                "name" => "Агенты: дата сверки и ответственный",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE
            ),
            'attorney' => array(
                "name" => "Доверенные лица",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE
            ),
            'goods' => array(
                "name" => "Товары и услуги",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE
            ),            
            'bank' => array(
                "name" => "Банки",
                "mask" => \acl::VIEW | \acl::CREATE  | \acl::UPDATE
            ),
            'cash' => array(
                "name" => "Кассы",
                "mask" => \acl::VIEW | \acl::CREATE  | \acl::UPDATE
            ),
            'firm' => array(
                "name" => "Собственные организации",
                "mask" => \acl::VIEW | \acl::CREATE  | \acl::UPDATE
            ),
            'ctype' => array(
                "name" => "Виды доходов",
                "mask" => \acl::VIEW | \acl::CREATE  | \acl::UPDATE
            ),
            'dtype' => array(
                "name" => "Виды расходов",
                "mask" => \acl::VIEW | \acl::CREATE  | \acl::UPDATE
            ),
            'storelist' => array(
                "name" => "Справочник складов",
                "mask" => \acl::VIEW | \acl::CREATE  | \acl::UPDATE
            ),
            'account' => array(
                "name" => "Бухгалтерские счета",
                "mask" => \acl::VIEW | \acl::CREATE  | \acl::UPDATE
            ),
            'postype' => array(
                "name" => "Типы складских наименований",
                "mask" => \acl::VIEW | \acl::CREATE  | \acl::UPDATE
            ),
            'posparam' => array(
                "name" => "Параметры складских наименований",
                "mask" => \acl::VIEW | \acl::CREATE  | \acl::UPDATE
            ),
            'unit' => array(
                "name" => "Единицы измерения",
                "mask" => \acl::VIEW | \acl::CREATE  | \acl::UPDATE
            ),
        );
    }
    
}
