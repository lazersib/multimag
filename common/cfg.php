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

/// Класс работы с конфигурацией системы
class cfg {
    protected static $_instance;    ///< Экземпляр для синглтона
    
    /// Конструктор копирования запрещён
    final private function __clone() {    
    }

    /// Получить экземпляр класса
    /// @return aclTester
    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /// Конструктор
    final private function __construct() {
    }

    static function get($sect, $param, $default = null) {
        global $CONFIG;
        if(isset($CONFIG[$sect][$param])) {
            return $CONFIG[$sect][$param];
        } else {
            return $default;
        }
    }
    
    static function getsub($sect, $param, $subparam, $default = null) {
        global $CONFIG;
        if(isset($CONFIG[$sect][$param][$subparam])) {
            return $CONFIG[$sect][$param][$subparam];
        } else {
            return $default;
        }
    }
    
    static function test($sect, $param) {
        global $CONFIG;
        if(isset($CONFIG[$sect][$param])) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    static function testsub($sect, $param, $subparam) {
        global $CONFIG;
        if(isset($CONFIG[$sect][$param][$subparam])) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}
