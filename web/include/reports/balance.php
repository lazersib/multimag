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
/// Отчёт о балансе
class Report_Balance {

    function getName($short = 0) {
        if ($short) {
            return "Баланс";
        } else {
            return "Состояние счетов и касс";
        }
    }

    function Form() {
        global $tmpl, $db;
        $tmpl->addBreadcrumb($this->getName(), '');
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <div id='page-info'>Отображается текущее количество средств во всех кассах и банках</div>
            <table width='50%' class='list'>
            <tr><th>Тип</th><th>Название</th><th>Балланс</th></tr>");
        $i = 0;
        $res = $db->query("SELECT `ids`,`name`,`ballance` FROM `doc_kassa`");
        while ($nxt = $res->fetch_row()) {
            $i = 1 - $i;
            $val_p = number_format($nxt[2], 2, '.', ' ');
            $style = $nxt[2]<0?" style='color:#f00;font-weight:bold;'":'';
            $tmpl->addContent("<tr><td>$nxt[0]</td><td>" . html_out($nxt[1]) . "</td><td align='right'{$style}>$val_p</td></tr>");
        }
        $dt = date("Y-m-d");
        $tmpl->addContent("</table>
            <form action=''>
            <input type='hidden' name='mode' value='balance'>
            <input type='hidden' name='opt' value='ok'>
            Вычислить баланс на дату:
            <input type=text id='id_pub_date_date' class='vDateField required' name='dt' value='$dt'><br>
            <label><input type=checkbox name=v value=1>Считать на вечер</label><br>
            <button type='submit'>Вычислить</button></form>");
    }

    function MakeHTML() {
        global $tmpl, $db;
        $dt = rcvdate('dt');
        $name = request('v');
        $tmpl->addBreadcrumb($this->getName() . " на $dt", '');
        $tmpl->addContent("<h1>" . $this->getName() . " на $dt</h1>");
        $tm = strtotime($dt);
        $bank_names = $cash_names = array();
        $bank_sums = $cash_sums = array();
        $r = $db->query("SELECT `ids`, `num`, `name` FROM `doc_kassa`");
        while ($n = $r->fetch_assoc()) {
            if ($n['ids'] == 'bank') {
                $bank_names[$n['num']] = $n['name'];
                $bank_sums[$n['num']] = 0;
            } else {
                $cash_names[$n['num']] = $n['name'];
                $cash_sums[$n['num']] = 0;
            }
        }
        if ($name) {
            $tm+=60 * 60 * 24 - 1;
        }
        $res = $db->query("SELECT `type`, `sum`, `bank`"
            . " FROM `doc_list`"
            . " WHERE (`type`=4 OR `type`=5)"
                . " AND `ok`>'0' AND `date`<'$tm'");
        while($line = $res->fetch_assoc()) {
            if($line['type']==4) {
                $bank_sums[$line['bank']] += $line['sum'];
            } else {
                $bank_sums[$line['bank']] -= $line['sum'];
            }
        }
        $res = $db->query("SELECT `type`, `sum`, `kassa`, `doc_dopdata`.`value` AS `v_kassu`"
            . " FROM `doc_list`"
            . " LEFT JOIN `doc_dopdata`	ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='v_kassu'"
            . " WHERE (`type`=6 OR `type`=7 OR `type`=9)"
                . " AND `ok`>'0' AND `date`<'$tm'");
        while ($line = $res->fetch_assoc()) {
            if ($line['type'] == 6) {
                $cash_sums[$line['kassa']] += $line['sum'];
            } elseif ($line['type'] == 7) {
                $cash_sums[$line['kassa']] -= $line['sum'];
            } elseif ($line['type'] == 9) {
                $cash_sums[$line['kassa']] -= $line['sum'];
                $cash_sums[$line['v_kassu']] += $line['sum'];
            }
        }

        $tmpl->addContent("<table width='50%' class='list'>
		<tr><th>N</th><th>Наименование</th><th>Балланс</th></tr>
		<tr><th colspan='5'>Банки");
        foreach ($bank_names as $id => $name) {
            $val_p = number_format($bank_sums[$id], 2, '.', ' ');
            $style = $bank_sums[$id]<0?" style='color:#f00;font-weight:bold;'":'';
            $tmpl->addContent("<tr><td>$id</td><td>".html_out($name)."</td><td align='right'{$style}>$val_p</td></tr>");
        }
        $tmpl->addContent("
		<tr><th colspan='5'>Кассы (все)");
        foreach ($cash_names as $id => $name) {
            $val_p = number_format($cash_sums[$id], 2, '.', ' ');
            $style = $cash_sums[$id]<0?" style='color:#f00;font-weight:bold;'":'';
            $tmpl->addContent("<tr><td>$id</td><td>".html_out($name)."</td><td align='right'{$style}>$val_p</td></tr>");
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
