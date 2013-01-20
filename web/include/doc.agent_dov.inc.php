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

require_once("include/doc.core.php");

/// Работа с доверенными лицами агентов
class BDocAgentDov
{
	// Форма создания-редактирования
	function Form($mode,$ag_id,$data="")
	{
		$prn="
		<h2>Данные доверенного лица</h2>
		<form method=post>
		<input type=hidden name=mode value='$mode'>
		<input type=hidden name='_id' value='".$data['id']."'>
		<input type=hidden name='ag_id' value='$ag_id'>
		<table class=minigroup>
		<tr><th>Имя<td><input type=text name=name value='".$data['name']."'>
		<tr><th>Фамилия<td><input type=text name=surname value='".$data['surname']."'>
		<tr><th>Отчество<td><input type=text name=name2 value='".$data['name2']."'>
		<tr><th>Должность<td><input type=text name=range value='".$data['range']."'>
		<tr><th>Паспорт: номер<td><input type=text name=pasp_num value='".$data['pasp_num']."'>
		<tr><th>Паспорт: серия<td><input type=text name=pasp_ser value='".$data['pasp_ser']."'>
		<tr><th>Паспорт: выдан<td><input type=text name=pasp_kem value='".$data['pasp_kem']."'>
		<tr><th>Паспорт: дата выдачи<td><input type=text name=pasp_data value='".$data['pasp_data']."'>
		</table>
		</form>
		";
		return $prn;
	}
	// Добавить или заменить элемент
	function Write($update=1)
	{
		$_id=rcv('_id');
		$ag_id=rcv('ag_id');
		$name=rcv('name');
		$name2=rcv('name2');
		$surname=rcv('surname');
		$range=rcv('range');
		$pasp_num=rcv('pasp_num');
		$pasp_ser=rcv('pasp_ser');
		$pasp_kem=rcv('pasp_kem');
		$pasp_data=rcv('pasp_data');
		if(!$update)
		{
			$res=mysql_query("INSERT INTO `doc_agent_dov`
			(`ag_id`,`name`,`name2`,`surname`,`range`,`pasp_num`,`pasp_ser`,`pasp_kem`,`pasp_data`)
			VALUES
			('$ag_id','$name','$name2','$surname','$range','$pasp_num','$pasp_ser','$pasp_kem','$pasp_data')
			");
			if($res)
			{
				doc_log("INSERT doc_agent_dov","$ag_id, $name, $name2, $surname, $range, $pasp_num, $pasp_ser, $pasp_kem, $pasp_data");
				return mysql_insert_id();
			}
			else doc_log("ERROR INSERT doc_agent_dov","$ag_id, $name, $name2, $surname, $range, $pasp_num, $pasp_ser, $pasp_kem, $pasp_data");
		}
		else
		{
			$res=mysql_query("UPDATE `doc_agent_dov` SET
			`name`='$name', `name2`='$name2', `surname`='$surname', `$range`='$range', `pasp_num`='$pasp_num', `pasp_ser`='$pasp_ser', `pasp_kem`='$pasp_kem', `pasp_data`='$pasp_data'
			WHERE `id`='$_id'
			");
			if($res)
			{
				doc_log("UPDATE doc_agent_dov","$ag_id, $name, $name2, $surname, $range, $pasp_num, $pasp_ser, $pasp_kem, $pasp_data");
				return 1;
			}
			else doc_log("ERROR UPDATE doc_agent_dov","$ag_id, $name, $name2, $surname, $range, $pasp_num, $pasp_ser, $pasp_kem, $pasp_data");
		}
		return 0;
	}
};

?>