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


// если не настроена синхронизация
if(!$CONFIG['bank']['mountpoint'])	exit(0);

`umount {$CONFIG['bank']['mountpoint']}`;
`mount -t smbfs -o username={$CONFIG['bank']['login']},password={$CONFIG['bank']['pass']},iocharset=utf8 {$CONFIG['bank']['remotepoint']} {$CONFIG['bank']['mountpoint']}`;


$file=file($CONFIG['bank']['mountpoint'].'/export/STATES.TXT');
if($file)
{
$params=array();
$parsing=0;
foreach($file as $line)
{
	$line=iconv( 'windows-1251','UTF-8', $line);	
	//echo $line.'<br>';
	$line=trim($line);
	$pl=split("=",$line,2);
	switch($pl[0])
	{
		case 'СекцияДокумент':
			if($pl[1]=="Платёжное поручение")
			{
				$parsing=1;
				echo"Новый $pl[1]\n";
			}
			else echo"Неопознанный документ: $pl[1]\n";
		break;
		case 'КонецДокумента':
			if($parsing)
			{
				doc_process($params);
			}
			$parsing=0;
			$params=array();
		break;
		case 'Номер':
			if($parsing)
				$params['docnum']=$pl[1];
		break;
		case 'УникальныйНомерДокумента':
			if($parsing)
				$params['unique']=$pl[1];
		break;
		case 'ДатаПроведения':
			if($parsing)
				$params['date']=$pl[1];
		break;	
		case 'БИК':
			if($parsing)
				$params['bik']=$pl[1];
		break;	
		case 'Счет':
			if($parsing)
				$params['schet']=$pl[1];
		break;
		case 'КорреспондентБИК':
			if($parsing)
				$params['kbik']=$pl[1];
		break;
		case 'КорреспондентСчет':
			if($parsing)
				$params['kschet']=$pl[1];
		break;	
		case 'ДебетСумма':
			if($parsing)
				$params['debet']=$pl[1];
		break;
		case 'КредитСумма':
			if($parsing)
				$params['kredit']=$pl[1];
		break;
		case 'НазначениеПлатежа':
			if($parsing)
				$params['desc']=$pl[1];
		break;
	}
}
}

global $dv;

$res=mysql_query("SELECT * FROM `doc_vars` WHERE `id`='$firm_id'");
$dv=mysql_fetch_assoc($res);
$innkpp=split('/',$dv['firm_inn']);


$i=0;
$dt=date("d.m.Y");
$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`sum`, `doc_list`.`comment`,
`doc_agent`.`fullname`, `doc_agent`.`inn`, `doc_agent`.`rs`, `doc_agent`.`bik`, `doc_agent`.`ks`, `doc_agent`.`bank`
FROM `doc_list`
LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
WHERE `doc_list`.`type`='5' AND `doc_list`.`ok`>'0' AND `doc_list`.`nds`>'0'");
echo mysql_error();
while($nxt=mysql_fetch_row($res))
{
	if(!$i)
	{
		$i=1;
		$fd=fopen("{$CONFIG['bank']['mountpoint']}/import/810pay.txt","a+");
		fseek($fd,0,SEEK_END);
		if(!ftell($fd))
		{
			$header="1CClientBankExchange\r\nВерсияФормата=1\r\nКодировка=Windows\r\n";
			fwrite($fd, to_win($header));
		}
	}
	$f_innkpp=split('/',$nxt[5]);
	$str="СекцияДокумент=Платежное поручение\r\nНомер=$nxt[1]\r\nДата=$dt\r\nСумма=$nxt[2]\r\n". "ВидПлатежа=Электронный\r\nВидОплаты=01\r\nОчередность=6\r\nПлательщикИНН=$innkpp[0]\r\nПлательщикКПП=$innkpp[1]\r\n". "Плательщик=".$dv['firm_name']."\r\nПлательщикСчет=".$dv['firm_schet']."\r\nПлательщикБИК=0".$dv['firm_bik']."\r\n". "ПлательщикКорсчет=".$dv['firm_bank_kor_s']."\r\nПлательщикБанк1=".$dv['firm_bank']."\r\nПолучательИНН=$f_innkpp[0]\r\n". "ПолучательКПП=$f_innkpp[1]\r\nПолучатель=$nxt[4]\r\nПолучательСчет=$nxt[6]\r\nПолучательБИК=$nxt[7]\r\n". "ПолучательКорсчет=$nxt[8]\r\nПолучательБанк1=$nxt[9]\r\nНазначениеПлатежа=$nxt[3]\r\nКонецДокумента\r\n\r\n";
	fwrite($fd, to_win($str));
	echo "$nxt[0] ($nxt[4])\n";
	mysql_query("UPDATE `doc_list` SET `nds`='0' WHERE `id`='$nxt[0]'");
}
if($fd) fclose($fd);

function to_win($str)
{
	return iconv( 'UTF-8', 'windows-1251', $str);	
}


function doc_process($params)
{
	echo"result: doc: ".$params['docnum'];
	//var_dump($params);
	//echo"\n";
	if($params['debet']>0)
	{
		$type=5;
		$sum=$params['debet'];
	}
	else
	{
		$type=4;
		$sum=$params['kredit'];
	}
	echo", SUM: $sum\n";
	$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`sum`, `doc_dopdata`.`value`
	FROM `doc_list` 
	LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='unique'
	WHERE `doc_list`.`altnum`='".$params['docnum']."' AND `doc_list`.`type`='$type'");
	$i=0;
	while($nxt=mysql_fetch_row($res))
	{
		echo"id: $nxt[0], altnum: $nxt[1], subtype: $nxt[2], sum: $nxt[3]\n";
		if($nxt[4]==$params['unique']) $i++;
	}
	echo "FIND: $i\n";
	if(!$params['unique'])	echo"UINQUE IS NULL!\n";
	if(($i==0) && ($type==4))
	{
			$tm=time();
			$res=mysql_query("SELECT `id`, `agent` FROM `doc_list` WHERE `type`='3' AND `sum`='$sum' ORDER BY `id` DESC");
			@$p_doc=mysql_result($res,0,0);
			@$agent=mysql_result($res,0,1);
			if(!$agent) $agent=1;
			$desc=mysql_escape_string($params['desc']);
 			mysql_query("INSERT INTO `doc_list` ( `type`, `agent`, `comment`, `date`, `altnum`, `subtype`, `sum`, `p_doc`, `sklad`, `bank`)
 			VALUES ('4', '$agent', '$desc', '$tm', '".$params['docnum']."', 'auto', '$sum', '$p_doc' , '1', '1')");
 			$new_id=mysql_insert_id();
 			echo "insert_id: $new_id\n".mysql_error();
 			mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
						VALUES ('$new_id','unique','".$params['unique']."')");
 			
	}
	
	echo"\n";
}



?>