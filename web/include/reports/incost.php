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
/// Закупочная стоимость товаров
/// Алгоритм расчёта основан на алгоритме вычисления актуальной цены поступления
class Report_Incost extends BaseGSReport {
	
	/// Получить наименование отчёта
	function getName($short = 0) {
		if ($short)	return "По себестоимости проданных товаров";
		else		return "Отчёт по себестоимости проданных товаров";
	}

	function Form() {
		global $tmpl;
		$d_t = date("Y-m-d");
		$d_f = date("Y-m-d", time() - 60 * 60 * 24 * 31);
		$tmpl->addContent("<h1>" . $this->getName() . "</h1>");
		$tmpl->msg("Отчёт <b>очень</b> тяжелый! Время генерации может составлять 5 минут и более. Запаситесь терпением.<br>
			Обратите внимание на то, что во время формирования отчёта работа с базой и сайтом сильно замедляется!<br>
			Не выбирайте большие интервалы, если отчёт будет выполняться более 10 минут - его формирование прервётся без выдачи результата!");
		$tmpl->addContent("<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='incost'>
		<fieldset><legend>Дата</legend>
		С:<input type=text id='dt_f' name='dt_f' value='$d_f'><br>
		По:<input type=text id='dt_t' name='dt_t' value='$d_t'>
		</fieldset>
		<br>
                <select name='grp'>
                <option selected value=''>не группировать</option>
                <option value='d'>Группировать по дням</option>
                <option value='m'>Группировать по месяцам</option>
		<option value='y'>Группировать по годам</option>
                </select>
		Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Сформировать отчёт</button>
		</form>		
		</script>
		");
	}

	function Make($engine) {
		global $db;
		set_time_limit(60 * 10); // Выполнять не более 10 минут
		$this->loadEngine($engine);

		$dt_f = strtotime(rcvdate('dt_f'));
		$dt_t = strtotime(rcvdate('dt_t'));
                
                $grp = request('grp');

		$print_df = date('Y-m-d', $dt_f);
		$print_dt = date('Y-m-d', $dt_t);

		$this->header($this->getName() . " с $print_df по $print_dt");

		$widths = array(15, 75, 10);
		$headers = array('Дата', 'Объект', 'Сумма');

		$this->col_cnt = count($widths);
		$this->tableBegin($widths);
		$this->tableHeader($headers);
                
		$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`altnum`"
                        . "FROM `doc_list` "
                        . "WHERE `doc_list`.`type`=2 AND `doc_list`.`ok`>0 AND `doc_list`.`date`>={$dt_f} AND `doc_list`.`date`<={$dt_t} "
                        . "ORDER BY `doc_list`.`date`");
	
		$hsums = array();
		$hinfo = array();
		
                while($doc_info = $res->fetch_assoc()) {
			switch ($grp) {
				case 'd':
					$date = getdate($doc_info['date']);
					$hash = $date['year']*366 + $date['yday'];
					if(!isset($hinfo[$hash])) {
						$hinfo[$hash] = array(
						    'date_p' => date("Y-m-d", $doc_info['date']),
						    'info_p' => "Всего за день $hash"
						);
					}
					break;
				case 'm':
					$date = getdate($doc_info['date']);
					$hash = $date['year']*12 + $date['mon']-1;
					if(!isset($hinfo[$hash])) {
						$hinfo[$hash] = array(
						    'date_p' => date("Y-m", $doc_info['date']),
						    'info_p' => "Всего за {$date['mon']} месяц {$date['year']} года"
						);
					}
					break;
				case 'y':
					$date = getdate($doc_info['date']);
					$hash = $date['year'];
					if(!isset($hinfo[$hash])) {
						$hinfo[$hash] = array(
						    'date_p' => date("Y", $doc_info['date']),
						    'info_p' => "Всего за {$date['year']} год"
						);
					}
					break;
				default:
					$hash = $doc_info['id'];
					if(!isset($hinfo[$hash])) {
						$hinfo[$hash] = array(
						    'date_p' => date("Y-m-d H:i:s", $doc_info['date']),
						    'info_p' => "Реализация {$doc_info['altnum']} ({$doc_info['id']})"
						);
					}
			}		
			
                        $line_sum = 0;
                        $l_res = $db->query("SELECT `tovar` FROM `doc_list_pos` WHERE `doc`={$doc_info['id']}");
                        while($pos_info = $l_res->fetch_assoc()) {
                                $line_sum += getInCost($pos_info['tovar'], $doc_info['date'], 0);
                        }
			
			if($line_sum>0) {
				if(!isset($hsums[$hash]))
					$hsums[$hash] = $line_sum;
				else	$hsums[$hash] += $line_sum;				
			}                       
                }
		
		$sum = 0;
		foreach($hsums as $hash => $line_sum) {
			$sum += $line_sum;
			$sum_p = number_format($line_sum, 2, '.', ' ');
                        $this->tableRow(array($hinfo[$hash]['date_p'], $hinfo[$hash]['info_p'], $sum_p));
		}
                
		$sum_p = number_format($sum, 2, '.', ' ');
		$this->tableRow(array("", "Всего", "$sum_p р."));
		$this->tableEnd();
		$this->output();
		exit(0);
	}
}
?>