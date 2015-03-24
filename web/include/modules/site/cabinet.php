<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2015, BlackLight, TND Team, http://tndproject.org
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

namespace Modules\Site;

/// Класс личного кабинета
class cabinet extends \IModule {

    public function __construct() {
        parent::__construct();
        $this->link_prefix = '/user.php';
    }
    
    // Получить название модуля
    /// @return Строка с именем
    public function getName() {
        return 'Личный кабинет';
    }
    
    /// Получить описание модуля
    /// @return Строка с описанием
    public function getDescription() {
        return 'Управление профилем пользователя и доступ к внутренним функциям';  
    }
    
    /// Запустить модуль на исполнение
    public function run() {
        global $tmpl;
        $tmpl->setTitle("Личный кабинет");
        $this->ExecMode(request('mode'));
    }

    /// Отобразить страницу новостей
    /// @param mode: '' - список новостей
    public function ExecMode($mode = '') {
        global $tmpl, $CONFIG, $db;
        $tmpl->addBreadcrumb('Главная', '/');
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $tmpl->setContent("<h1>Личный кабинет</h1>");
        $tmpl->setTitle("Личный кабинет");
        if ($mode == '') {
            $tmpl->addBreadcrumb($this->getName(), '');
            $tmpl->addContent("<h2>Заглушка</h2>");
        } else if ($mode == 'profile') {
            $this->tryShowProfile();
        } else if ($mode == 'chpwd') {
            $this->tryChangePassword();
        } elseif ($mode == 'cemail') {
            $this->tryChangeEmail();
        } elseif ($mode == 'cphone') {
            $this->tryChangePhone();
        } elseif ($mode == 'my_docs') {
            $this->getDocListForThisUser();
        } elseif ($mode == 'get_doc') {
            $this->getDocPdf();
        } elseif ($mode == 'elog') {
            $this->viewErrorLog();
        } elseif ($mode == 'log_call_request') {
            $this->viewCallRequestLog();
        } elseif ($mode == 'feedback') {
            $this->viewFeedbackForm();
        } elseif ($mode == 'feedback_send') {
            $this->sendFeedback();
        } else {
            throw new \NotFoundException("Неверный $mode");
        }
    }

    /// Сформировать HTML код формы смены пароля
    public function getChPwdForm() {
        $a = $this->getFormAction();
        $ret = "<form method='post' action='$a'>
        <input type='hidden' name='mode' value='chpwd'>
        <input type='hidden' name='step' value='1'>
        <table>
        <tr><td>Текущий пароль:</td>
        <td><input type='password' name='oldpass'></td></tr>
        <tr><td colspan='2'>Пароль должен быть не менее 8 символов.</td></tr>
        <tr><td>Новый пароль:</td>
        <td><input type='password' name='newpass' id='pass_field'><br><b id='pass_info'></b></td></tr>
        <tr><td>Повторите новый пароль:</td>
        <td><input type='password' name='confirmpass'></td></tr>
        <tr><td>&nbsp;</td>
        <td><button type='submit'>Сменить пароль</button></td></tr>
        </table>
        </form>
        <script type='text/javascript'>
        var pass_field = document.getElementById('pass_field');
        pass_field.onkeyup = function () {
            var password = pass_field.value;
            var pass_info = document.getElementById('pass_info');
            var s_letters = 'qwertyuiopasdfghjklzxcvbnm';
            var b_letters = 'QWERTYUIOPLKJHGFDSAZXCVBNM';
            var digits = '0123456789'; // Цифры
            var specials = '!@#$%^&*()_-+=\\\\\\'|/.,:;[]{}'; 
            var is_s = false;
            var is_b = false;
            var is_d = false;
            var is_sp = false;
            for (var i = 0; i < password.length; i++) {
              if (!is_s && s_letters.indexOf(password[i]) != -1) is_s = true;
              else if (!is_b && b_letters.indexOf(password[i]) != -1) is_b = true;
              else if (!is_d && digits.indexOf(password[i]) != -1) is_d = true;
              else if (!is_sp && specials.indexOf(password[i]) != -1) is_sp = true;
            }
            var rating = 0;
            if (is_s) rating++;
            if (is_b) rating++;
            if (is_d) rating++;
            if (is_sp) rating++;
            if (password.length>15) {
                rating +=2
            } else if(password.length>10) {
                rating +=1
            } else if(password.length<8) {
                rating = 1;
            }
            var text = '';
            var color = '';
            switch(rating) {
                case 1: text = 'Слишком простой';
                        color = '#F00';
                        break;
                case 2: text = 'Простой';
                        color = '#E70';
                        break;
                case 3: text = 'Средний';
                        color = '#BB0';
                        break;
                case 4: text = 'Уже лучше';
                        color = '#8B0';
                        break;
                default:text = 'Хороший';
                        color = '#090';
            }
            pass_info.innerHTML = text;
            pass_info.style.color = color;
        }
        </script>";
        return $ret;
    }
    
    /// Формирует HTM код формы профиля пользователя
    public function getUserProfileForm($user_data, $agent_data) {
        $a = $this->getFormAction();
        $esubscribe = $user_data['reg_email_subscribe'] ? 'checked' : '';
        $psubscribe = $user_data['reg_phone_subscribe'] ? 'checked' : '';
        $bifact = $user_data['bifact_auth'] ? 'Да' : 'Выключена';
        
        if($user_data['reg_phone']) {
            $phone_field = "<b>" . html_out($user_data['reg_phone']) . "</b> (<a href='?mode=cphone'>Сменить</a>)";
        } else {
            $phone_field = "<a href='?mode=cphone'>Установить</a>";
        }
        
        if($user_data['reg_email']) {
            $email_field = "<b>" . html_out($user_data['reg_email']) . "</b> (<a href='?mode=cemail'>Сменить</a>)";
        } else {
            $email_field = "<a href='?mode=cemail'>Установить</a>";
        }
        
        $ret = "<form action='$a' method='post'>
        <input type='hidden' name='mode' value='profile'>
        <input type='hidden' name='opt' value='save'>
        <table border='0' width='700' class='list'>
        <tr><td width=220>Логин</td><td><b>" . html_out($user_data['name']) . "</b></td></tr>
        <tr><td>Пароль</td><td>******** (<a href='?mode=chpwd'>Сменить</a>)</td></tr>
        <tr><td>Двухфакторная аутентификация</td><td>$bifact</td></tr>
        <tr><td>Дата регистрации</td><td>" . html_out($user_data['reg_date']) . "</td></tr>
        <tr><td>E-mail</td><td>$email_field</td></tr>
        <tr><td>E-mail уведомления и рассылки</td><td>
            <label><input type='checkbox' name='esubscribe' value='1' $esubscribe>Присылать</label></td></tr>
        <tr><td>Телефон</td><td>{$phone_field}</td></tr>
        <tr><td>SMS уведомления</td><td><label><input type='checkbox' name='psubscribe' value='1' $psubscribe>Присылать</label></td></tr>
        <tr><td>Jabber ID<td><input type='text' name='jid' value='" . html_out($user_data['jid']) . "'>
        <tr><td>Контактное лицо</td><td><input type='text' name='rname' value='" . html_out($user_data['real_name']) . "'></td></tr>
        <tr><td>Адрес доставки</td><td><textarea name='adres' rows=4 cols=50>" . html_out($user_data['real_address']) . "</textarea></td></tr>

        <tr><td><td><button type='submit'>Сохранить</button>
        </table></form><br>";
        
        if ($user_data['worker']) {
            $ret .= "<table border='0' width='700' class='list'>
                <tr><th colspan='2'>Карточка сотрудника</th></tr>
                <tr><td>ФИО</td><td>" . html_out($user_data['worker_real_name']) . "</td></tr>
                <tr><td>Должность</td><td>" . html_out($user_data['worker_post_name']) . "</td></tr>
                <tr><td>Рабочий email</td><td><a href='mailto:{$user_data['worker_email']}'>{$user_data['worker_email']}</a></td></tr>
                <tr><td>Рабочий телефон</td><td>" . html_out($user_data['worker_phone']) . "</td></tr>
                <tr><td>Рабочий Jabber</td><td>" . html_out($user_data['worker_jid']) . "</td></tr>                
                <tr><td>Рабочий адрес</td><td>" . html_out($user_data['worker_real_address']) . "</td></tr>                    
                <tr><td colspan='2'><b>Для изменения этой информации обратитесь к руководителю</b></td></tr>
                </table><br>";
        }
           
//        <tr><th colspan='2'>Дополнительная контактная информация</td></tr>
//        <tr><td>UIN ICQ:</td><td><input type='text' name='icq' value='" . html_out($user_dopdata['icq']) . "'></td></tr>
//        <tr><td>Skype-login:</td><td><input type='text' name='skype' value='" . html_out($user_dopdata['skype']) . "'></td></tr>
//        <tr><td>Mail-ru ID:</td><td><input type='text' name='mra' value='" . html_out($user_dopdata['mra']) . "'></td></tr>
//        <tr><td>Сайт:</td><td><input type='text' name='site_name' value='" . html_out($user_dopdata['site_name']) . "'></td></tr>
        
        if ( is_array($agent_data) ) {
            $ret .= "<table border='0' width='700' class='list'>
                <tr><th colspan='2'>Аккаунт прикреплён к агенту</th></tr>
                <tr><td>ID агента</td><td>{$agent_data['id']}</td></tr>
                <tr><td>Наименование</td><td>" . html_out($agent_data['fullname']) . "</td></tr>";
            if($agent_data['inn']) {
                $ret .= "<tr><td>ИНН</td><td>" . html_out($agent_data['inn']) . "</td></tr>";
            }
            if($agent_data['tel']) {
                $ret .= "<tr><td>Телефон</td><td>" . html_out($agent_data['tel']) . "</td></tr>";
            }
            if($agent_data['fax_phone']) {
                $ret .= "<tr><td>Факс</td><td>" . html_out($agent_data['fax_phone']) . "</td></tr>";
            }
            if($agent_data['sms_phone']) {
                $ret .= "<tr><td>Телефон для SMS</td><td>" . html_out($agent_data['sms_phone']) . "</td></tr>";
            }
            if($agent_data['adres']) {
                $ret .= "<tr><td>Адрес</td><td>" . html_out($agent_data['adres']) . "</td></tr>";
            }
            $ret .= "<tr><td>Дата последней сверки</td><td>" . html_out($agent_data['data_sverki']) . "</td></tr>
                <tr><td colspan='2'><b>Для изменения этой информации обратитесь в отдел продаж</b></td></tr>
                
                </table>";
        }
        return $ret;
    }
    
    public function getFeedbackForm($token, $fields) {
        $a = $this->getFormAction();
        $ret = "<div id='page-info'>Внимание! Страница является упрощённым интерфейсом к <a href='http://multimag.tndproject.org/newticket' >http://multimag.tndproject.org/newticket</a></div>
	<p class='text'>Заполняя эту форму, вы формируете заказ на доработку сайта от имени Вашей компании в общедоступный реестр заказов, расположенный по адресу <a href='http://multimag.tndproject.org/report/3'>http://multimag.tndproject.org/report/3</a>. Копия Вашего сообщения будет отправлена всем разработчикам и пользователям multimag, подписанных на получение таких сообщений.
	<br>
	Внимательно заполните все поля. Если иное не написано рядом с полем, все поля являются обязательными для заполнения. Особое внимание стоит уделить полю *краткое содержание*.
	<br>
	<b>Для удобства отслеживания исполнения задач (вашего и разработчиков) каждая задача должна быть добавлена отдельно. Нарушение этого условия скорее всего приведёт к тому, что некоторые задачи окажутся незамеченными.</b>
	<br>
	Все задания можно и нужно отслеживать через систему-треккер.
	</p>
	</p>

	<form action='$a' method='post'>
	<input type='hidden' name='token' value='$token'>
	<input type='hidden' name='mode' value='feedback_send'>

	<b>Тип задачи</b> определяет суть задачи и очерёдность её исполнения.
	<ul>
	<li>Тип <u>Дефект</u> используется для информирования разработчиков о неверной работе существующих частей сайта. Такие задачи исполняются в первую очередь.</li>
	<li>Тип <u>Улучшение</u> используйте для задач по доработке существующего функционала сайта</li>
	<li>Тип <u>Задача</u> используется для задач, описывающих новый функционал. Это тип по умолчанию.</li>
	<li>Тип <u>Предложение</u> используете в том случае, если Вам бы хотелось видеть какой-либо функционал на сайте, но Вы не планируете заказывать его разработку в ближайшее время. Используется для отправки идей по доработке разработчикам и другим пользователям программы.</li>
	</ul>
	<i><u>Пример</u>: Задача</i><br>
	<select name='field_type'>{$fields['field_type']}</select><br><br>

	<b>Краткое содержание</b>. Тема задачи. Максимально кратко (3-8 слов) и ёмко изложите суть поставленной задачи. Максимум 64 символа.<br>
	<i><u>Пример</u>: Реализовать печатную форму: Приходный кассовый ордер</i><br>
	<input type='text' maxlength='64' name='field_summary' style='width:90%'><br><br>

	<b>Подробное описание</b>. Максимально подробно изложите суть задачи. Описание должно являться дополнением краткого содержания. Не допускается писать несколько задач. Можно использовать wiki разметку для форматирвания.<br>
	<i><u>Пример</u>: Форма должна быть доступна в документе *приходный кассовый ордер*, должна быть в PDF формате, и соответствовать общепринятой форме КО-1</i><br>
	<textarea name='field_description' rows='7' cols='80'></textarea><br><br>

	<b>Компонент приложения</b>. Выбирается исходя из того, к какой части сайта относится ваша задача. Если задача относится к вашим индивидуальным модификациям - выбирайте *пользовательский дизайн*<br>
	<i><u>Пример</u>: Документы</i><br>
	<select name='field_component'>{$fields['field_component']}</select><br><br>

	<b>Приоритет</b> определяет то, насколько срочно требуется выполнить поставленную задачу. Критический приоритет допустимо указывать только для задач с типом *дефект*<br>
	<i><u>Пример</u>: Обычный</i><br>
	<select name='field_priority'>{$fields['field_priority']}</select><br><br>

	<b>Целевая версия</b> нужна, чтобы указать, в какой версии программы вы хотели бы видеть реализацию этой задачи. Вы можете отложить реализацию, указав более позднюю версию. Нет смысла выбирать более раннюю версию, т.к. приём задач в неё закрыт. В случае, если задача не соответствует целям версии, разработчики могут изменить этот параметр.<br>
	<i><u>Пример</u>: 0.9</i><br>
	<select name='field_milestone'>{$fields['field_milestone']}</select><br><br>

	<button type='submit'>Опубликовать задачу</button>
	</form>";
        
        return $ret;
    }

    /// Попытка смены пароля
    public function tryChangePassword() {
        global $tmpl, $db;
        $step = request('step');
        $tmpl->setTitle("Смена пароля");  
        $tmpl->setContent("<h1>Смена пароля</h1>");
        $tmpl->addBreadcrumb('Мой профиль', '/user.php?mode=profile');
        $tmpl->addBreadcrumb('Смена пароля', '');
        if(!$step) {
            $tmpl->addContent( $this->getChPwdForm() );
        } else {
            $oldpass = request('oldpass');
            $newpass = request('newpass');
            $confirmpass = request('confirmpass');
            $auth = new \authenticator();
            $db->startTransaction();
            if( !$auth->loadDataForID($_SESSION['uid']) ) {
                throw new \Exception('Ошибка загрузки данных профиля');
            }
            if(!$oldpass || !$newpass || !$confirmpass) {
                $tmpl->errorMessage("Одно из полей не заполнено!");
                $tmpl->addContent( $this->getChPwdForm() );
            }
            elseif($newpass != $confirmpass) {
                $tmpl->errorMessage("Новый пароль и подтверждение не совпадают");
                $tmpl->addContent( $this->getChPwdForm() );
            } 
            elseif ( strlen($newpass)<8 ) {
                $tmpl->errorMessage("Пароль слишком короткий");
                $tmpl->addContent($this->getChPwdForm());
            }
            elseif (!$auth->isCorrectPassword($newpass) ) {
                $tmpl->errorMessage("Пароль содержит недопустимые символы (вероятно, русские буквы)");
                $tmpl->addContent($this->getChPwdForm());
            }
            elseif (!$auth->testPassword($oldpass)) {
                $tmpl->errorMessage("Текущий пароль не верен");
                $tmpl->addContent($this->getChPwdForm());
            } 
            else {
                $auth->setPassword($newpass);
                $tmpl->msg("Пароль успешно изменён! Не забудьте его!", "ok");
                $db->commit();
            }
        }
    }
    
    public function tryShowProfile() {
        global $tmpl, $db;
        $opt = request('opt');
        $uid = intval($_SESSION['uid']);
        $tmpl->setContent("<h1>Мой профиль</h1>");
        $tmpl->setTitle("Мой профиль");
        $tmpl->addBreadcrumb('Мой профиль', '');
        
        if ($opt == 'save') {
            $data = array(
                'real_name' => request('rname'),
                'reg_email_subscribe' => rcvint('esubscribe'),
                'reg_phone_subscribe' => rcvint('psubscribe'),
                'real_address' => request('adres'),
                'jid' => request('jid'));
            $db->updateA('users', $uid, $data);
            $data = requestA(array('icq', 'skype', 'mra', 'site_name'));
            $db->replaceKA('users_data', 'uid', $uid, $data);
            $tmpl->msg("Данные обновлены!", "ok");
        }

        $auth = new \authenticator();
        $auth->loadDataForID($_SESSION['uid']);
        $user_data = $auth->getUserInfo();

        if ($user_data['agent_id']) {
            $adata = $db->selectRowA('doc_agent', $user_data['agent_id'], array('id', 'name', 'fullname', 'inn', 'tel', 'fax_phone', 
                'sms_phone', 'adres', 'data_sverki'));
        } else {
            $adata = false;
        }

        $tmpl->addContent( $this->getUserProfileForm($user_data, $adata) );
    }
    
    public function tryChangeEmail() {
        global $tmpl;
        $tmpl->addBreadcrumb('Мой профиль', '/user.php?mode=profile');        
        
        $auth = new \authenticator();
        if( !$auth->loadDataForID( $_SESSION['uid'] ) ) {
            throw new \Exception("Ошибка загрузки профиля пользователя.");
        }
        if($auth->isConfirmed() && ( !$auth->getRegPhone() || $auth->isNeedConfirmPhone()) ) {
            throw new \Exception("Для смены email адреса сначала установите и подтвердите номер телефона.");
        } 
        $user_info = $auth->getUserInfo();
        $login = new \Modules\Site\login();
        $page_name = $user_info['reg_email'] ? 'Смена email адреса' : 'Установка email адреса';
        $tmpl->setTitle($page_name);  
        $tmpl->setContent("<h1>$page_name</h1>");
        $tmpl->addBreadcrumb($page_name, '');
        $tmpl->addContent( $login->getUpdateEmailForm($user_info['name'], $user_info['reg_email']) );
    }
    
    public function tryChangePhone() {
        global $tmpl;        
        $tmpl->addBreadcrumb('Мой профиль', '/user.php?mode=profile');        
        
        $auth = new \authenticator();
        if( !$auth->loadDataForID( $_SESSION['uid'] ) ) {
            throw new \Exception("Ошибка загрузки профиля пользователя.");
        }
        if($auth->isConfirmed() && ( !$auth->getRegEmail() || $auth->isNeedConfirmEmail()) ) {
            throw new \Exception("Для смены номера телефона сначала установите и подтвердите адрес email.");
        } 
        $user_info = $auth->getUserInfo();
        $login = new \Modules\Site\login();
        $page_name = $user_info['reg_phone'] ? 'Смена номера телефона' : 'Установка номера телефона';
        $tmpl->setTitle($page_name);  
        $tmpl->setContent("<h1>$page_name</h1>");
        $tmpl->addBreadcrumb($page_name, '');
        $tmpl->addContent( $login->getUpdatePhoneForm($user_info['name'], $user_info['reg_phone']) );
    }

    /// Получить список документов авторства текущего пользователя, либо выписанных на прикреплённого к нему агента
    public function getDocListForThisUser() {
        global $tmpl, $db;
        $tmpl->addBreadcrumb('Мои документы', '');
        $auth = new \authenticator();
        $auth->loadDataForID($_SESSION['uid']);
        $user_info = $auth->getUserInfo();
        $tmpl->setContent("<h1>Мои документы</h1>
        <p>В таблице находятся документы, которые создали Вы, либо выписанные на прикреплённого к Вам агента</p>
	<div class='content'>
	<table width='100%' class='list'>
	<tr class='title'><th>ID</th><th>Номер</th><th>Дата</th><th>Документ</th><th>Сумма</th><th>Агент</th><th>Подтверждён ?</th></tr>");
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_types`.`name`, `doc_list`.`ok`, `doc_list`.`sum`, `doc_list`.`type`, 
            `doc_agent`.`fullname` AS `agent_fullname`, `doc_list`.`altnum`
            FROM `doc_list`
            LEFT JOIN `doc_types` ON `doc_types`.`id` = `doc_list`.`type`
            LEFT JOIN `doc_agent` ON `doc_agent`.`id` = `doc_list`.`agent`
            WHERE (`doc_list`.`user`='{$_SESSION['uid']}' OR `doc_list`.`agent`='{$user_info['agent_id']}') AND `doc_list`.`agent`!=0 
            ORDER BY `date` DESC");
        while ($nxt = $res->fetch_assoc()) {
            $date = date("Y-m-d", $nxt['date']);
            $ok = $nxt['ok'] ? 'Да ('.date("Y-m-d", $nxt['ok']).')' : 'Нет';
            $lnum = $nxt['id'];
            if ($nxt['type'] == 2 || $nxt['type'] == 3) {
                $lnum = "<a href='{$this->link_prefix}?mode=get_doc&amp;doc={$nxt['id']}'>{$nxt['id']}</a>";
            }
            $tmpl->addContent("<tr><td align='right'>$lnum</td><td align='right'>{$nxt['altnum']}</td><td align='center'>$date</td><td>" . html_out($nxt['name']) . "</td><td align='right'>{$nxt['sum']}</td><td align='center'>" . html_out($nxt['agent_fullname']) . "</td><td>$ok</td></tr>");
        }
        $tmpl->addContent("</table></div>");
    }

    public function getDocPdf() {
        global $db, $CONFIG;
        include_once("include/doc.core.php");
        include_once("include/doc.nulltype.php");
        $doc = rcvint('doc');
        if ($doc) {
            $auth = new \authenticator();
            $auth->loadDataForID($_SESSION['uid']);
            $user_info = $auth->getUserInfo();
            
            $document = AutoDocument($doc);
            $doc_data = $document->getDocDataA();
            if($doc_data['user']!=$user_info['id'] && $doc_data['agent']!=$user_info['agent_id']) {
                throw new \NotFoundException("Документ не найден");
            }
            if ($doc_data['type'] == 3) {
                $document->PrintForm('int:schet');
            } else if ($doc_data['type'] == 2) {
                $document->PrintForm('int:nak');
            } else {
                throw new \Exception("Способ просмотра не задан!");
            }
        } else {
            throw new \NotFoundException("Документ не указан");
        }
    }
    
    public function viewErrorLog() {
        global $tmpl, $db, $CONFIG;
        if (!isAccess('log_error', 'view')) {
            throw new \AccessException();
        }
        $id = rcvint('id');
        if($id) {
            $tmpl->setContent("<h1>Детализация ошибки $id</h1>");
            $tmpl->addBreadcrumb('Журнал ошибок', $this->link_prefix.'?mode=elog');
            $tmpl->addBreadcrumb('Детализация ошибки '.$id, '');
            $line = $db->selectRow('errorlog', $id);
            $line['trace'] = str_replace("\n", '</li><li>', html_out($line['trace']));
            $pref_len = strlen($CONFIG['location']);
            $fname = substr($line['file'], $pref_len);
            $link = 'http://multimag.tndproject.org/browser/trunk'.$fname.'?rev='.MULTIMAG_REV.'#L'.$line['line'];
            $tmpl->addContent("<ui class='items'>"
                . "<li>id: {$line['id']}</li>"
                . "<li>Сообщение: ".html_out($line['msg'])."</li>"
                . "<li>Класс: ".html_out($line['class'])."</li>"
                . "<li>Кoд: ".html_out($line['code'])."</li>"
                . "<li>Файл: <a href='$link'>".html_out($line['file'])."</a></li>"
                . "<li>Строка: ".html_out($line['line'])."</li>"
                . "<li>Страница: ".html_out($line['page'])."</li>"
                . "<li>Ссылка: ".html_out($line['referer'])."</li>"
                . "<li>Дата: ".html_out($line['date'])."</li>"
                . "<li>IP: ".html_out($line['ip'])."</li>"
                . "<li>Броузер: ".html_out($line['useragent'])."</li>"
                . "<li>ID пользователя: ".html_out($line['uid'])."</li>"
                . "<li>Стек:<ul><li>".$line['trace']."</li></ul></li>"
                . "</ul>");
        }
        else {
            $tmpl->addBreadcrumb('Журнал ошибок', '');
            $p = rcvint('p', 1);
            if ($p <= 0) {
                $p = 1;
            }
            $lines = 250;
            $from=($p-1)*$lines;
            $tmpl->setContent("<h1>Журнал ошибок</h1>");
            $res = $db->query("SELECT SQL_CALC_FOUND_ROWS `id`, `class`, `page`, `referer`, `code`, `msg`, `file`, `line`, `date`, `ip`, `useragent`, `uid` "
                . "FROM `errorlog` "
                . "ORDER BY `id` DESC LIMIT $from, $lines");
            $fr = $db->query('SELECT FOUND_ROWS()');
            list($total) = $fr->fetch_row();
            $tmpl->addContent("<table width='100%' class='list'>
            <tr><th>Дата</th><th>Класс</th><th>Код</th><th>Ошибка</th><th>Файл:строка</th><th>Страница</th><th>ID</th></tr>");
            $i=0;
            while($line = $res->fetch_assoc()) {
                $line['date'] = str_replace(' ', '&nbsp', html_out($line['date']));
                $tmpl->addContent('<tr>'
                . '<td>'.$line['date'].'</td>'
                . '<td>'.html_out($line['class']).'</td>'
                . '<td>'.$line['code'].'</td>'
                . '<td>'.html_out($line['msg']).'</td>'
                . '<td>'.html_out(basename($line['file'])).':'.$line['line'].'</td>'
                . '<td>'.html_out($line['page']).'</td>'
                . '<td><a href="/user.php?mode=elog&amp;id='.$line['id'].'">'.$line['id'].'</a></td>'
                . '</tr>');
            }
            $tmpl->addContent('</table>');

            $pages_count = ceil($total/$lines);
            if ($pages_count > 1) {
                $tmpl->addContent('<p>Страницы: ');
                for ($i = 1; $i <= $pages_count; ++$i) {
                    if ($i == $p) {
                        $tmpl->addContent("<b>$i</b> ");
                    } else {
                        $tmpl->addContent("<a href='?mode=elog&amp;p=$i'>$i</a> ");
                    }
                }
                $tmpl->addContent("</p>");
            }
        }
    }

    // Отобразить журнал запрошенных звонков
    public function viewCallRequestLog() {
        global $tmpl, $db;
        if (!isAccess('log_call_request', 'view')) {
            throw new AccessException();
        }
        $tmpl->addBreadcrumb('Журнал запрошенных звонков', '');
        $tmpl->setContent("<h1>Журнал запрошенных звонков</h1>
	<div class='content'>
	<table width='100%' class='list' cellspacing='0'>
	<tr><th>Дата запроса</th><th>Кому звонить?</th><th>Куда звонить?</th><th>Когда звонить?</th><th>IP</th></tr>");
	$res=$db->query("SELECT `id`, `request_date`, `name`, `phone`, `call_date`, `ip` FROM `log_call_requests` ORDER BY `request_date` DESC");
	while ($line = $res->fetch_assoc()) {
            $tmpl->addContent("<tr><td>" . html_out($line['request_date']) . "</td><td>" . html_out($line['name']) . "</td><td>" . html_out($line['phone']) . 
                "</td><td>" . html_out($line['call_date']) . "</td><td>{$line['ip']}</td></tr>");
        }
        $tmpl->addContent("</table></div>");
    }

    
    public function viewFeedbackForm() {
        global $tmpl, $CONFIG;
        if (!$CONFIG['site']['trackticket_login']) {
            throw new \Exception("Конфигурация модуля обратной связи не заполнена!");
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://multimag.tndproject.org/login");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $CONFIG['site']['trackticket_login'] . ':' . $CONFIG['site']['trackticket_pass']);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $res = false;
        $data = curl_exec($ch);
        $header = substr($data, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        //$body=substr($data,curl_getinfo($ch,CURLINFO_HEADER_SIZE));
        preg_match_all("/Set-Cookie: (.*?)=(.*?);/i", $header, $res);
        $cookie = '';
        foreach ($res[1] as $key => $value) {
            $cookie .= $value . '=' . $res[2][$key] . '; ';
        }

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        curl_setopt($ch, CURLOPT_URL, "http://multimag.tndproject.org/newticket");
        $output = curl_exec($ch);
        curl_close($ch);

        $_SESSION['trac_cookie'] = $cookie;

        $doc = new \DOMDocument('1.0', 'UTF8');
        @$doc->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $output);
        $doc->normalizeDocument();

        $form = $doc->getElementById('propertyform');
        if (!$form) {
            throw new Exception("Не удалость получить форму треккера!");
        }

        $form_inputs = $form->getElementsByTagName('input');
        $token = '';
        foreach ($form_inputs as $input) {
            $input_name = $input->attributes->getNamedItem('name');
            $input_name = $input_name ? $input_name->nodeValue : '';
            if ($input_name == '__FORM_TOKEN') {
                $input_value = $input->attributes->getNamedItem('value');
                $input_value = $input_value ? $input_value->nodeValue : '';
                $token = $input_value;
                break;
            }
        }
        $form_selects = $form->getElementsByTagName('select');
        $selects = array();
        $selects_html = array();
        foreach ($form_selects as $select) {
            $select_name = $select->attributes->getNamedItem('name');
            $select_name = $select_name ? $select_name->nodeValue : '';
            $selects[$select_name] = array();
            $select_options = $select->getElementsByTagName('option');
            $selects_html[$select_name] = '';

            foreach ($select_options as $option) {
                if ($option->nodeValue == 'Ядро')
                    continue;
                $selected = $option->attributes->getNamedItem('selected');
                $selected = $selected ? ' selected' : '';
                $selects[$select_name][] = $option->nodeValue;
                $selects_html[$select_name].='<option' . $selected . '>' . $option->nodeValue . '</option>';
            }
        }

        $tmpl->setTitle("Запрос на доработку программы");
        $tmpl->addBreadcrumb('Запрос на доработку программы', '');
        $tmpl->setContent("<h1>Запрос на доработку программы</h1>");
        $tmpl->addContent( $this->getFeedbackForm($token, $selects_html) );
    }
    
    public function sendFeedback() {
        global $tmpl, $CONFIG;
        $fields = array(
            '__FORM_TOKEN' => $_POST['token'],
            'field_type' => $_POST['field_type'],
            'field_summary' => $_POST['field_summary'],
            'field_description' => $_POST['field_description'] . "\nUser: {$_SESSION['name']} at {$_SERVER['HTTP_HOST']} ({$CONFIG['site']['name']})",
            'field_component' => $_POST['field_component'],
            'field_priority' => $_POST['field_priority'],
            'field_milestone' => $_POST['field_milestone'],
            'field_reporter' => $CONFIG['site']['trackticket_login'],
            'field_cc' => $_SESSION['name'] . '@' . $CONFIG['site']['name'],
            'submit' => 'submit'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://multimag.tndproject.org/newticket");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_COOKIE, $_SESSION['trac_cookie'] . ' trac_form_token=' . $_POST['token']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);


        $data = curl_exec($ch);
        $header = substr($data, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        //$body = substr($data, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        curl_close($ch);

        $ticket = 0;
        $ticket_url = '';
        $hlines = explode("\n", $header);
        foreach ($hlines as $line) {
            $line = trim($line);
            if (strpos($line, 'Location') === 0) {
                $chunks = explode(": ", $line);
                $ticket_url = trim($chunks[1]);
                $chunks = explode("/", $ticket_url);
                $ticket = $chunks[count($chunks) - 1];
                settype($ticket, 'int');
                break;
            }
        }
        $tmpl->setTitle("Отправка запроса");
        $tmpl->addBreadcrumb('Запрос на доработку программы', $this->link_prefix.'?mode=feedback');
        $tmpl->addBreadcrumb('Отправка запроса', '');
        $tmpl->setContent("<h1>Отправка запроса</h1>");
        $tmpl->addContent("<div id='page-info'>Внимание! Страница является упрощённым интерфейсом к <a href='http://multimag.tndproject.org/newticket' >http://multimag.tndproject.org/newticket</a></div>");
        if ($ticket) {
            $tmpl->msg("Номер задачи: <b>$ticket</b>.<br>Посмотресть созданную задачу, а так же следить за ходом её выполнения, можно по ссылке: <a href='$ticket_url'>$ticket_url</a>", "ok", "Задача успешно внесена в реестр!");
            $tmpl->addContent("<iframe width='100%' height='70%' src='$ticket_url'></iframe>");
        } else {
            $tmpl->msg("Не удалось создать задачу! Сообщите о проблеме своему системному администратору!", "err");
        }
    }
        
    /// Сормировать строку - URL цели форм
    /// @return url формы регистрации
    protected function getFormAction() {
        global $CONFIG;
        $form_action = $this->link_prefix;
        if ($CONFIG['site']['force_https_login']) {
            $host = $_SERVER['HTTP_HOST'];
            $form_action = 'https://' . $host . $this->link_prefix;
        }
        return $form_action;
    }
}