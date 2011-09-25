<?php

function rusdate($fstr,$rtime=-1)
{
   if($rtime==-1) $rtime=time();
   $dstr=date($fstr,$rtime);
   $dstr=eregi_replace("Monday","Понедельник",$dstr);
   $dstr=eregi_replace("Tuesday","Вторник",$dstr);
   $dstr=eregi_replace("Wednesday","Среда",$dstr);
   $dstr=eregi_replace("Thursday","Четверг",$dstr);
   $dstr=eregi_replace("Friday","Пятница",$dstr);
   $dstr=eregi_replace("Saturday","Суббота",$dstr);
   $dstr=eregi_replace("Sunday","Воскресенье",$dstr);
   $dstr=eregi_replace("January","января",$dstr);
   $dstr=eregi_replace("February","февраля",$dstr);
   $dstr=eregi_replace("March","марта",$dstr);
   $dstr=eregi_replace("April","апреля",$dstr);
   $dstr=eregi_replace("May","мая",$dstr);
   $dstr=eregi_replace("June","июня",$dstr);
   $dstr=eregi_replace("July","июля",$dstr);
   $dstr=eregi_replace("August","августа",$dstr);
   $dstr=eregi_replace("September","сентября",$dstr);
   $dstr=eregi_replace("October","октября",$dstr);
   $dstr=eregi_replace("November","ноября",$dstr);
   $dstr=eregi_replace("December","декабря",$dstr);
   return $dstr;
}


function skin_render($page,$tpl)
{
	global $tmpl, $CONFIG;
	
	if(@$_SESSION['uid'])
	{
		$tmpl->AddRMenu("<li class='noborder'><a href='/login.php?mode=logout' title='Покинуть сайт'>Выход</a></li>");
		$tmpl->AddLMenu("<p class='vspace sidehead'><a>{$_SESSION['name']}:</a></p>
		<ul><li><a href='/login.php?mode=logout' accesskey='q' title='Выйти с сайта'>Выход</a></li><li><a href='/user.php' accesskey='s' title='Дополнительные возможности'>Возможности</a></li></ul>");
	}
	else
	{
		$tmpl->AddRMenu("<li class='noborder'><a href='/login.php' title='Войти на сайт'>Вход</a></li>");
		$tmpl->AddLMenu("<ul><li><a href='/login.php' accesskey='l' title='Войти на сайт'>Вход на сайт</a></li><li><a href='/login.php?mode=reg'>Регистрация</a></li></ul>");
	}
	
	$rr=$ll='';
	if(isset($_SESSION['korz_cnt'])) 
	{
		$rr="style='background-color: #f94;'";
		$ll="style='color: #fff; font-weight: bold;'";
	}
	
	$tmpl->AddLMenu("<p class='vspace sidehead'><a class='wikilink' >Навигация</a></p>
	<ul>
	<li><a class='selflink' href='/index.php'>Домашняя страница</a></li>
	<li $rr><a class='selflink' href='/vitrina.php?mode=basket' $ll rel='nofollow'>Корзина</a></li>
	<li><a class='selflink' href='/adv_search.php'>Поиск по параметрам</a></li>
	<li><a class='selflink' href='/message.php?to={$CONFIG['site']['doc_adm_email']}&amp;opt=email'>Задать вопрос</a></li>
	<li><a class='selflink' href='/photogalery.php'>Фотогалерея</a></li>
	<li><a class='wikilink' href='/wiki/ContactInfo.html'>Контактная информация</a></li>
	</ul>
	<p class='vspace sidehead'> <a class='wikilink' href='/wiki/'>Статьи</a></p>
	<ul>
	<li><a class='wikilink' href='/wiki/Zakaz.html'>Как заказать</a></li>
	<li><a class='wikilink' href='/wiki/Delivery.html'>Доставка</a></li>
	</ul>
	<p class='vspace sidehead'> <a class='wikilink' href='/index.php?n=Main.Links'>Наши друзья</a></p>
	<ul>
	<li><a class='urllink' href='http://nsk-ps.info'>nsk-ps.info</a></li>
	<li><a class='urllink' href='http://root-shop.ru'>root-shop.ru</a></li>
	<li><a class='urllink' href='http://magnoliasib.ru'>magnoliasib.ru</a></li>
	<li><a class='urllink' href='http://tndproject.org'>tndproject.org</a></li>
	</ul>");
	
	$page=$tmpl->page;
	
	ksort($page);
	$sign=array("<!--site-text-->","<!--site-tmenu-->","<!--site-rmenu-->","<!--site-title-->","<!--site-style-->",
	"<!--site-lmenu-->","<!--site-notes-->");
	if(!isset($tmpl->hide_blocks['left'])) $tpl=str_replace("<!--site-text-->","<div id='wiki-menu' class='wiki-menu'><!--site-lmenu--></div><div id='wiki-page' class='wiki-page'><!--site-text--></div>",$tpl);
	else $tpl=str_replace("<!--site-text-->","<div id='wiki-page-nolmenu' class='wiki-page-nolmenu'><!--site-text--></div>",$tpl);
	if(!isset($tmpl->hide_blocks['right'])) $tpl=str_replace("<!--site-rmenu-->","<div id='info-right'><ul><!--site-rmenu--></ul></div>",$tpl);
	$tpl=str_replace("<!--site-topleft-->",rusdate ("l, d.m.Y H:i"),$tpl);
	$res=str_replace($sign,$page,$tpl);
	return $res;
}


?>
