<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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

/// Редактор справочника агентов
class doc_s_Agent {

    function __construct() {
        $this->agent_vars = array('group', 'name', 'type', 'fullname', 'adres', 'real_address', 'inn', 'kpp', 'rs', 'ks', 'okved', 'okpo', 'ogrn', 'bank',
            'bik', 'pfio', 'pdol', 'pasp_num', 'pasp_date', 'pasp_kem', 'comment', 'responsible', 'data_sverki'
            , 'leader_name', 'leader_post', 'leader_reason', 'leader_name_r', 'leader_post_r', 'leader_reason_r'
            , 'dishonest',
            'p_agent', 'price_id', 'no_retail_prices', 'no_bulk_prices', 'no_bonuses', 'region');
    }

    /// Просмотр списка агентов
    function View() {
        global $tmpl;
        doc_menu(0, 0);
        \acl::accessGuard('directory.agent', \acl::VIEW);
        $tmpl->setTitle("Редактор агентов");
        $tmpl->addContent("<h1>Агенты</h1><table width=100%><tr><td id='groups' width='200' valign='top' class='lin0'>");
        $this->draw_groups(0);
        $tmpl->addContent("<td id='list' valign='top'  class='lin1'>");
        $this->ViewList();
        $tmpl->addContent("</table>");
    }

    /// Служебные методы
    function Service() {
        global $tmpl, $db;

        $opt = request("opt");
        $g = rcvint('g');
        if ($opt == 'pl') {
            $s = request('s');
            $tmpl->ajax = 1;
            if ($s) {
                $this->ViewListS($s);
            } else {
                $this->ViewList($g);
            }
        } else if ($opt == 'ep') {
            $this->Edit();
        } else if ($opt == 'acost') {
            $pos = rcvint('pos');
            $tmpl->ajax = 1;
            $tmpl->addContent(getInCost($pos));
        } else if ($opt == 'popup') {
            $s = request('s');
            $tmpl->ajax = 1;
            $s_sql = $db->real_escape_string($s);
            $res = $db->query("SELECT `id`,`name` FROM `doc_agent` WHERE LOWER(`name`) LIKE LOWER('%$s_sql%') LIMIT 50");
            if ($res->num_rows) {
                $tmpl->addContent("Ищем: $s ({$res->num_rows} совпадений)<br>");
                while ($nxt = $res->fetch_row())
                    $tmpl->addContent("<a onclick=\"return SubmitData('$nxt[1]',$nxt[0]);\">" . html_out($nxt[1]) . "</a><br>");
            } else
                $tmpl->addContent("<b>Искомая комбинация не найдена!");
        }
        else if ($opt == 'ac') {
            $q = request('q');
            $tmpl->ajax = 1;
            $q_sql = $db->real_escape_string($q);
	        $res = $db->query("
				SELECT `name`, `id`, `tel`, `inn` FROM `doc_agent`
				 WHERE (
					 LOWER(`name`) LIKE LOWER('%$q_sql%') 
					 OR 
					 `inn` LIKE '%$q_sql%' 
				 )
				 ORDER BY `name`
			");
	        while ($nxt = $res->fetch_row()) {
		        $name = $nxt[0];
		        if(intval($q) == $q && intval($q) != 0) {
			        list($nxt[0],$nxt[3])=[$nxt[3],$nxt[0]];
		        }
		        $tmpl->addContent("$nxt[0] ".($nxt[3] ? "($nxt[3])" : "")."|$nxt[1]|$nxt[2]|$nxt[3]|$name\n");
	        }
        } elseif ($opt == 'jgetcontracts') {
            $tmpl->ajax = 1;
            $agent_id = rcvint('agent_id');
            $firm_id = rcvint('firm_id');
            $res = $db->query("SELECT `doc_list`.`id`, `doc_dopdata`.`value` AS `name` FROM `doc_list`
                LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='name'
                WHERE `agent`='$agent_id' AND `type`='14' AND `firm_id`='$firm_id'");
            $list = array();
            while ($line = $res->fetch_assoc()) {
                $list[] = $line;
            }
            $result = array(
                'response' => 'contract_list',
                'content' => $list,
            );
            $tmpl->setContent(json_encode($result, JSON_UNESCAPED_UNICODE));
        } else {
            throw new \NotFoundException("Неверный режим!");
        }
    }
    
    /// Сформировать главную форму редактирования агента
    protected function getMainForm($form_data) {
        global $db;
        $ret = '';
        $item_id = intval($form_data['id']);
        
        $linked_users = '';
        $r = $db->query("SELECT `id`, `name` FROM `users` WHERE `agent_id`=$item_id");
        if ($r->num_rows) {
            while ($nn = $r->fetch_assoc()) {
                if ($linked_users) {
                    $linked_users .= ', ';
                }
                $linked_users .= "<a href='/adm.php?mode=users&amp;sect=view&amp;user_id={$nn['id']}'>" . html_out($nn['name']) . " ({$nn['id']})</a>";
            }
        } else {
            $linked_users = 'отсутствуют';
        }
        $ext = '';
        if (!\acl::testAccess('directory.agent.ext', \acl::UPDATE)) {
            $ext = 'disabled';
        }
        if ($form_data['p_agent'] > 0) {
            $pagent_info = $db->selectRowA('doc_agent', $form_data['p_agent'], array('name'));
            $html_pagent_name = html_out($pagent_info['name']);
        } else {
            $html_pagent_name = '';
        }
        if($form_data['id']>0) {
            $ace = new \ListEditors\agentContactEditor($db);
            $ace->acl_object_name = 'directory.agent';  /// TODO: Поменять
            $ace->agent_id = intval($item_id);
            $contact_info = $ace->getListItems(false);
        } else {
            $contact_info = '';
        }
        $span = 5;
        $span_all = 6;
        $dish_checked = $form_data['dishonest'] ? 'checked' : '';
        $ret .= "<form action='' method='post' id='agent_edit_form'>
            <table cellpadding='0' width='100%' class='list editcard'>
            <input type='hidden' name='mode' value='esave'>
            <input type='hidden' name='l' value='agent'>
            <input type='hidden' name='pos' value='$item_id'>
            <tr><td align='right' width='20%'>Краткое наименование<br>
            <small>По этому полю выполняется поиск. Не пишите здесь аббревиатуры вроде OOO, ИП, МУП, итд. а так же кавычки и подобные символы!</small>
                <td colspan='3'><input type='text' name='name' value='" . html_out($form_data['name']) . "' style='width: 90%;' maxlength='64'><br>
                    <label class='autoalert'>
                        <input type='checkbox' name='dishonest' value='1' $dish_checked><span>Недобросовестный агент</span></label></td>
                <td align='right'>Связанные пользователи</td>
                <td>$linked_users</td>
                </tr>
            <tr><td align=right>Полное название / ФИО:<br><small>Так, как должно быть в документах</small>
                <td colspan='$span'><input type=text name='fullname' value='" . html_out($form_data['fullname']) . "' style='width: 90%;' maxlength='256'></td></tr>
            <tr><td align=right>Тип:</td><td>";

        $at_check = array(0 => '', 1 => '', 2 => '');
        $at_check[$form_data['type']] = ' checked';

        $ret .= "<label class='autohl'><input type='radio' name='type' value='0'{$at_check[0]} id='atype_rb0'><span>Физическое лицо</span></label><br>
            <label class='autohl'><input type='radio' name='type' value='1'{$at_check[1]} id='atype_rb1'><span>Юридическое лицо</span></label><br>
            <label class='autohl'><input type='radio' name='type' value='2'{$at_check[2]} id='atype_rb2'><span>Нерезидент</span></label>";

        $ret .= "<td align='right'>Группа</td>
            <td>" . selectAgentGroup('g', $form_data['group'], false, '', '', \cfg::get('agents', 'leaf_only') ) . "</select>
                <td align='right'>Относится к:</td>
                <td><input type='hidden' name='p_agent' id='agent_id' value='{$form_data['p_agent']}'>
                    <input type='text' id='agent_nm' name='p_agent_nm'  style='width: 95%;' value='$html_pagent_name'>
                    <div id='agent_info'></div>
            <tr><td align=right>Юридический адрес / Адрес прописки
                <td colspan='2'><textarea name='adres'>" . html_out($form_data['adres']) . "</textarea>
                <td colspan='3'>Контакты:<br>
                $contact_info
            <tr><td align=right>ИНН:
                <td><input type=text name='inn' value='" . html_out($form_data['inn']) . "' class='inn validate'>
                <td align=right>КПП:
                <td><input type=text name='kpp' value='" . html_out($form_data['kpp']) . "'>
                <td><td>
            <tr><td align=right>ОКВЭД
                <td><input type=text name='okved' value='" . html_out($form_data['okved']) . "'>
                <td align=right>ОГРН / ОГРНИП
                <td><input type=text name='ogrn' value='" . html_out($form_data['ogrn']) . "'>
                <td align=right>ОКПО
                <td><input type=text name='okpo' value='" . html_out($form_data['okpo']) . "' class='okpo validate'>
            <tr><th colspan='$span_all'>Сведения о руководителе (для договоров)</th></tr>
            <tr><td align=right>ФИО
                <td><input type=text name='leader_name' value='" . html_out($form_data['leader_name']) . "'>
                <td align=right>Должность
                <td><input type=text name='leader_post' value='" . html_out($form_data['leader_post']) . "'>
                <td align=right>На основании чего действует<br><small>Устав, доверенность, и.т.п.</small>
                <td><input type=text name='leader_reason' value='" . html_out($form_data['leader_reason']) . "'>
            <tr><td align=right>В родительном падеже
                <td><input type=text name='leader_name_r' value='" . html_out($form_data['leader_name_r']) . "'>
                <td align=right>В родительном падеже
                <td><input type=text name='leader_post_r' value='" . html_out($form_data['leader_post_r']) . "'>
                <td align=right>В родительном падеже
                <td><input type=text name='leader_reason_r' value='" . html_out($form_data['leader_reason_r']) . "'>
            <tr><th colspan='$span_all'>Паспортные данные физического лица</th></tr>
            <tr><td align=right>Номер</td>
                <td><input type=text name='pasp_num' value='" . html_out($form_data['pasp_num']) . "'></td>
                <td align=right>Дата выдачи</td>
                <td><input type=text name='pasp_date' value='" . html_out($form_data['pasp_date']) . "' id='pasp_date'></td>
                <td align=right>Кем выдан</td>
                <td><input type=text name='pasp_kem' value='" . html_out($form_data['pasp_kem']) . "'></td>
            <tr><th colspan='$span_all'>Другое</th></tr>
            <tr><td align=right>Дата последней сверки:
                <td><input type=text name='data_sverki' value='" . html_out($form_data['data_sverki']) . "' id='data_sverki' $ext>
                <td align=right>Ответственный:
                <td>";
        $ldo = new \Models\LDO\workernames();
        $ret .= \widgets::getEscapedSelect('responsible', $ldo->getData(), $form_data['responsible'], 'не назначен'
            , !\acl::testAccess('directory.agent.ext', \acl::UPDATE));
        $ret .= "</td>
            <td>Регион:</td>
            <td>";
        $ldo = new \Models\LDO\regionnames();
        $ret .= \widgets::getEscapedSelect('region', $ldo->getData(), $form_data['region'], 'не назначен');
        $ret .="</td>
            <tr><td align='right'>Фиксированная цена</td>
            <td>";
        $ldo = new \Models\LDO\pricenames();
        $ret .= \widgets::getEscapedSelect('price_id', $ldo->getData(), $form_data['price_id'], 'не задана');
        
        $nbp_checked = $form_data['no_bulk_prices'] ? 'checked' : '';
        $nrp_checked = $form_data['no_retail_prices'] ? 'checked' : '';
        $nbon_checked = $form_data['no_bonuses'] ? 'checked' : '';
        
        $ret .= "</td>
                <td><label><input type='checkbox' name='no_bulk_prices' value='1' $nbp_checked>Отключить разовые скидки</label></td>
                <td><label><input type='checkbox' name='no_retail_prices' value='1' $nrp_checked>Игнорировать розничные цены</label></td>
                <td><label><input type='checkbox' name='no_bonuses' value='1' $nbon_checked>Отключить бонусы</label></td>
                <td></td>
            <tr><td align='right'>Особые отметки
                <td colspan='$span'>

            <tr><td align=right>Комментарий
                <td colspan='$span'><textarea name='comment'>" . html_out($form_data['comment']) . "</textarea>"
            . "<tr><td><td>";
        if(\acl::testAccess([ 'directory.agent.global', 'directory.agent.ingroup.'.$form_data['group']], \acl::UPDATE | \acl::CREATE)) {
            $ret .= "<button type='submit' id='b_submit'>Сохранить</button>";
        } else {
            $ret .= "<input type='hidden' id='b_submit'><b>У Вас нет привилегий для сохранения этой формы</b>";
        }
            
        $ret .= "</table></form>

            <script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
            <script type='text/javascript' src='/js/formvalid.js'></script>
            <script type=\"text/javascript\">
            $(document).ready(function(){
                $(\"#agent_nm\").autocomplete(\"/docs.php\", {
                    delay:300,
                    minChars:1,
                    matchSubset:1,
                    autoFill:false,
                    selectFirst:true,
                    matchContains:1,
                    cacheLength:10,
                    maxItemsToShow:15,
                    onItemSelect:agselectItem,
                    extraParams:{'l':'agent','mode':'srv','opt':'ac'}
                });
            });

            function agselectItem(li) {
                if( li == null ) var sValue = \"Ничего не выбрано!\";
                if( !!li.extra ) var sValue = li.extra[0];
                else var sValue = li.selectValue;
                document.getElementById('agent_id').value=sValue;
            }
            initCalendar('pasp_date');
            initCalendar('data_sverki');
            var valid=form_validator('agent_edit_form');

            var atype_rb0 = document.getElementById('atype_rb0');
            var atype_rb1 = document.getElementById('atype_rb1');
            var atype_rb2 = document.getElementById('atype_rb2');
            atype_rb0.onclick = atype_rb1.onclick = function () {valid.enable(true);};
            atype_rb2.onclick = function () {valid.enable(false);};
            if(atype_rb2.checked) {
                valid.enable(false);
            }
            </script>";
        return $ret;
    }


    /// Отобразить главную форму редактирования агента
    protected function showMainForm($item_id, $group_id) {
        global $tmpl;
        $tmpl->addBreadcrumb('Агенты', '/docs.php?l=agent');
        $agent_obj = new \models\agent();
        if ($item_id > 0) {
            $agent_obj->load($item_id);
            $agent_info = $agent_obj->getData();
            $is_access = \acl::testAccess('directory.agent.global', \acl::VIEW) | \acl::testAccess('directory.agent.ingroup.'.$agent_info['group'], \acl::VIEW);
            if(!$is_access) {
                throw new \AccessException("Нет привилегии *Просмотр* для directory.agent.global или directory.agent.ingroup.".$agent_info['group']);
            }
            $tmpl->addBreadcrumb($agent_info['id'] . ': ' . $agent_info['name'], '');            
            $tmpl->setTitle("Правка агента " . html_out($agent_info['name']));
        } else {
            $tmpl->addBreadcrumb('Новая запись', '');
            $tmpl->setTitle("Новая запись");
            $agent_info = array();
            foreach ($this->agent_vars as $value) {
                $agent_info[$value] = '';
            }
            $agent_info['id'] = null;
            $agent_info['group'] = $group_id;
            $is_access = \acl::testAccess('directory.agent.global', \acl::CREATE) | \acl::testAccess('directory.agent.ingroup.'.$agent_info['group'], \acl::CREATE);
            if(!$is_access) {
                throw new \AccessException("Нет привилегии *Создание* для directory.agent.global или directory.agent.ingroup.".$agent_info['group']);
            }
        }

        $tmpl->addContent( $this->getMainForm($agent_info) );
    }

    /// Точка входа в редактирование справочника
    public function Edit() {
        global $tmpl, $db, $CONFIG;
        doc_menu();
        $pos = rcvint('pos');
        $param = request('param');
        $group = rcvint('g');
        \acl::accessGuard('directory.agent', \acl::VIEW);
        $tmpl->setTitle("Правка агента");
        $tmpl->addBreadcrumb('Агенты', '/docs.php?l=agent');

        if (($pos == 0) && ($param != 'g')) {
            $param = '';
        }

        if ($pos != 0) {
            $this->PosMenu($pos, $param);
        }

        if ($param == '' || $param == 'v') {
            $this->showMainForm($pos, $group);
        } elseif ($param == 'c') {
            $tmpl->addBreadcrumb('Агенты', '/docs.php?l=agent');
            $info = $db->selectRow('doc_agent', $pos);
            if ($info) {
                if(!\acl::testAccess('directory.agent.global', \acl::VIEW)) {
                    \acl::accessGuard('directory.agent.ingroup.'.$info['group'], \acl::VIEW);
                }
                $tmpl->addBreadcrumb($info['id'] . ': ' . $info['name'], '/docs.php?l=agent&mode=srv&opt=ep&pos=' . $pos);
            } else {
                throw new \NotFoundException('Агент не найден');
            }
            $editor = new \ListEditors\agentContactEditor($db);
            $editor->line_var_name = 'leid';
            $editor->opt_var_name = 'leopt';
            $editor->link_prefix = '/docs.php?l=agent&amp;mode=srv&amp;opt=ep&amp;param=c&amp;pos=' . $pos;
            $editor->acl_object_name = 'directory.agent';
            $editor->agent_id = $pos;
            $editor->run();
        } else if ($param == 'h') {
            $tmpl->addBreadcrumb('Агенты', '/docs.php?l=agent');

            $ares = $db->query("SELECT * FROM `doc_agent` WHERE `id` = $pos");
            if ($ares->num_rows) {
                $agent_info = $ares->fetch_assoc();
                if(!\acl::testAccess('directory.agent.global', \acl::VIEW)) {
                    \acl::accessGuard('directory.agent.ingroup.'.$agent_info['group'], \acl::VIEW);
                }
                $tmpl->addBreadcrumb($agent_info['id'] . ': ' . $agent_info['name'], '/docs.php?l=agent&mode=srv&opt=ep&pos=' . $pos);
                $tmpl->addBreadcrumb('История правок', '');
            } else {
                throw new \NotFoundException('Агент не найден');
            }
            $logview = new \LogView();
            $logview->setObject('agent');
            $logview->setObjectId($pos);
            $logview->showLog();
        }
        // Банковские реквизиты
        elseif ($param == 'b') {
            $ares = $db->query("SELECT * FROM `doc_agent` WHERE `id` = $pos");
            if ($ares->num_rows) {
                $agent_info = $ares->fetch_assoc();
                if(!\acl::testAccess('directory.agent.global', \acl::VIEW)) {
                    \acl::accessGuard('directory.agent.ingroup.'.$agent_info['group'], \acl::VIEW);
                }
                $tmpl->addBreadcrumb($agent_info['id'] . ': ' . $agent_info['name'], '/docs.php?l=agent&mode=srv&opt=ep&pos=' . $pos);
            } else {
                throw new \NotFoundException('Агент не найден');
            }
            $editor = new \ListEditors\agentBankEditor($db);
            $editor->line_var_name = 'leid';
            $editor->opt_var_name = 'leopt';
            $editor->link_prefix = '/docs.php?l=agent&amp;mode=srv&amp;opt=ep&amp;param=b&amp;pos=' . $pos;
            $editor->acl_object_name = 'directory.agent';   /// TODO: Поменять
            $editor->agent_id = $pos;
            $editor->run();
        }
        // Правка описания группы
        else if ($param == 'g') {
            \acl::accessGuard('directory.agent.groups', \acl::VIEW);
            $res = $db->query("SELECT `id`, `name`, `desc`, `pid` FROM `doc_agent_group` WHERE `id`='$group'");
            $nxt = $res->fetch_row();
            $tmpl->addContent("<h1>Описание группы</h1>
                <form action='docs.php' method='post'>
                <input type='hidden' name='mode' value='esave'>
                <input type='hidden' name='l' value='agent'>
                <input type='hidden' name='g' value='$nxt[0]'>
                <input type='hidden' name='param' value='g'>
                <table cellpadding='0' width='50%'>
                <tr><td>Наименование группы $nxt[0]:</td><td><input type=text name='name' value='" . html_out($nxt[1]) . "'></td></tr>
                <tr class=lin0>
                <td>Находится в группе:
                <td>" . selectAgentGroup('pid', $nxt[3], true) . "</td>
                <tr class=lin1>
                <td>Описание:
                <td><textarea name='desc'>" . html_out($nxt[2]) . "</textarea>
                <tr class=lin0><td colspan=2 align=center>
                <input type='submit' value='Сохранить'>
                </table>
                </form>");
        } else {
            throw new \NotFoundException("Неизвестная закладка");
        }
    }

    function ESave() {
        global $tmpl, $db, $CONFIG;
        doc_menu();
        $pos = rcvint('pos');
        $param = request('param');
        $group = rcvint('g');
        $tmpl->setTitle("Правка агента");
        if ($param == '' || $param == 'v') {
            $ag_info = $db->selectRowA('doc_agent', $pos, $this->agent_vars);
            unset($ag_info['id']);
            if (!$ag_info['p_agent']) {
                $ag_info['p_agent'] = 'NULL';
            }

            $new_agent_info = array();
            foreach ($this->agent_vars as $value) {
                $new_agent_info[$value] = request($value);
            }
            if (request('p_agent_nm')) {
                $new_agent_info['p_agent'] = rcvint('p_agent');
            } else {
                $new_agent_info['p_agent'] = 'NULL';
            }
            $new_agent_info['group'] = rcvint('g');

            settype($ag_info['group'], 'int');
            settype($ag_info['dishonest'], 'int');
            settype($new_agent_info['group'], 'int');
            settype($new_agent_info['dishonest'], 'int');
            settype($ag_info['no_retail_prices'], 'int');
            settype($new_agent_info['no_retail_prices'], 'int');
            settype($ag_info['no_bulk_prices'], 'int');
            settype($new_agent_info['no_bulk_prices'], 'int');
            settype($ag_info['no_bonuses'], 'int');
            settype($new_agent_info['no_bonuses'], 'int');
            
            if (!\acl::testAccess('directory.agent.ext', \acl::UPDATE)) {
                unset($new_agent_info['responsible']);
                unset($new_agent_info['data_sverki']);
                unset($ag_info['responsible']);
                unset($ag_info['data_sverki']);
            }

            if (@$CONFIG['agents']['leaf_only']) {
                $new_group = $new_agent_info['group'];
                $res = $db->query("SELECT `id` FROM `doc_agent_group` WHERE `pid`=$new_group");
                if ($res->num_rows)
                    throw new Exception("Запись агента возможна только в конечную группу!");
            }

            $log_text = getCompareStr($ag_info, $new_agent_info);

            //if( (!preg_match('/^\w+([-\.\w]+)*\w@\w(([-\.\w])*\w+)*\.\w{2,8}$/', $new_agent_info['email'])) && ($new_agent_info['email']!='') )
            //	throw new Exception("Неверный e-mail!");
            if ($pos) {
                if(!\acl::testAccess('directory.agent.global', \acl::UPDATE)) {
                    \acl::accessGuard('directory.agent.ingroup.'.$ag_info['group'], \acl::UPDATE);
                    \acl::accessGuard('directory.agent.ingroup.'.$new_agent_info['group'], \acl::UPDATE);
                }
                $log_start = 'UPDATE';
                $db->updateA('doc_agent', $pos, $new_agent_info);
                $this->PosMenu($pos, '');
                $tmpl->msg("Данные обновлены!");
                $this->showMainForm($pos, $new_agent_info['group']);
            } else {
                $log_start = 'CREATE';
                $new_agent_info['responsible'] = $_SESSION['uid'];
                if(!\acl::testAccess('directory.agent.global', \acl::CREATE)) {
                    \acl::accessGuard('directory.agent.ingroup.'.$new_agent_info['group'], \acl::CREATE);
                }

                $pos = $db->insertA('doc_agent', $new_agent_info);
                $this->PosMenu($pos, '');
                $tmpl->msg("Добавлена новая запись!");
                $this->showMainForm($pos, $new_agent_info['group']);
            }
            doc_log($log_start, $log_text, 'agent', $pos);
            
        } else if ($param == 'g') {
            $new_data = array(
                'name' => request('name'),
                'desc' => request('desc'),
                'pid' => rcvint('pid')
            );
            if ($group) {
                \acl::accessGuard('directory.agent.groups', \acl::UPDATE);
                $old_data = $db->selectRowAi('doc_agent_group', $group, $new_data);
                $log_text = getCompareStr($old_data, $new_data);
                $db->updateA('doc_agent_group', $group, $new_data);
                doc_log('UPDATE', $log_text, 'agent_group', $group);
            } else {
                \acl::accessGuard('directory.agent.groups', \acl::CREATE);
                $old_data = array();
                foreach ($new_data as $id => $value) {
                    $old_data[$id] = '';
                }
                $log_text = getCompareStr($old_data, $new_data);
                $db->insertA('doc_agent_group', $new_data);
                doc_log('CREATE', $log_text, 'agent_group', $group);
            }
            $tmpl->msg("Сохранено!");
        } else {
            throw new \NotFoundException("Неизвестная закладка");
        }
    }

    /// Сформировать один и все вложенные уровни списка групп агентов
    function draw_level($select, $level) {
        global $db;
        settype($level, 'int');
        $ret = '';
        $res = $db->query("SELECT `id`, `name`, `desc` FROM `doc_agent_group` WHERE `pid`='$level' ORDER BY `name`");
        $i = 0;
        $r = '';
        if ($level == 0) {
            $r = 'IsRoot';
        }
        while ($nxt = $res->fetch_row()) {
            if ($nxt[0] == 0) {
                continue;
            }
            if(!\acl::testAccess([ 'directory.agent.global', 'directory.agent.ingroup.'.$nxt[0]], \acl::VIEW)) {
                continue;
            }
            $item = "<a href='' title='$nxt[2]' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&g=$nxt[0]','list'); return false;\" >" . html_out($nxt[1]) . "</a>";
            if ($i >= ($res->num_rows - 1)) {
                $r.=" IsLast";
            }

            $tmp = $this->draw_level($select, $nxt[0]); // рекурсия
            if ($tmp) {
                $ret.="<li class='Node ExpandClosed $r'>
                    <div class='Expand'></div>
                    <div class='Content'>$item
                    </div><ul class='Container'>" . $tmp . '</ul></li>';
            } else {
                $ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
            }
            $i++;
        }
        return $ret;
    }

    /// Отобразить список групп агентов
    function draw_groups($select) {
        global $tmpl, $db;
        $tmpl->addContent("
            Фильтр:<input type='text' id='f_search' onkeydown=\"DelayedSave('/docs.php?l=agent&mode=srv&opt=pl','list', 'f_search'); return true;\" ><br>
            <div onclick='tree_toggle(arguments[0])'>
            <div><a href='' title='' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&g=0','list'); return false;\" >Группы</a> (<a href='/docs.php?l=agent&mode=edit&param=g&g=0'><img src='/img/i_add.png' alt=''></a>)</div>
            <ul class='Container'>" . $this->draw_level($select, 0) . "</ul></div>
            <hr>");
        $res = $db->query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
        while ($nx = $res->fetch_row()) {
            $m = ($_SESSION['uid'] == $nx[0]) ? ' (МОИ)' : '';
            $tmpl->addContent("<a href='' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&resp=$nx[0]','list'); return false;\">Агенты " . html_out($nx[1]) . "{$m}</a><br>");
        }
        $tmpl->addContent("<br><a href='' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&resp=0','list'); return false;\">Непривязанные агенты</a>");
    }

    /// Отобразить список агентов из заданной группы
    function ViewList($group = 0) {
        global $tmpl, $db;
        
        if (isset($_REQUEST['resp'])) {
            $this->ViewListRespFiltered(request('resp'));
        } else {
            \acl::accessGuard([ 'directory.agent.global', 'directory.agent.ingroup.'.$group], \acl::VIEW);
            if ($group) {
                $desc_data = $db->selectRow('doc_agent_group', $group);
                if ($desc_data['desc']) {
                    $tmpl->addContent('<p>' . html_out($desc_data['desc']) . '</p>');
                }
            }

            $sql = "SELECT `doc_agent`.`id`, `doc_agent`.`group`, `doc_agent`.`name`, `doc_agent`.`type`, `doc_agent`.`fullname`,
                `doc_agent`.`pfio`, `users`.`name` AS `responsible_name`, `doc_agent`.`dishonest`
                    , (SELECT `value` FROM `agent_contacts` WHERE `agent_contacts`.`agent_id`=`doc_agent`.`id` AND `agent_contacts`.`type`='phone' LIMIT 1) AS `phone`
                    , (SELECT `value` FROM `agent_contacts` WHERE `agent_contacts`.`agent_id`=`doc_agent`.`id` AND `agent_contacts`.`type`='email' LIMIT 1) AS `email`
                FROM `doc_agent`
                LEFT JOIN `users` ON `doc_agent`.`responsible`=`users`.`id`
                WHERE `doc_agent`.`group`='$group'
                ORDER BY `doc_agent`.`name`";

            $lim = 150;
            $page = rcvint('p');
            $res = $db->query($sql);
            $row = $res->num_rows;
            if ($row > $lim) {
                $dop = "g=$group";
                if ($page < 1) {
                    $page = 1;
                }
                if ($page > 1) {
                    $i = $page - 1;
                    $tmpl->addContent("<a href='' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&$dop&p=$i','list'); return false;\">&lt;&lt;</a> ");
                }
                $cp = $row / $lim;
                for ($i = 1; $i < ($cp + 1); $i++) {
                    if ($i == $page) {
                        $tmpl->addContent(" <b>$i</b> ");
                    } else {
                        $tmpl->addContent("<a href='' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&$dop&p=$i','list'); return false;\">$i</a> ");
                    }
                }
                if ($page < $cp) {
                    $i = $page + 1;
                    $tmpl->addContent("<a href='' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&$dop&p=$i','list'); return false;\">&gt;&gt;</a> ");
                }
                $tmpl->addContent("<br>");
                $sl = ($page - 1) * $lim;

                $res->data_seek($sl);
            }

            if ($row) {
                $tmpl->addContent("<table class='list' width='100%' cellspacing='1' cellpadding='2'>
				<tr><th>№</th><th>Название</th><th>Телефон</th><th>e-mail</th><th>Дополнительно</th><th>Отв.менеджер</th></tr>");
                $this->DrawTable($res, '', $lim);
                $tmpl->addContent("</table>");
            } else {
                $tmpl->msg("В выбранной группе записей не найдено!");
            }
            $tmpl->addContent("
                <a href='/docs.php?l=agent&mode=srv&opt=ep&pos=0&g=$group'><img src='/img/i_add.png' alt=''> Добавить</a> |
                <a href='/docs.php?l=agent&mode=edit&param=g&g=$group'><img src='/img/i_edit.png' alt=''> Правка группы</a> |
                <a href='/docs.php?l=agent&mode=search'><img src='/img/i_find.png' alt=''> Расширенный поиск</a>");
        }
    }

    /// Отобразить список агентов, отфильторванный по заданной строке
    function ViewListS($s = '') {
        global $tmpl, $db;
        $sf = 0;
        $tmpl->addContent("<b>Показаны записи изо всех групп!</b><br>");
        $tmpl->addContent("<table class='list' width='100%' cellspacing='1' cellpadding='2'>
		<tr><th>№</th><th>Название</th><th>Телефон</th><th>e-mail</th><th>Дополнительно</th><th>Отв.менеджер</th></tr>");
        $s_sql = $db->real_escape_string($s);
        $sql = "SELECT `doc_agent`.`id`, `doc_agent`.`group`, `doc_agent`.`name`, `doc_agent`.`type`, `doc_agent`.`fullname`,
                    `doc_agent`.`pfio`, `users`.`name` AS `responsible_name`, `doc_agent`.`dishonest`
                    , (SELECT `value` FROM `agent_contacts` WHERE `agent_contacts`.`agent_id`=`doc_agent`.`id` AND `agent_contacts`.`type`='phone' LIMIT 1) AS `phone`
                    , (SELECT `value` FROM `agent_contacts` WHERE `agent_contacts`.`agent_id`=`doc_agent`.`id` AND `agent_contacts`.`type`='email' LIMIT 1) AS `email`

		FROM `doc_agent`
		LEFT JOIN `users` ON `doc_agent`.`responsible`=`users`.`id`";

        $sqla = $sql . "WHERE `doc_agent`.`name` LIKE '$s_sql%' OR `doc_agent`.`fullname` LIKE '$s_sql%' ORDER BY `doc_agent`.`name` LIMIT 30";
        $res = $db->query($sqla);
        if ($res->num_rows) {
            $tmpl->addContent("<tr><th colspan='16' align='center'>Фильтр по названию, начинающемуся на " . html_out($s) . ": {$res->num_rows} строк найдено</th></tr>");
            $this->DrawTable($res, $s);
            $sf = 1;
        }

        $sqla = $sql . "WHERE (`doc_agent`.`name` LIKE '%$s_sql%' OR `doc_agent`.`fullname` LIKE '%$s_sql%') AND (`doc_agent`.`name` NOT LIKE '$s_sql%' AND `doc_agent`.`fullname` NOT LIKE '$s_sql%') ORDER BY `doc_agent`.`name` LIMIT 30";
        $res = $db->query($sqla);
        if ($res->num_rows) {
            $tmpl->addContent("<tr><th colspan='16' align='center'>Фильтр по названию, содержащему " . html_out($s) . ": {$res->num_rows}  строк найдено</th></tr>");
            $this->DrawTable($res, $s);
            $sf = 1;
        }

        $tmpl->addContent("</table><a href='/docs.php?l=agent&mode=srv&opt=ep&pos=0&g=0'><img src='/img/i_add.png' alt=''> Добавить</a>");

        if ($sf == 0)
            $tmpl->msg("По данным критериям записей не найдено!");
    }

    /// Список агентов с фильтрацией по ответственному сотруднику
    function ViewListRespFiltered($resp) {
        global $tmpl, $db;
        settype($resp, 'int');
        $sf = 0;
        $tmpl->addContent("<table class='list' width='100%' cellspacing='1' cellpadding='2'>
		<tr><th>№</th><th>Название</th><th>Телефон</th><th>e-mail</th><th>Дополнительно</th><th>Ответственный</th></tr>");
        $sql = "SELECT `doc_agent`.`id`, `doc_agent`.`group`, `doc_agent`.`name`, `doc_agent`.`type`, `doc_agent`.`fullname`, `doc_agent`.`pfio`, `users`.`name` AS `responsible_name`, `doc_agent`.`dishonest`
            , (SELECT `value` FROM `agent_contacts` WHERE `agent_contacts`.`agent_id`=`doc_agent`.`id` AND `agent_contacts`.`type`='phone' LIMIT 1) AS `phone`
            , (SELECT `value` FROM `agent_contacts` WHERE `agent_contacts`.`agent_id`=`doc_agent`.`id` AND `agent_contacts`.`type`='email' LIMIT 1) AS `email`

		FROM `doc_agent`
		LEFT JOIN `users` ON `doc_agent`.`responsible`=`users`.`id`
		WHERE `doc_agent`.`responsible`='$resp'";
        $res = $db->query($sql);
        if ($res->num_rows) {
            $tmpl->addContent("<tr><th colspan='6' align='center'>Использован фильтр по ответственному. Найдено: {$res->num_rows}. ID: $resp");
            $this->DrawTable($res, '');
            $sf = 1;
        }
        $tmpl->addContent("</table>");
        if ($sf == 0) {
            $tmpl->msg("По данным критериям записей не найдено!");
        }
    }

    /// Расширенный поиск агентов
    function Search() {
        global $tmpl, $db;
        $opt = request("opt");
        if ($opt == '') {
            doc_menu();
            $tmpl->addContent("<h1>Расширенный поиск</h1>
			<form action='docs.php' method='post'>
			<input type='hidden' name='mode' value='search'>
			<input type='hidden' name='l' value='agent'>
			<input type='hidden' name='opt' value='s'>
			<table width='100%'>
			<tr><th>Наименование</th>
			<th>e-mail</th>
			<th>ИНН</th>
			<th>Телефон</th>
			</tr>
			<tr>
			<td><input type=text name='name'></td>
			<td><input type=text name='mail'></td>
			<td><input type=text name='inn'></td>
			<td><input type=text name='tel'></td>
			</tr>
			<tr>
			<th>Адрес</th>
			<th>Расчетный счет</th>
			<th>Контактное лицо</th>
			<th>Номер паспорта</th>
			</tr>
			<tr>
			<td><input type=text name='adres'></td>
			<td><input type=text name='rs'></td>
			<td><input type=text name='kont'></td>
			<td><input type=text name='pasp_num'></td>
			</tr>
			<tr>
			<td colspan='5' align='center'><input type='submit' value='Найти'></td>
			</tr>
			</table>
			</form>");
        } else if ($opt == 's') {
            doc_menu();
            $tmpl->addContent("<h1>Результаты</h1>");
            $name = $db->real_escape_string(request('name'));
            $mail = $db->real_escape_string(request('mail'));
            $inn = $db->real_escape_string(request('inn'));
            $tel = $db->real_escape_string(request('tel'));
            $adres = $db->real_escape_string(request('adres'));
            $rs = $db->real_escape_string(request('rs'));
            $kont = $db->real_escape_string(request('kont'));
            $pasp_num = rcvint('pasp_num');

            $sql = "SELECT `doc_agent`.`id`, `doc_agent`.`group`, `doc_agent`.`name`, `doc_agent`.`tel`, `doc_agent`.`email`, `doc_agent`.`type`, `doc_agent`.`fullname`, `doc_agent`.`pfio`, `users`.`name`, `doc_agent`.`dishonest`, `doc_agent`.`fax_phone`, `doc_agent`.`sms_phone`, `doc_agent`.`alt_phone`
			FROM `doc_agent`
			LEFT JOIN `users` ON `doc_agent`.`responsible`=`users`.`id`
			WHERE 1";

            if ($name)
                $sql.=" AND (`doc_agent`.`name` LIKE '%$name%' OR `doc_agent`.`fullname` LIKE '%$name%')";
            if ($mail)
                $sql.=" AND `doc_agent`.`email` LIKE '%$mail%'";
            if ($inn)
                $sql.=" AND `doc_agent`.`inn` LIKE '%$inn%'";
            if ($tel)
                $sql.=" AND `doc_agent`.`tel` LIKE '%$tel%'";
            if ($adres)
                $sql.=" AND `doc_agent`.`adres` LIKE '%$adres%'";
            if ($rs)
                $sql.=" AND `doc_agent`.`rs` LIKE '%$rs%'";
            if ($kont)
                $sql.=" AND `doc_agent`.`kont` LIKE '%$kont%'";
            if ($pasp_num)
                $sql.=" AND `doc_base_dop`.`size` LIKE '%$pasp_num%'";

            $sql.=" ORDER BY `doc_agent`.`name`";

            $tmpl->addContent("<table class='list' width='100%' cellspacing='1' cellpadding='2'><tr>
			<th>№</th><th>Название</th><th>Телефон</th><th>e-mail</th><th>Дополнительно</th><th>Ответственный</th></tr>");
            $res = $db->query($sql);
            if ($res->num_rows) {
                $tmpl->addContent("<tr><th colspan='16' align='center'>Параметрический поиск, найдено {$res->num_rows} агентов</th></tr>");
                $this->DrawTable($res, request('name'));
            } else
                $tmpl->msg("По данным критериям записей не найдено!");
            $tmpl->addContent("</table>");
        }
    }

    /// Отобразить строки таблицы агентов
    /// @param res		Объект mysqli_result, возвращенный запросом списка агентов
    /// @param s		Строка поиска. Будет подсвечена в данных
    /// @param limit	Ограничение на количество выводимых строк
    function DrawTable($res, $s, $limit = 1000) {
        global $tmpl;
        $c = 0;
        while ($nxt = $res->fetch_assoc()) {
            if(!\acl::testAccess('directory.agent.global', \acl::VIEW)  &&
                !\acl::testAccess('directory.agent.ingroup.'.$nxt['group'], \acl::VIEW)) {
                continue;
            }
            $name = SearchHilight(html_out($nxt['name']), $s);
            if ($nxt['type']) {
                $info = $nxt['pfio'];
            } else {
                $info = $nxt['fullname'];
            }
            $info = SearchHilight(html_out($info), $s);
            $red = $nxt['dishonest'] ? "style='color: #f00;'" : '';
            if ($nxt['email']) {
                $email = "<a href='mailto:" . html_out($nxt['email']) . "'>" . html_out($nxt['email']) . "</a>";
            } else {
                $email = '';
            }
            $phone_info = '';
            if ($nxt['phone']) {
                $phone_info.='тел. ' . formatPhoneNumber($nxt['phone']) . ' ';
            }
           
            $tmpl->addContent("<tr class='pointer' align='right' $red oncontextmenu=\"ShowAgentContextMenu(event,{$nxt['id']}); return false;\">
                <td><a href='/docs.php?l=agent&mode=srv&opt=ep&pos={$nxt['id']}'>{$nxt['id']}</a>
		<a href='' onclick=\"ShowAgentContextMenu(event,{$nxt['id']}); return false;\" title='Меню' accesskey=\"S\"><img src='img/i_menu.png' alt='Меню' border='0'></a></td>
			<td align='left'>$name<td>$phone_info</td><td>$email</td><td>$info</td><td>" . html_out($nxt['responsible_name']) . "</td></tr>");
            if ($c++ >= $limit)
                break;
        }
    }

    /// Меню элемента (закладки)
    function PosMenu($pos, $param) {
        global $tmpl;
        $items = array('v' => 'Основные', 'c' => 'Контакты', 'b' => 'Банковские реквизиты', 'h' => 'История');
        if ($param == '') {
            $param = 'v';
        }
        $link = "/docs.php?l=agent&amp;mode=srv&amp;opt=ep&amp;pos=$pos";
        $tmpl->addContent("<ul class='tabs'>");
        foreach ($items as $id => $name) {
            $sel = $param == $id ? " class='selected'" : '';
            $tmpl->addContent("<li><a href='{$link}&amp;param={$id}'{$sel}>$name</a></li>");
        }
        $tmpl->addContent("</ul>");
    }

}
