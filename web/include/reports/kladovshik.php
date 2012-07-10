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

/// Отчёт по кладовщикам в реализациях
class Report_Kladovshik extends BaseGSReport
{
	function getName($short=0)
	{
		if($short)	return "По кладовщикам в реализациях";
		else		return "Отчёт по кладовщикам в реализациях";
	}
	
	function Form()
	{
		global $tmpl;
		$d_f=date("Y-m-d",time()-60*60*24*31);
		$d_t=date("Y-m-d");
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='kladovshik'>
		<fieldset><legend>Дата</legend>
		От: <input type=text id='dt_f' name='dt_f' value='$d_f'><br>
		До: <input type=text id='dt_t' name='dt_t' value='$d_t'>
		</fieldset>
		Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Сформировать отчёт</button>
		</form>
		
		<script type=\"text/javascript\">
		function dtinit()
		{
			initCalendar('dt_apply',false)
			initCalendar('dt_update',false)
		}
		
		addEventListener('load',dtinit,false)
		</script>
		");
	}
	
	function Make($engine)
	{
		global $CONFIG;
		$this->loadEngine($engine);		

		$dt_f=strtotime(rcv('dt_f'));
		$dt_t=strtotime(rcv('dt_t'));
		
		$print_f=date('Y-m-d', $dt_f);
		$print_t=date('Y-m-d', $dt_t);
		
		$this->header($this->getName().", с $print_f по $print_t");
		
		$widths=array(10,20,20, 20, 30);
		$headers=array('ID док','Дата','Сумма','Автор','Кладовщик');
		
		$this->col_cnt=count($widths);
		$this->tableBegin($widths);
		$this->tableHeader($headers);
		
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`sum`, `autor`.`name`, `klad`.`name`
		FROM `doc_list`
		LEFT JOIN `users` AS `autor` ON `autor`.`id`=`doc_list`.`user`
		LEFT JOIN `doc_dopdata` ON `doc_list`.`id`=`doc_dopdata`.`doc` AND `doc_dopdata`.`param`='kladovshik'
		LEFT JOIN `users` AS `klad` ON `klad`.`id`=`doc_dopdata`.`value`
		WHERE `doc_list`.`ok`>'0' AND `doc_list`.`type`='2' AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<='$dt_t'");
		if(mysql_errno())		throw new MysqlException("Не удалось получить список документов");
		while($nxt=mysql_fetch_row($res))
		{
			
			$this->tableRow(array($nxt[0], date('Y-m-d H:i:s',$nxt[1]), $nxt[2], $nxt[3], $nxt[4]));
		}
// 		$res=mysql_query("SELECT `id`, `name` FROM `doc_sklady` WHERE `id`='{$this->sklad}'");
// 		if(mysql_errno())		throw MysqlException("Не удалось получить информацию о складе");
// 		if(!mysql_num_rows($res))	throw new Exception("Склад не найден");
// 		list($sklad_id,$sklad_name)=mysql_fetch_row($res);
// 		
// 		
// 		
// 		if(!$this->w_docs)	
// 		{
// 			$widths=array(5,8,38, 7, 7, 7, 7, 7, 7, 7);
// 			$headers=array('ID','Код','Наименование','Базов. цена','Нач. кол-во','Приход','Реализ.','Перем.','Сборка','Итог');
// 		}
// 		else if($this->div_dt)
// 		{
// 			$widths=array(15,25,40,7,7,7);
// 			$headers=array('Дата','Документ','Источник','Кол-во','Цена','Сумма');
// 		}
// 		else 
// 		{
// 			$widths=array(15,21,40,8,8,8);
// 			$headers=array('Дата','Документ','','Приход','Расход','Кол-во');
// 		}
// 		$this->col_cnt=count($widths);
// 		$this->tableBegin($widths);
// 		$this->tableHeader($headers);
// 		switch($CONFIG['doc']['sklad_default_order'])
// 		{
// 			case 'vc':	$order='`doc_base`.`vc`';	break;
// 			case 'cost':	$order='`doc_base`.`cost`';	break;
// 			default:	$order='`doc_base`.`name`';
// 		}
// 		if($sel_type=='pos')
// 		{
// 			$pos_id=rcv('pos_id');
// 			$res=mysql_query("SELECT `vc`, `name`, `doc_base`.`cost`  FROM `doc_base` WHERE `id`='$pos_id'");
// 			if(mysql_errno())		throw MysqlException("Не удалось получить информацию о товаре");
// 			if(mysql_num_rows($res)==0)	throw new Exception("Товар не выбран!");
// 			$tov_data=mysql_fetch_row($res);
// 			$this->outPos($pos_id, $tov_data[0], $tov_data[1], $dt_f, $dt_t, $tov_data[2]);
// 		}
// 		else if($sel_type=='all')
// 		{
// 			$res=mysql_query("SELECT `id`, `vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`, `doc_base`.`cost` FROM `doc_base` ORDER BY $order");
// 			if(mysql_errno())		throw MysqlException("Не удалось получить информацию о товарах");
// 
// 			while($nxt=mysql_fetch_row($res))
// 			{
// 				$this->outPos($nxt[0], $nxt[1], $nxt[2], $dt_f, $dt_t, $nxt[3]);
// 			}
// 		}
// 		else if($sel_type=='group')
// 		{
// 			$res_group=mysql_query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
// 			if(mysql_errno())	throw new MysqlException("Не удалось получить список групп");
// 			while($group_line=mysql_fetch_assoc($res_group))
// 			{
// 				if(is_array($g))
// 					if(!in_array($group_line['id'],$g))	continue;
// 
// 				$this->tableAltStyle();
// 				$this->tableSpannedRow(array($this->col_cnt),array($group_line['id'].'. '.$group_line['name']));
// 				$this->tableAltStyle(false);
// 				$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`, `doc_base`.`cost`
// 				FROM `doc_base`
// 				WHERE `doc_base`.`group`='{$group_line['id']}'
// 				ORDER BY $order");
// 				if(mysql_errno())	throw new MysqlException("Не удалось получить список наименований");
// 				
// 				while($nxt=mysql_fetch_row($res))
// 				{
// 					$this->outPos($nxt[0], $nxt[1], $nxt[2], $dt_f, $dt_t, $nxt[3]);
// 				}
// 			}
// 		}
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

