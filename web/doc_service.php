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

include_once("core.php");
include_once("include/doc.core.php");
need_auth();

SafeLoadTemplate($CONFIG['site']['inner_skin']);
$tmpl->hideBlock('left');

try {
    $mode = request('mode');
    $uid = intval($_SESSION['uid']);
    \acl::accessGuard('service.docservice', \acl::VIEW);

    doc_menu();
    $tmpl->addBreadcrumb('Документы', '/docj_new.php');
    $tmpl->addBreadcrumb('Служебные функции', '/doc_service.php');
    if ($mode == '') {
        $tmpl->addBreadcrumb('Служебные функции', '');
        $tmpl->setTitle("Служебные функции");
        $tmpl->addContent("<h1>Служебные функции</h1>
	<ul class='items'>
	<li>Справочники</li>
	<ul>
	<li><a href='?mode=firms'>Организации</a></li>	
	<li><a href='?mode=dtypes'>Виды расходов</a></li>
	<li><a href='?mode=ctypes'>Виды доходов</a></li>
	<li><a href='?mode=accounts'>Бухгалтерские счета</a></li>
	<li><a href='?mode=banks'>Банки</a></li>
	<li><a href='?mode=kass'>Кассы</a></li>
	<li><a href='?mode=stores'>Склады</a></li>
	<li><a href='?mode=pos_types'>Типы товаров</a></li>
	<li><a href='?mode=cost'>Цены</a></li>
        <li><a href='?mode=gparams'>Группы свойств складской номенклатуры</a></li>
	<li><a href='?mode=params'>Свойства складской номенклатуры</a></li>
	<li><a href='?mode=param_collections'>Наборы свойств складской номенклатуры</a></li>	
	<li><a href='?mode=units'>Единицы измерения</a></li>
        <li><a href='?mode=regions'>Регионы доставки</a></li>
        <li><a href='?mode=shiptypes'>Способы доставки</a></li>
        <li><a href='?mode=ctemplates'>Шаблоны договоров</a></li>
	</ul>
	<li>Обработки</li>
	<ul>
	<li><a href='?mode=cimage'>Замена изображений</a></li>
	<li><a href='?mode=merge_agent'>Группировка агентов</a></li>
	<li><a href='?mode=merge_tovar'>Группировка складской номенклатуры</a></li>
	</ul>
	<li>Отчёты</li>
	<ul>
	<li><a href='?mode=auinfo'>Документы, изменённые после проведения</a></li>
	<li><a href='?mode=pcinfo'>Информация по изменениям в номеклатуре</a></li>
	<li><a href='?mode=doc_log'>Журнал изменений</a></li>	
	</ul>
	</ul>");
    } else if ($mode == 'merge_agent') {
        $tmpl->addContent("<h1>Группировка агентов</h1>
	Данная функция перепривязывает все документы и доверенных лиц от агента с большим ID на агента с меньшим ID. После этого, имя агента с большим ID получает префикс old, и агент перемещается в указанную группу.<h2 style='color: #f00'>ВНИМАНИЕ! Данное действие необратимо, и может привести к ошибкам в документах! Перед выполнением убедитесь в том, что у Вас есть резервная копия базы данных! После выполнения действия рекомендуется выполнить процедуру оптимизации!</h2>
	<form method='post'><input type='hidden' name='mode' value='merge_agent_ok'>
	<fieldset><legend>Данные, необходимые для объединения</legend>
	ID первого агента:<br><input type='text' name='ag1'><br>
	ID второго агента:<br><input type='text' name='ag2'><br>
	Группа для перемещения:<br><select name='gr'>");
        $res = $db->query("SELECT `id`, `name` FROM `doc_agent_group` ORDER BY `name`");
        while ($nxt = $res->fetch_row())
            $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . " (id:$nxt[0])</option>");
        $tmpl->addContent("</select><br><br>
	<button>Выполнить запрошенную операцию</button>
	</fieldset></form>");
    } else if ($mode == 'merge_agent_ok') {
        \acl::accessGuard('service.docservice', \acl::UPDATE);
        $ag1 = rcvint('ag1');
        $ag2 = rcvint('ag2');
        $gr = rcvint('gr');
        if (($ag1 == 0) || ($ag2 == 0))
            throw new Exception("не указан ID агента!");
        if ($ag1 == $ag2)
            throw new Exception("ID агентов должны быть разные!");
        if ($ag2 < $ag1) {
            $ag = $ag1;
            $ag1 = $ag2;
            $ag2 = $ag;
        }
        $db->startTransaction();
        $res = $db->query("UPDATE `doc_list` SET `agent`='$ag1' WHERE `agent`='$ag2'");
        $af_doc = $res->affected_rows;
        $res = $db->query("UPDATE `doc_agent_dov` SET `ag_id`='$ag1' WHERE `ag_id`='$ag2'");
        $af_dov = $res->affected_rows;
        $res = $db->query("UPDATE `doc_agent` SET `name`=CONCAT('old ',`name`), `group`='$gr' WHERE `id`='$ag2'");
        $res = $db->commit();

        $tmpl->msg("Операция выполнена - обновлено $af_doc документов и $af_dov доверенных лиц", "ok");
    } else if ($mode == 'merge_tovar') {
        $tmpl->addContent("<h1>Группировка складской номенклатуры</h1>
	Данная функция перепривязывает всю номенклатуру в документах и комплектующих от объекта с большим ID на объект с меньшим ID. После этого, имя объекта с большим ID получает префикс old, и объекта перемещается в указанную группу.<h2 style='color: #f00'>ВНИМАНИЕ! Данное действие необратимо, и может привести к ошибкам в документах и на складе! Перед выполнением убедитесь в том, что Вы осознаёте, что делаете, и что у Вас есть резервная копия базы данных! После выполнения действия ОБЯЗАТЕЛЬНО выполнить процедуру оптимизации, иначе остатки на складе будут неверны!</h2>
	<form method='post'><input type='hidden' name='mode' value='merge_tovar_ok'>
	<fieldset><legend>Данные, необходимые для объединения</legend>
	ID первого объекта:<br><input type='text' name='tov1'><br>
	ID второго объекта:<br><input type='text' name='tov2'><br>
	Группа для перемещения:<br><select name='gr'>");
        $res = $db->query("SELECT `id`, `name` FROM `doc_group` ORDER BY `name`");
        while ($nxt = $res->fetch_row())
            $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . " (id:$nxt[0])</option>");
        $tmpl->addContent("</select><br><br>
	<button>Выполнить запрошенную операцию</button>
	</fieldset></form>");
    } else if ($mode == 'merge_tovar_ok') {
        \acl::accessGuard('service.docservice', \acl::UPDATE);
        $tov1 = rcvint('tov1');
        $tov2 = rcvint('tov2');
        $gr = rcvint('gr');
        if (($tov1 == 0) || ($tov2 == 0))
            throw new Exception("не указан ID объекта!");
        if ($tov1 == $tov2)
            throw new Exception("ID объектов должны быть разные!");
        if ($tov2 < $tov1) {
            $tov = $tov1;
            $tov1 = $tov2;
            $tov2 = $tov;
        }
        $db->startTransaction();
        // Меняем товары в документах
        $res = $db->query("UPDATE `doc_list_pos` SET `tovar`='$tov1' WHERE `tovar`='$tov2'");
        $af_doc = $res->affected_rows;
        // Меняем информацию в комплектующих
        $res = $db->query("UPDATE `doc_base_kompl` SET `pos_id`='$tov1' WHERE `pos_id`='$tov2'");
        $af_cb = $res->affected_rows;
        $res = $db->query("UPDATE `doc_base_kompl` SET `kompl_id`='$tov1' WHERE `kompl_id`='$tov2'");
        $af_cc = $res->affected_rows;
        $res = $db->query("UPDATE `doc_base` SET `name`=CONCAT('old ',`name`), `group`='$gr' WHERE `id`='$tov2'");

        $res = $db->commit();

        $tmpl->msg("Операция выполнена - обновлено $af_doc документов, $af_cb / $af_cc комплектующих", "ok");
    } else if ($mode == 'doc_log') {
        $motions = $targets = array();
        $res = $db->query("SELECT DISTINCT `motion` FROM `doc_log`");
        while ($nxt = $res->fetch_row()) {
            $nxt[0] = str_replace(':', '', $nxt[0]);
            list($motions[], $targets[]) = explode(' ', $nxt[0]);
        }
        $motions = array_unique($motions);
        $targets = array_unique($targets);
        $tmpl->msg("Разработка функции временно приостановлена. Функция неработоспособна.", "err");
        $tmpl->addContent("<h1>Журнал изменений</h1>
	Данная функция позволяет получить информацию по изменениям в базе документов, отобранной по заданным критериям.
	<form method='post'><input type='hidden' name='mode' value=doc_log_ok'>
	<table width='100%'>
	<tr><th>Дата<th>Типы объектов<th>Действие
	<tr>
	<td>
	От: <input type=text id='id_pub_date_date' class='vDateField required' name='dt_from' value='$dt_from'><br>
	До: <input type=text id='id_pub_date_date' class='vDateField required' name='dt_to' value='$dt_to'><br>
	<td>
	<label><input type='radio' name='obj_type' value='all'>Все</label><br>
	<label><input type='radio' name='obj_type' value='sel'>Выбранные:</label><br>");
        $res = $db->query("SELECT DISTINCT `object` FROM `doc_log`");
        while ($nxt = $res->fetch_row()) {
            switch ($nxt[0]) {
                case '': $desc = '{не задан}';
                    break;
                case 'agent': $desc = 'Агент';
                    break;
                case 'doc': $desc = 'Документ';
                    break;
                case 'tovar': $desc = 'Товар';
                    break;
                default: $desc = $nxt[0];
            }
            $tmpl->addContent("<label><input type='checkbox' name='obj' value='agent'>$desc</label><br>");
        }
        $tmpl->addContent("
	<label><input type='radio' name='obj_type' value='def'>Свой</label><br>
	<input type='text' name='obj_name' value='$obj_name'>
	<td><label><input type='radio' name='motion' value='all'>Все</label><br>");
        foreach ($motions as $id => $val) {
            $tmpl->addContent("<label><input type='radio' name='motion' value='$val'>$val</label><br>");
        }


        $tmpl->addContent("</table>
	<button>Отобразить</button>
	</fieldset></form>");
    } else if ($mode == 'cost') {
        $tmpl->addContent("<h1>Управление ценами</h1>");
        $res = $db->query("SELECT `id`, `name`, `type`, `value`, `context`, `priority`, `accuracy`, `direction`, `bulk_threshold`, `acc_threshold` FROM `doc_cost`");

        $tmpl->addContent("<form><input type='hidden' name='mode' value='costs'>
		<table class='list'><tr><th>ID</th><th>Наименование</th><th>Тип</th><th>Значение</th><th>Вид</th><th>Точность</th><th>Округление</th>
		<th>Порог разового заказа</th><th>Накопительный порог</th><th>Приоритет</th></tr>");
        $cost_contexts = array('r' => 'Розничная', 's' => 'Интернет-цена', 'd' => 'По умолчанию', 'b' => 'Оптовая автоматическая');
        $cost_types = array('pp' => 'Процент', 'abs' => 'Абсолютная наценка', 'fix' => 'Фиксированная цена');
        $direct = array(-1 => 'Вниз', 0 => 'K ближайшему', 1 => 'Вверх');

        while ($line = $res->fetch_assoc()) {
            $tmpl->addContent("<tr><td>{$line['id']}</td>
			<td><input type='text' name='name[{$line['id']}]' value='" . html_out($line['name']) . "'></td>
		<td><select name='cost_type[{$line['id']}]'>");
            foreach ($cost_types as $id => $type) {
                $sel = ($id == $line['type']) ? ' selected' : '';
                $tmpl->addContent("<option value='$id'{$sel}>$type</option>");
            }
            $tmpl->addContent("</select></td>
		<td><input type='text' name='value[{$line['id']}]' value='{$line['value']}'></td>
		<td>");
            foreach ($cost_contexts as $id => $name) {
                $sel = '';
                if (strpos($line['context'], $id) !== false)
                    $sel = ' checked';
                $tmpl->addContent("<label><input type='checkbox' name='context[{$line['id']}][$id]' value='$id'{$sel}>$name</label></br>");
            }
            $tmpl->addContent("</td>
		<td><select name='accuracy[{$line['id']}]'>");
            for ($i = -3; $i < 3; $i++) {
                $a = sprintf("%0.2f", pow(10, $i * (-1)));
                $sel = $line['accuracy'] == $i ? 'selected' : '';
                $tmpl->addContent("<option value='$i' $sel>$a</option>");
            }
            $tmpl->addContent("</select></td>
		<td><select name='direction[{$line['id']}]'>");
            for ($i = (-1); $i < 2; $i++) {
                $sel = $line['direction'] == $i ? 'selected' : '';
                $tmpl->addContent("<option value='$i' $sel>{$direct[$i]}</option>");
            }
            $tmpl->addContent("</select></td>
		<td><input type='text' name='bulk_threshold[{$line['id']}]' value='{$line['bulk_threshold']}'></td>
		<td><input type='text' name='acc_threshold[{$line['id']}]' value='{$line['acc_threshold']}'></td>
		<td><input type='text' name='priority[{$line['id']}]' value='{$line['priority']}'></td>
		</tr>");
        }
        $tmpl->addContent("
	<tr><td>Новая<td><input type='text' name='name[0]' value=''>
	<td><select name='cost_type[0]'>");
        foreach ($cost_types as $id => $type) {
            $sel = $id == 'pp' ? ' selected' : '';
            $tmpl->addContent("<option value='$id'$sel>$type</option>");
        }
        $tmpl->addContent("</select>
	<td><input type='text' name='value[0]' value=''>
	<td>");
        foreach ($cost_contexts as $id => $name) {
            $sel = '';
            $tmpl->addContent("<label><input type='checkbox' name='context[0][$id]' value='$id'{$sel}>$name</label></br>");
        }
        $tmpl->addContent("</td>
	<td><select name='accuracy[0]'>");
        for ($i = -3; $i < 3; $i++) {
            $a = sprintf("%0.2f", pow(10, $i * (-1)));
            $sel = 2 == $i ? 'selected' : '';
            $tmpl->addContent("<option value='$i' $sel>$a</option>");
        }
        $tmpl->addContent("</select>
	<td><select name='direction[0]'>");
        for ($i = -1; $i < 2; $i++) {
            $sel = 1 == $i ? 'selected' : '';
            $tmpl->addContent("<option value='$i' $sel>{$direct[$i]}</option>");
        }
        $tmpl->addContent("</select></td>
	<td><input type='text' name='bulk_threshold[0]' value='0'></td>
	<td><input type='text' name='acc_threshold[0]' value='0'></td>
	<td><input type='text' name='priority[0]' value='0'></td>	
	</table>
	<button type='submit'>Сохранить</button>
	</form>
	<fieldset><legend>Виды цен</legend>
	<ul>
	<li><b>По умолчанию</b> - устанавливается при создании нового документа по умолчанию. Так же по этой цене отображаются товары на витрине для неаутентифицированных пользователей. Относительно цены по умолчанию отображается размер скидки. Должна быть единственной.</li>
	<li><b>Интернет-цена</b> - применяется для всех аутентифицированных пользователей.  Должна быть единственной.</li>
	<li><b>Розничная цена</b> используется в случаях, когда заказываемое количество меньше минимального оптового количества. Должна быть единственной.</li>
	<li><b>Оптовая автоматическая</b> вкключается автоматически при превышении суммы заказа или накопительной суммы за период. Допустимо любое количество таких цен.</li>
	</ul>
	</fieldset>");
    } else if ($mode == 'costs') {
        \acl::accessGuard('service.docservice', \acl::UPDATE);
        $context_a = array('r', 's', 'd', 'b');

        $name = $_REQUEST['name'];
        $cost_type = $_REQUEST['cost_type'];
        $value = $_REQUEST['value'];
        $context_r = $_REQUEST['context'];
        $priority = $_REQUEST['priority'];
        $accuracy = $_REQUEST['accuracy'];
        $direction = $_REQUEST['direction'];
        $bulk_threshold = $_REQUEST['bulk_threshold'];
        $acc_threshold = $_REQUEST['acc_threshold'];

        $res = $db->query("SELECT `id` FROM `doc_cost`");

        while ($line = $res->fetch_assoc()) {

            $name_sql = $db->real_escape_string($name[$line['id']]);
            $cost_type_sql = $db->real_escape_string($cost_type[$line['id']]);
            $value_sql = round($value[$line['id']], 3);
            $priority_sql = intval($priority[$line['id']]);
            $accuracy_sql = intval($accuracy[$line['id']]);
            $direction_sql = intval($direction[$line['id']]);
            $bulk_threshold_sql = intval($bulk_threshold[$line['id']]);
            $acc_threshold_sql = intval($acc_threshold[$line['id']]);

            $context_sql = '';

            if (is_array(@$context_r[$line['id']]))
                foreach ($context_r[$line['id']] as $item) {
                    if (in_array($item, $context_a))
                        $context_sql .= $item;
                }

            $db->query("UPDATE `doc_cost` SET `name`='$name_sql', `type`='$cost_type_sql', `value`='$value_sql',  `context` = '$context_sql',
			`priority` = '$priority_sql', `accuracy`='$accuracy_sql', `direction`='$direction_sql', `bulk_threshold` = '$bulk_threshold_sql',
			`acc_threshold` = '$acc_threshold_sql' WHERE `id`='{$line['id']}'");
        }

        if ($name[0] != '') {
            $name_sql = $db->real_escape_string($name[0]);
            $cost_type_sql = $db->real_escape_string($cost_type[0]);
            $value_sql = round($value[0], 3);
            $priority_sql = intval($priority[0]);
            $accuracy_sql = intval($accuracy[0]);
            $direction_sql = intval($direction[0]);
            $bulk_threshold_sql = intval($bulk_threshold[0]);
            $acc_threshold_sql = intval($acc_threshold[0]);

            $context_sql = '';

            if (is_array(@$context_r[0]))
                foreach ($context_r[0] as $item) {
                    if (in_array($item, $context_a))
                        $context_sql .= $item;
                }

            $db->query("INSERT INTO `doc_cost` (`name`, `type`, `value`, `context`, `priority`, `accuracy`, `direction`, `bulk_threshold`, `acc_threshold`)
		VALUES ('$name_sql', '$cost_type_sql', '$value_sql', '$context_sql', '$priority_sql', '$accuracy_sql', '$direction_sql', '$bulk_threshold_sql', '$acc_threshold_sql')");
        }
        header("Location: doc_service.php?mode=cost");
    }
    else if ($mode == 'param_collections') {
        $opt = request('opt');
        if ($opt == 'save') {
            \acl::accessGuard('service.docservice', \acl::UPDATE);
            $newp = $newc = 0;

            if (isset($_POST['name'])) {
                $name = $db->real_escape_string($_POST['name']);
                if (strlen($name) > 0) {
                    \acl::accessGuard('service.docservice', \acl::CREATE);
                    $res = $db->query("INSERT INTO `doc_base_pcollections_list` (`name`) VALUES ('$name')");
                    $collection_id = $db->insert_id;
                    doc_log('CREATE', "name: $name", 'base_pcollections', $collection_id);
                    $newc = 1;
                }
            }
            if ($newc)
                $tmpl->msg("Набор создан", "ok");

            if (isset($_POST['add']))
                if (is_array($_POST['add'])) {
                    foreach ($_POST['add'] as $collection_id => $param_id) {
                        settype($collection_id, 'int');
                        settype($param_id, 'int');
                        if ($param_id < 1)
                            continue;

                        $res = $db->query("INSERT INTO `doc_base_pcollections_set` (`param_id`, `collection_id`) VALUES ('$param_id', '$collection_id')");
                        doc_log('INSERT', "param_id: $param_id", 'base_pcollections', $collection_id);
                        $newp = 1;
                    }
                }
            if ($newp)
                $tmpl->msg("Параметр добавлен", "ok");
            if ((!$newc) && (!$newp))
                $tmpl->msg("Ничего не изменено!", "info");
        }
        else if ($opt == 'del') {
            \acl::accessGuard('service.docservice', \acl::DELETE);
            $p = rcvint('p');
            $c = rcvint('c');
            $res = $db->query("DELETE FROM `doc_base_pcollections_set` WHERE `param_id`='$p' AND `collection_id`='$c'");
            $tmpl->msg("Параметр удалён", "ok");
        }

        $tmpl->addContent("<h1>Настройки наборов свойств складской номенклатуры</h1>");
        $tmpl->addContent("<form action='' method='post'>
	<input type='hidden' name='mode' value='param_collections'>
	<input type='hidden' name='opt' value='save'>");
        $rgroups = $db->query("SELECT `id`, `name` FROM `doc_base_pcollections_list` ORDER BY `name`");
        while ($group = $rgroups->fetch_row()) {
            $tmpl->addContent("<fieldset><legend>$group[1]</legend><table>");
            $rparams = $db->query("SELECT `doc_base_pcollections_set`.`param_id`, `doc_base_params`.`name`
		FROM `doc_base_pcollections_set`
		INNER JOIN `doc_base_params` ON `doc_base_params`.`id`=`doc_base_pcollections_set`.`param_id`
		WHERE `collection_id`='$group[0]'");
            while ($param = $rparams->fetch_row()) {
                $tmpl->addContent("<tr><td><a href='/doc_service.php?mode=param_collections&amp;opt=del&amp;p=$param[0]&amp;c=$group[0]'><img alt='Удалить' src='/img/i_del.png'></a></td><td>$param[1]</td></tr>");
            }
            $tmpl->addContent("<tr><td><b>+</b></td><td><select name='add[$group[0]]'>
		<option value='0' selected>--не выбрано--</option>");
            $res_group = $db->query("SELECT `id`, `name` FROM `doc_base_gparams` ORDER BY `name`");
            while ($group = $res_group->fetch_row()) {
                $tmpl->addContent("<optgroup label='" . html_out($group[1]) . "'>");
                $res = $db->query("SELECT `id`, `name` FROM `doc_base_params` WHERE `group_id`='$group[0]' ORDER BY `name`");
                while ($param = $res->fetch_row()) {
                    $tmpl->addContent("<option value='$param[0]'>$param[1]</option>");
                }
                $tmpl->addContent("</optgroup>");
            }
            $tmpl->addContent("</select></td></tr>");
            $tmpl->addContent("</table></fieldset>");
        }

        $tmpl->addContent("Новый набор: <input type='text' name='name' value=''><br>");
        $tmpl->addContent("<button>Сохранить</button></form>");
    } else if ($mode == 'auinfo') {
        $dt_apply = strtotime(rcvdate('dt_apply', date("Y-m-d", time() - 60 * 60 * 24 * 31)));
        $dt_update = strtotime(rcvdate('dt_update', date("Y-m-d", time() - 60 * 60 * 24 * 31)));
        $print_dt_apply = date('Y-m-d', $dt_apply);
        $print_dt_update = date('Y-m-d', $dt_update);
        $ndd = rcvint('ndd');
        $ndd_check = $ndd ? 'checked' : '';

        $tmpl->addContent("<h1 id='page-title'>Информация по документам, изменённым после проведения</h1>
	<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
	<form action='' method='post'>
	<input type='hidden' name='mode' value='auinfo'>
	Проведен не ранее: <input type=text id='dt_apply' name='dt_apply' value='$print_dt_apply'><br>
	Изменён не ранее: <input type=text id='dt_update' name='dt_update' value='$print_dt_update'><br>
	<label><input type='checkbox' name='ndd' value='1' $ndd_check>Не показывать правки, сделанные в день проведения</label><br>
	<button type='submit'>Отобразить</button>
	</form>

	<script type=\"text/javascript\">
	function dtinit()
	{
		initCalendar('dt_apply',false)
		initCalendar('dt_update',false)
	}

	addEventListener('load',dtinit,false)
	</script>
	<table class='list'>
	<tr><th rowspan='2'>ID док.</th><th rowspan='2'>Название</th><th colspan='2'>Проведен до изменения</th><th rowspan='2'>Кто правил</th><th colspan='2'>Последняя правка</th><th rowspan='2'>Окончательно проведён</th></tr>
	<tr><th>Когда</th><th>Кем</th><th>Когда</th><th>Кто</th></tr>");

        $res = $db->query("SELECT `doc_log`.`object_id` AS `doc_id`, `doc_log`.`time`, `doc_log`.`user`, `users`.`name` AS `user_name`, `doc_list`.`ok`, `doc_types`.`name` AS `doc_type`
	FROM `doc_log`
	LEFT JOIN `users` ON `users`.`id`=`doc_log`.`user`
	LEFT JOIN `doc_list` ON `doc_list`.`id`=`doc_log`.`object_id`
	LEFT JOIN `doc_types` ON `doc_list`.`type`=`doc_types`.`id`
	WHERE `doc_log`.`object`='doc' AND `time`>='$print_dt_apply 00:00:00' AND `motion` LIKE 'APPLY%'
	ORDER BY `doc_log`.`time`");
        $docs = array();
        while ($nxt = $res->fetch_assoc()) {
            if (in_array($nxt['doc_id'], $docs))
                continue;
            $update_res = $db->query("SELECT `doc_log`.`object_id` AS `doc_id`, `doc_log`.`time`, `doc_log`.`user`, `users`.`name` AS `user_name`, `doc_log`.`desc`
		FROM `doc_log`
		LEFT JOIN `users` ON `users`.`id`=`doc_log`.`user`
		WHERE `doc_log`.`object`='doc' AND `doc_log`.`object_id`='{$nxt['doc_id']}' AND `time`>='$print_dt_update 00:00:00' AND `time`>='{$nxt['time']}' AND `motion` LIKE 'UPDATE%'");
            if ($update_res->num_rows == 0)
                continue;
            else {
                $c_users = array();
                $lastchange = $lastuser = $lastdesc = '';
                $datec = date('Y-m-d', strtotime($nxt['time']));
                while ($updates = $update_res->fetch_array()) {
                    if ($ndd)
                        if (date('Y-m-d', strtotime($updates['time'])) == $datec)
                            continue;

                    $c_users[$updates['user_name']] = 1;
                    $lastchange = $updates['time'];
                    $lastuser = $updates['user_name'];
                    $lastdesc = $updates['desc'];
                }
                if ($lastchange == '')
                    continue;
                $users = '';
                foreach ($c_users as $user => $v) {
                    $users.=$user . ', ';
                }
                if ($nxt['ok'])
                    $a_date = date("Y-m-d H:i:s", $nxt['ok']);
                else
                    $a_date = 'не проведён';
                $tmpl->addContent("<tr><td><a href='/doc.php?mode=body&amp;doc={$nxt['doc_id']}'>{$nxt['doc_id']}</a></td><td>{$nxt['doc_type']}</td><td>{$nxt['time']}</td><td>{$nxt['user_name']}</td><td>$users</td><td>$lastchange</td><td>$lastuser</td><td>$a_date</td></tr>");
            }
            $docs[] = $nxt['doc_id'];
        }

        $tmpl->addContent("</table>");
    }
    else if ($mode == 'pcinfo') {
        $from = request('from', '1970-01-01');
        $to = request('to', date('Y-m-d'));
        $tmpl->addContent("<h1 id='page-title'>Информация по изменениям в номенклатуре</h1>
	<form method='get' action=''>
	<input type='hidden' name='mode' value='pcinfo'>
	С: <input type='text' name='from' value='$from'><br>
	По: <input type='text' name='to' value='$to'><br>
	<button>Показать</button>
	</form>
	<table class='list'><tr><th>Пользователь</th><th>Затраченное время</th><th>Отредактировано наименований</th></tr>");
        $res = $db->query("SELECT `id`, `name` FROM `users` ORDER BY `name`");
        while ($user_data = $res->fetch_row()) {
            $oldtime = $totaltime = 0;
            $pos = array();
            $res_log = $db->query("SELECT `time`, `object_id` FROM `doc_log` WHERE `user`='{$user_data[0]}' AND `object`='pos' AND `time`>='$from' AND `time`<='$to' ORDER BY `time`");
            while ($logline = $res_log->fetch_row()) {
                $curtime = strtotime($logline[0]);
                if ($curtime <= ($oldtime + 60 * 15))
                    $totaltime+=$curtime - $oldtime;
                else
                    $totaltime+=180; // по 180 сек на наименование, если оно первое или единственное в серии редактирования
                $oldtime = $curtime;
                $pos[$logline[1]] = 1;
            }
            if (!$totaltime)
                continue;
            $ptotaltime = sectostrinterval($totaltime);
            $poscnt = count($pos);
            $tmpl->addContent("<tr><td>{$user_data[1]}</td><td>$ptotaltime</td><td>$poscnt</td></tr>");
        }
        $tmpl->addContent("</table>");
    }
    else if ($mode == 'cimage') {
        $tmpl->addContent("<h1 id='page-title'>Управление изображениями товаров</h1>");
        $tmpl->setTitle("Управление изображениями товаров");
        $img = rcvint('img');
        if ($img == '') {
            $tmpl->addStyle(".fl {float: left; padding: 5px; margin: 10px; border: 1px solid #00f; width: 120px; height: 150px; text-align: center; background: #888;}");
            $res = $db->query("SELECT * FROM `doc_img` ORDER BY `id`");
            while ($line = $res->fetch_assoc()) {
                $img = new ImageProductor($line['id'], 'p', $line['type']);
                $img->SetY(120);
                $img->SetX(100);

                $tmpl->addContent("<div class='fl'><a href='/doc_service.php?mode=cimage&amp;img={$line['id']}'><img src=\"" . $img->GetURI() . "\"></a>
			<br>" . html_out($line['name']) . "<br><b>{$line['id']}.{$line['type']}</b></div>");
            }
            $tmpl->addContent("<div style='clear: both'></div>");
        } else {
            if (request('save')) {
                $db->startTransaction();
                $res = $db->query("SELECT * FROM `doc_img` WHERE `id`=$img");
                if ($res->num_rows == 0) {
                    throw new NotFoundException("Изображение не найдено");
                }
                $line = $res->fetch_assoc();
                if ($_FILES['userfile']['size'] > 0) {
                    $iminfo = getimagesize($_FILES['userfile']['tmp_name']);
                    switch ($iminfo[2]) {
                        case IMAGETYPE_JPEG: $imtype = 'jpg';
                            break;
                        case IMAGETYPE_PNG: $imtype = 'png';
                            break;
                        case IMAGETYPE_GIF: $imtype = 'gif';
                            break;
                        default: $imtype = '';
                    }
                    if (!$imtype) {
                        throw new Exception("Файл - не картинка, или неверный формат файла. Рекомендуется PNG и JPG, допустим но не рекомендуется GIF.");
                    }


                    if (!move_uploaded_file($_FILES['userfile']['tmp_name'], $CONFIG['site']['var_data_fs'] . '/pos/' . $img . '.' . $imtype)) {
                        throw new Exception("Файл не загружен, $img.$imtype");
                    }
                    $line['type'] = $imtype;

                    $tmpl->msg("Файл загружен, $img.$imtype", "info");
                    if ($dh = opendir("{$CONFIG['site']['var_data_fs']}/cache/pos/")) {
                        while (($file = readdir($dh)) !== false) {
                            if ($file == '.' || $file == '..') {
                                continue;
                            }
                            unlink("{$CONFIG['site']['var_data_fs']}/cache/pos/$file");
                        }
                        closedir($dh);
                    }
                } else {
                    $tmpl->msg("Файл не получен!", "info", "Внимание");
                }
                $name_sql = $db->real_escape_string(request('name'));
                $db->query("UPDATE `doc_img` SET `name` = '$name_sql', `type` = '{$line['type']}' WHERE `id`=$img");
                $db->commit();
                $tmpl->msg("Данные сохранены", "ок");
            }
            $res = $db->query("SELECT * FROM `doc_img` WHERE `id`=$img");
            if ($res->num_rows == 0) {
                throw new NotFoundException("Изображение не найдено");
            }
            $line = $res->fetch_assoc();
            $max_fs = \webcore::getMaxUploadFileSize();
            $max_fs_size = \webcore::toStrDataSizeInaccurate($max_fs);
            
            $o_link = "{$CONFIG['site']['var_data_web']}/pos/{$line['id']}.{$line['type']}";
            $tmpl->msg("Замена файла очистит кеш изображений!", "err", "Внимание");
            $tmpl->addContent("<form method='post' enctype='multipart/form-data'>
		<input type='hidden' name='mode' value='cimage'>
		<input type='hidden' name='save' value='ok'>
		<input type='hidden' name='img' value='$img'>
		Новое название:<br>
		<input type='text' name='name' value='" . html_out($line['name']) . "'><br>
		Новый файл изображения:<br>
		<input type='hidden' name='MAX_FILE_SIZE' value='$max_fs'><input name='userfile' type='file'><br>
		<b>Форматы</b>: Не более $max_fs_size, форматы JPG, PNG, допустим, но не рекомендуется GIF<br>
		<button>Сохранить</button>
		</form><br>
		<a href='$o_link'>Скачать оригинал изображения</a><br><br>");
            $img = new ImageProductor($line['id'], 'p', $line['type']);
            $img->SetNoEnlarge(1);
            $img->SetY(800);

            $tmpl->addContent("<img src=\"" . $img->GetURI() . "\">");
        }
    } elseif ($mode == 'banks') {
        $editor = new \ListEditors\BankListEditor($db);
        $editor->line_var_name = 'id';
        $editor->link_prefix = '/doc_service.php?mode=banks';
        $editor->acl_object_name = 'directory.bank';
        $editor->run();
    } elseif ($mode == 'kass') {
        $editor = new \ListEditors\KassListEditor($db);
        $editor->line_var_name = 'id';
        $editor->link_prefix = '/doc_service.php?mode=kass';
        $editor->acl_object_name = 'directory.cash';
        $editor->run();
    } elseif ($mode == 'firms') {
        $editor = new \ListEditors\FirmListEditor($db);
        $editor->line_var_name = 'id';
        $editor->link_prefix = '/doc_service.php?mode=firms';
        $editor->acl_object_name = 'directory.firm';
        $editor->run();
    } elseif ($mode == 'ctypes') {
        $editor = new \ListEditors\CTypesListEditor($db);
        $editor->line_var_name = 'id';
        $editor->link_prefix = '/doc_service.php?mode=ctypes';
        $editor->acl_object_name = 'directory.ctype';
        $editor->run();
        $tmpl->addContent("<h3>Некоторые используемые <b>кодовые обозначения</b></h3>"
            . "<ul>"
            . "<li><b>goods_sell</b>: Продажа товара. Используется при создании приходного кассового или банковского документа на основе реализации</li>"
            . "<li><b>goods_return</b>: Возврат поставщику.. Используется при создании приходного кассового или банковского документа на основе возврата поставщику.</li>"
                . "</ul>");
    } elseif ($mode == 'dtypes') {
        $editor = new \ListEditors\DTypesListEditor($db);
        $editor->line_var_name = 'id';
        $editor->link_prefix = '/doc_service.php?mode=dtypes';
        $editor->acl_object_name = 'directory.dtype';
        $editor->run();
        $tmpl->addContent("<h3>Некоторые используемые <b>кодовые обозначения</b></h3>"
                . "<ul>"
                . "<li><b>ag_fee</b>: Агентское вознаграждение. Используется в сценарии &quot;Сборка с выдачей заработной платы&quot;</li>"
                . "<li><b>goods_buy</b>: Расход на закупку товара на склад. Используется при создании расходного кассового или банковского документа на основе поступления</li>"
                . "<li><b>goods_return</b>: Расход на возврат товара от покупателя. Используется при создании расходного кассового или банковского документа на основе возврата товара от покупателя.</li>"
                . "</ul>");
    } elseif ($mode == 'accounts') {
        $editor = new \ListEditors\AccountListEditor($db);
        $editor->line_var_name = 'id';
        $editor->link_prefix = '/doc_service.php?mode=accounts';
        $editor->acl_object_name = 'directory.account';
        $editor->run();
    } elseif ($mode == 'stores') {
        $editor = new \ListEditors\StoresListEditor($db);
        $editor->line_var_name = 'id';
        $editor->link_prefix = '/doc_service.php?mode=stores';
        $editor->acl_object_name = 'directory.storelist';
        $editor->run();
    } elseif ($mode == 'pos_types') {
        $editor = new \ListEditors\PosTypesListEditor($db);
        $editor->line_var_name = 'id';
        $editor->link_prefix = '/doc_service.php?mode=pos_types';
        $editor->acl_object_name = 'directory.postype';
        $editor->run();
    } elseif ($mode == 'gparams') {
        $editor = new \ListEditors\PGroupListEditor($db);
        $editor->line_var_name = 'id';
        $editor->link_prefix = '/doc_service.php?mode=gparams';
        $editor->acl_object_name = 'directory.pgroup';
        $tmpl->addContent("<ul class='tabs'>
            <li><a class='selected' href='/doc_service.php?mode=gparams'>Группы свойств</a></li>
            <li><a href='/doc_service.php?mode=params'>Свойства</a></li>
            </ul>");
        $editor->run();
    }
    elseif ($mode == 'params') {
        $editor = new \ListEditors\PosParamListEditor($db);
        $editor->line_var_name = 'id';
        $editor->link_prefix = '/doc_service.php?mode=params';
        $editor->acl_object_name = 'directory.posparam';
        $tmpl->addContent("<ul class='tabs'>
            <li><a href='/doc_service.php?mode=gparams'>Группы свойств</a></li>
            <li><a class='selected' href='/doc_service.php?mode=params'>Свойства</a></li>
            </ul>");
        $editor->run();
        $tmpl->addContent("<h3>Некоторые используемые <b>кодовые обозначения</b></h3>"
                . "<ul>"
                . "<li><b>ZP</b>: Размер вознаграждения сборщику на приозводстве. Необходим в сценарии &quot;Сборка с выдачей заработной платы&quot; и в модуле &quot;Учёт производства&quot; (factory.php)</li>"
                . "<li><b>pack_complexity_sk</b>: Сложность работы кладовщика. Необходим в сценариях и модулях начисления вознаграждения кладовщику.</li>"
                . "<li><b>bigpack_cnt</b>: Количество товара в большой упаковке. Отображается на витрине. Необходим для начисления вознаграждения кладовщикам.</li>"
                . "<li><b>cert_num</b>: N сертификата. Необходим в печатной форме &quot;Реестр сертификатов&quot;</li>"
                . "<li><b>cert_expire</b>: Срок действия сертификата. Необходим в печатной форме &quot;Реестр сертификатов&quot;</li>"
                . "<li><b>cert_publisher</b>: Орган сертификации, выдавший сертификат. Необходим в печатной форме &quot;Реестр сертификатов&quot;</li>"
                . "<li><b>expiration_period</b>: Срок годности. Необходим в печатной форме &quot;Реестр сертификатов&quot;</li>"
                . "<li><b>storage_temperature</b>: Температура хранения. Необходим в печатной форме &quot;Реестр сертификатов&quot;</li>"
                . "<li><b>provider_code</b>: Артикул поставщика. Используется в отчётах.</li>"
                . "<li><b>provider_codename</b>: Кодовое название поставщика. Используется в специализированных отчётах.</li>"
                . "<li><b>ym_url</b>: URL страницы янтекс-маркета с описанием товара. Необходим для загрузки свойств товара в редакторе номенклатуры, а так же может использоваться на витрине.</li>"
                . "</ul>");
    } elseif ($mode == 'units') {
        $editor = new \ListEditors\UnitsEditor($db);
        $editor->line_var_name = 'id';
        $editor->link_prefix = '/doc_service.php?mode='.$mode;
        $editor->acl_object_name = 'directory.unit';
        $editor->run();
    } elseif ($mode == 'regions') {
        $editor = new \ListEditors\RegionsEditor($db);
        $editor->line_var_name = 'id';
        $editor->link_prefix = '/doc_service.php?mode='.$mode;
        $editor->acl_object_name = 'directory.region';
        $editor->run();
    } elseif ($mode == 'shiptypes') {
        $editor = new \ListEditors\ShipTypesEditor($db);
        $editor->line_var_name = 'id';
        $editor->link_prefix = '/doc_service.php?mode='.$mode;
        $editor->acl_object_name = 'directory.shiptype';
        $editor->run();
    }  elseif ($mode == 'ctemplates') {
        $editor = new \ListEditors\contractEditor($db);
        $editor->line_var_name = 'id';
        $editor->link_prefix = '/doc_service.php?mode='.$mode;
        $editor->acl_object_name = 'directory.contract_templates';
        $editor->run();
    } else {
        throw new NotFoundException("Несуществующая опция");
    }
} catch (mysqli_sql_exception $e) {
    $tmpl->ajax = 0;
    $id = writeLogException($e);
    $tmpl->errorMessage("Порядковый номер ошибки: $id<br>Сообщение об ошибке занесено в журнал", "Ошибка в базе данных");
} catch (AccessException $e) {
    $tmpl->errorMessage($e->getMessage(), "У Вас недостаточно привилегий!");
} catch (Exception $e) {
    $db->rollback();
    $id = writeLogException($e);
    $tmpl->errorMessage($e->getMessage(), 'Ошибка выполнения операции');
}

$tmpl->write();
