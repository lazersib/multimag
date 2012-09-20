#!/usr/bin/php
<?php

// Ежедневный запуск в 0:01 
$c=explode('/',__FILE__);$base_path='';
for($i=0;$i<(count($c)-2);$i++)	$base_path.=$c[$i].'/';
include_once("$base_path/config_cli.php");


// Очистка от неподтверждённых пользователей
if($CONFIG['auto']['user_del_days']>0)
{
	$tim=time();
	$dtim=time()-60*60*24*$CONFIG['auto']['user_del_days'];
	$dtim=date('Y-m-d H:i:s',$dtim);
	mysql_query("DELETE FROM `users` WHERE `date_reg`<'$dtim' AND `confirm`!='0' AND `confirm`!=''");
}

// Перемещение непроведённых реализаций на начало текущего дня
if($CONFIG['auto']['move_nr_to_end']==true)
{
	$end_day=strtotime(date("Y-m-d 00:00:01"));
	mysql_query("UPDATE `doc_list` SET `date`='$end_day' WHERE `type`='2' AND `ok`='0'");
}

// Перемещение непроведённых заявок на начало текущего дня
if($CONFIG['auto']['move_no_to_end']==true)
{
	$end_day=strtotime(date("Y-m-d 00:00:01"));
	mysql_query("UPDATE `doc_list` SET `date`='$end_day' WHERE `type`='3' AND `ok`='0'");
}

// Очистка счётчика посещений от старых данных
$tt=time()-60*60*24*10;
mysql_query("DELETE FROM `counter` WHERE `date` < '$tt'");

// Загрузка курсов валют
$data=file_get_contents("http://www.cbr.ru/scripts/XML_daily.asp");
$doc = new DOMDocument('1.0');
$doc->loadXML($data);
$doc->normalizeDocument ();
$valutes=$doc->getElementsByTagName('Valute');
foreach($valutes as $valute)
{
	$name=$value=0;
	foreach($valute->childNodes as $val)
	{
		switch($val->nodeName)
		{
			case 'CharCode':
				$name=$val->nodeValue;
				break;
			case 'Value':
				$value=$val->nodeValue;
				break;
		}
	}
	$value=round(str_replace(',','.',$value),4);
	mysql_query("UPDATE `currency` SET `coeff`='$value' WHERE `name`='$name'");
}

?>
