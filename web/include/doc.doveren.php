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

/// Документ *доверенность*
class doc_Doveren extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=10;
		$this->doc_name				='doveren';
		$this->doc_viewname			='Доверенность';
		$this->sklad_editor_enable		=true;
		$this->sklad_modify			=0;
		$this->header_fields			='separator agent cena';
	}
	
	function initDefDopdata() {
		$this->def_dop_data = array('ot'=>'');
	}

	function DopHead() {
		global $tmpl;
		$tmpl->addContent("На получение от:<br>
		<input type='text' name='ot' value='{$this->dop_data['ot']}'><br>");	
	}

	function DopSave() {
		$new_data = array(
			'ot' => request('ot')
		);
		$old_data = array_intersect_key($new_data, $this->dop_data);
		
		$log_data='';
		if($this->doc)
			$log_data = getCompareStr($old_data, $new_data);
		$this->setDopDataA($new_data);
		if($log_data)	doc_log("UPDATE {$this->doc_name}", $log_data, 'doc', $this->doc);
	}
	
	// Формирование другого документа на основании текущего
	function MorphTo($target_type)
	{
		global $tmpl;
		if ($target_type == '') {
			$tmpl->ajax = 1;
			$tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=1'\">
			<li><a href=''>Поступление товара</div>");
		}
		else if ($target_type == 1) {
			$sum = $this->recalcSum();
			if (!isAccess('doc_postuplenie', 'create'))
				throw new AccessException();
			$new_doc = new doc_Postuplenie();
			$dd = $new_doc->createFrom($this);
			$ref = "Location: doc.php?mode=body&doc=$ndoc";
			header($ref);
		}
		else	$tmpl->msg("В разработке", "info");
	}

};
?>