#!/usr/bin/php
<?php
$c=explode('/',__FILE__);$base_path='';
for($i=0;$i<(count($c)-2);$i++)	$base_path.=$c[$i].'/';
include_once("$base_path/config_cli.php");
require_once($CONFIG['cli']['location']."/core.cli.inc.php");

if(!$CONFIG['route']['backup_ext_ip'] || !$CONFIG['route']['backup_ext_iface'])    exit(0);

$mail_text='';

function logmail($str)
{
	global $mail_text;
	$mail_text.="$str\n";
	echo "$str\n";
}


$script_start_time=time();

$route_res=`route -n`;
$route_res=explode("\n", $route_res);
$def_route_iface=$CONFIG['route']['ext_iface'];
foreach($route_res as $r)
{
	$data = preg_split("/[\s,]+/", $r);
	if(trim($data[0]=='0.0.0.0'))
		$def_route_iface=trim($data[7]);
}

$ping_res=`ping {$CONFIG['route']['test_host']} -c1 -n -I{$CONFIG['route']['ext_iface']} | grep bytes`;
if(strpos($ping_res, 'ttl')===FALSE)
{
	logmail("Нет связи c с хостом {$CONFIG['route']['test_host']} через основной канал. Вероятно это означает, что канал неисправен.");
	if($def_route_iface!=$CONFIG['route']['backup_ext_iface'])
	{
		logmail("Тест доступности второго канала...");
		$backup_active=0;
		$ifconfig_res=`ifconfig`;
		if(strpos($ifconfig_res, $CONFIG['route']['backup_ext_iface'])===FALSE)
		{
			logmail("Резервный канал не доступен!");
			if($CONFIG['route']['backup_pppoe_name'])
			{
				$ppp_res=`ps ax | grep {$CONFIG['route']['backup_pppoe_name']}`;
				if(strpos($ppp_res, 'ppp')===FALSE)
				{
					logmail("PPP соединение требуется, но не запущено. Запускаем...");
					`pon {$CONFIG['route']['backup_pppoe_name']}`;
				}
			}
			while( ($script_start_time+50)>time())
			{
				$ifconfig_res=`ifconfig`;
				if(strpos($ifconfig_res, $CONFIG['route']['backup_ext_iface'])!==FALSE)
				{
					logmail("Канал запущен!\n");
					$backup_active=1;
					break;
				}
				sleep(1);
			}
		}
		else
		{
			$backup_active=1;
			logmail("Канал доступен!");
		}
		if($backup_active==1)
		{
			logmail("Перевод маршрутизции на резервный канал");
			`route del default`;
			`route del default`;
			`route add default dev {$CONFIG['route']['backup_ext_iface']}`;
		}
	}
	else logmail("Продолжаем использовать резервный канал");

}
else
{
	if($def_route_iface!=$CONFIG['route']['ext_iface'])
	{
		logmail("Основной канал восстановился, возвращаем маршрутизцию через основной канал!");
		`route del default`;
		`route del default`;
		`route add default dev {$CONFIG['route']['ext_iface']}`;
		`poff {$CONFIG['route']['backup_pppoe_name']}`;
	}
	echo "Связь в норме!\n";
}

if($mail_text)
{
	try
	{
		$mail_text="Обнаружены проблемы со связью с внешним миром:\n****\n\n".$mail_text."\n\n****\nПо указанным выше причинам могут быть перебои со связью с интерентом!\n";
		mailto($CONFIG['site']['admin_email'], "Inet backup errors", $mail_text);
		mailto($CONFIG['site']['doc_adm_email'], "Inet backup errors", $mail_text);
		echo "Почта отправлена!\n";
	}
	catch(Exception $e)
	{
		echo"Ошибка отправки почты!".$e->getMessage();
	}
}



?>