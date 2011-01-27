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
	}
	else if($name=="TABLE:TABLE-ROW")
	{
		$line = array();
		$line_pos = -0;
	}
	else if($name=="TABLE:TABLE-CELL")
	{
		//var_dump($attrs);
		
		if($attrs["TABLE:NUMBER-COLUMNS-SPANNED"]>1)
		{
			$line_pos+=$attrs["TABLE:NUMBER-COLUMNS-SPANNED"];
		}
		else if($attrs['TABLE:NUMBER-COLUMNS-REPEATED']>1)
		$line_pos+=$attrs['TABLE:NUMBER-COLUMNS-REPEATED'];
		else $line_pos++;
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
	
	global $table_parsing;

	if($table_parsing==0)	return;

	if($name=="TABLE:TABLE-ROW")
	{
		$cost=$line[$num_cost];
		$cost=preg_replace("/[^,.\d]+/","",$cost);
		$cost=str_replace(",",".",$cost);
		settype($cost,"double");

		//echo $line[$num_name].": $cost\n";
		//var_dump($line);
		//echo"\n";

		if($line[$num_name] && ($line[$num_nal] || $line[$num_cost]) )
		{
			$line_cnt++;

			mysql_query("INSERT INTO `price`
			(`name`,`cost`,`firm`,`art`,`date`, `nal`) VALUES 
			('{$line[$num_name]}', '$cost', '$firm_id', '{$line[$num_art]}', NOW(), '$line[$num_nal]' )");
			//echo"add\n";
		}
	}
}



function parse($xml, $silent=0)
{
	global $tmpl;
	global $firm_id;
	global $line_cnt;
	global $table_parsing;
	$table_parsing=0;
	$ok=true;

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