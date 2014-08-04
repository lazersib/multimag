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

require_once("core.php");
require_once("include/doc.core.php");

/// Класс, реализующий страницу поиска
class SearchPage
{
	var $search_str;	///< Искомая строка

	/// Конструктор
	/// @param search_str Искомая строка
	function __construct($search_str) {
		$this->search_str=$search_str;
	}

	/// Поиск товара
	/// @param s Подстрока поиска
	function SearchTovar($s) {
		global $CONFIG, $db;

		$ret='';
                $no_header = 1;
                $this->all_cnt = 30;
		$s_sql = $db->real_escape_string($s);
                   
                $sql="SELECT `doc_base`.`id`, `doc_group`.`printname`, `doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`cost`, `doc_base`.`cost_date`, `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`), `doc_base`.`transit_cnt`
		FROM `doc_base`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE (`doc_base`.`name` LIKE '$s_sql%' OR `doc_base`.`proizv` LIKE '$s%' OR `doc_base`.`vc` LIKE '$s_sql%' OR `doc_base_dop`.`analog` LIKE '$s_sql%') AND `doc_base`.`hidden`='0' AND `doc_group`.`hidelevel`='0'
                ORDER BY `doc_base`.`name`
		LIMIT 30";
		$res = $db->query($sql);
		
		if($row = $res->num_rows) {
                        if($no_header) {
                                $no_header = 0;
                                $ret.="<table width='100%' cellspacing='0' border='0' class='list'><tr><th>Наименование<th>Производитель<th>Аналог<th>Наличие
                                <th>Цена, руб<th>d, мм<th>D, мм<th>B, мм<th>m, кг<th>";
                        }
			$i=0;
			
			$ret.=$this->searchOut($res);
			
		}
                
                $limit = $this->all_cnt+5;
                
		$sql="SELECT `doc_base`.`id`, `doc_group`.`printname`, `doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`cost`, `doc_base`.`cost_date`, `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`), `doc_base`.`transit_cnt`
		FROM `doc_base`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE (`doc_base`.`name` LIKE '%$s_sql%' OR `doc_base`.`desc` LIKE '%$s_sql%' OR `doc_base`.`proizv` LIKE '%$s%' OR `doc_base_dop`.`analog` LIKE '%$s_sql%' OR `doc_base`.`vc` LIKE '%$s_sql%') AND `doc_base`.`hidden`='0' AND `doc_group`.`hidelevel`='0' AND (`doc_base`.`name` NOT LIKE '$s_sql%' AND `doc_base`.`proizv` NOT LIKE '$s%' AND `doc_base`.`vc` NOT LIKE '$s_sql%' AND `doc_base_dop`.`analog` NOT LIKE '$s_sql%')
                ORDER BY `doc_base`.`name`
		LIMIT 18";
		$res = $db->query($sql);
		
		if($row = $res->num_rows) {
                        if($no_header) {
                                $no_header = 0;
                                $ret.="<table width='100%' cellspacing='0' border='0' class='list'><tr><th>Наименование<th>Производитель<th>Аналог<th>Наличие
                                <th>Цена, руб<th>d, мм<th>D, мм<th>B, мм<th>m, кг<th>";
                        }
			$i=0;
			
			$ret.=$this->searchOut($res);
			
		}
                
                if(!$no_header) {
                        $ret.="</table>";
			if(@$CONFIG['site']['grey_price_days'])
				$ret.="<span style='color:#888'>Серая цена</span> требует уточнения<br>";
                }
                
		return $ret;
	}
        
        /// Вывод данных по поиску товаров
        function searchOut($res) {
                global $CONFIG;
                if(@$CONFIG['site']['grey_price_days'])
			$cce_time = $CONFIG['site']['grey_price_days'] * 60*60*24;
                $basket_img = "/skins/".$CONFIG['site']['skin']."/basket16.png";
                $pc = PriceCalc::getInstance();
                $ret = '';
                while($nxt=$res->fetch_row()) {
                        if($CONFIG['site']['recode_enable'])	$link= "/vitrina/ip/$nxt[0].html";
                        else					$link= "/vitrina.php?mode=product&amp;p=$nxt[0]";

                        $cost = $pc->getPosDefaultPriceValue($nxt[0]);
                        if($cost<=0)	$cost='уточняйте';
                        $nal=$this->GetCountInfo($nxt[12], $nxt[13]);

                        $cce = '';
                        if(@$CONFIG['site']['grey_price_days']) {
                                if( strtotime($nxt[5]) < $cce_time )
                                        $cce = ' style=\'color:#888\'';
                        }

                        $ret.="<tr><td><a href='$link'>".html_out("$nxt[1] $nxt[2]")."</a></td>
                        <td>".html_out($nxt[3])."</td><td>".html_out($nxt[6])."</td><td>$nal</td><td $cce>$cost</td><td>$nxt[8]</td><td>$nxt[9]</td><td>$nxt[10]</td><td>$nxt[11]</td><td>
                        <a href='/vitrina.php?mode=korz_add&amp;p={$nxt[0]}&amp;cnt=1' onclick=\"ShowPopupWin('/vitrina.php?mode=korz_adj&amp;p={$nxt[0]}&amp;cnt=1','popwin'); return false;\" rel='nofollow'><img src='$basket_img' alt='В корзину!'></a></td></tr>";
                        $this->all_cnt--;
                }
                return $ret;
        }

	/// Поиск по статьям
	/// @param s Подстрока поиска
	function SearchText($s)	{
		global $wikiparser, $db;
		$ret='';
		$i=1;
		$res=$db->query("SELECT `name`, `text` FROM `articles` WHERE `text` LIKE '%$s%' OR `name` LIKE '%$s'");
		while($nxt=$res->fetch_row())
		{
			$text	= $wikiparser->parse( $nxt[1] );
			$head	= $wikiparser->title;
			if($head=='')	$head=$nxt[0];
			$text	= strip_tags($text);
			$size	= 130;
			$text	= ". $text .";
			$pos	= mb_stripos($text, $s);
			if($pos===FALSE) $pos=0;
			$start	= $pos-$size;
			if($start<0) $start=0;
			$width	= $size*2;
			$str	= mb_substr ($text, $start, $width);
			$str_array=mb_split(' ',$str);
			$c	= '';
			$str	= '... ';
			foreach($str_array as $id => $elem)
			{
				if($id==0) continue;
				$str.=$c.' ';
				$c=$elem;
			}
			$str.=" ...";
			$str=mb_eregi_replace($s,"<b>$s</b>",$str);
			$ret.="<li><a href='/wiki/$nxt[0].html'>".html_out($head)."</a><br>$str</li>";
		}
		return $ret;
	}

	/// Формирование html кода формы поиска
	function SearchBlock()
	{
		$ret="<div class='searchblock'><h1>Поиск по сайту</h1>
		<form action='/search.php' method='get'>
		<input type='search' name='s' placeholder='Искать..' value='".html_out($this->search_str)."' class='sp' require> <input type='submit' value='Найти'><br>
		<a href='/adv_search.php?s=".html_out($this->search_str)."'>Расширенный поиск продукции</a>
		</form>
		</div>";
		return $ret;
	}

	/// Выполнить поиск с заданными параметрами
	function Exec()
	{
		global $tmpl;
		$tmpl->addContent($this->SearchBlock());
		if(mb_strlen($this->search_str)>=2)
		{
			$str=$this->SearchTovar($this->search_str);
			$tmpl->addContent("<h2>Поиск по предлагаемым товарам</h2>");
			if($str) $tmpl->addContent($str."<br><a href='/adv_search.php?s=".html_out($this->search_str)."'>Ещё товары по запросу *".html_out($this->search_str)."* &gt;&gt;&gt;</a>");
			else $tmpl->addContent("Не дал результатов");

			$str=$this->SearchText($this->search_str);
			$tmpl->addContent("<h2>Поиск по документации и статьям </h2>");
			if($str) $tmpl->addContent("<ol>$str</ol>");
			else $tmpl->addContent("Не дал результатов");
		}
		else if($this->search_str)	$tmpl->msg("Поисковый запрос слишком короткий!",'info');
	}

	/// Получить отображаемую информацию о количестве товара
	/// @param count Количество товара в наличиии
	/// @param tranzit Количество товара в пути
	/// @return Строка с информацией о наличии
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
}


try
{
	$s = request('s');
	$tmpl->setTitle("Поиск по сайту: ".$s);

	if(file_exists( $CONFIG['site']['location'].'/skins/'.$CONFIG['site']['skin'].'/search.tpl.php' ) )
		include_once($CONFIG['site']['location'].'/skins/'.$CONFIG['site']['skin'].'/search.tpl.php');
	if(!isset($search))	$search=new SearchPage($s);

	$search->Exec();
}
catch(mysqli_sql_exception $e) {
	$tmpl->ajax=0;
	$id = $tmpl->logger($e->getMessage(), 1);
	$tmpl->msg("Порядковый номер ошибки: $id<br>Сообщение передано администратору", 'err', "Ошибка в базе данных");
}
catch(Exception $e) {
	$db->rollback();
	$tmpl->addContent("<br><br>");
	$tmpl->logger($e->getMessage());
}


$tmpl->write();
?>