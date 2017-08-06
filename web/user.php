<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2017, BlackLight, TND Team, http://tndproject.org
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

include_once("core.php");
need_auth($tmpl);

try {
    $tmpl->setTitle("Личный кабинет");
    $tmpl->setContent("<h1>Личный кабинет</h1>");

    $tmpl->hideBlock('left');
    $mode = request('mode');

    if ($mode == '') {
        $tmpl->addBreadcrumb('Главная', '/');
        $tmpl->addBreadcrumb('Личный кабинет', '');
        $auth = new \authenticator();
        $auth->loadDataForID($_SESSION['uid']);
        if ($auth->isNeedConfirmEmail() || $auth->isNeedConfirmPhone()) {
            $login_page = new \Modules\Site\login();
            $tmpl->addContent($login_page->getConfirmForm($_SESSION['name']) . "<br><br>");
        }
        $exp_days = $auth->getDaysExpiredAfter();
        if ($exp_days <= 7) {
            $tmpl->msg("Ваш пароль устареет через $exp_days дней. Вам необходимо <a href='/user.php?mode=chpwd'>сменить</a> его.");
        }

        $cab = new \Modules\Site\cabinet();
        $cab->ExecMode($mode);

        $oauth_login = new \Modules\Site\oauthLogin();
        $tmpl->addContent("<h2>Прекрепить профиль</h2>");
        $tmpl->addContent($oauth_login->getLoginForm());
        $tmpl->msg("Прикрепление профиля позволит Вам входить на этот сайт, не вводя учётных данных.");
    } else if ($mode == 'profile' || $mode == 'chpwd' || $mode == 'cemail' || $mode == 'cphone' || $mode == 'my_docs' || $mode == 'get_doc' || $mode == 'elog' || $mode == 'log_call_request' || $mode == 'feedback' || $mode == 'feedback_send') {
        $cab = new \Modules\Site\cabinet();
        $cab->ExecMode($mode);
    } else {
        throw new NotFoundException("Неверный запрос");
    }
} catch (mysqli_sql_exception $e) {
    $tmpl->ajax = 0;
    $id = writeLogException($e);
    $tmpl->errorMessage("Порядковый номер ошибки: $id<br>Сообщение об ошибке занесено в журнал", "Ошибка в базе данных");
} catch (Exception $e) {
    writeLogException($e);
    $tmpl->errorMessage($e->getMessage());
}
$tmpl->write();

