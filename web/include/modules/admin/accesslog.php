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

namespace modules\admin;

/// Журнал обращений/посещений
class accessLog extends \IModule {

    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'admin.accesslog';
    }

    public function getName() {
        return 'Журнал посещений';
    }
    
    public function getDescription() {
        return 'Модуль для просмотра статистики посещений сайта.';  
    }
    

    protected function viewLog() {
        global $db, $tmpl;
        $tmpl->addContent("<h1>Журнал посещений</h1>");
	if (request('m')) {
            $g = " GROUP BY `ip`";
            $tmpl->addContent("<a href='{$this->link_prefix}&amp;m=ng'>Без группировки</a><br><br>");
        } else {
            $g = '';
        }
	$res = $db->query("SELECT * FROM `counter` $g ORDER BY `date` DESC");
	$tmpl->addContent("<table class='list'><tr><th>IP</th><th>Страница</th><th>Ссылка (referer)</th><th>UserAgent</th><th>Дата</th></tr>");
	while ($nxt = $res->fetch_row()) {
		$dt = date("Y-m-d H:i:s", $nxt[1]);
		$tmpl->addContent("<tr><td>$nxt[2]</td><td>" . html_out($nxt[5]) . "<br><small>" . html_out($nxt[6]) . "</small></td><td>" . html_out($nxt[4]) . "</td><td>" . html_out($nxt[3]) . "</td><td>$dt</td></tr>");
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
                $this->viewLog();
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

}
