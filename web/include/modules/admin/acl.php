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

/// Управление привилегиями доступа пользователей
class Acl extends \IModule {
    
    protected $acl_dir = 'include/acl/';
    protected $items = array(
            'gle' => 'Редактор списка групп пользователей',
            'all' => 'Привилегии анонимных пользователей',
            'auth' => 'Привилегии аутентифицимрованных пользователейй',
            'groups' => 'Привилегии групп пользователей',
        );

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
                $tmpl->addContent("<p>".$this->getDescription()."</p><ul>");
                foreach($this->items as $id=>$value ) {
                    $tmpl->addContent("<li><a href='" . $this->link_prefix . "&amp;sect={$id}'>{$value}</li>");
                }
                $tmpl->addContent("</ul>");
                break;
            case 'gle':
                $editor = new \ListEditors\AccessGroupEditor($db);
                $editor->line_var_name = 'id';
                $editor->link_prefix = $this->link_prefix . '&sect=' . $sect;
                $editor->acl_object_name = $this->acl_object_name;
                $editor->run();
                break;
            case 'groups':
                $this->renderUsersGroupsList($tmpl, $db);
                break;
            case 'group_acl':
                $this->groupAclEditor($tmpl, $db);
                break;
            case 'group_acl_save':
                $this->groupAclSave($tmpl, $db);
                $this->groupAclEditor($tmpl, $db);
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

    // Вывод списка групп пользователей
    protected function renderUsersGroupsList($tmpl, $db) {
        $tmpl->addBreadcrumb($this->items['groups'], '');
        $link_prefix = $this->link_prefix . '&amp;sect=groupacl';
        $tmpl->addContent("<table class='list'><tr><th>N</th><th>Название</th><th>Описание</th><th>Действие</th></tr>");
	$res=$db->query("SELECT `id`,`name`,`comment` FROM `users_grouplist`");
	while($nxt = $res->fetch_row()) {
		$tmpl->addContent("<tr><td>$nxt[0]</td><td><a href='{$link_prefix}&amp;sect=group_acl&amp;group_id=$nxt[0]'>$nxt[1]</a></td><td>$nxt[2]</td>"
                    . "<td><a href='{$link_prefix}&amp;group=$nxt[0]'>Управлять</a></td></tr>");
	}
	$tmpl->addContent("</table><a href='?mode=gre'>Новая группа</a>");
    }
    
    protected function loadAclCategory($category_codename) {
        $class_name = "\\acl\\" . $category_codename;
        $cur_acl = new $class_name;
        
        /*
        $dir = $this->acl_dir.$category_codename;
        if (is_dir($dir)) {
            $dh = opendir($dir);
            if ($dh) {
                $modules = array();
                while (($file = readdir($dh)) !== false) {
                    if (preg_match('/.php$/', $file)) {
                        $cn = explode('.', $file);
                        $class_name = "\\Modules\\Admin\\" . $cn[0];
                        $module = new $class_name;
                        if($module->isAllow()) {
                            $printname = $module->getName();
                            $modules[$cn[0]] = $printname;
                        }
                    }
                }
                closedir($dh);
                asort($modules);
            }
        }
         * 
         */
    }
    
    protected function loadAclCategoryList() {
        $list = array();
        if (is_dir($this->acl_dir)) {
            $dh = opendir($this->acl_dir);
            if ($dh) {
                $modules = array();
                while (($file = readdir($dh)) !== false) {
                    if($file=='.' || $file == '..') {
                        continue;
                    }
                    $fullname = $this->acl_dir.$file;
                    if(is_dir($fullname)) {
                        $class_name = "\\acl\\" . $file.'\\main';
                        $cur_acl = new $class_name;
                        $list[$file] = array('name' => $cur_acl->getName());
                    }
                }
                closedir($dh);
            }
        } 
        asort($list);
        return $list;
    }
    
    protected function loadAclForGroup($group_id) {
        global $db;
        settype($group_id,'int');
        $ret = array();
        $res = $db->query("SELECT `id`, `object`, `value` FROM `users_groups_acl`"
            . " WHERE `gid`=$group_id");
        while($line = $res->fetch_assoc()) {
            $ret[$line['object']] = $line['value'];
        }
        return $ret;
    }
    
    protected function groupAclSave($tmpl, $db) {
        \acl::accessGuard('admin_acl', \acl::UPDATE);
        $group_id = rcvint('group_id');
        $acl_cat = request('acl_cat', 'admin');
        $class_name = "\\acl\\" . $acl_cat . '\\main';
        $cur_acl = new $class_name;
        $items = $cur_acl->getList();
        $sql_data = '';
        foreach($items as $id=>$value) {
            if(!isset($_REQUEST[$id])) {
                $line = array();
            } else if(!is_array($_REQUEST[$id])) {
                $line = array();
            } else {
                $line = $_REQUEST[$id];
            }
            $acl_value = 0;
            foreach($line as $v) {
                $acl_value |= intval($v);
            }
            $acl_object_sql = $db->real_escape_string($acl_cat.'_'.$id);
            if($sql_data) {
                $sql_data.=',';
            }
            $sql_data .= "($group_id,'$acl_object_sql',$acl_value)";
        }
        $sql_query = "REPLACE INTO `users_groups_acl` (`gid`, `object`, `value`) VALUES ".$sql_data;
        $db->query($sql_query);
        if($db->affected_rows>0) {
            $tmpl->msg("Данные обновлены", "ok");
        } else {
            $tmpl->msg("Ничего не изменилось");
        }
    }
    
    protected function groupAclEditor($tmpl, $db) {
        $group_id = rcvint('group_id');
        $tmpl->addBreadcrumb('Управление привилегиями группы ' . $group_id, '');
        $acl_cat = request('acl_cat', 'admin');
        $list = $this->loadAclCategoryList();
        $tmpl->addTabsWidget($list, $acl_cat, $this->link_prefix . "&amp;sect=group_acl&amp;group_id=$group_id", 'acl_cat');
        $class_name = "\\acl\\" . $acl_cat . '\\main';
        $cur_acl = new $class_name;
        $table_header = array('Объект');
        $mask = $cur_acl->getMask();
        $a_names = \Acl::getAccessNames();
        for ($i = 0, $c = 1; $i < 32; $i++, $c<<=1) {
            if ($mask & $c) {
                $table_header[] = $a_names[$c];
            }
        }
        $table_body = array();
        $items = $cur_acl->getList();
        $acl = $this->loadAclForGroup($group_id);
        foreach($items as $id=>$value) {
            $table_line = array(
                $value['name']                
            );
            for ($i = 0, $c = 1; $i < 32; $i++, $c<<=1) {
                if ($mask & $c) {
                    if($value['mask']&$c) {
                        $checked = '';
                        if(isset($acl[$acl_cat.'_'.$id])) {
                            if($acl[$acl_cat.'_'.$id] & $c) {
                                $checked = ' checked';
                            }
                        }
                        $table_line[] = "<label><input type='checkbox' name='{$id}[]' value='$c'{$checked}>Да</label>";
                    } else {
                        $table_line[] = '';
                    }
                }
            }
            $table_body[] = $table_line;
        }
        $tmpl->addContent("<form method='post' action='{$this->link_prefix}&amp;group_id=$group_id&amp;acl_cat=".html_out($acl_cat)."'>"
            . "<input type='hidden' name='sect' value='group_acl_save'>");
        
        $tmpl->addTableWidget($table_header, $table_body, 10);
        $tmpl->addContent("<button type='submit'>Сохранить</button></form>");
    }

}
