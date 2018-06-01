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
namespace Models\LDO;

/// Класс списка статусов заявки покупателя
class zstatuses extends \Models\ListDataObject {

    /// @brief Получить данные
    public function getData() {
        $def_list = array(
            'new'=>'Новый',
            'err'=>'Ошибочный',
            'inproc'=>'В процессе',
            'ready'=>'Готов',
            'ok'=>'Отгружен'
        );
        $res_list = \cfg::get('doc', 'status_list', null);
        if (is_array($res_list)) {
            $res_list = array_merge($def_list, $res_list);
        } else {
            $res_list = $def_list;
        }
        return $res_list;
    }

}
