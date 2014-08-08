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
include_once("include/doc.core.php");
include_once("include/imgresizer.php");

/// Класс витрины интернет-магазина
class Vitrina
{
	var $is_pc_init;
/// Конструктор
function __construct() {
	global $tmpl;
	$tmpl->setTitle("Интернет - витрина");
}

/// Проверка и исполнение recode-запроса
function ProbeRecode() {
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

/// Исполнение заданной функции
/// @param mode Название функции витрины
function ExecMode($mode)
{
	global $tmpl, $db;
	$p = rcvint('p');
	$g = rcvint('g');
	if ($mode == '') { // Верхний уровень. Никакая группа не выбрана.
		$this->TopGroup();
	} 
	else if ($mode == 'group') {
		$this->ViewGroup($g, $p);
	} 
	else if ($mode == 'product') {
		$this->ProductCard($p);
	} 
	else if ($mode == 'basket') {
		$this->Basket();
	} else if ($mode == 'block') {
		$this->ViewBlock($_REQUEST['type']);
	} 
	else if ($mode == 'korz_add') {
		$cnt = rcvint('cnt');
		if ($p) {
			$basket = Models\Basket::getInstance();
			$basket->setItem($p, $cnt);
			$basket->save();
			$tmpl->ajax = 1;
			
			if(isset($_REQUEST['j']) || isset($_REQUEST['json'])) {
				$basket_cnt = $basket->getCount();
				$sum = 0;
				if($basket_cnt) {
					$pc = $this->priceCalcInit();
					$basket_items = $basket->getItems();
					
					foreach ($basket_items as $item) {
						$price = $pc->getPosAutoPriceValue($item['pos_id'], $item['cnt']);
						$sum += $price * $cnt;
					}
				}
				
				
				if(isset($_REQUEST['json']))
					echo json_encode(array('cnt'=>$basket_cnt, 'sum'=>$sum), JSON_UNESCAPED_UNICODE);
				else if(isset($_REQUEST['j'])) {
					if($basket_cnt)
						echo "Товаров: $basket_cnt на $sum руб.";
					else	echo "Корзина пуста";
				}
			}
			else {
				if (getenv("HTTP_REFERER"))
					header('Location: ' . getenv("HTTP_REFERER"));
				$tmpl->msg("Товар добавлен в корзину!", "info", "<a class='urllink' href='/vitrina.php?mode=basket'>Ваша корзина</a>");
			}
		}
		else throw new NotFoundException("ID товара не задан!");
	} 
	else if ($mode == 'korz_adj') {
		$tmpl->ajax = 1;
		$cnt = rcvint('cnt');
		if ($p) {
			$basket = Models\Basket::getInstance();
			$basket->setItem($p, $cnt);
			$basket->save();
			$tmpl->addContent("Товар добавлен в корзину!<br><a class='urllink' href='/vitrina.php?mode=basket'>Ваша корзина</a>");
		}
		else throw new NotFoundException("Номер товара не задан!");
	}
	else if ($mode == 'korz_del') {
		$basket = Models\Basket::getInstance();
		$basket->removeItem($p);
		$basket->save();
		$tmpl->msg("Товар убран из корзины!", "info", "<a class='urllink' href='/vitrina.php?mode=basket'>Ваша корзина</a>");
	}
	else if ($mode == 'korz_clear') {
		$basket = Models\Basket::getInstance();
		$basket->clear();
		$basket->save();
		$tmpl->msg("Корзина очищена!", "info", "<a class='urllink' href='/vitrina.php'>Вернутья на витрину</a>");
	}
	else if ($mode == 'basket_submit') {
		$tmpl->ajax = 1;
		$basket = Models\Basket::getInstance();
		
		if ($basket->getCount()) {
			$basket_items = $basket->getItems();
			foreach ($basket_items as $item) {
				$new_cnt = request('cnt' . $item['pos_id']);
				if ($new_cnt <= 0)
					$basket->removeItem($item['pos_id']);
				else	$basket->setItem($item['pos_id'], round($new_cnt), request('comm'.$item['pos_id']));
			}
			$basket->save();
		}
		
		if (@$_REQUEST['button'] == 'recalc') {
			if (getenv("HTTP_REFERER"))
				header('Location: ' . getenv("HTTP_REFERER"));
			else	header('Location: /vitrina.php?mode=basket');
		}
		else	header('Location: /vitrina.php?mode=buy');
	}
	else if ($mode == 'buy')
		$this->Buy();
	else if ($mode == 'delivery')
		$this->Delivery();
	else if ($mode == 'buyform')
		$this->BuyMakeForm();
	else if ($mode == 'makebuy')
		$this->MakeBuy();
	else if ($mode == 'pay')
		$this->Payment();
	else if ($mode == 'print_schet') {
		include_once("include/doc.nulltype.php");
		$doc = $_SESSION['order_id'];
		if ($doc) {
			$document = AutoDocument($doc);
			$document->PrintForm('schet');
		}
		else	$tmpl->msg("Вы ещё не оформили заказ! Вернитесь и оформите!");
	}
	else if ($mode == 'comm_add') {
		require_once("include/comments.inc.php");
		if (!@$_SESSION['uid']) {
			if ((strtoupper($_SESSION['captcha_keystring']) != strtoupper(@$_REQUEST['img'])) || ($_SESSION['captcha_keystring'] == '')) {
				unset($_SESSION['captcha_keystring']);
				throw new Exception("Защитный код введён неверно!");
			}
			unset($_SESSION['captcha_keystring']);
			$cd = new CommentDispatcher('product', $p);
			$cd->WriteComment(@$_REQUEST['text'], @$_REQUEST['rate'], @$_REQUEST['autor_name'], @$_REQUEST['autor_email']);
		}
		else {
			$cd = new CommentDispatcher('product', $p);
			$cd->WriteComment(@$_REQUEST['text'], @$_REQUEST['rate']);
		}
		$tmpl->msg("Коментарий добавлен!", "ok");
	}
	else throw new NotFoundException("Неверная ссылка!");
}

// ======== Приватные функции ========================
// -------- Основные функции -------------------------
/// Отобразить корень витрины
protected function TopGroup() {
	global $tmpl, $CONFIG;
	$tmpl->addContent("<h1 id='page-title'>Витрина</h1>");
	if($CONFIG['site']['vitrina_glstyle']=='item')	$this->GroupList_ItemStyle(0);
	else						$this->GroupList_ImageStyle(0);
}

/// Отобразить список групп / подгрупп
/// @param group ID группы, которую нужно отобразить
/// @param page Номер страницы отображаемой группы
protected function ViewGroup($group, $page)
{
	global $tmpl, $CONFIG, $wikiparser, $db;
	settype($group,'int');
	settype($page,'int');
	$res=$db->query("SELECT `name`, `pid`, `desc`, `title_tag`, `meta_keywords`, `meta_description` FROM `doc_group` WHERE `id`='$group' AND `hidelevel`='0'");
	if(!$res->num_rows)	throw new NotFoundException('Группа не найдена! Воспользуйтесь каталогом.');

	$group_data=$res->fetch_assoc();
	$group_name_html=html_out($group_data['name']);

	if(file_exists("{$CONFIG['site']['var_data_fs']}/category/$group.jpg"))
		$tmpl->addContent("<div style='float: right; margin: 35px 35px 20px 20px;'><img src='{$CONFIG['site']['var_data_web']}/category/$group.jpg' alt='$group_name_html'></div>");

	if($group_data['title_tag'])	$title=html_out($group_data['title_tag']);
	else				$title=$group_name_html.', цены, купить';
	if($page>1)	$title.=" - стр.$page";
	$tmpl->setTitle($title);
	if($group_data['meta_keywords'])$tmpl->setMetaKeywords( html_out($group_data['meta_keywords']) );
	else
	{
		$k1=array('купить цены','продажа цены','отзывы купить','продажа отзывы','купить недорого');
		$meta_key=$group_name_html.' '.$k1[rand(0,count($k1)-1)].' интернет-магазин '.$CONFIG['site']['display_name'];
		$tmpl->setMetaKeywords($meta_key);
	}

	if($group_data['meta_description'])	$tmpl->setMetaDescription( html_out($group_data['meta_description']) );
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
		$meta_desc=$group_name_html.' - '.$d1[rand(0,count($d1)-1)].' в интернет-магазине '.$CONFIG['site']['display_name'].' по '.$d2[rand(0,count($d2)-1)].' '.$d3[rand(0,count($d3)-1)].'. '.$d4[rand(0,count($d4)-1)].' '.$d5[rand(0,count($d5)-1)].' '.$d6[rand(0,count($d6)-1)].'. '.$d7[rand(0,count($d7)-1)].' '.$d8[rand(0,count($d8)-1)].'.';
		$tmpl->setMetaDescription($meta_desc);
	}

	$h1=$group_name_html;
	if($page>1)	$h1.=" - стр.$page";
	$tmpl->addContent("<h1 id='page-title'>$h1</h1>");
	$tmpl->addContent("<div class='breadcrumbs'>".$this->GetVitPath($group_data['pid'])."</div>");
	if($group_data['desc'])
	{
		$text=$wikiparser->parse($group_data['desc']);
		$tmpl->addContent("<div class='group-description'>$text</div><br>");
	}
	$tmpl->addContent("<div style='clear: right'></div>");
	if($CONFIG['site']['vitrina_glstyle']=='item')	$this->GroupList_ItemStyle($group);
	else						$this->GroupList_ImageStyle($group);

	$this->ProductList($group, $page);
}

/// Список товаров в группе
/// @param group ID группы, из которой нужно отбразить товары
/// @param page Номер страницы отображаемой группы
protected function ProductList($group, $page) {
	global $tmpl, $CONFIG, $db;
	settype($group,'int');
	settype($page,'int');

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
	`doc_base`.`transit_cnt`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base`.`proizv`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`vc`, `doc_base`.`buy_time`, `doc_base`.`create_time`, `doc_base`.`bulkcnt`, `doc_base`.`mult`
	FROM `doc_base`
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
	LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
	LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
	WHERE `doc_base`.`group`='$group' AND `doc_base`.`hidden`='0' $sql_photo_only
	ORDER BY $sql_order";

	$res=$db->query($sql);
	$lim=intval($CONFIG['site']['vitrina_limit']);
	if($lim==0)	$lim=100;

        if($res->num_rows)
        {
		if($page<1 || $lim*($page-1)>$res->num_rows)
		{
			header("Location: ".(empty($_SERVER['HTTPS'])?"http":"https")."://".$_SERVER['HTTP_HOST'].$this->GetGroupLink($group),false,301);
			exit();
		}
		$this->OrderAndViewBar($group,$page,$order,$view);

		$this->PageBar($group, $res->num_rows, $lim, $page);
		if( ($lim < $res->num_rows) && $page )
			$res->data_seek($lim*($page-1));
		
		if($view=='i')			$this->TovList_ImageList($res, $lim);
		else if($view=='t')		$this->TovList_ExTable($res, $lim);
		else				$this->TovList_SimpleTable($res, $lim);

		$this->PageBar($group, $res->num_rows, $lim, $page);
		if(@$CONFIG['site']['grey_price_days'])
			$tmpl->addContent("<span style='color:#888'>Серая цена</span> требует уточнения<br>");
	}
        elseif(isset ($page) && $page!=1)
        {
                header("Location: ".(empty($_SERVER['HTTPS'])?"http":"https")."://".$_SERVER['HTTP_HOST'].$this->GetGroupLink($group),false,301);
		exit(); ;
        }
}

/// Отобразить блок товаров, выбранных по признаку, основанному на типе блока
/// @param block Тип отображаемого блока: stock - Распродажа, popular - Популярные товары, new - Новинки, transit - Товарв пути
protected function ViewBlock($block)
{
	global $tmpl, $CONFIG, $db, $wikiparser;
	$cnt_where=@$CONFIG['site']['vitrina_sklad']?(" AND `doc_base_cnt`.`sklad`=".intval($CONFIG['site']['vitrina_sklad'])." "):'';
	
	// Определение типа блока
	if($block=='stock')
	{
		$sql="SELECT `doc_base`.`id`, `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`cost_date`, `doc_base`.`cost`,
		( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` $cnt_where GROUP BY `doc_base`.`id`) AS `count`,
		`doc_base`.`transit_cnt`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base`.`proizv`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`vc`, `doc_base`.`buy_time`, `doc_base`.`create_time`, `doc_base`.`bulkcnt`, `doc_base`.`mult`
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
		`doc_base`.`transit_cnt`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base`.`proizv`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`vc`, `doc_base`.`buy_time`, `doc_base`.`create_time`, `doc_base`.`bulkcnt`, `doc_base`.`mult`
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
		`doc_base`.`transit_cnt`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base`.`proizv`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`vc`, `doc_base`.`buy_time`, `doc_base`.`create_time`, `doc_base`.`bulkcnt`, `doc_base`.`mult`
		FROM `doc_base`
		INNER JOIN `doc_group` ON `doc_group`.`id`= `doc_base`.`group` AND `doc_group`.`hidelevel`='0'
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
		LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_base`.`hidden`='0' AND `doc_base`.`buy_time`>='$new_time'
		ORDER BY `doc_base`.`buy_time` DESC
		LIMIT 24";
		$head='Новинки';
	}
	else if($block=='new_ns')
	{
		if($CONFIG['site']['vitrina_newtime'])	$new_time=date("Y-m-d H:i:s",time()-60*60*24*$CONFIG['site']['vitrina_newtime']);
		else					$new_time=date("Y-m-d H:i:s",time()-60*60*24*180);
		$sql="SELECT `doc_base`.`id`, `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`cost_date`, `doc_base`.`cost`,
		( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` $cnt_where GROUP BY `doc_base`.`id`) AS `count`,
		`doc_base`.`transit_cnt`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base`.`proizv`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`vc`, `doc_base`.`buy_time`, `doc_base`.`create_time`, `doc_base`.`bulkcnt`, `doc_base`.`mult`
		FROM `doc_base`
		INNER JOIN `doc_group` ON `doc_group`.`id`= `doc_base`.`group` AND `doc_group`.`hidelevel`='0'
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
		LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_base`.`hidden`='0' AND `doc_base`.`create_time`>='$new_time' AND `doc_base`.`buy_time`='1970-01-01' 
			AND ( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` $cnt_where GROUP BY `doc_base`.`id`)=0
		ORDER BY `doc_base`.`buy_time` DESC
		LIMIT 24";
		$head='Новые образцы';
	}
	else if($block=='best')
	{
		$sql = "SELECT `doc_base`.`id`, `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`cost_date`, `doc_base`.`cost`,
		( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` $cnt_where GROUP BY `doc_base`.`id`) AS `count`,
		`doc_base`.`transit_cnt`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base`.`proizv`,
		`doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`vc`, `doc_base`.`buy_time`,
		`doc_base`.`create_time`, `doc_base`.`bulkcnt`, `doc_base`.`mult`
		FROM `comments`
		INNER JOIN `doc_base` ON `doc_base`.`id`=`comments`.`object_id` AND `comments`.`object_name`='product'
		INNER JOIN `doc_group` ON `doc_group`.`id`= `doc_base`.`group` AND `doc_group`.`hidelevel`='0'
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`comments`.`object_id`
		LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`comments`.`object_id` AND `doc_base_img`.`default`='1'
		LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `comments`.`rate`>0 AND `doc_base`.`hidden`='0'
		GROUP BY `comments`.`object_id`
		ORDER BY AVG(`comments`.`rate`)*3+COUNT(`comments`.`rate`)  DESC
		LIMIT 24";
		
		$head='Товары с лучшим рейтингом';
	}
	else if($block=='transit')
	{
		$sql="SELECT `doc_base`.`id`, `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`cost_date`, `doc_base`.`cost`,
		( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` $cnt_where GROUP BY `doc_base`.`id`) AS `count`,
		`doc_base`.`transit_cnt`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base`.`proizv`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`vc`, `doc_base`.`buy_time`, `doc_base`.`create_time`, `doc_base`.`bulkcnt`, `doc_base`.`mult`
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
	else	throw new NotFoundException('Блок не найден!');
	
	$page_name = $db->real_escape_string('vitrina:'.$block);
	$text = '';
	$wres = $db->query("SELECT `articles`.`name`, `a`.`name` AS `a_name`, `articles`.`date`, `articles`.`changed`, `b`.`name` AS `b_name`, `articles`.`text`, `articles`.`type`
	FROM `articles`
	LEFT JOIN `users` AS `a` ON `a`.`id`=`articles`.`autor`
	LEFT JOIN `users` AS `b` ON `b`.`id`=`articles`.`changeautor`
	WHERE `articles`.`name` = '$page_name'");
	if($nxt = $wres->fetch_assoc()) {
		$text=$nxt['text'];
		if($nxt['type']==0)	$text=strip_tags($text, '<nowiki>');
		if($nxt['type']==0 || $nxt['type']==2) {
			$text=$wikiparser->parse($text);
			if(@$wikiparser->title)
				$head = $wikiparser->title;
		}
	}
	
	$tmpl->addContent("<div class='breadcrumbs'><a href='/vitrina.php'>Главная</a> <a href='/vitrina.php'>Витрина</a> $head</div>");
	$tmpl->addContent("<h1 id='page-title'>$head</h1><div>$text</div>");
	$tmpl->SetTitle($head);

	$res=$db->query($sql);
	$lim=1000;
        if($res->num_rows)
        {
		if($CONFIG['site']['vitrina_plstyle']=='imagelist')		$view='i';
		else if($CONFIG['site']['vitrina_plstyle']=='extable')		$view='t';
		else								$view='l';

		if($view=='i')			$this->TovList_ImageList($res, $lim);
		else if($view=='t')		$this->TovList_ExTable($res, $lim);
		else				$this->TovList_SimpleTable($res, $lim);
		
		if(@$CONFIG['site']['grey_price_days'])
			$tmpl->addContent("<span style='color:#888'>Серая цена</span> требует уточнения<br>");
	}
	else $tmpl->msg("Товары в данной категории отсутствуют");
}

/// Отобразить блок ссылок смены вида отображения и сортировки предложений в группе
/// @param group	ID текущей группы
/// @param page 	Номер текущей страницы
/// @param order	Установелнная сортировка
/// @param view		Установелнный вид отображения
protected function OrderAndViewBar($group, $page, $order, $view)
{
	global $tmpl;
	$tmpl->addContent("<div class='orderviewbar'>");
	$tmpl->addContent("<div class='orderbar'>Показывать: ");
	if($view=='i')	$tmpl->addContent("<span class='selected'>Картинками</span> ");
	else		$tmpl->addContent("<span><a href='".$this->GetGroupLink($group, $page, 'view=i')."'>Картинками</a></span> ");
	if($view=='t')	$tmpl->addContent("<span class='selected'>Таблицей</span> ");
	else		$tmpl->addContent("<span><a href='".$this->GetGroupLink($group, $page, 'view=t')."'>Таблицей</a></span> ");
	if($view=='l')	$tmpl->addContent("<span class='selected'>Списком</span> ");
	else		$tmpl->addContent("<span><a href='".$this->GetGroupLink($group, $page, 'view=l')."'>Списком</a></span> ");
	if(@$_SESSION['vit_photo_only'])	$tmpl->addContent("<span class='selected'><a class='down'  href='".$this->GetGroupLink($group, $page, 'op=0')."'>Только с фото</a></span> ");
	else					$tmpl->addContent("<span><a href='".$this->GetGroupLink($group, $page, 'op=1')."'>Только с фото</a></span> ");
	$tmpl->addContent("</div>");
	$tmpl->addContent("<div class='viewbar'>Сортировать по: ");
	if($order=='n')		$tmpl->addContent("<span class='selected'><a href='".$this->GetGroupLink($group, $page, 'order=nd')."'>Названию</a></span> ");
	else if($order=='nd')	$tmpl->addContent("<span class='selected'><a class='down' href='".$this->GetGroupLink($group, $page, 'order=n')."'>Названию</a></span> ");
	else			$tmpl->addContent("<span><a href='".$this->GetGroupLink($group, $page, 'order=n')."'>Названию</a></span> ");

	if($order=='vc')	$tmpl->addContent("<span class='selected'><a href='".$this->GetGroupLink($group, $page, 'order=vcd')."'>Коду</a></span> ");
	else if($order=='vcd')	$tmpl->addContent("<span class='selected'><a href='".$this->GetGroupLink($group, $page, 'order=vc')."'>Коду</a></span> ");
	else			$tmpl->addContent("<span><a class='down' href='".$this->GetGroupLink($group, $page, 'order=vc')."'>Коду</a></span> ");

	if($order=='c')		$tmpl->addContent("<span class='selected'><a href='".$this->GetGroupLink($group, $page, 'order=cd')."'>Цене</a></span> ");
	else if($order=='cd')	$tmpl->addContent("<span class='selected'><a class='down' href='".$this->GetGroupLink($group, $page, 'order=c')."'>Цене</a></span> ");
	else		$tmpl->addContent("<span><a href='".$this->GetGroupLink($group, $page, 'order=c')."'>Цене</a></span> ");

	if($order=='s')		$tmpl->addContent("<span class='selected'><a href='".$this->GetGroupLink($group, $page, 'order=sd')."'>Наличию</a></span> ");
	else if($order=='sd')	$tmpl->addContent("<span class='selected'><a class='down' href='".$this->GetGroupLink($group, $page, 'order=s')."'>Наличию</a></span> ");
	else			$tmpl->addContent("<span><a href='".$this->GetGroupLink($group, $page, 'order=s')."'>Наличию</a></span> ");
	$tmpl->addContent("</div><div class='clear'></div>");
	$tmpl->addContent("</div>");
}

/// Отобразить карточку товара
/// @param product ID отображаемого товара/услуги
protected function ProductCard($product)
{
	global $tmpl, $CONFIG, $wikiparser, $db;
	settype($product,'int');

	$cnt_where=@$CONFIG['site']['vitrina_sklad']?(" AND `doc_base_cnt`.`sklad`=".intval($CONFIG['site']['vitrina_sklad'])." "):'';
	$res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`group`, `doc_base`.`cost`,
	`doc_base`.`proizv`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`,
	`doc_base_dop`.`analog`, ( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` $cnt_where) AS `cnt`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `doc_base_dop_type`.`name` AS `dop_name`, `class_unit`.`name` AS `units`, `doc_group`.`printname` AS `group_printname`, `doc_base`.`vc`, `doc_base`.`title_tag`, `doc_base`.`meta_description`, `doc_base`.`meta_keywords`, `doc_base`.`buy_time`, `doc_base`.`create_time`, `doc_base`.`transit_cnt`, `class_unit`.`rus_name1` AS `units_min`, `doc_base`.`cost_date`, `doc_base`.`bulkcnt`, `doc_base`.`mult`,
		`doc_base`.`mass`, `doc_base`.`analog_group`
	FROM `doc_base`
	INNER JOIN `doc_group` ON `doc_base`.`group`=`doc_group`.`id`
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	LEFT JOIN `doc_base_dop_type` ON `doc_base_dop_type`.`id`=`doc_base_dop`.`type`
	LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
	LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
	LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
	WHERE `doc_base`.`id`=$product
	ORDER BY `doc_base`.`name` ASC LIMIT 1");
	$i=0;
	$product_data=$res->fetch_assoc();
	if($product_data)
	{
		$product_name_html=html_out($product_data['group_printname'].' '.$product_data['name']);

		if($product_data['title_tag'])	$title=html_out($product_data['title_tag']);
		else				$title="$product_name_html, цены и характеристики, купить";
		$tmpl->setTitle($title);
		$base=abs(crc32($product_data['name'].$product_data['group'].$product_data['proizv'].$product_data['vc'].$product_data['desc']));
		if($product_data['meta_keywords'])	$tmpl->setMetaKeywords(html_out($product_data['meta_keywords']));
		else
		{
			$k1=array('купить','цены','характеристики','фото','выбор','каталог','описания','отзывы','продажа','описание');
			$l=count($k1);
			$i1=$base%$l;
			$base=floor($base/$l);
			$i2=$base%$l;
			$base=floor($base/$l);
			$meta_key=$product_name_html.' '.$k1[$i1].' '.$k1[$i2];
			$tmpl->setMetaKeywords($meta_key);
		}

		if($product_data['meta_description'])	$tmpl->setMetaDescription( html_out($product_data['meta_description']) );
		else
		{
			$d=array();
			$d[0]=array($product_name_html.' '.html_out($product_data['proizv']).' - ');
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
 			$tmpl->setMetaDescription($str);
		}


		$tmpl->addContent("<h1 id='page-title'>$product_name_html</h1>");
		$tmpl->addContent("<div class='breadcrumbs'>".$this->GetVitPath($product_data['group'])."</div>");
		$appends=$img_mini="";
		if($product_data['img_id'])
		{
			$miniimg=new ImageProductor($product_data['img_id'],'p', $product_data['img_type']);
			$miniimg->SetY(220);
			$miniimg->SetX(200);
			$fullimg=new ImageProductor($product_data['img_id'],'p', $product_data['img_type']);
			$img="<img src='".$miniimg->GetURI()."' alt='".html_out($product_data['name'])."' onload='$(this).fadeTo(500,1);' style='opacity: 1' id='midiphoto'>";
			$res=$db->query("SELECT `doc_img`.`id` AS `img_id`, `doc_base_img`.`default`, `doc_img`.`name`, `doc_img`.`type` AS `img_type` FROM `doc_base_img`
			LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
			WHERE `doc_base_img`.`pos_id`='{$product_data['id']}'");

			while($img_data=$res->fetch_assoc())
			{
				$miniimg=new ImageProductor($img_data['img_id'],'p', $img_data['img_type']);
				$miniimg->SetX(40);
				$miniimg->SetY(40);
				$midiimg=new ImageProductor($img_data['img_id'],'p', $img_data['img_type']);
				$midiimg->SetX(200);
				$midiimg->SetY(220);
				$fullimg=new ImageProductor($img_data['img_id'],'p', $img_data['img_type']);
				$fullimg->SetY(800);
				//$originimg=new ImageProductor($img_data['img_id'],'p', $img_data['img_type']);
				if($res->num_rows>1)
					$img_mini.="<a href='".$midiimg->GetURI()."' onclick=\"return setPhoto({$img_data['img_id']});\"><img src='".$miniimg->GetURI()."' alt='{$img_data['name']}'></a>";
				$appends.="midiphoto.appendImage({$img_data['img_id']},'".$midiimg->GetURI(1)."', '".$fullimg->GetURI(1)."');\n";

			}
		}
		else $img="<img src='/skins/{$CONFIG['site']['skin']}/images/no_photo.png' alt='no photo'>";

		$tmpl->addContent("<table class='product-card'>
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

		$tmpl->addContent("<td class='field'>Наименование:</td><td>".html_out($product_data['name'])."</td></tr>");
		if($product_data['vc']) $tmpl->addContent("<tr><td class='field'>Код производителя:</td><td>".html_out($product_data['vc'])."</td></tr>");
		if($product_data['desc'])
		{
			$text=$wikiparser->parse($product_data['desc']);
			$tmpl->addContent("<tr><td valign='top' class='field'>Описание:<td>$text");
		}

		if($product_data['dop_name']) $tmpl->addContent("<tr><td class='field'>Тип:<td>".html_out($product_data['dop_name']));
		
		$cce = '';
		if(@$CONFIG['site']['grey_price_days']) {
			$cce_time = $CONFIG['site']['grey_price_days'] * 60*60*24;
			if( strtotime($product_data['cost_date']) < $cce_time )
				$cce = ' style=\'color:#888\'';
		}
			
		$pc = $this->priceCalcInit();
		$cena = $pc->getPosDefaultPriceValue($product_data['id']);
		if($cena<=0)	$cena='уточняйте';
						
		if($pc->getRetailPriceId() != $pc->getDefaultPriceID()) {
			$ret_price = $pc->getPosRetailPriceValue($product_data['id']);
			if($ret_price<=0)	$ret_price='уточняйте';
			$tmpl->addContent("<tr><td class='field'>Розничная цена:<td{$cce}>$ret_price</td></tr>");
			$tmpl->addContent("<tr><td class='field'>Оптовая цена:<td{$cce}>$cena</td></tr>");
		}
		else	$tmpl->addContent("<tr><td class='field'>Цена:<td{$cce}>$cena</td></tr>");

		if($pc->getCurrentPriceId() != $pc->getDefaultPriceID()) {
			$user_price = $pc->getPosUserPriceValue($product_data['id']);
			if($user_price<=0)	$user_price='уточняйте';
			$tmpl->addContent("<tr><td class='field'>Цена для Вас:<td{$cce}>$user_price</td></tr>");
		}
		
		if($product_data['mult']>1)	
			$tmpl->addContent("<tr><td class='field'>В упаковке:<td>{$product_data['mult']} штук</td></tr>");
			
		$tmpl->addContent("<tr><td class='field'>Единица измерения:<td>".html_out($product_data['units']));
		
		$nal = $this->GetCountInfo($product_data['cnt'], $product_data['transit_cnt']);

		if($nal) $tmpl->addContent("<tr><td class='field'>Наличие: <td><b>$nal</b><br>");
		else $tmpl->addContent("<tr><td class='field'>Наличие:<td>Под заказ<br>");
		if($product_data['d_int'])	$tmpl->addContent("<tr><td class='field'>Внутренний диаметр: <td>{$product_data['d_int']} мм.<br>");
		if($product_data['d_ext'])	$tmpl->addContent("<tr><td class='field'>Внешний диаметр: <td>{$product_data['d_ext']} мм.<br>");
		if($product_data['size'])	$tmpl->addContent("<tr><td class='field'>Высота: <td>{$product_data['size']} мм.<br>");
		if($product_data['mass'])	$tmpl->addContent("<tr><td class='field'>Масса: <td>{$product_data['mass']} кг.<br>");
		if($product_data['analog'])	$tmpl->addContent("<tr><td class='field'>Аналог: <td>{$product_data['analog']}<br>");
		if($product_data['proizv'])	$tmpl->addContent("<tr><td class='field'>Производитель: <td>".html_out($product_data['proizv']));

		$param_res=$db->query("SELECT `doc_base_params`.`param`, `doc_base_values`.`value` FROM `doc_base_values`
		LEFT JOIN `doc_base_params` ON `doc_base_params`.`id`=`doc_base_values`.`param_id`
		WHERE `doc_base_values`.`id`='{$product_data['id']}' AND `doc_base_params`.`pgroup_id`='0' AND `doc_base_params`.`system`='0'");
		while($params=$param_res->fetch_row()) {
			$tmpl->addContent("<tr><td class='field'>".html_out($params[0])."</td><td>".html_out($params[1])."</td></tr>");
		}

		$resg=$db->query("SELECT `id`, `name` FROM `doc_base_gparams`");
		while($nxtg=$resg->fetch_row())
		{
			$f=0;
			$param_res=$db->query("SELECT `doc_base_params`.`param`, `doc_base_values`.`value` FROM `doc_base_values`
			LEFT JOIN `doc_base_params` ON `doc_base_params`.`id`=`doc_base_values`.`param_id`
			WHERE `doc_base_values`.`id`='{$product_data['id']}' AND `doc_base_params`.`pgroup_id`='$nxtg[0]' AND `doc_base_params`.`system`='0'");
			while($params=$param_res->fetch_row())
			{
				if(!$f)
				{
					$f=1;
					$tmpl->addContent("<tr><th colspan='2'>".html_out($nxtg[1])."</th></tr>");
				}
				$tmpl->addContent("<tr><td class='field'>".html_out($params[0])."</td><td>".html_out($params[1])."</td></tr>");
			}
		}

		$att_res=$db->query("SELECT `doc_base_attachments`.`attachment_id`, `attachments`.`original_filename`, `attachments`.`comment`
		FROM `doc_base_attachments`
		LEFT JOIN `attachments` ON `attachments`.`id`=`doc_base_attachments`.`attachment_id`
		WHERE `doc_base_attachments`.`pos_id`='$product'");
		if($att_res->num_rows>0)
		{
			$tmpl->addContent("<tr><th colspan='3'>Прикреплённые файлы</th></tr>");
			while($anxt=$att_res->fetch_row())
			{
				if($CONFIG['site']['recode_enable'])	$link="/attachments/{$anxt[0]}/$anxt[1]";
				else					$link="/attachments.php?att_id={$anxt[0]}";
				$tmpl->addContent("<tr><td><a href='$link'>$anxt[1]</a></td><td>$anxt[2]</td></tr>");
			}
		}
		if($product_data['mult']>1)
			$k_info = "<br>должно быть кратно ".$product_data['mult'];
		else	$k_info = '';
		
		if($product_data['bulkcnt']>1)		$buy_cnt = $product_data['bulkcnt'];
		else if($product_data['mult']>1)	$buy_cnt = $product_data['mult'];
		else					$buy_cnt = 1;
		
		$tmpl->addContent("<tr><td colspan='3'>
		<form action='/vitrina.php'>
		<input type='hidden' name='mode' value='korz_add'>
		<input type='hidden' name='p' value='$product'>
		<div>
		Добавить
		<input type='text' name='cnt' value='$buy_cnt' class='mini'> штук <button type='submit'>В корзину!</button>{$k_info}
		</div>
		</form>
		</td></tr></table>");
		$d=array();
		$d[]=array('В нашем');
		$d[]=array('магазине','интернет-магазине','каталоге','прайс-листе');
		$d[]=array('Вы можете');
		$d[]=array('купить','заказать','приобрести');
		$d[]=array($product_data['group_printname'].' '.$product_data['name'].' '.$product_data['proizv'].' по ');
		$d[]=array('доступной','отличной','хорошей','разумной','выгодной');
		$d[]=array('цене за','стоимости за');
		$d[]=array('наличный расчёт.','безналичный расчёт.','webmoney.');
		$d[]=array('Так же можно');
		$d[]=array('заказать','запросить','осуществить');
		$d[]=array('доставку','экспресс-доставку','доставку транспортной компанией','почтовую доставку','доставку курьером');
		$d[]=array('этого товара','выбранной продукции');
		$d[]=array('по всей России.','в любой город России.','по РФ.','в любой регион России.');
		$str='';
		$base=abs(crc32($product_data['name'].$product_data['group'].$product_data['proizv'].$product_data['vc'].$product_data['desc']));
		foreach($d as $id => $item)
		{
			$l=count($item);
			$i=$base%$l;
			$base=floor($base/$l);
			$str.=$item[$i].' ';
		}
		$tmpl->addContent("<div class='description'>$str</div>");
		
		// Аналоги
		if($product_data['analog_group']) {
			$analog_group_sql = $db->real_escape_string($product_data['analog_group']);
			$cnt_where=@$CONFIG['site']['vitrina_sklad']?(" AND `doc_base_cnt`.`sklad`=".intval($CONFIG['site']['vitrina_sklad'])." "):'';
			$res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`cost_date`, `doc_base`.`cost`,
			( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` $cnt_where GROUP BY `doc_base`.`id`) AS `count`,
			`doc_base`.`transit_cnt`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base`.`proizv`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`vc`, `doc_base`.`buy_time`, `doc_base`.`create_time`, `doc_base`.`bulkcnt`, `doc_base`.`mult`
			FROM `doc_base`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
			LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
			LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
			WHERE `doc_base`.`analog_group`='$analog_group_sql' AND `doc_base`.`hidden`='0'
			ORDER BY `doc_base`.`id`");
			if($res->num_rows) {
				$tmpl->addContent("<div><h2>Аналоги</h2>");		        
				while($link_info = $res->fetch_assoc()) {
					$tmpl->addContent($this->getProductMiniElement($link_info));
				}
				$tmpl->addContent("</div>");
			}
			$tmpl->addContent("<hr class='clear'>");
		}
		
		// Сопутствующие товары
		$cnt_where=@$CONFIG['site']['vitrina_sklad']?(" AND `doc_base_cnt`.`sklad`=".intval($CONFIG['site']['vitrina_sklad'])." "):'';
		$res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`group`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`cost_date`, `doc_base`.`cost`,
		( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` $cnt_where GROUP BY `doc_base`.`id`) AS `count`,
		`doc_base`.`transit_cnt`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, `doc_base`.`proizv`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`vc`, `doc_base`.`buy_time`, `doc_base`.`create_time`, `doc_base`.`bulkcnt`, `doc_base`.`mult`
		FROM `doc_base_links`
		INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_base_links`.`pos2_id`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
		LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_base_links`.`pos1_id`='$product' AND `doc_base`.`hidden`='0'
		ORDER BY `doc_base`.`id`");
		if($res->num_rows) {
			$tmpl->addContent("<div><h2>Сопутствующие товары</h2>");		        
                        while($link_info = $res->fetch_assoc()) {
				$tmpl->addContent($this->getProductMiniElement($link_info));
			}
			$tmpl->addContent("</div>");
			$tmpl->addContent("<hr class='clear'>");
		}
		
		$tmpl->addContent("<script type='text/javascript' charset='utf-8'>
		$(document).ready(function(){
		$(\"a[rel^='prettyPhoto']\").prettyPhoto({theme:'dark_rounded'});
		});
		</script>");
		$i++;
	}

	if($i==0) {
		$tmpl->addContent("<h1 id='page-title'>Информация о товаре</h1>");
		throw new NotFoundException("К сожалению, товар не найден. Возможно, Вы пришли по неверной ссылке.");
	}
}

/// Получить HTML код товарного предложения стандартного размера
public function getProductBaseElement($product_info) {
	global $CONFIG;
	if(@$CONFIG['site']['grey_price_days'])
		$cce_time = $CONFIG['site']['grey_price_days'] * 60*60*24;
	$cce = '';
	if(@$CONFIG['site']['grey_price_days']) {
		if( strtotime($nxt['cost_date']) < $cce_time )
			$cce = ' style=\'color:#888\'';
	}
	$pc = $this->priceCalcInit();
	if($product_info['img_id']) {
		$miniimg = new ImageProductor($product_info['img_id'], 'p', $product_info['img_type']);
		$miniimg->SetX(135);
		$miniimg->SetY(180);
		$img="<img src='".$miniimg->GetURI()."' alt='".html_out($product_info['name'])."' width='13px' height='17px'>";
	}
	else $img="<img src='/skins/{$CONFIG['site']['skin']}/images/no_photo_131.jpg' alt='no photo'>";
	$nal = $this->GetCountInfo($product_info['count'], $product_info['transit_cnt']);
	$link = $this->GetProductLink($product_info['id'], $product_info['name']);
	$price = $pc->getPosDefaultPriceValue($product_info['id']);
	$price = number_format($price, 2, '.', ' ');
	if($price <= 0)	$price='уточняйте';
	if($product_info['bulkcnt']>1)	$buy_cnt = $product_info['bulkcnt'];
	else if($product_info['mult']>1)	$buy_cnt = $product_info['mult'];
	else				$buy_cnt = 1;
	
	return "<div class='pitem'>
	<a href='$link'>$img</a>
	<a href='$link'>".html_out($product_info['name'])."</a><br>
	<b>Код:</b> ".html_out($product_info['vc'])."<br>
	<b>Цена:</b> <span{$cce}>$price руб.</span> / {$product_info['units']}<br>
	<b>Производитель:</b> ".html_out($product_info['proizv'])."<br>
	<b>Кол-во:</b> $nal<br>
	<a rel='nofollow' href='/vitrina.php?mode=korz_add&amp;p={$product_info['id']}&amp;cnt=$buy_cnt' onclick=\"return ShowPopupWin('/vitrina.php?mode=korz_adj&amp;p={$product_info['id']}&amp;cnt=$buy_cnt','popwin');\" rel='nofollow'>В корзину!</a>
	</div>";

}

/// Получить HTML код товарного предложения уменьшенного размера
public function getProductMiniElement($product_info) {
	global $CONFIG;
	if(@$CONFIG['site']['grey_price_days'])
		$cce_time = $CONFIG['site']['grey_price_days'] * 60*60*24;
	$cce = '';
	if(@$CONFIG['site']['grey_price_days']) {
		if( strtotime($nxt['cost_date']) < $cce_time )
			$cce = ' style=\'color:#888\'';
	}
	$pc = $this->priceCalcInit();
	if($product_info['img_id']) {
		$miniimg = new ImageProductor($product_info['img_id'], 'p', $product_info['img_type']);
		$miniimg->SetX(63);
		$miniimg->SetY(85);
		$img="<img src='".$miniimg->GetURI()."' alt='".html_out($product_info['name'])."' width='13px' height='17px'>";
	}
	else $img="<img src='/skins/{$CONFIG['site']['skin']}/images/no_photo_131.jpg' alt='no photo'>";
	$nal = $this->GetCountInfo($product_info['count'], $product_info['transit_cnt']);
	$link = $this->GetProductLink($product_info['id'], $product_info['name']);
	$price = $pc->getPosDefaultPriceValue($product_info['id']);
	$price = number_format($price, 2, '.', ' ');
	if($price <= 0)	$price='уточняйте';
	if($product_info['bulkcnt']>1)	$buy_cnt = $product_info['bulkcnt'];
	else if($product_info['mult']>1)	$buy_cnt = $product_info['mult'];
	else				$buy_cnt = 1;
	
	return "<div class='pitem_mini'>
	<a href='$link'>$img</a>
	<a href='$link'>".html_out($product_info['name'])."</a><br>
	<b>Код:</b> ".html_out($product_info['vc'])."<br>
	<b>Цена:</b> <span{$cce}>$price руб.</span> / {$product_info['units']}<br>
	<b>Производитель:</b> ".html_out($product_info['proizv'])."<br>
	<b>Кол-во:</b> $nal<br>
	<a rel='nofollow' href='/vitrina.php?mode=korz_add&amp;p={$product_info['id']}&amp;cnt=$buy_cnt' onclick=\"return ShowPopupWin('/vitrina.php?mode=korz_adj&amp;p={$product_info['id']}&amp;cnt=$buy_cnt','popwin');\" rel='nofollow'>В корзину!</a>
	</div>";
}

/// Просмотр корзины
protected function Basket() {
	global $tmpl, $CONFIG, $db;
	$s = '';
	$sum = $exist = $lock = $lock_mark = $mult_lock = 0;
	$i = 1;
	
	$basket = Models\Basket::getInstance();
	if($basket->getCount()) {
		$pc = $this->priceCalcInit();
		$basket_items = $basket->getItems();
		foreach ($basket_items as $item) {
			settype($item['pos_id'], 'int');
			settype($item['cnt'], 'int');
			$res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`cost`, `doc_img`.`id` AS `img_id`,
				`doc_img`.`type` AS `img_type`, `mult`, `bulkcnt`, `class_unit`.`rus_name1` AS `unit_name`
			FROM `doc_base`
			LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
			LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
			LEFT JOIN `class_unit` ON `class_unit`.`id`=`doc_base`.`unit`
			WHERE `doc_base`.`id`=".intval($item['pos_id']));
			$line = $res->fetch_assoc();
			
			$price = $pc->getPosAutoPriceValue($line['id'], $item['cnt']);
			
			// При нулевой цене предупреждать *товар под заказ*
			if($price<=0) {
				$lock = 1;
				$lock_mark = 1;
			}
			else $lock_mark = 0;
			
			// Не давать оформить заказ при нарушении кратности
			if($line['mult']>1) {
				if($item['cnt']%$line['mult']) {
					$mult_lock = 1;
					$lock_mark = 1;
				}
			}
			
			// Если параметр включен - при превышении кол-ва на складе(за вычетом резервов) тоже сообщать *товар под заказ*
			if(@$CONFIG['site']['vitrina_cntlock'])	{
				if(isset($CONFIG['site']['vitrina_sklad'])) {
					$sklad_id = round($CONFIG['site']['vitrina_sklad']);
					$res = $db->query("SELECT `doc_base_cnt`.`cnt` FROM `doc_base_cnt` WHERE `id`='{$line['id']}' AND `sklad`='$sklad_id'");
				}
				else	$res = $db->query("SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `id`='{$line['id']}'");
				if($res->num_rows) {
					$tmp = $res->fetch_row();
					$sklad_cnt = $tmp[0] - DocRezerv($line['id']);
				}
				else	$sklad_cnt = DocRezerv($line['id'])*(-1);

				if($item['cnt']>$sklad_cnt) {
					$lock=1;
					$lock_mark=1;
				}
			}
			
			$cce = '';
			// При *серой* цене информировать - *товар под заказ*
			if(@$CONFIG['site']['grey_price_days']) {
				$cce_time = $CONFIG['site']['grey_price_days'] * 60*60*24;
				if( strtotime($line['cost_date']) < $cce_time ) {
					if(@$CONFIG['site']['vitrina_pricelock']) {
						$lock=1;
						$lock_mark=1;
					}
					$cce = ' style=\'color:#888\'';
				}
			}
			
			$sm = $price * $item['cnt'];
			$sum += $sm;
			$sm = sprintf("%0.2f", $sm);
			$lock_mark = $lock_mark?'color: #f00':'';
			if($price<=0)	$price='уточняйте';
			
			$link = $this->GetProductLink($item['pos_id'],'');
			
			if($line['img_id']) {
				$miniimg=new ImageProductor($line['img_id'],'p', $line['img_type']);
				$miniimg->SetX(24);
				$miniimg->SetY(32);
				$img="<img src='".$miniimg->GetURI()."' alt='".html_out($line['name'])."'>";
			}
			else $img="";
			
			$s.="<tr id='korz_ajax_item_{$item['pos_id']}' style='$lock_mark'>
			<td class='right'>$i <span id='korz_item_clear_url_{$item['pos_id']}'><a href='/vitrina.php?mode=korz_del&p={$item['pos_id']}' onClick='korz_item_clear({$item['pos_id']}); return false;'><img src='/img/i_del.png' alt='Убрать'></a></span></td>
			<td>$img</td>
			<td><a href='/vitrina.php?mode=product&amp;p={$line['id']}'>".html_out($line['name'])."</a></td>
			<td class='right' $cce>$price</td>
			<td class='right'><span class='sum'>$sm</span></td>
			<td><input type='number' name='cnt{$item['pos_id']}' value='{$item['cnt']}' class='mini'></td>
			<td><input type='text' name='comm{$item['pos_id']}' style='width: 90%' value='".html_out($item['comment'])."' maxlength='100'></td>
			</tr>";
			$exist = 1;
			$i++;
		}
	}
	
	if(!$exist) $tmpl->msg("Ваша корзина пуста! Выберите, пожалуйста интересующие Вас товары!","info");
	else {
		$tmpl->addContent("
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
		if($mult_lock) {
			$tmpl->msg("Количество заказанного Вами товара меньше, чем количество товара в упаковке. Строки с ошибкой выделены красным. Вы не сможете оормить заказ, пока не исправите ошибку.","err", "Предупреждение");
			$buy_disable = ' disabled';
		}
		else	$buy_disable = '';
		$tmpl->addContent("
		<form action='' method='post'>
		<input type='hidden' name='mode' value='basket_submit'>
		<table width='100%' class='list'>
		<tr class='title'><th>N</th><th>&nbsp;</th><th>Наименование<th>Цена, руб<th>Сумма, руб<th>Количество, шт<th>Коментарии</tr>
		$s
		<tr class='total'><td>&nbsp;</td><td colspan='2'>Итого:</td><td colspan='4'><span class='sums'>$sum</span> рублей</td></tr>
		</table>
		<br>
		<center><button name='button' value='recalc' type='submit'>Пересчитать</button>
		<button name='button' value='buy' type='submit'{$buy_disable}>Оформить заказ</button></center><br>
		<center><span id='korz_clear_url'><a href='/vitrina.php?mode=korz_clear' onClick='korz_clear(); return false;'><b>Очистить корзину!</b></a></span></center><br>
		</form>
		</center><br><br>
		");		
	}
}

/// Оформление доставки
protected function Delivery() {
	$this->basket_sum = 0;
	$basket = Models\Basket::getInstance();
	
	if($basket->getCount()) {
		$pc = $this->priceCalcInit();
		$basket_items = $basket->getItems();
		foreach($basket_items as $item) {
			$this->basket_sum += $pc->getPosAutoPriceValue($item['pos_id'], $item['cnt']) * $item['cnt'];
		}
	}
	
	if(!isset($_REQUEST['delivery_type'])) {
		$this->DeliveryTypeForm();
	}
	else if(!@$_REQUEST['delivery_region']) {
		$_SESSION['basket']['delivery_type'] = round($_REQUEST['delivery_type']);
		if($_REQUEST['delivery_type']==0)	
			$this->BuyMakeForm();
		else {
			if(isset($_SESSION['uid'])) {
				$up = getUserProfile($_SESSION['uid']);
				$this->basket_address	= @$up['main']['real_address'];
			}
			else	$this->basket_address = '';
			$this->DeliveryRegionForm();
		}
	}
	else {
		$_SESSION['basket']['delivery_region']	= request('delivery_region');
		$_SESSION['basket']['delivery_address']	= request('delivery_address');
		$_SESSION['basket']['delivery_date']	= request('delivery_date');
		$this->BuyMakeForm();
	}
}

/// Форма *способ доставки*
protected function DeliveryTypeForm() {
	global $tmpl, $db;
	$tmpl->setContent("<h1>Способ доставки</h1>");
	$tmpl->addContent("<form action='' method='post'>
	<input type='hidden' name='mode' value='delivery'>
	<label><input type='radio' name='delivery_type' value='0'> Самовывоз</label><br><small>Вы сможете забрать товар с нашего склада</small><br><br>");
	$res = $db->query("SELECT `id`, `name`, `min_price`, `description` FROM `delivery_types`");
	while($nxt=$res->fetch_assoc()) {
		$disabled = $this->basket_sum < $nxt['min_price']?' disabled':'';
		$tmpl->addContent("<label><input type='radio' name='delivery_type' value='{$nxt['id']}'$disabled> {$nxt['name']}</label><br>Минимальная сумма заказа - {$nxt['min_price']} рублей.<br><small>{$nxt['description']}</small><br><br>");
	}
	$tmpl->addContent("<button type='submit'>Далее</button></form>");
}

/// Форма *регион доставки*
protected function DeliveryRegionForm()
{
	global $tmpl, $db;
	$tmpl->setContent("<h1>Регион доставки</h1>");
	$tmpl->addContent("<form action='' method='post'>
	<input type='hidden' name='mode' value='delivery'>
	<input type='hidden' name='delivery_type' value='{$_REQUEST['delivery_type']}'>");
	$res = $db->query("SELECT `id`, `name`, `price`, `description` FROM `delivery_regions` WHERE `delivery_type`='{$_SESSION['basket']['delivery_type']}'");
	while($nxt = $res->fetch_assoc()) {
		$tmpl->addContent("<label><input type='radio' name='delivery_region' value='{$nxt['id']}'> {$nxt['name']} - {$nxt['price']} рублей.</label><br><small>{$nxt['description']}</small><br><br>");
	}
	$tmpl->addContent("
	Желаемые дата и время доставки:<br>
	<input type='text' name='delivery_date'><br>
	Адрес доставки:<br>
	<textarea name='delivery_address' rows='5' cols='80'>".html_out($this->basket_address)."</textarea><br>
	<button type='submit'>Далее</button></form>");
}


/// Оформление покупки
protected function Buy() {
	global $tmpl;
	$step = rcvint('step');
	$tmpl->setContent("<h1 id='page-title'>Оформление заказа</h1>");
	if((!@$_SESSION['uid'])&&($step!=1))
	{
		if($step==2) {
			$_SESSION['last_page']="/vitrina.php?mode=buy";
			header("Location: /login.php?mode=reg");
		}
		if($step==3) {
			$_SESSION['last_page']="/vitrina.php?mode=buy";
			header("Location: /login.php");
		}
		else {
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
	global $tmpl, $db;
	settype($group,'int');
	$res=$db->query("SELECT `id`, `name` FROM `doc_group` WHERE `hidelevel`='0' AND `pid`='$group' ORDER BY `id`");
	$tmpl->addStyle(".vitem { width: 250px; float: left; font-size:	14px; } .vitem:before{content: '\\203A \\0020' ; } hr.clear{border: 0 none; margin: 0;}");
	while($nxt=$res->fetch_row()) {
		$tmpl->addContent("<div class='vitem'><a href='".$this->GetGroupLink($nxt[0])."'>$nxt[1]</a></div>");
	}
	$tmpl->addContent("<hr class='clear'>");
}
/// Список групп с изображениями
protected function GroupList_ImageStyle($group) {
	global $tmpl, $CONFIG, $db;

	$res = $db->query("SELECT * FROM `doc_group` WHERE `hidelevel`='0' AND `pid`='$group' ORDER BY `id`");
	$tmpl->addStyle(".vitem { width: 360px; float: left; font-size:	14px; margin: 10px;} .vitem img {float: left; padding-right: 8px;} hr.clear{border: 0 none; margin: 0;}");
	while ($nxt = $res->fetch_row()) {
		$link = $this->GetGroupLink($nxt[0]);
		$tmpl->addContent("<div class='vitem'><a href='$link'>");
		if (file_exists("{$CONFIG['site']['var_data_fs']}/category/$nxt[0].jpg"))
			$tmpl->addContent("<img src='{$CONFIG['site']['var_data_web']}/category/$nxt[0].jpg' alt='$nxt[1]'>");
		else {
			if (file_exists($CONFIG['site']['location'] . '/skins/' . $CONFIG['site']['skin'] . '/no_photo.png'))
				$img_url = '/skins/' . $CONFIG['site']['skin'] . '/no_photo.png';
			else	$img_url = '/img/no_photo.png';
			$tmpl->addContent("<img src='$img_url' alt='Изображение не доступно'>");
		}
		$tmpl->addContent("</a><div><a href='$link'><b>" . html_out($nxt[1]) . "</b></a><br>");
		if ($nxt[2]) {
			$desc = explode('.', $nxt[2], 2);
			if ($desc[0])	$tmpl->addContent($desc[0]);
			else		$tmpl->addContent($nxt[2]);
		}
		$tmpl->addContent("</div></div>");
	}
	$tmpl->addContent("<hr class='clear'>");
}

/// Простая таблица товаров
/// @param res mysqli_result Список товарных предложений
/// @param lim Максимальное количество выводимых строк
protected function TovList_SimpleTable($res, $lim) {
	global $tmpl, $CONFIG;
	
	$s_retail = $s_current = $i = 0;
	$pc = $this->priceCalcInit();
	
	$tmpl->addContent("<table width='100%' class='list'><tr class='title'>");
	if(@$CONFIG['site']['vitrina_show_vc'])
		$tmpl->addContent("<th>Код</th>");
	$tmpl->addContent("<th>Наименование</th><th>Производитель</th><th>Наличие</th>");
	
	if($pc->getRetailPriceId() != $pc->getDefaultPriceID()) {
		$tmpl->addContent("<th>В розницу</th><th>Оптом</th>");
		$s_retail = 1;
	}
	else	$tmpl->addContent("<th>Цена</th>");
	
	if($pc->getCurrentPriceId() != $pc->getDefaultPriceID()) {
		$tmpl->addContent("<th>Для Вас</th>");
		$s_current = 1;
	}
	
	$tmpl->addContent("<th>Купить</th></tr>");
	
	$basket_img = "/skins/".$CONFIG['site']['skin']."/basket16.png";
	
	if(@$CONFIG['site']['grey_price_days'])
		$cce_time = $CONFIG['site']['grey_price_days'] * 60*60*24;
	
	while($nxt = $res->fetch_assoc()) {
		$nal = $this->GetCountInfo($nxt['count'], @$nxt['tranit']);
		$link = $this->GetProductLink($nxt['id'], $nxt['name']);
		$price = $pc->getPosDefaultPriceValue($nxt['id']);
		if($price<=0)	$price='уточняйте';
		
		$cce = '';
		if(@$CONFIG['site']['grey_price_days']) {
			if( strtotime($nxt['cost_date']) < $cce_time )
				$cce = ' style=\'color:#888\'';
		}
		
		$tmpl->addContent("<tr>");
		if(@$CONFIG['site']['vitrina_show_vc'])
			$tmpl->addContent("<td>".html_out($nxt['vc'])."</td>");
		$tmpl->addContent("<td><a href='$link'>".html_out($nxt['name'])."</a></td>
		<td>".html_out($nxt['proizv'])."</td><td>$nal</td>");
		if($s_retail) {
			$ret_price = $pc->getPosRetailPriceValue($nxt['id']);
			if($ret_price<=0)	$ret_price='уточняйте';
			$tmpl->addContent("<td{$cce}>$ret_price</td><td{$cce}>$price</td>");
		}
		else $tmpl->addContent("<td{$cce}>$price</td>");
		if($s_current) {
			$user_price = $pc->getPosUserPriceValue($nxt['id']);
			if($user_price<=0)	$user_price='уточняйте';
			$tmpl->addContent("<td{$cce}>$user_price</td>");
		}
		
		if($nxt['bulkcnt']>1)	$buy_cnt = $nxt['bulkcnt'];
		else if($nxt['mult']>1)	$buy_cnt = $nxt['mult'];
		else			$buy_cnt = 1;
		
		@$tmpl->addContent("<td><a rel='nofollow' href='/vitrina.php?mode=korz_add&amp;p={$nxt['id']}&amp;cnt=$buy_cnt' onclick=\"return ShowPopupWin('/vitrina.php?mode=korz_adj&amp;p={$nxt['id']}&amp;cnt=$buy_cnt','popwin');\" rel='nofollow'><img src='$basket_img' alt='В корзину!'></a></td></tr>");
		$i++;
		if($i >= $lim)	break;
	}
	$tmpl->addContent("</table>");
}

/// Список товаров в виде изображений
/// @param res mysqli_result Список товарных предложений
/// @param lim Максимальное количество выводимых строк
protected function TovList_ImageList($res, $lim) {
	global $tmpl, $CONFIG;
	$cc=$i=0;

	if(@$CONFIG['site']['grey_price_days'])
		$cce_time = $CONFIG['site']['grey_price_days'] * 60*60*24;
	
	$pc = $this->priceCalcInit();	
	
	while($nxt=$res->fetch_assoc())	{
		$nal = $this->GetCountInfo($nxt['count'], $nxt['transit_cnt']);
		$link = $this->GetProductLink($nxt['id'], $nxt['name']);
		
		$price = $pc->getPosDefaultPriceValue($nxt['id']);
		if($price<=0)	$price='уточняйте';
		
		$cce = '';
		if(@$CONFIG['site']['grey_price_days']) {
			if( strtotime($nxt['cost_date']) < $cce_time )
				$cce = ' style=\'color:#888\'';
		}
		
		if($nxt['img_id']) {
			$miniimg=new ImageProductor($nxt['img_id'],'p', $nxt['img_type']);
			$miniimg->SetX(135);
			$miniimg->SetY(180);
			$img="<img src='".$miniimg->GetURI()."' style='float: left; margin-right: 10px;' alt='{$nxt['name']}'>";
		}
		else {
			if(file_exists($CONFIG['site']['location'].'/skins/'.$CONFIG['site']['skin'].'/no_photo.png'))
				$img_url='/skins/'.$CONFIG['site']['skin'].'/no_photo.png';
			else	$img_url='/img/no_photo.png';
			$img="<img src='$img_url' alt='no photo' style='float: left; margin-right: 10px; width: 135px;' alt='no photo'>";
		}
		$desc=$nxt['desc'];
		if(strpos($desc,'.')!==false) {
			list($desc) = explode('.',$desc,2);
		}

		if($nxt['bulkcnt']>1)	$buy_cnt = $nxt['bulkcnt'];
		else if($nxt['mult']>1)	$buy_cnt = $nxt['mult'];
		else			$buy_cnt = 1;
		
		$tmpl->addContent("<div class='pitem'>
		<a href='$link'>$img</a>
		<a href='$link'>".html_out($nxt['name'])."</a><br>
		<b>Код:</b> ".html_out($nxt['vc'])."<br>
		<b>Цена:</b> <span{$cce}>$price руб.</span> / {$nxt['units']}<br>
		<b>Производитель:</b> ".html_out($nxt['proizv'])."<br>
		<b>Кол-во:</b> $nal<br>
		<a rel='nofollow' href='/vitrina.php?mode=korz_add&amp;p={$nxt['id']}&amp;cnt=$buy_cnt' onclick=\"return ShowPopupWin('/vitrina.php?mode=korz_adj&amp;p={$nxt['id']}&amp;cnt=$buy_cnt','popwin');\" rel='nofollow'>В корзину!</a>
		</div>");

		$i++;
		$cc=1-$cc;
		if($i>=$lim)	break;
	}
	$tmpl->addContent("<div class='clear'></div>");
}

/// Подробная таблица товаров
/// @param res mysqli_result Список товарных предложений
/// @param lim Максимальное количество выводимых строк
protected function TovList_ExTable($res, $lim) {
	global $tmpl, $CONFIG;
	
	$pc = $this->priceCalcInit();
	
	$tmpl->addContent("<table width='100%' class='list'><tr class='title'>");
	if(@$CONFIG['site']['vitrina_show_vc'])
		$tmpl->addContent("<th>Код</th>");
	$tmpl->addContent("<th>Наименование</th><th>Производитель</th><th>Наличие</th><th>Цена</th><th>d, мм</th><th>D, мм</th><th>B, мм</th><th>m, кг</th><th>Купить</th></tr>");
	$basket_img = "/skins/".$CONFIG['site']['skin']."/basket16.png";
	
	if(@$CONFIG['site']['grey_price_days'])
		$cce_time = $CONFIG['site']['grey_price_days'] * 60*60*24;
	
	while($nxt = $res->fetch_assoc()) {
		$nal = $this->GetCountInfo($nxt['count'], $nxt['transit_cnt']);
		$link = $this->GetProductLink($nxt['id'], $nxt['name']);
		$price = $pc->getPosDefaultPriceValue($nxt['id']);
		if($price<=0)	$price='уточняйте';
		$cce = '';
		
		if(@$CONFIG['site']['grey_price_days']) {
			if( strtotime($nxt['cost_date']) < $cce_time )
				$cce = ' style=\'color:#888\'';
		}
		if($nxt['bulkcnt']>1)	$buy_cnt = $nxt['bulkcnt'];
		else if($nxt['mult']>1)	$buy_cnt = $nxt['mult'];
		else			$buy_cnt = 1;
		
		$tmpl->addContent("<tr>");
		if(@$CONFIG['site']['vitrina_show_vc'])
			$tmpl->addContent("<td>".html_out($nxt['vc'])."</td>");
		$tmpl->addContent("<td><a href='$link'>".html_out($nxt['name'])."</a><td>".html_out($nxt['proizv'])."<td>$nal
		<td $cce>$price<td>{$nxt['d_int']}<td>{$nxt['d_ext']}<td>{$nxt['size']}<td>{$nxt['mass']}<td>
		<a href='/vitrina.php?mode=korz_add&amp;p={$nxt['id']}&amp;cnt=$buy_cnt' onclick=\"return ShowPopupWin('/vitrina.php?mode=korz_adj&amp;p={$nxt['id']}&amp;cnt=$buy_cnt','popwin');\" rel='nofollow'><img src='$basket_img' alt='В корзину!'></a>");
	}
	$tmpl->addContent("</table>");
}


/// Форма аутентификации при покупке. Выдаётся, только если посетитель не вошёл на сайт
protected function BuyAuthForm() {
	global $tmpl;
	$tmpl->setTitle("Оформление зкакза");
	$tmpl->addContent("<p id='text'>Для использования всех возможностей этого сайта необходимо пройти процедуру регистрации. Регистрация не сложная, и займёт всего несколько минут.
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
	if(@$_SESSION['uid']) {
		$up = getUserProfile($_SESSION['uid']);
		$str = 'Товар будет зарезервирован для Вас на 3 рабочих дня.';
		$email_field = '';
	}
	else
	{
		$up = getUserProfile(-1);	// Пустой профиль
		$str='<b>Для незарегистрированных пользователей наличие товара на складе не гарантируется.</b>';
		$email_field="e-mail:<br>
		<input type='text' name='email' value=''><br>
		Необходимо заполнить телефон или e-mail<br><br>";
	}

	if(isset($_REQUEST['cwarn']))	$tmpl->msg("Необходимо заполнить e-mail или контактный телефон!","err");

	if(@$up['main']['reg_phone'])	$phone = substr($up['main']['reg_phone'],2);
	else				$phone = '';

	$tmpl->addContent("
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
	if(is_array($CONFIG['payments']['types'])) {
		$tmpl->addContent("<br>Способ оплаты:<br>");
		foreach($CONFIG['payments']['types'] as $type => $val) {
			if(!$val)	continue;
			if($type==@$CONFIG['payments']['default'])	$checked=' checked';
			else						$checked='';
			switch($type) {
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
			$tmpl->addContent($s);
		}
	}

	$tmpl->addContent("
	Другая информация:<br>
	<textarea name='dop' rows='5' cols='80'>".@$up['dop']['dop_info']."</textarea><br>
	<button type='submit'>Оформить заказ</button>
	</div>
	</form>");
}

/// Сделать покупку
protected function MakeBuy() {
	global $tmpl, $CONFIG, $db;
	if(!isset($CONFIG['site']['default_firm']))	$CONFIG['site']['default_firm'] = 1;
	settype($CONFIG['site']['default_firm'],'int');
	$pay_type = request('pay_type');
	switch($pay_type) {
		case 'bank':
		case 'cash':
		case 'card_o':
		case 'card_t':
		case 'credit_brs':
		case 'wmr':	break;
		default:	$pay_type='';
	}
	$rname		= request('rname');	
	$delivery	= intval($_SESSION['basket']['delivery_type']);	
	$delivery_region= $_SESSION['basket']['delivery_region'];
	$email		= request('email');	
	$comment	= request('dop');
	
	$rname_sql	= $db->real_escape_string($rname);
	$adres_sql	= $db->real_escape_string(@$_SESSION['basket']['delivery_address']);
	$delivery_date	= $db->real_escape_string(@$_SESSION['basket']['delivery_date']);
	$email_sql	= $db->real_escape_string($email);
	$comment_sql	= $db->real_escape_string($comment);
	$agent=1;

	if(@$_REQUEST['phone'])
		$tel='+7'.intval(@$_REQUEST['phone']);
	else	$tel='';

	if(@$_SESSION['uid']) {
		$res = $db->query("UPDATE `users` SET `real_name`='$rname_sql', `real_address`='$adres_sql' WHERE `id`='{$_SESSION['uid']}'");
		$res = $db->query("REPLACE `users_data` (`uid`, `param`, `value`) VALUES ('{$_SESSION['uid']}', 'dop_info', '$comment_sql') ");
		// Получить ID агента
		$res = $db->query("SELECT `name`, `reg_email`, `reg_date`, `reg_email_subscribe`, `real_name`, `reg_phone`, `real_address`, `agent_id` FROM `users` WHERE `id`='{$_SESSION['uid']}'");
		$user_data = $res->fetch_assoc();
		$agent = $user_data['agent_id'];
		settype($agent,'int');
		if($agent<1)	$agent = 1;
	}
	else if(!$tel && !$email) {
		header("Location: /vitrina.php?mode=buyform&step=1&cwarn=1");
		return;
	}
	
	$basket = Models\Basket::getInstance();

	if($basket->getCount()) {
		$pc = $this->priceCalcInit();
		$basket_items = $basket->getItems();

		if(!isset($CONFIG['site']['vitrina_subtype']))		$subtype = "site";
		else	$subtype = $CONFIG['site']['vitrina_subtype'];
		$tm = time();
		$altnum = GetNextAltNum(3, $subtype, 0, date('Y-m-d'), $CONFIG['site']['default_firm']);
		$ip = getenv("REMOTE_ADDR");
		if(isset($CONFIG['site']['default_bank']))	$bank = $CONFIG['site']['default_bank'];
		else {
			$res = $db->query("SELECT `num` FROM `doc_kassa` WHERE `ids`='bank' AND `firm_id`='{$CONFIG['site']['default_firm']}'");
			if($res->num_rows<1)	throw new Exception("Не найден банк выбранной организации");
			list($bank)=$res->fetch_row();
		}
		
		if(isset($_SESSION['uid']))	$uid = $_SESSION['uid'];
		else				$uid = 0;
		
		$res = $db->query("INSERT INTO doc_list (`type`,`agent`,`date`,`sklad`,`user`,`nds`,`altnum`,`subtype`,`comment`,`firm_id`,`bank`)
		VALUES ('3','$agent','$tm','1','$uid','1','$altnum','$subtype','$comment_sql','{$CONFIG['site']['default_firm']}','$bank')");
		$doc=$db->insert_id;

		$res = $db->query("REPLACE INTO `doc_dopdata` (`doc`, `param`, `value`) VALUES ('$doc', 'ishop', '1'),  ('$doc', 'buyer_email', '$email_sql'), ('$doc', 'buyer_phone', '$tel'), ('$doc', 'buyer_rname', '$rname_sql'), ('$doc', 'buyer_ip', '$ip'), ('$doc', 'delivery', '$delivery'), ('$doc', 'delivery_date', '$delivery_date'), ('$doc', 'delivery_address', '$adres_sql'), ('$doc', 'delivery_region', '$delivery_region'), ('$doc', 'pay_type', '$pay_type') ");

		$order_items = $admin_items = $lock = '';
		
		foreach ($basket_items as $item) {			
			settype($item['pos_id'], 'int');
			
			$price = $pc->getPosAutoPriceValue($item['pos_id'], $item['cnt']);
			$comm_sql = $db->real_escape_string($item['comment']);
			
			$db->insertA('doc_list_pos', array('doc'=>$doc, 'tovar'=>$item['pos_id'], 'cnt'=>$item['cnt'], 'cost'=>$price, 'comm'=>$item['comment']));

			$res = $db->query("SELECT `doc_base`.`id`, CONCAT(`doc_group`.`printname`, ' ' , `doc_base`.`name`) AS `pos_name`,
				`doc_base`.`proizv` AS `vendor`, `doc_base`.`vc`, `doc_base`.`cost` AS `base_price`, `class_unit`.`rus_name1` AS `unit_name`, `doc_base`.`cost_date`
				FROM `doc_base`
				LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
				LEFT JOIN `class_unit` ON `class_unit`.`id`=`doc_base`.`unit`
				WHERE `doc_base`.`id`='{$item['pos_id']}'");

			$pos_info = $res->fetch_assoc();
			$item_str = $pos_info['pos_name'].'/'.$pos_info['vendor'];
			if($pos_info['vc'])
				$item_str .= ' ('.$pos_info['vc'].')';
			$item_str .= ' - '.$item['cnt'].' '.$pos_info['unit_name'].' - '.$price.' руб.';
			$order_items .=  $item_str."\n";
			$admin_items .= $item_str." (базовая - {$pos_info['base_price']} руб.)\n";
			
			if($price<=0) {
				$lock = 1;
				$lock_mark = 1;
			}
			
			if(@$CONFIG['site']['vitrina_cntlock'] || @$CONFIG['site']['vitrina_pricelock']) {
				if(@$CONFIG['site']['vitrina_cntlock']) {
					if(isset($CONFIG['site']['vitrina_sklad'])) {
						$sklad_id = round($CONFIG['site']['vitrina_sklad']);
						$res = $db->query("SELECT `doc_base_cnt`.`cnt` FROM `doc_base_cnt` WHERE `id`='{$item['pos_id']}' AND `sklad`='$sklad_id'");
					}
					else	$res = $db->query("SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `id`='{$item['pos_id']}'");
					if($res->num_rows) {
						$tmp = $res->fetch_row();
						$sklad_cnt = $tmp[0]-DocRezerv($item['pos_id']);
					}
					else	$sklad_cnt = DocRezerv($item['pos_id'])*(-1);
					
					if($item['cnt']>$sklad_cnt) {
						$lock = 1;
						$lock_mark = 1;
					}
				}
				if(@$CONFIG['site']['vitrina_pricelock']) {
					if(strtotime($pos_info['cost_date'])<(time()-60*60*24*30*6)) {
						$lock = 1;
						$lock_mark = 1;
					}
				}
			}
		}
		if($_SESSION['basket']['delivery_type']) {
			$res = $db->query("SELECT `service_id` FROM `delivery_types` WHERE `id`='$delivery'");
			list($d_service_id) = $res->fetch_row();
			$res = $db->query("SELECT `price` FROM `delivery_regions` WHERE `id`='$delivery_region'");
			list($d_price) = $res->fetch_row();
			$res = $db->query("INSERT INTO `doc_list_pos` (`doc`,`tovar`,`cnt`,`cost`,`comm`) VALUES ('$doc','$d_service_id','1','$d_price','')");
			$res = $db->query("SELECT `doc_base`.`id`, CONCAT(`doc_group`.`printname`, ' ', `doc_base`.`name`) AS `pos_name`
				FROM `doc_base`
				LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
				LEFT JOIN `class_unit` ON `class_unit`.`id`=`doc_base`.`unit`
				WHERE `doc_base`.`id`='$d_service_id'");
			$pos_info = $res->fetch_assoc();
			$order_items .= $pos_info['pos_name']." - $d_price руб.\n";
			$admin_items .= $pos_info['pos_name']." - $d_price руб.\n";
		}
		$zakaz_sum = DocSumUpdate($doc);
		$_SESSION['order_id'] = $doc;

		$text="На сайте {$CONFIG['site']['name']} оформлен новый заказ.\n";
		$text.="Посмотреть можно по ссылке: http://{$CONFIG['site']['name']}/doc.php?mode=body&doc=$doc\nIP отправителя: ".getenv("REMOTE_ADDR")."\nSESSION ID:".session_id();
		if(@$_SESSION['name']) $text.="\nLogin отправителя: ".$_SESSION['name'];
		$text.="----------------------------------\n".$admin_items;

		if($CONFIG['site']['doc_adm_jid'])
		{
			try
			{
				require_once($CONFIG['location'].'/common/XMPPHP/XMPP.php');
				$xmppclient = new XMPPHP_XMPP( $CONFIG['xmpp']['host'], $CONFIG['xmpp']['port'], $CONFIG['xmpp']['login'], $CONFIG['xmpp']['pass'], 'xmpphp', '');
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
		$user_msg.="--------------------------------------\n$order_items\n--------------------------------------\n";
		$user_msg.="\n\n\nСообщение отправлено роботом. Не отвечайте на это письмо.";

		if($email)
			mailto($email,"Message from {$CONFIG['site']['name']}", $user_msg);

		$tmpl->setContent("<h1 id='page-title'>Заказ оформлен</h1>");
		if(!$lock)
		{
			switch($pay_type)
			{
				case 'bank':
				case 'card_o':
				case 'credit_brs':
					$tmpl->addContent("<p>Заказ оформлен. Теперь вы можете оплатить его! <a href='/vitrina.php?mode=pay'>Перейти к оплате</a></p>");
				break;
				default:
					$tmpl->msg("Ваш заказ оформлен! Номер заказа: $doc/$altnum. Запомните или запишите его. С вами свяжутся в ближайшее время для уточнения деталей!");
			}
		}
		else $tmpl->msg("Ваш заказ оформлен! Номер заказа: $doc/$altnum. Запомните или запишите его. С вами свяжутся в ближайшее время для уточнения цены и наличия товара! Оплатить заказ будет возможно после его подтверждения оператором.");
		//unset($_SESSION['basket']);
		$basket->clear();
		$basket->save();
	}
	else $tmpl->msg("Ваша корзина пуста! Вы не можете оформить заказ! Быть может, Вы его уже оформили?","err");
}

protected function Payment() {
	global $tmpl, $CONFIG, $db;
	$order_id=$_SESSION['order_id'];
	settype($order_id,'int');

	$res=$db->query("SELECT `doc_list`.`id` FROM `doc_list`
	WHERE `doc_list`.`p_doc`='$order_id' AND (`doc_list`.`type`='4' OR `doc_list`.`type`='6') AND `doc_list`.`mark_del`='0'");
	if($res->num_rows)		$tmpl->msg("Этот заказ уже оплачен!");
	else {
		$res=$db->query("SELECT `doc_list`.`id`, `dd_pt`.`value` AS `pay_type`, `doc_list`.`altnum` FROM `doc_list`
		LEFT JOIN `doc_dopdata` AS `dd_pt` ON `dd_pt`.`doc`=`doc_list`.`id` AND `dd_pt`.`param`='pay_type'
		WHERE `doc_list`.`id`='$order_id' AND `doc_list`.`type`='3'");

		$order_info=$res->fetch_assoc();
		if($order_info['pay_type'] == 'card_o') {
			$init_url="{$CONFIG['gpb']['initial_url']}?lang=ru&merch_id={$CONFIG['gpb']['merch_id']}&back_url_s=http://{$CONFIG['site']['name']}/gpb_pay_success.php&back_url_f=http://{$CONFIG['site']['name']}/gpb_pay_failed.php&o.order_id=$order_id";
			header("Location: $init_url");
			exit();
		}
		else if($order_info['pay_type'] == 'credit_brs') {
			$res=$db->query("SELECT `doc_list_pos`.`tovar`, CONCAT(`doc_group`.`printname`, ' ', `doc_base`.`name`) AS `name`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`
			FROM `doc_list_pos`
			INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
			INNER JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
			WHERE `doc_list_pos`.`doc`=$order_id");
			$pos_line = '';
			$cnt = 0;
			while($line=$res->fetch_assoc()) {
				$cnt++;
				$pos_line.="&TC_$cnt={$line['cnt']}&TPr_$cnt={$line['cost']}&TName_$cnt=".urlencode($line['name']);
			}
			$url="{$CONFIG['credit_brs']['address']}?idTpl={$CONFIG['credit_brs']['id_tpl']}&TTName={$CONFIG['site']['name']}&Order=$order_id&TCount={$cnt}{$pos_line}";
			header("Location: $url");
			exit();
		}
		else if($order_info['pay_type']=='bank') {
			$tmpl->msg("Номер счёта: $order_id/{$order_info['altnum']}. Теперь Вам необходимо <a href='/vitrina.php?mode=print_schet'>получить счёт</a>, и оплатить его. После оплаты счёта Ваш заказ поступит в обработку.");
			$tmpl->addContent("<a href='?mode=print_schet'>Получить счёт</a>");
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
		$tmpl->addContent("<div class='pagebar'>");
		if($cur_page>1)
		{
			$i=$cur_page-1;
			$tmpl->addContent(" <a href='".$this->GetGroupLink($group, $i)."'>&lt;&lt;</a> ");
		}	else	$tmpl->addContent(" &lt;&lt; ");

		for($i=1;$i<$pages_count+1;$i++)
		{
			if($i==$cur_page) $tmpl->addContent(" $i ");
			else $tmpl->addContent(" <a href='".$this->GetGroupLink($group, $i)."'>$i</a> ");
		}
		if($cur_page<$pages_count)
		{
			$i=$cur_page+1;
			$tmpl->addContent(" <a href='".$this->GetGroupLink($group, $i)."'>&gt;&gt;</a> ");
		}	else	$tmpl->addContent(" &gt;&gt; ");
		$tmpl->addContent("</div>");
	}
}

/// Возвращает html код *хлебных крошек* витрины
/// @param group_id Текущая группа витрины
protected function GetVitPath($group_id)
{
	global $db;
	settype($group_id,'int');
	$res=$db->query("SELECT `id`, `name`, `pid` FROM `doc_group` WHERE `id`='$group_id'");
	$nxt=$res->fetch_row();
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

protected function priceCalcInit() {
	$pc = PriceCalc::getInstance();
	if($this->is_pc_init)
		return $pc;
	else $this->is_pc_init = 1;
	$basket = Models\Basket::getInstance();

	if(@$_SESSION['uid']) {
		$pc->setFromSiteFlag(1);
		$up = getUserProfile($_SESSION['uid']);
		$pc->setAgentId($up['main']['agent_id']);
	}
	
	if($basket->getCount()) {
		$basket_items = $basket->getItems();		
		$sum = 0;
		foreach ($basket_items as $item) {
			$sum += $pc->getPosDefaultPriceValue($item['pos_id']) * $item['cnt'];
		}
		$pc->setOrderSum($sum);
		$this->base_basket_sum = $sum;
	}
	return $pc;
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
// 		$res=mysql _query("SELECT `id` FROM `doc_base` WHERE `name`  LIKE '$pos' $proizv");
// 		@$pos=mysql _result($res,0,0);
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
	$tmpl->setTitle("Интернет - витрина");

	if(file_exists( $CONFIG['site']['location'].'/skins/'.$CONFIG['site']['skin'].'/vitrina.tpl.php' ) )
		include_once($CONFIG['site']['location'].'/skins/'.$CONFIG['site']['skin'].'/vitrina.tpl.php');
	if(!isset($vitrina))	$vitrina=new Vitrina();

	if(! $vitrina->ProbeRecode() )
	$vitrina->ExecMode($mode);
}
catch(Exception $e)
{
	global $db;
	$db->query("ROLLBACK");
	$tmpl->addContent("<br><br>");
	$tmpl->logger($e->getMessage());
}



$tmpl->write();



?>


