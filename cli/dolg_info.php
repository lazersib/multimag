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

require_once($CONFIG['site']['location']."/core.php"); 
//require_once($CONFIG['site']['location']."/include/class.phpmailer.php"); 
require_once($CONFIG['site']['location']."/include/doc.core.php"); 

$mail = new PHPMailer();  
  
$mail->From     = $CONFIG['site']['admin_email'];
$mail->FromName = $CONFIG['site']['name'].' - Site Service System';  
$mail->Mailer   = "mail";  
$mail->CharSet  = "UTF-8";
$mail->Subject="Ваши долги";

$mail_text=array();
$sum_dolga=array();

$res=mysql_query("SELECT `id`, `name`, `responsible` FROM `doc_agent` ORDER BY `name`");
while($nxt=mysql_fetch_row($res))
{
	//if($nxt[2]==0)	continue;
	$dolg=DocCalcDolg($nxt[0],0);
	if( $dolg>0 )
	{
		$dolg=abs($dolg);
		$sum_dolga[$nxt[2]]+=$dolg;
		$dolg=sprintf("%0.2f",$dolg);
		$a_name=html_entity_decode ($nxt[1],ENT_QUOTES,"UTF-8");
		$mail_text[$nxt[2]].="Агент $a_name (id:$nxt[0]) должен нам $dolg рублей\n";
	}
}

try 
{
	$xmppclient->connect();
	$xmppclient->processUntil('session_start');
	$xmppclient->presence();

$res=mysql_query("SELECT `id`, `name`, `email`, `jid` FROM `users`");
while($nxt=mysql_fetch_row($res))
{
	if($mail_text[$nxt[0]])
	{
		$dolg=sprintf("%0.2f",$sum_dolga[$nxt[0]]);
		$text="Уважаемый(ая) $nxt[1]!\nНекоторые из Ваших клиентов, для которых Вы являетесь ответственным менеджером, имеют непогашенные долги перед нашей компанией на общую сумму {$dolg} рублей.\nНеобходимо в кратчайший срок решить данную проблему!\n\nВот список этих клиентов:\n".$mail_text[$nxt[0]]."\n\nПожалуйста, не откладывайте решение проблемы на длительный срок!";
		
		$mail->ClearAddress();
		$mail->AddAddress($nxt[2],$nxt[1]);
		$mail->Body=$text;
		if($mail->Send())
			echo "\nПочта отправлена!";
		else echo"\nошибка почты!".$mail->ErrorInfo;
		
		if($nxt[3])
		{

				$xmppclient->message($nxt[3], $text);
				echo "\nСообщение было отправлено через XMPP!";

		}
		
		echo $text."\n\n\n\n";
	}
}

	$xmppclient->disconnect();
	echo "\nСообщение было отправлено через XMPP!";
} 
catch(XMPPHP_Exception $e) 
{
	echo"\nНевозможно отправить сообщение XMPP";
}

?>