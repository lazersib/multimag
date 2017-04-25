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
namespace Actions;

/// Информирование агентов об их накопительных скидках при помощи email
class agentDiscountNotify extends \Action {
	
    /// Конструктор
    public function __construct($config, $db) {
        parent::__construct($config, $db);
        $this->interval = self::DAILY;
        $this->depends = array(
            'agentCalcAvgsum'
        );
    }

    /// Получить название действия
    public function getName() {
        return "Информирование агентов об их накопительных скидках";
    }    
    
    /// Проверить, разрешен ли периодический запуск действия
    public function isEnabled() {
        return \cfg::get('auto', 'agent_discount_notify');
    }

    /// Проверка, запускать ли в этот день
    protected function checkRunNow() {
        $notify = false;
        $cur_day = intval(date("d"));
        $cur_month = intval(date("m"));

        // Проверка, слать ли сейчас
        switch (\cfg::get('pricecalc', 'acc_type')) {
            case 'months': // Шлём ежемесячно, 25 числа
            case 'prevmonth':
                if ($cur_day == 25) {
                    $notify = true;
                }
                break;
            case 'years': // Шлём 1 декабря
            case 'prevyear':
                if ($cur_day == 1 && $cur_month == 12) {
                    $notify = true;
                }
                break;
            case 'prevquarter':// Шлём 15 числа последнего месяца квартала
                if ($cur_day == 15 && $cur_month % 3 == 0) {
                    $notify = true;
                }
                break;
            case 'prevhalfyear':// Шлём 15 числа последнего месяца полугодия
                if ($cur_day == 15 && $cur_month % 6 == 0) {
                    $notify = true;
                }
                break;
        }
        return $notify;
    }
    
    /// Расчёт дат для выборки
    protected function calculatePeriod() {
        $cnt = intval(\cfg::get('pricecalc', 'acc_time'));
        $this->print_period = '';
        
        $di = new \DateCalcInterval();
        switch (\cfg::get('pricecalc', 'acc_type')) {
            case 'months':
                $di->calcXMonthsBack($cnt);
                $this->print_period = "последние $cnt месяц(ев)";
                $this->start_period = $di->start;
                break;
            case 'years':
                $di->calcXYearsBack($cnt);
                $this->print_period = "последние $cnt лет";
                $this->start_period = $di->start;
                break;
            case 'prevmonth':
                $di->calcPrevMonth();
                $this->print_period = "текущий месяц";
                $this->start_period = $di->end;
                break;
            case 'prevquarter':
                $di->calcPrevQuarter();
                $this->print_period = "текущий квартал";
                $this->start_period = $di->end;
                break;
            case 'prevhalfyear':
                $di->calcPrevHalfyear();
                $this->print_period = "текущее полугодие";
                $this->start_period = $di->end;
                break;
            case '':break;
            case 'prevyear':
            default:
                $di->calcPrevYear();
                $this->print_period = "текущий год";
                $this->start_period = $di->end;
        }
    }

    /// @brief Запустить
    public function run() {
        if(!$this->checkRunNow()) {
            return;
        }
        $this->calculatePeriod();
        
        // Получение суммы расчётов за текущий период
        $cur_acc = array();
        $res = $this->db->query("SELECT `agent`, `sum` FROM `doc_list` WHERE `date`>='{$this->start_period}'
			AND (`type`='1' OR `type`='4' OR `type`='6') AND `ok`>0 AND `agent`>0 AND `sum`>0");
        while ($line = $res->fetch_assoc()) {
            if (isset($cur_acc[$line['agent']])) {
                $cur_acc[$line['agent']] += $line['sum'];
            } else {
                $cur_acc[$line['agent']] = $line['sum'];
            }
        }

        // Получение списка цен
        $bulk_prices = array();
        $res = $this->db->query("SELECT `id`, `name`, `type`, `value`, `context`, `priority`, `accuracy`, `direction`,
                        `bulk_threshold`, `acc_threshold`
                    FROM `doc_cost` ORDER BY `priority`");
        while ($line = $res->fetch_assoc()) {
            $contexts = str_split($line['context']);
            foreach ($contexts as $context) {
                if ($context == 'b') { // bulk
                    $bulk_prices[] = $line;
                    break;
                }
            }
        }

        // Получить название фирмы, от которой выполняется рассылка
        $default_firm_id = \cfg::get('site', 'default_firm');
        $site_name = \cfg::get('site', 'name');
        $site_display_name = \cfg::get('site', 'display_name');
        $admin_email = \cfg::get('site', 'admin_email');
        
        $res = $this->db->query("SELECT `firm_name` FROM `doc_vars` WHERE `id`='{$default_firm_id}'");
        list($firm_name) = $res->fetch_row();

        // Оповещаем только подписанных агентов с нефиксированной ценой, у которых были специальные цены в предыдущем периоде
        $res = $this->db->query("SELECT `doc_agent`.`id`, `doc_agent`.`fullname`, `doc_agent`.`pfio`, `doc_agent`.`avg_sum`,"
            . " `agent_contacts`.`value` AS `email`"
            . " FROM `doc_agent`"
            . " INNER JOIN `agent_contacts` ON `agent_contacts`.`agent_id`=`doc_agent`.`id`"
            . " AND `agent_contacts`.`type`='email' AND `agent_contacts`.`no_ads`='0'"
            . " WHERE `no_mail`=0 AND `price_id`>0 AND `avg_sum`>0");
        while ($agent_info = $res->fetch_assoc()) {
            $no_notify = $next_price_sum = $cur_price_sum = 0;
            $cur_price_sum_name = $next_price_name = $sum_spec_price_name = '';
            if (!$agent_info['email']) {
                continue;
            }

            if (isset($cur_acc[$agent_info['id']])) {
                $sum = round($cur_acc[$agent_info['id']], 2);
            } else {
                $sum = 0;
            }
            $avg_sum = round($agent_info['avg_sum'], 2);

            // Получение наименования цены текущего периода и следующей цены
            foreach ($bulk_prices as $price) {
                if ($sum && $sum >= $price['acc_threshold']) {
                    $sum_spec_price_name = $price['name'];
                    break;
                }
                $next_price_name = $price['name'];
                $next_price_sum = $price['acc_threshold'];
            }

            // Получение наименования текущей цены
            foreach ($bulk_prices as $price) {
                if ($avg_sum && $avg_sum >= intval($price['acc_threshold'])) {
                    $cur_price_sum_name = $price['name'];
                    $cur_price_sum = $price['acc_threshold'];
                    break;
                }
            }

            $mail_text = 'Здравствуйте, ' . $agent_info['fullname'];
            if ($agent_info['pfio']) {
                $mail_text .= "и " . $agent_info['pfio'];
            }
            $mail_text .= "!\n\n";

            $mail_text .= "Как привилегированного клиента интернет-магазина http://{$site_name} и компании $firm_name, информируем Вас о следующем:\n\n";

            $mail_text .= "За " . $this->print_period;
            if ($sum) {
                $sum_p = number_format($sum, 2, '.', ' ');
                $mail_text .= " Ваш оборот по расчётам с нашей компанией составил $sum_p рублей";

                if ($sum_spec_price_name) {
                    $mail_text .= ", что соответствует специальной цене \"$sum_spec_price_name\"";
                }
            }
            else {
                $mail_text .= " Вы не производили расчётов с нашей компанией";
            }

            $mail_text .= ".\n";

            if ($sum < $cur_price_sum) {
                $s = number_format($cur_price_sum - $sum, 2, '.', ' ');
                $mail_text .= "Для сохранения существующей специальной цены \"$cur_price_sum_name\" Вам необходимо совершить в текущем периоде дополнительных покупок на сумму не менее $s рублей.\n";
            } else if ($next_price_sum && $cur_price_sum) {
                $s = number_format($next_price_sum - $sum, 2, '.', ' ');
                $mail_text .= "Для получения специальной цены \"$next_price_name\" Вам необходимо совершить в текущем периоде дополнительных покупок на сумму не менее $s рублей.\n";
            } else {
                $no_notify = 1;
            }

            if (!$no_notify) {
                $mail_text .= "\n\n--------\nВы получили это письмо потому что подписаны на уведомления сайта"
                    . " {$site_display_name} ( http://{$site_name}?from=email ), и являетесь клиентом $firm_name.\n"
                    . "Отказаться от оповещений, и других рассылок можно, перейдя по ссылке "
                    . "http://{$site_name}/login.php?mode=unsubscribe&email={$agent_info['email']}&from=email\n";
                $list_id = 'adn.' . date("dmY") . '.' . $site_name;

                $email_message = new \email_message();
                $email_message->default_charset = "UTF-8";
                $email_message->SetEncodedEmailHeader("To", $agent_info['email'], $agent_info['email']);
                $email_message->SetEncodedHeader("Subject", 'Уведомление о скидках - ' . $site_name);
                $email_message->SetEncodedEmailHeader("From", $admin_email, $site_display_name);
                $email_message->SetHeader("Sender", $admin_email);
                $email_message->SetHeader("List-id", '<' . $list_id . '>');
                $email_message->SetHeader("List-Unsubscribe", "http://{$site_name}/login.php?mode=unsubscribe&email={$agent_info['email']}&from=list_unsubscribe");
                $email_message->SetHeader("X-Multimag-version", MULTIMAG_VERSION);

                $email_message->AddQuotedPrintableTextPart($mail_text);
                $error = $email_message->Send();

                if (strcmp($error, "")) {
                    throw new \Exception($error);
                }
            }
        }
    }

}
