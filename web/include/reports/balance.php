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
/// Отчёт о балансе
class Report_Balance {

	function getName($short = 0) {
		if ($short)	return "Баланс";
		else		return "Состояние счетов и касс";
	}

	function Form() {
		global $tmpl, $db;
		$tmpl->addContent("<h1>" . $this->getName() . "</h1>
		<div id='page-info'>Отображается текущее количество средств во всех кассах и банках</div>
		<table width='50%' cellspacing='0' cellpadding='0' border='0' class='list'>
		<tr><th>Тип</th><th>Название</th><th>Балланс</th></tr>");
		$i = 0;
		$res = $db->query("SELECT `ids`,`name`,`ballance` FROM `doc_kassa`");
		while ($nxt = $res->fetch_row()) {
			$i = 1 - $i;
			$pr = sprintf("%0.2f руб.", $nxt[2]);
			$tmpl->addContent("<tr><td>$nxt[0]</td><td>".html_out($nxt[1])."</td><td align='right'>$pr</td></tr>");
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
		$v = request('v');
		$tmpl->addContent("<h1>" . $this->getName() . " на $dt</h1>");
		$tm = strtotime($dt);
		$b = $k = array();
		$r = $db->query("SELECT `ids`, `num`, `name` FROM `doc_kassa`");
		while($n = $r->fetch_assoc()) {
			if($n['ids']=='bank')	$b[$n['num']] = $n['name'];
			else			$k[$n['num']] = $n['name'];
		}
		if ($v)	$tm+=60 * 60 * 24 - 1;
		$res = $db->query("SELECT SUM(`sum`), `bank` FROM `doc_list` WHERE `type`='4' AND `ok`>'0' AND `date`<'$tm' GROUP BY `bank`");
		while ($nxt = $res->fetch_row())
			$bank_p[$nxt[1]] = $nxt[0];
		$res = $db->query("SELECT SUM(`sum`), `bank` FROM `doc_list` WHERE `type`='5' AND `ok`>'0' AND `date`<'$tm' GROUP BY `bank`");
		while ($nxt = $res->fetch_row())
			$bank_r[$nxt[1]] = $nxt[0];
		$res = $db->query("SELECT SUM(`sum`), `kassa` FROM `doc_list` WHERE `type`='6' AND `ok`>'0' AND `date`<'$tm' GROUP BY `kassa`");
		while ($nxt = $res->fetch_row())
			$kassa_p[$nxt[1]] = $nxt[0];
		$res = $db->query("SELECT SUM(`sum`), `kassa` FROM `doc_list` WHERE `type`='7' AND `ok`>'0' AND `date`<'$tm' GROUP BY `kassa`");
		while ($nxt = $res->fetch_row())
			$kassa_r[$nxt[1]] = $nxt[0];

		$tmpl->addContent("<table width='50%' cellspacing='0' cellpadding='0' border='0'>
		<tr><th>N</th><th>Имя</th><th>Приход</th><th>Расход</th><th>Балланс</th></tr>
		<tr><th colspan='5'>Банки (все)");
		foreach ($bank_p as $id => $v) {
			$sum = $v - $bank_r[$id];
			$tmpl->addContent("<tr><td>$id</td><td>{$b[$id]}</td><td>$v</td><td>$bank_r[$id]</td><td>$sum</td></tr>");
		}
		$tmpl->addContent("
		<tr><th colspan='5'>Кассы (все)");
		foreach ($kassa_p as $id => $v) {
			$sum = $v - $kassa_r[$id];
			$tmpl->addContent("<tr><td>$id</td><td>{$k[$id]}</td><td>$v</td><td>$kassa_r[$id]</td><td>$sum</td></tr>");
		}
		$tmpl->addContent("</table>");
	}

	function Run($opt) {
		if ($opt == '')
			$this->Form();
		else
			$this->MakeHTML();
	}

}

