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

namespace ListEditors;

class CdrContextEditor extends \ListEditor {

    public function __construct($db_link) {
        parent::__construct($db_link);
        $this->print_name = 'Справочник контекстов детализации вызовов';
        $this->table_name = 'asterisk_context';
        $this->directions = array(
            'in' => 'Входящий',
            'out' => 'Исходящий',
            'int' => 'Внутренний',
            'unk' => 'Неопределённый',
        );
    }

    /// Получить массив с именами колонок списка
    public function getColumnNames() {
        return array(
            'id' => 'id',
            'name' => 'Наименование',
            'direction' => 'Направление',
            'group_name' => 'Имя группы',
        );
    }

    protected function getFieldDirection($data) {
        if (isset($this->directions[$data['direction']])) {
            return html_out($this->directions[$data['direction']]);
        }
        return '';
    }

    protected function getInputDirection($name, $value) {
        $ret = "<select name='$name'>";
        foreach ($this->directions as $id => $item_name) {
            $selected = $id == $value ? ' selected' : '';
            $ret.= "<option value='{$id}'{$selected}>" . html_out($item_name) . "</option>";
        }
        $ret.= "</select>";
        return $ret;
    }

}
