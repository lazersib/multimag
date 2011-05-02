<?php
include_once("core.php");

$tmpl->SetText("<h1 id='page-title'>Новости сайта</h1>");

$tmpl->AddText("");
$rights=getright('news',$uid);
$tmpl->SetTitle("Новости сайта - ".$CONFIG['site']['name']);
if($rights['write'])
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
		<label><input type='radio' name='type' value='action'>Акция<br><small>Отображается в ленте новостей и списке акций. Дата - дата окончания акции</small></label><br>
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
		$text=rcv('text');
		$type=rcv('type');
		$ex_date=rcv('ex_date');
		$ext='';

		$_SESSION['last_time']=0;
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
	
		SendSubscribe("Новости сайта",$msg);
		mysql_query("COMMIT");
		$tmpl->msg("Новость добавлена!","ok");
	
	}
}

if($rights['read'])
{
	if($mode=='')
	{
		$res=mysql_query("SELECT `news`.`id`,`news`.`text`,`news`.`date`,`users`.`name` FROM `news`
		INNER JOIN `users` ON `users`.`id`=`news`.`autor`
		ORDER BY `date` DESC LIMIT 50");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список новостей!");
		if(mysql_num_rows($res))
		{
			while($nxt=mysql_fetch_row($res))
			{
				$text=$wikiparser->parse(html_entity_decode($nxt[1],ENT_QUOTES,"UTF-8"));
				$text_s=split("\.",$text,2);
				$tmpl->AddText("<p><b>$text_s[0]".".</b>$text_s[1]<br><i>$nxt[2], $nxt[3]</i> <a href='forum.php'>Комментарии: 0</a></p>");
			}
		}
		else $tmpl->msg("Новости отсутствуют");
	}
}
else $tmpl->msg("У Вас нет прав на чтение новостей!");


$tmpl->write();
?>