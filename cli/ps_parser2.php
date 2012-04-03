#!/usr/bin/php
<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2012, Sl_An, TND Team, http://tndproject.org
//
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU Affero General Public License as
//	published by the Free Software Foundation, either version 3 of the
//	License, or (at your option) any later version.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY OR FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU Affero General Public License for more details.
//
//	You should have received a copy of the GNU Affero General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

$c=explode('/',__FILE__);$base_path='';
for($i=0;$i<(count($c)-2);$i++)	$base_path.=$c[$i].'/';
require_once("$base_path/config_cli.php");
require_once($CONFIG['cli']['location']."/core.cli.inc.php");

if($last_time_counter = mysql_fetch_row(mysql_query("SELECT `data` FROM `ps_parser` where `parametr` = 'last_time_counter'")))
{
	$last_time_counter = intval($last_time_counter[0]);
	
	$refer_query = "SELECT `date`, `refer` FROM `counter` where `date`>'$last_time_counter' and ( ";
	$refer_query_first_like = true;
	$ps_settings = mysql_query("SELECT `id`, `name`, `template`, `template_like` FROM `ps_settings` ORDER BY `priority`");
	while ($ps_settings_data = mysql_fetch_row($ps_settings))
	{
		if ($refer_query_first_like) $refer_query .= "`refer` LIKE '".$ps_settings_data[3]."'";
		else $refer_query .= " OR `refer` LIKE '".$ps_settings_data[3]."'";
		$refer_query_first_like = false;
	}
	$refer_query .= " )";
	
	$refer_query = mysql_query($refer_query);
	while ($refer_query_data = mysql_fetch_row($refer_query))
	{
		
		//$str= urldecode ($refer_query_data[1]); // Договорились сразу писать декодированные рефы, так что это должно быть ненужным
		$str= $refer_query_data[1];
		$str = iconv("utf-8", "windows-1251", $str); // У меня в локале были проблемы, так что тоже может быть ненужным
		
		$str = trim($str);
		//echo $str.'<br />';
		
		$last_time_counter = intval($refer_query_data[0]);
		
		$ps_settings = mysql_query("SELECT `id`, `name`, `template`, `template_like` FROM `ps_settings` ORDER BY `priority`");
		// Избыточность: под каждый запрос мы постоянно запрашиваем одни и теже данные по шаблонам ПС, хотя мы их запросили в 44 строке,
		// но не нашел как после прохода mysql_fetch_row возвращать маркер на первую строчку
		while ($ps_settings_data = mysql_fetch_row($ps_settings))
		{
			preg_match($ps_settings_data[2], $str, $matches);
			if(count($matches)>0)
			{
				/*echo '<br />';
				print_r($matches);
				echo '<br />Поисковик: '.$ps_settings_data[1].'<br />';*/
				
				$matches = trim(mysql_real_escape_string($matches[1]));
				if ($ps_query_data = mysql_fetch_row(mysql_query("SELECT `id` FROM `ps_query` where `query`='$matches'")))
				{
					//echo 'Запрос найден в БД<br />';
					$ps_query_id=$ps_query_data[0];
				}
				else
				{
					//echo 'Запроса нет, добавляем в БД<br />';
					mysql_query("INSERT INTO `ps_query` (`query`) VALUES ('$matches')");
					//$ps_query_data = mysql_fetch_row(mysql_query("SELECT `id` FROM `ps_query` WHERE `query`='$matches'"));
					$ps_query_id=mysql_insert_id();
				}
				
// 				if (mysql_fetch_row(mysql_query("SELECT `counter` FROM `ps_counter`
// 				WHERE `date`='".date('Y-m-d',$last_time_counter)."' and `query`='".$ps_query_data[0]."' and `ps`='".$ps_settings_data[0]."'")))
// 				{
// 					//echo 'Счетчик найден в БД<br />';
// 					mysql_query("UPDATE `ps_counter` SET `counter`=`counter`+1
// 					WHERE `date`='".date('Y-m-d',$last_time_counter)."' AND `query`='".$ps_query_data[0]."' AND `ps`='".$ps_settings_data[0]."'");
// 				}
// 				else
// 				{
// 					//echo 'Счетчик не найден в БД<br />';
// 					mysql_query("INSERT INTO `ps_counter` (`date`,`query`,`ps`,`counter`) VALUES ('".date('Y-m-d',$last_time_counter)."','".$ps_query_data[0]."','".$ps_settings_data[0]."','1')");
// 				}
				
				mysql_query("INSERT INTO `ps_counter` (`date`,`query`,`ps`,`counter`) VALUES ('".date('Y-m-d',$last_time_counter)."','".$ps_query_id."','".$ps_settings_data[0]."','1')
				ON DUPLICATE KEY UPDATE `counter`=`counter`+1");
				echo mysql_error();
				break;
			}
		}
	}
	mysql_query("REPLACE `ps_parser` (`parametr`, `data`) VALUES ('last_time_counter', '$last_time_counter')");
}


?>