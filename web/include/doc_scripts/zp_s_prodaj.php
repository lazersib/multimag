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


/// Сценарий автоматизации: Расчёт и выплата зарплаты с продаж
class ds_zp_s_prodaj {

	var $coeff = 0.05;
        var $l_coeff = 0.5;

	function Run($mode) {
		global $tmpl, $CONFIG, $db;
                $uid = intval($_SESSION['uid']);
		if (isset($CONFIG['doc_scripts']['zp_s_prodaj.coeff']))
			$this->coeff = $CONFIG['doc_scripts']['zp_s_prodaj.coeff'];
                if (isset($CONFIG['doc_scripts']['zp_s_prodaj.l_coeff'])) {
			$this->l_coeff = $CONFIG['doc_scripts']['zp_s_prodaj.l_coeff'];
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
			<input type='hidden' name='sn' value='zp_s_prodaj'>
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
				$tmpl->addContent("<option value='$nxt[0]'>".html_out($nxt[1])."</option>");
			$tmpl->addContent("</select><br>
			Показывать:<br>
			<select name='show'>
			<option value='all'>Все</option>
			<option value='nach'>С выполненными начислениями</option>
			<option value='nonach'>С невыполненными начислениями</option>
			</select><br>
			
			Начислять зарплату:<br>
			<select name='calc'>
			<option value='z' selected>Автору заявки</option>
			<option value='r'>Автору реализации</option>
                        <option value='s'>Ответственному агента</option>
			</select><br>
                        <label><input type='checkbox' name='use_likv' value='1'>Учитывать ликвидность товара</label><br>
			
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
			$date_t = strtotime(rcvdate('date_t')." 23:59:59");
			$user_id = rcvint('user_id');
			$show = request('show');
			$calc = request('calc');
                        $use_likv = request('use_likv');

			$tmpl->addContent("<h1>" . $this->getname() . "</h1>");
			if (!$tov_id)	throw new Exception("Не указана услуга!");

			$res = $db->query("SELECT `agent_id` FROM `users` WHERE `id`='$user_id'");
			if (!$res->num_rows)	throw new Exception("Сотрудник на найден!");
			list($agent_id) = $res->fetch_row();
			if (!$agent_id)	$tmpl->msg("Пользователь не привязан к агенту. Вы не сможете начислить заработную плату!", 'err');

                        switch($calc) {
                            case 's':
                                $lock = "`doc_agent`.`responsible`=$user_id";
                                break;
                            case 'r':
                                $lock = "`curlist`.`user`=$user_id";
                                break;
                            default:
                                $lock = "`zlist`.`user`=$user_id";
                        }
                        
                        $ar_type = -1;
                        $res = $db->query("SELECT `id` FROM `doc_dtypes` WHERE `codename`='ag_fee'");
                        if($res->num_rows) {
                            list($ar_type) = $res->fetch_row();
                        } else {
                            $tmpl->msg("Вид расхода *агентское вознаграждение* (кодовое название: ag_fee) не найден. Агентские вознаграждения не учитываются.");
                        }
			
			$res = $db->query("SELECT `curlist`.`id`, `curlist`.`user`, `doc_agent`.`name` AS `agent_name`, `curlist`.`date`, `curlist`.`sum`,
                            `curusers`.`name` AS `ruser_name`, `zlist`.`user` AS `zuser`, `zusers`.`name` AS `zuser_name`, `curlist`.`p_doc`,
                            `rkolist`.`sum` AS `ag_sum`,`rbanklist`.`sum` AS `ag_bank_sum`, `curlist`.`agent` AS `agent_id`, `n_data`.`value` AS `zp_s_prodaj`,
                            `ar_type`.`value` AS `d_type`
			FROM `doc_list` AS `curlist`
			INNER JOIN `doc_agent` ON		`doc_agent`.`id`=`curlist`.`agent`
			INNER JOIN `users` AS `curusers`	ON `curusers`.`id`=`curlist`.`user`
			LEFT JOIN `doc_list` AS `zlist`		ON `zlist`.`id`=`curlist`.`p_doc` AND `zlist`.`type`='3'
			LEFT JOIN `doc_list` AS `rkolist`	ON `rkolist`.`p_doc`=`curlist`.`id` AND `rkolist`.`type`='7'
                        LEFT JOIN `doc_list` AS `rbanklist`	ON `rbanklist`.`p_doc`=`curlist`.`id` AND `rbanklist`.`type`='5'
                        LEFT JOIN `doc_dopdata` AS `ar_type`    ON (`ar_type`.`doc`=`rbanklist`.`id` OR `ar_type`.`doc`=`rkolist`.`id`) AND `ar_type`.`param` = 'rasxodi'
			LEFT JOIN `users` AS `zusers`		ON `zusers`.`id`=`zlist`.`user`
			LEFT JOIN `doc_dopdata` AS `n_data`	ON `n_data`.`doc`=`curlist`.`id` AND `n_data`.`param`='zp_s_prodaj'
			WHERE `curlist`.`ok`>'0' AND `curlist`.`type`='2' AND `curlist`.`date`>='$date_f' AND `curlist`.`date`<='$date_t'
			AND $lock");

			$tmpl->addContent("
			<form action='' method='post' enctype='multipart/form-data'>
			<input type='hidden' name='mode' value='exec'>
			<input type='hidden' name='param' value='i'>
			<input type='hidden' name='sn' value='zp_s_prodaj'>
			<input type='hidden' name='tov_id' id='tov_id' value='$tov_id'>
			<input type='hidden' name='user_id' id='user_id' value='$user_id'>
			<table width='100%' class='list'>
			<tr><th>ID</th><th>Заявка</th><th>Реализация</th><th>Агент</th><th>Дата</th><th>Сумма</th><th>Агентские</th><th>К начислению</th><th>Начислить</th></tr>");

			$all_sum = 0;	// Общая сумма по всем документам, включая те, по которым не было оплаты
			$no_sum = 0;	// Сумма не оплаченных документов, по которым не было начислений
			$kn_sum = 0;	// Сумма к начислению
			$nd_sum = 0;	// Сумма уже начисленного по документам
			$ns_sum = 0;	// Сумма уже начисленного по сценарию
			$ag_sum = 0;	// Сумма агентских вознаграждений
			$r_sum = 0;	// Сумма реализаций
                        $old_date = '';
                        
			while ($nxt = $res->fetch_assoc()) {
                            if($use_likv && date("Ymd", $nxt['date'])!=$old_date ) {
                                $a_likv = getLiquidityOnDate($nxt['date'] - 1);
                                $old_date = date("Ymd", $nxt['date']);
                            }
                            if($nxt['d_type']==$ar_type) {
                                $nxt['ag_sum'] = sprintf("%0.2f", $nxt['ag_sum']+$nxt['ag_bank_sum']);
                            } else {
                                $nxt['ag_sum'] = '';
                            }
                            // Расчёт входящей стоимости
                            $res_tov = $db->query("SELECT `doc_list_pos`.`id`, `doc_list_pos`.`tovar`, `doc_list_pos`.`cost`, `doc_list_pos`.`cnt`
                                    FROM `doc_list_pos`
                                    WHERE `doc_list_pos`.`doc`='{$nxt['id']}'");
                            $nach_sum = 0;
                            while ($nxt_tov = $res_tov->fetch_assoc()) {
                                $incost = getInCost($nxt_tov['tovar'], $nxt['date']);
                                if($use_likv && isset($a_likv[$nxt_tov['tovar']])) {
                                    $nach_sum += ($nxt_tov['cost'] - $incost) * $this->coeff * $nxt_tov['cnt'] * (1 - $a_likv[$nxt_tov['tovar']]*$this->l_coeff/100 );
                                } else {
                                    $nach_sum += ($nxt_tov['cost'] - $incost) * $this->coeff * $nxt_tov['cnt'];
                                }
                            }
                            $nach_sum -= ($nxt['ag_sum']*$this->coeff);
                            $nach_sum = sprintf("%0.2f", $nach_sum);
                            // Проверка факта оплаты
                            $add = '';
                            if ($nxt['p_doc'])
                                    $add = " OR `p_doc`='{$nxt['p_doc']}'";
                            $rs = $db->query("SELECT SUM(`sum`) FROM `doc_list`
                                    WHERE (`p_doc`='{$nxt['id']}' $add) AND (`type`='4' OR `type`='6') AND `ok`>0");

                            $ok_pay = 0;
                            if($rs->num_rows) {
                                    $pp = $rs->fetch_row();
                                    $prop = round($pp[0], 2);
                                    if ($prop >= $nxt['sum']) $ok_pay = 1;

                            }
                            if (agentCalcDebt($nxt['agent_id']) <= 0)  $ok_pay = 1;

                            $date = date("Y-m-d H:i:s", $nxt['date']);

                            $cl = $ok_pay?'f_green':'f_red';

                            $out_line = "<tr class='$cl'>
                                    <td><a href='/doc.php?mode=body&doc={$nxt['id']}'>{$nxt['id']}</a></td>
                                    <td>".html_out($nxt['zuser_name'])."</td><td>".html_out($nxt['ruser_name'])."</td>
                                    <td>".html_out($nxt['agent_name'])."</td><td>$date</td><td>{$nxt['sum']} / $prop</td><td>{$nxt['ag_sum']}</td><td>";

                            if (!$nxt['zp_s_prodaj']) {
                                    if($ok_pay) {
                                            $n_check = ' checked';
                                            $kn_sum += $nach_sum;
                                    }
                                    else {
                                            $n_check = '';
                                            $no_sum += $nach_sum;
                                    }
                                    $out_line .= "<input type='text' name='sum_doc[{$nxt['id']}]' value='$nach_sum'></td>
                                            <td><label><input type='checkbox' name='cb_doc[{$nxt['id']}]' value='1'$n_check>Ok</label></td></tr>";

                            }
                            else {
                                    $out_line .= "{$nxt['zp_s_prodaj']}</td><td></td></tr>";
                                    $nd_sum += $nxt['zp_s_prodaj'];
                                    $ns_sum += $nach_sum;
                            }

                            $all_sum += $nach_sum;
                            $ag_sum += $nxt['ag_sum'];
                            $r_sum += $nxt['sum'];

                            if($show == 'nach' && $nxt['zp_s_prodaj'])		$tmpl->addContent($out_line);
                            else if($show == 'nonach' && !$nxt['zp_s_prodaj'])	$tmpl->addContent($out_line);
                            else if($show == 'all')					$tmpl->addContent($out_line);
			}
			$but_disabled = '';
			if (!$agent_id)	$but_disabled = 'disabled';

			$tmpl->addContent("</table>
			<button $but_disabled>Начислить зарплату</button>
			</form>
			<table>
			<tr><th>К начислению</th><td>$kn_sum</td></tr>
			<tr><th>Не оплачено</th><td>$no_sum</td></tr>
			<tr><th>Агентские</th><td>$ag_sum</td></tr>
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
			if (!$res->num_rows)	throw new Exception("Сотрудник на найден!");
			list($agent_id) = $res->fetch_row();
			if (!$agent_id)	throw new Exception("Необходимо привязать пользователя к агенту!");
			$db->startTransaction();
			$all_sum = 0;
			foreach ($_REQUEST['sum_doc'] as $doc => $sum) {
				if(!isset($_REQUEST['cb_doc'][$doc]))	continue;
				$sum = round($sum, 2);
				settype($doc, 'int');
				if (!$sum)	continue;
				$all_sum+=$sum;
				$db->query("INSERT INTO `doc_dopdata` (`doc`, `param`, `value`) VALUES ('$doc', 'zp_s_prodaj', '$sum')");
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
		return "Расчёт и выплата зарплаты с продаж";
	}

}
