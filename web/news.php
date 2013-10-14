<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2013, BlackLight, TND Team, http://tndproject.org
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

/// Класс новостного модуля. Формирует ленты новостей. Предоставляет средства для добавления новостей и рассылки уведомлений.
class NewsModule
{

/// Проверка и исполнение recode-запроса
public function ProbeRecode()
{
	global $tmpl, $CONFIG;
	/// Обрабатывает запросы-ссылки  вида http://example.com/news/news.html
	/// Возвращает false в случае неудачи.
	$arr = explode( '/' , $_SERVER['REQUEST_URI'] );
	if(!is_array($arr))	return false;
	if(count($arr)<3)	return false;
	$mode = @explode( '.' , $arr[2]);
	$query = @explode( '.' , $arr[3]);
	if(is_array($mode))	$mode=$mode[0];
	else			$mode=$arr[2];
	if(is_array($query))	$query=$query[0];
	else			$query=$arr[3];
	if($mode=='read')
	{
		if(isAccess('generic_news','view'))
			$this->View($query);
		return true;
	}
	else if($mode=='all' || $mode=='news' || $mode=='stocks' || $mode=='events')
	{
		if(isAccess('generic_news','create',1))	$tmpl->addContent("<a href='/news.php?mode=add&amp;opt=$mode'>Добавить новость</a><br>");
		if(isAccess('generic_news','view'))
		{
			$this->ShowList($mode);
		}
		return true;
	}
	else
	{
		header('HTTP/1.0 404 Not Found');
		header('Status: 404 Not Found');
		throw new Exception("Новость не найдена");
	}
	return false;
}

/// Отобразить страницу новостей
/// @param mode: '' - список новостей,
public function ExecMode($mode='')
{
	global $tmpl, $CONFIG;
	$tmpl->setContent("<div id='breadcrumbs'><a href='/'>Главная</a>Новости</div><h1>Новости сайта</h1>");
	$tmpl->setTitle("Новости сайта - ".$CONFIG['site']['display_name']);
	if($mode=='')
	{
		if(isAccess('generic_news','create',1))	$tmpl->addContent("<a href='/news.php?mode=add&amp;opt=".@$_REQUEST['type']."'>Добавить новость</a><br>");
		if(isAccess('generic_news','view'))
		{
			$this->ShowList(@$_REQUEST['type']);
		}
	}
	else if($mode=='read')
	{
		if(isAccess('generic_news','view'))
			$this->View(@$_REQUEST['id']);
	}
	else if($mode=='add')
	{
		if(isAccess('generic_news','create'))
			$this->WriteForm();
	}
	else if($mode=='write')
	{
		if(isAccess('generic_news','create'))
			$this->SaveAndSend();
	}
	else
	{
		header('HTTP/1.0 404 Not Found');
		header('Status: 404 Not Found');
		throw new Exception("Неверный $mode");
	}
}

/// Отобразить летну новостей заданного типа
/// @param type: '' - любые типы, news - только новости, stocks - только акции, events - только события
protected function ShowList($type='')
{
	global $tmpl, $CONFIG, $wikiparser, $db;
	switch($type)
	{
		case 'news':	$name='Новости';
				$where="WHERE `news`.`type`='novelty'";
				break;
		case 'stocks':	$name='Акции';
				$where="WHERE `news`.`type`='stock'";
				break;
		case 'events':	$name='События';
				$where="WHERE `news`.`type`='event'";
				break;
		default:	$type='';
				$name='Новости, акции, события';
				$where='';
	}
	$res=$db->query("SELECT `news`.`id`, `news`.`text`, `news`.`date`, `users`.`name` AS `autor_name`, `news`.`ex_date`, `news`.`img_ext`, `news`.`type` FROM `news`
	INNER JOIN `users` ON `users`.`id`=`news`.`autor`
	$where
	ORDER BY `date` DESC LIMIT 50");
	if($res->num_rows())
	{
		$tmpl->setContent("<div id='breadcrumbs'><a href='/'>Главная</a>$name</div><h1>$name</h1>");
		$tmpl->setTitle("$name сайта - ".$CONFIG['site']['display_name']);
		if(isAccess('generic_news','create',1))
			$tmpl->addContent("<a href='/news.php?mode=add&amp;opt=$type'>Добавить новость</a><br>");
		while($nxt=$res->fetch_assoc())
		{
			$wikiparser->title='';
			$tmpl->addContent("<div class='news-block'>");
			$text=$wikiparser->parse( html_out($nxt['text']) );
			if($nxt['img_ext'])
			{
				$miniimg=new ImageProductor($nxt['id'],'n', $nxt['img_ext']);
				$miniimg->SetX(48);
				$miniimg->SetY(48);
				$tmpl->addContent("<img src='".$miniimg->GetURI()."' style='float: left; margin-right: 10px;' alt=''>");
			}
			if($nxt['type']=='stock')	$do="<br><i><u>Действительно до:	{$nxt['ex_date']}</u></i>";
			else if($nxt['type']=='event')	$do="<br><i><u>Дата проведения:	{$nxt['ex_date']}</u></i>";
			else			$do='';
			$tmpl->addContent("<h3>{$wikiparser->title}</h3><p>$text<br><i>{$nxt['date']}, {$nxt['autor_name']}</i>$do</p></div>");
			// <!--<br><a href='/forum.php'>Комментарии: 0</a>-->
		}
	}
	else $tmpl->msg("$name отсутствуют");
}

/// Отобразить заданную новость
protected function View($id)
{
	global $tmpl, $wikiparser, $db;
	$res=$db->query("SELECT `news`.`id`, `news`.`text`, `news`.`date`, `users`.`name` AS `autor_name`, `news`.`ex_date`, `news`.`img_ext`, `news`.`type` FROM `news`
	INNER JOIN `users` ON `users`.`id`=`news`.`autor`
	WHERE `news`.`id`='$id'");
	if($res->num_rows)
	{
		while($nxt=$res->fetch_assoc())
		{
			$wikiparser->title='';
			$text=$wikiparser->parse( html_out($nxt['text']) );
			if($nxt['type'])	$do="<br><i><u>Действительно до:	{$nxt['ex_date']}</u></i>";
			else			$do='';
			$tmpl->setContent("<div id='breadcrumbs'><a href='/'>Главная</a><a href='/news.php'>Новости</a>{$wikiparser->title}</div><h1>{$wikiparser->title}</h1><p>$text<br><i>{$nxt['date']}, {$nxt['autor_name']}</i><br>$do</p>");
			// <a href='/forum.php'>Комментарии: 0</a>
		}
	}
	else
	{
		header('HTTP/1.0 404 Not Found');
		header('Status: 404 Not Found');
		throw new Exception('Новость не найдена! Воспользуйтесь списком новостей.');
	}
}

/// Форма создания новости
protected function WriteForm()
{
	global $tmpl;
	$novelty_c=$stock_c=$event_c='';
	switch(@$_REQUEST['opt'])
	{
		case 'news':	$novelty_c=' checked';
				break;
		case 'stocks':	$stock_c=' checked';
				break;
		case 'events':	$event_c=' checked';
				break;
	}
	$tmpl->addContent("
	<form action='' method='post' enctype='multipart/form-data'>
	<h2>Добавление новости</h2>
	<input type='hidden' name='mode' value='write'>
	Класс новости:<br>
	<small>Определяет место её отображения</small><br>
	<label><input type='radio' name='type' value='novelty'$novelty_c>Обычная<br><small>Отображается только в ленте новостей</small></label><br>
	<label><input type='radio' name='type' value='stock'$stock_c>Акция<br><small>Отображается в ленте новостей и списке акций. Дата - дата окончания акции</small></label><br>
	<label><input type='radio' name='type' value='event'$event_c>Событие<br><small>Проведение выставки, распродажа, конурс, итд. Дата - дата наступления события</small></label><br>
	<br>
	<label><input type='checkbox' name='no_mail' value='1'>Не выполнять рассылку</label><br>
	Дата:<br>
	<input type='text' name='ex_date'><br><br>
	Текст новости:<br>
	<small>Можно использовать wiki-разметку. Заголовок будет взят из текста.</small><br>
	<textarea name='text' class='e_msg' rows='6' cols='80'></textarea><br><br>
	Изображение для списка новостей (jpg, png, gif):<br>
	<small>Будет автоматически уменьшено до нужного размера.</small><br>
	<input type='hidden' name='MAX_FILE_SIZE' value='8000000'>
	<input name='img' type='file'><br><br>
	<button type='submit'>Добавить новость</button><br>
	<small>При нажатии кнопки так же будет выполнена рассылка</small>
	</form>");
}

/// Запись новости в хранилище
protected function SaveAndSend()
{
	global $tmpl, $wikiparser, $CONFIG, $db;
	$text		= strip_tags( request('text') );
	$type		= request('type');
	$ex_date	= date("Y-m-d", strtotime( request('ex_date') ) );
	$no_mail	= request('no_mail');
	$uwtext		= $wikiparser->parse( html_entity_decode($text,ENT_QUOTES,"UTF-8") );
	$title		= $wikiparser->title;
	$uwtext		= strip_tags($uwtext);
	$uwtext		= $wikiparser->title."\n".$uwtext;
	$ext='';

	if($type!='novelty' && $type!='stock' && $type!='event')
		$type='novelty';

	$db->query("START TRANSACTION");

	$title_sql=$db->real_escape_string($title);
	$text_sql=$db->real_escape_string($text);

	$res=$db->query("INSERT INTO `news` (`type`, `title`, `text`,`date`, `autor`, `ex_date`)
	VALUES ('$type', '$title', '$text', NOW(), '{$_SESSION['uid']}', '$ex_date' )");

	$news_id=$db->insert_id;
	if(!$news_id)		throw new Exception("Не удалось получить ID новости");
	if($type=='stock')	$uwtext.="\n\nАкция действует до: $ex_date\n";

	if(is_uploaded_file($_FILES['img']['tmp_name']))
	{
		$aa=getimagesize($_FILES['img']['tmp_name']);
		if(!$aa)			throw new Exception('Полученный файл не является изображением');
		if((@$aa[0]<20)||(@$aa[1]<20))	throw new Exception('Слишком мальенькое изображение');
		switch($aa[2])
		{
			case IMAGETYPE_GIF:	$ext='gif'; break;
			case IMAGETYPE_JPEG:	$ext='jpg'; break;
			case IMAGETYPE_PNG:	$ext='png'; break;
			default:		throw new Exception('Формат изображения не поддерживается');
		}
		@mkdir($CONFIG['site']['var_data_fs']."/news/",0755);
		$m_ok=move_uploaded_file($_FILES['img']['tmp_name'], $CONFIG['site']['var_data_fs']."/news/$news_id.$ext");
		if(!$m_ok)			throw new Exception("Не удалось записать изображение в хранилище");
		$res=$db->query("UPDATE `news` SET `img_ext`='$ext' WHERE `id`='$news_id'");
	}
	if(!$no_mail)
	{
		SendSubscribe("Новости сайта", $uwtext);
		$tmpl->msg("Рассылка выполнена","ok");
	}
	$db->commit();
	$tmpl->msg("Новость добавлена!","ok");

}
/// Получить ссылку на новость с заданным ID
protected function GetNewsLink($id, $alt_param='')
{
	global $CONFIG;
	if($CONFIG['site']['recode_enable'])	return "/news/read/$id.html".($alt_param?"?$alt_param":'');
	else					return "/news.php?mode=read&amp;id=$id".($alt_param?"&amp;$alt_param":'');
}


};


try
{
	$tmpl->setTitle("Новости");
	if(file_exists( $CONFIG['site']['location'].'/skins/'.$CONFIG['site']['skin'].'/news.tpl.php' ) )
		include_once($CONFIG['site']['location'].'/skins/'.$CONFIG['site']['skin'].'/news.tpl.php');
	if(!isset($news_module))		$news_module=new NewsModule();
	if(!$news_module->ProbeRecode())	$news_module->ExecMode($mode);
}
catch(mysqli_sql_exception $e)
{
	$db->rollback();
	$id = $tmpl->logger($e->getMessage(), 1);
	$tmpl->addContent("<br><br>");
	$tmpl->msg("Ошибка базы данных, $id","err");
}
catch(Exception $e)
{
	$db->rollback();
	$tmpl->addContent("<br><br>");
	$tmpl->logger($e->getMessage());
}



$tmpl->write();
?>