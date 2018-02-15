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
namespace api; 

/// Обработчик API
class getmessage {
    
    
    public function dispatch($action, $data=null) {
        ignore_user_abort(FALSE);
        sleep(10);
        $agent_id = rand(1, 100);
        return array(
            'title' => 'Уведомление о звонке',
            'icon' => '/img/i_add.png',
            'message' => 'Лови звонок агента '.$agent_id,
            'link' => '/docs.php?l=agent&mode=srv&opt=ep&pos='.$agent_id,
        );
    }
}