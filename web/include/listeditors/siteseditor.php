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

class SitesEditor extends \ListEditor {

    public function __construct($db_link) {
        parent::__construct($db_link);
        $this->print_name = 'Справочник сайтов';
        $this->table_name = 'sites';
        $this->initList();
    }

    /// Получить массив с именами колонок списка
    public function getColumnNames() {
        return array(
            'id' => 'id',
            'name' => 'Наименование',
            'short_name' => 'Краткое название',
            'display_name' => 'Отображаемое наименование',
            'email' => 'email ответственного',
            'jid' => 'jid ответственного',
            'default_firm_id' => 'Организация по умолчанию',
            'default_bank_id' => 'Банк по умолчанию',
            'default_cash_id' => 'Касса по умолчанию',
            'default_agent_id' => 'Агент по умолчанию',
            'default_store_id' => 'Склад по умолчанию',
            'default_site' => 'Основной сайт',
            'site_store_id' => 'Склад отображения остатков на сайте',
        );
    }
    
    public function getInputDefault_firm_id($name, $value) {
        $ret = "<select name='$name'>";
        foreach ($this->firm_list as $id => $item_name) {
            $sel = $value == $id ? ' selected' : '';
            $ret .="<option value='$id'{$sel}>$id: " . html_out($item_name) . "</option>";
        }
        $ret .="</select>";
        return $ret;
    }
    
    public function getInputDefault_bank_id($name, $value) {
        $ret = "<select name='$name'>";
        foreach ($this->bank_list as $id => $item_name) {
            $sel = $value == $id ? ' selected' : '';
            $ret .="<option value='$id'{$sel}>$id: " . html_out($item_name) . "</option>";
        }
        $ret .="</select>";
        return $ret;
    }
    
    public function getInputDefault_cash_id($name, $value) {
        $ret = "<select name='$name'>";
        foreach ($this->cash_list as $id => $item_name) {
            $sel = $value == $id ? ' selected' : '';
            $ret .="<option value='$id'{$sel}>$id: " . html_out($item_name) . "</option>";
        }
        $ret .="</select>";
        return $ret;
    }

    public function getInputDefault_store_id($name, $value) {
        $ret = "<select name='$name'>";
        foreach ($this->store_list as $id => $item_name) {
            $sel = $value == $id ? ' selected' : '';
            $ret .="<option value='$id'{$sel}>$id: " . html_out($item_name) . "</option>";
        }
        $ret .="</select>";
        return $ret;
    }
    
    public function getFieldDefault_firm_id($data) {
        if ($data['default_firm_id'] > 0) {
            return html_out($this->firm_list[$data['default_firm_id']]);
        } else {
            return '-- не задано --';
        }
    }
    
    public function getFieldDefault_bank_id($data) {
        if ($data['default_bank_id'] > 0) {
            return html_out($this->bank_list[$data['default_bank_id']]);
        } else {
            return '-- не задано --';
        }
    }
    
    public function getFieldDefault_cash_id($data) {
        if ($data['default_cash_id'] > 0) {
            return html_out($this->cash_list[$data['default_cash_id']]);
        } else {
            return '-- не задано --';
        }
    }
    
    public function getFieldDefault_agent_id($data) {
        if ($data['default_agent_id'] > 0) {
            return html_out($this->agent_list[$data['default_agent_id']]);
        } else {
            return '-- не задано --';
        }
    }
    
    public function getFieldDefault_store_id($data) {
        if ($data['default_store_id'] > 0) {
            return html_out($this->store_list[$data['default_store_id']]);
        } else {
            return '-- не задано --';
        }
    }
    
    public function getInputDefault_site($name, $value) {
        return $this->getCheckboxInput($name, 'Да', $value);
    }

    public function getFieldDefault_site($data) {
        return $data['default_site'] ? "<b style='color:#0c0'>Да</b>" : "<b style='color:#f00'>Нет</b>";
    }
    
    protected function initList() {
        if (isset($this->firm_list)) {
            return;
        }
        $ldo = new \Models\LDO\firmnames();
        $this->firm_list = $ldo->getData();
        
        $ldo = new \Models\LDO\banknames();
        $this->bank_list = $ldo->getData();
        
        $ldo = new \Models\LDO\kassnames();
        $this->cash_list = $ldo->getData();
        
        $ldo = new \Models\LDO\agentnames();
        $this->agent_list = $ldo->getData();
        
        $ldo = new \Models\LDO\skladnames();
        $this->store_list = $ldo->getData();
    }
}
