<?php

//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2012, BlackLight, TND Team, http://tndproject.org
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

require_once($CONFIG['location'] . "/common/asyncworker.php");
require_once($CONFIG['site']['location'] . "/include/doc.core.php");
require_once($CONFIG['site']['location'] . "/include/doc.nulltype.php");

/// Ассинхронный обработчик. Анализ статистики переходов на сайт и выборка информации о переходах с поисковиков, и текстах запросов
class PsParserWorker extends AsyncWorker {

	function getDescription() {
		return "Анализ статистики переходов на сайт и выборка информации о переходах с поисковиков";
	}

	function run() {
		global $CONFIG, $db;

		$res = $db->query("select `data` from `ps_parser` where `parametr` = 'last_time_counter'");

		if ($last_time_counter = $res->fetch_row()) {
			$last_time_counter = intval($last_time_counter[0]);

			$refer_query = "select `date`, `refer` from `counter` where `date`>'$last_time_counter' and ( ";
			$refer_query_first_like = true;
			$ps_settings = $db->query("select `id`, `name`, `template`, `template_like` from `ps_settings` order by `prioritet`");
			while ($ps_settings_data = $ps_settings->fetch_row()) {
				if ($refer_query_first_like)
					$refer_query .= "`refer` like '" . $ps_settings_data[3] . "'";
				else
					$refer_query .= " or `refer` like '" . $ps_settings_data[3] . "'";
				$refer_query_first_like = false;
			}
			$refer_query .= " )";

			$refer_query = $db->query($refer_query);
			while ($refer_query_data = $refer_query->fetch_row()) {
				//$str= urldecode ($refer_query_data[1]); // Договорились сразу писать декодированные рефы, так что это должно быть ненужным
				$str = $refer_query_data[1];
				//$str = iconv("UTF-8", "cp1251", $str); // У меня в локале были проблемы, так что тоже может быть ненужным

				$str = trim($str);
				//echo $str."\n";

				$last_time_counter = intval($refer_query_data[0]);

				$true_ref = false;

				$ps_settings = $db->query("select `id`, `name`, `template`, `template_like` from `ps_settings` order by `prioritet`"); // Избыточность: под каждый запрос мы постоянно запрашиваем одни и теже данные по шаблонам ПС, хотя мы их запросили в 44 строке, но не нашел как после прохода mysql _fetch_row возвращать маркер на первую строчку
				while ($ps_settings_data = $ps_settings->fetch_row()) {
					preg_match($ps_settings_data[2], $str, $matches);
					if (count($matches) > 0) {
						//echo "\n";
						//print_r($matches);
						//echo "\nПоисковик: ".$ps_settings_data[1]."\n";

						$true_ref = true;

						$matches = trim($db->real_escape_string($matches[1]));
						if ($matches == '' || $matches == '\"') {
							echo "Пустой результат (" . $matches . "): " . $str . "\n";
							continue;
						}
						echo "Добавлено: " . $matches . "\n";
						if ($ps_query_data = $db->query("select `id` from `ps_query` where `query`='$matches'")->fetch_row()) {
							//echo "Запрос найден в БД\n";
						} else {
							//echo "Запроса нет, добавляем в БД\n";
							$db->query("INSERT INTO `ps_query` (`query`) VALUES ('$matches')");
							$ps_query_data = $db->query("select `id` from `ps_query` where `query`='$matches'")->fetch_row();
						}

						if ($db->query("select `counter` from `ps_counter` where `date`='" . date('Y-m-d', $last_time_counter) . "' and `query`='" . $ps_query_data[0] . "' and `ps`='" . $ps_settings_data[0] . "'")->fetch_row()) {
							//echo "Счетчик найден в БД\n";
							$db->query("UPDATE `ps_counter` SET `counter`=`counter`+1 WHERE `date`='" . date('Y-m-d', $last_time_counter) . "' AND `query`='" . $ps_query_data[0] . "' AND `ps`='" . $ps_settings_data[0] . "'");
						} else {
							//echo "Счетчик не найден в БД\n";
							$db->query("INSERT INTO `ps_counter` (`date`,`query`,`ps`,`counter`) VALUES ('" . date('Y-m-d', $last_time_counter) . "','" . $ps_query_data[0] . "','" . $ps_settings_data[0] . "','1')");
						}
						break;
					}
				}
				if (!$true_ref)		echo "Нет регулярки: " . $str . "\n";
			}
			$db->query("UPDATE `ps_parser` SET `data`='$last_time_counter' WHERE `parametr`='last_time_counter'");
		}
	}

	function finalize() {
	}

}

;
?>