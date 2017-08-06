<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2017, BlackLight, TND Team, http://tndproject.org
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

require_once($CONFIG['location'] . '/common/XMPPHP/XMPP.php');

/// Информирование ответственных сотрудников о задолженностях его агентов при помощи email и jabber
class respDebtNotify extends \Action {

    /// Конструктор
    public function __construct($config, $db) {
        parent::__construct($config, $db);
        $this->interval = self::DAILY;
    }

    /// Получить название действия
    public function getName() {
        return "Информирование ответственных сотрудников о задолженностях его агентов";
    }    
    
    /// Проверить, разрешен ли периодический запуск действия
    public function isEnabled() {
        return \cfg::get('auto', 'resp_debt_notify');
    }
    
    /// @brief Запустить
    public function run() {
        $mail_text = array();
        $debt_sums = array();

        $res_agents = $this->db->query("SELECT `id`, `name`, `responsible` FROM `doc_agent`
			WHERE `responsible`!=0 AND `responsible` IS NOT NULL ORDER BY `name`");

        while ($nxt = $res_agents->fetch_row()) {
            $debt = agentCalcDebt($nxt[0], 0, 0, $this->db);

            if ($debt > 0) {
                if(!isset($debt_sums[$nxt[2]])) {
                    $debt_sums[$nxt[2]] = 0;
                    $mail_text[$nxt[2]] = '';
                }
                $debt = abs($debt);
                $debt_sums[$nxt[2]]+=$debt;
                $debt_p = sprintf("%0.2f", $debt);
                $a_name = html_entity_decode($nxt[1], ENT_QUOTES, "UTF-8");
                $mail_text[$nxt[2]].="Агент $a_name (id:$nxt[0]) должен нам $debt_p рублей\n";
            }
        }
        $res_agents->free();

        if (\cfg::get('xmpp', 'host')) {
            $xmppclient = new \XMPPHP_XMPP(\cfg::get('xmpp', 'host'), \cfg::get('xmpp', 'port'), \cfg::get('xmpp','login'), \cfg::get('xmpp','pass')
                , 'MultiMag r' . MULTIMAG_REV .','. get_class($this));
        }
        $xmpp_connected = 0;

        $res = $this->db->query("SELECT `users`.`id`, `users`.`name`, `users`.`reg_email`, `users`.`jid`, `users`.`reg_email_subscribe`,
                `users`.`reg_email_confirm`, `users`.`real_name`, `users_worker_info`.`worker_email`, `users_worker_info`.`worker_jid`,
                `users_worker_info`.`worker_real_name`
            FROM `users`
            LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id`
            WHERE `users_worker_info`.`worker`>0");

        while ($nxt = $res->fetch_assoc()) {
            if (!isset($mail_text[$nxt['id']])) {
                continue;
            }
            if ($mail_text[$nxt['id']] == '') {
                continue;
            }

            $debt = sprintf("%0.2f", $debt_sums[$nxt['id']]);
            $name = $nxt['worker_real_name'];
            if (!$name) {
                $name = $nxt['real_name'];
            }
            if (!$name) {
                $name = $nxt['name'];
            }

            $text = "Уважаемый(ая) $name!\nНекоторые из Ваших клиентов, для которых Вы являетесь ответственным сотрудником, имеют непогашенные долги перед нашей компанией на общую сумму {$debt} рублей.\nНеобходимо в кратчайший срок решить данную проблему!\n\nВот список этих клиентов:\n" . $mail_text[$nxt['id']] . "\n\nПожалуйста, не откладывайте решение проблемы на длительный срок!";

            if ($nxt['worker_email']) {
                mailto($nxt['worker_email'], "Ваши долги", $text);
            } else if ($nxt['reg_email'] && $nxt['reg_email_subscribe'] && $nxt['reg_email_confirm'] == '1') {
                mailto($nxt['reg_email'], "Ваши долги", $text);
            }

            if ($nxt['worker_jid']) {
                $jid = $nxt['worker_jid'];
            } else if ($nxt['jid']) {
                $jid = $nxt['jid'];
            } else {
                $jid = '';
            }
            if ($jid && \cfg::get('xmpp', 'host')) {
                if (!$xmpp_connected) {
                    $xmppclient->connect();
                    $xmppclient->processUntil('session_start');
                    $xmppclient->presence();
                    $xmpp_connected = 1;
                }
                $xmppclient->message($jid, $text);
            }
        }
        if ($xmpp_connected) {
            $xmppclient->disconnect();
        }
    }

}
