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

/// Контейнер - очередь с возможностью удаления по сумме значений.
class ValueQueue {
    protected $container;
    
    public function __construct() {
        $this->container = array();
    }
    
    public function append($value, $data = null) {
        $this->container[] = array('data'=>$data, 'value'=>$value);
    }
    
    public function remove($value) {
        while($value>0 && count($this->container)>0) {
            $cur_value = $this->container[0]['value'];
            if ($cur_value >= $value) {
                $this->container[0]['value'] -= $value;
                $value = 0;
            } else {
                $value -= $cur_value;
                array_shift($this->container);
            }
        }
        return $value;
    }
    
    public function getContainer() {
        return $this->container;
    }
}
