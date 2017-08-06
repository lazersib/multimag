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
            'unit_id' => 'Единица измерения',
            'ym_assign' => 'Идентификатор яндекс-маркета',
            'hidden' => 'Скрытый',
            'secret' => 'Секретный'
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
    
    public function getInputUnit_id($name, $value) {
        global $db;
        $ret = "<select name='$name'>";
        $ret .="<option value='null'>-- не задано --</option>";
        $res2 = $db->query("SELECT `id`, `name` FROM `class_unit_group` ORDER BY `id`");
        while ($nx2 = $res2->fetch_row()) {
            $ret .= "<optgroup label='" . html_out($nx2[1]) . "'>";
            $res = $db->query("SELECT `id`, `name`, `rus_name1` FROM `class_unit` WHERE `class_unit_group_id`='$nx2[0]'");
            while ($nx = $res->fetch_row()) {
                $i = ($nx[0] == $value)?" selected":'';
                $ret .="<option value='$nx[0]' $i>" . html_out("$nx[1] ($nx[2])") . "</option>";
            }
            $ret .= "</optgroup>";
        }
        $ret .="</select>";
        return $ret;
    }
    
    public function getFieldUnit_id($data) {
        $this->initUnitsList();
        if ($data['unit_id'] > 0) {
            return html_out($this->units_list[$data['unit_id']]);
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
        if($data['type'] && isset($this->types[$data['type']])) {
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
    
    public function getInputSecret($name, $value) {
        return $this->getCheckboxInput($name, 'Да', $value);
    }

    public function getFieldSecret($data) {
        return $data['secret'] ? "<b style='color:#f00'>Да</b>" : "<b style='color:#0c0'>Нет</b>";
    }
    
    protected function initGroupList() {
        if (isset($this->group_list)) {
            return;
        }
        $this->group_list = array();
        $res = $this->db_link->query("SELECT `id`, `name` FROM `doc_base_gparams` ORDER BY `id`");
        while ($line = $res->fetch_assoc()) {
            $this->group_list[$line['id']] = $line['name'];
        }
    }
    
    protected function initUnitsList() {
        if (isset($this->units_list)) {
            return;
        }
        $this->units_list = array();
        $res = $this->db_link->query("SELECT `id`, CONCAT(`name`, ', ', `rus_name1`) AS `name` FROM `class_unit` ORDER BY `id`");
        while ($line = $res->fetch_assoc()) {
            $this->units_list[$line['id']] = $line['name'];
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
