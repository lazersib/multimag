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

$tim=time();
$i_time=time()-60*60*24*$CONFIG['resp_clear']['info_time'];
$c_time=time()-60*60*24*$CONFIG['resp_clear']['clear_time'];

$info_mail='';

$mail->CharSet  = "UTF-8";
$mail->FromName = $CONFIG['site']['name'].' - Site Service System';  

if($CONFIG['resp_clear']['info_time'])
{
	$res=mysql_query("SELECT `doc_agent`.`id`, `doc_agent`.`name`, `doc_agent`.`responsible`, `users`.`name`, `users`.`email` FROM `doc_agent`
	LEFT JOIN `users` ON `users`.`id`=`doc_agent`.`responsible`
	WHERE `doc_agent`.`id` NOT IN (SELECT `agent` FROM `doc_list` WHERE `date`>='$i_time' ) AND `doc_agent`.`responsible`>'0'");
	
	$resp_info=array();
	$resp_mail=array();
	
	if(mysql_num_rows($res)>0)	$info_mail.="По следующим агентам, ассоциированным с ответственными, не было движения более {$CONFIG['resp_clear']['info_time']} дней:\n";
	
	while($nxt=mysql_fetch_row($res))
	{
		$info_mail.='id:'.str_pad($nxt[0], 6, ' ', STR_PAD_LEFT).' - '.$nxt[1]." (ответственный - $nxt[3] (id:$nxt[2])\n";
		$resp_info[$nxt[2]].='id:'.str_pad($nxt[0], 6, ' ', STR_PAD_LEFT).' - '.$nxt[1]."\n";
		$resp_mail[$nxt[2]]=$nxt[4];
	}
	
	foreach($resp_info as $id => $resp)
	{
		$mail->ClearAddress();
		$mail->AddAddress($resp_mail[$id], $resp_mail[$id]);  
		$mail->Subject=$CONFIG['site']['name']." - Info for responsible manager";
		
		$mail->Body="По следующим агентам, для которых Вы назначены ответственным менеджером, , не было движения более {$CONFIG['resp_clear']['info_time']} дней:\n\n".$resp."\nЕсли Вы не примите меры, то через некоторое время Вы перестанете быть ответственным менеджером этого агента!\n\nВы получили это письмо, так как являетесь ответственным менеджером.\nЭто письмо сгенерированно автоматически системой оповещения сайта {$CONFIG['site']['name']}.\nОтвечать на него не нужно."; 
		if($mail->Send())	echo "\nПочта отправлена на $resp_mail[$id]!";
		else 			echo"\nошибка почты на $resp_mail[$id]! ".$mail->ErrorInfo;
	}
}

if($CONFIG['resp_clear']['clear_time'])
{
	$res=mysql_query("SELECT `doc_agent`.`id`, `doc_agent`.`name`, `doc_agent`.`responsible`, `users`.`name`, `users`.`email` FROM `doc_agent`
	LEFT JOIN `users` ON `users`.`id`=`doc_agent`.`responsible`
	WHERE `doc_agent`.`id` NOT IN (SELECT `agent` FROM `doc_list` WHERE `date`>='$c_time' ) AND `doc_agent`.`responsible`>'0'");
	
	if(mysql_num_rows($res)>0)	$info_mail.="\n\nУ следующих агентов были сняты ассоциации с ответственным, т.к. не было движения более {$CONFIG['resp_clear']['clear_time']} дней:\n";
	
	while($nxt=mysql_fetch_row($res))
	{
		$info_mail.='id:'.str_pad($nxt[0], 6, ' ', STR_PAD_LEFT).' - '.$nxt[1]." (был ответственный - $nxt[3] (id:$nxt[2])\n";
		mysql_query("UPDATE `doc_agent` SET `responsible`='0' WHERE `id`='$nxt[0]'");
		if(mysql_error())	throw new Exception("Не удалось убрать ответственного менеджера!");
	}
}


if($info_mail)
{
	$mail->ClearAddress();
	$mail->AddAddress($CONFIG['resp_clear']['info_mail'], $CONFIG['resp_clear']['info_mail']);  
	$mail->Subject=$CONFIG['site']['name']." - Info about non-active client";
	
	$mail->Body=$info_mail."\n\nВы получили это письмо, так как ваш адрес указан в настройках сайта.\nЭто письмо сгенерированно автоматически системой оповещения сайта {$CONFIG['site']['name']}.\nОтвечать на него не нужно.";
	if($mail->Send())	echo "\nПочта отправлена на {$CONFIG['resp_clear']['info_mail']}!";
	else 			echo"\nошибка почты на {$CONFIG['resp_clear']['info_mail']}! ".$mail->ErrorInfo;
}

?>