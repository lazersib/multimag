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

class agentBankEditor extends \ListEditor {
    var $agent_id;
    var $can_delete = true;

    public function __construct($db_link) {
        parent::__construct($db_link);
        $this->print_name = 'Редактор банков агента';
        $this->table_name = 'agent_banks';
        $this->agent_id = 0;
        $this->can_delete = true;
    }

    /// Получить массив с именами колонок списка
    public function getColumnNames() {
        return array(
            'id' => 'id',
            'name' => 'Наименование',
            'bik' => 'Бик',            
            'ks' => 'К.счет',
            'rs' => 'Р.счет',
        );
    }

    /// Загрузить список всех элементов справочника
    public function loadList() {
        global $db;
        $a_id = intval($this->agent_id);
        $res = $db->query("SELECT `id`, `agent_id`, `name`, `bik`, `ks`, `rs`
            FROM `agent_banks`
            WHERE `agent_id`='$a_id'
            ORDER BY `id`");
        $this->list = array();
        while ($line = $res->fetch_assoc()) {
            $this->list[$line['id']] = $line;
        }
    }
    
    public function saveItem($id, $data) {
        $write_data = array();
        $col_names = $this->getColumnNames();
        foreach ($col_names as $col_id => $col_value) {
            if ($col_id == 'id') {
                continue;
            }
            if (isset($data[$col_id])) {
                if ($data[$col_id] === 'null') {
                    $write_data[$col_id] = 'NULL';
                } else {
                    $write_data[$col_id] = $data[$col_id];
                }
            } else {
                $write_data[$col_id] = 'NULL';
            }
        }
        $write_data['agent_id'] = intval($this->agent_id);
        if ($id) {
            \acl::accessGuard($this->acl_object_name, \acl::UPDATE);
            $old_data = $this->getItem($id);
            unset($old_data['id']);
            $this->db_link->updateA($this->table_name, $id, $write_data);
            unset($old_data['agent_id']);
            unset($write_data['agent_id']);
            $log_text = getCompareStr($old_data, $write_data);
            doc_log('UPDATE agent_bank ID:'.$id, $log_text, 'agent', intval($this->agent_id));
        } else {
            \acl::accessGuard($this->acl_object_name, \acl::CREATE);
            $id = $this->db_link->insertA($this->table_name, $write_data);
            $log_text = getCompareStr(array('name'=>'','bik'=>'','rs'=>'','ks'=>''), $write_data);
            doc_log('ADD agent_bank', $log_text, 'agent', intval($this->agent_id));
        }
        return $id;
    }
}
