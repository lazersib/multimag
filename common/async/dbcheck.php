<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2015, BlackLight, TND Team, http://tndproject.org
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
namespace async;

require_once($CONFIG['site']['location'] . "/include/doc.core.php");
require_once($CONFIG['site']['location'] . "/include/doc.nulltype.php");

/// Ассинхронный обработчик. Перепроводка документов и перерасчёт контрольных значений в таблицах базы данных.
class dbcheck extends \AsyncWorker {

	function getDescription() {
            return "Перепроводка документов и перерасчёт контрольных значений в таблицах базы данных.";
	}

	/// Запускает обработчик
	function run() {

		global $CONFIG, $db;
		$this->mail_text = '';
		$tim = time();
                
		$this->SetStatusText("Запуск...");
		$db->query("UPDATE `variables` SET `corrupted`='1', `recalc_active`='1'");
		sleep(1);
		$res = $db->query("SELECT `version` FROM `db_version`");

		$db_version = $res->fetch_row();
		if ($db_version[0] != MULTIMAG_REV) {
			$text = "Версия базы данных не соответствует ревизии программы. Это говорит о некорректно выполненном обновлении. "
				."Версия базы: {$db_version[0]}, ревизия программы: " . MULTIMAG_REV . " (" . MULTIMAG_VERSION . ")\n";
			$this->mail_text.=$text;
			echo $text;
		}
		
		$this->SetStatusText("Сброс остатков...");
		$db->query("UPDATE `doc_base_cnt` SET `cnt`='0'");
		$db->query("UPDATE `doc_kassa` SET `ballance`='0'");
		$db->query("UPDATE `doc_base` SET `buy_time`='1970-01-01 00:00:00', `likvid`=0, `transit_cnt`=0");
                
        	// Заполнение нулевого количества для всех товаров
                
		$res = $db->query("SELECT `id` FROM `doc_sklady`");
		while ($sline = $res->fetch_row()) {
			$pres = $db->query("SELECT `doc_base`.`id`, `doc_base_cnt`.`id`
			FROM `doc_base`
			LEFT JOIN `doc_base_cnt` ON `doc_base`.`id`=`doc_base_cnt`.`id` AND `doc_base_cnt`.`sklad`='$sline[0]'
			WHERE `doc_base`.`pos_type`='0'");
			while ($pline = $pres->fetch_row()) {
				if (!$pline[1])
					$db->query("INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`)	VALUES ('$pline[0]', '$sline[0]', '0')");
			}
		}
                
                $this->SetStatusText("Поиск проведённых документов с пометкой на удаление...");
		// Поиск проведённых документов с пометкой на удаление
		$res = $db->query("SELECT `id`, `altnum`, `subtype`
                    FROM `doc_list`
		WHERE `ok`>0 AND `mark_del`>0
                ORDER BY `date`");
		while ($line = $res->fetch_assoc()) {
                    $text = "Проведённый документ ID {$line['id']} ({$line['altnum']}{$line['subtype']}) помечен на удаление!\n";
                    echo $text;
                    $this->mail_text .= $text;
		}
                
		$this->SetStatusText("Расчет дат первой покупки...");                
        	// Заполнение дат первой покупки для раздела новинок
                $db->query("CREATE TEMPORARY TABLE `buytime_tmp` (
                `id` int(11) NOT NULL,
                `time` datetime NOT NULL,
                UNIQUE KEY `id` (`id`)
                ) ENGINE=memory  DEFAULT CHARSET=utf8;");
                
		$res = $db->query("SELECT `id` FROM `doc_base` WHERE `doc_base`.`pos_type`=0");
		while ($pos_data = $res->fetch_row()) {
			$doc_res = $db->query("SELECT `doc_list`.`date`, `doc_dopdata`.`value`
			FROM `doc_list_pos`
			INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND (`doc_list`.`type`='1' OR `doc_list`.`type`='17') AND `doc_list`.`ok`!=0
                        LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='return'
			WHERE `doc_list_pos`.`tovar`=$pos_data[0]
			ORDER BY `doc_list`.`date`");
			if ($doc_res->num_rows) {
                            while($doc_info = $doc_res->fetch_row()) {
                                if($doc_info[1]) {
                                    continue;
                                }
				$buy_time = date("Y-m-d H:i:s", $doc_info[0]);
				//echo "$buy_time\n";
                                break;
                            }
                            $db->query("INSERT INTO `buytime_tmp` VALUES ({$pos_data[0]}, '$buy_time')");
			}
		}
                $db->query("UPDATE `doc_base`, `buytime_tmp` SET `doc_base`.`buy_time`=`buytime_tmp`.`time` WHERE `doc_base`.`id`=`buytime_tmp`.`id`");
                $db->query("DROP TABLE `buytime_tmp`");                
                
		$this->SetStatusText("Расчет транзитов...");
		// Кеширование транзитов
		$res = $db->query("SELECT `doc_base`.`id`, (SELECT SUM(`doc_list_pos`.`cnt`) FROM `doc_list_pos`
		INNER JOIN `doc_list` ON `doc_list`.`type`='12' AND `doc_list`.`ok`>'0'
		AND `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`id` NOT IN (SELECT DISTINCT `p_doc` FROM `doc_list` WHERE `ok` != '0' AND `type`='1' )
		WHERE `doc_list_pos`.`tovar`=`doc_base`.`id` GROUP BY `doc_list_pos`.`tovar`) FROM `doc_base`");
		while ($line = $res->fetch_row()) {
			if (!$line[1])	continue;
			$db->query("UPDATE `doc_base` SET `transit_cnt`='$line[1]' WHERE `id`='$line[0]'");
		}

		// ============== Расчет ликвидности ===================================================
                $this->SetStatusText("Расчет ликвидности...");
		$a_likv = getLiquidityOnDate(time());
                $db->query("CREATE TEMPORARY TABLE IF NOT EXISTS `doc_base_likv_update` (
                `id` int(11) NOT NULL auto_increment,
                `likvid` double NOT NULL,
                UNIQUE KEY `id` (`id`)
                ) ENGINE=Memory  DEFAULT CHARSET=utf8;");

                foreach($a_likv as $pos_id => $likv) {
                    $db->query("INSERT INTO `doc_base_likv_update` VALUES ($pos_id, $likv)");
                }

                $db->query("UPDATE `doc_base`,`doc_base_likv_update` SET `doc_base`.`likvid`=`doc_base_likv_update`.`likvid` 
                    WHERE `doc_base`.`id`=`doc_base_likv_update`.`id`");
		
		// ================ Проверка, что у всех перемещений установлен не нулевой склад назначения ===================
		$res = $db->query("SELECT `doc_list`.`id`, `doc_dopdata`.`value`
		FROM `doc_list`
		LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='na_sklad'
		WHERE `doc_list`.`type`='8'");
		while ($line = $res->fetch_row()) {
                    if (!$line[1]) {
                        $text = "У перемещения ID $line[0] не задан склад назначения. Остатки на складе не верны!\n";
                        echo $text;
                        $this->mail_text .= $text;
                    }
		}

		// ================================ Перепроводка документов с коррекцией сумм ============================
		$this->SetStatusText("Перепроводка документов...");
		$i = 0;
                
                $query = \document::getStandardSqlQuery() 
                    . " WHERE `a`.`ok`>'0' AND `a`.`type`!='3' AND `a`.`mark_del`='0'"
                    . " ORDER BY `a`.`date`, `a`.`type`";
		$res = $db->query($query);
		$allcnt = $res->num_rows;
		$opp = $cnt = 0;

		while ($line = $res->fetch_assoc()) {
                    $pp = floor(($cnt / $allcnt) * 100);
                    if ($pp != $opp) {
                            $this->SetStatus($pp);
                            $opp = $pp;
                    }
                    $cnt++;
                    $document = \document::getInstanceFromArray($line);
                    $err = $document->apply(true);
                    if ($err) {
                        $dt = date("Y-m-d H:i:s", $line['date']);
                        $db->query("UPDATE `doc_list` SET `err_flag`='1' WHERE `id`='{$line['id']}'");
                        $text = "{$line['id']} (" . $document->getViewName() . " N {$line['altnum']}{$line['subtype']} от $dt): $err\n";
                        echo $text;
                        $this->mail_text .= $text;
                        $i++;
                    }
		}
		if ($i) {
			$text = "-----------------------\nИтого: $i документов с ошибками проведения!\n";
			echo $text;
			$this->mail_text.=$text;
		}
		else	echo"Ошибки последовательности документов не найдены!\n";
		$res = $db->query("UPDATE `variables` SET `recalc_active`='0'");

		$this->SetStatusText("Удаление помеченных на удаление...\n");
		// ============================= Удаление помеченных на удаление =========================================
		$tim_minus = time() - 60 * 60 * 24 * @$CONFIG['auto']['doc_del_days'];
		$res = $db->query(\document::getStandardSqlQuery() . " WHERE `a`.`mark_del`<'$tim_minus' AND `a`.`mark_del`>'0'");
		while ($line = $res->fetch_assoc()) {
                    try {
                        $document = \document::getInstanceFromArray($line);
                        $document->delExec();                                
                        echo "Док. ID:{$line['id']} удалён\n";
                    } catch (\Exception $e) {
                        $text = "Док. ID:{$line['id']}, ошибка удаления: " . $e->getMessage() . "\n";
                        $this->mail_text.=$text;
                        echo $text;
                    }
		}
		$res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list`.`id`, `doc_agent`.`name`, `doc_list_pos`.`id`, `doc_base`.`name` FROM `doc_list_pos`
		INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`type`='1' AND `doc_list`.`ok`>'0'
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
		WHERE `doc_list_pos`.`cost`<='0' ");
		while ($line = $res->fetch_row()) {
			$text = "Поступление ID:$line[1], товар $line[0]($line[4]) - нулевая цена! Агент $line[2]\n";
			echo $text;
			$this->mail_text.=$text;
		}

		if ($this->mail_text) {
                    try {

                        $mail_text = "При автоматической проверке базы данных сайта найдены следующие проблемы:\n****\n\n" . $this->mail_text . "\n\n****\nНеобходимо исправить найденные ошибки!";

                        mailto($CONFIG['site']['doc_adm_email'], "DB check report", $mail_text);
                        echo "Почта отправлена!";
                        $db->query("UPDATE `variables` SET `corrupted`='1'");
                    } catch (Exception $e) {
                        echo"Ошибка отправки почты!" . $e->getMessage();
                    }
                } else {
                    echo"Ошибок не найдено, не о чем оповещать!\n";
                    $db->query("UPDATE `variables` SET `corrupted`='0'");
                }
        }

	function finalize() {
		global $db;
		$db->query("UPDATE `variables` SET `recalc_active`='0'");
	}

}
