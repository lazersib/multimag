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
namespace sync;

class dataimport {
    protected $db;                  //< Ссылка на соединение с базой данных

    /// Конструктор
    /// @param $db Объект связи с базой данных
    public function __construct($db) {
        $this->db = $db;
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
        if($id==0) {
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
        if($id==0) {
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
        if($id==0) {
            $newid = $this->db->insertA('doc_cost', $db_data);
            return $newid;
        } else {
            $this->db->updateA('doc_cost', $id, $db_data);
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
        if($id==0) {
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
        if($id==0) {
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
        if($id==0) {
            $newid = $this->db->insertA('doc_group', $db_data);
            return $newid;
        } else {
            $this->db->updateA('doc_group', $id, $db_data);
            return false;
        }
    }
    
    protected function loadNomenclatureItemsObject($id, $data) {
        $this->ng_fields = array(
            'pid' => 'group_id',
            'type' => 'type',
            'name' => 'name',
            'vc' => 'vendor_code',
            'country' => 'country_id',
            'unit' => 'unit_id',
            'desc' => 'comment'
        );

        $db_data = array();
        foreach($this->ng_fields as $db_field => $exp_field) {
            if( isset($data[$exp_field]) ) {
                $db_data[$db_field] = $data[$exp_field];
            }
        }
        if($id==0) {
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
            $this->db->query("INSERT INTO `doc_doptdata` (`doc`, `param`, `value`) VALUES ($doc_id, 'guid_1c', '$sql_guid')");
        }
                
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