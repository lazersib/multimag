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
namespace doc\printforms\realizaciya; 

class tcnd extends \doc\printforms\realizaciya\tc {
    protected $show_agent = 1;  ///< Выводить ли информацию о агенте-покупателе
    protected $show_disc = 0;   ///< Выводить ли информацию о скидках
    protected $show_kkt = 1;    ///< Выводить ли информацию о работе без использования ККТ
    
    /// Возвращает имя документа
    public function getName() {
        return "Товарный чек (без инф.о скидках)";
    }
}
