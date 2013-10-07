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


/// Класс формирует прайс-лист в формате XLS
/// TODO: сделать подсветку устаревших цен серым
class PriceWriterXLS extends BasePriceWriter
{
	var $workbook;		// книга XLS
	var $worksheet;		// Лист XLS
	var $line;		// Текущая строка
	var $format_line;	// формат для строк наименований прайса
	var $format_group;	// формат для строк групп прайса
	
	/// Конструктор
	function __construct($db)	{
		parent::__construct($db);
		$this->line		= 0;
	}
	
	/// Сформировать шапку прайса
	function open()	{
		require_once('include/Spreadsheet/Excel/Writer.php');
		global $CONFIG;
		$this->workbook	= new Spreadsheet_Excel_Writer();
		// sending HTTP headers
		$this->workbook->send('price.xls');

		// Creating a worksheet
		$this->worksheet =& $this->workbook->addWorksheet($CONFIG['site']['name']);

		$this->format_footer=& $this->workbook->addFormat();
		$this->format_footer->SetAlign('center');
		$this->format_footer->setColor(39);
		$this->format_footer->setFgColor(27);
		$this->format_footer->SetSize(8);

		$this->format_line=array();
		$this->format_line[0]=& $this->workbook->addFormat();
		$this->format_line[0]->setColor(0);
		$this->format_line[0]->setFgColor(26);
		$this->format_line[0]->SetSize(12);
		$this->format_line[1]=& $this->workbook->addFormat();
		$this->format_line[1]->setColor(0);
		$this->format_line[1]->setFgColor(41);
		$this->format_line[1]->SetSize(12);

		$this->format_group=array();
		$this->format_group[0]=& $this->workbook->addFormat();
		$this->format_group[0]->setColor(0);
		$this->format_group[0]->setFgColor(53);
		$this->format_group[0]->SetSize(14);
		$this->format_group[0]->SetAlign('center');
		$this->format_group[1]=& $this->workbook->addFormat();
		$this->format_group[1]->setColor(0);
		$this->format_group[1]->setFgColor(52);
		$this->format_group[1]->SetSize(14);
		$this->format_group[1]->SetAlign('center');
		$this->format_group[2]=& $this->workbook->addFormat();
		$this->format_group[2]->setColor(0);
		$this->format_group[2]->setFgColor(51);
		$this->format_group[2]->SetSize(14);
		$this->format_group[2]->SetAlign('center');
		
		
		$format_title =& $this->workbook->addFormat();
		$format_title->setBold();
		$format_title->setColor('blue');
		$format_title->setPattern(1);
		$format_title->setFgColor('yellow');
		$format_title->SetSize(26);

		$format_info =& $this->workbook->addFormat();
		//$format_info->setBold();
		$format_info->setColor('blue');
		$format_info->setPattern(1);
		$format_info->setFgColor('yellow');
		$format_info->SetSize(16);

		$format_header =& $this->workbook->addFormat();
		$format_header->setBold();
		$format_header->setColor(1);
		$format_header->setPattern(1);
		$format_header->setFgColor(63);
		$format_header->SetSize(16);
		$format_header->SetAlign('center');
		$format_header->SetAlign('vcenter');
		// Настройка ширины столбцов
		$column_width=array(8,120,15,15);
		foreach($column_width as $id=> $width)
			$this->worksheet->setColumn($id,$id,$width);
		$this->column_count=count($column_width);

		if(is_array($CONFIG['site']['price_text']))
		foreach($CONFIG['site']['price_text'] as $text)	{
			$str = iconv('UTF-8', 'windows-1251', $text);
			$this->worksheet->setRow($this->line,30);
			$this->worksheet->write($this->line, 0, $str, $format_title);
			$this->worksheet->setMerge($this->line,0,$this->line,$this->column_count-1);
			$this->line++;
		}

		$str = 'Прайс загружен с сайта http://'.$CONFIG['site']['name'];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$this->worksheet->write($this->line, 0, $str, $format_info);
		$this->worksheet->setMerge($this->line, 0, $this->line, $this->column_count-1);
		$this->line++;

//		$str = 'При заказе через сайт предоставляется скидка!';
//		$str = iconv('UTF-8', 'windows-1251', $str);
//		$this->worksheet->write($this->line, 0, $str, $format_info);
//		$this->worksheet->setMerge($this->line, 0, $this->line, $this->column_count-1);
//		$this->line++;

		$dt=date("d.m.Y");
		$str = 'Цены действительны на дату: '.$dt.'. Цены, выделенные серым цветом, необходимо уточнять.';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$this->worksheet->write($this->line, 0, $str, $format_info);
		$this->worksheet->setMerge($this->line, 0, $this->line, $this->column_count-1);
		$this->line++;

		if(is_array($this->view_groups)){
			$this->line++;
			//$this->Ln(3);
			//$this->SetFont('','',14);
			//$this->SetTextColor(255,24,24);
			$str = 'Прайс содержит неполный список позиций, в соответствии с выбранными критериями при его загрузке с сайта.';
			$str = iconv('UTF-8', 'windows-1251', $str);
			$this->worksheet->write($this->line, 0, $str, $format_info);
			$this->worksheet->setMerge($this->line, 0, $this->line, $this->column_count-1);
			$this->line++;
		}

		$this->line++;
		$this->worksheet->write(8, 8, ' ');
		$headers=array("Арт.", "Наименование", "Наличие", "Цена");
		foreach($headers as $id => $item)
			$headers[$id] = iconv('UTF-8', 'windows-1251', $item);
		$this->worksheet->writeRow($this->line,0,$headers,$format_header);
		$this->line++;
	}

	/// Сформирвать тело прайса
	function write($group=0, $level=0)	{
		if($level>2)	$level=2;
		$res=$this->db->query("SELECT `id`, `name`, `printname` FROM `doc_group` WHERE `pid`='$group' AND `hidelevel`='0' ORDER BY `id`");
		if(!$res)	throw new MysqlException("Не удалось получить список групп наименований", $this->db);
		while($nxt=$res->fetch_row())	{
			if($nxt[0]==0) continue;
			if(is_array($this->view_groups))
				if(!in_array($nxt[0],$this->view_groups))	continue;

			$str = iconv('UTF-8', 'windows-1251', $nxt[1]);
			$this->worksheet->write($this->line, 0, $str, $this->format_group[$level]);
			$this->worksheet->setMerge($this->line,0,$this->line,$this->column_count-1);
			$this->line++;

			$this->writepos($nxt[0], $nxt[2]?$nxt[2]:$nxt[1] );
			$this->write($nxt[0], $level+1); // рекурсия

		}
	}
	
	/// Сформировать завершающий блок прайса
	function close()	{
		global $CONFIG;
		$this->line+=5;
		$this->worksheet->write($this->line, 0, "Generated from MultiMag (http://multimag.tndproject.org) via PHPExcelWriter, for http://".$CONFIG['site']['name'], $this->format_footer);
		$this->worksheet->setMerge($this->line, 0, $this->line, $this->column_count-1);
		$this->line++;
		$str = iconv('UTF-8', 'windows-1251', "Прайс создан системой MultiMag (http://multimag.tndproject.org), специально для http://".$CONFIG['site']['name']);
		$this->worksheet->write($this->line, 0, $str, $this->format_footer);
		$this->worksheet->setMerge($this->line, 0, $this->line, $this->column_count-1);
		$this->workbook->close();
	}

	/// Сформировать строку прайса
	function writepos($group=0, $group_name='')
	{
		$res=$this->db->query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost_date` , `doc_base`.`proizv`
		FROM `doc_base`
		LEFT JOIN `doc_group` ON `doc_base`.`group`=`doc_group`.`id`
		WHERE `doc_base`.`group`='$group' AND `doc_base`.`hidden`='0' ORDER BY `doc_base`.`name`");
		if(!$res)	throw new MysqlException("Не удалось получить список наименований", $this->db);
		$i=0;
		while($nxt=$res->fetch_row())
		{
			$this->worksheet->write($this->line, 0, $nxt[0], $this->format_line[$i]);	// артикул
			$name=iconv('UTF-8', 'windows-1251', "$group_name $nxt[1]".(($this->view_proizv&&$nxt[3])?" ($nxt[3])":''));
			$this->worksheet->write($this->line, 1, $name, $this->format_line[$i]);		// наименование
			$this->worksheet->write($this->line, 2, '', $this->format_line[$i]);		// наличие - пока не отображается
			$cost = getCostPos($nxt[0], $this->cost_id);
			if($cost==0)	continue;
			$str=iconv('UTF-8', 'windows-1251',$cost);
			$this->worksheet->write($this->line, 3, $str, $this->format_line[$i]);		// цена
///TODO: НАДО СДЕЛАТЬ ПОДСВЕТКУ ЦЕН
// 			$dcc=strtotime($data['cost_date']);
// 			if( ($dcc<(time()-60*60*24*30*6))|| ($str==0) ) $cce=128;
// 			else $cce=0;

 			$this->line++;
 			$i=1-$i;
		}
	}
};

?>