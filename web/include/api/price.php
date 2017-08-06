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
namespace api; 

/// Обработчик API запросов к объектам справочника складов. Проверяет необходимиые привилегии перед осуществлением действий.
class price {

    protected function shortlist() {
        global $db;
        $store_list = array();
        $res = $db->query("SELECT `id`, `name`, `firm_id` FROM `doc_sklady` ORDER by `id` ASC");
        while ($line = $res->fetch_assoc()) {
            $store_list[$line['id']] = $line;
        }
        return $store_list;
    }

    public function dispatch($action, $data = null) {
        switch ($action) {
            case 'listnames':
                //\acl::accessGuard('directory.storelist', \acl::VIEW);
                $ldo = new \Models\LDO\pricenames();
                return $ldo->getData();
            case 'shortlist':
                //\acl::accessGuard('directory.storelist', \acl::VIEW);
                return $this->shortlist();
            default:
                throw new \NotFoundException('Некорректное действие');
        }
    }

}
