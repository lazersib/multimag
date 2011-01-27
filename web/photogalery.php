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
$lim=16;
$gpath="img/galery";

$tmpl->SetText("<h1>Фотогалерея</h1>");
$tmpl->SetTitle("Фотогалерея");

if($mode==""||$mode=='view')
{
	$lt=1;
	$pp="";

	$page=rcv('p');
	if(!$idp) $res=mysql_query("SELECT `photogalery`.`id`, `photogalery`.`uid`, `photogalery`.`comment`, `users`.`name`
	FROM `photogalery`
	LEFT JOIN `users` ON `users`.`id`=`photogalery`.`uid`");
	else $res=mysql_query("SELECT `photogalery`.`id`, `photogalery`.`uid`, `photogalery`.`comment`, `users`.`name` FROM `photogalery`
	LEFT JOIN `users` ON `users`.`id`=`photogalery`.`uid`
	WHERE `photogalery`.`uid`='$idp'");


	$row=mysql_num_rows($res);
	if($row>$lim)
	{
		if($page<1) $page=1;
		$tmpl->AddText("<b>Страницы</b> ");
		if($page>1)
		{
		$i=$page-1;
		$tmpl->AddText(" <a href='photogalery.php?mode=view&p=$i&$pp'>&lt&lt</a> ");
		}
		$cp=$row/$lim;
		for($i=1;$i<($cp+1);$i++)
		{
		if($i==$page) $tmpl->AddText(" $i ");
		else $tmpl->AddText(" <a href='photogalery.php?mode=view&p=$i&$pp'>$i</a> ");
		}
		if($page<$cp)
		{
		$i=$page+1;
		$tmpl->AddText(" <a href='photogalery.php?mode=view&p=$i&$pp'>&gt&gt</a> ");
		}
		$tmpl->AddText("<br>");
		$sl=($page-1)*$lim;

		$res=mysql_query("SELECT `photogalery`.`id`,`photogalery`.`uid`,`photogalery`.`comment`,`users`.`name`
		FROM `photogalery`
		LEFT JOIN `users` ON `users`.`id`=`photogalery`.`uid`
		LIMIT $sl,$lim");

	}


	$tmpl->AddText("");
	while($nxt=mysql_fetch_row($res))
	{
		$tmpl->AddText("<div class='photomini'><a href='photogalery.php?mode=viewall&n=$nxt[0]' title='Увеличить'><img src='photo.php?n=$nxt[0]&x=150' alt='Увеличить'></a></div>");
	}
	$tmpl->AddText("<div class='nofloat'>-</div>");

	$row=mysql_num_rows($res);
	if($row>$lim)
	{
		if($page<1) $page=1;
		$tmpl->AddText("<b>Страницы</b> ");
		if($page>1)
		{
		$i=$page-1;
		$tmpl->AddText(" <a href='photogalery.php?mode=view&p=$i&$pp'>&lt&lt</a> ");
		}
		$cp=$row/$lim;
		for($i=1;$i<($cp+1);$i++)
		{
		if($i==$page) $tmpl->AddText(" $i ");
		else $tmpl->AddText(" <a href='photogalery.php?mode=view&p=$i&$pp'>$i</a> ");
		}
		if($page<$cp)
		{
		$i=$page+1;
		$tmpl->AddText(" <a href='photogalery.php?mode=view&p=$i&$pp'>&gt&gt</a> ");
		}
		$tmpl->AddText("<br>");
		$sl=($page-1)*$lim;

		$res=mysql_query("SELECT `photogalery`.`id`,`photogalery`.`uid`,`photogalery`.`comment`,`users`.`name`
		FROM `photogalery`
		LEFT JOIN `users` ON `users`.`id`=`photogalery`.`uid`
		LIMIT $sl,$lim");

	}
	$rights=getright('photogalery',$uid);
	if($rights['write'])
	{
		$tmpl->AddText("<br><a href='?mode=add'>Добавить</a>");
	}
}
else if($mode=='viewall')
{
	$n=rcv('n');
	$tmpl->AddText("<a href='photo.php?n=$n&x=10240&q=95' title='Показать максимальный размер'><img src='photo.php?n=$n&x=700' alt='Показать максимальный размер'></a><br>
	<b>Открыть с разрешением<sup>*</sup>:</b> <a href='photo.php?n=$n&x=800&y=600'>800x600</a>, <a href='photo.php?n=$n&x=1024&y=768'>1024x768</a>, <a href='photo.php?n=$n&x=1280&y=1024'>1280x1024</a>, <a href='photo.php?n=$n&x=1600&y=1200&q=85'>1600x1200</a>, <a href='photo.php?n=$n&x=100000&q=95'>Максимум</a><br>
	* Примечание: если оригинал изображения имеет разрешение, меньшее, чем запрошено, изображение будет показано в оригинальном размере.");

}
else if($mode=="add")
{
	$rights=getright('photogalery',$uid);
	if($rights['write'])
	{
		$tmpl->AddText("<h3>Добавить фотографию</h3>");
		$tmpl->AddText("При добавлении фотографии не забывайте про <a href='wiki/правила_фотогалереи'>правила</a>!
		Для особо непонятливых - фотогалерея это не место для хранения обоев и других подобных картинок. Да, это красиво, но не попадает в тематику.<br>
		<form method=post action='photogalery.php' enctype='multipart/form-data'>
		<input type=hidden name=mode value='addo'>
		Фотография (JPEG, до 4 Мб, 300*400 - 5000*5000)<br>
		<input type='hidden' name='MAX_FILE_SIZE' value='8000000'>
		<input name='fotofile' type='file'><br>
		Подпись к фото<br>
		<input type=text name=comm><br>
		<input type=submit value='Сохранить'>
		</form>");
	}
	else $tmpl->msg("Недостаточто прав!");
}
else if($mode=="addo")
{
	$tmpl->AddText("<h3>Сохранение фотографии</h3>");
	$comm=rcv('comm');
	$rights=getright('photogalery',$uid);
	if($rights['write'])
	{
	$an=" Фотография не установлена!";
	if(strlen($comm)>5)
	{
	if(is_uploaded_file($_FILES['fotofile']['tmp_name']))
	{
		if($_FILES['fotofile']['size']>4000000)
		$tmpl->msg("Слишком большой файл!$an","err");
		else
		{
		$aa=getimagesize($_FILES['fotofile']['tmp_name']);
		if(!$aa)
		$tmpl->msg("Файл фотографии не является картинкой!$an","err");
		else if(@$aa[2]!=2) $tmpl->msg("Даннная фотография не в формате JPG!$an","err");
		else if((@$aa[0]<300)||(@$aa[1]<400)||(@$aa[0]>5000)||(@$aa[1]>5000)) $tmpl->msg("Некорректное разрешение (должно быть > 640*480 и < 2600*2600)!$an","err");
		else
		{
			$res=mysql_query("INSERT INTO `photogalery` (`uid`,`comment`) VALUES ('$uid','$comm')");
			if($res)
			{
				$fid=mysql_insert_id();
	
				$m_ok=move_uploaded_file($_FILES['fotofile']['tmp_name'], "$gpath/$fid.jpg");
				if($m_ok) $tmpl->msg("Вроде бы фотография добавлена!","ok");
				else $tmpl->msg("Не удалось записать файл!","err");
			}
			else $tmpl->msg("Ошибка базы данных!","err");
		}
		}
	}
	else $tmpl->msg("Не передан файл!$an","err");
	}
	else $tmpl->msg("Необходимо написать комментарий!","err");
	}
	else $tmpl->msg("Недостаточто прав!");
}
else $tmpl->msg("Ты сюда не ходи!","info");

$tmpl->write();
?>

