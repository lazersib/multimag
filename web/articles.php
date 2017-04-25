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

require_once("core.php");

$tmpl->addBreadcrumb('Главная', '/');

if (!isset($_REQUEST['p'])) {
    $arr = explode('/', $_SERVER['REQUEST_URI']);
    $arr = explode('.', @$arr[2]);
    $p = urldecode(urldecode(@$arr[0]));
} else {
    $p = $_REQUEST['p'];
}

try {
    $wikipage = new \modules\site\wikipage();
    $wikipage->setPageName($p);
    $wikipage->run();
} catch (mysqli_sql_exception $e) {
    $db->rollback();
    $tmpl->ajax = 0;
    $id = writeLogException($e);
    $tmpl->msg("Порядковый номер ошибки: $id<br>Сообщение об ошибке занесено в журнал", 'err', "Ошибка в базе данных");
} catch (NotFoundException $e) {
    $db->query("ROLLBACK");
    $tmpl->setContent("");
    $tmpl->errorMessage($e->getMessage());
    $edit_link = $wikipage->getEditLink(strip_tags($p));
    $tmpl->addContent("<a href='$edit_link'>Создать статью</a>");
} catch (Exception $e) {
    $db->query("ROLLBACK");
    $tmpl->addContent("<br><br>");
    writeLogException($e);
    $tmpl->errorMessage($e->getMessage());
}

$tmpl->write();
