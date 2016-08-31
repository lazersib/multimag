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
/// Сценарий автоматизации: Расчёт и выплата зарплаты кладовщика
class ds_salary_storekeeper {

    var $pack_coeff = 1;
    var $place_coeff = 2;
    var $cnt_coeff = 2;

    function Run($mode) {
        global $tmpl, $CONFIG, $db;
        $uid = intval($_SESSION['uid']);

        if (isset($CONFIG['doc_scripts']['salary_storekeeper.pack_coeff']))
            $this->pack_coeff = $CONFIG['doc_scripts']['salary_storekeeper.pack_coeff'];
        if (isset($CONFIG['doc_scripts']['salary_storekeeper.place_coeff'])) {
            $this->place_coeff = $CONFIG['doc_scripts']['salary_storekeeper.place_coeff'];
        }
        if (isset($CONFIG['doc_scripts']['salary_storekeeper.cnt_coeff'])) {
            $this->cnt_coeff = $CONFIG['doc_scripts']['salary_storekeeper.cnt_coeff'];
        }

        $tmpl->hideBlock('left');
        if ($mode == 'view') {
            $curdate = date("Y-m-d");
            $tmpl->addContent("<h1>" . $this->getname() . "</h1>
                <script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
                <script src='/js/calendar.js'></script>
                <form action='' method='post' enctype='multipart/form-data'>
                <input type='hidden' name='mode' value='create'>
                <input type='hidden' name='param' value='i'>
                <input type='hidden' name='sn' value='salary_storekeeper'>
                Услуга начисления зарплаты:<br>
                <input type='hidden' name='tov_id' id='tov_id' value=''>
                <input type='text' id='tov'  style='width: 400px;' value=''><br>
                Рассчитывать с:<br>
                <input type='text' name='date_f' id='datepicker_f' value='$curdate'><br>
                По:<br>
                <input type='text' name='date_t' id='datepicker_t' value='$curdate'><br>
                Сотрудник:<br><select name='user_id'>");
            $res = $db->query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
            while ($nxt = $res->fetch_row())
                $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
            $tmpl->addContent("</select><br>
			Показывать:<br>
			<select name='show'>
			<option value='all'>Все</option>
			<option value='nach'>С выполненными начислениями</option>
			<option value='nonach'>С невыполненными начислениями</option>
			</select><br>
			
			<script type=\"text/javascript\">
			initCalendar('datepicker_f',false);
			initCalendar('datepicker_t',false);
			$(document).ready(function(){
				$(\"#tov\").autocomplete(\"/docs.php\", {
				delay:300,
				minChars:1,
				matchSubset:1,
				autoFill:false,
				selectFirst:true,
				matchContains:1,
				cacheLength:10,
				maxItemsToShow:15,
				formatItem:tovliFormat,
				onItemSelect:tovselectItem,
				extraParams:{'l':'sklad','mode':'srv','opt':'ac'}
				});
			});

			function tovliFormat (row, i, num) {
				var result = row[0] + \"<em class='qnt'>\" +
				row[2] + \"</em> \";
				return result;
			}

			function tovselectItem(li) {
				if( li == null ) var sValue = \"Ничего не выбрано!\";
				if( !!li.extra ) var sValue = li.extra[0];
				else var sValue = li.selectValue;
				document.getElementById('tov_id').value=sValue;

			}
			</script>
			<button type='submit'>Выполнить</button>
			</form>
			");
        } else if ($mode == 'create') {
            $tov_id = rcvint('tov_id');
            $date_f = strtotime(rcvdate('date_f'));
            $date_t = strtotime(rcvdate('date_t') . " 23:59:59");
            $user_id = rcvint('user_id');
            $show = request('show');

            $tmpl->addContent("<h1>" . $this->getname() . "</h1>");
            if (!$tov_id) {
                throw new Exception("Не указана услуга!");
            }

            $res = $db->query("SELECT `agent_id` FROM `users` WHERE `id`='$user_id'");
            if (!$res->num_rows) {
                throw new Exception("Сотрудник на найден!");
            }
            list($agent_id) = $res->fetch_row();
            if (!$agent_id) {
                $tmpl->msg("Пользователь не привязан к агенту. Вы не сможете начислить заработную плату!", 'err');
            }

            $res = $db->query("SELECT `id` FROM `doc_base_params` WHERE `codename`='pack_complexity_sk'");
            if (!$res->num_rows) {
                $db->query("INSERT INTO `doc_base_params` (`name`, `codename`, `type`, `hidden`)"
                    . " VALUES ('Сложность кладовщика', 'pack_complexity_sk', 'float', 1)");
                throw new \Exception("Параметр начисления зарплаты не был найден. Параметр создан. Перед начислением заработной платы необходимо заполнить свойства номенклатуры.");
            }
            list($param_id) = $res->fetch_row();
            

            $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`user`, `doc_agent`.`name` AS `agent_name`, `doc_list`.`date`, `doc_list`.`sum`,
                    `curusers`.`name` AS `ruser_name`, `doc_list`.`agent` AS `agent_id`, `n_data`.`value` AS `salary`, `doc_list`.`sklad` AS `store_id`
                FROM `doc_list`
                INNER JOIN `doc_agent` ON		`doc_agent`.`id`=`doc_list`.`agent`
                INNER JOIN `users` AS `curusers`	ON `curusers`.`id`=`doc_list`.`user`
                LEFT JOIN `doc_list` AS `rkolist`	ON `rkolist`.`p_doc`=`doc_list`.`id` AND `rkolist`.`type`='7'
                LEFT JOIN `doc_dopdata` AS `n_data`	ON `n_data`.`doc`=`doc_list`.`id` AND `n_data`.`param`='salary_storekeeper'
                INNER JOIN `doc_dopdata` AS `klad`	ON `klad`.`doc`=`doc_list`.`id` AND `klad`.`param`='kladovshik' AND `klad`.`value`='$user_id'
                WHERE `doc_list`.`ok`>'0' AND `doc_list`.`type`='2' AND `doc_list`.`date`>='$date_f' AND `doc_list`.`date`<='$date_t'");

            $tmpl->addContent("
                <form action='' method='post' enctype='multipart/form-data'>
                <input type='hidden' name='mode' value='exec'>
                <input type='hidden' name='param' value='i'>
                <input type='hidden' name='sn' value='salary_storekeeper'>
                <input type='hidden' name='tov_id' id='tov_id' value='$tov_id'>
                <input type='hidden' name='user_id' id='user_id' value='$user_id'>
                <table width='100%' class='list'>
                <tr><th>ID</th><th>Агент</th><th>Дата</th><th>Сумма</th><th>За товар</th><th>За места</th><th>За количество</th><th>К начислению</th><th>Начислить</th></tr>");

            $all_sum = 0; // Общая сумма по всем документам, включая те, по которым не было оплаты
            $kn_sum = 0; // Сумма к начислению
            $nd_sum = 0; // Сумма уже начисленного по документам
            $ns_sum = 0; // Сумма уже начисленного по сценарию
            $ag_sum = 0; // Сумма агентских вознаграждений
            $r_sum = 0; // Сумма реализаций

            while ($nxt = $res->fetch_assoc()) {
                // Расчёт входящей стоимости

                $res_tov = $db->query("SELECT `doc_list_pos`.`id`, `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_values`.`value`, `doc_base`.`mult`,
                        `doc_base_cnt`.`mesto`
                    FROM `doc_list_pos`
                    INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
                    LEFT JOIN `doc_base_values` ON `doc_base_values`.`id`=`doc_list_pos`.`tovar` AND `doc_base_values`.`param_id`=$param_id
                    INNER JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$nxt['store_id']}'
                    WHERE `doc_list_pos`.`doc`='{$nxt['id']}'");

                $pos_sum = 0;
                $places_sum = 0;
                $cnt_sum = 0;
                
                $a_places = array();
                $cnt = 0;
                while ($nxt_tov = $res_tov->fetch_assoc()) {
                    if(!$nxt_tov['mult']) {
                        $nxt_tov['mult'] = 1;
                    }
                    $a_places[intval($nxt_tov['mesto'])] = 1;
                    $pos_sum += $nxt_tov['value'] * $nxt_tov['cnt'] / $nxt_tov['mult'];
                    $cnt++;
                }
                $pos_sum *=  $this->pack_coeff;
                $places_sum = count($a_places)*$this->place_coeff;
                $cnt_sum = $cnt*$this->cnt_coeff;
                $nach_sum = $pos_sum + $places_sum + $cnt_sum;
                
                $pos_sum = sprintf("%0.2f", $pos_sum);
                $places_sum = sprintf("%0.2f", $places_sum);
                $cnt_sum = sprintf("%0.2f", $cnt_sum);
                $nach_sum = sprintf("%0.2f", $nach_sum);
                
                $date = date("Y-m-d", $nxt['date']);

                $out_line = "<tr>
                    <td><a href='/doc.php?mode=body&doc={$nxt['id']}'>{$nxt['id']}</a></td>
                    <td>" . html_out($nxt['agent_name']) . "</td><td>$date</td><td>{$nxt['sum']}</td><td>$pos_sum</td><td>$places_sum</td><td>$cnt_sum</td>";

                if (!$nxt['salary']) {
                    $n_check = ' checked';
                    $kn_sum += $nach_sum;

                    $out_line .= "<td><input type='text' name='sum_doc[{$nxt['id']}]' value='$nach_sum'></td>
                        <td><label><input type='checkbox' name='cb_doc[{$nxt['id']}]' value='1'$n_check>Ok</label></td></tr>";
                } else {
                    $out_line .= "<td>{$nxt['salary']}</td><td></td></tr>";
                    $nd_sum += $nxt['salary'];
                    $ns_sum += $nach_sum;
                }

                $all_sum += $nach_sum;
                $r_sum += $nxt['sum'];

                if ($show == 'nach' && $nxt['salary'])
                    $tmpl->addContent($out_line);
                else if ($show == 'nonach' && !$nxt['salary'])
                    $tmpl->addContent($out_line);
                else if ($show == 'all')
                    $tmpl->addContent($out_line);
            }
            $but_disabled = '';
            if (!$agent_id)
                $but_disabled = 'disabled';

            $tmpl->addContent("</table>
			<button $but_disabled>Начислить зарплату</button>
			</form>
			<table>
			<tr><th>К начислению</th><td>$kn_sum</td></tr>
			<tr><th>Начислено по док-там</th><td>$nd_sum</td></tr>
			<tr><th>Начислено по сценарию</th><td>$ns_sum</td></tr>
			<tr><th>Сумма</th><td>$all_sum</td></tr>
			<tr><th>Сумма реализаций</th><td>$r_sum</td></tr>
			</table>");
        } else if ($mode == 'exec') {
            $tov_id = intval($_REQUEST['tov_id']);
            $user_id = intval($_REQUEST['user_id']);

            if (!is_array($_REQUEST['sum_doc']))
                throw new Exception("Нечего начислять!");
            if (!$user_id)
                throw new Exception("Некому начислять!");
            if (!$tov_id)
                throw new Exception("Не указана услуга!");

            $res = $db->query("SELECT `agent_id` FROM `users` WHERE `id`='$user_id'");
            if (!$res->num_rows)
                throw new Exception("Сотрудник на найден!");
            list($agent_id) = $res->fetch_row();
            if (!$agent_id)
                throw new Exception("Необходимо привязать пользователя к агенту!");
            $db->startTransaction();
            $all_sum = 0;
            foreach ($_REQUEST['sum_doc'] as $doc => $sum) {
                if (!isset($_REQUEST['cb_doc'][$doc]))
                    continue;
                $sum = round($sum, 2);
                settype($doc, 'int');
                if (!$sum)
                    continue;
                $all_sum+=$sum;
                $db->query("INSERT INTO `doc_dopdata` (`doc`, `param`, `value`) VALUES ('$doc', 'salary_storekeeper', '$sum')");
            }

            $tim = time();
            $uid = $_SESSION['uid'];
            $altnum = GetNextAltNum(1, 'auto', 0, date("Y-m-d"), 1);
            $db->query("INSERT INTO `doc_list` (`date`, `firm_id`, `type`, `user`, `altnum`, `subtype`, `sklad`, `agent`, `p_doc`, `sum`)
				VALUES	('$tim', '1', '1', '$uid', '$altnum', 'auto', '1', '$agent_id', '0', '$all_sum')");
            $post_doc = $db->insert_id;
            $db->query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`) VALUES ('$post_doc', '$tov_id', '1', '$all_sum')");
            $db->commit();
            header("location: /doc.php?mode=body&doc=$post_doc");
        }
    }

    function getName() {
        return "Расчёт и выплата зарплаты кладовщика";
    }

}
