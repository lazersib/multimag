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


$doc_types[8]="Перемещение товара";

class doc_Peremeshenie extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=8;
		$this->doc_name				='peremeshenie';
		$this->doc_viewname			='Перемещение товара со склада на склад';
		$this->sklad_editor_enable		=true;
		$this->sklad_modify			=0;
		$this->header_fields			='cena separator sklad';
		settype($this->doc,'int');
	}

	function DopHead()
	{
		global $tmpl;
		$klad_id=@$this->dop_data['kladovshik'];
		if(!$klad_id)	$klad_id=$this->firm_vars['firm_kladovshik_id'];
		$tmpl->AddText("На склад:<br>
		<select name='nasklad'>");
		$res=mysql_query("SELECT `id`,`name` FROM `doc_sklady` ORDER BY `name`");
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[0]==$this->dop_data['na_sklad'])
				$tmpl->AddText("<option value='$nxt[0]' selected>$nxt[1]</option>");
			else
				$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");
		}
		$tmpl->AddText("</select><br>
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
		<input type='text' name='mest' value='{$this->dop_data['mest']}'><br>");
	}

	function DopSave()
	{
		$nasklad=rcv('nasklad');
		$mest=rcv('mest');
		$kladovshik=rcv('kladovshik');
		$res=mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
		VALUES ('{$this->doc}','na_sklad','$nasklad'),
		( '{$this->doc}' ,'mest','$mest'),
		( '{$this->doc}' ,'kladovshik','$kladovshik')");
		if(!$res)		throw new MysqlException("Не удалось установить склад назначения в поступлении!");
	}

	function DopBody()
	{
		global $tmpl;
        	$res=mysql_query("SELECT `doc_sklady`.`name` FROM `doc_sklady`
		WHERE `doc_sklady`.`id`='{$this->dop_data['na_sklad']}'");

        	$nxt=mysql_fetch_row($res);
		$tmpl->AddText("<b>На склад:</b> $nxt[0]");
	}

	function DocApply($silent=0)
	{
		global $CONFIG;
		$tim=time();
		$nasklad=$this->dop_data['na_sklad'];
		if(!$nasklad)	throw new Exception("Не определён склад назначения!");
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
		FROM `doc_list`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)	throw new MysqlException('Ошибка выборки данных документа при проведении!');
		$nx=@mysql_fetch_assoc($res);
		if(!$nx)	throw new Exception('Документ не найден!');
		if( $nx['ok'] && (!$silent) )	throw new Exception('Документ уже был проведён!');
		if(!@$this->dop_data['mest'] && @$CONFIG['doc']['require_pack_count'] && !$silent)	throw new Exception("Количество мест не задано");

		$res=mysql_query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`pos_type`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$nx['sklad']}'
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		if(!$res)	throw new MysqlException('Ошибка получения списка товара в документе!');
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[5]>0)		throw new Exception("Перемещение услуги '$nxt[3]:$nxt[4]' недопустимо!");
			if(!$nx['dnc'])
			{
				if($nxt[1]>$nxt[2])	throw new Exception("Недостаточно ($nxt[1]) товара '$nxt[3]:$nxt[4]' на складе($nxt[2])!");
			}
			mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='{$nx['sklad']}'");
			if(mysql_error()) 	throw new MysqlException('Ошибка изменения количества на исходном складе!');
			mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`+'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nasklad'");
			if(mysql_error()) 	throw new MysqlException('Ошибка изменения количества на складе назначения!');
			// Если это первое поступление
			if(mysql_affected_rows()==0) mysql_query("INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`)
			VALUES ('$nxt[0]', '$nasklad', '$nxt[1]')");
			if(mysql_error()) 	throw new MysqlException('Ошибка изменения количества на складе назначения!');

			if( (!$nx['dnc']) && (!$silent))
			{
				$budet=getStoreCntOnDate($nxt[0], $nx['sklad']);
				if($budet<0)
					throw new Exception("Невозможно, т.к. будет недостаточно ($budet) товара '$nxt[3]:$nxt[4]' !");
			}
		}
		if($silent)	return;
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if(!$res)	throw new MysqlException('Ошибка установки даты проведения документа!');

	}

	function DocCancel()
	{
		global $uid;
		$tim=time();
		$nasklad=$this->dop_data['na_sklad'];

		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
		FROM `doc_list`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка выборки данных документа!');
		if(! ($nx=@mysql_fetch_assoc($res)))	throw new Exception('Документ не найден!');
		if(!$nx['ok'])				throw new Exception('Документ не проведён!');
		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка установки флага!');
		$res=mysql_query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$nx['sklad']}'
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка выборки товаров документа!');
		while($nxt=mysql_fetch_row($res))
		{
			mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nasklad'");
			if(mysql_error())		throw new Exception("Ошибка проведения, ошибка изменения количества на складе $nasklad!");
			mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`+'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='{$nx['sklad']}'");
			if(mysql_error())		throw new Exception("Ошибка проведения, ошибка изменения количества на складе {$nx['sklad']}!");
			if(!$nx['dnc'])
			{
				$budet=getStoreCntOnDate($nxt[0], $nx['sklad']);
				if($budet<0)			throw new Exception("Невозможно, т.к. будет недостаточно ($budet) товара '$nxt[3]' !");
			}
		}
	}

	function PrintForm($doc, $opt='')
	{
		if($opt=='')
		{
			global $tmpl;
			$tmpl->ajax=1;
			$tmpl->AddText("<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=prn'\">Накладная</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=pdf'\">Накладная PDF</div>");
		}
		else if($opt=='pdf')	$this->PrintNaklPDF();
 		else $this->PrintNakl($doc);

	}
	// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		global $tmpl;

		$tmpl->ajax=1;
		$tmpl->AddText("Не поддерживается для данного типа документа");

	}

	function Service($doc)
	{
		$tmpl->ajax=1;
		$opt=rcv('opt');
		$pos=rcv('pos');
		parent::_Service($opt,$pos);
	}
//	================== Функции только этого класса ======================================================

// -- Обычная накладная --------------
	function PrintNakl($doc)
	{
		get_docdata($doc);
		global $tmpl;
		global $uid;

		$tmpl->LoadTemplate('print');
		$dt=date("d.m.Y",$this->doc_data[5]);

		$res=mysql_query("SELECT `name` FROM `doc_sklady` WHERE `id`='{$this->doc_data['sklad']}'");
		if(mysql_errno())		throw new MysqlException("Не удалось получить информацию о складе-источнике");
		if(! mysql_num_rows($res))	throw new Exception("Склад не найден!");
		$line=mysql_fetch_row($res);
		if(!$line)			throw new Exception("Склад не найден!");
		$from_sklad=$line[0];

		$res=mysql_query("SELECT `name` FROM `doc_sklady` WHERE `id`='{$this->dop_data['na_sklad']}'");
		if(mysql_errno())		throw new MysqlException("Не удалось получить информацию о складе-назначении");
		if(! mysql_num_rows($res))	throw new Exception("Склад назначения не найден!");
		$line=mysql_fetch_row($res);
		if(!$line)			throw new Exception("Склад назначения не найден!");
		$to_sklad=$line[0];

		$tmpl->AddText("<h1>Накладная перемещения N {$this->doc_data[9]}{$this->doc_data[10]}, от $dt </h1>
		<b>Со склада: </b>$from_sklad<br>
		<b>На склад:</b> $to_sklad<br><br>");

		$tmpl->AddText("
		<table width=800 cellspacing=0 cellpadding=0>
		<tr><th>№</th><th width=450>Наименование<th>Кол-во<th>Стоимость<th>Сумма</tr>");
		$res=mysql_query("SELECT `doc_group`.`name`,`doc_base`.`name`,`doc_base`.`proizv` ,`doc_list_pos`.`cnt`,`doc_list_pos`.`cost`
		FROM `doc_list_pos`,`doc_base`,`doc_group`
		WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_list_pos`.`tovar`=`doc_base`.`id` AND `doc_group`.`id`=`doc_base`.`group`
		ORDER BY `doc_list_pos`.`id`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить список наименований");
		$i=0;
		$ii=1;
		$sum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f руб.", $nxt[4]);
			$cost2 = sprintf("%01.2f руб.", $sm);
			$tmpl->AddText("<tr align=right><td>$ii</td><td align=left>$nxt[0] $nxt[1] / $nxt[2]<td>$nxt[3]<td>$cost<td>$cost2");
			$i=1-$i;
			$ii++;
			$sum+=$sm;
		}
		$ii--;
		$cost = sprintf("%01.2f руб.", $sum);

		$tmpl->AddText("</table>
		<p>Всего <b>$ii</b> наименований на сумму <b>$cost</b></p>
		<p class=mini>Товар получил, претензий к качеству товара и внешнему виду не имею.</p>
		<p>Кладовщик:_____________________________________</p>");

	}

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

		$pdf->SetFont('','',16);
		$str="Накладная перемещения N {$this->doc_data[9]}{$this->doc_data[10]}, от $dt";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,8,$str,0,1,'C',0);

		$res=mysql_query("SELECT `name` FROM `doc_sklady` WHERE `id`='{$this->doc_data['sklad']}'");
		if(mysql_errno())		throw new MysqlException("Не удалось получить информацию о складе-источнике");
		if(! mysql_num_rows($res))	throw new Exception("Склад не найден!");
		$line=mysql_fetch_row($res);
		if(!$line)			throw new Exception("Склад не найден!");
		$from_sklad=$line[0];

		$res=mysql_query("SELECT `name` FROM `doc_sklady` WHERE `id`='{$this->dop_data['na_sklad']}'");
		if(mysql_errno())		throw new MysqlException("Не удалось получить информацию о складе-назначении");
		if(! mysql_num_rows($res))	throw new Exception("Склад назначения не найден!");
		$line=mysql_fetch_row($res);
		if(!$line)			throw new Exception("Склад назначения не найден!");
		$to_sklad=$line[0];

		$pdf->SetFont('','',10);
		$str="C: $from_sklad";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="На: $to_sklad";
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
		$t_text=array_merge($t_text, array('Место', 'Кол-во', 'Масса 1 ед.', 'Общ. масса'));

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

		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_base_dop`.`mass`, `doc_base_cnt`.`mesto`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`id`, `doc_base`.`vc`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_base_dop` ON `doc_list_pos`.`tovar`=`doc_base_dop`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->dop_data['na_sklad']}'
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$ii=1;
		$sum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.3f кг", $nxt[4]);
			$cost2 = sprintf("%01.3f кг", $sm);
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
		}
		$ii--;
		$sum = sprintf("%01.3f кг.", $sum);

		$pdf->Ln();

		$str="Всего $ii наименований общей массой $sum";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);

		$str="Выдал кладовщик: ____________________________________";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);

		$str="Вид и количество принятого товара совпадает с накладной. Внешние дефекты не обнаружены.";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="Принял кладовщик:_____________________________________";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);

		if($to_str)
			return $pdf->Output('blading.pdf','S');
		else
			$pdf->Output('blading.pdf','I');
	}

};
?>