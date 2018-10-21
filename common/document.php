<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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
    protected $id = null;			///< ID документа
    protected $typename;			///< Наименование типа документа    (для контроля прав и пр.)
    protected $viewname;                        ///< Отображаемое название документа при просмотре и печати
    
    protected $doc_data;			///< Основные данные документа
    protected $dop_data;			///< Дополнительные данные документа
    protected $text_data = [];                  ///< Дополнительные текстовые данные документа
    protected $firm_vars;			///< информация с данными о фирме
    protected $def_dop_data = [];               ///< Список дополнительных параметров текущего документа со значениями по умолчанию
    
    protected $def_doc_data = [
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
    ];
    
    const DOC_TYPES = [  /// TODO: вынести в фабрику документов
        1 => "postuplenie",
        2 => "realizaciya",
        3 => "zayavka",
        4 => "pbank",
        5 => "rbank",
        6 => "pko",
        7 => "rko",
        8 => "peremeshenie",
        9 => "perkas",
        10 => "doveren",
        11 => "predlojenie",
        12 => "v_puti",
        13 => "kompredl",
        14 => "dogovor",
        15 => "realiz_op",
        16 => "specific",
        17 => "sborka",
        18 => "kordolga",
        19 => "korbonus",
        20 => "realiz_bonus",
        21 => "zsbor",
        22 => "pko_oper",
        23 => "permitout",
        24 => "payinfo",
        25 => "corract",
    ];

    /// Получить ID документа
    public function getId() {
        return $this->id;
    }

    /// Получить кодовое имя типа документа
    public function getTypeName() {
        return $this->typename;
    }
    
    /// Получить отображаемое имя документа
    public function getViewName() {
        return $this->viewname;
    }

    /// Получить стандартную строку запроса загрузки документа
    static function getStandardSqlQuery() {
        return "SELECT `a`.`id`, `a`.`type`, `a`.`agent`, `b`.`name` AS `agent_name`, `a`.`comment`, `a`.`date`, `a`.`ok`, `a`.`sklad`, 
                `a`.`user`, `a`.`altnum`, `a`.`subtype`, `a`.`sum`, `a`.`nds`, `a`.`p_doc`, `a`.`mark_del`, `a`.`kassa`, `a`.`bank`, `a`.`firm_id`, 
                `b`.`dishonest` AS `agent_dishonest`, `b`.`comment` AS `agent_comment`, `a`.`contract`, `a`.`created`, `b`.`fullname` AS `agent_fullname`
            FROM `doc_list` AS `a`
            LEFT JOIN `doc_agent` AS `b` ON `a`.`agent`=`b`.`id`";
    }
    
    /// Получить имя класса документа по его номеру типа
    static function getClassNameFromType($type) {
        return self::getClassNameFromName(self::getNameFromType($type));
    }
    
    static public function getNameFromType($type) {
        if(array_key_exists($type, self::DOC_TYPES)) {
            return self::DOC_TYPES[$type];
        }
        return null;
    }
    
    /// Получить имя класса документа по его имени
    static function getClassNameFromName($doc_name) {
        if(in_array($doc_name, self::DOC_TYPES)) {
            return '\doc_'.$doc_name;
        }
        return null;
    }
    
    static public function getViewNameFromName($doc_name) {
        $classname = self::getClassNameFromName($doc_name);
        if($classname == null) {
            return null;
        }
        $doc = new $classname;
        return $doc->viewname;
    }

        /// Получить спискок типов документов
    static function getListTypes() {
        return self::DOC_TYPES;
    }
    
    /// @return document
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
        if($doc_class==null) {
            throw new \LogicException('Запрошенный тип документа ('.html_out($doc_class).') не зарегистрирован!');
        }
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
        $this->loadTextDataFromDb();
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
        $this->loadTextDataFromDb();
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
    
    protected function loadTextDataFromDb() {
        global $db;
        $res = $db->query("SELECT `param`, `value` FROM `doc_textdata` WHERE `doc_id`={$this->id}");
        $this->text_data = array();
        while($nxt = $res->fetch_row())	{
                $this->text_data[$nxt[0]]=$nxt[1];
        }
    }
    
    /** Сохранить запись журнала о совершённом действии
     * 
     * @param type $action  Действие
     * @param type $array   Массив с изменениями в формате ['param'=>['old'=>$old_data,'new'=>$new_data],....]
     */
    protected function writeLogArray($action, $array) {
        doc_log($action, json_encode($array, JSON_UNESCAPED_UNICODE), 'doc', $this->id);
    }
  
    /// @brief Получить значение основного параметра документа.
    /// Вернёт пустую строку в случае отсутствия параметра
    /// @param name Имя параметра
    public function getDocData($name) {
        if (isset($this->doc_data[$name])) {
            return $this->doc_data[$name];
        } else {
            return '';
        }
    }
    
    /// Получить все основные параметры документа в виде ассоциативного массива
    public function getDocDataA() {
        return $this->doc_data;
    }
    
    /// Установить основной параметр документа
    public function setDocData($name, $value) {
        global $db;
        if(!isset($this->doc_data[$name])) {
            $this->doc_data[$name] = null;
        }
        if ($this->id && $this->doc_data[$name] !== $value) {
            $_name = $db->real_escape_string($name);
            $db->update('doc_list', $this->id, $_name, $value);
            $log_data = [$name => ['old'=>$this->doc_data[$name], 'new'=>$value] ];
            $this->writeLogArray("UPDATE", $log_data);
        }
        $this->doc_data[$name] = $value;
    }
    
    protected function setDocDataA($data) {
        global $db;
        $log_data = array();
        $res = $db->query("SHOW COLUMNS FROM `doc_list`");
        $col_array = array();
        while ($nxt = $res->fetch_row()) {
            $col_array[$nxt[0]] = $nxt[0];
        }
        unset($col_array['id']);
        $i_data = array_intersect_key($this->doc_data, $col_array);

        if ($this->id) {
            $to_write_data = array_diff_assoc($data, $i_data);
            foreach ($to_write_data as $name => $value) {
                if (!isset($this->doc_data[$name])) {
                    $log_data[$name] = ['new' => $value];
                } else if ($this->doc_data[$name] !== $value) {
                    $log_data[$name] = ['old' => $this->doc_data[$name], 'new' => $value];
                }
            }
            if (count($to_write_data) > 0) {
                $db->updateA('doc_list', $this->id, $to_write_data);
                $this->writeLogArray('UPDATE', $log_data);
            }
        } else {
            $to_write_data = array_intersect_key($data, $i_data);
            $this->id = $db->insertA('doc_list', $to_write_data);
            $this->writeLogArray("CREATE", $to_write_data);
        }
        foreach ($to_write_data as $name => $value) {
            $this->doc_data[$name] = $value;
        }
        return $this->id;
    }
    
    /// @brief Получить значение дополнительного параметра документа.
    /// Вернёт пустую строку в случае отсутствия параметра
    /// @param name Имя параметра
    public function getDopData($name) {
        if (isset($this->dop_data[$name])) {
            return $this->dop_data[$name];
        } else {
            return '';
        }
    }
    
    /// Получить все дополнительные параметры документа в виде ассоциативного массива
    public function getDopDataA() {
        return $this->dop_data;
    }
    
    /** Установить дополнительный параметр текущего документа
     * Записывает изменения в базу. Изменения так же автоматически вносятся в лог.
     * @param string $name  Имя параметра
     * @param string $value Значение параметра
     */
    public function setDopData($name, $value) {
        global $db;
        if(!isset($this->dop_data[$name])) {
            $this->dop_data[$name] = null;
        }
        if ($this->id && $this->dop_data[$name] !== $value) {
            $_name = $db->real_escape_string($name);
            $_value = $db->real_escape_string($value);
            $db->query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`) VALUES ( '{$this->id}' ,'$_name','$_value')");
            $log_data = [$name => ['old'=>$this->dop_data[$name], 'new'=>$value] ];
            $this->writeLogArray("UPDATE", $log_data);
        }
        $this->dop_data[$name] = $value;
        return true;
    }
    
    /// Установить дополнительные данные текущего документа
    public function setDopDataA($array) {
        global $db;
        if ($this->id) {
            $to_write_data = array_diff_assoc($array, $this->dop_data);
            $log_data = array();
            foreach ($to_write_data as $name => $value) {
                if(!isset($this->dop_data[$name])) {
                    $this->dop_data[$name] = null;
                }
                $log_data[$name] = ['old'=>$this->dop_data[$name], 'new'=>$value];                
                $this->dop_data[$name] = $value;
            }
            if(count($to_write_data)>0) {
                $db->replaceKA('doc_dopdata', 'doc', $this->id, $to_write_data);
                $this->writeLogArray("UPDATE", $log_data);
            }
        }
    }
    
    public function getTextData($name) {
        if(isset($this->text_data[$name])) {
            return $this->text_data[$name];
        }
        else {
            return '';
        }
    }
    
    /** Установить текстовый параметр текущего документа
     * Записывает изменения в базу. Изменения так же автоматически вносятся в лог.
     * @param string $name  Имя параметра
     * @param string $value Значение параметра
     */
    public function setTextData($name, $value) {
        global $db;
        if(!isset($this->text_data[$name])) {
            $this->text_data[$name] = null;
        }
        if ($this->id && $this->text_data[$name] !== $value) {
            $_name = $db->real_escape_string($name);
            $_value = $db->real_escape_string($value);
            $db->query("REPLACE INTO `doc_textdata` (`doc_id`,`param`,`value`) VALUES ( '{$this->id}' ,'$_name','$_value')");
            $log_data = [$name => ['old'=>$this->text_data[$name], 'new'=>$value] ];
            $this->writeLogArray("UPDATE", $log_data);
        }
        $this->text_data[$name] = $value;
        return true;
    }
    
    /** 
     * Отметить документ для удаления
     * @throws Exception Есть подчинённые документы без пометок на удаление
     */
    public function markForDelete() {
        global $db;
        if ($this->getDocData('mark_del')>0) { // Уже отмечен на удаление
            return false;
        } 
        if ($this->getDocData('ok')) {
            throw new \Exception("Удаление проведённых документов не возможно!");
        } 
        $res = $db->query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$this->id}' AND `mark_del`='0'");
        if ($res->num_rows) {
            throw new \Exception("Есть подчинённые документы без пометок на удаление. Удаление невозможно.");
        }
        $tim = time();
        $db->update('doc_list', $this->id, 'mark_del', $tim);
        $this->writeLogArray("MARKDELETE", []);
        return $tim;
    }
    
    public function unMarkDelete() {
        global $db;
        if ($this->getDocData('mark_del')==0) { // Не отмечен на удаление
            return false;
        }
        $db->update('doc_list', $this->id, 'mark_del', 0);
        $this->writeLogArray("UNMARKDELETE", []);
        return true;
    }
    
    /// Сделать документ потомком указанного документа
    function subordinate($p_doc) {
        global $db;        
        if ($this->id == $p_doc) {
            throw new \Exception('Нельзя связать с самим собой!');
        }
        if ($this->doc_data['ok']) {
            throw new \Exception("Операция не допускается для проведённого документа!");
        }
        if ($p_doc != 0) {
            // Проверяем существование документа
            $res = $db->query("SELECT `p_doc` FROM `doc_list` WHERE `id`=$p_doc");
            if (!$res->num_rows) {
                throw new \Exception('Документ с ID ' . $p_doc . ' не найден.');
            }
        }
        $this->setDocData('p_doc', $p_doc);
        return true;
    }
    
    /// Получить все текстовые параметры документа в виде ассоциативного массива
    public function getTextDataA() {
        return $this->text_data;
    }
    
    /// Получить список документов, в которые может быть преобразован текущий
    /// Переопределяется у потомков
    public function getMorphList() {
        return [];
    }
    
    /// Создать подчинённый документ из текущего
    public function morph($target) {
        $ml = $this->getMorphList();
        $info = null;
        foreach($ml as $m_info) {
            if($m_info['name']===$target) {
                $info = $m_info;
                break;
            }
        }
        if(!$info) {
            throw new \Exception("Неверный код целевого документа.");
        }
        $method = 'morphTo_'.$info['name'];
        if(!method_exists($this, $method)) {
            throw new \Exception("Метод морфинга не определён.");
        }        
        return $this->$method($target);
    }
    
    /** Получить краткую информацию о родительском документе
     * 
     * @return array Информация о родительском бокументе или null 
     */
    public function getParentInfo() {
        global $db;
        if(!$this->id || !$this->doc_data['p_doc']) {
            return null;
        }
        $p_doc = intval($this->doc_data['p_doc']);
        $res = $db->query("SELECT `id`, `type`, `altnum`, `subtype`, `date`, `ok`
            FROM `doc_list`
            WHERE `doc_list`.`id`='$p_doc'");
        $line = $res->fetch_assoc();
        if(!$line) {
            return null;
        }
        $line['typename'] = self::getNameFromType($line['type']);
        $line['viewname'] = self::getViewNameFromName($line['typename']);
        unset($line['type']);
        $line['date'] = date("d.m.Y H:i:s", $line['date']);
        return $line;            
    }
    
    /** Получить краткую информацию о подчинённых документах
     * 
     * @global type $db
     * @return type
     */
    public function getSubordinatesInfo() {
        global $db;
        if(!$this->id) {
            return null;
        }
        $ret = array();
        $res = $db->query("SELECT `id`, `type`, `altnum`, `subtype`, `date`, `ok` FROM `doc_list`
            WHERE `doc_list`.`p_doc`='{$this->id}'");
        $pod = '';
        while ($line = $res->fetch_assoc()) {
            $line['typename'] = self::getNameFromType($line['type']);
            $line['viewname'] = self::getViewNameFromName($line['typename']);
            unset($line['type']);
            $line['date'] = date("d.m.Y H:i:s", $line['date']);
            $ret[] = $line;
        }
        return $ret;
    }
    
    /// Слияние или перезапись табличной части двух документов
    public function refillPosList($from_doc_id, $preclear = 0, $no_sum = 0) {
        global $db;
        if (!$this->sklad_editor_enable) {
            throw new \Exception('Номенклатурная таблица в этом документе отсутствует!');
        }

        include_once(\cfg::get('site', 'location') . "/include/doc.poseditor.php");
        $poseditor = new \DocPosEditor($this);
        $poseditor->setAllowNegativeCounts($this->allow_neg_cnt);            

        if ($from_doc_id == 0) {
            throw new \Exception("Документ с данными не задан");
        }
        $db->startTransaction();

        $res = $db->query("SELECT `id` FROM `doc_list` WHERE `id`=$from_doc_id");
        if (!$res->num_rows) {
            throw new \Exception("Документ с данными не найден");
        }
        if ($preclear) {
            $db->query("DELETE FROM `doc_list_pos` WHERE `doc`='{$this->id}'");
        }
        $res = $db->query("SELECT `doc`, `tovar`, SUM(`cnt`) AS `cnt`, `gtd`, `comm`, `cost`, `page` FROM `doc_list_pos`"
            . "WHERE `doc`=$from_doc_id AND `page`=0 GROUP BY `tovar`");
        while ($line = $res->fetch_assoc()) {
            if (!$no_sum) {
                $poseditor->simpleIncrementPos($line['tovar'], $line['cost'], $line['cnt'], $line['comm']);
            } else {
                $poseditor->simpleRewritePos($line['tovar'], $line['cost'], $line['cnt'], $line['comm']);
            }
        }
        $this->writeLogArray("REWRITE", []);
        $db->commit();

        return true;
    }
}
