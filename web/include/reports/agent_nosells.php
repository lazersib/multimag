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


class Report_Agent
{
	function getName($short=0)
	{
		if($short)	return "По агентам без продаж";
		else		return "Отчёт по агентам без продаж";
	}
	

	function Form()
	{
		global $tmpl;
		$date_st=date("Y-01-01");
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<h2>Отчёт не доделан!</h2>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='agent'>
		<input type='hidden' name='opt' value='make'>
		<p class='datetime'>
		<fieldset><legend>Дата отсчета</legend>
		<input type=text id='id_pub_date_date' class='vDateField required' name='dt_f' value='$date_st'>
		</fieldset>
		<label><input type='checkbox' name='fix' value='1'>Только с назначенным ответственным лицом</label><br>
		<button type='submit'>Создать отчет</button></form>");	
	}
	
	function MakeHTML()
	{
		global $tmpl;
		$dt_f=rcv('dt_f');
		$sql_add= (rcv('fix')==1) ? " AND `doc_agent`.`responsible`>'0' " : '';
		$tmpl->SetText("<h1>Агенты без продаж с $dt_f по текущий момент</h1><ul>");
		
		$res=mysql_query("SELECT `doc_agent`.`id`, `doc_agent`.`name`, `doc_agent`.`responsible`, `users`.`name` FROM `doc_agent`
		LEFT JOIN `users` ON `users`.`id`=`doc_agent`.`responsible`
		WHERE `doc_agent`.`id` NOT IN (SELECT `agent` FROM `doc_list` WHERE `date`>='$dt_sql' ) $sql_add");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<li>id:$nxt[0] - $nxt[1] ($nxt[3], id:$nxt[2])</li>");
		}
		$tmpl->AddText("</ul>");
	}
	
	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();	
	}
};

?>

