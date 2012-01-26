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


class Report_Cons_Finance
{
	function getName($short=0)
	{
		if($short)	return "Сводный финансовый";
		else		return "Сводный финансовый отчет";
	}
	

	function Form()
	{
		global $tmpl;
		$date_st=date("Y-m-01");
		$date_end=date("Y-m-d");
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='cons_finance'>
		<input type='hidden' name='opt' value='make'>
		<p class='datetime'>
		Дата от:<input type='text' id='id_pub_date_date' class='vDateField required' name='date_st' size='10' value='$date_st' maxlength='10' /><br>
		до:<input type='text' id='id_pub_date_date' class='vDateField required' name='date_end' size='10' value='$date_end' maxlength='10' />
		</p><button type='submit'>Создать отчет</button></form>");	
	}
	
	function MakeHTML()
	{
		global $tmpl;
		$date_st=strtotime(rcv('date_st'));
		$date_end=strtotime(rcv('date_end'))+60*60*24-1;
		if(!$date_end) $date_end=time();
		$agent=rcv('agent');

		$date_st_print=date("d.m.Y H:i:s",$date_st);
		$date_end_print=date("d.m.Y H:i:s",$date_end);

		$tmpl->LoadTemplate('print');


		$tmpl->SetText("<h1>".$this->getName()."</h1>
		<h4>С $date_st_print по $date_end_print</h4>");

		// Счётчики для обработки
		$rasxody_nal="";
		$rasxody_bn="";
		$podotchet=0;
		$prixody_nal=0;
		$prixody_bn=0;

		// Обработка ПРОВЕДЁННЫХ документов за указанный период

		$res=mysql_query("SELECT `doc_list`.`id`,`doc_list`.`type`,`doc_list`.`date`,`doc_list`.`sum`,
		`doc_list`.`altnum`
		FROM `doc_list`
		WHERE `doc_list`.`ok`!='0' AND `doc_list`.`date`>='$date_st' AND `doc_list`.`date`<='$date_end'");
		
		while($nxt=mysql_fetch_row($res))
		{
			$dopdata="";
			$rr=mysql_query("SELECT `param`,`value` FROM `doc_dopdata` WHERE `doc`='$nxt[0]'");
			while($nx=mysql_fetch_row($rr))
			{
				$dopdata["$nx[0]"]=$nx[1];
			}

			if($nxt[1]==4) // Банковский приход
			{
				$prixody_bn+=$nxt[3];
			}
			if($nxt[1]==5)	// Банковский расход
			{
				$vid=$dopdata['rasxodi'];
				if(!$vid) $vid=0;
				$rasxody_bn[$vid]+=$nxt[3];
				if($vid==12) $podotchet+=$nxt[3];
			}
			else if($nxt[1]==6) // Кассовый приход
			{
				$prixody_nal+=$nxt[3];
			}
			else if($nxt[1]==7)	// Кассовый расход
			{
				$vid=$dopdata['rasxodi']; 
				if(!$vid) $vid=0; 
				$rasxody_nal[$vid]+=$nxt[3]; 
				if($vid==12) $podotchet+=$nxt[3]; 
			}
		}

		$adm=0;
		$tovar=0;
		$sum_nal=0;
		$sum_bn=0;
		$sum_prixod=$prixody_bn+$prixody_nal-$podotchet;

		$nal_prn=sprintf("%01.2f", $prixody_nal);
		$bn_prn=sprintf("%01.2f", $prixody_bn);
		$sum_prn=sprintf("%01.2f", $sum_prixod);
		
		$pod_prn=sprintf("%01.2f",(-1)*$podotchet);
		$nalsum_prn=sprintf("%01.2f", $prixody_nal-$podotchet);
		
		
		$tmpl->AddText("
		<h4>Движения денежных средств</h4>
		<table class=right_align>
		<tr><th>N<th>Вид<th>Наличные средства<th>Безналичные средства<th>Сумма
		<tr><th colspan=5>Поступления
		<tr><td>1<td id=lf>Поступления на р/счёт<td>0.00<td>$bn_prn<td>$bn_prn
		<tr><td>2<td id=lf>Поступления в кассу<td>$nal_prn<td>$pod_prn<td>$nalsum_prn
		<tr><th colspan=2>Получено:<th>$nal_prn<th>$bn_prn<th>$sum_prn
		<tr><th colspan=5>Затраты");
		$res=mysql_query("SELECT * FROM `doc_rasxodi`");
		while($nxt=mysql_fetch_row($res))
		{
			$nal=$rasxody_nal[$nxt[0]];
			$bn=$rasxody_bn[$nxt[0]];
			$cur_sum=$nal+$bn;

			$nal_prn=sprintf("%01.2f", $nal);
			$bn_prn=sprintf("%01.2f", $bn);
			$cur_sum_prn=sprintf("%01.2f", $cur_sum);
			$tmpl->AddText("<tr><td>$nxt[0]<td id=lf>$nxt[1]<td>$nal_prn<td>$bn_prn<td>$cur_sum_prn");
			// Суммирование
			$sum_nal+=$nal;
			$sum_bn+=$bn;
			if($nxt[2]) $adm+=$cur_sum;
			else $tovar+=$cur_sum;
		}

		$nal_prn=sprintf("%01.2f", $sum_nal);
		$bn_prn=sprintf("%01.2f", $sum_bn);
		$sum_prn=sprintf("%01.2f", $sum_nal+$sum_bn-$rasxody_bn[12]);
		$adm_prn=sprintf("%01.2f", $adm);
		$tovar_prn=sprintf("%01.2f", $sum_nal+$sum_bn-$adm-$rasxody_bn[12]);

		if($sum_prixod==0) $adm_proc_prn="бесконечность";
		else $adm_proc_prn=sprintf("%01.4f", ($adm/$sum_prixod*100));


		$tmpl->AddText("
		<tr><th colspan=2>Итого:<th>$nal_prn<th>$bn_prn<th>$sum_prn
		<tr><th colspan=4>Административные затраты:<th>$adm_prn
		<tr><th colspan=4>В процентах от прихода:<th>$adm_proc_prn
		<tr><th colspan=4>За товар:<th>$tovar_prn
		<tr><th colspan=4>Итого:<th>$sum_prn
		</table>");
	}
	
	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();	
	}
};

?>

