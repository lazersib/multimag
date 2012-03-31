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

if(!@mysql_connect('localhost','root',''))
{
	header("503 Service temporary unavariable");
	echo"<h1>503 Сервис временно недоступен!</h1>Не удалось соединиться с сервером баз данных. Возможно он перегружен, и слишком медленно отвечает на запросы, либо выключен. Попробуйте подключиться через 5 минут.";
	exit();
}
if(!@mysql_select_db('mmag_demo'))
{
    echo"Невозможно активизировать базу данных! Возможно, база данных повреждена. Попробуйте подключиться через 5 минут.";
    exit();
}

/*mysql_query("SET CHARACTER SET UTF8");
mysql_query("SET character_set_client = UTF8");
mysql_query("SET character_set_results = UTF8");
mysql_query("SET character_set_connection = UTF8");*/


if($last_time_counter = mysql_fetch_row(mysql_query("select `data` from `ps_parser` where `parametr` = 'last_time_counter'"))) {
	$last_time_counter = intval($last_time_counter[0]);
	
	$refer_query = "select `date`, `refer` from `counter` where `date`>'$last_time_counter' and ( ";
	$refer_query_first_like = true;
	$ps_settings = mysql_query("select `id`, `name`, `template`, `template_like` from `ps_settings` order by `prioritet`");
	while ($ps_settings_data = mysql_fetch_row($ps_settings)) {
		if ($refer_query_first_like) $refer_query .= "`refer` like '".$ps_settings_data[3]."'";
		else $refer_query .= " or `refer` like '".$ps_settings_data[3]."'";
		$refer_query_first_like = false;
	}
	$refer_query .= " )";
	
	$refer_query = mysql_query($refer_query);
	while ($refer_query_data = mysql_fetch_row($refer_query)) {
		
		$str= urldecode ($refer_query_data[1]); // Договорились сразу писать декодированные рефы, так что это должно быть ненужным
		$str = iconv("utf-8", "windows-1251", $str); // У меня в локале были проблемы, так что тоже может быть ненужным
		
		$str = trim($str);
		//echo $str.'<br />';
		
		$last_time_counter = intval($refer_query_data[0]);
		
		$ps_settings = mysql_query("select `id`, `name`, `template`, `template_like` from `ps_settings` order by `prioritet`"); // Избыточность: под каждый запрос мы постоянно запрашиваем одни и теже данные по шаблонам ПС, хотя мы их запросили в 44 строке, но не нашел как после прохода mysql_fetch_row возвращать маркер на первую строчку
		while ($ps_settings_data = mysql_fetch_row($ps_settings)) {
			preg_match($ps_settings_data[2], $str, $matches);
			if(count($matches)>0)
			{
				/*echo '<br />';
				print_r($matches);
				echo '<br />Поисковик: '.$ps_settings_data[1].'<br />';*/
				
				$matches = trim(mysql_real_escape_string($matches[1]));
				if ($ps_query_data = mysql_fetch_row(mysql_query("select `id` from `ps_query` where `query`='$matches'"))) {
					//echo 'Запрос найден в БД<br />';
				} else {
					//echo 'Запроса нет, добавляем в БД<br />';
					mysql_query("INSERT INTO `ps_query` (`query`) VALUES ('$matches')");
					$ps_query_data = mysql_fetch_row(mysql_query("select `id` from `ps_query` where `query`='$matches'"));
				}
				
				if (mysql_fetch_row(mysql_query("select `counter` from `ps_counter` where `date`='".date('Y-m-d',$last_time_counter)."' and `query`='".$ps_query_data[0]."' and `ps`='".$ps_settings_data[0]."'"))) {
					//echo 'Счетчик найден в БД<br />';
					mysql_query("UPDATE `ps_counter` SET `counter`=`counter`+1 WHERE `date`='".date('Y-m-d',$last_time_counter)."' AND `query`='".$ps_query_data[0]."' AND `ps`='".$ps_settings_data[0]."'");
				} else {
					//echo 'Счетчик не найден в БД<br />';
					mysql_query("INSERT INTO `ps_counter` (`date`,`query`,`ps`,`counter`) VALUES ('".date('Y-m-d',$last_time_counter)."','".$ps_query_data[0]."','".$ps_settings_data[0]."','1')");
				}
				break;
			}
		}
	}
	mysql_query("UPDATE `ps_parser` SET `data`='$last_time_counter' WHERE `parametr`='last_time_counter'");
}


?>