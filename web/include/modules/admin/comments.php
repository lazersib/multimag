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

namespace Modules\Admin;

/// Настройка почтовых ящиков и алиасов
class comments extends \IModule {

    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'admin_comments';
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
        $tmpl->addBreadcrumb('Последние коментарии', '');
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
            $autor = $line['autor_id'] ? "{$line['autor_id']}:<a href='/adm_users.php?mode=view&amp;id={$line['autor_id']}'>{$line['user_name']}</a>" : $line['autor_name'];
            $response = $line['response'] ? html_out($line['response']) . "<br><a href='?mode=response&amp;id={$line['id']}'>Правка</a>" : "<a href='?mode=response&amp;id={$line['id']}'>Ответить</a>";
            $html_text = html_out($line['text']);
            $tmpl->addContent("<tr>
		<td>{$line['id']} <a href='?mode=rm&amp;id={$line['id']}'><img src='/img/i_del.png' alt='Удалить'></a></td>
		<td>{$line['date']}</td><td>$object</td><td>$autor</td> <td>$email</td><td>$html_text</td><td>{$line['rate']}</td><td>$response</td><td>{$line['ip']}</td></tr>");
        }
        $tmpl->addContent("</table>");
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
            case 'domains':
                $editor = new \ListEditors\MailDomainsEditor($db);
                $editor->line_var_name = 'id';
                $editor->link_prefix = $this->link_prefix . '&sect=' . $sect;
                $editor->acl_object_name = $this->acl_object_name;
                $editor->run();
                break;
            case 'alias':
                $editor = new \ListEditors\MailAliasEditor($db);
                $editor->line_var_name = 'id';
                $editor->link_prefix = $this->link_prefix . '&sect=' . $sect;
                $editor->acl_object_name = $this->acl_object_name;
                $editor->run();
                break;
            case 'umap':
                $this->renderUMap($tmpl, $db);
                break;
            case 'amap':
                $this->renderAMap($tmpl, $db);
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

}
