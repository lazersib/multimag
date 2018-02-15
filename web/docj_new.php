<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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
// Новый журнал документов. Оптимизированная версия для открытия большого журнала
include_once("core.php");
include_once("include/doc.core.php");

\acl::accessGuard('service.doclist', \acl::VIEW);

SafeLoadTemplate($CONFIG['site']['inner_skin']);
$tmpl->hideBlock('left');

if (!isset($_REQUEST['mode'])) {
	$f = '';
	$agent_id = rcvint('agent_id');
	$pos_id = rcvint('pos_id');
	if($agent_id) {
		$agent_info = $db->selectRow('doc_agent', $agent_id);
		$f = "agentId: '$agent_id', agentName: '".html_out($agent_info['name'])."'";
	}
	else if($pos_id) {
		$pos_info = $db->selectRow('doc_base', $pos_id);
                if(@$CONFIG['poseditor']['vc']) {
                    $pos_info['name'] = $pos_info['vc'].' '.$pos_info['name'];
                }
		$f = "posId: '$pos_id', posName: '".html_out($pos_info['name'])."'";
	}
	else $f = "dateFrom: '".date("Y-m-d")."'";
	
	$no_new_page = 0;
	$res = $db->query("SELECT `value` FROM `users_data` WHERE `param`='docj_no_new_page' AND `uid`=".intval($_SESSION['uid']));
	if($line = $res->fetch_row()) {
		$no_new_page = intval($line[0]);
	}
	
	$tmpl->setTitle("Реестр документов");
	doc_menu("<a href='?mode=print' title='Печать реестра' id='djprint_link'><img src='img/i_print.png' alt='Реестр документов' border='0'></a>");
	$tmpl->addContent("<script type='text/javascript' src='/css/doc_script.js'></script>
	<div id='doc_list_filter'></div>
	<div class='clear'></div>
	<div id='doc_list_status'></div>

	<table width='100%' cellspacing='1' onclick='hlThisRow(event)' id='doc_list' class='list'>
	<thead id='doc_list_head'>
	<tr>
	<th width='55'>a.№</th><th width='20'>&nbsp;</th><th width='20'>&nbsp;<th>Тип<th>Участник 1<th>Участник 2<th>Сумма<th>Дата<th>Автор<th width='45'>id</th>
	</tr>
	</thead>
	<tbody id='docj_list_body'>
	</tbody>
	</table>

	<br><b>Легенда</b>: строка - <span class='f_green'>интернет - магазин</span>, <span class='f_red'>с ошибкой</span><br>
	Номер реализации - <span class='f_green'>Оплачено</span>, <span class='f_red'>Не оплачено</span>, <span class='f_brown'>Частично оплачено</span>, <span class='f_purple'>Переплата</span><br>
        <script type='text/javascript' src='/js/common.js'></script>
	<script type='text/javascript' src='/js/doc_journal.js'></script>
	<script>
	var dj = initDocJournal('doc_list', { $f }, {'no_new_page': $no_new_page});
	var djprint_link=document.getElementById('djprint_link');
	djprint_link.onclick = function() {
		dj.print();
		return false;
	}
	</script>
	");
} else if($_REQUEST['mode']=='print') {
	
	$tmp = new Models\LDO\docnames;
	$doc_names = $tmp->getData();
	$tmp = new Models\LDO\agentnames;
	$agent_names = $tmp->getData();
	$tmp = new Models\LDO\firmnames;
	$firmnames = $tmp->getData();
	$tmp = new Models\LDO\skladnames;
	$skladnames = $tmp->getData();
	$tmp = new Models\LDO\banknames;
	$banknames = $tmp->getData();
	$tmp = new Models\LDO\kassnames;
	$kassnames = $tmp->getData();
	$tmp = new Models\LDO\usernames;
	$usernames = $tmp->getData();
	
	$doc_list = new Models\LDO\doclist;
	
	$o = request('o/doclist');
	if($o)	$doc_list->setOrderField($o);
	$d = request('d/doclist');
	if($d)	$doc_list->setReverseOrderDirection($true);
	$f = request('f/doclist');
	if($f)	$doc_list->setFields($f);
	$p = request('p/doclist');
	if($p)	$doc_list->setPage($p);
	$l = request('l/doclist');
	if($l)	$doc_list->setLimit($f);
		
	$z = request('doclist');
	if($z)	$doc_list->setOptions($z);
	
	require('fpdf/fpdf_mc.php');
	$pdf = new PDF_MC_Table('L');
	$pdf->Open();
	$pdf->SetAutoPageBreak(1, 12);
	$pdf->AddFont('Arial', '', 'arial.php');
	$pdf->tMargin = 5;
	$pdf->AddPage();
	$pdf->SetFont('Arial', '', 10);
	$pdf->SetFillColor(255);
	
	$pdf->SetFont('','',22);
	$str="Реестр документов";
	$pdf->CellIconv(0, 10, $str, 0, 1, 'C', 0);
	$pdf->SetFont('','',10);
	

	$pdf->SetLineWidth(0.5);
	$t_width=array(12, 12, 35, 75, 75, 18, 30, 20);
	$t_text=array('a.№', 'id', 'Тип', 'Участник 1', 'Участник 2', 'Сумма', 'Дата', 'Автор');


	foreach($t_width as $id=>$w)
	{
		$str = iconv('UTF-8', 'windows-1251', $t_text[$id]);
		$pdf->CellIconv($w,6,$t_text[$id],1,0,'C',0);
	}
	$pdf->Ln();
	
	$pdf->SetWidths($t_width);
	$pdf->SetHeight(5);

	$aligns=array('R','C','L','L','L','R','L','R');

	$pdf->SetAligns($aligns);
	$pdf->SetLineWidth(0.2);
	$pdf->SetFont('','',8);
	
	$doc_list_data = $doc_list->getData();
	
	foreach ($doc_list_data as $line) {
		$source = "Агент: ".@$agent_names[$line['agent_id']];
		$target = '';
		switch($line['type']) {
			case 1:
			case 2: 
			case 20:$target = "Склад: ".$skladnames[$line['sklad_id']];
				break;
			case 3:
			case 11:
			case 12:
			case 10:
			case 18:
			case 19:
			case 13:
			case 14:
			case 15:
			case 16:$target = "Фирма: ".$firmnames[$line['firm_id']];
				break;
			case 4:	
			case 5:	$target = "Банк: ".$banknames[$line['bank_id']];
				break;
			case 6:
			case 7:	$target = "Касса: ".$kassnames[$line['kassa_id']];
				break;
			case 8:	$source = "Склад: ".$skladnames[$line['sklad_id']];
				$target = "Склад: ".@$skladnames[$line['nasklad_id']];
				break;
			case 9:	$source = "Касса: ".$kassnames[$line['kassa_id']];
				$target = "Касса: ".@$kassnames[$line['vkassu_id']];
				break;
				break;
			case 17:$source = "Склад: ".$skladnames[$line['sklad_id']];
				$target = "Склад: ".@$skladnames[$line['sklad_id']];
				break;
			case 21:$source = "Фирма: ".$firmnames[$line['firm_id']];
				$target = "Склад: ".@$skladnames[$line['sklad_id']];
				break;
		}
		$line['date'] = str_replace("&nbsp", " ", $line['date']);
		$_data = array(
		    $line['altnum'].$line['subtype'],
		    $line['id'],
		    $doc_names[$line['type']],
		    $source,
		    $target,
		    $line['sum'],
		    $line['date'],
		    @$usernames[$line['author_id']]
		);
		
		$pdf->RowIconv($_data);
	}
	
	
	$pdf->Output('registry.pdf','I');
}

$tmpl->write();
?>
