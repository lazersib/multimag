<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2017, BlackLight, TND Team, http://tndproject.org
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
namespace Models\LDO;

/// Класс списка наименований банков
class banknames extends \Models\ListDataObject {
	
	/// @brief Получить данные
	public function getData() {
		global $db;
		$sql = "SELECT `num`, `name` FROM `doc_kassa` WHERE `ids`='bank' ORDER by `num` ASC";
		$result = '';
		$a = array();
		$res = $db->query($sql);
		while ($line = $res->fetch_assoc()) {
			$a[$line['num']] = $line['name'];
		}
		return $a;
	}
}