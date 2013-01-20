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

/// Редактор списка наименований. Для ускорения работы используется ajax технология
class PosEditor
{

	var $editable;		///< Разрешено ли редактирование и показ складского блока
	var $cost_id;		///< id выбранной цены. 0 - базовая
	var $sklad_id;		///< id склада
	var $show_vc;		///< Показывать код производителя
	var $show_tdb;		///< Показывать тип/размеры/массу
	var $show_rto;		///< Показывать резерв/в пути/предложения

function __construct()
{
	global $CONFIG;
	$this->editable=0;
	$this->show_vc=@$CONFIG['poseditor']['vc'];
	$this->show_tdb=@$CONFIG['poseditor']['tdb'];
	$this->show_rto=@$CONFIG['poseditor']['rto'];
}

/// Разрешить или запретить изменение данных в списке наименований
/// @param editable 0: запретить, 1: разрешить
function SetEditable($editable)
{
	$this->editable=$editable;
}


function SetVC($vc)
{
	$this->show_vc=$vc;
}

function getGroupsTree()
{
	return "Отбор:<input type='text' id='sklsearch'><br>
	<div onclick='tree_toggle(arguments[0])'>
	<div><a href='' onclick=\"\">Группы</a></div>
	<ul class='Container'>".$this->getGroupLevel(0)."</ul>
	</div>";
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
		if($tmp)	$ret.="<li class='Node ExpandClosed $r'><div class='Expand'></div><div class='Content'>$item</div><ul class='Container'>$tmp</ul></li>\n";
        	else   		$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>\n";
		$i++;
	}
	return $ret;
}

function getOrder()
{
	global $CONFIG;
	switch(@$CONFIG['doc']['sklad_default_order'])
	{
		case 'vc':	$order='`doc_base`.`vc`';	break;
		case 'cost':	$order='`doc_base`.`cost`';	break;
		default:	$order='`doc_base`.`name`';
	}
	return $order;
}

};

/// Редактор списка наименований документа.
/// При создании экземпляра класса нужно указать ID существующеего документа
class DocPosEditor extends PosEditor
{
	var $doc;	// Id документа
	var $doc_obj;	// Объект - документ
	var $show_sn;	// Показать серийные номера
	var $show_gtd;	// Показывать номер ГТД в поступлении

function __construct($doc)
{
	global $CONFIG;
	parent::__construct();
	$this->doc=$doc->getDocNum();
	$this->show_sn=0;
	$this->doc_obj=$doc;
	$doc_data=$this->doc_obj->getDocData();
	if( @$CONFIG['poseditor']['sn_enable'] && ($doc_data['type']==1 || $doc_data['type']==2))	$this->show_sn=1;
	if( @$CONFIG['poseditor']['true_gtd'] && $doc_data['type']==1)					$this->show_gtd=1;
}

/// Формирует html код списка товаров документа
function Show($param='')
{
	global $CONFIG;
	// Список товаров
	/// @note TODO: возможность отключения редактирования в зависимости от статуса документа, настройка отображаемых столбцов из конфига. Не забыть про серийные номера.
	/// Возможность отключения строки быстрого ввода
	/// В итоге - сделать базовый класс, от которого наследуется редактор документов, редактор комплектующих, итп.
	$ret="
	<script src='/css/poseditor.js' type='text/javascript'></script>
	<link href='/css/poseditor.css' rel='stylesheet' type='text/css' media='screen'>
	<table width='100%' id='poslist'><thead><tr>
	<th width='60px' align='left'>№</th>";
	if($this->show_vc>0)	$ret.="<th width='90px' align='left' title='Код изготовителя'><div class='order_button' id='pl_order_vc'></div> Код</th>";
	$ret.="<th><div class='order_button' id='pl_order_name'></div> Наименование</th>
	<th width='90px' title='Выбранная цена'>Выбр. цена</th>
	<th width='90px' class='hl'><div class='order_button' id='pl_order_cost'></div> Цена</th>
	<th width='60px' class='hl'>Кол-во</th>
	<th width='90px' class='hl'>Стоимость</th>
	<th width='60px' title='Остаток товара на складе'>Остаток</th>
	<th width='90px'>Место</th>";
	if($this->show_sn)	$ret.="<th>SN</th>";
	if($this->show_gtd)	$ret.="<th>ГТД</th>";
	$ret.="</tr>
	</thead>
	<tfoot>
	<tr id='pladd'>
	<td><input type='text' id='pos_id' autocomplete='off' tabindex='1'></td>";
	if($this->show_vc>0)	$ret.="<td><input type='text' id='pos_vc' autocomplete='off' tabindex='2'></td>";
	$ret.="<td><input type='text' id='pos_name' autocomplete='off' tabindex='3'></td>
	<td id='pos_scost'></td>
	<td><input type='text' id='pos_cost' autocomplete='off' tabindex='4'></td>
	<td><input type='text' id='pos_cnt' autocomplete='off' tabindex='5'></td>
	<td id='pos_sum'></td>
	<td id='pos_sklad_cnt'></td>
	<td id='pos_mesto'></td>";
	if($this->show_sn)	$ret.="<td></td>";
	if($this->show_gtd)	$ret.="<td></td>";

	$ret.="
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
	$ret.="</td><td valign='top' class='lin1'>
	<table width='100%' cellspacing='1' cellpadding='2'>
	<tr><thead>
	<th>№";
	if($this->show_vc>0)	$ret.="<th>Код";
	$ret.="<th>Наименование<th>Марка<th>Цена, р.<th>Ликв.<th>Р.цена, р.<th>Аналог";
	if($this->show_tdb>0)	$ret.="<th>Тип<th>d<th>D<th>B<th>Масса";
	if($this->show_rto>0)	$ret.="<th><img src='/img/i_lock.png' alt='В резерве'><th><img src='/img/i_alert.png' alt='Предложений'><th><img src='/img/i_truck.png' alt='В пути'>";
	$ret.="<th>Склад<th>Всего<th>Место
	</thead>
	<tbody id='sklad_list'>
	</tbody>
	</table>
	</td></tr>
	</table>";
	if(!@$CONFIG['poseditor']['need_dialog'])	$CONFIG['poseditor']['need_dialog']=0;
	else						$CONFIG['poseditor']['need_dialog']=1;
	$ret.=@"<script type=\"text/javascript\">
	var poslist=PosEditorInit('/doc.php?doc={$this->doc}&mode=srv',{$this->editable})
	poslist.show_column['sn']='{$this->show_sn}'
	poslist.show_column['vc']='{$this->show_vc}'
	poslist.show_column['gtd']='{$this->show_gtd}'

	var skladview=document.getElementById('sklad_view')
	skladview.show_column['vc']='{$this->show_vc}'
	skladview.show_column['tdb']='{$this->show_tdb}'
	skladview.show_column['rto']='{$this->show_rto}'

	skladlist=document.getElementById('sklad_list').needDialog={$CONFIG['poseditor']['need_dialog']};
	</script>";

	return $ret;
}

/// Получить весь текущий список товаров (документа)
function GetAllContent()
{
	$res=mysql_query("SELECT `doc_list_pos`.`id` AS `line_id`, `doc_base`.`id` AS `pos_id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`cost` AS `bcost`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto`, `doc_list_pos`.`gtd`, `doc_list_pos`.`comm`
	FROM `doc_list_pos`
	INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
	WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_list_pos`.`page`='0'
	ORDER BY `doc_list_pos`.`id`");
	if(mysql_errno())	throw new MysqlException("Ошибка получения списка товаров документа");
	$ret='';
	while($nxt=mysql_fetch_assoc($res))
	{
		if($this->cost_id)	$scost=GetCostPos($nxt['pos_id'], $this->cost_id);
		else			$scost=sprintf("%0.2f",$nxt['bcost']);
		$nxt['cost']=		sprintf("%0.2f",$nxt['cost']);
		if($ret)	$ret.=', ';

		$ret.="{
		line_id: '{$nxt['line_id']}', pos_id: '{$nxt['pos_id']}', vc: '{$nxt['vc']}', name: '{$nxt['name']} - {$nxt['proizv']}', cnt: '{$nxt['cnt']}', cost: '{$nxt['cost']}', scost: '$scost', sklad_cnt: '{$nxt['sklad_cnt']}', mesto: '{$nxt['mesto']}', gtd: '{$nxt['gtd']}', comm: '{$nxt['comm']}'";

		if($this->show_sn)
		{
			$doc_data=$this->doc_obj->getDocData();
			if($doc_data[1]==1)		$column='prix_list_pos';
			else if($doc_data[1]==2)	$column='rasx_list_pos';
			else				throw new Exception("Документ не поддерживает работу с серийными номерами");
			$rs=mysql_query("SELECT `doc_list_sn`.`id`, `doc_list_sn`.`num`, `doc_list_sn`.`rasx_list_pos` FROM `doc_list_sn` WHERE `$column`='{$nxt['line_id']}'");
			$ret.=", sn: '".mysql_num_rows($rs)."'";
		}
		$ret.="}";

	}
	return $ret;
}

/// Получить информацию о наименовании
function GetPosInfo($pos)
{
	$ret='{response: 0}';

	$res=mysql_query("SELECT `doc_list_pos`.`id` AS `line_id`, `doc_base`.`id` AS `pos_id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`cost` AS `bcost`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto`, `doc_list_pos`.`gtd`
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
		line_id: '{$nxt['line_id']}', pos_id: '{$nxt['pos_id']}', vc: '{$nxt['vc']}', name: '{$nxt['name']} - {$nxt['proizv']}', cnt: '{$nxt['cnt']}', cost: '{$nxt['cost']}', scost: '$scost', sklad_cnt: '{$nxt['sklad_cnt']}', mesto: '{$nxt['mesto']}', gtd: '{$nxt['gtd']}'
		} }";
	}

	return $ret;
}

/// Возвращает выбранные данные, которые необходимо отобразить
function ShowPosContent($param='')
{
	$ret='';
	$doc=rcv('doc');
	$res=mysql_query("SELECT `id`, `pos`, `cost`, `cnt` FROM `doc_list_pos` WHERE `doc`='$doc'");
	if(mysql_errno())	throw new MysqlException("Ошибка получения имени");
	while($nxt=mysql_fetch_row($res))
	{
		$ret.="<tr><td>$nxt[0]</td><td>$nxt[1]</td><td>$nxt[2]</td><td>$nxt[3]</td></tr>";
	}
	return $ret;
}

/// Получить список номенклатуры заданной группы
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
	ORDER BY ".$this->getOrder();
	$res=mysql_query($sql);
	if(mysql_errno())	throw new MysqlException("Не удалось получить списока номенклатуры группы $group, склада {$this->sklad_id}");

	return $this->FormatResult($res);
}

/// Получить список номенклатуры, содержащей в названии заданную строку
function SearchSkladList($s)
{
	$ret='';
	$sql="SELECT `doc_base`.`id`,`doc_base`.`vc`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`proizv`, `doc_base`.`likvid`, `doc_base`.`cost`, `doc_base`.`cost_date`,
	`doc_base_dop`.`koncost`,  `doc_base_dop`.`analog`, `doc_base_dop`.`type`, `doc_base_dop`.`d_int`, `doc_base_dop`.`d_ext`, `doc_base_dop`.`size`, `doc_base_dop`.`mass`,
	`doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`, (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `allcnt`";

	$sqla=$sql."FROM `doc_base`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE `doc_base`.`name` LIKE '$s%' OR `doc_base`.`vc` LIKE '$s%' ORDER BY ".$this->getOrder()." LIMIT 200";
	$res=mysql_query($sqla);
	if(mysql_errno())	throw new MysqlException("Ошибка поиска");
	if($cnt=mysql_num_rows($res))
	{
		if($ret!='')	$ret.=', ';
		$ret.="{id: 'header', name: 'Поиск по названию, начинающемуся на $s - $cnt наименований найдено'}";
		$ret=$this->FormatResult($res,$ret);
	}
	$sqla=$sql."FROM `doc_base`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE (`doc_base`.`name` LIKE '%$s%' OR `doc_base`.`vc` LIKE '%$s%') AND `doc_base`.`name` NOT LIKE '$s%' AND `doc_base`.`vc` NOT LIKE '$s%' ORDER BY ".$this->getOrder()." LIMIT 100";
	$res=mysql_query($sqla);
	if(mysql_errno())	throw new MysqlException("Ошибка поиска");
	if($cnt=mysql_num_rows($res))
	{
		if($ret!='')	$ret.=', ';
		$ret.="{id: 'header', name: 'Поиск по названию, содержащему $s - $cnt наименований найдено'}";
		$ret=$this->FormatResult($res,$ret);
	}
	$sqla=$sql."FROM `doc_base`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
	WHERE `doc_base_dop`.`analog` LIKE '%$s%' AND `doc_base`.`name` NOT LIKE '%$s%' AND `doc_base`.`vc` NOT LIKE '%$s%' ORDER BY ".$this->getOrder()." LIMIT 100";
	$res=mysql_query($sqla);
	if(mysql_errno())	throw new MysqlException("Ошибка поиска");
	if($cnt=mysql_num_rows($res))
	{
		if($ret!='')	$ret.=', ';
		$ret.="{id: 'header', name: 'Поиск по аналогу($s) - $cnt наименований найдено'}";
		$ret=$this->FormatResult($res,$ret);
	}

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

/// Добавляет указанную складскую позицию в список
function AddPos($pos)
{
	$cnt=rcv('cnt');
	$cost=rcv('cost');
	$add=0;
	$ret='';

	if(!$pos)	throw new Exception("ID позиции не задан!");
	if($cnt<=0)	throw new Exception("Количество должно быть положительным!");
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
		doc_log("UPDATE","change cnt: pos:$nxt[1], doc_list_pos:$nxt[0], cnt:$nxt[2]+1, doc:{$this->doc}",'pos',$nxt[1]);
	}
	$doc_sum=DocSumUpdate($this->doc);

	if($add)
	{
		$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto`
		FROM `doc_list_pos`
		INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
		WHERE `doc_list_pos`.`id`='$pos_line'");
		if(mysql_errno())	throw MysqlException("Не удалось получить строку документа");
		$line=mysql_fetch_assoc($res);
		$cost=$this->cost_id?GetCostPos($line['id'], $this->cost_id):$line['cost'];
		$ret="{ response: '1', add: { line_id: '$pos_line', pos_id: '{$line['id']}', vc: '{$line['vc']}', name: '{$line['name']} - {$line['proizv']}', cnt: '{$line['cnt']}', scost: '$cost', cost: '{$line['cost']}', sklad_cnt: '{$line['sklad_cnt']}', mesto: '{$line['mesto']}', gtd: '' }, sum: '$doc_sum' }";
	}
	else
	{
		$cost=sprintf("%0.2f",$cost);
		$ret="{ response: '4', update: { line_id: '$pos_line', cnt: '{$cnt}', cost: '{$cost}'}, sum: '$doc_sum' }";
	}
	return $ret;
}

/// Удалить из списка строку с указанным ID
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

/// Обновить строку документа с указанным ID (type - идентификатор колонки, value - записываемое значение)
function UpdateLine($line_id, $type, $value)
{
	$res=mysql_query("SELECT `tovar`, `cnt`, `cost`, `doc`, `gtd`, `comm` FROM `doc_list_pos` WHERE `id`='$line_id'");
	if(mysql_errno())	throw new MysqlException("Не удалось выбрать строку документа!");
	$nxt=mysql_fetch_row($res);
	if(!$nxt)		throw new Exception("Строка не найдена. Вероятно, она была удалена другим пользователем или Вами в другом окне.");
	if($nxt[3]!=$this->doc)	throw new Exception("Строка отностися к другому документу. Правка невозможна.");

	if($type=='cnt' && $value!=$nxt[1])
	{
		if($value<=0) $value=1;
		$res=mysql_query("UPDATE `doc_list_pos` SET `cnt`='$value' WHERE `doc`='{$this->doc}' AND `id`='$line_id'");
		if(mysql_errno())	throw new MysqlException("Не удалось обновить количество в строке документа");
		$doc_sum=DocSumUpdate($this->doc);
		doc_log("UPDATE","change cnt: pos:$nxt[0], line_id:$line_id, cnt:$nxt[1] => $value",'doc',$this->doc);
		doc_log("UPDATE","change cnt: pos:$nxt[0], line_id:$line_id, cnt:$nxt[1] => $value",'pos',$nxt[0]);
		return "{ response: '4', update: { line_id: '$line_id', cnt: '{$value}', cost: '{$nxt[2]}', gtd: '{$nxt[4]}'}, sum: '$doc_sum' }";
	}
	else if($type=='cost' && $value!=$nxt[2])
	{
		if($value<=0) $value=1;
		$res=mysql_query("UPDATE `doc_list_pos` SET `cost`='$value' WHERE `doc`='{$this->doc}' AND `id`='$line_id'");
		if(mysql_errno())	throw new MysqlException("Не удалось обновить цену в строке документа");
		$doc_sum=DocSumUpdate($this->doc);
		doc_log("UPDATE","change cost: pos:$nxt[0], line_id:$line_id, cost:$nxt[2] => $value",'doc',$this->doc);
		doc_log("UPDATE","change cost: pos:$nxt[0], line_id:$line_id, cost:$nxt[2] => $value",'pos',$nxt[0]);
		$value=sprintf('%0.2f',$value);
		return "{ response: '4', update: { line_id: '$line_id', cnt: '{$nxt[1]}', cost: '{$value}', gtd: '{$nxt[4]}'}, sum: '$doc_sum' }";
	}
	else if($type=='sum' && $value!=($nxt[1]*$nxt[2]))
	{
		if($value<=0) $value=1;
		$value=sprintf("%0.2f",$value/$nxt[1]);
		$res=mysql_query("UPDATE `doc_list_pos` SET `cost`='$value' WHERE `doc`='{$this->doc}' AND `id`='$line_id'");
		if(mysql_errno())	throw new MysqlException("Не удалось обновить цену в строке документа");
		$doc_sum=DocSumUpdate($this->doc);
		doc_log("UPDATE","change cost: pos:$nxt[0], line_id:$line_id, cost:$nxt[2] => $value",'doc',$this->doc);
		doc_log("UPDATE","change cost: pos:$nxt[0], line_id:$line_id, cost:$nxt[2] => $value",'pos',$nxt[0]);
		return "{ response: '4', update: { line_id: '$line_id', cnt: '{$nxt[1]}', cost: '{$value}', gtd: '{$nxt[4]}'}, sum: '$doc_sum' }";
	}
	else if($type=='gtd' && $value!=$nxt[4])
	{
		$res=mysql_query("UPDATE `doc_list_pos` SET `gtd`='$value' WHERE `doc`='{$this->doc}' AND `id`='$line_id'");
		if(mysql_errno())	throw new MysqlException("Не удалось обновить ГТД в строке документа");
		$doc_sum=DocSumUpdate($this->doc);
		doc_log("UPDATE","change gtd: pos:$nxt[0], line_id:$line_id, gtd:$nxt[4] => $value",'doc',$this->doc);
		return "{ response: '4', update: { line_id: '$line_id', cnt: '{$nxt[1]}', cost: '{$nxt[2]}', gtd: '{$value}'}, sum: '$doc_sum' }";
	}
	else if($type=='comm' && $value!=$nxt[5])
	{
		$res=mysql_query("UPDATE `doc_list_pos` SET `comm`='$value' WHERE `doc`='{$this->doc}' AND `id`='$line_id'");
		if(mysql_errno())	throw new MysqlException("Не удалось обновить комментарий в строке документа");
		doc_log("UPDATE","change comm: pos:$nxt[0], line_id:$line_id, comm:$nxt[5] => $value",'doc',$this->doc);
		return "{ response: '4', update: { line_id: '$line_id', cnt: '{$nxt[1]}', cost: '{$nxt[2]}', comm: '{$value}'} }";
	}
	else return "{ response: '0', message: 'value: $value, type:$type, line_id:$line_id'}";
}

function SerialNum($action, $line_id, $data)
{
	$doc_data=$this->doc_obj->getDocData();
	if($action=='l')	// List
	{
		if($doc_data[1]==1)		$column='prix_list_pos';
		else if($doc_data[1]==2)	$column='rasx_list_pos';
		else				throw new Exception("В данном документе серийные номера не используются!");
		$res=mysql_query("SELECT `doc_list_sn`.`id`, `doc_list_sn`.`num`, `doc_list_sn`.`rasx_list_pos` FROM `doc_list_sn` WHERE `$column`='$line_id'");
		$ret='';
		while($nxt=mysql_fetch_row($res))
		{
			if($ret)	$ret.=', ';
			$ret.="{ id: '$nxt[0]', sn: '$nxt[1]' }";
		}
		return "{response: 'sn_list', list: [ $ret ]}";
	}
	else if($action=='d')	// delete
	{
		if($doc_data[1]==1)		mysql_query("DELETE FROM `doc_list_sn` WHERE `id`='$line_id' AND  `rasx_list_pos` IS NULL");
		else if($doc_data[1]==2)	mysql_query("UPDATE `doc_list_sn` SET `rasx_list_pos`=NULL  WHERE `id`='$line_id'");
		else				throw new Exception("В данном документе серийные номера не используются!");
		if(mysql_errno())		throw new MysqlException("Не удалось удалить номер");
		if(mysql_affected_rows())	return "{response: 'deleted' }";
		else				return "{response: 'not_deleted', message: 'Номер уже удалён, или используется в реализации' }";
	}
}

function reOrder($by='name')
{
	if($by!=='name' && $by!=='cost' && $by!=='vc')	$by='name';
	mysql_query("START TRANSACTION");
	$res=mysql_query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_list_pos`.`gtd`, `doc_list_pos`.`comm`, `doc_list_pos`.`cost`, `doc_list_pos`.`page`, `doc_base`.`name`, `doc_base`.`vc`
	FROM `doc_list_pos`
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
	WHERE `doc_list_pos`.`doc`='{$this->doc}'
	ORDER BY `$by`");
	if(mysql_errno())		throw new MysqlException("Не удалось получить наименования");
	mysql_query("DELETE FROM `doc_list_pos` WHERE `doc`='{$this->doc}'");
	if(mysql_errno())		throw new MysqlException("Не удалось удалить старые наименования");
	while($nxt=mysql_fetch_row($res))
	{
		mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `gtd`, `comm`, `cost`, `page`)
		VALUES ('{$this->doc}', '$nxt[0]', '$nxt[1]', '$nxt[2]', '$nxt[3]', '$nxt[4]', '$nxt[5]')");
		if(mysql_errno())		throw new MysqlException("Не удалось вставить наименования");
	}
	mysql_query("COMMIT");
	doc_log("UPDATE","ORDER poslist BY $by",'doc',$this->doc);
}

};


?>