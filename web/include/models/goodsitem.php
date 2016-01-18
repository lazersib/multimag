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
namespace models;

/// @brief Класс работы с товарными наименованиями
class goodsitem {
    protected $list_tn = 'doc_base';    /// Основная таблица с элементами
    protected $item_id = null;          /// ID текущего элемента
    protected $data = array();          /// Основные данные элемента
    protected $img_data = array();      /// Данные изображений
    protected $def_img_data = array();  /// Данные изображения по умолчанию
    
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
    public function load() {
        return $this->loadMainData();
    }
    
    /// Получить данные
    public function getData() {
        return $this->data;
    }
    
    /// Получить из базы данные по умолчанию для элемента
    public function getDefaultMainData() {
        global $db;
        $data = array();
        $res = $db->query("SHOW COLUMNS FROM '".$db->real_escape_string($this->list_tn).'`');
        while($line = $res->fetch_assoc()) {
            $data[$line['field']] = $line['field'];
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
        $this->data = $db->selectRow($this->list_tn, $this->item_id);
        if(!$this->data) {
            return false;
        }  
        return $this->data;
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
    
    
    
}

