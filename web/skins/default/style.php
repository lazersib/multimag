<?php

function rusdate($fstr,$rtime=-1)
{
   if($rtime==-1) $rtime=time();
   $dstr=date($fstr,$rtime);
   $dstr=str_ireplace("Monday","Понедельник",$dstr);
   $dstr=str_ireplace("Tuesday","Вторник",$dstr);
   $dstr=str_ireplace("Wednesday","Среда",$dstr);
   $dstr=str_ireplace("Thursday","Четверг",$dstr);
   $dstr=str_ireplace("Friday","Пятница",$dstr);
   $dstr=str_ireplace("Saturday","Суббота",$dstr);
   $dstr=str_ireplace("Sunday","Воскресенье",$dstr);
   $dstr=str_ireplace("January","января",$dstr);
   $dstr=str_ireplace("February","февраля",$dstr);
   $dstr=str_ireplace("March","марта",$dstr);
   $dstr=str_ireplace("April","апреля",$dstr);
   $dstr=str_ireplace("May","мая",$dstr);
   $dstr=str_ireplace("June","июня",$dstr);
   $dstr=str_ireplace("July","июля",$dstr);
   $dstr=str_ireplace("August","августа",$dstr);
   $dstr=str_ireplace("September","сентября",$dstr);
   $dstr=str_ireplace("October","октября",$dstr);
   $dstr=str_ireplace("November","ноября",$dstr);
   $dstr=str_ireplace("December","декабря",$dstr);
   return $dstr;
}


function skin_prepare()
{
	global $tmpl, $CONFIG;

	if(@$_SESSION['uid'])
	{
		$tmpl->addRight("<li class='noborder'><a href='/login.php?mode=logout' title='Покинуть сайт'>Выход</a></li>");
		$tmpl->addLeft("<p class='vspace sidehead'><a>{$_SESSION['name']}:</a></p>
		<ul><li><a href='/login.php?mode=logout' accesskey='q' title='Выйти с сайта'>Выход</a></li><li><a href='/user.php' accesskey='s' title='Дополнительные возможности'>Возможности</a></li></ul>");
	}
	else
	{
		$tmpl->addRight("<li class='noborder'><a href='/login.php' title='Войти на сайт'>Вход</a></li>");
		$tmpl->addLeft("<ul><li><a href='/login.php' accesskey='l' title='Войти на сайт'>Вход на сайт</a></li><li><a href='/login.php?mode=reg'>Регистрация</a></li></ul>");
	}

	$rr=$ll='';
	if(isset($_SESSION['korz_cnt']))
	{
		$rr="style='background-color: #f94;'";
		$ll="style='color: #fff; font-weight: bold;'";
	}

	$tmpl->addLeft("<p class='vspace sidehead'><a class='wikilink' >Навигация</a></p>
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
	<li><a class='wikilink' href='/wiki/Payment.html'>Оплата</a></li>
	</ul>
	<p class='vspace sidehead'> <a class='wikilink' href='/index.php?n=Main.Links'>Наши друзья</a></p>
	<ul>
	<li><a class='urllink' href='http://nsk-ps.info'>nsk-ps.info</a></li>
	<li><a class='urllink' href='http://root-shop.ru'>root-shop.ru</a></li>
	<li><a class='urllink' href='http://magnoliasib.ru'>magnoliasib.ru</a></li>
	<li><a class='urllink' href='http://tndproject.org'>tndproject.org</a></li>
	</ul>");

	if(!isset($tmpl->hide_blocks['left'])) $tmpl->tpl=str_replace("<!--site-content-->","<div id='wiki-menu' class='wiki-menu'><!--site-left--></div><div id='wiki-page' class='wiki-page'><!--site-content--></div>",$tmpl->tpl);
	else $tmpl->tpl=str_replace("<!--site-content-->","<div id='wiki-page-nolmenu' class='wiki-page-nolmenu'><!--site-content--></div>",$tmpl->tpl);
	if(!isset($tmpl->hide_blocks['right'])) $tmpl->tpl=str_replace("<!--site-right-->","<div id='info-right'><ul><!--site-right--></ul></div>",$tmpl->tpl);
	
	$tmpl->setCustomBlockData('topleft', rusdate ("l, d.m.Y H:i"));
}


?>
