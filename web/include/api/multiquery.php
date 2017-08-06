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
namespace api; 

/// Обработчик API мультизапросов
class multiquery {
    
    protected function run($data) {
        if(!is_array($data)) {
            throw new \InvalidArgumentException('Отсутствуют параметы запроса');
        }
        if(!isset($data['query']) || !is_array($data['query'])) {
            throw new \InvalidArgumentException('Отсутствуют параметы мультизапроса');
        }
        if(count($data['query'])==0) {
            return [];
        }
        $ret = array();
        $count = 0;
        foreach($data['query'] as $query) {
            $sub_data = array();
            if(isset($data[$query])) {
                $sub_data = $data[$query];
            }
            list($sub_obj, $sub_action) = explode('.', $query);
            if(!preg_match('/^\\w+$/', $sub_obj)) {
                throw new \InvalidArgumentException('Некорректный подзапрос');
            }
            $class_name = '\\api\\' . $sub_obj;
            if(!class_exists($class_name)) {
                throw new \InvalidArgumentException('Отсутствует обработчик для мультизапроса '.$sub_obj);
            }
            $class = new $class_name;
            $ret[$query] = $class->dispatch($sub_action, $sub_data);           
            $count++;
        }
        $ret['count'] = $count;
        return $ret;
    }
    
    public function dispatch($action, $data=null) {
        switch($action) {
            case 'run':
                return $this->run($data);
            default:
                throw new \NotFoundException('Некорректное действие');
        }
    }
}