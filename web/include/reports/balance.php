<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2012, BlackLight, TND Team, http://tndproject.org
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


class Report_Balance
{

	function getName($short=0)
	{
		if($short)	return "Баланс";
		else		return "Состояние счетов и касс";
	}

	function Form()
	{
		global $tmpl;
		$curdate=date("Y-m-d");
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<div id='page-info'>Отображается текущее количество средств во всех кассах и банках</div>
		<table width=50% cellspacing=0 cellpadding=0 border=0>
		<tr><th>Тип<th>Название<th>Балланс");
		$i=0;
		$res=mysql_query("SELECT `ids`,`name`,`ballance` FROM `doc_kassa`");
		while($nxt=mysql_fetch_row($res))
		{
			$i=1-$i;
			$pr=sprintf("%0.2f руб.",$nxt[2]);
			$tmpl->AddText("<tr class=lin$i><td>$nxt[0]<td>$nxt[1]<td align=right>$pr");
		}
		$dt=date("Y-m-d");
		$tmpl->AddText("</table>
		<form action=''>
		<input type='hidden' name='mode' value='balance'>
		<input type='hidden' name='opt' value='ok'>
		Вычислить балланс на дату:
		<input type=text id='id_pub_date_date' class='vDateField required' name='dt' value='$dt'><br>
		<label><input type=checkbox name=v value=1>Считать на вечер</label><br>
		<button type='submit'>Вычислить</button></form>");		
	}
	
	function MakeHTML()
	{
		global $tmpl;
		$dt=rcv('dt');
		$v=rcv('v');
		$tmpl->AddText("<h1>".$this->getName()." на $dt</h1>");
		$tm=strtotime($dt);
		if($v) $tm+=60*60*24-1;
		$res=mysql_query("SELECT SUM(`sum`), `bank` FROM `doc_list` WHERE `type`='4' AND `ok`>'0' AND `date`<'$tm' GROUP BY `bank`");
		while($nxt=mysql_fetch_row($res))
		$bank_p[$nxt[1]]=$nxt[0];
		$res=mysql_query("SELECT SUM(`sum`), `bank` FROM `doc_list` WHERE `type`='5' AND `ok`>'0' AND `date`<'$tm' GROUP BY `bank`");
		while($nxt=mysql_fetch_row($res))
		$bank_r[$nxt[1]]=$nxt[0];
		$res=mysql_query("SELECT SUM(`sum`), `kassa` FROM `doc_list` WHERE `type`='6' AND `ok`>'0' AND `date`<'$tm' GROUP BY `kassa`");
		while($nxt=mysql_fetch_row($res))
		$kassa_p[$nxt[1]]=$nxt[0];
		$res=mysql_query("SELECT SUM(`sum`), `kassa` FROM `doc_list` WHERE `type`='7' AND `ok`>'0' AND `date`<'$tm' GROUP BY `kassa`");
		while($nxt=mysql_fetch_row($res))
		$kassa_r[$nxt[1]]=$nxt[0];

		$tmpl->AddText("<table width=50% cellspacing=0 cellpadding=0 border=0>
		<tr><th>N<th>Приход<th>Расход<th>Балланс
		<tr><th colspan=4>Банки (все)");
		foreach($bank_p as $id => $v)
		{
			$sum=$v-$bank_r[$id];
			$tmpl->AddText("<tr><td>$id<td>$v<td>$bank_r[$id]<td>$sum");
		}
		$tmpl->AddText("
		<tr><th colspan=4>Кассы (все)");
		foreach($kassa_p as $id => $v)
		{
			$sum=$v-$kassa_r[$id];
			$tmpl->AddText("<tr><td>$id<td>$v<td>$kassa_r[$id]<td>$sum");
		}
		$tmpl->AddText("</table>");
	}
	
	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();	
	}
};

?>

