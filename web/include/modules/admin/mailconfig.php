<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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


/// Настройка почтовых ящиков и алиасов
class MailConfig extends \IModule {
	
	public function __construct() {
		parent::__construct();
		$this->print_name = 'Почтовые домены, ящики, алиасы';
		$this->acl_object_name = 'admin_mailconfig';
	}
	
	public function run() {
		global $CONFIG, $tmpl;
		if (!isset($CONFIG['admin_mailconfig'])) {
			throw new \Exception("Модуль не настроен!");
		}
		if (!is_array($CONFIG['admin_mailconfig'])) {
			throw new \Exception("Неверные настройки модуля!");
		}
		$conf = $CONFIG['admin_mailconfig'];
		$db =  new \MysqiExtended($conf['db_host'], $conf['db_login'], $conf['db_pass'], $conf['db_name']);
		if($db->connect_error) {
			throw new Exception("Не удалось соединиться с базой данных настроек почтового сервера");
		}
		$tmpl->addBreadcrumb($this->print_name, $this->link_prefix);
		$sect = request('sect');
		switch($sect) {
			case '':
                            $tmpl->addBreadcrumb($this->print_name, '');
                            $tmpl->addContent("<ul>"
                                . "<li><a href='".$this->link_prefix."&amp;sect=domains'>Домены</li>"
                                . "<li><a href='".$this->link_prefix."&amp;sect=users'>Пользователи</li>"
                                . "<li><a href='".$this->link_prefix."&amp;sect=alias'>Алиасы</li>"
                                . "</ul>");
                            break;
                        case 'domains':
                            $editor = new \ListEditors\MailDomainsEditor($db);
                            $editor->line_var_name = 'id';
                            $editor->link_prefix = $this->link_prefix.'&sect='.$sect;
                            $editor->acl_object_name = $this->acl_object_name; 
                            $editor->run();
                            break;
                        case 'users':
                            $editor = new \ListEditors\MailUsersEditor($db);
                            $editor->line_var_name = 'id';
                            $editor->link_prefix = $this->link_prefix.'&sect='.$sect;
                            $editor->acl_object_name = $this->acl_object_name; 
                            $editor->run();
                            break;
                        case 'alias':
                            $editor = new \ListEditors\MailAliasEditor($db);
                            $editor->line_var_name = 'id';
                            $editor->link_prefix = $this->link_prefix.'&sect='.$sect;
                            $editor->acl_object_name = $this->acl_object_name; 
                            $editor->run();
                            break;
                        
			default:
				throw new \NotFoundException("Секция не найдена");
		}
		
	}	
}
