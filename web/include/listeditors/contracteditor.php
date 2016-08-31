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

namespace ListEditors;

class contractEditor extends \ListEditor {

    public function __construct($db_link) {
        parent::__construct($db_link);
        $this->print_name = 'Редактор шаблонов договоров';
        $this->table_name = 'contract_templates';
    }

    /// Получить массив с именами колонок списка
    public function getColumnNames() {
        return array(
            'id' => 'id',
            'name' => 'Наименование',
            'text' => 'Текст',
        );
    }
    
    public function getInputText($name, $value) {
        $ret = "<textarea class='wikieditor' name='$name' rows='80' cols='10' width='90%'>";
        $ret .= html_out($value);
        $ret .="</textarea>";
        return $ret;
    }
    
    public function getFieldText($data) {        
        $text = mb_substr($data['text'], 0, 160);
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\n\n", "\n", $text);
        $text = str_replace("\n", '<br>', $text);
        return $text . '...';
    }
}
