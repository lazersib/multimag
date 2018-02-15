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

/// Класс формирует прайс-лист в формате CSV
class csv extends BasePriceWriter {

    var $divider;  //< Разделитель
    var $shielder;  //< Экранирование строк
    var $line;  //< Текущая строка
    var $buffer;
    var $column_count;
    protected $svc;

    /// Конструктор
    function __construct($db) {
        parent::__construct($db);
        $this->divider = ";";
        $this->shielder = '"';
        $this->line = 0;
        $this->buffer = '';
        $this->column_count = 1;
        $this->svc = \cfg::get('site', 'price_show_vc');
    }

    /// Установить символ разделителя колонок
    /// @param $divider Символ разделителя колонок (,;:)
    function setDivider($divider = ",") {
        $this->divider = $divider;
        if ($this->divider != ";" && $this->divider != ":") {
            $this->divider = ",";
        }
    }
    
    /// Задать колонок
    /// @param $divider Символ разделителя колонок (,;:)
    function setColumnsCount($count = 1) {
        $this->column_count = $count;
    }

    /// Установить символ экранирования строк
    /// @param $shielder Символ экранирования строк ('"*)
    function setShielder($shielder = '"') {
        $this->shielder = $shielder;
        if ($this->shielder != "'" && $this->shielder != "*") {
            $this->shielder = "\"";
        }
    }

    /// Сформировать шапку прайса
    function open() {        
        for ($i = 0; $i < $this->column_count; $i++) {
            if ($this->svc) {
                $this->buffer.= $this->shielder . "Код" . $this->shielder . $this->divider;
            }
            $this->buffer.= $this->shielder . "Название" . $this->shielder . $this->divider . $this->shielder . "Цена" . $this->shielder;
            if ($i < ($this->column_count - 1)) {
                $this->buffer.=  $this->divider . $this->shielder . $this->shielder . $this->divider;
            }
        }
        $this->buffer.= "\n";
        $this->line++;
    }

    /// Сформирвать тело прайса
    /// param $group id номенклатурной группы
    function write($group = 0) {
        $res = $this->db->query("SELECT `id`, `name`, `printname` FROM `doc_group` WHERE `pid`='$group' AND `hidelevel`='0' ORDER BY `id`");
        while ($nxt = $res->fetch_row()) {
            if ($nxt[0] == 0) {
                continue;
            }
            if (is_array($this->view_groups)) {
                if (!in_array($nxt[0], $this->view_groups)) {
                    continue;
                }
            }

            $this->line++;
            if ($this->svc) {
                $this->buffer.= $this->divider;
            }
            $this->buffer.= $this->shielder . $nxt[1] . $this->shielder;
            $this->buffer.="\n";
            $this->writepos($nxt[0], $nxt[2]);
            $this->write($nxt[0]); // рекурсия
        }
    }

    /// Сформировать завершающий блок прайса
    function close() {
        $pref = \pref::getInstance();
        $this->buffer.="\n\n";
        $this->line+=5;
        if ($this->svc) {
            $this->buffer.= $this->divider;
        }
        $this->buffer.= $this->shielder . "Generated from MultiMag (http://multimag.tndproject.org), for http://" . $pref->site_name . $this->shielder;
        $this->line++;
        $this->buffer.="\n";
        if ($this->svc) {
            $this->buffer.= $this->divider;
        }
        $this->buffer.= $this->shielder . "Прайс создан системой MultiMag (http://multimag.tndproject.org), специально для http://" . $pref->site_name . $this->shielder;
        if($this->to_string) {
            return $this->buffer;
        }
        else {
            header("Content-Type: text/csv; charset=utf-8");
            header("Content-Disposition: attachment; filename=price_list.csv;");
            header("Content-Length: ".strlen($this->buffer));
            echo $this->buffer;
        }
    }

    /// Сформировать строки прайса
    /// param $group id номенклатурной группы
    function writepos($group = 0) {
        $pref = \pref::getInstance();
        $cnt_where = $pref->getSitePref('site_store_id') ? (" AND `doc_base_cnt`.`sklad`=" . intval($pref->getSitePref('site_store_id')) . " ") : '';

        $res = $this->db->query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost_date` , `doc_base`.`proizv`, `doc_base`.`vc`,		
			( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` $cnt_where) AS `cnt`,
				`doc_base_dop`.`transit`, `doc_base`.`cost` AS `base_price`, `doc_base`.`bulkcnt`, `doc_base`.`group`
		FROM `doc_base`
                LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_base`.`group`=`doc_group`.`id`
		WHERE `doc_base`.`group`='$group' AND `doc_base`.`hidden`='0' ORDER BY `doc_base`.`name`");
        $i = $cur_col = 0;
        $pc = \PriceCalc::getInstance();
        $pref = \pref::getInstance();
        $pc->setFirmId($pref->getSitePref('default_firm_id'));
        while ($nxt = $res->fetch_assoc()) {
            if($this->vendor_filter!='' && $nxt['proizv']!=$this->vendor_filter) {
                continue;
            }
            if($this->count_filter=='instock' && $nxt['cnt']<=0) {
                continue;
            }
            if($this->count_filter=='intransit' && $nxt['cnt']<=0 && $nxt['transit']<=0) {
                continue;
            }            
            if ($cur_col >= $this->column_count) {
                $cur_col = 0;
                $this->buffer.="\n";
            } else if ($cur_col != 0) {
                $this->buffer.= $this->divider . $this->shielder . $this->shielder . $this->divider;
            }

            $c = $pc->getPosSelectedPriceValue($nxt['id'], $this->cost_id, $nxt);
            if ($c == 0) {
                continue;
            }
            if (($this->view_proizv) && ($nxt['proizv'])) {
                $pr = " (" . $nxt['proizv'] . ")";
            } else {
                $pr = "";
            }
            if ($this->svc) {
                $this->buffer.= $this->shielder . $nxt['vc'] . $this->shielder . $this->divider;
            }
            $this->buffer.= $this->shielder . $nxt['name'] . $pr . $this->shielder . $this->divider . $this->shielder . $c . $this->shielder;

            $this->line++;
            $i = 1 - $i;
            $cur_col++;
        }
        $this->buffer.="\n\n";
    }
}
