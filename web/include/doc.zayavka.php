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


$doc_types[3]="Заявка покупателя";

class doc_Zayavka extends doc_Nulltype
{
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=3;
		$this->doc_name				='zayavka';
		$this->doc_viewname			='Заявка покупателя';
		$this->sklad_editor_enable		=true;
		$this->sklad_modify			=0;
		$this->header_fields			='agent cena sklad bank';
		settype($this->doc,'int');
	}
	
	function DopHead()
	{
		global $tmpl;
		$klad_id=@$this->dop_data['kladovshik'];
		if(!$klad_id)	$klad_id=$this->firm_vars['firm_kladovshik_id'];
		$tmpl->AddText("Кладовщик:<br><select name='kladovshik'>");	
		$res=mysql_query("SELECT `id`, `name`, `rname` FROM `users` WHERE `worker`='1' ORDER BY `name`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить имя кладовщика");
		while($nxt=mysql_fetch_row($res))
		{
			$s=($klad_id==$nxt[0])?'selected':'';
			$tmpl->AddText("<option value='$nxt[0]' $s>$nxt[1] ($nxt[2])</option>");
		}
		$tmpl->AddText("</select><br>");
	}

	function DopSave()
	{
		$kladovshik=rcv('kladovshik');
		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
		VALUES ( '{$this->doc}' ,'kladovshik','$kladovshik')");
	}
	
	function DopBody()
	{
		global $tmpl;
		$klad_id=@$this->dop_data['kladovshik'];
		$res=mysql_query("SELECT `id`, `name`, `rname` FROM `users` WHERE `id`='$klad_id'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить имя кладовщика");
		$nxt=mysql_fetch_row($res);
		if($nxt)
		{
			$tmpl->AddText(", <b>Кладовщик:</b> $nxt[1] ($nxt[2]) ");
		}
	}
	
	function DocApply($silent=0)
	{
		$tim=time();
		
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if( !($nx=@mysql_fetch_row($res) ) )	throw new MysqlException('Ошибка выборки данных документа при проведении!');	
		if( $nx[4] && ( !$silent) )		throw new Exception('Документ уже был проведён!');
		if($silent)	return;
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if( !$res )				throw new MysqlException('Ошибка проведения, ошибка установки даты проведения!');
	}

	function DocCancel()
	{
		$err='';
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка выборки данных документа!');
		if(! ($nx=@mysql_fetch_row($res)))	throw new Exception('Документ не найден!');	
		if(!$nx[4])				throw new Exception('Документ НЕ проведён!');
		$tim=time();
		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка установки флага отмены!');
	}

	function PrintForm($doc, $opt='')
	{
		$doc=$this->doc;
		if($opt=='')
		{
			global $tmpl;
			$tmpl->ajax=1;
			$tmpl->AddText("
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=komplekt'\">Накладная на комплектацию</div>			
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=schet_pdf'\">Счёт</div>		
			<div onclick=\"ShowPopupWin('/doc.php?mode=print&amp;doc=$doc&amp;opt=schet_email'); return false;\">Счёт PDF по e-mail</div>			
			<div onclick=\"ShowPopupWin('/doc.php?mode=print&amp;doc=$doc&amp;opt=schet_ue'); return false;\">Счёт в у.е.</div>	
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=csv_export'\">Экспорт в CSV</div>");
		}
		else if($opt=='schet')
			$this->PrintSchet($doc);
		else if($opt=='schet_ue')
		{
			global $tmpl;
			$tmpl->ajax=1;
			$tmpl->AddText("<form action=''>
			<input type='hidden' name='mode' value='print'>
			<input type='hidden' name='doc' value='$doc'>
			<input type='hidden' name='opt' value='schet_ue_p'>
			1 рубль = <input type='text' name='c' value='1'> у.е.
			<input type='submit' value='&gt;&gt;'>
			</form>");
		}
		else if($opt=='schet_ue_p')
		{
			$coeff=rcv('c');
			$this->PrintSchetUE($doc,$coeff);
		}
		else if($opt=='schet_pdf')
			$this->PrintPDF($doc);
		else if($opt=='schet_email')
			$this->SendEmail($doc);
		else if($opt=='komplekt')
			$this->PrintKomplekt($doc);
		else if($opt=='csv_export')
			$this->CSVExport($doc);
		else $tmpl->logger("Запрошена неизвестная опция!");
	}
	// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		global $tmpl;
		global $uid;
		$doc=$this->doc;

		if($target_type=='')
		{
			$tmpl->ajax=1;
			$tmpl->AddText("
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc=$doc&amp;tt=2'\">Реализация</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc=$doc&amp;tt=6'\">Приходный кассовый ордер</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc=$doc&amp;tt=4'\">Приход средств в банк</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc=$doc&amp;tt=15'\">Оперативная реализация</div>");
		}
		// Реализация
		else if($target_type==2)
		{
			mysql_query("START TRANSACTION");
			$base=$this->Otgruzka($target_type);
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
		// Оперативная реализация
		else if($target_type==15)
		{
			mysql_query("START TRANSACTION");
			$base=$this->Otgruzka($target_type);
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
		else if($target_type==6)
		{
			$sum=DocSumUpdate($this->doc);
			mysql_query("START TRANSACTION");
			$base=$this->Otgruzka(2);
			if(!$base)
			{
				mysql_query("ROLLBACK");
				$tmpl->msg("Не удалось создать подчинённый документ!","err");
			}
			else
			{
				$tm=time();
				$altnum=GetNextAltNum($target_type ,$this->doc_data[10]);
				$res=mysql_query("INSERT INTO `doc_list`
				(`type`, `agent`, `date`, `kassa`, `user`, `altnum`, `subtype`, `p_doc`, `sum`, `firm_id`)
				VALUES ('$target_type', '{$this->doc_data[2]}', '$tm', '1', '$uid', '$altnum', '{$this->doc_data[10]}', '$base', '$sum', '{$this->doc_data[17]}')");
				$ndoc= mysql_insert_id();

				if($res)
				{
					mysql_query("COMMIT");
					$ref="Location: doc.php?mode=body&doc=$ndoc";
					header($ref);
				}
				else
				{
					mysql_query("ROLLBACK");
					$tmpl->msg("Не удалось создать подчинённый документ!","err");
				}
			}
		}
		else if($target_type==4)
		{
			$sum=DocSumUpdate($this->doc);
			mysql_query("START TRANSACTION");
			$base=$this->Otgruzka(2);
			if(!$base)
			{
				mysql_query("ROLLBACK");
				$tmpl->msg("Не удалось создать подчинённый документ!","err");
			}
			else
			{
				$tm=time();
				$altnum=GetNextAltNum($target_type ,$this->doc_data[10]);
				$res=mysql_query("INSERT INTO `doc_list`
				(`type`, `agent`, `date`, `bank`, `user`, `altnum`, `subtype`, `p_doc`, `sum`, `firm_id`)
				VALUES ('$target_type', '{$this->doc_data[2]}', '$tm', '{$this->doc_data[16]}', '$uid', '$altnum', '{$this->doc_data[10]}', '$base', '$sum', '{$this->doc_data[17]}')");
				$ndoc= mysql_insert_id();
				if($res)
				{
					mysql_query("COMMIT");
					$ref="Location: doc.php?mode=body&doc=$ndoc";
					header($ref);
				}
				else
				{
					mysql_query("ROLLBACK");
					$tmpl->msg("Не удалось создать подчинённый документ!","err");
				}
			}
		}
		else
		{
			$tmpl->msg("В разработке","info");
		}
	}
	// Выполнить удаление документа. Если есть зависимости - удаление не производится.
	function DelExec($doc)
	{
		$res=mysql_query("SELECT `ok` FROM `doc_list` WHERE `id`='$doc'");
		if(!mysql_result($res,0,0)) // Если проведён - нельзя удалять
		{
			$res=mysql_query("SELECT `id`, `mark_del` FROM `doc_list` WHERE `p_doc`='$doc'");
			if(!mysql_num_rows($res)) // Если есть потомки - нельзя удалять
			{
				mysql_query("DELETE FORM `doc_list_pos` WHERE `doc`='$doc'");
				mysql_query("DELETE FROM `doc_dopdata` WHERE `doc`='$doc'");
				mysql_query("DELETE FROM `doc_list` WHERE `id`='$doc'");
				return 0;
			}
		}
		return 1;
   	}
   	
	function Service($doc)
	{
		$tmpl->ajax=1;
		$opt=rcv('opt');
		$pos=rcv('pos');
		parent::_Service($opt,$pos);
	}
//	================== Функции только этого класса ======================================================
	function Otgruzka($target_type)
	{
		global $tmpl, $uid;

		$res=mysql_query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$this->doc}' AND `type`='$target_type'");
		@$r_id=mysql_result($res,0,0);
		if(!$r_id)
		{
			$altnum=GetNextAltNum($target_type, $this->doc_data[10]);
			$tm=time();
			$sum=DocSumUpdate($this->doc);
			$res=mysql_query("INSERT INTO `doc_list`
			(`type`, `agent`, `date`, `sklad`, `user`, `altnum`, `subtype`, `p_doc`, `sum`, `nds`, `firm_id`)
			VALUES ('$target_type', '{$this->doc_data[2]}', '$tm', '{$this->doc_data[7]}', '$uid', '$altnum', '{$this->doc_data[10]}', '{$this->doc}', '$sum', '{$this->doc_data[12]}', '{$this->doc_data[17]}')");
			
			$r_id= mysql_insert_id();

			if(!$r_id) return 0;
			
			doc_log("CREATE", "FROM {$this->doc_name} {$this->doc_name}", 'doc', $r_id);
			
			mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
			VALUES ('$r_id','cena','{$this->dop_data['cena']}')");

			$res=mysql_query("SELECT `tovar`, `cnt`, `sn`, `comm`, `cost` FROM `doc_list_pos`
			WHERE `doc_list_pos`.`doc`='{$this->doc}'");
			while($nxt=mysql_fetch_row($res))
			{
				mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `sn`, `comm`, `cost`)
				VALUES ('$r_id', '$nxt[0]', '$nxt[1]', '$nxt[2]', '$nxt[3]', '$nxt[4]' )");
			}
		}
		else
		{
			$new_id=0;
			$res=mysql_query("SELECT `a`.`tovar`, `a`.`cnt`, `a`.`sn`, `a`.`comm`, `a`.`cost`,
			( SELECT SUM(`b`.`cnt`) FROM `doc_list_pos` AS `b`
			INNER JOIN `doc_list` ON `b`.`doc`=`doc_list`.`id` AND `doc_list`.`p_doc`='{$this->doc}' AND `doc_list`.`mark_del`='0'
			WHERE `b`.`tovar`=`a`.`tovar` )
			FROM `doc_list_pos` AS `a`
			WHERE `a`.`doc`='{$this->doc}'");

			while($nxt=mysql_fetch_row($res))
			{
				//echo"$nxt[5] - $nxt[1]<br>";
				if($nxt[5]<$nxt[1])
				{
					
					if(!$new_id)
					{
						$altnum=GetNextAltNum($target_type, $this->doc_data[10]);
						$tm=time();
						$sum=DocSumUpdate($this->doc);
						$rs=mysql_query("INSERT INTO `doc_list`
						(`type`, `agent`, `date`, `sklad`, `user`, `altnum`, `subtype`, `p_doc`, `sum`, `nds`, `firm_id`)
						VALUES ('$target_type', '{$this->doc_data[2]}', '$tm', '{$this->doc_data[7]}', '$uid', '$altnum', '{$this->doc_data[10]}', '{$this->doc}', '$sum', '{$this->doc_data[12]}', '{$this->doc_data[17]}')");
						$new_id= mysql_insert_id();
						
						mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
						VALUES ('$new_id','cena','{$this->dop_data['cena']}')");
					}
					$n_cnt=$nxt[1]-$nxt[5];
					mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `sn`, `comm`, `cost`)
 					VALUES ('$new_id', '$nxt[0]', '$n_cnt', '$nxt[2]', '$nxt[3]', '$nxt[4]' )");
				}
			}
			if($new_id)
			{
				$r_id=$new_id;
				DocSumUpdate($new_id);
			}
		}

		return $r_id;
	}

	function PrintSchet($doc)
	{
		get_docdata($doc);
		global $tmpl, $CONFIG, $uid;

		if(0)
		//if(!$doc_data[6])
		{
			doc_menu(0,0);
			$tmpl->AddText("<h1>Печать счёта</h1>");

			$tmpl->msg("Сначала нужно провести документ!","err");
		}
		else
		{
			$tmpl->LoadTemplate('print');

			$res=mysql_query("SELECT `adres`, `tel` FROM `doc_agent` WHERE `id`='{$this->doc_data[2]}'");
			$agent_data=mysql_fetch_row($res);
			
			$dt=date("d.m.Y",$this->doc_data[5]);
			$tmpl->AddText("
			<table width=800 class=ht><tr class=nb><td class=ht>
			<table width=800>
			<tr><td align=center>
			Внимание! Оплата данного счёта означает согласие с условиями поставки товара. Уведомление об оплате обязательно,
			иначе не гарантируется наличие товара на складе. Товар отпускается по факту прихода денег на р/с поставщика,
			самовывозом, при наличии доверенности и паспорта.<br>
			<b>Счёт действителен в течение трёх банковских дней.</b>
			<h3>Образец заполнения платёжного поручения</h3>
			</table>
			<br>
			<table width=800>
			<tr><td rowspan=2>".$this->firm_vars['firm_bank']."<td>Бик<td>".$this->firm_vars['firm_bik']."
			<tr><td>кор/с<td>".$this->firm_vars['firm_bank_kor_s']."
			<tr><td>ИНН ".$this->firm_vars['firm_inn']." КПП<td rowspan=2>р/с №<td rowspan=2>".$this->firm_vars['firm_schet']."
			<tr><td>Получатель: ".$this->firm_vars['firm_name']."
			</table>

			<br><br>

			<h1>Счёт N {$this->doc_data[9]}, от $dt </h1><hr>
			<b>Поставщик: </b>".$this->firm_vars['firm_name'].", ".$this->firm_vars['firm_adres'].$this->firm_vars['firm_telefon']."<br>
			<b>Покупатель: </b>{$this->doc_data[3]}, адрес: $agent_data[0], телефон: $agent_data[1]<br><br>

			<table width=800 cellspacing=0 cellpadding=0  class=nb>
			<tr><th>№<th width=450>Наименование<th>Кол-во<th>Цена<th>Сумма");

			$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_dop`.`mass`
			FROM `doc_list_pos`
			LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
			LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
			WHERE `doc_list_pos`.`doc`='$doc'");
			$i=0;
			$sum=$summass=0;
			while($nxt=mysql_fetch_row($res))
			{
				$i++;
				$sm=$nxt[3]*$nxt[4];
				$sum+=$sm;
				$summass+=$nxt[5]*$nxt[3];
				$cost = sprintf("%01.2f", $nxt[4]);
				$smcost = sprintf("%01.2f", $sm);
				$tmpl->AddText("<tr align=right><td>$i<td align=left>$nxt[0] $nxt[1] / $nxt[2]<td>$nxt[3]<td>$cost<td>$smcost");


			}
			$cost = num2str($sum);
			$sumcost = sprintf("%01.2f", $sum);
			$summass = sprintf("%01.3f", $summass);
			
			if($this->doc_data[12])
			{

				$nds=$sum/(100+$this->firm_vars['param_nds'])*$this->firm_vars['param_nds'];
				$nds = sprintf("%01.2f", $nds);
				$tmpl->AddText("<tr class=nb><td colspan=2 class=nb><td class=itog colspan=3>Итого: <b>$sumcost</b> руб.
				<tr class=nb><td colspan=2 class=nb><td class=itog colspan=3>В том числе НДС 18%: <b>$nds</b>  руб.
				</table>
				<p>Всего <b>$i</b> наименований, на сумму <b>$sumcost</b> руб. ($cost)<br>
				В том числе НДС 18%: <b>$nds руб.</b></p>");
			}
			else
			{
				$nds=$sum*$this->firm_vars['param_nds']/100;
				$cst=$sum+$nds;
				$nds_p = sprintf("%01.2f руб.", $nds);
				$cost2 = sprintf("%01.2f руб.", $cst);

				$tmpl->AddText("<tr class=nb><td colspan=2 class=nb><td class=itog colspan=3>Итого: $sumcost руб.
				<tr class=nb><td colspan=2 class=nb><td class=itog colspan=3>НДС: $nds_p
				<tr class=nb><td colspan=2 class=nb><td class=itog colspan=3>Всего: <b>$cost2</b>
				</table>
				<p>Всего <b>$i</b> наименований, на сумму <b>$sumcost</b> руб.<br><b>$cost</b><br>
				Кроме того, НДС 18%: <b>$nds_p</b><br>Всего: <b>$cost2</b>");
			}
			$tmpl->AddText("<hr>");
			if($CONFIG['site']['doc_shtamp'])
				$tmpl->AddText("<img src='{$CONFIG['site']['doc_shtamp']}' alt='Место для печати'>");
			$tmpl->AddText("<p align=right>Масса товара: <b>$summass</b> кг.<br></p>");
		}		
	}
	
	function SendEMail($doc, $email='')
	{
		global $tmpl;
		global $mail;
		global $CONFIG;
		if(!$email)
			$email=rcv('email');
		
		if($email=='')
		{
			$tmpl->ajax=1;
			$res=mysql_query("SELECT `email` FROM `doc_agent` WHERE `id`='{$this->doc_data[2]}'");
			$email=mysql_result($res,0,0);
			$tmpl->AddText("<form action=''>
			<input type=hidden name=mode value='print'>
			<input type=hidden name=doc value='$doc'>
			<input type=hidden name=opt value='schet_email'>
			email:<input type=text name=email value='$email'><br>
			Коментарий:<br>
			<textarea name='comm'></textarea><br>
			<input type=submit value='&gt;&gt;'>
			</form>");	
		}
		else
		{
			$comm=rcv('comm');
			$sender_name=$_SESSION['name'];
			
			$res=mysql_query("SELECT `rname`, `tel`, `email` FROM `users` WHERE `id`='{$this->doc_data[8]}'");
			$manager_name=@mysql_result($res,0,0);	
			$manager_tel=@mysql_result($res,0,1);
			$manager_email=@mysql_result($res,0,2);	
			
			if(!$manager_email)
			{
				$mail->Body = "Доброго времени суток!\nВо вложении находится заказанный Вами счёт от {$CONFIG['site']['name']}\n\n$comm\n\nСообщение сгенерировано автоматически, отвечать на него не нужно! Для переписки используйте адрес, указанный на сайте http://{$CONFIG['site']['name']}!";
			}
			else
			{
				$mail->Body = "Доброго времени суток!\nВо вложении находится заказанный Вами счёт от {$CONFIG['site']['name']}\n\n$comm\n\nИсполнительный менеджер $manager_name\nКонтактный телефон: $manager_tel\nЭлектронная почта (e-mail): $manager_email\nОтправитель: $sender_name";
 				$mail->Sender   = $manager_email;  
 				$mail->From     = $manager_email;  
 				//$mail->FromName = "{$mail->FromName} ({$manager_name})";
			}

			$mail->AddAddress($email, $email );  
			$mail->Subject="Счёт от {$CONFIG['site']['name']}";
			
			$mail->AddStringAttachment($this->PrintPDF($doc, 1), "schet.pdf");  
			if($mail->Send())
				$tmpl->msg("Сообщение отправлено!","ok");
			else
				$tmpl->msg("Ошибка отправки сообщения!",'err');
    	}
		
	}
	
	function PrintSchetUE($doc, $coeff)
	{
		get_docdata($doc);
		global $tmpl, $CONFIG, $uid;
		
		if($coeff==0) $coeff=1;

		if(0)
		//if(!$doc_data[6])
		{
			doc_menu(0,0);
			$tmpl->AddText("<h1>Печать счёта</h1>");

			$tmpl->msg("Сначала нужно провести документ!","err");
		}
		else
		{
			$tmpl->LoadTemplate('print');

			$res=mysql_query("SELECT `adres`, `tel` FROM `doc_agent` WHERE `id`='{$this->doc_data[2]}'");
			$agent_data=mysql_fetch_row($res);
			
			$dt=date("d.m.Y",$this->doc_data[5]);
			$tmpl->AddText("
			<table width=800 class=ht><tr class=nb><td class=ht>
			<table width=800>
			<tr><td align=center>
			
			Счёт действителен в течение трёх банковских дней.<br>
			Внимание! Оплата данного счёта означает согласие с условиями поставки товара. Уведомление об оплате обязательно,
			иначе не гарантируется наличие товара на складе. Товар отпускается по факту прихода денег на р/с поставщика,
			самовывозом, при наличии доверенности и паспорта.<br>
			<b>Выполняйте заказы через наш сайт http://{$CONFIG['site']['name']} - экономьте своё и наше время!<br>
			При заказе через сайт предоставляются скидки!</b><br>			
			1 у.е. = $coeff руб. Курс действителен на дату выписки счёта.
			<h3>Образец заполнения платёжного поручения</h3>
			</table>
			<br>
			<table width=800>
			<tr><td rowspan=2>".$this->firm_vars['firm_bank']."<td>Бик<td>".$this->firm_vars['firm_bik']."
			<tr><td>кор/с<td>".$this->firm_vars['firm_bank_kor_s']."
			<tr><td>ИНН ".$this->firm_vars['firm_inn']." КПП<td rowspan=2>р/с №<td rowspan=2>".$this->firm_vars['firm_schet']."
			<tr><td>Получатель: ".$this->firm_vars['firm_name']."
			</table>

			<br><br>

			<h1>Счёт N {$this->doc_data[9]}, от $dt </h1><hr>
			<b>Поставщик: </b>".$this->firm_vars['firm_name'].", ".$this->firm_vars['firm_adres'].$this->firm_vars['firm_telefon']."<br>
			<b>Покупатель: </b>{$this->doc_data[3]}, адрес: $agent_data[0], телефон: $agent_data[1]<br><br>

			<table width=800 cellspacing=0 cellpadding=0  class=nb>
			<tr><th>№<th width=450>Наименование<th>Кол-во<th>Цена<th>Сумма");

			$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_dop`.`mass`
			FROM `doc_list_pos`
			LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
			LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
			WHERE `doc_list_pos`.`doc`='$doc'");
			$i=0;
			$sum=$summass=0;
			while($nxt=mysql_fetch_row($res))
			{
				$i++;
				$sm=$nxt[3]*$nxt[4]/$coeff;
				$sum+=$sm;
				$summass+=$nxt[5]*$nxt[3];
				$cost = sprintf("%01.2f", $nxt[4]/$coeff);
				$smcost = sprintf("%01.2f", $sm);
				$tmpl->AddText("<tr align=right><td>$i<td align=left>$nxt[0] $nxt[1] / $nxt[2]<td>$nxt[3]<td>$cost<td>$smcost");
			}
			$cost = num2str($sum, "nul");
			$sumcost = sprintf("%01.2f", $sum);
			$summass = sprintf("%01.3f", $summass);
			
			if($this->doc_data[12])
			{

				$nds=$sum/(100+$this->firm_vars['param_nds'])*$this->firm_vars['param_nds'];
				$nds = sprintf("%01.2f", $nds);
				$tmpl->AddText("<tr class=nb><td colspan=2 class=nb><td class=itog colspan=3>Итого: <b>$sumcost</b> у.е.
				<tr class=nb><td colspan=2 class=nb><td class=itog colspan=3>В том числе НДС 18%: <b>$nds</b>  у.е.
				</table>
				<p>Всего <b>$i</b> наименований, на сумму <b>$sumcost</b> у.е. ($cost)<br>
				В том числе НДС 18%: <b>$nds у.е.</b></p>");
			}
			else
			{
				$nds=$sum*$this->firm_vars['param_nds']/100;
				$cst=$sum+$nds;
				$nds_p = sprintf("%01.2f у.е.", $nds);
				$cost2 = sprintf("%01.2f у.е.", $cst);

				$tmpl->AddText("<tr class=nb><td colspan=2 class=nb><td class=itog colspan=3>Итого: $sumcost у.е..
				<tr class=nb><td colspan=2 class=nb><td class=itog colspan=3>НДС: $nds_p
				<tr class=nb><td colspan=2 class=nb><td class=itog colspan=3>Всего: <b>$cost2</b>
				</table>
				<p>Всего <b>$i</b> наименований, на сумму <b>$sumcost</b> у.е.<br><b>$cost у.е.</b><br>
				Кроме того, НДС 18%: <b>$nds_p</b><br>Всего: <b>$cost2</b>");
			}
			$tmpl->AddText("<hr>");
			
			if($CONFIG['site']['doc_shtamp'])
				$tmpl->AddText("<img src='{$CONFIG['site']['doc_shtamp']}' alt='Место для печати'>");
			$tmpl->AddText("<p align=right>Масса товара: <b>$summass</b> кг.<br></p>");
		}		
	}
	
	function PrintPDF($doc, $to_str=0)
	{
		define('FPDF_FONT_PATH','/var/www/gate/fpdf/font/');
		require('fpdf/fpdf_mysql.php');
		global $tmpl, $CONFIG, $uid;
		
		$res=mysql_query("SELECT `adres`, `tel` FROM `doc_agent` WHERE `id`='{$this->doc_data[2]}'");
		$agent_data=mysql_fetch_row($res);
		
		$res=mysql_query("SELECT `name`, `bik`, `rs`, `ks` FROM `doc_kassa` WHERE `ids`='bank' AND `num`='{$this->doc_data[16]}'");
		$bank_data=mysql_fetch_row($res);
		
		$dt=date("d.m.Y",$this->doc_data[5]);
		
		if(!isset($coeff))	$coeff=1;
		if($coeff==0) $coeff=1;
		if(!$to_str) $tmpl->ajax=1;
		
		$pdf=new FPDF('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(0,10);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=5;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);
		
		if(@$CONFIG['site']['doc_header'])
		{
			$header_img=str_replace('{FN}', $this->doc_data['firm_id'], $CONFIG['site']['doc_header']);
			$pdf->Image($header_img,8,10, 190);	
			$pdf->Sety(54);
		}
		
		$str = "Внимание! Оплата данного счёта означает согласие с условиями поставки товара. Уведомление об оплате обязательно, иначе не гарантируется наличие товара на складе. Товар отпускается по факту прихода денег на р/с поставщика, самовывозом, при наличии доверенности и паспорта. Система интернет-заказов для постоянных клиентов доступна на нашем сайте http://{$CONFIG['site']['name']}.";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0,5,$str,1,1,'c',0);
		$pdf->y++;
		$str='Счёт действителен в течение трёх банковских дней!';
		$pdf->SetFont('','U',10);
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'C',0);
		
// 		$str="При оформлении заказа через сайт {$CONFIG['site']['name']} предоставляется значительная скидка!";
// 		$pdf->SetFont('','U',12);
// 		$str = iconv('UTF-8', 'windows-1251', $str);
// 		$pdf->Cell(0,8,$str,0,1,'C',0);
		
		$str='Образец заполнения платёжного поручения:';
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
		$str='Получатель: '.unhtmlentities($this->firm_vars['firm_name']);
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
		
		
		$pdf->SetFont('','',16);
		$str='Счёт № '.$this->doc_data[9].', от '.$dt;
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$pdf->SetFont('','',8);
		$str='Поставщик: '.unhtmlentities($this->firm_vars['firm_name'].', '.$this->firm_vars['firm_adres'].', тел:'.$this->firm_vars['firm_telefon']);
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->MultiCell(0,4,$str,0,1,'L',0);
		$str="Покупатель: ".unhtmlentities($this->doc_data[3].", адрес: $agent_data[0], телефон: $agent_data[1]");
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->MultiCell(0,4,$str,0,1,'L',0);
		
		$pdf->Ln(3);
		$pdf->SetFont('','',11);
		$str = iconv('UTF-8', 'windows-1251', str_replace("<br>",", ",unhtmlentities($this->doc_data[4])));	
		$pdf->MultiCell(0,5,$str,0,1,'L',0);
		
		$pdf->Ln(3);
		
		$t_width=array(8,110,20,25,0);
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
		$str='Сумма';
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell($t_width[4],5,$str,1,0,'C',0);
		$pdf->Ln();
		
		$pdf->SetFont('','',9);
		
		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_dop`.`mass`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE `doc_list_pos`.`doc`='$doc'");
		$i=0;
		$sum=$summass=0;
		while($nxt=mysql_fetch_row($res))
		{
			$i++;
			$sm=$nxt[3]*$nxt[4];
			$sum+=$sm;
			$summass+=$nxt[5]*$nxt[3];
			$cost = sprintf("%01.2f р.", $nxt[4]);
			$smcost = sprintf("%01.2f р.", $sm);
			
			$name=$nxt[0].' '.$nxt[1];
			if($nxt[2]) $name.='('.$nxt[2].')';
			$name = iconv('UTF-8', 'windows-1251', unhtmlentities($name));

			$rough_lines=ceil($pdf->GetStringWidth($name)/$t_width[1]);

			if( $pdf->h <= ($pdf->GetY()+15 + $rough_lines*5 ) ) $pdf->AddPage();			

			
			// Вывод наименования и расчёт отступов
			$old_x=$pdf->GetX();
			$old_y=$pdf->GetY();
			$pdf->SetX($pdf->GetX()+$t_width[0]);			
			$pdf->MultiCell($t_width[1],5,$name,1,'L');
			$line_height=$pdf->GetY()-$old_y;
			$pdf->SetX($old_x);
			$pdf->SetY($old_y);
			
			

			$pdf->Cell($t_width[0],$line_height,$i,1,0,'R');
			$pdf->Cell($t_width[1],5,'',0,0,'L');
			//if($pdf->GetStringWidth($str)>$t_width[1])
			//$pdf->MultiCell($t_width[1],5,$str,1,'L');
			$pdf->Cell($t_width[2],$line_height,$nxt[3],1,0,'C');
			$str = iconv('UTF-8', 'windows-1251', $cost);	
			$pdf->Cell($t_width[3],$line_height,$str,1,0,'R');
			$str = iconv('UTF-8', 'windows-1251', $smcost);	
			$pdf->Cell($t_width[4],$line_height,$str,1,0,'R');
			$pdf->Ln($line_height);
		}
		
		$cost = num2str($sum);
		$sumcost = sprintf("%01.2f", $sum);
		$summass = sprintf("%01.3f", $summass);
	
		
		if($pdf->h<=($pdf->GetY()+60)) $pdf->AddPage();
		
		$delta=$pdf->h-($pdf->GetY()+55);
		if($delta>17) $delta=17;
		
		if($CONFIG['site']['doc_shtamp'])
		{
			$shtamp_img=str_replace('{FN}', $this->doc_data['firm_id'], $CONFIG['site']['doc_shtamp']);
			$pdf->Image($shtamp_img, 4,$pdf->GetY()+$delta, 120);	
		}
		
		$pdf->SetFont('','',8);
		$str="Масса товара: $summass кг.";
		$str = iconv('UTF-8', 'windows-1251', $str);	
		$pdf->Cell(0,6,$str,0,0,'L',0);
		
		if($this->doc_data[12])
		{
			$nds=$sum/(100+$this->firm_vars['param_nds'])*$this->firm_vars['param_nds'];
			$nds = sprintf("%01.2f", $nds);
			$pdf->SetFont('','',12);
			$str="Итого: $sumcost руб.";
			$str = iconv('UTF-8', 'windows-1251', $str);	
			$pdf->Cell(0,7,$str,0,1,'R',0);
			$str="В том числе НДС ".$this->firm_vars['param_nds']."%: $nds руб.";
			$str = iconv('UTF-8', 'windows-1251', $str);	
			$pdf->Cell(0,5,$str,0,1,'R',0);
			
			$pdf->SetFont('','',8);
			$str="Всего $i наименований, на сумму $sumcost руб. ($cost)";
			$str = iconv('UTF-8', 'windows-1251', $str);	
			$pdf->Cell(0,4,$str,0,1,'L',0);
			$str="В том числе НДС ".$this->firm_vars['param_nds']."%: $nds руб.";
			$str = iconv('UTF-8', 'windows-1251', $str);	
			$pdf->Cell(0,4,$str,0,1,'L',0);
			
		}
		else
		{
			$nds=$sum*$this->firm_vars['param_nds']/100;
			$cst=$sum+$nds;
			$nds_p = sprintf("%01.2f", $nds);
			$cost2 = sprintf("%01.2f", $cst);
			$pdf->SetFont('','',10);
			$str="Итого: $sumcost руб.";
			$str = iconv('UTF-8', 'windows-1251', $str);	
			$pdf->Cell(0,5,$str,0,1,'R',0);
			$str="НДС ".$this->firm_vars['param_nds']."%: $nds_p руб.";
			$str = iconv('UTF-8', 'windows-1251', $str);	
			$pdf->Cell(0,4,$str,0,1,'R',0);
			$str="Всего: $cost2 руб.";
			$str = iconv('UTF-8', 'windows-1251', $str);	
			$pdf->Cell(0,4,$str,0,1,'R',0);
			
			$pdf->SetFont('','',8);
			$str="Всего $i наименований, на сумму $sumcost руб. ($cost)";
			$str = iconv('UTF-8', 'windows-1251', $str);	
			$pdf->Cell(0,4,$str,0,1,'L',0);
			$str="Кроме того, НДС ".$this->firm_vars['param_nds']."%: $nds_p, Всего $cost2 руб.";
			$str = iconv('UTF-8', 'windows-1251', $str);	
			$pdf->Cell(0,4,$str,0,1,'L',0);
		}
		
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
			return $pdf->Output('zayavka.pdf','S');
		else
			$pdf->Output('zayavka.pdf','I');
	}


	function PrintKomplekt($doc)
	{
		get_docdata($doc);
		global $tmpl;
		global $uid;

		if(!$this->doc_data[6])
		{
			doc_menu(0,0);
			$tmpl->AddText("<h1>Печать накладной на комплектацию</h1>");

			$tmpl->msg("Сначала нужно провести документ!","err");
		}
		else
		{
			$tmpl->LoadTemplate('print');

			$dt=date("d.m.Y",$this->doc_data[5]);
			$tmpl->AddText("
			<table width=800 class=ht><tr class=nb><td class=ht>

			<h1>Накладная на комплектацию</h1>
			<h3>К счёту N {$this->doc_data[9]}, от $dt </h3><hr>
			<b>Поставщик: </b>".$this->firm_vars['firm_name']."<br>
			<b>Покупатель: </b>{$this->doc_data[3]}<br><br>

			<br><br>
			<table width=800 cellspacing=0 cellpadding=0>
			<tr><th>№<th width=450>Наименование<th>Кол-во<th>Остаток<th>Резерв<th>Масса<th>Место");

			$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_base_dop`.`mass`, `doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`, `doc_list_pos`.`tovar`
			FROM `doc_list_pos`
			LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
			LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data[7]}'
			WHERE `doc_list_pos`.`doc`='$doc'");
			$i=0;
			$summass=0;
			while($nxt=mysql_fetch_row($res))
			{
				$i++;
				$summass+=$nxt[4]*$nxt[3];
				$cost = sprintf("%01.2f", $nxt[4]);
				$smcost = sprintf("%01.2f", $sm);
				$mass=sprintf("%0.3f",$nxt[4]);

				$rezerv=DocRezerv($nxt[7],$doc);

				$ostatok=$nxt[6];

				$tmpl->AddText("<tr align=right>
				<td>$i<td align=left>$nxt[0] $nxt[1] / $nxt[2]<td>$nxt[3]<td>$ostatok<td>$rezerv<td>$mass<td>$nxt[5]");


			}
			$mass_p=num2str($summass,'kg',3);
			$summass = sprintf("%01.3f", $summass);
			
			$res=mysql_query("SELECT `name` FROM `users` WHERE `id`='$uid'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить имя пользователя");
			$vip_name=@mysql_result($res,0,0);
			
			$res=mysql_query("SELECT `name` FROM `users` WHERE `id`='{$this->doc_data['user']}'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить имя автора");
			$autor_name=@mysql_result($res,0,0);

			$klad_id=$this->dop_data['kladovshik'];
			$res=mysql_query("SELECT `id`, `name`, `rname` FROM `users` WHERE `id`='$klad_id'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить имя кладовщика");
			$nxt=mysql_fetch_row($res);

			$tmpl->AddText("</table>
			<p>Всего <b>$i</b> наименований, массой <b>$summass</b> кг.<br>
			<b>$mass_p</b></p><hr><br><br><p>
			Заявку принял: _________________________________________ ($autor_name)<br><br>
			Документ выписал: ______________________________________ ($vip_name)<br><br>
			Заказ скомплектовал: ___________________________________ ( $nxt[1] - $nxt[2] )</p></table>");

		}
	}
	
	function CSVExport($doc)
	{
		global $tmpl;
		global $uid;
		
		$dt=date("d.m.Y",$this->doc_data[5]);
		
		if($coeff==0) $coeff=1;
		if(!$to_str) $tmpl->ajax=1;
		
		header("Content-type: 'application/octet-stream'"); 
		header("Content-Disposition: attachment; filename=zayavka.csv;");
		echo"PosNum;ID;Name;Proizv;Cnt;Cost;Sum\r\n";
		
		$res=mysql_query("SELECT `doc_base`.`id`, `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE `doc_list_pos`.`doc`='$doc'");
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