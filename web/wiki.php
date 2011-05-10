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
$p=rcv('p');


if(!$p)
{
	$arr = explode( '/' , $_SERVER['REQUEST_URI'] );
	$arr = explode( '.' , $arr[2] );
	$p=urldecode(urldecode($arr[0]));
}

$rights=getright('wiki',$uid);

function wiki_form($p,$text='')
{
	global $tmpl;
	$tmpl->AddText("
	<form action='/wiki.php' method='post'>
	<input type=hidden name='mode' value='save'>
	<input type=hidden name='p' value='$p'>
	<textarea class='e_msg' name='text' rows='8' cols='30'>$text</textarea><br>
	<input type=submit value='Сохранить'>
	</form><br><a href='/wikiphoto.php'>Галерея изображений</a>");
}


if($p=="")
{
	if($rights['read'])
	{
		$tmpl->SetText("<h1 id='page-title'>Статьи</h1>Здесь собранны различные статьи, которые могут пригодиться посетителям сайта. Так-же здесь находятся мини-статьи с объяснением терминов, встречающихся на витрине и в других статьях. Раздел постоянно наполняется. В списке Вы видите системные названия статей - в том виде, в котором они создавались, и видны сайту. Реальные заголовки могут отличаться.");
		$tmpl->SetTitle("Статьи");
		$res=mysql_query("SELECT * FROM `wiki` ORDER BY `name`");
		$tmpl->AddText("<ul>");
		while($nxt=mysql_fetch_row($res))
		{
			$h=$wikiparser->unwiki_link($nxt[0]);
			$tmpl->AddText("<li><a class='wiki' href='/wiki/$nxt[0].html'>$h</a></li>");
		}
		$tmpl->AddText("</ul>");
	}
	else $tmpl->msg("У Вас нет прав!");
}
else
{
	if($rights['read'])
	{
		$res=mysql_query("SELECT `wiki`.`name`, a.`name`, `wiki`.`date`, `wiki`.`changed`, `b`.`name`, `wiki`.`text`
		FROM `wiki`
		LEFT JOIN `users` AS `a` ON `a`.`id`=`wiki`.`autor`
		LEFT JOIN `users` AS `b` ON `b`.`id`=`wiki`.`changeautor`
		WHERE `wiki`.`name` LIKE '$p'");
		if(@$nxt=mysql_fetch_row($res))
		{
			$text=$wikiparser->parse(html_entity_decode($nxt[5],ENT_QUOTES,"UTF-8"));
			$h=$wikiparser->title;
			if(!$h)
			{
				$h=explode(":",$p,2);
				if($h[1])
					$h=$wikiparser->unwiki_link($h[1]);
				else $h=$wikiparser->unwiki_link($p);
			}
			//else $h=$wikiparser->unwiki_link($nxt[0]);
			if($mode=='')
			{
				$tmpl->SetTitle($h);
				if($nxt[4]) $ch=", последнее изменение - $nxt[4], date $nxt[3]";
				else $ch="";
				$tmpl->AddText("<h2 id='page-title'>$h</h2>");
				if(@$_SESSION['uid'])
				{
					$tmpl->AddText("<div id='page-info'>Создал: $nxt[1], date: $nxt[2] $ch");
					if($rights['write'])	$tmpl->AddText(", <a href='/wiki.php?p=$p&amp;mode=edit'>Исправить</a>");
					$tmpl->AddText("</div>");
				}
				$tmpl->AddText("$text<br><br>");
				
			}
			else if($rights['write'])
			{
				if($mode=='edit')
				{
					$tmpl->AddText("<h1>Правим $h</h1>
					<h2>=== Оригинальный текст ===</h2>$text<h2>=== Конец оригинального текста ===</h2>");
					wiki_form($p,$nxt[5]);
				}
				else if($mode=='save')
				{
					$text=rcv('text');
					$res=mysql_query("UPDATE `wiki` SET `changeautor`='$uid', `changed`=NOW() ,`text`='$text'
					WHERE `name` LIKE '$p'");
					//echo mysql_error();
					if($res)
					{
						header("Location: /wiki.php?p=".$p);
						exit();
					}
					else $tmpl->msg("Не удалось сохранить!");
	
				}
			}
			else $tmpl->msg("У Вас нет прав!");
		}
		else
		{
			if($mode=='')
			{
				$res=mysql_query("SELECT * FROM `wiki` WHERE `name` LIKE '$p:%' ORDER BY `name`");
				if(mysql_num_rows($res))
				{
					$tmpl->SetText("<h1>Раздел $p</h1>");
					$tmpl->SetTitle($p);
					$tmpl->AddText("<ul>");
					while($nxt=mysql_fetch_row($res))
					{
						$h=explode(":",$nxt[0],2);
						$h=$wikiparser->unwiki_link($h[1]);
						$tmpl->AddText("<li><a href='/wiki/$nxt[0].html'>$h</a></li>");
					}
					$tmpl->AddText("</ul>");
				}
				else
				{		
					$tmpl->msg("Извините, данная страница не найдена ($p)!","info");
					if($rights['write'])
						$tmpl->AddText("<a href='/wiki.php?p=$p&amp;mode=edit'>Создать</a>");
				}
			}
			else if($rights['write'])
			{
				if($mode=='edit')
				{
					$h=$wikiparser->unwiki_link($p);
					$tmpl->AddText("<h1>Создаём $h</h1>");
					wiki_form($p);
				}
				else if($mode=='save')
				{
					$text=rcv('text');
					$res=mysql_query("INSERT INTO `wiki` (`name`,`autor`,`date`,`text`)
					VALUES ('$p','$uid', NOW(), '$text')");
					if($res)
					{
						header("Location: wiki.php?p=".$p);
						exit();
					}
					else $tmpl->msg("Не удалось сохранить!");
	
				}
			}
			else $tmpl->msg("У Вас нет прав!");
		}
	}
	else $tmpl->msg("У Вас нет прав!");

}

$tmpl->write();

?>