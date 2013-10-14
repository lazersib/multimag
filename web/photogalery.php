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
$lim=16;
$gpath="img/galery";

try
{

$tmpl->setContent("<h1>Фотогалерея</h1>");
$tmpl->setTitle("Фотогалерея");

if($mode==""||$mode=='view')
{
	$lt=1;
	$pp="";

	$page=rcvint('p');
	$res=$db->query("SELECT `photogalery`.`id`, `photogalery`.`uid`, `photogalery`.`comment`, `users`.`name`
	FROM `photogalery`
	LEFT JOIN `users` ON `users`.`id`=`photogalery`.`uid`");
	$row=$res->num_rows;
	if($row>$lim)
	{
		if($page<1) $page=1;
		$tmpl->addContent("<b>Страницы</b> ");
		if($page>1)
		{
			$i=$page-1;
			$tmpl->addContent(" <a href='/photogalery.php?mode=view&amp;p=$i&amp;$pp'>&lt&lt</a> ");
		}
		$cp=$row/$lim;
		for($i=1;$i<($cp+1);$i++)
		{
			if($i==$page) $tmpl->addContent(" $i ");
			else $tmpl->addContent(" <a href='/photogalery.php?mode=view&amp;p=$i&amp;$pp'>$i</a> ");
		}
		if($page<$cp)
		{
			$i=$page+1;
			$tmpl->addContent(" <a href='/photogalery.php?mode=view&amp;p=$i&amp;$pp'>&gt&gt</a> ");
		}
		$tmpl->addContent("<br>");
		$sl=($page-1)*$lim;

		$res=$db->query("SELECT `photogalery`.`id`,`photogalery`.`uid`,`photogalery`.`comment`,`users`.`name`
		FROM `photogalery`
		LEFT JOIN `users` ON `users`.`id`=`photogalery`.`uid`
		LIMIT $sl,$lim");
	}
	while($nxt=$res->fetch_row())
	{
		$tmpl->addContent("<div class='photomini'><a href='/photogalery.php?mode=viewall&amp;n=$nxt[0]' title='Увеличить'><img src='/photo.php?n=$nxt[0]&amp;x=150' alt='Увеличить'></a></div>");
	}
	$tmpl->addContent("<div class='nofloat'>-</div>");

	$row=$res->num_rows;
	if($row>$lim)
	{
		if($page<1) $page=1;
		$tmpl->addContent("<b>Страницы</b> ");
		if($page>1)
		{
			$i=$page-1;
			$tmpl->addContent(" <a href='/photogalery.php?mode=view&amp;p=$i&amp;$pp'>&lt&lt</a> ");
		}
		$cp=$row/$lim;
		for($i=1;$i<($cp+1);$i++)
		{
			if($i==$page) $tmpl->addContent(" $i ");
			else $tmpl->addContent(" <a href='/photogalery.php?mode=view&amp;p=$i&amp;$pp'>$i</a> ");
		}
		if($page<$cp)
		{
			$i=$page+1;
			$tmpl->addContent(" <a href='/photogalery.php?mode=view&amp;p=$i&amp;$pp'>&gt&gt</a> ");
		}
		$tmpl->addContent("<br>");
		$sl=($page-1)*$lim;

		$res=$db->query("SELECT `photogalery`.`id`,`photogalery`.`uid`,`photogalery`.`comment`,`users`.`name`
		FROM `photogalery`
		LEFT JOIN `users` ON `users`.`id`=`photogalery`.`uid`
		LIMIT $sl,$lim");
	}
	if(isAccess('generic_galery','edit'))
		$tmpl->addContent("<br><a href='?mode=add'>Добавить</a>");
}
else if($mode=='viewall')
{
	$n=rcvint('n');
	$tmpl->addContent("<a href='/photo.php?n=$n&amp;x=10240&amp;q=95' title='Показать максимальный размер'><img src='/photo.php?n=$n&amp;x=700' alt='Показать максимальный размер'></a><br>
	<b>Открыть с разрешением<sup>*</sup>:</b> <a href='/photo.php?n=$n&amp;x=800&amp;y=600'>800x600</a>, <a href='/photo.php?n=$n&amp;x=1024&amp;y=768'>1024x768</a>, <a href='/photo.php?n=$n&amp;x=1280&amp;y=1024'>1280x1024</a>, <a href='/photo.php?n=$n&amp;x=1600&amp;y=1200&amp;q=85'>1600x1200</a>, <a href='/photo.php?n=$n&amp;x=100000&amp;q=95'>Максимум</a><br>
	* Примечание: если оригинал изображения имеет разрешение, меньшее, чем запрошено, изображение будет показано в оригинальном размере.");
}
else if($mode=="add")
{
	if(!isAccess('generic_galery','create'))	throw new AccessException();
	
	$max_fs=get_max_upload_filesize();
	$max_img_size=min(16*1024*1204,$max_fs);
	if($max_img_size>1024*1024)	$max_img_size=($max_img_size/(1024*1024)).' Мб';
	else if($max_img_size>1024)	$max_img_size=($max_img_size/(1024)).' Кб';
	else				$max_img_size.='байт';

	$tmpl->addContent("<h3>Добавить фотографию</h3>");
	$tmpl->addContent("При добавлении фотографии не забывайте про <a href='wiki/правила_фотогалереи'>правила</a>!
	Для особо непонятливых - фотогалерея это не место для хранения обоев и других подобных картинок. Да, это красиво, но не попадает в тематику.<br>
	<form method=post action='photogalery.php' enctype='multipart/form-data'>
	<input type=hidden name=mode value='addo'>
	Фотография (JPEG, до $max_img_size, 300*400 - 10000*10000)<br>
	<input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'>
	<input name='fotofile' type='file'><br>
	Подпись к фото<br>
	<input type=text name=comm><br>
	<input type=submit value='Сохранить'>
	</form>");
}
else if($mode=="addo")
{
	$max_fs=get_max_upload_filesize();
	$max_img_size=min(16*1024*1204,$max_fs);
	$tmpl->addContent("<h3>Сохранение фотографии</h3>");
	$comm=request('comm');
	if(!isAccess('generic_galery','create'))		throw new AccessException();
	
	$an=" Фотография не установлена!";
	if(strlen($comm)<6)					throw new Exception("Необходим более подробный комментарий");
	
	if(!is_uploaded_file($_FILES['fotofile']['tmp_name']))	throw new Exception("Не передан файл!$an");
	
	if($_FILES['fotofile']['size']>$max_img_size)		throw new Exception("Слишком большой файл!$an");

	$aa=getimagesize($_FILES['fotofile']['tmp_name']);
	if(!$aa)						throw new Exception("Файл фотографии не является картинкой!$an");
	if(@$aa[2]!=IMAGETYPE_JPEG) 				throw new Exception("Даннная фотография не в формате JPG!$an");
	if((@$aa[0]<300)||(@$aa[1]<400)||(@$aa[0]>10000)||(@$aa[1]>10000))
								throw new Exception("Некорректное разрешение (должно быть > 300*400 и < 10000*10000)!$an");
	$sql_comm=$db->real_escape_string($comm);
	$uid=round($_SESSION['uid']);
	$db->query("START TRANSACTION");
	$res=$db->query("INSERT INTO `photogalery` (`uid`,`comment`) VALUES ('$uid','$sql_comm')");
	$fid=$db->insert_id;

	$m_ok=move_uploaded_file($_FILES['fotofile']['tmp_name'], "$gpath/$fid.jpg");
	if(!$m_ok)						throw new Exception("Не удалось записать файл!");
	$db->commit();
	$tmpl->msg("Фотография добавлена!","ok");
}
else throw new Exception("Неверный параметр");

$tmpl->write();

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

?>

