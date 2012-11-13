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


class Report_Pos_NoSells extends BaseGSReport
{
	function getName($short=0)
	{
		if($short)	return "По номенклатуре без продаж";
		else		return "Отчёт по номенклатуре без продаж за заданный период";
	}
	

	function Form()
	{
		global $tmpl;
		$d_t=date("Y-m-d");
		$d_f=date("Y-m-d",time()-60*60*24*31);
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<script type=\"text/javascript\">
		function dtinit()
		{
			initCalendar('dt_f',false)
			initCalendar('dt_t',false)
		}
		addEventListener('load',dtinit,false)	
		</script>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='pos_nosells'>
		<fieldset><legend>Дата</legend>
		С:<input type=text id='dt_f' name='dt_f' value='$d_f'><br>
		По:<input type=text id='dt_t' name='dt_t' value='$d_t'>
		</fieldset>
		Склад:<br>
		<select name='sklad'>");
		$res=mysql_query("SELECT `id`, `name` FROM `doc_sklady`");
		while($nxt=mysql_fetch_row($res))
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");		
		$tmpl->AddText("</select><br>
		Группа товаров:<br>");
		$this->GroupSelBlock();
		$tmpl->AddText("Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Сформировать отчёт</button>
		</form>");
	}
	
	function Make($engine)
	{
		global $CONFIG;
		$this->loadEngine($engine);
		$dt_f=strtotime(rcv('dt_f'));
		$dt_t=strtotime(rcv('dt_t'));
		$gs=rcv('gs');
		$sklad=rcv('sklad');
		$g=@$_POST['g'];
		
		$print_df=date('Y-m-d', $dt_f);
		$print_dt=date('Y-m-d', $dt_t);
		$this->header("Отчёт по номенклатуре без продаж с $print_df по $print_dt");
		$headers=array('ID');
		$widths=array(5);

		$headers[]='Код';
		$widths[]=10;
		$widths[]=68;
		
		switch(@$CONFIG['doc']['sklad_default_order'])
		{
			case 'vc':	$order='`doc_base`.`vc`';	break;
			case 'cost':	$order='`doc_base`.`cost`';	break;
			default:	$order='`doc_base`.`name`';
		}
		$widths[]=8;
		$widths[]=8;
		$headers=array_merge($headers, array('Наименование', 'Ликв.','Остаток'));
		$this->tableBegin($widths);
		$this->tableHeader($headers);
		$cnt=0;
		$col_cnt=count($headers);
		$res_group=mysql_query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список групп");
		while($group_line=mysql_fetch_assoc($res_group))
		{
			if($gs && is_array($g))
				if(!in_array($group_line['id'],$g))	continue;
			$this->tableAltStyle();
			$this->tableSpannedRow(array($col_cnt),array($group_line['id'].': '.$group_line['name']));
			$this->tableAltStyle(false);
			$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_base`.`name`, CONCAT(`doc_base`.`likvid`,'%'), `doc_base_cnt`.`cnt`
			FROM `doc_base`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
			WHERE `doc_base`.`id` NOT IN (
			SELECT `doc_list_pos`.`tovar` FROM `doc_list_pos`
			INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<='$dt_t' AND `doc_list`.`type`='2' AND `doc_list`.`ok`>'0'
			) AND `doc_base`.`group`='{$group_line['id']}'
			ORDER BY $order");
			
			while($nxt=mysql_fetch_row($res))
			{
				$this->tableRow($nxt);
				$cnt++;
			}
		}
		$this->tableAltStyle();
		$this->tableSpannedRow(array(1,$col_cnt-1),array('Итого:', $cnt.' товаров без продаж'));
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

