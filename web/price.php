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

include_once("core.php");
include_once("include/doc.core.php");

$f=rcv('f');
$nal=rcv('nal');
$proizv=rcv('proizv');

$res=mysql_query("SELECT * FROM `doc_vars`");
$dv=mysql_fetch_assoc($res);


function draw_groups_tree($level)
{
	$ret='';
	$res=mysql_query("SELECT `id`, `name`, `desc` FROM `doc_group` WHERE `pid`='$level' AND `hidelevel`='0' ORDER BY `name`");
	$i=0;
	$r='';
	if($level==0) $r='IsRoot';
	$cnt=mysql_num_rows($res);
	while($nxt=mysql_fetch_row($res))
	{
		if($nxt[0]==0) continue;
		$item="<label><input type='checkbox' name='g[]' value='$nxt[0]' id='cb$nxt[0]' class='cb' checked onclick='CheckCheck($nxt[0])'>$nxt[1]</label>";
		if($i>=($cnt-1)) $r.=" IsLast";
		$tmp=draw_groups_tree($nxt[0]); // рекурсия
		if($tmp)
			$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div><ul class='Container' id='cont$nxt[0]'>".$tmp.'</ul></li>';
        	else
        		$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
		$i++;
	}
	return $ret;
}


function GroupSelBlock()
{
	global $tmpl;
	$tmpl->AddStyle(".scroll_block
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
	$tmpl->AddText("<script type='text/javascript'>
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
	".draw_groups_tree(0)."</ul></div>");

}

class PriceWriterXLS
{
	var $workbook;		// книга XLS
	var $worksheet;		// Лист XLS
	var $line;		// Текущая строка
	var $format_line;	// формат для строк наименований прайса
	var $format_group;	// формат для строк групп прайса
	var $cost_id;		// ID цены для прайса

	var $column_count;	// Кол-во колонок
	var $view_proizv;	// Отображать производителя
	var $view_groups;	// Группы, которые надо отображать

	function __construct()
	{
		require_once 'include/Spreadsheet/Excel/Writer.php';
		global $CONFIG;
		$this->line	= 0;
		$this->cost_id=1;
		$this->view_proizv=0;
		$this->view_groups=0;
		$this->workbook	= new Spreadsheet_Excel_Writer();
		// sending HTTP headers
		$this->workbook->send('price.xls');

		// Creating a worksheet
		$this->worksheet =& $this->workbook->addWorksheet($CONFIG['site']['name']);

		$this->format_footer	=& $this->workbook->addFormat();
		$this->format_footer->SetAlign('center');
		$this->format_footer->setColor(39);
		$this->format_footer->setFgColor(27);
		$this->format_footer->SetSize(8);

		$this->format_line=array();
		$this->format_line[0]	=& $this->workbook->addFormat();
		$this->format_line[0]->setColor(0);
		$this->format_line[0]->setFgColor(26);
		$this->format_line[0]->SetSize(12);
		$this->format_line[1]	=& $this->workbook->addFormat();
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
	}

	function Open()
	{
		global $CONFIG;
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
		foreach($CONFIG['site']['price_text'] as $text)
		{
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

		if(is_array($this->view_groups))
		{
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

		//$this->worksheet->freezePanes(array($this->line,count($column_width),0,0));
	}

	function ViewProizv($flag=1)
	{
		$this->view_proizv=$flag;
	}

	function SetCost($cost=1)
	{
		$this->cost_id=$cost;
	}

	function SetColCount($count)
	{
		$this->column_count=$count;
	}

	function SetViewGroups($groups)
	{
		$this->view_groups=$groups;
	}

	function write($group=0, $level=0)
	{
		if($level>2)	$level=2;
		$res=mysql_query("SELECT `id`, `name`, `printname` FROM `doc_group` WHERE `pid`='$group' AND `hidelevel`='0' ORDER BY `id`");
		while($nxt=mysql_fetch_row($res))
		{
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

	function close()
	{
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

	// Внутренние функции класса
	function writepos($group=0, $group_name='')
	{
		$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost_date` , `doc_base`.`proizv`
		FROM `doc_base`
		LEFT JOIN `doc_group` ON `doc_base`.`group`=`doc_group`.`id`
		WHERE `doc_base`.`group`='$group' AND `doc_base`.`hidden`='0' ORDER BY `doc_base`.`name`");
		$i=0;
		while($nxt=mysql_fetch_row($res))
		{
			$this->worksheet->write($this->line, 0, $nxt[0], $this->format_line[$i]);	// артикул
			$name=iconv('UTF-8', 'windows-1251', "$group_name $nxt[1]".(($this->view_proizv&&$nxt[3])?" ($nxt[3])":''));
			$this->worksheet->write($this->line, 1, $name, $this->format_line[$i]);		// наименование
			$this->worksheet->write($this->line, 2, '', $this->format_line[$i]);		// наличие - пока не отображается
			$cost = GetCostPos($nxt[0], $this->cost_id);
			if($cost==0)	continue;
			$str=iconv('UTF-8', 'windows-1251',$cost);
			$this->worksheet->write($this->line, 3, $str, $this->format_line[$i]);		// цена
// НАДО СДЕЛАТЬ ПОДСВЕТКУ ЦЕН
// 			$dcc=strtotime($data['cost_date']);
// 			if( ($dcc<(time()-60*60*24*30*6))|| ($str==0) ) $cce=128;
// 			else $cce=0;

 			$this->line++;
 			$i=1-$i;
		}
	}


};


class PriceWriterCSV
{
	var $column_count;	// Кол-во колонок
	var $view_proizv;	// Отображать производителя
	var $divider;		// Разделитель
	var $shielder;		// Экранирование строк
	var $line;		// Текущая строка
	var $cost_id;		// ID цены для прайса
	var $view_groups;	// Группы, которые надо отображать
	function __construct($divider=",", $shielder='"')
	{
		$this->column_count=2;
		$this->view_proizv=0;
		$this->divider=$divider;
		$this->shielder=$shielder;
		$this->line=0;
		$this->cost_id=1;
		$this->view_groups=0;

		header("Content-Type: text/csv; charset=utf-8");
		header("Content-Disposition: attachment; filename=price_list.csv;");

		if($this->divider!=";" && $this->divider!=":")		$this->divider=",";
		if($this->shielder!="'" && $this->shielder!="*")	$this->shielder="\"";

		settype($this->column_count, "int");
		if($this->column_count<1) $this->column_count=1;
		if($this->column_count>5) $this->column_count=5;
	}

	function Open()
	{
		for($i=0;$i<$this->column_count;$i++)
		{
			echo $this->shielder."Название".$this->shielder.$this->divider.$this->shielder."Цена".$this->shielder;
			if($i<($this->column_count-1)) echo $this->divider.$this->shielder.$this->shielder.$this->divider;
		}
		echo "\n";
		$this->line++;
	}

	function ViewProizv($flag=1)
	{
		$this->view_proizv=$flag;
	}

	function SetCost($cost=1)
	{
		$this->cost_id=$cost;
	}

	function SetColCount($count)
	{
		$this->column_count=$count;
	}

	function SetViewGroups($groups)
	{
		$this->view_groups=$groups;
		var_dump($this->view_groups);
	}

	function write($group=0, $level=0)
	{
		if($level>2)	$level=2;
		$res=mysql_query("SELECT `id`, `name`, `printname` FROM `doc_group` WHERE `pid`='$group' AND `hidelevel`='0' ORDER BY `id`");
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[0]==0) continue;
			if(is_array($this->view_groups))
				if(!in_array($nxt[0],$this->view_groups))	continue;

			$this->line++;
			echo $this->shielder.$nxt[1].$this->shielder;
			echo"\n";
			$this->writepos($nxt[0], $nxt[2]?$nxt[2]:$nxt[1] );
			$this->write($nxt[0], $level+1); // рекурсия

		}
	}
	function close()
	{
		global $CONFIG;
		echo"\n\n\n\n\n";
		$this->line+=5;
		echo $this->shielder."Generated from MultiMag (http://multimag.tndproject.org), for http://".$CONFIG['site']['name'].$this->shielder;
		$this->line++;
		echo"\n";
		echo $this->shielder."Прайс создан системой MultiMag (http://multimag.tndproject.org), специально для http://".$CONFIG['site']['name'].$this->shielder;
	}

	// Внутренние функции класса
	function writepos($group=0, $group_name='')
	{
		$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost_date` , `doc_base`.`proizv`
		FROM `doc_base`
		LEFT JOIN `doc_group` ON `doc_base`.`group`=`doc_group`.`id`
		WHERE `doc_base`.`group`='$group' AND `doc_base`.`hidden`='0' ORDER BY `doc_base`.`name`");
		$i=0;
		$cur_col=0;
		while($nxt=mysql_fetch_row($res))
		{
			if($cur_col>=$this->column_count)
			{
				$cur_col=0;
				echo"\n";
			}
			else if($cur_col!=0)
			{
				echo $this->divider.$this->shielder.$this->shielder.$this->divider;
			}

			$c = GetCostPos($nxt[0], $this->cost_id);
			if($c==0)	continue;
			if(($this->view_proizv)&&($nxt[3])) $pr=" (".$nxt[3].")"; else $pr="";
			echo $this->shielder.$nxt[1].$pr.$this->shielder.$this->divider.$this->shielder.$c.$this->shielder;

 			$this->line++;
 			$i=1-$i;
 			$cur_col++;
		}
		echo"\n\n";
	}
}


class PriceWriterHTML
{
	var $column_count;	// Кол-во колонок
	var $view_proizv;	// Отображать производителя
	var $line;		// Текущая строка
	var $cost_id;		// ID цены для прайса
	var $view_groups;	// Группы, которые надо отображать
	var $span;		// Количество столбцов таблицы
	function __construct()
	{
		global $CONFIG;
		$this->column_count=4;
		$this->view_proizv=0;
		$this->line=0;
		$this->cost_id=1;
		$this->view_groups=0;
		$this->span=$this->column_count*2;

		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html lang=\"ru\">
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">

<title>Прайс-лист: задание параметров</title>
<style type='text/css'>
body {font-size: 10px; color: #000; font-family: sans-serif; background-color: #fff}
h1 {font-weight: bold; font-size: 24px; font-family: sans-serif; color: #00f;}
h2 {font-weight: bold; font-size: 22px; font-family: sans-serif; color: #00f;}
h3 {font-weight: bold; font-size: 20px; font-family: sans-serif; color: #00f;}
h4 {font-weight: bold; font-size: 18px; font-family: sans-serif; color: #00f;}
h5 {font-weight: bold; font-size: 16px; font-family: sans-serif; color: #00f;}
h6 {font-weight: bold; font-size: 14px; font-family: sans-serif; color: #00f;}
table {border: #000 1px solid; border-collapse: collapse; width: 100%; font-size: 10px; border-spacing: 0;}
tr {background-color: #ffc;}
th { border: #000 1px solid; padding: 2px; text-align: center; font-weight: bold; color: #000; background-color: #f60;}
th.cost {background-color: #333; color: #fff;}
th.n1 {background-color: #f90;}
th.n2 {background-color: #fc0;}
th.n3 {background-color: #fd0;}
td { border: #000 1px solid; padding: 2px; }
tr:nth-child(odd) {background-color: #cff;}
.mini {font-size: 10px; text-align: center;}
.v2 {width: 30px;}
.np {page-break-after: always;}
</style>
</head>
<body>
<center>";

	}

	function Open()
	{
		global $CONFIG;
		$i=1;
		if(is_array($CONFIG['site']['price_text']))
		foreach($CONFIG['site']['price_text'] as $text)
		{
			echo"<h$i>$text</h$i>";
			$this->line++;
			$i++;
		}

		$this->line++;
		echo"</center><table><tr>";
		for($cur_col=0;$cur_col<$this->column_count;$cur_col++)
			echo"<th class='cost'>Наименование</th><th class='cost'>Цена</th>";
		echo"</tr>";
	}

	function ViewProizv($flag=1)
	{
		$this->view_proizv=$flag;
	}

	function SetCost($cost=1)
	{
		$this->cost_id=$cost;
	}

	function SetColCount($count)
	{
		$this->column_count=$count;
	}

	function SetViewGroups($groups)
	{
		$this->view_groups=$groups;
		var_dump($this->view_groups);
	}

	function write($group=0, $level=0)
	{

		if($level>3)	$level=3;
		$res=mysql_query("SELECT `id`, `name`, `printname` FROM `doc_group` WHERE `pid`='$group' AND `hidelevel`='0' ORDER BY `id`");
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[0]==0) continue;
			if(is_array($this->view_groups))
				if(!in_array($nxt[0],$this->view_groups))	continue;

			$this->line++;
			echo"<tr><th class='n$level' colspan='{$this->span}'>$nxt[1]</th></tr>";
			$this->writepos($nxt[0], $nxt[2]?$nxt[2]:$nxt[1] );
			$this->write($nxt[0], $level+1); // рекурсия

		}
	}
	function close()
	{
		global $CONFIG;
		echo "<tr><td colspan='{$this->span}' class='mini'>Generated from MultiMag (<a href='http://multimag.tndproject.org'>http://multimag.tndproject.org</a>), for <a href='http://{$CONFIG['site']['name']}'>http://{$CONFIG['site']['name']}</a><br>Прайс создан системой MultiMag (<a href='http://multimag.tndproject.org'>http://multimag.tndproject.org</a>), специально для <a href='http://{$CONFIG['site']['name']}'>http://{$CONFIG['site']['name']}</a></td></tr></table>";
	}

	// Внутренние функции класса
	function writepos($group=0, $group_name='')
	{
		$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost_date` , `doc_base`.`proizv`
		FROM `doc_base`
		LEFT JOIN `doc_group` ON `doc_base`.`group`=`doc_group`.`id`
		WHERE `doc_base`.`group`='$group' AND `doc_base`.`hidden`='0' ORDER BY `doc_base`.`name`");
		$i=0;
		$cur_col=0;
		while($nxt=mysql_fetch_row($res))
		{
			if($cur_col>=$this->column_count)
			{
				$cur_col=0;
				echo"<tr>";
			}
			else if($cur_col!=0)
			{
				//echo $this->divider.$this->shielder.$this->shielder.$this->divider;
			}

			$c = GetCostPos($nxt[0], $this->cost_id);
			if($c==0)	continue;
			if(($this->view_proizv)&&($nxt[3])) $pr=" (".$nxt[3].")"; else $pr="";
			echo "<td>".$nxt[1].$pr."</td><td>".$c."</td>";

 			$this->line++;
 			$i=1-$i;
 			$cur_col++;
		}
		echo"</tr>";
	}
}

$ceni=NULL;
if($uid)
	$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='-1'");
else
	$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='1'");
$c_cena_id=@mysql_result($res,0,0);
if(!$c_cena_id)	$c_cena_id=1;

if($mode=="")
{
	$tmpl->SetTitle("Прайс-лист");
	$tmpl->AddText("<h1 id='page-title'>Прайс-лист</h1><div id='page-info'>Формирование прайс-листа по вашим требованиям</div>
	Для тех, кому не удобно просматривать товары в режиме онлайн, сделана возможность сформировать прайс - лист. Специально для Вас мы сделали возможность получить прайс-лист в наиболее удобном для Вас формате. Сейчас доступны <a class='wiki' href='/price.php?mode=gen&amp;f=pdf'>PDF</a>, <a class='wiki' href='/price.php?mode=gen&amp;f=csv'>CSV</a>, <a class='wiki' href='/price.php?mode=gen&amp;f=html'>HTML</a> и <a class='wiki' href='/price.php?mode=gen&amp;f=xls'>XLS</a> форматы. В ближайшее время планируется реализовать ODF. Для получения прайса выберите формат:<br>
	<ul>
	<li><a class='wiki' href='/price.php?mode=gen&amp;f=pdf'>Прайс-лист в формате pdf</a> (для просмотра и печати в программах Foxit reader, Adobe reader, Okular, и <a class='wiki_ext' href='http://pdfreaders.org/'>другие</a>...)</li>
	<li><a class='wiki' href='/price.php?mode=gen&amp;f=csv'>Прайс-лист в формате csv</a>  (для просмотра в текстоовых редакторах, Openoffice Calc и Microsoft office Excel)</li>
	<li><a class='wiki' href='/price.php?mode=gen&amp;f=html'>Прайс-лист в формате html</a> (для просмотра в любом html броузере: Mozilla, Opera, Internet explorer)</li>
	<li><a class='wiki' href='/price.php?mode=gen&amp;f=xls'>Прайс-лист в формате xls</a> (для просмотра в табличных редакторах Microsoft office Excel, Openoffice Calc, и подобных)</li>
	<li style='color: #f00;'>Если не знаете, что именно Вам выбрать - выбирайте <a class='wiki' href='/price.php?mode=gen&amp;f=html'>Прайс-лист в формате html</a>!</li>
	</ul>");
}
else if($mode=="gen")
{
	$tmpl->SetTitle("Прайс-лист: задание параметров");

	if($f=="csv")
	{
		$tmpl->AddText("<h1 id='page-title'>Загрузка прайс - листа</h1><div id='page-info'>Используется csv формат</div>
		В файле содержится электронная таблица. Формат удобен для случаев, когда Вам необходимо что-либо изменить в полученном прайсе, или если Вам привычнее пользоваться табличным редактором.<br>
		Загруженный файл можно будет открыть при помощи:
		<ul>
		<li>OpenOffice Calc (рекомендуется, <a class='wiki_ext' href='http://download.openoffice.org' rel='nofollow'>скачать программу</a>)</li>
		<li>Microsoft office Excel</li>
		</ul>
		Внимание! Редактор Microsoft office Excel требует для правильного открытия таких файлов указать кодировку UTF-8!<br>
		<br>
		<form action='price.php' method='post'>
		<input type='hidden' name='mode' value='get'>
		<input type='hidden' name='f' value='$f'>
		<table width='100%'>
		<tr>
		<th>Количество колонок:
		<th>Разделитель:
		<th>Ограничитель текста:
		<th>Дополнительные параметры

		<tr class=lin0>
		<td><select name='kol'><option value='1'>1</option><option selected value='2'>2</option>
		<option value='3'>3</option><option selected value='4'>4</option></select><br>

		<td><select name='razd'><option value='1'>,</option><option selected value='2'>;</option>
		<option value='3'>:</option></select><br>

		<td><select name='ogr'><option value='1'>'</option><option selected value='2'>\"</option>
		<option value='3'>*</option></select><br>
		<td><input type='checkbox' name='proizv' value='1' checked> - Указать производителя<br>
		</table><br>");
		GroupSelBlock();
		$tmpl->AddText("<div style='color: #f00; text-align: center;'>Если не знаете, какие параметры выбрать - просто нажмите кнопку *Загрузить прайс-лист*!<br>
		<span style='color: #090'>Внимание! Для вывода прайс листа на печать рекомендуется использовать <a class='wiki' href='?mode=gen&f=pdf'>формат PDF</a>!</span><br>
		<button type='submit'>Загрузить прайс-лист!</button>
		</div>
		</div></form>");
	}
	else if($f=="html")
	{
		$ag=getenv("HTTP_USER_AGENT");
		$link['opera']="<a class='wiki_ext' href='http://opera.com' rel='nofollow'>скачать здесь</a>";
		$link['mozilla']="<a class='wiki_ext' href='http://mozilla.com' rel='nofollow'>скачать здесь</a>";
		$link['ie']="не рекомендуется";

		if(stripos(' '.$ag,'opera'))
			$link['opera']='<span style="color: #090">Используется Вами в данный момент</span>';
		else if(stripos(' '.$ag,'MSIE'))
			$link['ie'].=', <span style="color: #090">Используется Вами в данный момент</span>';
		else if(stripos(' '.$ag,'mozilla'))
			$link['mozilla']='<span style="color: #090">Используется Вами в данный момент</span>';
		else
			$link['other']='<span style="color: #090">Используется Вами в данный момент</span>';
		foreach($link as $id => $l)
		{
			if($l)	$link[$id]='('.$l.')';
		}

		$tmpl->AddText("<h2 id='page-title'>Загрузка прайс - листа</h2><div id='page-info'>Используется HTML формат</div>
		Прайс в виде обычной веб-страницы. Для просмотра можно использовать обычные веб броузеры, например:
		<ul>
		<li>Opera {$link['opera']}</li>
		<li>Mozilla, Fierfox {$link['mozilla']}</li>
		<li>Microsoft Internrt Exploerer {$link['ie']}</li>
		<li>Любую другую прграмму просмотра сайтов {$link['other']}</li>
		</ul>
		<br>

		<form action='price.php' method='post'>
		<input type=hidden name=mode value=get>
		<input type=hidden name=f value=$f>

		<table width='100%'>
		<tr>
		<th>Количество колонок
		<th>Количество строк на \"странице\"
		<th>Дополнительные параметры

		<tr class=lin0>
		<td><select name=kol><option value=1>1</option><option selected value=2>2</option>
		<option value=3>3</option><option selected value=4>4</option>
		<option value=5>5</option><option value=6>6</option></select><br>

		<td><input type=text name=str value=50>
		<td><input type=checkbox name=proizv value=1 checked> - Указать производителя<br>
		</table>

		<div style='color: #f00; text-align: center;'>Если не знаете, какие параметры выбрать - просто нажмите кнопку *Загрузить прайс-лист*!<br><span style='color: #090'>Внимание! Для вывода прайс листа на печать рекомендуется использовать <a class='wiki' href='?mode=gen&f=pdf'>формат PDF</a>!</span><br>
		<button type='submit'>Загрузить прайс-лист!</button>
		</div>
		</div>
		</form>");

	}
	else if($f=="pdf")
	{
		$ag=getenv("HTTP_USER_AGENT");
		$list='';

		if(!stripos(' '.$ag,'Windows'))
			$list.="<li><a class='wiki_ext' href='http://okular.kde.org/' rel='nofollow'>Okular (KPDF)</a> (рекомендуется)</li><li>Adobe reader</li><li>KGhostView</li>";
		if(!stripos(' '.$ag,'Linux'))
			$list.="<li><a class='wiki_ext' href='http://www.foxitsoftware.com/pdf/reader/' rel='nofollow'>Foxit reader</a> (рекомендуется)</li><li><a class='wiki_ext' herf='http://get.adobe.com/reader/' rel='nofollow'>Adobe reader</a></li><li>Djvu reader</li>";

		$list.="<li><a class='wiki_ext' href='http://pdfreaders.org/'>Другие</a></li>";



		$tmpl->AddText("<h2 id='page-title'>Загрузка прайс - листа</h2><div id='page-info'>Используется PDF формат</div>");
		$tmpl->AddText("
		Идеальный формат для вывода на печать. Для просмотра можно использовать любые PDF просмотрщики, например:
		<ul>$list</ul>
		<br>

		<form action='price.php' method='get'>
		<input type=hidden name=mode value=get>
		<input type=hidden name=f value=$f>

		<table width='100%'>
		<tr>
		<th>Дополнительные параметры

		<tr class=lin0>
		<td><label><input type=checkbox name=proizv value=1 checked> Указать производителя</label></table>");

		GroupSelBlock();

		$tmpl->AddText("

		<div style='color: #f00;'>Если не знаете, какие параметры выбрать - просто нажмите кнопку *Загрузить прайс-лист*!<br>
		<button type='submit'>Загрузить прайс-лист!</button>
		</div>
		</form>");

	}
	else if($f=='xls')
	{

		$tmpl->AddText("<h2 id='page-title'>Загрузка прайс - листа</h2><div id='page-info'>Используется xls формат</div>
		В файле содержится электронная таблица Microsoft Excel. Формат удобен только для пользователей этой программы, желающих вносить изменения в прайс. Для просмотра и печати рекомендуется <a class='wiki' href='?mode=gen&f=pdf'>формат PDF</a>.<br>
		Загруженный файл можно будет открыть при помощи:
		<ul>
		<li>Microsoft office Excel (рекомендуется)</li>
		<li>OpenOffice Calc (<a class='wiki_ext' href='http://download.openoffice.org' rel='nofollow'>скачать программу</a>)</li>

		</ul>
		<br>
		<form action='price.php' method='post'>
		<input type=hidden name=mode value=get>
		<input type=hidden name=f value=$f>
		<table width='100%'>
		<tr>
		<th>Дополнительные параметры

		<tr class=lin0>
		<td><label><input type='checkbox' name='proizv' value='1' checked> Указать производителя</label><br>
		<label><input type='checkbox' name='nal' value='1' disabled> Указать наличие</label><br>
		</table><br>");
		GroupSelBlock();
		$tmpl->AddText("<div style='color: #f00; text-align: center;'>Если не знаете, какие параметры выбрать - просто нажмите кнопку *Загрузить прайс-лист*!<br>
		<span style='color: #090'>Внимание! Для вывода прайс листа на печать рекомендуется использовать <a class='wiki' href='?mode=gen&f=pdf'>формат PDF</a>!</span><br>
		<button type='submit'>Загрузить прайс-лист!</button>
		</div>
		</div></form>");


	}
	else $tmlp->msg("Извините, но данный формат не поддрерживается! Возможно, его поддержка будет реализована позднее!","err");
}
else if($mode=="get")
{
	$tmpl->ajax=1;
	global $cena;
	global $CONFIG;
	//$cena=rcv('cena');
	//if($cena<=0) $cena=1;
	$cena=$c_cena_id;
	$tdata="";

	if($f=='pdf') //PDF
	{
		define('FPDF_FONT_PATH','/var/www/gate/fpdf/font/');
		require('fpdf/fpdf_mysql.php');

		$pdf=new PDF_MySQL_Table();
		$pdf->Open();
		$pdf->SetAutoPageBreak(1,12);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=5;
		$pdf->AddPage();


		if(@$CONFIG['site']['doc_header'])
		{
			$header_img=str_replace('{FN}', $CONFIG['site']['default_firm'], $CONFIG['site']['doc_header']);
			$pdf->Image($header_img,8,10, 190);
			$pdf->Sety(54);
		}

		$i=0;
		if(is_array($CONFIG['site']['price_text']))
		foreach($CONFIG['site']['price_text'] as $text)
		{
			$pdf->SetFont('Arial','',20-($i*4));
			$str = iconv('UTF-8', 'windows-1251', $text);
			$pdf->Cell(0,7-$i,$str,0,1,'C');
			$i++;
			if($i>4) $i=4;
		}

		$pdf->SetTextColor(0,0,255);
		$pdf->SetFont('','U');
		$pdf->SetFont('Arial','U',14);
		$str = 'Прайс загружен с сайта http://'.$CONFIG['site']['name'];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,6,$str,0,1,'C',0,'http://'.$CONFIG['site']['name']);
		$pdf->SetFont('','',10);
		$pdf->SetTextColor(0);
//		$str = 'При заказе через сайт предоставляется скидка!';
//		$str = iconv('UTF-8', 'windows-1251', $str);
//		$pdf->Cell(0,5,$str,0,1,'C');

		$dt=date("d.m.Y");
		$str = 'Цены действительны на дату: '.$dt.'. Цены, выделенные серым цветом, необходимо уточнять.';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,4,$str,0,1,'C');

		if(rcv('gs'))
		{
			$pdf->Ln(3);
			$pdf->SetFont('','',14);
			$pdf->SetTextColor(255,24,24);
			$str = 'Прайс содержит неполный список позиций, в соответствии с выбранными критериями при его загрузке с сайта.';
			$str = iconv('UTF-8', 'windows-1251', $str);
			$pdf->MultiCell(0,4,$str,0,'C');
		}
		$pdf->Ln(6);

		$pdf->SetTextColor(0);


		global $CONFIG;
		if(!$CONFIG['site']['price_col_cnt'])		$CONFIG['site']['price_col_cnt']=2;
		if(!$CONFIG['site']['price_width_cost'])	$CONFIG['site']['price_width_cost']=16;
		if(!$CONFIG['site']['price_width_name'])
		{
			$CONFIG['site']['price_width_name']=(194-$CONFIG['site']['price_width_cost']*$CONFIG['site']['price_col_cnt']-$CONFIG['site']['price_col_cnt']*2)/$CONFIG['site']['price_col_cnt'];
			settype($CONFIG['site']['price_width_name'],'int');
		}

		$pdf->numCols=$CONFIG['site']['price_col_cnt'];


		//$pdf->Sety(90);
		$str = iconv('UTF-8', 'windows-1251', 'Наименование');
		$pdf->AddCol('name',$CONFIG['site']['price_width_name'], $str,'');
		$str = iconv('UTF-8', 'windows-1251', 'Цена');
		$pdf->AddCol('cost',$CONFIG['site']['price_width_cost'],$str,'R');
		$prop=array('HeaderColor'=>array(255,150,100),
				'color1'=>array(210,245,255),
				'color2'=>array(255,255,210),
				'padding'=>1,
				'cost_id'=>$c_cena_id);
		if(rcv('gs'))
		{
			$prop['groups']=@$_GET['g'];
		}



		if($proizv) $proizv='`doc_base`.`proizv`';
		else $proizv="''";

		$pdf->Table("SELECT `doc_base`.`name`, $proizv, `doc_base`.`id` AS `pos_id` , `doc_base`.`cost_date`, `class_unit`.`rus_name1` AS `units_name`
		FROM `doc_base`
		LEFT JOIN `class_unit` ON `class_unit`.`id`=`doc_base`.`unit`
		",$prop);




		$pdf->Output();

	}
	else if($f=="csv")
	{
		$kol=rcv('kol');
		$razd=rcv('razd');
		$ogr=rcv('ogr');
		$proizv=rcv('proizv');
		$cena=rcv('cena');
		if($razd==1) $razd=","; else if($razd==3) $razd=":"; else $razd=";";
		if($ogr==1) $ogr="'"; else if($ogr==3) $ogr="*"; else $ogr="\"";

		$price=new PriceWriterCSV($razd,$ogr);
		if(rcv('gs')&&is_array($_POST['g']))
		{
			$price->SetViewGroups($_POST['g']);
		}
		$price->ViewProizv($proizv);
		$price->SetCost($c_cena_id);
		$price->SetColCount($kol);
		$price->Open();
		$price->write();
		$price->close();
		exit();
	}
	else if($f=='xls')
	{
		$proizv=rcv('proizv');
		$nal=rcv('nal');
		$price=new PriceWriterXLS();
		if(rcv('gs')&&is_array($_POST['g']))
		{
			$price->SetViewGroups($_POST['g']);
		}
		$price->ViewProizv($proizv);
		$price->Open();
		$price->write();
		$price->close();
		exit();
	}
	else if($f=="html")
	{
		$kol=rcv('kol');
		$str=rcv('str');
		$zip=rcv('zip');
		$cena=rcv('cena');

		$proizv=rcv('proizv');
		$price=new PriceWriterHTML();
		if(rcv('gs')&&is_array($_POST['g']))
		{
			$price->SetViewGroups($_POST['g']);
		}
		$price->ViewProizv($proizv);
		$price->SetCost($c_cena_id);
		$price->SetColCount($kol);
		$price->Open();
		$price->write();
		$price->close();
		exit();
	}
	else msg("Извините, но данный формат не поддрерживается! Возможно, его поддержка будет реализована позднее!","err");



}
$tmpl->Write();

?>
