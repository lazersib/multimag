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
$s=rcv('s');

$tmpl->AddStyle("
.searchblock
{
	background-color: #e0f0ff;
	width:	95%;
	height:	100px;
	margin:	10px;
	padding:5px;
	border: 1px rgb(39,78,144) solid;
}

.searchblock h1
{
	margin: 4px;
	margin-left: 10px;
	padding: 0px;
}

.searchblock .sp
{
	width: 90%;
}

");

$tmpl->SetTitle("Поиск по сайту: ".$s);
$tmpl->AddText("<div class='searchblock'><h1>Поиск по сайту</h1>
<form action='/search.php' method='get'>
<input type='text' name='s' value='$s' class='sp'> <input type='submit' value='Найти'><br>
<a href='/adv_search.php?s=$s'>Расширенный поиск продукции</a>
</form>
</div>");

if(strlen($s)>=3)
{
	mb_internal_encoding("UTF-8");
	
	$str=SearchTovar($s);
	$tmpl->AddText("<h2>Поиск по предлагаемым товарам</h2>");
	if($str) $tmpl->AddText($str."<br><a href='/adv_search.php?s=$s'>Ещё товары по запросу *$s* &gt;&gt;&gt;</a>");
	else $tmpl->AddText("Не дал результатов");
	
	$str=SearchText($s);
	$tmpl->AddText("<h2>Поиск по документации и статьям </h2>");
	if($str) $tmpl->AddText("<ol>$str</ol>");
	else $tmpl->AddText("Не дал результатов");






}
else if($s)	$tmpl->msg("Поисковый запрос слишком короткий!");

function SearchTovar($s)
{
	global $uid;
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
	echo mysql_error();
	if($row=mysql_num_rows($res))
	{
		$ret.="<table width='100%' cellspacing='0' border='0'><tr><th>Наименование<th>Производитель<th>Аналог<th>Наличие
		<th>Цена, руб<th>d, мм<th>D, мм<th>B, мм<th>m, кг<th>";
		$i=0;
		$cl="lin0";
		while($nxt=mysql_fetch_row($res))
		{
			$i=1-$i;
			$cost = GetCostPos($nxt[0], $c_cena_id);;
			
			if($nxt[12]<=0)
			{
				if($rrr[13]) $nal='в пути';
				else	$nal="";
			}
			else if($nxt[12]==1) $nal="мало";
			else if($nxt[12]<10) $nal="есть";
			else if($nxt[12]<100) $nal="много";
			else $nal="оч.много";
			
			$dcc=strtotime($nxt[5]);
			$cce='';
			if($dcc<(time()-60*60*24*30*6)) $cce="style='color:#888'";
			$ret.="<tr class=lin$i><td><a href='vitrina.php?mode=info&amp;p=$nxt[0]'>$nxt[1] $nxt[2]</a>
			<td>$nxt[3]<td>$nxt[6]<td>$nal<td $cce>$cost<td>$nxt[8]<td>$nxt[9]<td>$nxt[10]<td>$nxt[11]<td>
			<a href='/vitrina.php?mode=korz_add&amp;p=$nxt[0]&amp;cnt=1'><img src='img/i_korz.png' alt='В корзину!'></a>";
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
	$ret='';
	$i=1;
	$res=mysql_query("SELECT `name`, `text` FROM `wiki` WHERE `text` LIKE '%$s%' OR `name` LIKE '%$s'");
	while($nxt=mysql_fetch_row($res))
	{
		$text=$wikiparser->parse(html_entity_decode($nxt[1],ENT_QUOTES,"UTF-8"));
		$head=$wikiparser->title;
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




$tmpl->write();
?>
