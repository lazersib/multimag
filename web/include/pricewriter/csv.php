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

/// Класс формирует прайс-лист в формате CSV
class csv extends BasePriceWriter {

    var $divider;  //< Разделитель
    var $shielder;  //< Экранирование строк
    var $line;  //< Текущая строка

    /// Конструктор
    function __construct($db) {
        parent::__construct($db);
        $this->divider = ",";
        $this->shielder = '"';
        $this->line = 0;
    }

    /// Установить символ разделителя колонок
    /// @param $divider Символ разделителя колонок (,;:)
    function setDivider($divider = ",") {
        $this->divider = $divider;
        if ($this->divider != ";" && $this->divider != ":") {
            $this->divider = ",";
        }
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
        global $CONFIG;
        header("Content-Type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=price_list.csv;");
        for ($i = 0; $i < $this->column_count; $i++) {
            if (@$CONFIG['site']['price_show_vc']) {
                echo $this->shielder . "Код" . $this->shielder . $this->divider;
            }
            echo $this->shielder . "Название" . $this->shielder . $this->divider . $this->shielder . "Цена" . $this->shielder;
            if ($i < ($this->column_count - 1)) {
                echo $this->divider . $this->shielder . $this->shielder . $this->divider;
            }
        }
        echo "\n";
        $this->line++;
    }

    /// Сформирвать тело прайса
    /// param $group id номенклатурной группы
    function write($group = 0) {
        global $CONFIG;
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
            if (@$CONFIG['site']['price_show_vc']) {
                echo $this->divider;
            }
            echo $this->shielder . $nxt[1] . $this->shielder;
            echo"\n";
            $this->writepos($nxt[0], $nxt[2]);
            $this->write($nxt[0]); // рекурсия
        }
    }

    /// Сформировать завершающий блок прайса
    function close() {
        global $CONFIG;
        $pref = \pref::getInstance();
        echo"\n\n";
        $this->line+=5;
        if (@$CONFIG['site']['price_show_vc']) {
            echo $this->divider;
        }
        echo $this->shielder . "Generated from MultiMag (http://multimag.tndproject.org), for http://" . $pref->site_name . $this->shielder;
        $this->line++;
        echo"\n";
        if (@$CONFIG['site']['price_show_vc']) {
            echo $this->divider;
        }
        echo $this->shielder . "Прайс создан системой MultiMag (http://multimag.tndproject.org), специально для http://" . $pref->site_name . $this->shielder;
    }

    /// Сформировать строки прайса
    /// param $group id номенклатурной группы
    function writepos($group = 0) {
        global $CONFIG;
        $res = $this->db->query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost_date` , `doc_base`.`proizv`, `doc_base`.`vc`,
			`doc_base`.`cost` AS `base_price`, `doc_base`.`bulkcnt`, `doc_base`.`group`
		FROM `doc_base`
		LEFT JOIN `doc_group` ON `doc_base`.`group`=`doc_group`.`id`
		WHERE `doc_base`.`group`='$group' AND `doc_base`.`hidden`='0' ORDER BY `doc_base`.`name`");
        $i = $cur_col = 0;
        $pc = \PriceCalc::getInstance();
        $pref = \pref::getInstance();
        $pc->setFirmId($pref->getSitePref('default_firm_id'));
        while ($nxt = $res->fetch_assoc()) {
            if ($cur_col >= $this->column_count) {
                $cur_col = 0;
                echo"\n";
            } else if ($cur_col != 0) {
                echo $this->divider . $this->shielder . $this->shielder . $this->divider;
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
            if (@$CONFIG['site']['price_show_vc']) {
                echo $this->shielder . $nxt['vc'] . $this->shielder . $this->divider;
            }
            echo $this->shielder . $nxt['name'] . $pr . $this->shielder . $this->divider . $this->shielder . $c . $this->shielder;

            $this->line++;
            $i = 1 - $i;
            $cur_col++;
        }
        echo"\n\n";
    }

}
