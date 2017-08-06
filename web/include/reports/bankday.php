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
/// Отчёт по банку
class Report_BankDay extends BaseReport {

    function getName($short = 0) {
        if ($short) {
            return "По банку";
        } else {
            return "Отчёт по банку";
        }
    }

    function Form() {
        global $tmpl, $db;
        $curdate = date("Y-m-d");
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <form action=''>
            <input type='hidden' name='mode' value='bankday'>
            <input type='hidden' name='opt' value='ok'>
            Выберите Банк:<br>
            <select name='bank'>");
        $res = $db->query("SELECT `num`, `name`, `rs`, `firm_id` FROM `doc_kassa` WHERE `ids`='bank'  ORDER BY `num`");
        while ($nxt = $res->fetch_row()) {
            if(!\acl::testAccess([ 'firm.global', 'firm.'.$nxt[3]], \acl::VIEW)) {
                continue;
            }
            $tmpl->addContent("<option value='$nxt[0]'>" . html_out("$nxt[1] ($nxt[2])") . "</option>");
        }
        $tmpl->addContent("</select><br>
		Начальная дата:<br>
		<input type='text' name='date_f' id='datepicker_f' value='$curdate'><br>
		Конечная дата:<br>
		<input type='text' name='date_t' id='datepicker_t' value='$curdate'><br>
		Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Сформировать</button></form>
		<script type=\"text/javascript\">
		initCalendar('datepicker_f',false);
		initCalendar('datepicker_t',false);
		</script>
		");
    }

    function Make($engine) {
        global $db;
        $this->loadEngine($engine);

        $dt_f = rcvdate('date_f');
        $dt_t = rcvdate('date_t');
        $bank = rcvint('bank');

        $daystart = strtotime("$dt_f 00:00:00");
        $dayend = strtotime("$dt_t 23:59:59");
        $print_df = date('Y-m-d', $daystart);
        $print_dt = date('Y-m-d', $dayend);

        if (!$bank) {
            throw new Exception("Банк не выбран");
        }
        $bres = $db->query("SELECT `num`, `name`, `rs`, `firm_id` FROM `doc_kassa` WHERE `ids`='bank' AND `num`=$bank");
        if (!$bres->num_rows) {
            throw new Exception("Банк не найден");
        }
        $bank_info = $bres->fetch_assoc();
        \acl::accessGuard([ 'firm.global', 'firm.'.$bank_info['firm_id']], \acl::VIEW);
        
        $this->header($this->getName() . ' ' . $bank_info['name'] . " с $print_df по $print_dt");

        $widths = array(6, 12, 46, 12, 12, 12);
        $headers = array('ID', 'Дата', 'Документ', 'Приход', 'Расход', 'В банке');
        $this->tableBegin($widths);
        $this->tableHeader($headers);

        $doc_res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`sum`, `doc_list`.`date`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_types`.`name`, `doc_agent`.`name`, `doc_list`.`p_doc`, `t`.`name`, `p`.`altnum`, `p`.`subtype`, `p`.`date`, `p`.`sum`, `doc_list`.`bank`
		FROM `doc_list`
		LEFT JOIN `doc_agent`		ON `doc_agent`.`id` = `doc_list`.`agent`
		INNER JOIN `doc_types`		ON `doc_types`.`id` = `doc_list`.`type`
		LEFT JOIN `doc_list` AS `p`	ON `p`.`id`=`doc_list`.`p_doc`
		LEFT JOIN `doc_types` AS `t`	ON `t`.`id` = `p`.`type`
		WHERE `doc_list`.`ok`>'0' AND ( `doc_list`.`type`='4' OR `doc_list`.`type`='5')
		AND `doc_list`.`bank`='$bank'
		ORDER BY `doc_list`.`date`");
        $sum = $daysum = $prix = $rasx = 0;
        $flag = 0;
        $lastdate = 0;
        while ($nxt = $doc_res->fetch_row()) {
            $lastdate = $nxt[3];
            $csum_p = $csum_r = '';
            if (!$flag && $nxt[3] >= $daystart && $nxt[3] <= $dayend) {
                $flag = 1;
                $sum_p = sprintf("%0.2f руб.", $sum);
                $this->tableAltStyle(true);
                $this->tableSpannedRow(array(5, 1), array('На начало периода', $sum_p));
                $this->tableAltStyle(false);
            }
            if ($nxt[1] == 4)
                $sum+=$nxt[2];
            else if ($nxt[1] == 5)
                $sum-=$nxt[2];
            if ($nxt[3] >= $daystart && $nxt[3] <= $dayend) {
                if ($nxt[1] == 4) {
                    $daysum+=$nxt[2];
                    $prix+=$nxt[2];
                    $csum_p = sprintf("%0.2f руб.", $nxt[2]);
                } else if ($nxt[1] == 5) {
                    $daysum-=$nxt[2];
                    $rasx+=$nxt[2];
                    $csum_r = sprintf("%0.2f руб.", $nxt[2]);
                }
                if ($nxt[8]) {
                    $sadd = "\n<i>к $nxt[9] N$nxt[10]$nxt[11] от " . date("d-m-Y H:i:s", $nxt[12]) . " на сумму " . sprintf("%0.2f руб", $nxt[13]) . "</i>";
                } else {
                    $sadd = '';
                }
                if ($nxt[1] == 4) {
                    $sadd.="\nот $nxt[7]";
                } else if ($nxt[1] == 5) {
                    $sadd.="\nдля $nxt[7]";
                }

                $dt = date("Y-m-d H:i:s", $nxt[3]);
                $sum_p = sprintf("%0.2f руб.", $sum);

                $this->tableRow(array($nxt[0], $dt, "$nxt[6] N$nxt[4]$nxt[5]   $sadd", $csum_p, $csum_r, $sum_p));
            }
        }
        if (!$flag && $lastdate <= $dayend) {
            $sum_p = sprintf("%0.2f руб.", $sum);
            $this->tableAltStyle(true);
            $this->tableSpannedRow(array(5, 1), array('На начало дня', $sum_p));
            $this->tableAltStyle(false);
        }
        if ($flag) {
            $dsum_p = sprintf("%0.2f руб.", $daysum);
            $psum_p = sprintf("%0.2f руб.", $prix);
            $rsum_p = sprintf("%0.2f руб.", $rasx);

            $this->tableAltStyle(true);
            $this->tableSpannedRow(array(3, 1, 1, 1), array('На конец периода', $psum_p, $rsum_p, $sum_p));
            $this->tableSpannedRow(array(3, 3), array('Разница за период', $dsum_p));
            $this->tableAltStyle(false);
        } else {
            $this->tableAltStyle(true);
            $this->tableSpannedRow(array(6), array('Нет данных по балансу за выбранный период'));
            $this->tableAltStyle(false);
        }

        $res = $db->query("SELECT `name` FROM `users` WHERE `id`='{$_SESSION['uid']}'");
        list($nm) = $res->fetch_row();
        $this->tableSpannedRow(array(6), array("\nCоответствие сумм подтверждаю ___________________ ($nm)\nБез подписи не действителен!"));

        $this->tableEnd();
        $this->output();
        exit(0);
    }

}
