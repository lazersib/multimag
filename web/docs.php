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

require_once("core.php");
require_once("include/doc.s.nulltype.php");
need_auth();
$tmpl->hideBlock('left');
SafeLoadTemplate($CONFIG['site']['inner_skin']);

try {
    switch (request('l')) {
        case 'agent':
            $sprav = new doc_s_Agent();
            break;
        case 'dov':
            $sprav = new doc_s_Agent_dov();
            break;
        case 'inf':
            $sprav = new doc_s_Inform();
            break;
        case 'pran':
            $sprav = new doc_s_Price_an();
            break;
        default:
            $sprav = new doc_s_Sklad();
    }
    switch (request('mode')) {
        case '':
            $sprav->View();
            break;
        case 'srv':
            $sprav->Service();
            break;
        case 'edit':
            $sprav->Edit();
            break;
        case 'esave':
            $sprav->ESave();
            break;
        case 'search':
            $sprav->Search();
            break;
        default:
            throw new Exception('Неверный параметр');
    }
} catch (AccessException $e) {
    $tmpl->ajax = 0;
    $tmpl->errorMessage('Не достаточно привилегий: ' . $e->getMessage(), "Нет доступа");
} catch (mysqli_sql_exception $e) {
    $tmpl->ajax = 0;
    switch ($e->getCode()) {
        case 1062:
            $tmpl->errorMessage("Неверно заполнены поля: не соблюдена уникальность!<br>" . $e->getMessage(), "Ошибка в базе данных");
            break;
        default:
            $id = writeLogException($e);
            $tmpl->errorMessage("Порядковый номер ошибки: $id<br>Сообщение об ошибке занесено в журнал", "Ошибка в базе данных");
    }
} catch (Exception $e) {
    $db->rollback();
    $tmpl->setContent('');
    writeLogException($e);
    $tmpl->errorMessage($e->getMessage());
}

$tmpl->write();
