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

/// Базовый класс для модулей
abstract class IModule {
	protected $print_name = 'unnamed module';	//< отображаемое имя
	protected $acl_object_name;			//< Имя объекта контроля привилегий
	var $link_prefix;				//< Префикс для ссылок
	
	/// Конструктор
	function __construct() {
		
	}
	
	/// Получить имя модуля
	function getPrintName() {
		return $this->print_name;
	}
	
	function isAllow() {
		return isAccess($this->acl_object_name, 'view');
	}
	
	/// Запустить модуль на исполнение
	abstract function run();
}
