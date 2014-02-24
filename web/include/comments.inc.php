<?php

//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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

/// Работа с коментариями к статьям, новостям, товарам, и пр.
class CommentDispatcher
{
	protected $object_name;
	protected $object_id;

	function __construct($object_name, $object_id)
	{
		settype($object_id, 'int');
		$this->object_name=$object_name;
		$this->object_id=$object_id;
	}

	/// Сохранить коментарий в базу и отправить уведомление при необходимости
	function WriteComment($text, $rate, $autor_name='', $autor_email='')
	{
		global $CONFIG, $db;
		$uid=@$_SESSION['uid'];
		settype($uid, 'int');
		settype($rate, 'int');
		if($rate<0)	$rate=0;
		if($rate>5)	$rate=5;
		if($uid>0)	$autor_name=$autor_email='';
		else		$uid=0;

		$ip=getenv("REMOTE_ADDR");
		$ua=getenv("HTTP_USER_AGENT");
		$text=$db->real_escape_string($text);
		$ua=$db->real_escape_string($ua);
		$autor_name=$db->real_escape_string($autor_name);
		$autor_email=$db->real_escape_string($autor_email);
		$object_name_sql=$db->real_escape_string($this->object_name);
		$db->query("INSERT INTO `comments` (`date`, `object_name`, `object_id`, `autor_name`, `autor_email`, `autor_id`, `text`, `rate`, `ip`, `user_agent`)
		VALUES (NOW(), '$object_name_sql', '{$this->object_id}', '$autor_name', '$autor_email', '$uid', '$text', '$rate', '$ip', '$ua')");
		if($CONFIG['noify']['comments'])
		{
			switch($this->object_name)
			{
				case 'product':
					$url='http://'.$CONFIG['site']['name'].'/vitrina.php?mode=product&p='.$this->object_id;
					break;
				default:
					$url='UNKNOWN';

			}
			$text="Объект: {$this->object_name}|{$this->object_id}\nСсылка: $url\nАвтор: $autor_name <$autor_email>\nUID: $uid\nРейтинг:$rate\nТекст: $text";
			sendAdmMessage($text,'Новый коментарий');
		}
		return $db->insert_id;
	}

	/// Получить рейтинг заданного объекта
	function GetRating()
	{
		global $db;
		$res=$db->query("SELECT SUM(`rate`)/COUNT(`rate`) FROM `comments` WHERE `object_name`='{$this->object_name}' AND `object_id`='{$this->object_id}'");
		if(!$res->num_rows)	return 0;
		$r=$res->fetch_row();
		return round($r[0]);
	}
};


?>