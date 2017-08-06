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

namespace Models;

/// @brief Класс - хранилище списка товаров корзины. Синглтон.
class Basket {

    protected static $_instance; //< Экземпляр для синглтона
    protected $list;  //< Массив с товарами

    /// Получить экземпляр класса
    /// @return Models\Basket

    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /// Конструктор
    final private function __construct() {
        global $db;
        $this->list = array();

        // Хранилище в переменных сессии для не аутентифицированных
        if (isset($_SESSION['basket']['list'])) {
            if (is_array($_SESSION['basket']['list'])) {
                $this->list = $_SESSION['basket']['list'];
            }
        }
        // Хранилище в cookie
        if (isset($_COOKIE['basket'])) {
            $items = explode(',', $_COOKIE['basket']);
            foreach ($items as $line) {
                $item = explode(':', $line);
                if (is_array($item)) {
                    if (isset($item[1])) {
                        settype($item[0], 'int');
                        settype($item[1], 'int');
                        if ($item[0] <= 0 || $item[1] <= 0) {
                            continue;
                        }
                        if (!isset($this->list[$item[0]])) {
                            $this->list[$item[0]] = array('pos_id' => $item[0], 'cnt' => $item[1], 'comment' => '');
                        }
                    }
                }
            }
        }
        // Хранилище в базе на сервере для зарегистрированных пользователей
        if (isset($_SESSION['uid'])) {
            if ($_SESSION['uid'] > 0) {
                $res = $db->query("SELECT `pos_id`, `cnt`, `comment` FROM `users_basket` WHERE `user_id`=" . intval($_SESSION['uid']));
                while ($line = $res->fetch_assoc()) {
                    // Чтобы не перетереть при аутентификации после заполнения корзины
                    if (!isset($this->list[$line['pos_id']])) {
                        $this->list[$line['pos_id']] = $line;
                    }
                }
            }
        }
    }

    /// Очистить корзину
    public function clear() {
        $this->list = array();
    }

    /// Сохранить содержимое корзины в доступные хранилища
    public function save() {
        global $db;

        // Хранилище в переменных сессии для не аутентифицированных
        if (!isset($_SESSION['basket'])) {
            $_SESSION['basket'] = array();
        }
        $_SESSION['basket']['list'] = $this->list;

        // Хранилище в cookie, на год. Комментарии не храним.
        $cookie_str = '';
        $first = 1;
        foreach ($this->list as $item) {
            //if(!$item['pos_id'])
            //	continue;
            if (!$first) {
                $cookie_str .= ',';
            } else {
                $first = 0;
            }
            $cookie_str .= $item['pos_id'] . ':' . $item['cnt'];
        }
        setcookie('basket', $cookie_str, time() + 60 * 60 * 24 * 365);

        // Хранилище в базе на сервере для зарегистрированных пользователей
        if (isset($_SESSION['uid'])) {
            if ($_SESSION['uid'] > 0) {
                $db->startTransaction();
                $db->query("DELETE FROM `users_basket` WHERE `user_id`=" . intval($_SESSION['uid']));
                foreach ($this->list as $item) {
                    if (!$item['pos_id']) {
                        continue;
                    }
                    $item['user_id'] = $_SESSION['uid'];
                    if ($item['comment'] === null) {
                        $item['comment'] = '';
                    }
                    $db->insertA('users_basket', $item);
                }
                $db->commit();
            }
        }
    }

    /// Получить список товаров в корзине
    /// @return Массив, индексы которого - pos_id, а элемены - ассоциативные массивы с ключами pos_id, cnt, comment
    public function getItems() {
        return $this->list;
    }

    // Получить количество товаров в корзине
    public function getCount() {
        return count($this->list);
    }

    /// Добавить/обновить элемент крозины
    public function setItem($pos_id, $count, $comment = null) {
        settype($pos_id, 'int');
        settype($count, 'int');
        if (isset($this->list[$pos_id]) && $comment === null) {
            $this->list[$pos_id] = array('pos_id' => $pos_id, 'cnt' => $count, 'comment' => $this->list[$pos_id]['comment']);
        } else {
            $this->list[$pos_id] = array('pos_id' => $pos_id, 'cnt' => $count, 'comment' => $comment);
        }
    }

    /// Удалить элемент из корзины
    public function removeItem($pos_id) {
        settype($pos_id, 'int');
        if (isset($this->list[$pos_id])) {
            unset($this->list[$pos_id]);
        }
    }

}
