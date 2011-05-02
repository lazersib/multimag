<?php

//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2010, BlackLight, TND Team, http://tndproject.org
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

include_once("core.php");
$tmpl->SetTitle("Карта сайта");

function GetGroupLink($group, $page=0)
{
	global $CONFIG;
	if($CONFIG['site']['recode_enable'])	return "/vitrina/ig/$page/$group.html";
	else					return "/vitrina.php?mode=group&amp;g=$group".($page?"&amp;p=$page":'');
}

function GroupList($group)
{
	global $CONFIG;
	$ret='';
	$res=mysql_query("SELECT `id`, `name` FROM `doc_group` WHERE `hidelevel`='0' AND `pid`='$group' ORDER BY `id`");
	if(mysql_errno())	throw new MysqlException('Не удалось выбрать список групп');
	if(mysql_num_rows($res))
	{
		$ret.="<ul>";
		while($nxt=mysql_fetch_row($res))
		{
			$ret.="<li><a href='".GetGroupLink($nxt[0])."'>$nxt[1]</a>";
			$ret.=GroupList($nxt[0]);
			$ret.="</li>";
		}
		$ret.="</ul>";
	}
	return $ret;
}

$tmpl->SetText("<h1 id='page-title'>Карта сайта</h1>
<ul>
<li><a href='/index.php'>Главная</a></li>
<li><a href='/price.php'>Прайсы</a></li>
<li><a href='/vitrina.php'>Витрина</a>".GroupList(0)."</li>
<li><a href='/wiki.php'>Статьи</a>
<ul>");

$res=mysql_query("SELECT * FROM `wiki` ORDER BY `name`");
if(mysql_errno())	throw new MysqlException('Не удалось выбрать список статей');
while($nxt=mysql_fetch_row($res))
{
	$wikiparser->parse(html_entity_decode($nxt[5],ENT_QUOTES,"UTF-8"));
	$h=$wikiparser->title;
	$tmpl->AddText("<li><a class='wiki' href='/wiki/$nxt[0].html'>$h</a></li>");
}
$tmpl->AddText("</ul>");


$tmpl->AddText("</ul></li>
<li><a href='/golos.php'>Голосования</a></li>
<li><a href='/photogalery.php'>Фотогалерея</a></li>
<li><a href='/message.php'>Отправить сообщение</a></li>
</ul>");

$tmpl->write();

?>