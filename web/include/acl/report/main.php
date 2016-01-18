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

namespace acl\report;

class main extends \acl\aclContainer {
    protected $name = "Отчёты";
    
    
    public function __construct() {
        global $CONFIG;
        $dir = $CONFIG['site']['location'].'/include/reports/';
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                $list = array();
                while (($file = readdir($dh)) !== false) {
                    if (preg_match('/.php$/', $file)) {
                        $cn = explode('.', $file);
                        include_once("$dir/$file");
                        $class_name = 'Report_' . $cn[0];
                        $class = new $class_name;
                        $nm = $class->getName(true);
                        $list[$cn[0]] = $nm;
                    }
                }
                closedir($dh);
                asort($list);
            }
        }
        $this->list = array();
        foreach ($list as $id => $item) {
            $this->list[$id] = array(
                    "name" => $item,
                    "mask" => \acl::VIEW
                );
        }
    }
    
}
