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



class doc_s_Inform
{
	function View()
	{

	}
	
	function Service()
	{
		global $tmpl;
		$opt=rcv('opt');
		$pos=rcv('pos');
		$doc=rcv('doc');
		$tmpl->ajax=1;
		if($opt=='p_zak')
		{
			
			$rt=time()-60*60*24*365;
			$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_agent`.`name`
			FROM `doc_list_pos`
			INNER JOIN `doc_list` ON `doc_list`.`type`='11' AND `doc_list`.`ok`>'0' AND `doc_list`.`date`>'$rt' AND `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`id` NOT IN (SELECT DISTINCT `p_doc` FROM `doc_list` WHERE `ok` != '0' AND `type`='1' )
			LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
			WHERE `doc_list_pos`.`tovar`='$pos'");
			if(mysql_num_rows($res))
			{
				$tmpl->AddText("<table width='100%'>
				<tr><th>N<th>Дата<th>Агент<th>Кол-во<th>Цена");
				$i=0;
				while($nxt=mysql_fetch_row($res))
				{
					$nxt[3]=date('d.m.Y',$nxt[3]);
					$tmpl->AddText("<tr class='lin$i'><td><a href='/doc.php?mode=body&amp;doc=$nxt[0]'>$nxt[1]$nxt[2]</a><td>$nxt[3]<td>$nxt[6]<td>$nxt[4]<td>$nxt[5]");
					$i=1-$i;
				}
				
				$tmpl->AddText("</table>");
			}
			else $tmpl->msg("Не найдено!");
		}
		else if($opt=='vputi')
		{
			$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_agent`.`name`, `doc_dopdata`.`value`
			FROM `doc_list_pos`
			INNER JOIN `doc_list` ON `doc_list`.`type`='12' AND `doc_list`.`ok`>'0' AND `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`id` NOT IN (SELECT DISTINCT `p_doc` FROM `doc_list` WHERE `ok` != '0' AND `type`='1' )
			LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
			LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='dataprib'
			WHERE `doc_list_pos`.`tovar`='$pos'");
			if(mysql_num_rows($res))
			{
				$tmpl->AddText("<table width='290'>
				<tr><th>N<th>Дата док-та<th>Агент<th>Кол-во<th>Цена<th>Дата приб.");
				$i=0;
				while($nxt=mysql_fetch_row($res))
				{
					$nxt[3]=date('d.m.Y',$nxt[3]);
					$tmpl->AddText("<tr class='lin$i'><td><a href='/doc.php?mode=body&amp;doc=$nxt[0]'>$nxt[1]$nxt[2]</a><td>$nxt[3]<td>$nxt[6]<td>$nxt[4]<td>$nxt[5]<td>$nxt[7]");
					$i=1-$i;
				}
				
				$tmpl->AddText("</table>");
			}
			else $tmpl->msg("Не найдено!");
		
		}
		else if($opt=='rezerv')
		{
			$rt=time()-60*60*24*30;
			$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_agent`.`name`
			FROM `doc_list_pos`
			INNER JOIN `doc_list` ON `doc_list`.`type`='3' AND `doc_list`.`ok`>'0' AND `doc_list`.`id`!='$doc'
			AND `doc_list`.`id`=`doc_list_pos`.`doc` 
			AND `doc_list`.`id` NOT IN (SELECT DISTINCT `p_doc` FROM `doc_list` 
			INNER JOIN `doc_list_pos` ON `doc_list`.`id`=`doc_list_pos`.`doc`
			WHERE `ok` != '0' AND `type`='2' AND `doc_list_pos`.`tovar`='$pos' )
			LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
			WHERE `doc_list_pos`.`tovar`='$pos'");
			if(mysql_num_rows($res))
			{
				$tmpl->AddText("<table width='100%'>
				<tr><th>N<th>Дата<th>Агент<th>Кол-во<th>Цена");
// 				$i=0;
				while($nxt=mysql_fetch_row($res))
				{
					$nxt[3]=date('d.m.Y',$nxt[3]);
					$tmpl->AddText("<tr class='lin$i'><td><a href='/doc.php?mode=body&amp;doc=$nxt[0]'>$nxt[1]$nxt[2]</a><td>$nxt[3]<td>$nxt[6]<td>$nxt[4]<td>$nxt[5]");
					$i=1-$i;
				}
				
				$tmpl->AddText("</table>");
			}
			else $tmpl->msg("Не найдено!");
		
		}
		else if($opt=='dolgi')
		{
			$agent=rcv('agent');
			$res=mysql_query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `id`");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список организаций");
			while($nxt=mysql_fetch_row($res))
			{
				$dolg=DocCalcDolg($agent,0,$nxt[0]);
				$tmpl->AddText("<div>Долг перед $nxt[1]: <b>$dolg</b> руб.</div>");
			}
		}
	}
		
// Служебные функции класса
	function Edit()
	{
		global $tmpl;		
				
	}
	function ESave()
	{
		global $tmpl;		
		
	}	
	

	
};


?>
