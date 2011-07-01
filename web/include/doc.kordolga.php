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


$doc_types[18]="Договор";

class doc_Kordolga extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=18;
		$this->doc_name				='kordolga';
		$this->doc_viewname			='Корректировка долга';
		$this->sklad_editor_enable		=false;
		$this->header_fields			='agent sum';
		settype($this->doc,'int');
	}
	
	function DocApply($silent=0)
	{
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)			throw new MysqlException('Ошибка выборки данных документа при проведении!');
		$nx=@mysql_fetch_row($res);
		if(!$nx)			throw new Exception('Документ не найден!');
		if( $nx[1] && (!$silent) )	throw new Exception('Документ уже был проведён!');
		if($silent)	return;
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if(!$res)			throw new MysqlException('Ошибка установки даты проведения документа!');	
	}

	function DocCancel()
	{
		global $uid;
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");		
		if(!$res)				throw new MysqlException('Ошибка выборки данных документа!');
		if(! ($nx=@mysql_fetch_row($res)))	throw new Exception('Документ не найден!');
		if(! $nx[4])				throw new Exception('Документ НЕ проведён!');
		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка установки флага!');
	}
	
	function PrintForm($doc, $opt='')
	{
		global $tmpl;
		$tmpl->ajax=1;
		$tmpl->AddText("Печатные формы отсутствуют");
	}

	// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		global $tmpl;
		$tmpl->ajax=1;
		$tmpl->AddText("Нечего создать");
	}

	// Выполнить удаление документа. Если есть зависимости - удаление не производится.
	function DelExec($doc)
	{
		$res=mysql_query("SELECT `ok` FROM `doc_list` WHERE `id`='{$this->doc}'");
		if(!mysql_result($res,0,0)) // Если проведён - нельзя удалять
		{
			$res=mysql_query("SELECT `id`, `mark_del` FROM `doc_list` WHERE `p_doc`='{$this->doc}'");
			if(!mysql_num_rows($res)) // Если есть потомки - нельзя удалять
			{
				mysql_query("DELETE FORM `doc_list_pos` WHERE `doc`='{$this->doc}'");
				mysql_query("DELETE FROM `doc_dopdata` WHERE `doc`='{$this->doc}'");
				mysql_query("DELETE FROM `doc_list` WHERE `id`='{$this->doc}'");
				return 0;
			}
		}
		return 1;
   	}



};
?>