#!/usr/bin/php
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

$c=explode('/',__FILE__);$base_path='';
for($i=0;$i<(count($c)-2);$i++)	$base_path.=$c[$i].'/';
require_once("$base_path/config_cli.php");

require_once($CONFIG['cli']['location']."/core.cli.inc.php");

include_once($CONFIG['site']['location']."/include/doc.core.php");
include_once($CONFIG['site']['location']."/include/doc.nulltype.php");
include_once($CONFIG['site']['location']."/include/doc.postuplenie.php");
include_once($CONFIG['site']['location']."/include/doc.realizaciya.php");
include_once($CONFIG['site']['location']."/include/doc.zayavka.php");
include_once($CONFIG['site']['location']."/include/doc.rbank.php");
include_once($CONFIG['site']['location']."/include/doc.pbank.php");
include_once($CONFIG['site']['location']."/include/doc.rko.php");
include_once($CONFIG['site']['location']."/include/doc.pko.php");
include_once($CONFIG['site']['location']."/include/doc.peremeshenie.php");
include_once($CONFIG['site']['location']."/include/doc.perkas.php");
include_once($CONFIG['site']['location']."/include/doc.sborka.php");
include_once($CONFIG['site']['location']."/include/doc.kordolga.php");

$mail_text='';

$tim=time();
$dtim=time()-60*60*24*365;

mysql_query("REPAIR TABLE `doc_agent`");
mysql_query("REPAIR TABLE `doc_base`");
mysql_query("REPAIR TABLE `doc_list`");
mysql_query("REPAIR TABLE `doc_list_pos`");
mysql_query("UPDATE `variables` SET `corrupted`='1'");
$res=mysql_query("SELECT `version` FROM `db_version`");
if(mysql_errno())
{
	$text="Не удалось получить версию базы данных!\nЭто само по себе не является серьёзной ошибкой, но может указывать на нарушение целостности базы данных.\n";
	$mail_text.=$text;
	echo $text;
}
else
{
	$db_version=@mysql_result($res,0,0);
	if($db_version!=MULTIMAG_REV)
	{
		$text="Версия базы данных не соответствует ревизии программы. Это может привести к ошибкам в работе. Версия базы: $db_version, ревизия программы: ".MULTIMAG_REV." (".MULTIMAG_VERSION.")\n";
		$mail_text.=$text;
		echo $text;
	}
}

echo"Сброс остатков...";
$res=mysql_query("SELECT `doc_base`.`id`, (SELECT SUM(`doc_list_pos`.`cnt`) FROM `doc_list_pos`
INNER JOIN `doc_list` ON `doc_list`.`type`='12' AND `doc_list`.`ok`>'0'
AND `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`id` NOT IN (SELECT DISTINCT `p_doc` FROM `doc_list` WHERE `ok` != '0' AND `type`='1' )
WHERE `doc_list_pos`.`tovar`=`doc_base`.`id` GROUP BY `doc_list_pos`.`tovar`) FROM `doc_base`");
while($nxt=mysql_fetch_row($res))
{
    mysql_query("UPDATE `doc_list_pos` SET `tranzit`='$nxt[1]' WHERE `id`='$nxt[0]'");
}
echo" готово!\n";

// ============== Расчет ликвидности ===================================================
$starttime=time();
echo"Расчет ликвидности...";
$res=mysql_query("SELECT `doc_list_pos`.`tovar`, COUNT(`doc_list_pos`.`tovar`) AS `aa`
FROM `doc_list_pos`, `doc_list`
WHERE `doc_list_pos`.`doc`= `doc_list`.`id` AND (`doc_list`.`type`='2' OR `doc_list`.`type`='3') AND `doc_list`.`date`>'$dtim'
GROUP BY `doc_list_pos`.`tovar`
 ORDER BY `aa` DESC");
$nxt=mysql_fetch_row($res);
$max=$nxt[1]/100;
mysql_query("CREATE TEMPORARY TABLE IF NOT EXISTS `doc_base_likv_update` (
  `id` int(11) NOT NULL auto_increment,
  `likvid` double NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
echo mysql_error();
$rs=mysql_query("UPDATE `doc_base` SET `likvid`='0'");
$res=mysql_query("SELECT `doc_list_pos`.`tovar`, COUNT(`doc_list_pos`.`tovar`)
FROM `doc_list_pos`, `doc_list`
WHERE `doc_list_pos`.`doc`= `doc_list`.`id` AND (`doc_list`.`type`='2' OR `doc_list`.`type`='3') AND `doc_list`.`date`>'$dtim'
GROUP BY `doc_list_pos`.`tovar`");

while($nxt=mysql_fetch_row($res))
{
 	$l=$nxt[1]/$max;
 	mysql_unbuffered_query("INSERT INTO `doc_base_likv_update` VALUES ( $nxt[0], $l)");
 	if(mysql_errno())	echo mysql_error();
}

mysql_unbuffered_query("UPDATE `doc_base`,`doc_base_likv_update` SET `doc_base`.`likvid`=`doc_base_likv_update`.`likvid`  WHERE `doc_base`.`id`=`doc_base_likv_update`.`id`");
if(mysql_errno())	echo mysql_error();
echo" сделано!\n";

global $badpos;

function seek_and_up($date,$pos)
{
	$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date` FROM `doc_list`
	INNER JOIN `doc_list_pos` ON `doc_list_pos`.`tovar`='$pos' AND `doc_list_pos`.`doc`=`doc_list`.`id`
	WHERE `doc_list`.`date`>'$date' AND `doc_list`.`type`='1'");
	@$doc=mysql_result($res,0,0);
	@$dt=mysql_result($res,0,1);
	
	if($doc)
	{
		$dtn=$date-60;
		mysql_query("UPDATE `doc_list` SET `date`='$dtn' WHERE `id`='$doc'");
	}
	else return "Не найдено!";
	return $doc." ".date("d.m.Y H:i:s",$dt);
}


// ================================ Перепроводка документов с коррекцией сумм ============================
echo"Перепроводка документов...\n\n";
$i=0;
$res=mysql_query("UPDATE `doc_kassa` SET `ballance`='0'");
$res=mysql_query("UPDATE `doc_base_cnt` SET `cnt`='0'");
$res=mysql_query("SELECT `id`, `type`, `altnum`, `date` FROM `doc_list` WHERE `ok`>'0' AND `type`!='3' AND `mark_del`='0' ORDER BY `date`");
$allcnt=mysql_num_rows($res);
$opp=$cnt=0;

while($nxt=mysql_fetch_row($res))
{
	$dt=date("d.m.Y H:i:s",$nxt[3]);
	$pp=round(($cnt/$allcnt)*100);
	if($pp!=$opp)
	{
		$opp=$pp;
		$remains=(time()-$starttime)*(100/$pp-1);
		$remainm=round($remains/60);
		$remains%=60;
		if($remainm)	echo"\rВыполнено $pp% (осталось около $remainm мин. $remains сек.)      ";
		else		echo"\rВыполнено $pp% (осталось около $remains сек.)     ";
	}
	$cnt++;
	$document=AutoDocumentType($nxt[1],$nxt[0]);
	if($err=$document->Apply($nxt[0],1))
	{
		mysql_query("UPDATE `doc_list` SET `err_flag`='1' WHERE `id`='$nxt[0]'");
		$text="$nxt[0](".$document->getViewName()." N $nxt[2] от $dt): $err ЭТО КРИТИЧЕСКАЯ ОШИБКА! ОСТАТКИ НА СКЛАДЕ, В КАССАХ, И БАНКАХ НЕВЕРНЫ!\n";
		echo $text;
		$mail_text.=$text;
		$i++;
	}
}
if($i)
{
	$text="-----------------------\nИтого: $i документов в минус!\n";
	echo $text;
	$mail_text.=$text;
}
else echo"Ошибки последовательности документов не найдены!\n";


echo "Удаление помеченных на удаление...\n";
// ============================= Удаление помеченных на удаление =========================================
$tim_minus=time()-60*60*24*$CONFIG['auto']['doc_del_days'];
$res=mysql_query("SELECT `id`, `type` FROM `doc_list` WHERE `mark_del`<'$tim_minus' AND `mark_del`>'0'");
while($nxt=mysql_fetch_row($res))
{
	try
	{
		$document=AutoDocumentType($nxt[1], $nxt[0]);
		$document->DelExec($nxt[0]);
		echo "Док. ID:$nxt[0],type:$nxt[1] удалён\n";
	}
	catch(Exception $e)
	{
		$text="Док. ID:$nxt[0],type:$nxt[1], ошибка удаления: ".$e->getMessage()."\n";
		echo $text;
	}
}

$res=mysql_query("SELECT `doc_list_pos`.`tovar`, `doc_list`.`id`, `doc_agent`.`name`, `doc_list_pos`.`id`, `doc_base`.`name` FROM `doc_list_pos`
INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`type`='1' AND `doc_list`.`ok`>'0' 
LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
WHERE `doc_list_pos`.`cost`<='0' ");
while($nxt=mysql_fetch_row($res))
{
	$text="Поступление ID:$nxt[1], товар $nxt[0]($nxt[4]) - нулевая цена! Агент $nxt[2]\n";
	echo $text;
	$mail_text.=$text;
}


if($mail_text)
{
	try
	{
		$mail_text="При автоматической проверке базы данных сайта найдены следующие проблемы:\n****\n\n".$mail_text."\n\n****\nНеобходимо исправить найденные ошибки!";

		mailto($CONFIG['site']['doc_adm_email'], "DB check report", $mail_text);
		echo "Почта отправлена!";
		mysql_query("UPDATE `variables` SET `corrupted`='1'");
	}
	catch(Exception $e) 
	{
		echo"Ошибка отправки почты!".$e->getMessage();
	}
}
else
{
	echo"Ошибок не найдено, не о чем оповещать!\n";
	mysql_query("UPDATE `variables` SET `corrupted`='0'");
}


?>