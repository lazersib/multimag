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

/// Базовый класс документов
class document {
    protected $id;
    protected $firm_id;
    
    protected $doc_data;			///< Основные данные документа
    protected $dop_data;			///< Дополнительные данные документа
    protected $firm_vars;			///< информация с данными о фирме
    protected $def_dop_data=array();            ///< Список дополнительных параметров текущего документа со значениями по умолчанию
    
    protected $def_doc_data = array(
        'id' => 0, 
        'type' => 0, 
        'agent' => null, 
        'comment' => '', 
        'date' => 0, 
        'ok' => 0, 
        'sklad' => null, 
        'user' => null, 
        'altnum' => 0, 
        'subtype' => '', 
        'sum' => 0, 
        'nds' => 1, 
        'p_doc' => null, 
        'mark_del' => 0, 
        'kassa' => null, 
        'bank' => null, 
        'firm_id' => null, 
        'contract' => null, 
        'created' => 0, 
        'agent_name' => '', 
        'agent_fullname' => '', 
        'agent_dishonest' => 0, 
        'agent_comment' => ''
        );

    /// Получить стандартную строку запроса загрузки документа
    static function getStandardSqlQuery() {
        return "SELECT `a`.`id`, `a`.`type`, `a`.`agent`, `b`.`name` AS `agent_name`, `a`.`comment`, `a`.`date`, `a`.`ok`, `a`.`sklad`, 
                `a`.`user`, `a`.`altnum`, `a`.`subtype`, `a`.`sum`, `a`.`nds`, `a`.`p_doc`, `a`.`mark_del`, `a`.`kassa`, `a`.`bank`, `a`.`firm_id`, 
                `b`.`dishonest` AS `agent_dishonest`, `b`.`comment` AS `agent_comment`, `a`.`contract`, `a`.`created`, `b`.`fullname` AS `agent_fullname`
            FROM `doc_list` AS `a`
            LEFT JOIN `doc_agent` AS `b` ON `a`.`agent`=`b`.`id`";
    }
    
    /// 
    static function getClassNameFromType($type) {
        switch($type)	{
            case 1: 
                return "doc_Postuplenie";
            case 2: 
                return "doc_Realizaciya";
            case 3:
                return "doc_Zayavka";
            case 4:
                return "doc_PBank";
            case 5:
                return "doc_RBank";
            case 6:
                return "doc_Pko";
            case 7:
                return "doc_Rko";
            case 8:
                return "doc_Peremeshenie";
            case 9:
                return "doc_PerKas";
            case 10:
                return "doc_Doveren";
            case 11:
                return "doc_Predlojenie";
            case 12:
                return "doc_v_puti";
            case 13:
                return "doc_Kompredl";
            case 14:
                return "doc_Dogovor";
            case 15:
                return "doc_Realiz_op";
            case 16:
                return "doc_Specific";
            case 17:
                return "doc_Sborka";
            case 18:
                return "doc_Kordolga";
            case 19:
                return "doc_Korbonus";
            case 20:
                return "doc_Realiz_bonus";
            case 21:
                return "doc_ZSbor";
            case 22:
                return "doc_Pko_oper";
            default:
                return null;
        }
    }
    
    static function getInstanceFromDb($doc_id) {
        global $db;
        settype($doc_id, 'int');
        $res = $db->query( self::getStandardSqlQuery() . " WHERE `a`.`id`=$doc_id");
        if (!$res->num_rows) {
            throw new \NotFoundException("Документ не найден");
        }
        $doc_data = $res->fetch_assoc();
        return self::getInstanceFromArray($doc_data);
    }
    
    static function getInstanceFromType($type) {
        $doc_class = self::getClassNameFromType($type);
        return new $doc_class;
    }
    
    static function getInstanceFromArray($doc_data) {
        $doc = self::getInstanceFromType($doc_data['type']);
        $doc->loadFromArray($doc_data);
        return $doc;
    }
    
    public function loadFromArray($doc_data) {
        $this->id = $this->doc = (int) $doc_data['id'];
        $this->doc_data = $doc_data;
        $this->loadDopDataFromDb();
    }

    public function loadFromDb($doc_id) {
        global $db;
        $this->id = $this->doc = (int) $doc_id;
        $res = $db->query( $this->getStandardSqlQuery() . " WHERE `a`.`id`={$this->id}");
        if (!$res->num_rows) {
            throw new \NotFoundException("Документ не найден");
        }
        $this->doc_data = $res->fetch_assoc();
        $this->loadDopDataFromDb();        
    }
    
    protected function loadDopDataFromDb() {
        global $db;
        $res = $db->query("SELECT `param`, `value` FROM `doc_dopdata` WHERE `doc`={$this->id}");
        $this->dop_data = array();
        while($nxt = $res->fetch_row())	{
                $this->dop_data[$nxt[0]]=$nxt[1];
        }
        $this->firm_vars = $db->selectRow('doc_vars', $this->doc_data['firm_id']);

        if (method_exists($this, 'initDefDopData')) {
            $this->initDefDopData();
        }
        $this->dop_data = array_merge($this->def_dop_data, $this->dop_data);
    }
}
