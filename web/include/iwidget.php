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

/// Базовый класс для виджетов
abstract class IWidget {
    protected $acl_object_name;         //< Имя объекта контроля привилегий

    public function __construct(){}
    
    /// Получить название виджета
    /// @return Строка с именем
    abstract public function getName();

    /// Получить описание виджета
    /// @return Строка с описанием
    abstract public function getDescription();
    
    /// Задать параметры отображения виджета
    /// @param $param_str   Строка параметров отображения
    /// @return true, если параметры допустимы, false в ином случае 
    abstract public function setParams($param_str);
    
    /// Узнать, есть ли необходимые привилегии
    /// @param $mode    Константа привилегии. По умолчанию - view (просмотр)
    final function isAllow($mode =  \acl::VIEW) {
            if(!$this->acl_object_name)
                return true;
            return \acl::testAccess($this->acl_object_name, $mode, true);
    }

    /// Получить HTML код виджета
    abstract function getHTML();
}
