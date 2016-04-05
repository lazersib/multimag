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

/// Класс загрузки XML файла для обмена с 1с
class simplexml1cdataimport extends \sync\dataimport {
    protected $xml;                 //< Объект SimleXMLElement
    protected $newids;

    public function __construct($db) {
        parent::__construct($db);
        libxml_use_internal_errors(true);
        $this->newids = array('firms');
    }
    
    public function importData() {
        if($this->xml->getName() != 'multimag_exchange') {
            throw new \Exception('Файл не является файлом обмена между 1с и multimag!');
        }
        // Тест статуса
        $status = '';
        $rbnode = null;
        $docnode = null;
        foreach($this->xml->children() as $name => $node) {
            if($name=='result') {
                $message = '';
                foreach($node->children() as $rchild) {
                   switch($rchild->getName()) {
                       case 'status':
                           $status = $rchild->__toString();
                           break;
                       case 'message':
                           $message = $rchild->__toString();
                           break;
                   }
                }
                if($status!='ok') {
                    throw new \Exception('Файл обмена сообщает об ошибке: '.$message);
                }
            } elseif($name == 'refbooks') {
                $rbnode = $node;
            } elseif ($name == 'documents') {
                $docnode = $node;
            }
        }
        if($status == '') {
            throw new \Exception('Неполный файл: нет информации о статусе!');
        }
        // Загружаем справочники
        foreach($rbnode->children() as $name => $node) {
            switch ($name) {
                case 'firms':
                    $this->parseFirmsNode($node);
                    break;
                case 'stores':
                    $this->parseStoresNode($node);
                    break;
                case 'banks':
                    $this->parseBanksNode($node);
                    break;
                case 'tills':
                    $this->parseTillsNode($node);
                    break;
                case 'prices':
                    $this->parsePricesNode($node);
                    break;
                case 'countries':
                    $this->parseCountriesNode($node);
                    break;
                case 'units':
                    $this->parseUnitsNode($node);
                    break;
                case 'agents':
                    $this->parseAgentsNode($node);
                    break;
                case 'nomenclature':
                    $this->parseNomenclatureNode($node);
                    break;
            }
        }
        // Загружаем документы
        if($docnode) {
            $this->parseDocumentNode($docnode);
        }
        // Формирование ответа
        $out = new \SimpleXMLElement('<multimag_exchange version="1.0.1"><result><status>ok</status><message>Ok</message></result></multimag_exchange>');
        if(count($this->newids)>0) {
            $refbooks = $out->addChild('refbooks');
            if(isset($this->newids['firms'])) {
                foreach($this->newids['firms'] as $id => $newid) {
                    $group_node = $refbooks->addChild('firm');
                    $group_node->addAttribute('id', $id);
                    $group_node->addAttribute('newid', $newid);
                }
            }
            if(isset($this->newids['stores'])) {
                foreach($this->newids['stores'] as $id => $newid) {
                    $group_node = $refbooks->addChild('store');
                    $group_node->addAttribute('id', $id);
                    $group_node->addAttribute('newid', $newid);
                }
            }
            if(isset($this->newids['banks'])) {
                foreach($this->newids['banks'] as $id => $newid) {
                    $group_node = $refbooks->addChild('bank');
                    $group_node->addAttribute('id', $id);
                    $group_node->addAttribute('newid', $newid);
                }
            }
            if(isset($this->newids['prices'])) {
                foreach($this->newids['prices'] as $id => $newid) {
                    $group_node = $refbooks->addChild('price');
                    $group_node->addAttribute('id', $id);
                    $group_node->addAttribute('newid', $newid);
                }
            }
            if(isset($this->newids['agent.groups']) || isset($this->newids['agent.items'])) {
                $agents = $refbooks->addChild('agents');
                if(isset($this->newids['agent.groups'])) {
                    $groups = $agents->addChild('groups');
                    foreach($this->newids['agent.groups'] as $id => $newid) {
                        $group_node = $groups->addChild('group');
                        $group_node->addAttribute('id', $id);
                        $group_node->addAttribute('newid', $newid);
                    }
                }
                if(isset($this->newids['agent.items'])) {
                    $groups = $agents->addChild('items');
                    foreach($this->newids['agent.items'] as $id => $newid) {
                        $group_node = $groups->addChild('item');
                        $group_node->addAttribute('id', $id);
                        $group_node->addAttribute('newid', $newid);
                    }
                }
            }
            if(isset($this->newids['nomenclature.groups']) || isset($this->newids['nomenclature.items'])) {
                $noms = $refbooks->addChild('nomenclature');
                if(isset($this->newids['nomenclature.groups'])) {
                    $groups = $noms->addChild('groups');
                    foreach($this->newids['nomenclature.groups'] as $id => $newid) {
                        $group_node = $groups->addChild('group');
                        $group_node->addAttribute('id', $id);
                        $group_node->addAttribute('newid', $newid);
                    }
                }
                if(isset($this->newids['nomenclature.items'])) {
                    $groups = $noms->addChild('items');
                    foreach($this->newids['agent.items'] as $id => $newid) {
                        $group_node = $groups->addChild('item');
                        $group_node->addAttribute('id', $id);
                        $group_node->addAttribute('newid', $newid);
                    }
                }
            }
        }
        return $out->asXML();
    }

    public function loadFromString($str) {
        $this->xml = simplexml_load_string($str);
        $error = libxml_get_last_error();
        if($error) {
            throw new \Exception('Ошибка разбора XML: '.$error->message, $error->code);
        }
    }
    
    public function loadFromFile($filename) {
        $this->xml = simplexml_load_file($filename);
        $error = libxml_get_last_error();
        if($error) {
            throw new \Exception('Ошибка разбора XML: '.$error->message, $error->code);
        }
    }
    
    protected function parseFirmsNode($node) {
        $newids = array();
        foreach($node->children() as $cname => $cnode) {
            if($cname!='firm') {
                throw new \Exception('Недопустимый элемент в блоке организаций!');
            }
            $data = array();
            $att = $cnode->attributes();
            $id = intval($att['id']);
            foreach($cnode as $name => $value) {
                $data[$name] = $value->__toString();
            }
            $newid = $this->loadFirmObject($id, $data);
            if(!$id) {
                $newids[$att['id']->__toString()] = $newid;
            }
        }
        $this->newids['firms'] = $newids;
    }
    
    protected function parseStoresNode($node) {
        $newids = array();
        foreach($node->children() as $cname => $cnode) {
            if($cname!='store') {
                throw new \Exception('Недопустимый элемент в блоке складов!');
            }
            $data = array();
            $att = $cnode->attributes();
            $id = intval($att['id']);
            foreach($cnode as $name => $value) {
                $data[$name] = $value->__toString();
            }
            $newid = $this->loadStoreObject($id, $data);
            if(!$id) {
                $newids[$att['id']->__toString()] = $newid;
            }
        }
        $this->newids['stores'] = $newids;
    }
    
    protected function parseBanksNode($node) {
        $newids = array();
        foreach($node->children() as $cname => $cnode) {
            if($cname!='bank') {
                throw new \Exception('Недопустимый элемент в блоке банков!');
            }
            $data = array();
            $att = $cnode->attributes();
            $id = intval($att['id']);
            foreach($cnode as $name => $value) {
                $data[$name] = $value->__toString();
            }
            $newid = $this->loadBankObject($id, $data);
            if(!$id) {
                $newids[$att['id']->__toString()] = $newid;
            }
        }
        $this->newids['bank'] = $newids;
    }
    
    protected function parseTillsNode($node) {
        $newids = array();
        foreach($node->children() as $cname => $cnode) {
            if($cname!='till') {
                throw new \Exception('Недопустимый элемент в блоке касс!');
            }
            $data = array();
            $att = $cnode->attributes();
            $id = intval($att['id']);
            foreach($cnode as $name => $value) {
                $data[$name] = $value->__toString();
            }
            $newid = $this->loadTillObject($id, $data);
            if(!$id) {
                $newids[$att['id']->__toString()] = $newid;
            }
        }
        $this->newids['till'] = $newids;
    }
    
    protected function parseUnitsNode($node) {
        foreach($node->children() as $cname => $cnode) {
            if($cname!='unit') {
                throw new \Exception('Недопустимый элемент в блоке единиц измерения!');
            }
            $data = array();
            foreach($cnode as $name => $value) {
                $data[$name] = $value->__toString();
            }
            $this->loadUnitObjectForCode($data);
        }
    }
    
    protected function parseCountriesNode($node) {
        foreach($node->children() as $cname => $cnode) {
            if($cname!='country') {
                throw new \Exception('Недопустимый элемент в блоке стран мира!');
            }
            $data = array();
            foreach($cnode as $name => $value) {
                $data[$name] = $value->__toString();
            }
            $this->loadCountryObjectForCode($data);
        }
    }
    
    protected function parsePricesNode($node) {
        $newids = array();
        foreach($node->children() as $cname => $cnode) {
            if($cname!='price') {
                throw new \Exception('Недопустимый элемент в блоке цен!');
            }
            $data = array();
            $att = $cnode->attributes();
            $id = intval($att['id']);
            foreach($cnode as $name => $value) {
                $data[$name] = $value->__toString();
            }
            $newid = $this->loadPriceObject($id, $data);
            if(!$id) {
                $newids[$att['id']->__toString()] = $newid;
            }
        }
        $this->newids['price'] = $newids;
    }
    
    protected function parseAgentsNode($node) {
        foreach($node->children() as $cname => $cnode) {
            switch($cname) {
                case 'groups':
                    $this->parseAgentGroupsNode($cnode);
                    break;
                case 'items':
                    $this->parseAgentItemsNode($cnode);
                    break;
                default:
                   throw new \Exception('Недопустимый элемент в блоке агентов!'); 
            }
        }
    }
    
    protected function parseAgentGroupsNode($node) {
        $newids = array();
        foreach($node->children() as $cname => $cnode) {
            if($cname!='group') {
                throw new \Exception('Недопустимый элемент в блоке групп агентов!');
            }
            $data = array();
            $att = $cnode->attributes();
            $id = intval($att['id']);
            foreach($cnode as $name => $value) {
                $data[$name] = $value->__toString();
            }
            $newid = $this->loadAgentGroupObject($id, $data);
            if(!$id) {
                $newids[$att['id']->__toString()] = $newid;
            }
        }
        $this->newids['agent.groups'] = $newids;
    }
    
    protected function parseAgentItemsNode($node) {
        $newids = array();
        foreach($node->children() as $cname => $cnode) {
            if($cname!='item') {
                throw new \Exception('Недопустимый элемент в блоке элементов агентов!');
            }
            $data = array();
            $att = $cnode->attributes();
            $id = intval($att['id']);
            $contacts = null;
            $bank_details = null;
            foreach($cnode as $name => $value) {
                switch($name) {
                    case 'contacts':
                        $contacts = $value;
                        break;
                    case 'bank_details':
                        $bank_details = $value;
                        break;
                    default:
                        $data[$name] = $value->__toString();
                }
            }
            
            $newid = $this->loadAgentItemObject($id, $data);
            if(!$id) {
                $newids[$att['id']->__toString()] = $newid;
            }
        }
        $this->newids['agent.items'] = $newids;
    }
    
    protected function parseNomenclatureNode($node) {
        foreach($node->children() as $cname => $cnode) {
            switch($cname) {
                case 'groups':
                    $this->parseNomenclatureGroupsNode($cnode);
                    break;
                case 'items':
                    $this->parseNomenclatureItemsNode($cnode);
                    break;
                default:
                   throw new \Exception('Недопустимый элемент в блоке номенклатуры!'); 
            }
        }
    }
    
    protected function parseNomenclatureGroupsNode($node) {
        $newids = array();
        foreach($node->children() as $cname => $cnode) {
            if($cname!='group') {
                throw new \Exception('Недопустимый элемент в блоке групп номенклатуры!');
            }
            $data = array();
            $att = $cnode->attributes();
            $id = intval($att['id']);
            foreach($cnode as $name => $value) {
                $data[$name] = $value->__toString();
            }
            $newid = $this->loadNomenclatureGroupObject($id, $data);
            if(!$id) {
                $newids[$att['id']->__toString()] = $newid;
            }
        }
        $this->newids['nomenclature.groups'] = $newids;
    }
    
    protected function parseNomenclatureItemsNode($node) {
        $newids = array();
        foreach($node->children() as $cname => $cnode) {
            if($cname!='item') {
                throw new \Exception('Недопустимый элемент в блоке элементов номенклатуры!');
            }
            $data = array();
            $att = $cnode->attributes();
            $id = intval($att['id']);
            $prices = null;
            foreach($cnode as $name => $value) {
                switch($name) {
                    case 'prices':
                        $prices = $value;
                        break;
                    default:
                        $data[$name] = $value->__toString();
                }
            }
            
            $newid = $this->loadNomenclatureItemObject($id, $data);
            if(!$id) {
                $newids[$att['id']->__toString()] = $newid;
            }
        }
        $this->newids['nomenclature.items'] = $newids;
    }
    
    protected function parseDocumentNode($node) {
        foreach($node->children() as $cname => $cnode) {
            if($cname!='document') {
                throw new \Exception('Недопустимый элемент в блоке документов!');
            }
            $data = array();
            $att = $cnode->attributes();
            $id = intval($att['id']);
            $positions = null;
            foreach($cnode as $name => $value) {
                switch($name) {
                    case 'positions':
                        $positions = $value;
                        break;
                    default:
                        $data[$name] = $value->__toString();
                }
            }
            if($positions) {
                $data['positions'] = array();
                $line = array();
                foreach($positions as $pname => $pnode) {
                    if($pname != 'position')  {
                        throw new \Exception('Недопустимый элемент в табличной части документа!');
                    }
                    foreach($pnode as $pi_name => $pi_value) {
                        $line[$pi_name] = $pi_value->__toString();      
                    }
                }  
                $data['positions'][] = $line;
            }
            $this->loadDocumentObject($id, $data);
        }
    }
}