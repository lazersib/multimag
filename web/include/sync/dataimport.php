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
//
namespace sync;

class dataimport {
    protected $db;                  //< Ссылка на соединение с базой данных
    protected $units_classifer = null;
    protected $countries_classifer = null;
    
    /// Конструктор
    /// @param $db Объект связи с базой данных
    public function __construct($db) {
        $this->db = $db;
    }
    
    protected function getUnitsClassiferIds() {
        if($this->units_classifer) {
            return $this->units_classifer;
        }
        $this->units_classifer = array();
        $res = $this->db->query("SELECT `id`, `number_code` FROM `class_unit`");
        while($line = $res->fetch_row()) {
            $this->units_classifer[$line[1]] = $line[0];
        }
        return $this->units_classifer;
    }

    protected function getCountriesClassiferIds() {
        if($this->countries_classifer) {
            return $this->countries_classifer;
        }
        $this->countries_classifer = array();
        $res = $this->db->query("SELECT `id`, `number_code` FROM `class_country`");
        while($line = $res->fetch_row()) {
            $this->countries_classifer[$line[1]] = $line[0];
        }
        return $this->countries_classifer;
    }

    protected function loadFirmObject($id, $data) {
        $this->firm_fields = array(
            'firm_name' => 'name',
            'firm_director' => 'director',
            'firm_manager' => 'manager',
            'firm_buhgalter' => 'buhgalter',
            'firm_kladovshik' => 'kladovshik',
            'firm_inn' => 'inn',
            'firm_adres' => 'address',
            'firm_realadres' => 'realaddress',
            'firm_gruzootpr' => 'storesender',
            'firm_telefon' => 'phone',
            'firm_okpo' => 'okpo', 
            'param_nds' => 'nds' 
        );
        
        if( isset($data['inn']) && isset($data['kpp']) ) {
            $data['inn'] .= '/'.$data['kpp'];
            unset($data['kpp']);
        }
        
        $db_data = array();
        foreach($this->firm_fields as $db_field => $exp_field) {
            if( isset($data[$exp_field]) ) {
                $db_data[$db_field] = $data[$exp_field];
            }
        }
        $seek = $this->db->selectRow('doc_vars', $id);
        if($seek===0 || $id==0) {
            if($id) {
                $db_data['id'] = $id;
            }
            $newid = $this->db->insertA('doc_vars', $db_data);
            return $newid;
        } else {
            $this->db->updateA('doc_vars', $id, $db_data);
            return false;
        }
    }
    
    protected function loadStoreObject($id, $data) {
        $this->store_fields = array(
            'name' => 'name' 
        );

        $db_data = array();
        foreach($this->store_fields as $db_field => $exp_field) {
            if( isset($data[$exp_field]) ) {
                $db_data[$db_field] = $data[$exp_field];
            }
        }
        $seek = $this->db->selectRow('doc_sklady', $id);
        if($seek===0 || $id==0) {
            if($id) {
                $db_data['id'] = $id;
            }
            $newid = $this->db->insertA('doc_sklady', $db_data);
            return $newid;
        } else {
            $this->db->updateA('doc_sklady', $id, $db_data);
            return false;
        }
    }
    
    protected function loadBankObject($id, $data) {
        $editor = new \ListEditors\BankListEditor($this->db);
        $ret = $editor->saveItem($id, $data);
        return $ret==$id ? false : $ret;
    }

    protected function loadTillObject($id, $data) {
        $editor = new \ListEditors\KassListEditor($this->db);
        $ret = $editor->saveItem($id, $data);
        return $ret==$id ? false : $ret;
    }
    
    protected function loadPriceObject($id, $data) {
        $this->price_fields = array(
            'name' => 'name' 
        );

        $db_data = array();
        foreach($this->price_fields as $db_field => $exp_field) {
            if( isset($data[$exp_field]) ) {
                $db_data[$db_field] = $data[$exp_field];
            }
        }
        $seek = $this->db->selectRow('doc_cost', $id);
        if($seek===0 || $id==0) {
            if($id) {
                $db_data['id'] = $id;
            }
            $newid = $this->db->insertA('doc_cost', $db_data);
            return $newid;
        } else {
            $this->db->updateA('doc_cost', $id, $db_data);
            return false;
        }
    }

    protected function loadUnitObjectForCode($data) {
        $this->units_fields = array(
            'name' => 'name',
            'rus_name1' => 'short_name'
        );

        $db_data = array();
        foreach($this->units_fields as $db_field => $exp_field) {
            if( isset($data[$exp_field]) ) {
                $db_data[$db_field] = $data[$exp_field];
            }
        }
        if(isset($data['number_code'])) {
            $key = $data['number_code'];
        } else {
            throw new \Exception("У элемента справочника единиц измерения не задан код!");
        }
        $seek = $this->db->selectRowK('class_unit', 'number_code', $key);
        if($seek===0) {
            if(!isset($db_data['class_unit_group_id'])) {
                $db_data['class_unit_group_id'] = 1;
            }
            if(!isset($db_data['class_unit_type_id'])) {
                $db_data['class_unit_type_id'] = 1;
            }
            $newid = $this->db->insertA('class_unit', $db_data);
            return $newid;
        } else {
            $this->db->updateA('class_unit', $seek['id'], $db_data);
            return false;
        }
    }
    
    protected function loadCountryObjectForCode($data) {
        $this->country_fields = array(
            'name' => 'name',
            'full_name' => 'short_name',
            'alfa2' => 'alfa2',
            'alfa3' => 'alfa3'
        );

        $db_data = array();
        foreach($this->country_fields as $db_field => $exp_field) {
            if( isset($data[$exp_field]) ) {
                $db_data[$db_field] = $data[$exp_field];
            }
        }
        if(isset($data['number_code'])) {
            $key = $data['number_code'];
        } else {
            throw new \Exception("У элемента справочника стран мира не задан код!");
        }
        $seek = $this->db->selectRowK('class_country', 'number_code', $key);
        if($seek===0) {
            $newid = $this->db->insertA('class_country', $db_data);
            return $newid;
        } else {
            $this->db->updateA('class_country', $seek['id'], $db_data);
            return false;
        }
    }
    
    protected function loadAgentGroupObject($id, $data) {
        $this->ag_fields = array(
            'pid' => 'parent_id',
            'name' => 'name',
            'desc' => 'comment'
        );

        $db_data = array();
        foreach($this->ag_fields as $db_field => $exp_field) {
            if( isset($data[$exp_field]) ) {
                $db_data[$db_field] = $data[$exp_field];
            }
        }
        $seek = $this->db->selectRow('doc_agent_group', $id);
        if($seek===0) {
            if($id) {
                $db_data['id'] = $id;
            }
            $newid = $this->db->insertA('doc_agent_group', $db_data);
            return $newid;
        } else {
            $this->db->updateA('doc_agent_group', $id, $db_data);
            return false;
        }
    }
    
    protected function loadAgentItemObject($id, $data) {
        $this->ai_fields = array(
            'group' => 'group_id',
            'type' => 'type',
            'name' => 'name',
            'fullname' => 'fullname',
            'address' => 'adres'
        );

        $db_data = array();
        foreach($this->ai_fields as $db_field => $exp_field) {
            if( isset($data[$exp_field]) ) {
                $db_data[$db_field] = $data[$exp_field];
            }
        }
        switch ($db_data['type']) {
            case 'ul':
                $line['type'] = 1;
                break;
            case 'nr':
                $line['type'] = 2;
                break;
            default:
                $line['type'] = 0;
        }
        $seek = $this->db->selectRow('doc_agent', $id);
        if($seek===0) {
            if($id) {
                $db_data['id'] = $id;
            }
            $newid = $this->db->insertA('doc_agent', $db_data);
            return $newid;
        } else {
            $this->db->updateA('doc_agent', $id, $db_data);
            return false;
        }
    }
    
    protected function loadNomenclatureGroupObject($id, $data) {
        $this->ng_fields = array(
            'pid' => 'parent_id',
            'name' => 'name',
            'desc' => 'comment'
        );

        $db_data = array();
        foreach($this->ng_fields as $db_field => $exp_field) {
            if( isset($data[$exp_field]) ) {
                $db_data[$db_field] = $data[$exp_field];
            }
        }
        $seek = $this->db->selectRow('doc_group', $id);
        if($seek===0 || $id==0) {
            if($id) {
                $db_data['id'] = $id;
            }
            $newid = $this->db->insertA('doc_group', $db_data);
            return $newid;
        } else {
            $this->db->updateA('doc_group', $id, $db_data);
            return false;
        }
    }
    
    protected function loadNomenclatureItemObject($id, $data) {
        $this->ng_fields = array(
            'group' => 'group_id',
            'pos_type' => 'type',
            'name' => 'name',
            'vc' => 'vendor_code',
            'nds' => 'nds',
            //'country' => 'country_id',
            //'unit' => 'unit_id',
            'desc' => 'comment'
        );

        $db_data = array();
        foreach($this->ng_fields as $db_field => $exp_field) {
            if( isset($data[$exp_field]) ) {
                $db_data[$db_field] = $data[$exp_field];
            }
        }
        $ui = $this->getUnitsClassiferIds();
        if(isset($data['unit_code'])) {
            if($data['unit_code'] == 'null' || $data['unit_code'] == 'NULL') {
                $db_data['unit'] = 'null';
            } elseif (isset($ui[$data['unit_code']])) {
                $db_data['unit'] = $ui[$data['unit_code']];
            } else {
                throw new \Exception("Код *{$data['unit_code']}* в справочнике единиц измерения не найден.");
            }
        } else {
            $db_data['unit'] = 'null';
        }
        
        $ci = $this->getCountriesClassiferIds();
        if(isset($data['country_code'])) {
            if($data['country_code'] == 'null' || $data['country_code'] == 'NULL') {
                $db_data['country'] = 'null';
            } elseif (isset($ci[$data['country_code']])) {
                $db_data['country'] = $ci[$data['country_code']];
            } else {
                throw new \Exception("Код *{$data['country_code']}* в справочнике стран мира не найден.");
            }
        } else {
            $db_data['country'] = 'null';
        }
        
        $seek = $this->db->selectRow('doc_base', $id);
        if($seek===0 || $id==0) {
            if($id) {
                $db_data['id'] = $id;
            }
            $newid = $this->db->insertA('doc_base', $db_data);
            return $newid;
        } else {
            $this->db->updateA('doc_base', $id, $db_data);
            return false;
        }
    }
    
    protected function loadDocumentObject($id, $data) {
        $type = $this->getTypeFromDocName($data['type']);
        if(!$type) {
            throw new Exception("Неизвестный тип документа!");
        }
        $sql_guid = $this->db->real_escape_string($data['guid']);
        $res = $this->db->query("SELECT `a`.`id`, `a`.`type`, `a`.`altnum`, `a`.`subtype`, `a`.`agent`, `a`.`sklad`, `a`.`kassa`, `a`.`bank`, 
            `a`.`date`, `a`.`ok`, `a`.`mark_del`, `a`.`user`, `a`.`sum`, `a`.`nds`, `a`.`p_doc`, `a`.`firm_id`,
            `a`.`contract`, `a`.`comment`, `b`.`value` AS `guid_1c`
            FROM `doc_list` AS `a`
            INNER JOIN `doc_dopdata` AS `b` ON `a`.`id`=`b`.`doc` AND `b`.`param`='guid_1c'
            WHERE `b`.`param`='{$sql_guid}'");        
        $db_data = array(
            'type'  => $type,
            'agent' => $data['agent'],
            'date'  => strtotime($data['date']),
            'altnum'=> $data['altnum'],
            'subtype'=> $data['subtype'],
            'sum'   => $data['sum'],
            'nds'   => $data['nds'],
            'firm_id'=> $data['firm_id'],
            'mark_del'=> $data['mark_del'] ? 1 : 0,
            'comment'=> $data['comment']
        );
        if( isset($data['store_id']) ) {
            $db_data['sklad'] = $data['store_id'];
        }
        if($res->num_rows) {
            $old_doc_data = $res->fetch_assoc();
            if( !$old_doc_data['ok'] || ($old_doc_data['ok'] && !$data['ok']) ) {
                $db_data['ok'] = $data['ok'] ? time() : 0;
            }
            $this->db->updateA("doc_list", $old_doc_data['id'], $db_data);
            $doc_id = $old_doc_data['id'];
        } else {
            $db_data['ok'] = $data['ok'] ? time() : 0;
            $data['author'] = $_SESSION['uid'];
            $doc_id = $this->db->insertA("doc_list", $db_data);
            $this->db->query("INSERT INTO `doc_dopdata` (`doc`, `param`, `value`) VALUES ($doc_id, 'guid_1c', '$sql_guid')");
        }
        if(isset($data['positions'])) {
            $res = $this->db->query("SELECT `id`, `tovar` AS `pos_id`, `cost` AS `price`, `cnt`, `gtd`"
                . " FROM `doc_list_pos`"
                . " WHERE `doc`='$doc_id'");
            $old_pl = array();
            while($line = $res->fetch_assoc()) {
                $old_pl[$line['tovar']] = $line;
            }
            // обновляем
            foreach($data['positions'] as $pos_line) {
                if(isset($old_pl[ $pos_line['pos_id'] ])) {
                    $line_id = $old_pl[ $pos_line['pos_id'] ]['id'];
                    $this->updateDocLine($line_id, $pos_line);
                    unset($old_pl[ $pos_line['pos_id'] ]);
                } else {
                    $pos_line['doc'] = $doc_id;
                    $this->insertDocLine($pos_line);
                }
            }
            // удаляем остатки
            foreach($old_pl AS $pos_line) {
                $this->db->delete('doc_list_pos', $pos_line['id']);
            }
        }
    }
    
    protected function updateDocLine($old_line, $new_line) {
        $this->docline_fields = array(
            'tovar' => 'pos_id',
            'cost' => 'price',
            'cnt' => 'cnt',
            'gtd' => 'gtd'
        );
        $db_data = array();
        foreach($this->docline_fields as $db_field => $exp_field) {
            if( isset($new_line[$exp_field]) ) {
                $db_data[$db_field] = $new_line[$exp_field];
            }
        }        
        $this->db->updateA('doc_list_pos', $old_line['id'], $db_data);
    }
    
    protected function insertDocLine($new_line) {
        $this->docline_fields = array(
            'doc'   => 'doc',
            'tovar' => 'pos_id',
            'cost' => 'price',
            'cnt' => 'cnt',
            'gtd' => 'gtd'
        );
        $db_data = array();
        foreach($this->docline_fields as $db_field => $exp_field) {
            if( isset($new_line[$exp_field]) ) {
                $db_data[$db_field] = $new_line[$exp_field];
            }
        }        
        $newid = $this->db->insertA('doc_list_pos', $db_data);
        return $newid;
    }

    protected function getTypeFromDocName($doc_name) {
        $doc_types = array(
            1 => 'postuplenie',
            2 => 'realizaciya',
            3 => 'zayavka',
            4 => 'pbank',
            5 => 'rbank',
            6 => 'pko',
            7 => 'rko',
            8 => 'peremeshenie',
            9 => 'perkas',
            10 => 'doveren',
            11 => 'predlojenie',
            12 => 'v_puti',
            13 => 'kompredl',
            14 => 'dogovor',
            15 => 'realiz_op',
            16 => 'specific',
            17 => 'sborka',
            18 => 'kordolga',
            19 => 'korbonus',
            20 => 'realiz_bonus',
            21 => 'zsbor'
        );
        $type = array_search($doc_name, $doc_types);
        return $type;
    }
    

    
}