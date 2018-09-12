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

namespace pricewriter;

/// Базовый класс формирования прайс-листов
class BasePriceWriter {
    protected $mn_vendor;           ///< Модификатор наименования + наименование производителя
    protected $mn_pgroup;           ///< Модификатор наименования + печатное имя группы
    protected $show_groups;         ///< Группы, которые надо отображать. Массив.
    protected $column_count;        ///< Кол-во колонок в прайсе
    protected $db;                  ///< mysqli коннектор к нужной базе
    protected $to_string = false;   ///< Сохранить в буфер, не отправлять в броузер
    protected $vendor_filter = '';  ///< Фильтр по производителю
    protected $count_filter = '';   ///< Фильтр по наличию
    protected $show_vc = false; ///< Колонока с наименованием производителя
    protected $show_vn = false; ///< Колонока с наименованием производителя
    protected $column_list;     ///< Список колонок для отображения
    protected $price_id;         ///< Идентификатор цены

    /// Конструктор
    /// @param db mysqli-объект для подключения к базе данных
    public function __construct($db) {
        $this->db = $db;
        $this->column_list = ['id'];
        if(\cfg::get('site', 'price_show_vc', false)) {
            $this->column_list[] = 'vc';
        }
        $this->column_list[] = 'name';        
        if(\cfg::get('site', 'price_show_vn', false)) {
            $this->column_list[] = 'vendor';
        }
        $this->column_list[] = 'count'; 
        $this->column_list[] = 'price'; 
        
        $this->column_count = 1;
        $this->mn_vendor = false;
        $this->mn_pgroup = true;     
        $this->price_id = 1;
        $this->show_groups = false;        
    }

    /// Сформировать прайс-лист, и отправить его в STDOUT
    public function run() {
        $this->open();
        $this->write();
        $this->close();
    }
    
    /// Сформировать прайс-лист, и вернуть его
    public function get() {
        $this->to_string = true;
        $this->open();
        $this->write();
        return $this->close();
    }

    /// Включает отображение наименования производителя в наименовании товара
    /// @param $visible true - отображать , false - не отображать
    public function showProizv($visible = 1) {
        $this->mn_vendor = $visible;
    }

    /// Включает отображение префикса наименования - печатного наименования группы
    /// @param $visible true - отображать , false - не отображать
    public function showGroupName($visible = 1) {
        $this->mn_pgroup = $visible;
    }
    
    /// Включает фильтр по группам номенклатуры
    /// @param $groups Массив с id групп, которые должны быть включены в прайс-лист
    public function setGroupsFilter($groups) {
        $this->show_groups = $groups;
    }

    /// Задаёт количество колонок, отображаемых в прайс-листе
    /// @param $count Количество колонок
    public function setColCount($count) {
        $this->column_count = $count;
        settype($this->column_count, "int");
        if ($this->column_count < 1) {
            $this->column_count = 1;
        }
        if ($this->column_count > 5) {
            $this->column_count = 5;
        }
    }
    
    /**
     * Устанавливает список колонок для отображения в прайс-листе
     * @param array $clist Массив с идентификаторами колонок
     */    
    public function setColumnList($clist) {        
        $this->column_list = $clist;
    }

    /// Устанавливает цену, которая должна быть отображена в прайс-листе
    /// @param $cost Id отображаемой цены
    public function setPriceId($price_id = 1) {
        $this->price_id = $price_id;
        settype($this->price_id, "int");
    }
    
    /// Устанавливает фильтрацию прайса по заданному производителю
    /// @param $vendorfilter Имя производителя
    public function setVendorFilter($vendorfilter = null) {
        $this->vendor_filter = $vendorfilter;
    }
    
    /// Устанавливает фильтрацию прайса по наличию
    /// @param $countfilter Фильтр наличия: all / instock / intransit
    public function setCountFilter($countfilter = 'all') {
        $this->count_filter = $countfilter;
    }

    /// Получить информации о количестве товара. Формат информации - в конфигурационном файле
    /// @param $count	Количество единиц товара на складе
    /// @param $transit	Количество единиц товара в пути
    protected function getCountInfo($count, $transit) {        
        $vars = array(
            1 => array(
                '*',
                '**',
                '***',
                '****',
            ),
            2 => array(
                'мало',
                'есть',
                'много',
                'оч.много',
            ),
        );
        $pcnt_limit = \cfg::get('site', 'vitrina_pcnt_limit', array(1, 10, 100));
        $pcnt = \cfg::get('site', 'vitrina_pcnt', 0);
        if ($pcnt == 1 || $pcnt == 2) {
            if ($count <= 0) {
                if ($transit) {
                    return 'в пути';
                } else {
                    return 'уточняйте';
                }
            }
            for($i=0;$i<3;$i++) {
                if ($count <= $pcnt_limit[$i]) {
                    return $vars[$pcnt][$i];
                } 
            }
            return $vars[$pcnt][3];
        }
        return round($count) . ($transit ? ('(' . $transit . ')') : '');
    }
}
