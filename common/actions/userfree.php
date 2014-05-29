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
//
namespace Actions;

/// Очистка от неподтверждённых пользователей
class UserFree extends \Action {
	
	/// @brief Запустить
	public function run() {
		$dtim = time() - 60 * 60 * 24 * $this->config['auto']['user_del_days'];
		$dtim_p = date('Y-m-d H:i:s', $dtim);
		$res = $this->db->query("SELECT `id` FROM `users`
			LEFT JOIN `users_openid` ON `users_openid`.`user_id`=`users`.`id`
			WHERE `users_openid`.`user_id` IS NULL AND
				`users`.`reg_date`<'$dtim_p' AND `users`.`reg_email_confirm`!='1' AND `reg_phone_confirm`!='1'");
		while ($nxt = $res->fetch_row())
			$this->db->query("DELETE FROM `users` WHERE `id`=$nxt[0]");	
	}
}
