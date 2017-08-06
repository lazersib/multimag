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

/// Обработчик API запросов к объектам справочника собственных банков. Проверяет необходимиые привилегии перед осуществлением действий.
class mybank {

    protected function shortlist() {
        global $db;
        $bank_list = array();
        $res = $db->query("SELECT `num` AS `id`, `name`, `firm_id`
                FROM `doc_kassa`
                WHERE `ids`='bank'
                ORDER BY `num`");
        while ($line = $res->fetch_assoc()) {
            $bank_list[$line['id']] = $line;
        }
        return $bank_list;
    }

    public function dispatch($action, $data = null) {
        switch ($action) {
            case 'listnames':
                \acl::accessGuard('directory.bank', \acl::VIEW);
                $ldo = new \Models\LDO\banknames();
                return $ldo->getData();
            case 'shortlist':
                \acl::accessGuard('directory.bank', \acl::VIEW);
                return $this->shortlist();
            default:
                throw new \NotFoundException('Некорректное действие');
        }
    }

}
