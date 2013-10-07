<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2013, BlackLight, TND Team, http://tndproject.org
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

/// Документ *приходный кассовый ордер*
class doc_Pko extends doc_Nulltype {
	function __construct($doc=0) {
		parent::__construct($doc);
		$this->doc_type				=6;
		$this->doc_name				='pko';
		$this->doc_viewname			='Приходный кассовый ордер';
		$this->ksaas_modify			=1;
		$this->header_fields			='kassa sum separator agent';
		$this->PDFForms=array(
			array('name'=>'pko','desc'=>'Приходный ордер','method'=>'PrintPKOPDF')
		);
	}

	// Провести
	function DocApply($silent=0) {
		global $db;
		$data = $db->selectRow('doc_list', $this->doc);
		if(!$data)
			throw new Exception('Ошибка выборки данных документа при проведении!');
		if($data['ok'] && (!$silent) )
			throw new Exception('Документ уже проведён!');
		
		$res = $db->query("SELECT `ballance` FROM `doc_kassa` WHERE `ids`='kassa' AND `num`='{$data['kassa']}'");
		if(!$res->num_rows)		throw new Exception('Ошибка получения суммы кассы!');
		$nxt = $res->fetch_row();
		if($nxt[0]<$data['sum'])	throw new Exception("Не хватает денег в кассе N{$data['kassa']} ($nxt[0] < {$data['sum']})!");

		$res = $db->query("UPDATE `doc_kassa` SET `ballance`=`ballance`+'{$data['sum']}'	WHERE `ids`='kassa' AND `num`='{$data['kassa']}'");
		if(! $db->affected_rows)	throw new Exception('Ошибка обновления кассы!');
		if($silent)	return;
		
		$db->update('doc_list', $this->doc, 'ok', time() );
		$this->sentZEvent('apply');
	}

	// Отменить проведение
	function DocCancel() {
		global $db;
		$data = $db->selectRow('doc_list', $this->doc);
		if(!$data)
			throw new Exception('Ошибка выборки данных документа!');
		if(!$data['ok'])
			throw new Exception('Документ не проведён!');
		
		$res = $db->query("UPDATE `doc_kassa` SET `ballance`=`ballance`-'{$data['sum']}'	WHERE `ids`='kassa' AND `num`='{$data['kassa']}'");
		if(! $db->affected_rows)	throw new Exception('Ошибка обновления кассы!');
		
		$db->update('doc_list', $this->doc, 'ok', 0 );
		$budet = $this->checkKassMinus();
		if($budet<0)				throw new Exception("Невозможно, т.к. будет недостаточно ($budet) денег в кассе!");
		$this->sentZEvent('cancel');
	}
	
	function PrintPKOPDF($to_str=false) {
		global $tmpl, $CONFIG, $db;
		if(!$to_str) $tmpl->ajax=1;
		
		require('fpdf/fpdf.php');
		$pdf=new FPDF('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(0,10);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=10;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		$pdf->Rect(136, 3, 3, 120 );

		$pdf->lMargin=5;
		$pdf->rMargin=75;

		$pdf->SetFont('','',6);
		$str = "Унифицированная форма № КО-1\nУтверждена постановлением Госкомстата\nРоссии от 18.08.1998г. №88";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0,3,$str,0,'R',0);

		$pdf->SetX(120);
		$str = iconv('UTF-8', 'windows-1251', "Код");
		$pdf->Cell(0,4,$str,1,1,'C',0);
		$y=$pdf->GetY();
		$pdf->SetLineWidth(0.5);
		$pdf->SetX(120);
		$pdf->Cell(0,16,'',1,1,'C',0);
		$pdf->SetLineWidth(0.2);
		$pdf->SetY($y);

		$str = iconv('UTF-8', 'windows-1251', "Форма по ОКУД");
		$pdf->Cell(115,4,$str,0,0,'R',0);
		$pdf->Cell(0,4,'0310001',1,1,'C',0);

		$str = iconv('UTF-8', 'windows-1251', html_in($this->firm_vars['firm_name']));
		$pdf->Cell(95,4,$str,0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "по ОКПО");
		$pdf->Cell(20,4,$str,0,0,'R',0);
		$pdf->Cell(0,4,$this->firm_vars['firm_okpo'],1,1,'C',0);

		$pdf->SetFont('','',5);
		$pdf->Line(5, $pdf->GetY(), 100, $pdf->GetY());
		$str = iconv('UTF-8', 'windows-1251', "организация");
		$pdf->Cell(115,2,$str,0,0,'C',0);
		$pdf->Cell(0,4,'',1,1,'C',0);

		$pdf->Cell(115,4,'',0,1,'C',0);
		$pdf->Line(5, $pdf->GetY(), 100, $pdf->GetY());
		$str = iconv('UTF-8', 'windows-1251', "структурное подразделение");
		$pdf->Cell(115,2,$str,0,1,'C',0);


		$pdf->Cell(85,4,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "Номер документа");
		$pdf->Cell(18,3,$str,1,0,'C',0);
		$str = iconv('UTF-8', 'windows-1251', "Дата составления");
		$pdf->Cell(0,3,$str,1,1,'C',0);

		$pdf->SetLineWidth(0.5);
		$pdf->SetFont('','',14);
		$str = iconv('UTF-8', 'windows-1251', "Приходный кассовый ордер");
		$pdf->Cell(85,4,$str,0,0,'C',0);
		$pdf->SetFont('','',7);
		$pdf->Cell(18,4,$this->doc_data['altnum'],1,0,'C',0);
		$date=date("d.m.Y",$this->doc_data['date']);
		$pdf->Cell(0,4,$date,1,1,'C',0);
		$pdf->SetLineWidth(0.2);
		$pdf->Ln();


		$y=$pdf->GetY();

		$t_all_offset=array();
		$pdf->SetFont('','',10);
		$t_width=array(10,70,23,20,7);
		$t_ydelta=array(2,1,6,4,1);
		$t_text=array(
		'Дебет',
		'Кредит',
		'Сумма руб, коп',
		'Код целевого назначения',
		'');

		foreach($t_width as $w)
		{
			$pdf->Cell($w,16,'',1,0,'C',0);
		}
		$pdf->Ln();
		$pdf->Ln(0.5);
		$pdf->SetFont('','',8);
		$offset=0;
		foreach($t_width as $i => $w)
		{
			$t_all_offset[$offset]=$offset;
			$pdf->SetY($y+$t_ydelta[$i]+0.2);
			$pdf->SetX($offset+$pdf->lMargin);
			$str = iconv('UTF-8', 'windows-1251', $t_text[$i] );
			$pdf->MultiCell($w,3,$str,0,'C',0);
			$offset+=$w;
		}

		$t2_width=array(25, 25, 20);
		$t2_start=array(1,1,1);
		$t2_ydelta=array(2,1,1);
		$t2_text=array(
		'код структурного подразделения',
		'корреспондиру- ющий счёт, субсчёт',
		'код аналитичес- кого учёта');
		$offset=0;
		$c_id=0;
		$old_col=0;
		$y+=5;

		foreach($t2_width as $i => $w2)
		{
			while($c_id<$t2_start[$i])
			{
				$t_a[$offset]=$offset;
				$offset+=$t_width[$c_id++];
			}

			if($old_col==$t2_start[$i])	$off2+=$t2_width[$i-1];
			else				$off2=0;
			$old_col=$t2_start[$i];
			$t_all_offset[$offset+$off2]=$offset+$off2;
			$pdf->SetY($y);
			$pdf->SetX($offset+$off2+$pdf->lMargin);
			$pdf->Cell($w2,11,'',1,0,'C',0);

			$pdf->SetY($y+$t2_ydelta[$i]);
			$pdf->SetX($offset+$off2+$pdf->lMargin);
			$str = iconv('UTF-8', 'windows-1251', $t2_text[$i] );
			$pdf->MultiCell($w2,3,$str,0,'C',0);
		}

		sort ( $t_all_offset, SORT_NUMERIC );
		$pdf->SetY($y+11);
		$t_all_width=array();
		$old_offset=0;
		foreach($t_all_offset as $offset)
		{
			if($offset==0)	continue;
			$t_all_width[]=	$offset-$old_offset;
			$old_offset=$offset;
		}
		$t_all_width[]=0;
		$i=1;
		$pdf->SetLineWidth(0.4);
		foreach($t_all_width as $id => $w)
		{
			$pdf->Cell($w,4,'',1,0,'C',0);
			$i++;
		}
		$pdf->SetLineWidth(0.2);
		$pdf->Ln(6);
		$pdf->SetFont('','',7);
		$res = $db->query("SELECT `doc_agent`.`fullname` FROM `doc_agent` WHERE `doc_agent`.`id`='{$this->doc_data['agent']}'");
		$agent_info = $res->fetch_assoc();
		if(!$agent_info)		throw new Exception('Агент не найден');

		$str = iconv('UTF-8', 'windows-1251', "Принято от");
		$pdf->Cell(20,4,$str,'B',0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', $agent_info['fullname']);
		$pdf->Cell(0,4,$str,'B',1,'L',0);

		if($this->doc_data['p_doc'])	{
			$res = $db->query("SELECT `doc_list`.`altnum`, `doc_list`.`date` FROM `doc_list`
			WHERE `doc_list`.`id`='{$this->doc_data['p_doc']}'");
			$data = $res->fetch_assoc();
			$ddate=date("d.m.Y",$data['date']);
			$str_osn="Оплата к с/ф №{$data['altnum']} от $ddate";
			$str_osn = iconv('UTF-8', 'windows-1251', $str_osn);
		}
		else $str_osn='';
		$str = iconv('UTF-8', 'windows-1251', "Основание:");
		$pdf->Cell(20,4,$str,'B',0,'L',0);
		$pdf->Cell(0,4,$str_osn,'B',1,'L',0);

		$str = iconv('UTF-8', 'windows-1251', "Сумма");
		$pdf->Cell(15,4,$str,'B',0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', num2str($this->doc_data['sum']));
		$pdf->Cell(0,4,$str,'B',1,'L',0);

		$sum_r=round($this->doc_data['sum']);
		$sum_c=round(($this->doc_data['sum']-$sum_r)*100);
		$str = iconv('UTF-8', 'windows-1251', "Сумма");
		$pdf->Cell(90,4,'','B',0,'L',0);
		$pdf->Cell(20,4,$sum_r,'B',0,'R',0);
		$str = iconv('UTF-8', 'windows-1251', "руб.");
		$pdf->Cell(10,4,$str,0,0,'C',0);
		$pdf->Cell(5,4,$sum_c,'B',0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "коп.");
		$pdf->Cell(0,4,$str,0,1,'L',0);

		$str = iconv('UTF-8', 'windows-1251', "В том числе");
		$pdf->Cell(20,4,$str,0,0,'L',0);
		$pdf->Cell(0,4,'','B',1,'L',0);

		$str = iconv('UTF-8', 'windows-1251', "Приложение");
		$pdf->Cell(20,4,$str,0,0,'L',0);
		$pdf->Cell(0,4,'','B',1,'L',0);

		$pdf->Ln(3);
		$str = iconv('UTF-8', 'windows-1251', "Бухгалтер");
		$pdf->Cell(20,4,$str,0,0,'L',0);
		$pdf->Cell(40,4,'','B',0,'L',0);
		$pdf->Cell(5,4,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', $this->firm_vars['firm_buhgalter'] );
		$pdf->Cell(0,4,$str,'B',1,'L',0);

		$pdf->SetFont('','',5);
		$pdf->Cell(20,2,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "(подпись)");
		$pdf->Cell(40,2,$str,0,0,'C',0);
		$pdf->Cell(5,2,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "(расшифровка подписи)");
		$pdf->Cell(0,2,$str,0,1,'C',0);
		$pdf->SetFont('','',7);
		
		$res = $db->query("SELECT `worker_real_name` FROM `users_worker_info` WHERE `user_id`='{$this->doc_data['user']}'");
		if($res->num_rows) {
			$worker_info = $res->fetch_assoc();
			$name = $worker_info['worker_real_name'];
		}
		else $name=$this->firm_vars['firm_buhgalter'];

		$str = iconv('UTF-8', 'windows-1251', "Получил кассир");
		$pdf->Cell(20,4,$str,0,0,'L',0);
		$pdf->Cell(40,4,'','B',0,'L',0);
		$pdf->Cell(5,4,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', $name );
		$pdf->Cell(0,4,$str,'B',1,'L',0);

		$pdf->SetFont('','',5);
		$pdf->Cell(20,2,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "(подпись)");
		$pdf->Cell(40,2,$str,0,0,'C',0);
		$pdf->Cell(5,2,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "(расшифровка подписи)");
		$pdf->Cell(0,2,$str,0,1,'C',0);
		$pdf->SetFont('','',7);

		$pdf->lMargin=140;
		$pdf->rMargin=5;
		$pdf->SetY(5);
		$pdf->Ln();

		$str = iconv('UTF-8', 'windows-1251', $this->firm_vars['firm_name']);
		$pdf->MultiCell(0,4,$str,'B','L',0);

		$pdf->SetFont('','',5);
		$str = iconv('UTF-8', 'windows-1251', "организация");
		$pdf->Cell(0,2,$str,0,1,'C',0);

		$pdf->SetFont('','',14);
		$str = iconv('UTF-8', 'windows-1251', "Квитанция");
		$pdf->Cell(0,12,$str,0,1,'C',0);

		$pdf->SetFont('','',7);
		$str = iconv('UTF-8', 'windows-1251', "К приходно-кассовому ордеру №");
		$pdf->Cell(40,4,$str,0,0,'L',0);
		$pdf->Cell(0,4,$this->doc_data['altnum'],'B',1,'C',0);

		$date=date("d.m.Y",$this->doc_data['date']);
		$str = iconv('UTF-8', 'windows-1251', "От $date");
		$pdf->Cell(0,4,$str,'B',1,'L',0);

		$str = iconv('UTF-8', 'windows-1251', "Принято от");
		$pdf->Cell(20,4,$str,0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', $agent_info['fullname']);
		$pdf->Cell(0,4,'','B',1,'L',0);

		$y=$pdf->GetY();
		$pdf->Cell(0,4,'','B',1,'L',0);
		$pdf->Cell(0,4,'','B',1,'L',0);
		$pdf->SetY($y);
		$pdf->MultiCell(0,4,$str,'B','L',0);
		$pdf->SetY($y+8);
		$str = iconv('UTF-8', 'windows-1251', "Основание:");
		$pdf->Cell(20,4,$str,0,0,'L',0);
		$pdf->Cell(0,4,'','B',1,'L',0);
		$y=$pdf->GetY();
		$pdf->Cell(0,4,'','B',1,'L',0);
		$pdf->Cell(0,4,'','B',1,'L',0);
		$pdf->SetY($y);
		$pdf->MultiCell(0,4,$str_osn,0,'L',0);
		$pdf->SetY($y+8);
		$sum_r=round($this->doc_data['sum']);
		$sum_c=round(($this->doc_data['sum']-$sum_r)*100);
		$str = iconv('UTF-8', 'windows-1251', "Сумма");
		$pdf->Cell(10,4,$str,0,0,'L',0);
		$pdf->Cell(30,4,$sum_r,'B',0,'R',0);
		$str = iconv('UTF-8', 'windows-1251', "руб.");
		$pdf->Cell(10,4,$str,0,0,'C',0);
		$pdf->Cell(5,4,$sum_c,'B',0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "коп.");
		$pdf->Cell(0,4,$str,0,1,'L',0);
		$pdf->SetFont('','',5);
		$str = iconv('UTF-8', 'windows-1251', "цифрами");
		$pdf->Cell(0,2,$str,0,1,'C',0);
		$pdf->SetFont('','',7);


		$str = iconv('UTF-8', 'windows-1251', num2str($this->doc_data['sum']));
		$y=$pdf->GetY();
		$pdf->Cell(0,4,'','B',1,'L',0);
		$pdf->Cell(0,4,'','B',1,'L',0);
		$pdf->SetY($y);
		$pdf->MultiCell(0,4,$str,0,'L',0);
		$pdf->SetY($y+8);


		$pdf->SetFont('','',5);
		$str = iconv('UTF-8', 'windows-1251', "прописью");
		$pdf->Cell(0,2,$str,0,1,'C',0);
		$pdf->SetFont('','',7);

		$str = iconv('UTF-8', 'windows-1251', "В том числе");
		$pdf->Cell(20,4,$str,0,0,'L',0);
		$pdf->Cell(0,4,'','B',1,'L',0);

		$date=date("d.m.Y",$this->doc_data['date']);
		$pdf->Cell(0,6,$date,0,1,'L',0);

		$str = iconv('UTF-8', 'windows-1251', "МП (штампа)");
		$pdf->Cell(0,6,$str,0,1,'C',0);

		$pdf->Ln(3);
		$str = iconv('UTF-8', 'windows-1251', "Бухгалтер");
		$pdf->Cell(14,4,$str,0,0,'L',0);
		$pdf->Cell(20,4,'','B',0,'L',0);
		$pdf->Cell(5,4,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', $this->firm_vars['firm_buhgalter'] );
		$pdf->Cell(0,4,$str,'B',1,'L',0);

		$pdf->SetFont('','',5);
		$pdf->Cell(14,2,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "(подпись)");
		$pdf->Cell(20,2,$str,0,0,'C',0);
		$pdf->Cell(5,2,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "(расшифровка подписи)");
		$pdf->Cell(0,2,$str,0,1,'C',0);
		$pdf->SetFont('','',7);

		$res = $db->query("SELECT `worker_real_name` FROM `users_worker_info` WHERE `user_id`='{$this->doc_data['user']}'");
		if($res->num_rows) {
			$worker_info = $res->fetch_assoc();
			$name = $worker_info['worker_real_name'];
		}
		else $name=$this->firm_vars['firm_buhgalter'];

		$str = iconv('UTF-8', 'windows-1251', "Кассир");
		$pdf->Cell(10,4,$str,0,0,'L',0);
		$pdf->Cell(20,4,'','B',0,'L',0);
		$pdf->Cell(5,4,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', $name );
		$pdf->Cell(0,4,$str,'B',1,'L',0);

		$pdf->SetFont('','',5);
		$pdf->Cell(10,2,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "(подпись)");
		$pdf->Cell(20,2,$str,0,0,'C',0);
		$pdf->Cell(5,2,'',0,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "(расшифровка подписи)");
		$pdf->Cell(0,2,$str,0,1,'C',0);

		if($to_str)
			return $pdf->Output('pko.pdf','S');
		else
			$pdf->Output('pko.pdf','I');
	}
};


?>