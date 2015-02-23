<?php
//	MultiMag v0.2 - Complex sales system
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

/// Документ *Оперативная реализация*
class doc_Realiz_op extends doc_Realizaciya {
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				= 15;
		$this->doc_name				= 'realiz_op';
		$this->doc_viewname			= 'Реализация товара (опер)';
	}

	/// Провести документ
	/// @param silent Не менять отметку проведения
	function DocApply($silent=0) {
		global $db;
		if($silent)	return;
		$data = $db->selectRow('doc_list', $this->doc);
		if(!$data)
			throw new Exception('Ошибка выборки данных документа при проведении!');
		if($data['ok'])
			throw new Exception('Документ уже проведён!');
		$db->update('doc_list', $this->doc, 'ok', time() );
		$this->sentZEvent('apply');
	}
	
	/// отменить проведение документа
	function DocCancel() {
		global $db;
		$data = $db->selectRow('doc_list', $this->doc);
		if(!$data)
			throw new Exception('Ошибка выборки данных документа!');
		if(!$data['ok'])
			throw new Exception('Документ не проведён!');
		$db->update('doc_list', $this->doc, 'ok', 0 );
		$this->sentZEvent('cancel');			
	}

}
