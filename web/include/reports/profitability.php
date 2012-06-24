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

/// Отчёт по рентабельности и прибыли
/// Алгоритм расчёта основан на алгоритме вычисления актуальной цены поступления
class Report_Profitability extends BaseGSReport
{
	
	function GroupSelBlock()
	{
		global $tmpl;
		$tmpl->AddStyle("		
		div#sb
		{
			display:		none;
			border:			1px solid #888;
			max-height:		250px;
			overflow:		auto;
		}
		
		.selmenu
		{
			background-color:	#888;
			width:			auto;
			font-weight:		bold;
			padding-left:		20px;
		}
		
		.selmenu a
		{
			color:			#fff;
			cursor:			pointer;	
		}
		
		.cb
		{
			width:			14px;
			height:			14px;
			border:			1px solid #ccc;
		}
		
		");
		$tmpl->AddText("<script type='text/javascript'>
		function SelAll(flag)
		{
			var elems = document.getElementsByName('g[]');
			var l = elems.length;
			for(var i=0; i<l; i++)
			{
				elems[i].checked=flag;
				if(flag)	elems[i].disabled = false;
			}
		}
		
		function CheckCheck(ids)
		{
			var cb = document.getElementById('cb'+ids);
			var cont=document.getElementById('cont'+ids);
			if(!cont)	return;
			var elems=cont.getElementsByTagName('input');
			var l = elems.length;
			for(var i=0; i<l; i++)
			{
				if(!cb.checked)		elems[i].checked=false;
				elems[i].disabled =! cb.checked;
			}
		}
		
		</script>
		<div class='groups_block' id='sb'>
		<ul class='Container'>
		<div class='selmenu'><a onclick='SelAll(true)'>Выбрать всё<a> | <a onclick='SelAll(false)'>Снять всё</a></div>
		".$this->draw_groups_tree(0)."</ul></div>");
	}

	function getName($short=0)
	{
		if($short)	return "По рентабельности и прибыли";
		else		return "Отчёт по рентабельности и прибыли";
	}
	
	function Form()
	{
		global $tmpl;
		$d_t=date("Y-m-d");
		$d_f=date("Y-m-d",time()-60*60*24*31);
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='profitability'>
		<fieldset><legend>Дата</legend>
		С:<input type=text id='dt_f' name='dt_f' value='$d_f'><br>
		По:<input type=text id='dt_t' name='dt_t' value='$d_t'>
		</fieldset>
		Не показывать с прибылью менее <input type='text' name='ren_min_abs'> руб.<br>
		Не показывать с рентабельностью менее <input type='text' name='ren_min_pp'> %<br>
		<label><input type='checkbox' name='neg_pos' checked>Поместить наименования с отрицательной прибылью в начало списка</label>
		<br>
		<fieldset><legend>Отчёт по</legend>
		<select name='sel_type' id='sel_type'>
		<option value='all'>Всей номенклатуре</option>
		<option value='group'>Выбранной группе</option>
		</select>
		");
		$this->GroupSelBlock();
		$tmpl->AddText("
		</fieldset>
		Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Сформировать отчёт</button>
		</form>
		
		<script type=\"text/javascript\">
		function dtinit()
		{
			initCalendar('dt_f',false)
			initCalendar('dt_t',false)
		}
		function selectChange(event)
		{
			if(this.value=='group')
				document.getElementById('sb').style.display='block';
			else	document.getElementById('sb').style.display='none';
		}
		
		
		addEventListener('load',dtinit,false)	
		document.getElementById('sel_type').addEventListener('change',selectChange,false)	
		
		</script>
		");
	}
	
	/// Вычисляет прибыль по заданному наименованию за выбранный период
	function calcPos($pos_id, $date_from, $date_to)
	{
		settype($pos_id,'int');
		settype($date_from,'int');
		settype($date_to,'int');
		$cnt=$out_cnt=$cost=$profit=0;
		

		$res=mysql_query("SELECT `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_list`.`type`, `doc_list_pos`.`page`, `doc_dopdata`.`value`, `doc_list`.`date`
		FROM `doc_list_pos`
		INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND (`doc_list`.`type`<='2' OR `doc_list`.`type`='17')
		LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list_pos`.`doc` AND `doc_dopdata`.`param`='return'
		WHERE `doc_list_pos`.`tovar`='$pos_id' AND `doc_list`.`ok`>'0' AND `doc_list`.`date`<='$date_to' ORDER BY `doc_list`.`date`");
		if(mysql_errno())	throw new MysqlException("Не удалось выбрать данные движения");
		
		while($nxt=mysql_fetch_row($res))
		{
			if(($nxt[2]==2) || ($nxt[2]==17) && ($nxt[3]!='0'))	$nxt[0]=$nxt[0]*(-1);
			if($nxt[0]>0 && $nxt[4]!=1 && $cnt+$nxt[0]!=0  )
				$cost=( ($cnt*$cost)+($nxt[0]*$nxt[1])) / ($cnt+$nxt[0]);
			if($nxt[2]==2 && $nxt[5]>=$date_from)
			{
				$profit+=$nxt[0]*($cost-$nxt[1]);
				$out_cnt-=$nxt[0];
			}
			$cnt+=$nxt[0];
			if($cnt<0)	return array(0xFFFFBADF00D,0);	// Невозможно расчитать прибыль, если остатки уходили в минус
		}
		
		return array($profit,$out_cnt);
	}
	
	function Make($engine)
	{
		global $CONFIG;
		$this->loadEngine($engine);		

		$dt_f=strtotime(rcv('dt_f'));
		$dt_t=strtotime(rcv('dt_t'));
		$g=@$_POST['g'];
		$sel_type=rcv('sel_type');
		$ren_min_abs=rcv('ren_min_abs');
		$ren_min_pp=rcv('ren_min_pp');
		$neg_pos=rcv('neg_pos');
		
		$max_profit=0;
		
		$print_df=date('Y-m-d', $dt_f);
		$print_dt=date('Y-m-d', $dt_t);
		
		$this->header($this->getName()." с $print_df по $print_dt");
		
		$widths=array(5, 8, 53, 8, 9, 9, 8);
		$headers=array('ID','Код','Наименование','Б. цена','Продано','Прибыль','Рентаб.');

		$this->col_cnt=count($widths);
		$this->tableBegin($widths);
		$this->tableHeader($headers);
		
		mysql_query("CREATE TEMPORARY TABLE `temp_report_profit` (`pos_id` INT NOT NULL , `profit` DECIMAL( 16, 2 ) NOT NULL , `count` INT( 11 ) NOT NULL) ENGINE = MEMORY");
		if(mysql_errno())	throw new MysqlException("Не удалось создать временную таблицу");
		
		if($sel_type=='all')
		{
			$res=mysql_query("SELECT `id` FROM `doc_base`");
			if(mysql_errno())		throw MysqlException("Не удалось получить информацию о товарах");

			while($nxt=mysql_fetch_row($res))
			{
				list($profit,$count)=$this->calcPos($nxt[0], $dt_f, $dt_t);
				if($max_profit<$profit && $profit!=0xFFFFBADF00D)	$max_profit=$profit;
				mysql_query("INSERT INTO `temp_report_profit` VALUES ( $nxt[0], $profit, $count)");
			}
		}
		else if($sel_type=='group')
		{
			$res_group=mysql_query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список групп");
			while($group_line=mysql_fetch_assoc($res_group))
			{
				if(is_array($g))
					if(!in_array($group_line['id'],$g))	continue;

				$res=mysql_query("SELECT `doc_base`.`id` FROM `doc_base` WHERE `doc_base`.`group`='{$group_line['id']}'");
				if(mysql_errno())	throw new MysqlException("Не удалось получить список наименований");
				
				while($nxt=mysql_fetch_row($res))
				{
					list($profit,$count)=$this->calcPos($nxt[0], $dt_f, $dt_t);
					if($max_profit<$profit && $profit!=0xFFFFBADF00D)	$max_profit=$profit;
					mysql_query("INSERT INTO `temp_report_profit` VALUES ( $nxt[0], $profit, $count)");
				}
			}
		}
		
		if($neg_pos)
		{
			$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`, `doc_base`.`cost`, `temp_report_profit`.`profit`, `temp_report_profit`.`count` FROM `temp_report_profit`
			LEFT JOIN `doc_base` ON `temp_report_profit`.`pos_id`=`doc_base`.`id`
			WHERE `temp_report_profit`.`profit`<'0'
			ORDER BY `temp_report_profit`.`profit` ASC");
			if(mysql_errno())	throw new MysqlException("Не удалось получить временные данные");
			while($nxt=mysql_fetch_row($res))
			{
				$profitability=round($nxt[4]*100/$max_profit, 2);
				$this->tableRow(array($nxt[0], $nxt[1], $nxt[2], $nxt[3], $nxt[5], "$nxt[4] р.", "$profitability %"));
			}
		}
		
		$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`, `doc_base`.`cost`, `temp_report_profit`.`profit`, `temp_report_profit`.`count` FROM `temp_report_profit`
		LEFT JOIN `doc_base` ON `temp_report_profit`.`pos_id`=`doc_base`.`id`
		WHERE `temp_report_profit`.`profit`>'$ren_min_abs'
		ORDER BY `temp_report_profit`.`profit` DESC");
		if(mysql_errno())	throw new MysqlException("Не удалось получить временные данные");
		$sum=0;
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[4]==0xFFFFBADF00D)
			{
				$this->tableRow(array($nxt[0], $nxt[1], $nxt[2], $nxt[3], "ошибка", "ошибка", "conut < 0"));
			}
			else
			{
				$sum+=$nxt[4];
				$profitability=round($nxt[4]*100/$max_profit, 2);
				if($profitability<$ren_min_pp)	continue;
				$this->tableRow(array($nxt[0], $nxt[1], $nxt[2], $nxt[3], $nxt[5], "$nxt[4] р.", "$profitability %"));
			}
		}
		$this->tableRow(array("", "", "Всего", "","", "$sum р.", ""));
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

