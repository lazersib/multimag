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

require_once("include/doc.core.php");

/// Работа с доверенными лицами агентов
class BDocAgentDov {

	// Форма создания-редактирования
	function Form($mode, $ag_id, $data = "") {
		$prn = "
		<h2>Данные доверенного лица</h2>
		<form method='post'>
		<input type='hidden' name='mode' value='$mode'>
		<input type='hidden' name='id' value='" . $data['id'] . "'>
		<input type='hidden' name='ag_id' value='$ag_id'>
		<table class='minigroup'>
		<tr><th>Имя</th><td><input type='text' name='data[name] value='" . html_out($data['name']) . "'>
		<tr><th>Фамилия</th><td><input type='text' name='data[surname]' value='" . html_out($data['surname']) . "'>
		<tr><th>Отчество</th><td><input type='text' name='data[name2]' value='" . html_out($data['name2']) . "'>
		<tr><th>Должность</th><td><input type='text' name='data[range]' value='" . html_out($data['range']) . "'>
		<tr><th>Паспорт: номер</th><td><input type='text' name='data[pasp_num]' value='" . html_out($data['pasp_num']) . "'>
		<tr><th>Паспорт: серия</th><td><input type='text' name='data[pasp_ser]' value='" . html_out($data['pasp_ser']) . "'>
		<tr><th>Паспорт: выдан</th><td><input type='text' name='data[pasp_kem]' value='" . html_out($data['pasp_kem']) . "'>
		<tr><th>Паспорт: дата выдачи</th><td><input type='text' name='data[pasp_data]' value='" . html_out($data['pasp_data']) . "'>
		</table>
		</form>
		";
		return $prn;
	}

	// Добавить или заменить элемент
	function Write($update = 1) {
		global $db;
		$id = rcvint('id');
		$ag_id = rcvint('ag_id');
		$form_fields = array('name' => '', 'surname' => '', 'name2' => '', 'range' => '', 'pasp_num' => '', 'pasp_ser' => '', 'pasp_kem' => '', 'pasp_data' => '');
		if (is_array($_REQUEST['data']))
			$form_fields = array_intersect_key($_REQUEST['data'], $form_fields);

		if (!$update) {
			$form_fields['ag_id'] = $ag_id;
			$ins_id = $db->insertA('doc_agent_dov', $form_fields);
			doc_log("INSERT doc_agent_dov", var_export($form_fields, true), 'agent_dov', $ins_id);
			return $ins_id;
		}
		else {
			$old = $db->selectRowAi('doc_agent_dov', $id, $form_fields);
			$log = getCompareStr($old, $form_fields);
			if ($log) {
				doc_log("UPDATE doc_agent_dov", $log);
				$res = $db->updateA('doc_agent_dov', $_id, $form_fields);
			}
			return 1;
		}
		return 0;
	}

}

;
?>