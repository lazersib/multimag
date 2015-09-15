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
//
namespace models;

/// @brief Класс работы с данными агента
class agent {
    var $agents_tn = 'doc_agent';
    var $contacts_tn = 'agent_contacts';
    protected $data = array();
    protected $parsed_contacts = array();
    
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
        $res = $db->query("SELECT * FROM `{$this->contacts_tn}` WHERE 'agent_id'='$agent_id'");
        while($line = $res->fetch_assoc()) {
            $contacts[$line['id']] = $line;
        }
        $this->data['contacts'] = $contacts;
        $this->parseContacts();
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
    
    public function getEmail() {
        if(isset($this->parsed_contacts['email'])) {
            return $this->parsed_contacts['email'];
        }
    }


    protected function parseContacts() {
        $this->parsed_contacts = array();
        foreach($this->data as $line) {
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
        }
    }
}

