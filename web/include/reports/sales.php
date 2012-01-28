<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2010, BlackLight, TND Team, http://tndproject.org
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


class Report_Sales
{
	function getName($short=0)
	{
		if($short)	return "По продажам";
		else		return "Отчёт по продажам";
	}
	

	function Form()
	{
		global $tmpl;
		$d_t=date("Y-m-d");
		$d_f=date("Y-m-d",time()-60*60*24*31);
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='sales'>
		<input type='hidden' name='opt' value='make'>
		<p class='datetime'>
		<fieldset><legend>Дата</legend>
		С:<input type=text id='id_pub_date_date' class='vDateField required' name='dt_f' value='$d_f'><br>
		По:<input type=text id='id_pub_date_date' class='vDateField required' name='dt_t' value='$d_t'>
		</fieldset>
		</p>
		<button type='submit'>Сформировать отчёт</button>
		</form>");
	}
	
	function MakeHTML()
	{
		global $tmpl;
		$tmpl->LoadTemplate('print');
		$dt_f=strtotime(rcv('dt_f'));
		$dt_t=strtotime(rcv('dt_t'));
		$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`likvid`, SUM(`doc_list_pos`.`cnt`), SUM(`doc_list_pos`.`cnt`*`doc_list_pos`.`cost`)
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<='$dt_t' AND `doc_list`.`type`='2' AND `doc_list`.`ok`>'0'
		GROUP BY `doc_list_pos`.`tovar`
		ORDER BY `doc_base`.`name`");
		
		$print_df=date('Y-m-d', $dt_f);
		$print_dt=date('Y-m-d', $dt_t);
		$tmpl->SetText("
		<h1>Отчёт по продажам с $print_df по $print_dt</h1>
		<table width='100%'>
		<tr><th>ID<th>Наименование<th>Ликвидность<th>Кол-во проданного<th>Сумма по поступлениям<th>Сумма продаж<th>Прибыль");
		$cntsum=$postsum=$prodsum=$pribsum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$insum=sprintf('%0.2f.', GetInCost($nxt[0])*$nxt[3]);
			$prib=sprintf('%0.2f', $nxt[4]-$insum);
			$cntsum+=$nxt[3];
			$postsum+=$insum;
			$prodsum+=$nxt[4];
			$pribsum+=$prib;
			$prib_style=$prib<0?"style='color: #f00'":'';
			$tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]<td>$nxt[2] %<td>$nxt[3]<td>$insum руб.<td>$nxt[4] руб.<td $prib_style>$prib руб.");
		}
		$prib_style=$pribsum<0?"style='color: #f00'":'';
		$tmpl->AddTExt("
		<tr><td colspan='3'>Итого:<td>$cntsum<td>$postsum руб.<td>$prodsum руб.<td $prib_style>$pribsum руб.
		</table>");
	}
	
	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();	
	}
};

?>

