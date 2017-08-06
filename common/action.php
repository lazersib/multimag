<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2017, BlackLight, TND Team, http://tndproject.org
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

/// Базовый класс для действий
abstract class action {
    const MANUAL = 0;
    const HOURLY = 1;
    const DAILY = 2;
    const WEEKLY = 3;
    const MONTHLY = 4;

    protected $db;
    protected $config;
    protected $verbose = false;
    protected $depends = array();   // Зависимости

    protected $interval = self::MANUAL;

    /// @brief Конструктор
    public function __construct($config, $db) {
        $this->db = $db;
        $this->config = $config;
    }
        
    public function setVerbose($flag = true) {
        $this->verbose = $flag;
    }
    
    public function getDepends() {
        return $this->depends;
    }
    
    public function getInterval() {
        return $this->interval;
    }
    
    /// Получить имя действия
    abstract public function getName();
    
    /// Проверить, разрешен ли периодический запуск действия
    abstract public function isEnabled();
    
    /// Запустить задачу
    abstract public function run();
}
