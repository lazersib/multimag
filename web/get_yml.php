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

$tmpl->ajax=1;

$yml_now=date("Y-m-d H:i");

$res=mysql_query("SELECT * FROM `doc_vars` WHERE `id`='{$CONFIG['site']['default_firm']}'");
$firm_vars=mysql_fetch_assoc($res);

$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='1'");
$cost_id=		@mysql_result($res,0,0);
if($cost_id)		$cost_id=1;

$finds=array('"', '&', '>', '<', '\'');
$replaces=array('&quot;', '&amp;', '&gt;', '&lt;', '&apos;');

header("Content-type: text/xml");

echo"<?xml version=\"1.0\" encoding=\"utf-8\"?>
<!DOCTYPE yml_catalog SYSTEM \"shops.dtd\">
<yml_catalog date=\"$yml_now\">
<shop>
<name>{$CONFIG['site']['display_name']}</name>
<company>{$firm_vars['firm_name']}</company>
<url>http://{$CONFIG['site']['name']}/</url>
<platform>MultiMag</platform>
<version>".MULTIMAG_VERSION."</version>
<agency>{$CONFIG['site']['admin_name']}</agency>
<email>{$CONFIG['site']['admin_email']}</email>

<currencies>
<currency id=\"RUR\" rate=\"1\"/>
</currencies>
<categories>\n";
$res=mysql_query("SELECT `id`, `name`, `pid` FROM `doc_group` WHERE `hidelevel`='0' ORDER BY `id`");
if(mysql_errno())	throw new MysqlException("Не удалось получить список групп!");
while($nxt=mysql_fetch_row($res))
{
	$nxt[0]=html_entity_decode($nxt[0],ENT_QUOTES,"UTF-8");
	$nxt[0]=str_replace($finds, $replaces, $nxt[0]);
	$pid=($nxt[2]>0)?"parentId=\"$nxt[2]\"":'';
	echo"<category id=\"$nxt[0]\" $pid>$nxt[1]</category>\n";
}
echo"</categories>
<local_delivery_cost>{$CONFIG['ymarket']['local_delivery_cost']}</local_delivery_cost>
<offers>";
$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`group`, `doc_base`.`vc`, `doc_base`.`proizv`, `doc_img`.`id`  AS `img_id`, `doc_base`.`desc`, `doc_base_dop`.`strana`, ( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id`) AS `nal`, `doc_base`.`cost`, `doc_base`.`warranty_type`
FROM `doc_base`
INNER JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
LEFT JOIN `doc_base_img` ON `doc_base_img`.`pos_id`=`doc_base`.`id` AND `doc_base_img`.`default`='1'
LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
WHERE `doc_base`.`hidden`='0' AND `doc_group`.`hidelevel`='0'");
if(mysql_errno())	throw new MysqlException("Не удалось получить список товаров!");
while($nxt=mysql_fetch_assoc($res))
{
	$avariable=($nxt['nal']>0)?'true':'false';
	if($CONFIG['site']['recode_enable'])	$url= "http://{$CONFIG['site']['name']}/vitrina/ip/{$nxt['id']}.html";
	else					$url= "http://{$CONFIG['site']['name']}/vitrina.php?mode=product&amp;p={$nxt['id']}";
	$cost=GetCostPos($nxt['id'], $cost_id);
	if($nxt['cost']==0)	continue;
	if($cost==0)		continue;
	$picture=($nxt['img_id'])?"<picture>http://{$CONFIG['site']['name']}/vitrina.php?mode=img&amp;p={$nxt['id']}&amp;x=200</picture>":'';

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
	$param_res=mysql_query("SELECT `doc_base_params`.`param`, `doc_base_values`.`value` FROM `doc_base_values`
	LEFT JOIN `doc_base_params` ON `doc_base_params`.`id`=`doc_base_values`.`param_id`
	WHERE `doc_base_values`.`id`='{$nxt['id']}'");
	if(mysql_errno())	throw new MysqlException("Не удалось получить список свойств!");
	while($params=mysql_fetch_row($param_res))
	{
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
//    <description>{$nxt['desc']}</description>
}
echo"</offers>
</shop>
</yml_catalog>";


?>
