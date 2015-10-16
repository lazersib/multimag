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

    const VIEW      = 0x01;     ///< Просмотр
    const CREATE    = 0x02;     ///< Создание
    const UPDATE    = 0x04;     ///< Обновление
    const DELETE    = 0x08;     ///< Удаление
    
    const APPLY         = 0x10; ///< Проведение / запуск
    const CANCEL        = 0x20; ///< Отмена проведения / остановка
    const TODAY_APPLY   = 0x40; ///< Проведение / запуск текущим днём
    const TODAY_CANCEL  = 0x80; ///< Отмена проведения / остановка текущим днём
        
    const CANCEL_FORCE  = 0x100; ///< Принудительная отмена / остановка
    const GET_PRINTFORM = 0x200; ///< Формирование печатной формы
    const GET_PRINTDRAFT= 0x400; ///< Формирование черновика печатной формы (непроведённого документа)
    
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
        if(isset($_SESSION['uid'])) {
            $this->uid = $_SESSION['uid'];
        }
    }
    
    public static function getAccessNames() {
        $access_names = array(
            self::VIEW            => 'Просмотр',
            self::CREATE          => 'Создание',
            self::UPDATE          => 'Обновление',
            self::DELETE          => 'Удаление',
            self::APPLY           => 'Проведение / запуск',
            self::CANCEL          => 'Отмена проведения / остановка',
            self::TODAY_APPLY     => 'Проведение / запуск текущим днём',
            self::TODAY_CANCEL    => 'Отмена проведения / остановка текущим днём',
            self::CANCEL_FORCE    => 'Принудительная отмена / остановка',
            self::GET_PRINTFORM   => 'Формирование печатной формы',
            self::GET_PRINTDRAFT  => 'Формирование черновика печатной формы',
        );
        return $access_names;
    }
    
    /// Получить список доступа для анонимных пользователей
    protected function getAnonymousACL() {
        global $db;
        $data = array();
        $res = $db->query("SELECT `id`, `object`, `value` FROM `users_acl` WHERE `uid` IS NULL");
        while($line = $res->fetch_assoc()) {
            $data[$line['object']] = $line['value'];
        }
        return $data;
    }
    
    /// Получить список доступа для аутентифицированных пользователей
    protected function getAuthenticatedACL() {
        global $db;
        $data = array();
        $res = $db->query("SELECT `id`, `object`, `value` FROM `users_groups_acl` WHERE `gid` IS NULL");
        while($line = $res->fetch_assoc()) {
            $data[$line['object']] = $line['value'];
        }
        return $data;
    }
    
    /// Получить список доступа для текущего пользователя
    protected function getUserACL() {
        global $db;
        $data = array();
        $res = $db->query("SELECT `id`, `object`, `value` FROM `users_acl` WHERE `uid`='{$this->uid}'");
        while($line = $res->fetch_assoc()) {
            $data[$line['object']] = $line['value'];
        }
        return $data;
    }
    
    /// Получить список доступа для групп текущего пользователя
    protected function getUserGroupACL() {
        global $db;
        $data = array();
        $res = $db->query("SELECT `object`, `value` FROM `users_groups_acl`"
            . " INNER JOIN `users_in_group` ON `users_in_group`.`gid`=`users_groups_acl`.`gid`"
            . " WHERE `uid`='{$this->uid}'");
        while($line = $res->fetch_assoc()) {
            $data[$line['object']] = $line['value'];
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
        $this->acl = $this->getAnonymousACL();
        if($this->uid>0) {
            $this->acl = $this->mergeACL($this->acl, $this->getAuthenticatedACL());
            $this->acl = $this->mergeACL($this->acl, $this->getUserACL());
            $this->acl = $this->mergeACL($this->acl, $this->getUserGroupACL());
        }        
    }

    
    public static function need_auth() {
        $cur = self::getInstance();
        if(!$cur->uid) {
            if(isset($_SERVER['REQUEST_URI'])) {
                $_SESSION['last_page'] = $_SERVER['REQUEST_URI'];
            }
            $_SESSION['cook_test'] = 'data';
            redirect('/login.php');
            exit();
        }
        return 1;
    }

    /// Есть ли привилегия доступа к указанному объекту для указанной операции
    /// @param $object Имя объекта, для которого нужно проверить привилегии
    /// @param $flags  Битовая маска флагов, которые нужно проверить
    /// @param $no_redirect Если false - то в случае отсутствия привилегий, и если не пройдена аутентификация, выполняет редирект на страницу аутентификации
    public static function testAccess($object, $flags, $no_redirect=false) {
        $cur = self::getInstance();
        if($cur->uid == 1) { // Админ-основатель
            return true;
        }
        if($cur->acl == null) {
            $cur->loadACL();
        }
        if(!isset($cur->acl[$object])) {
            $cur->acl[$object] = 0;
        }
        $access = ($flags && (($cur->acl[$object] & $flags) == $flags)) ? true : false;
        if((!$cur->uid) && (!$access) && (!$no_redirect)) {
            self::need_auth();
        }        
        return $access;
    }
    
    /// То же, что и testAccess, но бросает исключение, если нет доступа
    public static function accessGuard($object, $flags, $no_redirect=false) {
        if(!self::testAccess($object, $flags, $no_redirect=false)) {
            $names = self::getAccessNames();
            if(isset($names[$flags])) {
                $name = 'привилегии *'.$names[$flags].'*';
            } else {
                $name = 'привилегий';
            }
            throw new \AccessException("Нет {$name} для доступа к $object");
        }
    }
}
