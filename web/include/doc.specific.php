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


$doc_types[16]="Спецификация";

class doc_Specific extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				= 16;
		$this->doc_name				= 'specific';
		$this->doc_viewname			= 'Спецификация';
		$this->sklad_editor_enable		= true;
		$this->header_fields			= 'agent cena';
		settype($this->doc,'int');
	}
	
	function DopHead()
	{
		global $tmpl;
		$srok=$this->dop_data['srok'];
		$tmpl->AddText("Срок поставки:<br><input type='text' name='srok' value='$srok'><br>");	
		
	}

	function DopSave()
	{
		$srok=rcv('srok');
		$doc=$this->doc;
		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
		VALUES ( '{$this->doc}' ,'srok','$srok')");

	}
	
	function DopBody()
	{
		global $tmpl;
		$srok=$this->dop_data['srok'];
		$tmpl->AddText("<b>, cрок поставки:</b> $srok рабочих дней<br>");
	}

	function DocApply($silent=0)
	{
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)			throw new MysqlException('Ошибка выборки данных документа при проведении!');
		$nx=@mysql_fetch_row($res);
		if(!$nx)			throw new Exception('Документ не найден!');
		if( $nx[1] && (!$silent) )	throw new Exception('Документ уже был проведён!');
		if($silent)	return;
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if(!$res)			throw new MysqlException('Ошибка установки даты проведения документа!');	
	}
	
	function DocCancel()
	{
		global $uid;
		$tim=time();
		$rights=getright('doc_'.$this->doc_name,$uid);		
		if(!$rights['edit'])			throw new AccessException('');
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");		
		if(!$res)				throw new MysqlException('Ошибка выборки данных документа!');
		if(! ($nx=@mysql_fetch_row($res)))	throw new Exception('Документ не найден!');
		if(! $nx[4])				throw new Exception('Документ НЕ проведён!');
		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка установки флага!');
	}

	function PrintForm($doc, $opt='')
	{
		global $tmpl;
		if($opt=='')
		{
			$tmpl->ajax=1;
			$tmpl->AddText("<a href='?mode=print&amp;doc={$this->doc}&amp;opt=print_pdf'><div>Спецификация (PDF)</div></a>");
		}
		else if($opt=='print_pdf')
			$this->PrintPDF();
		else $tmpl->logger("Запрошена неизвестная опция!");
	}

	// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		global $tmpl;
		if($target_type=='')
		{
			$tmpl->ajax=1;
			$tmpl->AddText("
			<a href='?mode=morphto&amp;doc={$this->doc}&amp;tt=3'><div>Заявка покупателя</div></a>");
		}
		else if($target_type==3)
		{
			mysql_query("START TRANSACTION");
			$base=$this->Zayavka($this->doc);
			if(!$base)
			{
				mysql_query("ROLLBACK");
				$tmpl->msg("Не удалось создать подчинённый документ!","err");
			}
			else
			{
				mysql_query("COMMIT");
				$ref="Location: doc.php?mode=body&doc=$base";
				header($ref);
			}
		}
	}

	// Выполнить удаление документа. Если есть зависимости - удаление не производится.
	function DelExec($doc)
	{
		$res=mysql_query("SELECT `ok` FROM `doc_list` WHERE `id`='{$this->doc}'");
		if(!mysql_result($res,0,0)) // Если проведён - нельзя удалять
		{
			$res=mysql_query("SELECT `id`, `mark_del` FROM `doc_list` WHERE `p_doc`='{$this->doc}'");
			if(!mysql_num_rows($res)) // Если есть потомки - нельзя удалять
			{
				mysql_query("DELETE FORM `doc_list_pos` WHERE `doc`='{$this->doc}'");
				mysql_query("DELETE FROM `doc_dopdata` WHERE `doc`='{$this->doc}'");
				mysql_query("DELETE FROM `doc_list` WHERE `id`='{$this->doc}'");
				return 0;
			}
		}
		return 1;
	}
	
//	================== Функции только этого класса ======================================================
	function Zayavka()
	{
		$target_type=3;
		global $tmpl;
		global $uid;

		$altnum=GetNextAltNum($target_type, $this->doc_data[10]);
		$tm=time();
		$sum=DocSumUpdate($this->doc);
		$res=mysql_query("INSERT INTO `doc_list`
		(`type`, `agent`, `date`, `sklad`, `user`, `altnum`, `subtype`, `p_doc`, `sum`, `nds`, `firm_id`)
		VALUES ('$target_type', '{$this->doc_data[2]}', '$tm', '{$this->doc_data[7]}', '$uid', '$altnum', '{$this->doc_data[10]}', '{$this->doc}', '$sum', '{$this->doc_data[12]}', '{$this->doc_data[17]}')");
		
		$r_id= mysql_insert_id();

		if(!$r_id) return 0;
		
		doc_log("CREATE", "FROM {$this->doc_name} {$this->doc}", 'doc', $r_id);
		
		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
		VALUES ('$r_id','cena','{$this->dop_data['cena']}')");

		$res=mysql_query("SELECT `tovar`, `cnt`, `sn`, `comm`, `cost` FROM `doc_list_pos`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		while($nxt=mysql_fetch_row($res))
		{
			mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `sn`, `comm`, `cost`)
			VALUES ('$r_id', '$nxt[0]', '$nxt[1]', '$nxt[2]', '$nxt[3]', '$nxt[4]' )");
		}

		return $r_id;
	}

	function Service($doc)
	{
		$tmpl->ajax=1;
		$opt=rcv('opt');
		$pos=rcv('pos');
		parent::_Service($opt,$pos);
	}
	

	
	function PrintPDF($to_str=0)
	{
		define('FPDF_FONT_PATH','/var/www/gate/fpdf/font/');
		require('fpdf/fpdf_mysql.php');

		global $tmpl, $uid, $CONFIG;
		
		$dt=date("d.m.Y",$this->doc_data[5]);
		
		if($coeff==0) $coeff=1;
		if(!$to_str) $tmpl->ajax=1;
		
		$pdf=new FPDF('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(1,12);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=5;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);
		
		if($CONFIG['site']['doc_header'])
		{
			$pdf->Image($CONFIG['site']['doc_header'],8,10, 190);	
			$pdf->Sety(54);
		}
		
		$res=mysql_query("SELECT `altnum`, `date` FROM `doc_list` WHERE `id`='{$this->doc_data['p_doc']}'");
		if(mysql_errno())	throw new MysqlException("Невозможно получить номер договора!");
		$dog=mysql_fetch_assoc($res);
		if(!$dog)		throw new MysqlException("Спецификация должна быть подчинена договору!");
		
		$dog['date']=date("Y-m-d",$dog['date']);
		$pdf->SetFont('','',12);	
		$str="К договору N{$dog['altnum']} от {$dog['date']}";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'R',0);
		$pdf->Ln(5);
		
		$pdf->SetFont('','',20);
		$str='Спецификация № '.$this->doc_data[9].' от '.$dt;
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell(0,6,$str,0,1,'C',0);
		$str="на поставку продукции";
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell(0,6,$str,0,1,'C',0);		
		$pdf->Ln(10);

		
		$t_width=array(8,95,20,32,0);
		$pdf->SetFont('','',12);
		$str='№';
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell($t_width[0],5,$str,1,0,'C',0);
		$str='Наименование продукции';
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell($t_width[1],5,$str,1,0,'C',0);
		$str='Кол-во';
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell($t_width[2],5,$str,1,0,'C',0);
		$str="Цена без НДС";
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell($t_width[3],5,$str,1,0,'C',0);
		$str="Cумма, без НДС";
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell($t_width[4],5,$str,1,0,'C',0);
		$pdf->Ln();
		
		$pdf->SetFont('','',10);
		
		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_dop`.`mass`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		$i=$allsum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$i++;
			$cost = sprintf("%01.2f р.", $nxt[4]);
			$sum = sprintf("%01.2f р.", $nxt[4]*$nxt[3] );
			$allsum+=$nxt[4]*$nxt[3];
			$pdf->Cell($t_width[0],5,$i,1,0,'R',0);
			$str=$nxt[0].' '.$nxt[1];
			if($nxt[2]) $str.='('.$nxt[2].')';
			$str = iconv('UTF-8', 'windows-1251', $str);	
			$pdf->Cell($t_width[1],5,$str,1,0,'L',0);
			$pdf->Cell($t_width[2],5,$nxt[3],1,0,'R',0);
			
			$str = iconv('UTF-8', 'windows-1251', $cost);	
			$pdf->Cell($t_width[3],5,$str,1,0,'R',0);
			$str = iconv('UTF-8', 'windows-1251', $sum);	
			$pdf->Cell($t_width[4],5,$str,1,0,'R',0);
			$pdf->Ln();
		}
		
		if($pdf->h<=($pdf->GetY()+40)) $pdf->AddPage();
	
		$pdf->ln(10);
		
		if($this->doc_data[4])
		{
			$pdf->SetFont('','',10);
			$str = iconv('UTF-8', 'windows-1251', $this->doc_data[4]);	
			$pdf->MultiCell(0,5,$str,0,1,'R',0);		
			$pdf->ln(6);
		}
		
		$pdf->SetFont('','',12);
		$allsum_p = sprintf("%01.2f", $allsum);
		$str="Общая сумма спецификации N {$this->doc_data[9]} с учетом НДС составляет $allsum_p рублей.";
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell(0,5,$str,0,1,'C',0);
		
		$str="Срок поставки - в течение {$this->dop_data['srok']} рабочих дней с момента поступления товара на склад поставщика.";
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell(0,5,$str,0,1,'C',0);
		$pdf->Ln(10);
		
		$pdf->SetFont('','',16);
		$str="Покупатель";
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell(100,6,$str,0,0,'L',0);
		$str="Поставщик";
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell(0,6,$str,0,0,'L',0);

		$pdf->Ln(5);
		$pdf->SetFont('','',10);
		$res=mysql_query("SELECT `doc_agent`.`id`, `doc_agent`.`fullname`, `doc_agent`.`adres`,  `doc_agent`.`tel`, `doc_agent`.`inn`, `doc_agent`.`okpo`, `doc_agent`.`okevd`, `doc_agent`.`bik`, `doc_agent`.`rs`, `doc_agent`.`ks`, `doc_agent`.`bank`, `doc_agent`.`pfio`, `doc_agent`.`pdol`
		FROM `doc_agent` WHERE `doc_agent`.`id`='{$this->doc_data[2]}'	");
		if(mysql_errno())		throw new MysqlException("Невозможно получить данные агента!");
		
		$agent_info=mysql_fetch_array($res);
		
		$str=unhtmlentities("$agent_info[1]\n$agent_info[2], тел. $agent_info[3]\nИНН/КПП $agent_info[4], ОКПО $agent_info[5], ОКВЭД $agent_info[6]\nР/С $agent_info[8], в банке $agent_info[10]\nК/С $agent_info[9], БИК $agent_info[7]");
		$str = iconv('UTF-8', 'windows-1251', $str);
		
		$y=$pdf->GetY();
		
		$pdf->MultiCell(100,5,$str,0,'L',0);
		$pdf->SetY($y);
		$pdf->SetX(110);

		$str=unhtmlentities("{$this->firm_vars['firm_name']}\n{$this->firm_vars['firm_adres']}\nИНН/КПП {$this->firm_vars['firm_inn']}\nР/С {$this->firm_vars['firm_schet']}, в банке {$this->firm_vars['firm_bank']}\nК/С {$this->firm_vars['firm_bank_kor_s']}, БИК {$this->firm_vars['firm_bik']}");
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0,5,$str,0,'L',0);
		
		$res=mysql_query("SELECT `rname`, `tel`, `email` FROM `users` WHERE `id`='{$this->doc_data[8]}'");
		$name=@mysql_result($res,0,0);
		if(!$name) $name='('.$_SESSION['name'].')';
		$tel=@mysql_result($res,0,1);
		$email=@mysql_result($res,0,2);
		
		$pdf->SetAutoPageBreak(0,10);
		$pdf->SetY($pdf->h-18);
		$pdf->Ln(1);
		$pdf->SetFont('','',10);
		$str="Исп. менеджер $name";
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell(0,4,$str,0,1,'R',0);
		$str="Контактный телефон: $tel";
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell(0,4,$str,0,1,'R',0);
		$str="Электронная почта: $email";
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell(0,4,$str,0,1,'R',0);
		
		if($to_str)
			return $pdf->Output('buisness_offer.pdf','S');
		else
			$pdf->Output('specific.pdf','I');
	}
};
?>