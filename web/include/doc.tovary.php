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

// Работа с товарами

function doc_poslist($doc)
{
	global $tmpl, $CONFIG;
	get_docdata($doc);
	global $doc_data;
	global $dop_data;

	if($doc_data[1]!=3)	$sklad=$doc_data[7];
	else $sklad=1;
	if(!$doc_data[6])	$refcost="<a href='' title='Сбросить' onclick=\"EditThis('/doc.php?mode=srv&opt=rc&doc=$doc','poslist'); return false;\"><img src='/img/i_reload.png' alt='Сбросить'></a>";
	else			$refcost='';
	$tmpl->AddText("<div id='poslist'><table width='100%' cellspacing='1' cellpadding='2'>
	<tr><th align=left>№<th>Наименование<th title='Выбранная цена по прайсу'>Выбр. цена<th>Цена $refcost<th width='60px'>Кол-во<th>Стоимость<th title='Остаток товара на складе'>Остаток<th>Место");
	if(@$CONFIG['site']['sn_enable'] && $doc_data[1]<3)	$tmpl->AddText("<th>SN");
	$res=mysql_query("SELECT `doc_base`.`name`, `doc_base`.`cost`, `doc_base_cnt`.`cnt`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`sn`, `doc_list_pos`.`cost`, `doc_list_pos`.`comm`, `doc_list_pos`.`id`, `doc_base_cnt`.`mesto`, `doc_base`.`cost_date`, `doc_base`.`pos_type`, `doc_base`.`id`
	FROM `doc_list_pos`
	LEFT JOIN `doc_base_cnt`  ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='$sklad'
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
	WHERE `doc_list_pos`.`doc`='$doc' AND `doc_list_pos`.`page`='0'");
	if(mysql_errno())	throw new MysqlException('Не удалось получить список товаров в документе');
	$i=0;
	$ii=1;
	$sum=0;

	while($nxt=mysql_fetch_row($res))
	{
		
		
		$cost=$nxt[6]*$nxt[4];
		$sum+=$cost;
		$cost_p=sprintf("%01.2f",$cost);
		$bcen=$dop_data['cena']?GetCostPos($nxt[12], $dop_data['cena']):$nxt[1];
		$dcen=sprintf("%01.2f",$nxt[6]);
		
		{
		// Дата цены 
			$dcc=strtotime($nxt[10]);
			$cc="";
			if($dcc>(time()-60*60*24*30*3)) $cc="class=f_green";
			else if($dcc>(time()-60*60*24*30*6)) $cc="class=f_purple";
			else if($dcc>(time()-60*60*24*30*9)) $cc="class=f_brown";
			else if($dcc>(time()-60*60*24*30*12)) $cc="class=f_more";
		}

		$cl='lin'.$i;
		if( ($nxt[2]<$nxt[4]) && (!$doc_data[6]) && ($doc_data[1]!=1) &&(!$nxt[11]) ) $cl.=' f_red';
		if($nxt[11])	$nxt[2]='Услуга';

		$tmpl->AddText("<tr class='$cl' align=right  oncontextmenu=\"return ShowContextMenu(event, '/docs.php?mode=srv&opt=menu&doc=0&pos=$nxt[12]')\"><td>");
		if(!$doc_data[6]) $tmpl->AddText("$ii
		<a href='' title='Удалить' onclick=\"EditThis('/doc.php?mode=srv&opt=del&doc=$doc&pos=$nxt[8]','poslist'); return false;\">
		<img src='/img/i_del.png' alt='Удалить'></a>
		<a href='' onclick=\"return ShowContextMenu(event, '/docs.php?mode=srv&opt=menu&doc=$doc&pos=$nxt[12]')\" title='Меню' accesskey=\"S\"><img src='img/i_menu.png' alt='Меню' border='0'></a>");
		else $tmpl->AddText("$ii");
		$tmpl->AddText("<td align=left>$nxt[0] / $nxt[3]<td $cc>$bcen<td id='cost$nxt[8]'>");
		
		if(!$doc_data[6]) $tmpl->AddText("<input type=text class='tedit $cl' id='val$nxt[8]c' value='$dcen' onblur=\"EditThisSave('/doc.php?mode=srv&opt=costs&doc=$doc&pos=$nxt[8]','poslist','val$nxt[8]c'); return false;\">");
		else $tmpl->AddText("$dcen");
		
		
		$tmpl->AddText("
		<td id='cnt$nxt[8]'>");
		if($nxt[5]=='') $nxt[5]='---';
		
		if(!$doc_data[6]) $tmpl->AddText("<input type=text class='tedit $cl' id='val$nxt[8]t' value='$nxt[4]' onblur=\"EditThisSave('/doc.php?mode=srv&opt=cnts&doc=$doc&pos=$nxt[8]','poslist','val$nxt[8]t'); return false;\">");
		else $tmpl->AddText("$nxt[4]");
		
		if(!$doc_data[6]) $tmpl->AddText("<td><input type=text class='tedit $cl' id='val$nxt[8]s' value='$cost_p' onblur=\"EditThisSave('/doc.php?mode=srv&opt=sts&doc=$doc&pos=$nxt[8]','poslist','val$nxt[8]s'); return false;\">");
		else $tmpl->AddText("<td>$cost_p");
		
		$tmpl->AddText("<td>$nxt[2]<td>$nxt[9]");
		
		if(@$CONFIG['site']['sn_enable'] && $doc_data[1]<3)
		{
				if($doc_data[1]==1)		$column='prix_list_pos';
				else if($doc_data[1]==2)	$column='rasx_list_pos';
				$rs=mysql_query("SELECT `doc_list_sn`.`id`, `doc_list_sn`.`num`, `doc_list_sn`.`rasx_list_pos` FROM `doc_list_sn` WHERE `$column`='$nxt[8]'");
				$sn_str='';
				while($nx=mysql_fetch_row($rs))
				{
					$sn_str.=$nx[1].', ';
				}
			
			if(!$doc_data[6])	$tmpl->AddText("<td onclick=\"ShowSnEditor($doc,$nxt[8]); return false;\" >$sn_str");
			else		$tmpl->AddText("<td>$sn_str");
		}
// 		if(!$doc_data[6])
// 		{
// 			if($nxt[7]=='') $nxt[7]='---';
// 			$tmpl->AddText("<a href='' onclick=\"EditThis('/doc.php?mode=srv&opt=com&doc=$doc&pos=$nxt[8]','com$nxt[8]'); return false;\" >$nxt[7]</a>");
// 		}
// 		else $tmpl->AddText("$nxt[7]");
		$i=1-$i;
		$ii++;
	}

	$ii--;
	$sum_p=sprintf("%01.2f",$sum);
	$tmpl->AddText("</table><p align=right id=sum>Итого: $ii позиций на сумму $sum_p руб.</p></div>");
}

function draw_group_level($doc,$level)
{
	$ret='';
	$res=mysql_query("SELECT `id`, `name`, `desc` FROM `doc_group` WHERE `pid`='$level' ORDER BY `id`");
	$i=0;
	$r='';
	if($level==0) $r='IsRoot';
	$cnt=mysql_num_rows($res);
	while($nxt=mysql_fetch_row($res))
	{
		if($nxt[0]==0) continue;
		$item="<a href='' title='$nxt[2]' onclick=\"EditThis('/doc.php?mode=srv&opt=sklad&doc=$doc&group=$nxt[0]','sklad'); return false;\" >$nxt[1]</a>";
		if($i>=($cnt-1)) $r.=" IsLast";
		$tmp=draw_group_level($doc,$nxt[0]); // рекурсия
		if($tmp)
			$ret.="<li class='Node ExpandClosed $r'><div class='Expand'></div><div class='Content'>$item</div><ul class='Container'>".$tmp.'</ul></li>';
        	else
        		$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
		$i++;
	}
	return $ret;
}


function doc_groups($doc)
{
	global $tmpl;
	$tmpl->AddText("<div onclick='tree_toggle(arguments[0])'>
	<div><a href='' title='Каталог' onclick=\"EditThis('/doc.php?mode=srv&opt=sklad&doc=$doc&group=0','sklad'); return false;\">Группы</a></div>
	<ul class='Container'>".draw_group_level($doc,0)."</ul></div>
	Или отбор:<input type=text id=sklsearch onkeydown=\"DelayedSave('/doc.php?mode=srv&opt=sklad&doc=$doc','sklad', 'sklsearch'); return true;\">");

}

function link_sklad($doc, $link, $text)
{
	global $tmpl;
	return "<a title='$link' href='' onclick=\"EditThis('/doc.php?mode=srv&opt=sklad&doc=$doc&$link','sklad'); return false;\" >$text</a> ";
}

function doc_sklad($doc, $group, $sklad=1)
{
	global $tmpl;
	get_docdata($doc);
	global $doc_data;
	global $dop_data;

	$s=rcv('s');
	if($s)
		ViewSkladS($doc, $group, $s, $doc_data[7]);
	else
		ViewSklad($doc, $group, $s, $doc_data[7]);
	return;
}

function ViewSklad($doc, $group, $s, $sklad)
{
	global $tmpl;
	
	$sql="SELECT `doc_base`.`id`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`, `doc_base`.`cost_date`,
	`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`,
	`doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt` , (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`)
	FROM `doc_base`
	LEFT JOIN `doc_base_cnt`  ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE `doc_base`.`group`='$group'
	ORDER BY `doc_base`.`name`";

	$lim=50;
	$page=rcv('p');
	$res=mysql_query($sql);
	$row=mysql_num_rows($res);
	$pagebar='';
	if($row>$lim)
	{
		$dop="group=$group";
		if($page<1) $page=1;
		if($page>1)
		{
			$i=$page-1;
			$pagebar.=link_sklad($doc, "$dop&p=$i","&lt;&lt;");
		}
		else $pagebar.='<span>&lt;&lt;</span>';
		$cp=$row/$lim;
		for($i=1;$i<($cp+1);$i++)
		{
			if($i==$page) $pagebar.=" <b>$i</b> ";
			else $pagebar.="<a href='' onclick=\"EditThis('/doc.php?mode=srv&amp;opt=sklad&amp;doc=$doc&amp;group=$group&amp;p=$i','sklad'); return false;\">$i</a> ";
		}
		if($page<$cp)
		{
			$i=$page+1;
			$pagebar.=link_sklad($doc, "$dop&p=$i","&gt;&gt;");
		}
		else $pagebar.='<span>&gt;&gt;</span>';
		$sl=($page-1)*$lim;

		mysql_data_seek($res,$sl);
	}

	if(mysql_num_rows($res))
	{
		$tmpl->AddText("$pagebar<br><table width=100% cellspacing=1 cellpadding=2><tr>
		<th>№<th>Наименование<th>Производитель<th>Цена, р.<th>Ликв.<th>Р.цена, р.<th>Аналог<th>Тип<th>d<th>D<th>B
		<th>Масса<th><img src='/img/i_lock.png' alt='В резерве'><th><img src='/img/i_alert.png' alt='Под заказ'><th><img src='/img/i_truck.png' alt='В пути'><th>Склад<th>Всего<th>Место");
		DrawSkladTable($res,$s,$doc,$lim);
		$tmpl->AddText("</table>$pagebar<br><a href='/docs.php?mode=srv&opt=ep&pos=0&g=$group'><img src='/img/i_add.png' alt=''> Добавить</a>");
	}
	else $tmpl->msg("В выбранной группе товаров не найдено!");
}

function ViewSkladS($doc, $group, $s, $sklad)
{
	global $tmpl;
	$sf=0;
	$tmpl->AddText("<b>Показаны наименования изо всех групп!</b><br>");
	$tmpl->AddText("<table width=100% cellspacing=1 cellpadding=2><tr>
	<th>№<th>Наименование<th>Производитель<th>Цена, р.<th>Ликв.<th>Р.цена, р.<th>Аналог<th>Тип<th>d<th>D<th>B
	<th>Масса<th><img src='/img/i_lock.png' alt='В резерве'><th><img src='/img/i_alert.png' alt='Под заказ'><th><img src='/img/i_truck.png' alt='В пути'><th>Склад<th>Всего<th>Место");
	
	$sql="SELECT `doc_base`.`id`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`, `doc_base`.`cost_date`,
	`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`,
	`doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`, (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`)";
		
	$sqla=$sql."FROM `doc_base`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE `doc_base`.`name` LIKE '$s%' ORDER BY `doc_base`.`name` LIMIT 100";
	$res=mysql_query($sqla);
	if($cnt=mysql_num_rows($res))
	{
		$tmpl->AddText("<tr class=lin0><th colspan=18 align=center>Поиск по названию, начинающемуся на $s: найдено $cnt");
		DrawSkladTable($res,$s,$doc);
		$sf=1;
	}
	
	$sqla=$sql."FROM `doc_base`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE `doc_base`.`name` LIKE '%$s%' AND `doc_base`.`name` NOT LIKE '$s%' ORDER BY `doc_base`.`name` LIMIT 30";
	$res=mysql_query($sqla);
	if($cnt=mysql_num_rows($res))
	{
		$tmpl->AddText("<tr class=lin0><th colspan=18 align=center>Поиск по названию, содержащему $s: найдено $cnt");
		DrawSkladTable($res,$s,$doc);
		$sf=1;
	}
	
	$sqla=$sql."FROM `doc_base`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE `doc_base_dop`.`analog` LIKE '%$s%' AND `doc_base`.`name` NOT LIKE '%$s%' ORDER BY `doc_base`.`name` LIMIT 30";
	$res=mysql_query($sqla);
	echo mysql_error();
	if($cnt=mysql_num_rows($res))
	{
		$tmpl->AddText("<tr class=lin0><th colspan=18 align=center>Поиск аналога, для $s: найдено $cnt");
		DrawSkladTable($res,$s,$doc);
		$sf=1;
	}
	
	$tmpl->AddText("</table><a href='/docs.php?mode=srv&opt=ep&pos=0&g=$group'><img src='/img/i_add.png' alt=''> Добавить</a>");
	
	if($sf==0)	$tmpl->msg("По данным критериям товаров не найдено!");
}

function DrawSkladTable($res,$s,$doc,$limit=0)
{
	global $tmpl, $dop_data;
	$i=0;
	$cnt=0;
	while($nxt=mysql_fetch_row($res))
	{
		$rezerv=DocRezerv($nxt[0],$doc);
		$pod_zakaz=DocPodZakaz($nxt[0],$doc);
		$v_puti=DocVPuti($nxt[0],$doc);
		
		if($rezerv)	$rezerv="<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=rezerv&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$rezerv</a>";
	
		if($pod_zakaz)	$pod_zakaz="<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$pod_zakaz</a>";

		if($v_puti)	$v_puti="<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=vputi&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=vputi&pos=$nxt[0]'>$v_puti</a>";
		{
			// Дата цены $nxt[5]
			$dcc=strtotime($nxt[6]);
			$cc="";
			if($dcc>(time()-60*60*24*30*3)) $cc="class=f_green";
			else if($dcc>(time()-60*60*24*30*6)) $cc="class=f_purple";
			else if($dcc>(time()-60*60*24*30*9)) $cc="class=f_brown";
			else if($dcc>(time()-60*60*24*30*12)) $cc="class=f_more";
		}
		$end=date("Y-m-d");
					
		$nxt[2]=SearchHilight($nxt[2],$s);
		$nxt[8]=SearchHilight($nxt[8],$s);	
		$i=1-$i;
		$cost_p=$dop_data['cena']?GetCostPos($nxt[0], $dop_data['cena']):$nxt[5];
		$cost_r=sprintf("%0.2f",$nxt[7]);
		
		$tmpl->AddText("<tr class='lin$i pointer' oncontextmenu=\"return ShowContextMenu(event, '/docs.php?mode=srv&opt=menu&doc=0&pos=$nxt[0]')\"
		ondblclick=\"EditThis('/doc.php?mode=srv&opt=pos&doc=$doc&pos=$nxt[0]','poslist'); return false;\">
		<td>$nxt[0]
		<a href='' onclick=\"return ShowContextMenu(event, '/docs.php?mode=srv&opt=menu&doc=0&pos=$nxt[0]')\" title='Меню' accesskey=\"S\"><img src='img/i_menu.png' alt='Меню' border='0'></a>
		<td align=left>$nxt[2]<td>$nxt[3]<td $cc>$cost_p<td>$nxt[4]%<td>$cost_r<td>$nxt[8]<td>$nxt[9]<td>$nxt[10]<td>$nxt[11]<td>$nxt[12]<td>$nxt[13]<td>$rezerv<td>$pod_zakaz<td>$v_puti<td>$nxt[15]<td>$nxt[16]<td>$nxt[14]");
		$cnt++;
		if( $limit && ( $cnt>= $limit))	break;
	}	
}

// Проверка, не уходило ли когда-либо количество какого-либо товара в минус
// Используется при отмене документов, уменьшающих остатки на складе, напр. реализаций и перемещений
function CheckMinus($pos, $sklad)
{
	$cnt=0;
	$sql_add=$to_date?" AND `doc_list`.`date`<'$to_date'":'';
	$res=mysql_query("SELECT `doc_list_pos`.`cnt`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`id` FROM `doc_list_pos`
	LEFT JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
	WHERE  `doc_list`.`ok`>'0' AND `doc_list_pos`.`tovar`='$pos' $sql_add
	ORDER BY `doc_list`.`date`");
	if(mysql_errno())	throw new MysqlExceprion("Не удалось запросить список документов с товаром ID:$pos при проверке на отрицательные остатки");
	while($nxt=mysql_fetch_row($res))
	{
		if($nxt[1]==1)
		{
			if($nxt[2]==$sklad)	$cnt+=$nxt[0];
		}
		else if($nxt[1]==2)
		{
			if($nxt[2]==$sklad)	$cnt-=$nxt[0];
		}
		else if($nxt[1]==8)
		{
			if($nxt[2]==$sklad)	$cnt-=$nxt[0];
			else
			{
				$rr=mysql_query("SELECT `value` FROM `doc_dopdata` WHERE `doc`='$nxt[3]' AND `param`='na_sklad'");
				if(mysql_errno())	throw new MysqlExceprion("Не удалось запросить склад назначения в перемещении $nxt[3] при проверке на отрицательные остатки");
				$nasklad=mysql_result($rr,0,0);
				if(!$nasklad)		throw new Exceprion("Не удалось получить склад назначения в перемещении $nxt[3] при проверке на отрицательные остатки");
				if($nasklad==$sklad)	$cnt+=$nxt[0];
			}
		}
		else if($nxt[1]==17)
		{
			if($nxt[2]==$sklad)	$cnt-=$nxt[0];
			else
			{
				$rr=mysql_query("SELECT `value` FROM `doc_dopdata` WHERE `doc`='$nxt[3]' AND `param`='na_sklad'");
				if(mysql_errno())	throw new MysqlExceprion("Не удалось запросить склад назначения в перемещении $nxt[3] при проверке на отрицательные остатки");
				$nasklad=mysql_result($rr,0,0);
				if(!$nasklad)		throw new Exceprion("Не удалось получить склад назначения в перемещении $nxt[3] при проверке на отрицательные остатки");
				if($nasklad==$sklad)	$cnt+=$nxt[0];
			}
		}
		if($cnt<0) break;
	}
	mysql_free_result($res);
	return $cnt;
}


?>