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

class UnitsEditor extends \ListEditor {

    public function __construct($db_link) {
        parent::__construct($db_link);
        $this->print_name = 'Справочник единиц измерения (ОКЕИ)';
        $this->table_name = 'class_unit';
        $this->initGroupList();
        $this->initTypeList();
    }

    /// Получить массив с именами колонок списка
    public function getColumnNames() {
        return array(
            'id' => 'id',
            'number_code' => 'Код по ОКЕИ',
            'name' => 'Наименование',
            'rus_name1' => 'Русское условное обозначение',
            'eng_name1' => 'Международное условное обозначение',
            'rus_name2' => 'Русское кодовое обозначение',
            'eng_name2' => 'Международное кодовое обозначение',
            'class_unit_group_id' => 'Группа единиц',
            'class_unit_type_id' => 'Тип единицы',
            'visible' => 'Видимость',
            'comment' => 'Комментарий',
        );
    }
    
    public function getInputClass_unit_group_id($name, $value) {
        $ret = "<select name='$name'>";
        foreach ($this->group_list as $id => $group_name) {
            $sel = $value == $id ? ' selected' : '';
            $ret .="<option value='$id'{$sel}>$id: " . html_out($group_name) . "</option>";
        }
        $ret .="</select>";
        return $ret;
    }

    public function getFieldClass_unit_group_id($data) {
        if ($data['class_unit_group_id'] > 0) {
            return html_out($this->group_list[$data['class_unit_group_id']]);
        } else {
            return '-- не задано --';
        }
    }
    
    public function getInputClass_unit_type_id($name, $value) {
        $ret = "<select name='$name'>";
        foreach ($this->type_list as $id => $type_name) {
            $sel = $value == $id ? ' selected' : '';
            $ret .="<option value='$id'{$sel}>$id: " . html_out($type_name) . "</option>";
        }
        $ret .="</select>";
        return $ret;
    }

    public function getFieldClass_unit_type_id($data) {
        if ($data['class_unit_type_id'] > 0) {
            return html_out($this->type_list[$data['class_unit_type_id']]);
        } else {
            return '-- не задано --';
        }
    }
    
    public function getInputVisible($name, $value) {
        return $this->getCheckboxInput($name, 'Да', $value);
    }

    public function getFieldVisible($data) {
        return $data['visible'] ? "<b style='color:#0c0'>Да</b>" : "<b style='color:#f00'>Нет</b>";
    }
    
    protected function initGroupList() {
        if (isset($this->group_list)) {
            return;
        }
        $this->group_list = array();
        $res = $this->db_link->query("SELECT `id`, `name` FROM `class_unit_group` ORDER BY `id`");
        while ($line = $res->fetch_assoc()) {
            $this->group_list[$line['id']] = $line['name'];
        }
    }
    
    protected function initTypeList() {
        if (isset($this->type_list)) {
            return;
        }
        $this->type_list = array();
        $res = $this->db_link->query("SELECT `id`, `name` FROM `class_unit_type` ORDER BY `id`");
        while ($line = $res->fetch_assoc()) {
            $this->type_list[$line['id']] = $line['name'];
        }
    }
}
