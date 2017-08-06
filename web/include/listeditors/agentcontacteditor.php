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

class agentContactEditor extends \ListEditor {
    var $agent_id;
    var $types_list = array(
        'phone'=>'Телефон',
        'email'=>'Эл.почта',
        'jid'=>'Jabber/XMPP id',
        'icq' => 'ICQ UIN',
        'skype'=>'Skype логин',
        'mra'=>'MailRu агент'
    );
    var $context_list = array(
        'home'  => 'Домашний',
        'work'  => 'Рабочий',
        'mobile'=> 'Мобильный'
    );

    public function __construct($db_link) {
        parent::__construct($db_link);
        $this->print_name = 'Редактор контактов агента';
        $this->table_name = 'agent_contacts';
        $this->agent_id = 0;
        $this->can_delete = true;
    }

    /// Получить массив с именами колонок списка
    public function getColumnNames() {
        return array(
            'id' => 'id',
            'context' => 'Класс',
            'type' => 'Вид',
            'value' => 'Значение',
            'person_name' => 'Контактное лицо',
            'person_post' => 'Должность',
            'for_sms' => 'Для СМС',
            'for_fax' => 'Для факсов',
            'no_ads' => 'Запрет рассылок',            
        );
    }

    /// Загрузить список всех элементов справочника
    public function loadList() {
        global $db;
        $a_id = intval($this->agent_id);
        $res = $db->query("SELECT `id`, `agent_id`, `context`, `type`, `value`, `person_name`, `person_post`, `for_sms`, `for_fax`, `no_ads`
            FROM {$this->table_name}
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
                $write_data[$col_id] = $data[$col_id];
            } else {
                $write_data[$col_id] = 0;
            }
        }
        if($write_data['type']=='phone') {
            $phone = normalizePhone($write_data['value']);
            if($phone) {
                $write_data['value'] = $phone;
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
            doc_log('UPDATE agent_contact ID:'.$id, $log_text, 'agent', intval($this->agent_id));
        } else {
            \acl::accessGuard($this->acl_object_name, \acl::CREATE);
            $id = $this->db_link->insertA($this->table_name, $write_data);
            $log_text = getCompareStr(array('context'=>'','type'=>'','value'=>'','no_ads'=>'','for_sms'=>'','for_fax'=>''), $write_data);
            doc_log('ADD agent_contact', $log_text, 'agent', intval($this->agent_id));
        }
        return $id;
    }
    
    function getInputType($name, $value) {
        $ret = "<select name='$name'>";
         foreach($this->types_list as $id => $item_name) {
                $sel = $value==$id?' selected':'';
                $ret .="<option value='$id'{$sel}>".html_out($item_name)." (".$id.")</option>";
        }
        $ret .="</select>";
        return $ret;
    }
    
    function getInputContext($name, $value) {
        $ret = "<select name='$name'>";
         foreach($this->context_list as $id => $item_name) {
                $sel = $value==$id?' selected':'';
                $ret .="<option value='$id'{$sel}>".html_out($item_name)." (".$id.")</option>";
        }
        $ret .="</select>";
        return $ret;
    }
    
    public function getInputFor_sms($name, $value) {
        return $this->getCheckboxInput($name, 'Да', $value);
    }
    
    public function getInputFor_Fax($name, $value) {
        return $this->getCheckboxInput($name, 'Да', $value);
    }
    
    public function getInputNo_Ads($name, $value) {
        return $this->getCheckboxInput($name, 'Да', $value);
    }
    
    public function getFieldType($data) {
        if(isset($this->types_list[$data['type']])) {
            return $this->types_list[$data['type']];
        } 
        else {
            return $data['type'];
        }
    }
        
    public function getFieldContext($data) {
        if(isset($this->context_list[$data['context']])) {
            return $this->context_list[$data['context']];
        } 
        else {
            return $data['context'];
        }
    }
    
    public function getFieldFor_sms($data) {
        return $data['for_sms'] ? "<b style='color:#0c0'>Да</b>" : "Нет";
    }
    
    public function getFieldFor_Fax($data) {
        return $data['for_fax'] ? "<b style='color:#0c0'>Да</b>" : "Нет";
    }
    
    public function getFieldNo_Ads($data) {
        return $data['no_ads'] ? "<b style='color:#f00'>Да</b>" : "Нет";
    }
    
    public function getFieldValue($data) {
        if($data['type']=='email') {
            return "<a href='mailto:".html_out($data['value'])."'>".html_out($data['value'])."</a>";
        }
        return $data['value'];
    }
}
