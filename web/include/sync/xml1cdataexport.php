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

/// Класс формирования XML файла для обмена с 1с
class Xml1cDataExport extends \sync\dataexport {
    protected $dom;                 //< Объект DOMDocument
    protected $rootNode;            //< Корневой элемент


    /// Преобразовывает ассоциативный многоуровневый массив $data в DOMElement
    /// @param $group_node_name Имя создвавемого элемента списка
    /// @param $item_node_name Имя объектов-элементов
    /// @param $data ассоциативный многоуровневый массив с данными
    /// @return DOMElement c данными из $data
    public function convertToXmlElement($group_node_name, $item_node_name, $data) {
        $res_node = $this->dom->createElement($group_node_name);
        foreach($data as $id=>$line) {
            $item_node = $this->dom->createElement($item_node_name);
            $item_node->setAttribute('id', $id);
            foreach($line AS $name=>$value) {
                if($name==='id' || $value===null || $value==='' || $value==='0000-00-00') {
                    continue;
                }
                if(is_array($value)) {
                    $param_node = $this->convertToXmlElement($name, substr($name, 0, -1), $value);
                } else {
                    $param_node = $this->dom->createElement($name, $value);
                }
                $item_node->appendChild($param_node);
            }
            $res_node->appendChild($item_node);
        }
        return $res_node;
    }
    
    /// Получить экспортируемые данные
    public function getData() {
        $this->createDom();
        $refbooks = $this->dom->createElement('refbooks');    // Узел справочников
        // Выгрузка справочника собственных организаций
        if (in_array('firms', $this->refbooks_list)) {
            $firms = $this->convertToXmlElement('firms', 'firm', $this->getFirmsData());
            $refbooks->appendChild($firms);
        }
        // Выгрузка справочника складов
        if (in_array('stores', $this->refbooks_list)) {
            $stores = $this->convertToXmlElement('stores', 'store', $this->getStoresData());
            $refbooks->appendChild($stores);
        }
        // Выгрузка справочника касс
        if (in_array('tills', $this->refbooks_list)) {
            $tills = $this->convertToXmlElement('tills', 'till', $this->getTillsData());
            $refbooks->appendChild($tills);
        }
        // Выгрузка справочника банков
        if (in_array('banks', $this->refbooks_list)) {
            $banks = $this->convertToXmlElement('banks', 'bank', $this->getBanksData());
            $refbooks->appendChild($banks);
        }
        // Выгрузка справочника цен
        if (in_array('prices', $this->refbooks_list)) {
            $prices = $this->convertToXmlElement('prices', 'price', $this->getPricesData());
            $refbooks->appendChild($prices);
        }
        // Выгрузка справочника сотрудников
        if (in_array('workers', $this->refbooks_list)) {
            $workers = $this->convertToXmlElement('workers', 'worker', $this->getWorkersData());
            $refbooks->appendChild($workers);
        }
        // Выгрузка справочника стран мира (ОКСМ)
        if (in_array('countries', $this->refbooks_list)) {
            $workers = $this->convertToXmlElement('countries', 'country', $this->getCountriesData());
            $refbooks->appendChild($workers);
        }
        // Выгрузка справочника единиц измерения (ОКЕИ)
        if (in_array('units', $this->refbooks_list)) {
            $units = $this->convertToXmlElement('units', 'unit', $this->getUnitsData());
            $refbooks->appendChild($units);
        }
        // Выгрузка справочника агентов
        if (in_array('agents', $this->refbooks_list)) {
            $agents = $this->dom->createElement('agents');    
            $groups = $this->convertToXmlElement('groups', 'group', $this->getAgentGroupsData());
            $agents->appendChild($groups);
            $items = $this->convertToXmlElement('items', 'item', $this->getAgentsListData());
            $agents->appendChild($items);    
            $refbooks->appendChild($agents);
        }
        // Выгрузка справочника номенклатуры
        if (in_array('nomenclature', $this->refbooks_list)) {
            $nomenclature = $this->dom->createElement('nomenclature');
            // Номенклатурные группы
            $groups = $this->convertToXmlElement('groups', 'group', $this->getNomenclatureGroupsData());
            $nomenclature->appendChild($groups);
            $items = $this->convertToXmlElement('items', 'item', $this->getNomenclatureListData());
            $nomenclature->appendChild($items);    
            $refbooks->appendChild($nomenclature);
        }
        $this->rootNode->appendChild($refbooks);
            
        // Документы
        $documents = $this->convertToXmlElement('documents', 'document', $this->getDocumentsData() );
        $this->rootNode->appendChild($documents);
        return $this->dom->saveXML();
    }

    /// Создаёт базовый DOM
    protected function createDom() {
        $this->dom = new \domDocument("1.0", "utf-8");
        $this->rootNode = $this->dom->createElement("multimag_exchange"); // Создаём корневой элемент
        $this->rootNode->setAttribute('version', '1.0');
        $this->dom->appendChild($this->rootNode);
        // Информация о выгрузке
        $result = $this->dom->createElement('result');            // Код возврата
        $result_code = $this->dom->createElement('status', 'ok');
        $result_desc = $this->dom->createElement('message', 'Ok');
        $result_timestamp = $this->dom->createElement('timestamp', time()-1);
        $result->appendChild($result_code);
        $result->appendChild($result_desc);
        $result->appendChild($result_timestamp);
        $this->rootNode->appendChild($result);
    }
}