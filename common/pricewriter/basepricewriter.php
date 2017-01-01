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

namespace pricewriter;

/// Базовый класс формирования прайс-листов
class BasePriceWriter {
    protected $view_proizv;         ///< Отображать ли наименование производителя
    protected $view_groups;         ///< Группы, которые надо отображать. Массив.
    protected $column_count;        ///< Кол-во колонок в прайсе
    protected $db;                  ///< mysqli коннектор к нужной базе
    protected $to_string = false;   ///< Сохранить в буфер, не отправлять в броузер
    protected $vendor_filter = '';  ///< Фильтр по производителю
    protected $count_filter = '';   ///< Фильтр по наличию

    /// Конструктор
    /// @param db mysqli-объект для подключения к базе данных
    public function __construct($db) {
        $this->db = $db;
        $this->column_count = 2;
        $this->view_proizv = 0;
        $this->cost_id = 1;
        $this->view_groups = false;
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
        $this->view_proizv = $visible;
    }

    /// Включает режим отображения в прайс-листе только заданных групп товаров
    /// @param $groups Массив с id групп, которые должны быть включены в прайс-лист
    public function setViewGroups($groups) {
        $this->view_groups = $groups;
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

    /// Устанавливает цену, которая должна быть отображена в прайс-листе
    /// @param $cost Id отображаемой цены
    public function setCost($cost = 1) {
        $this->cost_id = $cost;
        settype($this->cost_id, "int");
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
