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

include_once("include/doc.core.php");
include_once("include/doc.s.sklad.php");
include_once("include/doc.s.agent.php");
include_once("include/doc.s.agent_dov.php");
include_once("include/doc.s.inform.php");
include_once("include/doc.s.price_an.php");

$doc_types[0]="Неопределённый справочник";

/// Неопределённый справочник
/// TODO: сделать базовым классом справочников
class doc_s_Nulltype
{
	function doc_s_Nulltype()
	{
	
	}
	
	function View()
	{
		global $tmpl;
	        $tmpl->msg("Неизвестный тип справочника, либо справочник в процессе разработки!",err);
	}
	// Применить изменения редактирования
	function head_submit()
	{
		global $tmpl;
        $tmpl->msg("Неизвестный тип документа, либо документ в процессе разработки!",err);
	}
	// Редактирование тела докумнета
	function body($doc)
	{
		global $tmpl;
        $tmpl->msg("Неизвестный тип документа, либо документ в процессе разработки!",err);
	}
	// Провести
	function Apply($doc, $silent=0)
	{
		global $tmpl;
        $tmpl->msg("Неизвестный тип документа, либо документ в процессе разработки!",err);
	}
	// Отменить проведение
	function Cancel($doc)
	{
		global $tmpl;
        $tmpl->msg("Неизвестный тип документа, либо документ в процессе разработки!",err);
	}
	// Печать документа
	function Printform($doc, $opt='')
	{
		global $tmpl;
        $tmpl->msg("Неизвестный тип документа, либо документ в процессе разработки!",err);
	}
	// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		global $tmpl;
        $tmpl->msg("Неизвестный тип документа, либо документ в процессе разработки!",err);
	}
	// Выполнить удаление документа. Если есть зависимости - удаление не производится.
	function DelExec($doc)
	{
		global $tmpl;
		return 1;
   	}
	// Служебные опции
	function Service($doc)
	{
		global $tmpl;
        $tmpl->msg("Неизвестный тип документа, либо документ в процессе разработки!",err);
	}

};


?>