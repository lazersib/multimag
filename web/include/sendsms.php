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

/// Отправка факсов через web сервисы
/// требует php-libcurl
/// использует параметры конфигурации ['sendsms']
class SMSSender
{
	var $worker;
	function __construct()
	{
		global $CONFIG;
		if(@$CONFIG['sendsms']['service']=='')
			throw new Exception("Работа с sms не настроена!");
		else if($CONFIG['sendsms']['service']=='infosmska')
			$this->worker=new SendSMSTransportInfosmska();		
		
		else if($CONFIG['sendsms']['service']=='virtualofficetools')
			$this->worker=new SendSMSTransportVirtualofficetools();
	}
	
	/// Установить текст для отправки
	function setText($text,$translit=false)	{
		$this->worker->setText($text,$translit);
	}

	/// Установить номер для отправки
	function setNumber($number)	{
		$this->worker->setNumber($number);
	}
	
	/// Отправить sms сообщение
	function send()	{
		$this->worker->send();
	}
}

/// Базовый класс sms транспотра
abstract class SendSMSTransport
{
	var $text='';
	var $translit=false;
	var $number=0;
	
	/// Установить текст для отправки
	/// Если параметр translit установлен, сообщение будет отправлено в транслитерации
	function setText($text,$translit=false)
	{
		$this->text=$text;
		$this->translit=$translit;
	}

	/// Установить номер для отправки
	function setNumber($number)
	{
		$this->number=$number;
	}
	
	/// Отправить sms сообщение
	abstract function send();
}


/// SMS транспорт через infosmska.ru
class SendSMSTransportInfosmska extends SendSMSTransport
{
	/// Конструктор. Проверяет настройки и баланс
	/// Генерирует исключение при ошибке
	function __construct()
	{
		global $CONFIG;
		if(!isset($CONFIG['sendsms']))		throw new Exception("Работа с sms не настроена!");
		if(!@$CONFIG['sendsms']['login'])	throw new Exception("Работа с sms не настроена (не указан логин)");
		if(!@$CONFIG['sendsms']['password'])	throw new Exception("Работа с sms не настроена (не указан пароль)");
		$postdata = array(
		'login' => rawurlencode($CONFIG['sendsms']['login']), 
		'pwd' => rawurlencode(MD5($CONFIG['sendsms']['password']))
		); 

		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, 'http://api.infosmska.ru/interfaces/getbalance.ashx'); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_POST, 1); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata); 
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20); 
		$output=curl_exec($ch);  
		if(curl_errno($ch))	throw new Exception("Ошибка передачи: ".curl_error());
		curl_close($ch);
		
		$balance=sprintf("%0.2f",$output);
		if($balance<0.5)	throw new Exception("Не достаточно средств ($balance) на счете для отправки sms!");
	}
	
	/// Установить номер для отправки
	function setNumber($number)
	{
		if(preg_match('/^\+7\d{1,15}$/', $number))
			$number=substr($number,1);
		else throw new Exception('Номер для SMS указан в недопустимом формате!');	
		$this->number=$number;
	}

	function send()
	{
		global $CONFIG;
		if(!$this->number)	throw new Exception("Номер отправки не указан".$this->number);
		if($this->text=='')	throw new Exception("Текст собщения не задан");
		$translit=$this->translit?'1':'';
		
		$postdata = array(
		'login' => $CONFIG['sendsms']['login'], 
		'pwd' => MD5($CONFIG['sendsms']['password']),
		'phones' => $this->number,
		'message' => $this->text,
		'sender' => $CONFIG['sendsms']['callerid'],
		'translit' => $translit
		); 

		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, 'http://api.infosmska.ru/interfaces/SendMessages.ashx'); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_POST, 1); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata); 
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20); 
		$output=curl_exec($ch);  
		if(curl_errno($ch))	throw new Exception("Ошибка передачи: ".curl_error());
		curl_close($ch);
		
		if($output[0]!='o' && $output[0]!='O')	throw new Exception("Ошибка передачи сообщения: ".$output);
	}
}

/// SMS транспорт через virtualofficetools.ru
class SendSMSTransportVirtualofficetools extends SendSMSTransport
{
	/// Конструктор. Проверяет настройки и баланс
	/// Генерирует исключение при ошибке
	function __construct()
	{
		global $CONFIG;
		if(!isset($CONFIG['sendsms']))		throw new Exception("Работа с sms не настроена!");
		if(!@$CONFIG['sendsms']['login'])	throw new Exception("Работа с sms не настроена (не указан логин)");
		if(!@$CONFIG['sendsms']['password'])	throw new Exception("Работа с sms не настроена (не указан пароль)");
	}
	
	/// Установить номер для отправки
	function setNumber($number)
	{
		if(preg_match('/^\+7\d{10}$/', $number))
			$number=substr($number,2);
		else throw new Exception('Номер для SMS указан в недопустимом формате!');	
		$this->number=$number;
	}

	function send()
	{
		global $CONFIG;
		if(!$this->number)	throw new Exception("Номер отправки не указан".$this->number);
		if($this->text=='')	throw new Exception("Текст собщения не задан");
		$translit=$this->translit?'1':'';
		
		$postdata = array(
		'username' => $CONFIG['sendsms']['login'], 
		'password' => MD5($CONFIG['sendsms']['password']),
		'sender' => $CONFIG['sendsms']['callerid'],
		'phone' => $this->number,
		'smstext' => iconv("UTF-8", "CP1251", $this->text)
		); 

		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, 'http://www.virtualofficetools.ru/api/sms.task.once.api.php'); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_POST, 1); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata); 
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20); 
		$output=curl_exec($ch);  
		if(curl_errno($ch))	throw new Exception("Ошибка передачи: ".curl_error());
		curl_close($ch);
		
		$doc = new DOMDocument();
		$doc->loadXML($output);
		$errnode=$doc->getElementsByTagName('response')->item(0)->getElementsByTagName('error')->item(0);
		if($errnode->getAttribute('code')!=0)	throw new Exception("Ошибка от сервиса передачи: ".$errnode->getAttribute('message'));
	}
}

?>