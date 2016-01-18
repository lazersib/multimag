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
// Невыполненные заявки
include_once("core.php");
include_once("include/doc.core.php");

function getPaySum($doc_id) {
    global $db;
    settype($doc_id, 'int');

    $docs = array($doc_id);
    $sum = 0;

    while (count($docs)) {
        $cur_doc = array_pop($docs);
        $res = $db->query("SELECT `id`, `sum`, `type`, `ok` FROM `doc_list` WHERE `p_doc`='$cur_doc'");

        while ($line = $res->fetch_assoc()) {
            array_push($docs, $line['id']);
            if ($line['type'] != 4 && $line['type'] != 6) {
                continue;
            }
            if ($line['ok'] == 0) {
                continue;
            }
            $sum += $line['sum'];
        }
    }
    return round($sum, 2);
}

need_auth();
\acl::accessGuard('service.orders', \acl::VIEW);

$r_status_list = array('no'=>'-не задан-', 'new'=>'Новый', 'in_process'=>'В процессе', 'readytomake' => 'Готов к сборке', 'readytoship'=>'Собран и готов к отгрузке', 'err'=>'Ошибочный', 'ok' =>'Отгружен');
$doc_types = \document::getListTypes();

SafeLoadTemplate($CONFIG['site']['inner_skin']);

$tmpl->setTitle("Документы в работе");
doc_menu();

$sel = array('z' => '', 'c' => '', 'p'=>'', 'r' => '');
$mode = request('mode');
if ($mode == '') {
    $mode = 'z';
}
$sel[$mode] = "class='selected'";
$tmpl->addContent("
<ul class='tabs'>
<li><a {$sel['z']} href='/incomp_orders.php'>Невыполненные заявки</a></li>
<li><a {$sel['c']} href='/incomp_orders.php?mode=c'>Реализации на сборку</a></li>
<li><a {$sel['p']} href='/incomp_orders.php?mode=p'>Реализации в процессе сборки</a></li>
<li><a {$sel['r']} href='/incomp_orders.php?mode=r'>Реализации, готовые к отгрузке</a></li>
</ul>");

if ($mode == 'z') {
    $resp = rcvint('resp');
    if($resp) {
        $where_add = " AND `doc_agent`.`responsible` = $resp";
    }
    else {
        $where_add = '';
    }
    $sql = "SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`,  `doc_list`.`user`,  `doc_list`.`firm_id`, `doc_list`.`type`,
            `doc_agent`.`name` AS `agent_name`, `doc_list`.`sum`, `authors`.`name` AS `user_name`, `doc_types`.`name`, `doc_list`.`p_doc`,
            `dop_delivery`.`value` AS `delivery`, `dop_delivery_date`.`value` AS `delivery_date`, `dop_status`.`value` AS `status`,
            `dop_pay`.`value` AS `pay_type`, `doc_ishop`.`value` AS `ishop`, `delivery_types`.`name` AS `delivery_name`,
            `delivery_regions`.`name` AS `region_name`, `r_list`.`id` AS `r_id`, `resp`.`name` AS `resp_name`
	FROM `doc_list`
	LEFT JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
        LEFT JOIN `users` AS `resp` ON `resp`.`id`=`doc_agent`.`responsible`
	LEFT JOIN `users` AS `authors` ON `authors`.`id`=`doc_list`.`user`
	LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
	LEFT JOIN `doc_dopdata` AS `dop_delivery` ON `dop_delivery`.`doc`=`doc_list`.`id` AND `dop_delivery`.`param`='delivery'
	LEFT JOIN `doc_dopdata` AS `dop_delivery_date` ON `dop_delivery_date`.`doc`=`doc_list`.`id` AND `dop_delivery_date`.`param`='delivery_date'
	LEFT JOIN `doc_dopdata` AS `dop_delivery_region` ON `dop_delivery_region`.`doc`=`doc_list`.`id` AND `dop_delivery_region`.`param`='delivery_region'
	LEFT JOIN `doc_dopdata` AS `dop_status` ON `dop_status`.`doc`=`doc_list`.`id` AND `dop_status`.`param`='status'
	LEFT JOIN `doc_dopdata` AS `dop_pay` ON `dop_pay`.`doc`=`doc_list`.`id` AND `dop_pay`.`param`='pay_type'
	LEFT JOIN `doc_dopdata` AS `doc_ishop` ON `doc_ishop`.`doc`=`doc_list`.`id` AND `doc_ishop`.`param`='ishop'
	LEFT JOIN `delivery_types` ON `delivery_types`.`id` = `dop_delivery`.`value`
	LEFT JOIN `delivery_regions` ON `delivery_regions`.`id` = `dop_delivery_region`.`value`
	LEFT JOIN `doc_list` AS `r_list` ON `r_list`.`p_doc`=`doc_list`.`id` AND `r_list`.`type`=2
	WHERE `doc_list`.`type`=3 AND `doc_list`.`mark_del`=0 $where_add
	ORDER by `doc_list`.`date` ASC";

    $res = $db->query($sql);
    $row = $res->num_rows;

    $i = 0;
    $pr = $ras = 0;
    $tpr = $tras = 0;
    
    $tmpl->addContent("<form action='/incomp_orders.php' method='get'>"
        . "<input type='hidden' name='mode' value='z'>"
        . "<fieldset><legend>Фильтр</legend>"
        . "Ответственный: <select name='resp'>"
        . "<option value='0'>--не задан--</option>");
    $ldo = new \Models\LDO\workernames();
    $w_list = $ldo->getData();
    foreach($w_list as $id=>$name) {
        $sel = $id==$resp?' selected':'';
        $tmpl->addContent("<option value='$id'{$sel}>".html_out($name)."</option>");
    }
    $tmpl->addContent("</select>"
        . "<button type='submit'>отфильтровать</button>"
        . "</fieldset>"
        . "</form>");

    $tmpl->addContent("<table width='100%' cellspacing='1' class='list'>
	<tr>
	<th width='70'>№</th><th width='50'>ID</th><th width='50'>Р-я</th><th>Статус</th><th>Агент</th><th>Отв.</th><th>Сумма</th><th>Расчёт</th><th>Оплачено</th>
	<th>Доставка</th><th>Дата</th><th>С сайта</th><th>Автор</th></tr>");

    $new_lines = $inproc_lines = $other_lines = $ready_lines = '';

    while ($line = $res->fetch_assoc()) {
        if ($line['status'] == 'ok' || $line['status'] == 'err') {
            continue;
        }
        if(!\acl::testAccess('firm.'.$line['firm_id'], \acl::VIEW) || !\acl::testAccess('doc.'.$doc_types[$line['type']], \acl::VIEW)) {
            continue;
        }
        if (!$line['status']) {
            $line['status'] = 'new';
        }
        $status = @$CONFIG['doc']['status_list'][$line['status']];
        if ($line['resp_name'] == 'anonymous') {
            $line['resp_name'] = '-';
        }
        $pay_style = '';
        switch ($line['pay_type']) {
            case 'bank':
                $pay_type = "безнал.";
                $pay_style = 'color:#00c;';
                break;
            case 'cash':
                $pay_type = "нал.";
                $pay_style = 'color:#d60;';
                break;
            case 'card':
                $pay_type = "карта";
                break;
            case 'card_o':
                $pay_type = "картой на сайте";
                $pay_style = 'color:#0c0;';
                break;
            case 'card_t':
                $pay_type = "картой при получении";
                $pay_style = 'color:#c00;';
                break;
            case 'wmr':
                $pay_type = "WMR";
                break;
            case null:
                $pay_type = '-';
                break;
            default:
                $pay_type = "не определён ({$line['pay_type']})";
        }

        $date = date('Y-m-d', $line['date']);
        if ($line['delivery_name'] === null) {
            $line['delivery_name'] = '-';
        } else if ($line['region_name']) {
            $line['delivery_name'].= ' (' . html_out($line['region_name']) . ')';
        }

        $ishop = $line['ishop'] ? "<b style='color:#0c0'>Да</b>" : 'Нет';
        $link = "/doc.php?mode=body&amp;doc=" . $line['id'];

        $status_style = '';
        switch ($line['status']) {
            case 'new':
                $status_style = " style='color:#f00'";
                break;
            case 'inproc':
                $status_style = " style='color:#880'";
                break;
            case 'ready':
                $status_style = " style='color:#0c0'";
                break;
        }

        if ($line['r_id']) {
            $r_info = "<a href='/doc.php?mode=body&amp;doc={$line['r_id']}'>{$line['r_id']}</a>";
        } else {
            $r_info = '--нет--';
        }

        $pay_sum = getPaySum($line['id']);
        if (!$pay_sum) {
            $pay_sum = '-';
        } else {
            $pay_sum = number_format($pay_sum, 2, ".", " ");
        }

        $line['sum'] = number_format($line['sum'], 2, ".", " ");

        $str = "<tr><td align='right'><a href='$link'>{$line['altnum']}{$line['subtype']}</a></td><td><a href='$link'>{$line['id']}</a></td>
            <td>$r_info</td><td{$status_style}>$status</td><td>{$line['agent_name']}</td><td>{$line['resp_name']}</td><td align='right'>{$line['sum']}</td>
            <td style='$pay_style'>$pay_type</td><td>$pay_sum</td><td>{$line['delivery_name']}</td>
            <td>$date</td><td>$ishop</td><td><a href='/adm.php?mode=users&amp;sect=view&amp;user_id={$line['user']}'>{$line['user_name']}</a></td>
            </tr>";

        switch ($line['status']) {
            case 'new':
                $new_lines .= $str;
                break;
            case 'inproc':
                $inproc_lines .= $str;
                break;
            case 'ready':
                $ready_lines .= $str;
                break;
            default:
                $other_lines .= $str;
        }
    }
    $tmpl->addContent($new_lines . $inproc_lines . $other_lines . $ready_lines . "</table>");
    $tmpl->msg("В списке отображаются заявки, не отмеченные на удаления и с любым статусом, кроме &quot;отгружен&quot; и &quot;ошибочный&quot;");
}
else if ($mode == 'c') {
    $author = rcvint('author');
    if($author) {
        $where_add = " AND `doc_list`.`user` = $author";
    }
    else {
        $where_add = '';
    }
    $sql = "SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`,  `doc_list`.`user`, `doc_list`.`firm_id`, `doc_list`.`type`,
            `doc_agent`.`name` AS `agent_name`, `doc_list`.`sum`, `users`.`name` AS `user_name`, `doc_types`.`name`, `doc_list`.`p_doc`,
            `dop_status`.`value` AS `status`, `doc_list`.`sklad`, `users_worker_info`.`worker_real_name` AS `kladovshik`
	FROM `doc_list`
	LEFT JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
	LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
	LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
	LEFT JOIN `doc_dopdata` AS `dop_status` ON `dop_status`.`doc`=`doc_list`.`id` AND `dop_status`.`param`='status'
        LEFT JOIN `doc_dopdata` AS `dop_klad` ON `dop_klad`.`doc`=`doc_list`.`id` AND `dop_klad`.`param`='kladovshik'
        LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`dop_klad`.`value`
	WHERE (`doc_list`.`type`=2 OR `doc_list`.`type`=20) AND `doc_list`.`mark_del`=0 AND `doc_list`.`ok`=0 $where_add
	ORDER by `doc_list`.`date` ASC";

    $res = $db->query($sql);
    $tmpl->addContent("<form action='/incomp_orders.php' method='get'>"
            . "<input type='hidden' name='mode' value='c'>"
            . "<fieldset><legend>Фильтр</legend>"
            . "Автор: <select name='author'>"
            . "<option value='0'>--не задан--</option>");
        $ldo = new \Models\LDO\workernames();
        $w_list = $ldo->getData();
        foreach($w_list as $id=>$name) {
            $sel = $id==$author?' selected':'';
            $tmpl->addContent("<option value='$id'{$sel}>".html_out($name)."</option>");
        }
        $tmpl->addContent("</select>"
            . "<button type='submit'>отфильтровать</button>"
            . "</fieldset>"
            . "</form>");
    $tmpl->addContent("<table width='100%' class='list'><tr>
<th width='70'>№</th><th width='55'>ID</th><th width='55'>Счёт</th><th width='100'>Статус</th><th>Агент</th><th width='90'>Сумма</th><th width='150'>Дата</th>
<th>Кладовщик</th><th>Автор</th></tr>");
    $new_lines = $inproc_lines = $other_lines = $ready_lines = '';
    while ($line = $res->fetch_assoc()) {
        if ($line['status'] == 'readytoship' || $line['status'] == 'ok' || $line['status'] == 'err') {
            continue;
        }
        if(!\acl::testAccess('firm.'.$line['firm_id'], \acl::VIEW) || !\acl::testAccess('doc.'.$doc_types[$line['type']], \acl::VIEW)) {
            continue;
        }
        if ($line['status'] == '') {
            $line['status'] = 'no';
        }
        $in_store = 1;
        $pos_res = $db->query("SELECT `doc_base`.`id` AS `pos_id`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt` AS `sklad_cnt`
			FROM `doc_list_pos`
			INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$line['sklad']}'
			WHERE `doc_list_pos`.`doc`='{$line['id']}'");
        while ($pos_info = $pos_res->fetch_assoc()) {
            if ($pos_info['sklad_cnt'] < $pos_info['cnt']) {
                $in_store = 0;
            }
        }
        $status_style = '';
        switch ($line['status']) {
            case 'no':
                $status_style = " style='color:#f00'";
                break;
            case 'in_process':
                $status_style = " style='color:#880'";
                break;
            case 'ready':
                $status_style = " style='color:#0c0'";
                break;
        }
        $line_style = '';
        if ($in_store) {
            $line_style = " style='background-color:#bfb'";
        }
        $status = $r_status_list[$line['status']];
        $date = date('Y-m-d', $line['date']);
        $link = "/doc.php?mode=body&amp;doc=" . $line['id'];
        if ($line['p_doc'])
            $z = "<a href='/doc.php?mode=body&amp;doc={$line['p_doc']}'>{$line['p_doc']}</a>";
        else
            $z = '--нет--';
        $str = "<tr{$line_style}><td align='right'><a href='$link'>{$line['altnum']}{$line['subtype']}</a></td><td><a href='$link'>{$line['id']}</a></td>
            <td>$z</td><td{$status_style}>$status</td><td>{$line['agent_name']}</td><td align='right'>{$line['sum']}</td>
            <td>$date</td><td>{$line['kladovshik']}</td><td><a href='/adm.php?mode=users&amp;sect=view&amp;user_id={$line['user']}'>{$line['user_name']}</a></td>
            </tr>";
        switch ($line['status']) {
            case 'no':
                $new_lines .= $str;
                break;
            case 'in_process':
                $inproc_lines .= $str;
                break;
            case 'ready':
                $ready_lines .= $str;
                break;
            default:
                $other_lines .= $str;
        }
    }
    $tmpl->addContent($new_lines . $inproc_lines . $other_lines . $ready_lines . "</table>");
    $tmpl->msg("В списке отображаются непроведённые реализации, не отмеченные на удаления и с любым статусом, кроме &quot;готов к отгрузке&quot;,  &quot;отгружен&quot; и &quot;ошибочный&quot;");
}
else if ($mode == 'p') {
    $sql = "SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`,  `doc_list`.`user`, `doc_agent`.`name` AS `agent_name`,
            `doc_list`.`sum`, `users`.`name` AS `user_name`, `doc_types`.`name`, `doc_list`.`p_doc`, `dop_status`.`value` AS `status`, `doc_list`.`firm_id`, `doc_list`.`type`
    FROM `doc_list`
    LEFT JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
    LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
    LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
    LEFT JOIN `doc_dopdata` AS `dop_status` ON `dop_status`.`doc`=`doc_list`.`id` AND `dop_status`.`param`='status'
    WHERE (`doc_list`.`type`=2 OR `doc_list`.`type`=20) AND `doc_list`.`mark_del`=0 AND `doc_list`.`ok`=0 AND `dop_status`.`value`='in_process'
    ORDER by `doc_list`.`date` ASC";

    $res = $db->query($sql);
    $row = $res->num_rows;

    $i = 0;
    $pr = $ras = 0;
    $tpr = $tras = 0;

    $tmpl->addContent("<table width='100%' cellspacing='1' class='list'><tr>
    <th width='70'>№</th><th width='55'>ID</th><th width='55'>Счет</th><th>Агент</th><th width='90'>Сумма</th><th width='150'>Дата</th><th>Автор</th></tr>");
    while ($line = $res->fetch_assoc()) {
        if(!\acl::testAccess('firm.'.$line['firm_id'], \acl::VIEW) || !\acl::testAccess('doc.'.$doc_types[$line['type']], \acl::VIEW)) {
            continue;
        }
            $date = date('Y-m-d H:i:s', $line['date']);
            $link = "/doc.php?mode=body&amp;doc=" . $line['id'];
            if ($line['p_doc'])
                    $z = "<a href='/doc.php?mode=body&amp;doc={$line['p_doc']}'>{$line['p_doc']}</a>";
            else
                    $z = '--нет--';
            $tmpl->addContent("<tr><td align='right'><a href='$link'>{$line['altnum']}{$line['subtype']}</a></td><td><a href='$link'>{$line['id']}</a></td>
    <td>$z</td><td>{$line['agent_name']}</td><td align='right'>{$line['sum']}</td>
    <td>$date</td><td><a href='/adm.php?mode=users&amp;sect=view&amp;user_id={$line['user']}'>{$line['user_name']}</a></td>
    </tr>");
    }
    $tmpl->addContent("</table>");
    $tmpl->msg("В списке отображаются реализации со статусом &quot;в процессе сборки&quot;");
    
}
else if ($mode == 'r') {
	$sql = "SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`,  `doc_list`.`user`, `doc_agent`.`name` AS `agent_name`,
		`doc_list`.`sum`, `users`.`name` AS `user_name`, `doc_types`.`name`, `doc_list`.`p_doc`, `dop_status`.`value` AS `status`, `doc_list`.`firm_id`, `doc_list`.`type`
	FROM `doc_list`
	LEFT JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
	LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
	LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
	LEFT JOIN `doc_dopdata` AS `dop_status` ON `dop_status`.`doc`=`doc_list`.`id` AND `dop_status`.`param`='status'
	WHERE (`doc_list`.`type`=2 OR `doc_list`.`type`=20) AND `doc_list`.`mark_del`=0 AND `doc_list`.`ok`=0 AND `dop_status`.`value`='readytoship'
	ORDER by `doc_list`.`date` ASC";

	$res = $db->query($sql);
	$row = $res->num_rows;

	$i = 0;
	$pr = $ras = 0;
	$tpr = $tras = 0;

	$tmpl->addContent("<table width='100%' cellspacing='1' class='list'><tr>
	<th width='70'>№</th><th width='55'>ID</th><th width='55'>Счет</th><th>Агент</th><th width='90'>Сумма</th><th width='150'>Дата</th><th>Автор</th></tr>");
	while ($line = $res->fetch_assoc()) {
            if(!\acl::testAccess('firm.'.$line['firm_id'], \acl::VIEW) || !\acl::testAccess('doc.'.$doc_types[$line['type']], \acl::VIEW)) {
                continue;
            }
		$date = date('Y-m-d H:i:s', $line['date']);
		$link = "/doc.php?mode=body&amp;doc=" . $line['id'];
		if ($line['p_doc'])
			$z = "<a href='/doc.php?mode=body&amp;doc={$line['p_doc']}'>{$line['p_doc']}</a>";
		else
			$z = '--нет--';
		$tmpl->addContent("<tr><td align='right'><a href='$link'>{$line['altnum']}{$line['subtype']}</a></td><td><a href='$link'>{$line['id']}</a></td>
	<td>$z</td><td>{$line['agent_name']}</td><td align='right'>{$line['sum']}</td>
	<td>$date</td><td><a href='/adm.php?mode=users&amp;sect=view&amp;user_id={$line['user']}'>{$line['user_name']}</a></td>
	</tr>");
	}
	$tmpl->addContent("</table>");
	$tmpl->msg("В списке отображаются непроведённые реализации, не отмеченные на удаления, со статусом &quot;готов к отгрузке&quot;");
}

$tmpl->write();
