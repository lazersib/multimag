<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
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

try
{
	$tmpl->ajax=1;
	$yml_now = date("Y-m-d H:i");
        $pref = \pref::getInstance();
	$res = $db->query("SELECT * FROM `doc_vars` WHERE `id`='{$pref->site_default_firm_id}'");
	if(!$res->num_rows)	throw new Exception("Организация не найдена");
	$firm_vars = $res->fetch_assoc();

	$pc = PriceCalc::getInstance();
        $pc->setFirmId($pref->site_default_firm_id);
	
	$finds=array('"', '&', '>', '<', '\'');
	$replaces=array('&quot;', '&amp;', '&gt;', '&lt;', '&apos;');

	header("Content-type: text/xml");
	
	if(isset($CONFIG['site']['yml_local_delivery_cost']))
		$delivery_cost = intval($CONFIG['site']['yml_local_delivery_cost']);
	else	$delivery_cost = 0;
	
	echo"<?xml version=\"1.0\" encoding=\"utf-8\"?>
	<!DOCTYPE yml_catalog SYSTEM \"shops.dtd\">
	<yml_catalog date=\"$yml_now\">
	<shop>
	<name>{$pref->site_display_name}</name>
	<company>{$firm_vars['firm_name']}</company>
	<url>http://{$pref->site_name}/</url>
	<platform>MultiMag</platform>
	<version>".MULTIMAG_VERSION."</version>
	<agency>{$pref->site_display_name}</agency>
	<email>{$pref->site_email}</email>

	<currencies>
	<currency id=\"RUR\" rate=\"1\"/>
	</currencies>
	
	<categories>\n";

	$res=$db->query("SELECT `id`, `name`, `pid` FROM `doc_group` WHERE `hidelevel`='0' AND `no_export_yml`='0' ORDER BY `id`");
	while($nxt=$res->fetch_row())
	{
		$nxt[1]=html_entity_decode($nxt[1],ENT_QUOTES,"UTF-8");
		$nxt[1]=str_replace($finds, $replaces, $nxt[1]);
		$pid=($nxt[2]>0)?"parentId=\"$nxt[2]\"":'';
		echo"<category id=\"$nxt[0]\" $pid>$nxt[1]</category>\n";
	}
	echo"</categories>
	<local_delivery_cost>{$CONFIG['ymarket']['local_delivery_cost']}</local_delivery_cost>
	<offers>";
	$cols_add=$join_add='';
	if(@$CONFIG['ymarket']['av_from_prices'])
	{
		$cols_add=", `parsed_price`.`nal`, `firm_info`.`delivery_info`";
		$join_add="LEFT JOIN `parsed_price` ON `parsed_price`.`pos`=`doc_base`.`id` AND `parsed_price`.`selected`='1'
		LEFT JOIN `firm_info` ON `firm_info`.`id`=`parsed_price`.`firm`";
	}
	$res=$db->query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`group`, `doc_base`.`vc`, `doc_base`.`proizv`, `doc_img`.`id`  AS `img_id`, `doc_base`.`desc`, `class_country`.`name` AS `strana`, ( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id`) AS `sklad_nal`, `doc_base`.`cost`, `doc_base`.`warranty_type`, `doc_img`.`type` AS `img_type`, `doc_group`.`printname` AS `group_name` $cols_add
	FROM `doc_base`
	INNER JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
	LEFT JOIN `class_country` ON `doc_base`.`country` = `class_country`.`id`
	LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
	LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	$join_add
	WHERE `doc_base`.`hidden`='0' AND `doc_group`.`hidelevel`='0' AND `doc_base`.`no_export_yml`='0' AND `doc_group`.`no_export_yml`='0'
	GROUP BY `doc_base`.`id`");
	while($nxt=$res->fetch_assoc()) {
		$avariable=($nxt['sklad_nal']>0)?'true':'false';
		if(@$CONFIG['ymarket']['av_from_prices'])
			if($nxt['nal']>1 || strstr($nxt['nal'],'*') || strstr($nxt['nal'],'+'))
				if($nxt['delivery_info']=='+')	$avariable='true';

		if($CONFIG['site']['recode_enable'])	$url= "http://{$pref->site_name}/vitrina/ip/{$nxt['id']}.html";
		else					$url= "http://{$pref->site_name}/vitrina.php?mode=product&amp;p={$nxt['id']}";

		$cost = $pc->getPosDefaultPriceValue($nxt['id']);
	
		if($cost==0)		continue;
		if($nxt['img_id']) {
			$miniimg=new ImageProductor($nxt['img_id'],'p', $nxt['img_type']);
			$miniimg->SetX(200);
			
			$picture="<picture>http://{$pref->site_name}".$miniimg->GetURI()."</picture>";
		}
		else	$picture='';
		
		if($nxt['group_name'])
			$nxt['name'] = $nxt['group_name'].' '.$nxt['name'];
		if(@!$CONFIG['doc']['no_print_vendor'])
			$nxt['name'] .= ' - '.$nxt['proizv'];
		
		$nxt['name']=html_entity_decode($nxt['name'],ENT_QUOTES,"UTF-8");
		$nxt['name']=str_replace($finds, $replaces, $nxt['name']);
		$nxt['proizv']=html_entity_decode($nxt['proizv'],ENT_QUOTES,"UTF-8");
		$nxt['proizv']=str_replace($finds, $replaces, $nxt['proizv']);	
		$nxt['desc']=html_entity_decode($nxt['desc'],ENT_QUOTES,"UTF-8");
		$nxt['desc']=str_replace($finds, $replaces, $nxt['desc']);
		$nxt['strana']=html_entity_decode($nxt['strana'],ENT_QUOTES,"UTF-8");
		$nxt['strana']=str_replace($finds, $replaces, $nxt['strana']);
		$nxt['warranty_type']=$nxt['warranty_type']?'true':'false';
		
		$coo=($nxt['strana'])?"<country_of_origin>{$nxt['strana']}</country_of_origin>":'';
		
		$param='';
		$param_res=$db->query("SELECT `doc_base_params`.`name`, `doc_base_values`.`value` FROM `doc_base_values`
		LEFT JOIN `doc_base_params` ON `doc_base_params`.`id`=`doc_base_values`.`param_id`
		WHERE `doc_base_values`.`id`='{$nxt['id']}'");
		while($params=$param_res->fetch_row())
		{
			$params[1]=html_entity_decode($params[1],ENT_QUOTES,"UTF-8");
			$params[1]=str_replace($finds, $replaces, $params[1]);
			$param.="<param name=\"$params[0]\">$params[1]</param>\n";
		}
		
		
		echo"<offer id=\"{$nxt['id']}\" available=\"$avariable\">
		<url>$url</url>
		<price>$cost</price>
		<currencyId>RUR</currencyId>
		<categoryId>{$nxt['group']}</categoryId>
		$picture
		<store>true</store>
		<pickup>true</pickup>
		<delivery>true</delivery>
		<name>{$nxt['name']}</name>
		<vendor>{$nxt['proizv']}</vendor>
		<vendorCode>{$nxt['vc']}</vendorCode>
		<manufacturer_warranty>{$nxt['warranty_type']}</manufacturer_warranty>
		$coo
		</offer>\n";
	//<description>{$nxt['desc']}</description>
	}
	echo"</offers>
	</shop>
	</yml_catalog>";

}
catch(mysqli_sql_exception $e) {
	$db->rollback();
	$tmpl->ajax=0;
	$id = writeLogException($e);
	$tmpl->msg("Порядковый номер ошибки: $id<br>Сообщение передано администратору", 'err', "Ошибка в базе данных");
}
catch(Exception $e)
{
    $db->rollback();
    $tmpl->addContent("<br><br>");
    writeLogException($e);
    $tmpl->errorMessage($e->getMessage());
}
