<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2013, BlackLight, TND Team, http://tndproject.org
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


class Report_Outlay_Items extends BaseReport
{

	function getName($short=0)
	{
		if($short)	return "По статьям расходов";
		else		return "Отчёт по статьям расходов";
	}

	function Form()
	{
		global $tmpl;
		$curdate=date("Y-m-d");
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<script src='/js/calendar.js'></script>
		<form action=''>
		<input type='hidden' name='mode' value='outlay_items'>
		Выберите фирму:<br>
		<select name='firm'>");
		$res=mysql_query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `id`");
		$tmpl->AddText("<option value='0'>--не выбрана--</option>");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");
		}
		$tmpl->AddText("</select><br>
		Начальная дата:<br>
		<input type='text' name='date_f' id='datepicker_f' value='$curdate'><br>
		Конечная дата:<br>
		<input type='text' name='date_t' id='datepicker_t' value='$curdate'><br>
		Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Сформировать</button></form>
		<script type=\"text/javascript\">
		initCalendar('datepicker_f',false);
		initCalendar('datepicker_t',false);
		</script>
		");
	}
	
	function Make($engine)
	{
		global $CONFIG;
		$this->loadEngine($engine);
		
		$dt_f=rcv('date_f');
		$dt_t=rcv('date_t');
		$firm=rcv('firm');
		
		
		$this->header($this->getName()." с $dt_f по $dt_t");
	
		$daystart=strtotime("$dt_f 00:00:00");
		$dayend=strtotime("$dt_t 23:59:59");
		
		$widths=array(5, 18, 47, 20, 10);
		$headers=array('ID', 'Дата, время', 'Агент', 'Документ', 'Сумма');
		
		$this->col_cnt=count($widths);
		$this->tableBegin($widths);
		$this->tableHeader($headers);
		
		$res_vr=mysql_query("SELECT `id`, `name` FROM `doc_rasxodi` ORDER BY `id`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список расходов");
		while($vr=mysql_fetch_row($res_vr))
		{
			$this->tableAltStyle();
			$this->tableSpannedRow( array($this->col_cnt), array("$vr[0]. $vr[1]") );
			$this->tableAltStyle(false);
			$sum=0;
			$res=mysql_query("SELECT `doc_list`.`id` AS `doc_id`, `doc_list`.`date`, `doc_list`.`sum`, `doc_types`.`name` AS `doc_name`, `doc_agent`.`name` AS `agent_fullname`
			FROM `doc_list`
			INNER JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='rasxodi' AND `doc_dopdata`.`value`='$vr[0]'
			LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
			LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
			WHERE `doc_list`.`ok`>'0' AND ( `doc_list`.`type`='5' OR `doc_list`.`type`='7') AND `doc_list`.`date`>='$daystart' AND `doc_list`.`date`<='$dayend'
			ORDER BY `doc_list`.`date`");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список документов");
			while($nxt=mysql_fetch_assoc($res))
			{
				$dt=date("Y-m-d H:i:s",$nxt['date']);
				$this->tableRow( array($nxt['doc_id'], $dt, unhtmlentities($nxt['agent_fullname']), $nxt['doc_name'], $nxt['sum']) );
				$sum+=$nxt['sum'];
			}
			$this->tableSpannedRow( array(2,2,1), array('','Итого по статье:', sprintf("%0.2f",$sum) ) );
			
		}
		
		
		
		
// 		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`sum`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_types`.`name`, `doc_agent`.`name`, `doc_list`.`p_doc`, `t`.`name`, `p`.`altnum`, `p`.`subtype`, `p`.`date`, `p`.`sum`, `doc_list`.`kassa`, `doc_dopdata`.`value` AS `vk_value`
// 		FROM `doc_list`
// 		LEFT JOIN `doc_agent`		ON `doc_agent`.`id` = `doc_list`.`agent`
// 		INNER JOIN `doc_types`		ON `doc_types`.`id` = `doc_list`.`type`
// 		LEFT JOIN `doc_list` AS `p`	ON `p`.`id`=`doc_list`.`p_doc`
// 		LEFT JOIN `doc_types` AS `t`	ON `t`.`id` = `p`.`type`
// 		LEFT JOIN `doc_dopdata`		ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='v_kassu'
// 		WHERE `doc_list`.`ok`>'0' AND ( `doc_list`.`type`='6' OR `doc_list`.`type`='7' OR `doc_list`.`type`='9')
// 		AND (`doc_list`.`kassa`='$kass' OR `doc_dopdata`.`value`='$kass')
// 		ORDER BY `doc_list`.`date`");
// 		$sum=$daysum=$prix=$rasx=0;
// 		$flag=0;
// 		while($nxt=mysql_fetch_array($res))
// 		{
// 			$csum_p=$csum_r='';
// 			if( !$flag && $nxt[3]>=$daystart && $nxt[3]<=$dayend)
// 			{
// 				$flag=1;
// 				$sum_p=sprintf("%0.2f",$sum);
// 				$this->tableAltStyle();
// 				$this->tableSpannedRow(array($this->col_cnt-1,1),array("На начало периода",$sum_p));
// 				$this->tableAltStyle(false);
// 			}
// 			if($nxt[1]==6)		$sum+=$nxt[2];
// 			else if($nxt[1]==7)	$sum-=$nxt[2];
// 			else if($nxt[1]==9)
// 			{
// 				if($nxt['kassa']==$kass)
// 					$sum-=$nxt[2];
// 				else	$sum+=$nxt[2];
// 			}
// 			if($nxt[3]>=$daystart && $nxt[3]<=$dayend)
// 			{
// 				if($nxt[1]==6)
// 				{
// 					$daysum+=$nxt[2];
// 					$prix+=$nxt[2];
// 					$csum_p=sprintf("%0.2f",$nxt[2]);
// 				}
// 				else if($nxt[1]==7)
// 				{
// 					$daysum-=$nxt[2];
// 					$rasx+=$nxt[2];
// 					$csum_r=sprintf("%0.2f",$nxt[2]);
// 				}
// 				else
// 				{
// 					if($nxt['kassa']==$kass)
// 					{
// 						$daysum-=$nxt[2];
// 						$rasx+=$nxt[2];
// 						$csum_r=sprintf("%0.2f",$nxt[2]);
// 					}
// 					else
// 					{
// 						$daysum+=$nxt[2];
// 						$prix+=$nxt[2];
// 						$csum_p=sprintf("%0.2f",$nxt[2]);
// 					}
// 				}
// 				if($nxt[8])	$sadd="\nк $nxt[9] N$nxt[10]$nxt[11] от ".date("d-m-Y H:i:s",$nxt[12])." на сумму ".sprintf("%0.2f руб",$nxt[13])."";
// 				else		$sadd='';
// 				if($nxt[1]==6)		$sadd.="\nот $nxt[7]";
// 				else if($nxt[1]==7)	$sadd.="\nдля $nxt[7]";
// 				else if($nxt[1]==9)
// 				{
// 					if($nxt['kassa']==$kass)	$sadd.="\nв кассу {$kass_list[$nxt['vk_value']]}";
// 					else				$sadd.="\nиз кассы {$kass_list[$nxt['kassa']]}";
// 				}
// 				$dt=date("H:i:s",$nxt[3]);
// 				$sum_p=sprintf("%0.2f",$sum);
// 				$this->tableRow(array($nxt[0], $dt, "$nxt[6] N$nxt[4]$nxt[5]   $sadd", $csum_p, $csum_r, $sum_p));
// 			}
// 		}
// 		if( !$flag)
// 		{
// 				$sum_p=sprintf("%0.2f",$sum);
// 				$this->tableAltStyle();
// 				$this->tableSpannedRow(array($this->col_cnt-1,1),array("На начало периода",$sum_p));
// 				$this->tableAltStyle(false);
// 		}
// 		if($flag)
// 		{
// 			$dsum_p=sprintf("%0.2f",$daysum);
// 			$psum_p=sprintf("%0.2f",$prix);
// 			$rsum_p=sprintf("%0.2f",$rasx);
// 			
// 			$this->tableAltStyle();
// 			$this->tableSpannedRow(array(3,1,1,1),array("На конец периода",$psum_p,$rsum_p,$sum_p));
// 			$this->tableSpannedRow(array(3,3),array("Разница за период",$dsum_p));
// 			$this->tableAltStyle(false);
//  		}
//  		else
//  		{
// 			$this->tableSpannedRow(array($this->col_cnt),array("Нет данных по балансу за выбранный период"));
//  		}
 		

		$this->tableEnd();
		$this->output();
		exit(0);
	}
	
	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->Make($opt);
	}
};

?>

