<?php
//	MultiMag v0.1 - Complex sales system
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

require_once($CONFIG['location']."/common/priceloader.php");

class ODSPriceLoader extends PriceLoader
{
	// Входные данные
	private $xml;
	
	// Рабочие переменные
	private $rowspan;		// Данные об объединённых вертикальных ячейках
	private $inc_lines;		// Смещение после текущей ячейки (её ширина в столбцах)
	private $line_pos;		// Текущий столбец
	function __construct($filename)
	{
		$zip = new ZipArchive;
		$zip->open($filename,ZIPARCHIVE::CREATE);
		$this->xml = $zip->getFromName("content.xml");
		$zip->close();
		$this->line_pos = 1;
	}
	
	public function findSignature($signature)
	{
		if(stripos($this->xml,$signature))	return true;
		return false;	
	}
	
	protected function parse()
	{
		$this->rowspan=array();
		$this->inc_lines=1;

		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, array($this, 'tableStart'), array($this, 'tableFinish'));
		xml_set_character_data_handler($xml_parser, array($this, 'tableData'));

		if (!xml_parse($xml_parser, $this->xml))	throw new Exception(sprintf("XML error: %s at line %d",
								xml_error_string(xml_get_error_code($xml_parser)),
								xml_get_current_line_number($xml_parser)));

		xml_parser_free($xml_parser);

		return $this->line_cnt;
	}
	
	private function tableStart($parser, $name, $attrs)
	{
		if($name=="TABLE:TABLE")
		{
			$this->tableBegin($attrs['TABLE:NAME']);
		}
		else if($name=="TABLE:TABLE-ROW")
		{
			$this->line = array();
			$this->line_pos = 1;
			$this->rowBegin();
		}
		else if($name=="TABLE:TABLE-CELL")
		{
			// Смещение для вертикально объединённых ячеек
			while(@($this->rowspan[$this->line_pos]>0))
			{
				$this->rowspan[$this->line_pos]--;
				$this->line_pos++;
			}
			if(isset($attrs["TABLE:NUMBER-ROWS-SPANNED"]))
			{
				if($attrs["TABLE:NUMBER-ROWS-SPANNED"]>1)
				{
					$this->rowspan[$this->line_pos]=$attrs["TABLE:NUMBER-ROWS-SPANNED"]-1;
				}
			}

			if(@$attrs["TABLE:NUMBER-COLUMNS-SPANNED"]>1)		$this->inc_lines=$attrs["TABLE:NUMBER-COLUMNS-SPANNED"];
			else if(@$attrs['TABLE:NUMBER-COLUMNS-REPEATED']>1)	$this->inc_lines=$attrs['TABLE:NUMBER-COLUMNS-REPEATED'];
			else 							$this->inc_lines=1;
			$this->line[$this->line_pos]='';
		}
	}

	private function tableData($parser, $data)
	{
		$this->line[$this->line_pos].=$data;
	}

	private function tableFinish($parser, $name)
	{
		if($this->table_parsing==0)	return;

		if($name=="TABLE:TABLE-ROW")
		{
			$this->rowEnd();
		}
		else if($name=="TABLE:TABLE-CELL")
		{
			$this->line_pos+=$this->inc_lines;
		}
		else if($name=="TABLE:TABLE")
		{
			$this->tableEnd();
		}
	}
}

?>