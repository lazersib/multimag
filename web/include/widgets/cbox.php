<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
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
namespace Widgets;

class cbox extends \IWidget {

    protected $params;      //< Параметры


    public function getName() {
        return 'Цветной квадрат';
    }

    public function getDescription() {
        return 'Цветной квадрат';
    }

    public function setParams($param_str) {
        $this->params = $param_str;
        return true;
    }

    public function getHTML() {
        $size = 16;
        $color = 'FFF';
        if(!$this->params) {
            return '{{CBOX: цвет не задан}}';
        }
        if(stripos($this->params, ':')!==false) {
            list($color, $size) = explode(':', $this->params);            
        } else {
            $color = $this->params;
        }
        settype($size, 'int');
        if(!preg_match('([0-9a-fA-F]{3,6})', $color)) {
            return '{{CBOX: цвет задан некорректно1}}';
        }
        if(strlen($color)!=3 && strlen($color)!=6) {
            return '{{CBOX: цвет задан некорректно2}}';
        }
        return "<div style='border:1px solid #000;display:inline-block;width:{$size}px;height:{$size}px;background-color:#$color'>&nbsp;</div>";
    }

}
