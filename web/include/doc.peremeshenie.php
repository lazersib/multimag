<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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


/// Документ *перемещение товара*
class doc_Peremeshenie extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого
	function __construct($doc=0) {
		parent::__construct($doc);
		$this->doc_type				=8;
		$this->doc_name				='peremeshenie';
		$this->doc_viewname			='Перемещение товара со склада на склад';
		$this->sklad_editor_enable		=true;
		$this->sklad_modify			=0;
		$this->header_fields			='cena separator sklad';
		$this->PDFForms=array(
			array('name'=>'nakl','desc'=>'Накладная PDF','method'=>'PrintNaklPDF')
		);
	}
	
	function initDefDopdata() {
		$this->def_dop_data = array('kladovshik'=>0, 'na_sklad'=>0, 'mest'=>'', 'cena'=>0);
	}

	function DopHead() {
		global $tmpl, $db;
		$klad_id = $this->dop_data['kladovshik'];
		if(!$klad_id)	$klad_id=$this->firm_vars['firm_kladovshik_id'];
		$tmpl->addContent("На склад:<br>
		<select name='nasklad'>");
		$res = $db->query("SELECT `id`,`name` FROM `doc_sklady` ORDER BY `name`");
		while($nxt = $res->fetch_row())
		{
			if($nxt[0]==$this->dop_data['na_sklad'])
				$tmpl->addContent("<option value='$nxt[0]' selected>".html_out($nxt[1])."</option>");
			else
				$tmpl->addContent("<option value='$nxt[0]'>".html_out($nxt[1])."</option>");
		}
		$tmpl->addContent("</select><br>
		Кладовщик:<br><select name='kladovshik'>
		<option value='0'>--не выбран--</option>");
		$res = $db->query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
		while($nxt = $res->fetch_row())
		{
			$s=($klad_id==$nxt[0])?'selected':'';
			$tmpl->addContent("<option value='$nxt[0]' $s>".html_out($nxt[1])."</option>");
		}
		$tmpl->addContent("</select><br>
		Количество мест:<br>
		<input type='text' name='mest' value='{$this->dop_data['mest']}'><br>");
	}

	function DopSave() {
		$new_data = array(
			'na_sklad' => rcvint('nasklad'),
			'mest' => rcvint('mest'),
			'kladovshik' => rcvint('kladovshik')
		);
		$old_data = array_intersect_key($new_data, $this->dop_data);
		
		$log_data='';
		if($this->doc)
			$log_data = getCompareStr($old_data, $new_data);
		$this->setDopDataA($new_data);
		if($log_data)	doc_log("UPDATE {$this->doc_name}", $log_data, 'doc', $this->doc);
	}

	function DocApply($silent=0) {
		global $CONFIG, $db;
		$tim = time();
		$nasklad = (int) $this->dop_data['na_sklad'];
		if(!$nasklad)	throw new Exception("Не определён склад назначения!");
		
		if($this->doc_data['sklad']==$nasklad)
				throw new Exception("Исходный склад совпадает со складом назначения! {$this->doc_data['sklad']}==$nasklad");
		
		$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
		FROM `doc_list`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res->num_rows)
			throw new Exception('Документ не найден!');
		$nx = $res->fetch_assoc();
		
		if( $nx['ok'] && (!$silent) )	throw new Exception('Документ уже был проведён!');
		if(!@$this->dop_data['mest'] && @$CONFIG['doc']['require_pack_count'] && !$silent)	throw new Exception("Количество мест не задано");

		$res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`pos_type`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$nx['sklad']}'
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		while($nxt = $res->fetch_row()) {
			if($nxt[5]>0)		throw new Exception("Перемещение услуги '$nxt[3]:$nxt[4]' недопустимо!");
			if(!$nx['dnc'])	{
				if($nxt[1]>$nxt[2])	throw new Exception("Недостаточно ($nxt[1]) товара '$nxt[3]:$nxt[4]' на складе($nxt[2])!");
			}
			$db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='{$nx['sklad']}'");
			$db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`+'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nasklad'");
			// Если это первое поступление
			if($db->affected_rows==0)
				$db->query("INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`) VALUES ('$nxt[0]', '$nasklad', '$nxt[1]')");

			if( (!$nx['dnc']) && (!$silent)) {
				$budet=getStoreCntOnDate($nxt[0], $nx['sklad']);
				if($budet<0)
					throw new Exception("Невозможно, т.к. будет недостаточно ($budet) товара '$nxt[3]:$nxt[4]' !");
			}
		}
		$res->free();
		if($silent)	return;
		$db->query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		$this->sentZEvent('apply');

	}

	function DocCancel() {
		global $db;
		$nasklad = (int)$this->dop_data['na_sklad'];

		$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
		FROM `doc_list`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res->num_rows)			throw new Exception('Документ не найден!');
		$nx = $res->fetch_assoc();
		if(!$nx['ok'])				throw new Exception('Документ не проведён!');
		$res = $db->query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		$res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$nx['sklad']}'
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		while($nxt = $res->fetch_row()) {
			$db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nasklad'");
			$db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`+'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='{$nx['sklad']}'");
			if(!$nx['dnc'])	{
				$budet=getStoreCntOnDate($nxt[0], $nx['sklad']);
				if($budet<0)			throw new Exception("Невозможно, т.к. будет недостаточно ($budet) товара '$nxt[3]' !");
			}
		}
	}

/// Обычная накладная в PDF формате
/// @param to_str Вернуть строку, содержащую данные документа (в противном случае - отправить файлом)
	function PrintNaklPDF($to_str = false) {
		require('fpdf/fpdf_mc.php');
		global $tmpl, $CONFIG, $db;

		if(!$to_str) $tmpl->ajax=1;

		$pdf = new PDF_MC_Table('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(0,10);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=10;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		$dt = date("d.m.Y",$this->doc_data['date']);

		$pdf->SetFont('','',16);
		$str="Накладная перемещения N {$this->doc_data['altnum']}{$this->doc_data['subtype']}, от $dt";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,8,$str,0,1,'C',0);

		$sklad_info = $db->selectRowA('doc_sklady', $this->doc_data['sklad'], array('name'));
		if(! $sklad_info )	throw new Exception("Склад не найден!");
		$from_sklad = $sklad_info['name'];

		$sklad_info = $db->selectRowA('doc_sklady', $this->dop_data['na_sklad'], array('name'));
		if(! $sklad_info )	throw new Exception("Склад назначения не найден!");
		$to_sklad = $sklad_info['name'];

		$pdf->SetFont('','',10);
		$str="C: $from_sklad";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="На: $to_sklad";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$pdf->Ln();

		$pdf->SetLineWidth(0.5);
		$t_width=array(8);
		if($CONFIG['poseditor']['vc']) {
			$t_width[]=20;
			$t_width[]=90;
		}
		else	$t_width[]=110;
		$t_width=array_merge($t_width, array(14, 20, 20, 20));

		$t_text=array('№');
		if($CONFIG['poseditor']['vc'])
		{
			$t_text[]='Код';
			$t_text[]='Наименование';
		}
		else	$t_text[]='Наименование';
		$t_text=array_merge($t_text, array('Кол-во', 'Место ист.', 'Место наз.', 'О. масса'));

		foreach($t_width as $id=>$w)
		{
			$str = iconv('UTF-8', 'windows-1251', $t_text[$id]);
			$pdf->Cell($w,6,$str,1,0,'C',0);
		}
		$pdf->Ln();
		$pdf->SetWidths($t_width);
		$pdf->SetHeight(3.8);

		$aligns=array('R');
		if($CONFIG['poseditor']['vc'])
		{
			$aligns[]='L';
			$aligns[]='L';
		}
		else	$aligns[]='L';
		$aligns=array_merge($aligns, array('R','R','R','R'));

		$pdf->SetAligns($aligns);
		$pdf->SetLineWidth(0.2);
		$pdf->SetFont('','',8);
		
		$to_sklad = (int) $this->dop_data['na_sklad'];
			
		$res = $db->query("SELECT `doc_group`.`printname` AS `group_pname`, `doc_base`.`name`, `doc_base`.`proizv` AS `vendor`, `doc_list_pos`.`cnt`,
			`doc_base_dop`.`mass`, `pt_s`.`mesto` AS `place_s`, `pt_d`.`mesto` AS `place_d`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`id`,
			`doc_base`.`vc`, `doc_list_pos`.`comm`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_base_dop` ON `doc_list_pos`.`tovar`=`doc_base_dop`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_cnt` AS `pt_s` ON `pt_s`.`id`=`doc_list_pos`.`tovar` AND `pt_s`.`sklad`='{$this->doc_data['sklad']}'
		LEFT JOIN `doc_base_cnt` AS `pt_d` ON `pt_d`.`id`=`doc_list_pos`.`tovar` AND `pt_d`.`sklad`='{$to_sklad}'
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$ii = 1;
		$sum = 0;
		while($nxt = $res->fetch_assoc()) {
			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt['vendor'])
				$nxt['name'].=' / '.$nxt['vendor'];

			$row = array($ii);
			$comment = array('');
			if($CONFIG['poseditor']['vc']) {
				$row[] = $nxt['vc'];
				$row[] = $nxt['group_pname'].' '.$nxt['name'];
				$comment[] = '';
			}
			else	$row[] = $nxt['group_pname'].' '.$nxt['name'];
			$comment[] = $nxt['comm'];
				
			$mass_p = sprintf("%01.3f кг", $nxt['cnt']*$nxt['mass']);
			
			$row = array_merge($row, array($nxt['cnt'].' '.$nxt['units'], $nxt['place_s'],  $nxt['place_d'], $mass_p));
			$comment  = array_merge($comment, array('', '',  '', ''));
			$pdf->RowIconvCommented($row, $comment);
			$ii++;
			$sum += $nxt['cnt']*$nxt['mass'];
		}
		$ii--;
		$sum = sprintf("%01.3f кг.", $sum);

		$pdf->Ln();

		$str="Всего $ii наименований общей массой $sum";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'L',0);

		$str="Выдал кладовщик: ____________________________________";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'L',0);

		$str="Вид и количество принятого товара совпадает с накладной. Внешние дефекты не обнаружены.";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="Принял кладовщик:_____________________________________";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'L',0);

		if($to_str)
			return $pdf->Output('blading.pdf','S');
		else
			$pdf->Output('blading.pdf','I');
	}

};
?>