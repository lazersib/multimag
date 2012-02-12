<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2010, BlackLight, TND Team, http://tndproject.org
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
require_once($CONFIG['location']."/common/excel_reader2.php");

class XLSPriceLoader extends PriceLoader
{
	// Входные данные
	private $xlsdata;
	private $signature_cache;
	
	function __construct($filename)
	{
		$this->xlsdata = new Spreadsheet_Excel_Reader($filename,false);
		$signature_cache='';
	}
	
	public function findSignature($signature)
	{
		if(!$this->signature_cache)
		{
			foreach($this->xlsdata->boundsheets as $sheet => $sheet_data)
			{
				for($row=1;$row<=$this->xlsdata->rowcount($sheet);$row++)
				{
					for($col=1;$col<=$this->xlsdata->colcount($sheet);$col++)
					{
						//if(stripos($this->xlsdata->val($row,$col,$sheet),$signature)!==false)	return true;
						$this->signature_cache.=$this->xlsdata->val($row,$col,$sheet);
					}
				}
			}
		}
		if(stripos($this->signature_cache,$signature)!==false)	return true;
		return false;	
	}
	
	protected function parse()
	{
		foreach($this->xlsdata->boundsheets as $sheet => $sheet_data)
		{
			$this->tableBegin($sheet_data['name']);
			for($row=1;$row<=$this->xlsdata->rowcount($sheet);$row++)
			{
				$this->rowBegin();
				for($col=1;$col<=$this->xlsdata->colcount($sheet);$col++)
				{
					$this->line[$col]=$this->xlsdata->val($row,$col,$sheet);
				}
				$this->rowEnd();
			}
			$this->tableEnd();
		}
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
			if($this->build_html_data)
				$this->html.="</tbody></table><br>";
		}
	}
}

?>