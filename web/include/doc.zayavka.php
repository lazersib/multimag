<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2015, BlackLight, TND Team, http://tndproject.org
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
class doc_Zayavka extends doc_Nulltype {
    /// Конструктор
    /// @param doc id документа
    function __construct($doc = 0) {
        global $CONFIG, $db;
        $this->def_dop_data = array('status' => '', 'pie' => 0, 'buyer_phone' => '', 'buyer_email' => '',
            'delivery' => 0, 'delivery_address' => '', 'delivery_date' => '', 'delivery_region' => '', 'ishop' => 0, 'buyer_rname' => '', 'buyer_ip' => '',
            'pay_type' => '', 'cena' => '');
        parent::__construct($doc);
        $this->doc_type = 3;
        $this->typename = 'zayavka';
        $this->viewname = 'Заявка покупателя';
        $this->sklad_editor_enable = true;
        $this->sklad_modify = 0;
        $this->header_fields = 'bank sklad separator agent cena';

        settype($this->id, 'int');
    }

    /// Получить строку с HTML кодом дополнительных кнопок документа
    protected function getAdditionalButtonsHTML() {
        global $CONFIG;
        $ret = "<a href='#' onclick='msgMenu(event, '{$this->id}')' title='Отправить сообщение покупателю'><img src='/img/i_mailsend.png' alt='msg'></a>";        
        if (@$CONFIG['doc']['pie'] && !@$this->dop_data['pie']) {
            $ret.="<a href='#' onclick='sendPie(event, '{$this->id}')' title='Отправить благодарность покупателю'><img src='/img/i_pie.png' alt='pie'></a>";
        }
        return $ret;
    }
	
	/// Функция обработки событий, связанных  с заказом
	/// @param event_name Полное название события
	public function dispatchZEvent($event_name)
	{
		global $CONFIG;
		if(isset($CONFIG['zstatus'][$event_name]))
		{
			$s=array('{DOC}','{SUM}','{DATE}');
			$r=array($this->id,$this->doc_data['sum'],date('Y-m-d',$this->doc_data['date']));
			
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
				mailto($email,"Заказ N {$this->id} на {$CONFIG['site']['name']}", $user_msg);
			}
		}

	}

	function DopHead()
	{
		global $tmpl, $CONFIG, $db;
		if(!isset($this->dop_data['delivery_date']))	$this->dop_data['delivery_date']='';

		$tmpl->addContent("<hr>");

		if(@$this->dop_data['ishop'])		$tmpl->addContent("<b>Заявка с интернет-витрины</b><br>");
		if(@$this->dop_data['buyer_rname'])	$tmpl->addContent("<b>ФИО: </b>{$this->dop_data['buyer_rname']}<br>");
		if(@$this->dop_data['buyer_ip'])	$tmpl->addContent("<b>IP адрес: </b>{$this->dop_data['buyer_ip']}<br>");
		if(@$this->dop_data['pay_type']) {
			$tmpl->addContent("<b>Выбранный способ оплаты: </b>");
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
		if(!isset($this->dop_data['buyer_email']))	$this->dop_data['buyer_email']='';
		if(!isset($this->dop_data['buyer_phone']))	$this->dop_data['buyer_phone']='';
		$tmpl->addContent("e-mail, прикреплённый к заявке<br><input type='text' name='buyer_email' style='width: 100%' value='{$this->dop_data['buyer_email']}'><br>");
 		$tmpl->addContent("Телефон для sms, прикреплённый к заявке<input type='text' name='buyer_phone' style='width: 100%' value='{$this->dop_data['buyer_phone']}'><br>");

		$tmpl->addContent("Доставка:<br><select name='delivery'><option value='0'>Не требуется</option>");
		$res = $db->query("SELECT `id`, `name` FROM `delivery_types` ORDER BY `id`");
		while($nxt = $res->fetch_row()) {
			if($nxt[0]==$this->dop_data['delivery'])
				$tmpl->addContent("<option value='$nxt[0]' selected>".html_out($nxt[1])."</option>");
			else
				$tmpl->addContent("<option value='$nxt[0]'>".html_out($nxt[1])."</option>");
		}
		
		$tmpl->addContent("</select>
		Регион доставки:<br><select name='delivery_region'><option value='0'>Не задан</option>");
		$res = $db->query("SELECT `id`, `name` FROM `delivery_regions` ORDER BY `id`");
		while($nxt = $res->fetch_row()) {
			if($nxt[0]==$this->dop_data['delivery_region'])
				$tmpl->addContent("<option value='$nxt[0]' selected>".html_out($nxt[1])."</option>");
			else
				$tmpl->addContent("<option value='$nxt[0]'>".html_out($nxt[1])."</option>");
		}
		
		$tmpl->addContent("</select>
			Желаемая дата доставки:<br>
			<input type='text' name='delivery_date' value='{$this->dop_data['delivery_date']}' style='width: 100%'><br>");
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
			'delivery' => rcvint('delivery'),
			'delivery_regions' => rcvint('delivery_regions'),
		    	'delivery_date' => request('delivery_date'),
			'buyer_email' => request('buyer_email'),
			'buyer_phone' => request('buyer_phone')
		);
		$old_data = array_intersect_key($new_data, $this->dop_data);

		$log_data='';
		if($this->id)
		{
			$log_data = getCompareStr($old_data, $new_data);
			if(@$old_data['status'] != $new_data['status'])
				$this->sentZEvent('cstatus:'.$new_data['status']);
		}
		$this->setDopDataA($new_data);
		if($log_data)	doc_log("UPDATE {$this->typename}", $log_data, 'doc', $this->id);
		
	}

    /// Провести документ
    /// @param silent Не менять отметку проведения
    function docApply($silent = 0) {
        global $db;
        // Резервы
        $res = $db->query("SELECT `id`, `ok` FROM `doc_list` WHERE `ok`>0 AND `type`=2 AND `p_doc`={$this->id}");
        if (!$res->num_rows) {
            $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`
                FROM `doc_list_pos`
                LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
                WHERE `doc_list_pos`.`doc`='{$this->id}'");
            $vals = '';
            while ($nxt = $res->fetch_row()) {
                if ($vals) {
                    $vals .= ',';
                }
                $vals .= "('$nxt[0]', '$nxt[1]')";
            }
            if($vals) {
                $db->query("INSERT INTO `doc_base_dop` (`id`, `reserve`) VALUES $vals
                    ON DUPLICATE KEY UPDATE `reserve`=`reserve`+VALUES(`reserve`)");
            } else {
                throw new Exception("Не удалось провести пустой документ!");
            }
        }
        if ($silent) {
            return;
        }
        if (!$this->isAltNumUnique()) {
            throw new Exception("Номер документа не уникален!");
        }
        $data = $db->selectRow('doc_list', $this->id);
        if (!$data) {
            throw new Exception('Ошибка выборки данных документа при проведении!');
        }
        if ($data['ok']) {
            throw new Exception('Документ уже проведён!');
        }
        $db->update('doc_list', $this->id, 'ok', time());        
        $this->sentZEvent('apply');
    }

    /// Отменить проведение документа
    function docCancel() {
        global $db;
        $data = $db->selectRow('doc_list', $this->id);
        if (!$data) {
            throw new Exception('Ошибка выборки данных документа!');
        }
        if (!$data['ok']) {
            throw new Exception('Документ не проведён!');
        }
        $db->update('doc_list', $this->id, 'ok', 0);
        // Резервы
        $res = $db->query("SELECT `id`, `ok` FROM `doc_list` WHERE `ok`>0 AND `type`=2 AND `p_doc`={$this->id}");
        if (!$res->num_rows) {
            $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`
            FROM `doc_list_pos`
            LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
            WHERE `doc_list_pos`.`doc`='{$this->id}'");
            $vals = '';
            while ($nxt = $res->fetch_row()) {
                if ($vals) {
                    $vals .= ',';
                }
                $vals .= "('$nxt[0]', '$nxt[1]')";
            }
            if($vals) {
                $db->query("INSERT INTO `doc_base_dop` (`id`, `reserve`) VALUES $vals
                    ON DUPLICATE KEY UPDATE `reserve`=`reserve`-VALUES(`reserve`)");
            }
        }
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
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=t2'\">Реализация (все товары)</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=d2'\">Реализация (неотгруженные)</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=2'\">Реализация (устарело)</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=6'\">Приходный кассовый ордер</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=4'\">Приход средств в банк</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=15'\">Оперативная реализация</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=1'\">Копия заявки</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=16'\">Спецификация (не рек. здесь)</div>");
		}
		else if ($target_type == 't2') {
			if (!isAccess('doc_realizaciya', 'create'))
				throw new AccessException();
			$new_doc = new doc_Realizaciya();
			$dd = $new_doc->createFromP($this);
			$new_doc->setDopData('cena', $this->dop_data['cena']);
			$new_doc->setDopData('platelshik', $this->doc_data['agent']);
			$new_doc->setDopData('gruzop', $this->doc_data['agent']);
			$new_doc->setDopData('ishop', $this->dop_data['ishop']);
			$new_doc->setDopData('received', 0);
			$this->sentZEvent('morph_realizaciya');
			header("Location: doc.php?mode=body&doc=$dd");
		}
		else if ($target_type == 1) {
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
			$new_doc->setDopData('ishop', $this->dop_data['ishop']);
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
			$new_doc->setDopData('ishop', $this->dop_data['ishop']);
			$this->sentZEvent('morph_oprealizaciya');
			header("Location: doc.php?mode=body&doc=$dd");
		}
		else if($target_type==16)
		{
			if(!isAccess('doc_specific','create'))
				throw new AccessException();
			$new_doc=new doc_Specific();
			$dd=$new_doc->createFromP($this);
			$new_doc->setDopData('ishop', $this->dop_data['ishop']);
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
		else	$tmpl->msg("В разработке","info");
	}

	function Service() {
		global $tmpl, $CONFIG, $db;
		$tmpl->ajax = 1;
		$opt = request('opt');
		$pos = rcvint('pos');
		if ($opt == 'pmsg') {
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
		else if($opt=='rewrite') {
			$db->startTransaction();
			$db->query("DELETE FROM `doc_list_pos` WHERE `doc`='{$this->id}'");
			$res = $db->query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$this->id}'");
			$docs = "`doc`='-1'";
			while($nxt=$res->fetch_row())
				$docs.=" OR `doc`='$nxt[0]'";
			$res=$db->query("SELECT `doc`, `tovar`, SUM(`cnt`) AS `cnt`, `gtd`, `comm`, `cost`, `page` FROM `doc_list_pos` WHERE $docs GROUP BY `tovar`");
			while($line = $res->fetch_assoc()) {
				$line['doc']=$this->id;
				$db->insertA('doc_list_pos', $line);
			}
			doc_log("REWRITE", "", 'doc', $this->id);
			$db->commit();
			header("location: /doc.php?mode=body&doc=".$this->id);
			//exit();
		}
		else if($opt=='pie')
		{
			try
			{
				$this->sendEmailNotify($CONFIG['doc']['pie']);
				$db->query("INSERT INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES	( '{$this->id}' ,'pie','1')");
				$tmpl->setContent("{response: 'send'}");
			}
			catch(Exception $e)
			{
				$tmpl->setContent("{response: 'err', text: '".$e->getMessage()."'}");
			}
		}
		else parent::_Service($opt,$pos);
	}
	
	/// Отгрузить текущую заявку
	function Otgruzka() {
		$this->recalcSum();
		$newdoc = new doc_Realizaciya();
		$newdoc_id = $newdoc->createFromPdiff($this);
		$newdoc->setDopData('cena', $this->dop_data['cena']);
		return $newdoc_id;
	}
}
