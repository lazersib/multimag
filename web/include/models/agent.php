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
//
namespace models;

/// @brief Класс работы с данными агента
class agent {
    var $agents_tn = 'doc_agent';
    var $contacts_tn = 'agent_contacts';
    protected $data = array();
    protected $parsed_contacts = array();
    
    protected $fields = array('group', 'name', 'type', 'fullname', 'adres', 'real_address', 'inn', 'kpp', 'okved', 'okpo', 'ogrn'
        , 'pasp_num', 'pasp_date', 'pasp_kem', 'comment', 'responsible', 'data_sverki'
        , 'leader_name', 'leader_post', 'leader_reason', 'leader_name_r', 'leader_post_r', 'leader_reason_r'
        , 'dishonest', 'p_agent', 'price_id', 'no_retail_prices', 'no_bulk_prices', 'no_bonuses', 'region');
    protected $contact_fields = array('context', 'type', 'value', 'person_name', 'person_post', 'for_sms', 'for_fax', 'no_ads', );
    
    /// Конструктор
    public function __construct($agent_id = null) {
        if($agent_id) {
            $this->load($agent_id);
        }
    }
	
    /// Загрузить данные агента
    public function load($agent_id) {
        global $db;
        settype($agent_id, 'int');
        $this->data = $db->selectRow($this->agents_tn, $agent_id);
        if(!$this->data) {
            return false;
        }        
        $contacts = array();
        $res = $db->query("SELECT * FROM `{$this->contacts_tn}` WHERE `agent_id`='$agent_id'");
        while($line = $res->fetch_assoc()) {
            $contacts[$line['id']] = $line;
        }
        $this->data['contacts'] = $contacts;
        $this->parseContacts();
    }
    
    /// Создать агента на основе заданного набора данных
    public function create($data) {
        global $db;
        $new_agent_info = array_fill_keys($this->fields, '');
        $new_agent_info = array_intersect_key($data, $new_agent_info);
        $new_group = $data['group'] = isset($data['group'])?intval($data['group']):0;
        if (\cfg::get('agents', 'leaf_only')) {
            $res = $db->query("SELECT `id` FROM `doc_agent_group` WHERE `pid`='$new_group'");
            if ($res->num_rows) {
                throw new \Exception("Запись агента возможна только в конечную группу!");
            }
        }
        $agent_id = $db->insertA('doc_agent', $new_agent_info);
        if(isset($data['contacts']) && is_array($data['contacts'])) {
            foreach($data['contacts'] as $contact) {
                $contact_info = array_fill_keys($this->contact_fields, '');
                $contact_info = array_intersect_key($contact, $contact_info);
                var_dump($contact_info);
                if($contact_info['type']=='phone') {
                    $phone = normalizePhone($contact_info['value']);
                    if($phone) {
                        $contact_info['value'] = $phone;
                    }
                }
                $contact_info['agent_id'] = $agent_id;
                $db->insertA('agent_contacts', $contact_info);
            }
        }
        $this->load($agent_id);
        return $agent_id;
    }
    
    public function getData() {
        return $this->data;
    }

    public function __get($name) {
        if(isset($this->data[$name])) {
            return $this->data[$name];
        }
        return null;
        //throw new \LogicException($name. ' not found in '.get_class());
    }
    
    public function getParsedContacts() {
        return $this->parsed_contacts;
    }
    
    public function getPhone() {
        if(isset($this->parsed_contacts['phone'])) {
            return $this->parsed_contacts['phone'];
        }
    }
    
    public function getSMSPhone() {
        if(isset($this->parsed_contacts['sms_phone'])) {
            return $this->parsed_contacts['sms_phone'];
        }
    }
    
    public function getEmail() {
        if(isset($this->parsed_contacts['email'])) {
            return $this->parsed_contacts['email'];
        }
    }
    
    public function getFaxNum() {
        if(isset($this->parsed_contacts['fax'])) {
            return $this->parsed_contacts['fax'];
        }
    }
    
    protected function parseContacts() {
        $this->parsed_contacts = array();
        foreach($this->data['contacts'] as $line) {
            switch($line['type']) {
                case 'phone':
                case 'email':
                case 'jid':
                case 'icq':
                case 'skype':
                case 'mra':
                    $this->parsed_contacts[$line['type']] = $line['value'];
                    $this->parsed_contacts[$line['type'].'s'][] = $line['value'];
                    break;
            }
            if($line['type']=='phone' && $line['for_fax']) {
                $this->parsed_contacts['fax'] = $line['value'];
            }                        
            if($line['type']=='phone' && $line['for_sms']) {
                $this->parsed_contacts['sms_phone'] = $line['value'];
            } 
        }
    }
}

