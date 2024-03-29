<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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
            'pay_type' => '', 'cena' => '', 'worker_id'=>0);
        parent::__construct($doc);
        $this->doc_type = 3;
        $this->typename = 'zayavka';
        $this->viewname = 'Заявка покупателя';
        $this->sklad_editor_enable = true;
        $this->header_fields = 'bank sklad separator agent cena';
        settype($this->id, 'int');        
    }
    
    public function getExtControls() {
        return $this->ext_controls = array(
            'ishop' => [
                'type' => 'label_flag',
                'label' => 'Заявка интернет-магазина',
            ],
            'buyer_info' => [
                'type' => 'buyer_info',
            ],
            'delivery_info' => [
                'type' => 'delivery_info',
            ],         
            'pay_type' => [
                'type' => 'select',               
                'label' => 'Способ оплаты',
                'data_source' => 'paytype.listnames',
            ],
            'worker_id' => [
                'type' => 'select',               
                'label' => 'Сотрудник',
                'data_source' => 'worker.listnames',
            ],
        );
    }

    /// Получить строку с HTML кодом дополнительных кнопок документа
    protected function getAdditionalButtonsHTML() {
        global $CONFIG;
        $ret = "<a href='#' onclick=\"msgMenu(event, '{$this->id}')\" title='Отправить сообщение покупателю'><img src='/img/i_mailsend.png' alt='msg'></a>";        
        if (@$CONFIG['doc']['pie'] && !@$this->dop_data['pie']) {
            $ret.="<a href='#' onclick=\"sendPie(event, '{$this->id}')\" title='Отправить благодарность покупателю'><img src='/img/i_pie.png' alt='pie'></a>";
        }
        $r_lock = '';
        if($this->getDopData('reserved')) {
            $r_action = "Снять";
        }
        else {
            $r_action = "Разрешить";
            $r_lock = 'un';
        }
        $ret.="<a href='#' onclick=\"toggleReserve(event, '{$this->id}')\" title='{$r_action} резервы'><img src='/img/22x22/object-{$r_lock}locked.png' alt='{$r_action} резервы'></a>";
        return $ret;
    }
	
    /// Функция обработки событий, связанных  с заказом
    /// @param event_name Полное название события
    public function dispatchZEvent($event_name, $initator = null) {
        global $CONFIG;
        if(@$CONFIG['zstatus']['debug']) {
            doc_log("EVENT", "$event_name, cur_status:{$this->dop_data['status']}", 'doc', $this->id);
        }
        if($initator instanceof doc_Realizaciya) {
            switch ($event_name) {
                case 'pre-apply':
                case 'pre-cancel':
                    $this->unsetReserves();                    
                    break;
                case 'cancel':
                case 'apply':
                    $this->setReserves();
                    break;
            }
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
        global $db;
        if (!\cfg::get('doc', 'notify_sms')) {
            return false;
        } 
        $smsphone = '';
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
            $user_data = $db->selectRowA('users', $this->doc_data['user'], array('reg_phone'));
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
            if( \cfg::get('doc', 'notify_debug') ) {
                doc_log("NOTIFY SMS", "number:$smsphone; text:$text", 'doc', $this->id);
            } 
            return true;
        }
        return false;
    }

    /// Отправить email с заданным текстом заказчику на все доступные адреса
    /// @param text текст отправляемого сообщения
    function sendEmailNotify($text, $subject=null) {
        global $db;
        $pref = \pref::getInstance();
        if (!\cfg::get('doc', 'notify_email') ) {
            return false;
        }
        $emails = array();
        if (isset($this->dop_data['buyer_email'])) {
            if($this->dop_data['buyer_email']) {
                $emails[$this->dop_data['buyer_email']] = $this->dop_data['buyer_email'];
            }
        }
        if ($this->doc_data['agent'] > 1) { // Частному лицу не рассылаем
            $agent = new \models\agent($this->doc_data['agent']);
            $contacts = $agent->contacts;
            foreach($contacts as $line) {
                if($line['type']=='email' && $line['value']) {
                    $emails[$line['value']] = $line['value'];
                }
            }
        }
        if($this->dop_data['ishop']) {
            $user_data = $db->selectRowA('users', $this->doc_data['user'], array('reg_email'));
            if (isset($user_data['reg_email'])) {
                if($user_data['reg_email']) {
                    $emails[$user_data['reg_email']] = $user_data['reg_email'];
                }
            }
        }
        if(count($emails)>0) {
            foreach($emails as $email) {
                $user_msg = "Уважаемый клиент!\n" . $text;
                if(!$subject) {
                    $subject = "Заказ N {$this->id} на {$pref->site_name}";
                }
                mailto($email, $subject, $user_msg);
                if( \cfg::get('doc', 'notify_debug') ) {
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
        global $db;
        if (!\cfg::get('doc', 'notify_xmpp')) {
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
            $user_data = $db->selectRowA('users', $this->doc_data['user'], array('jid'));
            if (isset($user_data['jid'])) {
                $addresses[] = $user_data['jid'];
            }
        }
        if(count($addresses)>0) {
            require_once(\cfg::getroot('location').'/common/XMPPHP/XMPP.php');
            $xmppclient = new \XMPPHP\XMPP( 
                \cfg::get('xmpp', 'host'), \cfg::get('xmpp', 'port'), \cfg::get('xmpp', 'login'), \cfg::get('xmpp', 'pass'), 'MultiMag r'.MULTIMAG_REV);
            $xmppclient->connect();
            $xmppclient->processUntil('session_start');
            $xmppclient->presence();
            foreach($addresses as $addr) {                
                $xmppclient->message($addr, $text);                    
                if(\cfg::get('doc','notify_debug') ) {
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
        if (!isset($this->dop_data['delivery_date'])) {
            $this->dop_data['delivery_date'] = '';
        }

        $tmpl->addContent("<hr>");

        if (@$this->dop_data['ishop']) {
            $tmpl->addContent("<b>Заявка с интернет-витрины</b><br>");
        }
        if (@$this->dop_data['buyer_rname']) {
            $tmpl->addContent("<b>ФИО: </b>{$this->dop_data['buyer_rname']}<br>");
        }
        if (@$this->dop_data['buyer_ip']) {
            $tmpl->addContent("<b>IP адрес: </b>{$this->dop_data['buyer_ip']}<br>");
        }
        if (@$this->dop_data['pay_type']) {
            $tmpl->addContent("<b>Способ оплаты: </b>");
            $ldo = new \Models\LDO\paytypes();
            $paytypes = $ldo->getData();
            if(isset($paytypes[$this->dop_data['pay_type']])) {
                $tmpl->addContent($paytypes[$this->dop_data['pay_type']]);
            }
            else {
                $tmpl->addContent("не определён ({$this->dop_data['pay_type']})");
            }
            $tmpl->addContent("<br>");
        }
        if (!isset($this->dop_data['buyer_email'])) {
            $this->dop_data['buyer_email'] = '';
        }
        if (!isset($this->dop_data['buyer_phone'])) {
            $this->dop_data['buyer_phone'] = '';
        }
        $tmpl->addContent("e-mail, прикреплённый к заявке<br><input type='text' name='buyer_email' style='width: 100%' value='{$this->dop_data['buyer_email']}'><br>");
        $tmpl->addContent("Телефон для sms, прикреплённый к заявке<input type='text' name='buyer_phone' style='width: 100%' value='{$this->dop_data['buyer_phone']}'><br>");

        $tmpl->addContent("Доставка:<br><select name='delivery'><option value='0'>Не требуется</option>");
        $res = $db->query("SELECT `id`, `name` FROM `delivery_types` ORDER BY `id`");
        while ($nxt = $res->fetch_row()) {
            if ($nxt[0] == $this->dop_data['delivery']) {
                $tmpl->addContent("<option value='$nxt[0]' selected>" . html_out($nxt[1]) . "</option>");
            } else {
                $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
            }
        }

        $tmpl->addContent("</select>
            Регион доставки:<br><select name='delivery_region'><option value='0'>Не задан</option>");
        $res = $db->query("SELECT `id`, `name` FROM `delivery_regions` ORDER BY `id`");
        while ($nxt = $res->fetch_row()) {
            if ($nxt[0] == $this->dop_data['delivery_region']) {
                $tmpl->addContent("<option value='$nxt[0]' selected>" . html_out($nxt[1]) . "</option>");
            } else {
                $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
            }
        }

        $tmpl->addContent("</select>
            Желаемая дата доставки:<br>
            <input type='text' name='delivery_date' value='{$this->dop_data['delivery_date']}' style='width: 100%'><br>");
        if (@$this->dop_data['delivery_address']) {
            $tmpl->addContent("<b>Адрес доставки: </b>{$this->dop_data['delivery_address']}<br>");
        }

        $tmpl->addContent("<br><hr>
            Статус (может меняться автоматически):<br>
            <select name='status'>");
        if (@$this->dop_data['status'] == '') {
            $tmpl->addContent("<option value=''>Не задан</option>");
        }
        $ldo = new \Models\LDO\zstatuses();
        $status_list = $ldo->getData();
        foreach ($status_list as $id => $name) {
            $s = (@$this->dop_data['status'] == $id) ? 'selected' : '';
            $tmpl->addContent("<option value='$id' $s>$name</option>");
        }

        $tmpl->addContent("</select><br><hr>");
        
        
        $ldo = new \Models\LDO\workernames();
        $ret = \widgets::getEscapedSelect('worker_id', $ldo->getData(), $this->dop_data['worker_id'], 'не назначен');
        
        $tmpl->addContent("Сотрудник:<br>$ret");        
    }

    /// Сохранение расширенных свойств документа
    function DopSave() {
        $new_data = array(
            'status' => request('status'),
            'delivery' => rcvint('delivery'),
            'delivery_region' => rcvint('delivery_region'),
            'delivery_date' => request('delivery_date'),
            'buyer_email' => request('buyer_email'),
            'buyer_phone' => request('buyer_phone'),
            'worker_id' => request('worker_id'),
        );
        $old_data = array_intersect_key($new_data, $this->dop_data);

        if ($this->id && @$old_data['status'] != $new_data['status']) {
            $this->sentZEvent('cstatus:' . $new_data['status']);
        }
        $this->setDopDataA($new_data);
    }

    /// Выполнение дополнительных проверок доступа для проведения документа
    public function extendedApplyAclCheck() {
        $acl_obj = ['store.global', 'store.'.$this->doc_data['sklad']];      
        if (!\acl::testAccess($acl_obj, \acl::APPLY)) {
           $d_start = date_day(time());
            $d_end = $d_start + 60 * 60 * 24 - 1;
            if (!\acl::testAccess($acl_obj, \acl::TODAY_APPLY)) {
                throw new \AccessException('Не достаточно привилегий для проведения документа с выбранным складом '.$this->doc_data['sklad']);
            } elseif ($this->doc_data['date'] < $d_start || $this->doc_data['date'] > $d_end) {
                throw new \AccessException('Не достаточно привилегий для проведения документа с выбранным складом '.$this->doc_data['sklad'].' произвольной датой');
            }
        }
        parent::extendedApplyAclCheck();
    }
    
    /// Выполнение дополнительных проверок доступа для отмены документа
    public function extendedCancelAclCheck() {
        $acl_obj = ['store.global', 'store.'.$this->doc_data['sklad']];      
        if (!\acl::testAccess($acl_obj, \acl::CANCEL)) {
           $d_start = date_day(time());
            $d_end = $d_start + 60 * 60 * 24 - 1;
            if (!\acl::testAccess($acl_obj, \acl::TODAY_CANCEL)) {
                throw new \AccessException('Не достаточно привилегий для отмены проведения документа с выбранным складом '.$this->doc_data['sklad']);
            } elseif ($this->doc_data['date'] < $d_start || $this->doc_data['date'] > $d_end) {
                throw new \AccessException('Не достаточно привилегий для отмены проведения документа с выбранным складом '.$this->doc_data['sklad'].' произвольной датой');
            }
        }
        parent::extendedCancelAclCheck();
    }
        
    /// Провести документ
    /// @param silent Не менять отметку проведения
    function docApply($silent = 0) {
        if (!$silent) {
            $this->setDopData('reserved', '1');
        }        
        $this->setReserves();
        if ($silent) {
            return;
        }
        $this->fixPrice();
        if (!$this->isAltNumUnique()) {
            throw new Exception("Номер документа не уникален!");
        }
        parent::docApply($silent);
    }
           
    /// Отменить проведение документа
    function docCancel() {
        global $db;
        if (!$this->doc_data['ok']) {
            throw new Exception('Документ не проведён!');
        }        
        $db->update('doc_list', $this->id, 'ok', 0);
        $this->doc_data['ok'] = 0;
        $this->unsetReserves();
        $this->sentZEvent('cancel');
    }
    
        /// Загружает счётчики резервов для текущей заявки
    protected function getReserves() {
        global $db;
        $ret = array();
        if(!$this->getDopData('reserved')) {
            return $ret;
        }
        $res = $db->query("SELECT `doc_list_pos`.`tovar` AS `pos_id`, `doc_list_pos`.`cnt`
            FROM `doc_list_pos`
            LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
            WHERE `doc_list_pos`.`doc`='{$this->id}'");
        while($line = $res->fetch_assoc()) {
            $c_res = $db->query("SELECT SUM(`doc_list_pos`.`cnt`)"
                . " FROM `doc_list_pos`"
                . " INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`"
                . " WHERE `doc_list_pos`.`tovar` = '{$line['pos_id']}' "
                    . " AND `doc_list`.`type`=2 AND `doc_list`.`ok`>0 AND `doc_list`.`mark_del`=0 AND `doc_list`.`p_doc`='{$this->id}'"
                );
            if($c_res->num_rows) {
                list($nr_cnt) = $c_res->fetch_row();
                if(!$nr_cnt) {
                    $nr_cnt = 0;
                }
            }
            else {
                $nr_cnt = 0;
            }
            
            $reserve = $line['cnt'] - $nr_cnt;
            if($reserve>0) {
                $ret[$line['pos_id']] = $reserve;
            }
        }
        return $ret;  
    }

    protected function unsetReserves() {
        global $db;
        $reserves = $this->getReserves();
        foreach($reserves as $pos_id => $reserve) {
            $db->query("INSERT INTO `doc_base_dop` (`id`, `reserve`) VALUES ($pos_id, '$reserve')
                ON DUPLICATE KEY UPDATE `reserve`=`reserve`-VALUES(`reserve`)");
        }        
    }
    
    protected function setReserves() {
        global $db;
        $reserves = $this->getReserves();
        foreach($reserves as $pos_id => $reserve) {
            $db->query("INSERT INTO `doc_base_dop` (`id`, `reserve`) VALUES ($pos_id, '$reserve')
                ON DUPLICATE KEY UPDATE `reserve`=`reserve`+VALUES(`reserve`)");
        }        
    }  

    /**
     * Получить список документов, которые можно создать на основе этого
     * @return array Список документов
     */
    public function getMorphList() {
        $morphs = array(
            'r_all' =>      ['name'=>'r_all', 'document' => 'realizaciya', 'viewname' => 'Реализация (все товары)', ],
            'r_partial' =>  ['name'=>'r_partial', 'document' => 'realizaciya', 'viewname' => 'Реализация (неотгруженные)', ],
            'pko' =>        ['name'=>'pko',  'document' => 'pko',         'viewname' => 'Приходный кассовый ордер', ],
            'pbank' =>      ['name'=>'pbank',  'document' => 'pbank',       'viewname' => 'Приход средств в банк', ],
            'realiz_op' =>  ['name'=>'realiz_op', 'document' => 'realiz_op',   'viewname' => 'Оперативная реализация', ],
            'zayavka' =>    ['name'=>'zayavka',  'document' => 'zayavka',     'viewname' => 'Копия заявки', ],
            'specific' =>   ['name'=>'specific', 'document' => 'specific',    'viewname' => 'Спецификация (не используй здесь)', ],
        );
        return $morphs;
    }

    /** Сформировать реализацию со всеми товарами на основе этого документа
     * 
     * @return \doc_Realizaciya
     */
    protected function morphTo_r_all() {        
        $new_doc = new \doc_Realizaciya();
        $new_doc->createFromP($this);
        $data = [
            'cena' => $this->dop_data['cena'],
            'platelshik' => $this->doc_data['agent'],
            'gruzop' => $this->doc_data['agent'],
            'ishop' => $this->dop_data['ishop'],
            'received' => 0,
        ];
        $new_doc->setDopDataA($data);
        $this->sentZEvent('morph_realizaciya');
        return $new_doc;
    }
    
    /** Сформировать реализацию с неотгруженными товарами на основе этого документа
     * 
     * @return \doc_Realizaciya
     */
    protected function morphTo_r_partial() {
        $new_doc = new \doc_Realizaciya();
        $new_doc->createFromPDiff($this);
        $data = [
            'cena' => $this->dop_data['cena'],
            'platelshik' => $this->doc_data['agent'],
            'gruzop' => $this->doc_data['agent'],
            'ishop' => $this->dop_data['ishop'],
            'received' => 0,
        ];
        $new_doc->setDopDataA($data);
        $this->sentZEvent('morph_realizaciya');
        return $new_doc;
    }
    
    /**
     * Сформировать приходный кассовый ордер
     */
    protected function morphTo_pko() {
        global $db;        
        $this->recalcSum();
        $base = $this->Otgruzka();
        if (!$base) {
            throw new \Exception("Не удалось создать подчинённый документ");
        }
        $new_doc = new doc_Pko();
        $doc_data = $this->doc_data;
        $doc_data['p_doc'] = $base;
        $new_doc->create($doc_data);
        $new_doc->setDocData('kassa', 1);
        $this->sentZEvent('morph_pko');
        return $new_doc;        
    }
    
    protected function morphTo_pbank() {
        global $db;        
        $this->recalcSum();
        $base = $this->Otgruzka();
        if (!$base) {
            throw new Exception("Не удалось создать подчинённый документ!");
        }        
        $new_doc = new doc_PBank();
        $doc_data = $this->doc_data;
        $doc_data['p_doc'] = $base;
        $new_doc->create($doc_data);
        $this->sentZEvent('morph_pbank');
        return $new_doc;        
    }

    protected function morphTo_realiz_op() {
        $new_doc = new doc_Realiz_op();
        $new_doc->createFromP($this);
        $new_doc->setDopData('ishop', $this->dop_data['ishop']);
        $this->sentZEvent('morph_oprealizaciya');
        return $new_doc;
    }
    
    protected function morphTo_zayavka() {
        $new_doc = new doc_zayavka();
        $new_doc->createFromP($this);
        $new_doc->setDopData('cena', $this->dop_data['cena']);
        return $new_doc;
    }
    
    protected function morphTo_specific() {
        $new_doc = new doc_Specific();
        $new_doc->createFromP($this);
        $new_doc->setDopData('cena', $this->dop_data['cena']);
        return $new_doc;
    } 
    
    protected function sendNotificationMessage() {
        global $tmpl;
        try {
            \acl::accessGuard('doc.' . $this->typename, \acl::UPDATE);
            $text = request('text');
            $send = false;
            if (request('sms')) {
                $send |= $this->sendSMSNotify($text);
            }
            if (request('mail')) {
                $send |= $this->sendEmailNotify($text);
               }
            if(!$send) {
                throw new Exception('Не удалось отправить сообщение.');
            }
            $tmpl->setContent("{\"object\":\"send_message\",\"response\":\"success\"}");
        } catch (Exception $e) {
            $ret_data = array(
                'object' => 'send_message',
                'response' => 'error',
                'errorcode' => $e->getCode(),
                'errormessage' => $e->getMessage()
            );
            $tmpl->setContent( json_encode($ret_data, JSON_UNESCAPED_UNICODE) );
        }
    }
    
    protected function sendPie() {
        global $tmpl;
        try {
            \acl::accessGuard('doc.' . $this->typename, \acl::UPDATE);
            $this->sendEmailNotify(\cfg::get('doc', 'pie'));
            $this->setDopData('pie', 1);
            $tmpl->setContent("{\"object\":\"send_pie\",\"response\":\"success\"}");
        } catch (Exception $e) {
            $ret_data = array(
                'object' => 'send_pie',
                'response' => 'error',
                'errorcode' => $e->getCode(),
                'errormessage' => $e->getMessage()
            );
            $tmpl->setContent(json_encode($ret_data, JSON_UNESCAPED_UNICODE));
        }
    }
    
    protected function rewriteposList() {
        global $db;
        \acl::accessGuard('doc.' . $this->typename, \acl::UPDATE);
        $db->startTransaction();
        $db->query("DELETE FROM `doc_list_pos` WHERE `doc`='{$this->id}'");
        $res = $db->query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$this->id}'");
        $docs = "`doc`='-1'";
        while ($nxt = $res->fetch_row()) {
            $docs.=" OR `doc`='$nxt[0]'";
        }
        $res = $db->query("SELECT `doc`, `tovar`, SUM(`cnt`) AS `cnt`, `gtd`, `comm`, `cost`, `page` FROM `doc_list_pos` WHERE $docs GROUP BY `tovar`");
        while ($line = $res->fetch_assoc()) {
            $line['doc'] = $this->id;
            $db->insertA('doc_list_pos', $line);
        }
        doc_log("REWRITE", "", 'doc', $this->id);
        $db->commit();
        header("location: /doc.php?mode=body&doc=" . $this->id);
    }
    
    protected function toggleReserve() {
        global $tmpl, $db;
        try {
            \acl::accessGuard('doc.' . $this->typename, \acl::UPDATE);
            $db->startTransaction();
            $new_res = $this->getDopData('reserved', 0)?0:1;
            if($this->doc_data['ok']) {
                $this->unsetReserves();
            }
            $this->setDopData('reserved', $new_res);
            if($this->doc_data['ok']) {
                $this->setReserves();
            }
            $db->commit();
            $state = $new_res?'разрешены':'сняты';
            $tmpl->setContent("{\"object\":\"togglereserve\",\"response\":\"success\",\"reserved\":\"$new_res\"}");
            $ret_data = array(
                'object' => 'togglereserve',
                'response' => 'success',
                'reserved' => $new_res,
                'message' => 'Резервы '.$state
            );
            $tmpl->setContent(json_encode($ret_data, JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            $ret_data = array(
                'object' => 'togglereserve',
                'response' => 'error',
                'errorcode' => $e->getCode(),
                'errormessage' => $e->getMessage()
            );
            $tmpl->setContent(json_encode($ret_data, JSON_UNESCAPED_UNICODE));
        }        
    }
    function Service() {
        global $tmpl, $CONFIG, $db;
        $tmpl->ajax = 1;
        $opt = request('opt');
        $pos = rcvint('pos');
        
        switch($opt) {
            case 'pmsg':
                $this->sendNotificationMessage();
                break;
            case 'rewrite':
                $this->rewriteposList();
                break;
            case 'pie':
                $this->sendPie();
                break;
            case 'togglereserve':
                $this->toggleReserve();
                break;
            default:
                parent::_Service($opt, $pos);
        }
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
