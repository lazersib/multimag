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
// Новый журнал документов. Оптимизированная версия для открытия большого журнала
include_once("core.php");
include_once("include/doc.core.php");
need_auth();
if (!isAccess('doc_list', 'view'))
	throw new AccessException("");

SafeLoadTemplate($CONFIG['site']['inner_skin']);
$tmpl->hideBlock('left');

if (!isset($_REQUEST['mode'])) {
	$tmpl->setTitle("Реестр документов");
	doc_menu("<a href='?mode=print' title='Печать реестра'><img src='img/i_print.png' alt='Реестр документов' border='0'></a>");
	$tmpl->addContent("<script type='text/javascript' src='/css/doc_script.js'></script>
	<div id='doc_list_filter'></div>
	<div class='clear'></div>
	<div id='doc_list_status'></div>

	<table width='100%' cellspacing='1' onclick='hlThisRow(event)' id='doc_list' class='list'>
	<thead>
	<tr>
	<th width='55'>a.№</th><th width='20'>&nbsp;</th><th width='45'>id</th><th width='20'>&nbsp;<th>Тип<th>Источник<th>Назначение<th>Сумма<th>Дата<th>Автор
	</tr>
	</thead>
	<tbody id='docj_list_body'>
	</tbody>
	</table>

	<br><b>Легенда</b>: строка - <span class='f_green'>интернет - магазин</span>, <span class='f_red'>с ошибкой</span><br>
	Номер реализации - <span class='f_green'>Оплачено</span>, <span class='f_red'>Не оплачено</span>, <span class='f_brown'>Частично оплачено</span>, <span class='f_purple'>Переплата</span><br>
	Номер заявки - <span class='f_green'>Отгружено</span>, <span class='f_brown'>Частично отгружено</span>
	<script type='text/javascript' src='/js/doc_journal.js'></script>
	<script>
	initDocJournal(document.getElementById('docj_list_body'), {dateFrom: '".date("Y-m-d")."'});
	</script>
	");
}

$tmpl->write();
?>
