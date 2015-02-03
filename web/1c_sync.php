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
        
    $export = new \sync\Xml1cDataExport($db);
    
    $export->dom = $dom;
    
    // Выгрузка справочника собственных организаций
    $firms = $export->convertToXmlElement('firms', 'firm', $export->getFirmsData());
    $refbooks->appendChild($firms);
    
    // Выгрузка справочника складов
    $stores = $export->convertToXmlElement('stores', 'store', $export->getStoresData());
    $refbooks->appendChild($stores);
    
    // Выгрузка справочника касс
    $tills = $export->convertToXmlElement('tills', 'till', $export->getTillsData());
    $refbooks->appendChild($tills);
    
    // Выгрузка справочника банков
    $banks = $export->convertToXmlElement('banks', 'bank', $export->getBanksData());
    $refbooks->appendChild($banks);
    
    // Выгрузка справочника цен
    $prices = $export->convertToXmlElement('prices', 'price', $export->getPricesData());
    $refbooks->appendChild($prices);
    
    // Выгрузка справочника сотрудников
    $workers = $export->convertToXmlElement('workers', 'worker', $export->getWorkersData());
    $refbooks->appendChild($workers);
    
    // Выгрузка справочника агентов
    $agents = $dom->createElement('agents');    
    $groups = $export->convertToXmlElement('groups', 'group', $export->getAgentGroupsData());
    $agents->appendChild($groups);
    $items = $export->convertToXmlElement('items', 'item', $export->getAgentsListData());
    $agents->appendChild($items);    
    $refbooks->appendChild($agents);
    
    // Выгрузка справочника стран мира (ОКСМ)
    $workers = $export->convertToXmlElement('countries', 'country', $export->getCountriesData());
    $refbooks->appendChild($workers);
    
    // Выгрузка справочника единиц измерения (ОКЕИ)
    $units = $export->convertToXmlElement('units', 'unit', $export->getUnitsData());
    $refbooks->appendChild($units);
    
    // Выгрузка справочника номенклатуры
    $nomenclature = $dom->createElement('nomenclature');
    // Номенклатурные группы
    $groups = $export->convertToXmlElement('groups', 'group', $export->getNomenclatureGroupsData());
    $nomenclature->appendChild($groups);
    $items = $export->convertToXmlElement('items', 'item', $export->getNomenclatureListData());
    $nomenclature->appendChild($items);    
    $refbooks->appendChild($nomenclature);
    
    $root->appendChild($refbooks);
    
    $from_date = strtotime("2014-06-01");
    $to_date = strtotime("2016-01-01");
    $documents = $export->convertToXmlElement('documents', 'document', $export->getDocumentsData($from_date, $to_date));
    
    $root->appendChild($documents);
    
    header("Content-type: application/xml");
    echo $dom->saveXML();  
    
} catch (Exception $e) {
    $dom = new domDocument("1.0", "utf-8");
    $root = $dom->createElement("multimag_exchange"); // Создаём корневой элемент
    $root->setAttribute('version', '1.0');
    $dom->appendChild($root);
    
    $result = $dom->createElement('result');            // Код возврата
    $result_code = $dom->createElement('status', 'err');
    $result_desc = $dom->createElement('desc', $e->getMessage());
    $result->appendChild($result_code);
    $result->appendChild($result_desc);
    $root->appendChild($result);
    
    header("Content-type: application/xml");
    echo $dom->saveXML(); 
}
