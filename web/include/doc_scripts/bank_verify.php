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


include_once($CONFIG['location'] . "/common/bank1c.php");

/// Сценарий автоматизации:  Сверка банковских документов
class ds_bank_verify {

	function Run($mode) {
		global $tmpl, $db;
		if ($mode == 'view') {
			$tmpl->addContent("<h1>" . $this->getname() . "</h1>
			<form action='' method='post' enctype='multipart/form-data'>
			<input type='hidden' name='mode' value='load'>
			<input type='hidden' name='param' value='i'>
			<input type='hidden' name='sn' value='bank_verify'>

			Файл банковской выписки:<br>
			<input type='hidden' name='MAX_FILE_SIZE' value='10000000'><input name='userfile' type='file'>
			<button type='submit'>Выполнить</button>
			</form>");
		}
		else if ($mode == 'load') {
			$tmpl->addContent("<h1>" . $this->getname() . "</h1>");
			if ($_FILES['userfile']['size'] <= 0)
				throw new Exception("Забыли выбрать файл?");
			$file = file($_FILES['userfile']['tmp_name']);
			$_SESSION['bankparser'] = new Bank1CPasrser($file);
			$_SESSION['bankparser']->Parse();
			$_SESSION['bp']['parsed_data'] = $_SESSION['bankparser']->parsed_data;

			$tmpl->addContent("<table width='100%'>
			<tr><th colspan='5'>В выписке<th colspan='5'>В базе
			<tr>
			<th>ID<th>Номер<th>Дата<th>Сумма<th>Счёт
			<th>ID<th>Номер<th>Дата<th>Сумма<th>Агент");

			foreach ($_SESSION['bankparser']->parsed_data as $v_line) {
				$u_sql = $db->real_escape_string($v_line['unique']);
				$tmpl->addContent("<tr><td>{$v_line['unique']}</td><td>{$v_line['docnum']}</td><td>{$v_line['date']}</td>
					<td>{$v_line['debet']} / {$v_line['kredit']}</td><td>{$v_line['kschet']}</td>");
				$res = $db->query("SELECT `doc_list`.`id`, CONCAT(`doc_list`.`altnum`, `doc_list`.`subtype`) AS `num`, `doc_list`.`date`,
					`doc_list`.`sum`, `doc_agent`.`name` AS `agent_name`
				FROM `doc_dopdata`
				LEFT JOIN `doc_list` ON `doc_dopdata`.`doc`=`doc_list`.`id`
				LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
				WHERE `doc_dopdata`.`param`='unique' AND `doc_dopdata`.`value`='$u_sql'");
				$doc_data = $res->fetch_array();

				if ($doc_data) {
					$date = date("d.m.Y H:i:s", $doc_data['date']);
					$tmpl->addContent("<td>{$doc_data['id']}</td><td>{$doc_data['num']}</td><td>$date<td>{$doc_data['sum']}</td>
						<td>".html_out($doc_data['agent_name'])."</td>");
				}
				$tmpl->AddContent("</tr>");
			}
			$tmpl->addContent("</table>");
		}
	}

	function getName() {
		return "Сверка банковских документов";
	}
}
?>