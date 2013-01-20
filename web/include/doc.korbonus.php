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


$doc_types[19]="Корректировка бонусов";

/// Документ *корректировка бонусов*
class doc_Korbonus extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=19;
		$this->doc_name				='korbonus';
		$this->doc_viewname			='Корректировка бонусов';
		$this->sklad_editor_enable		=false;
		$this->header_fields			='separator agent sum';
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
// 		mysql_query("UPDATE `doc_agent` SET `bonus`=`bonus`+'{$this->doc_data['sum']}' WHERE `id`='{$this->doc}'");
// 		if(mysql_errno())		throw new MysqlException('Ошибка проведения, ошибка начисления бонусного вознаграждения');
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
// 		mysql_query("UPDATE `doc_agent` SET `bonus`=`bonus`-'{$this->doc_data['sum']}' WHERE `id`='{$this->doc}'");
// 		if(mysql_errno())			throw new MysqlException('Ошибка проведения, ошибка начисления бонусного вознаграждения');
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




};
?>