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
//

/// Класс работы с конфигурацией системы
class cfg {
    protected static $_instance;    ///< Экземпляр для синглтона
    /// TODO: методы изменения конфигурации
    /// Конструктор копирования запрещён
    final private function __clone() {    
    }

    /// Получить экземпляр класса
    /// @return cfg
    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /// Конструктор
    final private function __construct() {
    }

    /// Получить параметр конфигурации
    /// @param $sect Имя секции конфигурации
    /// @param $param Имя параметра конфигурации
    /// @param $default Значение по умолчанию. Возвращается, если параметр не определён
    /// @return Параметр, или $default
    static function get($sect, $param, $default = null) {
        global $CONFIG;
        if(isset($CONFIG[$sect][$param])) {
            return $CONFIG[$sect][$param];
        } else {
            return $default;
        }
    }
    
    /// Получить вложенный параметр конфигурации
    /// @param $sect Имя секции конфигурации
    /// @param $param Имя параметра конфигурации
    /// @param $subparam Имя вложенного параметра конфигурации
    /// @param $default Значение по умолчанию. Возвращается, если параметр не определён
    /// @return Параметр, или $default
    static function getsub($sect, $param, $subparam, $default = null) {
        global $CONFIG;
        if(isset($CONFIG[$sect][$param][$subparam])) {
            return $CONFIG[$sect][$param][$subparam];
        } else {
            return $default;
        }
    }
    
    /// Получить параметр конфигурации
    /// @param $param Имя параметра конфигурации
    /// @param $default Значение по умолчанию. Возвращается, если параметр не определён
    /// @return Параметр, или $default
    static function getroot($param, $default = null) {
        global $CONFIG;
        if(isset($CONFIG[$param])) {
            return $CONFIG[$param];
        } else {
            return $default;
        }
    }
    
    /// Проверить существование параметра конфигурации
    /// @param $sect Имя секции конфигурации
    /// @param $param Имя параметра конфигурации
    /// @return true, если существует; false в ином случае
    static function exist($sect, $param) {
        global $CONFIG;
        if(isset($CONFIG[$sect][$param])) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    /// Проверить существование вложенного параметра конфигурации
    /// @param $sect Имя секции конфигурации
    /// @param $param Имя параметра конфигурации
    /// @param $subparam Имя вложенного параметра конфигурации
    /// @return true, если существует; false в ином случае
    static function existsub($sect, $param, $subparam) {
        global $CONFIG;
        if(isset($CONFIG[$sect][$param][$subparam])) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    /// Проверить существование параметра конфигурации
    /// @param $sect Имя секции конфигурации
    /// @param $param Имя параметра конфигурации
    /// Бросает исключение при отсутствии параметра
    static function required($sect, $param) {
        if(!self::exist($sect, $param)) {
            throw new \ErrorException('Обязательный параметр конфигурации '.$sect.'.'.$param.' не определён!');
        }
    }
    
    /// Проверить, что указанный параметр конфигурации не пуст
    /// @param $sect Имя секции конфигурации
    /// @param $param Имя параметра конфигурации
    /// Бросает исключение при отсутствии параметра
    static function requiredFilled($sect, $param) {
        if(!self::get($sect, $param)) {
            throw new \ErrorException('Обязательный параметр конфигурации '.$sect.'.'.$param.' пуст или не определён!');
        }
    }
        
    /// Проверить, что указанный параметр конфигурации не пуст
    /// @param $sect Имя секции конфигурации
    /// @param $param Имя параметра конфигурации
    /// Бросает исключение при отсутствии параметра
    static function requiredRootFilled($param) {
        if(!self::getroot($param)) {
            throw new \ErrorException('Обязательный параметр конфигурации '.$param.' пуст или не определён!');
        }
    }
}
