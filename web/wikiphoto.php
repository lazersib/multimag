<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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


$tmpl->setContent("<h1>Фотографии к статьям</h1>");
$tmpl->setTitle("Фотографии к статьям");

/// Формирует страницы просмотра изображений к статьям
class articlesImagePage {
public function __construct(){
	global $CONFIG;
	$this->lim	= 16;
	$this->gpath	= $CONFIG['site']['location']."/share/var/wikiphoto";
}

/// Получить список доступных изображений
public function outList($page)	{
	global $db, $tmpl;
	settype($page,'int');
	$res=$db->query("SELECT `wikiphoto`.`id`, `wikiphoto`.`uid`, `wikiphoto`.`comment`, `users`.`name`, `wikiphoto`.`ext`
	FROM `wikiphoto` LEFT JOIN `users` ON `users`.`id`=`wikiphoto`.`uid`");
	
	$rows=$res->num_rows;
	if($page<=1)				$page=1;
	if($page>floor($rows/$this->lim))	$page=floor($rows/$this->lim);
	
	$this->pageBar($rows, $this->lim, $page);
	$tmpl->addContent("<div>");
	$res->data_seek(($page-1)*$this->lim);	
	while($nxt=$res->fetch_row()){
		$img=new ImageProductor($nxt[0],'w', $nxt[4]);
		$img->setX(150);
		$img->setY(112);
		$img->setFixAspect(1);
		$img->setNoEnlarge(0);
		$tmpl->addContent("<div class='photomini'><a href='/wikiphoto.php?mode=view&n=$nxt[0]' title='Увеличить'><img src='".$img->GetURI()."' alt='Увеличить'></a></div>");
	}
	$tmpl->addContent("<div class='clear'>&nbsp;</div></div>");
	$this->pageBar($rows, $this->lim, $page);
	if(isAccess('generic_articles','edit',true))
		$tmpl->addContent("<br><a href='?mode=add'>Добавить</a>");
}

/// Показать страницу для заданного изображения
public function viewImage($n) {
	global $tmpl, $db;
	settype($n,'int');
	
	$img_info = $db->selectRow('wikiphoto', $n);
	if(!$img_info)
		throw new NotFoundException('Изображение не найдено в базе');
	
	$img = new ImageProductor($n, 'w', $img_info['ext']);
	
	$img->SetQuality(100);
	$size=$img->getRealImageSize();
	if(!$size)
		throw new NotFoundException('Изображение не найдено на диске');
	$img->setX($size[0]);
	$img->setY($size[1]);
	$full_uri=$img->getURI();
	
	$img=new ImageProductor($n,'w', $img_info['ext']);
	$img->setX(600);
	$img->setY(0);
	$page_uri=$img->getURI();
	
	$tmpl->addContent("<a href='$full_uri' title='Загрузить в максимальном размере и качестве'><img src='$page_uri' alt='Изображение'></a><br>
	<b>Другие размеры:</b> ");

	$aspect=$size[1]/$size[0];
	$sizes=array(320,640,800,1024,1280,1600,1920,2540);
	foreach($sizes as $sx){
		if($sx>$size[0])	break;
		$sy=round($sx*$aspect);
		$img=new ImageProductor($n,'w', $img_info['ext']);
		$img->setX($sx);
		$img->setY($sy);
		$img->SetNoEnlarge(1);
		$tmpl->addContent("<a href='".$img->GetURI()."'>{$sx}x{$sy}</a>, ");
	}
	$tmpl->addContent("<a href='$full_uri'>{$size[0]}x{$size[1]}</a>");
	if(isAccess('generic_articles','edit',true))
		$tmpl->addContent("<br>Код вставки изображения: [[Image:$n|options|alt]]<br>
		<ul class='items'>
		<li>options - набор опций с разделителем |</li>
		<li>alt - текст подписи и содержимое атрибута alt. Не должен быть пуст.</li>
		</ul>
		Опции:<br>
		<ul class='items'>
		<li>frame - в рамке с подписью справа</li>
		<li>left - в рамке с подписью слева</li>
		<li>right - справа без рамки и подписи</li>
		<li>Xpx - изображение с шириной X px</li>
		<li>link:X - задаёт ссылку для изображения (по умолчанию - эта страница). Если параметр пуст - ссылки не будет.</li>
		</ul>");
}

/// Отобразить форму загрузки изображения
public function addImageForm() {
	global $tmpl;
	if(!isAccess('generic_articles','edit'))	throw new AccessException();
	$max_fs=get_max_upload_filesize();
	$max_fs_size=$max_fs;
	if($max_fs_size>1024*1024)	$max_fs_size=($max_fs_size/(1024*1024)).' Мб';
	else if($max_fs_size>1024)	$max_fs_size=($max_fs_size/(1024)).' Кб';
	else				$max_fs_size.='байт';
	$tmpl->addContent("<h3>Добавить изображение</h3>");
	$tmpl->addContent("Изображения в этом разделе используются для последующего отображения в статьях. После добавления Вы получите код для вставки в статью.<br>
	<form method=post action='wikiphoto.php' enctype='multipart/form-data'>
	<input type=hidden name=mode value='save'>
	Изображение (JPEG, PNG, GIF, до $max_fs, 150*150 - 10000*10000)<br>
	<input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'>
	<input name='fotofile' type='file'><br>
	Коментарий к фото:<br>
	<input type='text' name='comm'><br>
	<button type='submit'>Сохранить</button>
	</form>");
}

/// Обработчик отправки формы добавления изображения
public function submitImageForm() {
	global $tmpl, $db;
	if(!isAccess('generic_articles','edit'))
				throw new AccessException("Недостаточно привилегий");
	$comm=request('comm');
	
	$tmpl->addContent("<h3>Добавление изображения</h3>");
	$an="Изображение не сохранено!";
	if(strlen($comm)<1)	throw new Exception("Необходимо написать комментарий. $an");
	
	if(!is_uploaded_file($_FILES['fotofile']['tmp_name']))
				throw new Exception("Не передан файл. $an");
	$aa=getimagesize($_FILES['fotofile']['tmp_name']);
	if(!$aa)	throw new Exception("Файл не является изображением. $an");
	$ext = '';
	switch($aa[2]) {
		case IMAGETYPE_GIF:	$ext='gif'; break;
		case IMAGETYPE_JPEG:	$ext='jpg'; break;
		case IMAGETYPE_PNG:	$ext='png'; break;
		default:		throw new Exception('Формат изображения не поддерживается');
	}
	if(($aa[0]<150)||($aa[1]<150)||($aa[0]>10000)||($aa[1]>10000))
			throw new Exception("Некорректное разрешение (должно быть > 150*150 и < 10000*10000)! $an");

	$uid=(int)$_SESSION['uid'];
	$sql_comm=$db->real_escape_string($comm);
	$res=$db->query("INSERT INTO `wikiphoto` (`uid`, `ext`, `comment`) VALUES ('$uid', '$ext', '$sql_comm')");
	$fid=$db->insert_id;
	$m_ok=move_uploaded_file($_FILES['fotofile']['tmp_name'], $this->gpath."/$fid.$ext");
	if(!$m_ok)	throw new AutoLoggedException("Не удалось сохранить изображение в хранилище");
	$tmpl->msg("Изображение сохранено. Для вставки в статью используйте следующий код:<br>[[Image:$fid|frame|alternate text]]","ok");		
}

/// Отобразить панель страниц
/// @param item_count	Количество элементов в наборе, который делим на страницы
/// @param per_page	Количество элементов на страницу
/// @param cur_page	Номер открываемой страницы
protected function pageBar($item_count, $per_page, $cur_page) {
	global $tmpl;
	if($item_count>$per_page) {
		$pages_count=ceil($item_count/$per_page);
		if($cur_page<1) 		$cur_page=1;
		if($cur_page>$pages_count)	$cur_page=$pages_count;
		$tmpl->addContent("<div class='pagebar'>");
		if($cur_page>1)	{
			$i=$cur_page-1;
			$tmpl->addContent(" <a href='".$this->getPageLink($i)."'>&lt;&lt;</a> ");
		}	else	$tmpl->addContent(" &lt;&lt; ");

		for($i=1;$i<$pages_count+1;$i++){
			if($i==$cur_page) $tmpl->addContent(" $i ");
			else $tmpl->addContent(" <a href='".$this->getPageLink($i)."'>$i</a> ");
		}
		if($cur_page<$pages_count){
			$i=$cur_page+1;
			$tmpl->addContent(" <a href='".$this->getPageLink($i)."'>&gt;&gt;</a> ");
		}	else	$tmpl->addContent(" &gt;&gt; ");
		$tmpl->addContent("</div>");
	}
}

/// Возвращает ссылку на страницу модуля с заданным номером
protected function getPageLink($p) {
	return '/wikiphoto.php?p='.(int)$p;
}
};


try{
	$aip=new articlesImagePage();
	switch($mode)
	{
		case '':	$aip->outList(request('p'));
				break;
		case 'view':	$aip->viewImage(request('n'));
				break;
		case 'add':	$aip->addImageForm();
				break;
		case 'save':	$aip->submitImageForm();
				break;
		default:	throw new NotFoundException('Данные не найдены');
	}
}
catch(mysqli_sql_exception $e)
{
	$db->rollback();
	$id = $tmpl->logger($e->getMessage(), 1);
	$tmpl->addContent("<br><br>");
	$tmpl->msg("Ошибка базы данных, $id","err");
}
catch(Exception $e){
	global $db;
	$db->query("ROLLBACK");
	$tmpl->addContent("<br><br>");
	$tmpl->logger($e->getMessage());
}


$tmpl->write();
?>

