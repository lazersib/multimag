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

class SearchPage
{
	var $search_str;
	
	function __construct($search_str)
	{
		$this->search_str=$search_str;
	}

	function SearchTovar($s)
	{
		global $uid, $CONFIG;
		if($uid)
			$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='-1'");
		else
			$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='1'");
		$c_cena_id=mysql_result($res,0,0);
		
		$ret='';
		$sql="SELECT `doc_base`.`id`, `doc_group`.`printname`, `doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`cost`, `doc_base`.`cost_date`, `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`, (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`), `doc_base_dop`.`tranzit`
		FROM `doc_base`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE (`doc_base_dop`.`analog` LIKE '%$s%' OR `doc_base`.`name` LIKE '%$s%' OR `doc_base`.`desc` LIKE '%$s%' OR `doc_base`.`proizv` LIKE '%$s%' OR `doc_base_dop`.`analog` LIKE '%$s%') AND `doc_base`.`hidden`='0' AND `doc_group`.`hidelevel`='0'
		LIMIT 20";
		$res=mysql_query($sql);
		if(mysql_errno())	throw new MysqlException("Не удалось сделать выборку товаров");
		if($row=mysql_num_rows($res))
		{
			$ret.="<table width='100%' cellspacing='0' border='0' class='list'><tr><th>Наименование<th>Производитель<th>Аналог<th>Наличие
			<th>Цена, руб<th>d, мм<th>D, мм<th>B, мм<th>m, кг<th>";
			$i=0;
			$cl="lin0";
			while($nxt=mysql_fetch_row($res))
			{
				if($CONFIG['site']['recode_enable'])	$link= "/vitrina/ip/$nxt[0].html";
				else					$link= "/vitrina.php?mode=product&amp;p=$nxt[0]";
				
				$i=1-$i;
				$cost = GetCostPos($nxt[0], $c_cena_id);;
				$nal=$this->GetCountInfo($nxt[12], $nxt[13]);
				
				$dcc=strtotime($nxt[5]);
				$cce='';
				if($dcc<(time()-60*60*24*30*6)) $cce="style='color:#888'";
				$ret.="<tr class=lin$i><td><a href='$link'>$nxt[1] $nxt[2]</a>
				<td>$nxt[3]<td>$nxt[6]<td>$nal<td $cce>$cost<td>$nxt[8]<td>$nxt[9]<td>$nxt[10]<td>$nxt[11]<td>
				<a href='/vitrina.php?mode=korz_add&amp;p={$nxt[0]}&amp;cnt=1' onclick=\"ShowPopupWin('/vitrina.php?mode=korz_adj&amp;p={$nxt[0]}&amp;cnt=1','popwin'); return false;\" rel='nofollow'><img src='/img/i_korz.png' alt='В корзину!'></a>";
				$sf++;
				$cc=1-$cc;
				$cl="lin".$cc;
			}
			$ret.="</table><span style='color:#888'>Серая цена</span> требует уточнения<br>";
		}
		return $ret;
	}

	function SearchText($s)
	{
		global $wikiparser;
		mb_internal_encoding("UTF-8");
		$ret='';
		$i=1;
		$res=mysql_query("SELECT `name`, `text` FROM `wiki` WHERE `text` LIKE '%$s%' OR `name` LIKE '%$s'");
		while($nxt=mysql_fetch_row($res))
		{
			$text=$wikiparser->parse(html_entity_decode($nxt[1],ENT_QUOTES,"UTF-8"));
			$head=$wikiparser->title;
			if($head=='')	$head=$nxt[0];
			$text=strip_tags($text);
			$size=130;
			$text=". $text .";
			$pos= mb_stripos($text, $s);
			if($pos===FALSE) $pos=0;
			$start=$pos-$size;
			if($start<0) $start=0;
			$width=$size*2;
			$str=mb_substr ($text, $start, $width);
			$str_array=mb_split(' ',$str);
			$c='';
			$str='... ';
			foreach($str_array as $id => $elem)
			{
				if($id==0) continue;
				$str.=$c.' ';
				$c=$elem;
			}
			$str.=" ...";	
			$str=mb_eregi_replace($s,"<b>$s</b>",$str);	
			$ret.="<li><a href='/wiki/$nxt[0].html'>$head</a><br>$str</li>";		
		}
		return $ret;
	}
	
	function SearchBlock()
	{
		$ret="<div class='searchblock'><h1>Поиск по сайту</h1>
		<form action='/search.php' method='get'>
		<input type='text' name='s' value='{$this->search_str}' class='sp'> <input type='submit' value='Найти'><br>
		<a href='/adv_search.php?s={$this->search_str}'>Расширенный поиск продукции</a>
		</form>
		</div>";
		return $ret;
	}
	
	function Exec()
	{
		global $tmpl;
		
		$tmpl->AddText($this->SearchBlock());
		if(strlen($this->search_str)>=2)
		{
			$str=$this->SearchTovar($this->search_str);
			$tmpl->AddText("<h2>Поиск по предлагаемым товарам</h2>");
			if($str) $tmpl->AddText($str."<br><a href='/adv_search.php?s={$this->search_str}'>Ещё товары по запросу *{$this->search_str}* &gt;&gt;&gt;</a>");
			else $tmpl->AddText("Не дал результатов");
			
			$str=$this->SearchText($this->search_str);
			$tmpl->AddText("<h2>Поиск по документации и статьям </h2>");
			if($str) $tmpl->AddText("<ol>$str</ol>");
			else $tmpl->AddText("Не дал результатов");
		}
		else if($this->search_str)	$tmpl->msg("Поисковый запрос слишком короткий!");
	}
	
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
	$s=rcv('s');
	$tmpl->SetTitle("Поиск по сайту: ".$s);
	
	if(file_exists( $CONFIG['site']['location'].'/skins/'.$CONFIG['site']['skin'].'/search.tpl.php' ) )
		include_once($CONFIG['site']['location'].'/skins/'.$CONFIG['site']['skin'].'/search.tpl.php');
	if(!isset($search))	$search=new SearchPage($s);
	
	$search->Exec();
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
