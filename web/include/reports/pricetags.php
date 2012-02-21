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


class Report_PriceTags
{
	var $templates;
	
	function __construct()
	{
		$this->templates=array();
		
		$this->templates[]=array(
		'name'		=> 'Миниатюрные наклейки',
		'width'		=> 27,
		'height'	=> 18,
		'ident'		=>1,
		'caption_fontsize'=>0,
		'vc_fontsize'	=> 0,
		'name_left'	=> 0,
		'name_top'	=> 1,
		'name_width'	=> 0,
		'name_lheight'	=> 2.2,
		'name_fontsize'	=> 7,
		'name_align'	=> 'C',
		'price_left'	=> 0,
		'price_top'	=> 14,
		'price_width'	=> 0,
		'price_lheight'	=> 4,
		'price_fontsize'=> 9,
		'price_align'	=> 'R',
		'vendor_fontsize'=> 0,
		'country_fontsize'=> 0
		);
		
		$this->templates[]=array(
		'name'		=> 'Стандартный ценник',
		'width'		=> 48,
		'height'	=> 55,
		'ident'		=>2,
		'caption_height'=>5,
		'caption_fontsize'=>7,
		'vc_left'	=> 0,
		'vc_top'	=> 5,
		'vc_width'	=> 0,
		'vc_lheight'	=> 5,
		'vc_fontsize'	=> 8,
		'vc_align'	=> 'L',
		'name_left'	=> 0,
		'name_top'	=> 10,
		'name_width'	=> 0,
		'name_lheight'	=> 6,
		'name_fontsize'	=> 14,
		'name_align'	=> 'C',
		'price_left'	=> 0,
		'price_top'	=> 43,
		'price_width'	=> 0,
		'price_lheight'	=> 7,
		'price_fontsize'=> 15,
		'price_align'	=> 'C',
		'vendor_left'	=> 0,
		'vendor_top'	=> 50,
		'vendor_width'	=> 0,
		'vendor_lheight'=> 7,
		'vendor_fontsize'=> 5,
		'vendor_align'	=> 'R',
		'country_left'	=> 0,
		'country_top'	=> 50,
		'country_width'	=> 0,
		'country_lheight'=> 7,
		'country_fontsize'=> 5,
		'country_align'	=> 'L'
		);

		$this->templates[]=array(
		'name'		=> 'Увеличенный ценник',
		'width'		=> 64,
		'height'	=> 69,
		'ident'		=>2,
		'caption_height'=>6,
		'caption_fontsize'=>10,
		'vc_left'	=> 0,
		'vc_top'	=> 9,
		'vc_width'	=> 0,
		'vc_lheight'	=> 8,
		'vc_fontsize'	=> 26,
		'vc_align'	=> 'C',
		'name_left'	=> 0,
		'name_top'	=> 27,
		'name_width'	=> 0,
		'name_lheight'	=> 6,
		'name_fontsize'	=> 16,
		'name_align'	=> 'C',
		'price_left'	=> 0,
		'price_top'	=> 56,
		'price_width'	=> 0,
		'price_lheight'	=> 7,
		'price_fontsize'=> 24,
		'price_align'	=> 'C',
		'vendor_left'	=> 0,
		'vendor_top'	=> 65,
		'vendor_width'	=> 0,
		'vendor_lheight'=> 4,
		'vendor_fontsize'=> 5,
		'vendor_align'	=> 'R',
		'country_left'	=> 0,
		'country_top'	=> 65,
		'country_width'	=> 0,
		'country_lheight'=> 4,
		'country_fontsize'=> 5,
		'country_align'	=> 'L'
		);
		
		$this->templates[]=array(
		'name'		=> 'Большой (для крупногабаритного товара)',
		'width'		=> 98,
		'height'	=> 55,
		'ident'		=>2,
		'caption_height'=>6,
		'caption_fontsize'=>10,
		'vc_left'	=> 0,
		'vc_top'	=> 7,
		'vc_width'	=> 0,
		'vc_lheight'	=> 5,
		'vc_fontsize'	=> 12,
		'vc_align'	=> 'L',
		'name_left'	=> 0,
		'name_top'	=> 12,
		'name_width'	=> 0,
		'name_lheight'	=> 6,
		'name_fontsize'	=> 18,
		'name_align'	=> 'C',
		'price_left'	=> 0,
		'price_top'	=> 40,
		'price_width'	=> 0,
		'price_lheight'	=> 10,
		'price_fontsize'=> 30,
		'price_align'	=> 'C',
		'vendor_left'	=> 0,
		'vendor_top'	=> 50,
		'vendor_width'	=> 0,
		'vendor_lheight'=> 5,
		'vendor_fontsize'=> 10,
		'vendor_align'	=> 'R',
		'country_left'	=> 0,
		'country_top'	=> 50,
		'country_width'	=> 0,
		'country_lheight'=> 5,
		'country_fontsize'=> 10,
		'country_align'	=> 'L'
		);
	}
	
	function getName($short=0)
	{
		if($short)	return "Ценники";
		else		return "Печать ценников";
	}
	
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
			$tmp=$this->draw_groups_tree($nxt[0]); // рекурсия
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
		".$this->draw_groups_tree(0)."</ul></div>");
	}
	
	function drawPDFPriceTag($pdf, $template, $pos_id, $cost_id)
	{
		global $CONFIG;
		$res=mysql_query("SELECT `doc_base`.`id`, CONCAT(`doc_group`.`printname`, ' ', `doc_base`.`name`) AS `name`, `doc_base`.`vc`, `doc_base`.`proizv` AS `vendor`, `class_country`.`name` AS `country` FROM `doc_base`
		LEFT JOIN `doc_group` ON `doc_base`.`group`=`doc_group`.`id`
		LEFT JOIN `class_country` ON `doc_base`.`country`=`class_country`.`id`
		WHERE `doc_base`.`id`='$pos_id'");
		if(mysql_errno())		throw new MysqlException("Не удалось получить данные наименования");
		if(mysql_num_rows($res)==0)	throw new Exception("Наименование не найдено!");
		$pos_info=mysql_fetch_assoc($res);
		$pos_info['price']=GetCostPos($pos_id, $cost_id);
		
		if(!$template['vc_width'])	$template['vc_width']=$template['width'];
		if(!$template['name_width'])	$template['name_width']=$template['width'];
		if(!$template['price_width'])	$template['price_width']=$template['width'];
		if(!$template['vendor_width'])	$template['vendor_width']=$template['width'];
		if(!$template['country_width'])	$template['country_width']=$template['width'];
		
		$x=$pdf->getX();
		$y=$pdf->getY();
		$pdf->Rect($x, $y, $template['width'], $template['height']);
		
		$caption=$CONFIG['site']['display_name'];
		if(!$caption)	$CONFIG['site']['name'];
		
		if($template['caption_fontsize'] && $caption)
		{
			$pdf->SetFillColor(80);
			$pdf->SetTextColor(255);
			$pdf->SetFont('Arial','',$template['caption_fontsize']);
			$str = iconv('UTF-8', 'windows-1251', $caption);		
			$pdf->Cell($template['width'], $template['caption_height'], $str, 0, 0, 'C', true);
		}
		
		$pdf->SetTextColor(0);
		
		$pdf->SetFont('','',$template['name_fontsize']);
		$pdf->SetXY($x+$template['name_left'], $y+$template['name_top']);
		$str = iconv('UTF-8', 'windows-1251', $pos_info['name']);		
		$pdf->MultiCell($template['name_width'], $template['name_lheight'], $str, 0, $template['name_align']);

		if($template['vc_fontsize'] && $pos_info['vc'])
		{
			$pdf->SetFont('','',$template['vc_fontsize']);
			$pdf->SetXY($x+$template['vc_left'], $y+$template['vc_top']);
			$str = iconv('UTF-8', 'windows-1251', 'Код: '.$pos_info['vc']);		
			$pdf->Cell($template['vc_width'], $template['vc_lheight'], $str, 0, 0, $template['vc_align']);
		}

		if($template['price_fontsize'])
		{
			$pdf->SetFont('','',$template['price_fontsize']);
			$pdf->SetXY($x+$template['price_left'], $y+$template['price_top']);
			$str = iconv('UTF-8', 'windows-1251', 'Цена: '.$pos_info['price'].'р');		
			$pdf->Cell($template['price_width'], $template['price_lheight'], $str, 0, 0, $template['price_align']);
		}
		
		if($template['vendor_fontsize'] && $pos_info['vendor'])
		{
			$pdf->SetFont('','',$template['vendor_fontsize']);
			$pdf->SetXY($x+$template['vendor_left'], $y+$template['vendor_top']);
			$str = iconv('UTF-8', 'windows-1251', 'Изготовитель: '.$pos_info['vendor']);		
			$pdf->Cell($template['vendor_width'], $template['vendor_lheight'], $str, 0, 0, $template['vendor_align']);
		}
		
		if($template['country_fontsize'] && $pos_info['country'])
		{
			$pdf->SetFont('','',$template['country_fontsize']);
			$pdf->SetXY($x+$template['country_left'], $y+$template['country_top']);
			$str = iconv('UTF-8', 'windows-1251', 'Страна: '.$pos_info['country']);		
			$pdf->Cell($template['country_width'], $template['country_lheight'], $str, 0, 0, $template['country_align']);
		}
		
		$x+=$template['width']+$template['ident'];
		
		if($x+$template['width']>$pdf->w-$pdf->rMargin)
		{
			$x=$pdf->lMargin;
			$y+=$template['height']+$template['ident'];
		}
		
		if($y+$template['height']>$pdf->h-$pdf->bMargin)
		{
			$pdf->AddPage($pdf->CurOrientation);
			$x=$pdf->lMargin;
			$y=$pdf->tMargin;
		}
		$pdf->SetXY($x,$y);
	}
	
	function Form()
	{
		global $tmpl;
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='pricetags'>
		<input type='hidden' name='opt' value='form2'>
		Использовать цену:<br>
		<select name='cost'>");
		$res=mysql_query("SELECT `id`, `name` FROM `doc_cost` ORDER BY `id");
		if(mysql_errno())	throw new MysqlException("Не удалось выбрать список цен");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");			
		}
		$tmpl->AddText("</select><br>
		Вид ценника:<br>
		<select name='tag_id'>");
		foreach($this->templates as $id => $t)
		{
			$tmpl->AddText("<option value='$id'>{$t['width']}мм X {$t['height']}мм - {$t['name']}</option>");
		}
		$tmpl->AddText("</select><br>
		Группа товаров:<br>");
		$this->GroupSelBlock();
		$tmpl->AddText("
		<button type='submit'>Далее</button>
		</form>");
	}
	
	function Form2()
	{
		global $tmpl;
		$cost_id=rcv('cost');
		$gs=rcv('gs');
		$g=@$_POST['g'];
		$tag_id=rcv('tag_id');
		settype($tag_id,'int');
		
		$tmpl->AddText("<h1>".$this->getName()."</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='pricetags'>
		<input type='hidden' name='cost' value='$cost_id'>
		<input type='hidden' name='tag_id' value='$tag_id'>
		<input type='hidden' name='opt' value='make'>
		Отметьте наименования, для которых требуется ценник:<br>
		<script type='text/javascript'>
	
		function SelAll(flag)
		{
			var elems = document.getElementsByName('pos_id[]');
			var l = elems.length;
			for(var i=0; i<l; i++)
			{
				elems[i].checked=flag;
			}
		}
		
		</script>
		<div class='selmenu'><a onclick='SelAll(true)' href='#'>Выбрать всё<a> | <a onclick='SelAll(false)' href='#'>Снять всё</a></div>
		<table class='list'>");

		$res_group=mysql_query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список групп");
		while($group_line=mysql_fetch_assoc($res_group))
		{
			if($gs && is_array($g))
				if(!in_array($group_line['id'],$g))	continue;			
			$tmpl->AddText("<tr><th>ID</th><th>Код</th><th>Наименование</th><th>Цена</th></tr>
			<tr><td colspan='8'>{$group_line['id']}. {$group_line['name']}</td></tr>");	
			
			$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`
			FROM `doc_base`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			WHERE `doc_base`.`group`='{$group_line['id']}'
			ORDER BY `doc_base`.`name`");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список наименований");
			
			while($nxt=mysql_fetch_row($res))
			{
				$cost=GetCostPos($nxt[0], $cost_id);
				$tmpl->AddText("<tr><td>$nxt[0]</td><td>$nxt[1]</td><td><label><input type='checkbox' name='pos_id[]' value='$nxt[0]' checked>$nxt[2]</label></td><td>$cost</td></tr>");	
			}
		}
		$tmpl->AddText("</table>
		<button type='submit'>Сформировать отчёт</button>
		</form>");
	}
	
	function MakePDF()
	{
		global $tmpl, $CONFIG;
		$cost=rcv('cost');
		$tag_id=rcv('tag_id');
		settype($tag_id,'int');
		$pos_id=@$_POST['pos_id'];

		
		
		$tmpl->ajax=1;
		$tmpl->SetText('');
		ob_start();
		define('FPDF_FONT_PATH',$CONFIG['site']['location'].'/fpdf/font/');
		require('fpdf/fpdf.php');
		$pdf=new FPDF('P');
		$pdf->Open();
		$pdf->AddFont('Arial','','arial.php');
		$pdf->SetMargins(6, 6);
		$pdf->SetAutoPageBreak(false,6);
		$pdf->AddPage('P');
		$pdf->SetFont('Arial','',10);
		
		if(!is_array($pos_id))	throw new Exception("Необходимо выбрать хотя бы одно наименование!");
		foreach($pos_id as $id => $val)
		{
			settype($val,'int');
			$this->drawPDFPriceTag($pdf, $this->templates[$tag_id], $val, $cost);
		}
		$pdf->Output('pricetags.pdf','I');
	}
	
	function Run($opt)
	{
		if($opt=='')		$this->Form();
		else if($opt=='form2')	$this->Form2();
		else			$this->MakePDF();	
	}
};

?>

