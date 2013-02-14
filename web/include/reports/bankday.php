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


class Report_BankDay
{

	function getName($short=0)
	{
		if($short)	return "Банковский";
		else		return "Банковский отчёт";
	}


	function Form()
	{
		global $tmpl;
		$curdate=date("Y-m-d");
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<form action=''>
		<input type='hidden' name='mode' value='bankday'>
		<input type='hidden' name='opt' value='ok'>
		Выберите кассу:<br>
		<select name='kass'>");
		$res=mysql_query("SELECT `num`, `name`, `rs` FROM `doc_kassa` WHERE `ids`='bank'  ORDER BY `num`");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1] ($nxt[2])</option>");
		}
		$tmpl->AddText("</select><br>
		Начальная дата:<br>
		<input type='text' name='date_f' id='datepicker_f' value='$curdate'><br>
		Конечная дата:<br>
		<input type='text' name='date_t' id='datepicker_t' value='$curdate'><br>
		<button type='submit'>Сформировать</button></form>
		<script type=\"text/javascript\">
		initCalendar('datepicker_f',false);
		initCalendar('datepicker_t',false);
		</script>
		");
	}
	
	function MakeHTML()
	{
		global $tmpl;
		$tmpl->LoadTemplate('print');
		$dt_f=rcv('date_f');
		$dt_t=rcv('date_t');
		$kass=rcv('kass');
		$res=mysql_query("SELECT `num`, `name`, `rs` FROM `doc_kassa` WHERE `ids`='bank'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список банок");
		$kass_list=array();
		while($nxt=mysql_fetch_row($res))	$kass_list[$nxt[0]]=$nxt;
		$tmpl->SetText("<h1>Отчёт по банку {$kass_list[$kass][1]} ({$kass_list[$kass][2]}) с $dt_f по $dt_t</h1>");	
		$daystart=strtotime("$dt_f 00:00:00");
		$dayend=strtotime("$dt_t 23:59:59");
		$tmpl->AddText("<table width='100%'><tr><th>ID<th>Время<th>Документ<th>Приход<th>Расход<th>В банке");			
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`sum`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_types`.`name`, `doc_agent`.`name`, `doc_list`.`p_doc`, `t`.`name`, `p`.`altnum`, `p`.`subtype`, `p`.`date`, `p`.`sum`, `doc_list`.`bank`
		FROM `doc_list`
		LEFT JOIN `doc_agent`		ON `doc_agent`.`id` = `doc_list`.`agent`
		INNER JOIN `doc_types`		ON `doc_types`.`id` = `doc_list`.`type`
		LEFT JOIN `doc_list` AS `p`	ON `p`.`id`=`doc_list`.`p_doc`
		LEFT JOIN `doc_types` AS `t`	ON `t`.`id` = `p`.`type`
		WHERE `doc_list`.`ok`>'0' AND ( `doc_list`.`type`='4' OR `doc_list`.`type`='5')
		AND `doc_list`.`bank`='$kass'
		ORDER BY `doc_list`.`date`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить данные отчёта".mysql_error());
		$sum=$daysum=$prix=$rasx=0;
		$flag=0;
		$lastdate=0;
		while($nxt=mysql_fetch_array($res))
		{
			$lastdate=$nxt[3];
			$csum_p=$csum_r='';
			if( !$flag && $nxt[3]>=$daystart && $nxt[3]<=$dayend)
			{
				$flag=1;
				$sum_p=sprintf("%0.2f руб.",$sum);
				$tmpl->AddText("<tr><td colspan=5><b>На начало периода</b><td align='right'><b>$sum_p</b>");
			}
			if($nxt[1]==4)		$sum+=$nxt[2];
			else if($nxt[1]==5)	$sum-=$nxt[2];
			if($nxt[3]>=$daystart && $nxt[3]<=$dayend)
			{
				if($nxt[1]==4)
				{
					$daysum+=$nxt[2];
					$prix+=$nxt[2];
					$csum_p=sprintf("%0.2f руб.",$nxt[2]);
				}
				else if($nxt[1]==5)
				{
					$daysum-=$nxt[2];
					$rasx+=$nxt[2];
					$csum_r=sprintf("%0.2f руб.",$nxt[2]);
				}
				if($nxt[8])	$sadd="<br><i>к $nxt[9] N$nxt[10]$nxt[11] от ".date("d-m-Y H:i:s",$nxt[12])." на сумму ".sprintf("%0.2f руб",$nxt[13])."</i>";
				else		$sadd='';
				if($nxt[1]==4)		$sadd.="<br>от $nxt[7]";
				else if($nxt[1]==5)	$sadd.="<br>для $nxt[7]";

				$dt=date("H:i:s",$nxt[3]);
				$sum_p=sprintf("%0.2f руб.",$sum);
				
				$tmpl->AddText("<tr><td>$nxt[0]<td>$dt<td>$nxt[6] N$nxt[4]$nxt[5]   $sadd<td align='right'>$csum_p<td align='right'>$csum_r<td align='right'>$sum_p</tr>");	
			}
		}
		if( !$flag && $lastdate<=$dayend)
		{
				$sum_p=sprintf("%0.2f руб.",$sum);
				$tmpl->AddText("<tr><td colspan=5><b>На начало дня</b><td align='right'><b>$sum_p</b>");
		}
		if($flag)
		{
			$dsum_p=sprintf("%0.2f руб.",$daysum);
			$psum_p=sprintf("%0.2f руб.",$prix);
			$rsum_p=sprintf("%0.2f руб.",$rasx);
			$tmpl->AddText("<tr><td>-<td>-<td><b>На конец периода</b><td align='right'><b>$psum_p</b><td align='right'><b>$rsum_p</b><td align='right'><b>$sum_p</b>");
			$tmpl->AddText("<tr><td>-<td>-<td><b>Разница за период</b><td align='right' colspan=3><b>$dsum_p</b>");
 		}
 		else	$tmpl->AddText("<tr><td>-<td>-<td><b>Нет данных по балансу за выбранный период</b><td align='right'><b>нет данных</b><td align='right'><b>нет данных</b><td align='right'><b>нет данных</b>");
 		
 		$res=mysql_query("SELECT `name` FROM `users` WHERE `id`='{$_SESSION['uid']}'");
 		$nm=mysql_result($res,0,0);
 		
 		$tmpl->AddText("</table><br><br>
 		Cоответствие сумм подтверждаю ___________________ (банкир $nm)");
	}
	
	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakeHTML();	
	}
};

?>

