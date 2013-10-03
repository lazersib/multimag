<?php

//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2013, BlackLight, TND Team, http://tndproject.org
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
/// Отчёт по кладовщикам в реализациях
class Report_Apay extends BaseGSReport {

	function getName($short = 0) {
		if ($short)	return "По платежам агентов";
		else		return "Отчёт по платежам агентов";
	}

	function Form() {
		global $tmpl;
		$d_f = date("Y-m-d", time() - 60 * 60 * 24 * 31);
		$d_t = date("Y-m-d");
		$tmpl->addContent("<h1>" . $this->getName() . "</h1>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='apay'>
		<fieldset><legend>Дата</legend>
		От: <input type=text id='dt_f' name='dt_f' value='$d_f'><br>
		До: <input type=text id='dt_t' name='dt_t' value='$d_t'>
		</fieldset>
		<br>
		Сортировать по:<br>
		<select name='order'>
		<option value='agent_name'>Имени агента</option>
		<option value='agent_id'>ID агента</option>
		<option value='bank_sum'>Приходу по банку</option>
		<option value='kass_sum'>Приходу по кассе</option>
		<option value='all_sum'>Общей сумме</option>
		</select><br>
		<label><input type='checkbox' name='direct' value='1'>В обратном порядке</label><br>
		Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Сформировать отчёт</button>
		</form>
		
		<script type=\"text/javascript\">
		function dtinit()
		{
			initCalendar('dt_apply',false)
			initCalendar('dt_update',false)
		}
		
		addEventListener('load',dtinit,false)
		</script>
		");
	}

	function Make($engine) {
		global $db;
		$this->loadEngine($engine);

		$dt_f = strtotime(rcvdate('dt_f'));
		$dt_t = strtotime(rcvdate('dt_t') . " 23:59:59");

		$print_f = date('Y-m-d', $dt_f);
		$print_t = date('Y-m-d', $dt_t);

		$this->header($this->getName() . ", с $print_f по $print_t");

		$widths = array(5, 65, 10, 10, 10);
		$headers = array('ID', 'Агент', 'По банку', 'По кассе', 'Сумма');
		$order = request('order');
		$direct = request('direct');
		$orders = array('agent_id', 'agent_name', 'bank_sum', 'kass_sum', 'all_sum');
		if (!in_array($order, $orders))
			$order = 'agent_name';
		$direct = $direct ? 'DESC' : 'ASC';
		$this->col_cnt = count($widths);
		$this->tableBegin($widths);
		$this->tableHeader($headers);

		$db->query("CREATE TEMPORARY TABLE `apay_report` (`agent_id` INT NOT NULL ,
		`agent_name` VARCHAR( 64 ) NOT NULL ,
		`bank_sum` DECIMAL( 10, 2 ) NOT NULL ,
		`kass_sum` DECIMAL( 10, 2 ) NOT NULL ,
		`all_sum` DECIMAL( 10, 2 ) NOT NULL 
		) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci");

		$db->query("INSERT INTO `apay_report` (`agent_id`, `agent_name`, `bank_sum`, `kass_sum`) SELECT `doc_agent`.`id` AS `agent_id`, `doc_agent`.`name` AS `agent_name`,
		( SELECT SUM(`doc_list`.`sum`) FROM `doc_list` WHERE `doc_list`.`agent`=`doc_agent`.`id` AND `doc_list`.`type`='4' AND `doc_list`.`ok`>0 AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<='$dt_t') AS `bank_sum`,
		( SELECT SUM(`doc_list`.`sum`) FROM `doc_list` WHERE `doc_list`.`agent`=`doc_agent`.`id` AND `doc_list`.`type`='6' AND `doc_list`.`ok`>0 AND `doc_list`.`date`>='$dt_f' AND `doc_list`.`date`<='$dt_t') AS `kass_sum`		
		FROM `doc_agent`");

		$db->query("UPDATE `apay_report` SET `all_sum`=`bank_sum`+`kass_sum`");

		$res = $db->query("SELECT `agent_id`, `agent_name`, `bank_sum`, `kass_sum`, `all_sum` FROM `apay_report` WHERE `all_sum`>0  ORDER BY $order $direct");
		$sumb = $sumc = $count = 0;

		while ($nxt = $res->fetch_row()) {
			$this->tableRow(array($nxt[0], $nxt[1], $nxt[2], $nxt[3], $nxt[4]));
			$sumb+=$nxt[2];
			$sumc+=$nxt[3];
			$count++;
		}
		$this->tableAltStyle(true);
		$sum = sprintf("%0.2f", $sumb + $sumc);
		$sumb = sprintf("%0.2f", $sumb);
		$sumc = sprintf("%0.2f", $sumc);
		$this->tableSpannedRow(array(2, 1, 1, 1), array("Итого: $count агентов", $sumb, $sumc, $sum));
		$this->tableEnd();
		$this->output();
		exit(0);
	}
}


?>