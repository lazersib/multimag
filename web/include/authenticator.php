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

/// Класс аутентификации и регистрации
class authenticator {
    var $ip_ban_attemps_limit = 20;
    var $ip_ban_attemps_interval = 3;
    var $ip_captcha_attemps_limit = 3;
    var $ip_captcha_attemps_interval = 0.5;

    var $net24_ban_attemps_limit = 100;
    var $net24_ban_attemps_interval = 3;
    var $net24_captcha_attemps_limit = 6;
    var $net24_captcha_attemps_interval = 0.5;
    
    var $net16_ban_attemps_limit = 500;
    var $net16_ban_attemps_interval = 3;
    var $net16_captcha_attemps_limit = 30;
    var $net16_captcha_attemps_interval = 0.5;
    
    var $all_captcha_attemps_limit = 100;
    var $all_captcha_attemps_interval = 0.25;
    
    protected $last_test = '';
    
    protected $sql_user_query = "SELECT `id`, `name`, `pass`, `pass_type`, `pass_expired`, `pass_date_change`, 
        `reg_email`, `reg_phone`, `reg_email_confirm`, `reg_phone_confirm`, `reg_email_subscribe`, `reg_phone_subscribe`,
        `reg_date`, `disabled`, `disabled_reason`, `bifact_auth`, `jid`, `real_name`, `real_address`, `agent_id`,
        `worker`, `worker_email`, `worker_email`, `worker_phone`, `worker_jid`, `worker_real_name`, `worker_real_address`, `worker_post_name`
        FROM `users`
        LEFT JOIN `users_worker_info` ON `user_id`=`users`.`id` ";
    
    protected $user_info = null;   // Кэш для данных подтверждения телефона/email
    
    public function __construct() {
        
    }
    
    protected function regEmailMsg($login, $pass, $conf) {
        global $CONFIG;
        $proto = 'http';
        if (@$CONFIG['site']['force_https_login'] || @$CONFIG['site']['force_https']) {
            $proto = 'https';
        }
        return 
        "Вы получили это письмо потому, что в заявке на регистрацию на сайте http://{$CONFIG['site']['name']} был указан Ваш адрес электронной почты. ".
        "Для продолжения регистрации, введите пожалуйста, следующий код подтверждения:\n".
        "{$conf}\n".
        "или перейдите по ссылке $proto://{$CONFIG['site']['name']}/login.php?mode=conf&login={$login}&e={$conf} .\n".
        "Если не переходить по ссылке (например, если заявка подана не Вами), то регистрационные данные будут автоматически удалены через неделю.\n\n".
        "Ваш аккаунт:\n".
        "Логин: $login\n".
        "Пароль: $pass\n\n".
        "После подтверждения регистрации Вы сможете получить доступ к расширенным функциям сайта. Неактивные аккаунты удаляются через 6 месяцев.\n\n".
        "------------------------------------------------------------------------------------------\n\n".
        "You have received this letter because in the form of registration in a site http://{$CONFIG['site']['name']} your e-mail address has been entered. ".
        "For continue of registration, please enter this key:\n".
        "$conf\n".
        "or pass under the link $proto://{$CONFIG['site']['name']}/login.php?mode=conf&login=$login&e=$conf .\n".
        "If not going under the reference (for example if the form is submitted not by you) registration data will be automatically removed after a week.\n\n".
        "Your account:\n".
        "Login: $login\n".
        "Pass: $pass\n\n".
        "After confirmatoin of registration you can get access to the expanded functions of a site. Inactive accounts leave in 6 months.\n\n".
        "------------------------------------------------------------------------------------------\n".
        "Сообщение сгенерировано автоматически, отвечать на него не нужно!\n".
        "The message is generated automatically, to answer it is not necessary!";
    }
    
    public function testPassword($password) {
        if(! @$this->user_info['pass'] ) {
            return false;
        }
        switch($this->user_info['pass_type']) {
            case 'CRYPT':
                if(crypt($password, $this->user_info['pass']) == $this->user_info['pass']) {
                    return true;
                }
                break;
            case 'SHA1':
                if (SHA1($password) == $this->user_info['pass']) {
                    return true;
                }
                break;
            default:
                if (MD5($password) == $this->user_info['pass']) {
                    return true;
                }
        }
        return false;
    }
    
    public function register($login, $email, $subs_email, $phone, $subs_phone, $captcha) {
        global $db, $CONFIG;
        $ret = array();
        $subs_email = $subs_email?1:0;
        $subs_phone = $subs_phone?1:0;

        $db->startTransaction();        
        // Проверка допустимости логина        
        if ($login == '') {
            $ret['login'] = 'Поле login не заполнено';
        } elseif (strlen($login) < 3) {
            $ret['login'] = 'login слишком короткий';
        } elseif (strlen($login) > 24) {
            $ret['login'] = 'login слишком длинный';
        } elseif (!preg_match('/^[a-zA-Z\d]*$/', $login)) {
            $ret['login'] = 'login должен состоять только из латинских букв и цифр';
        } else {
            $sql_login = $db->real_escape_string($login);
            $res = $db->query("SELECT `id` FROM `users` WHERE `name`='$sql_login'");
            if ($res->num_rows) {
                $ret['login'] = 'Такой login занят. Используйте другой.';
            }
        }
        // Проверка наличия телефона или email
        if (@$CONFIG['site']['allow_phone_regist']) {
            if ($email == '' && $phone == '') {
                $ret['email'] = 'Нужно заполнить телефон или email';
            }
        }
        else {
            if ($email == '') {
                $ret['email'] = 'Поле email не заполнено';
            }
        }
        // Проверка допустимости email
        if ($email != '') {
            if (!preg_match('/^\w+([-\.\w]+)*\w@\w(([-\.\w])*\w+)*\.\w{2,8}$/', $email)) {
                $ret['email'] = 'Неверный формат адреса e-mail. Адрес должен быть в формате user@host.zone';
            } else {
                $res = $db->query("SELECT `id` FROM `users` WHERE `reg_email`='$email'");
                if ($res->num_rows) {
                    // TODO: предложить восстановить доступ
                    $ret['email'] = 'Пользователь с таким email уже зарегистрирован. Используйте другой.';
                }
            }
        }
        // Проверка допустимости номера телефона
        if ($phone != '') {
            $phone = '+7' . $phone;
            if (!preg_match('/^\+79\d{9}$/', $phone)) {
                $phone = normalizePhone($phone);
                if ($phone === false) {
                    $ret['phone'] = 'Неверный формат телефона. Номер должен быть в федеральном формате +79XXXXXXXXX ';
                } else {
                    $res = $db->query("SELECT `id` FROM `users` WHERE `reg_phone`='$phone'");
                    if ($res->num_rows) {
                        // TODO: предложить восстановить доступ
                        $ret['phone'] = 'Пользователь с таким телефоном уже зарегистрирован. Используйте другой.';
                    }  
                }
            } else {
                $res = $db->query("SELECT `id` FROM `users` WHERE `reg_phone`='$phone'");
                if ($res->num_rows) {
                    // TODO: предложить восстановить доступ
                    $ret['phone'] = 'Пользователь с таким телефоном уже зарегистрирован. Используйте другой.';
                }
            }
        }
        // Проверка капчи
        if ($captcha == '') {
            $ret['captcha'] = 'Код подтверждения не введён';
        }
        if (strtoupper($_SESSION['captcha_keystring']) != strtoupper($captcha)) {
            $ret['captcha'] = 'Код подтверждения введён неверно';
        }
        
        // Были ли ошибки
        if(count($ret)) {
            return $ret;
        }
        
        // Подготовка к созданию учётной записи
        $email_conf = $email ? substr(MD5(time() + rand(0, 1000000)), 0, 8) : '';
        $phone_conf = $phone ? rand(1000, 99999) : '';
        $pass = keygen_unique(0, 8, 11);
                
        $sql_login = $db->real_escape_string($login);
        if (@$CONFIG['site']['pass_type'] == 'MD5') {
            $pass_hash = MD5($pass);
            $pass_type = 'MD5';
        } else if (@$CONFIG['site']['pass_type'] == 'SHA1') {
            $pass_hash = SHA1($pass);
            $pass_type = 'SHA1';
        } else {
            if (CRYPT_SHA256 == 1) {
                $salt = '';
                for($i=0;$i<16;$i++) {
                    $salt .= chr(rand(0, 255));
                }
                $salt = substr(base64_encode($salt), 16);
                $pass_hash = crypt($pass, '$5$' . $salt . '$');
            } else {
                $pass_hash = crypt($pass);
            }
            $pass_type = 'CRYPT';
        }

        $user_data = array(
            'name' => $login,
            'pass' => $pass_hash,
            'pass_type' => $pass_type,
            'pass_date_change' => date("Y-m-d H:i:s"),
            'reg_email' => $email,
            'reg_email_confirm' => $email_conf,
            'reg_email_subscribe' => $subs_email,
            'reg_phone' => $phone,
            'reg_phone_confirm' => $phone_conf,
            'reg_date' => date("Y-m-d H:i:s")            
        );
        
        $user_id = $db->insertA('users', $user_data);
        $this->loadDataForID($user_id);
        if ($email) {
            $msg = $this->regEmailMsg($login, $pass, $email_conf);
            mailto($email, "Регистрация на " . $CONFIG['site']['name'], $msg);
        }

        if ($phone) {
            $this->sendConfirmSMS($phone_conf, "\r\nЛогин:$login\r\nПароль:$pass");
        }
        $db->commit();
        return false;
    }

    public function getNewConfirmPhoneCode() {
        global $db;
        $code = rand(1000, 99999);
        $db->update('users', $this->user_info['id'], 'reg_phone_confirm', $code);
        $this->user_info['reg_phone_confirm'] = $code;
        return $code;
    }
    
    public function getNewConfirmEmailCode() {
        global $db;
        $code = substr(MD5(time() + rand(0, 1000000)), 0, 8);
        $db->update('users', $this->user_info['id'], 'reg_email_confirm', $code);
        $this->user_info['reg_email_confirm'] = $code;
        return $code;
    }
        
    public function sendConfirmSMS($code, $ext_text = 'Никому не сообщайте.') {
        global $CONFIG;
        require_once('include/sendsms.php');
        $sender = new SMSSender();
        $sender->setNumber($this->user_info['reg_phone']);
        $sender->setContent("Ваш код: $code\r\n$ext_text\r\n{$CONFIG['site']['name']}");
        $sender->send();
    }
    
    public function sendConfirmEmail($code) {
        global $CONFIG;        
        $proto = 'http';
        if ($CONFIG['site']['force_https_login'] || $CONFIG['site']['force_https']) {
            $proto = 'https';
        }
        $msg = "Поступил запрос на установку email адреса на сайте {$CONFIG['site']['name']} для аккаунта {$this->user_info['name']}."
            . "\nЕсли аккаунт Ваш, и вы действительно хотите установить адрес, перейдите по ссылке:"
            . "\n$proto://{$CONFIG['site']['name']}/login.php?mode=conf&login={$this->user_info['name']}&e={$code} ,"
            . "\nлибо введите код подтверждения:"
            . "\n{$code}"
            . "\nЕсли аккаунт Вам не принадлежит, проигнорируйте письмо."
            . "\n----------------------------------------"
            . "\nСообщение сгенерировано автоматически, отвечать на него не нужно!";
        mailto($this->user_info['reg_email'], "Смена регистрационного email адреса", $msg);
    }
    
    public function sendPassChangeEmail($user_id, $login, $session_key, $email) {
        global $db, $CONFIG;
        settype($user_id, 'int');
        $db->query("START TRANSACTION");
        $key = substr(md5($user_id . $email . time() . rand(0, 1000000)), 8);
        $proto = 'http';
        if ($CONFIG['site']['force_https_login'] || $CONFIG['site']['force_https']) {
            $proto = 'https';
        }
        $res = $db->query("UPDATE `users` SET `pass_change`='$key' WHERE `id`='$user_id'");
        $msg = "Поступил запрос на смену забытого пароля доступа к сайту {$CONFIG['site']['name']} для аккаунта $login.\nЕсли Вы действительно хотите сменить пароль, перейдите по ссылке\n$proto://{$CONFIG['site']['name']}/login.php?mode=rem&step=3&key=$session_key&s=$key ,\nлибо введите код подтверждения:\n$key\n----------------------------------------\nСообщение сгенерировано автоматически, отвечать на него не нужно!";
        mailto($email, "Смена забытого пароля", $msg);
        $db->query("COMMIT");
    }
    
    public function sendPassChangeSms($user_id, $login, $session_key, $phone) {
        global $db, $CONFIG;
        settype($user_id, 'int');
        require_once('include/sendsms.php');
        $db->query("START TRANSACTION");
        $key = rand(10000, 99999999);
        $res = $db->query("UPDATE `users` SET `pass_change`='$key' WHERE `id`='$user_id'");

        $sender = new SMSSender();
        $sender->setNumber($phone);
        $sender->setContent("$login, Ваш код: $key\n{$CONFIG['site']['name']}");
        $sender->send();
        $db->query("COMMIT");
    }
    
    public function loadDataForLogin($login) {
        global $db;
        if(is_array($this->user_info)) {
            if($this->user_info['name'] === $login) {
                return true;
            }
        }
        $sql_login = $db->real_escape_string($login);
        $res = $db->query($this->sql_user_query . " WHERE `name`='$sql_login'");
        if($res->num_rows) {
            $this->user_info = $res->fetch_assoc();
            return true;
        }
        return false;        
    }
    
    public function loadDataForID($user_id) {
        global $db;
        if(is_array($this->user_info)) {
            if($this->user_info['id'] == $user_id) {
                return true;
            }
        }
        settype($user_id, 'int');
        $res = $db->query($this->sql_user_query . " WHERE `id`='$user_id'");
        if($res->num_rows) {
            $this->user_info = $res->fetch_assoc();
            return true;
        }
        return false;        
    }
    
    public function tryConfirmEmail($confirm_key) {
        global $db;
        if($this->isNeedConfirmEmail() && $confirm_key && $confirm_key == $this->user_info['reg_email_confirm']) {
            $db->update('users', $this->user_info['id'], 'reg_email_confirm', 1);
            $this->user_info['reg_email_confirm'] = 1;
            return true;
        }
        return false;
    }
    
    public function tryConfirmPhone($confirm_key) {
        global $db;
        if($this->isNeedConfirmPhone() && $confirm_key && $confirm_key == $this->user_info['reg_phone_confirm']) {
            $db->update('users', $this->user_info['id'], 'reg_phone_confirm', 1);
            $this->user_info['reg_phone_confirm'] = 1;
            return true;
        }
        return false;
    }

    public function isNeedConfirmEmail() {
        if($this->user_info['reg_email'] && $this->user_info['reg_email_confirm']!=1) {
            return true;
        }
        return false;
    }
    
    public function isNeedConfirmPhone() {
        if($this->user_info['reg_phone'] && $this->user_info['reg_phone_confirm']!=1) {
            return true;
        }
        return false;
    }
    
    /// Подтверждён ли аккаунт хотя бы одним способом
    public function isConfirmed() {
        if($this->user_info['reg_email'] && $this->user_info['reg_email_confirm']==1) {
            return true;
        }
        if($this->user_info['reg_phone'] && $this->user_info['reg_phone_confirm']==1) {
            return true;
        }
        return false;
    }
    
    /// Заблокирован ли аккаунт
    public function isDisabled() {
        return $this->user_info['disabled'] ? true : false;
    }
    
    // Просрочен ли пароль у пользователя
    public function isExpired() {
        global $CONFIG;
        if($this->user_info['pass_expired']) {
            return true;
        }
        if(isset($CONFIG['site']['user_pass_period'])) {
            if($CONFIG['site']['user_pass_period']) {
                $pc_time = strtotime($this->user_info['pass_date_change']);
                if( $pc_time < time()-$CONFIG['site']['user_pass_period']*60*60*24 ) {
                    return true;
                }
            }
        }
        if($this->user_info['worker']) {
            if(isset($CONFIG['site']['worker_pass_period'])) {
                if($CONFIG['site']['worker_pass_period']) {
                    $pc_time = strtotime($this->user_info['pass_date_change']);
                    if( $pc_time < time()-$CONFIG['site']['worker_pass_period']*60*60*24 ) {
                        return true;
                    }
                }
            } else {
                $pc_time = strtotime($this->user_info['pass_date_change']);
                if( $pc_time < time()-90*60*60*24 ) {
                    return true;
                }
            }
        }
        return false;
    }
    
    // Определение кол-ва дней до окончания срока действия пароля
    public function getDaysExpiredAfter() {
        global $CONFIG;
        if($this->user_info['pass_expired']) {
            return 0;
        }
        if(isset($CONFIG['site']['user_pass_period'])) {
            if($CONFIG['site']['user_pass_period']) {
                $pc_time = strtotime($this->user_info['pass_date_change']);
                $exp_time = floor($CONFIG['site']['user_pass_period'] - (time() - $pc_time)/60/60/24);
                if($exp_time < 0) {
                    $exp_time = 0;
                }
                return $exp_time;
            }
        }
        if($this->user_info['worker']) {
            if(isset($CONFIG['site']['worker_pass_period'])) {
                if($CONFIG['site']['worker_pass_period']) {
                    $pc_time = strtotime($this->user_info['pass_date_change']);
                    $exp_time = floor($CONFIG['site']['worker_pass_period'] - (time() - $pc_time)/60/60/24);
                    if($exp_time < 0) {
                        $exp_time = 0;
                    }
                    return $exp_time;
                }
            } else {
                $pc_time = strtotime($this->user_info['pass_date_change']);
                $exp_time = floor(90 - (time() - $pc_time)/60/60/24);
                if($exp_time < 0) {
                    $exp_time = 0;
                }
                return $exp_time;
            }
        }
        return 999999;
    }
    
    public function getDisabledReason() {
        return $this->user_info['disabled_reason'];
    }
    
    public function getRegEmail() {
        return $this->user_info['reg_email'];
    }
    
    public function getRegPhone() {
        return $this->user_info['reg_phone'];
    }   
    
    public function getUserInfo() {
        return $this->user_info;
    }
    
    public function autoAuth() {
        $this->addHistoryLine('autoauth');
        $_SESSION['uid'] = $this->user_info['id'];
        $_SESSION['name'] = $this->user_info['name'];
    }
    
    public function addHistoryLine($auth_method) {
        global $db;
        $user_id = intval($this->user_info['id']);
        $ip = $db->real_escape_string(getenv("REMOTE_ADDR"));
        $ua = $db->real_escape_string($_SERVER['HTTP_USER_AGENT']);
        $m = $db->real_escape_string($auth_method);
        $db->query("INSERT INTO `users_login_history` (`user_id`, `date`, `ip`, `useragent`, `method`)
            VALUES ($user_id, NOW(), '$ip', '$ua', '$m')");
    }
    
    // Проверяет пароль на наличие недопустимых (с кодом > 127 или < 32) символов, и длину
    public function isCorrectPassword($pass) {
        $len = strlen($pass);
        if($len<8) {
            return false;
        }
        for($i=0;$i<$len;$i++) {
            if( ord($pass[$i])>127 || ord($pass[$i])<32 ) {
                return false;
            }
        }
        return true;
    }

    public function setPassword($password) {
        global $CONFIG, $db;
        if(!@$this->user_info['id']) {
            throw new \Exception("Не загружен профиль пользователя");
        }
        if (@$CONFIG['site']['pass_type'] == 'MD5') {
            $pass_hash = MD5($password);
            $sql_pass_type = 'MD5';
        } else if (@$CONFIG['site']['pass_type'] == 'SHA1') {
            $pass_hash = SHA1($password);
            $sql_pass_type = 'SHA1';
        } else {
            if (CRYPT_SHA256 == 1) {
                $salt = '';
                for ($i = 0; $i < 16; $i++) {
                    $salt .= chr(rand(48, 122));
                }
                $pass_hash = crypt($password, '$5$' . $salt . '$');
            } else {
                $pass_hash = crypt($password);
            }
            $sql_pass_type = 'CRYPT';
        }
        $sql_pass_hash = $db->real_escape_string($pass_hash);
        $db->query("UPDATE `users` SET `pass`='$sql_pass_hash', `pass_type`='$sql_pass_type', `pass_change`='', `pass_date_change`=NOW(), `pass_expired`=0
            WHERE `id`='{$this->user_info['id']}'");
        $this->addHistoryLine('chpwd');
    }
    
    public function setRegEmail($email) {
        global $db;
        if(!@$this->user_info['id']) {
            throw new \Exception("Не загружен профиль пользователя");
        }
        $sql_email = $db->real_escape_string($email);
        $db->query("UPDATE `users` SET `reg_email`='$sql_email', `reg_email_confirm`='0'
            WHERE `id`='{$this->user_info['id']}'");
        $this->user_info['reg_email'] = $email;
        $this->user_info['reg_email_confirm'] = 0;
        $this->addHistoryLine('chemail');
    }
    
    public function setRegPhone($phone) {
        global $db;
        if(!@$this->user_info['id']) {
            throw new \Exception("Не загружен профиль пользователя");
        }
        $sql_phone = $db->real_escape_string($phone);
        $db->query("UPDATE `users` SET `reg_phone`='$sql_phone', `reg_phone_confirm`='0'
            WHERE `id`='{$this->user_info['id']}'");
        $this->user_info['reg_phone'] = $phone;
        $this->user_info['reg_phone_confirm'] = 0;
        $this->addHistoryLine('chphone');
    }

    public function attackTest($ip) {
        global $db;
        $need_captcha = 0;

        $sql = 'SELECT `id` FROM `users_bad_auth`';
        $ip_sql = $db->real_escape_string($ip);

        $tm = time() - 60 * 60 * $this->ip_ban_attemps_interval;
        $res = $db->query("$sql WHERE `ip`='$ip_sql' AND `time`>'$tm'");
        if ($res->num_rows >= $this->ip_ban_attemps_limit) { // Более ip_ban_attemps_limit ошибок вводе пароля c данного IP за последние 3 часа. Блокируем аутентификацию.
            return 'ban_ip';
        }        
        $tm = time() - 60 * 60 * $this->ip_captcha_attemps_interval;
        $res = $db->query("$sql WHERE `ip`='$ip_sql' AND `time`>'$tm'");
        if ($res->num_rows >= $this->ip_captcha_attemps_limit) { // Более двух ошибок ввода пароля c данного IP за последние 30 минут. Планируем запрос captcha.
            $need_captcha = 1;
        }

        $ip_a = explode(".", $ip);
        if (!is_array($ip_a)) { // Если IP не удаётся разделить на элементы - завершаем тест
            if($need_captcha) {
                return $this->last_test = 'captcha';
            } else {
                return $this->last_test = 'ok';
            }
        }
        if (count($ip_a) < 2) { // Если IP не удаётся разделить на элементы - завершаем тест
            if($need_captcha) {
                return $this->last_test = 'captcha';
            } else {
                return $this->last_test = 'ok';
            }
        }
        
        $net24 = intval($ip_a[0]).'.'.intval($ip_a[1]).'.'.intval($ip_a[2]).'.%';
        $net16 = intval($ip_a[0]).'.'.intval($ip_a[1]).'.%';

        $tm = time() - 60 * 60 * $this->net24_ban_attemps_interval;
        $res = $db->query("$sql WHERE `ip` LIKE '$net24' AND `time`>'$tm'");
        if ($res->num_rows > $this->net24_ban_attemps_limit) { // Более 100 ошибок вводе пароля c подсети /24 за последние 3 часа. Блокируем аутентификацию.
            return 'ban_net';
        }
        $tm = time() - 60 * 60 * $this->net24_captcha_attemps_interval;
        $res = $db->query("$sql WHERE `ip` LIKE '$net24' AND `time`>'$tm'");
        if ($res->num_rows > $this->net24_captcha_attemps_limit) { // Более 6 ошибок ввода пароля c подсети /24 за последние 30 минут. Планируем запрос captcha.
            $need_captcha = 1;
        }

        $tm = time() - 60 * 60 * $this->net16_ban_attemps_interval;
        $res = $db->query("$sql WHERE `ip` LIKE '$net16' AND `time`>'$tm'");
        if ($res->num_rows > $this->net16_ban_attemps_limit) { // Более 500 ошибок вводе пароля c подсети /16 за последние 3 часа. Блокируем аутентификацию.
            return 'ban_net';
        }
        $tm = time() - 60 * 60 * $this->net16_captcha_attemps_interval;
        $res = $db->query("$sql WHERE `ip` LIKE '$net16' AND `time`>'$tm'");
        if ($res->num_rows > $this->net16_captcha_attemps_limit) { // Более 30 ошибок ввода пароля c подсети /16 за последние 30 минут. Планируем запрос captcha.
            $need_captcha = 1;
        }

        $tm = time() - 60 * 60 * $this->all_captcha_attemps_interval;
        $res = $db->query("$sql WHERE `time`>'$tm'");
        if ($res->num_rows > $this->all_captcha_attemps_limit) { // Более 100 ошибок ввода пароля со всей сети за последние 15 минут. Планируем запрос captcha.
            $need_captcha = 1;
        }
        
        if($need_captcha) {
            return $this->last_test = 'captcha';
        } else {
            return $this->last_test = 'ok';
        }
    }

}
