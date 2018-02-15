<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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

include_once("include/doc.core.php");
include_once("include/doc.s.sklad.php");
include_once("include/doc.s.agent.php");
include_once("include/doc.s.agent_dov.php");
include_once("include/doc.s.inform.php");
include_once("include/doc.s.price_an.php");

/// Неопределённый справочник
/// TODO: сделать базовым классом справочников
class doc_s_Nulltype
{
	function __construct() {}
	
	function View() {
		global $tmpl;
	        $tmpl->msg("Неизвестный тип справочника, либо справочник в процессе разработки!", 'err');
	}
	
	function Edit() {
		global $tmpl;
	        $tmpl->msg("Неизвестный тип справочника, либо справочник в процессе разработки!", 'err');
	}
	
	function ESave() {
		global $tmpl;
	        $tmpl->msg("Неизвестный тип справочника, либо справочник в процессе разработки!", 'err');
	}
	
	function Search() {
		global $tmpl;
	        $tmpl->msg("Неизвестный тип справочника, либо справочник в процессе разработки!", 'err');
	}
	
	// Служебные опции
	function Service() {
		global $tmpl;
		$tmpl->msg("Неизвестный тип справочника, либо справочник в процессе разработки!", 'err');
	}
}
