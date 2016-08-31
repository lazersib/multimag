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
/// Отчёт по ценам
class Report_Costs extends BaseGSReport {

    function getName($short = 0) {
        if ($short) {
            return "По ценам";
        } else {
            return "Отчёт по ценам";
        }
    }

    function Form() {
        global $tmpl, $db;
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='costs'>
            <input type='hidden' name='opt' value='make'>
            Организация:<br>
            <select name='firm_id'><option value='0'>--не задано--</option>");
        $res = $db->query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `id`");
        while ($nxt = $res->fetch_row()) {
            $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select><br>
            Отображать следующие расчётные цены:<br>");
        $res = $db->query("SELECT `id`, `name` FROM `doc_cost` ORDER BY `id");
        while ($nxt = $res->fetch_row()) {
            $tmpl->addContent("<label><input type='checkbox' name='cost$nxt[0]' value='1' checked>" . html_out($nxt[1]) . "</label><br>");
        }
        $tmpl->addContent("Группа товаров:<br>");
        $this->GroupSelBlock();
        $tmpl->addContent("<button type='submit'>Сформировать отчёт</button></form>");
    }

    function MakeHTML() {
        global $tmpl, $CONFIG, $db;
        $g = request('g', array());
        $gs = rcvint('gs');
        $firm_id = rcvint('firm_id');
        $tmpl->loadTemplate('print');
        $tmpl->setContent("<h1>" . $this->getName() . "</h1>");
        $costs = array();
        $res = $db->query("SELECT `id`, `name` FROM `doc_cost` ORDER BY `id");
        $cost_cnt = 0;
        while ($nxt = $res->fetch_row()) {
            if (!request('cost' . $nxt[0])) {
                continue;
            }
            $costs[$nxt[0]] = $nxt[1];
            $cost_cnt++;
        }

        switch (@$CONFIG['doc']['sklad_default_order']) {
            case 'vc': $order = '`doc_base`.`vc`';
                break;
            case 'cost': $order = '`doc_base`.`cost`';
                break;
            default: $order = '`doc_base`.`name`';
        }

        $tmpl->addContent("<table width='100%'>
		<tr><th rowspan='2'>N</th><th rowspan='2'>Код</th><th rowspan='2'>Наименование</th><th rowspan='2'>Базовая цена</th><th rowspan='2'>АЦП</th>
		<th colspan='$cost_cnt'>Расчётные цены</th></tr>
		<tr>");
        $col_count = 6;
        foreach ($costs as $cost_name) {
            $tmpl->addContent('<th>' . html_out($cost_name));
            $col_count++;
        }

        $pc = PriceCalc::getInstance();
        $pc->SetFirmId($firm_id);
        $res_group = $db->query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
        while ($group_line = $res_group->fetch_assoc()) {
            if ($gs && !in_array($group_line['id'], $g)) {
                continue;
            }
            $tmpl->addContent("<tr><td colspan='$col_count' class='m1'>{$group_line['id']}. " . html_out($group_line['name']) . "</td></tr>");

            $res = $db->query("SELECT `id`, `vc`, `name`, `proizv`, `cost` AS `base_price`, `group`, `bulkcnt` FROM `doc_base`
			WHERE `doc_base`.`group`='{$group_line['id']}'
			ORDER BY $order");
            while ($nxt = $res->fetch_assoc()) {
                $act_cost = sprintf('%0.2f', getInCost($nxt['id']));
                $tmpl->addContent("<tr><td>{$nxt['id']}</td><td>{$nxt['vc']}</td><td>{$nxt['name']} / {$nxt['proizv']}</td>
					<td align='right'>{$nxt['cost']}</td><td align='right'>$act_cost</td>");
                foreach ($costs as $cost_id => $cost_name) {
                    $cost = $pc->getPosSelectedPriceValue($nxt['id'], $cost_id);
                    $tmpl->addContent("<td align='right'>$cost</td>");
                }
                $tmpl->addContent('</tr>');
            }
        }
        $tmpl->addContent("</table>");
    }

    function Run($opt) {
        if ($opt == '') {
            $this->Form();
        } else {
            $this->MakeHTML();
        }
    }

}
