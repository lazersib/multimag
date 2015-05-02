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

/// Остатки товара на складе на выбранную дату
class Report_GroupStore extends BaseGSReport {

	function getName($short = 0) {
		if ($short)	return "Групповой отчёт по остаткам";
		else		return "Групповой отчёт с остатками по складам";
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
		<input type='hidden' name='mode' value='groupstore'>
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

		$gs = request('gs');
		$g = request('g');

		$res = $db->query("SELECT `name` FROM `doc_sklady` WHERE `id`='$sklad'");
		if (! $res->num_rows)	throw new Exception("Склад не найден!");
		list($sklad_name) = $res->fetch_row();

		$this->loadEngine($engine);
		
		$this->header("Групповой отчёт с остатками по складам на основе склада N$sklad ($sklad_name)");
		
		$col_sql = '';
		$join_sql = '';
		$widths = array(6);
		$headers = array('ID');
		
		if ($CONFIG['poseditor']['vc']) {
			$widths[] = 7;
			$headers[] = 'Код';
			$widths[] = 60;
			$headers[] = 'Наименование';
		}
		else {
			$widths[] = 67;
			$headers[] = 'Наименование';
		}
		
		$widths[] = 10;
		$headers[] = 'Резерв';
		
		$res = $db->query("SELECT `id`, `name` FROM `doc_sklady`");
		while ($line = $res->fetch_assoc()) {
			$widths[] = 10;
			$headers[] = $line['name'];
			$col_sql .= ", `bcnt{$line['id']}`.`cnt` AS `count{$line['id']}`";
			$join_sql .= " LEFT JOIN `doc_base_cnt` AS `bcnt{$line['id']}` ON `bcnt{$line['id']}`.`id`=`doc_base`.`id`
				AND `bcnt{$line['id']}`.`sklad` = {$line['id']}";
		}
		
		$col_count = count($widths);
		$sum = array_sum($widths);
		$coeff =  100 / $sum;
		for($i=0;$i<$col_count;$widths[$i]*=$coeff,$i++);
		
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
			$this->tableSpannedRow( array($col_count), array( "{$group_line['id']}. {$group_line['name']}" ) );
			$this->tableAltStyle(false);
			
			$res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`,
                                `doc_base_dop`.`reserve`, `doc_base_dop`.`transit`, `doc_base_dop`.`offer` $col_sql
                            FROM `doc_base`
                            LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
                            $join_sql
                            WHERE `doc_base`.`group`='{$group_line['id']}'
                            ORDER BY $order");
			while ($line = $res->fetch_assoc()) {
				if($line['count'.$sklad]<=0)
					continue;
				$a = array($line['id']);

				if ($CONFIG['poseditor']['vc'])
					$a[] = $line['vc'];
				
				$a[] = $line['name'];
				$a[] = $line['reserve'];
				
				foreach($line as $id => $value) {
					if($id == 'id' || $id == 'name' || $id == 'vc')
						continue;
					$a[] = round($value, 2);
				}
				
				$this->tableRow($a);
			}
		}

		$this->tableEnd();
		$this->output();
		exit(0);
	}
}

?>