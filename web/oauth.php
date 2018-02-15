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

include("core.php");

try {
    $login_page = new \Modules\Site\oauthLogin();
    $login_page->run();
}
catch(mysqli_sql_exception $e) {
    $id = writeLogException($e);
    $pref = \pref::getInstance();
    $tmpl->errorMessage("Ошибка при регистрации. Порядковый номер - $id<br>Сообщение об ошибке занесено в журнал", "Ошибка при регистрации");
    mailto($pref->site_email,"ВАЖНО! Ошибка регистрации на {$pref->site_name}. номер в журнале - $id", $e->getMessage());
}
catch(Exception $e) {
    $db->rollback();
    $id = writeLogException($e);
    $tmpl->errorMessage($e->getMessage() . ". Порядковый номер - $id<br>Сообщение об ошибке занесено в журнал", "Ошибка при аутентификации");
}

$tmpl->write();
