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


class Report_Images {

	function getName($short = 0) {
		if ($short)	return "По изображениям";
		else		return "Отчёт по изображениям складских наименований";
	}

	function Form() {
		global $tmpl;
		$tmpl->addContent("<h1>" . $this->getName() . "</h1>
		<form action=''>
		<input type='hidden' name='mode' value='images'>
		<input type='hidden' name='opt' value='ok'>
		<fieldset><legend>Эскизы изображений</legend>
		<label><input type='radio' name='show_img' value='0' checked>Не показывать</label><br>
		<label><input type='radio' name='show_img' value='1'>Показывать в низком качестве (ускорение загрузки, экономия трафика)</label><br>
		<label><input type='radio' name='show_img' value='2'>Показывать в обычном качестве (больше трафика)</label>
		</fieldset>
		<fieldset><legend>Сортировать по</legend>
		<label><input type='radio' name='rgroup' value='0' checked>Названиям изображений</label><br>
		<label><input type='radio' name='rgroup' value='1' disabled>Товарным наименованиям</label><br>
		</fieldset><button type='submit'>Сформировать</button></form>");
	}

	function MakeHTML() {
		global $tmpl, $db;
		$show_img = rcvint('show_img');
		$tmpl->loadTemplate('print');
		$tmpl->setContent("<h1>Отчёт по изображениям</h1>");
		$res = $db->query("SELECT `doc_img`.`id` AS `img_id`, `doc_img`.`name`, `doc_img`.`type`
		FROM `doc_img` ORDER BY `doc_img`.`id`");
		$img_col = $show_img ? '<th>Эскиз</th>' : '';
		$tmpl->addContent("<table width='100%'>
		<tr><th>ID</th>$img_col<th>Изображение</th><th>Умолч.</th><th>ID товара</th><th>Код</th><th>Наименование / произв.</th></tr>");
		while ($nxt = $res->fetch_array()) {
			$r = $db->query("SELECT `doc_base_img`.`pos_id`, `doc_base_img`.`default`, `doc_base`.`vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`
			FROM `doc_base_img`
			LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_base_img`.`pos_id`
			WHERE `doc_base_img`.`img_id`='{$nxt['img_id']}'");
			$pos_rows = array();
			$c = 0;
			while ($n = $r->fetch_array()) {
				$pos_rows[] = $n;
				$c++;
			}

			if ($show_img) {
				$img = new ImageProductor($nxt['img_id'], 'p', $nxt['type']);
				if ($show_img == 1) {
					$img->SetY(24);
					$img->SetQuality(20);
				} else {
					$img->SetY(64);
					$img->SetQuality(75);
				}
				$img_tag = "<img src='" . $img->GetURI() . "' alt=''>";
			}
			else	$img_tag = '';

			if ($c) {
				$a = 0;
				if ($show_img)	$img_tag = "<td rowspan='$c'>" . $img_tag;
				$tmpl->addContent("<tr><td rowspan='$c'>{$nxt['img_id']}$img_tag</td><td rowspan='$c'>".html_out("{$nxt['name']} ({$nxt['type']})"));
				foreach ($pos_rows as $line) {
					if ($a)	$tmpl->addContent("<tr>");
					$tmpl->addContent("<td>{$line['default']}<td>{$line['pos_id']}<td>{$line['vc']}<td>{$line['name']}\n");
					$a = 1;
				}
			}
			else {
				if ($show_img)	$img_tag = "<td>" . $img_tag;
				$tmpl->addContent("<tr><td>{$nxt['img_id']} $img_tag<td>".html_out("{$nxt['name']} ({$nxt['type']})")."<td colspan='5'>-\n");
			}
		}
		$tmpl->addContent("</table>");
	}

	function Run($opt) {
		if ($opt == '')	$this->Form();
		else		$this->MakeHTML();
	}

}

;
?>

