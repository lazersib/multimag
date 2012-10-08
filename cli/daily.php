#!/usr/bin/php
<?php

// Ежедневный запуск в 0:01 
$c=explode('/',__FILE__);$base_path='';
for($i=0;$i<(count($c)-2);$i++)	$base_path.=$c[$i].'/';
include_once("$base_path/config_cli.php");
require_once($CONFIG['cli']['location']."/core.cli.inc.php");

try
{

// Очистка от неподтверждённых пользователей
if($CONFIG['auto']['user_del_days']>0)
{
	$tim=time();
	$dtim=time()-60*60*24*$CONFIG['auto']['user_del_days'];
	$dtim=date('Y-m-d H:i:s',$dtim);
	$res=mysql_query("SELECT `id` FROM `users`
	LEFT JOIN `users_openid` ON `users_openid`.`user_id`=`users`.`id`
	WHERE `users_openid`.`user_id` IS NULL AND `users`.`reg_date`<'$dtim' AND `users`.`reg_email_confirm`!='1' AND `reg_phone_confirm`!='1'");
	if(mysql_errno())	throw new MysqlException("Не удалось получить пользователей для удаления");
	while($nxt=mysql_fetch_row($res))
	{
		mysql_query("DELETE FROM `users` WHERE `id`='$nxt[0]'");
		// Обработка ошибок не требуется, т.к. пользователи, по которым есть информация, не могут быть удалены.
	}
}

// Перемещение непроведённых реализаций на начало текущего дня
if($CONFIG['auto']['move_nr_to_end']==true)
{
	$end_day=strtotime(date("Y-m-d 00:00:01"));
	mysql_query("UPDATE `doc_list` SET `date`='$end_day' WHERE `type`='2' AND `ok`='0'");
	if(mysql_errno())	throw new MysqlException("Не удалось переместить реализации");
}

// Перемещение непроведённых заявок на начало текущего дня
if($CONFIG['auto']['move_no_to_end']==true)
{
	$end_day=strtotime(date("Y-m-d 00:00:01"));
	mysql_query("UPDATE `doc_list` SET `date`='$end_day' WHERE `type`='3' AND `ok`='0'");
	if(mysql_errno())	throw new MysqlException("Не удалось переместить заявки");
}

// Очистка счётчика посещений от старых данных
$tt=time()-60*60*24*10;
mysql_query("DELETE FROM `counter` WHERE `date` < '$tt'");
if(mysql_errno())	throw new MysqlException("Не удалось очистить счётчик");

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
	if(mysql_errno())	throw new MysqlException("Не удалось обновить курсы валют");
}

}
catch(Exception $e)
{
	mailto($CONFIG['site']['doc_adm_email'], "Error in daily.php", $e->getMessage());
	echo $e->getMessage()."\n";
}


?>
