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
            case 'userig':
                $group_id = rcvint('group_id');
                $tmpl->addBreadcrumb($this->items['groups'], $this->link_prefix . '&amp;sect=groups');
                $this->viewUsersInGroup($group_id);
                break;
            case 'userrm':
                $tmpl->addBreadcrumb($this->items['groups'], $this->link_prefix . '&amp;sect=groups');
                $user_id = rcvint('user_id');
                $group_id = rcvint('group_id');
                $this->rmUser($user_id, $group_id);
                $this->viewUsersInGroup($group_id);
                break;
            case 'userins':
                $tmpl->addBreadcrumb($this->items['groups'], $this->link_prefix . '&amp;sect=groups');
                $user_id = rcvint('user_id');
                $group_id = rcvint('group_id');
                $this->viewUserInsertRole($user_id, $group_id);
                break;
            case 'upl':
                $str = request('q');
                $this->getUsersList($str);
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

    // Вывод списка групп пользователей
    protected function renderUsersGroupsList($tmpl, $db) {
        $tmpl->addBreadcrumb($this->items['groups'], '');
        $link_prefix = $this->link_prefix . '&amp;sect=group_acl';
        $tmpl->addContent("<table class='list'><tr><th>N</th><th>Название</th><th>Описание</th><th>Правка</th><th>Пользователи</th></tr>");
	$res=$db->query("SELECT `id`,`name`,`comment` FROM `users_grouplist`");
	while($nxt = $res->fetch_row()) {
		$tmpl->addContent("<tr><td>$nxt[0]</td><td>$nxt[1]</td><td>$nxt[2]</td>"
                    . "<td><a href='{$link_prefix}&amp;group_id=$nxt[0]'>Изменить привилегии</a></td>"
                    . "<td><a href='{$this->link_prefix}&amp;sect=userig&amp;group_id=$nxt[0]'>Смотреть</a></td></tr>");
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
                        $list[$file] = array('name' => $cur_acl->getName(), 'description'=>$cur_acl->getDescription());
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
        \acl::accessGuard($this->acl_object_name, \acl::UPDATE);
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
        \acl::accessGuard($this->acl_object_name, \acl::UPDATE);
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
        if(isset($list[$acl_cat]['description'])) {
            $tmpl->addContent("<p>".$list[$acl_cat]['description']."</p>");
        }   
        $tmpl->addContent("<form method='post' action='{$this->link_prefix}&amp;group_id=$group_id&amp;acl_cat=".html_out($acl_cat)."'>"
            . "<input type='hidden' name='sect' value='group_acl_save'>");
        
        $acl = $this->loadAclForGroup($group_id);
        $table_data = $this->getAclTable($acl, $acl_cat);
        $tmpl->addTableWidget($table_data['header'], $table_data['body'], 20);
        $tmpl->addContent("<button type='submit'>Сохранить</button></form>");
        $tmpl->addContent("<script type='text/javascript'>
	function SelAll(flag) {
		var elems = document.getElementsByTagName('input');
		var l = elems.length;
		for(var i=0; i<l; i++) {
			elems[i].checked=flag;
			if(flag)	elems[i].disabled = false;
		}
	}
	</script>
	<div><a onclick='SelAll(true)'>Выбрать всё<a> | <a onclick='SelAll(false)'>Снять всё</a></div>");
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
    
    protected function getUsersList($str) {
        global $db, $tmpl;
        $tmpl->ajax = 1;
        $s = $db->real_escape_string($str);
        $res=$db->query("SELECT `id`,`name`, `reg_email` FROM `users` WHERE `name` LIKE '%$s%'");
        while($nxt=$res->fetch_row()) {
                echo"$nxt[1]|$nxt[0]|$nxt[2]\n";
        }
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
        $tmpl->addContent("<script type='text/javascript'>
	function SelAll(flag) {
		var elems = document.getElementsByTagName('input');
		var l = elems.length;
		for(var i=0; i<l; i++) {
			elems[i].checked=flag;
			if(flag)	elems[i].disabled = false;
		}
	}
	</script>
	<div><a onclick='SelAll(true)'>Выбрать всё<a> | <a onclick='SelAll(false)'>Снять всё</a></div>");
    }
    
    protected function viewUserInsertRole($user_id, $group_id) {
        global $tmpl;
        try {
            $this->addUserToGroup($user_id, $group_id);
        } catch (\mysqli_sql_exception $e) {
            $id = writeLogException($e);
            $tmpl->errorMessage("Порядковый номер ошибки: $id<br>Сообщение передано администратору", "Ошибка в базе данных");
        } catch (\Exception $e) {
            $tmpl->errorMessage($e->getMessage());
        }
        $this->viewUsersInGroup($group_id);
    }


    protected function getUngroupUserForm($user_id, $group_id) {
        return "<form action='{$this->link_prefix}' method='post'>"
        . "<input type='hidden' name='sect' value='userrm'>"
        . "<input type='hidden' name='user_id' value='$user_id'>"
        . "<input type='hidden' name='group_id' value='$group_id'>"
        . "<button type='submit'>Исключить</button>"
        . "</form>";
    }
    
    protected function rmUser($user_id, $group_id) {
        global $db, $tmpl;
        \acl::accessGuard($this->acl_object_name, \acl::DELETE);
        settype($user_id, 'int');
        settype($group_id, 'int');
        $db->query("DELETE FROM `users_in_group` WHERE `uid`=$user_id AND `gid`=$group_id");
        if($db->affected_rows) {
            $tmpl->msg("Пользователь снят с роли", 'ok');
        }
    }
    
    protected function addUserToGroup($user_id, $group_id) {
        global $db;
        \acl::accessGuard($this->acl_object_name, \acl::UPDATE);
        settype($user_id, 'int');
        settype($group_id, 'int');
        if($user_id<=0) {
            throw new \Exception("Пользователь не выбран");
        }
        if($group_id<=0) {
            throw new \Exception("Роль не задана");
        }
        $db->query("INSERT INTO `users_in_group` ( `uid`, `gid`) VALUES ('$user_id', '$group_id')");
        
    }

    protected function viewUsersInGroup($group_id) {
        global $tmpl, $db;
        $tmpl->addBreadcrumb('Пользователи в роли '.$group_id, '');
        $table_header = array('Id', 'Логин', 'Настоящее имя', 'Имя сотрудника', '');
        $table_body = array();
        $exist = false;
        $res = $db->query("SELECT `users_in_group`.`uid`, `users`.`name`, `users`.`real_name`, `users_worker_info`.`worker_real_name`"
            . " FROM `users_in_group`"
            . " LEFT JOIN `users` ON `users`.`id` = `users_in_group`.`uid`"
            . " LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users_in_group`.`uid`"
            . " WHERE `users_in_group`.`gid`=$group_id");
        while($line = $res->fetch_assoc()) {
            $table_body[] = array(
                $line['uid'], $line['name'], $line['real_name'], $line['worker_real_name'],
                $this->getUngroupUserForm($line['uid'], $group_id)
            );
            $exist = true;
        }
        if($exist) {
            $tmpl->addTableWidget($table_header, $table_body, 20);
        } else {
            $tmpl->msg("Эта роль не назначена ни одному пользователю.");
        }
        $tmpl->addContent("<form method='post' action='{$this->link_prefix}'>
            <script type='text/javascript' src='/css/jquery/jquery.js'></script>
            <script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
            <input type='hidden' name='sect' value='userins'>
            <input type='hidden' name='group_id' value='$group_id'>
            <input type='hidden' name='user_id' id='user_id' value='0'>
            <input type='text' id='user_nm' style='width: 450px;' value=''><br>
            <script type=\"text/javascript\">
	        $(document).ready(function(){
	                $(\"#user_nm\").autocomplete(\"/adm.php\", {
	                        delay:300,
	                        minChars:1,
	                        matchSubset:1,
	                        autoFill:false,
	                        selectFirst:true,
	                        matchContains:1,
	                        cacheLength:10,
	                        maxItemsToShow:15,
	                        formatItem:usliFormat,
                                onItemSelect:usselectItem,
	                        extraParams:{'mode':'acl','sect':'upl'}
	                });
	        });	
	        function usliFormat (row, i, num) {
	                var result = row[0] + \"<em class='qnt'>email: \" +
	                row[2] + \"</em> \";
	                return result;
	        }
                function usselectItem(li) {
	                if( li == null ) var sValue = \"Ничего не выбрано!\";
	                if( !!li.extra ) var sValue = li.extra[0];
	                else var sValue = li.selectValue;
	                document.getElementById('user_id').value=sValue;
	        }
	        </script>"
        . "<button type='submit'>Добавить</button>"
        . "</form>");
    }
}
