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
namespace api; 

/// Обработчик API запросов к объектам справочника сотрудников. Проверяет необходимиые привилегии перед осуществлением действий.
class worker {

    public function dispatch($action, $data = null) {
        switch ($action) {
            case 'listnames':
                \acl::accessGuard('service.doclist', \acl::VIEW);
                $ldo = new \Models\LDO\workernames();
                return $ldo->getData();
            default:
                throw new \NotFoundException('Некорректное действие');
        }
    }

}
