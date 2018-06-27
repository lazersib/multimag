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

class KassListEditor extends \ListEditor {

    protected $firm_list;
    protected $cr_list;

    public function __construct($db_link) {
        parent::__construct($db_link);
        $this->print_name = 'Справочник касс';
        $this->initFirmList();
        $this->initCashRegisterList();
    }

    /// Получить массив с именами колонок списка
    public function getColumnNames() {
        return array(
            'id' => 'id',
            'name' => 'Наименование',
            'firm_id' => 'Организация',
            'cash_register_id' => 'Кассовый аппарат'
        );
    }

    /// Загрузить список всех элементов справочника
    public function loadList() {
        $res = $this->db_link->query("SELECT `num` AS `id`, `name`, `firm_id`, `cash_register_id`
            FROM `doc_kassa`
            WHERE `ids`='kassa'
            ORDER BY `num`");
        $this->list = array();
        while ($line = $res->fetch_assoc()) {
            $this->list[$line['id']] = $line;
        }
    }

    public function getItem($id) {
        global $db;
        settype($id, 'int');
        $res = $db->query("SELECT `num` AS `id`, `name`, `bik`, `rs`, `ks`, `firm_id`, `cash_register_id`
            FROM `doc_kassa`
            WHERE `ids`='kassa' AND `num`=$id");
        if ($res->num_rows) {
            return $res->fetch_assoc();
        } else {
            return null;
        }
    }

    public function getInputFirm_id($name, $value) {
        $ret = "<select name='$name'>";
        $ret .= "<option value='null'>-- не задано --</option>";
        foreach ($this->firm_list as $id => $firm_name) {
            $sel = $value == $id ? ' selected' : '';
            $ret .= "<option value='$id'{$sel}>$id: " . html_out($firm_name) . "</option>";
        }
        $ret .= "</select>";
        return $ret;
    }

    public function getFieldFirm_id($data) {
        if ($data['firm_id'] > 0) {
            return html_out($this->firm_list[$data['firm_id']]);
        } else {
            return '-- не задано --';
        }
    }
    
    public function getFieldcash_register_id($data) {
        if ($data['cash_register_id'] > 0) {
            return html_out($this->cr_list[$data['cash_register_id']]);
        } else {
            return '-- не задано --';
        }
    }
    
    public function getInputcash_register_id($name, $value) {
        $ret = "<select name='$name'>";
        $ret .= "<option value='null'>-- не задано --</option>";
        foreach ($this->cr_list as $id => $cr_name) {
            $sel = $value == $id ? ' selected' : '';
            $ret .= "<option value='$id'{$sel}>$id: " . html_out($cr_name) . "</option>";
        }
        $ret .= "</select>";
        return $ret;
    }

    public function saveItem($id, $data) {
        settype($id, 'int');
        $name_sql = $this->db_link->real_escape_string($data['name']);
        if ($data['firm_id'] == 'null') {
            $firm_id = 'NULL';
        } else {
            $firm_id = intval($data['firm_id']);
        }
        if ($data['cash_register_id'] == 'null') {
            $cash_register_id = 'NULL';
        } else {
            $cash_register_id = intval($data['cash_register_id']);
        }
        if ($id) {
            $res = $this->db_link->query("SELECT `num` FROM `doc_kassa` WHERE `ids`='kassa' AND `num`='$id'");
            if ($res->num_rows) {
                $this->db_link->query("UPDATE `doc_kassa` SET `name`='$name_sql', `firm_id`=$firm_id, `cash_register_id`=$cash_register_id
				WHERE `ids`='kassa' AND `num`=$id");
                return $id;
            }
        }
        $res = $this->db_link->query("SELECT `num` FROM `doc_kassa` WHERE `ids`='kassa' ORDER BY `num` DESC LIMIT 1");
        if ($res->num_rows) {
            $line = $res->fetch_row();
            $id = $line[0] + 1;
        } else {
            $id = 1;
        }
        $this->db_link->query("INSERT INTO `doc_kassa` (`ids`, `num`, `name`, `firm_id`, `cash_register_id`) " .
                "VALUES ('kassa', $id, '$name_sql', '$firm_id', '$cash_register_id')");
        return $id;
    }

    protected function initFirmList() {
        if (isset($this->firm_list)) {
            return;
        }
        $ldo = new \Models\LDO\firmnames();
        $this->firm_list = $ldo->getData();
    }

    protected function initCashRegisterList() {
        if (isset($this->cr_list)) {
            return;
        }
        $ldo = new \Models\LDO\CashRegisterNames();
        $this->cr_list = $ldo->getData();
    }

}
