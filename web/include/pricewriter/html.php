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

/// Класс формирует прайс-лист в формате HTML
class PriceWriterHTML extends BasePriceWriter	{
	var $line;		///< Текущая строка
	var $span;		///< Количество столбцов таблицы

	/// Конструктор
	function __construct($db)
	{
		parent::__construct($db);
		$this->line=0;
	}

	/// Сформировать шапку прайса
	function open()	{
		global $CONFIG;
		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
		<html lang=\"ru\">
		<head>
		<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">

		<title>Прайс-лист: задание параметров</title>
		<style type='text/css'>
		body {font-size: 10px; color: #000; font-family: sans-serif; background-color: #fff}
		h1 {font-weight: bold; font-size: 24px; font-family: sans-serif; color: #00f;}
		h2 {font-weight: bold; font-size: 22px; font-family: sans-serif; color: #00f;}
		h3 {font-weight: bold; font-size: 20px; font-family: sans-serif; color: #00f;}
		h4 {font-weight: bold; font-size: 18px; font-family: sans-serif; color: #00f;}
		h5 {font-weight: bold; font-size: 16px; font-family: sans-serif; color: #00f;}
		h6 {font-weight: bold; font-size: 14px; font-family: sans-serif; color: #00f;}
		table {border: #000 1px solid; border-collapse: collapse; width: 100%; font-size: 10px; border-spacing: 0;}
		tr {background-color: #ffc;}
		th { border: #000 1px solid; padding: 2px; text-align: center; font-weight: bold; color: #000; background-color: #f60;}
		th.cost {background-color: #333; color: #fff;}
		th.n1 {background-color: #f90;}
		th.n2 {background-color: #fc0;}
		th.n3 {background-color: #fd0;}
		td { border: #000 1px solid; padding: 2px; }
		tr:nth-child(odd) {background-color: #cff;}
		.mini {font-size: 10px; text-align: center;}
		.v2 {width: 30px;}
		.np {page-break-after: always;}
		</style>
		</head>
		<body>
		<center>";
		$i=1;
		if(is_array($CONFIG['site']['price_text']))
		foreach($CONFIG['site']['price_text'] as $text)	{
			echo"<h$i>".html_out($text)."</h$i>";
			$this->line++;
			$i++;
		}

		$this->line++;
		echo"</center><table><tr>";
		for($cur_col=0;$cur_col<$this->column_count;$cur_col++)
			echo"<th class='cost'>Наименование</th><th class='cost'>Цена</th>";
		echo"</tr>";
		$this->span=$this->column_count*2;
	}

	/// Сформирвать тело прайса
	function write($group=0, $level=0)	{

		if($level>3)	$level=3;
		$res=$this->db->query("SELECT `id`, `name`, `printname` FROM `doc_group` WHERE `pid`='$group' AND `hidelevel`='0' ORDER BY `id`");
		while($nxt=$res->fetch_row()){
			if($nxt[0]==0) continue;
			if(is_array($this->view_groups))
				if(!in_array($nxt[0],$this->view_groups))	continue;

			$this->line++;
			echo"<tr><th class='n$level' colspan='{$this->span}'>".html_out($nxt[1])."</th></tr>";
			$this->writepos($nxt[0], $nxt[2]?$nxt[2]:$nxt[1] );
			$this->write($nxt[0], $level+1); // рекурсия

		}
	}

	/// Сформировать завершающий блок прайса
	function close()	{
		global $CONFIG;
		echo "<tr><td colspan='{$this->span}' class='mini'>Generated from MultiMag (<a href='http://multimag.tndproject.org'>http://multimag.tndproject.org</a>), for <a href='http://{$CONFIG['site']['name']}'>http://{$CONFIG['site']['name']}</a><br>Прайс создан системой MultiMag (<a href='http://multimag.tndproject.org'>http://multimag.tndproject.org</a>), специально для <a href='http://{$CONFIG['site']['name']}'>http://{$CONFIG['site']['name']}</a></td></tr></table>";
	}

	/// Сформировать строку прайса
	function writepos($group=0, $group_name='')
	{
		$res=$this->db->query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost_date` , `doc_base`.`proizv`
		FROM `doc_base`
		LEFT JOIN `doc_group` ON `doc_base`.`group`=`doc_group`.`id`
		WHERE `doc_base`.`group`='$group' AND `doc_base`.`hidden`='0' ORDER BY `doc_base`.`name`");
		$i=$cur_col=0;
		while($nxt=$res->fetch_row())	{
			if($cur_col>=$this->column_count)	{
				$cur_col=0;
				echo"<tr>";
			}

			$c = getCostPos($nxt[0], $this->cost_id);
			if($c==0)	continue;
			if(($this->view_proizv)&&($nxt[3])) $pr=" (".$nxt[3].")"; else $pr="";
			echo "<td>".html_out($nxt[1].$pr)."</td><td>".$c."</td>";

 			$this->line++;
 			$i=1-$i;
 			$cur_col++;
		}
		echo"</tr>";
	}
}
?>