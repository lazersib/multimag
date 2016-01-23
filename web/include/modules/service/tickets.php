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

namespace modules\service;

/// Класс, реализующий простейший треккер задач
class tickets extends \IModule {
    
    var $states = array(
        'NEW'       => 'Новый',
        'ACCEPTED'  => 'Принят',
        'CLOSED'    => 'Закрыт',        
    );
    
    var $resolutions = array(
        'SUCCESS'   => 'Исполнено',
        'ERROR'     => 'Ошибочно',
        'WONTFIX'   => 'Не решено',
        'DOUBLE'    => 'Повтор',
        'ESCALATED' => 'Переведено'
    );
    
    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'service.tickets';
    }

    public function getName() {
        return 'Задачи';
    }
    
    public function getDescription() {
        return 'Учёт исполнения задач, выданных сотрудникам';  
    }
    
    /// Меню треккера задач
    protected function PMenu($param) {
        global $tmpl;           
        $list = array(
            'wmy'     => ['name' => 'Мои текущие задачи'],
            'wok'     => ['name' => 'Мои выполненные задачи'],
            'all'     => ['name' => 'Все задачи'],
            'fmy'     => ['name' => 'Задачи от меня'],
            'new'     => ['name' => 'Новая задача'],
        );        
        $tmpl->addTabsWidget($list, $param, $this->link_prefix, 'sect');
    }
    
    /// Формирует список задач, назначенных на текущего пользователя
    protected function showMyWork() {
        global $tmpl, $db;        
        
        $res = $db->query("SELECT `tickets`.`id`, `tickets`.`date`, `tickets`.`theme`, `tickets_priority`.`name` AS `prio_name`, `users`.`name` AS `author_name`,
                `tickets`.`to_date`, `tickets`.`state`, `tickets_priority`.`color`
            FROM `tickets`
            INNER JOIN `tickets_responsibles` ON `tickets_responsibles`.`ticket_id` = `tickets`.`id` AND `tickets_responsibles`.`user_id`='{$_SESSION['uid']}'
            LEFT JOIN `users` ON `users`.`id`=`tickets`.`autor`
            LEFT JOIN `tickets_priority` ON `tickets_priority`.`id`=`tickets`.`priority`
            WHERE `tickets`.`state`!='CLOSED'
            ORDER BY `tickets`.`priority` DESC, `tickets`.`to_date` DESC, `tickets`.`date`");
        $tmpl->addContent("<table width='100%' class='list'>"
            . "<tr><th>N</th><th>Краткое описание</th><th>Важность</th><th>Статус</th><th>Дата</th><th>Автор</th><th>Срок</th></tr>");
        while ($line = $res->fetch_assoc()) {
            if(isset($this->states[$line['state']])) {
                $line['state'] = $this->states[$line['state']];
            }
            $tmpl->addContent("<tr class='pointer' style='color: #{$line['color']}'>"
                . "<td><a href='{$this->link_prefix}&amp;sect=view&id={$line['id']}'>#{$line['id']}</a></td>"
                . "<td>".html_out($line['theme'])."</td><td>".html_out($line['prio_name'])."</td><td>".html_out($line['state'])."</td>"
                . "<td>".html_out($line['date'])."</td><td>".html_out($line['author_name'])."</td><td>".html_out($line['to_date'])."</td></tr>");
        }
        $tmpl->addContent("</table>");
    }
    
    /// Формирует список закрытых задач, назначенных на текущего пользователя
    protected function showMyClosed() {
        global $tmpl, $db;        
        
        $res = $db->query("SELECT `tickets`.`id`, `tickets`.`date`, `tickets`.`theme`, `tickets_priority`.`name` AS `prio_name`, `users`.`name` AS `author_name`,
                `tickets`.`to_date`, `tickets`.`state`, `tickets_priority`.`color`, `tickets`.`resolution`
            FROM `tickets`
            INNER JOIN `tickets_responsibles` ON `tickets_responsibles`.`ticket_id` = `tickets`.`id` AND `tickets_responsibles`.`user_id`='{$_SESSION['uid']}'
            LEFT JOIN `users` ON `users`.`id`=`tickets`.`autor`
            LEFT JOIN `tickets_priority` ON `tickets_priority`.`id`=`tickets`.`priority`
            WHERE `tickets`.`state`='CLOSED'
            ORDER BY `tickets`.`priority` DESC, `tickets`.`to_date` DESC, `tickets`.`date`");
        $tmpl->addContent("<table width='100%' class='list'>"
            . "<tr><th>N</th><th>Краткое описание</th><th>Важность</th><th>Статус</th><th>Дата</th><th>Автор</th><th>Срок</th><th>Решение</th></tr>");
        while ($line = $res->fetch_assoc()) {
            if(isset($this->states[$line['state']])) {
                $line['state'] = $this->states[$line['state']];
            }
            if(isset($this->resolutions[$line['resolution']])) {
                $line['resolution'] = $this->resolutions[$line['resolution']];
            }
            $tmpl->addContent("<tr class='pointer' style='color: #{$line['color']}'>"
                . "<td><a href='{$this->link_prefix}&amp;sect=view&id={$line['id']}'>#{$line['id']}</a></td>"
                . "<td>".html_out($line['theme'])."</td><td>".html_out($line['prio_name'])."</td><td>".html_out($line['state'])."</td>"
                . "<td>".html_out($line['date'])."</td><td>".html_out($line['author_name'])."</td><td>".html_out($line['to_date'])."</td>"
                . "<td>".html_out($line['resolution'])."</td></tr>");
        }
        $tmpl->addContent("</table>");
    }
    
    /// Формирует список задач, созданных текущим пользователем
    protected function showMyTickets() {
        global $tmpl, $db; 
        $res = $db->query("SELECT `tickets`.`id`, `tickets`.`date`, `tickets`.`theme`, `tickets_priority`.`name` AS `prio_name`, 
                `tickets`.`to_date`, `tickets`.`state`, `tickets_priority`.`color`, `tickets`.`resolution`
            FROM `tickets`
            LEFT JOIN `tickets_priority` ON `tickets_priority`.`id`=`tickets`.`priority`
            WHERE `autor`='{$_SESSION['uid']}'
            ORDER BY `tickets`.`priority` DESC, `tickets`.`to_date`, `tickets`.`date`");
        $tmpl->addContent("<table width='100%' class='list'>"
            . "<tr><th>N</th><th>Краткое описание</th><th>Важность</th><th>Статус</th><th>Дата</th><th>Срок</th><th>Решение</th></tr>");
        while ($line = $res->fetch_assoc()) {
            if(isset($this->states[$line['state']])) {
                $line['state'] = $this->states[$line['state']];
            }
            if(isset($this->resolutions[$line['resolution']])) {
                $line['resolution'] = $this->resolutions[$line['resolution']];
            }
            $tmpl->addContent("<tr class='pointer' style='color: #{$line['color']}'>"
                . "<td><a href='{$this->link_prefix}&amp;sect=view&id={$line['id']}'>#{$line['id']}</a></td>"
                . "<td>".html_out($line['theme'])."</td><td>".html_out($line['prio_name'])."</td><td>".html_out($line['state'])."</td>"
                . "<td>".html_out($line['date'])."</td><td>".html_out($line['to_date'])."</td>"
                . "<td>".html_out($line['resolution'])."</td></tr>");
        }
        $tmpl->addContent("</table>");
    }
    
    /// Формирует список всех задач
    protected function showAllTickets() {
        global $tmpl, $db;        
        
        $res = $db->query("SELECT `tickets`.`id`, `tickets`.`date`, `tickets`.`theme`, `tickets_priority`.`name` AS `prio_name`, `users`.`name` AS `author_name`,
                `tickets`.`to_date`, `tickets`.`state`, `tickets_priority`.`color`, `tickets`.`resolution`
            FROM `tickets`
            INNER JOIN `tickets_responsibles` ON `tickets_responsibles`.`ticket_id` = `tickets`.`id`
            LEFT JOIN `users` ON `users`.`id`=`tickets`.`autor`
            LEFT JOIN `tickets_priority` ON `tickets_priority`.`id`=`tickets`.`priority`
            WHERE `tickets`.`state`!='CLOSED'
            ORDER BY `tickets`.`priority` DESC, `tickets`.`to_date` DESC, `tickets`.`date`");
        $tmpl->addContent("<table width='100%' class='list'>"
            . "<tr><th>N</th><th>Краткое описание</th><th>Важность</th><th>Статус</th><th>Дата</th><th>Автор</th><th>Срок</th><th>Решение</th></tr>");
        while ($line = $res->fetch_assoc()) {
            if(isset($this->states[$line['state']])) {
                $line['state'] = $this->states[$line['state']];
            }
            if(isset($this->resolutions[$line['resolution']])) {
                $line['resolution'] = $this->resolutions[$line['resolution']];
            }
            $tmpl->addContent("<tr class='pointer' style='color: #{$line['color']}'>"
                . "<td><a href='{$this->link_prefix}&amp;sect=view&id={$line['id']}'>#{$line['id']}</a></td>"
                . "<td>".html_out($line['theme'])."</td><td>".html_out($line['prio_name'])."</td><td>".html_out($line['state'])."</td>"
                . "<td>".html_out($line['date'])."</td><td>".html_out($line['author_name'])."</td><td>".html_out($line['to_date'])."</td>"
                . "<td>".html_out($line['resolution'])."</td></tr>");
        }
        $tmpl->addContent("</table>");
    }
    
    /// Показать задачу
    /// @param $id Номер задачи
    function viewTicket($id) {
        global $tmpl, $db;
        settype($id, 'int');
        $res = $db->query("SELECT `tickets`.`id`, `tickets`.`date`, `tickets`.`theme`, `tickets_priority`.`name` AS `prio_name`,
                `a`.`name` AS `author_name`, `tickets`.`to_date`, `tickets`.`state`, `tickets`.`text`, `tickets`.`state`, `tickets`.`resolution`
            FROM `tickets`
            LEFT JOIN `users` AS `a` ON `a`.`id`=`tickets`.`autor`
            LEFT JOIN `tickets_priority` ON `tickets_priority`.`id`=`tickets`.`priority`
            WHERE `tickets`.`id`='$id'");
        if(!$res->num_rows) {
            throw new \NotFoundException("Задача не найдена!");
        }
        $t_info = $res->fetch_assoc();
        $tmpl->addBreadcrumb('#'.$t_info['id'].': '.$t_info['theme'], '');
        if(isset($this->states[$t_info['state']])) {
            $t_info['state_txt'] = $this->states[$t_info['state']];
        } else {
            $t_info['state_txt'] = $t_info['state'];
        }
        $tmpl->addContent("<h1>" . html_out($t_info['theme']) . "</h1>
            <fieldset><legend>Информация о задаче</legend>
            <b>Дата создания:</b> {$t_info['date']}<br>
            <b>Важность:</b> {$t_info['prio_name']}<br>
            <b>Автор:</b> {$t_info['author_name']}<br>");
        $res = $db->query("SELECT `users`.`name`, `users_worker_info`.`worker_real_name`, `tickets_responsibles`.`user_id`
            FROM `tickets_responsibles`
            INNER JOIN `users` ON `users`.`id`=`tickets_responsibles`.`user_id`
            LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`tickets_responsibles`.`user_id`
            WHERE `tickets_responsibles`.`ticket_id` = $id");
        $no_cur_resp = true;
        if($res->num_rows) {
            $tmpl->addContent("<b>Исполнители:</b> ");
            while ($_line = $res->fetch_assoc()) {
                $tmpl->addContent(html_out($_line['worker_real_name']) . " (" . html_out($_line['name']) . "), ");
                if($_line['user_id'] == $_SESSION['uid']) {
                    $no_cur_resp = false;
                }
            }
            $tmpl->addContent("<br>");
        }
        $tmpl->addContent("<b>Срок:</b> {$t_info['to_date']}<br>
            <b>Состояние:</b> {$t_info['state_txt']}<br>
            <b>Описание:</b> " . html_out($t_info['text']) . "<br>");
        $res = $db->query("SELECT `users`.`name`, `tickets_log`.`date`, `tickets_log`.`text` FROM `tickets_log`
            LEFT JOIN `users` ON `users`.`id`=`tickets_log`.`uid`
            WHERE `ticket`='{$t_info['id']}'");
        if($res->num_rows) {
            $tmpl->addContent("<b>История:</b>
            <ul>");
            while ($nx = $res->fetch_row()) {
                $tmpl->addContent("<li><i>" . html_out($nx[1]) . "</i>, <b>$nx[0]:</b> " . html_out($nx[2]) . "</li>");
            }
            $tmpl->addContent("</ul><br>");
        }
        if($t_info['state']!='ACCEPTED' || $no_cur_resp) {
            $tmpl->addContent("
                <form action='{$this->link_prefix}' method='post'>
                <input type='hidden' name='sect' value='update'>
                <input type='hidden' name='opt' value='accept'>
                <input type='hidden' name='id' value='{$t_info['id']}'>
                <input type='submit' value='Принять к исполнению'></form>");
        }
        if(!$no_cur_resp){
            $tmpl->addContent("
                <form action='{$this->link_prefix}' method='post'>
                <input type='hidden' name='sect' value='update'>
                <input type='hidden' name='opt' value='decline'>
                <input type='hidden' name='id' value='{$t_info['id']}'>
                <input type='submit' value='Отказаться от задачи'></form>");
        }
        $tmpl->addContent("</fieldset>");
        $tmpl->addContent("<fieldset><legend>Закрыть задачу с пометкой</legend>
            <form action='{$this->link_prefix}' method='post'>
            <input type='hidden' name='sect' value='update'>
            <input type='hidden' name='opt' value='close'>
            <input type='hidden' name='id' value='{$t_info['id']}'>");
        $tmpl->addContent( \widgets::getEscapedSelect('resolution', $this->resolutions) );
        $tmpl->addContent("<input type='submit' value='Закрыть'></form></fieldset>
            <fieldset><legend>Добавить коментарий:</legend>
            <form action='{$this->link_prefix}' method='post'>
            <input type='hidden' name='sect' value='update'>
            <input type='hidden' name='opt' value='comment'>
            <input type='hidden' name='id' value='{$t_info['id']}'>
            <textarea name='comment'></textarea><br>
            <input type='submit' value='Добавить'></form></fieldset>

            <fieldset><legend>Изменить срок:</legend>
            <form action='{$this->link_prefix}' method='post'>
            <input type='hidden' name='sect' value='update'>
            <input type='hidden' name='opt' value='to_date'>
            <input type='hidden' name='id' value='{$t_info['id']}'>
            <input type='text' name='to_date' class='vDateField' value='{$t_info['to_date']}'>
            <input type='submit' value='Изменить'></form></fieldset>

            <fieldset><legend>Добавить исполнителя:</legend>
            <form action='{$this->link_prefix}' method='post'>
            <input type='hidden' name='sect' value='update'>
            <input type='hidden' name='opt' value='add_user'>
            <input type='hidden' name='id' value='{$t_info['id']}'>
            <select name='user_id'>");

        $res = $db->query("SELECT `users`.`id`, `users`.`name`, `users_worker_info`.`worker_real_name`
            FROM `users`
            INNER JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id`
            WHERE `users_worker_info`.`worker`>'0' AND `users`.`id` NOT IN 
                (SELECT `user_id` FROM `tickets_responsibles` WHERE `tickets_responsibles`.`ticket_id` = $id) 
            ORDER BY `users`.`name`");
        while ($nx = $res->fetch_row()) {
            $tmpl->addContent("<option value='$nx[0]'>$nx[1] - $nx[2] ($nx[0])</option>");
        }
        $tmpl->addContent("</select>
            <input type='submit' value='Добавить'></form></fieldset>

            <fieldset><legend>Убрать исполнителя:</legend>
            <form action='{$this->link_prefix}' method='post'>
            <input type='hidden' name='sect' value='update'>
            <input type='hidden' name='opt' value='del_user'>
            <input type='hidden' name='id' value='{$t_info['id']}'>
            <select name='user_id'>");

        $res = $db->query("SELECT `users`.`id`, `users`.`name`, `users_worker_info`.`worker_real_name` FROM `tickets_responsibles`
            INNER JOIN `users` ON `users`.`id`=`tickets_responsibles`.`user_id`
            LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`tickets_responsibles`.`user_id`
            WHERE `tickets_responsibles`.`ticket_id` = $id");
        while ($nx = $res->fetch_row()) {
            $tmpl->addContent("<option value='$nx[0]'>$nx[1] - $nx[2] ($nx[0])</option>");
        }
        $tmpl->addContent("</select>
            <input type='submit' value='Убрать'></form></fieldset>

            <fieldset><legend>Изменить приоритет:</legend>
            <form action='{$this->link_prefix}' method='post'>
            <input type='hidden' name='sect' value='update'>
            <input type='hidden' name='opt' value='prio'>
            <input type='hidden' name='id' value='{$t_info['id']}'>
            <select name='prio'>");
        $res = $db->query("SELECT `id`, `name`, `color` FROM `tickets_priority` ORDER BY `id`");
        while ($nx = $res->fetch_row()) {
            $tmpl->addContent("<option value='$nx[0]' style='color: #$nx[2]'>$nx[1] ($nx[0])</option>");
        }
        $tmpl->addContent("</select><input type='submit' value='Изменить'></form></fieldset>");
        
    }
    
    /// Формирует форму создания задачи
    function viewNewTicketForm() {
        global $tmpl, $db;        
        $tmpl->addBreadcrumb('Новая задача', '');
        $tmpl->addContent("<form action='{$this->link_prefix}' method='post'>
            <input type='hidden' name='sect' value='create'>
            Задача для:<br>
            <select name='to_uid'>");
        $res = $db->query("SELECT `users`.`id`, `users`.`name`, `users_worker_info`.`worker_real_name`
            FROM `users`
            INNER JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id`
            WHERE `users_worker_info`.`worker`>'0' ORDER BY `users`.`name`");
        while ($nxt = $res->fetch_row()) {
            $tmpl->addContent("<option value='$nxt[0]'>$nxt[1] - $nxt[2] ($nxt[0])</option>");
        }
        $tmpl->addContent("</select><br>Название:<br><input type='text' name='theme'><br>
            Важность, приоритет:<br><select name='prio'>");
        $res = $db->query("SELECT `id`, `name`, `color` FROM `tickets_priority` ORDER BY `id`");
        while ($nxt = $res->fetch_row()) {
            $tmpl->addContent("<option value='$nxt[0]' style='color: #$nxt[2]'>$nxt[1] ($nxt[0])</option>");
        }

        $tmpl->addContent("</select><br>
            Срок (указывать не обязательно):<br>
            <input type='text' name='to_date'  class='vDateField'><br>
            Описание задачи:<br>
            <textarea name='text'></textarea><br>
            <input type='submit' value='Назначить задачу'>
            </form>");
    }
    
    /// Создать задачу
    protected function createTicket() {
        global $db, $tmpl;
        $uid = @$_SESSION['uid'];
        $to_uid = rcvint('to_uid');
        $theme = request('theme');
        $prio = rcvint('prio');
        $to_date = rcvdate('to_date');
        $text = request('text');

        $theme_sql = $db->real_escape_string($theme);
        $text_sql = $db->real_escape_string($text);

        $db->query("INSERT INTO `tickets` (`date`, `autor`, `priority`, `theme`, `text`, `to_date`)
            VALUES ( NOW(), '$uid', '$prio', '$theme_sql', '$text_sql', '$to_date')");
        $id = $db->insert_id;
        $db->query("INSERT INTO `tickets_responsibles` (`ticket_id`, `user_id`) VALUES ($id, $to_uid)");
        $tmpl->msg("Задание назначено!", "ok");

        $res = $db->query("SELECT `reg_email` FROM `users` WHERE `id`='$to_uid'");
        list($email) = $res->fetch_row();

        $msg = "Для Вас новое задание от $uid: $theme - $text\n";
        if ($to_date) {
            $msg.="Выполнить до $to_date\n";
        }
        $pref = \pref::getInstance();
        $msg.="Посмотреть задание можно здесь: http://{$pref->site_name}{$this->link_prefix}&sect=view&id=$id";

        mailto($email, "У Вас Новое задание - $theme", $msg);
        $this->viewTicket($id);
    }
    
    protected function updateTicket($id) {
        global $db, $tmpl;
        settype($id, 'int');
        $opt = request('opt');
        $txt = '';
        $db->startTransaction();
        switch ($opt) {
            case 'accept':
                $txt = "принял задачу к исполнению";                
                $db->query("UPDATE `tickets` SET `state`='ACCEPTED' WHERE `id`='$id'");
                $no_cur_resp = true;
                $res = $db->query("SELECT `users`.`id`, `users`.`name`, `users_worker_info`.`worker_real_name` 
                    FROM `tickets_responsibles`
                    INNER JOIN `users` ON `users`.`id`=`tickets_responsibles`.`user_id`
                    LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`tickets_responsibles`.`user_id`
                    WHERE `tickets_responsibles`.`ticket_id` = $id");
                while ($nx = $res->fetch_row()) {
                    if($nx[0] == $_SESSION['uid']) {
                        $no_cur_resp = false;
                    }
                }
                if($no_cur_resp) {
                    $user_id = intval($_SESSION['uid']);
                    $db->query("INSERT INTO `tickets_responsibles` (`ticket_id`, `user_id`) VALUES ($id, $user_id)");
                }                
                break;
            case 'decline':
                $txt = "отказался от решения задачи";   
                $uid = intval($_SESSION['uid']);
                $db->query("DELETE FROM `tickets_responsibles` WHERE `ticket_id`=$id AND `user_id`=$uid");             
                break;
            case 'close':
                $resolution = request('resolution');
                if(isset($this->resolutions[$resolution])) {
                    $res_txt = $this->resolutions[$resolution];
                    $res_sql = $db->real_escape_string($resolution);
                } else {
                    throw new \NotFoundException('Решение не существует');
                }
                $db->query("UPDATE `tickets` SET `resolution`='$res_sql', `state`='CLOSED' WHERE `id`='$id'");
                $txt = "Закрыл задачу с решением *$res_txt*";
                break;
            case 'comment':
                $txt = "прокоментировал: ".request('comment');
                break;
            case 'to_date':
                $to_date = rcvdate('to_date');
                $db->query("UPDATE `tickets` SET `to_date`='$to_date' WHERE `id`='$id'");
                $txt = "Установил срок *$to_date*";
                break;
            case 'prio':
                $prio = rcvint('prio');
                $res = $db->query("SELECT `name` FROM `tickets_priority` WHERE `id`='$prio'");
                if(!$res->num_rows) {
                    throw new \NotFoundException('Приоритет не существует');
                }
                list($st_text) = $res->fetch_row();
                $db->query("UPDATE `tickets` SET `priority`='$prio' WHERE `id`='$id'");
                $txt = "Установил приоритет *$st_text ($prio)*";
                break;
            case 'add_user':
                $user_id = rcvint('user_id');
                $res = $db->query("SELECT `name` FROM `users` WHERE `id`='$user_id'");
                if(!$res->num_rows) {
                    throw new \NotFoundException('Исполнитель не существует');
                }
                list($user_name) = $res->fetch_row();
                $db->query("INSERT INTO `tickets_responsibles` (`ticket_id`, `user_id`) VALUES ($id, $user_id)");
                $txt = "добавил исполнителя $user_name ID $user_id";
                break;
            case 'del_user':
                $user_id = rcvint('user_id');
                $res = $db->query("SELECT `name` FROM `users` WHERE `id`='$user_id'");
                if(!$res->num_rows) {
                    throw new \NotFoundException('Исполнитель не существует');
                }
                list($user_name) = $res->fetch_row();
                $db->query("DELETE FROM `tickets_responsibles` WHERE `ticket_id`=$id AND `user_id`=$user_id");
                $txt = "убрал исполнителя $user_name ID $user_id";
                break;
        }
        if ($txt) {
            $txt_sql = $db->real_escape_string($txt);
            $uid = intval($_SESSION['uid']);
            $db->query("INSERT INTO `tickets_log` (`uid`, `ticket`, `date`, `text`)
		VALUES ('$uid', '$id', NOW(), '$txt_sql')");
            $res = $db->query("SELECT `users`.`reg_email`, `users`.`jid`, `tickets`.`theme`, `uwi`.`worker_email`, `uwi`.`worker_jid`, `users`.`name` AS `user_name`
                FROM `tickets`
		LEFT JOIN `users` ON `users`.`id`=`tickets`.`autor`
                LEFT JOIN `users_worker_info` AS `uwi` ON `uwi`.`user_id`=`users`.`id`
		WHERE `tickets`.`id`='$id'");
            $ticket_info = $res->fetch_assoc();
            $txt = $ticket_info['user_name'] . ' ' . $txt;
            $msg = "Изменение состояния Вашего задания #{$id}: {$ticket_info['theme']}\n$txt\n\n";
            $pref = \pref::getInstance();
            $msg.="Посмотреть задание можно здесь: http://{$pref->site_name}{$this->link_prefix}&sect=view&id=$id";
            $subject = "Change ticket #{$id}: {$ticket_info['theme']}";
            $email_msgs = array();
            $xmpp_msgs = array();
            if($ticket_info['reg_email']) {
                $email_msgs[$ticket_info['reg_email']] = ['email'=>$ticket_info['reg_email'], 'subject'=>$subject, 'message'=>$msg];
            }
            if($ticket_info['worker_email']) {
                $email_msgs[$ticket_info['worker_email']] = ['email'=>$ticket_info['worker_email'], 'subject'=>$subject, 'message'=>$msg];
            }
            
            if($ticket_info['jid']) {
                $xmpp_msgs[$ticket_info['jid']] = ['jid'=>$ticket_info['jid'], 'message'=>$msg];
            }
            if($ticket_info['worker_jid']) {
                $xmpp_msgs[$ticket_info['worker_jid']] = ['jid'=>$ticket_info['worker_jid'], 'message'=>$msg];
            }
            
            $res = $db->query("SELECT `users`.`id`, `users`.`reg_email`, `users`.`jid`, `uwi`.`worker_email`, `uwi`.`worker_jid`
                FROM `tickets_responsibles`
                INNER JOIN `users` ON `users`.`id`=`tickets_responsibles`.`user_id`
                LEFT JOIN `users_worker_info` AS `uwi` ON `uwi`.`user_id`=`tickets_responsibles`.`user_id`
                WHERE `tickets_responsibles`.`ticket_id` = $id");
            while ($line = $res->fetch_assoc()) {
                if($line['reg_email']) {
                    $email_msgs[$line['reg_email']] = ['email'=>$line['reg_email'], 'subject'=>$subject, 'message'=>$msg];
                }
                if($line['worker_email']) {
                    $email_msgs[$line['worker_email']] = ['email'=>$line['worker_email'], 'subject'=>$subject, 'message'=>$msg];
                }

                if($line['jid']) {
                    $xmpp_msgs[$line['jid']] = ['jid'=>$line['jid'], 'message'=>$msg];
                }
                if($line['worker_jid']) {
                    $xmpp_msgs[$line['worker_jid']] = ['jid'=>$line['worker_jid'], 'message'=>$msg];
                }
            }
            
            foreach($email_msgs as $email_line) {
                try {
                    if ($email_line['email']) {
                        mailto($email_line['email'], $email_line['subject'], $email_line['message']);
                        $tmpl->msg("Сообщение было отправлено по email!", "ok");
                    }
                } catch (Exception $e) {
                    writeLogException($e);
                    $tmpl->errorMessage("Невозможно отправить сообщение email на*".html_out($email_line['email']).'*');
                }                
            }
            foreach($xmpp_msgs as $xmpp_line) {
                $this->sendXMPP($xmpp_line['jid'], $xmpp_line['message']);
            }            
        }
        $db->commit();
        $tmpl->msg("Сделано!");
        $this->viewTicket($id);
    }

    protected function sendXMPP($jid, $msg) {
        global $tmpl;
        if ($jid && \cfg::get('xmpp', 'host') ) {
            try {
                require_once( \cfg::getroot('location') . '/common/XMPPHP/XMPP.php');
                $xmppclient = new \XMPPHP_XMPP(\cfg::get('xmpp', 'host'), \cfg::get('xmpp', 'port'), \cfg::get('xmpp','login'), \cfg::get('xmpp','pass')
                    , 'MultiMag r' . MULTIMAG_REV);
                $xmppclient->connect();
                $xmppclient->processUntil('session_start');
                $xmppclient->presence();
                $xmppclient->message($jid, $msg);
                $xmppclient->disconnect();
                $tmpl->msg("Сообщение было отправлено по XMPP!", "ok");
            } catch (Exception $e) {
                writeLogException($e);
                $tmpl->errorMessage("Невозможно отправить сообщение XMPP на адрес *".html_out($jid).'*');
            }
        }
    }
    
    public function run() {
        global $tmpl;
        \acl::accessGuard($this->acl_object_name, \acl::VIEW);
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect', 'wmy');
        $this->PMenu($sect);
        switch ($sect) {
            case '':
            case 'wmy':
                $tmpl->addBreadcrumb($this->getName(), '');                
                $this->showMyWork();
                break;
            case 'fmy':
                $tmpl->addBreadcrumb($this->getName(), '');                
                $this->showMyTickets();
                break;
            case 'all':
                $tmpl->addBreadcrumb($this->getName(), '');                
                $this->showAllTickets();
                break;
            case 'wok':
                $tmpl->addBreadcrumb($this->getName(), '');                
                $this->showMyClosed();
                break;
            case 'view':
                $id = rcvint('id');
                $this->viewTicket($id);
                break;
            case 'new':
                \acl::accessGuard($this->acl_object_name, \acl::CREATE);
                $this->viewNewTicketForm();
                break;
            case 'create':
                \acl::accessGuard($this->acl_object_name, \acl::CREATE);
                $this->createTicket();
                break;
            case 'update':
                \acl::accessGuard($this->acl_object_name, \acl::UPDATE);
                $id = rcvint('id');
                $this->updateTicket($id);
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

}
