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

namespace acl\directory;

class main extends \acl\aclContainer {
    protected $name = "Справочники";
    
    public function __construct() {
        global $db;
        $list1 = array(
            'agent' => array(
                "name" => "Агенты: доступ к справочнику и редактирование контактов",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE | \acl::DELETE
            ),
            'agent.global' => array(
            "name" => 'Агенты: Глобальные разрешения',
            "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE,
            ),
            'agent.ingroup.0' => array(
            "name" => 'Агенты в группе &quot;0&quot;',
            "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE,
            ),
        );
        $res = $db->query("SELECT `id`, `name` FROM `doc_agent_group` ORDER BY `name`");
        while($line = $res->fetch_assoc()) {
            $list1['agent.ingroup.'.$line['id']] = array(
                "name" => 'Агенты в группе &quot;'.$line['id'].':'.$line['name'].'&quot;',
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE,
            );
        }
        $list2 = array(
            'agent.groups' => array(
                "name" => 'Агенты: Справочник групп',
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE,
            ),
            'agent.ext' => array(
                "name" => "Агенты: Дата сверки и ответственный",
                "mask" => \acl::UPDATE
            ),
            'attorney' => array(
                "name" => "Доверенные лица",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE
            ),
            'goods' => array(
                "name" => "Товары и услуги",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE
            ),
            'goods.groups' => array(
                "name" => "Товары и услуги: Справочник групп",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE
            ),
            'goods.secfields' => array(
                "name" => "Товары и услуги: Секретные поля",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE
            ),  
            'goods.parts' => array(
                "name" => "Товары и услуги: Комплектующие",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE | \acl::DELETE
            ),
            'goods.approve' => array(
                "name" => "Товары и услуги: Подтверждение правильности",
                "mask" => \acl::VIEW | \acl::UPDATE
            ),
            'bank' => array(
                "name" => "Банки",
                "mask" => \acl::VIEW | \acl::CREATE  | \acl::UPDATE
            ),
            'cash' => array(
                "name" => "Кассы",
                "mask" => \acl::VIEW | \acl::CREATE  | \acl::UPDATE
            ),
            'contract_templates' => array(
                "name" => "Шаблоны договоров",
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
            'pgroup' => array(
                "name" => "Группы параметров складских наименований",
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
            'builder' => array(
                "name" => "Сборщики на производстве",
                "mask" => \acl::VIEW | \acl::CREATE  | \acl::UPDATE
            ),
            'region' => array(
                "name" => "Регионы доставки",
                "mask" => \acl::VIEW | \acl::CREATE  | \acl::UPDATE
            ),
            'shiptype' => array(
                "name" => "Способы доставки",
                "mask" => \acl::VIEW | \acl::CREATE  | \acl::UPDATE
            ),
        );
        $this->list = array_merge($list1, $list2);
    }
    
}
