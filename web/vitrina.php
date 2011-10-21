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
include_once("include/doc.core.php");
include_once("include/imgresizer.php");

class Vitrina
{
var $cost_id;
function __construct()
{
	global $tmpl;
	if(@$_SESSION['uid'])	$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='-1'");
	else			$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='1'");
	if(mysql_errno())	throw new MysqlException('Не удалось выбрать цену для пользователя');
	$this->cost_id=		mysql_result($res,0,0);
	if(!$this->cost_id)	$this->cost_id=1;
	$tmpl->SetTitle("Интернет - витрина");
}
/// Проверка и исполнение recode-запроса
function ProbeRecode()
{
	global $tmpl, $CONFIG;
	/// Обрабатывает запросы-ссылки  вида http://example.com/vitrina/ig/5.html
	/// Возвращает false в случае неудачи.
	$arr = explode( '/' , $_SERVER['REQUEST_URI'] );
	if(!is_array($arr))	return false;
	if(count($arr)<4)	return false;
	$block = @explode( '.' , $arr[3]);
	$query = @explode( '.' , $arr[4]);
	if(is_array($block))	$block=$block[0];
	else			$block=$arr[3];
	if(is_array($query))	$query=$query[0];
	else			$query=$arr[4];
	if($arr[2]=='ig')	// Индекс группы
	{
		$this->ViewGroup($query, $block);
		return true;
	}
	else if($arr[2]=='ip')	// Индекс позиции
	{
		$this->ProductCard($block);
		return true;
	}
	else if($arr[2]=='ng')	// Наименование группы
	{
	
	}
	else if($arr[2]=='np') // Наименование позиции
	{
	
	}
	return false;
}
/// Исполнение заданной функции
function ExecMode($mode)
{
	global $tmpl, $CONFIG;
	$p=rcv('p');
	if($mode=='')	// Верхний уровень. Никакая группа не выбрана.
	{
		$tmpl->AddText("<h1 id='page-title'>Витрина</h1>");
		if($CONFIG['site']['vitrina_glstyle']=='item')	$this->GroupList_ItemStyle(0);
		else						$this->GroupList_ImageStyle(0);
	}
	else if($mode=='group')
	{
		$g=rcv('g');
		$page=rcv('p');
		$this->ViewGroup($g, $page);
	}
	else if($mode=='product')
	{
		$p=rcv('p');
		$this->ProductCard($p);
	}
	else if($mode=='basket')
	{
		$this->Basket();
	}
	else if($mode=='korz_add')
	{
		$cnt=rcv('cnt');
		$j=rcv('j');
		if($p)
		{
			@$_SESSION['korz_cnt'][$p]+=$cnt;
			$tmpl->ajax=1;
			if(!$j)
			{
				if(getenv("HTTP_REFERER"))	header('Location: '.getenv("HTTP_REFERER"));
				$tmpl->msg("Товар добавлен в корзину!","info","<a class='urllink' href='/vitrina.php?mode=basket'>Ваша корзина</a>");
			}
			else
			{
				$korz_cnt=count(@$_SESSION['korz_cnt']);
				$sum=0;
				if(@$_SESSION['uid'])
					$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='-1'");
				else
					$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='1'");
				$c_cena_id=@mysql_result($res,0,0);
				if(!$c_cena_id)	$c_cena_id=1;
				if(is_array($_SESSION['korz_cnt']))
				foreach(@$_SESSION['korz_cnt'] as $item => $cnt)
				{
					$res=mysql_query("SELECT `id`, `name`, `cost` FROM `doc_base` WHERE `id`='$item'");
					$nx=mysql_fetch_row($res);
					$cena=GetCostPos($nx[0], 1);
					$sum+=$cena*$cnt;
				}
				$tmpl->AddText("Товаров: $korz_cnt на $sum руб.");
			}
		}
		else	$tmpl->msg("Номер товара не задан!","err","<a class='urllink' href='/vitrina.php?mode=basket'>Ваша корзина</a>");
	}
	else if($mode=='korz_adj')
	{
		$tmpl->ajax=1;
		$cnt=rcv('cnt');
		if($p)
		{
			$_SESSION['korz_cnt'][$p]+=$cnt;
			$tmpl->AddText("Товар добавлен в корзину!<br><a class='urllink' href='/vitrina.php?mode=basket'>Ваша корзина</a>");
			//echo"Товар добавлен в корзину!<br><a class='urllink' href='vitrina.php?mode=basket'>Ваша корзина</a>";
		}
		else	$tmpl->AddText("Номер товара зе задан!");
		//exit();
	}
	else if($mode=='korz_del')
	{
		$cnt=rcv('cnt');
		unset($_SESSION['korz_cnt'][$p]);
		$tmpl->msg("Товар убран из корзины!","info","<a class='urllink' href='/vitrina.php?mode=basket'>Ваша корзина</a>");
	}
	else if($mode=='korz_clear')
	{
		$cnt=rcv('cnt');
		unset($_SESSION['korz_cnt']);
		$tmpl->msg("Корзина очищена!","info","<a class='urllink' href='/vitrina.php'>Вернутья на витрину</a>");
	}
	else if($mode=='korz_setcnt')
	{
		$tmpl->ajax=1;
		foreach($_SESSION['korz_cnt'] as $item => $cnt)
		{
			$ncnt=rcv("cnt$item");
			if($ncnt<=0) unset($_SESSION['korz_cnt'][$item]);
			else $_SESSION['korz_cnt'][$item]=$ncnt;
		}
		if(getenv("HTTP_REFERER"))	header('Location: '.getenv("HTTP_REFERER"));
		else 	header('Location: vitrina.php?mode=basket');
	}
	else if($mode=='buy')		$this->Buy();
	else if($mode=='makebuy')	$this->MakeBuy();
	else if($mode=='print_schet')
	{
		include_once("include/doc.nulltype.php");
		$doc=$_SESSION['zakaz_docnum'];
		if($doc)
		{
			$document=AutoDocument($doc);
			$document->PrintForm($doc, 'schet_pdf');
		}
		else $tmpl->msg("Вы ещё не оформили заказ! Вернитесь и оформите!");
	}
	else if($mode=='comm_add')
	{
		require_once("include/comments.inc.php");
		$p=@$_POST['p'];
		if(!@$_SESSION['uid'])
		{
			$img=rcv('img');
			if( (strtoupper($_SESSION['captcha_keystring'])!=strtoupper($img)) || ($_SESSION['captcha_keystring']=='') )
				throw new Exception("Защитный код введён неверно!");
		}
		$cd=new CommentDispatcher('product',$p);
		$cd->WriteComment(@$_POST['text'], rcv('rate'), rcv('autor_name'), rcv('autor_email'));
				
		$tmpl->msg("Коментарий добавлен!","ok");
	}
	else throw new Exception("Неверная опция. Возможно, вам дали неверную ссылку, или же это ошибка сайта. Во втором случае, сообщите администратору о возникшей проблеме.");
}

// ======== Приватные функции ========================
// -------- Основные функции -------------------------
/// Список групп / подгрупп
protected function ViewGroup($group, $page)
{
	global $tmpl, $CONFIG, $wikiparser;
	settype($group,'int');
	$res=mysql_query("SELECT `name`, `pid`, `desc` FROM `doc_group` WHERE `id`='$group'");
	if(mysql_errno())	throw new MysqlException('Не удалось выбрать информацию о группе');
	$nxt=mysql_fetch_row($res);
	if(!$nxt)		throw new Exception('Группа не найдена! Воспользуйтесь каталогом.');
	if(file_exists("{$CONFIG['site']['var_data_fs']}/category/$group.jpg"))
		$tmpl->AddText("<div class='rfloat'><img src='{$CONFIG['site']['var_data_web']}/category/$group.jpg'></a></div>");
	$tmpl->AddText('<h1>'.$this->GetVitPath($nxt[1])." / $nxt[0]</h1>");
	if($nxt[2])
	{
		$text=$wikiparser->parse(html_entity_decode($nxt[2],ENT_QUOTES,"UTF-8"));
		$tmpl->AddText("<div class='group-description'>$text</div><br>");
	}
	if($CONFIG['site']['vitrina_glstyle']=='item')	$this->GroupList_ItemStyle($group);
	else						$this->GroupList_ImageStyle($group);
	/// TODO: сделать возможность выбора вида отображения списка товаров посетителем
	$this->ProductList($group, $page);
}
/// Список товаров в группе
protected function ProductList($group, $page)
{
	global $tmpl, $CONFIG;
	

	$sql="SELECT `doc_base`.`id`, `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`cost_date`, `doc_base`.`cost`,
	( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base`.`id`) AS `count`,
	`doc_base_dop`.`tranzit`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base`.`proizv`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `doc_units`.`printname` AS `units`
	FROM `doc_base`
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
	LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
	LEFT JOIN `doc_units` ON `doc_base`.`unit`=`doc_units`.`id`
	WHERE `doc_base`.`group`='$group' AND `doc_base`.`hidden`='0'
	ORDER BY `doc_base`.`name` ASC";
	$res=mysql_query($sql);
	if(mysql_errno())	throw new MysqlException("Не удалось получить список товаров!");
	$lim=$CONFIG['site']['vitrina_limit'];
	if($lim==0)	$lim=100;
	$rows=mysql_num_rows($res);	
        if($rows)
        {
  
		$this->PageBar($group, $rows, $lim, $page);
		
		if(($lim<$rows) && $page )	mysql_data_seek($res, $lim*($page-1));
		if($CONFIG['site']['vitrina_plstyle']=='imagelist')		$this->TovList_ImageList($res, $lim);
//		else if($CONFIG['site']['vitrina_plstyle']=='tilelist')		$this->TovList_TileList($res);
		else if($CONFIG['site']['vitrina_plstyle']=='extable')		$this->TovList_ExTable($res, $lim);
		else								$this->TovList_SimpleTable($res, $lim);
		$this->PageBar($group, $rows, $lim, $page);
		$tmpl->AddText("<span style='color:#888'>Серая цена</span> требует уточнения<br>");
	}
}
/// Карточка товара
protected function ProductCard($product)
{
	global $tmpl, $CONFIG, $wikiparser;
	$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`group`, `doc_base`.`cost`,
	`doc_base`.`proizv`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`,
	`doc_base_dop`.`mass`, `doc_base_dop`.`analog`, ( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id`), `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `doc_base_dop_type`.`name` AS `dop_name`, `doc_units`.`name` AS `units`, `doc_group`.`printname` AS `group_printname`
	FROM `doc_base`
	LEFT JOIN `doc_group` ON `doc_base`.`group`=`doc_group`.`id`
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	LEFT JOIN `doc_base_dop_type` ON `doc_base_dop_type`.`id`=`doc_base_dop`.`type`
	LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
	LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
	LEFT JOIN `doc_units` ON `doc_base`.`unit`=`doc_units`.`id`
	WHERE `doc_base`.`id`='$product'
	ORDER BY `doc_base`.`name` ASC LIMIT 1");
	if(mysql_errno())	throw new MysqlException('Не удалось получить карточку товара!');
	$i=0;
	if($nxt=mysql_fetch_array($res))
	{
		$tmpl->AddText("<h1 id='page-title'>{$nxt['name']}</h1>");
		$tmpl->SetTitle("{$nxt['group_printname']} {$nxt['name']}");
		$tmpl->AddText("<table cellpadding='1' cellspacing='0'>
		<tr valign='top'><td rowspan='15' width='150'>");
		if($nxt['img_id']) 
		{
			$miniimg=new ImageProductor($nxt['img_id'],'p', $nxt['img_type']);
			$miniimg->SetX(200);
			$fullimg=new ImageProductor($nxt['img_id'],'p', $nxt['img_type']);
			$img="<a href='".$fullimg->GetURI()."' rel='prettyPhoto[img]'><img src='".$miniimg->GetURI()."' alt='{$nxt['name']}'></a><br>";
		}
		else $img="<img src='/img/no_photo.png' alt='no photo'><br>";
		
		$tmpl->AddText("$img<td><b>Название:</b><td>{$nxt['group_printname']} {$nxt['name']}");
		if($nxt[2])
		{
			$text=$wikiparser->parse(html_entity_decode($nxt[2],ENT_QUOTES,"UTF-8"));
			$tmpl->AddText("<tr><td valign='top'><b>Описание:</b><td>$text");
		}
		if($nxt[14]) $tmpl->AddText("<tr><td><b>Тип:</b><td>$nxt[14]");
		$cena=GetCostPos($nxt[0], $this->cost_id);
		$tmpl->AddText("<tr><td><b>Цена:</b> <td>$cena<br>");
		$tmpl->AddText("<tr><td><b>Единица измерения:</b> <td>$nxt[15]<br>");
		if($nxt[11]) $tmpl->AddText("<tr><td><b>На складе:</b> <td><b>ЕСТЬ</b><br>");
		else $tmpl->AddText("<tr><td><b>На складе:</b> <td>Под заказ<br>");
		if($nxt[6]) $tmpl->AddText("<tr><td><b>Внутренний диаметр:</b> <td>$nxt[6] мм.<br>");
		if($nxt[7]) $tmpl->AddText("<tr><td><b>Внешний диаметр:</b> <td>$nxt[7] мм.<br>");
		if($nxt[8]) $tmpl->AddText("<tr><td><b>Высота:</b> <td>$nxt[8] мм.<br>");
		if($nxt[9]) $tmpl->AddText("<tr><td><b>Масса:</b> <td>$nxt[9] кг.<br>");
		if($nxt[10]) $tmpl->AddText("<tr><td><b>Аналог:</b> <td>$nxt[10]<br>");
		if($nxt[5]) $tmpl->AddText("<tr><td><b>Производитель:</b> <td>$nxt[5]<br>");
		$tmpl->AddText("<tr><td colspan='3'>
		<form action='/vitrina.php'>
		<input type='hidden' name='mode' value='korz_add'>
		<input type='hidden' name='p' value='$product'>
		<div>
		Добавить
		<input type='text' name='cnt' value='1' class='mini'> штук <button type='submit'>В корзину!</button>
		</div>
		</form>
		</td></tr></table>
		<script type='text/javascript' charset='utf-8'>
		$(document).ready(function(){
		$(\"a[rel^='prettyPhoto']\").prettyPhoto({theme:'dark_rounded'});
		});
		</script>");
		$i++;
	}
	if($_SESSION['korz_cnt'])  $tmpl->msg("<a class='selflink' href='/vitrina.php?mode=basket'>Ваша корзина</a>","info");
	if($i==0) 
	{
		$tmpl->AddText("<h1 id='page-title'>Информация о товаре</h1>");
		$tmpl->msg("К сожалению, товар не найден. Возможно, Вы пришли по неверной ссылке.");
	}
}
/// Просмотр корзины
protected function Basket()
{
	global $tmpl, $CONFIG;
	$s='';
	$cc=0;
	$sum=0;
	$exist=0;
	
	if($_SESSION['korz_cnt'])
	foreach($_SESSION['korz_cnt'] as $item => $cnt)
	{
		$res=mysql_query("SELECT `id`, `name`, `cost` FROM `doc_base` WHERE `id`='$item'");
		$nx=mysql_fetch_row($res);
		$cena=GetCostPos($nx[0], $this->cost_id);
		$sm=$cena*$cnt;
		$sum+=$sm;
		$s.="<tr class='lin$cc'><td><a href='/vitrina.php?mode=product&amp;p=$nx[0]'>$nx[1]</a><td>$cena<td>$sm<td><input type=text name='cnt$item' value='$cnt' class='mini'><td><a href='?mode=korz_del&amp;p=$item'>убрать</a>";
		$cc=1-$cc;
		$exist=1;
	}
	if(!$exist) $tmpl->msg("Ваша корзина пуста! Выберите, пожалуйста интересующие Вас товары!","info");
	else
	{
		$tmpl->AddText("
		<h1 id='page-title'>Ваша корзина</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='korz_setcnt'>
		<table width='100%' cellspacing='0' border='0' class='list'>
		<tr class='title'><th>Наименование<th>Цена, руб<th>Сумма, руб<th>Количество, шт<th>Дополнительно</tr>
		$s
		</table>
		<center><button type='submit'>Пересчитать</button></center></form><br>
		<center>Итого: Товаров на сумму <b>$sum</b> рублей<br><a href='/vitrina.php?mode=buy'><b>Оформить заказ!</b></a><br>
		<a href='/vitrina.php?mode=korz_clear'><b>Очистить корзину!</b></a><br>
		</center><br><br>");
		
		$_SESSION['korz_sum']=$sum;
		//if( ($_SESSION['korz_sum']>20000) )	$tmpl->msg("Ваш заказ на сумму более 20'000, вам будет предоставлена удвоенная скидка!");
		//else $tmpl->msg("Цены указаны со скидкой 3%. А при оформлении заказа на сумму более 20'000 рублей предоставляется скидка 6%","info");
	}
}
/// Оформление покупки
protected function Buy()
{
	global $tmpl, $CONFIG;
	$step=rcv('step');
	$tmpl->SetText("<h1 id='page-title'>Оформление заказа</h1>");
	if((!$_SESSION['uid'])&&($step!=1))
	{
		if($step==2)
		{
			$_SESSION['last_page']="/vitrina.php?mode=buy";
			header("Location: /login.php?mode=reg");
		}
		if($step==3)
		{
			$_SESSION['last_page']="/vitrina.php?mode=buy";
			header("Location: /login.php");
		}
		else
		{
			$_SESSION['last_page']="/vitrina.php?mode=buy";
			$this->BuyAuthForm();
		}
	}
	else	$this->BuyMakeForm($step);
}
// -------- Вспомогательные функции ------------------
/// Поэлементный список подгрупп
protected function GroupList_ItemStyle($group)
{
	global $tmpl, $CONFIG;
	
	$res=mysql_query("SELECT `id`, `name` FROM `doc_group` WHERE `hidelevel`='0' AND `pid`='$group' ORDER BY `id`");
	if(mysql_errno())	throw new MysqlException('Не удалось выбрать список групп');
	$tmpl->AddStyle(".vitem { width: 250px; float: left; font-size:	14px; } .vitem:before{content: '\\203A \\0020' ; } hr.clear{border: 0 none; margin: 0;}");
	while($nxt=mysql_fetch_row($res))
	{
		$tmpl->AddText("<div class='vitem'><a href='".$this->GetGroupLink($nxt[0])."'>$nxt[1]</a></div>");
	}
	$tmpl->AddText("<hr class='clear'>");
}
/// Список групп с изображениями
protected function GroupList_ImageStyle($group)
{
	global $tmpl, $CONFIG;
	
	$res=mysql_query("SELECT * FROM `doc_group` WHERE `hidelevel`='0' AND `pid`='$group'  ORDER BY `id`");
	if(mysql_errno())	throw new MysqlException('Не удалось выбрать список групп');
	$tmpl->AddStyle(".vitem { width: 360px; float: left; font-size:	14px; margin: 10px;} .vitem img {float: left; padding-right: 8px;} hr.clear{border: 0 none; margin: 0;}");
	while($nxt=mysql_fetch_row($res))
	{
		$link=$this->GetGroupLink($nxt[0]);
		$tmpl->AddText("<div class='vitem'><a href='$link'>");
		if(file_exists("{$CONFIG['site']['var_data_fs']}/category/$nxt[0].jpg"))
				$tmpl->AddText("<img src='{$CONFIG['site']['var_data_web']}/category/$nxt[0].jpg' alt='$nxt[1]'>");
		else		$tmpl->AddText("<img src='/img/no_photo.png' alt='Изображение не доступно'>");
		$tmpl->AddText("</a><div><a href='$link'><b>$nxt[1]</b><br>");
		if($nxt[2])
		{
			$desc=split('\.',$nxt[2],2);
			if($desc[0])	$tmpl->AddText($desc[0]);
			else		$tmpl->AddText($nxt[2]);		
		}
		$tmpl->AddText("</a></div></div>");
	}
	$tmpl->AddText("<hr class='clear'>");
}
/// Простая таблица товаров
protected function TovList_SimpleTable($res, $lim)
{
	global $tmpl, $CONFIG;
	$tmpl->AddText("<table width='100%' cellspacing='0' border='0' class='list'><tr class='title'><th>Наименование<th>Производитель<th>Наличие<th>Розничная цена<th>Купить</tr>");
	$cc=$i=0;
	$cl="lin0";
	while($nxt=mysql_fetch_assoc($res))
	{
		$nal=$this->GetCountInfo($nxt['count'], $nxt['tranzit']);
		$link=$this->GetProductLink($nxt['id'], $nxt['name']);	
		$cce='';
		$dcc=strtotime($nxt['cost_date']);
		if($dcc<(time()-60*60*24*30*6)) $cce="style='color:#888'";
		$cost=GetCostPos($nxt['id'], $this->cost_id);	
		$tmpl->AddText("<tr class='lin$cc'><td><a href='$link'>{$nxt['name']}</a>
		<td>{$nxt['proizv']}<td>$nal<td $cce>$cost
		<td><a href='/vitrina.php?mode=korz_add&amp;p={$nxt['id']}&amp;cnt=1' onclick=\"return ShowPopupWin('/vitrina.php?mode=korz_adj&amp;p={$nxt['id']}&amp;cnt=1','popwin');\" rel='nofollow'>
		<img src='/img/i_korz.png' alt='В корзину!'></a></tr>");
		$sf++;
		$i++;
		$cc=1-$cc;
		if($i>=$lim)	break;
	}
	$tmpl->AddText("</table>");
}
/// Список товаров в виде изображений
protected function TovList_ImageList($res, $lim)
{
	global $tmpl, $CONFIG;
	$cc=$i=0;
	$cl="lin0";
	
	$tmpl->AddStyle(".pitem	{
		float:			left;
		width:			330px;
		height:			180px;
		border:			1px solid #ccc;
		background:		#fafafa;
		margin:			10px;
		padding:		5px;
		border-radius:		10px;
		-moz-border-radius:	10px;
	}");
	
	while($nxt=mysql_fetch_assoc($res))
	{
		$nal=$this->GetCountInfo($nxt['count'], $nxt['tranzit']);
		$link=$this->GetProductLink($nxt['id'], $nxt['name']);		
		$cce='';
		$dcc=strtotime($nxt['cost_date']);
		if($dcc<(time()-60*60*24*30*6)) $cce="style='color:#888'";
		$cost=GetCostPos($nxt['id'], $this->cost_id);		
		if($nxt['img_id']) 
		{
			$miniimg=new ImageProductor($nxt['img_id'],'p', $nxt['img_type']);
			$miniimg->SetX(135);
			$miniimg->SetY(180);
			$img="<img src='".$miniimg->GetURI()."' style='float: left; margin-right: 10px;' alt='{$nxt['name']}'>";
		}
		else $img="<img src='/no_photo.png' alt='no photo' style='float: left; margin-right: 10px;' alt='no photo'>";
		$desc=$nxt['desc'];
		if(strpos($desc,'.')!==false)
		{
			$desc=explode('.',$desc,2);
			$desc=$desc[0];
		}
		
		$tmpl->AddText("<div class='pitem'>
		<a href='$link'>$img</a>
		{$nxt['name']}<br>
		<b>Цена:</b> $cost руб. / {$nxt['units']}<br>
		<b>Кол-во:</b> $nal<br>
		<a href='/vitrina.php?mode=korz_add&amp;p={$nxt['id']}&amp;cnt=1' onclick=\"return ShowPopupWin('/vitrina.php?mode=korz_adj&amp;p={$nxt['id']}&amp;cnt=1','popwin');\" rel='nowollow'>В корзину!</a>
		</div>");
		
		$sf++;
		$i++;
		$cc=1-$cc;
		if($i>=$lim)	break;
	}
	$tmpl->AddText("<br clear='all'>");
}
/// Подробная таблица товаров
protected function TovList_ExTable($res, $lim)
{
	global $tmpl, $CONFIG, $c_cena_id;
	$tmpl->AddText("<table width='100%' cellspacing='0' border='0' class='list'><tr class='title'><th>Наименование<th>Производитель<th>Наличие<th>Розничная цена <th>d, мм<th>D, мм<th>B, мм<th>m, кг<th>Купить</tr>");
	$cc=0;
	$cl="lin0";
	while($nxt=mysql_fetch_array($res))
	{
		$nal=$this->GetCountInfo($nxt['count'], $nxt['tranzit']);
		$link=$this->GetProductLink($nxt['id'], $nxt['name']);	
		$cce='';
		$dcc=strtotime($nxt['cost_date']);
		if($dcc<(time()-60*60*24*30*6)) $cce="style='color:#888'";
		$cost=GetCostPos($nxt['id'], $this->cost_id);	
		$tmpl->AddText("<tr class='lin$cc'><td><a href='$link'>{$nxt['name']}</a><td>{$nxt['proizv']}<td>$nal
		<td $cce>$cost<td>{$nxt['d_int']}<td>{$nxt['d_ext']}<td>{$nxt['size']}<td>{$nxt['mass']}<td>
		<a href='/vitrina.php?mode=korz_add&amp;p={$nxt['id']}&amp;cnt=1' onclick=\"return ShowPopupWin('/vitrina.php?mode=korz_adj&amp;p={$nxt['id']}&amp;cnt=1','popwin');\" rel='nofollow'><img src='/img/i_korz.png' alt='В корзину!'></a>");
		$sf++;
		$cc=1-$cc;
	}
	$tmpl->AddText("</table>");
}
/// Форма аутентификации при покупке. Выдаётся, только если посетитель не вошёл на сайт
protected function BuyAuthForm()
{
	global $tmpl, $CONFIG;
	$tmpl->SetTitle("Оформление зкакза");
	$tmpl->AddText("<p id='text'>Для использования всех возможностей этого сайта необходимо пройти процедуру регистрации. Регистрация не сложная, и займёт всего несколько минут.
	Кроме того, все зарегистрированные пользователи получают возможность приобретать товары по специальным ценам.</p>
	<form action='' method='post'>
	<input type='hidden' name='mode' value='buy'>
	<label><input type='radio' name='step' value='1'>Оформить заказ без регистрации</label><br>
	<label><input type='radio' name='step' value='2'>Зарегистрироваться как новый покупатель</label><br>
	<label><input type='radio' name='step' value='3'>Войти как уже зарегистрированный покупатель</label><br>
	<button type='submit'>Далее</button>
	</form>");
}
/// Заключительная форма оформления покупки
protected function BuyMakeForm()
{
	global $tmpl, $CONFIG;
	$users_data=array();
	if($_SESSION['uid'])
	{
		$res=mysql_query("SELECT `name`, `email`, `date_reg`, `subscribe`, `rname`, `tel`, `adres` FROM `users` WHERE `id`='{$_SESSION['uid']}'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить основные данные пользователя!");
		$user_data=mysql_fetch_assoc($res);
		$rr=mysql_query("SELECT `param`,`value` FROM `users_data` WHERE `uid`='".$_SESSION['uid']."'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить дополнительные данные пользователя!");
		while($nn=mysql_fetch_row($rr))
		{
			$user_dopdata["$nn[0]"]=$nn[1];
		}
		$str='Товар будет зарезервирован для Вас на 3 рабочих дня.';
		//if( ($_SESSION['korz_sum']>20000) && $uid )	$tmpl->msg("Ваш заказ на сумму более 20'000, вам будет предоставлена удвоенная скидка!");
	}
	else
	{
		$str='<b>Для незарегистрированных пользователей наличие товара на складе не гарантируется.</b>';
		$email_field="e-mail:<br>
		<input type='text' name='email' value=''><br>
		Необходимо заполнить телефон или e-mail<br>";
	}
	
	if(rcv('cwarn'))	$tmpl->msg("Необходимо заполнить e-mail или контактный телефон!","err");
	
	$tmpl->AddText("
	<h4>Для оформления заказа требуется следующая информация</h4>
	<form action='/vitrina.php' method='post'>
	<input type='hidden' name='mode' value='makebuy'>
	<div>
	Фамилия И.О.<br>
	<input type='text' name='rname' value='".$user_data['rname']."'><br>
	Телефон:<br>
	<input type='text' name='tel' value='".$user_data['tel']."'><br>
	$email_field
	
	Способ оплаты:<br>
	<!--
	<label><input type='radio' name='soplat' value='wmr' disabled >Webmoney WMR.
	<b>Cамый быстрый</b> способ получить Ваш заказ. <b>Заказ поступит в обработку сразу</b> после оплаты</b></label><br> -->
	<label><input type='radio' name='soplat' value='bn' checked>Безналичный перевод.
	<b>Дольше</b> предыдущего - обработка заказа начнётся <b>только после поступления денег</b> на наш счёт (занимает 1-2 дня)</label><br>
	<label><input type='radio' name='soplat' value='n'>Наличный расчет.
	<b>Только самовывоз</b>, расчет при отгрузке. $str</label>
	<br>Адрес доставки:<br>
	<textarea name='adres' rows='5' cols='15'>".$user_data['adres']."</textarea><br>
	Другая информация:<br>
	<textarea name='dop' rows='5' cols='15'>".$user_dopdata['dop_info']."</textarea><br>
	<button type='submit'>Оформить заказ</button>
	</div>
	</form>");
}
/// Сделать покупку
protected function MakeBuy()
{
	global $tmpl, $CONFIG, $uid, $xmppclient;
	$soplat=rcv('soplat');
	$rname=rcv('rname');
	$tel=rcv('tel');
	$adres=rcv('adres');
	$email=rcv('email');
	$dop=rcv('dop');
	if($_SESSION['uid'])
	{
		mysql_query("UPDATE `users` SET `rname`='$rname', `tel`='$tel', `adres`='$adres' WHERE `id`='$uid'");
		if(mysql_errno())	throw new MysqlException("Не удалось обновить основные данные пользователя!");
		mysql_query("REPLACE `users_data` (`uid`, `param`, `value`) VALUES ('$uid', 'dop_info', '$dop') ");
		if(mysql_errno())	throw new MysqlException("Не удалось обновить дополнительные данные пользователя!");
// 		if( $_SESSION['korz_sum']>20000)
// 		{
// 			$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='-2'");
// 			$this->cost_id=mysql_result($res,0,0);
// 			if(!$this->cost_id)	$this->cost_id=1;
// 		}
	}
	else if(!$tel && !$email)
	{
		header("Location: /vitrina.php?mode=buy&step=1&cwarn=1");
		return;
	}
	
	
	
	if($_SESSION['korz_cnt'])
	{
		$subtype="site";
		$agent=1;
		
		//if($_SESSION['uid'])	$agent=$_SESSION['uid'];	// ?????????????????????????/
		$tm=time();
		$altnum=GetNextAltNum(3,$subtype);
		$ip=getenv("REMOTE_ADDR");
		$comm="Организация: $org, телефон: $tel, контактное лицо: $kont, IP: $ip, адрес доставки: $adres<br>Другая информация: $dop";
		if(!$uid)	$comm="e-mail: $email<br>".$comm;
		$res=mysql_query("SELECT `num` FROM `doc_kassa` WHERE `ids`='bank' AND `firm_id`='{$CONFIG['site']['default_firm']}'");
		if(mysql_errno())	throw new MysqlException("Не удалось определить банк");
		if(mysql_num_rows($res)<1)	throw new Exception("Не найден банк выбранной организации");
		$bank=mysql_result($res,0,0);
		
		$res=mysql_query("INSERT INTO doc_list (`type`,`agent`,`date`,`sklad`,`user`,`nds`,`altnum`,`subtype`,`comment`,`firm_id`,`bank`) 
		VALUES ('3','$agent','$tm','1','$uid','1','$altnum','$subtype','$comm','{$CONFIG['site']['default_firm']}','$bank')");

		if(mysql_errno())	throw new MysqlException("Не удалось создать документ заявки");
		$doc=mysql_insert_id();
		mysql_query("REPLACE INTO `doc_dopdata` (`doc`, `param`, `value`) VALUES ('$doc', 'cena', '{$this->cost_id}')");
		if(mysql_errno())	throw new MysqlException("Не удалось установить цену документа");
		$zakaz_items='';
		foreach($_SESSION['korz_cnt'] as $item => $cnt)
		{
			$cena=GetCostPos($item, $this->cost_id);
			mysql_query("INSERT INTO `doc_list_pos` (`doc`,`tovar`,`cnt`,`cost`) VALUES ('$doc','$item','$cnt','$cena')");
			if(mysql_errno())	throw new MysqlException("Не удалось добавить товар в заказ");
			$res=mysql_query("SELECT `doc_base`.`id`, `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`vc`, `doc_base`.`cost` FROM `doc_base`
			LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
			WHERE `doc_base`.`id`='$item'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить информацию о товаре");
			$tov_info=mysql_fetch_row($res);
			$zakaz_items.="$tov_info[1] $tov_info[2]/$tov_info[3] ($tov_info[4]), $cnt шт. - $cena руб.\n";
			$admin_items.="$tov_info[1] $tov_info[2]/$tov_info[3] ($tov_info[4]), $cnt шт. - $cena руб. (базовая - $tov_info[5]р.)\n";
		}
		$zakaz_sum=DocSumUpdate($doc);
		$_SESSION['zakaz_docnum']=$doc;
		
		$text="На сайте {$CONFIG['site']['name']} оформлен новый заказ.\n";
		$text.="Посмотреть можно по ссылке: http://{$CONFIG['site']['name']}/doc.php?mode=body&doc=$doc\nIP отправителя: ".getenv("REMOTE_ADDR")."\nSESSION ID:".session_id();
		if(@$_SESSION['name']) $text.="\nLogin отправителя: ".$_SESSION['name'];
		$text.="----------------------------------\n".$admin_items;
		
		
		if($CONFIG['site']['doc_adm_jid'])
		{
			try 
			{
				$xmppclient->connect();
				$xmppclient->processUntil('session_start');
				$xmppclient->presence();
				$xmppclient->message($CONFIG['site']['doc_adm_jid'], $text);
				$xmppclient->disconnect();
			} 
			catch(XMPPHP_Exception $e) 
			{
				$tmpl->logger("Невозможно отправить сообщение XMPP!","err");
			}
		}
		if($CONFIG['site']['doc_adm_email'])
			mailto($CONFIG['site']['doc_adm_email'],"Message from {$CONFIG['site']['name']}", $text);
		
		if($_SESSION['uid'])
		{
			$res=mysql_query("SELECT `name`, `email`, `date_reg`, `subscribe`, `rname`, `tel`, `adres` FROM `users` WHERE `id`='{$_SESSION['uid']}'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить основные данные пользователя!");
			$user_data=mysql_fetch_assoc($res);
			$user_msg="Доброго времени суток, {$user_data['name']}!\nНа сайте {$CONFIG['site']['name']} на Ваше имя оформлен заказ на сумму $zakaz_sum рублей\nЗаказано:\n";
			$email=$user_data['email'];
		}
		else $user_msg="Доброго времени суток, $rname!\nКто-то (возможно, вы) при оформлении заказа на сайте {$CONFIG['site']['name']}, указал Ваш адрес электронной почты.\nЕсли Вы не оформляли заказ, просто проигнорируйте это письмо.\n Номер заказа: $doc/$altnum\nЗаказ на сумму $zakaz_sum рублей\nЗаказано:\n";
		$user_msg.="--------------------------------------\n$zakaz_items\n--------------------------------------\n";
		$user_msg.="\n\n\nСообщение отправлено роботом. Не отвечайте на это письмо.";
		
		if($email)
			mailto($email,"Message from {$CONFIG['site']['name']}", $user_msg);
		
		
		$tmpl->AddText("<h1 id='page-title'>Заказ оформлен</h1>");
		if($soplat=='bn')
		{
			$tmpl->msg("Ваш заказ оформлен! Номер заказа: $doc/$altnum. Теперь Вам необходимо <a href='/vitrina.php?mode=print_schet'>выписать счёт</a>, и оплатить его. После оплаты счёта Ваш заказ поступит в обработку.");
			$tmpl->AddText("<a href='?mode=print_schet'>выписать счёт</a>");
		}
		else $tmpl->msg("Ваш заказ оформлен! Номер заказа: $doc/$altnum. Запомните или запишите его. Вам перезвонят в ближайшее время для уточнения деталей!");
	}
	else $tmpl->msg("Ваша корзина пуста! Вы не можете оформить заказ! Быть может, Вы его уже оформили?","err");

}

/// Отобразить панель страниц
protected function PageBar($group, $item_count, $per_page, $cur_page)
{
	global $tmpl;
	if($item_count>$per_page)
	{
		$pages_count=ceil($item_count/$per_page);
		if($cur_page<1) 		$cur_page=1;
		if($cur_page>$pages_count)	$cur_page=$pages_count;
		$tmpl->AddText("<div class='pagebar'>");
		if($cur_page>1)
		{
			$i=$cur_page-1;
			$tmpl->AddText(" <a href='".$this->GetGroupLink($group, $i)."'>&lt;&lt;</a> ");
		}	else	$tmpl->AddText(" &lt;&lt; ");
		
		for($i=1;$i<$pages_count+1;$i++)
		{
			if($i==$cur_page) $tmpl->AddText(" $i ");
			else $tmpl->AddText(" <a href='".$this->GetGroupLink($group, $i)."'>$i</a> ");
		}
		if($cur_page<$pages_count)
		{
			$i=$cur_page+1;
			$tmpl->AddText(" <a href='".$this->GetGroupLink($group, $i)."'>&gt&gt</a> ");
		}	else	$tmpl->AddText(" &gt&gt ");
		$tmpl->AddText("</div>");
	}
}
/// *Хлебные крошки* витрины
protected function GetVitPath($group_id)
{
	$res=mysql_query("SELECT `id`, `name`, `pid` FROM `doc_group` WHERE `id`='$group_id'");
	if(mysql_errno())	throw new MysqlException("Не удалось выбрать группу при формировании пути!");
	$nxt=mysql_fetch_row($res);
	if(!$nxt)	return "<a href='/vitrina.php'>Витрина</a>";
	return $this->GetVitPath($nxt[2])." / <a href='".$this->GetGroupLink($nxt[0])."'>$nxt[1]</a>";
}
/// Получить ссылку на группу с заданным ID
protected function GetGroupLink($group, $page=0)
{
	global $CONFIG;
	if($CONFIG['site']['recode_enable'])	return "/vitrina/ig/$page/$group.html";
	else					return "/vitrina.php?mode=group&amp;g=$group".($page?"&amp;p=$page":'');
}
/// Получить ссылку на товар с заданным ID
protected function GetProductLink($product, $name)
{
	global $CONFIG;
	if($CONFIG['site']['recode_enable'])	return "/vitrina/ip/$product.html";
	else					return "/vitrina.php?mode=product&amp;p=$product";
}
/// Получить информации о количестве товара. Формат информации - в конфигурационном файле 
protected function GetCountInfo($count, $tranzit)
{
	global $CONFIG;	
	if(!isset($CONFIG['site']['vitrina_pcnt_limit']))	$CONFIG['site']['vitrina_pcnt_limit']	= array(1,10,100);
	if($CONFIG['site']['vitrina_pcnt']==1)
	{
		if($count<=0)
		{
			if($tranzit) return 'в пути';
			else	return 'уточняйте';
		}
		else if($count<=$CONFIG['site']['vitrina_pcnt_limit'][0]) return '*';
		else if($count<=$CONFIG['site']['vitrina_pcnt_limit'][1]) return '**';
		else if($count<=$CONFIG['site']['vitrina_pcnt_limit'][2]) return '***';
		else return '****';
	}
	else if($CONFIG['site']['vitrina_pcnt']==2)
	{
		if($count<=0)
		{
			if($tranzit) return 'в пути';
			else	return 'уточняйте';
		}
		else if($count<=$CONFIG['site']['vitrina_pcnt_limit'][0]) return 'мало';
		else if($count<=$CONFIG['site']['vitrina_pcnt_limit'][1]) return 'есть';
		else if($count<=$CONFIG['site']['vitrina_pcnt_limit'][2]) return 'много';
		else return 'оч.много';
	}
	else	return round($count).($tranzit?('/'.$tranzit):'');
}

};

// if(($mode=='')&&($gr==''))
// {
// 	$arr = explode( '/' , $_SERVER['REQUEST_URI'] );
// 	if($arr[2]=='i')
// 	{
// 		$arr = explode( '.' , $arr[3] );
// 		$pos=urldecode(urldecode($arr[0]));
// 		$pos=explode(":",$pos,2);
// 		$proizv=$pos[1];
// 		$pos=$pos[0];
// 		if($proizv) $proizv="AND `proizv` LIKE '$proizv'";
// 		$res=mysql_query("SELECT `id` FROM `doc_base` WHERE `name`  LIKE '$pos' $proizv");
// 		@$pos=mysql_result($res,0,0);
// 		if($pos)
// 		{
// 			$p=$pos;
// 			$mode='info';		
// 		}
// 		else $tmpl->msg("Выбранное наименование не найдено! Попробуйте поискать по каталогу!","info");
// 	}
// }

try
{
	$tmpl->SetTitle("Интернет - витрина");
	
	if(file_exists( $CONFIG['site']['location'].'/skins/'.$CONFIG['site']['skin'].'/vitrina.tpl.php' ) )
		include_once($CONFIG['site']['location'].'/skins/'.$CONFIG['site']['skin'].'/vitrina.tpl.php');
	if(!isset($vitrina))	$vitrina=new Vitrina();
	
	if(! $vitrina->ProbeRecode() )
	$vitrina->ExecMode($mode);
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


