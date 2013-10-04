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

/// Документ *Заявка покупателя*
class doc_Zayavka extends doc_Nulltype
{
	/// Конструктор
	/// @param doc id документа
	function __construct($doc=0)
	{
		global $CONFIG, $db;
		$this->def_dop_data			=array('status'=>'', 'pie'=>0, 'buyer_phone'=>'', 'buyer_email'=>'',
		    'delivery'=>0, 'delivery_address'=>'', 'delivery_date'=>'', 'ishop'=>0, 'buyer_rname'=>'', 'buyer_ip'=>'',
		    'pay_type'=>'', 'cena'=>'' );
		parent::__construct($doc);
		$this->doc_type				=3;
		$this->doc_name				='zayavka';
		$this->doc_viewname			='Заявка покупателя';
		$this->sklad_editor_enable		=true;
		$this->sklad_modify			=0;
		$this->header_fields			='bank sklad separator agent cena';
		// Избыточный запрос, есть блок отображения подчинённых документов
		$res = $db->query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$this->doc}'");
		if($res->num_rows)		$this->dop_menu_buttons			="<a href='/doc.php?mode=srv&amp;opt=rewrite&amp;doc=$doc' title='Перезаписать номенклатурой из подчинённых документов' onclick='return confirm(\"Подтвертите перезапись номенклатуры документа\")'><img src='img/i_rewrite.png' alt='rewrite'></a>";
		$this->dop_menu_buttons.="<a href='#' onclick='msgMenu(event, {$this->doc})' title='Отправить сообщение покупателю'><img src='/img/i_mailsend.png' alt='msg'></a>";
		if(@$CONFIG['doc']['pie'] && !@$this->dop_data['pie'])
			$this->dop_menu_buttons.="<a href='#' onclick='sendPie(event, {$this->doc})' title='Отправить благодарность покупателю'><img src='/img/i_pie.png' alt='pie'></a>";
		
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
				if( array_search($this->dop_data['status'], $status_options) >= array_search($status, $status_options) )
					return;		
				$this->setDopData('status', $status);
			}

			foreach($CONFIG['zstatus'][$event_name] as $trigger=>$value)
			{
				switch($trigger)
				{

					case 'set_status':	// Установить статус
						$this->setDopData('status', $value);
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
		global $CONFIG, $db;
		if(@$CONFIG['doc']['notify_sms'])
		{
			require_once('include/sendsms.php');
			if(isset($this->dop_data['buyer_phone']))
				$smsphone=$this->dop_data['buyer_phone'];
			else if($this->doc_data['agent']>1)
			{
				$agent_data = $db->selectA('doc_agent', $this->doc_data['user'], array('sms_phone'));
				if(isset($agent_data['sms_phone']))
					$smsphone=$agent_data['sms_phone'];
				if(!$smsphone)
				{
					$user_data = $db->selectA('users', $this->doc_data['user'], array('reg_phone'));
					if(isset($agent_data['reg_phone']))
						$smsphone=$agent_data['reg_phone'];
				}
			}
			else
			{
				$user_data = $db->selectA('users', $this->doc_data['user'], array('reg_phone'));
					if(isset($agent_data['reg_phone']))
						$smsphone=$agent_data['reg_phone'];
			}
			if(preg_match('/^\+79\d{9}$/', $smsphone))
			{
				$sender=new SMSSender();
				$sender->setNumber($smsphone);
				$sender->setContent($text);
				$sender->send();
			}
		}
	}

	/// Отправить email с заданным текстом заказчику
	/// @param text текст отправляемого сообщения
	function sendEmailNotify($text)
	{
		global $CONFIG, $db;
		if(@$CONFIG['doc']['notify_email'])
		{
			if(isset($this->dop_data['buyer_email']))	$email=$this->dop_data['buyer_email'];
			else if($this->doc_data['agent']>1)
			{
				$agent_data = $db->selectA('doc_agent', $this->doc_data['user'], array('email'));
				if(isset($agent_data['email']))
					$email=$agent_data['email'];
				if(!$email)
				{
					$user_data = $db->selectA('users', $this->doc_data['user'], array('email'));
					if(isset($agent_data['email']))
						$email=$agent_data['email'];
				}
			}
			else
			{
				$user_data = $db->selectA('users', $this->doc_data['user'], array('email'));
					if(isset($agent_data['email']))
						$email=$agent_data['email'];
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
		global $tmpl, $CONFIG, $db;
		$klad_id = $this->getDopData('kladovshik');
		if(!$klad_id)	$klad_id=@$this->firm_vars['firm_kladovshik_id'];
		if(!isset($this->dop_data['delivery_date']))	$this->dop_data['delivery_date']='';
		$delivery_checked=@$this->dop_data['delivery']?'checked':'';
		$tmpl->addContent("Кладовщик:<br><select name='kladovshik'>");
		
		$res=$db->query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
		while($nxt=$res->fetch_row())
		{
			$s=($klad_id==$nxt[0])?' selected':'';
			$tmpl->addContent("<option value='$nxt[0]'$s>$nxt[1]</option>");
		}
		$tmpl->addContent("</select><hr>");

		if(@$this->dop_data['ishop'])		$tmpl->addContent("<b>Заявка с интернет-витрины</b><br>");
		if(@$this->dop_data['buyer_rname'])	$tmpl->addContent("<b>ФИО: </b>{$this->dop_data['buyer_rname']}<br>");
		if(@$this->dop_data['buyer_ip'])	$tmpl->addContent("<b>IP адрес: </b>{$this->dop_data['buyer_ip']}<br>");
		if(!isset($this->dop_data['buyer_email']))	$this->dop_data['buyer_email']='';
		if(!isset($this->dop_data['buyer_phone']))	$this->dop_data['buyer_phone']='';
		$tmpl->addContent("e-mail, прикреплённый к заявке<br><input type='text' name='buyer_email' style='width: 100%' value='{$this->dop_data['buyer_email']}'><br>");
 		$tmpl->addContent("Телефон для sms, прикреплённый к заявке<input type='text' name='buyer_phone' style='width: 100%' value='{$this->dop_data['buyer_phone']}'><br>");
		if(@$this->dop_data['pay_type'])
		{
			$tmpl->addContent("<b>Способ оплаты: </b>");
			switch($this->dop_data['pay_type'])
			{
				case 'bank':	$tmpl->addContent("безналичный");	break;
				case 'cash':	$tmpl->addContent("наличными");	break;
				case 'card':	$tmpl->addContent("платёжной картой");	break;
				case 'card_o':	$tmpl->addContent("платёжной картой на сайте");	break;
				case 'card_t':	$tmpl->addContent("платёжной картой при получении");	break;
				case 'wmr':	$tmpl->addContent("Webmoney WMR");	break;
				default:	$tmpl->addContent("не определён ({$this->dop_data['pay_type']})");
			}
			$tmpl->addContent("<br>");
		}

		$tmpl->addContent("<label><input type='checkbox' name='delivery' value='1' $delivery_checked>Доставка</label><br>
		Желаемая дата доставки:<br><input type='text' name='delivery_date' value='{$this->dop_data['delivery_date']}' style='width: 100%'><br>");
		if(@$this->dop_data['delivery_address'])$tmpl->addContent("<b>Адрес доставки: </b>{$this->dop_data['delivery_address']}<br>");

		$tmpl->addContent("<br><hr>
		Статус (может меняться автоматически):<br>
		<select name='status'>");
		if(@$this->dop_data['status']=='')	$tmpl->addContent("<option value=''>Не задан</option>");
		foreach($CONFIG['doc']['status_list'] as $id => $name)
		{
			$s=(@$this->dop_data['status']==$id)?'selected':'';
			$tmpl->addContent("<option value='$id' $s>$name</option>");
		}

		$tmpl->addContent("</select><br><hr>");
	}

	function DopSave()
	{
		$new_data = array(
			'status' => request('status'),
			'kladovshik' => rcvint('kladovshik'),
			'delivery' => request('delivery')?'1':'0',
		    	'delivery_date' => rcvdatetime('delivery_date'),
			'buyer_email' => request('buyer_email'),
			'buyer_phone' => request('buyer_phone')
		);
		$old_data = array_intersect_key($new_data, $this->dop_data);

		$log_data='';
		if($this->doc)
		{
			$log_data = getCompareStr($old_data, $new_data);
			if(@$old_data['status'] != $new_data['status'])
				$this->sentZEvent('cstatus:'.$new_data['status']);
		}
		$this->setDopDataA($new_data);
		if($log_data)	doc_log("UPDATE {$this->doc_name}", $log_data, 'doc', $this->doc);
		
	}

	/// Провести документ
	/// @param silent Не менять отметку проведения
	function DocApply($silent=0) {
		global $db;
		if($silent)	return;
		$data = $db->selectRow('doc_list', $this->doc);
		if(!$data)	throw new Exception('Ошибка выборки данных документа при проведении!');
		if($data['ok'])	throw new Exception('Документ уже проведён!');
		$db->update('doc_list', $this->doc, 'ok', time() );
		$this->sentZEvent('apply');
	}
	
	/// отменить проведение документа
	function DocCancel() {
		global $db;
		$data = $db->selectRow('doc_list', $this->doc);
		if(!$data)		throw new Exception('Ошибка выборки данных документа!');
		if(!$data['ok'])	throw new Exception('Документ не проведён!');
		$db->update('doc_list', $this->doc, 'ok', 0 );
		$this->sentZEvent('cancel');			
	}

	/// Формирование другого документа на основании текущего
	function MorphTo($target_type)
	{
		global $tmpl, $db;

		if($target_type=='')
		{
			$tmpl->ajax=1;
			$tmpl->addContent("
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=t2'\">Реализация (все товары)</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=d2'\">Реализация (неотгруженные)</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=2'\">Реализация (устарело)</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=6'\">Приходный кассовый ордер</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=4'\">Приход средств в банк</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=15'\">Оперативная реализация</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=1'\">Копия заявки</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=16'\">Спецификация (не рек. здесь)</div>");
		}
		else if ($target_type == 't2') {
			if (!isAccess('doc_realizaciya', 'create'))
				throw new AccessException();
			$new_doc = new doc_Realizaciya();
			$dd = $new_doc->createFromP($this);
			$new_doc->setDopData('cena', $this->dop_data['cena']);
			$new_doc->setDopData('platelshik', $this->doc_data['agent']);
			$new_doc->setDopData('gruzop', $this->doc_data['agent']);
			$new_doc->setDopData('received', 0);
			$this->sentZEvent('morph_realizaciya');
			header("Location: doc.php?mode=body&doc=$dd");
		}
		else if ($target_type == '1') {
			if (!isAccess('doc_zayavka', 'create'))
				throw new AccessException();
			$new_doc = new doc_Zayavka();
			$dd = $new_doc->createFromP($this);
			$new_doc->setDopData('cena', $this->dop_data['cena']);
			header("Location: doc.php?mode=body&doc=$dd");
		}
		else if ($target_type == 'd2') {
			$new_doc = new doc_Realizaciya();
			$dd = $new_doc->CreateFromPDiff($this);
			$new_doc->setDopData('cena', $this->dop_data['cena']);
			$new_doc->setDopData('platelshik', $this->doc_data['agent']);
			$new_doc->setDopData('gruzop', $this->doc_data['agent']);
			$new_doc->setDopData('received', 0);
			$this->sentZEvent('morph_realizaciya');
			header("Location: doc.php?mode=body&doc=$dd");
		}
		// Реализация
		else if($target_type==2)
		{
			if(!isAccess('doc_realizaciya','create'))
				throw new AccessException();
			$db->startTransaction();
			$base = $this->Otgruzka();
			if(!$base){
				$db->rollback();
				$tmpl->msg("Не удалось создать подчинённый документ!","err");
			}
			else{
				$db->commit();
				$this->sentZEvent('morph_realizaciya');
				$ref="Location: doc.php?mode=body&doc=$base";
				header($ref);
			}
		}
		// Оперативная реализация
		else if($target_type==15)
		{
			if(!isAccess('doc_realiz_op','create'))
				throw new AccessException();
			$new_doc=new doc_Realiz_op();
			$dd=$new_doc->createFromP($this);
			$this->sentZEvent('morph_oprealizaciya');
			header("Location: doc.php?mode=body&doc=$dd");
		}
		else if($target_type==16)
		{
			if(!isAccess('doc_specific','create'))
				throw new AccessException();
			$new_doc=new doc_Specific();
			$dd=$new_doc->createFromP($this);
			header("Location: doc.php?mode=body&doc=$dd");
		}
		else if($target_type==6)
		{
			if(!isAccess('doc_pko','create'))
				throw new AccessException();
			$this->sentZEvent('morph_pko');
			$sum = $this->recalcSum();
			$db->startTransaction();
			$base=$this->Otgruzka();
			if(!$base){
				$db->rollback();
				$tmpl->msg("Не удалось создать подчинённый документ!","err");
			}
			else{
				$new_doc=new doc_Pko();
				$doc_data=$this->doc_data;
				$doc_data['p_doc']=$base;
				$dd = $new_doc->create($doc_data);

				$new_doc->setDocData('kassa', 1);
				$db->commit();
				$ref="Location: doc.php?mode=body&doc=".$dd;
				header($ref);
			}
		}
		else if($target_type==4)
		{
			if(!isAccess('doc_pbank','create'))	throw new AccessException("");
			$this->sentZEvent('morph_pbank');
			$sum = $this->recalcSum();
			$db->startTransaction();
			$base=$this->Otgruzka();
			if(!$base)
			{
				$db->rollback();
				throw new Exception("Не удалось создать подчинённый документ!");
			}
			else
			{
				$new_doc=new doc_PBank();
				$doc_data=$this->doc_data;
				$doc_data['p_doc']=$base;
				$dd = $new_doc->create($doc_data);

				$db->commit();
				$ref="Location: doc.php?mode=body&doc=".$dd;
				header($ref);
			}
		}
		else
		{
			$tmpl->msg("В разработке","info");
		}
	}

	function Service()
	{
		global $tmpl, $CONFIG, $db;
		$tmpl->ajax=1;
		$opt=request('opt');
		$pos=rcvint('pos');
		if($opt=='pmsg')
		{
			try
			{
				$text = request('text');
				if(request('sms'))
					$this->sendSMSNotify($text);
				if(request('mail'))
					$this->sendEmailNotify($text);
				$tmpl->setContent("{response: 'send'}");
			}
			catch(Exception $e)
			{
				$tmpl->setContent("{response: 'err', text: '".$e->getMessage()."'}");
			}
		}
		else if($opt=='rewrite')
		{
			$db->startTransaction();
			$db->query("DELETE FROM `doc_list_pos` WHERE `doc`='{$this->doc}'");
			$res=$db->query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$this->doc}'");
			$docs="`doc`='-1'";
			while($nxt=$res->fetch_row())
				$docs.=" OR `doc`='$nxt[0]'";
			$res=$db->query("SELECT `doc`, `tovar`, `cnt`, `gtd`, `comm`, `cost`, `page` FROM `doc_list_pos` WHERE $docs");
			while($line = $res->fetch_assoc())
			{
				$nxt['doc']=$this->doc;
				$db->insertA('doc_list_pos', $line);
			}
			doc_log("REWRITE", "", 'doc', $this->doc);
			$db->commit();
			header("location: /doc.php?mode=body&doc=".$this->doc);
			//exit();
		}
		else if($opt=='pie')
		{
			try
			{
				$this->sendEmailNotify($CONFIG['doc']['pie']);
				$db->query("INSERT INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES	( '{$this->doc}' ,'pie','1')");
				$tmpl->setContent("{response: 'send'}");
			}
			catch(Exception $e)
			{
				$tmpl->setContent("{response: 'err', text: '".$e->getMessage()."'}");
			}
		}
		else parent::_Service($opt,$pos);
	}
	
	/// Отгрузить текущую реализацию
	function Otgruzka() {
		$this->recalcSum();
		$newdoc = new doc_Realizaciya();
		$newdoc_id = $newdoc->createFromPdiff($this);
		$newdoc->setDopData('cena', $this->dop_data['cena']);
		return $newdoc_id;
	}
	
	/// Сформировать документ в PDF формате
	/// @param to_str	Вернуть в виде строки (иначе - вывести в броузер)
	function PrintPDF($to_str=0)
	{
		require('fpdf/fpdf_mc.php');
		global $tmpl, $CONFIG, $db;
		if(!$to_str) $tmpl->ajax=1;
		
		$agent_data = $db->selectRow('doc_agent', $this->doc_data['agent']);
		$res = $db->query("SELECT `name`, `bik`, `rs`, `ks` FROM `doc_kassa` WHERE `ids`='bank' AND `num`='{$this->doc_data['bank']}'");
		$bank_data = $res->fetch_assoc();
		
		$pdf = new PDF_MC_Table('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(0,10);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=5;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		if( @$CONFIG['site']['doc_header'] ) {
			$header_img = str_replace('{FN}', $this->doc_data['firm_id'], $CONFIG['site']['doc_header']);
			$size = getimagesize($header_img);
			if(!$size)			throw new Exception("Не удалось открыть файл изображения");
			if($size[2] != IMAGETYPE_JPEG)	throw new Exception("Файл изображения не в jpeg формате");
			if($size[0] < 800)		throw new Exception("Разрешение изображения слишком мало! Допустимя ширина - не менее 800px");
			$width = 190;
			$offset_y= $size[1]/$size[0]*$width+14;
			$pdf->Image($header_img, 8, 10, $width);
			$pdf->Sety($offset_y);
		}

		$str = "Внимание! Оплата данного счёта означает согласие с условиями поставки товара. Уведомление об оплате обязательно, иначе не гарантируется наличие товара на складе. Товар отпускается по факту прихода денег на р/с поставщика, самовывозом, при наличии доверенности и паспорта. Система интернет-заказов со специальными ценами для постоянных клиентов доступна на нашем сайте http://{$CONFIG['site']['name']}.";
		$pdf->MultiCellIconv(0,5,$str,1,'C',0);
		$pdf->y++;		
		$pdf->SetFont('','U',10);
		$str='Счёт действителен в течение трёх банковских дней!';
		$pdf->CellIconv(0,5,$str,0,1,'C',0);
		
		$pdf->SetFont('','',11);
		$str='Образец заполнения платёжного поручения:';
		$pdf->CellIconv(0,5,$str,0,1,'C',0);

		$old_x=$pdf->GetX();
		$old_y=$pdf->GetY();
		$old_margin=$pdf->lMargin;
		$table_c=110;
		$table_c2=15;

		$pdf->SetFont('','',12);
		$pdf->CellIconv($table_c, 10, $bank_data['name'], 1, 1, 'L', 0);
		$str='ИНН '.$this->firm_vars['firm_inn'].' КПП';
		$pdf->CellIconv($table_c, 5, $str, 1, 1, 'L', 0);
		
		$tx=$pdf->GetX();
		$ty=$pdf->GetY();
		$pdf->CellIconv($table_c,10,'',1,1,'L',0);
		$pdf->lMargin=$old_x+1;
		$pdf->SetX($tx+1);
		$pdf->SetY($ty+1);
		$pdf->SetFont('','',9);
		$str='Получатель: '.$this->firm_vars['firm_name'];
		$pdf->MultiCellIconv($table_c,3,$str,0,1,'L',0);

		$pdf->SetFont('','',12);
		$pdf->lMargin=$old_x+$table_c;
		$pdf->SetY($old_y);
		$str='БИК';
		$pdf->CellIconv($table_c2,5,$str,1,1,'L',0);
		$str='корр/с';
		$pdf->CellIconv($table_c2,10,$str,1,1,'L',0);
		$str='р/с N';
		$pdf->CellIconv($table_c2,10,$str,1,1,'L',0);

		$pdf->lMargin=$old_x+$table_c+$table_c2;
		$pdf->SetY($old_y);
		$pdf->Cell(0,5,$bank_data['bik'],1,1,'L',0);
		$pdf->Cell(0,5,$bank_data['ks'],1,1,'L',0);
		$pdf->Cell(0,15,$bank_data['rs'],1,1,'L',0);
		$pdf->lMargin=$old_margin;
		$pdf->SetY($old_y+30);

		$pdf->SetFont('','',16);
		$str='Счёт № '.$this->doc_data['altnum'].', от '.date("d.m.Y", $this->doc_data['date']);		
		$pdf->CellIconv(0,5,$str,0,1,'L',0);
		$pdf->SetFont('','',8);
		$str="Поставщик: {$this->firm_vars['firm_name']}, {$this->firm_vars['firm_adres']}, тел: {$this->firm_vars['firm_telefon']}";
		$pdf->MultiCellIconv(0,4,$str,0,1,'L',0);
		$str="Покупатель: {$agent_data['fullname']}, адрес: {$agent_data['adres']}, телефон: {$agent_data['tel']}";
		$pdf->MultiCellIconv(0,4,$str,0,1,'L',0);

		$pdf->Ln(3);
		$pdf->SetFont('','',11);
		$str = str_replace("<br>",", ",$this->doc_data['comment']);
		$pdf->MultiCellIconv(0,5,$str,0,1,'L',0);

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
		foreach($t_width as $id=>$w) {
			$pdf->CellIconv($w,6,$t_text[$id],1,0,'C',0);
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

		$res = $db->query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_dop`.`mass`, `doc_base`.`vc`, `class_unit`.`rus_name1` AS `units`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$sum=$summass=0;
		while($nxt = $res->fetch_row())
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

		if($this->doc_data['nds'])
		{
			$nds=$sum/(100+$this->firm_vars['param_nds'])*$this->firm_vars['param_nds'];
			$nds = sprintf("%01.2f", $nds);
			$pdf->SetFont('','',12);
			$str="Итого: $sumcost руб.";
			$pdf->CellIconv(0,7,$str,0,1,'R',0);
			$str="В том числе НДС ".$this->firm_vars['param_nds']."%: $nds руб.";
			$pdf->CellIconv(0,5,$str,0,1,'R',0);

			$pdf->SetFont('','',8);
			$str="Всего $i наименований, на сумму $sumcost руб. ($cost)";
			$pdf->CellIconv(0,4,$str,0,1,'L',0);
			$str="В том числе НДС ".$this->firm_vars['param_nds']."%: $nds руб.";
			$pdf->CellIconv(0,4,$str,0,1,'L',0);

		}
		else
		{
			$nds=$sum*$this->firm_vars['param_nds']/100;
			$cst=$sum+$nds;
			$nds_p = sprintf("%01.2f", $nds);
			$cost2 = sprintf("%01.2f", $cst);
			$pdf->SetFont('','',10);
			$str="Итого: $sumcost руб.";
			$pdf->CellIconv(0,5,$str,0,1,'R',0);
			$str="НДС ".$this->firm_vars['param_nds']."%: $nds_p руб.";
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
			$str="Всего: $cost2 руб.";
			$pdf->CellIconv(0,4,$str,0,1,'R',0);

			$pdf->SetFont('','',8);
			$str="Всего $i наименований, на сумму $sumcost руб. ($cost)";
			$pdf->CellIconv(0,4,$str,0,1,'L',0);
			$str="Кроме того, НДС ".$this->firm_vars['param_nds']."%: $nds_p, Всего $cost2 руб.";
			$pdf->CellIconv(0,4,$str,0,1,'L',0);
		}

		$res = $db->query("SELECT `worker_real_name`, `worker_phone`, `worker_email` FROM `users_worker_info` WHERE `user_id`='{$this->doc_data['user']}'");
		if($res->num_rows) {
			$worker_info = $res->fetch_assoc();
			$pdf->SetAutoPageBreak(0,10);
			$pdf->SetY($pdf->h-18);
			$pdf->Ln(1);
			$pdf->SetFont('','',10);
			$str="Отв. оператор ".$worker_info['worker_real_name'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
			$str="Контактный телефон: ".$worker_info['worker_phone'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
			$str="Электронная почта: ".$worker_info['worker_email'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
		}
		else {
			$pdf->SetAutoPageBreak(0,10);
			$pdf->SetY($pdf->h-12);
			$pdf->Ln(1);
			$pdf->SetFont('','',10);
			$str="Login автора: ".$_SESSION['name'];
			$pdf->CellIconv(0,4,$str,0,1,'R',0);
		}

		if($to_str)
			return $pdf->Output('zayavka.pdf','S');
		else
			$pdf->Output('zayavka.pdf','I');
	}
};
?>