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

namespace modules\service;

/// Страница со статьёй внутренней базы знаний в wiki формате
class wikipage extends \modules\IWikiPage {
    
    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'service.wikipage';
        $this->table_name = 'intkb';
        $this->files_fn = 'intfiles';
        if (\cfg::get('site', 'rewrite_enable')) {
            $this->link_prefix = '/intkb/';
        }
        else {
            $this->link_prefix = '/intkb.php';
        }
    }

    public function getName() {
        return 'База знаний';
    }
    
    public function getDescription() {
        return 'Модуль для просмотра и редактирования статей базы знаний';  
    }
   
    /// Отобразить список статей
    protected function viewList() {
        global $tmpl;
        $tmpl->setContent("<h1>Список статей в базе знаний</h1><ul><li><a href='http://multimag.tndproject.org/wiki/userdoc' style='color:#F00'>Общая справка по multimag</a></li></ul>");
        $tmpl->setTitle("Статьи");
        parent::viewList();
    }
}
