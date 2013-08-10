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

/// Отчёт по резервам товара
class Report_Reserve extends BaseGSReport
{
	function getName($short=0)
	{
		if($short)	return "Резервы";
		else		return "Отчёт по резервам товара";
	}
	
	function Form()
	{
		global $tmpl;
		$d_t=date("Y-m-d");
		$d_f=date("Y-m-d",time()-60*60*24*31);
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='reserve'>
		
		");
		$this->GroupSelBlock();
		$tmpl->AddText("

		Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Сформировать отчёт</button>
		</form>

		");
	}
	
	function groupsProcess($pgroup_id, $group_list)
	{
		$res=mysql_query("SELECT `id`, `name`
		FROM `doc_group` WHERE `pid`='$pgroup_id' ORDER BY `id`");
		if(!$res)	throw new MysqlException("Не удалось получить список групп");
		while($group_line=mysql_fetch_assoc($res))
		{
			if(is_array($group_list))	if(!in_array($group_line['id'], $group_list))	continue;
			$h_print=0;
			
			$pres=mysql_query("SELECT `id` AS `pos_id`, `name`, `vc`, (
				SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `pos_id`=`id`) AS `cnt`
			FROM `doc_base` WHERE `group`='{$group_line['id']}' ORDER BY {$this->order}");
			while($pos_line=mysql_fetch_assoc($pres))
			{
				$r_res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_agent`.`name` AS `agent_name`
				FROM `doc_list_pos`
				INNER JOIN `doc_list` ON `doc_list`.`type`='3' AND `doc_list`.`ok`>'0' AND `doc_list`.`id`=`doc_list_pos`.`doc` 
				AND `doc_list`.`id` NOT IN (
					SELECT DISTINCT `p_doc` FROM `doc_list`
					INNER JOIN `doc_list_pos` ON `doc_list`.`id`=`doc_list_pos`.`doc`
					WHERE `ok` != '0' AND `type`='2' AND `doc_list_pos`.`tovar`='{$pos_line['pos_id']}' )
				LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
				WHERE `doc_list_pos`.`tovar`='{$pos_line['pos_id']}'");
				if(mysql_num_rows($r_res))
				{
					if(!$h_print)
					{
						$h_print=1;
						$this->tableAltStyle();
						$this->tableSpannedRow(array(1, $this->col_cnt-1 ),array($group_line['id'], $group_line['name']));
						$this->tableAltStyle(false);
					}
					$r=0;
					while($nxt=mysql_fetch_assoc($r_res))
					{
						$r+=$nxt['cnt'];
					}
					mysql_data_seek($r_res,0);
					$this->tableRow(array($pos_line['pos_id'], $pos_line['vc'], $pos_line['name'], $r, $pos_line['cnt']));
					while($nxt=mysql_fetch_assoc($r_res))
					{
						$date=date("Y-m-d",$nxt['date']);
						$this->tableSpannedRow(array(2,1,2), array("{$nxt['id']} / $date", $nxt['agent_name'], $nxt['cnt']));
					}
				
				}
			
			
			}
			
			$this->groupsProcess($group_line['id'], $group_list);
		}
	}
	
	
	
	function Make($engine)
	{
		global $CONFIG;
		$this->loadEngine($engine);		

		$g=@$_POST['g'];

		$this->header($this->getName());
		
		$widths=array(5,8,73, 7, 7);
		$headers=array('ID', 'Код','Наименование', 'Резерв', 'Склад');

		$this->col_cnt=count($widths);
		$this->tableBegin($widths);
		$this->tableHeader($headers);
		switch(@$CONFIG['doc']['sklad_default_order'])
		{
			case 'vc':	$this->order='`doc_base`.`vc`';	break;
			case 'cost':	$this->order='`doc_base`.`cost`';	break;
			default:	$this->order='`doc_base`.`name`';
		}
		
		$this->groupsProcess(0,$g);
		
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

