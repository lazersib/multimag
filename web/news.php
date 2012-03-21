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

include_once("core.php");
include_once("include/imgresizer.php");

try
{
	$tmpl->SetText("<h1 id='page-title'>Новости сайта</h1>");

	$tmpl->SetTitle("Новости сайта - ".$CONFIG['site']['display_name']);
	if(isAccess('generic_news','create',1))
	{
		if($mode=='')	$tmpl->AddText("<a href='?mode=add'>Добавить новость</a><br>");
		else if($mode=='add')
		{
			$tmpl->AddText("
			<form action='' method='post' enctype='multipart/form-data'>
			<h2>Добавление новости</h2>
			<input type='hidden' name='mode' value='write'>
			Класс новости:<br>
			<small>Определяет место её отображения</small><br>
			<label><input type='radio' name='type' value=''>Обычная<br><small>Отображается только в ленте новостей</small></label><br>
			<label><input type='radio' name='type' value='stock'>Акция<br><small>Отображается в ленте новостей и списке акций. Дата - дата окончания акции</small></label><br>
			<label><input type='radio' name='type' value='event'>Событие<br><small>Проведение выставки, распродажа, конурс, итд. Дата - дата наступления события</small></label><br>
			<br>
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
		else if($mode=='write')
		{
			$text=strip_tags(rcv('text'));
			$type=rcv('type');
			$ex_date=rcv('ex_date');
			$ext='';
			$wikiparser->parse(html_entity_decode($text,ENT_QUOTES,"UTF-8"));
			$title=$wikiparser->title;

			mysql_query("START TRANSACTION");
			mysql_query("INSERT INTO `news` (`type`, `title`, `text`,`date`, `autor`, `ex_date`)
			VALUES ('$type', '$title', '$text', NOW(), '$uid','$ex_date' )");
			if(mysql_errno())	throw new MysqlException("Не удалось добавить новость");
			$news_id=mysql_insert_id();
			if(!$news_id)		throw new Exception("Не удалось получить ID новости");
			
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
				mysql_query("UPDATE `news` SET `img_ext`='$ext' WHERE `id`='$news_id'");
				if(mysql_errno())	throw new MysqlException("Не удалось сохранить тип изображения");
			}
		
			SendSubscribe("Новости сайта",$text);
			mysql_query("COMMIT");
			$tmpl->msg("Новость добавлена!","ok");
		
		}
	}

	if(isAccess('generic_news','view'))
	{
		if($mode=='')
		{
			$res=mysql_query("SELECT `news`.`id`, `news`.`text`, `news`.`date`, `users`.`name` AS `autor_name`, `news`.`ex_date`, `news`.`img_ext` FROM `news`
			INNER JOIN `users` ON `users`.`id`=`news`.`autor`
			ORDER BY `date` DESC LIMIT 50");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список новостей!");
			if(mysql_num_rows($res))
			{
				while($nxt=mysql_fetch_assoc($res))
				{
					$wikiparser->title='';
					$tmpl->AddText("<div class='news-block'>");
					$text=$wikiparser->parse(html_entity_decode($nxt['text'],ENT_QUOTES,"UTF-8"));
					if($nxt['img_ext'])
					{
						$miniimg=new ImageProductor($nxt['id'],'n', $nxt['img_ext']);
						$miniimg->SetX(48);
						$miniimg->SetY(48);
						$tmpl->AddText("<img src='".$miniimg->GetURI()."' style='float: left; margin-right: 10px;' alt=''>");
					}
					$tmpl->AddText("<h3>{$wikiparser->title}</h3><p>$text<br><i>{$nxt['date']}, {$nxt['autor_name']}</i></p></div>");
					// <!--<br><a href='/forum.php'>Комментарии: 0</a>-->
				}
			}
			else $tmpl->msg("Новости отсутствуют");
		}
		else if($mode=='read')
		{
			$id=rcv('id');
			$res=mysql_query("SELECT `news`.`id`, `news`.`text`, `news`.`date`, `users`.`name` AS `autor_name`, `news`.`ex_date`, `news`.`img_ext` FROM `news`
			INNER JOIN `users` ON `users`.`id`=`news`.`autor`
			WHERE `news`.`id`='$id'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список новостей!");
			if(mysql_num_rows($res))
			{
				while($nxt=mysql_fetch_assoc($res))
				{
					$wikiparser->title='';
					$text=$wikiparser->parse(html_entity_decode($nxt['text'],ENT_QUOTES,"UTF-8"));
					$tmpl->SetText("<h1 id='page-title'>{$wikiparser->title}</h1><p>$text<br><i>{$nxt['date']}, {$nxt['autor_name']}</i><br></p>");
					// <a href='/forum.php'>Комментарии: 0</a>
				}
			}
			else $tmpl->msg("Новость не найдена!","err");
		}
	}
	else $tmpl->msg("У Вас нет прав на чтение новостей!");
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