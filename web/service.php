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
//

include_once("core.php");
SafeLoadTemplate($CONFIG['site']['inner_skin']);

try {
    need_auth($tmpl);
    $tmpl->setTitle("Сервис");
    $tmpl->addBreadcrumb('ЛК', '/user.php');
    $tmpl->addBreadcrumb('Сервис', '/service.php');
    $dir = "include/modules/service/";
    
    $mode = request('mode');
    if ($mode == '') {
        $tmpl->addBreadcrumb('Сервис', '');
        $tmpl->addContent("<ul class='items'>");
        
        if (is_dir($dir)) {
            $dh = opendir($dir);
            if ($dh) {
                $modules = array();
                while (($file = readdir($dh)) !== false) {
                    if (preg_match('/.php$/', $file)) {
                        $cn = explode('.', $file);
                        $class_name = "\\Modules\\Service\\" . $cn[0];
                        $module = new $class_name;
                        if($module->isAllow()) {
                            $printname = $module->getName();
                            $modules[$cn[0]] = $printname;
                        }
                    }
                }
                closedir($dh);
                asort($modules);
                foreach ($modules AS $id => $name)
                    $tmpl->addContent("<li><a href=\"/service.php?mode=$id\"'>$name</a></li>");
            }
        }
        $tmpl->addContent("</ul>");
    }
    else {
        \acl::accessGuard('service.'.$mode, \acl::VIEW);
        $opt = request('opt');
        $fn = $dir . $mode . '.php';
        if (file_exists($fn)) {
            $class_name = '\\Modules\\Service\\' . $mode;
            $class = new $class_name;
            $tmpl->setTitle($class->getName());
            $class->link_prefix = '/service.php?mode=' . html_out($mode);
            $class->run($opt);
        } else {
            throw new \NotFoundException("Объект не найден");
        }        
    }
} catch (Exception $e) {
    global $db, $tmpl;
    $db->rollback();
    $tmpl->addContent("<br><br>");
    writeLogException($e);
    $tmpl->errorMessage($e->getMessage());
}

$tmpl->write();
