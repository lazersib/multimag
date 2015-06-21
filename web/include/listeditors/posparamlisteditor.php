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
namespace ListEditors;

class PosParamListEditor extends \ListEditor {

    public function __construct($db_link) {
        parent::__construct($db_link);
        $this->print_name = 'Справочник свойств номенклатуры';
        $this->table_name = 'doc_base_params';
        $this->initGroupList();
        $this->types = array('text' => 'Текстовый', 'int' => 'Целый', 'bool' => 'Логический', 'float' => 'С точкой');
    }

    /// Получить массив с именами колонок списка
    public function getColumnNames() {
        return array(
            'id' => 'id',
            'group_id' => 'Группа',
            'name' => 'Наименование',
            'codename' => 'Кодовое обозначение',
            'type' => 'Тип данных',
            'ym_assign' => 'Идентификатор яндекс-маркета',
            'hidden' => 'Скрытый'
        );
    }


    public function getInputGroup_id($name, $value) {
        $ret = "<select name='$name'>";
        $ret .="<option value='null'>-- не задано --</option>";
        foreach ($this->group_list as $id => $group_name) {
            $sel = $value == $id ? ' selected' : '';
            $ret .="<option value='$id'{$sel}>$id: " . html_out($group_name) . "</option>";
        }
        $ret .="</select>";
        return $ret;
    }

    public function getFieldGroup_id($data) {
        if ($data['group_id'] > 0) {
            return html_out($this->group_list[$data['group_id']]);
        } else {
            return '-- не задано --';
        }
    }

    public function getInputType($name, $value) {
        $ret = "<select name='$name'>";
        foreach ($this->types as $id => $typename) {
            $sel = $value == $id ? ' selected' : '';
            $ret .="<option value='$id'{$sel}>$id: " . html_out($typename) . "</option>";
        }
        $ret .="</select>";
        return $ret;
    }

    public function getFieldType($data) {
        if ($data['type']) {
            return html_out($this->types[$data['type']]);
        } else {
            return '-- не задано --';
        }
    }
    
    public function getInputHidden($name, $value) {
        return $this->getCheckboxInput($name, 'Да', $value);
    }

    public function getFieldHidden($data) {
        return $data['hidden'] ? "<b style='color:#f00'>Да</b>" : "<b style='color:#0c0'>Нет</b>";
    }
    
    protected function initGroupList() {
        if (isset($this->firm_list)) {
            return;
        }
        $this->group_list = array();
        $res = $this->db_link->query("SELECT `id`, `name` FROM `doc_base_gparams` ORDER BY `id`");
        while ($line = $res->fetch_assoc()) {
            $this->group_list[$line['id']] = $line['name'];
        }
    }
    
    /// Записать в базу строку справочника
    public function saveItem($id, $data) {
        if(!isset($data['hidden'])) {
            $data['hidden'] = 0;
        }        
        return parent::saveItem($id, $data);
    }
}
