<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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

class DataExport {
    protected $db;
    
    /// Конструктор
    /// @param $db Объект связи с базой данных
    public function __construct($db) {
        $this->db = $db;
    }
    
    protected function getDataFromMysqlQuery($query) {
        $ret = array();
        $res = $this->db->query($query);
        while($line = $res->fetch_assoc()) {
            $ret[$line['id']] = $line;
        }
        return $ret;
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
                return 'redlojenie';
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
    
    /// Получить данные справочника собственных организаций
    public function getFirmsData() {
        $ret = array();
        $res = $this->db->query("SELECT `id`, 
                `firm_name` AS `name`,
                `firm_director` AS `director`,
                `firm_manager` AS `manager`,
                `firm_buhgalter` AS `buhgalter`,
                `firm_kladovshik` AS `kladovshik`,
                `firm_inn` AS `inn`,
                `firm_adres` AS `address`,
                `firm_realadres` AS `realaddress`,
                `firm_gruzootpr` AS `storesender`,
                `firm_telefon` AS `phone`,
                `firm_okpo` AS `okpo`, 
                `param_nds` AS `nds` 
            FROM `doc_vars` 
            ORDER BY `id`");
        while($line = $res->fetch_assoc()) {
            $ik = explode('/', $line['inn'], 2);
            $line['inn'] = $ik[0];
            if(isset($ik[1])) {
                $line['kpp'] = $ik[1];
            } else {
                $line['kpp'] = '';
            }
            $ret[$line['id']] = $line;
        }
        return $ret;
    }
    
    /// Получить данные справочника списка агентов
    public function getAgentsListData() {
        $ret = array();
        $res = $this->db->query("SELECT `id`, `group` AS `group_id`, `type`, `name`, `fullname`, `adres` AS `address`, `real_address`, `inn`, `kpp`, `dir_fio`, 
                `pfio` AS `cpreson_fio`, `pdol` AS `cperson_post`, `okved` AS `okved`, `okpo` AS `okpo`, `ogrn` AS `ogrn`, `pasp_num` AS `passport_num`,
                `pasp_date` AS `passport_date`, `pasp_kem` AS `passport_source_info`, `comment`, `data_sverki` AS `revision_date`,
                `dishonest` AS `dishonest`, `p_agent` AS `p_agent_id`, `price_id` AS `price_id`, `tel`, `sms_phone`, `fax_phone`, `alt_phone`, `email`,
                `no_mail`, `rs`, `bank`, `ks`, `bik`
            FROM `doc_agent` 
            ORDER BY `id`");
        while($line = $res->fetch_assoc()) {
            // Тип агента
            switch ($line['type']) {
                case 1:
                    $line['type'] = 'ul';
                    break;
                case 2:
                    $line['type'] = 'nr';
                    break;
                default:
                    $line['type'] = 'fl';
            }
            
            // Контакты
            $contacts = array();
            if($line['tel']) {
                $contacts[1] = array('type'=>'phone', 'value'=>$line['tel']);
            }
            if($line['sms_phone']) {
                $contacts[2] = array('type'=>'phone', 'for_sms'=>1, 'value'=>$line['sms_phone']);
            }
            if($line['fax_phone']) {
                $contacts[3] = array('type'=>'phone', 'for_fax'=>1, 'value'=>$line['fax_phone']);
            }
            if($line['alt_phone']) {
                $contacts[4] = array('type'=>'phone', 'value'=>$line['alt_phone']);
            }
            if($line['email']) {
                $contacts[5] = array('type'=>'email', 'no_ads'=>$line['no_mail'], 'value'=>$line['email']);
            }
            $line['contacts'] = $contacts;   
            
            // Банковские реквизиты
            $bank_details = array();
            if($line['rs'] || $line['bank'] || $line['ks'] || $line['bik']) {
                $item = array('rs' => $line['rs'], 'bank_name' => $line['bank'], 'bik' => $line['bik'], 'ks' => $line['ks']);
                $bank_details[1] = $item;
            }
            $line['bank_details'] = $bank_details;   
            
            unset($line['tel']);
            unset($line['sms_phone']);
            unset($line['fax_phone']);
            unset($line['alt_phone']);
            unset($line['email']);
            unset($line['no_mail']);
            
            unset($line['rs']);
            unset($line['bank']);
            unset($line['bik']);
            unset($line['ks']);
            
            $ret[$line['id']] = $line;
        }
        return $ret;
    }
    
    /// Получить данные справочника списка номенклатуры
    public function getNomenclatureListData() {
        $ret = array();
        $res = $this->db->query("SELECT `id`, `group` AS `group_id`, `pos_type` AS `type`, `name`, `vc` AS `vendor_code`, `country` AS `country_id`, 
                `proizv` AS `vendor`, `cost` AS `base_price`, `unit` AS `unit_id`, `warranty`, `warranty_type`, `create_time`, `mult`, `bulkcnt`, 
                `mass`, `desc` AS `comment`, `stock`, `hidden`
            FROM `doc_base` 
            ORDER BY `id`");
        while($line = $res->fetch_assoc()) {
            $price_res = $this->db->query("SELECT `cost_id` AS `price_id`, `type`, `value`, `accuracy`, `direction` 
                FROM  `doc_base_cost` 
                WHERE `pos_id`='{$line['id']}'");
            if($price_res->num_rows) {
                $prices = array();
                while($price_line = $price_res->fetch_assoc()) {
                    $prices[$price_line['price_id']] = $price_line;
                }
                $line['prices'] = $prices;
            }
            
            $ret[$line['id']] = $line;
        }
        return $ret;
    }
    
    /// Получить данные справочника складов
    public function getStoresData() {
        return $this->getDataFromMysqlQuery("SELECT * FROM `doc_sklady` ORDER BY `id`");
    }
    
    /// Получить данные справочника касс
    public function getTillsData() {
        return $this->getDataFromMysqlQuery("SELECT `num` AS `id`, `name`, `firm_id` FROM `doc_kassa` WHERE `ids`='kassa' ORDER BY `id`");
    }
    
    /// Получить данные справочника банков
    public function getBanksData() {
        return $this->getDataFromMysqlQuery("SELECT `num` AS `id`, `name`, `rs`, `bik`, `ks`, `firm_id` FROM `doc_kassa` WHERE `ids`='bank' ORDER BY `id`");
    }
    
    /// Получить данные справочника банков
    public function getPricesData() {
        return $this->getDataFromMysqlQuery("SELECT `id`, `name`, `type`, `value`, `accuracy`, `direction` FROM `doc_cost` ORDER BY `id`");
    }
    
    /// Получить данные справочника сотрудников
    public function getWorkersData() {
        return $this->getDataFromMysqlQuery("SELECT `user_id` AS `id`, `worker`, `worker_email` AS `email`, `worker_phone` AS `phone`, 
             `worker_real_name` AS `real_name`, `worker_real_address` AS `real_address`, `worker_post_name` AS `post_name` 
             FROM `users_worker_info` ORDER BY `id`");
    }
    
    /// Получить данные справочника групп агентов
    public function getAgentGroupsData() {
        return $this->getDataFromMysqlQuery("SELECT `id`, `pid` AS `parent_id`, `name`, `desc` AS `comment` FROM `doc_agent_group` ORDER BY `id`");
    }
    
    /// Получить данные справочника групп номенклатуры
    public function getNomenclatureGroupsData() {
        return $this->getDataFromMysqlQuery("SELECT `id`, `pid` AS `parent_id`, `name`, `desc` AS `comment` FROM `doc_group` ORDER BY `id`");
    }
    
    /// Получить данные справочника стран мира
    public function getCountriesData() {
        return $this->getDataFromMysqlQuery("SELECT `id`, `name`, `full_name`, `number_code`, `alfa2`, `alfa3`, `comment` FROM `class_country` ORDER BY `id`");
    }
    
    /// Получить данные справочника единиц измерения
    public function getUnitsData() {
        return $this->getDataFromMysqlQuery("SELECT `id`, `name`, `rus_name1` AS `short_name`, `number_code`, `eng_name1` AS `eng_name`, `comment` 
            FROM `class_unit` ORDER BY `id`");
    }
    
    /// Получить документы
    public function getDocumentsData($from_date, $to_date) {
        $ret = array();
        $res = $this->db->query("SELECT `id`, `type`, `date`, `ok`, `sklad` AS `store_id`, `kassa` AS `till_id`, `bank` AS `bank_id`,
                `user` AS `author_id`, `altnum`, `subtype`, `sum`, `nds`, `p_doc` AS `parent_doc_id`, `mark_del`, `firm_id`, `contract` AS `contract_id`, `comment` 
            FROM `doc_list`
            WHERE `date`>='$from_date' AND `date`<='$to_date'");
        while($line = $res->fetch_assoc()) {
            $line['type'] = $this->getNameFromDocType($line['type']);
            // Дополнительные данные документа - преобразование в корректную форму
            $dop_res = $this->db->query("SELECT `param`, `value` FROM `doc_dopdata` WHERE `doc`='{$line['id']}'");
            while($dl = $dop_res->fetch_assoc()) {
                switch($dl['param']) {
                    case 'platelshik':
                        $line['payer_id'] = $dl['value'];
                        break;
                    case 'gruzop':
                        $line['consignee_id'] = $dl['value'];
                        break;
                    case 'kladovshik':
                        $line['storekeeper_id'] = $dl['value'];
                        break;
                    case 'mest':
                        $line['packages_cnt'] = $dl['value'];
                        break;
                    case 'cena':
                        $line['price_id'] = $dl['value'];
                        break;
                    case 'dov_agent':
                        $line['trusted_preson_id'] = $dl['value'];
                        break;
                    case 'dov':
                        $line['trust_num'] = $dl['value'];
                        break;
                    case 'dov_data':
                        $line['trust_date'] = $dl['value'];
                        break;
                    default:
                        $line[$dl['param']] = $dl['value'];
                        // 'received', 'return'
                }
            }
            
            // Таблица номенклатуры
            $nom_res = $this->db->query("SELECT `id`, `tovar` AS `pos_id`, `cnt`, `cost` AS `price`, `gtd`, `comm`, `page` AS `page_id`
                FROM  `doc_list_pos` 
                WHERE `doc`='{$line['id']}'");
            if($nom_res->num_rows) {
                $positions = array();
                while($nom_line = $nom_res->fetch_assoc()) {
                    if($nom_line['page_id']==0) {
                        unset($nom_line['page_id']);
                    }
                    $positions[$nom_line['id']] = $nom_line;
                }
                $line['positions'] = $positions;
            }
            
            $ret[$line['id']] = $line;
        }
        return $ret;
    }
}