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
namespace modules\service;

/// Модуль вывода списка изменений
class changelog extends \IModule {

    protected $logdata = array();

    public function __construct() {
        parent::__construct();        
    }
    
    public function load() {
        $filename = \cfg::getroot('location').'/changelog.xml';
        $this->xml = @simplexml_load_file($filename);
        $error = libxml_get_last_error();
        if($error) {
            return;
        }
        foreach($this->xml->children() as $name => $node) {
            $line = array('author'=>'', 'msg'=>'', 'date'=>'');
            $rev = 0;
            if ($name == 'logentry') {
                foreach ($node->attributes() as $aname => $achild) {
                    switch ($aname) {
                        case 'revision':
                            $rev = $achild->__toString();
                            break;
                    }
                }
                foreach ($node->children() as $cname => $rchild) {
                    switch ($cname) {
                        case 'author':
                        case 'msg':
                            $line[$cname] = $rchild->__toString();
                            break;
                        case 'date':
                            $line[$cname] = strtotime($rchild->__toString());
                            break;
                    }
                }
            }
            $this->logdata[$rev] = $line;
        }        
    }
    
    public function getLastChanges($count = 5) {
        $logdata = $this->logdata;
        $ret = '';
        while($count>0 && count($logdata)>0) {
            $item = array_pop($logdata);
            if($item['msg']=='') {
                continue;
            }
            $ret .= "<p><b>".date("Y-m-d", $item['date']).", ".$item['author'].':</b><br>';
            $ret .= str_replace("\n", "<br>", $item['msg']);
            $ret .= "</p>";
            $count--;
        }
        return $ret;
    }

    // Получить название модуля
    /// @return Строка с именем
    public function getName() {
        return 'Список изменений в системе';
    }

    /// Получить описание модуля
    /// @return Строка с описанием
    public function getDescription() {
        return 'Список недавних изменений в системе';
    }

    /// Запустить модуль на исполнение
    public function run() {
        global $tmpl;
        //\acl::accessGuard($this->acl_object_name, \acl::VIEW);
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect', '');
        $this->load();
        switch ($sect) {
            case '':
                $tmpl->addBreadcrumb($this->getName(), '');                
                $tmpl->addContent($this->getLastChanges(100));
                break;            
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

        
    
}
