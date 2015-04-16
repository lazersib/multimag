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


/// Документ *корректировка бонусов*
class doc_Korbonus extends doc_Nulltype
{
	function __construct($doc=0) {
		parent::__construct($doc);
		$this->doc_type				= 19;
		$this->typename				= 'korbonus';
		$this->viewname			= 'Корректировка бонусов';
		$this->sklad_editor_enable		= false;
		$this->header_fields			= 'separator agent sum';
	}
	
	function DocApply($silent=0) {
		global $db;
		if(!$silent) {
			$res = $db->query("SELECT `no_bonuses` FROM `doc_agent` WHERE `id`=".intval($this->doc_data['agent']));
			if(!$res->num_rows)
				throw new Exception ("Агент не найден");
			$agent_info = $res->fetch_row();
			if($agent_info[0])
				throw new Exception ("Агент не участвует в бонусной программе");
		}
		parent::DocApply($silent);
	}
};
?>