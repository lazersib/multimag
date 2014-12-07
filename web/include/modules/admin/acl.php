<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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

/// Управление привилегиями доступа пользователей
class Acl extends \IModule {

    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'admin_acl';
    }

    public function getName() {
        return 'Управление привилегиями доступа';
    }
    
    public function getDescription() {
        return 'Управление привилегиями доступа для зарегистрированныых и незарегистрированных пользователей и их групп. '
        . 'Привилегии, заданные для виртуального пользователя с ID=null применяются для всех пользователей, в т.ч. и для неавторизованных. '
        . 'Привилегии, заданные для виртуальной группы с ID=null, применяются для всех авторизованных пользователей.';  
    }

    public function run() {
        global $tmpl, $db;
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect');
        switch ($sect) {
            case '':
                $tmpl->addBreadcrumb($this->getName(), '');
                $tmpl->addContent("<p>".$this->getDescription()."</p>"
                    . "<ul>"
                    . "<li><a href='" . $this->link_prefix . "&amp;sect=groups'>Группы пользователей</li>"
                    . "<li><a href='" . $this->link_prefix . "&amp;sect=all'>Привилегии анонимных пользователей</li>"
                    . "<li><a href='" . $this->link_prefix . "&amp;sect=reg'>Привилегии зарегистрированных (и авторизованных) пользователей</li>"
                    . "</ul>");
                break;
            case 'groups':
                $editor = new \ListEditors\AccessGroupEditor($db);
                $editor->line_var_name = 'id';
                $editor->link_prefix = $this->link_prefix . '&sect=' . $sect;
                $editor->acl_object_name = $this->acl_object_name;
                $editor->run();
                break;
            case 'amap':
                $this->renderAMap($tmpl, $db);
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

    // Вывод списка групп пользователей
    protected function renderUsersGroupsList($tmpl, $db) {
        $tmpl->addBreadcrumb('Группы пользователей', '');
        $tmpl->addContent("<table class='list'><tr><th>N</th><th>Название</th><th>Описание</th></tr>");
	$res=$db->query("SELECT `id`,`name`,`comment` FROM `users_grouplist`");
	while($nxt = $res->fetch_row()) {
		$tmpl->addContent("<tr><td>$nxt[0]<a href='?mode=gre&amp;g=$nxt[0]'><img src='/img/i_edit.png' alt='Изменить'></a></td><td><a href='?mode=group_acl&amp;g=$nxt[0]'>$nxt[1]</a></td><td>$nxt[2]</td></tr>");
	}
	$tmpl->addContent("</table><a href='?mode=gre'>Новая группа</a>");
    }
}
