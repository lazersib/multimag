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

/// Редактор справочника доверенных лиц
class doc_s_Agent_dov
{
	/// Конструктор
	function __construct()	{
		$this->dl_vars = array('ag_id' , 'name' , 'name2' , 'surname' , 'range' , 'pasp_ser' , 'pasp_num' , 'pasp_kem' , 'pasp_data');
	}
	
	function View()	{
		global $tmpl;
		doc_menu();
		if(!isAccess('list_agent_dov','view'))	throw new AccessException();
		$tmpl->setTitle("Редактор доверенных лиц");
		$tmpl->addContent("<table width='100%'>
		<tr><td><h1>Доверенные лица</h1></td>
		<td align='right'>Отбор:<input type='text' id='f_search' onkeydown=\"DelayedSave('/docs.php?l=dov&mode=srv&opt=pl','list', 'f_search'); return true;\" >
		</table>
		<table width='100%'><tr>");
		$tmpl->addContent("<td id='list' valign='top' class='lin1'>");
		$this->ViewList();
		$tmpl->addContent("</table>");
	}

	function Service() {
		global $tmpl, $db;
		$opt = request("opt");
		if ($opt == 'pl') {
			$s = request('s');
			$tmpl->ajax = 1;
			if ($s)	$this->ViewListS($s);
			else	$this->ViewList();
		}
		else if ($opt == 'ep') {
			$this->Edit();
		}
		else if ($opt == 'popup') {
			$ag = rcvint('ag');
			$tmpl->ajax = 1;
			$s = request('s');
				
			$s_sql = $db->real_escape_string($s);
			$res = $db->query("SELECT `id`,`surname`,`name` FROM `doc_agent_dov` WHERE `ag_id`='$ag' AND LOWER(`surname`) LIKE LOWER('%$s_sql%') LIMIT 500");
			if($res->num_rows){
				$tmpl->addContent('Ищем: ' . html_out($s) . ' (' . $res->num_rows . ' совпадений) :' . $ag . '<br>');
				while ($nxt = $res->fetch_row())
					$tmpl->addContent("<a onclick=\"return SubmitData('$nxt[1] $nxt[2]',$nxt[0]);\">$nxt[1] $nxt[2]</a><br>");
			}
			else	$tmpl->addContent("<b>Искомая комбинация " . html_out($s) . " не найдена!</b>");
		}
		else	$tmpl->msg("Неверный режим!");
	}

/// Редактирование доверенного лица
	function Edit() {
		global $tmpl, $db;
		doc_menu();
		$pos = rcvint('pos');
		$ag_id = rcvint('ag_id');
		$param = request('param');
		if (!isAccess('list_agent_dov', 'view'))	throw new AccessException();
		if (($pos == 0) && ($param != 'g'))		$param = '';
		$tmpl->setTitle("Правка доверенного лица");
		if ($param == '') {
			$res = $db->query("SELECT `id`, `ag_id` , `name` , `name2` , `surname` , `range` , `pasp_ser` , `pasp_num` , `pasp_kem` , `pasp_data` , `mark_del`
			FROM `doc_agent_dov`
			WHERE `doc_agent_dov`.`id`='$pos'");
			$tmpl->addContent("<h1>Доверенные лица</h1>");
			if ($res->num_rows)
				$dl_info = $res->fetch_assoc();
			else {
				$tmpl->addContent("<h3>Новая запись</h3>");
				$dl_info = array();
				foreach ($this->dl_vars as $value)
					$dl_info[$value] = '';				
			}

			$tmpl->addContent("<form action='' method='post'><table class='list' cellpadding='0' width='100%'>
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='dov'>
			<input type='hidden' name='pos' value='$pos'>
			<tr><th width='20%'>Параметр</th><th>Значение</th></tr>
			<tr><td align='right' width='20%'>Имя</td><td><input type='text' name='name' value='".html_out($dl_info['name'])."'></td></tr>
			<tr><td align='right' width='20%'>Отчество</td><td><input type='text' name='name2' value='".html_out($dl_info['name2'])."'></td></tr>
			<tr><td align='right' width='20%'>Фамилия</td><td><input type='text' name='surname' value='".html_out($dl_info['surname'])."'></td></tr>
			<tr><td align='right'>Организация:</td><td><select name='ag_id'>");
			$r = $db->query("SELECT `id`,`name` FROM `doc_agent` ORDER BY `name`");
			while ($nx = $r->fetch_row()) {
				$i = "";
				if ((($pos != 0) && ($nx[0] == $dl_info['ag_id'])) || (($pos == 0) && ($ag_id == $nx[0])))
					$i = " selected style='background-color: #bfb;'";
				$tmpl->addContent("<option value='$nx[0]' $i>".html_out($nx[1])."</option>");
			}
			$tmpl->addContent("</select></td></tr>
			<tr><td align='right' width='20%'>Должность:</td><td><input type='text' name='range' value='".html_out($dl_info['range'])."'></td></tr>
			<tr><td align='right' width='20%'>Паспорт: серия</td><td><input type='text' name='pasp_ser' value='".html_out($dl_info['pasp_ser'])."'></td></tr>
			<tr><td align='right' width='20%'>Паспорт: номер</td><td><input type='text' name='pasp_num' value='".html_out($dl_info['pasp_num'])."'></td></tr>
			<tr><td align='right' width='20%'>Паспорт: выдан</td><td><input type='text' name='pasp_kem' value='".html_out($dl_info['pasp_kem'])."'></td></tr>
			<tr><td align='right' width='20%'>Паспорт: дата выдачи</td><td><input type='text' name='pasp_data' value='".html_out($dl_info['pasp_data'])."'></td></tr>
			<tr><td><td><button type='submit'>Сохранить</button></td></tr>
			</table></form>");
		}
		else	$tmpl->msg("Неизвестная закладка");
	}
	
	/// Сохранение данных доверенного лица
	function ESave() {
		global $tmpl, $db;
		doc_menu();
		$pos = rcvint('pos');
		$param = request('param');
		$tmpl->setTitle("Правка доверенного лица");
		if($param=='')	{
			
			$new_dl_info = array (
				'name'		=> request('name'),
				'name2'		=> request('name2'),
				'surname'	=> request('surname'),
				'range'		=> request('range'),
				'ag_id'		=> rcvint('ag_id'),
				'pasp_ser'	=> request('pasp_ser'),
				'pasp_num'	=> request('pasp_num'),
				'pasp_data'	=> rcvdate('pasp_data'),
				'pasp_kem'	=> request('pasp_kem')
			);
			
			if($pos) {
				if(!isAccess('list_agent_dov','edit'))	throw new AccessException();
				$res = $db->query("SELECT `ag_id` , `name` , `name2` , `surname` , `range` , `pasp_ser` , `pasp_num` , `pasp_kem` , `pasp_data`
				FROM `doc_agent_dov`
				WHERE `doc_agent_dov`.`id`='$pos'");
				$dl_info = $res->fetch_assoc();
				$db->updateA('doc_agent_dov', $pos, $new_dl_info);
				$log_text = getCompareStr($dl_info, $new_dl_info);
				doc_log('UPDATE', $log_text, 'agent_dov', $pos);
				
				$tmpl->msg("Данные обновлены");
			}
			else {
				if(!isAccess('list_agent_dov','create'))	throw new AccessException();
				$pos = $db->insertA('doc_agent_dov', $new_dl_info);
				$dl_info = array();
				foreach ($this->dl_vars as $value)
					$dl_info[$value] = '';
				$log_text = getCompareStr($dl_info, $new_dl_info);
				doc_log('CREATE', $log_text, 'agent_dov', $pos);
				$tmpl->msg("Добавлена новая запись!");
			}
		}
		else $tmpl->msg("Неизвестная закладка");
	}
	
	/// Отобразить список доверенных лиц
	function ViewList() {
		global $tmpl, $db;

		$sql = "SELECT a.`id`, `a`.`surname`, a.`name` , a.`name2` , b.`name`, a.`range`, a.`mark_del`
		FROM `doc_agent_dov` AS `a`
		LEFT JOIN `doc_agent` AS `b` ON `a`.`ag_id`=`b`.`id`
		ORDER BY `a`.`surname`";

		$lim = 50;
		$page = rcvint('p');
		$res = $db->query($sql);
		if ($res->num_rows > $lim) {
			if ($page < 1)
				$page = 1;
			if ($page > 1) {
				$i = $page - 1;
				$tmpl->addContent("<a href='' onclick=\"EditThis('/docs.php?l=dov&mode=srv&opt=pl&p=$i','list'); return false;\">&lt;&lt;</a> ");
			}
			$cp = $res->num_rows / $lim;
			for ($i = 1; $i < ($cp + 1); $i++) {
				if ($i == $page)
					$tmpl->addContent(" <b>$i</b> ");
				else
					$tmpl->addContent("<a href='' onclick=\"EditThis('/docs.php?l=dov&mode=srv&opt=pl&p=$i','list'); return false;\">$i</a> ");
			}
			if ($page < $cp) {
				$i = $page + 1;
				$tmpl->addContent("<a href='' onclick=\"EditThis('/docs.php?l=dov&mode=srv&opt=pl&p=$i','list'); return false;\">&gt;&gt;</a> ");
			}
			$tmpl->addContent("<br>");
			$sl = ($page - 1) * $lim;

			$res->data_seek($sl);
		}

		if ($res->num_rows) {
			$tmpl->addContent("<table width=100% cellspacing=1 cellpadding=2><tr>
			<th>№<th>Фамилия<th>Имя<th>Отчество<th>Организация<th>Должность");
			$this->DrawTable($res, '', $lim);
			$tmpl->addContent("</table>");
		}
		else	$tmpl->msg("Записей не найдено!");
		$tmpl->addContent("
		<a href='/docs.php?l=dov&mode=srv&opt=ep&pos=0'><img src='/img/i_add.gif' alt=''> Добавить</a>");
	}

	function ViewListS($s) {
		global $tmpl, $db;
		$sf = 0;
		$tmpl->addContent("<table class='list' width='100%' cellspacing='1' cellpadding='2'><tr>
		<th>№</th><th>Фамилия</th><th>Имя</th><th>Отчество</th><th>Организация</th><th>Должность</th></tr>");

		$sql = "SELECT a.`id`, `a`.`surname`, a.`name` , a.`name2` , b.`name`, a.`range`, a.`mark_del`
		FROM `doc_agent_dov` AS `a`
		LEFT JOIN `doc_agent` AS `b` ON `a`.`ag_id`=`b`.`id`";
		$s_sql = $db->real_escape_string($s);
		$sqla = $sql."WHERE `a`.`name` LIKE '$s_sql%' OR `a`.`surname` LIKE '$s_sql%' ORDER BY `a`.`name` LIMIT 30";
		$res = $db->query($sqla);
		if($res->num_rows) {
			$tmpl->addContent("<tr class=lin0><th colspan=16 align=center>Поиск по названию, начинающемуся на ".html_out($s).": найдено ".$res->num_rows);
			$this->DrawTable($res, $s);
			$sf = 1;
		}

		$sqla = $sql."WHERE (`a`.`name` LIKE '%$s_sql%' OR `a`.`surname` LIKE '%$s_sql%') AND (`a`.`name` NOT LIKE '$s_sql%' AND `a`.`surname` NOT LIKE '$s_sql%') ORDER BY `a`.`name` LIMIT 30";
		$res = $db->query($sqla);
		if($res->num_rows) {
			$tmpl->addContent("<tr class=lin0><th colspan=16 align=center>Поиск по названию, содержащему ".html_out($s).": найдено ".$res->num_rows);
			$this->DrawTable($res, $s);
			$sf = 1;
		}
		$tmpl->addContent("</table><a href='/docs.php?l=agent&mode=srv&opt=ep&pos=0'><img src='/img/i_add.gif' alt=''> Добавить</a>");

		if($sf == 0)	$tmpl->msg("По данным критериям записей не найдено!");
	}

	/// Отобразить строки таблицы доверенных лиц
	/// @param res		Объект mysqli_result, возвращенный запросом списка доверенных лиц
	/// @param s		Строка поиска. Будет подсвечена в данных
	/// @param limit	Ограничение на количество выводимых строк
	function DrawTable($res, $s, $lim=1000000)
	{
		global $tmpl;
		$i=0;
		while($nxt = $res->fetch_row())
		{
			$nxt[1] = SearchHilight( html_out($nxt[1]), $s);
			$nxt[2] = SearchHilight( html_out($nxt[2]), $s);
			$nxt[3] = SearchHilight( html_out($nxt[3]), $s);

			$tmpl->addContent("<tr align='right'>
			<td><a href='/docs.php?l=dov&mode=srv&opt=ep&pos=$nxt[0]'>$nxt[0]</a></td><td>$nxt[1]</td><td>$nxt[2]</td><td>$nxt[3]</td><td>".html_out($nxt[4])."</td><td>".html_out($nxt[5])."</td></tr>");
		
			if($i++ > $lim)	break;
		}
	}
};


?>
