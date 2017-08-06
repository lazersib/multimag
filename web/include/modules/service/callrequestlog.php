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

namespace modules\service;

/// Журнал запрошенных звонков
class callRequestlog extends \IModule {

    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'service.callrequestlog';
    }

    public function getName() {
        return 'Журнал запрошенных звонков';
    }
    
    public function getDescription() {
        return 'Модуль для просмотра журнала запрошенных звонков.';  
    }
    

    protected function viewLog() {
        global $tmpl, $db;
        $tmpl->setContent("<h1>Журнал запрошенных звонков</h1>
	<div class='content'>
	<table width='100%' class='list' cellspacing='0'>
	<tr><th>Дата запроса</th><th>Кому звонить?</th><th>Куда звонить?</th><th>Когда звонить?</th><th>IP</th></tr>");
	$res=$db->query("SELECT `id`, `request_date`, `name`, `phone`, `call_date`, `ip` FROM `log_call_requests` ORDER BY `request_date` DESC");
	while ($line = $res->fetch_assoc()) {
            $tmpl->addContent("<tr><td>" . html_out($line['request_date']) . "</td><td>" . html_out($line['name']) . "</td><td>" . html_out($line['phone']) . 
                "</td><td>" . html_out($line['call_date']) . "</td><td>{$line['ip']}</td></tr>");
        }
        $tmpl->addContent("</table></div>");
    }
    
    public function run() {
        global $tmpl;
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect');
        switch ($sect) {
            case '':
                $tmpl->addBreadcrumb($this->getName(), '');
                $tmpl->addContent("<p>".$this->getDescription()."</p>");
                $this->viewLog();
                break;
            case 'detail':
                $id = rcvint($id);
                $this->viewDetail($id);
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

}
