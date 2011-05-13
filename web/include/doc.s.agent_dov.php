<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2010, BlackLight, TND Team, http://tndproject.org
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


$doc_types[3]="Доверенные лица";

class doc_s_Agent_dov
{
	function View()
	{
		global $tmpl;
		doc_menu(0,0);
		if(!isAccess('list_agent_dov','view'))	throw new AccessException("");
		$tmpl->AddText("<table width=100%>
		<tr><td><h1>Доверенные лица</h1>
		<td align=right>Отбор:<input type='text' id='f_search' onkeydown=\"DelayedSave('/docs.php?l=dov&mode=srv&opt=pl','list', 'f_search'); return true;\" >
		</table>
		<table width=100%><tr>");
		//<td id='groups' width='200' valign='top' class='lin0'>");
//		$this->draw_groups(0);
		$tmpl->AddText("<td id='list' valign='top'  class='lin1'>");
		$this->ViewList();
		$tmpl->AddText("</table>");            	
	}
	
	function Service()
	{
		global $tmpl;

		$opt=rcv("opt");
		$g=rcv('g');
		if($opt=='pl')
		{
			$s=rcv('s');
			$tmpl->ajax=1;
			if($s)
				$this->ViewListS($g,$s);
			else
				$this->ViewList($g);
		}
		else if($opt=='ep')
		{
			$this->Edit();			
		}
		else if($opt=='popup')
		{
			$ag=rcv('ag');
			$tmpl->ajax=1;
			$s=rcv('s');
			$i=0;

			$res=mysql_query("SELECT `id`,`surname`,`name` FROM `doc_agent_dov` WHERE `ag_id`='$ag' AND LOWER(`surname`) LIKE LOWER('%$s%') LIMIT 500");
			$row=mysql_numrows($res);
			$tmpl->AddText("Ищем: $s ($row совпадений) :$ag<br>");
			while($nxt=mysql_fetch_row($res))
			{
				$i=1;
				$tmpl->AddText("<a onclick=\"return SubmitData('$nxt[1] $nxt[2]',$nxt[0]);\">$nxt[1] $nxt[2]</a><br>");
			}
			if(!$i) $tmpl->AddText("<b>Искомая комбинация не найдена!</b>");
		}
		else $tmpl->msg("Неверный режим!");
	}
		
// Служебные функции класса
	function Edit()
	{
		global $tmpl;		
		doc_menu();
		$pos=rcv('pos');
		$ag_id=rcv('ag_id');
		$param=rcv('param');
		if(!isAccess('list_agent_dov','view'))	throw new AccessException("");
		if(($pos==0)&&($param!='g')) $param='';

		if($pos!=0)
		{
			//$this->PosMenu($pos, $param);
		}
		
		if($param=='')
		{
			$res=mysql_query("SELECT `id`, `ag_id` , `name` , `name2` , `surname` , `range` , `pasp_ser` , `pasp_num` , `pasp_kem` , `pasp_data` , `mark_del` 
			FROM `doc_agent_dov`
			WHERE `doc_agent_dov`.`id`='$pos'");
			$nxt=@mysql_fetch_row($res);
			$tmpl->AddText("<h1>Доверенные лица</h1>");
			if(!$nxt) $tmpl->AddText("<h3>Новая запись</h3>");

			$tmpl->AddText("<form action='' method=post><table cellpadding=0 width=100%>
			<input type=hidden name=mode value=esave>
			<input type=hidden name=l value=dov>
			<input type=hidden name=pos value=$pos>
			<tr><th width=20%>Параметр<th>Значение
			<tr class=lin0><td align=right width=20%>Имя
			<td><input type=text name='name' value='$nxt[2]'>
			<tr class=lin1><td align=right width=20%>отчество
			<td><input type=text name='name2' value='$nxt[3]'> 
			<tr class=lin0><td align=right width=20%>Фамилия
			<td><input type=text name='surname' value='$nxt[4]'> 
			<tr class=lin1><td align=right>Организация:
			<td><select name='ag_id'>");
			$res=mysql_query("SELECT `id`,`name` FROM `doc_agent` ORDER BY `name`");
			while($nx=mysql_fetch_row($res))
			{
				$i="";
				
				if( (($pos!=0)&&($nx[0]==$nxt[1])) || (($pos==0)&&($ag_id==$nx[0])) ) $i=" selected style='background-color: #bfb;'";
				$tmpl->AddText("<option value='$nx[0]' $i>$nx[1]</option>");
			}
			$tmpl->AddText("</select>
			<tr class=lin0><td align=right width=20%>Должность:
			<td><input type=text name='range' value='$nxt[5]'>
			<tr class=lin1><td align=right width=20%>Паспорт: серия
			<td><input type=text name='pasp_ser' value='$nxt[6]'>
			<tr class=lin0><td align=right width=20%>Паспорт: номер
			<td><input type=text name='pasp_num' value='$nxt[7]'>
			<tr class=lin1><td align=right width=20%>Паспорт: выдан
			<td><input type=text name='pasp_kem' value='$nxt[8]'>
			<tr class=lin0><td align=right width=20%>Паспорт: дата выдачи
			<td><input type=text name='pasp_data' value='$nxt[9]'>
			
			<tr class=lin1><td><td><input type=submit value='Сохранить'>
			
			</table></form>");

		}
		else $tmpl->msg("Неизвестная закладка");
		
	}
	function ESave()
	{
		global $tmpl, $CONFIG;		
		doc_menu();
		$pos=rcv('pos');
		$param=rcv('param');
		$group=rcv('g');

		if($pos!=0)
		{
			//$this->PosMenu($pos, $param);
		}

		if($param=='')
		{
			$name=rcv('name');
			$name2=rcv('name2');
			$surname=rcv('surname');
			$range=rcv('range');
			$ag_id=rcv('ag_id');
			$pasp_ser=rcv('pasp_ser');
			$pasp_num=rcv('pasp_num');
			$pasp_data=rcv('pasp_data');
			$pasp_kem=rcv('pasp_kem');
			$comment=rcv('comment');
			
			if($pos)
			{
				if(!isAccess('list_agent_dov','edit'))	throw new AccessException("");
				$res=mysql_query("UPDATE `doc_agent_dov` SET `ag_id`='$ag_id', `name`='$name', `name2`='$name2', `surname`='$surname', `range`='$range', `pasp_ser`='$pasp_ser', `pasp_num`='$pasp_num', `pasp_data`='$pasp_data', `pasp_kem`='$pasp_kem' WHERE `id`='$pos'");
				if($res) $tmpl->msg("Данные обновлены! $cc");
				else $tmpl->msg("Ошибка сохранения!".mysql_error(),"err");
			}
			else
			{	
				if(!isAccess('list_agent_dov','create'))	throw new AccessException("");
				$res=mysql_query("INSERT INTO `doc_agent_dov` ( `ag_id`, `name`, `name2`, `surname`, `range`, `pasp_ser`, `pasp_num`, `pasp_data`, `pasp_kem` ) VALUES ( '$ag_id', '$name', '$name2', '$surname', '$range', '$pasp_ser', '$pasp_num', '$pasp_date', '$pasp_kem')");
				$pos=mysql_insert_id();
				if($res)
					$tmpl->msg("Добавлена новая запись!");
				else $tmpl->msg("Ошибка сохранения!".mysql_error(),"err");
			}
		}
		else $tmpl->msg("Неизвестная закладка");
	}	
	
	function draw_level($select, $level)
	{
		$ret='';
		$res=mysql_query("SELECT `id`, `name`, `desc` FROM `doc_agent_group` WHERE `pid`='$level' ORDER BY `name`");
		$i=0;
		$r='';
		if($level==0) $r='IsRoot';
		$cnt=mysql_num_rows($res);
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[0]==0) continue;
			$item="<a href='' title='$nxt[2]' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&g=$nxt[0]','list'); return false;\" >$nxt[1]</a>";
	
			if($i>=($cnt-1)) $r.=" IsLast";
	
			$tmp=$this->draw_level($select, $nxt[0]); // рекурсия
			if($tmp)
				$ret.="
				<li class='Node ExpandClosed $r'>
			<div class='Expand'></div>
			<div class='Content'>$item
			</div><ul class='Container'>".$tmp.'</ul></li>';
		else
			$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
			$i++;
		}
		return $ret;
	}
	
	
	function ViewList($group=0,$s='')
	{
		global $tmpl;

        
		$sql="SELECT a.`id`, `a`.`surname`, a.`name` , a.`name2` , b.`name`, a.`range`, a.`mark_del`
		FROM `doc_agent_dov` AS `a`
		LEFT JOIN `doc_agent` AS `b` ON `a`.`ag_id`=`b`.`id`
		ORDER BY `a`.`surname`";

		$lim=50;
		$page=rcv('p');
		$res=mysql_query($sql);
		$row=mysql_num_rows($res);
		if($row>$lim)
		{
			$dop="g=$group";
			if($page<1) $page=1;
			if($page>1)
			{
				$i=$page-1;
				$tmpl->AddText("<a href='' onclick=\"EditThis('/docs.php?l=dov&mode=srv&opt=pl&$dop&p=$i','list'); return false;\">&lt;&lt;</a> ");
			}
			$cp=$row/$lim;
			for($i=1;$i<($cp+1);$i++)
			{
				if($i==$page) $tmpl->AddText(" <b>$i</b> ");
				else $tmpl->AddText("<a href='' onclick=\"EditThis('/docs.php?l=dov&mode=srv&opt=pl&$dop&p=$i','list'); return false;\">$i</a> ");
			}
			if($page<$cp)
			{
				$i=$page+1;
				$tmpl->AddText("<a href='' onclick=\"EditThis('/docs.php?l=dov&mode=srv&opt=pl&$dop&p=$i','list'); return false;\">&gt;&gt;</a> ");
			}
			$tmpl->AddText("<br>");
			$sl=($page-1)*$lim;
	
			$res=mysql_query("$sql LIMIT $sl,$lim");
		}

		if(mysql_num_rows($res))
		{
			$tmpl->AddText("<table width=100% cellspacing=1 cellpadding=2><tr>
			<th>№<th>Фамилия<th>Имя<th>Отчество<th>Организация<th>Должность");
			$this->DrawTable($res,$s);
			$tmpl->AddText("</table>");

		}
		else $tmpl->msg("В выбранной группе записей не найдено!");
		$tmpl->AddText("
		<a href='/docs.php?l=dov&mode=srv&opt=ep&pos=0&g=$group'><img src='/img/i_add.gif' alt=''> Добавить</a> |
		<a href='/docs.php?l=agent&mode=search'><img src='/img/i_find.png' alt=''> Расширенный поиск</a>");
	}
	
	function ViewListS($group=0,$s)
	{
		global $tmpl;
		$sf=0;
		$tmpl->AddText("<table width=100% cellspacing=1 cellpadding=2><tr>
		<th>№<th>Фамилия<th>Имя<th>Отчество<th>Организация<th>Должность");
		        
		$sql="SELECT a.`id`, `a`.`surname`, a.`name` , a.`name2` , b.`name`, a.`range`, a.`mark_del`
		FROM `doc_agent_dov` AS `a`
		LEFT JOIN `doc_agent` AS `b` ON `a`.`ag_id`=`b`.`id`";
		
		$sqla=$sql."WHERE `a`.`name` LIKE '$s%' OR `a`.`surname` LIKE '$s%' ORDER BY `a`.`name` LIMIT 30";
		$res=mysql_query($sqla);
		echo mysql_error();
		if($cnt=mysql_num_rows($res))
		{
			$tmpl->AddText("<tr class=lin0><th colspan=16 align=center>Поиск по названию, начинающемуся на $s: найдено $cnt");
			$this->DrawTable($res,$s);
			$sf=1;
		}
		
		$sqla=$sql."WHERE (`a`.`name` LIKE '%$s%' OR `a`.`surname` LIKE '%$s%') AND (`a`.`name` NOT LIKE '$s%' AND `a`.`surname` NOT LIKE '$s%') ORDER BY `a`.`name` LIMIT 30";
		$res=mysql_query($sqla);
		if($cnt=mysql_num_rows($res))
		{
			$tmpl->AddText("<tr class=lin0><th colspan=16 align=center>Поиск по названию, содержащему $s: найдено $cnt");
			$this->DrawTable($res,$s);
			$sf=1;
		}
		
		$tmpl->AddText("</table><a href='/docs.php?l=agent&mode=srv&opt=ep&pos=0&g=$group'><img src='/img/i_add.gif' alt=''> Добавить</a>");
		
		if($sf==0)
			$tmpl->msg("По данным критериям записей не найдено!");
	}
	
	function Search()
	{
		global $tmpl;
		$opt=rcv("opt");
		if($opt=='')
		{
			doc_menu();
			$tmpl->AddText("<h1>Расширенный поиск</h1>
			<form action='docs.php' method='post'>
			<input type=hidden name=mode value=search>
			<input type=hidden name=opt value=s>
			<table width=100%>
			<tr><th colspan=2>Наименование
			<th colspan=2>Производитель
			<th>Место на складе
			<tr class=lin1>
			<td colspan=2><input type=text name=name><br><label><input type=checkbox name='analog' value=1>И аналог</label>
			<td colspan=2>
			<input type=text id='proizv' name='proizv' value='$nxt[3]' onkeydown=\"return AutoFill('/docs.php?mode=search&opt=pop_proizv','proizv','proizv_p')\"><br>
			<div id='proizv_p' class='dd'></div>
			<td><input type=text name=mesto>
			
			<tr>
			<th>Внутренний диаметр
			<th>Внешний диаметр
			<th>Высота
			<th>Масса
			<th>Цена
			<tr class=lin1>
			<td>От: <input type=text name='di_min'><br>до: <input type=text name='di_max'>
			<td>От: <input type=text name='de_min'><br>до: <input type=text name='de_max'>
			<td>От: <input type=text name='size_min'><br>до: <input type=text name='size_max'>
			<td>От: <input type=text name='m_min'><br>до: <input type=text name='m_max'>
			<td>От: <input type=text name='cost_min'><br>до: <input type=text name='cost_max'>
			
			<tr>
			<td colspan=5 align=center><input type='submit' value='Найти'>
			</table>
			</form>");
		}
		else if($opt=='s')
		{
			doc_menu();
			$tmpl->AddText("<h1>Результаты</h1>");
			$name=rcv('name');
			$analog=rcv('analog');
			$proizv=rcv('proizv');
			$mesto=rcv('mesto');
			$di_min=rcv('di_min');
			$di_max=rcv('di_max');
			$de_min=rcv('de_min');
			$de_max=rcv('de_max');
			$size_min=rcv('size_min');
			$size_max=rcv('size_max');
			$m_min=rcv('m_min');
			$m_max=rcv('m_max');
			$cost_min=rcv('cost_min');
			$cost_max=rcv('cost_max');
			
			$sklad=$_SESSION['sklad_num'];
			
			$sql="SELECT `doc_base`.`id`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`, `doc_base`.`cost_date`,
			`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`,
			`doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`, (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`), `doc_base`.`mincnt`
			FROM `doc_base`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			WHERE 1 ";
			
			if($name)
			{		
				if(!$analog) 	$sql.="AND `doc_base`.`name` LIKE '%$name%'";
				else $sql.="AND (`doc_base_dop`.`analog` LIKE '%$name%' OR `doc_base`.`name` LIKE '%$name%')";
					
			}
			if($proizv)		$sql.="AND `doc_base`.`proizv` LIKE '%$proizv%'";
			if($mesto)		$sql.="AND `doc_base_cnt`.`mesto` LIKE '$mesto'";
			if($di_min)		$sql.="AND `doc_base_dop`.`d_int` >= '$di_min'";
			if($di_max)		$sql.="AND `doc_base_dop`.`d_int` <= '$di_max'";
			if($de_min)		$sql.="AND `doc_base_dop`.`d_ext` >= '$de_min'";
			if($di_max)		$sql.="AND `doc_base_dop`.`d_ext` <= '$di_max'";
			if($size_min)	$sql.="AND `doc_base_dop`.`size` >= '$size_min'";
			if($size_max)	$sql.="AND `doc_base_dop`.`size` <= '$size_max'";
			if($m_min)		$sql.="AND `doc_base_dop`.`mass` >= '$m_min'";
			if($m_max)		$sql.="AND `doc_base_dop`.`mass` <= '$m_max'";
			if($cost_min)	$sql.="AND `doc_base`.`cost` >= '$cost_min'";
			if($cost_max)	$sql.="AND `doc_base`.`cost` <= '$cost_max'";
			

			$sql.="ORDER BY `doc_base`.`name`";
			
			
			$tmpl->AddText("<table width=100% cellspacing=1 cellpadding=2><tr>
			<th>№<th>Наименование<th>Производитель<th>Цена, р.<th>Ликв.<th>Рыноч.цена, р.<th>Аналог<th>Тип<th>d<th>D<th>B
			<th>Масса<th>Резерв<th>Склад<th>Всего<th>Место");
			
			$res=mysql_query($sql);
			if($cnt=mysql_num_rows($res))
			{
				$tmpl->AddText("<tr class=lin0><th colspan=16 align=center>Параметрический поиск, найдено $cnt");
				$this->DrawTable($res,$name);
				$sf=1;
			}
			$tmpl->AddText("</table>");
			
		
		}
	}
	
	function DrawTable($res,$s)
	{
		global $tmpl;
		$i=0;
		while($nxt=mysql_fetch_row($res))
		{
			$nxt[1]=SearchHilight($nxt[1],$s);
			$nxt[2]=SearchHilight($nxt[2],$s);
			$nxt[3]=SearchHilight($nxt[3],$s);

			$tmpl->AddText("<tr class='lin$i pointer' align=right>
			
			<td><a href='/docs.php?l=dov&mode=srv&opt=ep&pos=$nxt[0]'>$nxt[0]</a><td align=left>$nxt[1]<td>$nxt[2]<td>$nxt[3]<td>$nxt[4]<td>$nxt[5]");
		}	
	}
	
	function PosMenu($pos, $param)
	{
	
	}
	
};


?>
