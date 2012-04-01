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

require_once($CONFIG['cli']['location']."/core.cli.inc.php");

set_time_limit(60*120);	// Выполнять не более 120 минут
$start_time=microtime(TRUE);

if(!$CONFIG['price']['dir'])
{
	echo"Директория с прайсами не определена, завершаем работу...\n";
	exit(0);
}
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

$file=file("http://export.rbc.ru/free/cb.0/free.fcgi?period=DAILY&lastdays=0&separator=%2C&data_format=BROWSER");
if(!$file)
{
	$mail_text.="Не удалось получить курсы валют!\n";
	echo "Не удалось получить курсы валют!\n";
}
else foreach($file as $fl)
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

function forked_match_process($nproc, $limit, $res)
{
	global $a_start_time, $CONFIG;
	$old_p=$i=0;
	while($nxt=mysql_fetch_row($res))
	{
		$i++;
		//$speed=(microtime(TRUE)-$a_start_time)/$i;
		//echo"Proc: $nproc, Step $i, pos $nxt[3], $speed sec / pos...\n";
		
		if(!$nproc)
		{
			$p=floor($i/$limit*100);
			if($old_p!=$p)
			{
				$old_p=$p;
				//$p/=10;
				SetStatus("Analyze: $p pp");
			}
		}
		$res1=mysql_query("SELECT `id`, `search_str`, `replace_str` FROM `prices_replaces`");
		if(mysql_errno())		throw new Exception("Не удалось выбрать замены: ".mysql_error());
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

		$str_array=preg_split("/( OR | AND )/",$nxt[1],-1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		$sql_add='';
		$conn='';
		$c=1;
		foreach($str_array as $str_l)
		{
			if($c)	$sql_add.=" $conn (`price`.`name` LIKE '%$str_l%' OR `price`.`art` LIKE '%$str_l%')";
			else	$conn=$str_l;
			$c=1-$c;
		}

		$costar=array();
		$rs=mysql_query("SELECT `price`.`id`, `price`.`name`, `price`.`cost`, `price`.`firm`, `price`.`nal`, `firm_info`.`coeff`, `currency`.`coeff`, `price`.`art`, `price`.`currency`
		FROM `price`
		LEFT JOIN `firm_info` ON `firm_info`.`id`=`price`.`firm`
		LEFT JOIN `currency` ON `currency`.`id`=`price`.`currency`
		WHERE $sql_add");
		if(mysql_errno())		throw new Exception("Не удалось выбрать прайс-лист из базы данных: ".mysql_error());
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
				mysql_query("INSERT INTO `parsed_price_tmp` (`firm`, `pos`, `cost`, `nal`, `from`)
				VALUES ('$nx[3]', '$nxt[3]', '$cost', '$nx[4]', '$nx[0]' )");
				if(mysql_errno())	throw new Exception("Не удалось сохранить строку совпадения: ".mysql_error());
				
				if($CONFIG['price']['mark_matched'])
				{
					if($CONFIG['price']['mark_doubles'])
						mysql_query("INSERT INTO `price_seeked` VALUES ($nx[0], 1) ON DUPLICATE KEY UPDATE `seeked`=`seeked`+1");
					else	mysql_query("INSERT IGNORE INTO `price_seeked` VALUES ($nx[0], 1)");
					if(mysql_errno())	throw new Exception("Не удалось изменить счётчик совпадений: ".mysql_error());
	 			}
			}
		}
		if($i>$limit)	break;
	}
}


function mysql_reconnect()
{
	global $CONFIG;
	@mysql_close();
	if(!@mysql_connect($CONFIG['mysql']['host'],$CONFIG['mysql']['login'],$CONFIG['mysql']['pass']))
		throw new Exception("Нет связи с сервером баз данных: ".mysql_error());
	if(!@mysql_select_db($CONFIG['mysql']['db']))
		throw new Exception("Невозможно активизировать базу данных: ".mysql_error());
	mysql_query("SET CHARACTER SET UTF8");
	mysql_query("SET character_set_client = UTF8");
	mysql_query("SET character_set_results = UTF8");
	mysql_query("SET character_set_connection = UTF8");
}

function parallel_match()
{
	global $a_start_time, $CONFIG;
	$res=mysql_query("SELECT `doc_base`.`name`, `seekdata`.`sql`, `seekdata`.`regex`, `seekdata`.`id`, `doc_group`.`name`, `seekdata`.`regex_neg`
	FROM `seekdata` 
	LEFT JOIN `doc_group` ON `doc_group`.`id`=`seekdata`.`group`
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`seekdata`.`id`");
	if(mysql_errno())		throw new Exception("Не удалось выбрать наименования: ".mysql_error());
	$row=mysql_num_rows($res);
	SetStatus("Analyze: 0 pp");
	$a_start_time=microtime(TRUE);
	mysql_close();
	
	// Подготовка к распараллеливанию
	$numproc=$CONFIG['price']['numproc'];		// Включая родительский
	if($numproc<1)		$numproc=1;
	if($numproc>128)	$numproc=128;
	$pids_array=array();
	$limit_per_child=floor($row/$numproc);
	
	for($i=0;$i<($numproc-1);$i++)
	{
		$pid = pcntl_fork();
		if ($pid == -1)		throw new Exception("Параллельная обработка невозможна");
		$pids_array[]=$pid;
		if(!$pid)
		{
			mysql_data_seek($res , $limit_per_child*$i );
			if(mysql_errno())		throw new Exception("Не удалось сместить указатель $pid: ".mysql_error());
			mysql_reconnect();
			forked_match_process($i+1, $limit_per_child, $res);
			echo"Proc N$i end...\n";
			exit(0);
		}
	}
	mysql_data_seek($res , $limit_per_child*$i );
	if(mysql_errno())		throw new Exception("Не удалось сместить указатель $pid: ".mysql_error());
	mysql_reconnect();
	forked_match_process(0, $row-$limit_per_child*$i, $res);
	
	foreach($pids_array as $pid)	pcntl_waitpid($pid, $status);
	
	echo"Параллельная обработка завершена!";
}


try
{
	if(!file_exists($CONFIG['price']['dir']))	throw new Exception("Каталог с прайсами ({$CONFIG['price']['dir']}) не существует");
	if(!is_dir($CONFIG['price']['dir']))		throw new Exception("Каталог с прайсами ({$CONFIG['price']['dir']}) не является каталогом");
	$dh  = opendir($CONFIG['price']['dir']);
	if(!$dh)					throw new Exception("Не удалось открыть каталог с прайсами ({$CONFIG['price']['dir']})");
	SetStatus('Loading prices');
	require_once($CONFIG['location']."/common/priceloader.xls.php");
	require_once($CONFIG['location']."/common/priceloader.ods.php");
	
	while (false !== ($filename = readdir($dh)))
	{
		$path_info = pathinfo($filename);
		$ext=strtolower($path_info['extension']);
		if($ext=='xls')		$loader=new XLSPriceLoader($CONFIG['price']['dir'].'/'.$filename);
		else if($ext=='ods')	$loader=new ODSPriceLoader($CONFIG['price']['dir'].'/'.$filename);
		else continue;
		$f=0;
		$firm_array=$loader->detectSomeFirm();
		$loader->setInsertToDatabase();
		$msg="File: $filename\n";
		foreach($firm_array as $firm)
		{
			echo "{$msg}Firm_id: {$firm['firm_id']} ({$firm['firm_name']}), ";
			$loader->useFirmAndCurency($firm['firm_id'],$firm['curency_id']);
			$count=$loader->Run();
			echo "Parsed ($count items)!\n";
			$f=1;
		}
		if($f==0)
		{
			$msg.="соответствий не найдено. Прайс не обработан.";
 			$mail_text.="Анализ прайсов: $msg\n";
		}
		
		
// 		if($firm=$loader->detectFirm())
// 		{
// 			$loader->setInsertToDatabase();
// 			$msg.="Firm_id: $firm, ";
// 			$count=$loader->Run();
// 			$msg.="Parsed ($count items)!";	
// 			unlink($CONFIG['price']['dir']	.'/'.$filename);
// 		}
// 		else
// 		{
// 			$msg.="соответствий не найдено. Прайс не обработан.";
// 			$mail_text.="Анализ прайсов: $msg\n";
// 		}
		log_write($CONFIG['price']['dir'], $msg);
	}

	// Выборка
	echo "Начинаем анализ...\n";
	mysql_query("UPDATE `price` SET `seeked`='0'");
	mysql_query("CREATE TABLE IF NOT EXISTS `parsed_price_tmp` (
	`id` int(11) NOT NULL auto_increment,
	`firm` int(11) NOT NULL,
	`pos` int(11) NOT NULL,
	`cost` decimal(10,2) NOT NULL,
	`nal` varchar(10) NOT NULL,
	`from` int(11) NOT NULL,
	`selected` TINYINT(4) NOT NULL ,
	UNIQUE KEY `id` (`id`)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
	if(mysql_errno())		throw new Exception("Не удалось создать временную таблицу совпадений: ".mysql_error());
	
	if($CONFIG['price']['mark_matched'])
	{
		mysql_query("DROP TABLE `price_seeked`");
		mysql_query("CREATE TABLE IF NOT EXISTS `price_seeked` (
		`id` int(11) NOT NULL,
		`seeked` int(11) NOT NULL,
		UNIQUE KEY `id` (`id`)
		) ENGINE=Memory");
		if(mysql_errno())		throw new Exception("Не удалось создать временную таблицу отметок: ".mysql_error());
	}
	
	parallel_match();
	
	if($CONFIG['price']['mark_matched'])
	{
		mysql_unbuffered_query("UPDATE `price`,`price_seeked` SET `price`.`seeked`=`price_seeked`.`seeked`  WHERE `price`.`id`=`price_seeked`.`id`");
		if(mysql_errno())		throw new Exception("Не удалось записать отметки в основную таблицу: ".mysql_error());
	}
	
	mysql_query("ALTER TABLE`parsed_price_tmp`
	ADD INDEX ( `firm` ),
	ADD INDEX ( `pos` ),
	ADD INDEX ( `cost` ),
	ADD INDEX ( `nal` ),
	ADD INDEX ( `from` )");
	if(mysql_errno())	if(mysql_errno())	throw new Exception("Ошибка создания индексов в таблице с соответствиями: ".mysql_error());

	mysql_query("DROP TABLE `parsed_price`");
	if(mysql_errno())	if(mysql_errno())	throw new Exception("Ошибка удаления старой таблицы с соответствиями: ".mysql_error());
		
	mysql_query("RENAME TABLE `parsed_price_tmp` TO `parsed_price` ;");
	if(mysql_errno())				throw new Exception("Ошибка переименования таблицы с соответствиями: ".mysql_error());


	echo	"Анализ прайсов завершен успешно!";
	// ====================== ОБНОВЛЕНИЕ ЦЕН =============================================================
	$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`cost`, `doc_base`.`name`, (
	SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `allcnt`, `doc_base`.`group`
	FROM `doc_base`");
	if(mysql_errno())				throw new Exception("Ошибка выборки при обновлении цен: ".mysql_error());
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
		$ok_line=0;
		$rs=mysql_query("SELECT `parsed_price`.`cost`,`firm_info`.`type`, `firm_info_group`.`id`, `parsed_price`.`id`
		FROM  `parsed_price` 
		LEFT JOIN `firm_info` ON `firm_info`.`id`=`parsed_price`.`firm` 
		LEFT JOIN `firm_info_group` ON `firm_info_group`.`firm_id`=`parsed_price`.`firm` AND `firm_info_group`.`group_id`='$nxt[4]'
		WHERE `parsed_price`.`pos`='$nxt[0]' AND `parsed_price`.`cost`>'0' AND `parsed_price`.`nal`!='' AND `parsed_price`.`nal`!='-' AND `parsed_price`.`nal`!='call' AND `parsed_price`.`nal`!='0'");
		if(mysql_errno())	throw new Exception(mysql_error());
		while($nx=mysql_fetch_row($rs))
		{
			if(($nx[1]==1 || ($nx[1]==2 &&  $nx[2]!='')) && $mincost>$nx[0])
			{
				$mincost=$nx[0];
				$ok_line=$nx[3];
			}
		}
		
		if($ok_line==0)	$mincost=0;
		
		if( $nxt[3]==0 )
		{
			mysql_query("UPDATE `parsed_price` SET `selected`='1' WHERE `id`='$ok_line'");
			if(mysql_errno())	throw new Exception(mysql_error());
			if($nxt[1]!=$mincost)
			{
				$txt="У наименования ID:$nxt[0] изменена цена с $nxt[1] на $mincost. Наименование: $nxt[2]\n";
				mysql_query("UPDATE `doc_base` SET `cost`='$mincost', `cost_date`=NOW() WHERE `id`='$nxt[0]'");
				if(mysql_errno())	throw new Exception(mysql_error());
				echo $txt;
			}
		}
	}
	mysql_query("ALTER TABLE `parsed_price`
	ADD INDEX ( `selected` )");
	if(mysql_errno())	if(mysql_errno())	throw new Exception("Ошибка создания индексов (2) в таблице с соответствиями: ".mysql_error());

}
catch(Exception $e)
{
	$txt="Ошибка: ".$e->getMessage()."\n";
	echo $txt;
	$mail_text.=$txt;
}

$work_time=microtime(TRUE)-$start_time;;

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

$text_time='Скрипт выполнен за ';
if($h)	$text_time.="$h часов ";
if($m)	$text_time.="$m минут ";
if($s)	$text_time.="$s секунд ";
$text_time.=" (всего $work_time секунд)\n";

echo $text_time;

// ===================== ОТПРАВКА ПОЧТЫ =============================================================== 
if($mail_text)
{
	try
	{	
		$mail_text="При анализе прайс-листов произошло следующее:\n****\n\n".$mail_text."\n\n****\nНайденные ошибки желательно исправить в кратчайший срок!!\n\n$text_time";
		mailto($CONFIG['site']['admin_email'], "Price analyzer errors", $mail_text);
		mailto($CONFIG['site']['doc_adm_email'], "Price analyzer errors", $mail_text);
		echo "Почта отправлена!";
	}
	catch(Exception $e) 
	{
		echo"Ошибка отправки почты!".$e->getMessage();
	}
	
	
}
else echo"Ошибок не найдено, не о чем оповещать!\n";

mysql_query("DELETE FROM `sys_cli_status` WHERE `id`='$status_id'");

?>