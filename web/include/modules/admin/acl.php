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
            'all_acl' => 'Привилегии анонимных пользователей',
            'auth_acl' => 'Привилегии аутентифицированных пользователей',
            'gle' => 'Редактор списка ролей пользователей',
            'groups' => 'Привилегии для ролей пользователей',
        );

    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'admin.acl';
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
                $tmpl->addBreadcrumb($this->items['groups'], $this->link_prefix . '&amp;sect=groups');
                $group_id = rcvint('group_id');
                $this->groupAclEditor($group_id);
                break;
            case 'group_acl_save':
                $group_id = rcvint('group_id');
                $tmpl->addBreadcrumb($this->items['groups'], $this->link_prefix . '&amp;sect=groups');   
                $tmpl->addBreadcrumb('Управление привилегиями группы '.$group_id, $this->link_prefix . '&amp;sect=group_acl&amp;group_id='.$group_id);   
                $this->groupAclSave($group_id);
                $this->groupAclEditor($group_id);
                $tmpl->addBreadcrumb('Управление привилегиями группы '.$group_id, $this->link_prefix . '&amp;sect=group_acl&amp;group_id='.$group_id);   
                break;
            case 'auth_acl':
                $this->authenticAclEditor();
                break;
            case 'auth_acl_save':
                $this->groupAclSave(null);
                $this->authenticAclEditor();
                break;
            case 'all_acl':
                $this->anonymousAclEditor();
                break;
            case 'all_acl_save':
                $this->userAclSave(null);
                $this->anonymousAclEditor();
                break;
            case 'user_acl':
                $user_id = rcvint('user_id');
                $this->userAclEditor($user_id);
                break;
            case 'user_acl_save':
                $user_id = rcvint('user_id');
                $this->userAclSave($user_id);
                $this->userAclEditor();
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

    // Вывод списка групп пользователей
    protected function renderUsersGroupsList($tmpl, $db) {
        $tmpl->addBreadcrumb($this->items['groups'], '');
        $link_prefix = $this->link_prefix . '&amp;sect=group_acl';
        $tmpl->addContent("<table class='list'><tr><th>N</th><th>Название</th><th>Описание</th><th>Действие</th></tr>");
	$res=$db->query("SELECT `id`,`name`,`comment` FROM `users_grouplist`");
	while($nxt = $res->fetch_row()) {
		$tmpl->addContent("<tr><td>$nxt[0]</td><td>$nxt[1]</td><td>$nxt[2]</td>"
                    . "<td><a href='{$link_prefix}&amp;group_id=$nxt[0]'>Изменить привилегии</a></td></tr>");
	}
	$tmpl->addContent("</table>");
    }
    
    protected function loadAclCategoryList() {
        $list = array();
        if (is_dir($this->acl_dir)) {
            $dh = opendir($this->acl_dir);
            if ($dh) {
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
        if($group_id!==null) {
            $sql = "SELECT `id`, `object`, `value` FROM `users_groups_acl` WHERE `gid`=$group_id";
        } else {
            $sql = "SELECT `id`, `object`, `value` FROM `users_groups_acl` WHERE `gid` IS NULL";
        }
        $res = $db->query($sql);
        while($line = $res->fetch_assoc()) {
            $ret[$line['object']] = $line['value'];
        }
        return $ret;
    }
    
    protected function groupAclSave($group_id) {
        global $db, $tmpl;
        $tmpl->addBreadcrumb('Сохранение привилегий', '');
        \acl::accessGuard('admin_acl', \acl::UPDATE);
        if($group_id===null) {
            $group_id = 'NULL';
        }
        $acl_cat = request('acl_cat', 'generic');
        $class_name = "\\acl\\" . $acl_cat . '\\main';
        $cur_acl = new $class_name;
        $items = $cur_acl->getList();
        $sql_data = '';
        foreach($items as $id=>$value) {
            if(!isset($_REQUEST['acl'][$id])) {
                $line = array();
            } else if(!is_array($_REQUEST['acl'][$id])) {
                $line = array();
            } else {
                $line = $_REQUEST['acl'][$id];
            }
            $acl_value = 0;
            foreach($line as $v) {
                $acl_value |= intval($v);
            }
            $acl_object_sql = $db->real_escape_string($acl_cat.'.'.$id);
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
    
    protected function userAclSave($user_id) {
        global $db, $tmpl;
        $tmpl->addBreadcrumb('Сохранение привилегий', '');
        \acl::accessGuard('admin_acl', \acl::UPDATE);
        if($user_id===null) {
            $user_id = 'NULL';
        }
        $acl_cat = request('acl_cat', 'generic');
        $class_name = "\\acl\\" . $acl_cat . '\\main';
        $cur_acl = new $class_name;
        $items = $cur_acl->getList();
        $sql_data = '';
        foreach($items as $id=>$value) {
            if(!isset($_REQUEST['acl'][$id])) {
                $line = array();
            } else if(!is_array($_REQUEST['acl'][$id])) {
                $line = array();
            } else {
                $line = $_REQUEST['acl'][$id];
            }
            $acl_value = 0;
            foreach($line as $v) {
                $acl_value |= intval($v);
            }
            $acl_object_sql = $db->real_escape_string($acl_cat.'.'.$id);
            if($sql_data) {
                $sql_data.=',';
            }
            $sql_data .= "($user_id,'$acl_object_sql',$acl_value)";
        }
        $sql_query = "REPLACE INTO `users_acl` (`uid`, `object`, `value`) VALUES ".$sql_data;
        $db->query($sql_query);
        if($db->affected_rows>0) {
            $tmpl->msg("Данные обновлены", "ok");
        } else {
            $tmpl->msg("Ничего не изменилось");
        }
    }
    
    protected function loadAclForUser($user_id) {
        global $db;
        $ret = array();
        if($user_id!==null) {
            settype($user_id,'int');
            $sql = "SELECT `id`, `object`, `value` FROM `users_acl` WHERE `uid`=$user_id";
        } else {
            $sql = "SELECT `id`, `object`, `value` FROM `users_acl` WHERE `uid` IS NULL";
        }
        $res = $db->query($sql);
        while($line = $res->fetch_assoc()) {
            $ret[$line['object']] = $line['value'];
        }
        return $ret;
    }
    
    protected function getAclTable($acl, $acl_cat) {
        $class_name = "\\acl\\" . $acl_cat . '\\main';
        $cur_acl = new $class_name;
        
        $mask = $cur_acl->getMask();
        $a_names = \Acl::getAccessNames();
        $table_header = array('Объект');
        for ($i = 0, $c = 1; $i < 32; $i++, $c<<=1) {
            if ($mask & $c) {
                $table_header[] = $a_names[$c];
            }
        }
        
        $items = $cur_acl->getList();        
        $table_body = array();
        foreach($items as $id=>$value) {
            $table_line = array(
                $value['name']                
            );
            for ($i = 0, $c = 1; $i < 32; $i++, $c<<=1) {
                if ($mask & $c) {
                    if($value['mask']&$c) {
                        $checked = '';
                        if(isset($acl[$acl_cat.'.'.$id])) {
                            if($acl[$acl_cat.'.'.$id] & $c) {
                                $checked = ' checked';
                            }
                        }
                        $table_line[] = "<label><input type='checkbox' name='acl[{$id}][]' value='$c'{$checked}>Да</label>";
                    } else {
                        $table_line[] = '';
                    }
                }
            }
            $table_body[] = $table_line;
        }
        return ['header' => $table_header, 'body' => $table_body];
    }
    
    protected function groupAclEditor($group_id) {
        global $tmpl;             
        $acl_cat = request('acl_cat', 'generic');
        
        $tmpl->addBreadcrumb('Управление привилегиями группы '.$group_id, '');
        
        $list = $this->loadAclCategoryList();
        $tmpl->addTabsWidget($list, $acl_cat, $this->link_prefix . "&amp;sect=group_acl&amp;group_id=$group_id", 'acl_cat');
        
        $tmpl->addContent("<form method='post' action='{$this->link_prefix}&amp;group_id=$group_id&amp;acl_cat=".html_out($acl_cat)."'>"
            . "<input type='hidden' name='sect' value='group_acl_save'>");
        
        $acl = $this->loadAclForGroup($group_id);
        $table_data = $this->getAclTable($acl, $acl_cat);
        $tmpl->addTableWidget($table_data['header'], $table_data['body'], 20);
        $tmpl->addContent("<button type='submit'>Сохранить</button></form>");
    }
    
    protected function authenticAclEditor() {
        global $tmpl;             
        $acl_cat = request('acl_cat', 'generic');
        
        $tmpl->addBreadcrumb($this->items['auth_acl'], '');
        
        $list = $this->loadAclCategoryList();
        $tmpl->addTabsWidget($list, $acl_cat, $this->link_prefix . "&amp;sect=auth_acl", 'acl_cat');
        
        $tmpl->addContent("<form method='post' action='{$this->link_prefix}&amp;acl_cat=".html_out($acl_cat)."'>"
            . "<input type='hidden' name='sect' value='auth_acl_save'>");
        
        $acl = $this->loadAclForGroup(null);
        $table_data = $this->getAclTable($acl, $acl_cat);
        $tmpl->addTableWidget($table_data['header'], $table_data['body'], 20);
        $tmpl->addContent("<button type='submit'>Сохранить</button></form>");
    }

    protected function anonymousAclEditor() {
        global $tmpl;             
        $acl_cat = request('acl_cat', 'generic');
        
        $tmpl->addBreadcrumb($this->items['all_acl'], '');
        
        $list = $this->loadAclCategoryList();
        $tmpl->addTabsWidget($list, $acl_cat, $this->link_prefix . "&amp;sect=all_acl", 'acl_cat');
        
        $tmpl->addContent("<form method='post' action='{$this->link_prefix}&amp;acl_cat=".html_out($acl_cat)."'>"
            . "<input type='hidden' name='sect' value='all_acl_save'>");
        
        $acl = $this->loadAclForUser(null);
        $table_data = $this->getAclTable($acl, $acl_cat);
        $tmpl->addTableWidget($table_data['header'], $table_data['body'], 20);
        $tmpl->addContent("<button type='submit'>Сохранить</button></form>");
    }
    
    protected function userAclEditor($user_id) {
        global $tmpl;             
        $acl_cat = request('acl_cat', 'generic');
        
        $tmpl->addBreadcrumb('Привилегии пользователя '.$user_id, '');
        
        $list = $this->loadAclCategoryList();
        $tmpl->addTabsWidget($list, $acl_cat, $this->link_prefix . "&amp;sect=all_acl", 'acl_cat');
        
        $tmpl->addContent("<form method='post' action='{$this->link_prefix}&amp;acl_cat=".html_out($acl_cat)."'>"
            . "<input type='hidden' name='sect' value='all_acl_save'>");
        
        $acl = $this->loadAclForUser($user_id);
        $table_data = $this->getAclTable($acl, $acl_cat);
        $tmpl->addTableWidget($table_data['header'], $table_data['body'], 20);
        $tmpl->addContent("<button type='submit'>Сохранить</button></form>");
    }
}
