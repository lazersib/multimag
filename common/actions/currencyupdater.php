<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2015, BlackLight, TND Team, http://tndproject.org
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
namespace Actions;

/// Загрузка и обновление курсов валют
class CurrencyUpdater extends \Action {
	
	/// @brief Запустить
	public function run() {
		// REPLACE не используется, чтобы не менялись ID
		$res = $this->db->query("SELECT `id`, `name`, `coeff` FROM `currency` WHERE `name`='RUB'");
		if(!$res->num_rows) {
			$this->db->query("INSERT INTO `currency` (`name`, `coeff`) VALUES ('RUB', 1)");
		}
		else	$this->db->query("UPDATE `currency` SET `coeff`=1 WHERE `name`='RUB'");
		
		$data = file_get_contents("http://www.cbr.ru/scripts/XML_daily.asp");
		$doc = new \DOMDocument('1.0');
		$doc->loadXML($data);
		$doc->normalizeDocument();
		$valutes = $doc->getElementsByTagName('Valute');
		foreach($valutes as $valute) {
			$name = $value = 0;
			foreach ($valute->childNodes as $val) {
				switch ($val->nodeName) {
					case 'CharCode':
						$name = $val->nodeValue;
						break;
					case 'Value':
						$value = $val->nodeValue;
						break;
				}
			}
			$name_sql = $this->db->real_escape_string($name);
			$value = round(str_replace(',', '.', $value), 4);
			
			// REPLACE не используется, чтобы не менялись ID
			$res = $this->db->query("SELECT `id`, `name`, `coeff` FROM `currency` WHERE `name`='$name_sql'");
			if(!$res->num_rows) {
				$this->db->query("INSERT INTO `currency` (`name`, `coeff`) VALUES ('$name_sql', '$value')");
			}
			else	$this->db->query("UPDATE `currency` SET `coeff`='$value' WHERE `name`='$name_sql'");
		}
	}
}
