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

namespace actions;

require_once($CONFIG['location'] . '/common/email_message.php');
require_once($CONFIG['location'] . '/web/include/doc.core.php');

/// Информирование о неверных ценах наименований при помощи email
class BadPriceNotify extends \Action {

    /// @brief Запустить
    public function run() {
        if(!@$this->config['badpricenotify']['threshold_low'] && !@$this->config['badpricenotify']['threshold_high']) {
            return;
        }
        $t_l = @$this->config['badpricenotify']['threshold_low'];
        $t_h = @$this->config['badpricenotify']['threshold_high'];
        
        $notify = 0;
        $docadm_notify = array();
        $users_notify = array();
        $last_users = array();
        $res = $this->db->query("SELECT `object_id`, `user` AS `user_id` FROM `doc_log` WHERE `object`='pos' ORDER BY `time` DESC");
        while($line = $res->fetch_assoc()) {
            if(!isset($last_users[$line['object_id']])) {
                $last_users[$line['object_id']] = $line['user_id'];
            }
        }
        
        $res = $this->db->query("SELECT `id`, `cost` AS `price`, `name`, `vc` FROM `doc_base` WHERE `pos_type`=0");
        while($line = $res->fetch_assoc()) {
            $line['in_price'] = \getInCost($line['id']);
            if($line['in_price']>0) {
                $x = round($line['price'] / $line['in_price'] * 100);
                if( $t_l>0 && $x<100 && ( (100-$x)>=$t_l ) ) { // threshold low
                    $n_info = array('id'=>$line['id'], 'type'=>'l', 'value'=>100-$x, 
                        'price'=>$line['price'], 'in_price'=>$line['in_price'], 
                        'name'=>$line['name'], 'vc'=>$line['vc']);
                    $docadm_notify[] = $n_info;
                    if(isset($last_users[$line['id']])) {
                        $users_notify[$last_users[$line['id']]][] = $n_info;
                    }
                }
                if( $t_h>0 && $x>100 && ( ($x-100)>=$t_h ) ) { // threshold high
                    $n_info = array('id'=>$line['id'], 'type'=>'h', 'value'=>$x-100, 
                        'price'=>$line['price'], 'in_price'=>$line['in_price'], 
                        'name'=>$line['name'], 'vc'=>$line['vc']);
                    $docadm_notify[] = $n_info;
                    if(isset($last_users[$line['id']])) {
                        $users_notify[$last_users[$line['id']]][] = $n_info;
                    }
                }
            }
        }
                
        // Получить название фирмы, от которой выполняется рассылка
        $res = $this->db->query("SELECT `firm_name` FROM `doc_vars` WHERE `id`='{$this->config['site']['default_firm']}'");
        list($firm_name) = $res->fetch_row();
        
        if(count($docadm_notify)>0) {
            $header = "Здравствуйте, Администратор документов!\n";        
            $header .= "Как сотрудника интернет-магазина http://{$this->config['site']['name']} и компании $firm_name, информирую о следующем:\n\n";

            $footer = "Вы получили это письмо потому что Ваш email установлен как адрес администратора документов в настройках сайта {$this->config['site']['display_name']} ( http://{$this->config['site']['name']})\nИзмените адрес в настройках, если не хотите получать подобные уведомления.";
            $this->sendMessage($docadm_notify, $header, $footer, $this->config['site']['doc_adm_email']);
        
                
            // Оповещаем только подписанных агентов с нефиксированной ценой, у которых были специальные цены в предыдущем периоде
            $res = $this->db->query("SELECT `user_id`, `worker`, `worker_email`, `worker_real_name` FROM `users_worker_info` WHERE `worker`>0");
            while ($worker_info = $res->fetch_assoc()) {
                if(!isset($users_notify[$worker_info['user_id']])) {
                    continue;
                }
                $header = "Здравствуйте, {$worker_info['worker_real_name']}!\n";        
                $header .= "Как последнего, кто изменял перечисленные ниже наименования в интернет-магазине http://{$this->config['site']['name']}, информирую о следующем:\n\n";

                $footer = "Вы получили это письмо потому что являетесь сотрудником компании $firm_name, обслуживающей интернет-магазин {$this->config['site']['display_name']} ( http://{$this->config['site']['name']})\nЧтобы перестать получать уведомления, Вам нужно исправить цены, либо перестать быть сотрудником компании $firm_name.";
                $this->sendMessage($users_notify[$worker_info['user_id']], $header, $footer, $worker_info['worker_email']);
            }
        }
        
    }
    
    function sendMessage($pos_data, $header, $footer, $email) {
        
        $mail_text = $header;
        $mail_text .= "У следующих складских наименований, базовая цена выходит за допустимые пределы:\n\n";
        
        foreach($pos_data as $pos_info) {
            $text_line = 'Для наименования '.$pos_info['name'];
            if($pos_info['vc']) {
                $text_line .= " ({$pos_info['vc']})";                
            }
            $text_line .= "\n   ID:".str_pad($pos_info['id'], 6, ' ', STR_PAD_LEFT);
            $text_line .= ' - цена ' . ($pos_info['type']=='l'?'меньше':'больше') . ' актуальной';
            $text_line .= " на {$pos_info['value']}%:";
            $text_line .= "\n   базовая:{$pos_info['price']}, актуальная:{$pos_info['in_price']}\n";
            $mail_text .= $text_line;
        }
        
        $mail_text .= "\n\n\n".$footer;
        
        $email_message = new \email_message_class();
        $email_message->default_charset = "UTF-8";
        $email_message->SetEncodedEmailHeader("To", $email, $email);
        $email_message->SetEncodedHeader("Subject", 'Уведомление о проблемах с ценами - ' . $this->config['site']['name']);
        $email_message->SetEncodedEmailHeader("From", $this->config['site']['admin_email'], $this->config['site']['display_name']);
        $email_message->SetHeader("Sender", $this->config['site']['admin_email']);
        $email_message->SetHeader("X-Multimag-version", MULTIMAG_VERSION);

        $email_message->AddQuotedPrintableTextPart($mail_text);
        $error = $email_message->Send();

        if (strcmp($error, "")) {
            throw new Exception($error);
        }
    }

}
