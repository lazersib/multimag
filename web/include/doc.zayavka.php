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

$doc_types[3]="Заявка покупателя";

/// Документ *Заявка покупателя*
class doc_Zayavka extends doc_Nulltype
{
	/// Конструктор
	/// @param doc id документа
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=3;
		$this->doc_name				='zayavka';
		$this->doc_viewname			='Заявка покупателя';
		$this->sklad_editor_enable		=true;
		$this->sklad_modify			=0;
		$this->header_fields			='bank sklad separator agent cena';
		$res=mysql_query("SELECT `id` FROM `doc_list` WHERE `p_doc`='$doc'");
		if(mysql_errno())			throw new MysqlException("Не удалось получить подчинённые документы");
		if(mysql_num_rows($res))		$this->dop_menu_buttons			="<a href='/doc.php?mode=srv&amp;opt=rewrite&amp;doc=$doc' title='Перезаписать номенклатурой из подчинённых документов' onclick='return confirm(\"Подтвертите перезапись номенклатуры документа\")'><img src='img/i_rewrite.png' alt='rewrite'></a>";
		$this->dop_menu_buttons.="<a href='#' onclick='msgMenu(event, {$this->doc})'><img src='/img/i_mailsend.png' alt='msg'></a>";
		settype($this->doc,'int');
		$this->PDFForms=array(
			array('name'=>'schet','desc'=>'Счёт','method'=>'PrintPDF')
		);
	}
	
	/// Функция обработки событий, связанных  с заказом
	/// @param event_name Полное название события
	public function dispatchZEvent($event_name)
	{
		global $CONFIG;
		if(isset($CONFIG['zstatus'][$event_name]))
		{
			$s=array('{DOC}','{SUM}','{DATE}');
			$r=array($this->doc,$this->doc_data['sum'],date('Y-m-d',$this->doc_data['date']));
			
			// Проверка и повышение статуса. Если повышение не произошло - остальные действия не выполняются
			if(isset($CONFIG['zstatus'][$event_name]['testup_status']))
			{
				$status=$CONFIG['zstatus'][$event_name]['testup_status'];
				$status_options=array(0=>'new', 1=>'inproc', 2=>'ready', 3=>'ok' ,4=>'err');
				// Если устанавливаемый статус не стандартный - прервать тест
				if(!in_array($status, $status_options))		return;
				// Если текущий статус не стандартный - прервать тест
				if( @$this->dop_data['status']==$status )	return;
				// Если устанавливаемый статус равен текущему - прервать тест
				if( $this->dop_data['status']==$status )	return;
				// Если статус меняется на уменьшение - прервать тест
				if( array_search($this->dop_data['status'], $status_options) >= array_search($status, $status_options) )	return;
				mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES ( '{$this->doc}' ,'status','$status')");
				if(mysql_errno())	throw new MysqlException("Не удалось записпть статус заказа");
				$this->dop_data['status']=$status;
			}

			foreach($CONFIG['zstatus'][$event_name] as $trigger=>$value)
			{
				switch($trigger)
				{
					case 'set_status':	// Установить статус
						mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES ( '{$this->doc}' ,'status','$value')");
						if(mysql_errno())	throw new MysqlException("Не удалось записпть статус заказа");
						$this->dop_data['status']=$value;
						break;
					case 'send_sms':	// Послать sms
						$value=str_replace($s,$r,$value);
						$this->sendSMSNotify($value);
						break;
					case 'send_email':	// Послать email
						$value=str_replace($s,$r,$value);
						$this->sendEmailNotify($value);
						break;
					case 'notify':		// Известить всеми доступными способами
						$value=str_replace($s,$r,$value);
						$this->sendSMSNotify($value);
						$this->sendEmailNotify($value);
						break;
					/// TODO:
					/// Отправка по XMPP
					/// Отправка по телефону (голосом)
					/// Отправка по телефону (факсом)
				}
			}
		}
	}
	
	/// Отправить SMS с заданным текстом заказчику
	/// @param text текст отправляемого сообщения
	function sendSMSNotify($text)
	{
		global $CONFIG;
		if(@$CONFIG['doc']['notify_sms'])
		{
			require_once('include/sendsms.php');
			if(isset($this->dop_data['buyer_phone']))	$smsphone=$this->dop_data['buyer_phone'];
			else if($this->doc_data['agent']>1)
			{
				$res=mysql_query("SELECT `sms_phone` FROM `doc_agent` WHERE `id`='{$this->doc_data['user']}'");
				$smsphone=mysql_result($res,0,0);
				if(!$smsphone)
				{
					$res=mysql_query("SELECT `reg_phone` FROM `users` WHERE `id`='{$this->doc_data['user']}'");
					$smsphone=@mysql_result($res,0,0);
				}
			}
			else
			{
				$res=mysql_query("SELECT `reg_phone` FROM `users` WHERE `id`='{$this->doc_data['autor']}'");
				$smsphone=@mysql_result($res,0,0);
			}
			if(preg_match('/^\+79\d{9}$/', $smsphone))
			{
				$sender=new SMSSender();
				$sender->setNumber($smsphone);
				$sender->setText($text);
				$sender->send();
			}
		}
	}

	/// Отправить email с заданным текстом заказчику
	/// @param text текст отправляемого сообщения
	function sendEmailNotify($text)
	{
		global $CONFIG;
		if(@$CONFIG['doc']['notify_email'])
		{
			if(isset($this->dop_data['buyer_email']))	$email=$this->dop_data['buyer_email'];
			else if($this->doc_data['agent']>1)
			{
				$res=mysql_query("SELECT `email` FROM `doc_agent` WHERE `id`='{$this->doc_data['user']}'");
				$email=mysql_result($res,0,0);
				if(!$email)
				{
					$res=mysql_query("SELECT `email` FROM `users` WHERE `id`='{$this->doc_data['user']}'");
					$email=@mysql_result($res,0,0);
				}
			}
			else
			{
				$res=mysql_query("SELECT `email` FROM `users` WHERE `id`='{$this->doc_data['autor']}'");
				$email=@mysql_result($res,0,0);
			}

			if($email)
			{
				$user_msg="Уважаемый клиент!\n".$text;
				mailto($email,"Заказ N {$this->doc} на {$CONFIG['site']['name']}", $user_msg);
			}
		}

	}

	function DopHead()
	{
		global $tmpl, $CONFIG;

		$klad_id=@$this->dop_data['kladovshik'];
		if(!$klad_id)	$klad_id=@$this->firm_vars['firm_kladovshik_id'];
		if(!isset($this->dop_data['delivery_date']))	$this->dop_data['delivery_date']='';
		$delivery_checked=@$this->dop_data['delivery']?'checked':'';
		$tmpl->AddText("Кладовщик:<br><select name='kladovshik'>");
		$res=mysql_query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить имя кладовщика");
		while($nxt=mysql_fetch_row($res))
		{
			$s=($klad_id==$nxt[0])?'selected':'';
			$tmpl->AddText("<option value='$nxt[0]' $s>$nxt[1]</option>");
		}
		$tmpl->AddText("</select><hr>");

		if(@$this->dop_data['ishop'])		$tmpl->AddText("<b>Заявка с интернет-витрины</b><br>");
		if(@$this->dop_data['buyer_rname'])	$tmpl->AddText("<b>ФИО: </b>{$this->dop_data['buyer_rname']}<br>");
		if(@$this->dop_data['buyer_ip'])	$tmpl->AddText("<b>IP адрес: </b>{$this->dop_data['buyer_ip']}<br>");
		$tmpl->AddText("e-mail, прикреплённый к заявке<br><input type='text' name='buyer_email' style='width: 100%' value='{$this->dop_data['buyer_email']}'><br>");
 		$tmpl->AddText("Телефон для sms, прикреплённый к заявке<input type='text' name='buyer_phone' style='width: 100%' value='{$this->dop_data['buyer_phone']}'><br>");
		if(@$this->dop_data['pay_type'])
		{
			$tmpl->AddText("<b>Способ оплаты: </b>");
			switch($this->dop_data['pay_type'])
			{
				case 'bank':	$tmpl->AddText("безналичный");	break;
				case 'cash':	$tmpl->AddText("наличными");	break;
				case 'card':	$tmpl->AddText("платёжной картой");	break;
				case 'card_o':	$tmpl->AddText("платёжной картой на сайте");	break;
				case 'card_t':	$tmpl->AddText("платёжной картой при получении");	break;
				case 'wmr':	$tmpl->AddText("Webmoney WMR");	break;
				default:	$tmpl->AddText("не определён ({$this->dop_data['pay_type']})");
			}
			$tmpl->AddText("<br>");
		}

		$tmpl->AddText("<label><input type='checkbox' name='delivery' value='1' $delivery_checked>Доставка</label><br>
		Желаемая дата доставки:<br><input type='text' name='delivery_date' value='{$this->dop_data['delivery_date']}' style='width: 100%'><br>");
		if(@$this->dop_data['delivery_address'])$tmpl->AddText("<b>Адрес доставки: </b>{$this->dop_data['delivery_address']}<br>");

		$tmpl->AddText("<br><hr>
		Статус (может меняться автоматически):<br>
		<select name='status'>");
		if(@$this->dop_data['status']=='')	$tmpl->AddText("<option value=''>Не задан</option>");
		foreach($CONFIG['doc']['status_list'] as $id => $name)
		{
			$s=(@$this->dop_data['status']==$id)?'selected':'';
			$tmpl->AddText("<option value='$id' $s>$name</option>");
		}

		$tmpl->AddText("</select><br>


		<hr>");
	}

	function DopSave()
	{
		$status=rcv('status');
		$kladovshik=rcv('kladovshik');
		$delivery=rcv('delivery');
		$delivery_date=rcv('delivery_date');
		$buyer_email=rcv('buyer_email');
		$buyer_phone=rcv('buyer_phone');


		settype($kladovshik, 'int');
		$delivery=$delivery?'1':'0';
		if($delivery_date)	$delivery_date=date('Y-m-d H:i:s',strtotime($delivery_date));
		else			$delivery_date='';
		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES
		( '{$this->doc}' ,'kladovshik','$kladovshik'),
		( '{$this->doc}' ,'delivery','$delivery'),
		( '{$this->doc}' ,'delivery_date','$delivery_date'),
		( '{$this->doc}' ,'status','$status'),
		( '{$this->doc}' ,'buyer_email','$buyer_email'),
		( '{$this->doc}' ,'buyer_phone','$buyer_phone')
		");

		if($this->doc)
		{
			$log_data='';
			if(@$this->dop_data['kladovshik']!=$kladovshik)		$log_data.=@"kladovshik: {$this->dop_data['kladovshik']}=>$kladovshik, ";
			if(@$this->dop_data['delivery']!=$delivery)		$log_data.=@"delivery: {$this->dop_data['delivery']}=>$delivery, ";
			if(@$this->dop_data['delivery_date']!=$delivery_date)	$log_data.=@"delivery_date: {$this->dop_data['delivery_date']}=>$delivery_date, ";
			if(@$this->dop_data['status']!=$status)
			{
				$log_data.=@"status: {$this->dop_data['status']}=>$status, ";
				$this->sentZEvent('cstatus:'.$status);
			}
			if(@$this->dop_data['buyer_email']!=$buyer_email)	$log_data.=@"buyer_email: {$this->dop_data['buyer_email']}=>$buyer_email, ";
			if(@$this->dop_data['buyer_phone']!=$buyer_phone)	$log_data.=@"buyer_phone: {$this->dop_data['buyer_phone']}=>$mest, ";

			if($log_data)	doc_log("UPDATE {$this->doc_name}", $log_data, 'doc', $this->doc);
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
		$this->sentZEvent('apply');
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
		$this->sentZEvent('cancel');
	}

	function PrintForm($doc, $opt='')
	{
		if($opt!='')	$this->sentZEvent('print');
		if($opt=='')
		{
			global $tmpl;
			$tmpl->ajax=1;
			$tmpl->AddText("
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=komplekt'\">Накладная на комплектацию</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=schet_pdf'\">Счёт</div>
			<div onclick=\"ShowPopupWin('/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=schet_ue'); return false;\">Счёт в у.е.</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=csv_export'\">Экспорт в CSV</div>");
		}
		else if($opt=='schet')
			$this->PrintSchet();
		else if($opt=='schet_ue')
		{
			global $tmpl;
			$tmpl->ajax=1;
			$tmpl->AddText("<form action=''>
			<input type='hidden' name='mode' value='print'>
			<input type='hidden' name='doc' value='{$this->doc}'>
			<input type='hidden' name='opt' value='schet_ue_p'>
			1 рубль = <input type='text' name='c' value='1'> у.е.
			<input type='submit' value='&gt;&gt;'>
			</form>");
		}
		else if($opt=='schet_ue_p')
		{
			$coeff=rcv('c');
			$this->PrintSchetUE($coeff);
		}
		else if($opt=='schet_pdf')
			$this->PrintPDF();
		else if($opt=='komplekt')
			$this->PrintKomplekt();
		else if($opt=='csv_export')
			$this->CSVExport();
		else $tmpl->logger("Запрошена неизвестная опция!");
	}
	
	/// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		global $tmpl;
		global $uid;

		if($target_type=='')
		{
			$tmpl->ajax=1;
			$tmpl->AddText("
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=t2'\">Реализация (все товары)</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=d2'\">Реализация (неотгруженные)</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=2'\">Реализация (устарело)</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=6'\">Приходный кассовый ордер</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=4'\">Приход средств в банк</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=15'\">Оперативная реализация</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=1'\">Копия заявки</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=16'\">Спецификация (не рек. здесь)</div>");
		}
		else if($target_type=='t2')
		{
			$new_doc=new doc_Realizaciya();
			$dd=$new_doc->CreateFromP($this);
			$new_doc->SetDopData('cena',$this->dop_data['cena']);
			$new_doc->SetDopData('platelshik',$this->doc_data['agent']);
			$new_doc->SetDopData('gruzop',$this->doc_data['agent']);
			$new_doc->SetDopData('received',0);
			$this->sentZEvent('morph_realizaciya');
			header("Location: doc.php?mode=body&doc=$dd");
		}
		else if($target_type=='1')
		{
			$new_doc=new doc_Zayavka();
			$dd=$new_doc->CreateFromP($this);
			$new_doc->SetDopData('cena',$this->dop_data['cena']);
			header("Location: doc.php?mode=body&doc=$dd");
		}
		else if($target_type=='d2')
		{
			$new_doc=new doc_Realizaciya();
			$dd=$new_doc->CreateFromPDiff($this);
			$new_doc->SetDopData('cena',$this->dop_data['cena']);
			$new_doc->SetDopData('platelshik',$this->doc_data['agent']);
			$new_doc->SetDopData('gruzop',$this->doc_data['agent']);
			$new_doc->SetDopData('received',0);
			$this->sentZEvent('morph_realizaciya');
			header("Location: doc.php?mode=body&doc=$dd");
		}
		// Реализация
		else if($target_type==2)
		{
			if(!isAccess('doc_realizaciya','create'))	throw new AccessException("");
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
				$this->sentZEvent('morph_realizaciya');
				$ref="Location: doc.php?mode=body&doc=$base";
				header($ref);
			}
		}
		// Оперативная реализация
		else if($target_type==15)
		{
			$new_doc=new doc_Realiz_op();
			$dd=$new_doc->CreateFromP($this);
			$this->sentZEvent('morph_oprealizaciya');
			header("Location: doc.php?mode=body&doc=$dd");
		}
		else if($target_type==16)
		{
			$new_doc=new doc_Specific();
			$dd=$new_doc->CreateFromP($this);
			header("Location: doc.php?mode=body&doc=$dd");
		}
		else if($target_type==6)
		{
			if(!isAccess('doc_pko','create'))	throw new AccessException("");
			$this->sentZEvent('morph_pko');
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
				$altnum=GetNextAltNum($target_type ,$this->doc_data['subtype'],0,date("Y-m-d",$this->doc_data['date']), $this->doc_data['firm_id']);
				$res=mysql_query("INSERT INTO `doc_list`
				(`type`, `agent`, `date`, `kassa`, `user`, `altnum`, `subtype`, `p_doc`, `sum`, `firm_id`)
				VALUES ('$target_type', '{$this->doc_data[2]}', '$tm', '1', '$uid', '$altnum', '{$this->doc_data[10]}', '$base', '$sum', '{$this->doc_data[17]}')");
				$ndoc= mysql_insert_id();

				if($res)
				{
					mysql_query("COMMIT");
					$this->sentZEvent('morph_realizaciya');
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
			if(!isAccess('doc_pbank','create'))	throw new AccessException("");
			$this->sentZEvent('morph_pbank');
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
				$altnum=GetNextAltNum($target_type ,$this->doc_data['subtype'],0,date("Y-m-d",$this->doc_data['date']), $this->doc_data['firm_id']);
				$res=mysql_query("INSERT INTO `doc_list`
				(`type`, `agent`, `date`, `bank`, `user`, `altnum`, `subtype`, `p_doc`, `sum`, `firm_id`)
				VALUES ('$target_type', '{$this->doc_data[2]}', '$tm', '{$this->doc_data[16]}', '$uid', '$altnum', '{$this->doc_data[10]}', '$base', '$sum', '{$this->doc_data[17]}')");
				$ndoc= mysql_insert_id();
				if($res)
				{
					mysql_query("COMMIT");
					$this->sentZEvent('morph_realizaciya');
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

	function Service()
	{
		global $tmpl;
		$tmpl->ajax=1;
		$opt=rcv('opt');
		$pos=rcv('pos');
		/// TODO: Это уже не нужно?
		if($opt=='fax')
		{
			$faxnum=rcv('faxnum');
			if($faxnum=='')
			{
				$tmpl->ajax=1;
				$res=mysql_query("SELECT `tel` FROM `doc_agent` WHERE `id`='{$this->doc_data[2]}'");
				$faxnum=mysql_result($res,0,0);
				$tmpl->AddText("<form action='' method='get'>
				<input type=hidden name='mode' value='srv'>
				<input type=hidden name='doc' value='{$this->doc}'>
				<input type=hidden name='opt' value='fax'>
				Номер факса:<input type='text' name='faxnum' value='$faxnum'><br>
				Номер должен быть без пробелов, дефисов, и других разделителей!<br>
				<input type='submit' value='&gt;&gt;'>
				</form>");
			}
			else
			{
				$tmpl->ajax=0;
				doc_menu(0,0);
				$res=mysql_query("SELECT `rname`, `tel`, `email` FROM `users` WHERE `id`='{$this->doc_data[8]}'");
				$email=@mysql_result($res,0,2);
				include_once('sendfax.php');
				$fs=new FaxSender();
				$fs->setFileBuf($this->PrintPDF(1));
				$fs->setFaxNumber($faxnum);
				$fs->setNotifyMail($email);
				$res=$fs->send();
				$tmpl->msg("Факс успешно передан на сервер факсов! Вам придёт отчёт о доставке на email.","ok");
			}
		}
		else if($opt=='pmsg')
		{
			try
			{
				$text	= rcv('text');
				if(rcv('sms'))
					$this->sendSMSNotify($text);
				if(rcv('mail'))
					$this->sendEmailNotify($text);
				$tmpl->SetText("{response: 'send'}");
			}
			catch(Exception $e)
			{
				$tmpl->SetText("{response: 'err', text: '".$e->getMessage()."'}");
			}
		}
		else if($opt=='rewrite')
		{

			mysql_query("START TRANSACTION");
			mysql_query("DELETE FROM `doc_list_pos` WHERE `doc`='{$this->doc}'");
			if(mysql_errno())	throw new MysqlException("Не удалось удалить старую номенклатуру");
			$res=mysql_query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$this->doc}'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить подчинённые документы");
			$docs="`doc`='-1'";
			while($nxt=mysql_fetch_row($res))	$docs.=" OR `doc`='$nxt[0]'";
			$res=mysql_query("SELECT `tovar`, `cnt`, `gtd`, `comm`, `cost`, `page` FROM `doc_list_pos` WHERE $docs");
			if(mysql_errno())	throw new MysqlException("Не удалось получить список товаров");
			while($nxt=mysql_fetch_row($res))
			{
				mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `gtd`, `comm`, `cost`, `page`)
				VALUES ('{$this->doc}', '$nxt[0]', '$nxt[1]', '$nxt[2]', '$nxt[3]', '$nxt[4]', '$nxt[5]')");
				if(mysql_errno())	throw new MysqlException("Не удалось добавить товар в список");
			}
			doc_log("REWRITE", "", 'doc', $this->doc);
			mysql_query("COMMIT");
			header("location: /doc.php?mode=body&doc=".$this->doc);
			exit();
		}
		else parent::_Service($opt,$pos);
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

			$res=mysql_query("SELECT `tovar`, `cnt`, `comm`, `cost` FROM `doc_list_pos`
			WHERE `doc_list_pos`.`doc`='{$this->doc}'
			ORDER BY `doc_list_pos`.`id`");
			while($nxt=mysql_fetch_row($res))
			{
				mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `comm`, `cost`)
				VALUES ('$r_id', '$nxt[0]', '$nxt[1]', '$nxt[2]', '$nxt[3]')");
			}
		}
		else
		{
			$new_id=0;
			$res=mysql_query("SELECT `a`.`tovar`, `a`.`cnt`, `a`.`comm`, `a`.`cost`,
			( SELECT SUM(`b`.`cnt`) FROM `doc_list_pos` AS `b`
			INNER JOIN `doc_list` ON `b`.`doc`=`doc_list`.`id` AND `doc_list`.`p_doc`='{$this->doc}' AND `doc_list`.`mark_del`='0'
			WHERE `b`.`tovar`=`a`.`tovar` )
			FROM `doc_list_pos` AS `a`
			WHERE `a`.`doc`='{$this->doc}'
			ORDER BY `a`.`id`");

			while($nxt=mysql_fetch_row($res))
			{
				//echo"$nxt[5] - $nxt[1]<br>";
				if($nxt[4]<$nxt[1])
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
					$n_cnt=$nxt[1]-$nxt[4];
					mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `comm`, `cost`)
 					VALUES ('$new_id', '$nxt[0]', '$n_cnt', '$nxt[2]', '$nxt[3]' )");
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

	function PrintSchet()
	{
		global $tmpl, $CONFIG, $uid;

		if(0)
		//if(!$this->doc_data[6])
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
			WHERE `doc_list_pos`.`doc`='{$this->doc}'
			ORDER BY `doc_list_pos`.`id`");
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
			$tmpl->AddText("<p align='right'>Масса товара: <b>$summass</b> кг.<br></p>");
		}
	}

	function PrintSchetUE($coeff)
	{
		global $tmpl, $CONFIG, $uid;

		if($coeff==0) $coeff=1;

		if(0)
		//if(!$this->doc_data[6])
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
			WHERE `doc_list_pos`.`doc`='{$this->doc}'
			ORDER BY `doc_list_pos`.`id`");
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

	function PrintPDF($to_str=0)
	{
		define('FPDF_FONT_PATH','/var/www/gate/fpdf/font/');
		require('fpdf/fpdf_mc.php');
		global $tmpl, $CONFIG, $uid;

		$res=mysql_query("SELECT `adres`, `tel` FROM `doc_agent` WHERE `id`='{$this->doc_data[2]}'");
		$agent_data=mysql_fetch_row($res);

		$res=mysql_query("SELECT `name`, `bik`, `rs`, `ks` FROM `doc_kassa` WHERE `ids`='bank' AND `num`='{$this->doc_data[16]}'");
		$bank_data=mysql_fetch_row($res);

		$dt=date("d.m.Y",$this->doc_data[5]);

		if(!isset($coeff))	$coeff=1;
		if($coeff==0) $coeff=1;
		if(!$to_str) $tmpl->ajax=1;

		$pdf=new PDF_MC_Table('P');
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
			$size=getimagesize($header_img);
			if(!$size)			throw new Exception("Не удалось открыть файл изображения");
			if($size[2]!=IMAGETYPE_JPEG)	throw new Exception("Файл изображения не в jpeg формате");
			if($size[0]<800)		throw new Exception("Разрешение изображения слишком мало! Допустимя ширина - не менее 800px");
			$width=190;
			$offset_y=($size[1]/$size[0]*$width)+14;
			$pdf->Image($header_img,8,10, $width);
			$pdf->Sety($offset_y);

		}

		$str = "Внимание! Оплата данного счёта означает согласие с условиями поставки товара. Уведомление об оплате обязательно, иначе не гарантируется наличие товара на складе. Товар отпускается по факту прихода денег на р/с поставщика, самовывозом, при наличии доверенности и паспорта. Система интернет-заказов для постоянных клиентов доступна на нашем сайте http://{$CONFIG['site']['name']}.";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0,5,$str,1,'C',0);
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
		$str='Получатель: '.unhtmlentities
		($this->firm_vars['firm_name']);
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

		$pdf->SetLineWidth(0.5);
		$t_width=array(8);
		if($CONFIG['poseditor']['vc'])
		{
			$t_width[]=20;
			$t_width[]=92;
		}
		else	$t_width[]=112;
		$t_width=array_merge($t_width, array(20,25,25));

		$t_text=array('№');
		if($CONFIG['poseditor']['vc'])
		{
			$t_text[]='Код';
			$t_text[]='Наименование';
		}
		else	$t_text[]='Наименование';
		$t_text=array_merge($t_text, array('Кол-во', 'Цена', 'Сумма'));

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
			$aligns[]='R';
			$aligns[]='L';
		}
		else	$aligns[]='L';
		$aligns=array_merge($aligns, array('R','R','R'));

		$pdf->SetAligns($aligns);
		$pdf->SetLineWidth(0.2);
		$pdf->SetFont('','',8);

		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_dop`.`mass`, `doc_base`.`vc`, `class_unit`.`rus_name1` AS `units`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
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

			$row=array($i);
			if(@$CONFIG['poseditor']['vc'])
			{
				$row[]=$nxt[6];
				$row[]=$name;
			}
			else	$row[]=$name;
			$row=array_merge($row, array("$nxt[3] $nxt[7]", $cost, $smcost));

			if( $pdf->h <= ($pdf->GetY()+40 ) ) $pdf->AddPage();
			$pdf->RowIconv($row);
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

		$res=mysql_query("SELECT `worker_real_name`, `worker_phone`, `worker_email` FROM `users_worker_info` WHERE `user_id`='{$this->doc_data[8]}'");
		if(mysql_num_rows($res))
		{
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
		}
		else
		{
			$pdf->SetAutoPageBreak(0,10);
			$pdf->SetY($pdf->h-12);
			$pdf->Ln(1);
			$pdf->SetFont('','',10);
			$str="Login автора: ".$_SESSION['name'];
			$str = iconv('UTF-8', 'windows-1251', $str);
			$pdf->Cell(0,4,$str,0,1,'R',0);
		}

		if($to_str)
			return $pdf->Output('zayavka.pdf','S');
		else
			$pdf->Output('zayavka.pdf','I');
	}


	function PrintKomplekt()
	{
		global $tmpl, $uid, $CONFIG;

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
			<tr><th>№");
			if(@$CONFIG['poseditor']['vc'])	$tmpl->AddText("<th>Код");

			$tmpl->AddText("<th width=450>Наименование<th>Цена<th>Кол-во<th>Остаток<th>Резерв<th>Масса<th>Место");

			$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_base_dop`.`mass`, `doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt`, `doc_list_pos`.`tovar`, `doc_list_pos`.`cost`, `doc_base`.`vc`
			FROM `doc_list_pos`
			LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
			LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data[7]}'
			WHERE `doc_list_pos`.`doc`='{$this->doc}'
			ORDER BY `doc_list_pos`.`id`");
			$i=0;
			$summass=0;
			while($nxt=mysql_fetch_row($res))
			{
				$i++;
				$summass+=$nxt[4]*$nxt[3];
				$cost = sprintf("%01.2f", $nxt[4]);
				$smcost = sprintf("%01.2f", $sm);
				$mass=sprintf("%0.3f",$nxt[4]);

				$rezerv=DocRezerv($nxt[7],$this->doc);

				$ostatok=$nxt[6];

				$tmpl->AddText("<tr align=right><td>$i");
				if(@$CONFIG['poseditor']['vc'])	$tmpl->AddText("<td>$nxt[9]");
				$tmpl->AddText("<td align=left>$nxt[0] $nxt[1] / $nxt[2]<td>$nxt[8]<td>$nxt[3]<td>$ostatok<td>$rezerv<td>$mass<td>$nxt[5]");


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
			$res=mysql_query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `user_id`='$klad_id'");
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

	function CSVExport($to_str=0)
	{
		global $tmpl, $uid;
		if(!$to_str) $tmpl->ajax=1;

		header("Content-type: 'application/octet-stream'");
		header("Content-Disposition: attachment; filename=zayavka.csv;");
		echo"PosNum;ID;Name;Vendor;Cnt;Cost;Sum\r\n";

		$res=mysql_query("SELECT `doc_base`.`id`, `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
 		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		if(!$res)	throw new MysqlException("Не удалось выбрать список товаров в документе");
		$i=0;
		$sum=$summass=0;
		while($nxt=mysql_fetch_row($res))
		{
			$i++;
			$sm=$nxt[5]*$nxt[4];
			$sum+=$sm;
			echo"$i;$nxt[0];\"$nxt[1] $nxt[2]\";\"$nxt[3]\";$nxt[4];$nxt[5];$sm\n";
		}
		echo";;Summary;;;;$sum\n";

	}

};
?>