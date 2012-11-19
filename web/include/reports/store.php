<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2012, BlackLight, TND Team, http://tndproject.org
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


class Report_Store extends BaseGSReport
{
	function getName($short=0)
	{
		if($short)	return "Остатки на складе";
		else		return "Остатки товара на складе";
	}
	

	function Form()
	{
		global $tmpl;
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='store'>
		<fieldset><legend>Отобразить цены</legend>");
		$res=mysql_query("SELECT `id`, `name` FROM `doc_cost` ORDER BY `id`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список цен");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<label><input type='checkbox' name='cost[$nxt[0]]' value='$nxt[0]'>$nxt[1]</label><br>");
		}
		$tmpl->AddText("</fieldset><br>
		<fieldset><legend>Показывать</legend>
		<label><input type='checkbox' name='show_price' value='1'>Цены</label><br>
		<label><input type='checkbox' name='show_add' value='1'>Наценку</label><br>
		<label><input type='checkbox' name='show_sum' value='1'>Суммы</label><br>
		<label><input type='checkbox' name='show_mincnt' value='1'>Минимально допустимый остаток</label>
		</fieldset><br>
		Склад:<br>
		<select name='sklad'>
		<option value='0'>--не задан--</option>");
		$res=mysql_query("SELECT `id`, `name` FROM `doc_sklady` ORDER BY id");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список складов");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");
		}
		$tmpl->AddText("</select><br>
		Группа товаров:<br>");
		$this->GroupSelBlock();
		$tmpl->AddText("Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Создать отчет</button></form>");	
	}
	
	function MakeHTML()
	{
		global $tmpl, $CONFIG;
		$gs=rcv('gs');
		$show_price=	rcv('show_price');
		$show_add=	rcv('show_add');
		$show_sum=	rcv('show_sum');
		$show_mincnt=	rcv('show_mincnt');
		$sklad=		rcv('sklad');
		$g=@$_POST['g'];
		$cost=@$_POST['cost'];
		$tmpl->LoadTemplate('print');
		switch(@$CONFIG['doc']['sklad_default_order'])
		{
			case 'vc':	$order='`doc_base`.`vc`';	break;
			case 'cost':	$order='`doc_base`.`cost`';	break;
			default:	$order='`doc_base`.`name`';
		}
		if($sklad)
		{
			$res=mysql_query("SELECT `name` FROM `doc_sklady` WHERE `id`='$sklad'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить наименование склада");
			if(mysql_num_rows($res)<1)	throw new Exception("Склад не найден!");
			list($sklad_name)=mysql_fetch_row($res);
			$tmpl->SetText("<h1>Остатки товара на складе N{$sklad} ($sklad_name) на текущий момент (".date("Y-m-d H:i:s").")</h1>");
		}
		else		$tmpl->SetText("<h1>Остатки товара суммарно по всем складам на текущий момент (".date("Y-m-d H:i:s").")</h1>");
		$tmpl->AddText("<table width=100%><tr><th>N");
		$col_count=1;
		if($CONFIG['poseditor']['vc'])
		{
			$tmpl->AddText("<th>Код");
			$col_count++;
		}
		$tmpl->AddText("<th>Наименование<th>Количество");
		$col_count+=2;
		
		if($show_mincnt)
		{
			$tmpl->AddText("<th>Мин.Кол-во");
			$col_count++;
		}
		
		if($show_price)
		{
			$tmpl->AddText("<th>Актуальная цена<br>поступления<th>Базовая цена");
			$col_count+=2;
		}
		
		if($show_add)
		{
			$tmpl->AddText("<th>Наценка");
			$col_count++;
		}
		
		if($show_sum)
		{
			$tmpl->AddText("<th>Сумма по АЦП<th>Сумма по базовой");
			$col_count+=2;
		}
		
		if(is_array($cost))
		{
			$res=mysql_query("SELECT `id`, `name` FROM `doc_cost` ORDER BY `name`");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список цен");
			$costs=array();
			while($nxt=mysql_fetch_row($res))	$costs[$nxt[0]]=$nxt[1];
			foreach($cost as $id => $value)
			{
				$tmpl->AddText("<th>".$costs[$id]);
				$col_count++;
			}
		}
		
		if($sklad)
		{
			$cnt_field="`doc_base_cnt`.`cnt`";
			$cnt_join="INNER JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'";
			if($show_mincnt)	$cnt_field.=", `doc_base_cnt`.`mincnt`";
			
		}
		else
		{
			$cnt_field="(SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `cnt`";
			if($show_mincnt)	$cnt_field.=", (SELECT SUM(`mincnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `mincnt`";
			$cnt_join='';
		}
		$sum=$bsum=$summass=0;
		$res_group=mysql_query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
		while($group_line=mysql_fetch_assoc($res_group))
		{
			if($gs && is_array($g))
				if(!in_array($group_line['id'],$g))	continue;
			
			$tmpl->AddText("<tr><td colspan='$col_count' class='m1'>{$group_line['id']}. {$group_line['name']}</td></tr>");
		
		
			$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost`, {$cnt_field}, `doc_base_dop`.`mass`, `doc_base`.`vc`
			FROM `doc_base`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			$cnt_join
			WHERE `doc_base`.`group`='{$group_line['id']}'
			ORDER BY $order");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список наименований");
			
			while($nxt=mysql_fetch_array($res))
			{
				if($nxt['cnt']==0 && (!$show_mincnt)) continue;
				if($nxt['cnt']<0) $nxt['cnt']='<b>'.$nxt['cnt'].'</b>';
				$tmpl->AddText("<tr><td>{$nxt['id']}");
				if($CONFIG['poseditor']['vc'])		$tmpl->AddText("<td>{$nxt['vc']}");

				$tmpl->AddText("<td>{$nxt['name']}<td>{$nxt['cnt']}");
				
				if($show_mincnt)	$tmpl->AddText("<td>{$nxt['mincnt']}");
				
				if($show_price || $show_sum || $show_add)
				{
					$act_cost=sprintf('%0.2f',GetInCost($nxt['id']));
					$cost_p=sprintf("%0.2f",$nxt['cost']);
					if($show_price)
						$tmpl->AddText("<td>$act_cost р.<td>$cost_p р.");
				}
				
				if($show_add)
				{
					$nac=sprintf("%0.2f р. (%0.2f%%)",$cost_p-$act_cost,($cost_p/$act_cost)*100-100);
					$tmpl->AddText("<td>$nac");
				}
				
				
				if($show_sum)
				{
					$sum_p=sprintf("%0.2f",$act_cost*$nxt['cnt']);
					$bsum_p=sprintf("%0.2f",$nxt['cost']*$nxt['cnt']);
					$sum+=$act_cost*$nxt['cnt'];
					$bsum+=$nxt['cost']*$nxt['cnt'];
					$tmpl->AddText("<td>$sum_p р.<td>$bsum_p р.");
				}
				
				$summass+=$nxt['cnt']*$nxt['mass'];
				
				if(is_array($cost))
				{
					foreach($cost as $id => $value)
					{
						$tmpl->AddText("<td>".GetCostPos($nxt['id'], $id));
					}
				}
			}
		}
		$col_count=3;
		if($show_price)	$col_count+=2;
		if($show_add)	$col_count++;
		if($show_sum)		$tmpl->AddText("<tr><td colspan='$col_count'><b>Итого:</b><td>$sum р.<td>$bsum р.");
		$tmpl->AddText("</table><h3>Общая масса склада: $summass кг.</h3>");
	}
	
	function MakePDF()
	{
		global $tmpl, $CONFIG;
		ob_start();
		define('FPDF_FONT_PATH',$CONFIG['site']['location'].'/fpdf/font/');
		require('fpdf/fpdf_mc.php');		
		
		$pdf=new PDF_MC_Table('P');
		$pdf->Open();
		$pdf->AddFont('Arial','','arial.php');
		$pdf->SetMargins(6, 6);
		$pdf->SetAutoPageBreak(true,6);
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);
		
		$gs=rcv('gs');
		$show_price=	rcv('show_price');
		$show_add=	rcv('show_add');
		$show_sum=	rcv('show_sum');
		$show_mincnt=	rcv('show_mincnt');
		$sklad=		rcv('sklad');
		$g=@$_POST['g'];
		$cost=@$_POST['cost'];
		$tmpl->LoadTemplate('print');
		switch(@$CONFIG['doc']['sklad_default_order'])
		{
			case 'vc':	$order='`doc_base`.`vc`';	break;
			case 'cost':	$order='`doc_base`.`cost`';	break;
			default:$order='`doc_base`.`name`';
		}
		
		$headers=array('N');		
		$haligns=array('C');
		$aligns=array('L');
		$col_sizes=array(10);
		if($CONFIG['poseditor']['vc'])	
		{
			$headers[]='Код';
			$haligns[]='C';
			$aligns[]='L';
			$col_sizes[]=15;
		}
		$headers[]='Наименование';
		$headers[]='Кол-во';
		$haligns[]='C';
		$haligns[]='C';
		$aligns[]='L';
		$aligns[]='R';
		$col_sizes[]=100;
		$col_sizes[]=10;
		if($show_mincnt)
		{
			$headers[]='Мин.кол-во';
			$haligns[]='R';
			$col_sizes[]=10;
		}
		if($show_price)
		{
			$headers[]='АЦП';
			$headers[]='Базовая цена';
			$haligns[]='C';
			$haligns[]='C';
			$aligns[]='R';
			$aligns[]='R';
			$col_sizes[]=18;
			$col_sizes[]=18;
		}
		if($show_add)
		{
			$headers[]='Наценка';
			$haligns[]='C';
			$aligns[]='R';
			$col_sizes[]=15;
		}
		if($show_sum)
		{
			$headers[]='Сумма по АЦП';
			$headers[]='Сумма по базовой';
			$haligns[]='C';
			$haligns[]='C';
			$aligns[]='R';
			$aligns[]='R';
			$col_sizes[]=18;
			$col_sizes[]=18;
		}
		if(is_array($cost))
		{
			$res=mysql_query("SELECT `id`, `name` FROM `doc_cost` ORDER BY `name`");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список цен");
			$costs=array();
			while($nxt=mysql_fetch_row($res))	$costs[$nxt[0]]=$nxt[1];
			foreach($cost as $id => $value)
			{
				$headers[]=$costs[$id];
				$haligns[]='C';
				$aligns[]='R';
				$col_sizes[]=18;
			}
		}
		
		$col_count=count($headers);
// 		$col_sizes=array();
// 		$width=0;
// 		foreach($headers as $col)
// 		{
// 			$width+=$col_sizes[]=$pdf->GetStringWidth($col)+1;
// 		}
// 		$col_sizes[0]+=10;
// 		$width+=10;
		$width=array_sum($col_sizes);
		if($width<200)	
		{
			$multipler=200/$width;
			$pdf->AddPage('P');
		}
		else
		{
			$pdf->AddPage('L');
			$multipler=285/$width;
		}
		
		foreach($col_sizes as $id => $size)
		{
			$col_sizes[$id]=round($size*$multipler,1);
		}
				
		if($sklad)
		{
			$res=mysql_query("SELECT `name` FROM `doc_sklady` WHERE `id`='$sklad'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить наименование склада");
			if(mysql_num_rows($res)<1)	throw new Exception("Склад не найден!");
			list($sklad_name)=mysql_fetch_row($res);
			$text="Остатки товара на складе N{$sklad} ($sklad_name) на текущий момент (".date("Y-m-d H:i:s").")";
		}
		else	$text="Остатки товара суммарно по всем складам на текущий момент (".date("Y-m-d H:i:s").")";
		
		$str = iconv('UTF-8', 'windows-1251', $text);
		$pdf->Cell(0,5,$str,0,1,'C');

		$pdf->SetAligns($haligns);
		$pdf->SetWidths($col_sizes);
		$pdf->SetHeight(4);
		$pdf->SetLineWidth(0.3);
		$pdf->RowIconv($headers);
		$pdf->SetLineWidth(0.1);
		$pdf->SetAligns($aligns);
		$pdf->SetFont('','',8);
		
		$all_size=array_sum($col_sizes);
		
		if($sklad)
		{
			$cnt_field="`doc_base_cnt`.`cnt`";
			$cnt_join="INNER JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$sklad'";
			if($show_mincnt)	$cnt_field.=", `doc_base_cnt`.`mincnt`";
			
		}
		else
		{
			$cnt_field="(SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `cnt`";
			if($show_mincnt)	$cnt_field.=", (SELECT SUM(`mincnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `mincnt`";
			$cnt_join='';
		}
		
		$sum=$bsum=$summass=0;
		$res_group=mysql_query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
		while($group_line=mysql_fetch_assoc($res_group))
		{
			if($gs && is_array($g))
				if(!in_array($group_line['id'],$g))	continue;
			$pdf->SetFillColor(192);
			$str = iconv('UTF-8', 'windows-1251', "{$group_line['id']}. {$group_line['name']}");
			$pdf->Cell($all_size,5,$str,1,1,'L',1);
			$pdf->SetFillColor(255);
		
		
			$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost`, {$cnt_field}, `doc_base_dop`.`mass`, `doc_base`.`vc`
			FROM `doc_base`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			$cnt_join
			WHERE `doc_base`.`group`='{$group_line['id']}'
			ORDER BY $order");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список наименований");
			
			while($nxt=mysql_fetch_array($res))
			{
				if($nxt[3]==0 && (!$show_mincnt)) continue;
				
				$line=array($nxt[0]);
				if($CONFIG['poseditor']['vc'])		$line[]=$nxt['vc'];
				$line[]=$nxt[1];
				$line[]=$nxt[3];
				if($show_mincnt)
				{
					$line[]=$nxt['mincnt'];
				}
				if($show_price || $show_sum || $show_add)
				{
					$act_cost=sprintf('%0.2f',GetInCost($nxt[0]));
					$cost_p=sprintf("%0.2f",$nxt[2]);
					if($show_price)
					{
						$line[]=$act_cost;
						$line[]=$cost_p;
					}
				}
				
				if($show_add)
				{
					$line[]=sprintf("%0.2f р. (%0.2f%%)",$cost_p-$act_cost,($cost_p/$act_cost)*100-100);					
				}
				
				
				if($show_sum)
				{
					$sum_p=sprintf("%0.2f",$act_cost*$nxt[3]);
					$bsum_p=sprintf("%0.2f",$nxt[2]*$nxt[3]);
					$sum+=$act_cost*$nxt[3];
					$bsum+=$nxt[2]*$nxt[3];
					$line[]=$sum_p;
					$line[]=$bsum_p;
				}
				
				$summass+=$nxt[3]*$nxt['mass'];
				
				if(is_array($cost))
				{
					foreach($cost as $id => $value)
					{
						$line[]=GetCostPos($nxt[0], $id);
					}
				}
				$pdf->RowIconv($line);
			}
		}
// 		$col_count=3;
// 		if($show_price)	$col_count+=2;
// 		if($show_add)	$col_count++;
// 		if($show_sum)		$tmpl->AddText("<tr><td colspan='$col_count'><b>Итого:</b><td>$sum р.<td>$bsum р.");
// 		$tmpl->AddText("</table><h3>Общая масса склада: $summass кг.</h3>");

		$pdf->Output('store_report.pdf','I');
	}
	
	function Run($opt)
	{
		if($opt=='')		$this->Form();
		else if($opt=='pdf')	$this->MakePDF();
		else			$this->MakeHTML();	
	}
};

?>

