#!/usr/bin/php
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


$c=explode('/',__FILE__);$base_path='';
for($i=0;$i<(count($c)-2);$i++)	$base_path.=$c[$i].'/';
include_once("$base_path/config_cli.php");
require_once($CONFIG['cli']['location']."/core.cli.inc.php");

$db_name = $db->real_escape_string($CONFIG['mysql']['db']);

$tres = $db->query("SHOW TABLES");
while($table_line = $tres->fetch_row()){
	$table_name = $table_line[0];
	$res = $db->query("SELECT `COLUMN_NAME`, `DATA_TYPE` FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$db_name' AND TABLE_NAME='$table_name' AND (lower(`DATA_TYPE`) = 'varchar' OR lower(`DATA_TYPE`) = 'text' OR `COLUMN_NAME`='id') AND `COLUMN_NAME` != 'ip'");
	$field_str = '';
	$has_id = 0;
	$field_names = array();
	while($col_line = $res->fetch_assoc()){
		if($col_line['COLUMN_NAME'] == 'id')	$has_id = 1;
		else {
			if($field_str)	$field_str.=',';
			$field_str .= '`'.$col_line['COLUMN_NAME'].'`';
			$field_names[] = $col_line['COLUMN_NAME'];
		}
	}
	if( !$field_str)		continue;
	if($table_name == 'ulog')	continue;
	echo "$table_name\n";
	if($table_name == 'articles') {
		$res = $db->query("SELECT `name`, `text` FROM $table_name");
		while($line = $res->fetch_assoc()){
			$v = $db->real_escape_string( html_entity_decode($line['text'], ENT_QUOTES, "UTF-8") );
			$n = $db->real_escape_string( html_entity_decode($line['name'], ENT_QUOTES, "UTF-8") );
			$db->query("UPDATE `$table_name` SET `name`='$n', `text`='$v' WHERE `name`='".$line['name']."'");
		}
	}
	else if($table_name == 'doc_dopdata') {
		$res = $db->query("SELECT `doc`, `param`, `value` FROM $table_name");
		while($line = $res->fetch_assoc()){
			$v = $db->real_escape_string( html_entity_decode($line['value'], ENT_QUOTES, "UTF-8") );
			if($v) $db->query("UPDATE `$table_name` SET `value`='$v' WHERE `doc`='".$line['doc']."' AND `param`='".$line['param']."'");
		}
	}
	else if($table_name == 'doc_base_cnt') {
		$res = $db->query("SELECT `id`, `sklad`, `mesto` FROM $table_name");
		while($line = $res->fetch_assoc()){
			$v = $db->real_escape_string( html_entity_decode($line['mesto'], ENT_QUOTES, "UTF-8") );
			if($v) $db->query("UPDATE `$table_name` SET `mesto`='$v' WHERE `id`='".$line['id']."' AND `sklad`='".$line['sklad']."'");
		}
	}
	else if($table_name == 'doc_base_values') {
		$res = $db->query("SELECT `id`, `param_id`, `value`, `strval` FROM $table_name");
		while($line = $res->fetch_assoc()){
			$v = $db->real_escape_string( html_entity_decode($line['value'], ENT_QUOTES, "UTF-8") );
			$v2 = $db->real_escape_string( html_entity_decode($line['strval'], ENT_QUOTES, "UTF-8") );
			if($v || $v2) $db->query("UPDATE `$table_name` SET `value`='$v', `strval`='$v2' WHERE `id`='".$line['id']."' AND `param_id`='".$line['param_id']."'");
		}
	}
	else if($table_name == 'users_data') {
		$res = $db->query("SELECT `uid`, `param`, `value` FROM $table_name");
		while($line = $res->fetch_assoc()){
			$v = $db->real_escape_string( html_entity_decode($line['value'], ENT_QUOTES, "UTF-8") );
			if($v) $db->query("UPDATE `$table_name` SET `value`='$v' WHERE `uid`='".$line['uid']."' AND `param`='".$line['param']."'");
		}
	}
	else if($has_id) {
		$res = $db->query("SELECT `id`, $field_str FROM $table_name");
		while($line = $res->fetch_assoc()){
			$values = '';
			foreach($field_names AS $field) {
				if(!$line[$field])	continue;
				if($values) $values.=',';
				$v = $db->real_escape_string( html_entity_decode($line[$field], ENT_QUOTES, "UTF-8") );
				$values .= "`$field` = '$v'";
			}
			if($values)
				$db->query("UPDATE `$table_name` SET $values WHERE `id`=".$line['id']);
		}
		
	}
}

?>