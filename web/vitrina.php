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
include_once("include/doc.core.php");
include_once("include/imgresizer.php");

/// Класс витрины интернет-магазина
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
	else if($arr[2]=='block')// Заданный блок
	{
		$this->ViewBlock($block);
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

///Исполнение заданной функции
function ExecMode($mode)
{
	global $tmpl, $CONFIG;
	$p=intval(@$_REQUEST['p']);
	$g=intval(@$_REQUEST['g']);
	if($mode=='')	// Верхний уровень. Никакая группа не выбрана.
	{
		$this->TopGroup();
	}
	else if($mode=='group')
	{
		$this->ViewGroup($g, $p);
	}
	else if($mode=='product')
	{
		$this->ProductCard($p);
	}
	else if($mode=='basket')
	{
		$this->Basket();
	}
	else if($mode=='block')
	{
		$this->ViewBlock($_REQUEST['type']);
	}
	else if($mode=='korz_add')
	{
		$cnt=intval(@$_REQUEST['cnt']);
		if($p)
		{
			@$_SESSION['basket']['cnt'][$p]+=$cnt;
			$tmpl->ajax=1;
			if(isset($_REQUEST['j']))
			{
				$korz_cnt=count(@$_SESSION['basket']['cnt']);
				$sum=0;
				if(@$_SESSION['uid'])
					$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='-1'");
				else
					$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='1'");
				$c_cena_id=@mysql_result($res,0,0);
				if(!$c_cena_id)	$c_cena_id=1;
				if(is_array($_SESSION['basket']['cnt']))
				foreach(@$_SESSION['basket']['cnt'] as $item => $cnt)
				{
					$res=mysql_query("SELECT `id`, `name`, `cost` FROM `doc_base` WHERE `id`='$item'");
					$nx=mysql_fetch_row($res);
					$cena=GetCostPos($nx[0], 1);
					$sum+=$cena*$cnt;
				}
				echo "Товаров: $korz_cnt на $sum руб.";
			}
			else
			{
				if(getenv("HTTP_REFERER"))	header('Location: '.getenv("HTTP_REFERER"));
				$tmpl->msg("Товар добавлен в корзину!","info","<a class='urllink' href='/vitrina.php?mode=basket'>Ваша корзина</a>");
			}
		}
		else
		{
			header('HTTP/1.0 404 Not Found');
			header('Status: 404 Not Found');
			$tmpl->msg("Номер товара не задан!","err","<a class='urllink' href='/vitrina.php?mode=basket'>Ваша корзина</a>");
		}
	}
	else if($mode=='korz_adj')
	{
		$tmpl->ajax=1;
		$cnt=intval(@$_REQUEST['cnt']);
		if($p)
		{
			@$_SESSION['basket']['cnt'][$p]+=$cnt;
			$tmpl->AddText("Товар добавлен в корзину!<br><a class='urllink' href='/vitrina.php?mode=basket'>Ваша корзина</a>");
		}
		else
		{
			header('HTTP/1.0 404 Not Found');
			header('Status: 404 Not Found');
			$tmpl->AddText("Номер товара не задан!");
		}
	}
	else if($mode=='korz_del')
	{
		unset($_SESSION['basket']['cnt'][$p]);
		$tmpl->msg("Товар убран из корзины!","info","<a class='urllink' href='/vitrina.php?mode=basket'>Ваша корзина</a>");
	}
	else if($mode=='korz_clear')
	{
		unset($_SESSION['basket']['cnt']);
		$tmpl->msg("Корзина очищена!","info","<a class='urllink' href='/vitrina.php'>Вернутья на витрину</a>");
	}
	else if($mode=='basket_submit')
	{
		$tmpl->ajax=1;
		if(isset($_SESSION['basket']['cnt']))
			if(is_array($_SESSION['basket']['cnt']))
				foreach($_SESSION['basket']['cnt'] as $item => $cnt)
				{
					$ncnt=@$_REQUEST['cnt'.$item];
					if($ncnt<=0) unset($_SESSION['basket']['cnt'][$item]);
					else $_SESSION['basket']['cnt'][$item]=round($ncnt,3);
					$_SESSION['basket']['comments'][$item]=@$_REQUEST['comm'.$item];
				}
		if(@$_REQUEST['button']=='recalc')
		{
			if(getenv("HTTP_REFERER"))	header('Location: '.getenv("HTTP_REFERER"));
			else 	header('Location: /vitrina.php?mode=basket');
		}
		else	header('Location: /vitrina.php?mode=buy');
	}
	else if($mode=='buy')		$this->Buy();
	else if($mode=='delivery')	$this->Delivery();
	else if($mode=='buyform')	$this->BuyMakeForm();
	else if($mode=='makebuy')	$this->MakeBuy();
	else if($mode=='pay')		$this->Payment();
	else if($mode=='print_schet')
	{
		include_once("include/doc.nulltype.php");
		
		$doc=$_SESSION['order_id'];
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
		if(!@$_SESSION['uid'])
		{
			if( (strtoupper($_SESSION['captcha_keystring'])!=strtoupper(@$_REQUEST['img'])) || ($_SESSION['captcha_keystring']=='') )
			{
				unset($_SESSION['captcha_keystring']);
				throw new Exception("Защитный код введён неверно!");
			}
			unset($_SESSION['captcha_keystring']);
			$cd=new CommentDispatcher('product',$p);
			$cd->WriteComment(@$_REQUEST['text'], @$_REQUEST['rate'], @$_REQUEST['autor_name'], @$_REQUEST['autor_email']);
		}
		else
		{
			$cd=new CommentDispatcher('product',$p);
			$cd->WriteComment(@$_REQUEST['text'], @$_REQUEST['rate']);
		}
		$tmpl->msg("Коментарий добавлен!","ok");
	}
	else
	{
		header('HTTP/1.0 404 Not Found');
		header('Status: 404 Not Found');
		throw new Exception("Неверная опция. Возможно, вам дали неверную ссылку, или же это ошибка сайта. Во втором случае, сообщите администратору о возникшей проблеме.");
	}
}

// ======== Приватные функции ========================
// -------- Основные функции -------------------------
/// Корень каталога
protected function TopGroup()
{
	global $tmpl, $CONFIG;
	$tmpl->AddText("<h1 id='page-title'>Витрина</h1>");
	if($CONFIG['site']['vitrina_glstyle']=='item')	$this->GroupList_ItemStyle(0);
	else						$this->GroupList_ImageStyle(0);
}

/// Список групп / подгрупп
protected function ViewGroup($group, $page)
{
	global $tmpl, $CONFIG, $wikiparser;
	settype($group,'int');
	settype($page,'int');
	$res=mysql_query("SELECT `name`, `pid`, `desc`, `title_tag`, `meta_keywords`, `meta_description` FROM `doc_group` WHERE `id`='$group' AND `hidelevel`='0'");
	if(mysql_errno())	throw new MysqlException('Не удалось выбрать информацию о группе');
	$nxt=mysql_fetch_row($res);
	if(!$nxt)
	{
		header('HTTP/1.0 404 Not Found');
		header('Status: 404 Not Found');
		throw new Exception('Группа не найдена! Воспользуйтесь каталогом.');
	}
	if(file_exists("{$CONFIG['site']['var_data_fs']}/category/$group.jpg"))
		$tmpl->AddText("<div style='float: right; margin: 35px 35px 20px 20px;'><img src='{$CONFIG['site']['var_data_web']}/category/$group.jpg' alt='$nxt[0]'></div>");

	if($nxt[3])	$title=$nxt[3];
	else		$title=$nxt[0].', цены, купить';
	if($page>1)	$title.=" - стр.$page";
	$tmpl->SetTitle($title);
	if($nxt[4])	$tmpl->SetMetaKeywords($nxt[4]);
	else
	{
		$k1=array('купить цены','продажа цены','отзывы купить','продажа отзывы','купить недорого');
		$meta_key=$nxt[0].' '.$k1[rand(0,count($k1)-1)].' интернет-магазин '.$CONFIG['site']['display_name'];
		$tmpl->SetMetaKeywords($meta_key);
	}

	if($nxt[5])	$tmpl->SetMetaDescription($nxt[5]);
	else
	{
		$d1=array('купить','заказать','продажа','приобрести');
		$d2=array('доступной','отличной','хорошей','разумной','выгодной');
		$d3=array('цене','стоимости');
		$d4=array('Большой','Широкий','Огромный');
		$d5=array('выбор','каталог','ассортимент');
		$d6=array('товаров','продукции');
		$d7=array('Доставка','Экспресс-доставка','Доставка курьером','Почтовая доставка');
		$d8=array('по всей России','в любой город России','по РФ','в любой регион России');
		$meta_desc=$nxt[0].' - '.$d1[rand(0,count($d1)-1)].' в интернет-магазине '.$CONFIG['site']['display_name'].' по '.$d2[rand(0,count($d2)-1)].' '.$d3[rand(0,count($d3)-1)].'. '.$d4[rand(0,count($d4)-1)].' '.$d5[rand(0,count($d5)-1)].' '.$d6[rand(0,count($d6)-1)].'. '.$d7[rand(0,count($d7)-1)].' '.$d8[rand(0,count($d8)-1)].'.';
		$tmpl->SetMetaDescription($meta_desc);
	}

	$h1=$nxt[0];
	if($page>1)	$h1.=" - стр.$page";
	$tmpl->AddText("<h1 id='page-title'>$h1</h1>");
	$tmpl->AddText("<div class='breadcrumb'>".$this->GetVitPath($nxt[1])."</div>");
	if($nxt[2])
	{
		$text=$wikiparser->parse(html_entity_decode($nxt[2],ENT_QUOTES,"UTF-8"));
		$tmpl->AddText("<div class='group-description'>$text</div><br>");
	}
	$tmpl->AddText("<div style='clear: right'></div>");
	if($CONFIG['site']['vitrina_glstyle']=='item')	$this->GroupList_ItemStyle($group);
	else						$this->GroupList_ImageStyle($group);
	/// TODO: сделать возможность выбора вида отображения списка товаров посетителем
	$this->ProductList($group, $page);
}

/// Список товаров в группе
protected function ProductList($group, $page)
{
	global $tmpl, $CONFIG;
	if(isset($_GET['op']))
		$_SESSION['vit_photo_only']=$_GET['op']?1:0;

	if(isset($_REQUEST['order']))			$order=$_REQUEST['order'];
	else if(isset($_SESSION['vitrina_order']))	$order=@$_SESSION['vitrina_order'];
	else $order='';
	if(!$order)	$order=@$CONFIG['site']['vitrina_order'];

	switch($order)
	{
		case 'n':	$sql_order='`doc_base`.`name`';		break;
		case 'nd':	$sql_order='`doc_base`.`name` DESC';	break;
		case 'vc':	$sql_order='`doc_base`.`vc`';		break;
		case 'vcd':	$sql_order='`doc_base`.`vc` DESC';	break;
		case 'c':	$sql_order='`doc_base`.`cost`';		break;
		case 'cd':	$sql_order='`doc_base`.`cost` DESC';	break;
		case 's':	$sql_order='`count`';			break;
		case 'sd':	$sql_order='`count` DESC';		break;
		default:	$sql_order='`doc_base`.`name`';
				$order='n';
	}
	$_SESSION['vitrina_order']=$order;

	if(isset($_REQUEST['view']))			$view=$_REQUEST['view'];
	else if(isset($_SESSION['vitrina_view']))	$view=@$_SESSION['vitrina_view'];
	else $view='';
	if($view!='i' && $view!='l' && $view!='t')
	{
		if($CONFIG['site']['vitrina_plstyle']=='imagelist')		$view='i';
		else if($CONFIG['site']['vitrina_plstyle']=='extable')		$view='t';
		else								$view='l';
	}
	$_SESSION['vitrina_view']=$view;

	$sql_photo_only=@$_SESSION['vit_photo_only']?"AND `img_id` IS NOT NULL":"";
	$cnt_where=@$CONFIG['site']['vitrina_sklad']?(" AND `doc_base_cnt`.`sklad`=".intval($CONFIG['site']['vitrina_sklad'])." "):'';
	$sql="SELECT `doc_base`.`id`, `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`cost_date`, `doc_base`.`cost`,
	( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` $cnt_where GROUP BY `doc_base`.`id`) AS `count`,
	`doc_base`.`transit_cnt`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base`.`proizv`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`vc`, `doc_base`.`buy_time`, `doc_base`.`create_time`
	FROM `doc_base`
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
	LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
	LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
	WHERE `doc_base`.`group`='$group' AND `doc_base`.`hidden`='0' $sql_photo_only
	ORDER BY $sql_order";
	$res=mysql_query($sql);
	if(mysql_errno())	throw new MysqlException("Не удалось получить список товаров!");
	$lim=$CONFIG['site']['vitrina_limit'];
	if($lim==0)	$lim=100;
	$rows=mysql_num_rows($res);
        if($rows)
        {
		if($page<1 || $lim*($page-1)>$rows) 
		{ 
			header("Location: ".(empty($_SERVER['HTTPS'])?"http":"https")."://".$_SERVER['HTTP_HOST'].$this->GetGroupLink($group),false,301); 
			exit(); 
		}
		$this->OrderAndViewBar($group,$page,$order,$view);

		$this->PageBar($group, $rows, $lim, $page);
		if(($lim<$rows) && $page )	mysql_data_seek($res, $lim*($page-1));
		if($view=='i')			$this->TovList_ImageList($res, $lim);
		else if($view=='t')		$this->TovList_ExTable($res, $lim);
		else				$this->TovList_SimpleTable($res, $lim);
		$this->PageBar($group, $rows, $lim, $page);
		$tmpl->AddText("<span style='color:#888'>Серая цена</span> требует уточнения<br>");
	}
        elseif(isset ($page) && $page!=1)
        {
                header("Location: ".(empty($_SERVER['HTTPS'])?"http":"https")."://".$_SERVER['HTTP_HOST'].$this->GetGroupLink($group),false,301); 
		exit(); ;
        }
}

/// Блок товаров, выбранных по признаку, основанному на типе блока
protected function ViewBlock($block)
{
	global $tmpl, $CONFIG, $wikiparser;
	$cnt_where=@$CONFIG['site']['vitrina_sklad']?(" AND `doc_base_cnt`.`sklad`=".intval($CONFIG['site']['vitrina_sklad'])." "):'';
	$head='';
	/// Определение типа блока
	if($block=='stock')
	{
		$sql="SELECT `doc_base`.`id`, `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`cost_date`, `doc_base`.`cost`,
		( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` $cnt_where GROUP BY `doc_base`.`id`) AS `count`,
		`doc_base`.`transit_cnt`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base`.`proizv`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`vc`, `doc_base`.`buy_time`, `doc_base`.`create_time`
		FROM `doc_base`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
		LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_base`.`hidden`='0' AND `doc_base`.`stock`!='0'
		ORDER BY `doc_base`.`likvid` ASC";
		$head='Распродажа';
	}
	else if($block=='popular')
	{
		$sql="SELECT `doc_base`.`id`, `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`cost_date`, `doc_base`.`cost`,
		( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` $cnt_where GROUP BY `doc_base`.`id`) AS `count`,
		`doc_base`.`transit_cnt`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base`.`proizv`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`vc`, `doc_base`.`buy_time`, `doc_base`.`create_time`
		FROM `doc_base`
		INNER JOIN `doc_group` ON `doc_group`.`id`= `doc_base`.`group` AND `doc_group`.`hidelevel`='0'
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
		LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_base`.`hidden`='0'
		ORDER BY `doc_base`.`likvid` DESC
		LIMIT 48";
		$head='Популярные товары';
	}
	else if($block=='new')
	{
		if($CONFIG['site']['vitrina_newtime'])	$new_time=date("Y-m-d H:i:s",time()-60*60*24*$CONFIG['site']['vitrina_newtime']);
		else					$new_time=date("Y-m-d H:i:s",time()-60*60*24*180);
		$sql="SELECT `doc_base`.`id`, `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`cost_date`, `doc_base`.`cost`,
		( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` $cnt_where GROUP BY `doc_base`.`id`) AS `count`,
		`doc_base`.`transit_cnt`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base`.`proizv`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`vc`, `doc_base`.`buy_time`, `doc_base`.`create_time`
		FROM `doc_base`
		INNER JOIN `doc_group` ON `doc_group`.`id`= `doc_base`.`group` AND `doc_group`.`hidelevel`='0'
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
		LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_base`.`hidden`='0' AND (`doc_base`.`create_time`>='$new_time' OR `doc_base`.`buy_time`>='$new_time')
		ORDER BY `doc_base`.`buy_time` DESC
		LIMIT 24";
		$head='Новинки';
	}
	else if($block=='transit')
	{
		$sql="SELECT `doc_base`.`id`, `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`cost_date`, `doc_base`.`cost`,
		( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` $cnt_where GROUP BY `doc_base`.`id`) AS `count`,
		`doc_base`.`transit_cnt`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base`.`proizv`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`vc`, `doc_base`.`buy_time`, `doc_base`.`create_time`
		FROM `doc_base`
		INNER JOIN `doc_group` ON `doc_group`.`id`= `doc_base`.`group` AND `doc_group`.`hidelevel`='0'
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
		LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_base`.`hidden`='0' AND `doc_base`.`transit_cnt`>0
		ORDER BY `doc_base`.`name`";
		$head='Товар в пути';
	}
	else
	{
		header('HTTP/1.0 404 Not Found');
		header('Status: 404 Not Found');
		throw new Exception('Блок не найден!');
	}

	$page_name='vitrina:'.$block;
	$res=mysql_query("SELECT `articles`.`name`, `a`.`name` AS `a_name`, `articles`.`date`, `articles`.`changed`, `b`.`name` AS `b_name`, `articles`.`text`, `articles`.`type`
	FROM `articles`
	LEFT JOIN `users` AS `a` ON `a`.`id`=`articles`.`autor`
	LEFT JOIN `users` AS `b` ON `b`.`id`=`articles`.`changeautor`
	WHERE `articles`.`name` = '$page_name'");
	if(mysql_errno())	throw new MysqlException("Невозможно получить текст блока");
	if($nxt=mysql_fetch_assoc($res))
	{
		$meta_description=$meta_keywords='';
		$text=$nxt['text'];
		if($nxt['type']==0)	$text=strip_tags($text, '<nowiki>');
		if($nxt['type']==0 || $nxt['type']==2)
		{
			$text=$wikiparser->parse(html_entity_decode($text,ENT_QUOTES,"UTF-8"));
			if(@$wikiparser->title)
				$head=$wikiparser->title;
			$meta_description=@$wikiparser->definitions['meta_description'];
			$meta_keywords=@$wikiparser->definitions['meta_keywords'];
			$tmpl->AddText("<h1 id='page-title'>$head</h1>");
		}
		if($nxt['type']==1 || $nxt['type']==2)	$text=html_entity_decode($text,ENT_QUOTES,"UTF-8");

		$tmpl->SetTitle($head);

		if(@$_SESSION['uid'])
		{
			if(isAccess('generic_articles','edit'))
			{
				if($nxt['b_name']) $ch=", последнее изменение - {$nxt['b_name']}, date {$nxt['changed']}";
				else $ch='';
				$tmpl->AddText("<div id='page-info'>Создал: {$nxt['a_name']}, date: {$nxt['date']} $ch");
				$tmpl->AddText(", <a href='/articles.php?p=$page_name&amp;mode=edit'>Исправить</a>");
				$tmpl->AddText("</div>");
			}
		}
		$tmpl->AddText("<div>$text</div>");
		$tmpl->SetMetaKeywords($meta_keywords);
		$tmpl->SetMetaDescription($meta_description);
	}
	else
	{
		$tmpl->AddText("<h1 id='page-title'>$head</h1>");
		if(@$_SESSION['uid'])
		{
			if(isAccess('generic_articles','edit'))
				$tmpl->AddText("<div id='page-info'><a href='/articles.php?p=$page_name&amp;mode=edit'>Создать</a></div>");
		}
		$tmpl->SetTitle($head);
	}

	$res=mysql_query($sql);
	if(mysql_errno())	throw new MysqlException("Не удалось получить список товаров!");
	$lim=1000;
	$rows=mysql_num_rows($res);
        if($rows)
        {
		if($CONFIG['site']['vitrina_plstyle']=='imagelist')		$view='i';
		else if($CONFIG['site']['vitrina_plstyle']=='extable')		$view='t';
		else								$view='l';

		if($view=='i')			$this->TovList_ImageList($res, $lim);
		else if($view=='t')		$this->TovList_ExTable($res, $lim);
		else				$this->TovList_SimpleTable($res, $lim);

		$tmpl->AddText("<span style='color:#888'>Серая цена</span> требует уточнения<br>");
	}
	else $tmpl->msg("Товары в данной категории отсутствуют");
}

/// Блок ссылок смены вида отображения и сортировки
protected function OrderAndViewBar($group,$page,$order,$view)
{
	global $tmpl;
	$tmpl->AddText("<div class='orderviewbar'>");
	$tmpl->AddText("<div class='orderbar'>Показывать: ");
	if($view=='i')	$tmpl->AddText("<span class='selected'>Картинками</span> ");
	else		$tmpl->AddText("<span><a href='".$this->GetGroupLink($group, $page, 'view=i')."'>Картинками</a></span> ");
	if($view=='t')	$tmpl->AddText("<span class='selected'>Таблицей</span> ");
	else		$tmpl->AddText("<span><a href='".$this->GetGroupLink($group, $page, 'view=t')."'>Таблицей</a></span> ");
	if($view=='l')	$tmpl->AddText("<span class='selected'>Списком</span> ");
	else		$tmpl->AddText("<span><a href='".$this->GetGroupLink($group, $page, 'view=l')."'>Списком</a></span> ");
	if(@$_SESSION['vit_photo_only'])	$tmpl->AddText("<span class='selected'><a class='down'  href='".$this->GetGroupLink($group, $page, 'op=0')."'>Только с фото</a></span> ");
	else					$tmpl->AddText("<span><a href='".$this->GetGroupLink($group, $page, 'op=1')."'>Только с фото</a></span> ");
	$tmpl->AddText("</div>");
	$tmpl->AddText("<div class='viewbar'>Сортировать по: ");
	if($order=='n')		$tmpl->AddText("<span class='selected'><a href='".$this->GetGroupLink($group, $page, 'order=nd')."'>Названию</a></span> ");
	else if($order=='nd')	$tmpl->AddText("<span class='selected'><a class='down' href='".$this->GetGroupLink($group, $page, 'order=n')."'>Названию</a></span> ");
	else			$tmpl->AddText("<span><a href='".$this->GetGroupLink($group, $page, 'order=n')."'>Названию</a></span> ");

	if($order=='vc')	$tmpl->AddText("<span class='selected'><a href='".$this->GetGroupLink($group, $page, 'order=vcd')."'>Коду</a></span> ");
	else if($order=='vcd')	$tmpl->AddText("<span class='selected'><a href='".$this->GetGroupLink($group, $page, 'order=vc')."'>Коду</a></span> ");
	else			$tmpl->AddText("<span><a class='down' href='".$this->GetGroupLink($group, $page, 'order=vc')."'>Коду</a></span> ");

	if($order=='c')		$tmpl->AddText("<span class='selected'><a href='".$this->GetGroupLink($group, $page, 'order=cd')."'>Цене</a></span> ");
	else if($order=='cd')	$tmpl->AddText("<span class='selected'><a class='down' href='".$this->GetGroupLink($group, $page, 'order=c')."'>Цене</a></span> ");
	else		$tmpl->AddText("<span><a href='".$this->GetGroupLink($group, $page, 'order=c')."'>Цене</a></span> ");

	if($order=='s')		$tmpl->AddText("<span class='selected'><a href='".$this->GetGroupLink($group, $page, 'order=sd')."'>Наличию</a></span> ");
	else if($order=='sd')	$tmpl->AddText("<span class='selected'><a class='down' href='".$this->GetGroupLink($group, $page, 'order=s')."'>Наличию</a></span> ");
	else			$tmpl->AddText("<span><a href='".$this->GetGroupLink($group, $page, 'order=s')."'>Наличию</a></span> ");
	$tmpl->AddText("</div><div class='clear'></div>");
	$tmpl->AddText("</div>");
}

/// Карточка товара
protected function ProductCard($product)
{
	global $tmpl, $CONFIG, $wikiparser;
	settype($product,'int');
	$cnt_where=@$CONFIG['site']['vitrina_sklad']?(" AND `doc_base_cnt`.`sklad`=".intval($CONFIG['site']['vitrina_sklad'])." "):'';
	$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`group`, `doc_base`.`cost`,
	`doc_base`.`proizv`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`,
	`doc_base_dop`.`mass`, `doc_base_dop`.`analog`, ( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` $cnt_where) AS `cnt`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `doc_base_dop_type`.`name` AS `dop_name`, `class_unit`.`name` AS `units`, `doc_group`.`printname` AS `group_printname`, `doc_base`.`vc`, `doc_base`.`title_tag`, `doc_base`.`meta_description`, `doc_base`.`meta_keywords`, `doc_base`.`buy_time`, `doc_base`.`create_time`, `doc_base`.`transit_cnt`, `class_unit`.`rus_name1` AS `units_min`
	FROM `doc_base`
	INNER JOIN `doc_group` ON `doc_base`.`group`=`doc_group`.`id`
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	LEFT JOIN `doc_base_dop_type` ON `doc_base_dop_type`.`id`=`doc_base_dop`.`type`
	LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
	LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
	LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
	WHERE `doc_base`.`id`='$product'
	ORDER BY `doc_base`.`name` ASC LIMIT 1");
	if(mysql_errno())	throw new MysqlException('Не удалось получить карточку товара!');
	$i=0;
	if($nxt=mysql_fetch_array($res))
	{
		if($nxt['title_tag'])	$title=$nxt['title_tag'];
		else			$title="{$nxt['group_printname']} {$nxt['name']}, цены и характеристики, купить";
		$tmpl->SetTitle($title);
		$base=abs(crc32($nxt['name'].$nxt['group'].$nxt['proizv'].$nxt['vc'].$nxt['desc']));
		if($nxt['meta_keywords'])	$tmpl->SetMetaKeywords($nxt['meta_keywords']);
		else
		{
			$k1=array('купить','цены','характеристики','фото','выбор','каталог','описания','отзывы','продажа','описание');
			$l=count($k1);
			$i1=$base%$l;
			$base=floor($base/$l);
			$i2=$base%$l;
			$base=floor($base/$l);
			$meta_key=$nxt['group_printname'].' '.$nxt['name'].' '.$k1[$i1].' '.$k1[$i2];
			$tmpl->SetMetaKeywords($meta_key);
		}

		if($nxt['meta_description'])	$tmpl->SetMetaDescription($nxt['meta_description']);
		else
		{
			$d=array();
			$d[0]=array($nxt['group_printname'].' '.$nxt['name'].' '.$nxt['proizv'].' - ');
			$d[1]=array('купить','заказать','продажа','приобрести');
			$d[2]=array(' в интернет-магазине '.$CONFIG['site']['display_name'].' по ');
			$d[3]=array('доступной','отличной','хорошей','разумной','выгодной');
			$d[4]=array('цене.','стоимости.');
			$d[5]=array('Большой','Широкий','Огромный');
			$d[6]=array('выбор','каталог','ассортимент');
			$d[7]=array('товаров.','продукции.');
			$d[8]=array('Доставка','Экспресс-доставка','Доставка курьером','Почтовая доставка');
			$d[9]=array('по всей России.','в любой город России.','по РФ.','в любой регион России.');
			$str='';
			foreach($d as $id => $item)
			{
				$l=count($item);
				$i=$base%$l;
				$base=floor($base/$l);
				$str.=$item[$i].' ';
			}
 			$tmpl->SetMetaDescription($str);
		}


		$tmpl->AddText("<h1 id='page-title'>{$nxt['group_printname']} {$nxt['name']}</h1>");
		$tmpl->AddText("<div class='breadcrumb'>".$this->GetVitPath($nxt['group'])."</div>");
		$appends=$img_mini="";
		if($nxt['img_id'])
		{
			$miniimg=new ImageProductor($nxt['img_id'],'p', $nxt['img_type']);
			$miniimg->SetY(220);
			$miniimg->SetX(200);
			$fullimg=new ImageProductor($nxt['img_id'],'p', $nxt['img_type']);
			$img="<img src='".$miniimg->GetURI()."' alt='{$nxt['name']}' onload='$(this).fadeTo(500,1);' style='opacity: 1' id='midiphoto'>";
			$res=mysql_query("SELECT `doc_img`.`id` AS `img_id`, `doc_base_img`.`default`, `doc_img`.`name`, `doc_img`.`type` AS `img_type` FROM `doc_base_img`
			LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
			WHERE `doc_base_img`.`pos_id`='{$nxt['id']}'");
			if(mysql_errno())	throw new MysqlException('Не удалось выбрать информацию о изображениях');

			while($img_data=mysql_fetch_assoc($res))
			{
				$miniimg=new ImageProductor($img_data['img_id'],'p', $img_data['img_type']);
				$miniimg->SetX(40);
				$miniimg->SetY(40);
				$midiimg=new ImageProductor($img_data['img_id'],'p', $img_data['img_type']);
				$midiimg->SetX(200);
				$midiimg->SetY(220);
				$fullimg=new ImageProductor($img_data['img_id'],'p', $img_data['img_type']);
				$fullimg->SetY(800);
				$originimg=new ImageProductor($img_data['img_id'],'p', $img_data['img_type']);
				if(mysql_num_rows($res)>1)
					$img_mini.="<a href='".$midiimg->GetURI()."' onclick=\"return setPhoto({$img_data['img_id']});\"><img src='".$miniimg->GetURI()."' alt='{$img_data['name']}'></a>";
				$appends.="midiphoto.appendImage({$img_data['img_id']},'".html_entity_decode($midiimg->GetURI(), ENT_COMPAT, 'UTF-8')."', '".html_entity_decode($fullimg->GetURI(), ENT_COMPAT, 'UTF-8')."', '".html_entity_decode($originimg->GetURI(), ENT_COMPAT, 'UTF-8')."');\n";

			}
		}
		else $img="<img src='/skins/{$CONFIG['site']['skin']}/images/no_photo.png' alt='no photo'>";

		$tmpl->AddText("<table class='product-card'>
		<tr valign='top'><td rowspan='15' width='150'>
		<div class='image'><div class='one load'>$img</div><div class='list'>$img_mini</div></div>
		<script>
		var midiphoto=tripleView('midiphoto')
		$appends
		function setPhoto(id)
		{
			return midiphoto.setPhoto(id)
		}
		</script>");

		$tmpl->AddText("<td class='field'>Наименование:<td>{$nxt['name']}");
		if($nxt['vc']) $tmpl->AddText("<tr><td class='field'>Код производителя:<td>{$nxt['vc']}<br>");
		if($nxt[2])
		{
			$text=$wikiparser->parse(html_entity_decode($nxt[2],ENT_QUOTES,"UTF-8"));
			$tmpl->AddText("<tr><td valign='top' class='field'>Описание:<td>$text");
		}
		if($nxt[14]) $tmpl->AddText("<tr><td class='field'>Тип:<td>$nxt[14]");
		$cena=GetCostPos($nxt[0], $this->cost_id);
		$tmpl->AddText("<tr><td class='field'>Цена:<td>$cena<br>");
		$tmpl->AddText("<tr><td class='field'>Единица измерения:<td>$nxt[15]<br>");
		
		$nal=$this->GetCountInfo($nxt['count'], $nxt['transit_cnt']);
		if($nal) $tmpl->AddText("<tr><td class='field'>Наличие: <td><b>$nal</b><br>");
		else $tmpl->AddText("<tr><td class='field'>Наличие:<td>Под заказ<br>");
		if($nxt[6]) $tmpl->AddText("<tr><td class='field'>Внутренний диаметр: <td>$nxt[6] мм.<br>");
		if($nxt[7]) $tmpl->AddText("<tr><td class='field'>Внешний диаметр: <td>$nxt[7] мм.<br>");
		if($nxt[8]) $tmpl->AddText("<tr><td class='field'>Высота: <td>$nxt[8] мм.<br>");
		if($nxt[9]) $tmpl->AddText("<tr><td class='field'>Масса: <td>$nxt[9] кг.<br>");
		if($nxt[10]) $tmpl->AddText("<tr><td class='field'>Аналог: <td>$nxt[10]<br>");
		if($nxt[5]) $tmpl->AddText("<tr><td class='field'>Производитель: <td>$nxt[5]<br>");

		$param_res=mysql_query("SELECT `doc_base_params`.`param`, `doc_base_values`.`value` FROM `doc_base_values`
		LEFT JOIN `doc_base_params` ON `doc_base_params`.`id`=`doc_base_values`.`param_id`
		WHERE `doc_base_values`.`id`='{$nxt['id']}' AND `doc_base_params`.`pgroup_id`='0' AND `doc_base_params`.`system`='0'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список свойств!");
		while($params=mysql_fetch_row($param_res))
		{
			$tmpl->AddText("<tr><td class='field'>$params[0]:<td>$params[1]</tr>");
		}

		$resg=mysql_query("SELECT `id`, `name` FROM `doc_base_gparams`");
		if(mysql_errno())	throw new MysqlException("Не удалось параметры групп складской номенклатуры");
		while($nxtg=mysql_fetch_row($resg))
		{
			$f=0;
			$param_res=mysql_query("SELECT `doc_base_params`.`param`, `doc_base_values`.`value` FROM `doc_base_values`
			LEFT JOIN `doc_base_params` ON `doc_base_params`.`id`=`doc_base_values`.`param_id`
			WHERE `doc_base_values`.`id`='{$nxt['id']}' AND `doc_base_params`.`pgroup_id`='$nxtg[0]' AND `doc_base_params`.`system`='0'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список свойств!");
			while($params=mysql_fetch_row($param_res))
			{
				if(!$f)
				{
					$f=1;
					$tmpl->AddText("<tr><th colspan='2'>$nxtg[1]</th></tr>");
				}
				$tmpl->AddText("<tr><td class='field'>$params[0]:<td>$params[1]</tr>");
			}
		}

		$att_res=mysql_query("SELECT `doc_base_attachments`.`attachment_id`, `attachments`.`original_filename`, `attachments`.`comment`
		FROM `doc_base_attachments`
		LEFT JOIN `attachments` ON `attachments`.`id`=`doc_base_attachments`.`attachment_id`
		WHERE `doc_base_attachments`.`pos_id`='$product'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список прикреплённых файлов");
		if(mysql_num_rows($att_res)>0)
		{
			$tmpl->AddText("<tr><th colspan='3'>Прикреплённые файлы</th></tr>");
			while($anxt=@mysql_fetch_row($att_res))
			{
				if($CONFIG['site']['recode_enable'])	$link="/attachments/{$anxt[0]}/$anxt[1]";
				else					$link="/attachments.php?att_id={$anxt[0]}";
				$tmpl->AddText("<tr><td><a href='$link'>$anxt[1]</a></td><td>$anxt[2]</td></tr>");
			}
		}

		$tmpl->AddText("<tr><td colspan='3'>
		<form action='/vitrina.php'>
		<input type='hidden' name='mode' value='korz_add'>
		<input type='hidden' name='p' value='$product'>
		<div>
		Добавить
		<input type='text' name='cnt' value='1' class='mini'> штук <button type='submit'>В корзину!</button>
		</div>
		</form>
		</td></tr></table>");
		$d=array();
		$d[]=array('В нашем');
		$d[]=array('магазине','интернет-магазине','каталоге','прайс-листе');
		$d[]=array('Вы можете');
		$d[]=array('купить','заказать','приобрести');
		$d[]=array($nxt['group_printname'].' '.$nxt['name'].' '.$nxt['proizv'].' по ');
		$d[]=array('доступной','отличной','хорошей','разумной','выгодной');
		$d[]=array('цене за','стоимости за');
		$d[]=array('наличный расчёт.','безналичный расчёт.','webmoney.');
		$d[]=array('Так же можно');
		$d[]=array('заказать','запросить','осуществить');
		$d[]=array('доставку','экспресс-доставку','доставку транспортной компанией','почтовую доставку','доставку курьером');
		$d[]=array('этого товара','выбранной продукции');
		$d[]=array('по всей России.','в любой город России.','по РФ.','в любой регион России.');
		$str='';
		$base=abs(crc32($nxt['name'].$nxt['group'].$nxt['proizv'].$nxt['vc'].$nxt['desc']));
		foreach($d as $id => $item)
		{
			$l=count($item);
			$i=$base%$l;
			$base=floor($base/$l);
			$str.=$item[$i].' ';
		}
		$tmpl->AddText("<div class='description'>$str</div>");
		$tmpl->AddText("<script type='text/javascript' charset='utf-8'>
		$(document).ready(function(){
		$(\"a[rel^='prettyPhoto']\").prettyPhoto({theme:'dark_rounded'});
		});
		</script>");
		$i++;
	}

	if($i==0)
	{
		$tmpl->AddText("<h1 id='page-title'>Информация о товаре</h1>");
		header('HTTP/1.0 404 Not Found');
		header('Status: 404 Not Found');
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
	$i=1;
	$lock=0;
	if(isset($_SESSION['basket']['cnt']))
	foreach($_SESSION['basket']['cnt'] as $item => $cnt)
	{
		$lock_mark='';
		$res=mysql_query("SELECT `id`, `name`, `cost_date` FROM `doc_base` WHERE `id`='$item'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список товаров!");
		$nx=mysql_fetch_array($res);
		
		if(@$CONFIG['site']['vitrina_cntlock'])
		{
			if(isset($CONFIG['site']['vitrina_sklad']))
			{
				$sklad_id=round($CONFIG['site']['vitrina_sklad']);
				$res=mysql_query("SELECT `doc_base_cnt`.`cnt` FROM `doc_base_cnt` WHERE `id`='$item' AND `sklad`='$sklad_id'");
				$sklad_cnt=mysql_result($res,0,0)-DocRezerv($item);
			}
			else
			{
				$res=mysql_query("SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `id`='$item'");
				$sklad_cnt=mysql_result($res,0,0)-DocRezerv($item);
			}
			if($cnt>$sklad_cnt)
			{
				$lock=1;
				$lock_mark=1;
			}
		}
		if(@$CONFIG['site']['vitrina_pricelock'])
		{
			if(strtotime($nx['cost_date'])<(time()-60*60*24*30*6))
			{
				$lock=1;
				$lock_mark=1;
			}
		}
		$cena=GetCostPos($nx[0], $this->cost_id);
		$sm=$cena*$cnt;
		$sum+=$sm;
		$sm=sprintf("%0.2f",$sm);
		if(isset($_SESSION['basket']['comments'][$item]))	$comm=$_SESSION['basket']['comments'][$item];
		else	$comm='';
		$lock_mark=$lock_mark?'color: #f00':'';
		$s.="
		<tr id='korz_ajax_item_$item' style='$lock_mark'><td class='right'>$i <span id='korz_item_clear_url_$item'><a href='/vitrina.php?mode=korz_del&p=$item' onClick='korz_item_clear($item); return false;'><img src='/img/i_del.png' alt='Убрать'></a></span><td><a href='/vitrina.php?mode=product&amp;p=$nx[0]' style='$lock_mark'>$nx[1]</a><td class='right'>$cena<td class='right'><span class='sum'>$sm</span><td><input type='number' name='cnt$item' value='$cnt' class='mini'><td><input type='text' name='comm$item' style='width: 90%' value='$comm' maxlength='100'>
		
		";
		$cc=1-$cc;
		$exist=1;
		$i++;
	}
	if(!$exist) $tmpl->msg("Ваша корзина пуста! Выберите, пожалуйста интересующие Вас товары!","info");
	else
	{
		$tmpl->AddText("
		<h1 id='page-title'>Ваша корзина</h1>
		В поле *коментарий* вы можете высказать пожелания по конкретному товару (не более 100 символов).<br>
		<script>
		function korz_clear() {
		$.ajax({
		url: '/vitrina.php?mode=korz_clear',
		beforeSend: function() { $('#korz_clear_url').html('<img src=\"/img/icon_load.gif\" alt=\"обработка..\">'); },
		success: function() { $('#korz_ajax').html('Корзина очищена'); }
		})
		}

		function korz_item_clear(id) {
		$.ajax({
			url: '/vitrina.php?mode=korz_del&p='+id,
			async: false,
			beforeSend: function() { $('#korz_item_clear_url_'+id).html('<img src=\"/img/icon_load.gif\" alt=\"обработка..\">'); },
			success: function() { $('#korz_ajax_item_'+id).remove(); },
			complete: function() {
			sum = 0;
			$('span.sum').each(function() {
			var num = parseFloat($(this).text());
			if (num) sum += num;
			});
			$('span.sums').html(sum.toFixed(2));
			}
		})
		}
		</script>");
		if($lock)	$tmpl->msg("Обратите внимание, Ваша корзина содержит наименования, доступные только под заказ (выделены красным). Вы не сможете оплатить заказ до его подтверждения оператором.","info", "Предупреждение");
		$tmpl->AddText("
		<form action='' method='post'>
		<input type='hidden' name='mode' value='basket_submit'>
		<table width='100%' class='list'>
		<tr class='title'><th>N</th><th>Наименование<th>Цена, руб<th>Сумма, руб<th>Количество, шт<th>Коментарии</tr>
		$s
		<tr class='total'><td>&nbsp;</td><td colspan='2'>Итого:</td><td colspan='3'><span class='sums'>$sum</span> рублей</td></tr>
		</table>
		<br>
		<center><button name='button' value='recalc' type='submit'>Пересчитать</button>
		<button name='button' value='buy' type='submit'>Оформить заказ</button></center><br>
		<center><span id='korz_clear_url'><a href='/vitrina.php?mode=korz_clear' onClick='korz_clear(); return false;'><b>Очистить корзину!</b></a></span></center><br>
		</form>
		</center><br><br>
		");

		$_SESSION['korz_sum']=$sum;
		//if( ($_SESSION['korz_sum']>20000) )	$tmpl->msg("Ваш заказ на сумму более 20'000, вам будет предоставлена удвоенная скидка!");
		//else $tmpl->msg("Цены указаны со скидкой 3%. А при оформлении заказа на сумму более 20'000 рублей предоставляется скидка 6%","info");
		
	}
}

/// Оформление доставки
protected function Delivery()
{
	$this->basket_sum=0;
	if(isset($_SESSION['basket']['cnt']))
	foreach($_SESSION['basket']['cnt'] as $item => $cnt)
	{
		$this->basket_sum+=GetCostPos($item, $this->cost_id)*$cnt;
	}
	if(!isset($_REQUEST['delivery_type']))
	{
		$this->DeliveryTypeForm();
	}
	else if(!@$_REQUEST['delivery_region'])
	{
		$_SESSION['basket']['delivery_type']	= round($_REQUEST['delivery_type']);
		if($_REQUEST['delivery_type']==0)	$this->BuyMakeForm();
		else
		{
			if(isset($_SESSION['uid']))
			{
				$up=getUserProfile($_SESSION['uid']);
				$this->basket_address	= @$up['main']['real_address'];
			}
			else	$this->basket_address='';
			$this->DeliveryRegionForm();
		}
	}
	else
	{
		$_SESSION['basket']['delivery_region']	= rcv('delivery_region');
		$_SESSION['basket']['delivery_address']	= rcv('delivery_address');
		$_SESSION['basket']['delivery_date']	= rcv('delivery_date');
		$this->BuyMakeForm();
	}
}

/// Форма *способ доставки*
protected function DeliveryTypeForm()
{
	global $tmpl;
	$tmpl->SetText("<h1>Способ доставки</h1>");
	$tmpl->AddText("<form action='' method='post'>
	<input type='hidden' name='mode' value='delivery'>
	<label><input type='radio' name='delivery_type' value='0'> Самовывоз</label><br><small>Вы сможете забрать товар с нашего склала</small><br><br>");
	$res=mysql_query("SELECT `id`, `name`, `min_price`, `description` FROM `delivery_types`");
	if(!$res)	throw new MysqlException("Не удалось запростить виды доставки");
	while($nxt=mysql_fetch_assoc($res))
	{
		$disabled=$this->basket_sum<$nxt['min_price']?' disabled':'';
		$tmpl->AddText("<label><input type='radio' name='delivery_type' value='{$nxt['id']}'$disabled> {$nxt['name']}</label><br>Минимальная сумма заказа - {$nxt['min_price']} рублей.<br><small>{$nxt['description']}</small><br><br>");
	}
	$tmpl->AddText("<button type='submit'>Далее</button></form>");
}

/// Форма *регион доставки*
protected function DeliveryRegionForm()
{
	global $tmpl;
	$tmpl->SetText("<h1>Регион доставки</h1>");
	$tmpl->AddText("<form action='' method='post'>
	<input type='hidden' name='mode' value='delivery'>
	<input type='hidden' name='delivery_type' value='{$_REQUEST['delivery_type']}'>");
	$res=mysql_query("SELECT `id`, `name`, `price`, `description` FROM `delivery_regions` WHERE `delivery_type`='{$_SESSION['basket']['delivery_type']}'");
	if(!$res)	throw new MysqlException("Не удалось запростить регионы доставки");
	while($nxt=mysql_fetch_assoc($res))
	{
		$tmpl->AddText("<label><input type='radio' name='delivery_region' value='{$nxt['id']}'> {$nxt['name']} - {$nxt['price']} рублей.</label><br><small>{$nxt['description']}</small><br><br>");
	}
	$tmpl->AddText("
	Желаемые дата и время доставки:<br>
	<input type='text' name='delivery_date'><br>
	Адрес доставки:<br>
	<textarea name='delivery_address' rows='5' cols='80'>{$this->basket_address}</textarea><br>
	<button type='submit'>Далее</button></form>");
}


/// Оформление покупки
protected function Buy()
{
	global $tmpl;
	$step=intval(@$_REQUEST['step']);
	$tmpl->SetText("<h1 id='page-title'>Оформление заказа</h1>");
	if((!@$_SESSION['uid'])&&($step!=1))
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
	else	$this->Delivery();
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
		else
		{
			if(file_exists($CONFIG['site']['location'].'/skins/'.$CONFIG['site']['skin'].'/no_photo.png'))
				$img_url='/skins/'.$CONFIG['site']['skin'].'/no_photo.png';
			else	$img_url='/img/no_photo.png';
			$tmpl->AddText("<img src='$img_url' alt='Изображение не доступно'>");
		}
		$tmpl->AddText("</a><div><a href='$link'><b>$nxt[1]</b></a><br>");
		if($nxt[2])
		{
			$desc=split('\.',$nxt[2],2);
			if($desc[0])	$tmpl->AddText($desc[0]);
			else		$tmpl->AddText($nxt[2]);
		}
		$tmpl->AddText("</div></div>");
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
	$basket_img="/skins/".$CONFIG['site']['skin']."/basket16.png";
	while($nxt=mysql_fetch_assoc($res))
	{
		$nal=$this->GetCountInfo($nxt['count'], @$nxt['tranit']);
		$link=$this->GetProductLink($nxt['id'], $nxt['name']);
		$cce='';
		$dcc=strtotime($nxt['cost_date']);
		if($dcc<(time()-60*60*24*30*6)) $cce="style='color:#888'";
		$cost=GetCostPos($nxt['id'], $this->cost_id);
		@$tmpl->AddText("<tr class='lin$cc'><td><a href='$link'>{$nxt['name']}</a>
		<td>{$nxt['proizv']}<td>$nal<td $cce>$cost
		<td><a href='/vitrina.php?mode=korz_add&amp;p={$nxt['id']}&amp;cnt=1' onclick=\"return ShowPopupWin('/vitrina.php?mode=korz_adj&amp;p={$nxt['id']}&amp;cnt=1','popwin');\" rel='nofollow'>
		<img src='$basket_img' alt='В корзину!'></a></tr>");
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
		$nal=$this->GetCountInfo($nxt['count'], $nxt['transit_cnt']);
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
		else
		{
			if(file_exists($CONFIG['site']['location'].'/skins/'.$CONFIG['site']['skin'].'/no_photo.png'))
				$img_url='/skins/'.$CONFIG['site']['skin'].'/no_photo.png';
			else	$img_url='/img/no_photo.png';
			$img="<img src='$img_url' alt='no photo' style='float: left; margin-right: 10px; width: 135px;' alt='no photo'>";
		}
		$desc=$nxt['desc'];
		if(strpos($desc,'.')!==false)
		{
			$desc=explode('.',$desc,2);
			$desc=$desc[0];
		}

		$tmpl->AddText("<div class='pitem'>
		<a href='$link'>$img</a>
		<a href='$link'>{$nxt['name']}</a><br>
		<b>Код:</b> {$nxt['vc']}<br>
		<b>Цена:</b> $cost руб. / {$nxt['units']}<br>
		<b>Производитель:</b> {$nxt['proizv']}<br>
		<b>Кол-во:</b> $nal<br>
		<a href='/vitrina.php?mode=korz_add&amp;p={$nxt['id']}&amp;cnt=1' onclick=\"return ShowPopupWin('/vitrina.php?mode=korz_adj&amp;p={$nxt['id']}&amp;cnt=1','popwin');\" rel='nofollow'>В корзину!</a>
		</div>");

		$i++;
		$cc=1-$cc;
		if($i>=$lim)	break;
	}
	$tmpl->AddText("<div class='clear'></div>");
}
/// Подробная таблица товаров
protected function TovList_ExTable($res, $lim)
{
	global $tmpl, $CONFIG, $c_cena_id;
	$tmpl->AddText("<table width='100%' cellspacing='0' border='0' class='list'><tr class='title'><th>Наименование<th>Производитель<th>Наличие<th>Розничная цена <th>d, мм<th>D, мм<th>B, мм<th>m, кг<th>Купить</tr>");
	$cc=0;
	$cl="lin0";
	$basket_img="/skins/".$CONFIG['site']['skin']."/basket16.png";
	while($nxt=mysql_fetch_array($res))
	{
		$nal=$this->GetCountInfo($nxt['count'], $nxt['transit_cnt']);
		$link=$this->GetProductLink($nxt['id'], $nxt['name']);
		$cce='';
		$dcc=strtotime($nxt['cost_date']);
		if($dcc<(time()-60*60*24*30*6)) $cce="style='color:#888'";
		$cost=GetCostPos($nxt['id'], $this->cost_id);
		$tmpl->AddText("<tr class='lin$cc'><td><a href='$link'>{$nxt['name']}</a><td>{$nxt['proizv']}<td>$nal
		<td $cce>$cost<td>{$nxt['d_int']}<td>{$nxt['d_ext']}<td>{$nxt['size']}<td>{$nxt['mass']}<td>
		<a href='/vitrina.php?mode=korz_add&amp;p={$nxt['id']}&amp;cnt=1' onclick=\"return ShowPopupWin('/vitrina.php?mode=korz_adj&amp;p={$nxt['id']}&amp;cnt=1','popwin');\" rel='nofollow'><img src='$basket_img' alt='В корзину!'></a>");
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
	if(@$_SESSION['uid'])
	{
		$up=getUserProfile($_SESSION['uid']);
		$str='Товар будет зарезервирован для Вас на 3 рабочих дня.';
		$email_field='';
	}
	else
	{
		$up=getUserProfile(-1);	// Пустой профиль
		$str='<b>Для незарегистрированных пользователей наличие товара на складе не гарантируется.</b>';
		$email_field="e-mail:<br>
		<input type='text' name='email' value=''><br>
		Необходимо заполнить телефон или e-mail<br><br>";
	}

	if(isset($_REQUEST['cwarn']))	$tmpl->msg("Необходимо заполнить e-mail или контактный телефон!","err");

	if(@$up['main']['reg_phone'])	$phone=substr($up['main']['reg_phone'],2);
	else				$phone='';

	$tmpl->AddText("
	<h4>Для оформления заказа требуется следующая информация</h4>
	<form action='/vitrina.php' method='post'>
	<input type='hidden' name='mode' value='makebuy'>
	<div>
	Фамилия И.О.<br>
	<input type='text' name='rname' value='".@$up['main']['real_name']."'><br>
	Мобильный телефон: <span id='phone_num'></span><br>
	<small>Российский, 10 цифр, без +7 или 8</small>
	<br>
	+7<input type='text' name='phone' value='$phone' maxlength='10' placeholder='Номер' id='phone'><br>
	<br>

	$email_field");
	if(is_array($CONFIG['payments']['types']))
	{
		$tmpl->AddText("<br>Способ оплаты:<br>");
		foreach($CONFIG['payments']['types'] as $type => $val)
		{
			if(!$val)	continue;
			if($type==@$CONFIG['payments']['default'])	$checked=' checked';
			else						$checked='';
			switch($type)
			{
				case 'cash':	$s="<label><input type='radio' name='pay_type' value='$type' id='soplat_nal'$checked>Наличный расчет.
				<b>Только самовывоз</b>, расчет при отгрузке. $str</label><br>";
						break;
				case 'bank':	$s="<label><input type='radio' name='pay_type' value='$type'$checked>Безналичный банкосвкий перевод.
				<b>Дольше</b> предыдущего - обработка заказа начнётся <b>только после поступления денег</b> на наш счёт (занимает 1-2 дня). После оформления заказа Вы сможете распечатать счёт для оплаты.</label><br>";
						break;
				case 'wmr':	$s="<label><input type='radio' name='pay_type' value='$type'$checked>Webmoney WMR.
						<b>Cамый быстрый</b> способ получить Ваш заказ. <b>Заказ поступит в обработку сразу</b> после оплаты.</b></label><br>";
						break;
				case 'card_o':	$s="<label><input type='radio' name='pay_type' value='$type'$checked>Платёжной картой
						<b>VISA, MasterCard</b> на сайте. Обработка заказа начнётся после подтверждения оплаты банком (обычно сразу после оплаты).</label><br>";
						break;
				case 'card_t':	$s="<label><input type='radio' name='pay_type' value='$type'$checked>Платёжной картой
						<b>VISA, MasterCard</b> при получении товара. С вами свяжутся и обсудят условия.</label><br>";
						break;
				case 'credit_brs':	$s="<label><input type='radio' name='pay_type' value='$type'$checked>Онлайн-кредит в банке &quot;Русский стандарт&quot; за 5 минут</label><br>";
						break;
				default:	$s='';
			}
			$tmpl->AddText($s);
		}
	}

	$tmpl->AddText("<label><input type='checkbox' name='delivery' id='delivery' value='1'>Нужна доставка</label><br>
	<div id='delivery_fields'>
	Желаемые дата и время доставки:<br>
	<input type='text' name='delivery_date'><br>
	<br>Адрес доставки:<br>
	<textarea name='adres' rows='5' cols='80'>".@$up['main']['real_address']."</textarea><br>
	</div>



	Другая информация:<br>
	<textarea name='dop' rows='5' cols='80'>".@$up['dop']['dop_info']."</textarea><br>
	<button type='submit'>Оформить заказ</button>
	</div>
	</form>
	<script>

	var delivery=document.getElementById('delivery')
	var delivery_fields=document.getElementById('delivery_fields')
	function deliveryCheck()
	{
		if(delivery.checked)
			delivery_fields.style.display='block';
		else	delivery_fields.style.display='none';
	}
	delivery.onclick=deliveryCheck;
	deliveryCheck();

	</script>
	");
}

/// Сделать покупку
protected function MakeBuy()
{
	global $tmpl, $CONFIG, $uid, $xmppclient;
	$pay_type=@$_REQUEST['pay_type'];
	switch($pay_type)
	{
		case 'bank':
		case 'cash':
		case 'card_o':
		case 'card_t':
		case 'credit_brs':
		case 'wmr':	break;
		default:	$pay_type='';
	}
	$rname=@$_REQUEST['rname'];
	$rname_sql=mysql_real_escape_string($rname);
	$delivery=$_SESSION['basket']['delivery_type'];
	$adres_sql=mysql_real_escape_string($_SESSION['basket']['delivery_address']);
	$delivery_date=mysql_real_escape_string($_SESSION['basket']['delivery_date']);
	$email=@$_REQUEST['email'];
	$email_sql=mysql_real_escape_string($email);
	$comment=@$_REQUEST['dop'];
	$comment_sql=mysql_real_escape_string($comment);
	$agent=1;

	if(@$_REQUEST['phone'])
	{
		$tel='+7'.intval(@$_REQUEST['phone']);
	}
	else	$tel='';
	
	if(@$_SESSION['uid'])
	{
		mysql_query("UPDATE `users` SET `real_name`='$rname_sql', `real_address`='$adres_sql' WHERE `id`='$uid'");
		if(mysql_errno())	throw new MysqlException("Не удалось обновить основные данные пользователя!");
		mysql_query("REPLACE `users_data` (`uid`, `param`, `value`) VALUES ('$uid', 'dop_info', '$comment_sql') ");
		if(mysql_errno())	throw new MysqlException("Не удалось обновить дополнительные данные пользователя!");
		// Получить ID агента		
		$res=mysql_query("SELECT `name`, `reg_email`, `reg_date`, `reg_email_subscribe`, `real_name`, `reg_phone`, `real_address`, `agent_id` FROM `users` WHERE `id`='{$_SESSION['uid']}'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить основные данные пользователя!");
		$user_data=mysql_fetch_assoc($res);
		$agent=$user_data['agent_id'];
		settype($agent,'int');
		if($agent<1)	$agent=1;
	}
	else if(!$tel && !$email)
	{
		header("Location: /vitrina.php?mode=buyform&step=1&cwarn=1");
		return;
	}

	if($_SESSION['basket']['cnt'])
	{
		if(!isset($CONFIG['site']['vitrina_subtype']))		$subtype="site";
		else $subtype=$CONFIG['site']['vitrina_subtype'];
		
		$tm=time();
		$altnum=GetNextAltNum(3,$subtype,0,date('Y-m-d'),$CONFIG['site']['default_firm']);
		$ip=getenv("REMOTE_ADDR");
		if(isset($CONFIG['site']['default_bank']))	$bank=$CONFIG['site']['default_bank'];
		else
		{
			$res=mysql_query("SELECT `num` FROM `doc_kassa` WHERE `ids`='bank' AND `firm_id`='{$CONFIG['site']['default_firm']}'");
			if(mysql_errno())	throw new MysqlException("Не удалось определить банк");
			if(mysql_num_rows($res)<1)	throw new Exception("Не найден банк выбранной организации");
			$bank=mysql_result($res,0,0);
		}
		$res=mysql_query("INSERT INTO doc_list (`type`,`agent`,`date`,`sklad`,`user`,`nds`,`altnum`,`subtype`,`comment`,`firm_id`,`bank`)
		VALUES ('3','$agent','$tm','1','$uid','1','$altnum','$subtype','$comment_sql','{$CONFIG['site']['default_firm']}','$bank')");

		if(mysql_errno())	throw new MysqlException("Не удалось создать документ заявки");
		$doc=mysql_insert_id();
		mysql_query("REPLACE INTO `doc_dopdata` (`doc`, `param`, `value`) VALUES ('$doc', 'cena', '{$this->cost_id}'), ('$doc', 'ishop', '1'),  ('$doc', 'buyer_email', '$email_sql'), ('$doc', 'buyer_phone', '$tel'), ('$doc', 'buyer_rname', '$rname_sql'), ('$doc', 'buyer_ip', '$ip'), ('$doc', 'delivery', '$delivery'), ('$doc', 'delivery_date', '$delivery_date'), ('$doc', 'delivery_address', '$adres_sql'), ('$doc', 'pay_type', '$pay_type') ");
		if(mysql_errno())	throw new MysqlException("Не удалось установить цену документа");
		$zakaz_items=$admin_items=$lock='';
		foreach($_SESSION['basket']['cnt'] as $item => $cnt)
		{
			$cena=GetCostPos($item, $this->cost_id);
			if(isset($_SESSION['basket']['comments'][$item]))
				$comm=mysql_real_escape_string($_SESSION['basket']['comments'][$item]);	else $comm='';
			mysql_query("INSERT INTO `doc_list_pos` (`doc`,`tovar`,`cnt`,`cost`,`comm`) VALUES ('$doc','$item','$cnt','$cena','$comm')");
			if(mysql_errno())	throw new MysqlException("Не удалось добавить товар в заказ");
			$res=mysql_query("SELECT `doc_base`.`id`, `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`vc`, `doc_base`.`cost`, `class_unit`.`rus_name1` FROM `doc_base`
			LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
			LEFT JOIN `class_unit` ON `class_unit`.`id`=`doc_base`.`unit`
			WHERE `doc_base`.`id`='$item'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить информацию о товаре");
			$tov_info=mysql_fetch_row($res);
			$zakaz_items.="$tov_info[1] $tov_info[2]/$tov_info[3] ($tov_info[4]), $cnt $tov_info[6] - $cena руб.\n";
			$admin_items.="$tov_info[1] $tov_info[2]/$tov_info[3] ($tov_info[4]), $cnt $tov_info[6] - $cena руб. (базовая - $tov_info[5]р.)\n";
			
			if(@$CONFIG['site']['vitrina_cntlock'] || @$CONFIG['site']['vitrina_pricelock'])
			{
				$res=mysql_query("SELECT `id`, `name`, `cost_date` FROM `doc_base` WHERE `id`='$item'");
				if(mysql_errno())	throw new MysqlException("Не удалось получить список товаров!");
				$nx=mysql_fetch_array($res);
				
				if(@$CONFIG['site']['vitrina_cntlock'])
				{
					if(isset($CONFIG['site']['vitrina_sklad']))
					{
						$sklad_id=round($CONFIG['site']['vitrina_sklad']);
						$res=mysql_query("SELECT `doc_base_cnt`.`cnt` FROM `doc_base_cnt` WHERE `id`='$item' AND `sklad`='$sklad_id'");
						$sklad_cnt=mysql_result($res,0,0)-DocRezerv($item);
					}
					else
					{
						$res=mysql_query("SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `id`='$item'");
						$sklad_cnt=mysql_result($res,0,0)-DocRezerv($item);
					}
					if($cnt>$sklad_cnt)
					{
						$lock=1;
						$lock_mark=1;
					}
				}
				if(@$CONFIG['site']['vitrina_pricelock'])
				{
					if(strtotime($nx['cost_date'])<(time()-60*60*24*30*6))
					{
						$lock=1;
						$lock_mark=1;
					}
				}
			}
		}
		if($_SESSION['basket']['delivery_type'])
		{
			$res=mysql_query("SELECT `service_id` FROM `delivery_types` WHERE `id`='{$_SESSION['basket']['delivery_type']}'");
			if(!$res)	throw new MysqlException("Не удалось запростить типы доставки");
			list($d_service_id)=mysql_fetch_row($res);
			$res=mysql_query("SELECT `price` FROM `delivery_regions` WHERE `id`='{$_SESSION['basket']['delivery_region']}'");
			if(!$res)	throw new MysqlException("Не удалось запростить регионы доставки");
			list($d_price)=mysql_fetch_row($res);
			mysql_query("INSERT INTO `doc_list_pos` (`doc`,`tovar`,`cnt`,`cost`,`comm`) VALUES ('$doc','$d_service_id','1','$d_price','')");
			if(mysql_errno())	throw new MysqlException("Не удалось добавить услугу в заказ");
			$res=mysql_query("SELECT `doc_base`.`id`, `doc_group`.`printname`, `doc_base`.`name` FROM `doc_base`
			LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
			LEFT JOIN `class_unit` ON `class_unit`.`id`=`doc_base`.`unit`
			WHERE `doc_base`.`id`='$d_service_id'");
			$zakaz_items.="$tov_info[1] $tov_info[2] - $cena руб.\n";
			$admin_items.="$tov_info[1] $tov_info[2] - $cena руб.\n";
		}
		$zakaz_sum=DocSumUpdate($doc);
		$_SESSION['order_id']=$doc;

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

		if(@$_SESSION['uid'])
		{
			$user_msg="Доброго времени суток, {$user_data['name']}!\nНа сайте {$CONFIG['site']['name']} на Ваше имя оформлен заказ на сумму $zakaz_sum рублей\nЗаказано:\n";
			$email=$user_data['reg_email'];
		}
		else $user_msg="Доброго времени суток, $rname!\nКто-то (возможно, вы) при оформлении заказа на сайте {$CONFIG['site']['name']}, указал Ваш адрес электронной почты.\nЕсли Вы не оформляли заказ, просто проигнорируйте это письмо.\n Номер заказа: $doc/$altnum\nЗаказ на сумму $zakaz_sum рублей\nЗаказано:\n";
		$user_msg.="--------------------------------------\n$zakaz_items\n--------------------------------------\n";
		$user_msg.="\n\n\nСообщение отправлено роботом. Не отвечайте на это письмо.";

		if($email)
			mailto($email,"Message from {$CONFIG['site']['name']}", $user_msg);

		$tmpl->SetText("<h1 id='page-title'>Заказ оформлен</h1>");
		if(!$lock)
		{
			switch($pay_type)
			{
				case 'bank':
				case 'card_o':
				case 'credit_brs':
					$tmpl->AddText("<p>Заказ оформлен. Теперь вы можете оплатить его! <a href='/vitrina.php?mode=pay'>Перейти к оплате</a></p>");
				break;
				default:
					$tmpl->msg("Ваш заказ оформлен! Номер заказа: $doc/$altnum. Запомните или запишите его. С вами свяжутся в ближайшее время для уточнения деталей!");
			}
		}
		else $tmpl->msg("Ваш заказ оформлен! Номер заказа: $doc/$altnum. Запомните или запишите его. С вами свяжутся в ближайшее время для уточнения цены и наличия товара! Оплатить заказ будет возможно после его подтверждения оператором.");
		unset($_SESSION['basket']);
	}
	else $tmpl->msg("Ваша корзина пуста! Вы не можете оформить заказ! Быть может, Вы его уже оформили?","err");
}

protected function Payment()
{
	global $tmpl, $CONFIG;
	$order_id=$_SESSION['order_id'];
	settype($order_id,'int');
	$res=mysql_query("SELECT `doc_list`.`id` FROM `doc_list`
	WHERE `doc_list`.`p_doc`='$order_id' AND (`doc_list`.`type`='4' OR `doc_list`.`type`='6')");
	if(mysql_errno())	throw new MysqlException("Не удалось получить данные оплат");
	if(mysql_num_rows($res))	$tmpl->msg("Этот заказ уже оплачен!");
	else
	{
		$res=mysql_query("SELECT `doc_list`.`id`, `dd_pt`.`value` AS `pay_type` FROM `doc_list`
		LEFT JOIN `doc_dopdata` AS `dd_pt` ON `dd_pt`.`doc`=`doc_list`.`id` AND `dd_pt`.`param`='pay_type'
		WHERE `doc_list`.`id`='$order_id' AND `doc_list`.`type`='3'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить данные заказа");	
		
		$order_info=mysql_fetch_assoc($res);
		if($order_info['pay_type']=='card_o')
		{
			$init_url="https://test.pps.gazprombank.ru:443/payment/start.wsm?lang=ru&merch_id={$CONFIG['gpb']['merch_id']}&back_url_s=http://{$CONFIG['site']['name']}/gpb_pay_success.php&back_url_f=http://{$CONFIG['site']['name']}/gpb_pay_failed.php&o.order_id=$order_id";
			header("Location: $init_url");
			exit();
		}
		else if($order_info['pay_type']=='credit_brs')
		{
			$res=mysql_query("SELECT `doc_list_pos`.`tovar`, CONCAT(`doc_group`.`printname`, ' ', `doc_base`.`name`) AS `name`, `doc_list_pos`.`cnt`
			FROM `doc_list_pos`
			INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
			INNER JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
			WHERE `doc_list_pos`.`doc`=$order_id");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список товаров");
			$pos_line='';
			$cnt=0;
			while($line=mysql_fetch_assoc($res))
			{
				$cnt++;
				$cena=GetCostPos($line['tovar'], $this->cost_id);
				$pos_line.="&TC_$cnt={$line['cnt']}&TPr_$cnt=$cena&TName_$cnt=".urlencode($line['name']);
			}
			$url="{$CONFIG['credit_brs']['address']}?idTpl={$CONFIG['credit_brs']['id_tpl']}&TTName={$CONFIG['site']['name']}&Order=$order_id&TCount={$cnt}{$pos_line}";
			header("Location: $url");
			exit();
		}
		else if($order_info['pay_type']=='bank')
		{
			$tmpl->msg("Номер счёта: $doc/$altnum. Теперь Вам необходимо <a href='/vitrina.php?mode=print_schet'>получить счёт</a>, и оплатить его. После оплаты счёта Ваш заказ поступит в обработку.");
			$tmpl->AddText("<a href='?mode=print_schet'>Получить счёт</a>");
		}
		else throw new Exception("Данный тип оплаты ({$order_info['pay_type']}) не поддерживается!");
	}
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
			$tmpl->AddText(" <a href='".$this->GetGroupLink($group, $i)."'>&gt;&gt;</a> ");
		}	else	$tmpl->AddText(" &gt;&gt; ");
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
protected function GetGroupLink($group, $page=1, $alt_param='')
{
	global $CONFIG;
	if($CONFIG['site']['recode_enable'])	return "/vitrina/ig/$page/$group.html".($alt_param?"?$alt_param":'');
	else					return "/vitrina.php?mode=group&amp;g=$group".($page?"&amp;p=$page":'').($alt_param?"&amp;$alt_param":'');
}
/// Получить ссылку на товар с заданным ID
protected function GetProductLink($product, $name, $alt_param='')
{
	global $CONFIG;
	if($CONFIG['site']['recode_enable'])	return "/vitrina/ip/$product.html".($alt_param?"?$alt_param":'');
	else					return "/vitrina.php?mode=product&amp;p=$product".($alt_param?"&amp;$alt_param":'');
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
	else	return round($count).($tranzit?('('.$tranzit.')'):'');
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


