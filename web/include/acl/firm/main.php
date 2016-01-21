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
//

namespace acl\firm;

class main extends \acl\aclContainer {
    protected $name = "Документы организаций";
    
    public function __construct() {
        $firm_ldo = new \Models\LDO\firmnames();
        $list = $firm_ldo->getData();
        $this->list['global'] = array(
                "name" => 'Глобальный доступ (реализовано не полностью)',
                "mask" =>  \acl::VIEW | \acl::UPDATE | \acl::DELETE | \acl::CREATE 
                    | \acl::APPLY | \acl::CANCEL | \acl::TODAY_APPLY | \acl::TODAY_CANCEL
                    | \acl::CANCEL_FORCE | \acl::GET_PRINTFORM | \acl::GET_PRINTDRAFT | \acl::VIEW
            );
        foreach ($list as $id => $item) {
            $this->list[$id] = array(
                    "name" => $id.': '.$item,
                    "mask" =>  \acl::VIEW | \acl::UPDATE | \acl::DELETE | \acl::CREATE 
                        | \acl::APPLY | \acl::CANCEL | \acl::TODAY_APPLY | \acl::TODAY_CANCEL
                        | \acl::CANCEL_FORCE | \acl::GET_PRINTFORM | \acl::GET_PRINTDRAFT | \acl::VIEW
                );
        }
    }
    
}
