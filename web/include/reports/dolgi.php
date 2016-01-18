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

/// Отчёт по задолженностям по агентам
class Report_Dolgi extends BaseReport {

    function getName($short = 0) {
        if ($short) {
            return "По задолженностям агентов";
        } else {
            return "Отчёт по задолженностям по агентам";
        }
    }

    function Form() {
        global $tmpl, $db;
        $curdate = date("Y-m-d");
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
		<form action=''>
		<input type='hidden' name='mode' value='dolgi'>
		<input type='hidden' name='opt' value='ok'>
		Дата:<br>
		<input type='text' name='date' id='date' value='$curdate'><br>
		Организация:<br>
		<select name='firm_id'>
		<option value='0'>--все--</option>");
        $fres = $db->query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `id`");
        while ($nxt = $fres->fetch_row()) {
            $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select><br>
		Группа агентов:<br>
		<select name='agroup'>
		<option value='0'>--все--</option>");
        $res = $db->query("SELECT `id`, `name` FROM `doc_agent_group` ORDER BY `name`");
        while ($nxt = $res->fetch_row()) {
            $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select>
                <br>
		Ответственный:<br>
		<select name='resp_id'>
		<option value='0'>--все--</option>");
        $res = $db->query("SELECT `user_id` AS `id`, `worker_real_name` AS `name` FROM `users_worker_info`"
            . " WHERE `worker`>0 ORDER BY `worker_real_name`");
        while ($nxt = $res->fetch_row()) {
            $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select><br>
		<fieldset><legend>Вид задолженности</legend>
		<label><input type='radio' name='vdolga' value='1' checked>Нам должны</label><br>
		<label><input type='radio' name='vdolga' value='2'>Мы должны</label>
		</fieldset><br>
		Формат: <select name='opt'><option>pdf</option><option>html</option></select><br>
		<button type='submit'>Сформировать</button></form>
		<script>
		initCalendar('date',false);
		</script>");
    }

    function Make($engine) {
        global $db;
        $vdolga = request('vdolga');
        $agroup = rcvint('agroup');
        $firm_id = rcvint('firm_id');
        $resp_id = rcvint('resp_id');
        $date = intval(strtotime(request('date'))); // Для безопасной передачи в БД
        $this->loadEngine($engine);

        $date_p = date("Y-m-d", $date);
        $date = strtotime($date_p . ' 23:59:59');

        if ($vdolga == 2) {
            $header = "Информация по нашей задолженности на $date_p от " . date('d.m.Y');
        } else {
            $header = "Информация о задолженности перед нашей организацией на $date_p от " . date('d.m.Y');
        }
        $this->header($header);

        $widths = array(4, 34, 12, 10, 10, 10, 10, 10);
        $headers = array('N', 'Агент', 'Отв.', 'Дата сверки', 'Сумма', 'Просрочка', 'Дата посл. касс. док-та', 'Дата посл. банк. док-та');
        $this->tableBegin($widths);
        $this->tableHeader($headers);

        $sql_add = $agroup ? " AND `group`='$agroup'" : '';
        $sql_add .= $resp_id ? " AND `responsible`='$resp_id'" : '';
        $res = $db->query("SELECT `id` AS `agent_id`, `name`, `data_sverki`, `responsible`
            FROM `doc_agent` 
            WHERE 1 $sql_add ORDER BY `name`");
        $date_limit = " AND `date`<=$date";
        $i = 0;
        $sum_dolga = 0;
        $users_ldo = new \Models\LDO\usernames();
        $usernames = $users_ldo->getData();
        while ($nxt = $res->fetch_array()) {
            $dolg = $this->agentCalcDebt($nxt[0], $firm_id, $date);
            if ((($dolg['debt'] > 0) && ($vdolga == 1)) || (($dolg['debt'] < 0) && ($vdolga == 2))) {
                $d_res = $db->query("SELECT `date` FROM `doc_list`
                        WHERE `agent`={$nxt['agent_id']} AND (`type`=4 OR `type`=5) $date_limit ORDER BY `date` DESC LIMIT 1");
                if ($d_res->num_rows) {
                    list($k_date) = $d_res->fetch_row();
                } else {
                    $k_date = '';
                }
                $d_res = $db->query("SELECT `date` FROM `doc_list`
                        WHERE `agent`={$nxt['agent_id']} AND (`type`=6 OR `type`=7) $date_limit ORDER BY `date` DESC LIMIT 1");
                if ($d_res->num_rows) {
                    list($b_date) = $d_res->fetch_row();
                } else {
                    $b_date = '';
                }

                $i++;
                $sum_dolga += abs($dolg['debt']);
                $debt_p = number_format(abs($dolg['debt']), 2, '.', ' ');
                $delinquency_p = number_format(abs($dolg['delinquency']), 2, '.', ' ');
                $k_date = $k_date ? date("Y-m-d", $k_date) : '';
                $b_date = $b_date ? date("Y-m-d", $b_date) : '';
                $this->tableRow(array($i, $nxt[1], @$usernames[$nxt[3]], $nxt[2], $debt_p, $delinquency_p,  $k_date, $b_date));
            }
        }
        $sum_dolga_p = number_format($sum_dolga, 2, '.', ' ');

        $this->tableAltStyle(true);
        $this->tableSpannedRow(array(6), array("Итого: $i должников с общей суммой долга $sum_dolga_p  руб.\n" . num2str($sum_dolga) . ")"));
        $this->tableAltStyle(false);
        $this->tableEnd();
        $this->output();
        exit(0);
    }

    /// Расчёт долга агента и просрочки платежа
    /// @param $agent_id	ID агента, для которого расчитывается баланс
    /// @param $no_cache	Не брать данные расчёта из кеша
    /// @param $firm_id	ID собственной фирмы, для которой будет расчитан баланс. Если 0 - расчёт ведётся для всех фирм.
    /// @param $local_db	Дескриптор соединения с базой данных. Если не задан - используется глобальная переменная.
    /// @param $date	Дата, на которую расчитывается долг
    function agentCalcDebt($agent_id, $firm_id = 0, $date = 0) {
        global $db;//, $doc_agent_dolg_cache_storage;
        //if(!$no_cache && isset($doc_agent_dolg_cache_storage[$agent_id]))	return $doc_agent_dolg_cache_storage[$agent_id];
        settype($agent_id, 'int');
        settype($firm_id, 'int');
        settype($date, 'int');
        $debt = $defer = $delinquency = 0;
        
        $res = $db->query("SELECT `doc_list`.`id`, `doc_dopdata`.`value` AS `defer`"
            . " FROM `doc_list`"
            . " LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='deferment'"
            . " WHERE `ok`>'0' AND `agent`='$agent_id' AND `mark_del`='0' AND `type`=14 ORDER BY `date` DESC LIMIT 1");
        while($line = $res->fetch_assoc()) {
            $defer = $line['defer'];
        }
        
        $query = "SELECT `type`, `sum`, `date` FROM `doc_list` WHERE `ok`>'0' AND `agent`='$agent_id' AND `mark_del`='0'";
        if ($firm_id) {
            $query .= " AND `firm_id`='$firm_id'";
        }
        if ($date) {
            $query .= " AND `date`<=$date";
        }
        $qv = new \ValueQueue();
        $res = $db->query($query);
        while ($nxt = $res->fetch_assoc()) {
            switch ($nxt['type']) {
                case 1:
                case 4: 
                case 6:
                    $debt-=$nxt['sum'];
                    $qv->remove($nxt['sum']);
                    break;
                case 2:
                case 5:
                case 7:
                case 18:
                    $debt+=$nxt['sum'];
                    if($debt>0)
                    $qv->append(min($nxt['sum'],$debt), $nxt['date']);
                    break;
            }
        }
        $res->free();
        $cont = $qv->getContainer();
        $time_limit = time() - $defer*60*60*24;
        foreach ($cont as $cv) {
            if($cv['data']<$time_limit) {
                $delinquency+=$cv['value'];
            }
        }
        
        return ['debt'=>$debt, 'delinquency' => $delinquency];
    }
}

