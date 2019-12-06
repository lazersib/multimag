<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2019, BlackLight, TND Team, http://tndproject.org
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
/// Отчёт по покупкам агента за период
///

use \common\helpers\DocumentHelper;

class Report_Agent_Purchases_By_Period extends BaseReport {

	/// Получить название отчёта
	function getName($short = 0) {
		if ($short) {
			return "По покупкам агента";
		} else {
			return "Отчёт по покупкам агента за период";
		}
	}

	/// Форма для формирования отчёта
	function Form() {
		global $tmpl, $db;
		$date_start = date("Y-01-01");
		$date_end = date("Y-m-d");
		$tmpl->addContent("<h1>" . $this->getName() . "</h1>
        <script src='/css/jquery/jquery.js' type='text/javascript'></script>
        <script src='/css/jquery/jquery.alerts.js' type='text/javascript'></script>
        <link href='/css/jquery/jquery.alerts.css' rel='stylesheet' type='text/css' media='screen'>
        <script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
        <form action='' method='post'>
        <input type='hidden' name='mode' value='agent_purchases_by_period'>
        <input type='hidden' name='opt' value='make'>
        Начальная дата:<br>
        <input type='text' name='date_f' id='datepicker_f' value='$date_start'><br>
        Конечная дата:<br>
        <input type='text' name='date_t' id='datepicker_t' value='$date_end'><br>");
        
        
        $tmpl->addContent("
		Агент:<br>
            <input type='hidden' name='agent_id' id='agent_id' value=''>
            <input type='text' id='ag' name='agent_name' style='width: 400px;' value=''><br>
		");
        $tmpl->addContent("
		<script type='text/javascript'>
			$(document).ready(function(){
				$(\"#ag\").autocomplete(\"/docs.php\", {
				delay:300,
				minChars:1,
				matchSubset:1,
				autoFill:false,
				selectFirst:true,
				matchContains:1,
				cacheLength:10,
				maxItemsToShow:15,
				formatItem:agliFormat,
				onItemSelect:agselectItem,
				extraParams:{'l':'agent','mode':'srv','opt':'ac'}
			});

            });
            function agliFormat (row, i, num) {
                var result = 
				row[0] + 
				' (' + row[3] + ')' + 
				\"<em class='qnt'> тел . \" + row[2] + \"</em>\";
                return result;
            }
            function agselectItem(li) {
                if( li == null ) var sValue = \"Ничего не выбрано!\";
                if( !!li.extra ) var sValue = li.extra[0];
                else var sValue = li.selectValue;
                document.getElementById('agent_id').value=sValue;
            }

        </script>");
		
		$tmpl->addContent("<br>
        Формат: 
        <select name='opt'>
	        <option>pdf</option>
	        <option>html</option>
	        <option>xlsx</option>
	        <option>xls</option>
	        <option>ods</option>
        </select><br>
        <button type='submit'>Создать отчет</button></form>
        <script type=\"text/javascript\">
        initCalendar('datepicker_f',false);
        initCalendar('datepicker_t',false);
        </script>");
	}


	function Make($engine) {
		global $db;
		$agent_id = rcvint('agent_id');
		$dt_f = rcvdate('date_f', false);
		$dt_t = rcvdate('date_t', false);

		$dayStart = strtotime("$dt_f 00:00:00");
		$dayEnd = strtotime("$dt_t 23:59:59");
		if($dt_f == false || $dt_t == false || $dayEnd == -1 || $dayStart == -1) {
			throw new ErrorException("Что-то не так с датами");
		}
		$res = $db->query("SELECT `fullname` FROM `doc_agent` WHERE `id`=$agent_id");
		$agentFullname = $res->fetch_row()[0];
		if (!$res->num_rows) {
			throw new Exception("Покупатель не найден");
		}
		$this->loadEngine($engine);
		$this->header($this->getName(true) . " $agentFullname за период c $dt_f по $dt_t");
		$header = [
			'Id' => 7,
			'Док.' => 8,
			'Дата' => 10,
			'Товар' => 12,
			'Пров.' => 6,
			'Сумма' => 10,
			'Оплачено связанным' => 12,
			'Оплачено суммарно' => 10,
		];
		$this->tableBegin(array_values($header));
		$this->tableHeader(array_keys($header));

		$resource = $db->query("
			SELECT 
			`doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`,
			 `doc_list`.`date`, `doc_list`.`sum`, `doc_list`.`p_doc`,
			  `doc_list`.`ok`, `doc_list`.`agent`, `doc_list_pos`.`tovar`,
			  `doc_base`.`name` as product_name
			FROM `doc_list`
			LEFT JOIN `doc_list_pos` ON `doc_list_pos`.`doc`=`doc_list`.`id`
			LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
			WHERE
			`doc_list`.`agent` = $agent_id AND
			`doc_list`.`date` >= $dayStart AND
			`doc_list`.`date` <= $dayEnd AND
			`doc_list`.`type` = 2 AND
			`doc_list`.`mark_del`= 0
			ORDER BY `date`");
		while($documentRow = $resource->fetch_assoc()) {
			$sumFromChildren = DocumentHelper::getCalculatedPaySum($documentRow['id']);
			$paysum = DocumentHelper::getSavedPaySum($documentRow['id']);
			$this->tableRow([
				$documentRow['id'],
				$documentRow['altnum'] . $documentRow['subtype'],
				date('Y-m-d',$documentRow['date']),
				$documentRow['product_name'],
				$documentRow['ok']?'Да':'Нет',
				$documentRow['sum'],
				sprintf("%0.2f", $sumFromChildren),
				sprintf("%0.2f", $paysum),
			]);
		}
		$this->tableEnd();
		$this->output();
		exit(0);
	}
}
