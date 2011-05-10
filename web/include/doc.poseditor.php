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

// Редактор списка наименований
// Для ускорения работы используется ajax технология
class PosEditor
{
	var $editable;	// Разрешено ли редактирование и показ складского блока
	var $table;	// Таблица БД для хранения данных
	var $columns;	// Наименования столбцов в БД
	var $doc;	// Документ
	var $cost_id;	// id выбранной цены. 0 - базовая
	var $sklad_id;  // id склада
	
function __construct($table, $doc)
{
	$this->table=$table;
	$this->columns=array('doc' => 'doc', 'pos' => 'pos', 'cost' => 'cost', 'cnt' => 'cnt');
	$this->editable=0;
	$this->doc=$doc;
}

function SetColumn($name, $value)
{
	$this->columns[$name]=$value;
}

function Show($param='')
{
	global $CONFIG;
	// Список товаров
	/// TODO: возможность отключения редактирования в зависимости от статуса документа, настройка отображаемых столбцов из конфига. Не забыть про серийные номера.
	/// Возможность отключения строки быстрого ввода
	/// В итоге - сделать базовый класс, от которого наследуется редактор документов, редактор комплектующих, итп.
	$ret="
	<script src='/css/poseditor.js' type='text/javascript'></script>
	<link href='/css/poseditor.css' rel='stylesheet' type='text/css' media='screen'>
	<table width='100%' id='poslist'><thead><tr>
	<th width='60px' align='left'>№</th>
	<th width='90px' align='left' title='Код изготовителя'>Код</th>
	<th>Наименование</th>
	<th width='90px' title='Выбранная цена'>Выбр. цена</th>
	<th width='90px'>Цена</th>
	<th width='60px'>Кол-во</th>
	<th width='90px'>Стоимость</th>
	<th width='60px' title='Остаток товара на складе'>Остаток</th>
	<th width='90px'>Место</th></tr>
	</thead>
	<tfoot>
	<tr id='pladd'>
	<td><input type='text' id='pos_id' autocomplete='off' tabindex='1'></td>
	<td><input type='text' id='pos_vc' autocomplete='off' tabindex='2'></td>
	<td><input type='text' id='pos_name' autocomplete='off' tabindex='3'></td>
	<td id='pos_scost'></td>
	<td><input type='text' id='pos_cost' autocomplete='off' tabindex='4'></td>
	<td><input type='text' id='pos_cnt' autocomplete='off' tabindex='5'></td>
	<td id='pos_sum'></td>
	<td id='pos_sklad_cnt'></td>
	<td id='pos_mesto'></td>
	</tr>
	</tfoot>
	<tbody>
	<tr><td colspan='9' style='text-align: center;'><img src='/img/icon_load.gif' alt='Загрузка...'>
 	</tbody>
	</table>
	<p align='right' id='sum'></p>";
	
	$ret.="
	<table id='sklad_view'>
	<tr><td id='groups_list' width='200' valign='top' class='lin0'>";
	$ret.=$this->getGroupsTree();
	$ret.="</td>
	<td valign='top' class='lin1'>
	
	<table width='100%' cellspacing='1' cellpadding='2'>
	<tr>
	<thead>
	<th>№<th>Код<th>Наименование<th>Марка<th>Цена, р.<th>Ликв.<th>Р.цена, р.<th>Аналог<th>Тип<th>d<th>D<th>B
	<th>Масса<th><img src='/img/i_lock.png' alt='В резерве'><th><img src='/img/i_alert.png' alt='Предложений'><th><img src='/img/i_truck.png' alt='В пути'><th>Склад<th>Всего<th>Место
	</thead>
	<tbody id='sklad_list'>
	</tbody>
	</table>
	</td></tr>
	</table>
	";
	
	$ret.="	<script type=\"text/javascript\">
	PosEditorInit({$this->doc});
	skladlist=document.getElementById('sklad_list').needDialog={$CONFIG['poseditor']['need_dialog']}
	</script>";	
	
	return $ret;
}

function getGroupsTree()
{
	return "Отбор:<input type='text' id='sklsearch'><br>
	<div onclick='tree_toggle(arguments[0])'>
	<div><a href='' onclick=\"\">Группы</a></div>
	<ul class='Container'>".$this->getGroupLevel(0)."</ul>
	</div>";
	//onkeydown=\"DelayedSave('/doc.php?mode=srv&opt=sklad&doc={$this->doc}','sklad_list', 'sklsearch'); return true;\"
}

function getGroupLevel($level)
{
	$ret='';
	$res=mysql_query("SELECT `id`, `name`, `desc` FROM `doc_group` WHERE `pid`='$level' ORDER BY `id`");
	$i=0;
	$r='';
	if($level==0) $r='IsRoot';
	$cnt=mysql_num_rows($res);
	while($nxt=mysql_fetch_row($res))
	{
		if($nxt[0]==0) continue;
		$item="<a href='' title='$nxt[2]' onclick=\"return getSkladList(event, '$nxt[0]')\" >$nxt[1]</a>";
		if($i>=($cnt-1)) $r.=" IsLast";
		$tmp=$this->getGroupLevel($nxt[0]); // рекурсия
		if($tmp)
			$ret.="<li class='Node ExpandClosed $r'><div class='Expand'></div><div class='Content'>$item</div><ul class='Container'>$tmp</ul></li>\n";
        	else
        		$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>\n";
		$i++;
	}
	return $ret;
}

// Получить весь текущий список товаров (документа)
function GetAllContent()
{
	$res=mysql_query("SELECT `doc_list_pos`.`id` AS `line_id`, `doc_base`.`id` AS `pos_id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`cost` AS `bcost`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto`
	FROM `doc_list_pos`
	INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
	WHERE `doc_list_pos`.`doc`='{$this->doc}'");
	if(mysql_errno())	throw new MysqlException("Ошибка получения имени");
	$ret='';
	while($nxt=mysql_fetch_assoc($res))
	{
		if($this->cost_id)	$scost=GetCostPos($nxt['pos_id'], $this->cost_id);
		else			$scost=sprintf("%0.2f",$nxt['bcost']);
		$nxt['cost']=		sprintf("%0.2f",$nxt['cost']);
		if($ret)	$ret.=', ';
		$ret.="{
		line_id: '{$nxt['line_id']}', pos_id: '{$nxt['pos_id']}', vc: '{$nxt['vc']}', name: '{$nxt['name']} - {$nxt['proizv']}', cnt: '{$nxt['cnt']}', cost: '{$nxt['cost']}', scost: '$scost', sklad_cnt: '{$nxt['sklad_cnt']}', mesto: '{$nxt['mesto']}'
		}";
	}
	return $ret;
}

function GetPosInfo($pos)
{
	$ret='{response: 0}';

	$res=mysql_query("SELECT `doc_list_pos`.`id` AS `line_id`, `doc_base`.`id` AS `pos_id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`cost` AS `bcost`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto`
	FROM `doc_base`
	LEFT JOIN `doc_list_pos` ON `doc_base`.`id`=`doc_list_pos`.`tovar` AND `doc_list_pos`.`doc`='{$this->doc}'
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
	WHERE `doc_base`.`id`='$pos'");
	if(mysql_errno())	throw new MysqlException("Ошибка получения имени");
	$ret='';
	if($nxt=mysql_fetch_assoc($res))
	{
		if($this->cost_id)	$scost=GetCostPos($nxt['pos_id'], $this->cost_id);
		else			$scost=sprintf("%0.2f",$nxt['bcost']);
		if(!$nxt['cnt'])	$nxt['cnt']=1;
		if(!$nxt['cost'])	$nxt['cost']=$scost;
		$nxt['cost']=		sprintf("%0.2f",$nxt['cost']);
		$ret="{response: 3, data: {
		line_id: '{$nxt['line_id']}', pos_id: '{$nxt['pos_id']}', vc: '{$nxt['vc']}', name: '{$nxt['name']} - {$nxt['proizv']}', cnt: '{$nxt['cnt']}', cost: '{$nxt['cost']}', scost: '$scost', sklad_cnt: '{$nxt['sklad_cnt']}', mesto: '{$nxt['mesto']}'
		} }";
	}

	return $ret;
}

// Возвращает выбранные данные, которые необходимо отобразить
function ShowPosContent($param='')
{
	$ret='';
	$doc=rcv('doc');
	$res=mysql_query("SELECT `id`, `{$this->columns['pos']}`, `{$this->columns['cost']}`, `{$this->columns['cnt']}` FROM `{$this->table}` WHERE `{$this->columns['doc']}`='$doc'");
	if(mysql_errno())	throw new MysqlException("Ошибка получения имени");
	while($nxt=mysql_fetch_row($res))
	{
		$ret.="<tr><td>$nxt[0]</td><td>$nxt[1]</td><td>$nxt[2]</td><td>$nxt[3]</td></tr>";
	}
	return $ret;
}

function GetSkladList($group)
{
	$ret='';
	$sql="SELECT `doc_base`.`id`,`doc_base`.`vc`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`, `doc_base`.`cost_date`,
	`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`,
	`doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt` , (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `allcnt`
	FROM `doc_base`
	LEFT JOIN `doc_base_cnt`  ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE `doc_base`.`group`='$group'
	ORDER BY `doc_base`.`name`";
	$res=mysql_query($sql);
	if(mysql_errno())	throw new MysqlException("Не удалось получить списока номенклатуры группы $group, склада {$this->sklad_id}");

	return $this->FormatResult($res);
}

function SearchSkladList($s)
{
	$ret='';
	$sql="SELECT `doc_base`.`id`,`doc_base`.`vc`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`, `doc_base`.`cost_date`,
	`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`,
	`doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`, (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `allcnt`";
		
	$sqla=$sql."FROM `doc_base`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE `doc_base`.`name` LIKE '$s%' OR `doc_base`.`vc` LIKE '$s%' ORDER BY `doc_base`.`name` LIMIT 200";
	$res=mysql_query($sqla);
	if(mysql_errno())	throw new MysqlException("Ошибка поиска");
	$ret=$this->FormatResult($res,$ret);
	
	$sqla=$sql."FROM `doc_base`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE (`doc_base`.`name` LIKE '%$s%' OR `doc_base`.`vc` LIKE '%$s%') AND (`doc_base`.`name` NOT LIKE '$s%' OR `doc_base`.`vc` NOT LIKE '$s%') ORDER BY `doc_base`.`name` LIMIT 100";
	$res=mysql_query($sqla);
	if(mysql_errno())	throw new MysqlException("Ошибка поиска");
	$ret=$this->FormatResult($res,$ret);
	
	$sqla=$sql."FROM `doc_base`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE `doc_base_dop`.`analog` LIKE '%$s%' AND (`doc_base`.`name` NOT LIKE '%$s%' OR `doc_base`.`vc` NOT LIKE '%$s%') ORDER BY `doc_base`.`name` LIMIT 100";
	$res=mysql_query($sqla);
	if(mysql_errno())	throw new MysqlException("Ошибка поиска");
	$ret=$this->FormatResult($res,$ret);
	
	return $ret;
}


protected function FormatResult($res, $ret='')
{
	if(mysql_num_rows($res))
	{
		while($nxt=mysql_fetch_assoc($res))
		{
			$dcc=strtotime($nxt['cost_date']);
			$cc="";
			if($dcc>(time()-60*60*24*30*3)) $cc="c1";
			else if($dcc>(time()-60*60*24*30*6)) $cc="c2";
			else if($dcc>(time()-60*60*24*30*9)) $cc="c3";
			else if($dcc>(time()-60*60*24*30*12)) $cc="c4";
			$reserve=DocRezerv($nxt['id'],$this->doc);
			$offer=DocPodZakaz($nxt['id'],$this->doc);
			$transit=DocVPuti($nxt['id'],$this->doc);
			$cost=$this->cost_id?GetCostPos($nxt['id'], $this->cost_id):$nxt['cost'];
			$rcost=sprintf("%0.2f",$nxt['koncost']);
			if($ret!='')	$ret.=', ';
			$ret.="{ id: '{$nxt['id']}', name: '{$nxt['name']}', vc: '{$nxt['vc']}', vendor: '{$nxt['proizv']}', liquidity: '{$nxt['likvid']}', cost: '$cost', cost_class: '$cc', rcost: '$rcost', analog: '{$nxt['analog']}', type: '{$nxt['type']}', d_int: '{$nxt['d_int']}', d_ext: '{$nxt['d_ext']}', size: '{$nxt['size']}', mass: '{$nxt['mass']}', place: '{$nxt['mesto']}', cnt: '{$nxt['cnt']}', allcnt: '{$nxt['allcnt']}', reserve: '$reserve', offer: '$offer', transit: '$transit' }";
		}	
	}
	return $ret;
}

// Добавляет указанную складскую позицию в список
function AddPos($pos)
{
	$cnt=rcv('cnt');
	$cost=rcv('cost');
	$add=0;
	$ret='';
		
	$res=mysql_query("SELECT `id`, `tovar`, `cnt`, `cost` FROM `doc_list_pos` WHERE `doc`='{$this->doc}' AND `tovar`='$pos'");
	if(mysql_errno())	throw new MysqlException("Не удалось выбрать строку документа!");
	if(mysql_num_rows($res)==0)
	{
		mysql_query("INSERT INTO doc_list_pos (`doc`,`tovar`,`cnt`,`cost`) VALUES ('{$this->doc}','$pos','$cnt','$cost')");
		if(mysql_errno())	throw new MysqlException("Не удалось вставить строку в документ!");
		$pos_line=mysql_insert_id();
		doc_log("UPDATE","add pos: pos:$pos",'doc',$this->doc);
		doc_log("UPDATE","add pos: pos:$pos",'pos',$pos);
		$add=1;
	}
	else
	{
		$nxt=mysql_fetch_row($res);
		$pos_line=$nxt[0];
		mysql_query("UPDATE `doc_list_pos` SET `cnt`='$cnt', `cost`='$cost' WHERE `id`='$nxt[0]'");
		if(mysql_errno())	throw MysqlException("Не удалось вставить строку в документ!");
		doc_log("UPDATE","change cnt: pos:$nxt[1], doc_list_pos:$nxt[0], cnt:$nxt[2]+1",'doc',$this->doc);
		doc_log("UPDATE","change cnt: pos:$nxt[1], doc_list_pos:$nxt[0], cnt:$nxt[2]+1, doc:$doc",'pos',$nxt[1]);
	}	
	$doc_sum=DocSumUpdate($this->doc);
	
	if($add)
	{
		$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto`
		FROM `doc_list_pos`
		INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data['sklad']}'
		WHERE `doc_list_pos`.`id`='$pos_line'");
		if(mysql_errno())	throw MysqlException("Не удалось получить строку документа");
		$line=mysql_fetch_assoc($res);
		$cost=$this->cost_id?GetCostPos($line['id'], $this->cost_id):$line['cost'];
		$ret="{ response: '1', add: { line_id: '$pos_line', pos_id: '{$line['id']}', vc: '{$line['vc']}', name: '{$line['name']} - {$line['proizv']}', cnt: '{$line['cnt']}', scost: '$cost', cost: '{$line['cost']}', sklad_cnt: '{$line['sklad_cnt']}', mesto: '{$line['mesto']}' }, sum: '$doc_sum' }";
	}
	else
	{
		$cost=sprintf("%0.2f",$cost);
		$ret="{ response: '4', update: { line_id: '$pos_line', cnt: '{$cnt}', cost: '{$cost}'}, sum: '$doc_sum' }";
	}
	return $ret;
}

function RemoveLine($line_id)
{
	$res=mysql_query("SELECT `tovar`, `cnt`, `cost`, `doc` FROM `doc_list_pos` WHERE `id`='$line_id'");
	if(mysql_errno())	throw new MysqlException("Не удалось выбрать строку документа!");
	$nxt=mysql_fetch_row($res);
	if($nxt)
	{
		if($nxt[3]!=$this->doc)	throw new Exception("Строка отностися к другому документу. Удаление невозможно.");
		$res=mysql_query("DELETE FROM `doc_list_pos` WHERE `id`='$line_id'");
		doc_log("UPDATE","del line: pos:$nxt[0], line_id:$line_id, cnt:$nxt[1], cost:$nxt[2]",'doc',$this->doc);
		doc_log("UPDATE","del line: pos:$nxt[0], line_id:$line_id, cnt:$nxt[1], cost:$nxt[2]",'pos',$nxt[0]);
	}
	$doc_sum=DocSumUpdate($this->doc);
	return "{ response: '5', remove: { line_id: '$line_id' }, sum: '$doc_sum' }";
}


// // Используется для добавления/изменения/удаления строки
// // Возвращает json информацию необходимых изменений на клиенте
// function Modify()
// {
// 	$line_id=rcv('lid');
// 	$action=rcv('a');
// 	$doc=rcv('doc');
// 	$pid=rcv('pid');
// 	if($action=='add')
// 	{
// 		return $this->ActionAdd($doc, $pid);
// 	}
// 	else return "{ \"response\": \"err\", \"errmsg\": \"Неизвестное действие ($action). Вероятно, произошла ошибка в программе.\" }";
// }
// 
// // Внутренние функции
// function ActionAdd($doc, $pid)
// {
// 	mysql_query("INSERT INTO `{$this->table}` (`{$this->columns['doc']}`, `{$this->columns['pos']}`, `{$this->columns['cost']}`, `{$this->columns['cnt']}`) VALUES ('$doc', '$pid', '1', '1')");
// 	if(mysql_errno())	throw new MysqlException("Ошибка вставки строки");
// 	$line_id=mysql_insert_id();
// 	$res=mysql_query("SELECT `name` FROM `doc_base` WHERE `id`='$pid'");
// 	if(mysql_errno())	throw new MysqlException("Ошибка получения имени");
// 	$nxt=mysql_fetch_row($res);
// 	
// 	return "{ \"response\": \"add\", \"line_id\": \"$line_id\", \"name\": \"$nxt[0]\"}"; 
// }
// 




};


?>