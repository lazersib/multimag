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

include_once("core.php");
// Работа с коментариями к статьям, новостям, товарам, и пр.

class CommentDispatcher
{
	protected $object_name;
	protected $object_id;
	
	function __construct($object_name, $object_id)
	{
		settype($object_id, 'int');
		$object_name=mysql_real_escape_string($object_name);
		$this->object_name=$object_name;
		$this->object_id=$object_id;
	}
	
	function WriteComment($text, $rate, $autor_name='', $autor_email='')
	{
		global $CONFIG;
		$uid=@$_SESSION['uid'];
		settype($uid, 'int');
		settype($rate, 'int');
		if($rate<0)	$rate=0;
		if($rate>5)	$rate=5;
		if($uid>0)	$autor_name=$autor_email='';
		else		$uid=0;
		
		if($CONFIG['noify']['comments'])
		{
			$text="Object: {$this->object_name}|{$this->object_id}\nAuthor: $autor_name <$autor_email>\nUID: $uid\nRate:$rate\nText: $text";		
			sendAdmMessage($text,'New comments');
		}		
		
		$ip=getenv("REMOTE_ADDR");
		$ua=getenv("HTTP_USER_AGENT");
		$text=mysql_real_escape_string($text);
		$ua=mysql_real_escape_string($ua);
		$autor_name=mysql_real_escape_string($autor_name);
		$autor_email=mysql_real_escape_string($autor_email);
		mysql_query("INSERT INTO `comments` (`date`, `object_name`, `object_id`, `autor_name`, `autor_email`, `autor_id`, `text`, `rate`, `ip`, `user_agent`)
		VALUES (NOW(), '{$this->object_name}', '{$this->object_id}', '$autor_name', '$autor_email', '$uid', '$text', '$rate', '$ip', '$ua')");
		if(mysql_errno())	throw new MysqlException("Не удалось сохранить коментарий!");
		return mysql_insert_id();
	}
	
	function GetRating()
	{
		$res=mysql_query("SELECT SUM(`rate`)/COUNT(`rate`) FROM `comments` WHERE `object_name`='{$this->object_name}' AND `object_id`='{$this->object_id}'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить рейтинг");
		return @ round(mysql_result($res,0,0));
	}
};


?>