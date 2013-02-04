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


$doc_types[20]="Реализация";

/// Документ *Реализация за бонусы*
class doc_Realiz_bonus extends doc_Realizaciya
{

	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=20;
		$this->doc_name				='realiz_bonus';
		$this->doc_viewname			='Реализация товара за бонусы';
		$this->sklad_editor_enable		=true;
		$this->sklad_modify			=-1;
		$this->header_fields			='sklad cena separator agent';
		$this->dop_menu_buttons			="<a href='' onclick=\"ShowPopupWin('/doc.php?mode=srv&amp;opt=dov&amp;doc=$doc'); return false;\" title='Доверенное лицо'><img src='img/i_users.png' alt='users'></a>";
		settype($this->doc,'int');
		$this->PDFForms=array(
			array('name'=>'nak','desc'=>'Накладная','method'=>'PrintNaklPDF'),
			array('name'=>'tg12','desc'=>'Накладная ТОРГ-12','method'=>'PrintTg12PDF'),	/// А нужно ли?
			array('name'=>'nak_kompl','desc'=>'Накладная на комплектацию','method'=>'PrintNaklKomplektPDF'),
		);
	}

	function DopHead()
	{
		global $tmpl;

		$cur_agent=$this->doc_data['agent'];
		if(!$cur_agent)		$cur_agent=1;
		$klad_id=@$this->dop_data['kladovshik'];
		if(!$klad_id)	$klad_id=$this->firm_vars['firm_kladovshik_id'];

		if(!$this->dop_data['platelshik'])	$this->dop_data['platelshik']=$cur_agent;
		if(!$this->dop_data['gruzop'])		$this->dop_data['gruzop']=$cur_agent;

		$res=mysql_query("SELECT `name` FROM `doc_agent` WHERE `id`='{$this->dop_data['platelshik']}'");
		if(mysql_errno())	throw new MysqlException('Ошибка выборки имени плательщика');
		$plat_name=mysql_result($res,0,0);

		$res=mysql_query("SELECT `name` FROM `doc_agent` WHERE `id`='{$this->dop_data['gruzop']}'");
		if(mysql_errno())	throw new MysqlException('Ошибка выборки имени грузополучателя');
		$gruzop_name=mysql_result($res,0,0);

		$tmpl->AddText("
		Кладовщик:<br><select name='kladovshik'>");
		$res=mysql_query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить имя кладовщика");
		$tmpl->AddText("<option value='0'>--не выбран--</option>");
		while($nxt=mysql_fetch_row($res))
		{
			$s=($klad_id==$nxt[0])?'selected':'';
			$tmpl->AddText("<option value='$nxt[0]' $s>$nxt[1]</option>");
		}
		$tmpl->AddText("</select><br>
		Количество мест:<br>
		<input type='text' name='mest' value='{$this->dop_data['mest']}'><br>
		<script type=\"text/javascript\">
		</script>
		");
	}

	function DopSave()
	{
		$kladovshik=rcv('kladovshik');
		$mest=rcv('mest');
		settype($kladovshik, 'int');

		$doc=$this->doc;
		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
		VALUES
		( '{$this->doc}' ,'kladovshik','$kladovshik'),
		( '{$this->doc}' ,'mest','$mest')");
		if($this->doc)
		{
			$log_data='';
			if(@$this->dop_data['kladovshik']!=$kladovshik)		$log_data.=@"kladovshik: {$this->dop_data['kladovshik']}=>$kladovshik, ";
			if(@$this->dop_data['mest']!=$mest)			$log_data.=@"mest: {$this->dop_data['mest']}=>$mest, ";
			if(@$log_data)	doc_log("UPDATE {$this->doc_name}", $log_data, 'doc', $this->doc);
		}
	}

	function DopBody()
	{

	}

	function DocApply($silent=0)
	{
		global $CONFIG;
		$tim=time();
		if(!$silent)	$bonus=DocCalcBonus($this->doc_data['agent']);;
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
		FROM `doc_list`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list`.`id`='{$this->doc}'");
		if( !($nx=@mysql_fetch_assoc($res) ) )	throw new MysqlException('Ошибка выборки данных документа при проведении!');
		if( $nx['ok'] && ( !$silent) )		throw new Exception('Документ уже был проведён!');
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if( !$res )				throw new MysqlException('Ошибка проведения, ошибка установки даты проведения!');
		if(!@$this->dop_data['kladovshik'] && @$CONFIG['doc']['require_storekeeper'] && !$silent)	throw new Exception("Кладовщик не выбран!");
		if(!@$this->dop_data['mest'] && @$CONFIG['doc']['require_pack_count'] && !$silent)	throw new Exception("Количество мест не задано");
		$res=mysql_query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`pos_type`, `doc_list_pos`.`id`, `doc_base`.`vc`, `doc_list_pos`.`cost`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$nx['sklad']}'
		WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_base`.`pos_type`='0'");
		$sum=0;
		while($nxt=mysql_fetch_row($res))
		{
			if(!$nx['dnc'])
			{
				if($nxt[1]>$nxt[2])	throw new Exception("Недостаточно ($nxt[1]) товара '$nxt[3]:$nxt[4] - $nxt[7]($nxt[0])': на складе только $nxt[2] шт!");
			}
			mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='{$nx['sklad']}'");
			if(mysql_error())	throw new MysqlException('Ошибка проведения, ошибка изменения количества!');

			if(!$nx['dnc'] && (!$silent))
			{
				$budet=getStoreCntOnDate($nxt[0], $nx['sklad']);
				if( $budet<0)		throw new Exception("Невозможно ($silent), т.к. будет недостаточно ($budet) товара '$nxt[3]:$nxt[4] - $nxt[7]($nxt[0])'!");
			}

			if(@$CONFIG['poseditor']['sn_restrict'])
			{
				$r=mysql_query("SELECT COUNT(`doc_list_sn`.`id`) FROM `doc_list_sn` WHERE `rasx_list_pos`='$nxt[6]'");
				$sn_cnt=mysql_result($r,0,0);
				if($sn_cnt!=$nxt[1])	throw new Exception("Количество серийных номеров товара $nxt[0] ($nxt[1]) не соответствует количеству серийных номеров ($sn_cnt)");
			}
		}
		
		if($silent)	return;
		if($this->doc_data['sum']>$bonus)		throw new Exception("У агента недостаточно бонусов");
		mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if(mysql_errno())				throw new MysqlException('Ошибка проведения, ошибка установки даты проведения!');
		if($this->doc_data['p_doc'])
		{
			$doc=AutoDocument($this->doc_data['p_doc']);
			if($doc->doc_type==3)
			{
				$doc->setStatus('ok');

			}
		}
	}

	function DocCancel()
	{
		global $uid;
		$tmpl->ajax=1;
		$tim=time();
		$dd=date_day($tim);

		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка выборки данных документа!');
		if(! ($nx=@mysql_fetch_row($res)))	throw new Exception('Документ не найден!');
		if(! $nx[4])				throw new Exception('Документ НЕ проведён!');

		$res=mysql_query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$this->doc}' AND `ok`>'0'");
		if(!$res)				throw new MysqlException('Ошибка выборки потомков документов!');
		if(mysql_num_rows($res))		throw new Exception('Документ оплачен! Нельзя отменять!');

		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка установки флага!');
		$res=mysql_query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base`.`pos_type` FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`	WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_base`.`pos_type`='0'");
		if(mysql_errno())			throw new MysqlException('Ошибка выбоки товаров документа!');

		while($nxt=mysql_fetch_row($res))
		{
			mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`+'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nx[3]'");
			if(mysql_error())	throw new MysqlException("Ошибка изменения количества товара id:$nxt[0] на складе $nx[3]!");
		}
	}

	function PrintForm($doc, $opt='')
	{
		global $tmpl;
		if($opt=='')
		{

			$tmpl->ajax=1;
			$tmpl->AddText("
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=nak'\">Накладная</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=nak_pdf'\">Накладная PDF</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=nak_kompl_pdf'\">Накладная на комплектацию PDF</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=tg12_pdf'\">Накладная ТОРГ-12 (PDF)</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=nvco'\">Накладная c сорт. по коду</div>");
		}
		else if($opt=='tg12_pdf')
			$this->PrintTg12PDF();
		else if($opt=='nvco')
			$this->PrintNaklVCOrdered();
		else if($opt=='nak_pdf')
			$this->PrintNaklPDF();
		else if($opt=='nak_kompl_pdf')
			$this->PrintNaklKomplektPDF();
		else
			$this->PrintNakl($doc);
	}

//	================== Функции только этого класса ======================================================

/// Обычная накладная в PDF формате
/// @param to_str Вернуть строку, содержащую данные документа (в противном случае - отправить файлом)
	function PrintNaklPDF($to_str=false)
	{
		define('FPDF_FONT_PATH','/var/www/gate/fpdf/font/');
		require('fpdf/fpdf_mc.php');
		global $tmpl, $CONFIG, $uid;

		if(!$to_str) $tmpl->ajax=1;

		$pdf=new PDF_MC_Table('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(0,10);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=10;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		$dt=date("d.m.Y",$this->doc_data[5]);

		$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='1'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить цену по умолчанию");
		$def_cost=mysql_result($res,0,0);
		if(!$def_cost)		throw new Exception("Цена по умолчанию не определена!");

		$pdf->SetFont('','',16);
		$str="Накладная N {$this->doc_data[9]}{$this->doc_data[10]} ({$this->doc}), от $dt";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,8,$str,0,1,'C',0);
		$pdf->SetFont('','',10);
		$str="Поставщик: {$this->firm_vars['firm_name']}, тел: {$this->firm_vars['firm_telefon']}";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="Покупатель: {$this->doc_data[3]}";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$pdf->Ln();

		$pdf->SetLineWidth(0.5);
		$t_width=array(8);
		if($CONFIG['poseditor']['vc'])
		{
			$t_width[]=20;
			$t_width[]=91;
		}
		else	$t_width[]=111;
		$t_width=array_merge($t_width, array(12,15,23,23));

		$t_text=array('№');
		if($CONFIG['poseditor']['vc'])
		{
			$t_text[]='Код';
			$t_text[]='Наименование';
		}
		else	$t_text[]='Наименование';
		$t_text=array_merge($t_text, array('Место', 'Кол-во', 'Стоимость', 'Сумма'));

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
		$aligns=array_merge($aligns, array('C','R','R','R'));

		$pdf->SetAligns($aligns);
		$pdf->SetLineWidth(0.2);
		$pdf->SetFont('','',8);

		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`mesto`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`id`, `doc_base`.`vc`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data[7]}'
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$ii=1;
		$sum=0;
		$skid_sum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f руб.", $nxt[4]);
			$cost2 = sprintf("%01.2f руб.", $sm);
			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];

			$row=array($ii);
			if($CONFIG['poseditor']['vc'])
			{
				$row[]=$nxt[8];
				$row[]="$nxt[0] $nxt[1]";
			}
			else	$row[]="$nxt[0] $nxt[1]";
			$row=array_merge($row, array($nxt[5], "$nxt[3] $nxt[6]", $cost, $cost2));

			$pdf->RowIconv($row);
			$i=1-$i;
			$ii++;
			$sum+=$sm;
			$skid_sum+=GetCostPos($nxt[7], $def_cost)*$nxt[3];
		}
		$ii--;
		$cost = sprintf("%01.2f руб.", $sum);

		$pdf->Ln();

		$str="Всего $ii наименований на сумму $cost";
		if($this->dop_data['mest'])	$str.=", мест: ".$this->dop_data['mest'];
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);

		if($prop)
		{
			$prop="Бонусный баланс: ".DocCalcBonus($this->doc_data['agent']);
			$str = iconv('UTF-8', 'windows-1251', unhtmlentities($prop));
			$pdf->Cell(0,5,$str,0,1,'L',0);
		}

		$str="Товар получил, претензий к качеству товара и внешнему виду не имею.";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="Покупатель: ____________________________________";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="Поставщик:_____________________________________";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);

		if($to_str)
			return $pdf->Output('blading.pdf','S');
		else
			$pdf->Output('blading.pdf','I');
	}



};
?>