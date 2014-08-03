<?php

//	MultiMag v0.2 - Complex sales system
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

/// Остатки товара на складе на выбранную дату
class Report_OstatkiNaDatu extends BaseGSReport {

	function getName($short = 0) {
		if ($short)	return "Остатки на выбранную дату";
		else		return "Остатки товара на складе на выбранную дату";
	}

	function Form() {
		global $tmpl, $db;
		$curdate = date("Y-m-d");
		$tmpl->addContent("<h1>" . $this->getName() . "</h1>");
		$tmpl->addContent("
		<script type=\"text/javascript\">
		function dtinit()
		{
			initCalendar('dt',false)
		}
		addEventListener('load',dtinit,false)	
		</script>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='ostatkinadatu'>
		Дата:<br>
		<input type='text' name='date' id='dt' value='$curdate'><br>
		Склад:<br>
		<select name='sklad'>");
		$res = $db->query("SELECT `id`, `name` FROM `doc_sklady`");
		while ($nxt = $res->fetch_row())
			$tmpl->addContent("<option value='$nxt[0]'>".html_out($nxt[1])."</option>");
		$tmpl->addContent("</select><br>
		Группа товаров:<br>");
		$this->GroupSelBlock();
		$tmpl->addContent("Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Создать отчет</button></form>");
	}

	function Make($engine) {
		global $db, $CONFIG;
		$sklad = rcvint('sklad');
		$date = rcvdate('date');
		$unixtime = strtotime($date . " 23:59:59");
		$pdate = date("Y-m-d", $unixtime);
		$gs = request('gs');
		$g = request('g');

		$res = $db->query("SELECT `name` FROM `doc_sklady` WHERE `id`='$sklad'");
		if (! $res->num_rows)	throw new Exception("Склад не найден!");
		list($sklad_name) = $res->fetch_row();

		$this->loadEngine($engine);
		
		$this->header("Остатки товара на складе N$sklad ($sklad_name) на дату $pdate");
		
		$col_count = 1;
		$widths = array(6);
		$headers = array('ID');
		
		if ($CONFIG['poseditor']['vc']) {
			$widths[] = 7;
			$headers[] = 'Код';
			$widths[] = 60;
			$headers[] = 'Наименование';
			$col_count +=2;
		}
		else {
			$widths[] = 67;
			$headers[] = 'Наименование';
			$col_count +=1;
		}
		
		$headers = array_merge($headers, array('Кол-во', 'Б. цена', 'Сумма'));
		$widths = array_merge($widths, array(9, 9, 9));
		$col_count+=3;
		
		$this->tableBegin($widths);
		$this->tableHeader($headers);
		
		switch (@$CONFIG['doc']['sklad_default_order']) {
			case 'vc': $order = '`doc_base`.`vc`';
				break;
			case 'cost': $order = '`doc_base`.`cost`';
				break;
			default: $order = '`doc_base`.`name`';
		}
		
		$sum = $zeroflag = $bsum = $summass = 0;
		$res_group = $db->query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
		while ($group_line = $res_group->fetch_assoc()) {
			if ($gs && !in_array($group_line['id'], $g))	continue;
			
			$this->tableAltStyle();
			$this->tableSpannedRow( array($col_count), array( html_out("{$group_line['id']}. {$group_line['name']}") ) );
			$this->tableAltStyle(false);
			
			$res = $db->query("SELECT `doc_base`.`id`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name` , `doc_base`.`cost`,
				`doc_base_dop`.`mass`, `doc_base`.`vc`
			FROM `doc_base`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			WHERE `doc_base`.`group`='{$group_line['id']}'
			ORDER BY $order");
			while ($nxt = $res->fetch_row()) {
				$count = getStoreCntOnDate($nxt[0], $sklad, $unixtime, 1);
				if ($count == 0)	continue;
				if ($count < 0)		$zeroflag = 1;
				$cost_p = sprintf("%0.2f", $nxt[2]);
				$bsum_p = sprintf("%0.2f", $nxt[2] * $count);
				$count_p = round($count, 3);
				$bsum+=$nxt[2] * $count;
				$summass+=$count * $nxt[3];
				
				if ($CONFIG['poseditor']['vc'])
					$a = array($nxt[0], $nxt[4], $nxt[1], $count_p, $cost_p, $bsum_p);
				else	$a = array($nxt[0], $nxt[1], $count_p, $cost_p, $bsum_p);
				$this->tableRow($a);
			}
		}
		$cs = $col_count - 1;
		$bsum = sprintf("%0.2f", $bsum);
		
		$this->tableAltStyle();
		$this->tableSpannedRow( array($col_count - 1, 1), array('Итого:', $bsum ) );
		if (!$zeroflag)
			$this->tableSpannedRow( array($col_count), array( "Общая масса склада: $summass кг." ) );
		else	$this->tableSpannedRow( array($col_count), array( "Общая масса склада: невозможно определить из-за отрицательных остатков" ) );
		$this->tableAltStyle(false);
		$this->tableEnd();
		$this->output();
		exit(0);
	}
}

?>