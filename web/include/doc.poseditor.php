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
	var $sklad_id;
	
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
	// Список товаров
	/// TODO: возможность отключения редактирования в зависимости от статуса документа, настройка отображаемых столбцов из конфига. Не забыть про серийные номера.
	/// Возможность отключения строки быстрого ввода
	/// В итоге - сделать базовый класс, от которого наследуется редактор документов, редактор комплектующих, итп.
	$ret="
	<script src='/css/poseditor.js' type='text/javascript'></script>
	<link href='/css/poseditor.css' rel='stylesheet' type='text/css' media='screen' />
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
	<th>№<th>Код<th>Наименование<th>Производитель<th>Цена, р.<th>Ликв.<th>Р.цена, р.<th>Аналог<th>Тип<th>d<th>D<th>B
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
	</script>";	
	
	return $ret;
}

function getGroupsTree()
{
	return "<div onclick='tree_toggle(arguments[0])'>
	<div><a href='' onclick=\"\">Группы</a></div>
	<ul class='Container'>".$this->getGroupLevel(0)."</ul>
	</div>
	Или отбор:<input type='text' id='sklsearch' onkeydown=\"DelayedSave('/doc.php?mode=srv&opt=sklad&doc={$this->doc}','sklad_list', 'sklsearch'); return true;\">";
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
		$item="<a href='' title='$nxt[2]' onclick=\"return getSkladList('$nxt[0]')\" >$nxt[1]</a>";
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

	if(mysql_num_rows($res))
	{
		global $dop_data;
		$i=0;
		$cnt=0;
		while($nxt=mysql_fetch_assoc($res))
		{
// 			{
// 				// Дата цены $nxt[5]
// 				$dcc=strtotime($nxt[6]);
// 				$cc="";
// 				if($dcc>(time()-60*60*24*30*3)) $cc="class=f_green";
// 				else if($dcc>(time()-60*60*24*30*6)) $cc="class=f_purple";
// 				else if($dcc>(time()-60*60*24*30*9)) $cc="class=f_brown";
// 				else if($dcc>(time()-60*60*24*30*12)) $cc="class=f_more";
// 			}
// 			$end=date("Y-m-d");
// 						
// 			$nxt[2]=SearchHilight($nxt[2],$s);
// 			$nxt[8]=SearchHilight($nxt[8],$s);	
// 			$i=1-$i;

			// Новый код
			$reserve=DocRezerv($nxt['id'],$this->doc);
			$offer=DocPodZakaz($nxt['id'],$this->doc);
			$transit=DocVPuti($nxt['id'],$this->doc);
			
			$cost=$this->cost_id?GetCostPos($nxt['id'], $this->cost_id):$nxt['cost'];
			$rcost=sprintf("%0.2f",$nxt['koncost']);
			
			if($ret)	$ret.=', ';
			$ret.="{ id: '{$nxt['id']}', name: '{$nxt['name']}', vc: '{$nxt['vc']}', vendor: '{$nxt['proizv']}', liquidity: '{$nxt['likvid']}', cost: '$cost', rcost: '$rcost', analog: '{$nxt['analog']}', type: '{$nxt['type']}', d_int: '{$nxt['d_int']}', d_ext: '{$nxt['d_ext']}', size: '{$nxt['size']}', mass: '{$nxt['mass']}', place: '{$nxt['mesto']}', cnt: '{$nxt['cnt']}', allcnt: '{$nxt['allcnt']}', reserve: '$reserve', offer: '$offer', transit: '$transit' }";
			$cnt++;
		}	
	}
	return $ret;
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