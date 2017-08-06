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
namespace Widgets;

class Incl extends \IWidget {

    protected $article_name;      //< Имя статьи

    public function getName() {
        return 'Вставка другой статьи';
    }

    public function getDescription() {
        return 'Вставка другой статьи';
    }

    public function setParams($param_str) {
        $this->article_name = $param_str;
        return true;
    }

    public function getHTML() {
        global $CONFIG, $db;
        $page_escaped = $db->real_escape_string($this->article_name);
        $res = $db->query("SELECT `articles`.`name` AS `article_name`, `articles`.`date`, `articles`.`changed`, `articles`.`text`, `articles`.`type`
            FROM `articles`
            WHERE `articles`.`name` LIKE '$page_escaped'");
        if ($res->num_rows) {
            $wikiparser = new \WikiParser();
            $nxt = $res->fetch_assoc();
            $text = $nxt['text'];
            if ($nxt['type'] == 0 || $nxt['type'] == 2) {
                $text = $wikiparser->parse($text);
            }

            return "<div class='include_text'>$text</div>";
            
        } 
        return '{{ARTICLE '.html_out($this->article_name).' NOT FOUND}}';
    }

}
