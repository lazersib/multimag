<?php

//      MultiMag v0.2 - Complex sales system
//
//      Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
//
//      This program is free software: you can redistribute it and/or modify
//      it under the terms of the GNU Affero General Public License as
//      published by the Free Software Foundation, either version 3 of the
//      License, or (at your option) any later version.
//
//      This program is distributed in the hope that it will be useful,
//      but WITHOUT ANY WARRANTY; without even the implied warranty of
//      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//      GNU Affero General Public License for more details.
//
//      You should have received a copy of the GNU Affero General Public License
//      along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

namespace actions;

require_once($CONFIG['location'] . '/web/include/doc.core.php');

/// Информирование о красных событиях в документах при помощи email
class redEventDocNotify extends \Action {
    
    /// Конструктор
    public function __construct($config, $db) {
        parent::__construct($config, $db);
        $this->interval = self::DAILY;
    }

    /// Получить название действия
    public function getName() {
        return "Информирование о красных событиях в документах";
    }    
    
    /// Проверить, разрешен ли периодический запуск действия
    public function isEnabled() {
        return \cfg::get('auto', 'red_event_doc_notify');
    }
        
    /// @brief Запустить
    public function run() {
        $start_date_ut = time()-60*60*24;
        $start_date = date("Y-m-d H:i:s", $start_date_ut);
        $message = '';
        $res_dl = $this->db->query("SELECT DISTINCT `doc_log`.`object_id` AS `id`, `doc_list`.`ok`, `doc_list`.`date`"
            . " FROM `doc_log`"
            . " LEFT JOIN `doc_list` ON `doc_log`.`object_id`=`doc_list`.`id`"
            . " WHERE `object`='doc' AND `time`>='$start_date'");
        while($line_dl = $res_dl->fetch_assoc()) {
            if(!$line_dl['ok']) { // Не информировать о непроведённых
                continue;
            }
            $has_apply = false; // Проводился ли
            $as_flag = false;
            $d_flag = false;
            $res = $this->db->query("SELECT `user`, `motion`, `desc`, `time`"
                . " FROM `doc_log`"
                . " WHERE `object`='doc' AND `object_id`='{$line_dl['id']}'"
                . " ORDER BY `id`");
            while($line = $res->fetch_assoc()) {
                if($line['motion']=='APPLY') {
                    $has_apply = true;
                }
                if($start_date_ut > strtotime($line['time'])) {
                    continue;
                }
                if($line['motion']!='UPDATE' && $line['motion']!='APPLY') {
                    continue;
                }
                $d_flag = true;
                $desc_obj = json_decode($line['desc'],true);
                if(!$desc_obj) {
                    continue;
                }
                if(isset($desc_obj['agent']) || isset($desc_obj['sum'])) {
                    $as_flag = true;
                    break;
                }                
            }
            $str = '';
            if(date('Y-m-d', $line_dl['ok'])!=date('Y-m-d', $line_dl['date'])) {
                $str.= " - от ".date('Y-m-d', $line_dl['date'])." проведён не текущей датой (".date('Y-m-d', $line_dl['ok']).")\n";
            }
            if($as_flag) {
                $str.= " - изменён агент или сумма\n";
            }
            if($str && $d_flag) {
                $message .= "Красное событие в документе:{$line_dl['id']}\n".$str."\n";
            }
        }
        $email = \cfg::get('red_event_doc_notify', 'email',
            \cfg::get('site', 'doc_adm_email',
                \cfg::get('site', 'admin_email')
            )
        );
        
        if($message) {
            $this->sendMessage($message, $email);
        }
    }
    
    function sendMessage($text, $email) {        
        $mail_text = "В некоторых документах найдены красные события:\n\n" . $text;
        
        $email_message = new \email_message();
        $email_message->default_charset = "UTF-8";
        $email_message->SetEncodedEmailHeader("To", $email, $email);
        $email_message->SetEncodedHeader("Subject", 'Уведомление о красных событиях в документах - ' . \cfg::get('site', 'name'));
        $email_message->SetEncodedEmailHeader("From", \cfg::get('site', 'admin_email'), \cfg::get('site', 'display_name'));
        $email_message->SetHeader("Sender", \cfg::get('site', 'admin_email'));
        $email_message->SetHeader("X-Multimag-version", MULTIMAG_VERSION);

        $email_message->AddQuotedPrintableTextPart($mail_text);
        $error = $email_message->Send();

        if (strcmp($error, "")) {
            throw new \Exception($error);
        }
    }

}
