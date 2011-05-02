#!/usr/bin/php
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

$c=explode('/',__FILE__);$base_path='';
for($i=0;$i<(count($c)-2);$i++)	$base_path.=$c[$i].'/';
include_once("$base_path/config_cli.php");

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

  
$mail->FromName = $CONFIG['site']['name'].' - Site Service System';  
$mail->CharSet  = "UTF-8";
$mail->AddAddress($CONFIG['site']['doc_adm_email'], $CONFIG['site']['doc_adm_email'] );  
$mail->Subject="DB Check report";

$mail_text='';


$tim=time();
$dtim=time()-60*60*24*365;

mysql_query("REPAIR TABLE `doc_agent`");
mysql_query("REPAIR TABLE `doc_base`");
mysql_query("REPAIR TABLE `doc_list`");
mysql_query("REPAIR TABLE `doc_list_pos`");

echo"Сброс остатков\n";
$res=mysql_query("SELECT `doc_base`.`id`, (SELECT SUM(`doc_list_pos`.`cnt`) FROM `doc_list_pos`
INNER JOIN `doc_list` ON `doc_list`.`type`='12' AND `doc_list`.`ok`>'0' AND `doc_list`.`id`!='$doc'
AND `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`id` NOT IN (SELECT DISTINCT `p_doc` FROM `doc_list` WHERE `ok` != '0' AND `type`='1' )
WHERE `doc_list_pos`.`tovar`=`doc_base`.`id` GROUP BY `doc_list_pos`.`tovar`) FROM `doc_base`");
while($nxt=mysql_fetch_row($res))
{
    mysql_query("UPDATE `doc_list_pos` SET `tranzit`='$nxt[1]' WHERE `id`='$nxt[0]'");
}





// ============== Расчет ликвидности ===================================================
echo"Расчет ликвидности...";
$res=mysql_query("SELECT `doc_list_pos`.`tovar`,(SUM(`doc_list_pos`.`tovar`)/`doc_list_pos`.`tovar`) AS `aa`
FROM `doc_list_pos`, `doc_list`
WHERE `doc_list_pos`.`doc`= `doc_list`.`id` AND `doc_list`.`type`>'1' AND `doc_list`.`date`>'$dtim'
GROUP BY `doc_list_pos`.`tovar`
 ORDER BY `aa` DESC");
$nxt=mysql_fetch_row($res);
$max=$nxt[1]/100;
//echo"$max\n";

$rs=mysql_query("UPDATE `doc_base` SET `likvid`='0'");
$res=mysql_query("SELECT `doc_list_pos`.`tovar`,(SUM(`doc_list_pos`.`tovar`)/`doc_list_pos`.`tovar`)
FROM `doc_list_pos`, `doc_list`
WHERE `doc_list_pos`.`doc`= `doc_list`.`id` AND `doc_list`.`type`>'1' AND `doc_list`.`date`>'$dtim'
GROUP BY `doc_list_pos`.`tovar`");
while($nxt=mysql_fetch_row($res))
{
 	$l=$nxt[1]/$max;
 	//if($l>100) $l=100;
 	$rs=mysql_query("UPDATE `doc_base` SET `likvid`='$l' WHERE `id`='$nxt[0]'");
 	$ar=mysql_affected_rows();
 	//echo"$nxt[0] - $nxt[1] - $l - $ar\n";
}
echo" готово!\n";
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
echo"Перепроводка документов...";
$i=0;
$res=mysql_query("UPDATE `doc_kassa` SET `ballance`='0'");
$res=mysql_query("UPDATE `doc_base_cnt` SET `cnt`='0'");
$res=mysql_query("SELECT `id`, `type`, `altnum`, `date` FROM `doc_list` WHERE `ok`>'0' AND `type`!='3' AND `mark_del`='0' ORDER BY `date`");
while($nxt=mysql_fetch_row($res))
{
	//if( ($nxt[1]>2) && ($nxt[1]!=8) && ($nxt[1]!=4) && ($nxt[1]!=5)  ) continue;
	//DocSumUpdate($nxt[0]);
	$dt=date("d.m.Y H:i:s",$nxt[3]);
	$typename=$doc_types[$nxt[1]]."N $nxt[2] от $dt";;
	$document=AutoDocumentType($nxt[1],$nxt[0]);
	if($err=$document->Apply($nxt[0],1))
	{
		$text="$nxt[0]($typename): Списание в минус! ($err) ЭТО КРИТИЧЕСКАЯ ОШИБКА! ОСТАТКИ НА СКЛАДЕ НЕВЕРНЫ!\n";
		echo $text;
		$mail_text.=$text;
		//echo " ---------- ".seek_and_up($nxt[3],$badpos)."\n";
		
		$i++;
	}
	
	//else echo "$nxt[0]($typename): ok!\n";
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
$tim_minus=time()-60*60*24*7;
$res=mysql_query("SELECT `id`, `type` FROM `doc_list` WHERE `mark_del`<'$tim_minus' AND `mark_del`>'0'");
while($nxt=mysql_fetch_row($res))
{
	$document=AutoDocumentType($nxt[1], $nxt[0]);
	if($document->DelExec($nxt[0],1))
	{
		$text="$nxt[1] ID:$nxt[0], попытка удаления документа, имеющего не удалённые подчинённые документы!\n";
		echo $text;
		$mail_text.=$text;
	}
	else
	{
		$text="$nxt[1] ID:$nxt[0] удалён!\n";
		echo $text;
		$mail_text.=$text;
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






// if($mail->Send())

if($mail_text)
	{
	$mail_text="При автоматической проверке базы данных сайта найдены следующие проблемы:\n****\n\n".$mail_text."\n\n****
	Необходимо срочно исправить найденные ошибки!";
	
	$mail->Body=$mail_text;
	if($mail->Send())
		echo "Почта отправлена!";
	else echo"ошибка почты!".$mail->ErrorInfo;
	}


?>