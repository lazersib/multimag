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
namespace modules\site;

/// Модуль отправки сообщенеий
class message extends \IModule {

    public function __construct() {
        parent::__construct();
        $this->link_prefix = '/message.php';
        //$this->acl_object_name = '';
    }

    // Получить название модуля
    /// @return Строка с именем
    public function getName() {
        return 'Отправка сообщений';
    }

    /// Получить описание модуля
    /// @return Строка с описанием
    public function getDescription() {
        return '';
    }
    
    /// Сформировать html код формы отправки сообщений
    protected function getMessageForm($to, $transport, $sender_name, $sender_contact, $show_captcha = true, $text='') {
        $ret ="<form action='{$this->link_prefix}' method='post'>
            <input type='hidden' name='mode' value='send'>
            <input type='hidden' name='transport' value='$transport'>
            <input type='hidden' name='to' value='$to'>
            Ваше имя:<br>
            <input type='text' name='sender_name' value='$sender_name'><br>
            Контакт для обратной связи (e-mail, jid, номер телефона)<br>
            <input type='text' name='sender_contact' value='$sender_contact'><br>
            Текст сообщения:<br>
            <textarea name='text' rows='5' cols='40'>".html_out($text)."</textarea><br>
            <b style='color:#c00'>Не забудте указать информацию для обратной связи!</b><br>";
        if($show_captcha) {
            $ret.="Подтвердите что вы не робот, введя текст с картинки:<br>
            <img src='/kcaptcha/index.php'><br><input type='text' name='img'><br>";
        }
        $ret.="<input type='submit' value='Отправить'>
            </form>";
        return $ret;
    }
    
    protected function getCallRequestForm($form_data, $show_captcha=true) {
        $ret = "<div>Заполните форму - и вам перезвонят! Все поля обязательны к заполнению.</div>
            <form action='{$this->link_prefix}' method='post'>
            <input type='hidden' name='mode' value='send_call_request'>
            Ваше имя:<br>
            <input type='text' name='name' value='{$form_data['name']}'><br>
            Контактный телефон (лучше мобильный или sip):<br>
            <input type='text' name='phone' value='{$form_data['phone']}'><br>
            Желаемая дата и время звонка:<br>
            <small>Желательно запрашивать звонок в рабочее время магазина</small><br>
            <input type='text' name='call_date' value='{$form_data['call_date']}'><br>";
        if($show_captcha) {
            $ret .= "Подтвердите что вы не робот, введя текст с картинки:<br><img src='/kcaptcha/index.php'><br><input type='text' name='img'><br>";
        }
        $ret .= "<button type='submit'>Отправить запрос</button></form>";
        return $ret;
    }

    protected function filterRecipients($transport='xmpp', $to='') {
        $f_transport = '';
        do {
            if( ($transport=='xmpp' || $transport=='jabber') && \cfg::get('xmpp', 'host')) {
                if(!is_array(\cfg::get('site', 'allow_jids'))) {
                    $to = \cfg::get('site', 'doc_adm_jid');
                }
                elseif(!in_array($to, \cfg::get('site', 'allow_jids'))) {
                    $to = \cfg::get('site', 'doc_adm_jid');
                }
                $f_transport = 'xmpp';
            }
        } while(0);
        if(!$f_transport) {            
            if (!is_array(\cfg::get('site', 'allow_emails'))) {
                $to = \cfg::get('site', 'doc_adm_email');
            } elseif (!in_array($to, \cfg::get('site', 'allow_emails'))) {
                $to = \cfg::get('site', 'doc_adm_email');
            }
            $f_transport = 'email';
        }
        return ['transport'=>$transport, 'to'=>$to];
    }

    /// Отобразить форму отправки сообщения
    protected function viewMessageForm($send_params) {
        global $tmpl;
        $sender_name = $sender_contact = '';
        $show_captcha = true;
        if(@$_SESSION['uid']>0) {
            $user_profile = getUserProfile($_SESSION['uid']);
            if($user_profile['main']['real_name']) {
                $sender_name = $user_profile['main']['real_name'];
            } else {
                $sender_name = $user_profile['main']['name'];
            }
            if($user_profile['main']['reg_email'] && $user_profile['main']['reg_email_confirm']) {
                $sender_contact = 'email:'.$user_profile['main']['reg_email'];
            }
            elseif($user_profile['main']['reg_phone'] && $user_profile['main']['reg_phone_confirm']) {
                $sender_contact = 'phone:'.$user_profile['main']['reg_phone'];
            }
            elseif($user_profile['main']['jid']) {
                $sender_contact = 'jabber:'.$user_profile['main']['jid'];
            }
            $show_captcha = false;
        }
        $tmpl->addContent( $this->getMessageForm($send_params['to'], $send_params['transport'], $sender_name, $sender_contact, $show_captcha) );
    }

    /// Отправить сообщение
    protected function sendMessage() {
        global $tmpl;
        $sender_name = request('sender_name');
        $sender_contact = request('sender_contact');
        $text = request('text');
        $transport = request('transport');
        $to = request('to');
        $send_params = $this->filterRecipients($transport, $to);
        $s_text = "Нам написал сообщение $sender_name($sender_contact)с сайта {$_SERVER["HTTP_HOST"]}\n-------------------\n$text\n";
        $s_text .= "-------------------\nIP отправителя: " . getenv("REMOTE_ADDR") . "\nSESSION ID:" . session_id();
        $s_text .= "\nБроузер:  " . getenv("HTTP_USER_AGENT");
        $show_captcha = true;
        if(@$_SESSION['uid']>0) {
            $show_captcha = false;
        }
        if (@$_SESSION['name']) {
            $s_text.="\nLogin отправителя: " . $_SESSION['name'];
        }
        if (@$_SESSION['uid'] == 0 &&
            (request('img') == '' || strtoupper($_SESSION['captcha_keystring']) != strtoupper(request('img')))) {
            $tmpl->errorMessage("Не верно введён код с картинки");
            $tmpl->addContent( $this->getMessageForm($send_params['to'], $send_params['transport'], $sender_name, $sender_contact, $show_captcha, $text) );
        }
        elseif ($send_params['transport'] == 'xmpp') {
            try {
                require_once(\cfg::getroot('location') . '/common/XMPPHP/XMPP.php');
                $xmppclient = new \XMPPHP_XMPP(\cfg::get('xmpp', 'host'), \cfg::get('xmpp', 'port'), \cfg::get('xmpp','login'), \cfg::get('xmpp','pass')
                    , 'MultiMag r' . MULTIMAG_REV);
                $xmppclient->connect();
                $xmppclient->processUntil('session_start');
                $xmppclient->presence();
                $xmppclient->message($send_params['to'], $s_text);
                $xmppclient->disconnect();
                $tmpl->msg("Сообщение было отправлено!", "ok");
            } catch (XMPPHP_Exception $e) {
                writeLogException($e);
                $tmpl->errorMessage("Невозможно отправить сообщение XMPP!");
                $tmpl->addContent( $this->getMessageForm($send_params['to'], $send_params['transport'], $sender_name, $sender_contact, $show_captcha, $text) );
            }
        } else {
            try {
                mailto($send_params['to'], "Сообщение с сайта {$_SERVER["HTTP_HOST"]}", $s_text);
                $tmpl->msg("Сообщение было отправлено!", "ok");
            } catch (Exception $e) {
                writeLogException($e);
                $tmpl->errorMessage("Невозможно отправить сообщение email!");
                $tmpl->addContent( $this->getMessageForm($send_params['to'], $send_params['transport'], $sender_name, $sender_contact, $show_captcha, $text) );
            }
        }
    }

    
    protected function viewCallRequestForm() {
        global $tmpl;
        $show_captcha = true;
        $fd = ['name'=>'', 'phone'=>'', 'call_date'=>''];
        if(@$_SESSION['uid']>0) {
            $user_profile = getUserProfile($_SESSION['uid']);
            if($user_profile['main']['real_name']) {
                $fd['name'] = $user_profile['main']['real_name'];
            } else {
                $fd['name'] = $user_profile['main']['name'];
            }
            if($user_profile['main']['reg_phone'] && $user_profile['main']['reg_phone_confirm']) {
                $fd['phone'] = $user_profile['main']['reg_phone'];
            }
            $show_captcha = false;
        }
        $tmpl->addContent( $this->getCallRequestForm($fd, $show_captcha) );
    }

    protected function sendCallRequest() {
        global $tmpl, $db;
        $show_captcha = true;
        $fd = ['name'=>'', 'phone'=>'', 'call_date'=>''];
        try {
            $fd = requestA(['name', 'phone', 'call_date']);
            if (!$fd['name']) {
                throw new \ErrorException('Не заполнено поле *Имя*');
            }
            if (!$fd['phone']) {
                throw new \ErrorException('Не заполнено поле *Номер телефона*');
            }
            
            if (@$_SESSION['uid'] == 0 &&
                (request('img') == '' || strtoupper(@$_SESSION['captcha_keystring']) != strtoupper(request('img')))) {
                throw new \ErrorException("Не верно введён код с картинки");
            } else {
                $show_captcha = false;
            }
            
            $fd['ip'] = getenv("REMOTE_ADDR");
            $fd['request_date'] = date('Y-m-d H:i:s');
            $db->insertA('log_call_requests', $fd);
  
            $text = "Посетитель сайта {$_SERVER["HTTP_HOST"]}: {$fd['name']} просит перезвонить на {$fd['phone']} в {$fd['call_date']}";

            if (\cfg::get('call_request', 'email')) {
                mailto(\cfg::get('call_request', 'email'), "Запрос звонка с сайта {$_SERVER["HTTP_HOST"]}", $text);
            }
            if (\cfg::get('call_request', 'xmpp') && \cfg::get('xmpp', 'host')) {
                require_once(\cfg::getroot('location') . '/common/XMPPHP/XMPP.php');
                $xmppclient = new \XMPPHP_XMPP(\cfg::get('xmpp', 'host'), \cfg::get('xmpp', 'port'), \cfg::get('xmpp','login'), \cfg::get('xmpp','pass')
                    , 'MultiMag r' . MULTIMAG_REV);
                $xmppclient->connect();
                $xmppclient->processUntil('session_start');
                $xmppclient->presence();
                $xmppclient->message(\cfg::get('call_request', 'xmpp'), $text);
                $xmppclient->disconnect();
            }
            if (\cfg::get('call_request', 'sms')) {
                require_once('include/sendsms.php');
                $sender = new \SMSSender();
                $sender->setNumber(\cfg::get('call_request', 'sms'));
                $sender->setContent($text);
                $sender->send();
            }
            $tmpl->msg("Ваш запрос передан. Вам обязательно перезвонят.", "ok");
        }
        catch (\ErrorException $e) {
            writeLogException($e);
            $tmpl->errorMessage($e->getMessage());
            $tmpl->addContent( $this->getCallRequestForm($fd, $show_captcha) );
        }
        catch (\Exception $e) {
            writeLogException($e);
            $tmpl->errorMessage('Ошибка при отправке! Попробуйте позднее. Сообщение об ошибке передано администратору.');
            $tmpl->addContent( $this->getCallRequestForm($fd, $show_captcha) );
        }
    }
    
    /// Запустить модуль на исполнение
    public function run() {
        global $tmpl;
        $mode = request('mode');
        $tmpl->setTitle($this->getName());
        $tmpl->addBreadcrumb('Главная', '/');
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $tmpl->setContent("<h1>" . $this->getName() . "</h1>");
        if ($mode == '') {
            $tmpl->addBreadcrumb($this->getName(), '');
            $transport = request('transport', request('opt'));
            $to = request('to');
            $send_params = $this->filterRecipients($transport, $to);
            $this->viewMessageForm($send_params);
        }
        else if ($mode == 'send') {
            $tmpl->addBreadcrumb('Отправка', '');
            $this->sendMessage();
        }
        else if ($mode == 'call_request') {
            $tmpl->addBreadcrumb('Заказ обратного звонка', '');
            $tmpl->setContent("<h1>Заказ обратного звонка</h1>");
            $this->viewCallRequestForm();
        }
        else if ($mode == 'send_call_request') {
            $tmpl->addBreadcrumb('Заказ обратного звонка', $this->link_prefix.'?mode=call_request');
            $tmpl->addBreadcrumb('Отправка', '');
            $this->sendCallRequest();
        }
        else {
            throw new \NotFoundException("Неизвестная опция");
        }
    }
    
}
