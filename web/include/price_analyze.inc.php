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

/// Анализирует строку, содержащую content.xml из ODS файла
/// Позволяет загрузить данные в базу, либо сформировать HTML - таблицу с данными из файла
class ODFContentLoader
{
	// Входные данные
	private $xml;
	private $firm_id=0;
	
	// Настройки
	private $silent=0;		// Не выводиьть сообщений
	private $build_html_data=0;	// Сформировать HTML таблицу. Значение - кол-во колонок таблицы
	private $insert_to_database=0;	// Сохранить результат в базу данных

	// Рабочие переменные
	private $line_cnt=0;		// Счётчик обработанных строк прайса
	private $table_parsing=0;	// Флаг процесса обработки таблицы
	private $rowspan;		// Данные об объединённых вертикальных ячейках
	private $line;			// Массив ячеек текущей строки
	private $line_pos;		// ID текущей ячейки
	private $inc_lines;		// Смещение после текущей ячейки (её ширина в столбцах)
	private $firm_cols=array();	// Номера требуемых колонок прайса
	private $def_currency;		// Валюта по умолчанию
	private $currencies=array();	// Массив валют
	
	// Выходные данные
	private $html='';		// HTML - представление таблиц (опция build_html_data)
	
	
	function __construct($xml_string)	{$this->xml=$xml_string;}
	/// Включить/выключить создание HTML таблицы
	public function setBuildHTMLData($lines=20)	{$this->build_html_data=$lines;}
	/// Включить/выключить сохранение данных в базу. Требует определения соответствия прайса организации
	public function setInsertToDatabase($flag=1)	{$this->insert_to_database=$flag;}
	/// Получить HTML-представление
	public function getHTML()			{return $this->html;}
	
	/// Определение принадлежности прайс-листа по сигнатуре
	public function detectFirm()
	{
		$res=mysql_query("SELECT `id`, `name`, `signature`, `currency` FROM `firm_info`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить данные фирмы");
		while($nxt=mysql_fetch_row($res))
		{
			if(stripos($this->xml,$nxt[2]))
			{
				$this->currency=$nxt[3];
				return	$this->firm_id=$nxt[0];
			}
		}
		return false;
	}
	
	/// Запуск анализа
	public function Run()
	{
		$this->line_cnt=0;
		if(($this->firm_id==0) && $insert_to_database)		throw new Exception("Принадлежность прайс-листа к фирме не задана");	
		$this->table_parsing=0;
		$this->rowspan=array();
		$this->html='';
		$this->inc_lines=1;

		if($this->insert_to_database)
		{
			mysql_query("DELETE FROM `price` WHERE `firm` = '{$this->firm_id}'");
			if(mysql_errno())	throw new MysqlException("Не удалось удалить старый прайс фирмы {$this->firm_id}");
			mysql_query("UPDATE `firm_info` SET `last_update`=NOW() WHERE `id`='{$this->firm_id}'");
			if(mysql_errno())	throw new MysqlException("Не удалось установить дату обновления прайса фирмы {$this->firm_id}");
			$res=mysql_query("SELECT `id`, `name` FROM `currency`");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список валют");
			$this->currencies=array();
			while($nxt=mysql_fetch_row($res))
				$this->currencies[$nxt[1]]=$nxt[0];
				
			
		}
		
		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, array($this, 'tableStart'), array($this, 'tableEnd'));
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
			if($this->insert_to_database)
			{
				$sql_table_name=mysql_real_escape_string($attrs['TABLE:NAME']);
				$res=mysql_query("SELECT `art`, `name`, `cost`, `nal`, `currency` FROM `firm_info_struct` WHERE `firm_id`='{$this->firm_id}' AND `table_name` LIKE '$sql_table_name'");
				if(mysql_errno())	throw new MysqlException("Ошибка получения данных фирмы для листа *$sql_table_name*");
				if(!mysql_num_rows($res))
				{
					$res=mysql_query("SELECT `art`, `name`, `cost`, `nal`, `currency` FROM `firm_info_struct` WHERE `firm_id`='{$this->firm_id}' AND `table_name` = ''");
					if(mysql_errno())	throw new MysqlException("Ошибка получения данных фирмы для листа по умолчанию");
				}
				if(!mysql_num_rows($res))	
					//настройки для листа не найдены
					$this->table_parsing=0;
				else
				{
					$this->firm_cols=mysql_fetch_assoc($res);
					$this->table_parsing=1;
				}
			}
			if($this->build_html_data)
			{
				$this->html.="<table class='list'><caption>{$attrs['TABLE:NAME']}</caption><thead><tr>";
				for($i=1;$i<=$this->build_html_data;$i++)
				{
					$this->html.="<th>$i</th>";
				}
				$this->html.="</tr></thead><tbody>";
			}
			
		}
		else if($name=="TABLE:TABLE-ROW")
		{
			$this->line = array();
			$this->line_pos = 1;
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

	private function tableEnd($parser, $name)
	{
		if($this->table_parsing==0)	return;

		if($name=="TABLE:TABLE-ROW")
		{
			if($this->insert_to_database && $this->table_parsing && isset($this->line[$this->firm_cols['cost']]))
			{
				$cost=$this->line[$this->firm_cols['cost']];
				$cost=preg_replace("/[^,.\d]+/","",$cost);
				$cost=str_replace(",",".",$cost);
				settype($cost,"double");
				
				if(@$this->line[$this->firm_cols['name']] && (@$this->line[$this->firm_cols['nal']] || @$this->line[$this->firm_cols['cost']]) )
				{
					$this->line_cnt++;
					$name=mysql_real_escape_string(@$this->line[$this->firm_cols['name']]);
					$art=mysql_real_escape_string(@$this->line[$this->firm_cols['art']]);
					$nal=mysql_real_escape_string(@$this->line[$this->firm_cols['nal']]);
					$curr=trim(@$this->line[$this->firm_cols['currency']]);
					if(isset($this->currencies[$curr]))	$curr=$this->currencies[$curr];
					else					$curr=$this->def_currency;
					mysql_query("INSERT INTO `price`
					(`name`,`cost`,`firm`,`art`,`date`, `nal`, `currency`) VALUES 
					('$name', '$cost', '{$this->firm_id}', '$art', NOW(), '$nal', '$curr')");
					if(mysql_errno())	throw new MysqlException("Не удалось вставить строку прайса в базу!");
				}
			}
			if($this->build_html_data)
			{
				$this->html.="<tr>";
				for($i=1;$i<=$this->build_html_data;$i++)
				{
					@$this->html.="<td>{$this->line[$i]}</td>";
				}
				$this->html.="</tr>";
			}
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

// Старый код

function rowspanStrafe()
{
	global $line_pos, $rowspan;
	while(@($rowspan[$line_pos]>0))
	{
		$rowspan[$line_pos]--;
		$line_pos++;
	}
}

function tableStart($parser, $name, $attrs)
{
	global $line;
	global $line_pos;
	
	global $num_name;
	global $num_cost;
	global $num_art;
	global $num_nal;
	global $firm_id;
	global $table_parsing;
	
	global $rowspan;
	global $inc_lines;
	
	if($name=="TABLE:TABLE")
	{
		$sql_table_name=mysql_real_escape_string($attrs['TABLE:NAME']);
		$res=mysql_query("SELECT `art`, `name`, `cost`, `nal` FROM `firm_info_struct`
		WHERE `firm_id`='$firm_id' AND `table_name` LIKE '$sql_table_name'");
		if(!mysql_num_rows($res))
		{
			$res=mysql_query("SELECT `art`, `name`, `cost`, `nal` FROM `firm_info_struct`
			WHERE `firm_id`='$firm_id' AND `table_name` = ''");
		}
		if(!mysql_num_rows($res))	
		{
			//echo"List {$attrs['TABLE:NAME']} NOT be parsed!\n";
			$table_parsing=0;
		}
		else
		{
			$nxt=mysql_fetch_row($res);
			//echo"List {$attrs['TABLE:NAME']} be parsed!\n";
			$table_parsing=1;
			$num_art=$nxt[0];
			$num_name=$nxt[1];
			$num_cost=$nxt[2];
			$num_nal=$nxt[3];
		}
		echo"<table class='list'>";
		echo "<tr>";
		for($i=0;$i<20;$i++)
		{
			echo@ "<td>$i</td>";
		}
		echo"</tr>";
	}
	else if($name=="TABLE:TABLE-ROW")
	{
		$line = array();
		$line_pos = 1;
	}
	else if($name=="TABLE:TABLE-CELL")
	{
		//var_dump($attrs);
		rowspanStrafe();
		if(isset($attrs["TABLE:NUMBER-ROWS-SPANNED"]))
		{
			if($attrs["TABLE:NUMBER-ROWS-SPANNED"]>1)
			{
				$rowspan[$line_pos]=$attrs["TABLE:NUMBER-ROWS-SPANNED"]-1;
			}
		}

		if(@$attrs["TABLE:NUMBER-COLUMNS-SPANNED"]>1)		$inc_lines=$attrs["TABLE:NUMBER-COLUMNS-SPANNED"];
		else if(@$attrs['TABLE:NUMBER-COLUMNS-REPEATED']>1)	$inc_lines=$attrs['TABLE:NUMBER-COLUMNS-REPEATED'];
		else 							$inc_lines=1;
		$line[$line_pos]="";
	}
}

function tableData($parser, $data)
{
	global $line;
	global $line_pos;
	$line[$line_pos].=$data;
}

function tableEnd($parser, $name)
{
	global $line;
	global $line_pos;
	global $line_cnt;

	global $num_name;
	global $num_cost;
	global $num_art;
	global $num_nal;
	global $firm_id;
	global $line_pos;
	global $inc_lines;
	
	global $table_parsing;
	
	if($table_parsing==0)	return;

	if($name=="TABLE:TABLE-ROW")
	{
		echo "<tr>";
		for($i=0;$i<20;$i++)
		{
			echo@ "<td>{$line[$i]}</td>";
		}
		echo"</tr>";
		
		
		$cost=@$line[$num_cost];
		$cost=preg_replace("/[^,.\d]+/","",$cost);
		$cost=str_replace(",",".",$cost);
		settype($cost,"double");

		//echo "{$line[$num_name]}-{$line[$num_art]}: $cost<br>";
		//var_dump($line);
		//echo"\n";
		//echo"<br>";
		$f_det=0;
		
		
		if(@$line[$num_name] && (@$line[$num_nal] || @$line[$num_cost]) )
		{
			$line_cnt++;

			mysql_query("INSERT INTO `price`
			(`name`,`cost`,`firm`,`art`,`date`, `nal`) VALUES 
			('{$line[$num_name]}', '$cost', '$firm_id', '{$line[$num_art]}', NOW(), '$line[$num_nal]' )");
			//echo"add\n";
		}
	}
	else if($name=="TABLE:TABLE-CELL")
	{
		$line_pos+=$inc_lines;
	}
}



function parse($xml, $silent=0)
{
	global $tmpl;
	global $firm_id;
	global $line_cnt;
	global $table_parsing;
	global $rowspan;
	$table_parsing=0;
	$ok=true;
	$rowspan=array();

	mysql_query("DELETE FROM `price` WHERE `firm` = '$firm_id'");
	mysql_query("UPDATE `firm_info` SET `last_update`=NOW() WHERE `id`='$firm_id'");

	$xml_parser = xml_parser_create();

	xml_set_element_handler($xml_parser, "tableStart", "tableEnd");
	xml_set_character_data_handler($xml_parser,"tableData");

	if (!xml_parse($xml_parser, $xml))
	{
		if(!$silent)
		{
			$tmpl->msg(sprintf("XML error: %s at line %d",
				xml_error_string(xml_get_error_code($xml_parser)),
				xml_get_current_line_number($xml_parser)),'err');
		}
		$ok=false;
	}
	echo"</table>";
	xml_parser_free($xml_parser);
	if(!$silent)
	{
		$tmpl->msg("Успешно обработано $line_cnt позиций!","ok");
	}
	return $ok;
}


function detect_firm($xml, $silent=0)
{
	global $num_name;
	global $num_cost;
	global $num_art;
	global $num_nal;
	global $firm_id;
	global $tmpl;

	$res=mysql_query("SELECT `id`, `name`, `signature` FROM `firm_info`");
	while($nxt=mysql_fetch_row($res))
	{
		if(stripos($xml,$nxt[2]))
		{
			$firm_id=$nxt[0];
// 			$num_name=$nxt[2];
// 			$num_cost=$nxt[3];
// 			$num_art=$nxt[4];
// 			$num_nal=$nxt[6];
			if(!$silent) $tmpl->AddText("<b>Определена фирма:</b>$nxt[1]<br>");
			return true;
		}
	}
	if(!$silent)
	{
		$tmpl->msg("<b>Фирма НЕ определена!</b>",'err');
		firmAddForm();
	}
    //$tmpl->AddText($xml);
	return false;
}

?>