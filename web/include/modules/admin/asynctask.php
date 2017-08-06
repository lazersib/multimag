<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2017, BlackLight, TND Team, http://tndproject.org
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

namespace modules\admin;

/// Журнал обращений/посещений
class asyncTask extends \IModule {

    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'admin.asynctask';
    }

    public function getName() {
        return 'Ассинхронные задачи';
    }
    
    public function getDescription() {
        return 'Модуль для запуска асинхронных задач и просмотра из статуса.';  
    }
    
    protected function runTask($task) {
        global $db;
        if (!$task) {
            return false;
        }
        \acl::accessGuard($this->acl_object_name, \acl::APPLY);
        $sql_task = $db->real_escape_string($task);
        $db->query("INSERT INTO `async_workers_tasks` (`task`, `needrun`, `textstatus`) VALUES ('$sql_task', 1, 'Запланировано')");
        return true;
    }
    
    protected function getRunForm($task_code, $task_name) {
        return "<form action='{$this->link_prefix}' method='post'>"
        . "<input type='hidden' name='sect' value='run'>"
        . "<input type='hidden' name='task' value='".html_out($task_code)."'>"
        . "<button type='submit'>Запланировать запуск: ".html_out($task_name)."</button>"
        . "</form>";
    }

    protected function viewTaskList() {
        global $db, $tmpl, $CONFIG;
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>");
        $dir = $CONFIG['location'] . '/common/async/';
        if (!is_dir($dir)) {
            return;
        }
        $dh = opendir($dir);
        if(!$dh) {
            return;
        }
        
        $tmpl->addContent("<h2>Планирование</h1><ul>");
        while (($file = readdir($dh)) !== false) {
            if (preg_match('/.php$/', $file)) {
                $cn = explode('.', $file);
                include_once("$dir/$file");
                $class_name = '\\async\\' . $cn[0];
                $class = new $class_name(0);
                $nm = $class->getDescription();
                $tmpl->addContent("<li>".$this->getRunForm($cn[0], $nm)."</li>");
            }
        }
        closedir($dh);
        $tmpl->addContent("</ul>");

        $tmpl->addContent("<h2>Статус</h1><table class='list'><tr><th>ID</th><th>Задача</th><th>Ож.запуска</th><th>Состояние</th></tr>");
        $res = $db->query("SELECT `id`, `task`, `needrun`, `textstatus` FROM `async_workers_tasks` ORDER BY `id` DESC");
        while ($nxt = $res->fetch_row()) {
            $tmpl->addContent("<tr><td>$nxt[0]</td><td>$nxt[1]</td><td>$nxt[2]</td><td>" . html_out($nxt[3]) . "</td></tr>");
        }
        $tmpl->addContent("</table>");
    }

    public function run() {
        global $CONFIG, $tmpl, $db;
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect');
        switch ($sect) {
            case '':
                $tmpl->addBreadcrumb($this->getName(), '');
                $tmpl->addContent("<p>".$this->getDescription()."</p>");
                $this->viewTaskList();
                break;
            case 'run':
                $tmpl->addBreadcrumb("Планирование запуска ".html_out(request('task')), '');
                $this->runTask(request('task'));
                $tmpl->msg("Задача запланирована к выполнению", "ok");
                $this->viewTaskList();
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

}
