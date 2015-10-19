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

namespace acl\generic;

class main extends \acl\aclContainer {
    protected $name = "Общие";
    
    public function __construct() {
        $this->list = array(
            'articles' => array(
                "name" => "Статьи",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE | \acl::DELETE
            ),
            'galery' => array(
                "name" => "Фотогалерея",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE | \acl::DELETE
            ),
            'news' => array(
                "name" => "Новости",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE | \acl::DELETE
            ),
            'votings' => array(
                "name" => "Голосования (пользовательский интерфейс)",
                "mask" => \acl::VIEW | \acl::CREATE | \acl::UPDATE | \acl::DELETE
            ),
        );
    }
    
}
