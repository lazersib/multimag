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
namespace Widgets;

class PriceInfo extends \IWidget {

    protected $mode;      //< Режим выдачи

    public function getName() {
        return 'Виджет с информацией о ценах';
    }

    public function getDescription() {
        return 'Формирует таблицу с информацией о скидках. Если параметр содержит b - выводися информация о разовых скидках. '
            . 'Если параметр содержит a - о накопительных. Если v - выводися информация о размерах скидок. По умолчанию - bav.';
    }

    public function setParams($param_str) {
        $this->mode = $param_str;
        return true;
    }

    public function getHTML() {
        global $CONFIG, $db;
        if ($this->mode) {
            $o_b = stripos($this->mode, 'b')!==false ? true : false;
            $o_a = stripos($this->mode, 'a')!==false ? true : false;
            $o_v = stripos($this->mode, 'v')!==false ? true : false;
        } else {
            $o_b = true;
            $o_a = true;
            $o_v = true;
        }

        $res = $db->query("SELECT `id`, `name`, `type`, `value`, `context`, `priority`, `bulk_threshold`, `acc_threshold` "
            . "FROM `doc_cost` ORDER BY `priority` DESC");

        $ret = "<table class='list'><tr><th>Наименование</th>";
        if ($o_v) {
            $ret .="<th>Средняя скидка</th>";
        }
        if ($o_b) {
            $ret.="<th>Разовый порог</th>";
        }
        if ($o_a) {
            $ret .= "<th>Накопительный порог</th>";
        }
        $ret .= "</tr>";

        while ($line = $res->fetch_assoc()) {
            if (strpos($line['context'], 'b') === false) {
                continue;
            }
            $ret .= "<tr><td>" . html_out($line['name']) . "</td>";
            if ($o_v) {
                $unit = '%';
                if ($line['type'] == 'abs') {
                    $unit = 'р.';
                }
                $ret .= "<td>{$line['value']} $unit</td>";
            }
            if ($o_b) {
                $bt = number_format($line['bulk_threshold'], 0, ".", "&nbsp;");
                $ret .= "<td>$bt р.</td>";
            }
            if ($o_a) {
                $at = number_format($line['acc_threshold'], 0, ".", "&nbsp;");
                $ret .= "<td>$at р.</td>";
            }
            $ret .= "</tr>";
        }
        $ret .= '</table>';
        return $ret;
    }

}
