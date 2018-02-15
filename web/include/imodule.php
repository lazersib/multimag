<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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

    protected $acl_object_name;     //< Имя объекта контроля привилегий
    var $link_prefix;  //< Префикс для ссылок

    public function __construct() {
        
    }

    /// Получить название модуля
    /// @return Строка с именем
    abstract public function getName();

    /// Получить описание модуля
    /// @return Строка с описанием
    abstract public function getDescription();

    /// Запустить модуль на исполнение
    abstract function run();
    
    final public function getAclObjectname() {
        return $this->acl_object_name;
    }

    /// Узнать, есть ли необходимые привилегии
    /// @param $flags    Флаги доступа. По умолчанию - view (просмотр)
    final function isAllow($flags = \acl::VIEW) {
        if (!$this->acl_object_name) {
            return true;
        }
        return \acl::testAccess($this->acl_object_name, $flags);
    }

}
