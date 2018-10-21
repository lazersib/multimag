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
namespace models;

/// @brief Класс работы с товарными наименованиями
class goodsitem {
    protected $table_name = 'doc_base';    /// Основная таблица с элементами
    protected $exttable_name = 'doc_base_dop'; /// Дополнительная таблица с элементами
    protected $item_id = null;          /// ID текущего элемента
    protected $data = array();          /// Основные данные элемента
    protected $extdata = array();       /// Дополнительные данные элемента
    protected $img_data = array();      /// Данные изображений
    protected $def_img_data = array();  /// Данные изображения по умолчанию
    
    protected $fields = array('group', 'type_id', 'name', 'desc', 'proizv', 'cost', 'likvid', 'pos_type', 'hidden', 'unit', 'vc', 'stock', 'warranty', 'eol',
            'warranty_type', 'no_export_yml', 'country', 'title_tag', 'meta_keywords', 'meta_description', 'cost_date', 'mult', 'bulkcnt',
            'analog_group', 'mass', 'nds');
    
    protected $extfields = array('type', 'd_int', 'd_ext', 'size');
    
    /// Конструктор
    public function __construct($item_id = null) {
        if($item_id) {
            $this->setID($item_id);
            $this->load();
        }
    }
    
    /// Установить id эелемента
    public function setID($item_id) {
        settype($item_id, 'int');
        $this->item_id = $item_id;
        $this->data = array(); 
        $this->img_data = array(); 
        $this->def_img_data = array(); 
    }
	
    /// Проверить натичие ID
    protected function assertID() {
        if(!$this->item_id) {
            throw new \NotFoundException('Не указан id элемента справочника!');
        }
    }

    /// Загрузить данные
    public function load($full=false) {
        if(!$this->loadMainData()) {
            return false;
        }
        if($full) {
            $this->loadExtData();
            $this->loadImagesData();
        }
        return true;
    }
    
    /// Получить данные
    public function getData() {
        return $this->data;
    }
    
    /// Получить из базы данные по умолчанию для элемента
    public function getDefaultMainData() {
        global $db;
        $data = array();
        $res = $db->query("SHOW COLUMNS FROM `".$db->real_escape_string($this->table_name).'`');
        while($line = $res->fetch_assoc()) {
            $data[$line['Field']] = $line['Default'];
        }
        return $data;
    }

    /// Получить поле
    public function __get($name) {
        if(isset($this->data[$name])) {
            return $this->data[$name];
        }
        return null;
    }
     
    /// Загрузить основные свойства
    public function loadMainData() {
        global $db;
        $this->assertID();
        $this->data = $db->selectRow($this->table_name, $this->item_id);
        if(!$this->data) {
            return false;
        }  
        return $this->data;
    }
    
    /// Загрузить дополнительные свойства
    public function loadExtData() {
        global $db;
        $this->assertID();
        $this->extdata = $db->selectRow($this->exttable_name, $this->item_id);
        if(!$this->extdata) {
            return false;
        }  
        return $this->extdata;
    }
    
    /// Загрузить данные изображений
    public function loadImagesData() {
        global $db;
        $this->assertID();
        $res = $db->query("SELECT `doc_img`.`id`, `doc_img`.`type`, `doc_base_img`.`default`
            FROM `doc_base_img`
            INNER JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
            WHERE `doc_base_img`.`pos_id`='{$this->item_id}'");
        while($line = $res->fetch_assoc()) {
            $this->img_data[$line['id']] = $line;
            if($line['default']) {
                $this->def_img_data = $line;
            }
        }
    }
    
    /// Получить информацию об изображениях
    public function getImagesData() {
        return $this->img_data;
    }
    
    /// Получить информацию об изображении по умолчанию
    public function getImageDefaultData() {
        return $this->def_img_data;
    }
    
    /** Создание новой позиции
     * 
     * @param array $data       Массив с основными совйствами позиции
     * @return int              ID созданной позиции         
     */
    public function create($data) {
        global $db;
        $store_data = array();
        foreach ($this->fields as $field) {
            if ($field == 'nds') {
                if ($data[$field] === '') {
                    $store_data[$field] = null;
                } else {
                    $store_data[$field] = intval($data[$field]);
                }
            } elseif ($field != 'cost_date' && isset($data[$field])) {
                $store_data[$field] = $data[$field];
            }
        }
        $store_data['cost_date'] = date("Y-m-d H:i:s");
        if (\cfg::get('store', 'require_mass')) {
            if ($store_data['mass'] == 0 && $store_data['type'] == 0) {
                throw new \Exception('Обязательное поле *масса* не заполено');
            }
        }
        $pos_id = $db->insertA($this->table_name, $store_data);
        $this->writeLogArray('CREATE', $store_data);

        $res = $db->query("SELECT `id` FROM `doc_sklady`");
        while ($nxt = $res->fetch_row()) {
            $db->query("INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`) VALUES ('$pos_id', '$nxt[0]', '0')");
        }
        
        $this->setID($pos_id);
        $this->data = $store_data;
        return $pos_id;
    }
    
    public function update($data) {
        global $db;
        $this->loadMainData();
        $sql_add = $log_add = '';
        if(\cfg::get('store','leaf_only')) {
            $new_group = intval($data['group']);
            $res = $db->query("SELECT `id` FROM `doc_group` WHERE `pid`=$new_group");
            if ($res->num_rows) {
                throw new \Exception("Запись наименования возможна только в конечную группу!");
            }
        }
        $store_data = array();
        $log_data = array();
        foreach ($this->data as $field => $old_val) {
            if ($field == 'id' || $field == 'likvid' || $field == 'cost_date' || $field == 'pos_type' || !isset($data[$field])) {
                continue;
            }
            if($data[$field]==='null') {
                $data[$field] = NULL;
            }
            if ($data[$field] != $old_val) {
                switch($field) {
                    case 'country':
                        $store_data[$field] = intval($data[$field]);
                        if (!$store_data[$field]) {
                            $store_data[$field] = NULL;
                        }
                        break;
                    case 'cost':
                        $store_data[$field] = round($data[$field], 2);
                        $store_data['cost_date'] = date("Y-m-d H:i:s");
                        break;
                    case 'nds':
                        if ($data[$field] === '') {
                            $store_data[$field] = NULL;
                        } else {
                            $store_data[$field] = intval($data[$field]);
                        }
                        break;
                    default:
                        $store_data[$field] = $data[$field];
                }
                $log_data[$field] = ['old'=>$old_val, 'new'=>$store_data[$field]];
            }            
        }       
        if (\cfg::get('store', 'require_mass') && isset($store_data['mass'])) {
            if ($store_data['mass'] == 0 && $this->data['type'] == 0) {
                throw new \Exception('Нулевая масса запрещена в настройках');
            }
        }
        if (count($store_data)>0) {
            $db->updateA($this->table_name, $this->item_id, $store_data);
            $this->writeLogArray('UPDATE', $log_data);            
            $this->data = array_merge($this->data, $store_data);
            return true;
        } else {
            return false;
        }
    }
    
    /** Создание новой позиции с копированием данных из существующей
     * 
     * @param array $data       Массив с основными совйствами позиции
     * @param int $from_id      ID существующей позиции
     * @return int              ID созданной позиции         
     */
    public function createFrom($data, $from_id=0) {
        global $db;              
        settype($from_id, 'int');
        $this->create($data);
        if ($from_id) {
            $res = $db->query("SELECT `type`, `d_int`, `d_ext`, `size` FROM `doc_base_dop` WHERE `id`='$from_id'");
            $nxt = $res->fetch_assoc();
            if ($nxt) {
                $this->loadExtData();
                $this->setExtDataA($nxt);
            }
        } 
        return $this->item_id;
    }


    public function setExtDataA($array) {
        global $db;
        $store_data = array('id'=>$this->item_id);
        $log_data = array();
        foreach($this->extfields as $field) {
            if(isset($array[$field])) {
                $store_data[$field] = $array[$field];
                $log_data[$field] = ['old'=>$this->extdata[$field], 'new'=>$array[$field]];
            }
        }
        $db->replaceA($this->exttable_name, $store_data);
        $this->writeLogArray('UPDATE', ['extdata'=>$log_data]);
        $this->extdata = array_merge($this->extdata, $store_data);
        return true;
    }
    
    /** Сохранить запись журнала о совершённом действии
     * 
     * @param type $action  Действие
     * @param type $array   Массив с изменениями в формате ['param'=>['old'=>$old_data,'new'=>$new_data],....]
     */
    protected function writeLogArray($action, $array) {
        doc_log($action, json_encode($array, JSON_UNESCAPED_UNICODE), 'pos', $this->item_id);
    }
}

