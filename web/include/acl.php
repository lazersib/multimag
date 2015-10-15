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
//

class acl {
    protected static $_instance;    ///< Экземпляр для синглтона
    protected $uid = null;          ///< id текущего пользователя
    protected $acl = null;

    const ACL_WIEW      = 0x01;     ///< Просмотр
    const ACL_CREATE    = 0x02;     ///< Создание
    const ACL_UPDATE    = 0x04;     ///< Обновление
    const ACL_DELETE    = 0x08;     ///< Удаление
    
    const ACL_APPLY         = 0x10; ///< Проведение / запуск
    const ACL_CANCEL        = 0x20; ///< Отмена проведения / остановка
    const ACL_TODAY_APPLY   = 0x40; ///< Проведение / запуск текущим днём
    const ACL_TODAY_CANCEL  = 0x80; ///< Отмена проведения / остановка текущим днём
        
    const ACL_CANCEL_FORCE  = 0x100; ///< Принудительная отмена / остановка
    const ACL_GET_PRINTFORM = 0x200; ///< Формирование печатной формы
    const ACL_GET_PRINTDRAFT= 0x400; ///< Формирование черновика печатной формы (непроведённого документа)
    const ACL_LIST          = 0x800; ///< Загрузка списка
    
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
    
    /// Конструктор. Загружает и сортирует список цен из базы данных.
    final private function __construct() {
        
    }
    
    /// Устанавливает ID текущего пользователя
    public function setUserId($uid) {
        $this->uid = $uid;
    }
    
    
    /// Получить список доступа для анонимных пользователей
    protected function getAnonymousACL() {
        global $db;
        $data = array();
        $res = $db->query("SELECT `id`, `object`, `bitmask` FROM `users_acl` WHERE `uid` IS NULL");
        while($line = $res->fetch_assoc()) {
            $data[$line['object']] = $line['bitmask'];
        }
        return $data;
    }
    
    /// Получить список доступа для аутентифицированных пользователей
    protected function getAuthenticatedACL() {
        global $db;
        $data = array();
        $res = $db->query("SELECT `id`, `object`, `bitmask` FROM `users_groups_acl` WHERE `gid` IS NULL");
        while($line = $res->fetch_assoc()) {
            $data[$line['object']] = $line['bitmask'];
        }
        return $data;
    }
    
    /// Получить список доступа для текущего пользователя
    protected function getUserACL() {
        global $db;
        $data = array();
        $res = $db->query("SELECT `id`, `object`, `bitmask` FROM `users_acl` WHERE `uid`='{$this->uid}'");
        while($line = $res->fetch_assoc()) {
            $data[$line['object']] = $line['bitmask'];
        }
        return $data;
    }
    
    /// Получить список доступа для групп текущего пользователя
    protected function getUserGroupACL() {
        global $db;
        $data = array();
        $res = $db->query("SELECT `id`, `object`, `bitmask` FROM `users_groups_acl`"
            . " INNER JOIN `users_in_group` ON `users_in_group`.`gid`=`users_groups_acl`.`gid`"
            . " WHERE `uid`='{$this->uid}'");
        while($line = $res->fetch_assoc()) {
            $data[$line['object']] = $line['bitmask'];
        }
        return $data;
    }
    
    /// Объединить списки доступа
    protected function mergeACL($acl1, $acl2) {
        $acl = array_merge($acl1, $acl2);
        foreach ($acl AS $id=>$value) {
            if(isset($acl1[$id])) {
                $acl = $value | $acl1[$id];
            }
        }
        return $acl;
    }

    /// Загрузить текущие списки доступа
    protected function loadACL() {
        $this->acl = $this->getAnonymousACL;
        if($this->uid>0) {
            $this->acl = $this->mergeACL($this->acl, $this->getAuthenticatedACL());
            $this->acl = $this->mergeACL($this->acl, $this->getUserACL());
            $this->acl = $this->mergeACL($this->acl, $this->getUserGroupACL());
        }        
    }

    /// Проверить доступ к заданному объекту
    public static function isAccess($object, $flag) {
        $this = self::getInstance();
        if($this->uid == 1) { // Админ-основатель
            return true;
        }
        if($this->acl == null) {
            $this->loadACL();
        }
        if(!isset($this->acl[$object])) {
            $this->acl[$object] = 0;
        }
        return ($this->acl[$object] & $flag) ? true : false;
    }
}
