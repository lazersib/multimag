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
namespace sync;

class dataexport {
    protected $db;                  //< Ссылка на соединение с базой данных
    protected $start_time;          //< Начало дипапзона полной выгрузки
    protected $end_time;            //< Конец диапазона полной выгрузки
    protected $refbooks_list;       //< Список справочников к выгрузке
    protected $doctypes_list;       //< Список типов документов к выгрузке
    protected $en_startcounters;    //< Выгружать ли значения счётчиков на начало диапазона
    protected $partial_timeshtamp;  //< Время предыдущей синхронизации для частичной выгрузки

    /// Конструктор
    /// @param $db Объект связи с базой данных
    public function __construct($db) {
        $this->db = $db;
        $this->drl = array('firms', 'stores', 'tills', 'banks', 'prices', 'workers', 'agents', 'countries', 'units', 'nomenclature', 'pos_links'
            , 'pos_params', 'pos_param_groups', 'pos_pcollections', 'credit_types', 'debit_types', 'delivery_types', 'delivery_regions', );
        $this->refbooks_list = $this->drl;
        //$this->ddl = array(1=>'postuplenie', 2=>'realizaciya', 3=>'zayavka', 4=>'pbank', 5=>'rbank', 6=>'pko', 7=>'rko', 8=>'peremeshenie', 9=>'perkas');
        $this->ddl = \document::getListTypes();
        $this->doctypes_list = $this->ddl;
    }
    
    /// Задать период полной выгрузки
    /// @param $start_date  Начальная дата
    /// @param $end_date    Конечная дата
    public function setPeriod($start_date, $end_date) {
        $this->start_time = strtotime($start_date);
        $this->end_time = strtotime($end_date." 23:59:59");
    }
    
    /// Задать список справочников для экспорта
    /// @param $refbooks_list   Ассоциативный массив с наименованиями справочников или null для выгрузки всех справочников
    public function setRefbooksList($refbooks_list = null) {        
        $this->refbooks_list = $refbooks_list;
        if(!is_array($this->refbooks_list)) {
            $this->refbooks_list = array();
        }
    }
    
    public function setDocTypesList($doctypes_list) {        
        $this->doctypes_list = $doctypes_list;
        if(!is_array($this->doctypes_list)) {
            $this->doctypes_list = array();
        }
    }
    
    public function setStartCounters($val) {
        $this->en_startcounters = $val;
    }
    
    /// Задаёт время в unixtime предыдущей синхронизации. Время используется для сокращения объёма синхронизируемых данных. 
    /// Отсутствие повторной выгрузки синхронизированных данных не гарантируется.
    /// @param $time    Время предыдущей синхронизации или 0
    public function setPartialTimeshtamp($time) {
         $this->partial_timeshtamp = intval($time);
    }

    protected function getDataFromMysqlQuery($query) {
        $ret = array();
        $res = $this->db->query($query);
        while($line = $res->fetch_assoc()) {
            $ret[] = $line;
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
    
    /// Получить данные справочника собственных организаций
    public function getFirmsData() {
        $ret = array();
        $res = $this->db->query("SELECT `id`, 
                `firm_type` AS `type`,
                `firm_name` AS `name`,
                `firm_regnum` AS `regnum`,
                `firm_regdate` AS `regdate`,
                `firm_director` AS `director`,
                `firm_director_r` AS `director_r`,
                `firm_leader_post` AS `leader_post`,
                `firm_leader_post_r` AS `leader_post_r`,
                `firm_leader_reason` AS `leader_reason`,
                `firm_leader_reason_r` AS `leader_reason_r`,
                `firm_manager` AS `manager`,
                `firm_buhgalter` AS `buhgalter`,
                `firm_kladovshik` AS `kladovshik`,
                `firm_kladovshik_id` AS `storekeeper_id`,
                `firm_kladovshik_doljn` AS `storekeeper_post`,
                `firm_inn` AS `inn`,
                `firm_adres` AS `address`,
                `firm_realadres` AS `realaddress`,
                `firm_gruzootpr` AS `storesender`,
                `firm_telefon` AS `phone`,
                `firm_okpo` AS `okpo`, 
                `param_nds` AS `nds`,
                `pricecoeff`,
                `no_retailprices`,
                `firm_store_lock` AS `store_lock`,
                `firm_bank_lock` AS `bank_lock`,
                `firm_till_lock` AS `till_lock`
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
            $ret[] = $line;
        }
        return $ret;
    }
    
    /// Получить данные справочника списка агентов
    /// @param $partial Вернуть только изменённые с указанной даты в unixtime
    public function getAgentsListData($partial = false) {
        $ret = array();
        
        $sql = "SELECT `id`, `group` AS `group_id`, `type`, `name`, `fullname`, `adres` AS `address`, `real_address`, `inn`, `kpp`, `leader_name`, 
            `leader_post`, `leader_reason`,
                `pfio` AS `cpreson_fio`, `pdol` AS `cperson_post`, `okved` AS `okved`, `okpo` AS `okpo`, `ogrn` AS `ogrn`, `pasp_num` AS `passport_num`,
                `pasp_date` AS `passport_date`, `pasp_kem` AS `passport_source_info`, `comment`, `data_sverki` AS `revision_date`,
                `dishonest` AS `dishonest`, `p_agent` AS `p_agent_id`, `price_id` AS `price_id`, `tel`, `sms_phone`, `fax_phone`, `alt_phone`, `email`,
                `no_mail`, `rs`, `bank`, `ks`, `bik`
            FROM `doc_agent` ";
        if($partial) {
            $str_date = date("Y-m-d H:i:s", $partial);
            $sql .= " WHERE `id` IN ( SELECT `object_id` FROM `doc_log` WHERE `object`='agent' AND `time`>'$str_date' GROUP BY `object_id` )";
        }
        $sql .= " ORDER BY `id`";
        
        $res = $this->db->query($sql);
        
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
            $c_res = $this->db->query("SELECT `id`, `context`, `type`, `value`, `person_name`, `person_post`, `for_sms`, `for_fax`, `no_ads`
                FROM `agent_contacts`
                WHERE `agent_id`='{$line['id']}'
                ORDER BY `id`");
            while ($c_line = $c_res->fetch_assoc()) {
                $contacts[] = $c_line;
            }
            $line['contacts'] = $contacts;   
            
            // Банковские реквизиты
            $bank_details = array();
            /*if($line['rs'] || $line['bank'] || $line['ks'] || $line['bik']) {
                $item = array('rs' => $line['rs'], 'bank_name' => $line['bank'], 'bik' => $line['bik'], 'ks' => $line['ks']);
                $bank_details[0] = $item;
            }*/
            $b_res = $this->db->query("SELECT `id`, `name` AS `bank_name`, `bik`, `ks`, `rs`
                FROM `agent_banks`
                WHERE `agent_id`='{$line['id']}'
                ORDER BY `id`");
            while ($b_line = $b_res->fetch_assoc()) {
                $bank_details[] = $b_line;
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
            
            $ret[] = $line;
        }
        return $ret;
    }
    
    /// Получить данные справочника списка номенклатуры
    public function getNomenclatureListData() {
        $ret = array();
        $res = $this->db->query("SELECT `doc_base`.`id`, `doc_base`.`group` AS `group_id`, `doc_base`.`type_id`,
                `doc_base`.`pos_type` AS `type`, `doc_base`.`name`, 
                `doc_base`.`vc` AS `vendor_code`, `doc_base`.`country` AS `country_id`, `class_country`.`number_code` AS `country_code`,
                `doc_base`.`proizv` AS `vendor`, `doc_base`.`cost` AS `base_price`, `doc_base`.`unit` AS `unit_id`, `class_unit`.`number_code` AS `unit_code`,
                `doc_base`.`warranty`, `doc_base`.`warranty_type`, `doc_base`.`create_time`, `doc_base`.`mult`, `doc_base`.`bulkcnt`, 
                `doc_base`.`mass`, `doc_base`.`desc` AS `comment`, `doc_base`.`stock`, `doc_base`.`hidden`, `doc_group`.`printname` AS `group_printname`,
                `doc_base`.`no_export_yml`, `doc_base`.`eol`,
                `doc_base`.`meta_description`, `doc_base`.`meta_keywords`,
                `doc_base`.`title_tag`, `doc_base`.`analog_group`
            FROM `doc_base` 
            LEFT JOIN `class_unit` ON `class_unit`.`id`=`doc_base`.`unit`
            LEFT JOIN `class_country` ON `class_country`.`id`=`doc_base`.`country`
            LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
            ORDER BY `id`");
        while($line = $res->fetch_assoc()) {
            $pos_name = $line['name'];
            if ($line['group_printname']) {
                $pos_name = $line['group_printname'] . ' ' . $pos_name;
            }
            if (!\cfg::get('doc', 'no_print_vendor') && $line['vendor']) {
                $pos_name .= ' / ' . $line['vendor'];
            }
            $line['generated_name'] = $pos_name;
            
            $c_res = $this->db->query("SELECT `cost_id` AS `price_id`, `type`, `value`, `accuracy`, `direction` 
                FROM  `doc_base_cost` 
                WHERE `pos_id`='{$line['id']}'");
            if($c_res->num_rows) {
                $prices = array();
                while($c_line = $c_res->fetch_assoc()) {
                    $prices[$c_line['price_id']] = $c_line;
                }
                //$line['prices'] = $prices;
            }/*
            // Attachments
            $c_res = $this->db->query("SELECT `attachment_id` FROM  `doc_base_attachments` WHERE `pos_id`='{$line['id']}'");
            if($c_res->num_rows) {
                $attachments = array();
                while($c_line = $c_res->fetch_assoc()) {
                    $attachments[$c_line['attachment_id']] = $c_line;
                }
                $line['attachments'] = $attachments;
            }
            // Images
            $c_res = $this->db->query("SELECT `img_id`, `default` FROM  `doc_base_img` WHERE `pos_id`='{$line['id']}'");
            if($c_res->num_rows) {
                $images = array();
                while($c_line = $c_res->fetch_assoc()) {
                    $images[$c_line['img_id']] = $c_line;
                }
                $line['images'] = $images;
            }
            // Parts
            $c_res = $this->db->query("SELECT `id`, `kompl_id`, `cnt` FROM  `doc_base_kompl` WHERE `pos_id`='{$line['id']}'");
            if($c_res->num_rows) {
                $parts = array();
                while($c_line = $c_res->fetch_assoc()) {
                    $parts[$c_line['id']] = $c_line;
                }
                $line['parts'] = $parts;
            }           
            // Params
            $c_res = $this->db->query("SELECT `param_id`, `value` AS `raw_value`, `intval` AS `int_value`, `doubleval` AS `double_value`"
                . ", `strval` AS `text_value`"
                . " FROM `doc_base_values` WHERE `id`='{$line['id']}'");
            if($c_res->num_rows) {
                $values = array();
                while($c_line = $c_res->fetch_assoc()) {
                    $values[] = $c_line;
                }
                $line['params'] = $values;
            }*/
            unset($line['desc']);
            $ret[] = $line;
        }
        return $ret;
    }
    
    /// Получить данные коллекций динамических свойств номенклатуры
    public function getPosParamCollections() {
        $ret = array();
        $res = $this->db->query("SELECT * FROM `doc_base_pcollections_list` ORDER BY `id`");
        while($line = $res->fetch_assoc()) {            
            $p_res = $this->db->query("SELECT `param_id` FROM  `doc_base_pcollections_set` WHERE `collection_id`='{$line['id']}'");
            if($p_res->num_rows) {
                $params = array();
                while($p_line = $p_res->fetch_assoc()) {
                    $params[$p_line['param_id']] = $p_line;
                }
                $line['params'] = $params;
            }
            $ret[$line['id']] = $line;
        }        
        return $ret;
    }  
    
    /// Получить список регионов доставки
    public function getDeliveryRegions() {
        return $this->getDataFromMysqlQuery("SELECT * FROM `delivery_regions` ORDER BY `id`");
    } 
    
    /// Получить список видов доставки
    public function getDeliveryTypes() {
        return $this->getDataFromMysqlQuery("SELECT * FROM `delivery_types` ORDER BY `id`");
    } 
    
    /// Получить список видов расходов
    public function getCreditTypes() {
        return $this->getDataFromMysqlQuery("SELECT * FROM `doc_ctypes` ORDER BY `id`");
    }  
    
    /// Получить список видов доходов
    public function getDebitTypes() {
        return $this->getDataFromMysqlQuery("SELECT * FROM `doc_dtypes` ORDER BY `id`");
    }  
    
    
    /// Получить список групп динамических свойств номенклатуры
    public function getPosGroupParams() {
        return $this->getDataFromMysqlQuery("SELECT * FROM `doc_base_gparams` ORDER BY `id`");
    }    
    
    /// Получить список динамических свойств номенклатуры
    public function getPosParams() {
        return $this->getDataFromMysqlQuery("SELECT * FROM `doc_base_params` ORDER BY `id`");
    }
    
    /// Получить данные связей номенклатуры
    public function getPosLinksData() {
        return $this->getDataFromMysqlQuery("SELECT * FROM `doc_base_links` ORDER BY `id`");
    }
    
    /// Получить список складов
    public function getStoresData() {
        return $this->getDataFromMysqlQuery("SELECT * FROM `doc_sklady` ORDER BY `id`");
    }
    
    /// Получить список касс
    public function getTillsData() {
        return $this->getDataFromMysqlQuery("SELECT `num` AS `id`, `name`, `firm_id` FROM `doc_kassa` WHERE `ids`='kassa' ORDER BY `id`");
    }
    
    /// Получить список банков
    public function getBanksData() {
        return $this->getDataFromMysqlQuery("SELECT `num` AS `id`, `name`, `rs`, `bik`, `ks`, `firm_id` FROM `doc_kassa` WHERE `ids`='bank' ORDER BY `id`");
    }
    
    /// Получить список банков
    public function getPricesData() {
        return $this->getDataFromMysqlQuery("SELECT `id`, `name`, `type`, `value`, `accuracy`, `direction` FROM `doc_cost` ORDER BY `id`");
    }
    
    /// Получить список сотрудников
    public function getWorkersData() {
        return $this->getDataFromMysqlQuery("SELECT `user_id` AS `id`, `worker`, `worker_email` AS `email`, `worker_phone` AS `phone`, 
            `worker_jid` AS `jid`,
             `worker_real_name` AS `real_name`, `worker_real_address` AS `real_address`, `worker_post_name` AS `post_name` 
             FROM `users_worker_info` ORDER BY `id`");
    }
    
    /// Получить список групп агентов
    public function getAgentGroupsData() {
        return $this->getDataFromMysqlQuery("SELECT `id`, `pid` AS `parent_id`, `name`, `desc` AS `comment` FROM `doc_agent_group` ORDER BY `id`");
    }
    
    /// Получить список групп номенклатуры
    public function getNomenclatureGroupsData() {
        $ret = array();
        $res = $this->db->query("SELECT `id`, `pid` AS `parent_id`, `name`, `printname`, `desc` AS `comment`, `hidelevel` AS `hidden`"
            . ", `no_export_yml`, `meta_description`, `meta_keywords`, `title_tag`"
            . " FROM `doc_group` ORDER BY `id`");
        while($line = $res->fetch_assoc()) {            
            $price_res = $this->db->query("SELECT `cost_id` AS `price_id`, `type`, `value`, `accuracy`, `direction` 
                FROM  `doc_group_cost` 
                WHERE `group_id`='{$line['id']}'");
            if($price_res->num_rows) {
                $prices = array();
                while($price_line = $price_res->fetch_assoc()) {
                    $prices[] = $price_line;
                }
                $line['prices'] = $prices;
            }
            $ret[] = $line;
        }        
        return $ret;
    }
    
    /// Получить список стран мира
    public function getCountriesData() {
        return $this->getDataFromMysqlQuery("SELECT `id`, `name`, `full_name`, `number_code`, `alfa2`, `alfa3` FROM `class_country` ORDER BY `id`");
    }
    
    /// Получить список единиц измерения
    public function getUnitsData() {
        return $this->getDataFromMysqlQuery("SELECT `id`, `name`, `rus_name1` AS `short_name`, `number_code`
            FROM `class_unit` ORDER BY `id`");
    }
    
    /// Получить документы
    public function getDocumentsData() {
        $ret = array();
        $res = $this->db->query("SELECT `id`, `type`, `agent` AS `agent_id`, `date`, `ok`, `sklad` AS `store_id`, `kassa` AS `till_id`, `bank` AS `bank_id`,
                `user` AS `author_id`, `altnum`, `subtype`, `sum`, `nds`, `p_doc` AS `parent_doc_id`, `mark_del`, `firm_id`, `contract` AS `contract_id`,
                `comment` 
            FROM `doc_list`
            WHERE `date`>='{$this->start_time}' AND `date`<='{$this->end_time}'");
        while($line = $res->fetch_assoc()) {
            $line['type'] = $this->getNameFromDocType($line['type']);
            if(!in_array($line['type'], $this->doctypes_list) ) {
                continue;
            }
            $line['altnum'] = str_pad($line['altnum'], 6, "0", STR_PAD_LEFT);
            $line['subtype'] = str_pad($line['subtype'], 4, "-", STR_PAD_RIGHT);
            $line['date'] = date("Y-m-d H:i:s", $line['date']);
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
                    case 'guid_1c':
                        $line['guid'] = $dl['value'];
                        break;
                    default:
                        $line[$dl['param']] = $dl['value'];
                        // 'received', 'return'
                }
                
            }
            // Дополнительные текстовые данные документа - преобразование в корректную форму
            $dop_res = $this->db->query("SELECT `param`, `value` FROM `doc_textdata` WHERE `doc_id`='{$line['id']}'");
            while($dl = $dop_res->fetch_assoc()) {
                  $line['td_'.$dl['param']] = $dl['value'];
            }
            
            // Таблица номенклатуры
            if($line['type']=='realizaciya') {
                $doc_obj = \document::getInstanceFromDb($line['id']);
                $line['positions'] = $doc_obj->getDocumentNomenclatureWVATandNums();
            }
            else {
                $nom_res = $this->db->query("SELECT `id`, `tovar` AS `pos_id`, `cnt`, `cost` AS `price`, `gtd`, `comm`, `page` AS `page_id`
                    FROM  `doc_list_pos` 
                    WHERE `doc`='{$line['id']}'");
                if($nom_res->num_rows) {
                    $positions = array();
                    while($nom_line = $nom_res->fetch_assoc()) {
                        if($nom_line['page_id']==0) {
                            unset($nom_line['page_id']);
                        }
                        $positions[] = $nom_line;
                    }
                    $line['positions'] = $positions;
                }
            }
            $ret[] = $line;
        }
        return $ret;
    }
    
    /// Получить значения остатков на начало периода выгрузки
    public function getStartCountersData() {
        $res = $this->db->query("SELECT `id` FROM `doc_sklady`");
        $stores = array();
        while($line = $res->fetch_assoc()) {
            $stores[$line['id']] = $line['id'];
        }

        $res = $this->db->query("SELECT `id` FROM `doc_vars`");
        $firms = array();
        while($line = $res->fetch_assoc()) {
            $firms[$line['id']] = $line['id'];
        }

        $storedata = array();
        $res = $this->db->query("SELECT `id` FROM `doc_base`");
        while($nxt = $res->fetch_assoc()) {
            $data = array();
            foreach ($stores as $store_id) {
                $cnt = getStoreCntOnDate($nxt['id'], $store_id, $this->start_time);
                if($cnt!=0) {
                    $data[] = ['store_id'=>$store_id, 'count'=>$cnt];
                }
            }
            if(count($data)>0) {
                $storedata[] = ['pos_id'=>$nxt['id'], 'data'=>$data];
            }
        }
        $debtdata = array();
        $res = $this->db->query("SELECT `id` FROM `doc_agent`");
        while($nxt = $res->fetch_assoc()) {
            $data = array();
            foreach ($firms as $firm_id) {
                $cnt = agentCalcDebt($nxt['id'], true, $firm_id, $this->db, $this->start_time);
                if($cnt!=0) {
                    $data[] = ['firm_id'=>$firm_id, 'count'=>$cnt];
                }
            }
            if(count($data)>0) {
                $debtdata[$nxt['id']] = ['agent_id'=>$nxt['id'], 'data'=>$data];
            }
        }
        return array(
            'nomenclature' => $storedata,
            'balance' => $debtdata
        );
    }
    
}