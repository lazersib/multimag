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


// Новый журнал документов. Оптимизированная версия для открытия большого журнала
include_once("core.php");
include_once("include/doc.core.php");
need_auth();
if(!isAccess('doc_list','view'))	throw new AccessException("");

SafeLoadTemplate($CONFIG['site']['inner_skin']);
$tmpl->HideBlock('left');

function json_encode_line($line)
{
	$ret='';
	foreach($line as $id => $value)
	{
		if($ret)	$ret.=',';
		$value=str_replace("'","\\'",$value);
		//$ret.="'$id':'".htmlentities($value,ENT_QUOTES,"UTF-8")."'";
		$ret.="'$id':'$value'";
	}
	return "{ $ret }";
}

if(!isset($_REQUEST['mode']))
{
	$tmpl->SetTitle("Новый журнал");
	doc_menu("<a href='?mode=print' title='Печать реестра'><img src='img/i_print.png' alt='Реестр документов' border='0'></a>");
	$tmpl->AddText("<script type='text/javascript' src='/css/doc_script.js'></script>
	<div id='doc_list_filter'></div>
	<div class='clear'></div>
	<div id='doc_list_status'></div>
	
	<table width='100%' cellspacing='1' onclick='hlThisRow(event)' id='doc_list' class='list'>
	<thead>
	<tr>
	<th width='55'>a.№</th><th width='20'>&nbsp;</th><th width='45'>id</th><th width='20'>&nbsp;<th>Тип<th>Доп<th>Агент<th>Сумма<th>Дата<th>Автор
	</tr>
	</thead>
	<tbody id='docj_list_body'>
	</tbody>
	</table>
	
	<br><b>Легенда</b>: строка - <span class='f_green'>с сайта</span>, <span class='f_red'>с ошибкой</span><br>Номер реализации - <span class='f_green'>Оплачено</span>, <span class='f_red'>Не оплачено</span>, <span class='f_brown'>Частично оплачено</span>, <span class='f_purple'>Переплата</span><br>
	Номер заявки - <span class='f_green'>Отгружено</span>, <span class='f_brown'>Частично отгружено</span>
	<script type='text/javascript' src='/js/doc_journal.js'></script>
	");

}
else
{
try
{
	ob_start();
	mysql_query("RESET QUERY CACHE");
	$sql="SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`ok`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`user` AS `author_id`, `doc_list`.`sum`, `doc_list`.`mark_del`, `doc_list`.`err_flag`, `doc_list`.`p_doc`, `doc_list`.`kassa`, `doc_list`.`bank`, `doc_list`.`sklad`,
	`doc_agent`.`name` AS `agent_name`,
	`users`.`name` AS `author_name`,
	`doc_types`.`name` AS `doc_name`,
	`doc_sklady`.`name` AS `sklad_name`,
	`doc_kassa`.`name` AS `kassa_name`,
	`doc_bank`.`name` AS `bank_name`,
	`na_sklad_n`.`name` AS `nasklad_name`
	FROM `doc_list`
	LEFT JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
	LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
	LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
	LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
	LEFT JOIN `doc_kassa`  ON `doc_kassa`.`num`=`doc_list`.`kassa` AND `doc_kassa`.`ids`='kassa'
	LEFT JOIN `doc_kassa` AS `doc_bank` ON `doc_bank`.`num`=`doc_list`.`bank` AND `doc_bank`.`ids`='bank'
	LEFT JOIN `doc_dopdata` AS `na_sklad_t` ON `na_sklad_t`.`doc`=`doc_list`.`id` AND `na_sklad_t`.`param`='na_sklad'
	LEFT JOIN `doc_sklady` AS `na_sklad_n` ON `na_sklad_n`.`id`=`na_sklad_t`.`value`
	WHERE 1
	ORDER by `doc_list`.`date` DESC
	LIMIT 100";
	$starttime=microtime(true);
	$res=mysql_query($sql);
	if(mysql_errno())	throw new MysqlException("Не удалость получить данные документов ");
	$jdata="";
	while($line=mysql_fetch_assoc($res))
	{
		$line['num_highlight']='';
		$line['date']=date('Y-m-d H:i:s',$line['date']);
		switch($line['type'])
		{
			case 1:
			case 2:
			case 3:
			case 8:
			case 12:
			case 15:
			case 17:	$line['data1']='Склад: '.$line['sklad_name'];
					break;
			case 4:
			case 5:		$line['data1']='Банк: '.$line['bank_name'];
					break;
			case 6:
			case 7:
			case 9:		$line['data1']='Касса: '.$line['kassa_name'];
					break;
			default:	$line['data1']='';
		}		
		if($line['type']==8)	$line['agent_name']='На склад: '.$line['nasklad_name'];
		if($line['type']==3)	// Отгрузки
		{
			$r=mysql_query("SELECT `doc_list_pos`.`doc` AS `doc_id`, `doc_list_pos`.`tovar` AS `pos_id`, `doc_list_pos`.`cnt`, (	SELECT SUM(`doc_list_pos`.`cnt`) FROM `doc_list_pos`
			INNER JOIN `doc_list` ON `doc_list_pos`.`doc`=`doc_list`.`id`
			WHERE `doc_list_pos`.`tovar`=`pos_id` AND `doc_list`.`p_doc`=`doc_id` AND `doc_list`.`type`='2' AND `doc_list`.`ok`>'0'
			) AS `r_cnt`
			FROM `doc_list_pos`
			WHERE `doc_list_pos`.`doc`='{$line['id']}'");
			if(mysql_errno())	throw new MysqlException("Не удалость получить данные отгрузок");
			$f=0;
			while($nx=mysql_fetch_row($r))
			{
				if($nx[3]<=0)	continue;
				$f=1;
				if($nx[2]>$nx[3])
				{
					$f=2;
					break;
				}
			}
			if($f==1)	$line['num_highlight']='f_green';
			if($f==2)	$line['num_highlight']='f_brown';
			mysql_free_result($r);
		}
		
		// Проплаты
		if(($line['type']==2)&&($line['sum']>0))
		{
			$add='';
			if($line['p_doc']) $add=" OR (`p_doc`='{$line['p_doc']}' AND (`type`='4' OR `type`='6'))";
			$rs=mysql_query("SELECT SUM(`sum`) FROM `doc_list` WHERE
			(`p_doc`='{$line['id']}' AND (`type`='4' OR `type`='6'))
			$add
				AND `ok`>0 AND `p_doc`!='0' GROUP BY `p_doc`");
			if(@$prop=mysql_result($rs,0,0))
			{
				$prop=round($prop,2);
				if($prop==$line['sum'])		$line['num_highlight']='f_green';
				else if($prop>$line['sum'])	$line['num_highlight']='f_purple';
				else 				$line['num_highlight']='f_brown';
			}
			else 					$line['num_highlight']='f_red';
		}

		if(($line['type']==1)&&($line['sum']>0))
		{
			$add='';
			if($line['p_doc']) $add=" OR (`p_doc`='{$line['p_doc']}' AND (`type`='5' OR `type`='7'))";
			$rs=mysql_query("SELECT SUM(`sum`) FROM `doc_list` WHERE
			(`p_doc`='{$line['id']}' AND (`type`='5' OR `type`='7'))
			$add
				AND `ok`>0 AND `p_doc`!='0' GROUP BY `p_doc`");
			if(@$prop=mysql_result($rs,0,0))
			{
				$prop=round($prop,1);
				if($prop==$line['sum'])		$line['num_highlight']='f_green';
				else if($prop>$line['sum'])	$line['num_highlight']='f_purple';
				else 				$line['num_highlight']='f_brown';
			}
		}
		
		if($jdata)	$jdata.=", ";
		$jdata.=json_encode($line);
	}
	$exec_time=round(microtime(true)-$starttime,3);
	echo "{result: 'ok', doc_list: [$jdata], user_id: {$_SESSION['uid']}, exec_time: '$exec_time'}";
	ob_end_flush();
	exit();
}
catch(Exception $e)
{
	mysql_query("ROLLBACK");
	echo "{result: 'err', error: '".htmlentities($e->getMessage(),ENT_QUOTES)."'}";
}
exit();
}


$tmpl->write();


?>
