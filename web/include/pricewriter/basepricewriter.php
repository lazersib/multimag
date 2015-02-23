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

namespace pricewriter;

/// Базовый класс формирования прайс-листов
class BasePriceWriter {
    protected $view_proizv; ///< Отображать ли наименование производителя
    protected $view_groups; ///< Группы, которые надо отображать. Массив.
    protected $column_count; ///< Кол-во колонок в прайсе
    protected $db;  ///< mysqli коннектор к нужной базе

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

    /// Включает отображение наименования производителя в наименовании товара
    /// @param visible true - отображать , false - не отображать
    public function showProizv($visible = 1) {
        $this->view_proizv = $visible;
    }

    /// Включает режим отображения в прайс-листе только заданных групп товаров
    /// @param groups Массив с id групп, которые должны быть включены в прайс-лист
    public function setViewGroups($groups) {
        $this->view_groups = $groups;
    }

    /// Задаёт количество колонок, отображаемых в прайс-листе
    /// @param count Количество колонок
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
    /// @param cost Id отображаемой цены
    public function setCost($cost = 1) {
        $this->cost_id = $cost;
        settype($this->cost_id, "int");
    }

    /// Получить информации о количестве товара. Формат информации - в конфигурационном файле
    /// @param count	Количество единиц товара на складе
    /// @param transit	Количество единиц товара в пути
    protected function getCountInfo($count, $transit) {
        global $CONFIG;
        if (!isset($CONFIG['site']['vitrina_pcnt_limit'])) {
            $CONFIG['site']['vitrina_pcnt_limit'] = array(1, 10, 100);
        }
        if ($CONFIG['site']['vitrina_pcnt'] == 1) {
            if ($count <= 0) {
                if ($transit) {
                    return 'в пути';
                } else {
                    return 'уточняйте';
                }
            }
            else if ($count <= $CONFIG['site']['vitrina_pcnt_limit'][0]) {
                return '*';
            } else if ($count <= $CONFIG['site']['vitrina_pcnt_limit'][1]) {
                return '**';
            } else if ($count <= $CONFIG['site']['vitrina_pcnt_limit'][2]) {
                return '***';
            } else {
                return '****';
            }
        }
        else if ($CONFIG['site']['vitrina_pcnt'] == 2) {
            if ($count <= 0) {
                if ($transit) {
                    return 'в пути';
                } else {
                    return 'уточняйте';
                }
            } else if ($count <= $CONFIG['site']['vitrina_pcnt_limit'][0]) {
                return 'мало';
            } else if ($count <= $CONFIG['site']['vitrina_pcnt_limit'][1]) {
                return 'есть';
            } else if ($count <= $CONFIG['site']['vitrina_pcnt_limit'][2]) {
                return 'много';
            } else {
                return 'оч.много';
            }
        } else {
            return round($count) . ($transit ? ('(' . $transit . ')') : '');
        }
    }
}
