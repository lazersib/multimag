<?php

//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2012, BlackLight, TND Team, http://tndproject.org
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

class SiteMap
{
	private $maptype;
	private $buf='';

function __construct($maptype='html')
{
	$this->maptype=$maptype;
}

function getGroupLink($group, $page=1)
{
	global $CONFIG;
	if($CONFIG['site']['recode_enable'])	return "vitrina/ig/$page/$group.html";
	else					return "vitrina.php?mode=group&amp;g=$group".($page?"&amp;p=$page":'');
}

function addPriceGroup($group)
{
	global $CONFIG;
	$ret='';
	$res=mysql_query("SELECT `id`, `name` FROM `doc_group` WHERE `hidelevel`='0' AND `pid`='$group' ORDER BY `id`");
	if(mysql_errno())	throw new MysqlException('Не удалось выбрать список групп');
	if(mysql_num_rows($res))
	{
		$this->startGroup();
		while($nxt=mysql_fetch_row($res))
		{
			$this->AddLink($this->getGroupLink($nxt[0]), $nxt[1], '0.8');
			$this->addPriceGroup($nxt[0]);
		}
		$this->endGroup();
	}
	return $ret;
}

function startMap()
{
	if($this->maptype=='html')	$this->buf.="<ul>";
	else if($this->maptype=='xml')	$this->buf.='<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
}

function endMap()
{
	if($this->maptype=='html')	$this->buf.="</ul>";
	else if($this->maptype=='xml')	$this->buf.='</urlset>';
}

function AddLink($link, $text, $prio='0.5', $changefreq='always', $lastmod='')
{
	if($lastmod=='')	$lastmod=date("Y-m-d");
	$host=$_SERVER['HTTP_HOST'];
	$finds=array('"', '&', '>', '<', '\'');
	$replaces=array('&quot;', '&amp;', '&gt;', '&lt;', '&apos;');
	$link=str_replace($finds, $replaces, $link);
	$text=str_replace($finds, $replaces, $text);
	if($this->maptype=='html')	$this->buf.="<li><a href='/$link'>$text</a></li>";
	else if($this->maptype=='xml')	$this->buf.="<url><loc>http://$host/$link</loc><lastmod>$lastmod</lastmod><changefreq>$changefreq</changefreq><priority>$prio</priority></url>\n";
}

function startGroup()
{
	if($this->maptype=='html')	$this->buf.="<ul>";
}

function endGroup()
{
	if($this->maptype=='html')	$this->buf.="</ul>";
}


function getMap()
{
	global $wikiparser;
	$this->buf='';
	$this->startMap();
	$this->AddLink('index.php','Главная','1.0');
	$this->AddLink('price.php','Прайсы','0.2');
	$this->AddLink('vitrina.php','Витрина','0.8');
	$this->addPriceGroup(0);
	$this->AddLink('wiki.php','Статьи','0.1','weekly');
	$this->startGroup();
	$res=mysql_query("SELECT `name`, `date`, `text` FROM `articles` ORDER BY `name`");
	if(mysql_errno())	throw new MysqlException('Не удалось выбрать список статей');
	while($nxt=mysql_fetch_row($res))
	{
		@$wikiparser->parse(html_entity_decode($nxt[2],ENT_QUOTES,"UTF-8"));
		$h=$wikiparser->title;
		$this->AddLink("article/$nxt[0].html",$h,'0.4','weekly',$nxt[1]);
	}
	$this->endGroup();
	$this->AddLink('articles.php','Статьи');
	$this->AddLink('news.php','Новости');
	$this->AddLink('photogalery.php','Фотогалерея');
	$this->AddLink('message.php','Отправить сообщение');
	$this->AddLink('sitemap.xml','XML Sitemap','0.0');
	$this->endMap();
	return $this->buf;
}
};

?>