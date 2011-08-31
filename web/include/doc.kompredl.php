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


$doc_types[13]="Коммерческое предложение";

class doc_Kompredl extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				= 13;
		$this->doc_name				= 'kompredl';
		$this->doc_viewname			= 'Коммерческое предложение';
		$this->sklad_editor_enable		= true;
		$this->header_fields			= 'agent cena sklad bank';
		settype($this->doc,'int');
	}

	function DopHead()
	{
		global $tmpl;
		$tmpl->AddText("Текст шапки:<br><textarea name='shapka'>{$this->dop_data['shapka']}</textarea><br>");
	}

	function DopSave()
	{
		$shapka=rcv('shapka');
		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
		VALUES ( '{$this->doc}' ,'shapka','$shapka')");
	}
	
	function DopBody()
	{
		global $tmpl;
		if($this->dop_data['shapka'])
			$tmpl->AddText("<b>Текст шапки:</b> {$this->dop_data['shapka']}");
		else 	$tmpl->AddText("<br><b style='color: #f00'>ВНИМАНИЕ! Текст шапки не указан!</b><br>");	
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
		if($opt=='')
		{
			global $tmpl;
			$tmpl->ajax=1;
			$tmpl->AddText("<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=kom_pdf'\">Коммерческое предложение</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=kom_all'\">Коммерческое предложение (рассылка)</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=kom_pdf_cnt'\">Коммерческое предложение (с количеством)</div>
			<div onclick=\"ShowPopupWin('/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=kom_email')\">Коммерческое предложение (email)</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=csv_export'\">Экспорт в CSV</div>");
		}
		else if($opt=='kom_all')
			$this->KomPredlRassilka();
		else if($opt=='kom_pdf')
			$this->KomPredlPDF();
		else if($opt=='kom_pdf_cnt')
			$this->KomPredlPDF_Cnt();
		else if($opt=='kom_email')
			$this->SendEmail();	
		else if($opt=='csv_export')
			$this->CSVExport();
		else $tmpl->logger("Запрошена неизвестная опция!");
	}

	// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		global $tmpl;
		if($target_type=='')
		{
			$tmpl->ajax=1;
			$tmpl->AddText("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=3'\">Заявка покупателя</div>");
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

		$altnum=GetNextAltNum($target_type, $this->doc_data['subtype']);
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

		$res=mysql_query("SELECT `tovar`, `cnt`, `comm`, `cost` FROM `doc_list_pos`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		while($nxt=mysql_fetch_row($res))
		{
			mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `comm`, `cost`)
			VALUES ('$r_id', '$nxt[0]', '$nxt[1]', '$nxt[2]', '$nxt[3]' )");
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
	
	function SendEMail($email='')
	{
		global $tmpl;
		if(!$email)
			$email=rcv('email');
		
		if($email=='')
		{
			$tmpl->ajax=1;
			$res=mysql_query("SELECT `email` FROM `doc_agent` WHERE `id`='{$this->doc_data[2]}'");
			$email=mysql_result($res,0,0);
			$tmpl->AddText("<form action=''>
			<input type='hidden' name='mode' value='print'>
			<input type='hidden' name='doc' value='{$this->doc}'>
			<input type='hidden' name='opt' value='kom_email'>
			email:<input type='text' name='email' value='$email'><br>
			Коментарий:<br>
			<textarea name='comm'></textarea><br>
			<button type='submit'>&gt;&gt;</button>
			</form>");	
		}
		else
		{
			global $mail;
			$comm=rcv('comm');
			$sender_name=$_SESSION['name'];
			
			$res=mysql_query("SELECT `rname`, `tel`, `email` FROM `users` WHERE `id`='{$this->doc_data[8]}'");
			$manager_name=@mysql_result($res,0,0);	
			$manager_tel=@mysql_result($res,0,1);
			$manager_email=@mysql_result($res,0,2);	
			
			if(!$manager_email)
			{
				$mail->Body = "Доброго времени суток!\nВо вложении находится запрошенное Вами коммерческое предложение от {$CONFIG['site']['name']}\n\n$comm\n\nСообщение сгенерировано автоматически, отвечать на него не нужно! Для переписки используйте адрес, указанный на сайте http://{$CONFIG['site']['name']}!";
			}
			else
			{
				$mail->Body = "Доброго времени суток!\nВо вложении находится запрошенное Вами коммерческое предложение от {$CONFIG['site']['name']}\n\n$comm\n\nИсполнительный менеджер $manager_name\nКонтактный телефон: $manager_tel\nЭлектронная почта (e-mail): $manager_email\nОтправитель: $sender_name";
 				$mail->Sender   = $manager_email;  
 				$mail->From     = $manager_email;  
 				//$mail->FromName = "{$mail->FromName} ({$manager_name})";
			}

			$mail->AddAddress($email, $email );  
			$mail->Subject="Коммерческое предложение от {$CONFIG['site']['name']}";
			
			$mail->AddStringAttachment($this->KomPredlPDF(1), "buissness_offer.pdf");  
			if($mail->Send())
				$tmpl->msg("Сообщение отправлено!","ok");
			else
				$tmpl->msg("Ошибка отправки сообщения!",'err');
		}	
	}

//	================== Функции только этого класса ======================================================
	
	function KomPredlRassilka()
	{
		global $tmpl;
		global $uid;
		global $mail;
		global $CONFIG;
		$tmpl->ajax=0;
		$ok=rcv('ok');
		if($ok=='')
		{
			$i=0;
			$tmpl->AddText("<h1>Рассылка коммерческого предложения</h1>
			<form action='' method='post'>
			<input type=hidden name=mode value='print'>
			<input type=hidden name=doc value='{$this->doc}'>
			<input type=hidden name=opt value='kom_all'>
			<input type=hidden name='ok' value='ok'>
			<table width='100%'><tr><th>!<th>Название<th>Полное название<th>e-mail</tr>");
			
			$res=mysql_query("SELECT `users`.`name`, `a`.`value`, `users`.`rname`, `users`.`email` FROM `users`
			LEFT JOIN `users_data` AS `a` ON `a`.`uid`=`users`.`id` AND `a`.`param`='org'
			WHERE `users`.`subscribe`='1' AND `users`.`confirm`='0'");
			
			$tmpl->AddText("<tr><th colspan='4'>Пользователи, выразившие желание получать рассылки");
			while($nxt=mysql_fetch_row($res))
			{
				$i=1-$i;
				$tmpl->AddText("<tr class='lin$i'><td><input type='checkbox' name='email[]' value='$nxt[3]' checked><td>$nxt[0]<td>$nxt[1] $nxt[2]<td>$nxt[3]");
			}
		
			$res=mysql_query("SELECT `doc_agent`.`name`, `doc_agent`.`fullname`, `doc_agent`.`email`
			FROM `doc_agent`
			WHERE `doc_agent`.`no_mail`='0' AND `doc_agent`.`email`!=''");
			echo mysql_error();
			$tmpl->AddText("<tr><th colspan='4'>Клиенты, которым возможно отправить предложение");
			while($nxt=mysql_fetch_row($res))
			{
				$i=1-$i;
				$tmpl->AddText("<tr class='lin$i'><td><input type='checkbox' name='email[]' value='$nxt[2]'><td>$nxt[0]<td>$nxt[1]<td>$nxt[2]");
			}
			
			$res=mysql_query("SELECT `doc_agent`.`name`, `doc_agent`.`fullname`, `doc_agent`.`email`
			FROM `doc_agent`
			WHERE `doc_agent`.`no_mail`!='0' AND `doc_agent`.`email`!=''");
			
			$tmpl->AddText("<tr><th colspan='4'>Клиенты, не желающие получать рассылки");
			while($nxt=mysql_fetch_row($res))
			{
				$i=1-$i;
				$tmpl->AddText("<tr class='lin$i'><td>-<td>$nxt[0]<td>$nxt[1]<td>$nxt[2]");
			}
		
			$tmpl->AddText("</table>
			<input type='submit' value='Разослать предложение по электронной почте выбранным адресатам'>
			</form>");
		}
		else
		{

			$msg="Доброго времени суток!\n Просим Вас рассмотреть возможность закупки следующей продукции:\n\n";
			
			$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`cost`
			FROM `doc_list_pos`
			LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
			LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
			WHERE `doc_list_pos`.`doc`='{$this->doc}'");
			while($nxt=mysql_fetch_row($res))
			{
				$msg.="$nxt[0] $nxt[1] ($nxt[2]) - цена $nxt[3] руб.\n";
			}
			$msg.="\n\nВо вложении находится печатная версия этого предложения.\nЗаказать данную продукцию вы можете на нашем сайте http://{$CONFIG['site']['name']}. Так же с нашего сайта можно загрузить полный правйс-лист, или воспользоваться интернет-витриной. При заказе через сайт предоставляются скидки! Если для Вас по каким-либо причинам заказ через сайт не возможен, можно воспользоваться альтернативными способами связи:\nТелефоны: ".$this->firm_vars['firm_telefon']."\nЭлектронная почта: {$CONFIG['site']['doc_adm_email']}\nJabber(XMPP): {$CONFIG['site']['doc_adm_jid']}\n";
			$msg.="\n-----------------------------------------------------\nВы получили это письмо потому что подписаны на рассылку сайта http://{$CONFIG['site']['name']}, либо являетесь клиентом {$this->firm_vars['firm_name']}, не отказавшимся от рассылки.\nОтказаться от рассылки можно, перейдя по ссылке http://{$CONFIG['site']['name']}/login.php?mode=unsubscribe&email=";
			
			global $mail;
			//$mail->ContentType='text/plain';
			$mail->Subject='Коммерческое предложение от '.$CONFIG['site']['name'];
			$mail->AddStringAttachment($this->KomPredlPDF(1), "buissness_offer.pdf");  
			$mail->ClearAddress();
			
			$email=@$_POST['email'];
			foreach($email	as	$line)
			{
					$mail->Body = $msg.$line;
					$mail->AddAddress($line, $line);  
					if($mail->Send())
						$tmpl->msg("Сообщение для *$line* отправлено!","ok");
					else
						$tmpl->msg("Ошибка отправки сообщения!",'err');
					$mail->ClearAddress();
			}
			
 		}
	}
	
	function KomPredlPDF($to_str=0)
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
			$header_img=str_replace('{FN}', $this->doc_data['firm_id'], $CONFIG['site']['doc_header']);
			$pdf->Image($header_img,8,10, 190);	
			$pdf->Sety(54);
		}
		
		$res=mysql_query("SELECT `name`, `bik`, `rs`, `ks` FROM `doc_kassa` WHERE `ids`='bank' AND `num`='{$this->doc_data[16]}'");
		$bank_data=mysql_fetch_row($res);
		
		$str='Банковские реквизиты:';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->SetFont('','',11);
		$pdf->Cell(0,5,$str,0,1,'C',0);
		
		$old_x=$pdf->GetX();
		$old_y=$pdf->GetY();
		$old_margin=$pdf->lMargin;
		$table_c=110;
		$table_c2=15;
		
		$pdf->SetFont('','',12);
		$str=$bank_data[0];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c,10,$str,1,1,'L',0);
		$str='ИНН '.$this->firm_vars['firm_inn'].' КПП';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c,5,$str,1,1,'L',0);
		$str=unhtmlentities($this->firm_vars['firm_name']);
		$str = iconv('UTF-8', 'windows-1251', $str);
		$tx=$pdf->GetX();
		$ty=$pdf->GetY();
		$pdf->Cell($table_c,10,'',1,1,'L',0);
		
		$pdf->lMargin=$old_x+1;
		$pdf->SetX($tx+1);
		$pdf->SetY($ty+1);
		$pdf->SetFont('','',9);
		$pdf->MultiCell($table_c,3,$str,0,1,'L',0);

		$pdf->SetFont('','',12);
		$pdf->lMargin=$old_x+$table_c;
		$pdf->SetY($old_y);
		$str='БИК';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,5,$str,1,1,'L',0);
		$str='корр/с';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,10,$str,1,1,'L',0);
		$str='р/с N';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,10,$str,1,1,'L',0);
		
		$pdf->lMargin=$old_x+$table_c+$table_c2;
		$pdf->SetY($old_y);
		$str=$bank_data[1];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,1,1,'L',0);
		$str=$bank_data[3];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,1,1,'L',0);
		$str=$bank_data[2];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,15,$str,1,1,'L',0);
		
		$pdf->lMargin=$old_margin;
		$pdf->SetY($old_y+30);
		
		$pdf->SetFont('','',20);
		$str='Коммерческое предложение № '.$this->doc_data[9].' от '.$dt;
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell(0,5,$str,0,1,'C',0);
		$pdf->Ln(10);
		$pdf->SetFont('','',10);
		$str=unhtmlentities('Поставщик: '.$this->firm_vars['firm_name'].', '.$this->firm_vars['firm_telefon']);
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->MultiCell(0,5,$str,0,1,'L',0);
		$pdf->Ln(10);
		
		$str=$this->dop_data['shapka'];
		if($str)
		{
			$pdf->SetFont('','',16);
			$str = iconv('UTF-8', 'windows-1251', $str);	
			$pdf->MultiCell(0,7,$str,0,'C',0);
		}
		
		$t_width=array(8,140,0);
		$pdf->SetFont('','',12);
		$str='№';
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell($t_width[0],5,$str,1,0,'C',0);
		$str='Наименование';
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell($t_width[1],5,$str,1,0,'C',0);
		$str='Цена';
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell($t_width[3],5,$str,1,0,'C',0);
		$pdf->Ln();
		
		$pdf->SetFont('','',10);
		
		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_dop`.`mass`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		$i=0;
		while($nxt=mysql_fetch_row($res))
		{
			$i++;
			$cost = sprintf("%01.2f р.", $nxt[4]);
			$pdf->Cell($t_width[0],5,$i,1,0,'R',0);
			$str=$nxt[0].' '.$nxt[1];
			if($nxt[2]) $str.='('.$nxt[2].')';
			$str = iconv('UTF-8', 'windows-1251', $str);	
			$pdf->Cell($t_width[1],5,$str,1,0,'L',0);
			$str = iconv('UTF-8', 'windows-1251', $cost);	
			$pdf->Cell($t_width[2],5,$str,1,0,'R',0);
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
		$str="Система интернет-заказов для постоянных клиентов доступна на нашем сайте";
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell(0,5,$str,0,1,'C',0);
		
		$pdf->SetTextColor(0,0,192);
		$pdf->SetFont('','UI',20);

		$pdf->Cell(0,7,'http://'.$CONFIG['site']['name'],0,1,'C',0,'http://'.$CONFIG['site']['name']);
		
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','',12);
		$str="При оформлении заказа через сайт предоставляется скидка!";
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell(0,8,$str,0,1,'C',0);
		
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
			$pdf->Output('buisness_offer.pdf','I');
	}
	
	function KomPredlPDF_Cnt($to_str=0)
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
		
		$res=mysql_query("SELECT `name`, `bik`, `rs`, `ks` FROM `doc_kassa` WHERE `ids`='bank' AND `num`='{$this->doc_data[16]}'");
		$bank_data=mysql_fetch_row($res);
		
		$str='Банковские реквизиты:';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->SetFont('','',11);
		$pdf->Cell(0,5,$str,0,1,'C',0);
		
		$old_x=$pdf->GetX();
		$old_y=$pdf->GetY();
		$old_margin=$pdf->lMargin;
		$table_c=110;
		$table_c2=15;
		
		$pdf->SetFont('','',12);
		$str=$bank_data[0];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c,10,$str,1,1,'L',0);
		$str='ИНН '.$this->firm_vars['firm_inn'].' КПП';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c,5,$str,1,1,'L',0);
		$str=unhtmlentities($this->firm_vars['firm_name']);
		$str = iconv('UTF-8', 'windows-1251', $str);
		$tx=$pdf->GetX();
		$ty=$pdf->GetY();
		$pdf->Cell($table_c,10,'',1,1,'L',0);
		
		$pdf->lMargin=$old_x+1;
		$pdf->SetX($tx+1);
		$pdf->SetY($ty+1);
		$pdf->SetFont('','',9);
		$pdf->MultiCell($table_c,3,$str,0,1,'L',0);

		$pdf->SetFont('','',12);
		$pdf->lMargin=$old_x+$table_c;
		$pdf->SetY($old_y);
		$str='БИК';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,5,$str,1,1,'L',0);
		$str='корр/с';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,10,$str,1,1,'L',0);
		$str='р/с N';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($table_c2,10,$str,1,1,'L',0);
		
		$pdf->lMargin=$old_x+$table_c+$table_c2;
		$pdf->SetY($old_y);
		$str=$bank_data[1];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,1,1,'L',0);
		$str=$bank_data[3];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,1,1,'L',0);
		$str=$bank_data[2];
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,15,$str,1,1,'L',0);
		
		$pdf->lMargin=$old_margin;
		$pdf->SetY($old_y+30);
		
		$pdf->SetFont('','',20);
		$str='Коммерческое предложение № '.$this->doc_data[9].' от '.$dt;
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell(0,5,$str,0,1,'C',0);
		$pdf->Ln(10);
		$pdf->SetFont('','',10);
		$str=unhtmlentities('Поставщик: '.$this->firm_vars['firm_name'].', '.$this->firm_vars['firm_telefon']);
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->MultiCell(0,5,$str,0,1,'L',0);
		$pdf->Ln(10);
		
		$str=$this->dop_data['shapka'];
		if($str)
		{
			$pdf->SetFont('','',16);
			$str = iconv('UTF-8', 'windows-1251', $str);	
			$pdf->MultiCell(0,7,$str,0,'C',0);
		}
		
		$t_width=array(8,135,15,0);
		$pdf->SetFont('','',12);
		$str='№';
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell($t_width[0],5,$str,1,0,'C',0);
		$str='Наименование';
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell($t_width[1],5,$str,1,0,'C',0);
		$str='Кол-во';
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell($t_width[2],5,$str,1,0,'C',0);
		$str='Цена';
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell($t_width[3],5,$str,1,0,'C',0);
		$pdf->Ln();
		
		$pdf->SetFont('','',10);
		
		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_dop`.`mass`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		$i=0;
		while($nxt=mysql_fetch_row($res))
		{
			$i++;
			$cost = sprintf("%01.2f р.", $nxt[4]);
			$pdf->Cell($t_width[0],5,$i,1,0,'R',0);
			$str=$nxt[0].' '.$nxt[1];
			if($nxt[2]) $str.='('.$nxt[2].')';
			$str = iconv('UTF-8', 'windows-1251', $str);	
			$pdf->Cell($t_width[1],5,$str,1,0,'L',0);
			$pdf->Cell($t_width[2],5,$nxt[3],1,0,'C',0);
			$str = iconv('UTF-8', 'windows-1251', $cost);	
			$pdf->Cell($t_width[3],5,$str,1,0,'R',0);
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
		$str="Система интернет-заказов для постоянных клиентов доступна на нашем сайте";
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell(0,5,$str,0,1,'C',0);
		
		$pdf->SetTextColor(0,0,192);
		$pdf->SetFont('','UI',20);

		$pdf->Cell(0,7,'http://'.$CONFIG['site']['name'],0,1,'C',0,'http://'.$CONFIG['site']['name']);
		
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','',12);
		$str="При оформлении заказа через сайт предоставляется скидка!";
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell(0,8,$str,0,1,'C',0);
		
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
			$pdf->Output('buisness_offer.pdf','I');
	}
		
	
	function CSVExport()
	{
		global $tmpl;
		global $uid;
		
		$dt=date("d.m.Y",$this->doc_data[5]);
		
		if($coeff==0) $coeff=1;
		if(!$to_str) $tmpl->ajax=1;
		
		header("Content-type: 'application/octet-stream'"); 
		header("Content-Disposition: 'attachment'; filename=predlojenie.csv;");
		echo"PosNum;ID;Name;Proizv;Cnt;Cost;Sum\r\n";
		
		$res=mysql_query("SELECT `doc_base`.`id`, `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		$i=0;
		$sum=$summass=0;
		while($nxt=mysql_fetch_row($res))
		{
			$i++;
			$sm=$nxt[5]*$nxt[4];
			echo"$i;$nxt[0];\"$nxt[1] $nxt[2]\";\"$nxt[3]\";$nxt[4];$nxt[5];$sm\n";
		}

	}

};
?>