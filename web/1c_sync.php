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

include_once("core.php");
include_once("include/doc.core.php");

// Проверка необходимости перехода на https
if(!@$_SERVER['HTTPS'] && (@$CONFIG['site']['force_https'] || @$CONFIG['site']['force_https_login'])) {
    header('Location: https://' . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'], true, 301);
    exit();
}



try {
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="' . @$CONFIG['site']['name'] . '"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Authentification cancel by user';
        exit();
    } elseif (@$_SERVER['PHP_AUTH_USER'] != @$CONFIG['1csync']['login'] || @$_SERVER['PHP_AUTH_PW'] != @$CONFIG['1csync']['pass'] ||
            !@$CONFIG['1csync']['pass'] || !@$CONFIG['1csync']['login']) {
        header('WWW-Authenticate: Basic realm="' . @$CONFIG['site']['name'] . '"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Authentification error';
        exit();
    }

    $partial_time = rcvdatetime('partial_time', 0); // Если задано, то передаёт только изменения, произошедшие после этой даты
    $start_date = rcvdate('start_date');            // Только для полной синхронизации. Начало интервала.    
    $end_date = rcvdate('end_date');                // Только для полной синхронизации. Конец интервала.
    
    $db->startTransaction();
    
    $dom = new domDocument("1.0", "utf-8");
    $root = $dom->createElement("multimag_exchange"); // Создаём корневой элемент
    $root->setAttribute('version', '1.0');
    $dom->appendChild($root);
    
    // Информация о выгрузке
    $result = $dom->createElement('result');            // Код возврата
    $result_code = $dom->createElement('status', 'ok');
    $result_desc = $dom->createElement('desc', 'Ok');
    $result_timestamp = $dom->createElement('timestamp', time()-1);
    $result->appendChild($result_code);
    $result->appendChild($result_desc);
    $result->appendChild($result_timestamp);
    $root->appendChild($result);
    
    $refbooks = $dom->createElement('refbooks');    // Узел справочников
    $documents = $dom->createElement('documents');  // Узел документов
      
    // Выгрузка справочника собственных организаций
    $fields = array(
        'firm_name'=>'name',
        'firm_director'=>'director',
        'firm_manager'=>'manager',
        'firm_buhgalter'=>'buhgalter',
        'firm_kladovshik'=>'kladovshik',
        'firm_adres'=>'address',
        'firm_realadres'=>'realaddress',
        'firm_gruzootpr'=>'storesender',
        'firm_telefon'=>'phone',
        'firm_okpo'=>'okpo', 
        'param_nds'=>'nds'
    );
    $firms = $dom->createElement('firms');
    $res = $db->query("SELECT * FROM `doc_vars` ORDER BY `id`");
    while($line = $res->fetch_assoc()) {
        $node = $dom->createElement('firm');
        $node->setAttribute('id', $line['id']);
        foreach($fields AS $i_name => $e_name) {
            if(!$line[$i_name]) {
                continue;
            }
            $snode = $dom->createElement($e_name, $line[$i_name]);
            $node->appendChild($snode);
        }
        $ik = explode('/', $line['firm_inn'], 2);
        $snode = $dom->createElement('inn', $ik[0]);
        $node->appendChild($snode);
        if(isset($ik[1])) {
            $snode = $dom->createElement('kpp', $ik[1]);
            $node->appendChild($snode);
        }
        $firms->appendChild($node);
    }
    $refbooks->appendChild($firms);
    
    // Выгрузка справочника складов
    $stores = $dom->createElement('stores');
    $res = $db->query("SELECT * FROM `doc_sklady` ORDER BY `id`");
    while($line = $res->fetch_assoc()) {
        $node = $dom->createElement('store');
        $node->setAttribute('id', $line['id']);
        $name = $dom->createElement('name', $line['name']);
        $dnc = $dom->createElement('dnc', $line['dnc']);
        $node->appendChild($name);
        $node->appendChild($dnc);
        $stores->appendChild($node);
    }
    $refbooks->appendChild($stores);
    
    // Выгрузка справочника касс
    $tills = $dom->createElement('tills');
    $res = $db->query("SELECT * FROM `doc_kassa` WHERE `ids`='kassa' ORDER BY `num`");
    while($line = $res->fetch_assoc()) {
        $node = $dom->createElement('till');
        $node->setAttribute('id', $line['num']);
        $node->setAttribute('firm_id', $line['firm_id']);
        $name = $dom->createElement('name', $line['name']);
        $node->appendChild($name);
        $tills->appendChild($node);
    }
    $refbooks->appendChild($tills);
    
    // Выгрузка справочника банков
    $banks = $dom->createElement('banks');
    $res = $db->query("SELECT * FROM `doc_kassa` WHERE `ids`='bank' ORDER BY `num`");
    while($line = $res->fetch_assoc()) {
        $node = $dom->createElement('bank');
        $node->setAttribute('id', $line['num']);
        $node->setAttribute('firm_id', $line['firm_id']);
        $name = $dom->createElement('name', $line['name']);
        $bik = $dom->createElement('bik', $line['bik']);
        $rs = $dom->createElement('rs', $line['rs']);
        $ks = $dom->createElement('ks', $line['ks']);        
        
        $node->appendChild($name);
        $node->appendChild($bik);
        $node->appendChild($rs);
        $node->appendChild($ks);
        $banks->appendChild($node);
    }
    $refbooks->appendChild($banks);
    
    // Выгрузка справочника цен
    $fields = array('name', 'type', 'value', 'accuracy', 'direction');
    $prices = $dom->createElement('prices');
    $res = $db->query("SELECT * FROM `doc_cost` ORDER BY `id`");
    while($line = $res->fetch_assoc()) {
        $node = $dom->createElement('price');
        $node->setAttribute('id', $line['id']);
        foreach($fields AS $name) {
            $snode = $dom->createElement($name, $line[$name]);
            $node->appendChild($snode);
        }
        $prices->appendChild($node);
    }
    $refbooks->appendChild($prices);
    
    // Выгрузка справочника сотрудников
    $fields = array('worker', 'worker_email', 'worker_phone', 'worker_real_name', 'worker_real_address', 'worker_post_name');
    $workers = $dom->createElement('workers');
    $res = $db->query("SELECT * FROM `users_worker_info` ORDER BY `user_id`");
    while($line = $res->fetch_assoc()) {
        $node = $dom->createElement('worker');
        $node->setAttribute('id', $line['user_id']);
        foreach($fields AS $name) {
            $snode = $dom->createElement($name, $line[$name]);
            $node->appendChild($snode);
        }
        $workers->appendChild($node);
    }
    $refbooks->appendChild($workers);
    
    // Выгрузка справочника агентов
    $agents = $dom->createElement('agents');
    
    $fields = array('name', 'desc');
    $groups = $dom->createElement('groups');
    $res = $db->query("SELECT * FROM `doc_agent_group` ORDER BY `id`");
    while($line = $res->fetch_assoc()) {
        $node = $dom->createElement('group');
        $node->setAttribute('id', $line['id']);
        $node->setAttribute('parent_id', $line['pid']);
        foreach($fields AS $name) {
            $snode = $dom->createElement($name, $line[$name]);
            $node->appendChild($snode);
        }
        $groups->appendChild($node);
    }
    $agents->appendChild($groups);
    
    $fields = array('name' => 'name',
        'fullname' => 'fullname',
        'adres' => 'address',
        'real_address' => 'real_address',
        'inn' => 'inn',
        'kpp' => 'kpp',
        'dir_fio' => 'dir_fio',
        'pfio' => 'cpreson_fio',
        'pdol' => 'cperson_post',
        'okved' => 'okved',
        'okpo' => 'okpo',
        'ogrn' => 'ogrn',
        'pasp_num' => 'passport_num',
        'pasp_date' => 'passport_date',
        'pasp_kem' => 'passport_source_info',
        'comment' => 'comment',
        'data_sverki' => 'revision_date',
        'dishonest' => 'dishonest',
        'p_agent' => 'p_agent_id',
        'price_id' => 'price_id'
    );
    $items = $dom->createElement('items');
    $res = $db->query("SELECT * FROM `doc_agent` ORDER BY `id`");
    while($line = $res->fetch_assoc()) {
        $node = $dom->createElement('agent');
        $node->setAttribute('id', $line['id']);
        $node->setAttribute('group_id', $line['group']);
        // Тип агента
        switch ($line['type']) {
            case 1:
                $atype = 'ul';
                break;
            case 2:
                $atype = 'nr';
                break;
            default:
                $atype = 'fl';
        }
        $node->setAttribute('type', $atype);
        
        foreach($fields AS $i_name=>$e_name) {
            if($line[$i_name]==='' || $line[$i_name]===null || $line[$i_name]=='0000-00-00') {
                continue;
            }
            $snode = $dom->createElement($e_name, $line[$i_name]);
            $node->appendChild($snode);
        }
        
        // Контакты
        $contacts = $dom->createElement('contacts');
        if($line['tel']) {
            $snode = $dom->createElement('contact', $line['tel']);
            $snode->setAttribute('type', 'phone');
            $contacts->appendChild($snode);
        }
        if($line['sms_phone']) {
            $snode = $dom->createElement('contact', $line['sms_phone']);
            $snode->setAttribute('type', 'phone');
            $snode->setAttribute('for_sms', 1);
            $contacts->appendChild($snode);
        }
        if($line['fax_phone']) {
            $snode = $dom->createElement('contact', $line['fax_phone']);
            $snode->setAttribute('type', 'phone');
            $snode->setAttribute('for_fax', 1);
            $contacts->appendChild($snode);
        }
        if($line['alt_phone']) {
            $snode = $dom->createElement('contact', $line['alt_phone']);
            $snode->setAttribute('type', 'phone');
            $contacts->appendChild($snode);
        }
        if($line['email']) {
            $snode = $dom->createElement('contact', $line['email']);
            $snode->setAttribute('type', 'email');
            if($line['no_mail']) {
                $snode->setAttribute('no_ads', 1);
            }
            $contacts->appendChild($snode);
        }
        $node->appendChild($contacts);
        
        // Банковские реквизиты
        $bank_details = $dom->createElement('bank_details');
        if($line['rs'] || $line['bank'] || $line['ks'] || $line['bik']) {
            $item = $dom->createElement('item');
            $snode = $dom->createElement('rs', $line['rs']);
            $item->appendChild($snode);
            $snode = $dom->createElement('bank_name', $line['bank']);
            $item->appendChild($snode);
            $snode = $dom->createElement('bik', $line['bik']);
            $item->appendChild($snode);
            $snode = $dom->createElement('ks', $line['ks']);
            $item->appendChild($snode);            
            
            $bank_details->appendChild($item);
        }
        $node->appendChild($bank_details);        
        
        $items->appendChild($node);
    }
    $agents->appendChild($items);    
    $refbooks->appendChild($agents);
    
    // Выгрузка справочника номенклатуры
    $nomenclature = $dom->createElement('nomenclature');
    // Номенклатурные группы
    $fields = array('name', 'desc');
    $groups = $dom->createElement('groups');
    $res = $db->query("SELECT * FROM `doc_group` ORDER BY `id`");
    while($line = $res->fetch_assoc()) {
        $node = $dom->createElement('group');
        $node->setAttribute('id', $line['id']);
        $node->setAttribute('parent_id', $line['pid']);
        foreach($fields AS $name) {
            $snode = $dom->createElement($name, $line[$name]);
            $node->appendChild($snode);
        }
        $groups->appendChild($node);
    }
    $nomenclature->appendChild($groups);
    $fields = array(
        'name' => 'name',
        'vc' => 'vendor_code',
        'country' => 'country_id',
        'proizv' => 'vendor', 
        'cost' => 'base_price',
        'unit' => 'unit_id',
        'warranty' => 'warranty',
        'warranty_type' => 'warranty_type',
        'create_time' => 'create_time',
        'mult' => 'mult',
        'bulkcnt' => 'bulkcnt',
        'mass' => 'mass',
        'desc' => 'comment',
        'stock' => 'stock',
        'hidden' => 'hidden'
        // nds
    );
    $items = $dom->createElement('items');
    $res = $db->query("SELECT * FROM `doc_base` ORDER BY `id`");
    while($line = $res->fetch_assoc()) {
        $node = $dom->createElement('item');
        $node->setAttribute('id', $line['id']);
        $node->setAttribute('group_id', $line['group']);
        $node->setAttribute('type', $line['pos_type']);
        
        foreach($fields AS $i_name=>$e_name) {
            if($line[$i_name]===0 || $line[$i_name]==='0' || $line[$i_name]===null ||$line[$i_name]==='' || $line[$i_name]==='0000-00-00 00:00:00') {
                continue;
            }
            $snode = $dom->createElement($e_name, $line[$i_name]);
            $node->appendChild($snode);
        }
        $price_res = $db->query("SELECT `cost_id` AS `price_id`, `type`, `value`, `accuracy`, `direction` FROM  `doc_base_cost` WHERE `pos_id`='{$line['id']}'");
        if($price_res->num_rows) {
            $prices = $dom->createElement('prices');
            while($price_line = $price_res->fetch_assoc()) {
                $price = $dom->createElement('price');
                foreach($price_line as $i_name => $i_value) {
                    $snode = $dom->createElement($i_name, $i_value);
                    $price->appendChild($snode);
                } 
                $prices->appendChild($price);
            }
            $node->appendChild($prices);
        }
        
        $items->appendChild($node);
    }
    $nomenclature->appendChild($items);
    
    $refbooks->appendChild($nomenclature);
    
    $root->appendChild($refbooks);
    $root->appendChild($documents);
    
    header("Content-type: application/xml");
    echo $dom->saveXML();  
    
} catch (Exception $e) {
    header("Content-type: application/xml");
    echo"<?xml version=\"1.0\" encoding=\"utf-8\"?><root><result><code>err</code><desc>" . $e->getMessage() . ", order: $order_id, bp: $merchant_trx</desc></result></register-payment-response>";
}
