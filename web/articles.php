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

require_once("core.php");
require_once("include/imgresizer.php");
require_once("include/wikiparser.php");

$wikiparser=new WikiParser();
$wikiparser->reference_wiki	= "/article/";
$wikiparser->reference_site	= @($_SERVER['HTTPS']?'https':'http')."://{$_SERVER['HTTP_HOST']}/";
$wikiparser->image_uri		= "/share/var/wikiphoto/";
$wikiparser->ignore_images	= false;

$p=rcv('p');

if(!$p)
{
	$arr = @explode( '/' , $_SERVER['REQUEST_URI'] );
	$arr = @explode( '.' , $arr[2] );
	$p=@urldecode(urldecode($arr[0]));
}

function articles_form($p,$text='',$type=0)
{
	global $tmpl,$CONFIG;
	$types=array(0=>'Wiki (Простая и безопасная разметка, рекомендуется)', 1=>'HTML (Для профессионалов. Может быть небезопасно.)', 'Wiki+HTML');
	$tmpl->AddText("
	<script type='text/javascript' src='/js/tiny_mce/tiny_mce.js'></script>
	<script type='text/javascript'>

function schange()
{
	var tme=document.getElementById('tme')
	if(tme.checked)
	{
		tinyMCE.init({
		theme : 'advanced',
		mode : 'specific_textareas',
		editor_selector : 'e_msg',
		plugins : 'fullscreen',
		force_hex_style_colors : true,
		theme_advanced_buttons1 : 'bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect',
		theme_advanced_buttons2 : 'cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor',
		theme_advanced_buttons3 : 'tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,iespell,advhr,|,fullscreen',
		theme_advanced_buttons4 : 'insertlayer,moveforward,movebackward,absolute,|,styleprops,spellchecker,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,blockquote,pagebreak,|,insertfile,insertimage',
		theme_advanced_toolbar_location : 'top',
		theme_advanced_toolbar_align : 'left',
		theme_advanced_statusbar_location : 'bottom',
		theme_advanced_resizing : true,
		document_base_url : 'http://{$CONFIG['site']['name']}/articles/',
		fullscreen_new_window : true,
		element_format : 'html',
		plugins : 'autolink,lists,spellchecker,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template',

	});
		tinyMCE.activeEditor.show();
	}
	else		tinyMCE.activeEditor.hide();
}
</script>
	<fieldset>
	<legend>Правка статьи</legend>
	<form action='/articles.php' method='post'>
	<input type='hidden' name='mode' value='save'>
	<input type='hidden' name='p' value='$p'>
	Тип разметки:<br>
	<select name='type' id='select_type' onchange='schange()'>");
	foreach($types AS $id => $name)
	{
		$s=($id==$type)?'selected':'';
		$tmpl->AddText("<option value='$id'{$s}>$name</option>");
	}

	$tmpl->AddText("</select><label><input type='checkbox' id='tme' onclick='schange()'>Визуальный редактор</label><br>
	<textarea class='e_msg' name='text' rows='10' cols='80'>$text</textarea><br>
	<button type='submit'>Сохранить</button>
	</form><br><a href='/wikiphoto.php'>Галерея изображений</a><br>
	<h3>Примеры wiki разметки</h3>
	<table class='list' width='100%'>
	<th><tr>
	</table>");
}

try
{
	if($p=="")
	{
		//if(!isAccess('generic_articles','view'))	throw new AccessException("");
		$tmpl->SetText("<h1 id='page-title'>Статьи</h1>Здесь отображаются все статьи сайта. Так-же здесь находятся мини-статьи с объяснением терминов, встречающихся на витрине и в других статьях. В списке Вы видите системные названия статей - в том виде, в котором они создавались, и видны сайту. Реальные заголовки могут отличаться.");
		$tmpl->SetTitle("Статьи");
		$res=mysql_query("SELECT `name` FROM `articles` ORDER BY `name`");
		$tmpl->AddText("<ul>");
		while($nxt=mysql_fetch_row($res))
		{
			$h=$wikiparser->unwiki_link($nxt[0]);
			$tmpl->AddText("<li><a class='wiki' href='/article/$nxt[0].html'>$h</a></li>");
		}
		$tmpl->AddText("</ul>");
	}
	else
	{
		//if(!isAccess('generic_articles','view'))	throw new AccessException("");
		$res=mysql_query("SELECT `articles`.`name`, `a`.`name`, `articles`.`date`, `articles`.`changed`, `b`.`name`, `articles`.`text`, `articles`.`type`
		FROM `articles`
		LEFT JOIN `users` AS `a` ON `a`.`id`=`articles`.`autor`
		LEFT JOIN `users` AS `b` ON `b`.`id`=`articles`.`changeautor`
		WHERE `articles`.`name` LIKE '$p'");
		if(@$nxt=mysql_fetch_row($res))
		{
			$h=$meta_description=$meta_keywords='';
			$text=$nxt[5];
			if($nxt[6]==0)	$text=strip_tags($text, '<nowiki>');
			if($nxt[6]==0 || $nxt[6]==2)
			{
				$text=$wikiparser->parse(html_entity_decode($text,ENT_QUOTES,"UTF-8"));
				$h=$wikiparser->title;
				$meta_description=@$wikiparser->definitions['meta_description'];
				$meta_keywords=@$wikiparser->definitions['meta_keywords'];
			}
			if($nxt[6]==1 || $nxt[6]==2)	$text=html_entity_decode($text,ENT_QUOTES,"UTF-8");
			if(!$h)
			{
				$h=explode(":",$p,2);
				if($h[1])
					$h=$wikiparser->unwiki_link($h[1]);
				else $h=$wikiparser->unwiki_link($p);
			}
			if($mode=='')
			{
				$tmpl->SetTitle($h);
				if($nxt[4]) $ch=", последнее изменение - $nxt[4], date $nxt[3]";
				else $ch="";
				if($nxt[6]==0 || $nxt[6]==2)	$tmpl->AddText("<h1 id='page-title'>$h</h1>");
				if(@$_SESSION['uid'])
				{
					$tmpl->AddText("<div id='page-info'>Создал: $nxt[1], date: $nxt[2] $ch");
					if(isAccess('generic_articles','edit'))	$tmpl->AddText(", <a href='/articles.php?p=$p&amp;mode=edit'>Исправить</a>");
					$tmpl->AddText("</div>");
				}
				$tmpl->AddText("$text<br><br>");
				$tmpl->SetMetaKeywords($meta_keywords);
				$tmpl->SetMetaDescription($meta_description);

			}
			else
			{
				if($mode=='edit')
				{
					if(!isAccess('generic_articles','edit'))	throw new AccessException("");
					$tmpl->AddText("<h1>Правим $h</h1>
					<h2>=== Оригинальный текст ===</h2>$text<h2>=== Конец оригинального текста ===</h2>");
					articles_form($p,$nxt[5],$nxt[6]);
				}
				else if($mode=='save')
				{
					if(!isAccess('generic_articles','edit'))	throw new AccessException("");
					$type=rcv('type');
					$text=rcv('text');
					$res=mysql_query("UPDATE `articles` SET `changeautor`='$uid', `changed`=NOW() ,`text`='$text', `type`='$type'
					WHERE `name` LIKE '$p'");
					//echo mysql_error();
					if($res)
					{
						header("Location: /articles.php?p=".$p);
						exit();
					}
					else $tmpl->msg("Не удалось сохранить!");

				}
			}
		}
		else
		{
			if($mode=='')
			{
				$res=mysql_query("SELECT `name` FROM `articles` WHERE `name` LIKE '$p:%' ORDER BY `name`");
				if(mysql_num_rows($res))
				{
					$tmpl->SetText("<h1>Раздел $p</h1>");
					$tmpl->SetTitle($p);
					$tmpl->AddText("<ul>");
					while($nxt=mysql_fetch_row($res))
					{
						$h=explode(":",$nxt[0],2);
						$h=$wikiparser->unwiki_link($h[1]);
						$tmpl->AddText("<li><a href='/article/$nxt[0].html'>$h</a></li>");
					}
					$tmpl->AddText("</ul>");
				}
				else
				{
					$tmpl->msg("Извините, статья $p не найдена на нашем сайте. Возможно, вам дали неверную ссылку, либо статья была удалена или перемещена в другое место. Для того, чтобы найти интересующую Вас информацию, воспользуйтесь ","info");
					header('HTTP/1.0 404 Not Found');
					header('Status: 404 Not Found');
					if(isAccess('generic_articles','create', true))
						$tmpl->AddText("<a href='/articles.php?p=$p&amp;mode=edit'>Создать</a>");
				}
			}
			else
			{
				if($mode=='edit')
				{
					if(!isAccess('generic_articles','edit'))	throw new AccessException("");
					$h=$wikiparser->unwiki_link($p);
					$tmpl->AddText("<h1>Создаём $h</h1>");
					articles_form($p);
				}
				else if($mode=='save')
				{
					if(!isAccess('generic_articles','create'))	throw new AccessException("");
					$type=rcvint('type');
					$text=rcvstrsql('text');
					$res=mysql_query("INSERT INTO `articles` (`type`, `name`,`autor`,`date`,`text`)
					VALUES ('$type', '$p','$uid', NOW(), '$text')");
					if(!mysql_errno())
					{
						header("Location: /articles.php?p=".$p);
						exit();
					}
					else throw new MysqlException("Не удалось создать статью!");

				}
			}
		}
	}
}
catch(MysqlException $e)
{
	mysql_query("ROLLBACK");
	$tmpl->AddText("<br><br>");
	$tmpl->msg($e->getMessage(),"err");
}
catch(Exception $e)
{
	mysql_query("ROLLBACK");
	$tmpl->AddText("<br><br>");
	$tmpl->logger($e->getMessage());
}


$tmpl->write();

?>