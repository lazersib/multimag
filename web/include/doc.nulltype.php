<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
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

include_once($CONFIG['site']['location'] . "/include/doc.core.php");

/// Базовый класс для всех документов системы. Содержит основные методы для работы с документами.
class doc_Nulltype extends \document {

    protected $doc_type;   ///< ID типа документа	
    protected $sklad_editor_enable;  ///< Разрешить отображение редактора склада
    // Значение следующих полей: +1 - увеличивает, -1 - уменьшает, 0 - не влияет
    // Документы перемещений должны иметь 0 в соответствующих полях !
    protected $bank_modify;   ///< Изменяет ли общие средства в банке
    protected $kassa_modify;  ///< Изменяет ли общие средства в кассе
    protected $header_fields;  ///< Поля заголовка документа, доступные через форму редактирования
    protected $doc_data;   ///< Основные данные документа
    protected $dop_data;   ///< Дополнительные данные документа
    protected $firm_vars;   ///< Информация с данными о фирме
    protected $child_docs = array();        ///< Информация о документах-потомках
    protected $allow_neg_cnt;   ///< Разрешить отрицательное количество товара

    public function __construct($doc = 0) {
        $this->id = (int) $doc;
        $this->doc_type = 0;
        $this->typename = '';
        $this->viewname = 'Неопределенный документ';
        $this->sklad_editor_enable = false;
        $this->bank_modify = 0;
        $this->kassa_modify = 0;
        $this->header_fields = '';
        $this->get_docdata();
    }

    public function getDocDataA() {
        return $this->doc_data;
    }

    public function getDopDataA() {
        return $this->dop_data;
    }

//< Получить все дополнительные параметры документа в виде ассоциативного массива

    public function getFirmVarsA() {
        return $this->firm_vars;
    }

    /// Шаблон метода для инициализации дополнительных данных документа
    protected function initDefDopData() {
        
    }

    /// @brief Получить значение основного параметра документа.
    /// Вернёт пустую строку в случае отсутствия параметра
    /// @param name Имя параметра
    public function getDocData($name) {
        if (isset($this->doc_data[$name])) {
            return $this->doc_data[$name];
        } else {
            return '';
        }
    }
    
    protected function writeLogArray($motion, $array) {
        doc_log($motion, json_encode($array, JSON_UNESCAPED_UNICODE), 'doc', $this->id);
    }

    /// Установить основной параметр документа
    public function setDocData($name, $value) {
        global $db;
        if(!isset($this->doc_data[$name])) {
            $this->doc_data[$name] = null;
        }
        if ($this->id && $this->doc_data[$name] !== $value) {
            $_name = $db->real_escape_string($name);
            $db->update('doc_list', $this->id, $_name, $value);
            $log_data = [$name => ['old'=>$this->doc_data[$name], 'new'=>$value] ];
            $this->writeLogArray("UPDATE", $log_data);
        }
        $this->doc_data[$name] = $value;
    }

    /// @brief Получить значение дополниетльного параметра документа.
    /// Вернёт $default в случае отсутствия параметра
    /// @param $name Имя параметра
    /// @param $default Значение по умолчанию
    public function getDopData($name, $default = '') {
        if (isset($this->dop_data[$name])) {
            return $this->dop_data[$name];
        } else {
            return $default;
        }
    }

    /// Установить дополнительные данные текущего документа
    public function setDopData($name, $value) {
        global $db;
        if(!isset($this->dop_data[$name])) {
            $this->dop_data[$name] = null;
        }
        if ($this->id && $this->dop_data[$name] !== $value) {
            $_name = $db->real_escape_string($name);
            $_value = $db->real_escape_string($value);
            $db->query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`) VALUES ( '{$this->id}' ,'$_name','$_value')");
            $log_data = [$name => ['old'=>$this->dop_data[$name], 'new'=>$value] ];
            $this->writeLogArray("UPDATE", ['dop_data'=>$log_data]);
        }
        $this->dop_data[$name] = $value;
    }

    /// Установить дополнительные данные текущего документа
    public function setDopDataA($array) {
        global $db;
        if ($this->id) {
            $to_write_data = array_diff_assoc($array, $this->dop_data);
            $log_data = array();
            foreach ($to_write_data as $name => $value) {
                if(!isset($this->dop_data[$name])) {
                    $this->dop_data[$name] = null;
                }
                $log_data[$name] = ['old'=>$this->dop_data[$name], 'new'=>$value];
                $this->dop_data[$name] = $value;                
            }
            if(count($to_write_data)>0) {
                $db->replaceKA('doc_dopdata', 'doc', $this->id, $to_write_data);
                $this->writeLogArray("UPDATE", ['dop_data'=>$log_data]);
            }
        }
    }

    /// Зафиксировать цену документа, если она установлена в *авто*. Выполняется при проведении некоторых типов документов.
    protected function fixPrice() {
        if (!$this->dop_data['cena']) {
            $pc = PriceCalc::getInstance();
            $pc->setFirmId($this->doc_data['firm_id']);
            $pc->setOrderSum($this->doc_data['sum']);
            $pc->setAgentId($this->doc_data['agent']);
            $pc->setUserId($this->doc_data['user']);
            if (isset($this->dop_data['ishop'])) {
                $pc->setFromSiteFlag($this->dop_data['ishop']);
            }
            $price_id = $pc->getCurrentPriceID();
            $this->setDopData('cena', $price_id);
        }
    }

    /// Создать документ с заданными данными
    public function create($doc_data) {
        global $db;
        \acl::accessGuard('doc.' . $this->typename, \acl::CREATE);
        \acl::accessGuard([ 'firm.global', 'firm.' . $doc_data['firm_id'] ], \acl::CREATE);
        $date = time();
        $doc_data['altnum'] = $this->getNextAltNum($this->doc_type, $doc_data['subtype'], date("Y-m-d", $doc_data['date']), $doc_data['firm_id']);
        $doc_data['created'] = date("Y-m-d H:i:s");
        $res = $db->query("SHOW COLUMNS FROM `doc_list`");
        $col_array = array();
        while ($nxt = $res->fetch_row()) {
            $col_array[$nxt[0]] = $nxt[0];
        }
        // Эти поля копировать не нужно
        unset($col_array['id'], $col_array['date'], $col_array['type'], $col_array['user'], $col_array['ok']);

        $data = array_intersect_key($doc_data, $col_array);
        $data['date'] = $date;
        $data['type'] = $this->doc_type;
        $data['user'] = $_SESSION['uid'];

        $this->id = $db->insertA('doc_list', $data);
        $this->writeLogArray("CREATE", $data);
        
        unset($this->doc_data);
        unset($this->dop_data);
        $this->get_docdata();
        return $this->id;
    }

    public function getRootDocumentId() {

        function getRootDocument($doc) {
            global $db;
            while ($doc) {
                $res = $db->query("SELECT `p_doc` FROM `doc_list` WHERE `id`='$doc' AND `p_doc`>'0' AND `p_doc` IS NOT NULL");
                if (!$res->num_rows) {
                    return $doc;
                }
                list($pdoc) = $res->fetch_row();
                if (!$pdoc) {
                    return $doc;
                }
                $doc = $pdoc;
            }
            return $doc;
        }

        if ($this->doc_data['p_doc'] == 0) {
            return $this->id;
        }
        return getRootDocument($this->doc_data['p_doc']);
    }

    public function getSubtreeDocuments($doc) {
        global $db;
        settype($doc, 'int');
        $ret = array();
        $sql = "SELECT `doc_list`.`id`, `doc_list`.`ok`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`sum`, `doc_types`.`name`,
            `doc_list`.`type`, `doc_list`.`firm_id`
            , `doc_agent`.`name` AS `agent_name`
	FROM `doc_list`
	LEFT JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
	LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
	WHERE `doc_list`.`p_doc`='$doc'
	ORDER by `doc_list`.`date` DESC";
        $res = $db->query($sql);
        $i = 1;
        while ($line = $res->fetch_assoc()) {
            $line['date'] = date("Y.m.d H:i:s", $line['date']);
            $line['childs'] = $this->getSubtreeDocuments($line['id']);
            $ret[] = $line;
        }
        return $ret;
    }

    protected function getDocumentSubtreeElementHTML($item, $last = true) {
        $ret = '';
                
        $ok_status = $item['ok'] ? 'Проведённый' : 'Непроведённый';
        $r = ($last) ? " IsLast" : '';
        $ret .= "<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>";
        if (!\acl::testAccess([ 'firm.global', 'firm.' . $item['firm_id'] ], \acl::VIEW) && $item['firm_id']>0) {
            if ($item['id'] == $this->id) {
                $ret .= "<b>";
            }
            $ret .= "Неизвестный документ N {$item['altnum']}{$item['subtype']} от {$item['date']}."
                    . " Агент: {$item['agent_name']}";
            if ($item['id'] == $this->id) {
                $ret .= "</b>";
            }
        } else {
            if ($item['id'] == $this->id) {
                $ret .= "<b>";
            }
            $ret .= "<a href='doc.php?mode=body&doc={$item['id']}'>$ok_status {$item['name']}</a> N {$item['altnum']}{$item['subtype']} от {$item['date']}."
                    . " Агент: {$item['agent_name']}, на сумму {$item['sum']}";
            if ($item['id'] == $this->id) {
                $ret .= "</b>";
            }
        }
        $ret .= "</li>";
        return $ret;
    }

    protected function getDocumentSubtreeHTML($tree) {
        $ret = '';
        $cnt = count($tree);
        foreach ($tree as $i => $item) {
            $ret .= $this->getDocumentSubtreeElementHTML($item, $i >= $cnt);
            $ret .= "<ul class='Container'>";
            $ret .= $this->getDocumentSubtreeHTML($item['childs']);
            $ret .= "</ul></div></li>";
        }
        return $ret;
    }

    public function viewDocumentTree() {
        global $tmpl;
        \acl::accessGuard('doc.' . $this->typename, \acl::VIEW);
        if($this->doc_data['firm_id']>0) {
            \acl::accessGuard([ 'firm.global', 'firm.' . $this->doc_data['firm_id'] ], \acl::VIEW);
        }
        $root_doc_id = $this->getRootDocumentId();
        $tmpl->addContent("<h1>Структура для {$this->id} с $root_doc_id </h1>");
        $root_doc = \document::getInstanceFromDb($root_doc_id);
        $item = $root_doc->getDocDataA();
        $item['name'] = $root_doc->getViewName();
        $tree = $this->getSubtreeDocuments($root_doc_id);
        $tmpl->addContent("<ul class='Container'>");
        $tmpl->addContent($this->getDocumentSubtreeElementHTML($item));
        $tmpl->addContent("<ul class='Container'>");
        $tmpl->addContent($this->getDocumentSubtreeHTML($tree));
        $tmpl->addContent("</ul>");
        $tmpl->addContent("</ul>");
    }

    /// Создать документ на основе данных другого документа
    public function createFrom($doc_obj) {
        $doc_data = $doc_obj->doc_data;
        $doc_data['p_doc'] = $doc_obj->id;
        $this->create($doc_data);

        return $this->id;
    }

    /// Создать документ с товарными остатками на основе другого документа
    public function createFromP($doc_obj) {
        global $db;
        $doc_data = $doc_obj->doc_data;
        $doc_data['p_doc'] = $doc_obj->id;
        $this->create($doc_data);
        if ($this->sklad_editor_enable) {
            $res = $db->query("SELECT `tovar`, `cnt`, `cost`, `page`, `comm` FROM `doc_list_pos` WHERE `doc`='{$doc_obj->id}' ORDER BY `doc_list_pos`.`id`");
            while ($line = $res->fetch_assoc()) {
                $line['doc'] = $this->id;
                unset($line['id']);
                $db->insertA('doc_list_pos', $line);
            }
        }
        return $this->id;
    }

    /// Создать несвязанный документ с товарными остатками из другого документа
    public function createParent($doc_obj) {
        global $db;
        $doc_data = $doc_obj->doc_data;
        $doc_data['p_doc'] = 0;
        $this->create($doc_data);
        if ($this->sklad_editor_enable) {
            $res = $db->query("SELECT `tovar`, `cnt`, `cost`, `page`, `comm` FROM `doc_list_pos` WHERE `doc`='{$doc_obj->id}' ORDER BY `doc_list_pos`.`id`");
            while ($line = $res->fetch_assoc()) {
                $line['doc'] = $this->id;
                unset($line['id']);
                $db->insertA('doc_list_pos', $line);
            }
        }
        unset($this->doc_data);
        $this->get_docdata();
        return $this->id;
    }

    /// Создать документ с товарными остатками на основе другого документа
    /// В новый документ войдут только те наименования, которых нет в других подчинённых документах
    public function createFromPDiff($doc_obj) {
        global $db;
        $doc_data = $doc_obj->doc_data;
        $doc_data['p_doc'] = $doc_obj->id;
        if ($this->sklad_editor_enable) {
            $res = $db->query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$doc_obj->id}' AND `type`='{$this->doc_type}'");
            $child_count = $res->num_rows;
        }
        $this->create($doc_data);
        if ($this->sklad_editor_enable) {
            if ($child_count < 1) {
                $res = $db->query("SELECT `tovar`, `cnt`, `cost`, `page`, `comm` FROM `doc_list_pos` WHERE `doc`='{$doc_obj->id}' ORDER BY `doc_list_pos`.`id`");
                while ($line = $res->fetch_assoc()) {
                    $line['doc'] = $this->id;
                    unset($line['id']);
                    $db->insertA('doc_list_pos', $line);
                }
            } else {
                $res = $db->query("SELECT `a`.`tovar`, `a`.`cnt`, `a`.`comm`, `a`.`cost`,
				( SELECT SUM(`b`.`cnt`) FROM `doc_list_pos` AS `b`
				INNER JOIN `doc_list` ON `b`.`doc`=`doc_list`.`id` AND `doc_list`.`p_doc`='{$doc_obj->id}' AND `doc_list`.`mark_del`='0'
				WHERE `b`.`tovar`=`a`.`tovar` ) AS `doc_cnt`, `a`.`page`
				FROM `doc_list_pos` AS `a`
				WHERE `a`.`doc`='{$doc_obj->id}'
				ORDER BY `a`.`id`");
                while ($line = $res->fetch_assoc()) {
                    if ($line['doc_cnt'] < $line['cnt']) {
                        $line['cnt']-=$line['doc_cnt'];
                        unset($line['doc_cnt']);
                        $line['doc'] = $this->id;
                        unset($line['id']);
                        $db->insertA('doc_list_pos', $line);
                    }
                }
            }
            $this->recalcSum();
        }
        return $this->id;
    }

    /// Пересчитать и вернуть сумму документа, исходя из товаров в нём. Работает только для документов, в которых могут быть товары.
    /// Для безтоварных документов просто вернёт сумму.
    /// TODO: функция устарела. Перейти на использование DocPosEditor::updateDocSum()
    public function recalcSum() {
        global $db;
        if (!$this->id)
            return 0;
        if (!$this->sklad_editor_enable)
            return $this->doc_data['sum'];
        $old_sum = $this->doc_data['sum'];
        $sum = 0;
        $res = $db->query("SELECT `cnt`, `cost` FROM `doc_list_pos` WHERE `doc`='{$this->id}' AND `page`='0'");
        while ($nxt = $res->fetch_row())
            $sum+=$nxt[0] * $nxt[1];
        $res->free();
        if (round($sum, 2) != round($old_sum, 2))
            $this->setDocData('sum', $sum);
        return $sum;
    }

    /// Получить объект документа заявки для текущей цепочки документов
    /// @return Объект doc_Zayavka, или false если не найден. Может быть текущим документом.
    public function getZDoc() {
        global $db;
        if($this->doc_type == 3) {
            return $this;
        }
        $pdoc = $this->doc_data['p_doc'];
        while ($pdoc) {
            $res = $db->query("SELECT `id`, `type`, `p_doc` FROM `doc_list` WHERE `id`='$pdoc'");
            if (!$res->num_rows) {
                throw new Exception("Документ не найден");
            }
            list($doc_id, $pdoc_type, $pdoc_id) = $res->fetch_row();
            if ($pdoc_type == 3) {
                return new doc_Zayavka($doc_id);
            }
            $pdoc = $pdoc_id;
        }
        return false;
    }
    
    /// Послать в связанный заказ событие с заданным типом.
    /// Полное название события будет doc:{$docname}:{$event_type}
    /// @param event_type Название события
    /// TODO: зависимость от дочернего класса выглядит некорректной
    public function sentZEvent($event_type) {
        global $db;
        $event_name = "doc:{$this->typename}:$event_type";
        $zdoc = $this->getZDoc();
        if($zdoc) {
            return $zdoc->dispatchZEvent($event_name, $this);
        }
        return false;
    }
    
    /// Отправить оповещение по всем доступным каналам связи с клиентом
    function sendNotify($text) {
        return 
            $this->sendEmailNotify($text) ||
            $this->sendSMSNotify($text) || 
            $this->sendXMPPNotify($text);
    }
                    
    /// Отправить SMS с заданным текстом заказчику на первый из подходящих номеров
    /// @param text текст отправляемого сообщения
    function sendSMSNotify($text) {
        global $CONFIG, $db;
        if (!isset($CONFIG['doc']['notify_sms'])) {
            return false;
        }
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
        if (preg_match('/^\+79\d{9}$/', $smsphone)) {
            require_once('include/sendsms.php');
            $sender = new SMSSender();
            $sender->setNumber($smsphone);
            $sender->setContent($text);
            $sender->send();
            if(@$CONFIG['doc']['notify_debug']) {
                $this->writeLogArray("NOTIFY SMS", ['number'=>$smsphone,'text'=>$text]);
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
        if (!isset($CONFIG['doc']['notify_email'])) {
            return false;
        }
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
        if(count($emails)>0) {
            foreach($emails as $email) {
                $user_msg = "Уважаемый клиент!\n" . $text;
                if(!$subject) {
                    $subject = "Документ N {$this->id} на {$pref->site_name}";
                }
                mailto($email, $subject, $user_msg);
                if(@$CONFIG['doc']['notify_debug']) {
                    $this->writeLogArray("NOTIFY Email", ['email'=>$email,'text'=>$user_msg]);
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
        if (!isset($CONFIG['doc']['notify_xmpp'])) {
            return false;
        }
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
        if(count($addresses)>0) {
            require_once($CONFIG['location'].'/common/XMPPHP/XMPP.php');
            $xmppclient = new XMPPHP_XMPP( $CONFIG['xmpp']['host'], $CONFIG['xmpp']['port'], $CONFIG['xmpp']['login'], $CONFIG['xmpp']['pass'], 'MultiMag r'.MULTIMAG_REV);
            $xmppclient->connect();
            $xmppclient->processUntil('session_start');
            $xmppclient->presence();
            foreach($addresses as $addr) {
                $user_msg = $text;                    
                $xmppclient->message($addr, $user_msg);                    
                if(@$CONFIG['doc']['notify_debug']) {
                    $this->writeLogArray("NOTIFY xmpp", ['jid'=>$addr, 'text'=>$user_msg]);
                }
            }
            $xmppclient->disconnect();
            return true;
        }
        return false;
    }
    
    
    /// отобразить заголовок документа
    public function head() {
        global $tmpl;
        if ($this->doc_type == 0)
            throw new Exception("Невозможно создать документ без типа!");
        else {
            $tmpl->setTitle($this->viewname . ' N' . $this->id);
            if ($this->typename)
                $object = 'doc_' . $this->typename;
            else
                $object = 'doc';
            \acl::accessGuard('doc.' . $this->typename, \acl::VIEW);
            if($this->doc_data['firm_id']>0) {
                \acl::accessGuard([ 'firm.global', 'firm.' . $this->doc_data['firm_id'] ], \acl::VIEW);
            }
            doc_menu($this->getDopButtons());
            $this->drawHeadformStart();
            $fields = explode(' ', $this->header_fields);
            foreach ($fields as $f) {
                switch ($f) {
                    case 'agent': $this->DrawAgentField();
                        break;
                    case 'sklad': $this->DrawSkladField();
                        break;
                    case 'kassa': $this->drawKassaField();
                        break;
                    case 'bank': $this->drawBankField();
                        break;
                    case 'cena': $this->drawPriceField();
                        break;
                    case 'sum': $this->drawSumField();
                        break;
                    case 'separator': $tmpl->addContent("<hr>");
                        break;
                }
            }
            if (method_exists($this, 'DopHead'))
                $this->DopHead();

            $this->DrawHeadformEnd();
        }
    }
    
    protected function setDocDataA($data) {
        global $db;
        $log_data = array();
        $res = $db->query("SHOW COLUMNS FROM `doc_list`");
        $col_array = array();
        while ($nxt = $res->fetch_row()) {
            $col_array[$nxt[0]] = $nxt[0];
        }
        unset($col_array['id']);
        $i_data = array_intersect_key($this->doc_data, $col_array);
        
        if($this->id) {
            $to_write_data = array_diff_assoc($data, $i_data);        
            foreach($to_write_data as $name=>$value) {
                if(!isset($this->doc_data[$name])) {
                    $log_data[$name] = ['new'=>$value];
                }
                else if($this->doc_data[$name]!==$value){
                    $log_data[$name] = ['old'=>$this->doc_data[$name], 'new'=>$value];
                }
            }
            if(count($to_write_data)>0) {
                $db->updateA('doc_list', $this->id, $to_write_data);   
                $this->writeLogArray('UPDATE', $log_data);
            }
        }
        else {
            $to_write_data = array_intersect_key($data, $i_data); 
            $this->id = $db->insertA('doc_list', $to_write_data);
            $this->writeLogArray("CREATE", $to_write_data);
        }
        foreach($to_write_data as $name=>$value) {
             $this->doc_data[$name] = $value;
        }
        return $this->id;
    }
    
    protected function try_head_save() {
        $write_doc_data = array(
            'date' => @strtotime(request('datetime')),
            'firm_id' => rcvint('firm'),
            'comment' => request('comment'),
            'altnum' => rcvint('altnum'),
            'subtype' => request('subtype'),
        );
        $write_dop_data = array();
        if (!$write_doc_data['altnum']) {
            $write_doc_data['altnum'] = $this->getNextAltNum($this->doc_type, $write_doc_data['subtype']
                , date("Y-m-d", $write_doc_data['date']), $write_doc_data['firm_id']);
        }
        if(!$this->id) {
            $write_doc_data['user'] = intval($_SESSION['uid']);
            $write_doc_data['type'] = $this->doc_type; 
        }
        elseif (@$this->doc_data['ok']) {
            throw new \Exception("Операция не допускается для проведённого документа!");
        } 
        else if (@$this->doc_data['mark_del']) {
            throw new \Exception("Операция не допускается для документа, отмеченного для удаления!");
        } 
        $fields = explode(' ', $this->header_fields);
        foreach ($fields as $f) {
            switch($f) {
                case 'cena':
                case 'price':
                    $write_dop_data['cena'] = rcvint('cena');
                    $write_doc_data['nds'] = rcvint('nds');
                    break;
                case 'agent':
                    $write_doc_data['agent'] = rcvint('agent');
                    if(!$write_doc_data['agent']) {
                        $pref = \pref::getInstance();
                        $write_doc_data['agent'] = $pref->getSitePref('default_agent_id');
                    }
                    $write_dop_data['contract'] = rcvint('contract');
                    break;
                case 'separator':
                    break;
                case 'sum':
                    $write_doc_data['sum'] = rcvrounded('sum');
                    break;
                default:
                    $write_doc_data[$f] = rcvint($f);
                    break;
                    
            }
        }
        if ($this->id) {
            \acl::accessGuard('doc.' . $this->typename, \acl::UPDATE);
            \acl::accessGuard([ 'firm.global', 'firm.' . $this->doc_data['firm_id']], \acl::UPDATE);            
        }
        else {
            \acl::accessGuard('doc.' . $this->typename, \acl::CREATE);
            if ($this->doc_data['firm_id'] > 0) {
                \acl::accessGuard([ 'firm.global', 'firm.' . $this->doc_data['firm_id']], \acl::CREATE);
            }            
        }
        $this->setDocDataA($write_doc_data);
        if(count($write_dop_data)) {
            $this->setDopDataA($write_dop_data);
        }
        if (method_exists($this, 'DopSave')) {
            $this->DopSave();
        }
    }

    /// Применить изменения редактирования заголовка
    public function head_submit() {
        $this->try_head_save();
        redirect("/doc.php?mode=body&doc={$this->id}");
        return $this->id;
    }

    /// Сохранение заголовка документа и возврат результата в json формате
    public function json_head_submit() {
        global $tmpl;
        $tmpl->ajax = 1;
        try {
            $this->try_head_save();
            if ($this->doc_data['agent']) {
                $b = agentCalcDebt($this->doc_data['agent']);
            } else {
                $b = 0;
            }
            $json_content = json_encode(['response'=>'ok', 'agent_balance'=>$b], JSON_UNESCAPED_UNICODE);
            $tmpl->setContent($json_content);
            
        } 
        catch (mysqli_sql_exception $e) {
            $id = writeLogException($e);
            $ret_data = array('response' => 'err',
                    'text' => "Ошибка в базе данных! Порядковый номер ошибки: $id. Сообщение передано администратору.");
            $tmpl->setContent(json_encode($ret_data, JSON_UNESCAPED_UNICODE));
        } 
        catch (Exception $e) {
            $json_content = json_encode(['response'=>'err', 'text'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
            $tmpl->setContent($json_content);
        }
    }

    /// Редактирование тела докумнета
    public function body() {
        global $tmpl, $db;

        \acl::accessGuard('doc.' . $this->typename, \acl::VIEW);
        if($this->doc_data['firm_id']>0) {
            \acl::accessGuard([ 'firm.global', 'firm.' . $this->doc_data['firm_id']], \acl::VIEW);
        }
        $tmpl->setTitle($this->viewname . ' N' . $this->id);
        $dt = date("Y-m-d H:i:s", $this->doc_data['date']);
        doc_menu($this->getDopButtons());
        $tmpl->addContent("<div id='doc_container'>
		<div id='doc_left_block'>");
        $tmpl->addContent("<h1>{$this->viewname} N{$this->id}</h1>");

        $this->drawLHeadformStart();
        $fields = explode(' ', $this->header_fields);
        foreach ($fields as $f) {
            switch ($f) {
                case 'agent': $this->DrawAgentField();
                    break;
                case 'sklad': $this->DrawSkladField();
                    break;
                case 'kassa': $this->drawKassaField();
                    break;
                case 'bank': $this->drawBankField();
                    break;
                case 'cena': $this->drawPriceField();
                    break;
                case 'sum': $this->drawSumField();
                    break;
                case 'separator': $tmpl->addContent("<hr>");
                    break;
            }
        }
        if (method_exists($this, 'DopHead'))
            $this->DopHead();

        $this->DrawLHeadformEnd();

        $res = $db->query("SELECT `doc_list`.`id`, `doc_types`.`name`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_list`.`ok` FROM `doc_list`
		LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		WHERE `doc_list`.`id`='{$this->doc_data['p_doc']}'");
        if ($nxt = $res->fetch_row()) {
            if ($nxt[5])
                $r = 'Проведённый';
            else
                $r = 'Непроведённый';
            $dt = date("d.m.Y H:i:s", $nxt[4]);
            $tmpl->addContent("<b>Относится к:</b><br>$r <a href='?mode=body&amp;doc=$nxt[0]'>$nxt[1] N$nxt[2]$nxt[3]</a>, от $dt");
        }

        $res = $db->query("SELECT `doc_list`.`id`, `doc_types`.`name`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_list`.`ok` FROM `doc_list`
		LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		WHERE `doc_list`.`p_doc`='{$this->id}'");
        $pod = '';
        while ($nxt = $res->fetch_row()) {
            if ($nxt[5])
                $r = 'Проведённый';
            else
                $r = 'Непроведённый';
            $dt = date("d.m.Y H:i:s", $nxt[4]);
            //if($pod!='')	$pod.=', ';
            $pod.="$r <a href='?mode=body&amp;doc=$nxt[0]'>$nxt[1] N$nxt[2]$nxt[3]</a>, от $dt<br>";
        }
        if ($pod)
            $tmpl->addContent("<br><b>Зависящие документы:</b><br>$pod");
        $tmpl->addContent("<br><b>Дата создания:</b>: {$this->doc_data['created']}<br>");
        if ($this->doc_data['ok'])
            $tmpl->addContent("<b>Дата проведения:</b> " . date("Y-m-d H:i:s", $this->doc_data['ok']) . "<br>");
        $tmpl->addContent("</div>
		<script type=\"text/javascript\">
		addEventListener('load',DocHeadInit,false);
		</script>
		<div id='doc_main_block'>");
        $tmpl->addContent("<img src='/img/i_leftarrow.png' onclick='DocLeftToggle()' id='doc_left_arrow'><br>");

        if (method_exists($this, 'DopBody'))
            $this->DopBody();

        if ($this->sklad_editor_enable) {
            include_once('doc.poseditor.php');
            $poseditor = new DocPosEditor($this);
            $poseditor->cost_id = $this->dop_data['cena'];
            $poseditor->sklad_id = $this->doc_data['sklad'];
            $poseditor->SetEditable($this->doc_data['ok'] ? 0 : 1);
            $tmpl->addContent($poseditor->Show());
        }

        $tmpl->addContent("<div id='statusblock'></div><br><br></div></div>");
    }

    public function apply($silent = false) {
        global $tmpl, $db;

        $tmpl->ajax = 1;

        try {
            if ($this->doc_data['mark_del'])
                throw new Exception("Документ помечен на удаление!");
            if (!method_exists($this, 'DocApply'))
                throw new Exception("Метод проведения данного документа не определён!");
            $db->query("LOCK TABLES `doc_list` WRITE, `doc_base_cnt` WRITE, `doc_kassa` WRITE, `doc_list_pos` READ");
            $db->startTransaction();
            $this->DocApply($silent);
            $db->query("UPDATE `doc_list` SET `err_flag`='0' WHERE `id`='{$this->id}'");
        } catch (mysqli_sql_exception $e) {
            $db->rollback();
            if (!$silent) {
                $tmpl->addContent("<h3>" . $e->getMessage() . "</h3>");
                doc_log("ERROR APPLY", $e->getMessage(), 'doc', $this->id);
            }
            $db->query("UNLOCK TABLES");
            return $e->getMessage();
        } catch (Exception $e) {
            $db->rollback();
            if (!$silent) {
                $tmpl->addContent("<h3>" . $e->getMessage() . "</h3>");
                doc_log("ERROR APPLY", $e->getMessage(), 'doc', $this->id);
            }
            $db->query("UNLOCK TABLES");
            return $e->getMessage();
        }

        $db->commit();
        if (!$silent) {
            doc_log("APPLY", '', 'doc', $this->id);
            $tmpl->addContent("<h3>Докумен успешно проведён!</h3>");
        }
        $db->query("UNLOCK TABLES");
        return;
    }

    /// Выполнение дополнительных проверок доступа для проведения документа
    public function extendedApplyAclCheck() {
        return true;
    }

    /// Выполнение дополнительных проверок доступа для отмены документа
    public function extendedCancelAclCheck() {
        return true;
    }
   
    /// Провести документ и вренуть JSON результат
    public function applyJson() {
        global $db;

        try {
            $d_start = date_day(time());
            $d_end = $d_start + 60 * 60 * 24 - 1;
            if (!\acl::testAccess('doc.' . $this->typename, \acl::APPLY)) {
                if (!\acl::testAccess('doc.' . $this->typename, \acl::TODAY_APPLY)) {
                    throw new AccessException('Не достаточно привилегий для проведения документа');
                } elseif ($this->doc_data['date'] < $d_start || $this->doc_data['date'] > $d_end) {
                    throw new AccessException('Не достаточно привилегий для проведения документа произвольной датой');
                }
            }
            $this->extendedApplyAclCheck();
            if ($this->doc_data['mark_del']) {
                throw new Exception("Документ помечен на удаление!");
            }

            $res = $db->query("SELECT `recalc_active` FROM `variables`");
            if ($res->num_rows) {
                list($lock) = $res->fetch_row();
            } else {
                $lock = 0;
            }
            if ($lock) {
                throw new Exception("Идёт обслуживание базы данных. Проведение невозможно!");
            }

            if (!method_exists($this, 'DocApply')) {
                throw new Exception("Метод проведения данного документа не определён!");
            }

            $db->query("LOCK TABLES `doc_list` WRITE, `doc_base_cnt` WRITE, `doc_kassa` WRITE, `doc_list_pos` READ");
            $db->startTransaction();

            $this->DocApply(0);
            $db->query("UPDATE `doc_list` SET `err_flag`='0' WHERE `id`='{$this->id}'");
        } catch (mysqli_sql_exception $e) {
            $db->rollback();
            writeLogException($e);
            $db->query("UNLOCK TABLES");
            $data = array(
                'response' => 0,
                'message' => $e->getMessage(),
            );
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            return $json;
        } catch (Exception $e) {
            $db->rollback();
            writeLogException($e);
            $db->query("UNLOCK TABLES");
            $data = array(
                'response' => 0,
                'message' => $e->getMessage(),
            );
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            return $json;
        }

        $db->commit();
        doc_log("APPLY", '', 'doc', $this->id);
        $db->query("UNLOCK TABLES");
        $data = array(
            'response' => 1,
            'message' => "Документ успешно проведён!",
            'buttons' => $this->getCancelButtons(),
            'sklad_view' => 'hide',
            'statusblock' => 'Дата проведения: ' . date("Y-m-d H:i:s"),
            'poslist'   => 'refresh',
        );
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $json;
    }

    public function cancelJson() {
        global $db;
        $tim = time();
        $dd = date_day($tim);
        if ($this->typename) {
            $object = 'doc_' . $this->typename;
        } else {
            $object = 'doc';
        }

        try {
            if (!\acl::testAccess('doc.' . $this->typename, \acl::CANCEL)) {
                if ((!\acl::testAccess('doc.' . $this->typename, \acl::TODAY_CANCEL)) || ($dd > $this->doc_data['date'])) {
                    throw new \AccessException();
                }
            }
            $this->extendedCancelAclCheck();

            if (!method_exists($this, 'DocCancel')) {
                throw new Exception("Метод отмены данного документа не определён!");
            }

            $res = $db->query("SELECT `recalc_active` FROM `variables`");
            if ($res->num_rows) {
                list($lock) = $res->fetch_row();
            } else {
                $lock = 0;
            }
            if ($lock) {
                throw new Exception("Идёт обслуживание базы данных. Проведение невозможно!");
            }

            $db->query("LOCK TABLES `doc_list` WRITE, `doc_base_cnt` WRITE, `doc_kassa` WRITE, `doc_list_pos` READ");
            $db->startTransaction();
            $this->get_docdata();
            $this->DocCancel();
            $db->query("UPDATE `doc_list` SET `err_flag`='0' WHERE `id`='{$this->id}'");
        } catch (mysqli_sql_exception $e) {
            $db->rollback();
            writeLogException($e);
            $db->query("UNLOCK TABLES");
            $json = " { \"response\": \"0\", \"message\": \"" . $e->getMessage() . "\" }";
            return $json;
        } catch (AccessException $e) {
            $db->rollback();
            $db->query("UNLOCK TABLES");
            doc_log("CANCEL-DENIED", $e->getMessage(), 'doc', $this->id);
            $json = " { \"response\": \"0\", \"message\": \"Недостаточно привилегий для выполнения операции!<br>" . $e->getMessage() . "<br>Вы можете <a href='#' onclick=\"return petitionMenu(event, '{$this->id}')\">попросить руководителя</a> выполнить отмену этого документа.\" }";
            return $json;
        } catch (Exception $e) {
            $db->rollback();
            $db->query("UNLOCK TABLES");
            $msg = '';
            if (\acl::testAccess('doc.' . $this->typename, \acl::CANCEL_FORCE)) {
                $msg = "<br>Вы можете <a href='/doc.php?mode=forcecancel&amp;doc={$this->id}'>принудительно снять проведение</a>.";
            }
            $json = " { \"response\": \"0\", \"message\": \"" . $e->getMessage() . $msg . "\" }";
            return $json;
        }

        $db->commit();
        doc_log("CANCEL", '', 'doc', $this->id);
        $json = ' { "response": "1", "message": "Документ успешно отменен!", "buttons": "' . $this->getApplyButtons() . '", "sklad_view": "show", "statusblock": "Документ отменён", "poslist": "refresh" }';
        $db->query("UNLOCK TABLES");
        return $json;
    }

    /// Провести документ
    /// @param silent Не менять отметку проведения
    protected function docApply($silent = 0) {
        global $db;
        if ($silent) {
            return;
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

    /// отменить проведение документа
    protected function docCancel() {
        global $db;
        $data = $db->selectRow('doc_list', $this->id);
        if (!$data) {
            throw new Exception('Ошибка выборки данных документа!');
        }
        if (!$data['ok']) {
            throw new Exception('Документ не проведён!');
        }
        $db->update('doc_list', $this->id, 'ok', 0);
        $this->sentZEvent('cancel');
    }

    /// Отменить проведение, не обращая внимание на структуру подчинённости
    function forceCancel() {
        global $tmpl, $db;

        \acl::accessGuard('doc.' . $this->typename, \acl::CANCEL_FORCE);
        if($this->doc_data['firm_id']>0) {
            \acl::accessGuard([ 'firm.global', 'firm.'.$this->doc_data['firm_id'] ], \acl::CANCEL_FORCE);
        }
        $opt = request('opt');
        if ($opt == '') {
            $tmpl->addContent("<h2>Внимание! Опасная операция!</h2>Отмена производится простым снятием отметки проведения, без проверки зависимостией, учета структуры подчинённости и изменения значений счётчиков. Вы приниматете на себя все последствия данного действия. Вы точно хотите это сделать?<br>
			<center>
			<a href='/docj_new.php' style='color: #0b0'>Нет</a> |
			<a href='/doc.php?mode=forcecancel&amp;opt=yes&amp;doc={$this->id}' style='color: #f00'>Да</a>
			</center>");
        } else {
            doc_log("FORCE CANCEL", '', 'doc', $this->id);
            $db->query("UPDATE `doc_list` SET `ok`='0', `err_flag`='1' WHERE `id`='{$this->id}'");
            $db->query("UPDATE `variables` SET `corrupted`='1'");
            $tmpl->msg("Всё, сделано.", "err", "Снятие отметки проведения");
        }
    }

    /// Callback функция для сортировки (например, печатных форм)
    static function sortDescriptionCallback($a, $b) {
        return strcmp($a["desc"], $b["desc"]);
    }

    /// Получить список доступных печатных форм
    /// @return Массив со списком печатных форм
    protected function getPrintFormList() {
        global $CONFIG;

        $ret = array();
        if (isset($this->PDFForms)) {
            if (is_array($this->PDFForms)) {
                foreach ($this->PDFForms as $form) {
                    $ret[] = array('name' => 'int:' . $form['name'], 'desc' => $form['desc'], 'mime' => '');
                }
            }
        }
        $dir = $CONFIG['site']['location'] . '/include/doc/printforms/' . $this->typename . '/';
        if (is_dir($dir)) {
            $dh = opendir($dir);
            if ($dh) {
                while (($file = readdir($dh)) !== false) {
                    if (preg_match('/.php$/', $file)) {
                        $cn = explode('.', $file);
                        $class_name = '\\doc\\printforms\\' . $this->typename . '\\' . $cn[0];
                        $class = new $class_name;
                        $nm = $class->getName();
                        $mime = $class->getMimeType();
                        $ret[] = array('name' => 'ext:' . $cn[0], 'desc' => $nm, 'mime' => $mime);
                    }
                }
                closedir($dh);
            }
        }
        usort($ret, array(get_class(), 'sortDescriptionCallback'));
        return $ret;
    }

    /// Получить список доступных печатных форм c CSV экспортом
    /// @return Массив со списком печатных форм
    protected function getCSVPrintFormList() {
        $ret = $this->getPrintFormList();
        if ($this->sklad_editor_enable) {
            $ret[] = array('name' => 'csv:export', 'desc' => 'Экспорт в CSV', 'mime' => 'text/csv');
        }
        return $ret;
    }

    /// Проверить, существует ли печатная форма с заданным названием
    /// @return true, если существует, false в ином случае
    protected function isPrintFormExists($form_name) {
        $forms = $this->getCSVPrintFormList();
        $found = false;
        foreach ($forms as $form) {
            if ($form['name'] == $form_name) {
                $found = true;
                break;
            }
        }
        return $found;
    }

    /// Получить mime тип формы
    /// @return тип, если форма существует, false в ином случае
    protected function getPrintFormMime($form_name) {
        $forms = $this->getCSVPrintFormList();
        $found = false;
        foreach ($forms as $form) {
            if ($form['name'] == $form_name) {
                $found = $form['mime'];
                break;
            }
        }
        return $found;
    }

    /// Получить отображаемое наименование формы
    /// @return Название формы, если существует, null в ином случае
    protected function getPrintFormViewName($form_name) {
        $forms = $this->getPrintFormList();
        foreach ($forms as $form) {
            if ($form['name'] == $form_name) {
                return $form['desc'];
            }
        }
        return null;
    }

    /// Сформировать печатную форму
    /// @param $form_name   Имя печатной формы
    /// @param $to_str      Вернуть ли данные в виде строки
    /// @return             Если $to_str == true - возвращает сформированный документ, false в ином случае
    protected function makePrintForm($form_name, $to_str = false) {
        if ($this->typename) {
            $object = 'doc_' . $this->typename;
        } else {
            $object = 'doc';
        }
        if ($this->doc_data['ok']) {
            \acl::accessGuard('doc.' . $this->typename, \acl::GET_PRINTFORM);
            \acl::accessGuard([ 'firm.global', 'firm.'.$this->doc_data['firm_id'] ], \acl::GET_PRINTFORM);
        } else {
            \acl::accessGuard('doc.' . $this->typename, \acl::GET_PRINTDRAFT);
            \acl::accessGuard([ 'firm.global', 'firm.'.$this->doc_data['firm_id'] ], \acl::GET_PRINTDRAFT);
        }

        if (!$this->isPrintFormExists($form_name)) {
            throw new \Exception('Печатная форма ' . html_out($form_name) . ' не зарегистрирована');
        }
        $f_param = explode(':', $form_name);
        if ($f_param[0] == 'int') {
            $method = '';
            foreach ($this->PDFForms as $form) {
                if ($form['name'] == $f_param[1]) {
                    $method = $form['method'];
                }
            }
            return $this->$method($to_str);
        } elseif ($f_param[0] == 'ext') {
            $class_name = '\\doc\\printforms\\' . $this->typename . '\\' . $f_param[1];
            $print_obj = new $class_name;
            $print_obj->setDocument($this);
            $print_obj->initForm();
            $print_obj->make();
            return $print_obj->outData($to_str);
        } elseif ($f_param[0] == 'csv') {
            return $this->CSVExport($to_str);
        } else {
            throw new Exception('Неверный тип печатной формы');
        }
    }

    /// Отправка документа по факсу
    /// @param $form_name   Имя печатной формы
    final function sendFax($form_name = '') {
        global $tmpl, $db;
        $tmpl->ajax = 1;
        try {
            if ($form_name == '') {
                $agent = new \models\agent($this->doc_data['agent']);
                $ret_data = array(
                    'response' => 'item_list',
                    'faxnum' => $agent->getFaxNum(),
                    'content' => $this->getPrintFormList()
                );
                $tmpl->setContent(json_encode($ret_data, JSON_UNESCAPED_UNICODE));
            } else {
                $faxnum = request('faxnum');
                if ($faxnum == '') {
                    throw new \Exception('Номер факса не указан');
                }
                if (!preg_match('/^\+\d{8,15}$/', $faxnum)) {
                    throw new \Exception("Номер факса $faxnum указан в недопустимом формате");
                }
                include_once('sendfax.php');
                $data = $this->makePrintForm($form_name, true);
                $fs = new FaxSender();
                $fs->setFileBuf($data);
                $fs->setFaxNumber($faxnum);

                $res = $db->query("SELECT `worker_email` FROM `users_worker_info` WHERE `user_id`='{$_SESSION['uid']}'");
                if ($res->num_rows) {
                    list($email) = $res->fetch_row();
                    $fs->setNotifyMail($email);
                }
                $res = $fs->send();
                $tmpl->setContent("{'response': 'send'}");
                doc_log("Send FAX", $faxnum, 'doc', $this->id);
            }
        } catch (Exception $e) {
            $tmpl->setContent("{response: 'err', text: '" . $e->getMessage() . "'}");
        }
    }

    /// Отправка документа по электронной почте
    /// @param $form_name   Имя печатной формы
    final function sendEMail($form_name = '') {
        global $tmpl, $db;
        $tmpl->ajax = 1;
        try {
            if ($form_name == '') {
                $agent = new \models\agent($this->doc_data['agent']);
                $ret_data = array(
                    'response' => 'item_list',
                    'email' => $agent->getEmail(),
                    'content' => $this->getCSVPrintFormList()
                );
                $tmpl->setContent(json_encode($ret_data, JSON_UNESCAPED_UNICODE));
            } else {
                $email = request('email');
                $comment = request('comment');
                if ($email == '') {
                    throw new \Exception('Адрес электронной почты не указан!');
                } else {
                    $data = $this->makePrintForm($form_name, true);
                    $mime = $this->getPrintFormMime($form_name);
                    switch ($mime) {
                        case 'application/pdf':
                            $extension = '.pdf';
                            break;
                        case 'text/csv':
                            $extension = '.csv';
                            break;
                        case 'application/vnd.ms-excel':
                            $extension = '.xls';
                            break;
                        case 'application/vnd.oasis.opendocument.spreadsheet':
                            $extension = '.ods';
                            break;
                        default:
                            $extension = '.pdf';
                    }

                    $fname = $this->typename . '_' . str_replace(":", "_", $form_name) . $extension;
                    $viewname = $this->getPrintFormViewName($form_name) . ' (' . $this->viewname . ')';
                    $this->sendDocByEMail($email, $comment, $viewname, $data, $fname);
                    $tmpl->setContent("{'response': 'send'}");
                    doc_log("Send email", $email, 'doc', $this->id);
                }
            }
        } catch (Exception $e) {
            $tmpl->setContent("{'response':'err','text':'" . $e->getMessage() . "'}");
        }
    }

    /// Печать документа
    /// @param $form_name   Имя печатной формы
    /// @param $user_print  Если истина - документ запрошен из пользовательского раздела
    function printForm($form_name = '', $user_print = false) {
        global $tmpl;
        $tmpl->ajax = 1;
        if ($form_name == '') {
            $ret_data = array(
                'response' => 'item_list',
                'content' => $this->getCSVPrintFormList()
            );
            $tmpl->setContent(json_encode($ret_data, JSON_UNESCAPED_UNICODE));
        } else {
            $this->makePrintForm($form_name);            
            if($user_print) {
                $this->sentZEvent('print');
                doc_log("PRINT", $form_name, 'doc', $this->id);
            } else {
                $this->sentZEvent('userprint');
                doc_log("USERPRINT", $form_name, 'doc', $this->id);
            }
        }
    }

    /// Выполнить удаление документа. Если есть зависимости - удаление не производится.
    function delExec() {
        global $db;
        if ($this->doc_data['ok']) {
            throw new \Exception("Нельзя удалить проведённый документ");
        }
        $res = $db->query("SELECT `id`, `mark_del` FROM `doc_list` WHERE `p_doc`='{$this->id}'");
        if ($res->num_rows) {
            throw new \Exception("Нельзя удалить документ с неудалёнными потомками");
        }
        $db->query("DELETE FROM `doc_list_pos` WHERE `doc`='{$this->id}'");
        $db->query("DELETE FROM `doc_dopdata` WHERE `doc`='{$this->id}'");
        $db->query("DELETE FROM `doc_list` WHERE `id`='{$this->id}'");
    }

    /// Сделать документ потомком указанного документа
    function connect($p_doc) {
        global $db;
        \acl::accessGuard('doc.' . $this->typename, \acl::UPDATE);
        if($this->doc_data['firm_id']>0) {
            \acl::accessGuard([ 'firm.global', 'firm.'.$this->doc_data['firm_id']], \acl::UPDATE);
        }
        if ($this->id == $p_doc) {
            throw new \Exception('Нельзя связать с самим собой!');
        }
        if ($this->doc_data['ok']) {
            throw new \Exception("Операция не допускается для проведённого документа!");
        }
        if ($p_doc != 0) {
            // Проверяем существование документа
            $res = $db->query("SELECT `p_doc` FROM `doc_list` WHERE `id`=$p_doc");
            if (!$res->num_rows) {
                throw new \Exception('Документ с ID ' . $p_doc . ' не найден.');
            }
        }
        $db->query("UPDATE `doc_list` SET `p_doc`='$p_doc' WHERE `id`='{$this->id}'");
    }

    /// Сделать документ потомком указанного документа и вернуть резутьтат в json формате
    function connectJson($p_doc) {
        try {
            $this->Connect($p_doc);
            return " { \"response\": \"connect_ok\" }";
        } catch (Exception $e) {
            return " { \"response\": \"error\", \"message\": \"" . $e->getMessage() . "\" }";
        }
    }

    /// отправка документа по электронной почте
    function sendDocByEMail($email, $comment, $docname, $data, $filename, $body = '') {
        global $CONFIG, $db;
        $pref = \pref::getInstance();
        require_once($CONFIG['location'] . '/common/email_message.php');
        $res_autor = $db->query("SELECT `worker_real_name`, `worker_phone`, `worker_email` FROM `users_worker_info`
            WHERE `user_id`='" . $this->doc_data['user'] . "'");
        $doc_autor = $res_autor->fetch_assoc();
        $agent = new \models\agent($this->doc_data['agent']);

        $email_message = new email_message_class();
        $email_message->default_charset = "UTF-8";
        if ($agent->fullname) {
            $email_message->SetEncodedEmailHeader("To", $email, $agent->fullname);
        } else if ($agent->name) {
            $email_message->SetEncodedEmailHeader("To", $email, $agent->name);
        } else {
            $email_message->SetEncodedEmailHeader("To", $email, $email);
        }

        $email_message->SetEncodedHeader("Subject", "{$pref->site_display_name} - $docname ({$pref->site_name})");

        if (!@$doc_autor['worker_email']) {
            $email_message->SetEncodedEmailHeader("From", $pref->site_email, "Почтовый робот {$pref->site_name}");
            $email_message->SetHeader("Sender", $pref->site_email);
            $text_message = "Здравствуйте, {$agent->fullname}!\n"
                    . "Во вложении находится заказанный Вами документ ($docname) от {$pref->site_display_name} ({$pref->site_name})\n\n"
                    . "$comment\n\n"
                    . "Сообщение сгенерировано автоматически, отвечать на него не нужно!\n"
                    . "Для переписки используйте адрес, указанный в контактной информации на сайте http://{$pref->site_name}!";
        } else {
            $email_message->SetEncodedEmailHeader("From", $doc_autor['worker_email'], $doc_autor['worker_real_name']);
            $email_message->SetHeader("Sender", $doc_autor['worker_email']);
            $text_message = "Здравствуйте, {$agent->fullname}!\n"
                    . "Во вложении находится заказанный Вами документ ($docname) от {$pref->site_name}\n\n$comment\n\n"
                    . "Ответственный сотрудник: {$doc_autor['worker_real_name']}\n"
                    . "Контактный телефон: {$doc_autor['worker_phone']}\n"
                    . "Электронная почта (e-mail): {$doc_autor['worker_email']}\n"
                    . "Отправитель: {$_SESSION['name']}";
        }
        if ($body) {
            $email_message->AddQuotedPrintableTextPart($body);
        } else {
            $email_message->AddQuotedPrintableTextPart($text_message);
        }

        $text_attachment = array(
            "Data" => $data,
            "Name" => $filename,
            "Content-Type" => "automatic/name",
            "Disposition" => "attachment"
        );
        $email_message->AddFilePart($text_attachment);

        $error = $email_message->Send();

        if (strcmp($error, "")) {
            throw new Exception($error);
        } else {
            return 0;
        }
    }
    
    /// Обработка отправки запроса на отмену документа
    protected function sendPetition() {
        global $db;
        $ret = array('object' => 'send_petition', 'response' => 'success');
        try {
            $text = request('text');
            $pref = pref::getInstance();
            if (mb_strlen($text) < 8) {
                throw new Exception('Сообщение слишком короткое! Опишите причину подробнее!');
            }
            $res = $db->query("SELECT `users`.`reg_email`, `users_worker_info`.`worker_email` FROM `users`
                LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id`
                WHERE `id`='{$_SESSION['uid']}'");
            $user_info = $res->fetch_array();
            if ($user_info['worker_email'] != '') {
                $from = $user_info['worker_email'];
            } else if ($user_info['reg_email'] != '') {
                $from = $user_info['reg_email'];
            } else {
                $from = \cfg::get('site', 'doc_adm_email');
            }

            $proto = @$_SERVER['HTTPS'] ? 'https' : 'http';
            $ip = getenv("REMOTE_ADDR");
            $date = date("Y-m-d H:i:s", $this->doc_data['date']);
            $txt = "Здравствуйте!\nПользователь {$_SESSION['name']} просит Вас отменить проводку документа *{$this->viewname}* с ID: {$this->id},"
                . " {$this->doc_data['altnum']}{$this->doc_data['subtype']} от {$date} на сумму {$this->doc_data['sum']}."
                . " Клиент {$this->doc_data['agent_name']}.\n{$proto}://{$_SERVER["HTTP_HOST"]}/doc.php?mode=body&doc={$this->id} \n"
                . "Цель отмены: $text.\n"
                . "IP: $ip\n"
                . "Пожалуйста, дайте ответ на это письмо на $from, как в случае отмены документа, так и об отказе отмены!";

            if (\cfg::get('site', 'doc_adm_email')) {
                mailto(\cfg::get('site', 'doc_adm_email'), 'Запрос на отмену проведения документа', $txt, $from);
            }

            if (\cfg::get('site', 'doc_adm_jid') && \cfg::get('xmpp', 'host')) {
                require_once(\cfg::getroot('location') . '/common/XMPPHP/XMPP.php');
                $xmppclient = new \XMPPHP_XMPP(\cfg::get('xmpp', 'host'), \cfg::get('xmpp', 'port'), \cfg::get('xmpp','login'), \cfg::get('xmpp','pass')
                    , 'MultiMag r' . MULTIMAG_REV);
                $xmppclient->connect();
                $xmppclient->processUntil('session_start');
                $xmppclient->presence();
                $xmppclient->message(\cfg::get('site', 'doc_adm_jid'), $txt);
                $xmppclient->disconnect();
            }
            $ret['message'] = "Сообщение было отправлено уполномоченному лицу! Ответ о снятии проводки придёт вам на e-mail!";
        } 
        catch (XMPPHP_Exception $e) {
            writeLogException($e);
            $ret = array('object' => 'send_petition', 'response' => 'error',
                'errormessage' => "Невозможно отправить сообщение по XMPP: " . $e->getMessage()
            );
        }
        catch (\Exception $e) {
            $ret = array('object' => 'send_petition', 'response' => 'error', 'errormessage' => $e->getMessage());
        }
        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }

    function service() {
        global $tmpl;
        $tmpl->ajax = 1;
        $opt = request('opt');
        $pos = rcvint('pos');

        $this->_Service($opt, $pos);
    }

    /// Служебные опции
    function _service($opt, $pos) {
        global $tmpl, $db;
        $tmpl->ajax = 1;

        if ($this->sklad_editor_enable) {
            include_once('doc.poseditor.php');
            $poseditor = new DocPosEditor($this);
            $poseditor->cost_id = @$this->dop_data['cena'];
            $poseditor->sklad_id = $this->doc_data['sklad'];
            $poseditor->SetEditable($this->doc_data['ok'] ? 0 : 1);
            $poseditor->setAllowNegativeCounts($this->allow_neg_cnt);
        }

        $peopt = request('peopt'); // Опции редактора списка товаров

        if (\acl::testAccess('doc.' . $this->typename, \acl::VIEW)) {
            // Json-вариант списка товаров
            if ($peopt == 'jget') {
                // TODO: пересчет цены перенести внутрь poseditor
                $this->recalcSum();
                $doc_content = $poseditor->GetAllContent();
                $tmpl->addContent($doc_content);
            } else if ($peopt == 'jgetgroups') {
                $doc_content = $poseditor->getGroupList();
                $tmpl->addContent($doc_content);
            }
            // Снять пометку на удаление
            else if ($opt == 'jundeldoc') {
                $tmpl->setContent($this->serviceUnDelDoc());
            }
            else if ($opt == 'petition') {
                $tmpl->setContent($this->sendPetition());
            }
            /// TODO: Это тоже переделать!
            else if ($this->doc_data['ok']) {
                throw new Exception("Операция не допускается для проведённого документа!");
            } else if ($this->doc_data['mark_del']) {
                throw new Exception("Операция не допускается для документа, отмеченного для удаления!");
            }
            // Получение данных наименования
            else if ($peopt == 'jgpi') {
                $pos = rcvint('pos');
                $tmpl->addContent($poseditor->GetPosInfo($pos));
            }
            // Json вариант добавления позиции
            else if ($peopt == 'jadd') {
                \acl::accessGuard('doc.' . $this->typename, \acl::UPDATE);
                if($this->doc_data['firm_id']>0) {
                    \acl::accessGuard([ 'firm.global', 'firm.'.$this->doc_data['firm_id']], \acl::UPDATE);
                }
                $pe_pos = rcvint('pe_pos');
                $tmpl->setContent($poseditor->AddPos($pe_pos));
            }
            // Json вариант удаления строки
            else if ($peopt == 'jdel') {
                \acl::accessGuard('doc.' . $this->typename, \acl::UPDATE);
                if($this->doc_data['firm_id']>0) {
                    \acl::accessGuard([ 'firm.global', 'firm.'.$this->doc_data['firm_id']], \acl::UPDATE);
                }
                $line_id = rcvint('line_id');
                $tmpl->setContent($poseditor->Removeline($line_id));
            }
            // Json вариант обновления
            else if ($peopt == 'jup') {
                \acl::accessGuard('doc.' . $this->typename, \acl::UPDATE);
                if($this->doc_data['firm_id']>0) {
                    \acl::accessGuard([ 'firm.global', 'firm.'.$this->doc_data['firm_id']], \acl::UPDATE);
                }
                $line_id = rcvint('line_id');
                $value = request('value');
                $type = request('type');
                // TODO: пересчет цены перенести внутрь poseditor
                $tmpl->setContent($poseditor->UpdateLine($line_id, $type, $value));
            }
            // Получение номенклатуры выбранной группы
            else if ($peopt == 'jsklad') {
                $group_id = rcvint('group_id');
                $str = "{ response: 'sklad_list', group: '$group_id',  content: [" . $poseditor->GetSkladList($group_id) . "] }";
                $tmpl->setContent($str);
            }
            // Поиск по подстроке по складу
            else if ($peopt == 'jsklads') {
                $s = request('s');
                $str = "{ response: 'sklad_list', content: " . $poseditor->SearchSkladList($s) . " }";
                $tmpl->setContent($str);
            }
            // Серийные номера
            else if ($peopt == 'jsn') {
                $action = request('a');
                $line_id = request('line');
                $data = request('data');
                $tmpl->setContent($poseditor->SerialNum($action, $line_id, $data));
            }
            // Сброс цен
            else if ($peopt == 'jrc') {
                $poseditor->resetPrices();
            }
            // Сортировка наименований
            else if ($peopt == 'jorder') {
                $by = request('by');
                $poseditor->reOrder($by);
            }
            // Пометка на удаление
            else if ($opt == 'jdeldoc') {
                $tmpl->setContent($this->serviceDelDoc());
            }
            // Загрузка номенклатурной таблицы
            else if ($opt == 'merge') {
                $from_doc = rcvint('from_doc');
                $clear = rcvint('clear');
                $no_sum = rcvint('no_sum');

                try {
                    if ($from_doc == 0) {
                        throw new Exception("Документ не задан");
                    }
                    $db->startTransaction();

                    $res = $db->query("SELECT `id` FROM `doc_list` WHERE `id`=$from_doc");
                    if (!$res->num_rows) {
                        throw new Exception("Документ не найден");
                    }

                    if ($clear) {
                        $db->query("DELETE FROM `doc_list_pos` WHERE `doc`='{$this->id}'");
                    }

                    $res = $db->query("SELECT `doc`, `tovar`, SUM(`cnt`) AS `cnt`, `gtd`, `comm`, `cost`, `page` FROM `doc_list_pos`"
                            . "WHERE `doc`=$from_doc AND `page`=0 GROUP BY `tovar`");
                    while ($line = $res->fetch_assoc()) {
                        if (!$no_sum) {
                            $poseditor->simpleIncrementPos($line['tovar'], $line['cost'], $line['cnt'], $line['comm']);
                        } else {
                            $poseditor->simpleRewritePos($line['tovar'], $line['cost'], $line['cnt'], $line['comm']);
                        }
                    }
                    doc_log("REWRITE", "", 'doc', $this->id);
                    $db->commit();
                    $ret = array('response' => 'merge_ok');
                } catch (Exception $e) {
                    $ret = array('response' => 'err', 'text' => $e->getMessage());
                }
                $tmpl->setContent(json_encode($ret, JSON_UNESCAPED_UNICODE));
            }
            // Связи документа
            else if ($opt == 'link_info') {
                $childs = array();
                $parent = null;
                if ($this->doc_data['p_doc']) {
                    $res = $db->query("SELECT `doc_list`.`id`, `doc_types`.`name`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`,
                                    `doc_list`.`ok`, `doc_list`.`sum` FROM `doc_list`
                                    LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
                                    WHERE `doc_list`.`id`='{$this->doc_data['p_doc']}'");
                    $parent = $res->fetch_assoc();
                    $parent['vdate'] = date("d.m.Y", $parent['date']);
                }
                $res = $db->query("SELECT `doc_list`.`id`, `doc_types`.`name`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`,
                                `doc_list`.`ok`, `doc_list`.`sum` FROM `doc_list`
                                LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
                                WHERE `doc_list`.`p_doc`='{$this->id}'");

                while ($line = $res->fetch_assoc()) {
                    $line['vdate'] = date("d.m.Y", $line['date']);
                    $childs[] = $line;
                }
                $ret = array('response' => 'link_info', 'parent' => $parent, 'childs' => $childs);
                $tmpl->setContent(json_encode($ret, JSON_UNESCAPED_UNICODE));
            }

            // Для наследования!!!
            else {
                return 0;
            }

            return 1;
        } else {
            $tmpl->msg("Недостаточно привилегий для выполнения операции!", "err");
        }
    }

    protected function drawLHeadformStart() {
        $this->drawHeadformStart('j');
    }

    /// Отобразить заголовок шапки документа
    protected function drawHeadformStart($alt = '') {
        global $tmpl, $CONFIG, $db;
        $pref = \pref::getInstance();
        if ($this->doc_data['date'])
            $dt = date("Y-m-d H:i:s", $this->doc_data['date']);
        else
            $dt = date("Y-m-d H:i:s");
        $tmpl->addContent("<form method='post' action='' id='doc_head_form'>
		<input type='hidden' name='mode' value='{$alt}heads'>
		<input type='hidden' name='type' value='" . $this->doc_type . "'>");
        if (isset($this->doc_data['id']))
            $tmpl->addContent("<input type='hidden' name='doc' value='" . $this->doc_data['id'] . "'>");
        if (@$this->doc_data['mark_del'])
            $tmpl->addContent("<h3>Документ помечен на удаление!</h3>");
        $tmpl->addContent("
		<table id='doc_head_main'>
		<tr><td class='altnum'>А. номер</td><td class='subtype'>Подтип</td><td class='datetime'>Дата и время</td><tr>
		<tr class='inputs'>
		<td class='altnum'><input type='text' name='altnum' value='" . $this->doc_data['altnum'] . "' id='anum'><a href='#' onclick=\"return GetValue('/doc.php?mode=incnum&type=" . $this->doc_type . "&amp;doc=" . $this->id . "', 'anum', 'sudata', 'datetime', 'firm_id')\"><img border=0 src='/img/i_add.png' alt='Новый номер'></a></td>
		<td class='subtype'><input type='text' name='subtype' value='" . $this->doc_data['subtype'] . "' id='sudata'></td>
		<td class='datetime'><input type='text' name='datetime' value='$dt' id='datetime'></td>
		</tr>
		</table>
		Организация:<br><select name='firm' id='firm_id'>");
        $res = $db->query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `firm_name`");
        if (!$this->doc_data['firm_id'])
            $this->doc_data['firm_id'] = $pref->site_default_firm;
        while ($nx = $res->fetch_row()) {
            if ($this->doc_data['firm_id'] == $nx[0])
                $s = ' selected';
            else
                $s = '';
            $tmpl->addContent("<option value='$nx[0]' $s>$nx[1] / $nx[0]</option>");
        }
        $tmpl->addContent("</select><br>");
    }

    protected function drawLHeadformEnd() {
        global $tmpl;
        $tmpl->addContent("<br>Комментарий:<br><textarea name='comment'>" . html_out($this->doc_data['comment']) . "</textarea></form>");
    }

    protected function drawHeadformEnd() {
        global $tmpl;
        $tmpl->addContent(@"<br>Комментарий:<br><textarea name='comment'>" . html_out($this->doc_data['comment']) . "</textarea><br><input type=submit value='Записать'></form>");
    }

    /// Сформировать поля выбора агента
    protected function drawAgentField() {
        global $tmpl, $db;
        $balance = agentCalcDebt($this->doc_data['agent']);
        $bonus = docCalcBonus($this->doc_data['agent']);
        $col = '';
        if ($balance > 0)
            $col = "color: #f00; font-weight: bold;";
        if ($balance < 0)
            $col = "color: #f08; font-weight: bold;";

        $res = $db->query("SELECT `doc_list`.`id`, `doc_dopdata`.`value`
		FROM `doc_list`
		LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='name'
		WHERE `agent`='{$this->doc_data['agent']}' AND `type`='14' AND `firm_id`='{$this->doc_data['firm_id']}'");
        $contr_content = '';
        while ($nxt = $res->fetch_row()) {
            $selected = ($this->doc_data['contract'] == $nxt[0]) ? 'selected' : '';
            $contr_content.="<option value='$nxt[0]' $selected>N$nxt[0]: $nxt[1]</option>";
        }
        if ($contr_content)
            $contr_content = "Договор:<br><select name='contract'>$contr_content</select>";

        if ($this->doc_data['agent_dishonest'])
            $ag = "<span style='color: #f00; font-weight:bold;'>Был выбран недобросовестный агент!</span>";
        else
            $ag = '';
        $tmpl->addContent("
		<div>
		<div style='float: right; $col' id='agent_balance_info' onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=dolgi&agent={$this->doc_data['agent']}'); return false;\">$balance / $bonus</div>
		Агент:
		<a href='/docs.php?l=agent&mode=srv&opt=ep&pos={$this->doc_data['agent']}' id='ag_edit_link' target='_blank'><img src='/img/i_edit.png'></a>
		<a href='/docs.php?l=agent&mode=srv&opt=ep' target='_blank'><img src='/img/i_add.png'></a>
		</div>
		<input type='hidden' name='agent' id='agent_id' value='{$this->doc_data['agent']}'>
		<input type='text' id='agent_nm'  style='width: 100%;' value='" . html_out($this->doc_data['agent_name']) . "'>
		$ag
		<div id='agent_contract'>$contr_content</div>
		<br>

		<script type=\"text/javascript\">
		$(document).ready(function(){
			$(\"#agent_nm\").autocomplete(\"/docs.php\", {
				delay:300,
				minChars:1,
				matchSubset:1,
				autoFill:false,
				selectFirst:true,
				matchContains:1,
				cacheLength:10,
				maxItemsToShow:15,
				formatItem:agliFormat,
				onItemSelect:agselectItem,
				extraParams:{'l':'agent','mode':'srv','opt':'ac'}
			});
		});

		function agliFormat (row, i, num) {
			var result = row[0] + \"<em class='qnt'>тел. \" +
			row[2] + \"</em> \";
			return result;
		}

		function agselectItem(li) {
			if( li == null ) var sValue = \"Ничего не выбрано!\";
			if( !!li.extra ) var sValue = li.extra[0];
			else var sValue = li.selectValue;
			document.getElementById('agent_id').value=sValue;
			document.getElementById('ag_edit_link').href='/docs.php?l=agent&mode=srv&opt=ep&pos='+sValue;
			var firm_id_elem = document.getElementById('firm_id');
                        var firm_id = 0;
                        if(firm_id_elem) {
                            firm_id = firm_id_elem.value;
                        }
                        UpdateContractInfo('{$this->id}',firm_id,sValue);
                        
			");
        if (!$this->id)
            $tmpl->addContent("
			var plat_id=document.getElementById('plat_id');
			if(plat_id)	plat_id.value=li.extra[0];
			var plat=document.getElementById('plat');
			if(plat)	plat.value=li.selectValue;
			var gruzop_id=document.getElementById('gruzop_id');
			if(gruzop_id)	gruzop_id.value=li.extra[0];
			var gruzop=document.getElementById('gruzop');
			if(gruzop)	gruzop.value=li.selectValue;");
        $tmpl->addContent("
		}
		</script>");
    }

    protected function drawSkladField() {
        global $tmpl, $db;
        $tmpl->addContent("Склад:<br>
		<select name='sklad'>");
        $res = $db->query("SELECT `id`,`name` FROM `doc_sklady` ORDER BY `id`");

        while ($nxt = $res->fetch_row()) {
            if ($nxt[0] == $this->doc_data['sklad'])
                $tmpl->addContent("<option value='$nxt[0]' selected>" . html_out($nxt[1]) . "</option>");
            else
                $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select><br>");
    }

    protected function drawBankField() {
        global $tmpl, $CONFIG, $db;
        if ($this->doc_data['firm_id'])
            $sql_add = "AND ( `firm_id`='0' OR `num`='{$this->doc_data['bank']}' OR `firm_id`='{$this->doc_data['firm_id']}' )";
        else
            $sql_add = '';
        if ($this->doc_data['bank'])
            $bank = $this->doc_data['bank'];
        else {
            $pref = \pref::getInstance();
            $bank = $pref->getSitePref('default_bank_id');
        }
        $tmpl->addContent("Банк:<br><select name='bank'>");
        $res = $db->query("SELECT `num`, `name`, `rs` FROM `doc_kassa` WHERE `ids`='bank' $sql_add  ORDER BY `num`");
        while ($nxt = $res->fetch_row()) {
            if ($nxt[0] == $bank)
                $tmpl->addContent("<option value='$nxt[0]' selected>" . html_out($nxt[1] . ' / ' . $nxt[2]) . "</option>");
            else
                $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1] . ' / ' . $nxt[2]) . "</option>");
        }
        $tmpl->addContent("</select><br>");
    }

    protected function drawKassaField() {
        global $tmpl, $db, $CONFIG;
        if ($this->doc_data['kassa']) {
            $kassa = $this->doc_data['kassa'];
        } else {
            $pref = \pref::getInstance();
            $kassa = $pref->getSitePref('default_cash_id');
        }
        settype($kassa, 'int');
        $tmpl->addContent("Касса:<br><select name='kassa'>");
        $res = $db->query("SELECT `num`, `name` FROM `doc_kassa` WHERE `ids`='kassa' AND 
                    (`firm_id`='0' OR `firm_id` IS NULL OR `firm_id`='{$this->doc_data['firm_id']}' OR `num`='$kassa') ORDER BY `num`");        

        if ($kassa == 0) {
            $tmpl->addContent("<option value='0'>--не выбрана--</option>");
        }
        while ($nxt = $res->fetch_row()) {
            if ($nxt[0] == $kassa) {
                $tmpl->addContent("<option value='$nxt[0]' selected>" . html_out($nxt[1]) . "</option>");
            } else {
                $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
            }
        }
        $tmpl->addContent("</select><br>");
    }

    protected function drawSumField() {
        global $tmpl;
        $tmpl->addContent("Сумма:<br>
		<input type='text' name='sum' value='{$this->doc_data['sum']}'><img src='/img/i_+-.png'><br>");
    }

    protected function drawPriceField() {
        global $tmpl, $db;
        $tmpl->addContent("Цена:<a onclick='ResetCost(\"{$this->id}\"); return false;' id='reset_cost'><img src='/img/i_reload.png'></a><br>
		<select name='cena'>");
        $s = '';
        if ($this->dop_data['cena'] == 0)
            $s = ' selected';
        $tmpl->addContent("<option value='0'{$s}>--авто--</option>");
        $res = $db->query("SELECT `id`,`name` FROM `doc_cost` ORDER BY `name`");
        while ($nxt = $res->fetch_row()) {
            if ($this->dop_data['cena'] == $nxt[0])
                $s = 'selected';
            else
                $s = '';
            $tmpl->addContent("<option value='$nxt[0]' $s>" . html_out($nxt[1]) . "</option>");
        }

        if ($this->doc_data['nds'])
            $tmpl->addContent("<label><input type='radio' name='nds' value='0'>Выделять НДС</label>&nbsp;&nbsp;
			<label><input type='radio' name='nds' value='1' checked>Включать НДС</label><br>");
        else
            $tmpl->addContent("<label><input type='radio' name='nds' value='0' checked>Выделять НДС</label>&nbsp;&nbsp;
			<label><input type='radio' name='nds' value='1'>Включать НДС</label><br>");
        $tmpl->addContent("<br>");
    }

    // ====== Получение данных, связанных с документом =============================
    protected function get_docdata() {
        if (isset($this->doc_data))
            return;
        global $CONFIG, $db;
        if ($this->id) {
            $this->loadFromDb($this->id);
        } else {
            if (method_exists($this, 'initDefDopData')) {
                $this->initDefDopData();
            }
            $this->dop_data = $this->def_dop_data;
            $pref = \pref::getInstance();
            
            $this->doc_data = array('id' => 0, 'type' => '', 'agent' => $pref->getSitePref('default_agent_id'), 'comment' => '', 'date' => time(), 'ok' => 0,
                'sklad' => $pref->getSitePref('default_store_id'), 'user' => 0, 'altnum' => 0, 'subtype' => '', 'sum' => 0, 'nds' => 1, 'p_doc' => 0, 'mark_del' => 0,
                'kassa' => 0, 'bank' => 0, 'firm_id' => 0, 'contract' => 0, 'created' => 0, 'agent_name' => '', 'agent_fullname' => '', 'agent_dishonest' => 0, 'agent_comment' => '');
            
            if(!$this->doc_data['agent']) {
                $this->doc_data['agent'] = 1;
            }
            $agent_data = $db->selectRow('doc_agent', $this->doc_data['agent']);
            if (is_array($agent_data)) {
                $this->doc_data['agent_name'] = $agent_data['name'];
            }

            if (!$this->doc_data['sklad']) {
                $this->doc_data['sklad'] = 1;
            }
        }
    }

    /// Проверка уникальности альтернативного порядкового номера документа
    public function isAltNumUnique() {
        global $db;
        $start_date = strtotime(date("Y-01-01 00:00:00", $this->doc_data['date']));
        $end_date = strtotime(date("Y-12-31 23:59:59", $this->doc_data['date']));
        $subtype_sql = $db->real_escape_string($this->doc_data['subtype']);
        $res = $db->query("SELECT `altnum` FROM `doc_list`"
                . " WHERE `type`='{$this->doc_type}' AND `altnum`='{$this->doc_data['altnum']}' AND `subtype`='$subtype_sql'"
                . " AND `id`!='{$this->id}' AND `date`>='$start_date' AND `date`<='$end_date' AND `firm_id`='{$this->doc_data['firm_id']}'");
        return $res->num_rows ? false : true;
    }

    /// Получение альтернативного порядкового номера документа
    public function getNextAltNum($doc_type, $subtype, $date, $firm_id) {
        global $CONFIG, $db;
        if (!$doc_type) {
            $doc_type = $this->doc_type;
        }
        $start_date = strtotime(date("Y-01-01 00:00:00", strtotime($date)));
        $end_date = strtotime(date("Y-12-31 23:59:59", strtotime($date)));
        $res = $db->query("SELECT `altnum` FROM `doc_list` WHERE `type`='$doc_type' AND `subtype`='$subtype'"
                . " AND `id`!='{$this->id}' AND `date`>='$start_date' AND `date`<='$end_date' AND `firm_id`='$firm_id'"
                . " ORDER BY `altnum` ASC");
        $newnum = 0;
        while ($nxt = $res->fetch_row()) {
            if (($nxt[0] - 1 > $newnum) && @$CONFIG['doc']['use_persist_altnum'])
                break;
            $newnum = $nxt[0];
        }
        $newnum++;
        return $newnum;
    }

    /// Кнопки меню - провети / отменить
    protected function getDopButtons() {
        global $tmpl;
        $ret = '';
        if ($this->id) {
            $ret.="<a href='/doc.php?mode=log&amp;doc={$this->id}' title='История изменений документа'><img src='img/i_log.png' alt='История'></a>";
            $ret.="<span id='provodki'>";
            if ($this->doc_data['ok']) {
                $ret .= $this->getCancelButtons();
            } else {
                $ret .= $this->getApplyButtons();
            }

            $ret .= "</span>
                <img src='/img/i_separator.png' alt=''>
                <a href='#' onclick=\"return PrintMenu(event, '{$this->id}')\" title='Печать'>
                    <img src='img/i_print.png' alt='Печать'></a>
                <a href='#' onclick=\"return FaxMenu(event, '{$this->id}')\" title='Отправить по факсу'>
                    <img src='img/i_fax.png' alt='Факс'></a>
                <a href='#' onclick=\"return MailMenu(event, '{$this->id}')\" title='Отправить по email'>
                    <img src='img/i_mailsend.png' alt='email'></a>
                <img src='/img/i_separator.png' alt=''>
                <a href='#' onclick=\"DocConnect('{$this->id}', '{$this->doc_data['p_doc']}'); return false;\" title='Связать документ'>
                    <img src='img/i_conn.png' alt='Связать'></a>
                <a href='#' onclick=\"return ShowContextMenu(event, '/doc.php?mode=morphto&amp;doc={$this->id}')\"
                    title='Создать связанный документ'><img src='img/i_to_new.png' alt='Связь'></a>";
            if ($this->sklad_editor_enable) {
                $ret .= " <a href='#' onclick=\"return addNomMenu(event, '{$this->id}', '{$this->doc_data['p_doc']}');\" title='Обновить номенклатурную таблицу'><img src='img/i_addnom.png' alt='Обновить номенклатурную таблицу'></a>";
            }
            $ret.="<img src='/img/i_separator.png' alt=''>";
        }

        if (method_exists($this, 'getAdditionalButtonsHTML')) {
            $ret .= $this->getAdditionalButtonsHTML();
        }
        return $ret;
    }

    protected function getApplyButtons() {
        if ($this->doc_data['mark_del']) {
            return "<a href='#' title='Отменить удаление' onclick='unMarkDelDoc({$this->id}); return false;'><img src='img/i_trash_undo.png' alt='отменить удаление'></a>";
        }
        else {
            return "<a href='#' title='Пометить на удаление' onclick='MarkDelDoc({$this->id}); return false;'><img src='img/i_trash.png' alt='Пометить на удаление'></a>".
            "<a href='#' title='Провести документ' onclick='ApplyDoc({$this->id}); return false;'><img src='img/i_ok.png' alt='Провести'></a>";
        }
        //<a href='?mode=ehead&amp;doc={$this->doc}' title='Правка заголовка'><img src='img/i_docedit.png' alt='Правка'></a>
    }

    protected function getCancelButtons() {
        return "<a title='Отменить проводку' onclick='CancelDoc({$this->id}); return false;'><img src='img/i_revert.png' alt='Отменить' /></a>";
    }

    /// Вычисление, можно ли отменить кассовый документ
    protected function checkKassMinus() {
        global $db;
        $sum = $i = 0;
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`sum`, `doc_list`.`kassa` FROM `doc_list`
		WHERE  `doc_list`.`ok`>'0' AND ( `doc_list`.`type`='6' OR `doc_list`.`type`='7' OR `doc_list`.`type`='9')
		ORDER BY `doc_list`.`date`");
        while ($nxt = $res->fetch_row()) {
            if ($nxt[3] == $this->doc_data['kassa']) {
                if ($nxt[1] == 6)
                    $sum += $nxt[2];
                else if ($nxt[1] == 7 || $nxt[1] == 9)
                    $sum -= $nxt[2];
            }
            else if ($nxt[1] == 9) {
                $rr = $db->query("SELECT `value` FROM `doc_dopdata` WHERE `doc`='$nxt[0]' AND `param`='v_kassu'");
                if (!$rr->num_rows)
                    throw new AutoLoggedException('Касса назначения не найдена в документе ' . $this->id);
                $data = $rr->fetch_row();
                if ($data[0] == $this->doc_data['kassa'])
                    $sum+=$nxt[2];
            }

            $sum = sprintf("%01.2f", $sum);
            if ($sum < 0)
                break;
            $i++;
        }
        $res->free();
        return $sum;
    }

    /// Показать историю изменений документа
    public function showLog() {
        global $tmpl;
        \acl::accessGuard('doc.' . $this->typename, \acl::VIEW);
        if($this->doc_data['firm_id']>0) {
            \acl::accessGuard([ 'firm.global', 'firm.'.$this->doc_data['firm_id']], \acl::VIEW);
        }
        $tmpl->setTitle($this->viewname . ' N' . $this->id);
        doc_menu($this->getDopButtons());
        $tmpl->addContent("<h1>{$this->viewname} N{$this->id} - история документа</h1>");

        $logview = new \LogView();
        $logview->setObject('doc');
        $logview->setObjectId($this->id);
        $logview->showLog();
    }

    /// Получить список номенклатуры
    function getDocumentNomenclature($options = '') {
        global $CONFIG, $db;
        $opts = array();
        $e_options = explode(',', $options);
        foreach ($e_options as $opt) {
            $opts[$opt] = 1;
        }
        $fields_sql = $join_sql = '';
        if (isset($opts['country'])) {
            $fields_sql .= ", `class_country`.`name` AS `country_name`, `class_country`.`number_code` AS `country_code`";
            $join_sql .= " LEFT JOIN `class_country` ON `class_country`.`id`=`doc_base`.`country`";
        }
        if (isset($opts['comment'])) {
            $fields_sql .= ", `doc_list_pos`.`comm` AS `comment`";
        }
        if (isset($opts['base_desc'])) {
            $fields_sql .= ", `doc_base`.`desc` AS `base_desc`";
        }
        if (isset($opts['vat'])) {
            $fields_sql .= ", `doc_base`.`nds` AS `vat`";
        }
        if (isset($opts['base_price'])) {
            $fields_sql .= ", `doc_base`.`cost` AS `base_price`";
        }
        if (isset($opts['bulkcnt'])) {
            $fields_sql .= ", `doc_base`.`bulkcnt`";
        }
        if (isset($opts['dest_place'])) {
            $to_sklad = (int) $this->dop_data['na_sklad'];
            $fields_sql .= ", `pt_d`.`mesto` AS `dest_place`";
            $join_sql .= " LEFT JOIN `doc_base_cnt` AS `pt_d` ON `pt_d`.`id`=`doc_list_pos`.`tovar` AND `pt_d`.`sklad`='{$to_sklad}'";
        }
        if (isset($opts['bigpack'])) {
            // ID параметра большой упаковки
            $res = $db->query("SELECT `id` FROM `doc_base_params` WHERE `codename`='bigpack_cnt'");
            if (!$res->num_rows) {
                $db->query("INSERT INTO `doc_base_params` (`name`, `codename`, `type`, `hidden`)"
                        . " VALUES ('Кол-во в большой упаковке', 'bigpack_cnt', 'int', 0)");
                throw new \Exception("Параметр *bigpack_cnt - кол-во в большой упаковке* не найден. Параметр создан.");
            }
            list($p_bp_id) = $res->fetch_row();
            $fields_sql .= ", `bp_t`.`value` AS `bigpack_cnt`";
            $join_sql .= " LEFT JOIN `doc_base_values` AS `bp_t` ON `bp_t`.`id`=`doc_base`.`id` AND `bp_t`.`param_id`='$p_bp_id'";
        }
        if (isset($opts['rto'])) {
            $fields_sql .= ", `doc_base_dop`.`transit`, `doc_base_dop`.`reserve`, `doc_base_dop`.`offer`";
            $join_sql .= " LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`";
        }
        $list = array();
        $res = $db->query("SELECT 
                `doc_list_pos`.`tovar` AS `pos_id`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost` AS `price`, 
                `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv` AS `vendor`, `doc_base`.`mass`, `doc_base`.`mult`,
                `doc_group`.`printname` AS `group_printname`, `doc_group`.`id` AS `group_id`,
                `doc_base_cnt`.`mesto` AS `place`, `doc_base_cnt`.`cnt` AS `base_cnt`, 
                `class_unit`.`rus_name1` AS `unit_name`, `class_unit`.`number_code` AS `unit_code`
                $fields_sql
            FROM `doc_list_pos`
            INNER JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
            LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
            LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data['sklad']}'
            LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
            $join_sql
            WHERE `doc_list_pos`.`doc`='{$this->id}'
            ORDER BY `doc_list_pos`.`id`");

        while ($line = $res->fetch_assoc()) {
            if ($line['group_printname']) {
                $line['name'] = $line['group_printname'] . ' ' . $line['name'];
            }
            if (!@$CONFIG['doc']['no_print_vendor'] && $line['vendor']) {
                $line['name'] .= ' / ' . $line['vendor'];
            }
            $line['code'] = $line['pos_id'];
            if ($line['vc']) {
                $line['code'] .= ' / ' . $line['vc'];
            }
            $line['sum'] = $line['price'] * $line['cnt'];

            if (isset($opts['vat'])) {
                if ($line['vat'] !== null) {
                    $ndsp = $line['vat'];
                } else {
                    $ndsp = $this->firm_vars['param_nds'];
                }
                $vat = $ndsp / 100;
            /*    if ($line['vat'] !== null) {
                    $line['vat_p'] = $line['vat'];
                } else {
                    $line['vat_p'] = $this->firm_vars['param_nds'];
                }
                $line['price_wo_vat'] = round($line['price'] / (1 + ($line['vat_p'] / 100)), 2);
                $line['sum_wo_vat'] = $line['price_wo_vat'] * $line['cnt'];
                $line['vat_s'] = ($line['price'] * $line['cnt']) - $line['sum_wo_vat'];*/
                $pos = $this->calcVAT($line['price'], $line['cnt'], $vat);
                //$line['price'] = $pos['price'];
                $line['sum_wo_vat'] = round($pos['sum_wo_vat'], 2);
                $line['vat_p'] = $ndsp;
                $line['vat_s'] = round($pos['vat_s'], 2);
                $line['sum'] = round($pos['sum'], 2);
            }
            
            
            $list[] = $line;
        }
        $res->free();
        return $list;
    }

    /// Получить список номенклатуры документа с НДС и НТД
    public function getDocumentNomenclatureWVATandNums() {
        global $CONFIG, $db;

        $list = array();
        $res = $db->query("SELECT `doc_group`.`printname` AS `group_printname`, `doc_base`.`name`, `doc_base`.`proizv` AS `vendor`, `doc_list_pos`.`cnt`,
            `doc_list_pos`.`cost`, `doc_list_pos`.`gtd`, `class_country`.`name` AS `country_name`, `doc_base_dop`.`ntd`, 
            `class_unit`.`rus_name1` AS `unit_name`, `doc_list_pos`.`tovar` AS `pos_id`, `class_unit`.`number_code` AS `unit_code`, 
            `class_country`.`number_code` AS `country_code`, `doc_base`.`vc`, `doc_base`.`mass`, `doc_base`.`nds`
	FROM `doc_list_pos`
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
	LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
	LEFT JOIN `class_country` ON `class_country`.`id`=`doc_base`.`country`
	WHERE `doc_list_pos`.`doc`='{$this->id}'
	ORDER BY `doc_list_pos`.`id`");

        while ($nxt = $res->fetch_assoc()) {
            if ($nxt['nds'] !== null) {
                $ndsp = $nxt['nds'];
            } else {
                $ndsp = $this->firm_vars['param_nds'];
            }
            $nds = $ndsp / 100;

            if (!$nxt['country_code']) {
                throw new \Exception("Не возможно формирование списка номенклатуры без указания страны происхождения товара");
            }

            $pos_name = $nxt['name'];
            if ($nxt['group_printname']) {
                $pos_name = $nxt['group_printname'] . ' ' . $pos_name;
            }
            if (!@$CONFIG['doc']['no_print_vendor'] && $nxt['vendor']) {
                $pos_name .= ' / ' . $nxt['vendor'];
            }
            $pos_code = $nxt['pos_id'];
            if ($nxt['vc']) {
                $pos_code .= ' / ' . $nxt['vc'];
            }

            if (@$CONFIG['poseditor']['true_gtd']) {
                $gtd_array = array();
                $gres = $db->query("SELECT `doc_list`.`type`, `doc_list_pos`.`gtd`, `doc_list_pos`.`cnt`, `doc_list`.`id` FROM `doc_list_pos`
                    INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
                    WHERE `doc_list_pos`.`tovar`='{$nxt['pos_id']}' AND `doc_list`.`firm_id`='{$this->doc_data['firm_id']}' AND `doc_list`.`type`<='2'
                    AND `doc_list`.`date`<'{$this->doc_data['date']}' AND `doc_list`.`ok`>'0'
                    ORDER BY `doc_list`.`date`");
                while ($line = $gres->fetch_assoc()) {
                    if ($line['type'] == 1) { // Поступление
                        $gtd_array[] = array('num' => $line['gtd'], 'cnt' => $line['cnt']);
                    } else {
                        $cnt = $line['cnt'];
                        while ($cnt > 0) {
                            if (count($gtd_array) == 0) {
                                if ($CONFIG['poseditor']['true_gtd'] != 'easy') {
                                    throw new \Exception("Не найдены поступления для $cnt единиц товара {$nxt['name']} (для реализации N{$line['id']} в прошлом). Товар был оприходован на другую организацию?");
                                } else {
                                    $gtd_array[] = array('num' => $line['gtd'], 'cnt' => $cnt);
                                }
                            }
                            if ($gtd_array[0]['cnt'] == $cnt) {
                                array_shift($gtd_array);
                                $cnt = 0;
                            } elseif ($gtd_array[0]['cnt'] > $cnt) {
                                $gtd_array[0]['cnt'] -= $cnt;
                                $cnt = 0;
                            } else {
                                $cnt -= $gtd_array[0]['cnt'];
                                array_shift($gtd_array);
                            }
                        }
                    }
                }

                $unigtd = array();
                $need_cnt = $nxt['cnt'];
                while ($need_cnt > 0 && count($gtd_array) > 0) {
                    $gtd_num = $gtd_array[0]['num'];
                    $gtd_cnt = $gtd_array[0]['cnt'];
                    if ($gtd_cnt >= $need_cnt) {
                        if (isset($unigtd[$gtd_num])) {
                            $unigtd[$gtd_num] += $need_cnt;
                        } else {
                            $unigtd[$gtd_num] = $need_cnt;
                        }
                        $need_cnt = 0;
                    } else {
                        if (isset($unigtd[$gtd_num])) {
                            $unigtd[$gtd_num] += $gtd_cnt;
                        } else {
                            $unigtd[$gtd_num] = $gtd_cnt;
                        }
                        $need_cnt -= $gtd_cnt;
                        array_shift($gtd_array);
                    }
                }
                if ($need_cnt > 0) {
                    if ($CONFIG['poseditor']['true_gtd'] != 'easy') {
                        throw new Exception("Не найдены поступления для $need_cnt единиц товара {$pos_name}. Товар был оприходован на другую организацию?");
                    } else {
                        $unigtd['   --   '] = $need_cnt;
                    }
                }
                foreach ($unigtd as $gtd => $cnt) {
                    $pos = $this->calcVAT($nxt['cost'], $cnt, $nds);
                    $list[] = array(
                        'code' => $pos_code,
                        'name' => $pos_name,
                        'unit_code' => $nxt['unit_code'],
                        'unit_name' => $nxt['unit_name'],
                        'cnt' => $cnt,
                        'price' => $pos['price'],
                        'sum_wo_vat' => round($pos['sum_wo_vat'], 2),
                        'excise' => 'без акциза',
                        'vat_p' => $ndsp,
                        'vat_s' => round($pos['vat_s'], 2),
                        'sum' => round($pos['sum'], 2),
                        'country_code' => $nxt['country_code'],
                        'country_name' => $nxt['country_name'],
                        'ncd' => $gtd,
                        'mass' => $nxt['mass']
                    );
                }
            } else {
                $pos = $this->calcVAT($nxt['cost'], $nxt['cnt'], $nds);
                $list[] = array(
                    'code' => $pos_code,
                    'name' => $pos_name,
                    'unit_code' => $nxt['unit_code'],
                    'unit_name' => $nxt['unit_name'],
                    'cnt' => $nxt['cnt'],
                    'price' => $pos['price'],
                    'sum_wo_vat' => round($pos['sum_wo_vat'], 2),
                    'excise' => 'без акциза',
                    'vat_p' => $ndsp,
                    'vat_s' => round($pos['vat_s'], 2),
                    'sum' => round($pos['sum'], 2),
                    'country_code' => $nxt['country_code'],
                    'country_name' => $nxt['country_name'],
                    'ncd' => $nxt['ntd'],
                    'mass' => $nxt['mass']
                );
            }
        }
        return $list;
    }

    /// Расчет НДС для строки документа
    /// @param $doc_price Цена единицы товара в документе
    /// @param $count Количество товара
    /// @param $vat Ставка НДС
    protected function calcVAT($doc_price, $count, $vat) {
        global $CONFIG;
        if (isset($CONFIG['poseditor']['vat_scheme'])) {
            $scheme = $CONFIG['poseditor']['vat_scheme'];
        } else {
            $scheme = 'correct';
        }
        if ($this->doc_data['nds']) {   // НДС включен
            $pos['sum'] = $doc_price * $count;
            if ($scheme == '1c') {
                $pos['sum_wo_vat'] = round($pos['sum'] / (1 + $vat), 2);
                $pos['vat_s'] = $pos['sum'] - $pos['sum_wo_vat'];
                $pos['price'] = round($pos['sum_wo_vat'] / $count, 2);
            } else {
                $pos['price'] = round($doc_price / (1 + $vat), 2);
                $pos['sum_wo_vat'] = round($pos['price'] * $count, 2);
                $pos['vat_s'] = round($doc_price * $count, 2) - $pos['sum_wo_vat'];
            }
        } else {
            $pos['price'] = $doc_price;
            $pos['sum_wo_vat'] = round($pos['price'] * $count, 2);
            $pos['vat_s'] = round($pos['sum_wo_vat'] * $vat, 2);
            $pos['sum'] = $pos['sum_wo_vat'] + $pos['vat_s'];
        }
        return $pos;
    }

    /// Установить пометку на удаление у документа
    protected function serviceDelDoc() {
        global $db;
        try {
            \acl::accessGuard('doc.' . $this->typename, \acl::DELETE);
            if($this->doc_data['firm_id']>0) {
                \acl::accessGuard([ 'firm.global', 'firm.'.$this->doc_data['firm_id']], \acl::DELETE);
            }
            $tim = time();

            $res = $db->query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$this->id}' AND `mark_del`='0'");
            if ($res->num_rows) {
                throw new Exception("Есть подчинённые не удалённые документы. Удаление невозможно.");
            }
            $db->update('doc_list', $this->id, 'mark_del', $tim);
            doc_log("MARKDELETE", '', "doc", $this->id);
            $this->doc_data['mark_del'] = $tim;
            $json = ' { "response": "1", "message": "Пометка на удаление установлена!", "buttons": "' . $this->getApplyButtons() . '", '
                    . '"statusblock": "Документ помечен на удаление" }';
            return $json;
        } catch (Exception $e) {
            return "{response: 0, message: '" . $e->getMessage() . "'}";
        }
    }

    /// Снять пометку на удаление у документа
    protected function serviceUnDelDoc() {
        global $db;
        try {
            \acl::accessGuard('doc.' . $this->typename, \acl::DELETE);
            if($this->doc_data['firm_id']>0) {
                \acl::accessGuard([ 'firm.global', 'firm.'.$this->doc_data['firm_id']], \acl::DELETE);
            }
            $db->update('doc_list', $this->id, 'mark_del', 0);
            doc_log("UNDELETE", '', "doc", $this->id);
            $json = ' { "response": "1", "message": "Пометка на удаление снята!", "buttons": "' . $this->getApplyButtons() . '", '
                    . '"statusblock": "Документ не будет удалён" }';
            return $json;
        } catch (Exception $e) {
            return "{response: 0, message: '" . $e->getMessage() . "'}";
        }
    }

    /// Экспорт табличной части документа в CSV
    function CSVExport($to_str = 0) {
        global $tmpl;
        $header = "PosNum;ID;VC;Name;Vendor;Cnt;Price;Sum;Comment\r\n";
        if (!$to_str) {
            $tmpl->ajax = 1;
            header("Content-type: 'application/octet-stream'");
            header("Content-Disposition: 'attachment'; filename=predlojenie.csv;");
            echo $header;
        } else {
            $str_out = $header;
        }
        $nomenclature = $this->getDocumentNomenclature('base_desc');

        $i = 0;
        foreach ($nomenclature as $line) {
            $i++;
            $str_line = "$i;{$line['pos_id']};\"{$line['vc']}\";\"{$line['name']}\";\"{$line['vendor']}\";{$line['cnt']};{$line['price']};{$line['sum']}\r\n";
            if (!$to_str) {
                echo $str_line;
            } else {
                $str_out.=$str_line;
            }
        }
        if ($to_str) {
            return $str_out;
        }
    }

    /// @brief Создание другого документа на основе текущего
    /// Метод необходимо переопределить у потомков
    /// @param $target_type Тип создаваемого документа
    /// @return Всегда false
    public function morphTo($target_type) {
        return false;
    }

    /**
     * Проверка для приходных/расходных кассовых ордеров
     * и средств из/в банк при проведении документа
     * @throws Exception При отсутствии
     */
    protected function checkIfTypeForDocumentExists() {
        $allowedTypes = [
            4 => 'credit_type',
            5 => 'rasxodi',
            6 => 'credit_type',
            7 => 'rasxodi',
        ];
        if(!isset($allowedTypes[$this->doc_type])) {
            throw new \Exception('Для данного типа документа проверка не разрешена');
        }
        if(cfg::get('doc', 'restrict_dc_nulltype', true)
            && isset($this->dop_data[$allowedTypes[$this->doc_type]])
            && $this->dop_data[$allowedTypes[$this->doc_type]] == 0) {
            $type = $this->doc_type%2 === 1 ? 'расхода' : 'дохода';
            throw new \Exception("Не задан вид $type у проводимого документа.");
        }
    }
}
