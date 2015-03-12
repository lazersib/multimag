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
namespace Actions;

// Расчет оборота агентов за период (для периодической накопительной скидки)
class AgentCalcAvgsum extends \Action {
	
	/// @brief Запустить
	public function run() {
		if(!isset($this->config['pricecalc']['acc_type']))
			throw new \Exception ('Тип периода для расчёта оборота агентов (pricecalc - acc_type) не задан в конфигурационном файле');
		
		if(isset($this->config['pricecalc']['acc_time']))
			$cnt = intval($this->config['pricecalc']['acc_time']);
		else	$cnt = 0;
		
		$di = new \DateCalcInterval();
		
		switch($this->config['pricecalc']['acc_type']) {
			case 'days':
				$di->calcXDaysBack($cnt);
				break;
			case 'months':
				$di->calcXMonthsBack($cnt);
				break;
			case 'years':
				$di->calcXYearsBack($cnt);
				break;
			case 'prevmonth':
				$di->calcPrevMonth();
				break;
			case 'prevquarter':
				$di->calcPrevQuarter();
				break;
			case 'prevhalfyear':
				$di->calcPrevHalfyear();
				break;
			case '':break;
			case 'prevyear':
			default:
				$di->calcPrevYear();
		}

		$acc = array();
		$res = $this->db->query("SELECT `agent`, `sum` FROM `doc_list` WHERE `date`>='{$di->start}' AND `date`<='{$di->end}'
			AND (`type`='1' OR `type`='4' OR `type`='6') AND `ok`>0 AND `agent`>0 AND `sum`>0");
		while($line = $res->fetch_assoc()) {
			if(isset($acc[$line['agent']]))
				$acc[$line['agent']] += $line['sum'];
			else $acc[$line['agent']] = $line['sum'];
		}
		
		$this->db->query("UPDATE `doc_agent` SET `avg_sum`=0");	// Сброс, т.к. нулевые агенты не перезаписываются
		foreach($acc as $agent => $sum) {
			if($sum)$this->db->update('doc_agent', $agent, 'avg_sum', $sum);
		}		
	}
}
