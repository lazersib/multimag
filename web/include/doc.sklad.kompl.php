<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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

// Это временное решение для работы с комплектацией товаров

function kompl_poslist($pos) {
	global $tmpl, $db;
	settype($pos, 'int');
	$tmpl->addContent("<div id='poslist'><table width='100%' cellspacing='1' cellpadding='2' class='list'>
	<tr><th align='left'>№<th>Код<th>Наименование<th>Цена<th width='80px'>Кол-во<th>Стоимость");
	$res = $db->query("SELECT `doc_base_kompl`.`id`, `doc_base`.`name`, `doc_base`.`cost`, `doc_base_kompl`.`cnt`, `doc_base`.`proizv`, `doc_base`.`id`,
		`doc_base`.`vc`
		FROM `doc_base_kompl`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_base_kompl`.`kompl_id`
		WHERE `doc_base_kompl`.`pos_id`='$pos'");
	$i = 0;
	$ii = 1;
	$sum = 0;

	while ($nxt = $res->fetch_array()) {
		$nxt['cost'] = getInCost($nxt[5], 0, true);
		$sumline = $nxt['cost'] * $nxt['cnt'];
		$sum+=$sumline;
		$sumline_p = sprintf("%01.2f", $sumline);
		$cost_p = sprintf("%01.2f", $nxt['cost']);

		$cl = 'lin' . $i;

		$tmpl->addContent("<tr class='$cl'  align=right><td>$ii
		<a href='' title='Удалить' onclick=\"EditThis('/docs.php?l=sklad&mode=srv&opt=ep&param=k&plm=d&pos=$pos&vpos=$nxt[0]','poslist'); return false;\"><img src='/img/i_del.png' alt='Удалить'></a>
		<a href='' onclick=\"ShowContextMenu('/docs.php?mode=srv&opt=menu&pos=$nxt[5]'); return false;\" title='Меню' accesskey=\"S\"><img src='img/i_menu.png' alt='Меню' border='0'></a><td>".html_out($nxt['vc'])."</td>
		<td align=left>".html_out($nxt['name'])." / ".html_out($nxt['proizv'])."<td>$cost_p
		<td><input type=text class='tedit $cl' id='val$nxt[0]t' value='{$nxt['cnt']}' onblur=\"EditThisSave('/docs.php?l=sklad&mode=srv&opt=ep&param=k&pos=1&plm=cc&pos=$pos&vpos=$nxt[0]','poslist','val$nxt[0]t'); return false;\">
		<td>$sumline_p");
		$i = 1 - $i;
		$ii++;
	}

	$ii--;
	$sum_p = sprintf("%01.2f", $sum);
	$tmpl->addContent("</table><p align=right id=sum>Итого: $ii позиций на сумму $sum_p руб.</p></div>");
}

function kompl_draw_group_level($pos, $level) {
	global $db;
	$ret = '';
	$res = $db->query("SELECT `id`, `name` FROM `doc_group` WHERE `pid`='$level' ORDER BY `id`");
	$i = 0;
	$r = '';
	if ($level == 0)	$r = 'IsRoot';
	while ($nxt = $res->fetch_row()) {
		if ($nxt[0] == 0)	continue;
		$item = "<a href='#' onclick=\"EditThis('/docs.php?l=sklad&mode=srv&opt=ep&param=k&pos=1&plm=sg&group=$nxt[0]&pos=$pos','sklad'); return false;\" >"
			.html_out($nxt[1])."</a>";
		if ($i >= ($res->num_rows - 1))	$r.=" IsLast";
		$tmp = kompl_draw_group_level($pos, $nxt[0]); // рекурсия
		if ($tmp)	$ret.="<li class='Node ExpandClosed $r'><div class='Expand'></div><div class='Content'>$item</div><ul class='Container'>" . $tmp . '</ul></li>';
		else		$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
		$i++;
	}
	return $ret;
}

function kompl_groups($pos) {
	global $tmpl;
	$tmpl->addContent("<div onclick='tree_toggle(arguments[0])'>
	<div><a href='#' onclick=\"EditThis('/docs.php?l=sklad&mode=srv&opt=ep&param=k&plm=sg&group=0&pos=$pos','sklad'); return false;\">Группы</a></div>
	<ul class='Container'>" . kompl_draw_group_level($pos, 0) . "</ul></div>
	Или отбор:<input type=text id=sklsearch onkeydown=\"DelayedSave('/docs.php?mode=srv&opt=ep&param=k&pos=$pos','sklad', 'sklsearch'); return true;\">");
}

function kompl_sklad($pos, $group, $sklad = 1) {
	global $tmpl;
	$s = request('s');
	if ($s)	kompl_ViewSkladS($pos, $group, $s);
	else	kompl_ViewSklad($pos, $group);
}

function kompl_ViewSklad($pos, $group) {
	global $tmpl, $CONFIG, $db;
	settype($group, 'int');
	settype($pos, 'int');
	switch (@$CONFIG['doc']['sklad_default_order']) {
		case 'vc': $order = '`doc_base`.`vc`';
			break;
		case 'cost': $order = '`doc_base`.`cost`';
			break;
		default: $order = '`doc_base`.`name`';
	}
	$sql = "SELECT `doc_base`.`id`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`, `doc_base`.`cost_date`,
	`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`,
	`doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt` , (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`), `doc_base`.`vc`
	FROM `doc_base`
	LEFT JOIN `doc_base_cnt`  ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='0'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE `doc_base`.`group`='$group'
	ORDER BY $order";

	$lim = 50;
	$page = rcvint('p');
	$res = $db->query($sql);
	$row = $res->num_rows;
	if ($row > $lim) {
		if ($page < 1)	$page = 1;
		if ($page > 1) {
			$i = $page - 1;
			$tmpl->addContent("<a href='' onclick=\"EditThis('docs.php?l=sklad&mode=srv&opt=ep&param=k&pos=1&plm=sg&group=$group&pos=$pos&amp;p=$i','sklad'); return false;\">&lt;&lt;</a> ");
		}
		$cp = $row / $lim;
		for ($i = 1; $i < ($cp + 1); $i++) {
			if ($i == $page)$tmpl->addContent(" <b>$i</b> ");
			else		$tmpl->addContent("<a href='' onclick=\"EditThis('docs.php?l=sklad&mode=srv&opt=ep&param=k&pos=1&plm=sg&group=$group&pos=$pos&amp;p=$i','sklad'); return false;\">$i</a> ");
		}
		if ($page < $cp) {
			$i = $page + 1;
			$tmpl->addContent("<a href='' onclick=\"EditThis('docs.php?l=sklad&mode=srv&opt=ep&param=k&pos=1&plm=sg&group=$group&pos=$pos&amp;p=$i','sklad'); return false;\">&gt;&gt;</a> ");
		}
		$tmpl->addContent("<br>");
		$sl = ($page - 1) * $lim;
		$res->data_seek($sl);
	}

	if ($row) {
		$tmpl->addContent("<table width=100% cellspacing=1 cellpadding=2><tr>
		<th>№</th><th>Код</th><th>Наименование</th><th>Производитель</th><th>Цена, р.</th><th>Ликв.</th><th>Р.цена, р.</th><th>Аналог</th>
		<th>Тип</th><th>d</th><th>D</th><th>B</th><th>Масса</th><th><img src='/img/i_lock.png' alt='В резерве'></th>
		<th><img src='/img/i_alert.png' alt='Под заказ'></th><th><img src='/img/i_truck.png' alt='В пути'></th><th>Склад</th><th>Всего</th>
		<th>Место</th></tr>");
		kompl_DrawSkladTable($res, '', $pos);
		$tmpl->addContent("</table><a href='/docs.php?mode=srv&opt=ep&pos=0&g=$group'><img src='/img/i_add.png' alt=''> Добавить</a>");
	}
	else	$tmpl->msg("В выбранной группе товаров не найдено!");
}

function kompl_ViewSkladS($pos, $group, $s) {
	global $tmpl, $CONFIG, $db;
	$sf = 0;
	$tmpl->ajax = 1;
	$tmpl->setContent("<b>Показаны наименования изо всех групп!</b><br>");
	$tmpl->addContent("<table width='100%' cellspacing='1' cellpadding='2' class='list'><tr>
	<th>№<th>Код<th>Наименование<th>Производитель<th>Цена, р.<th>Ликв.<th>Р.цена, р.<th>Аналог<th>Тип<th>d<th>D<th>B
	<th>Масса<th><img src='/img/i_lock.png' alt='В резерве'><th><img src='/img/i_alert.png' alt='Под заказ'><th><img src='/img/i_truck.png' alt='В пути'><th>Склад<th>Всего<th>Место");
	switch (@$CONFIG['doc']['sklad_default_order']) {
		case 'vc': $order = '`doc_base`.`vc`';
			break;
		case 'cost': $order = '`doc_base`.`cost`';
			break;
		default: $order = '`doc_base`.`name`';
	}
	$sql = "SELECT `doc_base`.`id`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`, `doc_base`.`cost_date`,
		`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`,
		`doc_base_dop`.`mass`, `doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`,
		(SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`), `doc_base`.`vc`";
	$s_sql = $db->real_escape_string($s);
	$sqla = $sql . "FROM `doc_base`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='0'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE `doc_base`.`name` LIKE '$s_sql%' OR `doc_base`.`vc` LIKE '$s_sql%' ORDER BY $order LIMIT 100";
	$res = $db->query($sqla);
	if ($res->num_rows) {
		$tmpl->addContent("<tr class=lin0><th colspan=19 align=center>Поиск по названию, начинающемуся на ".html_out($s).": найдено {$res->num_rows}");
		kompl_DrawSkladTable($res, $s, $pos);
		$sf = 1;
	}

	$sqla = $sql . "FROM `doc_base`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='0'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE (`doc_base`.`name` LIKE '%$s_sql%' OR `doc_base`.`vc` LIKE '%$s_sql%') AND `doc_base`.`name` NOT LIKE '$s_sql%' AND `doc_base`.`vc` NOT LIKE '$s_sql%' ORDER BY $order LIMIT 30";
	$res = $db->query($sqla);
	if ($res->num_rows) {
		$tmpl->addContent("<tr class=lin0><th colspan=19 align=center>Поиск по названию, содержащему ".html_out($s).": найдено {$res->num_rows}");
		kompl_DrawSkladTable($res, $s, $pos);
		$sf = 1;
	}

	$sqla = $sql . "FROM `doc_base`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='0'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE `doc_base_dop`.`analog` LIKE '%$s_sql%' AND `doc_base`.`name` NOT LIKE '%$s_sql%' AND `doc_base`.`vc` NOT LIKE '%$s%' ORDER BY $order LIMIT 30";
	$res = $db->query($sqla);
	if ($res->num_rows) {
		$tmpl->addContent("<tr class=lin0><th colspan=19 align=center>Поиск аналога, для ".html_out($s).": найдено {$res->num_rows}");
		kompl_DrawSkladTable($res, $s, $pos);
		$sf = 1;
	}

	$tmpl->addContent("</table><a href='/docs.php?mode=srv&opt=ep&pos=0&g=$group'><img src='/img/i_add.png' alt=''> Добавить</a>");

	if ($sf == 0)	$tmpl->msg("По данным критериям товаров не найдено!");
}

function kompl_DrawSkladTable($res, $s, $pos) {
	global $tmpl, $dop_data;
	$i = 0;
	while ($nxt = $res->fetch_row()) {
		$rezerv = DocRezerv($nxt[0], 0);
		$pod_zakaz = DocPodZakaz($nxt[0], 0);
		$v_puti = DocVPuti($nxt[0], 0);

		if ($rezerv)
			$rezerv = "<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=rezerv&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$rezerv</a>";

		if ($pod_zakaz)
			$pod_zakaz = "<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$pod_zakaz</a>";

		if ($v_puti)
			$v_puti = "<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=vputi&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$v_puti</a>";
		// Дата цены $nxt[5]
		$dcc = strtotime($nxt[6]);
		$cc = "";
		if ($dcc > (time() - 60 * 60 * 24 * 30 * 3))
			$cc = "class=f_green";
		else if ($dcc > (time() - 60 * 60 * 24 * 30 * 6))
			$cc = "class=f_purple";
		else if ($dcc > (time() - 60 * 60 * 24 * 30 * 9))
			$cc = "class=f_brown";
		else if ($dcc > (time() - 60 * 60 * 24 * 30 * 12))
			$cc = "class=f_more";
		
		$end = date("Y-m-d");
		$nxt[17] = SearchHilight(html_out($nxt[17]), $s);
		$nxt[2] = SearchHilight(html_out($nxt[2]), $s);
		$nxt[8] = SearchHilight(html_out($nxt[8]), $s);
		$i = 1 - $i;
		$cost_p = $dop_data['cena'] ? getCostPos($nxt[0], $dop_data['cena']) : $nxt[5];
		$cost_r = sprintf("%0.2f", $nxt[7]);

		$tmpl->addContent("<tr class='lin$i pointer'
		ondblclick=\"EditThis('/docs.php?l=sklad&mode=srv&opt=ep&param=k&pos=1&plm=pos&pos=$pos&vpos=$nxt[0]','poslist'); return false;\">
		<td>$nxt[0]
		<a href='' onclick=\"ShowContextMenu('/docs.php?mode=srv&opt=menu&doc=0&pos=$nxt[0]'); return false;\" title='Меню' accesskey=\"S\"><img src='img/i_menu.png' alt='Меню' border='0'></a>
		<td>$nxt[17]<td align=left>$nxt[2]<td>$nxt[3]<td $cc>$cost_p<td>$nxt[4]%<td>$cost_r<td>$nxt[8]<td>$nxt[9]<td>$nxt[10]<td>$nxt[11]<td>$nxt[12]<td>$nxt[13]<td>$rezerv<td>$pod_zakaz<td>$v_puti<td>$nxt[15]<td>$nxt[16]<td>$nxt[14]");
	}
}

?>