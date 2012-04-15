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
		$g=@$_POST['g'];
		
		$print_df=date('Y-m-d', $dt_f);
		$print_dt=date('Y-m-d', $dt_t);
		$this->header("Отчёт по номенклатуре без продаж с $print_df по $print_dt");
		$headers=array('ID');
		$widths=array(5);

		if($CONFIG['poseditor']['vc'])
		{
			$headers[]='Код';
			$widths[]=10;
			$widths[]=65;
		}
		else	$widths[]=75;
		$widths[]=10;
		$headers=array_merge($headers, array('Наименование', 'Ликв.'));
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
			$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_base`.`name`, CONCAT(`doc_base`.`likvid`,'%')
			FROM `doc_base`
			WHERE `doc_base`.`id` NOT IN (
			SELECT `doc_list_pos`.`tovar` FROM `doc_list_pos`
			INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<='$dt_t' AND `doc_list`.`type`='2' AND `doc_list`.`ok`>'0'
			) AND `doc_base`.`group`='{$group_line['id']}'
			ORDER BY `doc_base`.`name`");
			
			while($nxt=mysql_fetch_row($res))
			{
				if(!$CONFIG['poseditor']['vc'])	unset($nxt[1]);
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

