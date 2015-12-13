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
    public function dispatchZEvent($event_name, $initator = null) {
        global $CONFIG;
        if(@$CONFIG['zstatus']['debug']) {
            doc_log("EVENT", "$event_name, cur_status:{$this->dop_data['status']}", 'doc', $this->id);
        }
        if (isset($CONFIG['zstatus'][$event_name])) {
            $s = array('{DOC}', '{SUM}', '{DATE}');
            $r = array($this->id, $this->doc_data['sum'], date('Y-m-d', $this->doc_data['date']));
            foreach($this->doc_data as $name => $value) {
                $s[] = '{'.strtoupper($name).'}';
                $r[] = $value;
            }
            foreach($this->dop_data as $name => $value) {
                $s[] = '{DOP_'.strtoupper($name).'}';
                $r[] = $value;
            }
            if($initator) {
                foreach($initator->doc_data as $name => $value) {
                    $s[] = '{I_'.strtoupper($name).'}';
                    $r[] = $value;
                }
                foreach($initator->dop_data as $name => $value) {
                    $s[] = '{I_DOP_'.strtoupper($name).'}';
                    $r[] = $value;
                }
            }
            // Проверка и повышение статуса. Если повышение не произошло - остальные действия не выполняются
            if (isset($CONFIG['zstatus'][$event_name]['testup_status'])) {
                $status = $CONFIG['zstatus'][$event_name]['testup_status'];
                $status_options = array(0 => 'new', 1 => 'inproc', 2 => 'ready', 3 => 'ok', 4 => 'err');
                // Если устанавливаемый статус не стандартный - прервать тест
                if (!in_array($status, $status_options)) {
                    return false;
                }
                // Если текущий статус не стандартный - прервать тест
                if (@$this->dop_data['status'] == $status) {
                    return false;
                }
                // Если устанавливаемый статус равен текущему - прервать тест
                if ($this->dop_data['status'] == $status) {
                    return false;
                }
                // Если статус меняется на уменьшение - прервать тест
                if (array_search($this->dop_data['status'], $status_options) >= array_search($status, $status_options)) {
                    return false;
                }
                $this->setDopData('status', $status);
            }

            foreach ($CONFIG['zstatus'][$event_name] as $trigger => $value) {
                switch ($trigger) {
                    case 'set_status': // Установить статус
                        $this->setDopData('status', $value);
                        break;
                    case 'send_sms': // Послать sms сообщение
                        $value = str_replace($s, $r, $value);
                        $this->sendSMSNotify($value);
                        break;
                    case 'send_email': // Послать email сообщение
                        $value = str_replace($s, $r, $value);
                        $this->sendEmailNotify($value);
                        break;
                    case 'send_xmpp': // Послать XMPP сообщение
                        $value = str_replace($s, $r, $value);
                        $this->sendXMPPNotify($value);
                        break;
                    case 'notify':  // Известить всеми доступными способами
                        $value = str_replace($s, $r, $value);
                        $this->sendNotify($value);  
                        break;

                    /// TODO:
                    /// Отправка по телефону (голосом)
                    /// Отправка по телефону (факсом)
                }
            }
            return true;
        }
        return false;
    }

    /// Отправить SMS с заданным текстом заказчику на первый из подходящих номеров
    /// @param text текст отправляемого сообщения
    function sendSMSNotify($text) {
        global $CONFIG, $db;
        if (!$CONFIG['doc']['notify_sms']) {
            return false;
        }        
        if (isset($this->dop_data['buyer_phone'])) {
            if(preg_match('/^\+79\d{9}$/', $this->dop_data['buyer_phone'])) {
                $smsphone = $this->dop_data['buyer_phone'];
            }
        } 
        if ($this->doc_data['agent'] > 1 && !$smsphone) {
            $agent = new \models\agent($this->doc_data['agent']);
            $smsphone = $agent->getSMSPhone();                
        }
        if (!$smsphone && $this->dop_data['ishop']) {
            $user_data = $db->selectA('users', $this->doc_data['user'], array('reg_phone'));
            if (isset($user_data['reg_phone'])) {
                $smsphone = $user_data['reg_phone'];
            }
        }
        if (preg_match('/^\+79\d{9}$/', $smsphone)) {
            require_once('include/sendsms.php');
            $sender = new SMSSender();
            $sender->setNumber($smsphone);
            $sender->setContent($text);
            $sender->send();
            if(@$CONFIG['doc']['notify_debug']) {
                doc_log("NOTIFY SMS", "number:$smsphone; text:$text", 'doc', $this->id);
            } 
            return true;
        }
        return false;
    }

    /// Отправить email с заданным текстом заказчику на все доступные адреса
    /// @param text текст отправляемого сообщения
    function sendEmailNotify($text, $subject=null) {
        global $CONFIG, $db;
        $pref = \pref::getInstance();
        if (!$CONFIG['doc']['notify_email']) {
            return false;
        }
        $emails = array();
        if (isset($this->dop_data['buyer_email'])) {
            if($this->dop_data['buyer_email']) {
                $emails[$this->dop_data['buyer_email']] = $this->dop_data['buyer_email'];
            }
        }
        if ($this->doc_data['agent'] > 1) {
            $agent = new \models\agent($this->doc_data['agent']);
            $contacts = $agent->contacts;
            foreach($contacts as $line) {
                if($line['type']=='email') {
                    $emails[$line['value']] = $line['value'];
                }
            }
        }
        if($this->dop_data['ishop']) {
            $user_data = $db->selectA('users', $this->doc_data['user'], array('reg_email'));
            if (isset($user_data['reg_email'])) {
                $emails[] = $user_data['reg_email'];
            }
        }
        if(count($emails)>0) {
            foreach($emails as $email) {
                $user_msg = "Уважаемый клиент!\n" . $text;
                if(!$subject) {
                    $subject = "Заказ N {$this->id} на {$pref->site_name}";
                }
                mailto($email, $subject, $user_msg);
                if(@$CONFIG['doc']['notify_debug']) {
                    doc_log("NOTIFY Email", "email:$email; text:$user_msg", 'doc', $this->id);
                }
            }
            return true;
        }
        return false;
    }
    
    /// Отправить сообщение по XMPP с заданным текстом заказчику на все доступные адреса
    /// @param text текст отправляемого сообщения
    function sendXMPPNotify($text) {
        global $CONFIG, $db;
        if (!$CONFIG['doc']['notify_xmpp']) {
            return false;
        }
        $addresses = array();
        if ($this->doc_data['agent'] > 1) {
            $agent = new \models\agent($this->doc_data['agent']);
            $contacts = $agent->contacts;
            foreach($contacts as $line) {
                if($line['type']=='jid' || $line['type']=='xmpp') {
                    $addresses[$line['value']] = $line['value'];
                }
            }
        }
        if($this->dop_data['ishop']) {
            $user_data = $db->selectA('users', $this->doc_data['user'], array('jid'));
            if (isset($user_data['jid'])) {
                $addresses[] = $user_data['jid'];
            }
        }
        if(count($addresses)>0) {
            require_once($CONFIG['location'].'/common/XMPPHP/XMPP.php');
            $xmppclient = new XMPPHP_XMPP( $CONFIG['xmpp']['host'], $CONFIG['xmpp']['port'], $CONFIG['xmpp']['login'], $CONFIG['xmpp']['pass'], 'MultiMag r'.MULTIMAG_REV);
            $xmppclient->connect();
            $xmppclient->processUntil('session_start');
            $xmppclient->presence();
            foreach($addresses as $addr) {                
                $xmppclient->message($addr, $text);                    
                if(@$CONFIG['doc']['notify_debug']) {
                    doc_log("NOTIFY xmpp", "jid:$addr; text:$text", 'doc', $this->id);
                }
            }
            $xmppclient->disconnect();
            return true;
        }
        return false;
    }

        function DopHead() {
		global $tmpl, $CONFIG, $db;
		if(!isset($this->dop_data['delivery_date']))	$this->dop_data['delivery_date']='';

		$tmpl->addContent("<hr>");

		if(@$this->dop_data['ishop'])		$tmpl->addContent("<b>Заявка с интернет-витрины</b><br>");
		if(@$this->dop_data['buyer_rname'])	$tmpl->addContent("<b>ФИО: </b>{$this->dop_data['buyer_rname']}<br>");
		if(@$this->dop_data['buyer_ip'])	$tmpl->addContent("<b>IP адрес: </b>{$this->dop_data['buyer_ip']}<br>");
		if(@$this->dop_data['pay_type']) {
			$tmpl->addContent("<b>Способ оплаты: </b>");
			switch($this->dop_data['pay_type'])
			{
				case 'bank':	$tmpl->addContent("безналичный");	break;
				case 'cash':	$tmpl->addContent("наличными");	break;
				case 'card':	$tmpl->addContent("картой ?");	break;
				case 'card_o':	$tmpl->addContent("картой на сайте");	break;
				case 'card_t':	$tmpl->addContent("картой при получении");	break;
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
        $this->fixPrice();
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
    function MorphTo($target_type) {
        global $tmpl, $db;
        $morphs = array(
                't2' => ['acl_object' => 'doc.realizaciya', 'viewname' => 'Реализация (все товары)', ],
                'd2' => ['acl_object' => 'doc.realizaciya', 'viewname' => 'Реализация (неотгруженные)', ],
                '6' =>  ['acl_object' => 'doc.pko',         'viewname' => 'Приходный кассовый ордер', ],
                '4' =>  ['acl_object' => 'doc.pbank',       'viewname' => 'Приход средств в банк', ],
                '15' => ['acl_object' => 'doc.realiz_op',   'viewname' => 'Оперативная реализация', ],
                '1' =>  ['acl_object' => 'doc.zayavka',     'viewname' => 'Копия заявки', ],
                '16' => ['acl_object' => 'doc.specific',    'viewname' => 'Спецификация (не используй здесь)', ],
            );
        
        if ($target_type == '') {
            $tmpl->ajax = 1;
            
            $base_link = "window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=";
            foreach($morphs as $id => $line) {
                if(\acl::testAccess($line['acl_object'], \acl::CREATE)) {
                    $tmpl->addContent("<div onclick=\"{$base_link}{$id}'\">{$line['viewname']}</div>");
                }
            }
        } else if ($target_type == 't2') {
            \acl::accessGuard($morphs[$target_type]['acl_object'], \acl::CREATE);
            $new_doc = new doc_Realizaciya();
            $dd = $new_doc->createFromP($this);
            $new_doc->setDopData('cena', $this->dop_data['cena']);
            $new_doc->setDopData('platelshik', $this->doc_data['agent']);
            $new_doc->setDopData('gruzop', $this->doc_data['agent']);
            $new_doc->setDopData('ishop', $this->dop_data['ishop']);
            $new_doc->setDopData('received', 0);
            $this->sentZEvent('morph_realizaciya');
            header("Location: doc.php?mode=body&doc=$dd");
        } else if ($target_type == 1) {
            \acl::accessGuard($morphs[$target_type]['acl_object'], \acl::CREATE);
            $new_doc = new doc_Zayavka();
            $dd = $new_doc->createFromP($this);
            $new_doc->setDopData('cena', $this->dop_data['cena']);
            header("Location: doc.php?mode=body&doc=$dd");
        }
        else if ($target_type == 'd2') {
            \acl::accessGuard($morphs[$target_type]['acl_object'], \acl::CREATE);
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
        // Оперативная реализация
        else if ($target_type == 15) {
            \acl::accessGuard($morphs[$target_type]['acl_object'], \acl::CREATE);
            $new_doc = new doc_Realiz_op();
            $dd = $new_doc->createFromP($this);
            $new_doc->setDopData('ishop', $this->dop_data['ishop']);
            $this->sentZEvent('morph_oprealizaciya');
            header("Location: doc.php?mode=body&doc=$dd");
        }
        else if ($target_type == 16) {
            \acl::accessGuard($morphs[$target_type]['acl_object'], \acl::CREATE);
            $new_doc = new doc_Specific();
            $dd = $new_doc->createFromP($this);
            $new_doc->setDopData('ishop', $this->dop_data['ishop']);
            header("Location: doc.php?mode=body&doc=$dd");
        }
        else if ($target_type == 6) {
            \acl::accessGuard($morphs[$target_type]['acl_object'], \acl::CREATE);
            $this->sentZEvent('morph_pko');
            $sum = $this->recalcSum();
            $db->startTransaction();
            $base = $this->Otgruzka();
            if (!$base) {
                $db->rollback();
                $tmpl->msg("Не удалось создать подчинённый документ!", "err");
            } else {
                $new_doc = new doc_Pko();
                $doc_data = $this->doc_data;
                $doc_data['p_doc'] = $base;
                $dd = $new_doc->create($doc_data);

                $new_doc->setDocData('kassa', 1);
                $db->commit();
                $ref = "Location: doc.php?mode=body&doc=" . $dd;
                header($ref);
            }
        } else if ($target_type == 4) {
            \acl::accessGuard($morphs[$target_type]['acl_object'], \acl::CREATE);
            $this->sentZEvent('morph_pbank');
            $sum = $this->recalcSum();
            $db->startTransaction();
            $base = $this->Otgruzka();
            if (!$base) {
                $db->rollback();
                throw new Exception("Не удалось создать подчинённый документ!");
            } else {
                $new_doc = new doc_PBank();
                $doc_data = $this->doc_data;
                $doc_data['p_doc'] = $base;
                $dd = $new_doc->create($doc_data);

                $db->commit();
                $ref = "Location: doc.php?mode=body&doc=" . $dd;
                header($ref);
            }
        } else {
            throw new \NotFoundException();
        }
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
