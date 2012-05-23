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

try
{

if(file_exists( $CONFIG['site']['location'].'/skins/'.$CONFIG['site']['skin'].'/index.tpl.php' ) )
	include_once($CONFIG['site']['location'].'/skins/'.$CONFIG['site']['skin'].'/index.tpl.php');
else
{

	include_once("include/doc.core.php");
	include_once("include/imgresizer.php");
	require_once("include/comments.inc.php");
	$tmpl->SetTitle($CONFIG['site']['display_name']);

	if(@$_SESSION['uid'])	$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='-1'");
	else			$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='1'");
	if(mysql_errno())	throw new MysqlException('Не удалось выбрать цену для пользователя');
	$cost_id=		mysql_result($res,0,0);
	if(!$cost_id)	$cost_id=1;


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
	}
	.pitem h2
	{
		margin:			3px;
		font-size:		16px;
	}
	");

	$res=mysql_query("SELECT `news`.`id`, `news`.`text`, `news`.`date`, `news`.`ex_date`, `news`.`img_ext` FROM `news` LIMIT 1");
	if(mysql_errno())	throw new MysqlException("Не удалось получить список новостей!");
	if(mysql_num_rows($res))
	{
		$tmpl->AddText("<table class='index-nsr'><tr>");

		$res=mysql_query("SELECT `news`.`id`, `news`.`text`, `news`.`date`, `news`.`ex_date`, `news`.`img_ext` FROM `news`
		WHERE `news`.`type`='stock'
		ORDER BY `date` DESC LIMIT 3");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список акций!");
		if(mysql_num_rows($res))
		{
			$tmpl->AddText("<td><h3>Акции</h3>");
			while($nxt=mysql_fetch_assoc($res))
			{
				$wikiparser->title='';
				$text=$wikiparser->parse(html_entity_decode($nxt['text'],ENT_QUOTES,"UTF-8"));
				if($nxt['img_ext'])
				{
					$miniimg=new ImageProductor($nxt['id'],'n', $nxt['img_ext']);
					$miniimg->SetX(50);
					$miniimg->SetY(50);
					$img="<img src='".$miniimg->GetURI()."' alt=''>";
				}
				else $img='';
				$text_a=mb_split( "[.!?]" , strip_tags($text), 2);
				if(@$text_a)	$text=$text_a[0]."...";
				$tmpl->AddText("<div class='news'><div class='image'><a href='/news.php?mode=read&amp;id={$nxt['id']}'>$img</a></div>
				<div class='text'><p class='date'>{$nxt['date']}</p><p class='title'><a href='/news.php?mode=read&amp;id={$nxt['id']}'>{$wikiparser->title}</a></p><p>$text</p></div>
				<div class='clear'></div>
				</div>");
			}
		}

		
		$res=mysql_query("SELECT `name`, `date`, `text`, `img_ext`  FROM `articles`
		WHERE `name` LIKE 'review:%'
		ORDER BY `date` DESC LIMIT 3");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список статей!");
		if(mysql_num_rows($res))
		{
			$tmpl->AddText("<td><h3>Обзоры</h3>");
			while($nxt=mysql_fetch_assoc($res))
			{
				$wikiparser->title='';
				$text=$wikiparser->parse(html_entity_decode($nxt['text'],ENT_QUOTES,"UTF-8"));
				if($nxt['img_ext'])
				{
					$miniimg=new ImageProductor($nxt['name'],'a', $nxt['img_ext']);
					$miniimg->SetX(50);
					$miniimg->SetY(50);
					$img="<img src='".$miniimg->GetURI()."' alt=''>";
				}
				else $img='';
				$text_a=mb_split( "[.!?]" , strip_tags($text), 2);
				if(@$text_a)	$text=$text_a[0]."...";
				$tmpl->AddText("<div class='news'><div class='image'><a href='/wiki/{$nxt['name']}'>$img</a></div>
				<div class='text'><p class='date'>{$nxt['date']}</p><p class='title'><a href='/wiki/{$nxt['name']}'>{$wikiparser->title}</a></p><p>$text</p></div>
				<div class='clear'></div>
				</div>");
			}
		}


		$res=mysql_query("SELECT `news`.`id`, `news`.`text`, `news`.`date`, `news`.`ex_date`, `news`.`img_ext` FROM `news`
		WHERE `news`.`type`=''
		ORDER BY `date` DESC LIMIT 3");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список новостей!");
		if(mysql_num_rows($res))
		{
			$tmpl->AddText("<td><h3><a href='/news.php'>Новости</a></h3>");
			while($nxt=mysql_fetch_assoc($res))
			{
				$wikiparser->title='';
				$text=$wikiparser->parse(html_entity_decode($nxt['text'],ENT_QUOTES,"UTF-8"));
				if($nxt['img_ext'])
				{
					$miniimg=new ImageProductor($nxt['id'],'n', $nxt['img_ext']);
					$miniimg->SetX(50);
					$miniimg->SetY(50);
					$img="<img src='".$miniimg->GetURI()."' alt=''>";
				}
				else $img='';
				$text_a=mb_split( "[.!?]" , strip_tags($text), 2);
				if(@$text_a)	$text=$text_a[0]."...";
				$tmpl->AddText("<div class='news'><div class='image'><a href='/news.php?mode=read&amp;id={$nxt['id']}'>$img</a></div>
				<div class='text'><p class='date'>{$nxt['date']}</p><p class='title'><a href='/news.php?mode=read&amp;id={$nxt['id']}'>{$wikiparser->title}</a></p><p>$text</p></div>
				<div class='clear'></div>
				</div>");
			}
		}
		$tmpl->AddText("</tr></table>");
	}

	$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`cost`, `doc_img`.`id` AS `img_id`, `doc_img`.`type` AS `img_type`, `class_unit`.`rus_name1` AS `units`, `doc_group`.`printname` AS `group_name` FROM `doc_base`
	LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
	LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
	LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
	LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
	WHERE `hidden`='0' AND `stock`!='0' LIMIT 12");
	if(mysql_errno())	throw new MysqlException("Выборка спецпредложений не удалась!");
	if(mysql_num_rows($res))
	{
		$tmpl->AddText("<h1>Спецпредложения</h1>
		<div class='sales'>");

		while($nxt=mysql_fetch_array($res))
		{
			if($CONFIG['site']['recode_enable'])	$link= "/vitrina/ip/$nxt[0].html";
			else					$link= "/vitrina.php?mode=product&amp;p=$nxt[0]";
			if($nxt['img_id'])
			{
				$miniimg=new ImageProductor($nxt['img_id'],'p', $nxt['img_type']);
				$miniimg->SetX(135);
				$miniimg->SetY(180);
				$img="<img src='".$miniimg->GetURI()."' style='float: left; margin-right: 10px;' alt='{$nxt['name']}'>";
			}
			else $img="<img src='/img/no_photo.png' alt='no photo' style='float: left; margin-right: 10px;'>";
			$cost=GetCostPos($nxt['id'], $cost_id);

			$tmpl->AddText("<div class='pitem'>
			<a href='$link'>$img</a>
			<h2><a href='$link'>{$nxt['group_name']} {$nxt['name']}</a></h2>
			<b>Цена:</b> $cost руб / {$nxt['units']}<br>
			<a href='/vitrina.php?mode=korz_add&amp;p={$nxt['id']}&amp;cnt=1' onclick=\"return ShowPopupWin('/vitrina.php?mode=korz_adj&amp;p={$nxt['id']}&amp;cnt=1','popwin');\" rel='nofollow'>В корзину!</a>
			</div>");
		}
		$tmpl->AddText("<div class='clear'><br></div>
		</div>");
	}

	$tmpl->AddText("<h1>Популярные товары</h1>");

	$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`desc`, `doc_base`.`cost`, `doc_img`.`id` AS `img_id`, `doc_base`.`likvid`, `doc_img`.`type` AS `img_type`, ( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base`.`id`) AS `count`, `class_unit`.`rus_name1` AS `units` FROM `doc_base`
	LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
	LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
	LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
	WHERE `hidden`='0'
	ORDER BY `likvid` DESC
	LIMIT 12");
	if(mysql_errno())	throw new MysqlException("Выборка популярных товаров не удалась!");
	$i=1;
	while($nxt=mysql_fetch_array($res))
	{
		if($nxt['cost']==0)	continue;
		if($CONFIG['site']['recode_enable'])	$link= "/vitrina/ip/$nxt[0].html";
		else					$link= "/vitrina.php?mode=product&amp;p=$nxt[0]";
		if($nxt['img_id'])
		{
			$miniimg=new ImageProductor($nxt['img_id'],'p', $nxt['img_type']);
			$miniimg->SetX(135);
			$miniimg->SetY(180);
			$img="<img src='".$miniimg->GetURI()."' style='float: left; margin-right: 10px;' alt='{$nxt['name']}'>";
		}
		else $img="<img src='/img/no_photo.png' alt='no photo'  style='float: left; margin-right: 10px;'>";
		$cost=GetCostPos($nxt['id'], $cost_id);

		$tmpl->AddText("<div class='pitem'>
		<a href='$link'>$img</a>
		<h2>{$nxt['name']}</h2>
		<b>Цена:</b> $cost руб / {$nxt['units']}<br>
		<a href='/vitrina.php?mode=korz_add&amp;p={$nxt['id']}&amp;cnt=1' onclick=\"return ShowPopupWin('/vitrina.php?mode=korz_adj&amp;p={$nxt['id']}&amp;cnt=1','popwin');\" rel='nofollow'>В корзину!</a>
		</div>");

		$i++;
	}
	$tmpl->AddText("<div class='clear'><br></div>");
}

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


