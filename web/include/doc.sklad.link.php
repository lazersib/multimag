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

include_once('include/doc.poseditor.php');


/// Работа со связанными товарами.
/// В работе использует javascript файл js/link_poslist.js
class LinkPosList extends PosEditor
{
var $linked_pos=0;		///< ID наименования, для которого формируется список связей

/// Конструктор.
/// @param pos_id ID наименования, для которого требуется просмотр/редактирование списка связанных наименований
function __construct($pos_id) 
{
	$this->linked_pos=$pos_id;
}

/// Установить ID связанного товара
/// @param pos_id ID наименования, для которого требуется просмотр/редактирование списка связанных наименований
public function SetLinkedPos($pos_id)
{
	$this->linked_pos=$pos_id;
}

/// Показать редактор.
/// @return HTML код редактора
public function Show($param='')
{
	global $CONFIG;
	/// TODO: возможность отключения редактирования в зависимости от статуса документа, настройка отображаемых столбцов из конфига. Не забыть про серийные номера.
	/// Возможность отключения строки быстрого ввода
	/// В итоге - сделать базовый класс, от которого наследуется редактор документов, редактор комплектующих, итп.
	$ret="
	<script src='/js/link_poslist.js' type='text/javascript'></script>
	<link href='/css/poseditor.css' rel='stylesheet' type='text/css' media='screen'>
	<script src='/css/jquery/jquery.alerts.js' type='text/javascript'></script>
	<link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen' />

	<table width='100%' id='poslist'><thead><tr>
	<th width='60px' align='left'>№</th>";
	if($CONFIG['poseditor']['vc']>0)
		$ret.="<th width='90px' align='left' title='Код изготовителя'><div class='order_button' id='pl_order_vc'></div> Код</th>";
	$ret.="<th><div class='order_button' id='pl_order_name'></div> Наименование</th>
	<th width='90px' class='hl'><div class='order_button' id='pl_order_cost'></div> Цена</th>
	<th width='60px' title='Остаток товара на складе'>Остаток</th>
	</tr>
	</thead>
	<tfoot>
	<tr id='pladd'>
	<td><input type='text' id='pos_id' autocomplete='off' tabindex='1'></td>";
	if($CONFIG['poseditor']['vc']>0)
		$ret.="<td><input type='text' id='pos_vc' autocomplete='off' tabindex='2'></td>";
	$ret.="<td><input type='text' id='pos_name' autocomplete='off' tabindex='3'></td>
	<td id='pos_cost'></td>
	<td id='pos_sklad_cnt'></td>
	</tr>
	</tfoot>
	<tbody>
	<tr><td colspan='9' style='text-align: center;'><img src='/img/icon_load.gif' alt='Загрузка...'>
	</tbody>
	</table>
	<p align='right' id='sum'></p>";
	
	$ret.="
	<table id='sklad_view'>
	<tr><td id='groups_list' width='200' valign='top' class='lin0'>";
	$ret.=$this->getGroupsTree();
	$ret.="</td><td valign='top' class='lin1'>	
	<table width='100%' cellspacing='1' cellpadding='2'>
	<tr><thead>
	<th>№";
	if($CONFIG['poseditor']['vc']>0)	$ret.="<th>Код";
	$ret.="<th>Наименование<th>Марка<th>Цена, р.<th>Ликв.<th>Р.цена, р.<th>Аналог";
	if($CONFIG['poseditor']['tdb']>0)	$ret.="<th>Тип<th>d<th>D<th>B<th>Масса";
	if($CONFIG['poseditor']['rto']>0)	$ret.="<th><img src='/img/i_lock.png' alt='В резерве'><th><img src='/img/i_alert.png' alt='Предложений'><th><img src='/img/i_truck.png' alt='В пути'>";
	$ret.="<th>Склад<th>Всего<th>Место
	</thead>
	<tbody id='sklad_list'>
	</tbody>
	</table>
	</td></tr>
	</table>";
	if(!@$CONFIG['poseditor']['need_dialog'])	$CONFIG['poseditor']['need_dialog']=0;
	else						$CONFIG['poseditor']['need_dialog']=1;
	$ret.=@"<script type=\"text/javascript\">
	var poslist=LinkPosListInit('/docs.php?l=sklad&mode=srv&opt=ep&param=l&pos={$this->linked_pos}','{$this->editable}')
	poslist.show_column['vc']='{$CONFIG['poseditor']['vc']}'

	var skladview=document.getElementById('sklad_view')
	skladview.show_column['vc']='{$CONFIG['poseditor']['vc']}'
	skladview.show_column['tdb']='{$CONFIG['poseditor']['tdb']}'
	skladview.show_column['rto']='{$CONFIG['poseditor']['rto']}'
	
	skladlist=document.getElementById('sklad_list').needDialog='{$CONFIG['poseditor']['need_dialog']}';
	</script>";	
	
	return $ret;
		
}

/// Получить список наименований, связанных с выбранным наименованием.
/// @return json-строка с данными о наименованиях
function GetAllContent()
{
	$res=mysql_query("SELECT `doc_base_links`.`id` AS `line_id`, `doc_base`.`id` AS `pos_id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`cost`, `doc_base`.`proizv`, `doc_base`.`id`, (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `sklad_cnt`
	FROM `doc_base_links`
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_base_links`.`pos2_id`
	WHERE `doc_base_links`.`pos1_id`='{$this->linked_pos}'");
	if(mysql_errno())	throw new MysqlException("Ошибка получения списка связанных наименований");
	$ret='';
	while($nxt=mysql_fetch_assoc($res))
	{
		$nxt['cost']=sprintf("%0.2f",$nxt['cost']);
		if($ret) $ret.=', ';
		
		$ret.="{line_id: '{$nxt['line_id']}', pos_id: '{$nxt['pos_id']}', vc: '{$nxt['vc']}', name: '{$nxt['name']} - {$nxt['proizv']}', cost: '{$nxt['cost']}', sklad_cnt: '{$nxt['sklad_cnt']}'";

		$ret.="}";
	}
	return $ret;
}

/// Получить список номенклатуры заданной группы
function GetSkladList($group)
{
	$ret='';
	$sql="SELECT `doc_base`.`id`,`doc_base`.`vc`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`, `doc_base`.`cost_date`,
	`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `allcnt`
	FROM `doc_base`
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE `doc_base`.`group`='$group'
	ORDER BY `doc_base`.`name`";
	$res=mysql_query($sql);
	if(mysql_errno())	throw new MysqlException("Не удалось получить списока номенклатуры группы $group, склада {$this->sklad_id}");

	return $this->FormatResult($res);
}

/// Получить список номенклатуры, содержащей в названии заданную строку
function SearchSkladList($s)
{
	$ret='';
	$sql="SELECT `doc_base`.`id`,`doc_base`.`vc`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`, `doc_base`.`cost_date`,
	`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`,
	`doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`, (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `allcnt`";
		
	$sqla=$sql."FROM `doc_base`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE `doc_base`.`name` LIKE '$s%' OR `doc_base`.`vc` LIKE '$s%' ORDER BY `doc_base`.`name` LIMIT 200";
	$res=mysql_query($sqla);
	if(mysql_errno())	throw new MysqlException("Ошибка поиска");
	if($cnt=mysql_num_rows($res))
	{
		if($ret!='')	$ret.=', ';
		$ret.="{id: 'header', name: 'Поиск по названию, начинающемуся на $s - $cnt наименований найдено'}";
		$ret=$this->FormatResult($res,$ret);
	}
	$sqla=$sql."FROM `doc_base`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE (`doc_base`.`name` LIKE '%$s%' OR `doc_base`.`vc` LIKE '%$s%') AND `doc_base`.`name` NOT LIKE '$s%' AND `doc_base`.`vc` NOT LIKE '$s%' ORDER BY `doc_base`.`name` LIMIT 100";
	$res=mysql_query($sqla);
	if(mysql_errno())	throw new MysqlException("Ошибка поиска");
	if($cnt=mysql_num_rows($res))
	{
		if($ret!='')	$ret.=', ';
		$ret.="{id: 'header', name: 'Поиск по названию, содержащему $s - $cnt наименований найдено'}";
		$ret=$this->FormatResult($res,$ret);
	}
	$sqla=$sql."FROM `doc_base`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE `doc_base_dop`.`analog` LIKE '%$s%' AND `doc_base`.`name` NOT LIKE '%$s%' AND `doc_base`.`vc` NOT LIKE '%$s%' ORDER BY `doc_base`.`name` LIMIT 100";
	$res=mysql_query($sqla);
	if(mysql_errno())	throw new MysqlException("Ошибка поиска");
	if($cnt=mysql_num_rows($res))
	{
		if($ret!='')	$ret.=', ';
		$ret.="{id: 'header', name: 'Поиск по аналогу($s) - $cnt наименований найдено'}";
		$ret=$this->FormatResult($res,$ret);
	}
	
	return $ret;
}

/// Добавляет указанную складскую позицию в список
function AddPos($pos)
{
	$ret='';
	if(!$pos)	throw new Exception("ID позиции не задан!");
		
	$res=mysql_query("SELECT `id`, `pos1_id`, `pos2_id` FROM `doc_base_links`
	WHERE (`pos1_id`='{$this->linked_pos}' AND `pos2_id`='$pos') OR (`pos1_id`='{$this->linked_pos}' AND `pos2_id`='$pos')");
	if(mysql_errno())	throw new MysqlException("Не удалось выбрать строку связанного товвара!");
	if(mysql_num_rows($res)==0)
	{
		mysql_query("INSERT INTO `doc_base_links` (`pos1_id`, `pos2_id`) VALUES ('{$this->linked_pos}','$pos')");
		if(mysql_errno())	throw new MysqlException("Не удалось вставить строку в документ!");
		$pos_line=mysql_insert_id();
		doc_log("UPDATE","add link: pos:$pos",'pos',$this->linked_pos);
		$add=1;

		$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto`
		FROM `doc_base_links`
		INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_base_links`.`pos2_id`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
		WHERE `doc_list_pos`.`id`='$pos_line'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить строку документа");
		$line=mysql_fetch_assoc($res);
		$cost=$this->cost_id?GetCostPos($line['id'], $this->cost_id):$line['cost'];
		$ret="{ response: '1', add: { line_id: '$pos_line', pos_id: '{$line['id']}', vc: '{$line['vc']}', name: '{$line['name']} - {$line['proizv']}', cnt: '{$line['cnt']}', scost: '$cost', cost: '{$line['cost']}', sklad_cnt: '{$line['sklad_cnt']}', mesto: '{$line['mesto']}', gtd: '' }, sum: '$doc_sum' }";
	}

	return $ret;
}


/// Формирует json строку с данными о наименованиях на основании результата выполнения sql запроса
/// @param res Результат sql запроса - выборка наименований по заданным критериям
/// @param ret json строка, к которой надо добавить данные
protected function FormatResult($res, $ret='')
{
	if(mysql_num_rows($res))
	{
		while($nxt=mysql_fetch_assoc($res))
		{
			$dcc=strtotime($nxt['cost_date']);
			$cc="";
			if($dcc>(time()-60*60*24*30*3)) $cc="c1";
			else if($dcc>(time()-60*60*24*30*6)) $cc="c2";
			else if($dcc>(time()-60*60*24*30*9)) $cc="c3";
			else if($dcc>(time()-60*60*24*30*12)) $cc="c4";
			$reserve=DocRezerv($nxt['id'],0);
			$offer=DocPodZakaz($nxt['id'],0);
			$transit=DocVPuti($nxt['id'],0);
			$cost=$nxt['cost'];
			$rcost=sprintf("%0.2f",$nxt['koncost']);
			if($ret!='')	$ret.=', ';
			$ret.=@"{ id: '{$nxt['id']}', name: '{$nxt['name']}', vc: '{$nxt['vc']}', vendor: '{$nxt['proizv']}', liquidity: '{$nxt['likvid']}', cost: '$cost', cost_class: '$cc', rcost: '$rcost', analog: '{$nxt['analog']}', type: '{$nxt['type']}', d_int: '{$nxt['d_int']}', d_ext: '{$nxt['d_ext']}', size: '{$nxt['size']}', mass: '{$nxt['mass']}', place: '{$nxt['mesto']}', cnt: '{$nxt['cnt']}', allcnt: '{$nxt['allcnt']}', reserve: '$reserve', offer: '$offer', transit: '$transit' }";
		}	
	}
	return $ret;
}

};



function link_poslist($pos)
{
	global $tmpl;
	global $dop_data;

	$tmpl->AddText("<div id='poslist'><table width='100%' cellspacing='1' cellpadding='2'>
	<tr><th align='left'>№<th>Наименование<th>Цена");
	$res=mysql_query("SELECT `doc_base_kompl`.`id`, `doc_base`.`name`, `doc_base`.`cost`, `doc_base`.`proizv`, `doc_base`.`id`
	FROM `doc_base_link`
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_base_link`.`pos2_id`
	WHERE `doc_base_link`.`pos1_id`='$pos'");

	$i=0;
	$ii=1;
	$sum=0;

	while($nxt=mysql_fetch_array($res))
	{
		$nxt['cost']=GetInCost($nxt[0]);
		$sumline=$nxt['cost']*$nxt['cnt'];
		$sum+=$sumline;
		$sumline_p=sprintf("%01.2f",$sumline);
		$cost_p=sprintf("%01.2f",$nxt['cost']);

		$cl='lin'.$i;

		$tmpl->AddText("<tr class='$cl'  align=right><td>$ii
		<a href='' title='Удалить' onclick=\"EditThis('/docs.php?l=sklad&mode=srv&opt=ep&param=l&plm=d&pos=$pos&vpos=$nxt[0]','poslist'); return false;\"><img src='/img/i_del.png' alt='Удалить'></a>
		<a href='' onclick=\"ShowContextMenu('/docs.php?mode=srv&opt=menu&doc=$doc&pos=$nxt[5]'); return false;\" title='Меню' accesskey=\"S\"><img src='img/i_menu.png' alt='Меню' border='0'></a>
		<td align=left>{$nxt['name']} / {$nxt['proizv']}<td>$cost_p
		<td><input type=text class='tedit $cl' id='val$nxt[0]t' value='{$nxt['cnt']}' onblur=\"EditThisSave('/docs.php?l=sklad&mode=srv&opt=ep&param=l&pos=1&plm=cc&pos=$pos&vpos=$nxt[0]','poslist','val$nxt[0]t'); return false;\">
		<td>$sumline_p");
		$i=1-$i;
		$ii++;
	}

	$ii--;
	$sum_p=sprintf("%01.2f",$sum);
	$tmpl->AddText("</table><p align=right id=sum>Итого: $ii позиций на сумму $sum_p руб.</p></div>");
}

function link_draw_group_level($pos,$level)
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
		$item="<a href='' title='$nxt[2]' onclick=\"EditThis('/docs.php?l=sklad&mode=srv&opt=ep&param=l&pos=1&plm=sg&group=$nxt[0]&pos=$pos','sklad'); return false;\" >$nxt[1]</a>";
		if($i>=($cnt-1)) $r.=" IsLast";
		$tmp=link_draw_group_level($pos,$nxt[0]); // рекурсия
		if($tmp)
			$ret.="<li class='Node ExpandClosed $r'><div class='Expand'></div><div class='Content'>$item</div><ul class='Container'>".$tmp.'</ul></li>';
        	else
        		$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
		$i++;
	}
	return $ret;
}


function link_groups($pos)
{
	global $tmpl;
	$tmpl->AddText("<div onclick='tree_toggle(arguments[0])'>
	<div><a href='' title='$nxt[2]' onclick=\"EditThis('/docs.php?l=sklad&mode=srv&opt=ep&param=l&plm=sg&group=0&pos=$pos','sklad'); return false;\">Группы</a></div>
	<ul class='Container'>".link_draw_group_level($pos,0)."</ul></div>
	Или отбор:<input type=text id=sklsearch onkeydown=\"DelayedSave('/docs.php?mode=srv&opt=ep&param=l&pos=$pos','sklad', 'sklsearch'); return true;\">");

}

// function link_link_sklad($pos, $link, $text)
// {
// 	global $tmpl;
// 	$tmpl->AddText("<a title='$link' href='' onclick=\"EditThis('/doc.s.sklad.php?mode=srv&opt=sklad&vpos=$vpos&$link','sklad'); return false;\" >$text</a> ");
// }

// function link_sklad($pos, $group, $sklad=1)
// {
// 	global $tmpl;
// 
// 	$s=rcv('s');
// 	if($s)
// 		link_ViewSkladS($pos, $group, $s);
// 	else
// 		link_ViewSklad($pos, $group);
// 	return;
// }

function link_ViewSklad($pos, $group)
{
	global $tmpl;
	
	$sql="SELECT `doc_base`.`id`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`, `doc_base`.`cost_date`,
	`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`,
	`doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt` , (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`)
	FROM `doc_base`
	LEFT JOIN `doc_base_cnt`  ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='0'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE `doc_base`.`group`='$group'
	ORDER BY `doc_base`.`name`";

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
			link_link_sklad($doc, "$dop&p=$i","&lt;&lt;");
		}
		$cp=$row/$lim;
		for($i=1;$i<($cp+1);$i++)
		{
			if($i==$page) $tmpl->AddText(" <b>$i</b> ");
			else $tmpl->AddText("<a href='' onclick=\"EditThis('docs.php?l=sklad&mode=srv&opt=ep&param=k&pos=1&plm=sg&group=$group&pos=$pos&amp;p=$i','sklad'); return false;\">$i</a> ");
		}
		if($page<$cp)
		{
			$i=$page+1;
			link_sklad($doc, "$dop&p=$i","&gt;&gt;");
		}
		$tmpl->AddText("<br>");
		$sl=($page-1)*$lim;

		$res=mysql_query("$sql LIMIT $sl,$lim");
	}

	if(mysql_num_rows($res))
	{
		$tmpl->AddText("<table width=100% cellspacing=1 cellpadding=2><tr>
		<th>№<th>Наименование<th>Производитель<th>Цена, р.<th>Ликв.<th>Р.цена, р.<th>Аналог<th>Тип<th>d<th>D<th>B
		<th>Масса<th><img src='/img/i_lock.png' alt='В резерве'><th><img src='/img/i_alert.png' alt='Под заказ'><th><img src='/img/i_truck.png' alt='В пути'><th>Склад<th>Всего<th>Место");
		link_DrawSkladTable($res,$s,$pos);
		$tmpl->AddText("</table><a href='/docs.php?mode=srv&opt=ep&pos=0&g=$group'><img src='/img/i_add.png' alt=''> Добавить</a>");
	}
	else $tmpl->msg("В выбранной группе товаров не найдено!");
}

function link_ViewSkladS($doc, $group, $s)
{
	global $tmpl;
	$sf=0;
	$tmpl->ajax=1;
	$tmpl->SetText("<b>Показаны наименования изо всех групп!</b><br>");
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
		link_DrawSkladTable($res,$s,$doc);
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
		link_DrawSkladTable($res,$s,$doc);
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
		link_DrawSkladTable($res,$s,$doc);
		$sf=1;
	}
	
	$tmpl->AddText("</table><a href='/docs.php?mode=srv&opt=ep&pos=0&g=$group'><img src='/img/i_add.png' alt=''> Добавить</a>");
	
	if($sf==0)	$tmpl->msg("По данным критериям товаров не найдено!");
}

function link_DrawSkladTable($res,$s,$pos)
{
	global $tmpl, $dop_data;
	$i=0;
	while($nxt=mysql_fetch_row($res))
	{
		$rezerv=DocRezerv($nxt[0],$doc);
		$pod_zakaz=DocPodZakaz($nxt[0],$doc);
		$v_puti=DocVPuti($nxt[0],$doc);
		
		if($rezerv)	$rezerv="<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=rezerv&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$rezerv</a>";
	
		if($pod_zakaz)	$pod_zakaz="<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$pod_zakaz</a>";

		if($v_puti)	$v_puti="<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=vputi&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$v_puti</a>";
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
		//docs.php?l=sklad&mode=srv&opt=ep&param=k&pos=1&plm=sg&group=0&vpos=$vpos
		//doc.php?mode=srv&opt=pos&doc=$doc&pos=$nxt[0]
		$tmpl->AddText("<tr class='lin$i pointer'
		ondblclick=\"EditThis('/docs.php?l=sklad&mode=srv&opt=ep&param=k&pos=1&plm=pos&pos=$pos&vpos=$nxt[0]','poslist'); return false;\">
		<td>$nxt[0]
		<a href='' onclick=\"ShowContextMenu('/docs.php?mode=srv&opt=menu&doc=0&pos=$nxt[0]'); return false;\" title='Меню' accesskey=\"S\"><img src='img/i_menu.png' alt='Меню' border='0'></a>
		<td align=left>$nxt[2]<td>$nxt[3]<td $cc>$cost_p<td>$nxt[4]%<td>$cost_r<td>$nxt[8]<td>$nxt[9]<td>$nxt[10]<td>$nxt[11]<td>$nxt[12]<td>$nxt[13]<td>$rezerv<td>$pod_zakaz<td>$v_puti<td>$nxt[15]<td>$nxt[16]<td>$nxt[14]");
	}	
}



?>