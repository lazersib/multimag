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
namespace actions;

/// Очистка от неподтверждённых пользователей
class UserFree extends \Action {
    
     /// Конструктор
    public function __construct($config, $db) {
        parent::__construct($config, $db);
        $this->interval = self::DAILY;
    }

    /// Получить название действия
    public function getName() {
        return "Очистка от неподтверждённых пользователей";
    }    
    
    /// Проверить, разрешен ли периодический запуск действия
    public function isEnabled() {
        return \cfg::get('auto', 'user_del_days')>0?true:false;
    }   

    /// @brief Запустить
    public function run() {
        $dtim = time() - 60 * 60 * 24 * \cfg::get('auto', 'user_del_days');
        $dtim_p = date('Y-m-d H:i:s', $dtim);
        $res = $this->db->query("SELECT `users`.`id` FROM `users`
            LEFT JOIN `users_openid` ON `users_openid`.`user_id`=`users`.`id`
            LEFT JOIN `users_oauth` ON `users_oauth`.`user_id`=`users`.`id`
            WHERE `users_openid`.`user_id` IS NULL AND `users_oauth`.`user_id` IS NULL AND
                `users`.`reg_date`<'$dtim_p' AND `users`.`reg_email_confirm`!='1' AND `reg_phone_confirm`!='1'");
        while ($nxt = $res->fetch_row()) {
            echo "Delete {$nxt[0]}\n";
            $this->db->query("DELETE FROM `users_data` WHERE `uid`=$nxt[0]");
            $this->db->query("DELETE FROM `users` WHERE `id`=$nxt[0]");
        }
    }
}
