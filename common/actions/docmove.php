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
namespace Actions;

/// Перемещение документов на начало текущего дня
class DocMove extends \Action {
	
	/// @brief Запустить
	public function run() {
		$start_day = strtotime(date("Y-m-d 00:00:01"));
		
		// Перемещение непроведённых реализаций на начало текущего дня
		if ($this->config['auto']['move_nr_to_end'] == true)
			$this->db->query("UPDATE `doc_list` SET `date`='$start_day' WHERE `type`=2 AND `ok`=0 AND `mark_del`=0");

		// Перемещение непроведённых заявок на начало текущего дня
		if ($this->config['auto']['move_no_to_end'] == true)
			$this->db->query("UPDATE `doc_list` SET `date`='$start_day' WHERE `type`=3 AND `ok`=0 AND `mark_del`=0");	
                
                // Перемещение непроведённых перемещений товаров на начало текущего дня
		if ($this->config['auto']['move_ntp_to_end'] == true)
			$this->db->query("UPDATE `doc_list` SET `date`='$start_day' WHERE `type`=8 AND `ok`=0 AND `mark_del`=0");
	}
}
