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
namespace modules\site;

/// Модуль *вопрос - ответ*
class qa extends \IModule {

    public function __construct() {
        parent::__construct();
        $this->link_prefix = '/qa.php';
        $this->acl_object_name = 'generic.qa';
    }

    // Получить название модуля
    /// @return Строка с именем
    public function getName() {
        return 'Вопрос - ответ';
    }

    /// Получить описание модуля
    /// @return Строка с описанием
    public function getDescription() {
        return 'Пользователи задают вопросы, сотрудники отвечают.';
    }
    
    // Получить список вопросов и ответов
    public function getQAList() {
        global $db;
        $wa = '';
        if(@$_SESSION['uid']>0) {
            $uid = intval($_SESSION['uid']);
            $wa = " OR `qu_id`='$uid'";
        }
        $res = $db->query("SELECT `qa`.`id`, `qa`.`question`, `qa`.`qu_id`, `qa`.`date`, `qa`.`answer`, `qa`.`au_id`, `qu`.`name` AS `qu_name`, `au`.`name` AS `au_name`"
                . " FROM `qa`"
                . " LEFT JOIN `users` AS `qu` ON `qu`.`id`=`qa`.`qu_id`"
                . " LEFT JOIN `users` AS `au` ON `au`.`id`=`qa`.`au_id`"
                . " WHERE `answer` != '' $wa"
                . " ORDER BY `qa`.`id` DESC");
        $ret = [];
        while($line = $res->fetch_assoc()) {
            $ret[$line['id']] = $line;
        }
        return $ret;
    }
    
    public function viewItem($id) {
        global $tmpl, $db;
        $id = intval($id);
        $res = $db->query("SELECT `qa`.`id`, `qa`.`question`, `qa`.`qu_id`, `qa`.`date`, `qa`.`answer`, `qa`.`au_id`, `qu`.`name` AS `qu_name`, `au`.`name` AS `au_name`"
            . " FROM `qa`"
            . " LEFT JOIN `users` AS `qu` ON `qu`.`id`=`qa`.`qu_id`"
            . " LEFT JOIN `users` AS `au` ON `au`.`id`=`qa`.`au_id`"
            . " WHERE `qa`.`id` = '$id'");
        if(!$res->num_rows) {
            throw new \Exception("Вопрос не найден!");
        }
        $data = $res->fetch_assoc();
        $q = html_out($data['question']);
        $a = html_out($data['answer']);
        $qu_name = html_out($data['qu_name']);
        $qu_name = html_out($data['qu_name']);
        $au_name= html_out($data['au_name']);
        if($a=='') {
            $a = 'пока отсутствует';
        }
        $tmpl->addContent("<ul>"
            . "<li><b>Вопрос:</b> $q</li>"
            . "<li><b>Спрашивал:</b> $qu_name</li>"
            . "<li><b>Дата:</b> {$data['date']}</li>"
            . "<li><b>Ответ:</b> $a<br><i>$au_name</i></li>"
            . "</ul>");
    }


    /// Показать страницу с вопросами и ответами
    public function viewPage() {
        global $tmpl;
        $data = $this->getQAList();
        if(count($data)==0) {
            $tmpl->msg("Похоже, что здесь ещё ничего нет");
        }
        foreach($data as $line) {
            $q = html_out($line['question']);
            $a = html_out($line['answer']);
            $qu_name = html_out($line['qu_name']);
            $au_name= html_out($line['au_name']);
            if($a=='') {      
                if(\acl::testAccess($this->acl_object_name, \acl::UPDATE, true)) {
                    $a = "<a href='{$this->link_prefix}&amp;mode=answer&amp;q_id={$line['id']}'>Ответить</a>";
                }
                else {
                    $a = "Ответ пока отсутствует";
                }
                $tmpl->addContent("<ul>"
                    . "<li><b>Вопрос:</b> $q<br><i>$qu_name</i></li>"
                    . "<li>$a</li>"
                    . "</ul>");
            } else {
                $tmpl->addContent("<ul>"
                    . "<li><b>Вопрос:</b> $q<br><i>$qu_name</i></li>"
                    . "<li><b>Ответ:</b> $a<br><i>$au_name</i></li>"
                    . "</ul>");
            }            
        }
        if(!auth() && !\acl::testAccess($this->acl_object_name, \acl::CREATE, true)) {
            $tmpl->addContent("<div class='text'>Анонимные посетители не могут задавать вопросы.<br>"
                    . "<a href='{$this->link_prefix}&amp;mode=add'>Авторизоваться и задать вопрос</a></div>");
        }
        else if(\acl::testAccess($this->acl_object_name, \acl::CREATE)) {
            $tmpl->addContent("<a href='{$this->link_prefix}&amp;mode=add'>Задать вопрос</a>");
        }        
    }
    
    /// Сформировать html код формы отправки вопроса
    protected function getQuestionForm($text = '') {
        $ret ="<form action='{$this->link_prefix}' method='post'>
            <input type='hidden' name='mode' value='send'>
            Текст вопроса: <small>(до 256 символов)</small><br>
            <textarea name='text' rows='5' cols='40'>".html_out($text)."</textarea><br>
            <b style='color:#c00'>Уведомление о полученном ответе может быть направлено по адресу или телефону, указанному при регистрации</b><br>
            <input type='submit' value='Отправить'>
            </form>";
        return $ret;
    }
    
    /// Сформировать html код формы отправки ответа
    protected function getAnswerForm($id, $text = '') {
        $id = intval($id);
        $ret ="<form action='{$this->link_prefix}' method='post'>
            <input type='hidden' name='mode' value='saveanswer'>
            <input type='hidden' name='q_id' value='$id'>
            Текст ответа:<br>
            <textarea name='text' rows='5' cols='40'>".html_out($text)."</textarea><br>
            <label><input type='checkbox' name='notify' value='1' checked>Уведомить посетителя об ответе</label><br>
            <input type='submit' value='Отправить'>
            </form>";
        return $ret;
    }
    
    /// Отобразить форму отправки вопроса
    public function viewQuestionForm() {
        global $tmpl;
        $tmpl->addContent($this->getQuestionForm());
        
    }
    
    /// Отобразить форму написания ответа
    public function viewAnswerForm($id) {
        global $tmpl, $db;
        $id = intval($id);
        $res = $db->query("SELECT `qa`.`id`, `qa`.`question`, `qa`.`qu_id`, `qa`.`date`, `qa`.`au_id`, `qu`.`name` AS `qu_name`"
            . " FROM `qa`"
            . " LEFT JOIN `users` AS `qu` ON `qu`.`id`=`qa`.`qu_id`"
            . " WHERE `qa`.`id` = '$id'");
        if(!$res->num_rows) {
            throw new \Exception("Вопрос не найден!");
        }
        $data = $res->fetch_assoc();
        $q = html_out($data['question']);
        $qu_name = html_out($data['qu_name']);
        $tmpl->addContent("<ul>"
            . "<li><b>Вопрос:</b> $q</li>"
            . "<li><b>Спрашивал:</b>$qu_name</li>"
            . "<li><b>Дата:</b>{$data['date']}</li>"
            . "</ul>");
        $tmpl->addContent($this->getAnswerForm($id));        
    }
    
    /// Сохранить вопрос
    public function saveQuestion($question) {
        global $db;
        return $db->insertA('qa', ['qu_id'=>$_SESSION['uid'], 'question'=>$question, 'date' => date("Y-m-d H:is:s")]);        
    }
    
    /// Сохранить вопрос
    public function saveAnswer($id, $answer) {
        global $db;
        return $db->updateA('qa', $id, ['au_id'=>$_SESSION['uid'], 'answer'=>$answer]);        
    }

    /// Отправка уведомления об ответе (если есть контакт)
    public function notify($q_id) {
        global $tmpl, $db;        
        $pref = \pref::getInstance();
        $q_id = intval($q_id);
        $res = $db->query("SELECT `qa`.`id`, `qa`.`question`, `qa`.`qu_id`, `qa`.`date`, `qu`.`name` AS `qu_name`"
            . " FROM `qa`"
            . " LEFT JOIN `users` AS `qu` ON `qu`.`id`=`qa`.`qu_id`"
            . " WHERE `qa`.`id` = '$q_id'");
        if(!$res->num_rows) {
            throw new \Exception("Вопрос не найден!");
        }
        $q_data = $res->fetch_assoc();
        $user_data = getUserProfile($q_data['qu_id']);
        $smsphone = @$user_data['main']['reg_phone'];
        $data = $res->fetch_assoc();
        if (preg_match('/^\+79\d{9}$/', $smsphone)) {
            require_once('include/sendsms.php');
            $sms_text = 'Получен ответ на вопрос, заданный на сайте '.$pref->site_name.'. Прочитать: http://'.$pref->site_name.$this->link_prefix.'&mode=view&q_id='.$q_id;
            $sender = new \SMSSender();
            $sender->setNumber($smsphone);
            $sender->setContent($sms_text);
            $sender->send();
            $tmpl->msg("Уведомление отправлено по SMS", "ok");
        }
        $user_msg = "Уважаемый клиент!\n"
               . "Получен ответ на вопрос, заданный на сайте {$pref->site_name}:\n"
               . "{$q_data['question']}\n"
               . "Прочитать ответ можно, перейдя по ссылке: http://{$pref->site_name}{$this->link_prefix}&mode=view&q_id={$q_id}";
        if(@$user_data['main']['reg_email']) {            
            $subject = "Ответ на вопрос N {$q_id} на {$pref->site_name}";
            mailto($user_data['main']['reg_email'], $subject, $user_msg);
            $tmpl->msg("Уведомление отправлено по email", "ok");
        }
        if(@$user_data['dop']['jid']) {
            require_once(\cfg::getroot('location').'/common/XMPPHP/XMPP.php');
            $xmppclient = new \XMPPHP\XMPP( 
                \cfg::get('xmpp', 'host'), \cfg::get('xmpp', 'port'), \cfg::get('xmpp', 'login'), \cfg::get('xmpp', 'pass'), 'MultiMag r'.MULTIMAG_REV);
            $xmppclient->connect();
            $xmppclient->processUntil('session_start');
            $xmppclient->presence();              
            $xmppclient->message($user_data['dop']['jid'], $user_msg);
            $xmppclient->disconnect();            
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
            \acl::accessGuard($this->acl_object_name, \acl::VIEW);
            $this->viewPage();
        }
        else if ($mode == 'view') {
            $q_id = rcvint('q_id');
            $tmpl->addBreadcrumb('Вопрос N'.$q_id, '');
            \acl::accessGuard($this->acl_object_name, \acl::VIEW);
            $this->viewItem($q_id);
        }
        else if ($mode == 'add') {
            $tmpl->addBreadcrumb('Задать вопрос', '');
            \acl::accessGuard($this->acl_object_name, \acl::CREATE);
            $this->viewQuestionForm();
        }
        else if ($mode == 'send') {
            $tmpl->addBreadcrumb('Задать вопрос', $this->link_prefix.'&amp;mode=add');
            $tmpl->addBreadcrumb('Отправка вопроса', '');
            \acl::accessGuard($this->acl_object_name, \acl::CREATE);
            $question = request('text');
            $this->saveQuestion($question);
            $tmpl->msg("Вопрос отправлен сотрудникам компании", "ok");
            $this->viewPage();
        }
        else if ($mode == 'answer') {
            $tmpl->addBreadcrumb('Пишем ответ...', '');
            \acl::accessGuard($this->acl_object_name, \acl::UPDATE);
            $this->viewAnswerForm(rcvint('q_id'));
        }
        else if ($mode == 'saveanswer') {
            $q_id = rcvint('q_id');
            $tmpl->addBreadcrumb('Пишем ответ...', $this->link_prefix.'&amp;mode=answer&amp;q_id='.$q_id);
            $tmpl->addBreadcrumb('Сохранение ответа', '');
            \acl::accessGuard($this->acl_object_name, \acl::UPDATE);
            $answer = request('text');
            $this->saveAnswer($q_id, $answer);
            $tmpl->msg("Ответ сохранён", "ok");
            if(request('notify')) {
                $this->notify($q_id);
            }
            $this->viewPage();
        }
        else if ($mode == 'send_call_request') {
            $tmpl->addBreadcrumb('Заказ обратного звонка', $this->link_prefix.'?mode=call_request');
            $tmpl->addBreadcrumb('Отправка', '');
            $this->sendCallRequest();
        }
        else if ($mode == 'ajax_call_request') {
            $tmpl->ajax = 1;
            $tmpl->addBreadcrumb('Заказ обратного звонка', $this->link_prefix.'?mode=call_request');
            $tmpl->addBreadcrumb('Отправка', '');
            $this->sendCallRequest();
        }
        else {
            throw new \NotFoundException("Неизвестная опция");
        }
    }
    
}
