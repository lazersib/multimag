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

class StoresListEditor extends \ListEditor {

    public function __construct($db_link) {
        parent::__construct($db_link);
        $this->print_name = 'Справочник складов';
        $this->table_name = 'doc_sklady';
        $this->initFirmList();
    }

    /// Получить массив с именами колонок списка
    public function getColumnNames() {
        return array(
            'id' => 'id',
            'name' => 'Наименование',
            'dnc' => 'Не контролировать остатки',
            'firm_id' => 'Организация'
        );
    }

    public function getInputDnc($name, $value) {
        return $this->getCheckboxInput($name, 'Да', $value);
    }

    public function getFieldDnc($data) {
        return $data['dnc'] ? "<b style='color:#f00'>Да</b>" : "<b style='color:#0c0'>Нет</b>";
    }

    public function getInputFirm_id($name, $value) {
        $ret = "<select name='$name'>";
        $ret .="<option value='null'>-- не задано --</option>";
        foreach ($this->firm_list as $id => $firm_name) {
            $sel = $value == $id ? ' selected' : '';
            $ret .="<option value='$id'{$sel}>$id: " . html_out($firm_name) . "</option>";
        }
        $ret .="</select>";
        return $ret;
    }

    public function getFieldFirm_id($data) {
        if ($data['firm_id'] > 0) {
            return html_out($this->firm_list[$data['firm_id']]);
        } else {
            return '-- не задано --';
        }
    }

    protected function initFirmList() {
        if (isset($this->firm_list)) {
            return;
        }
        $this->firm_list = array();
        $res = $this->db_link->query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `id`");
        while ($line = $res->fetch_assoc()) {
            $this->firm_list[$line['id']] = $line['firm_name'];
        }
    }
    
    /// Записать в базу строку справочника
    public function saveItem($id, $data) {
        if(!isset($data['dnc'])) {
            $data['dnc'] = 0;
        }        
        return parent::saveItem($id, $data);
    }
}
