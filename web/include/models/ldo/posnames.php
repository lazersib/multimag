<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
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

/// Класс списка складских наименований
class posnames extends \Models\ListDataObject {
	
	/// @brief Получить данные
	public function getData() {
		global $db, $CONFIG;
		$sql = "SELECT `id`, `name`, `proizv` AS `vendor`, `vc` FROM `doc_base` ORDER BY `name`";
		$result = '';
		$a = array();
		$res = $db->query($sql);
		while ($line = $res->fetch_assoc()) {
			$str = '';
			if (@$CONFIG['poseditor']['vc'] && $line['vc'])
				$str = $line['vc'].' ';
			$str .= $line['name'];
			if($line['vendor'])
				$str .= ' '.$line['vendor'];
			$a[$line['id']] = $str;
		}
		return $a;
	}
} 