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


class Report_KassDay
{

	function getName($short=0)
	{
		if($short)	return "Кассовый за день";
		else		return "Кассовый отчёт за текущий день";
	}

	function Form()
	{
		global $tmpl;
		$curdate=date("Y-m-d");
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<link rel='stylesheet' href='/css/jquery/ui/themes/base/jquery.ui.all.css'>
		<script src='/css/jquery/ui/jquery.ui.core.js'></script>
		<script src='/css/jquery/ui/jquery.ui.widget.js'></script>
		<script src='/css/jquery/ui/jquery.ui.datepicker.js'></script>
		<script src='/css/jquery/ui/i18n/jquery.ui.datepicker-ru.js'></script>
		<form action=''>
		<input type='hidden' name='mode' value='kassday'>
		<input type='hidden' name='opt' value='ok'>
		Выберите кассу:<br>
		<select name='kass'>");
		$res=mysql_query("SELECT `num`, `name` FROM `doc_kassa` WHERE `ids`='kassa'  ORDER BY `num`");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");
		}
		$tmpl->AddText("</select><br>
		Выберите дату:<br>
		<input type='text' name='date' id='datepicker_f' value='$curdate'><br>
		<button type='submit'>Сформировать</button></form>");
	}
	
	function MakeHTML()
	{
		global $tmpl;
		$tmpl->LoadTemplate('print');
		$dt=rcv('date');
		$kass=rcv('kass');
		$res=mysql_query("SELECT `num`, `name` FROM `doc_kassa` WHERE `ids`='kassa'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список касс");
		$kass_list=array();
		while($nxt=mysql_fetch_row($res))	$kass_list[$nxt[0]]=$nxt[1];
		$tmpl->SetText("<h1>Отчёт по кассе {$kass_list[$kass]} за $dt</h1>");		
		$daystart=strtotime("$dt 00:00:00");
		$dayend=strtotime("$dt 23:59:59");
		$tmpl->AddText("<table width='100%'><tr><th>ID<th>Время<th>Документ<th>Приход<th>Расход<th>В кассе");			
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`sum`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_types`.`name`, `doc_agent`.`name`, `doc_list`.`p_doc`, `t`.`name`, `p`.`altnum`, `p`.`subtype`, `p`.`date`, `p`.`sum`, `doc_list`.`kassa`, `doc_dopdata`.`value` AS `vk_value`
		FROM `doc_list`
		LEFT JOIN `doc_agent`		ON `doc_agent`.`id` = `doc_list`.`agent`
		INNER JOIN `doc_types`		ON `doc_types`.`id` = `doc_list`.`type`
		LEFT JOIN `doc_list` AS `p`	ON `p`.`id`=`doc_list`.`p_doc`
		LEFT JOIN `doc_types` AS `t`	ON `t`.`id` = `p`.`type`
		LEFT JOIN `doc_dopdata`		ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='v_kassu'
		WHERE `doc_list`.`ok`>'0' AND ( `doc_list`.`type`='6' OR `doc_list`.`type`='7' OR `doc_list`.`type`='9')
		AND (`doc_list`.`kassa`='$kass' OR `doc_dopdata`.`value`='$kass')
		ORDER BY `doc_list`.`date`");
		$sum=$daysum=$prix=$rasx=0;
		$flag=0;
		while($nxt=mysql_fetch_array($res))
		{
			$csum_p=$csum_r='';
			if( !$flag && $nxt[3]>=$daystart && $nxt[3]<=$dayend)
			{
				$flag=1;
				$sum_p=sprintf("%0.2f руб.",$sum);
				$tmpl->AddText("<tr><td colspan=5><b>На начало дня</b><td align='right'><b>$sum_p</b>");
			}
			if($nxt[1]==6)		$sum+=$nxt[2];
			else if($nxt[1]==7)	$sum-=$nxt[2];
			else if($nxt[1]==9)
			{
				if($nxt['kassa']==$kass)
					$sum-=$nxt[2];
				else	$sum+=$nxt[2];
			}
			if($nxt[3]>=$daystart && $nxt[3]<=$dayend)
			{
				if($nxt[1]==6)
				{
					$daysum+=$nxt[2];
					$prix+=$nxt[2];
					$csum_p=sprintf("%0.2f руб.",$nxt[2]);
				}
				else if($nxt[1]==7)
				{
					$daysum-=$nxt[2];
					$rasx+=$nxt[2];
					$csum_r=sprintf("%0.2f руб.",$nxt[2]);
				}
				else
				{
					if($nxt['kassa']==$kass)
					{
						$daysum-=$nxt[2];
						$rasx+=$nxt[2];
						$csum_r=sprintf("%0.2f руб.",$nxt[2]);
					}
					else
					{
						$daysum+=$nxt[2];
						$prix+=$nxt[2];
						$csum_p=sprintf("%0.2f руб.",$nxt[2]);
					}
				}
				if($nxt[8])	$sadd="<br><i>к $nxt[9] N$nxt[10]$nxt[11] от ".date("d-m-Y H:i:s",$nxt[12])." на сумму ".sprintf("%0.2f руб",$nxt[13])."</i>";
				else		$sadd='';
				if($nxt[1]==6)		$sadd.="<br>от $nxt[7]";
				else if($nxt[1]==7)	$sadd.="<br>для $nxt[7]";
				else if($nxt[1]==9)
				{
					if($nxt['kassa']==$kass)	$sadd.="<br>в кассу {$kass_list[$nxt['vk_value']]}";
					else				$sadd.="<br>из кассы {$kass_list[$nxt['kassa']]}";
				}
				$dt=date("H:i:s",$nxt[3]);
				$sum_p=sprintf("%0.2f руб.",$sum);
				
				$tmpl->AddText("<tr><td>$nxt[0]<td>$dt<td>$nxt[6] N$nxt[4]$nxt[5]   $sadd<td align='right'>$csum_p<td align='right'>$csum_r<td align='right'>$sum_p</tr>");	
			}
		}
		if( !$flag)
		{
				$sum_p=sprintf("%0.2f руб.",$sum);
				$tmpl->AddText("<tr><td colspan=5><b>На начало дня</b><td align='right'><b>$sum_p</b>");
		}
		if($flag)
		{
			$dsum_p=sprintf("%0.2f руб.",$daysum);
			$psum_p=sprintf("%0.2f руб.",$prix);
			$rsum_p=sprintf("%0.2f руб.",$rasx);
			$tmpl->AddText("<tr><td>-<td>-<td><b>На конец дня</b><td align='right'><b>$psum_p</b><td align='right'><b>$rsum_p</b><td align='right'><b>$sum_p</b>");
			$tmpl->AddText("<tr><td>-<td>-<td><b>Разница за смену</b><td align='right' colspan=3><b>$dsum_p</b>");
 		}
 		else	$tmpl->AddText("<tr><td>-<td>-<td><b>Нет данных по балансу на выбранную дату</b><td align='right'><b>нет данных</b><td align='right'><b>нет данных</b><td align='right'><b>нет данных</b>");
 		
 		$res=mysql_query("SELECT `name` FROM `users` WHERE `id`='{$_SESSION['uid']}'");
 		$nm=mysql_result($res,0,0);
 		
 		$tmpl->AddText("</table><br><br>
 		Cоответствие сумм подтверждаю ___________________ ($nm)");
	}
	
	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();	
	}
};

?>

