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

include_once("core.php");
include_once("include/doc.core.php");
need_auth();
SafeLoadTemplate($CONFIG['site']['inner_skin']);


/// Генератор отчётов в html формате
class ReportEngineHTML {
	private $buffer_body='';
	var $styles_base;	// Стили стандартных элементов
	var $styles_ext;	// Стили дополнительных элементов
	var $rowstyle;
	var $table_widths;

	function __construct()	{
		ob_start();
		$this->styles_base = array();
		$this->styles_ext = array();
		$this->rowstyle = '';
	}

	function header($text, $type=1)	{
		settype($type, 'int');
		if($type<1)	$type = 1;
		if($type>6)	$type = 6;
		$text = htmlentities($text, ENT_QUOTES, 'UTF-8', false );
		$this->buffer_body.="<h{$type}>$text</h$type>";
	}

	function tableBegin($widths) {
		if(!is_array($widths))	$widths = array();
		$this->table_widths = $widths;
		$this->buffer_body .= "<table>";
	}

	function tableHeader($cells) {
		$this->buffer_body .= "<tr>";
		foreach($cells as $id => $value) {
			$width = isset($this->table_widths[$id])?" width='{$this->table_widths[$id]}%'":'';
			$value = htmlentities($value, ENT_QUOTES, 'UTF-8', false );
			$this->buffer_body .= "<th{$width}>$value</th>";
		}
		$this->buffer_body .= "</tr>";
	}

	function tableAltStyle($use = true) {
		if($use)	$this->rowstyle = " class='alt'";
		else		$this->rowstyle = '';
	}

	function tableRow($cells) {
		$this->buffer_body .= "<tr{$this->rowstyle}>";
		foreach($cells as $id => $value) {
			$value = htmlentities($value, ENT_QUOTES, 'UTF-8', false );
			$this->buffer_body .= "<td>$value</td>";
		}
		$this->buffer_body .= "</tr>";
	}

	function tableSpannedRow($span_info, $cells) {
		if(!is_array($span_info))	$span_info = array();
		$this->buffer_body.="<tr{$this->rowstyle}>";
		foreach($cells as $id => $value) {
			$value = htmlentities($value, ENT_QUOTES, 'UTF-8', false );
			$span = @$span_info[$id]>1?" colspan='{$span_info[$id]}'":'';
			$this->buffer_body .= "<td{$span}>$value</td>";
		}
		$this->buffer_body .= "</tr>";
	}

	function tableEnd() {
		$this->buffer_body .= "</table>";
	}

	function output($fname) {
		$html = "<!DOCTYPE html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"><style type=\"text/css\">body{font:14px sans-serif;}h1, h2, h3, h4 {text-align: center;}table{width: 100%; border-collapse: collapse; border: 1px solid #000;}th{ font-size: 16px; text-align: center; font-weight: bold; border: 2px solid #000; color: #fff; background-color: #000; padding: 2px;}td{ text-align: left; font-weight: normal; border: 1px solid #000; padding: 1px 3px 1px 3px;}tr.alt{background-color:#ccc;}</style></head><body>".$this->buffer_body.'</body></html>';
		echo $html;
	}
}

/// Генератор отчётов в PDF формате
class ReportEnginePDF {
	var $pdf;
	var $styles;	// Стили
	var $rowstyle;

	function __construct() {
		global $CONFIG;
		ob_start();
		define('FPDF_FONT_PATH',$CONFIG['site']['location'].'/fpdf/font/');
		require('fpdf/fpdf_mc.php');
		$this->pdf = new PDF_MC_Table('P');
		$this->pdf->Open();
		$this->pdf->SetAutoPageBreak(1,5);
		$this->pdf->AddFont('Arial','','arial.php');
		$this->pdf->tMargin=10;
		$this->pdf->AddPage('P');

		$this->styles = array();

		$this->styles['table-head']	= array('line-width'=>0.4, 'font-size'=>10);
		$this->styles['table-row']	= array('line-width'=>0.2, 'font-size'=>7, 'background' => 255);
		$this->styles['table-altrow']	= array('line-width'=>0.2, 'font-size'=>7, 'background' => 200);

		$this->rowstyle = 'table-row';
	}

	function header($text, $type=1) {
		settype($type, 'int');
		if($type<1)	$type = 1;
		if($type>6)	$type = 6;
		$font_size = 18-$type*2;
		$this->pdf->SetFont('Arial','',$font_size);
		$text = iconv('UTF-8', 'windows-1251', $text);
		$this->pdf->MultiCell(0, $this->getCellHeight($font_size), $text, 0, 'C', 0);
	}

	function tableBegin($widths) {
		if(!is_array($widths))	$widths = array();
		foreach($widths as $id => $w)	$widths[$id] = $w*1.95;
		$this->pdf->SetWidths($widths);
		$this->useStyle($this->rowstyle);
	}

	function tableHeader($cells) {
		$this->useStyle('table-head');
		$this->pdf->RowIconv($cells);
	}

	function tableAltStyle($use=true) {
		if($use)	$this->rowstyle='table-altrow';
		else		$this->rowstyle='table-row';
	}

	function tableRow($cells) {
		$this->useStyle($this->rowstyle);
		$this->pdf->RowIconv($cells);
	}

	function tableSpannedRow($span_info, $cells) {
		if(!is_array($span_info))	$span_info = array();
		$old_widths = $this->pdf->widths;
		$cur = 0;
		$this->pdf->widths = array();
		foreach($span_info as $value) {
			$w=0;
			for($i=0;$i<$value;$i++)	$w += $old_widths[$i+$cur];
			$this->pdf->widths[] = $w;
			$cur += $value;
		}
		$this->tableRow($cells);
		$this->pdf->widths = $old_widths;
	}

	function tableEnd() {
		$this->pdf->Ln(5);
	}

	function output($fname) {
		$this->pdf->Output($fname.'.pdf', 'I');
	}

	// ********** Приватные функции
	private function useStyle($style) {
		foreach($this->styles[$style] as $name => $value) {
			switch($name) {
				case 'line-width':	$this->pdf->SetLineWidth($value);			break;
				case 'font-size':	$this->pdf->SetFont('','',$value);
							$this->pdf->SetHeight($this->getCellHeight($value));	break;
				case 'background':	$this->pdf->SetFillColor($value);			break;
			}
		}
	}

	private function getCellHeight($font_size) {
		return $font_size/3+1;
	}
}

// Все стили доложны быть заданы ДО вызова header!!!

/// Родительский класс для всех отчётов системы
class BaseReport {
	protected $output_format='html';
	protected $oe=null;			// output engine

	function __construct()	{}

	/// Выбрать движок вывода
	function loadEngine($engine='html') {
		switch($engine)	{
			case 'pdf':	$this->output_format='pdf';
					$this->oe=new ReportEnginePDF();
					break;
			default:	$this->output_format='html';
					$this->oe=new ReportEngineHTML();
		}
	}
	
	/// Запустить отчёт
	function Run($opt) {
		if ($opt == '')	$this->Form();
		else		$this->Make($opt);
	}

	function header($text, $type=1)	{ return $this->oe->header($text, $type); }
	function tableBegin($widths)	{ return $this->oe->tableBegin($widths); }
	function tableHeader($cells)	{ return $this->oe->tableHeader($cells); }
	function tableAltStyle($use=true){ return $this->oe->tableAltStyle($use); }
	function tableRow($cells)	{ return $this->oe->tableRow($cells); }
	function tableSpannedRow($si,$cells){ return $this->oe->tableSpannedRow($si,$cells); }
	function tableEnd()		{ return $this->oe->tableEnd(); }
	function output($fname='report'){ return $this->oe->output($fname); }
}

/// Отчёт с блоком выбора складских групп
class BaseGSReport extends BaseReport {
	function draw_groups_tree($level) {
		global $db;
		$ret = '';
		settype($level,'int');
		$res = $db->query("SELECT `id`, `name`, `desc` FROM `doc_group` WHERE `pid`='$level' ORDER BY `name`");
		$i = 0;
		$r = '';
		if($level == 0) $r = 'IsRoot';
		$cnt = $res->num_rows;
		while($nxt = $res->fetch_row()) {
			if($nxt[0] == 0) continue;
			$item="<label><input type='checkbox' name='g[]' value='$nxt[0]' id='cb$nxt[0]' class='cb' checked onclick='CheckCheck($nxt[0])'>$nxt[1]</label>";
			if($i>=($cnt-1)) $r.=" IsLast";
			$tmp=$this->draw_groups_tree($nxt[0]); // рекурсия
			if($tmp)	$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div><ul class='Container' id='cont$nxt[0]'>".$tmp.'</ul></li>';
			else		$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
			$i++;
		}
		return $ret;
	}


	function GroupSelBlock() {
		global $tmpl;
		$tmpl->addStyle(".scroll_block
		{
			max-height:		250px;
			overflow:		auto;
		}

		div#sb
		{
			display:		none;
			border:			1px solid #888;
		}

		.selmenu
		{
			background-color:	#888;
			width:			auto;
			font-weight:		bold;
			padding-left:		20px;
		}

		.selmenu a
		{
			color:			#fff;
			cursor:			pointer;
		}

		.cb
		{
			width:			14px;
			height:			14px;
			border:			1px solid #ccc;
		}

		");
		$tmpl->addContent("<script type='text/javascript'>
		function gstoggle()
		{
			var gs=document.getElementById('cgs').checked;
			if(gs==true)
				document.getElementById('sb').style.display='block';
			else	document.getElementById('sb').style.display='none';
		}

		function SelAll(flag)
		{
			var elems = document.getElementsByName('g[]');
			var l = elems.length;
			for(var i=0; i<l; i++)
			{
				elems[i].checked=flag;
				if(flag)	elems[i].disabled = false;
			}
		}

		function CheckCheck(ids)
		{
			var cb = document.getElementById('cb'+ids);
			var cont=document.getElementById('cont'+ids);
			if(!cont)	return;
			var elems=cont.getElementsByTagName('input');
			var l = elems.length;
			for(var i=0; i<l; i++)
			{
				if(!cb.checked)		elems[i].checked=false;
				elems[i].disabled =! cb.checked;
			}
		}

		</script>
		<label><input type=checkbox name='gs' id='cgs' value='1' onclick='gstoggle()'>Выбрать группы</label><br>
		<div class='scroll_block' id='sb'>
		<ul class='Container'>
		<div class='selmenu'><a onclick='SelAll(true)'>Выбрать всё<a> | <a onclick='SelAll(false)'>Снять всё</a></div>
		".$this->draw_groups_tree(0)."</ul></div>");
	}
}






$tmpl->hideBlock('left');
$mode = request('mode');

$dir = $CONFIG['site']['location'].'/include/reports/';

try {
	if ($mode == '') {
		doc_menu();
		$tmpl->setTitle("Отчёты");
		$tmpl->addContent("<h1>Отчёты</h1>
		<p>Внимание! Отчёты создают высокую нагрузку на сервер, поэтому не рекомендуеся генерировать отчёты во время интенсивной работы с базой данных, а так же не рекомендуется частое использование генератора отчётов по этой же причине!</p>");
		$tmpl->addContent("<ul>");
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				$reports = array();
				while (($file = readdir($dh)) !== false) {
					if (preg_match('/.php$/', $file)) {
						$cn = explode('.', $file);
						if (isAccess('report_' . $cn[0], 'view')) {
							include_once("$dir/$file");
							$class_name = 'Report_' . $cn[0];
							$class = new $class_name;
							$nm = $class->getName();
							//$tmpl->AddText("<li><a href='/doc_reports.php?mode=$cn[0]'>$nm</a></li>");
							$reports[$cn[0]] = $nm;
						}
					}
				}
				closedir($dh);
				asort($reports);
				foreach ($reports AS $id => $name)
					$tmpl->addContent("<li><a href='/doc_reports.php?mode=$id'>$name</a></li>");
			}
		}
		$tmpl->addContent("</ul>");
	} else if ($mode == 'pmenu') {
		$tmpl->ajax = 1;
		$tmpl->setContent("");
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				$reports = array();
				while (($file = readdir($dh)) !== false) {
					if (preg_match('/.php$/', $file)) {
						$cn = explode('.', $file);
						if (isAccess('report_' . $cn[0], 'view')) {
							include_once("$dir/$file");
							$class_name = 'Report_' . $cn[0];
							$class = new $class_name;
							$nm = $class->getName(1);
							$reports[$cn[0]] = $nm;
						}
					}
				}
				closedir($dh);
				asort($reports);
				foreach ($reports AS $id => $name)
					$tmpl->addContent("<div onclick='window.location=\"/doc_reports.php?mode=$id\"'>$name</div>");
			}
		}
		$tmpl->addContent("<hr><div onclick='window.location=\"/doc_reports.php\"'>Подробнее</div>");
	} else {
		doc_menu();
		if (!isAccess('report_' . $mode, 'view'))
			throw new AccessException("Недостаточно привилегий");
		$tmpl->setTitle("Отчёты");
		$opt = request('opt');
		$fn = $dir . $mode . '.php';
		if (file_exists($fn)) {
			include_once($fn);
			$class_name = 'Report_' . $mode;
			$class = new $class_name;
			$tmpl->setTitle($class->getName());
			$class->Run($opt);
		}
		else	$tmpl->msg("Сценарий $fn не найден!", "err");
	}
}
catch (AccessException $e) {
	$tmpl->setContent('');
	$tmpl->msg($e->getMessage(), 'err', "Нет доступа");
}
catch(mysqli_sql_exception $e) {
	$tmpl->ajax=0;
	$id = writeLogException($e);
	$tmpl->msg("Порядковый номер ошибки: $id<br>Сообщение передано администратору", 'err', "Ошибка в базе данных");
}
catch (Exception $e) {
    $tmpl->setContent('');
    $id = writeLogException($e);
    $tmpl->msg($e->getMessage(), 'err', "Общая ошибка");
}

$tmpl->write();
