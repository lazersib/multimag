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
/// Генератор карты сайта в нужном формате
class SiteMap {

    private $maptype; ///< Тип карты
    private $buf = ''; ///< Буфер текстовых данных карты

    /// Конструктор

    function __construct($maptype = 'html') {
        $this->maptype = $maptype;
    }

    /// Получить html код ссылки на группу
    /// @param group	Id группы
    /// @param page		Номер страницы
    function getGroupLink($group, $page = 1) {
        global $CONFIG;
        if ($CONFIG['site']['recode_enable'])
            return "vitrina/ig/$page/$group.html";
        else
            return "vitrina.php?mode=group&amp;g=$group" . ($page ? "&amp;p=$page" : '');
    }

/// Добавить к карте сайта заданную группы витрины с подгруппами
/// @param group Id группы товаров
    function addPriceGroup($group) {
        global $CONFIG, $db;
        $ret = '';
        $res = $db->query("SELECT `id`, `name` FROM `doc_group` WHERE `hidelevel`='0' AND `pid`='$group' ORDER BY `id`");
        if ($res->num_rows) {
            $this->startGroup();
            while ($nxt = $res->fetch_row()) {
                $this->AddLink($this->getGroupLink($nxt[0]), $nxt[1], '0.8');
                $this->addPriceGroup($nxt[0]);
            }
            $this->endGroup();
        }
        return $ret;
    }

    /// Сформировать заголовок карты сайта
    function startMap() {
        if ($this->maptype == 'html')
            $this->buf.="<ul class='items'>";
        else if ($this->maptype == 'xml')
            $this->buf.='<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    }

    /// Сформировать завершающий блок карты сайта
    function endMap() {
        if ($this->maptype == 'html')
            $this->buf.="</ul>";
        else if ($this->maptype == 'xml')
            $this->buf.='</urlset>';
    }

    /// Добавить ссылку в карту
    /// @param link		url ссылки
    /// @param text		Текст ссылки
    /// @param prio		Приоритет ссылки для sitemap
    /// @param changefreq	Частота изменения контента на целевой странице
    /// @param lastmod	Дата последней модификации целевой страницы
    function AddLink($link, $text, $prio = '0.5', $changefreq = 'always', $lastmod = '') {
        if ($lastmod == '')
            $lastmod = date("Y-m-d");
        $host = $_SERVER['HTTP_HOST'];
        $finds = array('"', '&', '>', '<', '\'');
        $replaces = array('&quot;', '&amp;', '&gt;', '&lt;', '&apos;');
        $link = str_replace($finds, $replaces, $link);
        $text = str_replace($finds, $replaces, $text);
        if ($this->maptype == 'html')
            $this->buf.="<li><a href='/$link'>$text</a></li>";
        else if ($this->maptype == 'xml')
            $this->buf.="<url><loc>http://$host/$link</loc><lastmod>$lastmod</lastmod><changefreq>$changefreq</changefreq><priority>$prio</priority></url>\n";
    }

    /// Добавить начало группы ссылок в карту
    function startGroup() {
        if ($this->maptype == 'html')
            $this->buf.="<ul class='items'>";
    }

    /// Добавить завершение группы ссылок в карту
    function endGroup() {
        if ($this->maptype == 'html')
            $this->buf.="</ul>";
    }

    /// Сформировать карту
    function getMap() {
        global $db;
        $wikiparser = new WikiParser();
        $this->buf = '';
        $this->startMap();
        $this->AddLink('index.php', 'Главная', '0.5');
        $this->AddLink('price.php', 'Прайсы', '0.2');
        $this->AddLink('vitrina.php', 'Витрина', '0.8');
        $this->addPriceGroup(0);
        $this->AddLink('articles.php', 'Статьи', '0.5', 'weekly');
        $this->startGroup();
        $res = $db->query("SELECT `name`, `date`, `text` FROM `articles` ORDER BY `name`");
        while ($nxt = $res->fetch_row()) {
            @$wikiparser->parse($nxt[2]);
            $h = $wikiparser->title;
            $this->AddLink("article/$nxt[0].html", $h, '0.4', 'weekly', $nxt[1]);
        }
        $this->endGroup();
        $this->AddLink('news.php', 'Новости', '0.1');
        $this->AddLink('photogalery.php', 'Фотогалерея', '0.1');
        $this->AddLink('voting.php', 'Голосования', '0.1');
        $this->AddLink('survey.php', 'Опросы', '0.1');
        $this->AddLink('message.php', 'Отправить сообщение', '0.0');
        $this->AddLink('sitemap.xml', 'XML Sitemap', '0.0');
        $this->endMap();
        return $this->buf;
    }

}
