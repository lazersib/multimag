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
	$res=mysql_query("DELETE FROM `users` WHERE `date_reg`<'$dtim' AND `confirm`!='0' AND `confirm`!=''");
}

// Перемещение непроведённых реализаций на конец дня
if($CONFIG['auto']['move_nr_to_end']==true)
{
	$end_day=strtotime(date("Y-m-d 00:00:01"));
	$res=mysql_query("UPDATE `doc_list` SET `date`='$end_day' WHERE `type`='2' AND `ok`='0'");
}

?>
