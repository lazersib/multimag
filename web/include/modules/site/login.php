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

/// Класс модуля регистрации и аутентификации
class login extends \IModule {

    public function __construct() {
        parent::__construct();
        $this->link_prefix = '/login.php';
    }
    
    // Получить название модуля
    /// @return Строка с именем
    public function getName() {
        return 'Регистрация и аутентификация';
    }
    
    /// Получить описание модуля
    /// @return Строка с описанием
    public function getDescription() {
        return 'Регистрация и аутентификация';  
    }
    
    /// Запустить модуль на исполнение
    public function run() {
        global $tmpl;
        $tmpl->setTitle("Логин");
        $mode = request('mode');
        if($mode == '') {
            $tmpl->setContent("<h1>Аутентификация</h1>");
            $tmpl->setTitle("Аутентификация");
            $this->tryLogin();
        } 
        else if ($mode == 'logout') {
            $this->logout();
        } 
        else if ($mode == 'reg') {
            $tmpl->setTitle("Регистрация");  
            $tmpl->setContent("<h1>Регистрация</h1>");
            if (!intval(@$_SESSION['uid'])) {
                $tmpl->addContent( $this->getRegisterForm(request('login'), request('email'), request('phone')) );            
            } else {
                $tmpl->msg("Вы уже являетесь нашим зарегистрированным пользователем. Повторная регистрация не требуется.", "info");
            }
        } 
        else if ($mode == 'regs') {
            $tmpl->setContent("<h1>Регистрация</h1>");
            $tmpl->setTitle("Регистрация");
            $tmpl->addContent( $this->tryRegister() );
        }
        else if ($mode == 'conf') {
            $tmpl->setContent("<h1>Завершение регистрации</h1>");
            $tmpl->setTitle("Завершение регистрации");
            $this->tryConfirm();
        } 
        else if ($mode == 'rem') {
            $tmpl->setContent("<h1>Восстановление доступа</h1>");
            $tmpl->setTitle("Восстановление доступа");
            switch(rcvint('step')) {
                case 0:
                    $tmpl->addContent( $this->getPassRecoveryForm() );
                    break;
                case 1:
                    $this->tryPassRecoveryStep1();
                    break;
                case 2:
                    $this->tryPassRecoveryStep2();            
                    break;
                case 3:
                    $this->tryPassRecoveryStep3(); 
                    break;
                case 4:
                    $this->tryPassRecoveryStep4(); 
                    break;
                default:
                    throw new NotFoundException("Неверный шаг");
            }
        }
        else if($mode == 'unsubscribe') {
            $tmpl->setContent("<h1>Отказ от рассылки</h1>");
            $tmpl->setTitle("Отказ от рассылки");
            $this->unsubscribeEmail(request('email'), request('from'));
        } 
        elseif($mode == 'cphone') {
            $this->tryChangePhone();
        }
        elseif($mode == 'cemail') {
            $this->tryChangeEmail();
        }
        else {
            throw new \NotFoundException("Неверная опция");
        }
    }
    
    /// Сормировать строку - URL цели форм регистрации и аутентификации
    /// @return url формы регистрации
    protected function getFormAction() {
        global $CONFIG;
        $form_action = '/login.php';
        if ($CONFIG['site']['force_https_login']) {
            $host = $_SERVER['HTTP_HOST'];
            $form_action = 'https://' . $host . '/login.php';
        }
        return $form_action;
    }
    
    /// Получить вступительный текст формы регистрации
    public function getRegisterBrief() {
        return "<p>Для использования всех возможностей этого сайта, пройдите процедуру регистрации. Регистрация не сложная, и займёт всего пару минут. После регистрации, Вам станут доступны специальные цены.</p><p>Регистрируясь, Вы даёте согласие на хранение, обработку и публикацию своей персональной информации, в соответствии с законом РФ ФЗ-152 &quot;О персональных данных&quot;.</p>";
    }

    /// Сформировать регистрационную форму
    /// @param $login Логин пользователя
    /// @param $email email пользователя
    /// @param $phone Номер телефона пользователя
    /// @param $subs_flag Флаг подписки
    /// @param $errors Массив с информацией об ошибках, которую нужно отобразить в форме или false
    /// @return HTML-представление формы регистрации
    function getRegisterForm($login, $email, $phone, $subs_flag = true, $errors = false) {
        global $CONFIG;
        
        $err_msgs = array('login' => '', 'email' => '', 'captcha' => '', 'phone' => '');
        if($errors) {
            $err_msgs = array_merge($err_msgs, $errors);
        }
        
        $form_action = $this->getFormAction();
        $ret = $this->getRegisterBrief();
        $ret .= "<form action='$form_action' method='post' id='reg-form' autocomplete='off'>
        <input type='hidden' name='mode' value='regs'>
	<h2>Регистрационные данные</h2>
	<table cellspacing='15'>
	<tr><td>
	<b>Желаемый логин</b><br>
	<small>латинские буквы, цифры, от 3 до 24 символов</small></td>
	<td>
	<input type='text' name='login' value='".html_out($login)."' id='login' autofocus><br>
	<span id='login_valid' style='color: #c00'>{$err_msgs['login']}</span></td></tr>";

        if (@$CONFIG['site']['allow_phone_regist']) {
            $ret .= "<tr><td colspan='2'>Заполните хотя бы одно из полей: номер телефона и e-mail</td></tr>";
        }

        $ret .= "<tr><td>
	<b>Адрес электронной почты e-mail</b><br>
	<small>в формате user@example.com</small></td>
	<td><input type='text' name='email' value='".html_out($email)."' id='email'><br>
	<span id='email_valid' style='color: #c00'>{$err_msgs['email']}</span></td></tr>";

        if (@$CONFIG['site']['allow_phone_regist']) {
            $ret .= "<tr><td><b>Мобильный телефон: <span id='phone_num'></span></b><br>
            <small>Российский, без +7 или 8</small></td>
            <td><input type='text' name='phone' value='".html_out($phone)."' maxlength='12' placeholder='Номер' id='phone'><br>
            <span id='phone_valid' style='color: #c00'>{$err_msgs['phone']}</span>";
        }
        $subs_check = $subs_flag?' checked':'';
        $ret .= "<tr><td colspan='2'><input type='checkbox' name='subs' value='1'{$subs_check}>Подписаться на новости и другую информацию</td></tr>
	<tr><td colspan='2'><b>Подтвердите что вы не робот, введя текст с картинки:</b></td></tr>
	<tr><td><img src='/kcaptcha/index.php'></td>
	<td><input type='text' name='img'><br><span id='captcha_valid' style='color: #c00'>{$err_msgs['captcha']}</span></td></tr>
	<tr><td style='color: #c00;'><td>
	<button type='submit'>Далее &gt;&gt;</button>
	</form></table>";
        if (@$CONFIG['site']['allow_openid']) {
            $ret .= "<b>Примечание:</b> Если Вы хоте зарегистрироваться, используя свой OpenID, Вам <a href='/login_oid.php'>сюда</a>!<br>";
        }
        $ret .= "<script type='text/javascript'>";

        if (@$CONFIG['site']['allow_phone_regist']) {
            $ret .= "var p=document.getElementById('phone');p.onkeyup = function () {var pn=document.getElementById('phone_num');var pv=document.getElementById('phone_valid');pn.innerHTML='+7'+p.value;var regexp=/^9\d{9}$/;if(!regexp.test(p.value)) {p.style.borderColor=\"#f00\";p.style.color=\"#f00\";pv.innerHTML='';} else {p.style.borderColor=\"\";p.style.color=\"\";pv.innerHTML='Введено верно';pv.style.color=\"#0c0\";}};";
        }

        $ret .= "var e=document.getElementById('email');e.onkeyup=function(){var ev=document.getElementById('email_valid');var regexp=/^\w+([+-\.\w]+)*\w@\w(([-\.\w])*\w+)*\.\w{2,8}$/;if(!regexp.test(e.value)){e.style.borderColor=\"#f00\";e.style.color=\"#f00\";ev.innerHTML='';}else{e.style.borderColor=\"\";e.style.color=\"\";ev.innerHTML='Введено верно';ev.style.color=\"#0c0\";}};
        var l=document.getElementById('login');l.onkeyup=function(){var lv=document.getElementById('login_valid');var regexp=/^[a-zA-Z\d]{3,24}$/;if(!regexp.test(l.value)){l.style.borderColor=\"#f00\";l.style.color=\"#f00\";lv.innerHTML='Заполнено неверно';}else{l.style.borderColor=\"\";l.style.color=\"\";lv.innerHTML='';}};        </script>";
        return $ret;
    }
    
    /// Сформировать HTML код формы подтверждения
    /// @param $login Логин пользователя
    /// @param $bad_email_key_flag отображать ли информацию о некореектном коде подтверждения почты
    /// @param $bad_phone_key_flag отображать ли информацию о некореектном коде подтверждения номера телефона
    public function getConfirmForm($login, $bad_email_key_flag = false, $bad_phone_key_flag = false) {
        $auth = new \authenticator();
        if( !$auth->loadDataForLogin($login) ) {
            throw new \Exception("Пользователь не найден в базе");
        }
        
        $ret = "<form action='/login.php' method='post' class='confirm_form' autocomplete='off'>
            <h3>Подтверждение регистрационных данных</h3>
            <input type='hidden' name='mode' value='conf'>
            <input type='hidden' name='login' value='".html_out($login)."'>
            <table>
            <tr style='display: none'><td></td><td><button type='submit' name='submit' value='submit'>Продолжить</button></td>";
        if($auth->isNeedConfirmEmail()) {
            $ret .= "<tr><td colspan=2>Для проверки, что адрес электронной почты <b>".html_out($auth->getRegEmail())."</b> 
                принадлежит Вам, на него было выслано сообщение.</td></tr>
                <tr><td>Введите код, полученный по email:</td>
                <td><input type='text' name='e'></td></tr>";
            if($bad_email_key_flag) {
                $ret .= "<tr><td></td><td><b style='color: #f00;'>Код неверен или устарел.</b></td></tr>";
            }
            $ret .="<tr><td>Cообщения email обычно приходят в течение 30 минут.</td>                
                <td><button type='submit' name='submit' value='resend_email' class='small'>Повторить сообщение с кодом</button>
                <button type='submit' name='submit' value='cemail' class='small'>Сменить email</button>
                </td></tr>";
        }
        if($auth->isNeedConfirmPhone()) {
            $ret .= "<tr><td colspan=2>Для проверки, что номер телефона <b>".html_out($auth->getRegPhone())."</b> 
                принадлежит Вам, на него было выслано сообщение.</td></tr>
                <tr><td>Введите код, полученный по SMS:</td>
                <td><input type='text' name='p'></td></tr>";
            if($bad_phone_key_flag) {
                $ret .= "<tr><td></td><td><b style='color: #f00;'>Код неверен или устарел.</b></td></tr>";
            }
            $ret .="<tr><td>SMS сообщения обычно приходят в течение 5 минут.</td>                
                <td><button type='submit' name='submit' value='resend_sms' class='small'>Повторить SMS с кодом</button>
                <button type='submit' name='submit' value='cphone' class='small'>Сменить номер</button>
                </td></tr>";
        }
        $ret .= "<tr><td></td><td><button type='submit' name='submit' value='submit'>Продолжить</button></td>
            </table></form>";
        return $ret;
    }
    
    /// Сформировать HTML код формы аутентификации
    /// @param $login Логин пользователя
    /// @param $need_captcha нужно ли выводить картинку с кодом подтверждения
    public function getLoginForm($login = '', $need_captcha = false) {
        global $CONFIG;
        $login_html = html_out($login);
        $form_action = $this->getFormAction();
        $ret = "<form method='post' action='$form_action' id='login-form'>
        <input type='hidden' name='opt' value='login'>
        <table id='login-table'>
        <tr><th colspan='2'>Введите данные:</th></tr>
        <tr><td colspan='2'>Если у Вас их нет, вы можете <a class='wiki' href='/login.php?mode=reg'>зарегистрироваться</a></th></tr>
        <tr><td>Логин</td><td><input type='text' name='login' class='text' id='input_name' value='$login_html' autofocus></td></tr>
        <tr><td>Пароль</td><td><input type='password' name='pass' class='text' autocomplete='off'></td></tr>";
        if($need_captcha) {
            $ret .= "<tr><td>Введите код подтверждения, изображенный на картинке:</td>            
            <td><img src='/kcaptcha/index.php' alt='Включите отображение картинок!'><br>
            <input type='text' name='captcha' class='text' autocomplete='off'></td></tr>";
        }
        $ret .= "<tr><td>&nbsp;</td>
        <td><button type='submit'>Вход!</button> ( <a class='wiki' href='/login.php?mode=rem'>Забыли пароль?</a> )</td></tr>
        </table></form>";
        if(@$CONFIG['site']['allow_openid']) {
            $ret .= "
            <table style='width: 800px'>
            <tr><th colspan='4'><center>Войти через</center></th></tr>
            <tr>
            <td><a href='/login_oid.php?oid=https://www.google.com/accounts/o8/id'><img src='/img/oid/google.png' alt='Войти через Google'></a></td>
            <td><a href='/login_oid.php?oid=http://openid.yandex.ru/'><img src='/img/oid/yandex.png' alt='Войти через Яндекс'></a></td>
            <td><a href='/login_oid.php?oid=vkontakteid.ru'><img src='/img/oid/vkontakte.png' alt='Войти через Вконтакте'></a></td>
            <td><a href='/login_oid.php?oid=loginza.ru'><img src='/img/oid/loginza.png' alt='Войти через Loginza'></a></td>
            </tr>
            </table>";
        }
        return $ret;
    }
    
    /// Сформировать HTML код формы восстановления забытого пароля
    public function getPassRecoveryForm() {
        $form_action = $this->getFormAction();
        $ret = "<form method='post' action='$form_action' autocomplete='off'>
        <p>Для начала процедуры смены пароля введите <b>логин</b> на сайте, номер телефона, или адрес электронной почты, указанный при регистрации:</p>
        <input type='hidden' name='mode' value='rem'>
        <input type='hidden' name='step' value='1'>
        <input type='text' name='login' autocomplete='off' autofocus='yes'><br>
        Подтвердите, что вы не робот, введите текст с картинки:<br>
        <img src='/kcaptcha/index.php'><br>
        <input type='text' name='captcha' autocomplete='off'><br>
        <button type='submit'>Далее</button>
        </form>";
        return $ret;
    }
    
    /// Сформировать HTML код формы выбора вариантов восстановления забытого пароля
    /// @param $session_key Сессионный ключ 
    /// @param $email       Адрес электронной почты
    /// @param $phone       Номер телефона
    /// @param $openid_list Массив с openid идентификаторами
    public function getPassRecoveryTypesForm($session_key, $email, $phone, $openid_list) {
        $form_action = $this->getFormAction();
        $ret = "<form action='$form_action' method='post'>
        <input type='hidden' name='mode' value='rem'>
        <input type='hidden' name='step' value='2'>
        <input type='hidden' name='key' value='$session_key'>
        <fieldset><legend>Восстановить доступ при помощи</legend>";
        if($email) {
            $m_email = html_out($this->maskEmail($email));
            $ret .= "<label><input type='radio' name='method' value='email'> Сообщения на email $m_email</label><br>";
        }
        if($phone) {
            $m_phone = html_out( $this->maskPhone($phone) );
            $ret .= "<label><input type='radio' name='method' value='sms'> SMS на мобильный телефон $m_phone</label><br>";
        }
        if(is_array($openid_list)) {
            foreach($openid_list as $oid) {
                $oid = html_out($oid);
                $ret .= "<label><input type='radio' name='method' value='$oid'> OpenID аккаунта $oid</label><br>";
            }
        }
        $ret .= "</fieldset><br><button type='submit'>Далее</button></form>";
        return $ret;
    }
    
    /// Сформировать HTML код формы ввода кода при восстановлении забытого пароля
    public function getPassRecoveryKeyForm($session_key) {
        $form_action = $this->getFormAction();
        return "<form action='$form_action' method='post'>
        <input type='hidden' name='mode' value='rem'>
        <input type='hidden' name='step' value='3'>
        <input type='hidden' name='key' value='$session_key'>
        Введите полученный код:<br>
        <input type='text' name='s' autofocus='yes' autocomplete='off'><br>
        <br><button type='submit'>Далее</button>
        </form>";
    }
    
    /// Сформировать HTML код формы ввода нового пароля (при восстановлении забытого)
    public function getNewPassRecoveryForm($session_key) {
        $form_action = $this->getFormAction();
        return "<form action='$form_action' method='post'>
        <input type='hidden' name='mode' value='rem'>
        <input type='hidden' name='step' value='4'>
        <input type='hidden' name='key' value='$session_key'>
        Новый пароль:<br>
        <small>От 8 латинских алфавитно-цифровых символов</small><br>
        <input type='password' name='newpass' id='pass_field' autofocus='yes'><br><b id='pass_info'></b><br>
        Повторите новый пароль:<br>
        <input type='password' name='newpass2'><br>
        <br><button type='submit'>Сменить пароль</button>
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
    }
    
    /// Получить HTML код формы смены номера телефона
    public function getUpdatePhoneForm($login, $phone='') {
        $form_action = $this->getFormAction();
        $ret = "<form action='$form_action' method='post'>
            <input type='hidden' name='mode' value='cphone'>
            <input type='hidden' name='login' value='".html_out($login)."'>
            <table>
            <tr><td>Новый номер телефона:</td><td><input type='text' name='phone' value='".html_out($phone)."'></td>
            <tr><td></td><td>На номер будет выслано SMS с кодом подтверждения.</td>
            <tr><td></td><td>Оставьте поле пустым, если не хотите указвать номер.</td>
            <tr><td></td><td><button type='submit' name='submit' value='submit'>Продолжить</button></td>
            </table>";
        return $ret;
    }
    
    /// Получить HTML код формы смены email адреса
    public function getUpdateEmailForm($login, $email='') {
        $form_action = $this->getFormAction();
        $ret = "<form action='$form_action' method='post'>
            <input type='hidden' name='mode' value='cemail'>
            <input type='hidden' name='login' value='".html_out($login)."'>
            <table>
            <tr><td>Новый адрес email:</td><td><input type='text' name='email' value='".html_out($email)."'></td>
            <tr><td></td><td>На email будет выслано сообщение с кодом подтверждения.</td>
            <tr><td></td><td>Оставьте поле пустым, если не хотите указвать адрес.</td>
            <tr><td></td><td><button type='submit' name='submit' value='submit'>Продолжить</button></td>
            </table>";
        return $ret;
    }
    
    /// Маскировать адрес электронной почты
    public function maskEmail($email) {
        $fg = explode('@', $email);
        $len = strlen($fg[0]);
        if($len < 5) {
            $email = $fg[0][0];
            for($i=1;$i<($len-1);$i++) {
                $email .= '*';
            }
            $email .= $fg[0][$len-1];
        }
        else {
            $email = $fg[0][0].$fg[0][1];
            for($i=2;$i<($len-2);$i++) {
                $email .= '*';
            }
            $email .= $fg[0][$len-2].$fg[0][$len-1];
        }
        $email .= '@';
        $dfg = explode('.', $fg[1]);
        $len = strlen($dfg[0]);
        if($len < 5) {
            $email .= $dfg[0][0];
            for($i=1;$i<($len-1);$i++) {
                $email .= '*';
            }
            $email .= $dfg[0][$len-1];
        }
        else {
            $email .= $dfg[0][0].$dfg[0][1];
            for($i=2;$i<($len-2);$i++) {
                $email .= '*';
            }
            $email .= $dfg[0][$len-2].$dfg[0][$len-1];
        }
        for($i=1;$i<count($dfg);$i++) {
            $email .= '.'.$dfg[$i];
        }
        return $email;
    }
    
    /// Маскировать номер телефона
    public function maskPhone($phone) {
        $m_phone = '';
        $len = strlen($phone);
        $m_phone = $phone[0].$phone[1].$phone[2];
        for($i=3;$i<($len-3);$i++) {
            $m_phone .= '*';
        }
        $m_phone .= $phone[$len-3].$phone[$len-2].$phone[$len-1];
        return $m_phone;
    }
    
    /// Попытка аутентификации
    public function tryLogin() {
        global $tmpl, $db, $CONFIG;
        $login = request('login');
        $pass = request('pass');
        $captcha = request('captcha');
        if(@$_SESSION['uid']) {
            redirect("/user.php");
	}
        $auth = new \authenticator();

        // Куда переходить после авторизации
        $from = getenv("HTTP_REFERER");
        if ($from) {
            $froma = explode("/", $from);
            $proto = @$_SERVER['HTTPS'] ? 'https' : 'http';
            if (($froma[2] != $_SERVER['HTTP_HOST']) || ($froma[3] == 'login.php') || ($froma[3] == '')) {
                $from = "$proto://" . $_SERVER['HTTP_HOST'];
            }
        }
        $_SESSION['redir_to'] = $from;

        $ip = getenv("REMOTE_ADDR");
        $at = $auth->attackTest($ip);

        if($at == 'ban_net') {            
            $db->insertA("users_bad_auth", array('ip' => $ip, 'time' => time() + 60) );
            throw new \Exception("Из-за попыток перебора паролей к сайту доступ с вашей подсети заблокирован! Вы сможете авторизоваться через несколько часов после прекращения попыток перебора пароля. Если Вы не предпринимали попыток перебора пароля, обратитесь к Вашему поставщику интернет-услуг - возможно, кто-то другой пытается подобрать пароль, используя ваш адрес.");
        }
        if($at == 'ban_ip') {
            $db->insertA("users_bad_auth", array('ip' => $ip, 'time' => time() + 60) );
            throw new \Exception("Из-за попыток перебора паролей к сайту доступ с вашего адреса заблокирован! Вы сможете авторизоваться через несколько часов после прекращения попыток перебора пароля. Если Вы не предпринимали попыток перебора пароля, обратитесь к Вашему поставщику интернет-услуг - возможно, кто-то другой пытается подобрать пароль, используя ваш адрес.");
        }
        
        $need_form = true;
        try {
            if(@$_REQUEST['opt']=='login')  {
                if( ($at == 'captcha') && ( (strtoupper(@$_SESSION['captcha_keystring']) != strtoupper($captcha)) || (@$_SESSION['captcha_keystring']=='') ) ) {
                    $db->insertA("users_bad_auth", array('ip' => getenv("REMOTE_ADDR"), 'time' => time()) );
                    throw new \Exception("Введите правильный код подтверждения, изображенный на картинке");
                }
                
                $_SESSION['captcha_keystring'] = ''; // Нельзя повторно использовать
                
                if(!$auth->loadDataForLogin($login)) {  // Не существует
                    $db->insertA("users_bad_auth", array('ip' => getenv("REMOTE_ADDR"), 'time' => time()) );
                    throw new \Exception("Неверная пара логин / пароль. Попробуйте снова.");
                }

                if(!$auth->testPassword($pass)) {   // Неверный пароль
                    $db->insertA("users_bad_auth", array('ip' => getenv("REMOTE_ADDR"), 'time' => time()) );
                    throw new \Exception("Неверная пара логин / пароль. Попробуйте снова.");
                }

                if ($auth->isDisabled()) {
                    throw new \Exception("Пользователь заблокирован (забанен). Причина блокировки: " . $auth->getDisabledReason() );
                }
                $need_form = false;
                if( !$auth->isConfirmed() ) {  
                    $tmpl->addContent( $this->getConfirmForm($login) );
                } elseif( $auth->isExpired() ) {
                    $user_info = $auth->getUserInfo();
                    $tmpl->msg("Ваш пароль просрочен, и должен быть изменён.", "err", "Ошибка при входе");
                    $_SESSION['session_pass_recovery_key'] = MD5(time() + rand(0, 1000000));
                    $_SESSION['session_pass_recovery_user_id'] = $user_info['id'];
                    $_SESSION['session_pass_recovery_executed_step'] = 3;
                    $tmpl->addContent( $this->getNewPassRecoveryForm($_SESSION['session_pass_recovery_key']) );
                }
                else {
                    $auth->authenticate('password');
                    if(@$_SESSION['last_page']) {
                        $lp = $_SESSION['last_page'];
                        unset($_SESSION['last_page']);
                        redirect($lp);
                    }
                    else if (@$_SESSION['redir_to']) {
                        redirect($_SESSION['redir_to']);
                    } else {
                        redirect("user.php");
                    }
                }
            }
        }
        catch(\Exception $e) {
            $tmpl->msg($e->getMessage(),"err","Ошибка при входе");
        }
        if($need_form) {
            if(@$_SESSION['another_device']) {
                $tmpl->errorMessage("Выполнен вход с другого устройства! Для продолжения работы необходимо пройти повторную аутентификацию, "
                    . "либо <a href='/login.php?mode=logout'>прервать сессию</a>!");
            }
            if (isset($_REQUEST['cont'])) {
                $tmpl->addContent("<div id='page-info'>Для доступа в этот раздел Вам необходимо пройти аутентификацию.</div>");
            }
            $tmpl->addContent( $this->getLoginForm($login, $at == 'captcha') );
        }
    }
    
    public function tryChangeEmail() {
        global $tmpl, $db;
        $login = request('login');
        $email = request('email');
        $auth = new \authenticator();
        $db->startTransaction();
        if( !$auth->loadDataForLogin($login) ) {
            throw new \Exception("Пользователь не найден в базе");
        }
        if($auth->isConfirmed() && ( !$auth->getRegPhone() || $auth->isNeedConfirmPhone()) ) {
            $tmpl->msg("Для смены email адреса сначала установите и подтвердите номер телефона.");
        } elseif( $email == $auth->getRegEmail() ) {
            $tmpl->msg("Вы не изменили email адрес!");
        }
        else {
            // Проверка допустимости номера телефона
            if ($email != '') {
                if (!preg_match('/^\w+([-\.\w]+)*\w@\w(([-\.\w])*\w+)*\.\w{2,8}$/', $email)) {
                    $tmpl->msg('Неверный формат адреса e-mail. Адрес должен быть в формате user@host.zone');
                } else {
                    $auth->setRegEmail($email);
                    $tmpl->msg("Адрес электронной почты изменён. Подтвердите, что он принадлежит Вам.", "ok");
                    $code = $auth->getNewConfirmEmailCode();
                    $auth->sendConfirmEmail($code);
                    $tmpl->addContent( $this->getConfirmForm($login) );
                    $db->commit();
                }
            } else {
                if($auth->isConfirmed() ) {
                    $auth->setRegEmail('');
                    $tmpl->msg("Адрес электронной почты удалён из профиля пользователя.");
                    $db->commit();
                } else {
                    $tmpl->msg("Удаление адреса электронной почты недоступно для неподтверждённых аккаунтов.");
                }
            }
        }
    }
    
    public function tryChangePhone() {
        global $tmpl, $db;
        $login = request('login');
        $phone = request('phone');
        $auth = new \authenticator();
        $db->startTransaction();
        if( !$auth->loadDataForLogin($login) ) {
            throw new \Exception("Пользователь не найден в базе");
        }
        if($auth->isConfirmed() && ( !$auth->getRegEmail() || $auth->isNeedConfirmEmail()) ) {
            $tmpl->msg("Для смены номера телефона сначала установите и подтвердите адрес email.");
        } elseif( $phone == $auth->getRegPhone() ) {
            $tmpl->msg("Вы не изменили номер телефона!");
        }
        else {
            // Проверка допустимости номера телефона
            if ($phone != '') {
                if (!preg_match('/^\+79\d{9}$/', $phone)) {
                    $phone = normalizePhone($phone);
                    if ($phone === false) {
                        $tmpl->msg('Неверный формат телефона. Номер должен быть в федеральном формате +79XXXXXXXXX.');
                    } else {
                        $auth->setRegPhone($phone);
                        $tmpl->msg("Номер телефона изменён. Подтвердите, что он принадлежит Вам.", "ok");
                        $code = $auth->getNewConfirmPhoneCode();
                        $auth->sendConfirmSMS($code);
                        $tmpl->addContent( $this->getConfirmForm($login) );
                        $db->commit();
                    }
                } else {
                    $auth->setRegPhone($phone);
                    $tmpl->msg("Номер телефона изменён. Подтвердите, что он принадлежит Вам.", "ok");
                    $code = $auth->getNewConfirmPhoneCode();
                    $auth->sendConfirmSMS($code);
                    $tmpl->addContent( $this->getConfirmForm($login) );
                    $db->commit();
                }
            } else {
                if($auth->isConfirmed() ) {
                    $auth->setRegPhone('');
                    $tmpl->msg("Номер телефона удалён из профиля пользователя.");
                    $db->commit();
                } else {
                    $tmpl->msg("Удаление номера телефона недоступно для неподтверждённых аккаунтов.");
                }
            }
        }
    }
    
    /// Попытка обработки регистрационных данных
    public function tryRegister() {
        $login = request('login');
        $email = request('email');
        $phone = request('phone');
        $captcha = strtoupper( request('img') );
        $subs_flag = request('subs');
        $subs_flag = $subs_flag ? 1 : 0;

        $auth = new \authenticator();
        $reg_errors = $auth->register($login, $email, $subs_flag, $phone, $subs_flag, $captcha);
        if($reg_errors) {
            return $this->getRegisterForm($login, $email, $phone, $subs_flag, $reg_errors);

        } else {
            return $this->getConfirmForm($login);
        }
    }
    
    /// Попытка подтверждения регистрационных данных
    public function tryConfirm() {
        global $tmpl;
        $bad_email_key = $bad_phone_key = $auto_auth = false;
        $login = request('login');
        $email_key = request('e');
        $phone_key = request('p');
        $submit_type = request('submit');

        $auth = new \authenticator();
        if( !$auth->loadDataForLogin($login) ) {
            throw new \Exception("Пользователь не найден в базе");
        }
        
        if($submit_type == 'resend_sms') {
            if($auth->isNeedConfirmPhone()) {
                $code = $auth->getNewConfirmPhoneCode();
                $auth->sendConfirmSMS($code);
                $tmpl->msg("Код подтверждения отправлен по SMS.", "ok");
            } else {
                $tmpl->msg("Подтверждение номера телефона не требуется");
            }            
            if( $auth->isNeedConfirmEmail() || $auth->isNeedConfirmPhone() ) {
                $tmpl->addContent( $this->getConfirmForm($login) );
            }
        } 
        elseif($submit_type == 'cphone') {
            if($auth->isConfirmed() && ( !$auth->getRegEmail() || $auth->isNeedConfirmEmail()) ) {
                $tmpl->msg("Для смены номера телефона сначала установите и подтвердите адрес email.");
            } else {
                $tmpl->setContent("<h1>Смена номера телефона</h1>");
                $tmpl->addContent( $this->getUpdatePhoneForm($login, $auth->getRegPhone() ) );
            }
        }
        elseif($submit_type == 'resend_email') {
            if($auth->isNeedConfirmEmail()) {
                $code = $auth->getNewConfirmEmailCode();
                $auth->sendConfirmEmail($code);
                $tmpl->msg("Код подтверждения отправлен по email.", "ok");
            } else {
                $tmpl->msg("Подтверждение email адреса не требуется");
            }            
            if( $auth->isNeedConfirmEmail() || $auth->isNeedConfirmPhone() ) {
                $tmpl->addContent( $this->getConfirmForm($login) );
            }
        }
        elseif($submit_type == 'cemail') {
            if($auth->isConfirmed() && ( !$auth->getRegPhone() || $auth->isNeedConfirmPhone()) ) {
                $tmpl->msg("Для смены номера телефона сначала установите и подтвердите номер телефона.");
            } else {
                $tmpl->setContent("<h1>Смена email адреса</h1>");
                $tmpl->addContent( $this->getUpdateEmailForm($login, $auth->getRegEmail() ) );
            }
        }
        else {
            if($email_key && $auth->isNeedConfirmEmail()) {
                if($auth->tryConfirmEmail($email_key)) {
                    $auto_auth = true;
                    $tmpl->msg("Адрес электронной почты успешно подтверждён.", "ok");
                } else {
                    $bad_email_key = true;
                }
            }

            if($phone_key && $auth->isNeedConfirmPhone()) {
                if($auth->tryConfirmPhone($phone_key)) {
                    $auto_auth = true;
                    $tmpl->msg("Номер телефона успешно подтверждён.", "ok");
                } else {
                    $bad_phone_key = true;
                }
            }
            if( $auth->isNeedConfirmEmail() || $auth->isNeedConfirmPhone() ) {
                $tmpl->addContent( $this->getConfirmForm($login, $bad_email_key, $bad_phone_key) );
            }
            if(!$_SESSION['uid']) {
                if($auto_auth) {
                    $auth->authenticate('register');
                    $tmpl->msg("Вход выполнен", "ok");
                }
            }
        }
    }
    
    /// Попытка прохождения шага 1 восстановления пароля
    public function tryPassRecoveryStep1() {
        global $tmpl, $CONFIG, $db;
        $login = request('login');            
        if (@$_REQUEST['captcha'] == '') {
            $tmpl->msg("Код с изображения не введён");
            $tmpl->addContent( $this->getPassRecoveryForm() );
        } elseif (strtoupper(@$_SESSION['captcha_keystring']) != strtoupper($_REQUEST['captcha'])) {
            $tmpl->msg("Код с изображения введён неверно");
            $tmpl->addContent( $this->getPassRecoveryForm() );
        } else {
            $sql_login = $db->real_escape_string($login);		
            $res = $db->query("SELECT `id`, `name`, `reg_email`, `reg_email_confirm`, `reg_phone`, `reg_phone_confirm`, `disabled`, `disabled_reason` 
                FROM `users` 
                WHERE `name`='$sql_login' OR `reg_email`='$sql_login' OR `reg_phone`='$sql_login'");
            if (!$res->num_rows) {
                $tmpl->msg("Пользователь не найден");
                $tmpl->addContent( $this->getPassRecoveryForm() );
            } else {
                $user_info = $res->fetch_assoc();
                if($user_info['disabled']) {
                    throw new \Exception("Пользователь заблокирован (забанен). Причина блокировки: ".$user_info['disabled_reason']);
                }
                $openid_list = array();
                if (@$CONFIG['site']['allow_openid']) {
                    $res = $db->query("SELECT `openid_identify` FROM `users_openid` WHERE `user_id`={$user_info['id']}");
                    while ($openid_info = $res->fetch_row()) {
                        $openid_list[] = $openid_info[0];
                    }
                }
                $_SESSION['session_pass_recovery_key'] = $sprk = MD5(time() + rand(0, 1000000));
                $_SESSION['session_pass_recovery_user_id'] = $user_info['id'];
                $_SESSION['session_pass_recovery_executed_step'] = 1;
                $tmpl->addContent( $this->getPassRecoveryTypesForm($sprk, $user_info['reg_email'], $user_info['reg_phone'], $openid_list) );
            }
        }
    }
    
    /// Попытка прохождения шага 2 восстановления пароля
    public function tryPassRecoveryStep2() {
        global $db, $CONFIG, $tmpl;
        $key = request('key');
        $method = request('method');
        $user_id = intval($_SESSION['session_pass_recovery_user_id']);

        if( $key != $_SESSION['session_pass_recovery_key'] ) {
            throw new \Exception('Ошибка сессии восстановления пароля. Попробуйте снова!');
        }
        if($_SESSION['session_pass_recovery_executed_step'] != 1) {
            throw new Exception('Нарушение последовательности восстановления пароля. Повторите попытку.');
        }            
        $_SESSION['session_pass_recovery_executed_step'] = 2;

        $res = $db->query("SELECT `id`, `name`, `reg_email`, `reg_email_confirm`, `reg_phone`, `reg_phone_confirm`, `disabled`, `disabled_reason` 
            FROM `users` WHERE `id`='$user_id'");
        if (!$res->num_rows) {
            throw new Exception("Пользователь не найден!");
        }
        $user_info = $res->fetch_assoc();
        if ($user_info['disabled']) {
            throw new Exception("Пользователь заблокирован (забанен). Причина блокировки: " . $user_info['disabled_reason']);
        }
        switch ($method) {
            case 'email':
                $auth = new \authenticator();
                $auth->sendPassChangeEmail($user_info['id'], $user_info['name'], $_SESSION['session_pass_recovery_key'], $user_info['reg_email']);
                $tmpl->msg("Код для смены пароля выслан Вам по электронной почте", "ok");
                $tmpl->addContent( $this->getPassRecoveryKeyForm($_SESSION['session_pass_recovery_key']) );
                break;
            case 'sms':
                $auth = new \authenticator();
                $auth->sendPassChangeSms($user_info['id'], $user_info['name'], $_SESSION['session_pass_recovery_key'], $user_info['reg_phone']);
                $tmpl->msg("Код для смены пароля выслан Вам по SMS", "ok");
                $tmpl->addContent( $this->getPassRecoveryKeyForm($_SESSION['session_pass_recovery_key']) );
                break;
            default: 
                if (@$CONFIG['site']['allow_openid']) {
                    header("Location: /login_oid.php?oid=$method");
                    /// TODO: после аутентификации нужно приглашение ввести новый пароль
                    exit();
                } else {
                    throw new NotFoundException("Метод не реализован или не доступен");
                }
        }
    }
    
    /// Попытка прохождения шага 3 восстановления пароля
    public function tryPassRecoveryStep3() {
        global $db, $tmpl;
        $key = request('key');
        $pc_key = request('s');
        $user_id = intval($_SESSION['session_pass_recovery_user_id']);

        if( $key != $_SESSION['session_pass_recovery_key'] ) {
            throw new \Exception('Ошибка сессии восстановления пароля. Попробуйте снова!');
        }
        if($_SESSION['session_pass_recovery_executed_step'] != 2) {
            throw new Exception('Нарушение последовательности восстановления пароля. Повторите попытку.');
        }
        $_SESSION['session_pass_recovery_executed_step'] = 3;

        $sql_pc_key = $db->real_escape_string($pc_key);
        $res = $db->query("SELECT `users`.`id`, `users`.`name`, `users_worker_info`.`worker` FROM `users` 
            LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id`
            WHERE `pass_change`='$sql_pc_key' AND `id`='$user_id'");
        if($user_info = $res->fetch_assoc()) {
            $tmpl->addContent( $this->getNewPassRecoveryForm($_SESSION['session_pass_recovery_key']) );                
        } else {
            $db->query("UPDATE `users` SET `pass_change`='' WHERE `id`='$user_id'");
            throw new Exception("Код неверен или устарел", "err");
        }
    }
    
    /// Попытка прохождения шага 4 восстановления пароля 
    public function tryPassRecoveryStep4() {
        global $tmpl, $db;
        $key = request('key');
        $newpass = request('newpass');
        $newpass2 = request('newpass2');
        $user_id = intval($_SESSION['session_pass_recovery_user_id']);
        if( $key != $_SESSION['session_pass_recovery_key'] ) {
            throw new \Exception('Ошибка сессии восстановления пароля. Попробуйте снова!');
        }
        if($_SESSION['session_pass_recovery_executed_step'] != 3) {
            throw new \Exception('Нарушение последовательности восстановления пароля. Повторите попытку.');
        }

        $auth = new \authenticator();
        $db->startTransaction();
        $auth->loadDataForID($user_id);
        if($newpass != $newpass2) {
            $tmpl->errorMessage("Пароль и подтверждение не совпадают!");
            $tmpl->addContent( $this->getNewPassRecoveryForm($_SESSION['session_pass_recovery_key']) );    
        } 
        elseif(strlen($newpass)<8) {
            $tmpl->errorMessage("Пароль слишком короткий!");
            $tmpl->addContent( $this->getNewPassRecoveryForm($_SESSION['session_pass_recovery_key']) );    
        }
        elseif( $auth->testPassword($newpass) && $auth->isExpired() ) {
            $tmpl->errorMessage("Новый пароль не может совпадать со старым!");
            $tmpl->addContent( $this->getNewPassRecoveryForm($_SESSION['session_pass_recovery_key']) );   
        }
        else {
            if( !$auth->isCorrectPassword($newpass) ) {
                $tmpl->errorMessage("Пароль слишком короткий, или содержит недопустимые символы!");
                $tmpl->addContent( $this->getNewPassRecoveryForm($_SESSION['session_pass_recovery_key']) );
            } else {
                $auth->setPassword($newpass);                
                $auth->authenticate('passrecovery');
                $tmpl->msg("Пароль успешно изменён! Не забудьте его!", "ok");
                $db->commit();
            }
        }
    }
    
    /// Отписка от рассылки
    /// @param $email Отписываемый адрес
    /// @param $from Информация о том, откуда инициирована отписка
    public function unsubscribeEmail($email, $from) {
        global $db, $tmpl;
        $c = 0;
        $email = $db->real_escape_string($email);
        $from_sql = $db->real_escape_string($from);
        $res = $db->query("UPDATE `users` SET `reg_email_subscribe`='0' WHERE `reg_email`='$email'");
        if ($db->affected_rows) {
            $db->query("INSERT INTO `users_unsubscribe_log` (`email`, `time`, `source`, `is_user`)
                VALUES ('$email', NOW(), '$from_sql', 1)");
            $tmpl->msg("Вы успешно отказались от автоматической рассылки!", "ok");
            $c = 1;
        }

        $res = $db->query("UPDATE `doc_agent` SET `no_mail`='1' WHERE `email`='$email'");
        if ($db->affected_rows) {
            $db->query("INSERT INTO `users_unsubscribe_log` (`email`, `time`, `source`, `is_user`)
                VALUES ('$email', NOW(), '$from_sql', 0)");
            $tmpl->msg("В нашей клиентской базе Ваш адрес помечен, как нежелательный для рассылки.", "ok");
            $c = 1;
        }

        if (!$c) {
            $tmpl->msg("Ваш адрес не найден в наших базах рассылки! Возможно, Вы отказались от рассылки ранее, или не являетесь нашим зарегистрированным пользователем. За разяснением обратитесь по телефону или e-mail, указанному на странице <a class='wiki' href='/article/ContactInfo'>Контакты</a>, либо в письме, полученном от нас. Спасибо за понимание!", "notify");
        }
    }
    
    /// Завершение аутентификационной сессии
    public function logout() {
        unset($_SESSION['uid']);
	unset($_SESSION['name']);
        unset($_SESSION['another_device']);
	redirect("/index.php");
    }
}
