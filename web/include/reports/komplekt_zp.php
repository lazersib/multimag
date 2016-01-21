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


class Report_Komplekt_Zp {

    function getName($short = 0) {
        if ($short) {
            return "По комплектующим с З/П";
        } else {
            return "Отчёт по комплектующим (с зарплатой)";
        }
    }

    function Form() {
        global $tmpl, $db;
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='komplekt_zp'>
            <input type='hidden' name='opt' value='make'>
            Группа товаров:<br>" .
            selectGroupPos('group', 0, 1, '', '')
            . "<button type='submit'>Создать отчет</button></form>");
    }

    function MakeHTML() {
        global $tmpl, $CONFIG, $db;
        $tmpl->loadTemplate('print');
        $group = rcvint('group');
        $date = date('Y-m-d');
        $sel = $group ? "AND `group`='$group'" : '';
        // Получение id свойства зарплаты
        $zres = $db->query("SELECT `id` FROM `doc_base_params` WHERE `codename`='ZP'");
        if ($zres->num_rows == 0) {
            throw new Exception("Данные о зарплате за сборку в базе не найдены. Необходим дополнительный параметр 'ZP'");
        }
        list($zp_id) = $zres->fetch_row();
        switch (@$CONFIG['doc']['sklad_default_order']) {
            case 'vc': $order = '`doc_base`.`vc`';
                break;
            case 'cost': $order = '`doc_base`.`cost`';
                break;
            default: $order = '`doc_base`.`name`';
        }
        $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`,
                    `doc_base_values`.`value` AS `zp`
            FROM `doc_base`
            LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
            LEFT JOIN `doc_base_values` ON `doc_base_values`.`id`=`doc_base`.`id` AND `doc_base_values`.`param_id`='$zp_id'
            WHERE 1 $sel
            ORDER BY $order");
        $tmpl->setContent("<h1>Отчёт по комплектующим с зарплатой для группы $group на $date</h1><table width='100%'>
            <tr><th rowspan='2'>ID<th rowspan='2'>Код<br>произв.<th rowspan='2'>Наименование<th rowspan='2'>Зар. плата<th colspan='5'>Комплектующие<th rowspan='2'>Стоимость сборки<th rowspan='2'>Стоимость с зарплатой
            <tr><th>Код<th>Наименование<th>Цена<th>Количество<th>Стоимость");
        $zp_sum = $kompl_sum = $all_sum = 0;
        while ($nxt = $res->fetch_assoc()) {
            settype($nxt['zp'], 'double');
            $cnt = $sum = 0;
            $kompl_data1 = $kompl_data = '';
            $rs = $db->query("SELECT `doc_base_kompl`.`kompl_id` AS `id`, `doc_base`.`name`, `doc_base`.`cost`, `doc_base_kompl`.`cnt`, `doc_base`.`vc`
                FROM `doc_base_kompl`
                LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_base_kompl`.`kompl_id`
                WHERE `doc_base_kompl`.`pos_id`='{$nxt['id']}'");
            while ($nx = $rs->fetch_row()) {
                $cnt++;
                $cost = sprintf("%0.2f", getInCost($nx[0], 0, 1));
                $cc = $cost * $nx[3];
                $sum+=$cc;
                if (!$kompl_data1) {
                    $kompl_data1 = "<td>$nx[4]<td>$nx[1]<td>$cost<td>$nx[3]<td>$cc";
                } else {
                    $kompl_data.="<tr><td>$nx[4]<td>$nx[1]<td>$cost<td>$nx[3]<td>$cc";
                }
            }
            $sum = round($sum, 2);

            $span = ($cnt > 1) ? "rowspan='$cnt'" : '';
            if (!$kompl_data1) {
                $kompl_data1 = "<td><td><td><td><td>";
            }
            $zsum = round($nxt['zp'] + $sum, 2);

            $tmpl->addContent("<tr style='border-top: 2px solid #000'><td $span>{$nxt['id']}<td $span>{$nxt['vc']}<td $span>{$nxt['printname']} {$nxt['name']} / {$nxt['proizv']}<td $span>{$nxt['zp']} $kompl_data1<td $span>$sum<td $span>$zsum
			$kompl_data");
            $zp_sum+=$nxt['zp'];
            $kompl_sum+=$sum;
            $all_sum+=$zsum;
        }
        $tmpl->addContent("<tr><td colspan='3'><b>Итого:</b><td>$zp_sum<td colspan='5'><td>$kompl_sum<td>$all_sum</table>");
    }

    function Run($opt) {
        if ($opt == '') {
            $this->Form();
        } else {
            $this->MakeHTML();
        }
    }

}
