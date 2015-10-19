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
class MailConfig extends \IModule {

    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'admin.mailconfig';
    }

    public function getName() {
        return 'Почтовые домены, ящики, алиасы';
    }
    
    public function getDescription() {
        return 'Модуль для настройки подконтрольного почтового сервера. Для работы модуля в конфигурационном файле'
        . ' необходимо указать параметры подключения к базе данных. Ящик доступен каждому пользователю, являющемуся сотрудником.';  
    }

    protected function calcUMap($db) {
        $umap = array();
        $res = $db->query("SELECT `email`, `user` FROM `view_aliases`");
        while ($line = $res->fetch_assoc()) {
            $umap[$line['user']][] = $line['email'];
        }
        return $umap;
    }

    protected function calcAMap($db) {
        $umap = array();
        $res = $db->query("SELECT `email`, `user` FROM `view_aliases`");
        while ($line = $res->fetch_assoc()) {
            $umap[$line['email']][] = $line['user'];
        }
        return $umap;
    }
    
    protected function renderUMap($tmpl, $db) {
        $tmpl->addBreadcrumb('Карта почтовых ящиков', '');
        $tmpl->addContent("<table class='list'>"
            . "<tr><th>Ящик</th><th>Алиас</th>");
        $map = $this->calcUMap($db);
        foreach ($map as $email=>$users) {
            $span = count($users);
            $tmpl->addContent("<tr><td rowspan='$span'>".html_out($email)."</td>");
            $fl = 1;
            foreach($users as $user) {
                if(!$fl) {
                    $tmpl->addContent("<tr>");
                    $fl = 0;
                }
                $tmpl->addContent("<td>".html_out($user)."</td></tr>");
            }
        }
        
        $tmpl->addContent("</table>");
    }
    
    protected function renderAMap($tmpl, $db) {
        $tmpl->addBreadcrumb('Карта почтовых алиасов', '');
        $tmpl->addContent("<table class='list'>"
            . "<tr><th>Алиас</th><th>Ящик</th>");
        $map = $this->calcAMap($db);
        foreach ($map as $email=>$users) {
            $span = count($users);
            $tmpl->addContent("<tr><td rowspan='$span'>".html_out($email)."</td>");
            $fl = 1;
            foreach($users as $user) {
                if(!$fl) {
                    $tmpl->addContent("<tr>");
                    $fl = 0;
                }
                $tmpl->addContent("<td>".html_out($user)."</td></tr>");
            }
        }
        
        $tmpl->addContent("</table>");
    }
    
    public function run() {
        global $CONFIG, $tmpl;
        if (!isset($CONFIG['admin_mailconfig'])) {
            throw new \Exception("Модуль не настроен!");
        }
        if (!is_array($CONFIG['admin_mailconfig'])) {
            throw new \Exception("Неверные настройки модуля!");
        }
        $conf = $CONFIG['admin_mailconfig'];
        $db = new \MysqiExtended($conf['db_host'], $conf['db_login'], $conf['db_pass'], $conf['db_name']);
        if ($db->connect_error) {
            throw new Exception("Не удалось соединиться с базой данных настроек почтового сервера");
        }
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect');
        switch ($sect) {
            case '':
                $tmpl->addBreadcrumb($this->getName(), '');
                $tmpl->addContent("<p>".$this->getDescription()."</p>"
                    . "<ul>"
                    . "<li><a href='" . $this->link_prefix . "&amp;sect=domains'>Домены</li>"
                    . "<li><a href='" . $this->link_prefix . "&amp;sect=alias'>Алиасы</li>"
                    . "<li><a href='" . $this->link_prefix . "&amp;sect=umap'>Карта почтовых ящиков</li>"
                    . "<li><a href='" . $this->link_prefix . "&amp;sect=amap'>Карта почтовых алиасов</li>"
                    . "</ul>");
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
