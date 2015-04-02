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

    protected function getNameFromDocType($doc_type) {
        switch ($doc_type) {
            case 1:
                return 'postuplenie';
            case 2:
                return 'realizaciya';
            case 3:
                return 'zayavka';
            case 4:
                return 'pbank';
            case 5:
                return 'rbank';
            case 6:
                return 'pko';
            case 7:
                return 'rko';
            case 8:
                return 'peremeshenie';
            case 9:
                return 'perkas';
            case 10:
                return 'doveren';
            case 11:
                return 'predlojenie';
            case 12:
                return 'v_puti';
            case 13:
                return 'kompredl';
            case 14:
                return 'dogovor';
            case 15:
                return 'realiz_op';
            case 16:
                return 'specific';
            case 17:
                return 'sborka';
            case 18:
                return 'kordolga';
            case 19:
                return 'korbonus';
            case 20:
                return 'realiz_bonus';
            case 21:
                return 'zsbor';
            default:
                return 'unknown';
        }
    }
    
}