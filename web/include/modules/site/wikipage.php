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

namespace modules\site;

/// Страница со статьёй в wiki формате
class wikipage extends \modules\IWikiPage {
    
    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'generic.articles';
        $this->table_name = 'articles';
        $this->files_fn = 'attachments';
        if (\cfg::get('site', 'rewrite_enable')) {
            $this->link_prefix = '/article/';
        }
        else {
            $this->link_prefix = '/articles.php';
        }
    }

    public function getName() {
        return 'Статьи';
    }
    
    public function getDescription() {
        return 'Модуль для просмотра и редактирования статей сайта';  
    }
   
    /// Отобразить список статей
    protected function viewList() {
        global $tmpl;
        $tmpl->setContent("<h1 id='page-title'>Статьи</h1>Здесь отображаются все статьи сайта. Так-же здесь находятся мини-статьи с объяснением терминов, встречающихся на витрине и в других статьях, и служебные статьи. В списке Вы видите системные названия статей - в том виде, в котором они создавались, и видны сайту. Реальные заголовки могут отличаться.");
        $tmpl->setTitle("Статьи");
        parent::viewList();
    }
}
