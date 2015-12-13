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

namespace modules\admin;

/// Администрирование сайтов
class sites extends \IModule {

    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'admin.sites';
    }

    public function getName() {
        return 'Администрирование сайтов';
    }
    
    public function getDescription() {
        return 'Модуль для управления параметрами сайтов.';  
    }
 
    public function run() {
        global $tmpl, $db;
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect');
        switch ($sect) {
            case '':
                $editor = new \ListEditors\SitesEditor($db);
                $editor->line_var_name = 'id';
                $editor->link_prefix = $this->link_prefix . '&sect=' . $sect;
                $editor->acl_object_name = $this->acl_object_name;
                $editor->run();
                break;            
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

}
