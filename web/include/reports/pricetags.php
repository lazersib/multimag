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
/// Печать ценников
class Report_PriceTags {

    var $templates;

    function __construct() {
        global $CONFIG;
        $this->templates = array();

        $this->templates[] = array(
            'tagname' => 'Миниатюрные наклейки',
            'width' => 27,
            'height' => 18,
            'margin' => 1,
            'name' => array(
                'left' => 0,
                'top' => 1,
                'width' => 0,
                'lheight' => 2.2,
                'fontsize' => 7,
                'align' => 'C'),
            'price' => array(
                'left' => 0,
                'top' => 14,
                'width' => 0,
                'lheight' => 4,
                'fontsize' => 9,
                'align' => 'R')
        );

        $this->templates[] = array(
            'tagname' => 'Стандартный ценник',
            'width' => 48,
            'height' => 55,
            'margin' => 2,
            'caption' => array(
                'height' => 5,
                'fontsize' => 7),
            'vc' => array(
                'left' => 0,
                'top' => 5,
                'width' => 0,
                'lheight' => 5,
                'fontsize' => 8,
                'align' => 'L'),
            'name' => array(
                'left' => 0,
                'top' => 10,
                'width' => 0,
                'lheight' => 6,
                'fontsize' => 14,
                'align' => 'C'),
            'price' => array(
                'left' => 0,
                'top' => 43,
                'width' => 0,
                'lheight' => 7,
                'fontsize' => 15,
                'align' => 'C'),
            'vendor' => array(
                'left' => 0,
                'top' => 50,
                'width' => 0,
                'lheight' => 7,
                'fontsize' => 5,
                'align' => 'R'),
            'country' => array(
                'left' => 0,
                'top' => 50,
                'width' => 0,
                'lheight' => 7,
                'fontsize' => 5,
                'align' => 'L')
        );

        $this->templates[] = array(
            'tagname' => 'Увеличенный ценник',
            'width' => 64,
            'height' => 69,
            'margin' => 2,
            'caption' => array(
                'height' => 6,
                'fontsize' => 10),
            'vc' => array(// Код производителя
                'left' => 0,
                'top' => 9,
                'width' => 0,
                'lheight' => 8,
                'fontsize' => 26,
                'align' => 'C'),
            'name' => array(// Наименование товара
                'left' => 0,
                'top' => 25,
                'width' => 0,
                'lheight' => 6,
                'fontsize' => 16,
                'align' => 'C'),
            'price' => array(// Цена по умолчанию
                'left' => 0,
                'top' => 52,
                'width' => 0,
                'lheight' => 7,
                'fontsize' => 22,
                'align' => 'C'),
            'ret_price' => array(// Розничная цена
                'left' => 0,
                'top' => 59,
                'width' => 0,
                'lheight' => 7,
                'fontsize' => 8,
                'align' => 'L'),
            'mult' => array(// Кратность ( кол-во в упаковке )
                'left' => 0,
                'top' => 46,
                'width' => 0,
                'lheight' => 7,
                'fontsize' => 10,
                'align' => 'C'),
            'bulkcnt' => array(// Количество оптом
                'left' => 0,
                'top' => 59,
                'width' => 0,
                'lheight' => 7,
                'fontsize' => 8,
                'align' => 'R'),
            'vendor' => array(// Производитель
                'left' => 0,
                'top' => 65,
                'width' => 0,
                'lheight' => 4,
                'fontsize' => 5,
                'align' => 'R'),
            'country' => array(// Страна происхождения
                'left' => 0,
                'top' => 65,
                'width' => 0,
                'lheight' => 4,
                'fontsize' => 5,
                'align' => 'L')
        );

        $this->templates[] = array(
            'tagname' => 'Большой (для крупногабаритного товара)',
            'width' => 98,
            'height' => 55,
            'margin' => 2,
            'caption' => array(
                'height' => 6,
                'fontsize' => 10),
            'vc' => array(
                'left' => 0,
                'top' => 7,
                'width' => 0,
                'lheight' => 5,
                'ontsize' => 12,
                'align' => 'L'),
            'name' => array(
                'left' => 0,
                'top' => 12,
                'width' => 0,
                'lheight' => 6,
                'fontsize' => 18,
                'align' => 'C'),
            'price' => array(
                'left' => 0,
                'top' => 40,
                'width' => 0,
                'lheight' => 10,
                'fontsize' => 30,
                'align' => 'C'),
            'vendor' => array(
                'left' => 0,
                'top' => 50,
                'width' => 0,
                'lheight' => 5,
                'fontsize' => 10,
                'align' => 'R'),
            'country' => array(
                'left' => 0,
                'top' => 50,
                'width' => 0,
                'lheight' => 5,
                'fontsize' => 10,
                'align' => 'L')
        );
        if (is_array(@$CONFIG['site']['tricetags'])) {
            $this->templates = array_merge($this->templates, $CONFIG['site']['tricetags']);
        }
    }

    function getName($short = 0) {
        if ($short) {
            return "Ценники";
        } else {
            return "Печать ценников";
        }
    }

    function draw_groups_tree($level) {
        global $db;
        $ret = '';
        $res = $db->query("SELECT `id`, `name`, `desc` FROM `doc_group` WHERE `pid`='$level' AND `hidelevel`='0' ORDER BY `name`");
        $i = 0;
        $r = '';
        if ($level == 0) {
            $r = 'IsRoot';
        }
        $cnt = $res->num_rows;
        while ($nxt = $res->fetch_row()) {
            if ($nxt[0] == 0) {
                continue;
            }
            $item = "<label><input type='checkbox' name='g[]' value='$nxt[0]' id='cb$nxt[0]' class='cb' checked onclick='CheckCheck($nxt[0])'>" .
                    html_out($nxt[1]) . "</label>";
            if ($i >= ($cnt - 1)) {
                $r.=" IsLast";
            }
            $tmp = $this->draw_groups_tree($nxt[0]); // рекурсия
            if ($tmp) {
                $ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div><ul class='Container' id='cont$nxt[0]'>" . $tmp . '</ul></li>';
            } else {
                $ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
            }
            $i++;
        }
        return $ret;
    }

    function GroupSelBlock() {
        global $tmpl;
        $tmpl->addStyle(".scroll_block
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
        $tmpl->addContent("<script type='text/javascript'>
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
		" . $this->draw_groups_tree(0) . "</ul></div>");
    }

    function drawPDFPriceTag($pdf, $template, $pos_id) {
        global $CONFIG, $db;
        $pref = \pref::getInstance();
        $pc = PriceCalc::getInstance();

        $res = $db->query("SELECT `doc_base`.`id`, CONCAT(`doc_group`.`printname`, ' ', `doc_base`.`name`) AS `name`, `doc_base`.`vc`,
            `doc_base`.`proizv` AS `vendor`, `class_country`.`name` AS `country`, `doc_base`.`cost` AS `base_price`, `doc_base`.`group`,
            `doc_base`.`bulkcnt`, `doc_base`.`mult`, `class_unit`.`rus_name1` AS `unit_name`
            FROM `doc_base`
            LEFT JOIN `doc_group` ON `doc_base`.`group`=`doc_group`.`id`
            LEFT JOIN `class_country` ON `doc_base`.`country`=`class_country`.`id`
            LEFT JOIN `class_unit` ON `class_unit`.`id`=`doc_base`.`unit`
            WHERE `doc_base`.`id`='$pos_id'");
        if ($res->num_rows == 0) {
            throw new Exception("Наименование не найдено!");
        }
        $pos_info = $res->fetch_assoc();

        $pos_info['price'] = $pc->getPosSelectedPriceValue($pos_id, $pc->getDefaultPriceId(), $pos_info);

        if (!@$template['price_width']) {
            $template['price_width'] = $template['width'];
        }
        if (!@$template['vendor_width']) {
            $template['vendor_width'] = $template['width'];
        }
        if (!@$template['country_width']) {
            $template['country_width'] = $template['width'];
        }

        $x = $pdf->getX();
        $y = $pdf->getY();
        $pdf->Rect($x, $y, $template['width'], $template['height']);

        $caption = $pref->site_display_name;

        if (isset($template['caption']) && $caption) {
            $pdf->SetFillColor(80);
            $pdf->SetTextColor(255);
            $pdf->SetFont('Arial', '', $template['caption']['fontsize']);
            $str = iconv('UTF-8', 'windows-1251', $caption);
            $pdf->Cell($template['width'], $template['caption']['height'], $str, 0, 0, 'C', true);
        }

        $pdf->SetTextColor(0);

        $lines = array(
            'vc' => 'Код: ',
            'name' => '',
            'price' => 'Цена: ',
            'country' => 'Страна: ',
            'vendor' => 'Изготовитель: ',
            'bulkcnt' => 'Опт от: ',
            'mult' => 'В упаковке: '
        );

        foreach ($lines as $id => $text) {
            if (isset($template[$id]) && isset($pos_info[$id])) {
                if (is_array($template[$id]) && $pos_info[$id]) {
                    $param = $template[$id];
                    if (!@$param['width']) {
                        $param['width'] = $template['width'];
                    }
                    $pdf->SetFont('', '', $param['fontsize']);
                    $pdf->SetXY($x + $param['left'], $y + $param['top']);
                    if ($id == 'bulkcnt' || $id == 'mult') {
                        $pos_info[$id] .= ' ' . $pos_info['unit_name'];
                    }
                    $str = iconv('UTF-8', 'windows-1251', $text . $pos_info[$id]);
                    $pdf->MultiCell($param['width'], $param['lheight'], $str, 0, $param['align']);
                }
            }
        }

        if ($pc->getDefaultPriceId() != $pc->getRetailPriceId()) {
            $ret_price = $pc->getPosSelectedPriceValue($pos_id, $pc->getRetailPriceId(), $pos_info);
            if ($pos_info['price'] != $ret_price && isset($template['ret_price'])) {
                $id = 'ret_price';
                $param = $template[$id];
                if (!@$param['width']) {
                    $param['width'] = $template['width'];
                }
                $pdf->SetFont('', '', $param['fontsize']);
                $pdf->SetXY($x + $param['left'], $y + $param['top']);
                $str = iconv('UTF-8', 'windows-1251', 'В розницу: ' . $ret_price);
                $pdf->Cell($param['width'], $param['lheight'], $str, 0, 0, $param['align']);
            }
        }

        $x+= $template['width'] + $template['margin'];

        if ($x + $template['width'] > $pdf->w - $pdf->rMargin) {
            $x = $pdf->lMargin;
            $y+=$template['height'] + $template['margin'];
        }

        if ($y + $template['height'] > $pdf->h - $pdf->bMargin) {
            $pdf->AddPage($pdf->CurOrientation);
            $x = $pdf->lMargin;
            $y = $pdf->tMargin;
        }
        $pdf->SetXY($x, $y);
    }

    function Form() {
        global $tmpl, $db;
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='pricetags'>
            <input type='hidden' name='opt' value='form2'>
            Организация:<br>
            <select name='firm_id'><option value='0'>--не задано--</option>");
        $res = $db->query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `id`");
        while ($nxt = $res->fetch_row()) {
            $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select><br>
            Вид ценника:<br>
            <select name='tag_id'>");
        foreach ($this->templates as $id => $t) {
            $tmpl->addContent("<option value='$id'>{$t['width']}мм X {$t['height']}мм - {$t['tagname']}</option>");
        }
        $tmpl->addContent("</select><br>
            Группа товаров:<br>");
        $this->GroupSelBlock();
        $tmpl->addContent("<button type='submit'>Далее</button></form>");
    }

    function Form2() {
        global $tmpl, $CONFIG, $db;
        $firm_id = rcvint('firm_id');
        $gs = rcvint('gs');
        $g = @$_POST['g'];
        $tag_id = rcvint('tag_id');
        switch (@$CONFIG['doc']['sklad_default_order']) {
            case 'vc': $order = '`doc_base`.`vc`';
                break;
            case 'cost': $order = '`doc_base`.`cost`';
                break;
            default: $order = '`doc_base`.`name`';
        }
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='pricetags'>
            <input type='hidden' name='tag_id' value='$tag_id'>
            <input type='hidden' name='firm_id' value='$firm_id'>
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

        $pc = PriceCalc::getInstance();
        $pc->SetFirmId($firm_id);
        $res_group = $db->query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
        while ($group_line = $res_group->fetch_assoc()) {
            if ($gs && is_array($g)) {
                if (!in_array($group_line['id'], $g)) {
                    continue;
                }
            }
            $tmpl->addContent("<tr><th>ID</th><th>Код</th><th>Наименование</th><th>Кол-во</th><th>Цена</th></tr>
			<tr><td colspan='8'>{$group_line['id']}. " . html_out($group_line['name']) . "</td></tr>");

            $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`,
				`doc_base`.`cost` AS `base_price`, `doc_base`.`group`, `doc_base`.`bulkcnt`
			FROM `doc_base`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			WHERE `doc_base`.`group`='{$group_line['id']}'
			ORDER BY $order");
            while ($nxt = $res->fetch_assoc()) {
                $cost = $pc->getPosSelectedPriceValue($nxt['id'], $pc->getDefaultPriceId(), $nxt);
                $tmpl->addContent("<tr><td>{$nxt['id']}</td><td>" . html_out($nxt['vc']) . "</td><td><label><input type='checkbox' name='pos_id[]' value='{$nxt['id']}' checked>" . html_out($nxt['name']) . "</label></td>
                    <td><input type='number' name='cnt[{$nxt['id']}]' value='1'></td>
                    <td>$cost</td></tr>");
            }
        }
        $tmpl->addContent("</table><button type='submit'>Сформировать отчёт</button></form>");
    }

    function MakePDF() {
        global $tmpl, $CONFIG;
        $tag_id = rcvint('tag_id');
        $pos_id = request('pos_id');
        $firm_id = rcvint('firm_id');
        $tmpl->ajax = 1;
        $tmpl->setContent('');
        ob_start();
        define('FPDF_FONT_PATH', $CONFIG['site']['location'] . '/fpdf/font/');
        require('fpdf/fpdf.php');
        $pdf = new FPDF('P');
        $pdf->Open();
        $pdf->AddFont('Arial', '', 'arial.php');
        $pdf->SetMargins(6, 6);
        $pdf->SetAutoPageBreak(false, 6);
        $pdf->AddPage('P');
        $pdf->SetFont('Arial', '', 10);
        
        $pc = PriceCalc::getInstance();
        $pc->SetFirmId($firm_id);
        
        if (!is_array($pos_id)) {
            throw new Exception("Необходимо выбрать хотя бы одно наименование!");
        }
        foreach ($pos_id as $val) {
            settype($val, 'int');
            $cnt = intval(@$_REQUEST['cnt'][$val]);
            if ($cnt < 1) {
                $cnt = 1;
            }
            for ($i = 0; $i < $cnt; $i++) {
                $this->drawPDFPriceTag($pdf, $this->templates[$tag_id], $val);
            }
        }
        $pdf->Output('pricetags.pdf', 'I');
    }

    function Run($opt) {
        if ($opt == '') {
            $this->Form();
        } else if ($opt == 'form2') {
            $this->Form2();
        } else {
            $this->MakePDF();
        }
    }

}
