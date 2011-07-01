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

set_time_limit(60*120);	// Выполнять не более 15 минут
$start_time=microtime(TRUE);

if(!$CONFIG['price']['dir'])	exit(0);

$mail_text='';

$c=explode('/',__FILE__);
mysql_query("INSERT INTO `sys_cli_status` (`script`, `status`) VALUES ('".$c[count($c)-1]."', 'Start')");
$status_id=mysql_insert_id();
 
function SetStatus($status)
{
	global $status_id;
	mysql_query("UPDATE `sys_cli_status` SET `status`='$status' WHERE `id`='$status_id'");
}

class Foo
{
    function AddText($t) {return;}
    function msg($t) {return;}
};

$tmpl=new Foo();


include_once($CONFIG['site']['location'].'/include/price_analyze.inc.php');

$file=file("http://export.rbc.ru/free/cb.0/free.fcgi?period=DAILY&lastdays=0&separator=%2C&data_format=BROWSER");
foreach($file as $fl)
{
	$fl=trim($fl);
	$fa=explode(',',$fl);
	mysql_query("UPDATE `currency` SET `coeff`='$fa[5]' WHERE `name`='$fa[0]'");
}



function log_write($dir, $msg)
{
	$f_log=fopen($dir.'/load.log','a');
	fprintf($f_log, date("Y.m.d H:i:s ").$msg."\n");
	fclose($f_log);
	echo $msg."\n";
}


try
{
	if(!file_exists($CONFIG['price']['dir']))	throw new Exception("Каталог с прайсами ({$CONFIG['price']['dir']}) не существует");
	if(!is_dir($CONFIG['price']['dir']))		throw new Exception("Каталог с прайсами ({$CONFIG['price']['dir']}) не является каталогом");
	$dh  = opendir($CONFIG['price']['dir']);
	if(!$dh)					throw new Exception("Не удалось открыть каталог с прайсами ({$CONFIG['price']['dir']})");
	while (false !== ($filename = readdir($dh)))
	{
		$msg='';
		if(!strpos($filename,'.ods'))
			continue;
		$msg.="$filename - ";
	
		$zip = new ZipArchive;
		$zip->open($CONFIG['price']['dir'].'/'.$filename,ZIPARCHIVE::CREATE);
		$xml = $zip->getFromName("content.xml");
		$zip->close();
		
		SetStatus('Loading prices');
		
		if(detect_firm($xml,1))
		{
			$p=parse($xml);
			if($p)
			{	
				$msg.="Parsed!";	
				unlink($CONFIG['price']['dir']	.'/'.$filename);
			}
			else $msg.="PARSE ERROR!";
		}
		else $msg.="NOT DETECTED!";
		
		if($msg)
		{
			log_write($CONFIG['price']['dir'], $msg);
			$mail_text.="Анализ прайсов: $msg\n";
		}
	}

}
catch(Exception $e)
{
	$txt="Ошибка: ".$e->getMessage()."\n";
	echo $txt;
	$mail_text.=$txt;
}

// Выборка
$mail_text.="Начинаем анализ...\n";
echo "Начинаем анализ...\n";
mysql_query("UPDATE `price` SET `seeked`='0'");
mysql_query("CREATE TABLE IF NOT EXISTS `parsed_price_tmp` (
  `id` int(11) NOT NULL auto_increment,
  `firm` int(11) NOT NULL,
  `pos` int(11) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `nal` varchar(10) NOT NULL,
  `from` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
echo mysql_error();

$res=mysql_query("SELECT `doc_base`.`name`, `seekdata`.`sql`, `seekdata`.`regex`, `seekdata`.`id`, `doc_group`.`name`, `seekdata`.`regex_neg`
FROM `seekdata` 
LEFT JOIN `doc_group` ON `doc_group`.`id`=`seekdata`.`group`
LEFT JOIN `doc_base` ON `doc_base`.`id`=`seekdata`.`id`");
$row=mysql_num_rows($res);
$old_p=$i=0;
while($nxt=mysql_fetch_row($res))
{
	$i++;
	$p=floor($i/$row*100);
	if($old_p!=$p)
	{
		$old_p=$p;
		SetStatus("Analyze: $p pp");
	}

	$res1=mysql_query("SELECT `id`, `search_str`, `replace_str` FROM `prices_replaces`");
	echo mysql_error();
	while($nxt1=mysql_fetch_row($res1))
	{
		$nxt[2]=str_replace("{{{$nxt1[1]}}}", $nxt1[2], $nxt[2]);
		$nxt[5]=str_replace("{{{$nxt1[1]}}}", $nxt1[2], $nxt[5]);
	}

	$a=preg_match("/$nxt[2]/", ' ');
	$b=preg_match("/$nxt[5]/", ' ');
	if($a===FALSE || $b===FALSE)
	{
		$mail_text.="Анализ прайсов: регулярное выражение позиции id:$nxt[3] (для $nxt[0]) составлено с ошибкой! Это значительно снижает быстродействие, и может вызвать сбой!\n";
		continue;
	}

	$costar=array();
	$rs=mysql_query("SELECT `price`.`id`, `price`.`name`, `price`.`cost`, `price`.`firm`, `price`.`nal`, `firm_info`.`coeff`, `currency`.`coeff`, `price`.`art` FROM `price`
	LEFT JOIN `firm_info` ON `firm_info`.`id`=`price`.`firm`
	LEFT JOIN `currency` ON `currency`.`id`=`firm_info`.`currency`
	WHERE `price`.`name` LIKE '%$nxt[1]%' OR `price`.`art` LIKE '%$nxt[1]%'");
	echo mysql_error();
	while($nx=mysql_fetch_row($rs))
	{
		$a=preg_match("/$nxt[2]/",$nx[1]);
		$b=preg_match("/$nxt[2]/",$nx[7]);
		
		if( $a || $b )
		{
			if($nxt[5])
			{
				$a=preg_match("/$nxt[5]/",$nx[1]);
				$b=preg_match("/$nxt[5]/",$nx[7]);
				if( $a || $b )	continue;
			}
			
			if($nx[5]==0) $nx[5]=1;
			if($nx[6]==0) $nx[6]=1;
			$cost=$nx[2]*$nx[5]*$nx[6];
			//if($nxt[3]==215)
			//echo"$nxt[0] - $nx[1] - $nx[5] - $nx[6]\n";
			mysql_query("INSERT INTO `parsed_price_tmp` (`firm`, `pos`, `cost`, `nal`, `from`)
			VALUES ('$nx[3]', '$nxt[3]', '$cost', '$nx[4]', '$nx[0]' )");
			if(mysql_errno())	echo mysql_error();
			mysql_query("UPDATE `price` SET `seeked`=`seeked`+'1' WHERE `id`='$nx[0]'");
			if(mysql_errno())	echo mysql_error();
		}
	}
	
}

mysql_query("DROP TABLE `parsed_price`");
if(mysql_errno())	echo mysql_error();
mysql_query("RENAME TABLE `parsed_price_tmp` TO `parsed_price` ;");
if(mysql_errno())	echo mysql_error();

$mail_text.="Анализ прайсов завершен успешно!";
echo	"Анализ прайсов завершен успешно!";
// ====================== ОБНОВЛЕНИЕ ЦЕН =============================================================
$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`cost`, `doc_base`.`name`, (
SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `allcnt`, `doc_base`.`group`

FROM `doc_base`");
echo mysql_error();
$row=mysql_num_rows($res);
$old_p=$i=0;
while($nxt=mysql_fetch_row($res))
{
	$i++;
	$p=floor($i/$row*100);
	if($old_p!=$p)
	{
		$old_p=$p;
		SetStatus("Cost change: $p pp");
	}
	settype($nxt[3],'int');
	
	$mincost=99999999;
	$rs=mysql_query("SELECT `parsed_price`.`cost`,`firm_info`.`type`, `firm_info_group`.`id`
	FROM  `parsed_price` 
	LEFT JOIN `firm_info` ON `firm_info`.`id`=`parsed_price`.`firm` 
	LEFT JOIN `firm_info_group` ON `firm_info_group`.`firm_id`=`parsed_price`.`firm` AND `firm_info_group`.`group_id`='$nxt[4]'
	WHERE `parsed_price`.`pos`='$nxt[0]' AND `parsed_price`.`cost`>'0'");
	echo mysql_error();
	while($nx=mysql_fetch_row($rs))
	{
		if(($nx[1]==1 || ($nx[1]==2 &&  $nx[2]!='')) && $mincost>$nx[0])	$mincost=$nx[0];
	
	}
	
	
	if( ($nxt[3]==0) && ($nxt[1]!=$mincost) && ($mincost>0) )
	{
		if($mincost==99999999)	$mincost=0;
		$txt="У наименования ID:$nxt[0] изменена цена с $nxt[1] на $mincost. Наименование: $nxt[2]\n";
		mysql_query("UPDATE `doc_base` SET `cost`='$mincost', `cost_date`=NOW() WHERE `id`='$nxt[0]'");
		echo $txt;
		$mail_text.=$txt;	
	}
}


// ===================== ОТПРАВКА ПОЧТЫ =============================================================== 
if($mail_text)
{
	$work_time=microtime(TRUE)-$start_time;;
	$send=1;
	
	$h=$m=0;
	$s=round($work_time*100)/100;
	if($s>60)
	{
		$m=floor($s/60);
		$s-=$m*60;
	}
	
	if($m>60)
	{
		$h=floor($m/60);
		$m-=$h*60;
	}
	
	$text_time='';
	if($h)	$text_time.="$h часов ";
	if($m)	$text_time.="$m минут ";
	if($s)	$text_time.="$s секунд ";
	$text_time.=" (всего $work_time секунд)";
	
	if($CONFIG['site']['doc_adm_email'])
		$mail->AddAddress($CONFIG['site']['doc_adm_email'], 'To document administrator' );
	else if($CONFIG['site']['admin_email'])
		$mail->AddAddress($CONFIG['site']['admin_email'], 'To site administrator' );
	else $send=0;
	if($send)
	{
		$mail->Subject="Price analyzer";
		$mail_text="При анализе прайс-листов произошло следующее:\n****\n\n".$mail_text."\n\n****\nЕсли произошла ошибка, её необходимо срочно исправить!\n\nСкрипт выполнен за $text_time";
		echo $mail_text;
		$mail->Body=$mail_text;
		if($mail->Send())
			echo "\n\nПочта отправлена!\n";
		else echo"\n\nошибка почты!".$mail->ErrorInfo."\n";
	}
}

mysql_query("DELETE FROM `sys_cli_status` WHERE `id`='$status_id'");

?>