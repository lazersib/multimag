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

namespace Modules\Admin;

/// Настройка почтовых ящиков и алиасов
class comments extends \IModule {

    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'admin.comments';
    }

    public function getName() {
        return 'Администрирование комментариев';
    }
    
    public function getDescription() {
        return '';  
    }

    protected function renderList() {
        global $db, $tmpl;
        $res = $db->query("SELECT `comments`.`id`, `date`, `object_name`, `object_id`, `autor_name`, `autor_email`, `autor_id`, `text`, `rate`, `ip`, `user_agent`, `comments`.`response`, `users`.`name` AS `user_name`, `users`.`reg_email` AS `user_email`
	FROM `comments`
	INNER JOIN `users` ON `users`.`id`=`comments`.`autor_id`
	ORDER BY `comments`.`id` DESC");
        $tmpl->addContent("<h1 id='page-title'>Последние коментарии</h1>
	<table class='list' width='100%'>
	<tr><th>ID</th><th>Дата</th><th>Объект</th><th>Автор</th><th>e-mail</th><th>Текст коментария</th><th>Оценка</th><th>Ответ</th><th>IP адрес</th></tr>");
        while ($line = $res->fetch_assoc()) {
            $object = "{$line['object_name']}:{$line['object_id']}";
            if ($line['object_name'] == 'product') {
                $object = "<a href='/vitrina.php?mode=product&amp;p={$line['object_id']}'>$object</a>";
            }
            $email = $line['autor_id'] ? $line['user_email'] : $line['autor_email'];
            $email = "<a href='mailto:$email'>$email</a>";
            $autor = $line['autor_id'] ? "{$line['autor_id']}:<a href='/adm.php?mode=users&amp;sect=view&amp;user_id={$line['autor_id']}'>{$line['user_name']}</a>" : $line['autor_name'];
            $response = $line['response'] ? html_out($line['response']) . 
                    "<br><a href='{$this->link_prefix}&amp;sect=response&amp;id={$line['id']}'>Правка</a>" 
                    : "<a href='{$this->link_prefix}&amp;sect=response&amp;id={$line['id']}'>Ответить</a>";
            $html_text = html_out($line['text']);
            $tmpl->addContent("<tr>
		<td>{$line['id']} <a href='{$this->link_prefix}&amp;sect=remove&amp;id={$line['id']}'><img src='/img/i_del.png' alt='Удалить'></a></td>
		<td>{$line['date']}</td><td>$object</td><td>$autor</td> <td>$email</td><td>$html_text</td><td>{$line['rate']}</td><td>$response</td><td>{$line['ip']}</td></tr>");
        }
        $tmpl->addContent("</table>");
    }
    
    protected function renderResponseForm() {
        global $tmpl, $db;
        $id = rcvint('id');
        $opt = request('opt');
        $tmpl->addBreadcrumb('Ответ на коментарий с ID ' . $id, '');
        if ($opt) {
            \acl::accessGuard($this->acl_object_name, \acl::UPDATE);
            $sql_text = $db->real_escape_string(request('text'));
            $res = $db->query("UPDATE `comments` SET `response`='$sql_text', `responser`='{$_SESSION['uid']}' WHERE `id`='$id'");
            if($db->affected_rows>0) {
                $tmpl->msg("Коментарий сохранен успешно", 'ok');
            } else {
                $tmpl->msg("Не удалось сохранить комментарий", 'err');
            }
        } elseif(!\acl::testAccess($this->acl_object_name, \acl::UPDATE)) {
            $tmpl->msg("У вас нет привилегий для ответа на комментарии", 'err');
        }
        $res = $db->query("SELECT `comments`.`id`, `date`, `object_name`, `object_id`, `autor_name`, `autor_email`, `autor_id`, `text`, `rate`, `ip`, `user_agent`, `comments`.`response`, `users`.`name` AS `user_name`, `users`.`reg_email` AS `user_email`
        FROM `comments`
        INNER JOIN `users` ON `users`.`id`=`comments`.`autor_id`
        WHERE `comments`.`id`='$id'");
        $line = $res->fetch_assoc();
        if (!$line) {
            throw new Exception("Коментарий не найден!");
        }
        $autor = $line['autor_id'] ? "{$line['autor_id']}:<a href='/adm.php?mode=users&amp;sect=view&amp;user_id={$line['autor_id']}'>{$line['user_name']}</a>" : $line['autor_name'];
        $object = "{$line['object_name']}:{$line['object_id']}";
        $html_text = html_out($line['text']);
        $html_response = html_out($line['response']);
        $tmpl->addContent("<h1 id='page-title'>Ответ на коментарий N{$line['id']}</h1>
        <div>{$line['date']} $autor для $object пишет:<br>$html_text</div>
        <form action='{$this->link_prefix}' method='post'>
        <input type='hidden' name='sect' value='response'>
        <input type='hidden' name='id' value='{$line['id']}'>
        <input type='hidden' name='opt' value='save'>
        Ваш ответ (500 символов максимум):<br>
        <textarea name='text' class='text'>$html_response</textarea><br>
        <button type='submit'>Сохранить</button>
        </form>");
        
    }
    
    protected function renderRemoveForm() {
        global $tmpl, $db;
        $id = rcvint('id');
        $opt = request('opt');
        $tmpl->addBreadcrumb('Удаление комментария с ID ' . $id, '');
        if ($opt) {
            \acl::accessGuard($this->acl_object_name, \acl::DELETE);
            $db->query("DELETE FROM `comments` WHERE `id`='$id'");
            if($db->affected_rows>0) {
                $tmpl->msg("Коментарий удалён успешно", 'ok');
            } else {
                $tmpl->msg("Не удалось удалить комментарий", 'err');
            }
        } else {
            if(!\acl::testAccess($this->acl_object_name, \acl::DELETE)) {
                $tmpl->msg("У вас нет привилегий для удаления комментариев", 'err');
            }
            $res = $db->query("SELECT `comments`.`id`, `date`, `object_name`, `object_id`, `autor_name`, `autor_email`, `autor_id`, `text`, `rate`, `ip`, `user_agent`, `comments`.`response`, `users`.`name` AS `user_name`, `users`.`reg_email` AS `user_email`
            FROM `comments`
            INNER JOIN `users` ON `users`.`id`=`comments`.`autor_id`
            WHERE `comments`.`id`='$id'");
            $line = $res->fetch_assoc();
            if (!$line) {
                throw new Exception("Коментарий не найден!");
            }
            $autor = $line['autor_id'] ? "{$line['autor_id']}:<a href='/adm.php?mode=users&amp;sect=view&amp;user_id={$line['autor_id']}'>{$line['user_name']}</a>" : $line['autor_name'];
            $object = "{$line['object_name']}:{$line['object_id']}";
            $html_text = html_out($line['text']);
            $tmpl->addContent("<h1 id='page-title'>Ответ на коментарий N{$line['id']}</h1>
            <div>{$line['date']} $autor для $object пишет:<br>$html_text</div>
            <form action='{$this->link_prefix}' method='post'>
            <input type='hidden' name='sect' value='remove'>
            <input type='hidden' name='id' value='{$line['id']}'>
            <input type='hidden' name='opt' value='exec'>
            <button type='submit'>Подтверждаю удаление комментария</button>
            </form>");
        }
    }
    
    public function run() {
        global $tmpl;
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect');
        switch ($sect) {
            case '':
                $tmpl->addBreadcrumb($this->getName(), '');
                $this->renderList();
                break;
            case 'response':
                $this->renderResponseForm();
                break;
            case 'remove':
                $this->renderRemoveForm();
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

}
