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

namespace actions;

require_once($CONFIG['location'] . '/common/email_message.php');
require_once($CONFIG['location'] . '/web/include/doc.core.php');

/// Информирование об изменённых ценах наименований при помощи email
class chPriceNotify extends \Action {
    
    var $period = 'hourly';
        
    /// @brief Запустить
    public function run() {
        global $CONFIG;
        if(!@$this->config['chpricenotify']['notify_workers'] && !@$this->config['chpricenotify']['notify_clients'] && !@$this->config['chpricenotify']['notify_address']) {
            return;
        }
        $this->list_id = md5(microtime()) . '.' . date("dmY") . '.' . $CONFIG['site']['name'];
        $pos_info = array();
        $start_date = date("Y-m-d H:i:s", time()-60*60*2 );
        $res = $this->db->query("SELECT `id`, `cost` AS `base_price`, `cost_date` AS `price_date`, `name`, `vc`, `group`, `bulkcnt`"
                . " FROM `doc_base` WHERE `cost_date`>='$start_date'");
        while($line = $res->fetch_assoc()) {
            $pos_info[] = $line;
        }
        if(count($pos_info)==0) {
            return;
        }
                
        // Получить название фирмы, от которой выполняется рассылка
        $res = $this->db->query("SELECT `firm_name` FROM `doc_vars` WHERE `id`='{$this->config['site']['default_firm']}'");
        list($firm_name) = $res->fetch_row();
        
        // Оповещаем сотрудников
        if(@$this->config['chpricenotify']['notify_workers']) {
            $res = $this->db->query("SELECT `user_id`, `worker`, `worker_email`, `worker_real_name` FROM `users_worker_info` WHERE `worker`>0");
            while ($worker_info = $res->fetch_assoc()) {
                if(!isset($worker_info['worker_email'])) {
                    continue;
                }
                $header = "Здравствуйте, {$worker_info['worker_real_name']}!\n";        
                $footer = "Вы получили это письмо потому что являетесь сотрудником компании $firm_name, обслуживающей интернет-магазин {$this->config['site']['display_name']} ( http://{$this->config['site']['name']})\n"
                . "Чтобы перестать получать уведомления, Вам нужно перестать быть сотрудником компании $firm_name.";
                $this->sendMessage($pos_info, $header, $footer, $worker_info['worker_email']);
            }
        }
        
        // Оповещаем клиентов
        if(@$this->config['chpricenotify']['notify_clients']) {
            $clients = getSubscribersEmailList();
            foreach($clients as $subscriber_info) {
                $header = "Здравствуйте, {$subscriber_info['name']}!\n"; 
                $footer = "Вы получили это письмо потому что подписаны на рассылку сайта {$CONFIG['site']['display_name']} ( http://{$CONFIG['site']['name']}?from=email ), либо являетесь клиентом $firm_name.
Отказаться от рассылки можно, перейдя по ссылке http://{$CONFIG['site']['name']}/login.php?mode=unsubscribe&email={$subscriber_info['email']}&from=email";
                $this->sendMessage($pos_info, $header, $footer, $subscriber_info['email']);
            }
        }
        
        // Оповещаем указанные адреса
        if(@$this->config['chpricenotify']['notify_address']) {            
            if(is_array($this->config['chpricenotify']['notify_address'])) {
                $s_list = $this->config['chpricenotify']['notify_address'];
            } else {
                $s_list = array($this->config['chpricenotify']['notify_address']);
            }
            foreach($s_list as $subscriber_email) {
                $header = "Здравствуйте!\n";        
                $footer = "Вы получили это письмо потому что Ваш адрес указан в настройках сайта {$CONFIG['site']['display_name']} ( http://{$CONFIG['site']['name']}?from=email )
Для отказа от рассылки свяжитесь с администратором сайта по адресу {$CONFIG['site']['admin_email']}, или ответив на это письмо.";
                $this->sendMessage($pos_info, $header, $footer, $subscriber_email);
            }
        }
    }
    
    function sendMessage($pos_data, $header, $footer, $email) {
        global $CONFIG;
        $pc = \PriceCalc::getInstance();
        $default_price_name = $pc->getDefaultPriceName();
        
        $mail_text = $header;
        $mail_text .= "У некоторых товаров и услуг произошло изменение базовых цен:\n\n";
        
        foreach($pos_data as $pos_info) {
            $text_line = 'У '.$pos_info['name'];
            if($pos_info['vc']) {
                $text_line .= " ({$pos_info['vc']})";                
            }
            $pos_info['default_price'] = $pc->getPosDefaultPriceValue($pos_info['id']);
            $text_line .= "\n   ID:".str_pad($pos_info['id'], 6, ' ', STR_PAD_LEFT);
            $text_line .= " - новая цена \"$default_price_name\": {$pos_info['default_price']}\n";
            $mail_text .= $text_line;
        }
        
        $mail_text .= "\n\n\n".$footer;
        
        $email_message = new \email_message_class();
        $email_message->default_charset = "UTF-8";
        $email_message->SetEncodedEmailHeader("To", $email, $email);
        $email_message->SetEncodedHeader("Subject", 'Уведомление об изменениях цен - ' . $this->config['site']['name']);
        $email_message->SetEncodedEmailHeader("From", $this->config['site']['admin_email'], $this->config['site']['display_name']);
        $email_message->SetHeader("Sender", $this->config['site']['admin_email']);
        $email_message->SetHeader("List-id", '<' . $this->list_id . '>');
        $email_message->SetHeader("List-Unsubscribe", "http://{$CONFIG['site']['name']}/login.php?mode=unsubscribe&email={$email}&from=list_unsubscribe");
        $email_message->SetHeader("X-Multimag-version", MULTIMAG_VERSION);

        $email_message->AddQuotedPrintableTextPart($mail_text);
        $error = $email_message->Send();

        if (strcmp($error, "")) {
            throw new Exception($error);
        }
    }

}
