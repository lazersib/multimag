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

/// Отчёт по остаткам комплектующих
class Report_Pos_Komplekt extends BaseGSReport
{
	function getName($short=0)
	{
		if($short)	return "По остаткам комплектующих";
		else		return "Отчёт по остаткам комплектующих";
	}
	

	function Form()
	{
		global $tmpl;
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='pos_komplekt'>
		Склад:<br>
		<select name='sklad'>");
		$res=mysql_query("SELECT `id`, `name` FROM `doc_sklady`");
		while($nxt=mysql_fetch_row($res))
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");		
		$tmpl->AddText("</select><br>
		Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Сформировать отчёт</button>
		</form>");
	}
	
	function Make($engine)
	{
		global $CONFIG;
		$this->loadEngine($engine);
		$sklad=rcv('sklad');
		
		$this->header($this->getName());
		$headers=array('ID','Код','Наименование','Остаток');
		$widths=array(5,10,75,10);

		switch(@$CONFIG['doc']['sklad_default_order'])
		{
			case 'vc':	$order='`doc_base`.`vc`';	break;
			case 'cost':	$order='`doc_base`.`cost`';	break;
			default:	$order='`doc_base`.`name`';
		}

		$this->tableBegin($widths);
		$this->tableHeader($headers);
		$cnt=0;
		$col_cnt=count($headers);
		$res_group=mysql_query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список групп");
		while($group_line=mysql_fetch_assoc($res_group))
		{
			$this->tableAltStyle();
			$this->tableSpannedRow(array($col_cnt),array($group_line['id'].': '.$group_line['name']));
			$this->tableAltStyle(false);
			$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base_cnt`.`cnt`
			FROM `doc_base_kompl`
			INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_base_kompl`.`kompl_id`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
			WHERE `doc_base`.`group`='{$group_line['id']}'
			GROUP BY `doc_base_kompl`.`kompl_id`
			ORDER BY $order
			");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список наименований");
			while($nxt=mysql_fetch_row($res))
			{
				if(!$nxt[3])	continue;
				$this->tableRow($nxt);
				$cnt++;
			}
		}
		$this->tableAltStyle();
		$this->tableSpannedRow(array(1,$col_cnt-1),array('Итого:', $cnt.' товаров'));
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

