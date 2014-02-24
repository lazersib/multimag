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

/// Документ *корректоровка долга*
class doc_Kordolga extends doc_Nulltype
{
	function __construct($doc=0) {
		parent::__construct($doc);
		$this->doc_type				= 18;
		$this->doc_name				= 'kordolga';
		$this->doc_viewname			= 'Корректировка долга';
		$this->sklad_editor_enable		= false;
		$this->header_fields			= 'separator agent sum';
	}
};
?>