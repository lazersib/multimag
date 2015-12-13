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

namespace modules\admin;

/// Администрирование пользователей
class users extends \IModule {

    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'admin.users';
    }

    public function getName() {
        return 'Администрирование пользователей';
    }
    
    public function getDescription() {
        return 'Модуль для управления пользователями сайта.';  
    }
    
    protected function viewList() {
        global $tmpl, $db;
        \acl::accessGuard($this->acl_object_name, \acl::VIEW);
        
        $ll_dates = array();
        $res = $db->query("SELECT `user_id`, `date` FROM `users_login_history` ORDER BY `date`");
        while ($line = $res->fetch_assoc()) {
            $ll_dates[$line['user_id']] = $line['date'];
        }

        $order = '`users`.`id`';
        $res = $db->query("SELECT `users`.`id`, `users`.`name`, `users`.`reg_email`, `users`.`reg_email_confirm`, `users`.`reg_email_subscribe`,
            `users`.`reg_phone`, `users`.`reg_phone_confirm`, `users`.`reg_phone_subscribe`, `users`.`reg_date`, `users`.`real_name`
                , `users_worker_info`.`worker`
            FROM `users`
            LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id`
            ORDER BY $order");        
        $tmpl->setTitle('Список пользователей');
        $tmpl->addContent("
            <table class='list' width='100%'>
            <tr><th rowspan='2'>ID</th>
            <th rowspan='2'>Логин</th>
            <th colspan='3'>email</th>
            <th colspan='3'>Телефон</th>
            <th rowspan='2'>Имя</th>
            <th rowspan='2'>Последнее посещение</th>
            <th rowspan='2'>Сотрудник?</th>
            <th rowspan='2'>Дата регистрации</th>
            </tr>
            <tr>
            <th>адрес</th><th>С</th><th>S</th>
            <th>номер</th><th>С</th><th>S</th>
            </tr>");


        while ($line = $res->fetch_assoc()) {
            $line['lastlogin_date'] = $econfirm = $esubscribe = $pconfirm = $psubscribe = $p_email = $ll_date = '';
            if (isset($ll_dates[$line['id']])) {
                $ll_date = $ll_dates[$line['id']];
                $ll_time = strtotime($ll_date);
                if ($ll_time > 0) {
                    $ll_date = date("Y-m-d", $ll_time);
                    if ((time() - $ll_time) < 60 * 60 * 24 * 45) {
                        $ll_date = "<b style='color:#080'>$ll_date</b>";
                    } else if ((time() - $ll_time) > 60 * 60 * 24 * 365) {
                        $ll_date = "<b style='color:#c00'>$ll_date</b>";
                    }
                } else {
                    $ll_date = '';
                }
            }
            if ($line['reg_email']) {
                $econfirm = $line['reg_email_confirm'] == '1' ? 'да' : '<b style="color:#c00">нет</b>';
                $esubscribe = $line['reg_email_subscribe'] ? 'да' : '<b style="color:#c00">нет</b>';
                $p_email = "<a href='mailto:{$line['reg_email']}'>{$line['reg_email']}</a>";
            }
            if ($line['reg_phone']) {
                $pconfirm = $line['reg_phone_confirm'] == '1' ? 'да' : '<b style="color:#c00">нет</b>';
                $psubscribe = $line['reg_phone_subscribe'] ? 'да' : '<b style="color:#c00">нет</b>';
            }
            $worker = $line['worker'] ? '<b style="color:#080">да</b>' : '';

            $tmpl->addContent("<tr><td><a href='{$this->link_prefix}&amp;sect=view&amp;user_id={$line['id']}'>{$line['id']}</a></td><td>{$line['name']}</td>
                <td>$p_email</td><td>$econfirm</td><td>$esubscribe</td>
                <td>{$line['reg_phone']}</td><td>$pconfirm</td><td>$psubscribe</td>
                <td>" . html_out($line['real_name']) . "</td>
                <td>$ll_date</td><td>$worker</td><td>{$line['reg_date']}</td></tr>");
        }
        $tmpl->addContent("</table>");
        
    }

    protected function viewProfile($user_id) {
        global $tmpl, $db;
        \acl::accessGuard($this->acl_object_name, \acl::VIEW);
        

        $res = $db->query("SELECT * FROM `users`
	LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`users`.`id`
	WHERE `id`='$user_id'");
        if (!$res->num_rows) {
            throw new \NotFoundException("Пользователь не найден!");
        }
        $line = $res->fetch_assoc();

        $passch = $line['pass_change'] ? 'Да' : 'Нет';
        $passexp = $line['pass_expired'] ? 'Да' : 'Нет';
        switch ($line['pass_type']) {
            case 'CRYPT': $pass_hash = 'Сильная';
                break;
            case 'SHA1': $pass_hash = 'Средняя';
                break;
            case '':
            case 'MD5': $pass_hash = 'Слабая';
                break;
            default: {
                    if ($line['pass'] == '0') {
                        $pass_hash = 'Пароль не задан';
                    } else {
                        $pass_hash = 'Не определена';
                    }
                }
        }
        $bifact = $line['bifact_auth'] ? 'Да' : 'Нет';
        $econfirm = $line['reg_email_confirm'] == '1' ? 'Да' : 'Нет';
        $esubscribe = $line['reg_email_subscribe'] ? 'Да' : 'Нет';
        $p_email = $line['reg_email'] ? "<a href='mailto:{$line['reg_email']}'>{$line['reg_email']}</a>" : '';
        $pconfirm = $line['reg_phone_confirm'] == '1' ? 'Да' : 'Нет';
        $psubscribe = $line['reg_phone_subscribe'] ? 'Да' : 'Нет';
        $diasbled = $line['disabled'] ? ('Да, ' . $line['disabled_reason']) : 'Нет';
        $worker = $line['worker'] ? 'Да' : 'Нет';

        $tmpl->addContent("<h1 id='page-title'>Информация о пользователе с ID $user_id</h1>
		<table class='list'>
		<tr><th colspan='2'>Основная информация (<a href='/adm.php?mode=users&amp;sect=view&amp;user_id=$user_id'>править</a>)</th></tr>
		<tr><td>ID</td><td>{$line['id']}</td></tr>
		<tr><td>Имя</td><td>" . html_out($line['name']) . "</td></tr>
		<tr><td>Дата регистрации</td><td>{$line['reg_date']}</td></tr>
		<tr><td>Заблокирован (забанен)</td><td>$diasbled</td></tr>
		<tr><td>Меняет пароль?</td><td>$passch</td></tr>
		<tr><td>Смна пароля при след. входе?</td><td>$passexp</td></tr>
		<tr><td>Дата смены пароля</td><td>{$line['pass_date_change']}</td></tr>
		<tr><td>Стойкость хэша пароля</td><td>$pass_hash</td></tr>
		<tr><td>Двухфакторная аутентификация</td><td>$bifact</td></tr>
		<tr><td>Регистрационный email</td><td>$p_email</td></tr>
		<tr><td>emal подтверждён?</td><td>$econfirm</td></tr>
		<tr><td>email подписан?</td><td>$esubscribe</td></tr>
		<tr><td>Регистрационный телефон</td><td><a href='mailto:{$line['reg_phone']}'>{$line['reg_phone']}</a></td></tr>
		<tr><td>телефон подтверждён?</td><td>$pconfirm</td></tr>
		<tr><td>телефон подписан?</td><td>$psubscribe</td></tr>
		<tr><td>Jabber ID</td><td>{$line['jid']}</td></tr>
		<tr><td>Настоящее имя</td><td>" . html_out($line['real_name']) . "</td></tr>
		<tr><td>Адрес доставки заказов</td><td>" . html_out($line['real_address']) . "</td></tr>
		<tr><th colspan='2'>Связь с агентами</th></tr>");
        if (!$line['agent_id']) {
            $tmpl->addContent("<tr><td colspan='2'>Связь отсутствует</td></tr>");
        } else {
            $res = $db->query("SELECT `id`, `name`, `fullname`, `adres`, `data_sverki` FROM `doc_agent` WHERE `id`='{$line['agent_id']}'");
            $adata = $res->fetch_assoc();
            $tmpl->addContent("
                <tr><td>ID агента</td><td><a href='/docs.php?l=agent&mode=srv&opt=ep&pos={$adata['id']}'>{$adata['id']}</a> - <a href='/adm.php?mode=users&amp;sect=view&amp;user_id=$user_id'>Убрать связь</a></td></tr>
                <tr><td>Краткое название</td><td>" . html_out($adata['name']) . "</td></tr>
                <tr><td>Полное название</td><td>" . html_out($adata['fullname']) . "</td></tr>			
                <tr><td>Адрес</td><td>" . html_out($adata['adres']) . "</td></tr>
                <tr><td>Дата сверки</td><td>" . html_out($adata['data_sverki']) . "</td></tr>");

            $c_editor = new \ListEditors\agentContactEditor($db);
            $res = $db->query("SELECT `context`, `type`, `value` FROM `agent_contacts` WHERE `agent_id`='{$line['agent_id']}'");
            while ($c_info = $res->fetch_assoc()) {
                $name = '';
                $value = $c_info['value'];
                if (isset($c_editor->context_list[$c_info['context']])) {
                    $name .= $c_editor->context_list[$c_info['context']] . ' ';
                }
                if (isset($c_editor->types_list[$c_info['type']])) {
                    $name .= $c_editor->types_list[$c_info['type']] . ' ';
                }
                if ($c_info['type'] == 'email') {
                    $value = "<a href='mailto:" . html_out($c_info['value']) . "'>" . html_out($c_info['value']) . "</a>";
                }
                $tmpl->addContent("<tr><td>$name</td><td>$value</td></tr>");
            }
        }
        $tmpl->addContent("
            <tr><th colspan='2'>Карточка сотрудника</th></tr>
            <tr><td>Является сотрудником</td><td>$worker</td></tr>");
        if ($line['worker']) {
            $tmpl->addContent("<tr><td>Рабочий email</td><td><a href='mailto:{$line['worker_email']}'>{$line['worker_email']}</a></td></tr>
                <tr><td>Рабочий телефон</td><td>" . html_out($line['worker_phone']) . "</td></tr>
                <tr><td>Рабочий Jabber</td><td>" . html_out($line['worker_jid']) . "</td></tr>
                <tr><td>Рабочее имя</td><td>" . html_out($line['worker_real_name']) . "</td></tr>
                <tr><td>Рабочий адрес</td><td>" . html_out($line['worker_real_address']) . "</td></tr>");
        }

        $tmpl->addContent("<tr><th colspan='2'>Дополнительная информация</th></tr>");
        $res = $db->query("SELECT `param`, `value` FROM `users_data` WHERE `uid`='$user_id'");
        while ($line = $res->fetch_row()) {
            $tmpl->addContent("<tr><td>$line[0]</td><td>" . html_out($line[1]) . "</td></tr>");
        }
        $tmpl->addContent("</table>");
        $tmpl->addContent("<a href='/adm.php?mode=acl&amp;sect=user_acl&amp;user_id=$user_id'>Править индивидуальные привилегии</a>");
    }
    
    protected function viewLoginHistory($user_id) {
        global $tmpl, $db;
        \acl::accessGuard($this->acl_object_name, \acl::VIEW);
        $tmpl->addContent("<h1 id='page-title'>История входов пользователя с ID $user_id</h1>
        <table class='list'>
        <tr><th>Дата/время</th><th>Метод</th><th>IP адрес</th><th>user-agent</th></tr>");
        $res = $db->query("SELECT `date`, `method`, `ip`, `useragent` FROM `users_login_history` WHERE `user_id`='$user_id' ORDER BY `date` DESC");
        while ($line = $res->fetch_row()) {
                $tmpl->addContent("<tr><td>$line[0]</td><td>$line[1]</td><td>$line[2]</td><td>$line[3]</td></tr>");
        }

        $tmpl->addContent("</table>");
    }
    
    protected function saveWorkerCard($user_id) {
        global $db;
        \acl::accessGuard($this->acl_object_name, \acl::UPDATE);

        $db->query("REPLACE `users_worker_info` (`user_id`, `worker`, `worker_email`, `worker_phone`, `worker_jid`, `worker_real_name`,
            `worker_real_address`, `worker_post_name`) VALUES 
            ($user_id, '" . $db->real_escape_string(rcvint('worker')) . "',
                '" . $db->real_escape_string(request('worker_email')) . "',
                '" . $db->real_escape_string(request('worker_phone')) . "',
                '" . $db->real_escape_string(request('worker_jid')) . "',
                '" . $db->real_escape_string(request('worker_real_name')) . "',
                '" . $db->real_escape_string(request('worker_real_address')) . "',
                '" . $db->real_escape_string(request('worker_post_name')) . "')");
            
    }

    protected function viewWorkerCardForm($user_id) {
        global $tmpl, $db;
        \acl::accessGuard($this->acl_object_name, \acl::VIEW);

        if (request('save')) {
            $this->saveWorkerCard($user_id);
            $tmpl->msg("Данные сохранены", "ok");
        }

        $worker_res = $db->query("SELECT * FROM `users_worker_info` WHERE `user_id`=$user_id");
        if ($worker_res->num_rows) {
            $worker_info = $worker_res->fetch_assoc();
        } else {
            $worker_info = array('worker' => 0, 'worker_email' => '', 'worker_phone' => '', 'worker_jid' => '', 'worker_real_name' => '',
                'worker_real_address' => '', 'worker_post_name' => '');
        }
        $worker_check = $worker_info['worker'] ? ' checked' : '';
        $tmpl->addContent("<h1 id='page-title'>Редактирование карточки сотрудника ID $user_id</h1>
            <form action='{$this->link_prefix}' method='post'>
            <input type='hidden' name='user_id' value='$user_id'>
            <input type='hidden' name='sect' value='we'>
            <input type='hidden' name='save' value='1'>
            <table class='list'>
            <tr><td>Рабочий email сотрудника:</td><td><input type='email' name='worker_email' value='" . html_out($worker_info['worker_email']) . "'></td></tr>
            <tr><td>Рабочий телефон сотрудника:</td><td><input type='text' name='worker_phone' value='" . html_out($worker_info['worker_phone']) . "'></td></tr>
            <tr><td>Рабочий jid сотрудника:</td><td><input type='text' name='worker_jid' value='" . html_out($worker_info['worker_jid']) . "'></td></tr>
            <tr><td>Имя и фамилия сотрудника:</td><td><input type='text' name='worker_real_name' value='" . html_out($worker_info['worker_real_name']) . "'></td></tr>
            <tr><td>Рабочий адрес сотрудника:</td><td><input type='text' name='worker_real_address' value='" . html_out($worker_info['worker_real_address']) . "'></td></tr>
            <tr><td>Должность сотрудника:</td><td><input type='text' name='worker_post_name' value='" . html_out($worker_info['worker_post_name']) . "'></td></tr>
            <tr><td></td><td><label><input type='checkbox' name='worker' value='1'{$worker_check}>Является сотрудником в настоящий момент</label></td></tr>
            <tr><td></td><td><button type='submit'>Сохранить</button></td></tr>
            </table>
            </form>");
    }
    
    protected function saveuserEditForm($user_id) {
        global $db;
        \acl::accessGuard($this->acl_object_name, \acl::UPDATE);
            $db->updateA('users', $user_id, array(
                'reg_email' => request('reg_email'),
                'reg_email_confirm' => request('reg_email_confirm'),
                'reg_email_subscribe' => request('reg_email_subscribe'),
                'reg_phone' => request('reg_phone'),
                'reg_phone_confirm' => request('reg_phone_confirm'),
                'reg_phone_subscribe' => request('reg_phone_subscribe'),
                'real_name' => request('real_name'),
                'real_address' => request('real_address'),
                'type' => request('type'),
                'pass_expired' => request('pass_expired'),
                'disabled' => request('disabled'),
                'disabled_reason' => request('disabled_reason'),
                'bifact_auth' => request('bifact_auth')
            ));
    }
    
    protected function viewUserEditForm($user_id) {
        global $tmpl, $db;
        \acl::accessGuard($this->acl_object_name, \acl::VIEW);

        if (request('save')) {
            $this->saveuserEditForm($user_id);
            $tmpl->msg("Данные сохранены", "ok");
        }

        $res = $db->query("SELECT * FROM `users` WHERE `id`=$user_id");
        $user_info = $res->fetch_assoc();

        $rec_check = $user_info['reg_email_confirm'] == '1' ? ' checked' : '';
        $res_check = $user_info['reg_email_subscribe'] ? ' checked' : '';

        $rpc_check = $user_info['reg_phone_confirm'] == '1' ? ' checked' : '';
        $rps_check = $user_info['reg_phone_subscribe'] ? ' checked' : '';

        $disabled_check = $user_info['disabled'] ? ' checked' : '';
        $pe_check = $user_info['pass_expired'] ? ' checked' : '';
        $bfa_check = $user_info['bifact_auth'] ? ' checked' : '';

        $tp_check = $user_info['type'] == 'p' ? ' checked' : '';
        $tc_check = $user_info['type'] == 'c' ? ' checked' : '';


        $tmpl->addContent("<h1 id='page-title'>Редактирование пользователя " . html_out($user_info['name']) . " c ID $user_id</h1>
		<form action='{$this->link_prefix}' method='post'>
                <input type='hidden' name='sect' value='edit'>
		<input type='hidden' name='user_id' value='$user_id'>
		<input type='hidden' name='save' value='1'>
		<table class='list'>
		<tr><th colspan='2'>Регистрационные и контактные данные</th></tr>
		<tr><td>Login:</td><td>" . html_out($user_info['name']) . "</td></tr>
		<tr><td>Регистрационный email:</td><td><input type='text' name='reg_email' value='" . html_out($user_info['reg_email']) . "'><br>
			<label><input type='checkbox' name='reg_email_confirm' value='1'{$rec_check}> Подтверждён</label><br>
			<label><input type='checkbox' name='reg_email_subscribe' value='1'{$res_check}> Подписан на рассылки и уведомления</label>
			</td></tr>
		<tr><td>Регистрационный мобильный телефон:</td><td><input type='text' name='reg_phone' value='" . html_out($user_info['reg_phone']) . "'><br>
			<label><input type='checkbox' name='reg_phone_confirm' value='1'{$rpc_check}> Подтверждён</label><br>
			<label><input type='checkbox' name='reg_phone_subscribe' value='1'{$rps_check}> Подписан на рассылки и уведомления</label>
			</td></tr>
		<tr><td>ФИО:</td><td><input type='text' name='real_name' value='" . html_out($user_info['real_name']) . "'></td></tr>
		<tr><td>Адрес доставки:</td><td><input type='text' name='real_address' value='" . html_out($user_info['real_address']) . "'></td></tr>
		<tr><td>Jabber ID:</td><td><input type='text' name='jid' value='" . html_out($user_info['jid']) . "'></td></tr>
		<tr><td>Тип:</td><td>
			<label><input type='radio' name='type' value='p'{$tp_check}> Физическое лицо</label><br>
			<label><input type='radio' name='type' value='c'{$tc_check}> Юридическое лицо</label><br>
		</td></tr>	
		<tr><th colspan='2'>Администрирование</th></tr>
		<tr><td>Пароль:</td><td><label><input type='checkbox' name='pass_expired' value='1'{$pe_check}> Требовать смену пароля</label></td></tr>
		<tr><td>Блокировка:</td><td><label><input type='checkbox' name='disabled' value='1'{$disabled_check}> Пользователь заблокирован</label></td></tr>
		<tr><td>Причина блокировки:</td><td><input type='text' name='disabled_reason' value='" . html_out($user_info['disabled_reason']) . "'></td></tr>
		<tr><td>Защита:</td><td><label><input type='checkbox' name='bifact_auth' value='1'{$bfa_check}> Двухфакторная аутентификация</label></td></tr>
		<tr><td></td><td><button type='submit'>Сохранить</button></td></tr>
		</table>
		</form>");
    }
    
    protected function viewAgentConnectForm($user_id) {
        global $db, $tmpl;
        \acl::accessGuard($this->acl_object_name, \acl::UPDATE);
        if (isset($_REQUEST['save'])) {
            if ($_REQUEST['agent_nm']) {
                $agent_id = $_REQUEST['agent_id'];
                settype($agent_id, 'int');
            } else {
                $agent_id = 'NULL';
            }
            $res = $db->query("UPDATE `users` SET `agent_id`=$agent_id WHERE `id`='$user_id'");
            $tmpl->msg("Привязка выполнена!", 'ok');
        }
        $res = $db->query("SELECT `users`.`agent_id`, `doc_agent`.`name` FROM `users`
			LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`users`.`agent_id`
			WHERE `users`.`id`='$user_id'");
        if (!$res->num_rows)
            throw new Exception("Пользователь не найден!");
        $line = $res->fetch_assoc();
        $tmpl->addContent("<h1>Привязка пользователя к агенту</h1>
            <form action='{$this->link_prefix}' method='post'>
            <input type='hidden' name='sect' value='agent'>
            <input type='hidden' name='user_id' value='$user_id'>
            <input type='hidden' name='save' value='save'>
            Краткое название прикрепляемого агента:<br>
            <script type='text/javascript' src='/css/jquery/jquery.js'></script>
            <script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
            <input type='hidden' name='agent_id' id='agent_id' value='{$line['agent_id']}'>
            <input type='text' id='agent_nm' name='agent_nm'  style='width: 450px;' value='" . html_out($line['name']) . "'><br>

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
                            formatItem:usliFormat,
                            onItemSelect:usselectItem,
                            extraParams:{'l':'agent','mode':'srv','opt':'ac'}
                    });
            });

            function usliFormat (row, i, num) {
                    var result = row[0] + \"<em class='qnt'>id: \" +
                    row[1] + \"</em> \";
                    return result;
            }
            function usselectItem(li) {
                    if( li == null ) var sValue = \"Ничего не выбрано!\";
                    if( !!li.extra ) var sValue = li.extra[0];
                    else var sValue = li.selectValue;
                    document.getElementById('agent_id').value=sValue;
            }
            </script>
            <input type='submit' value='Записать'>
            </form>");
    }

    public function run() {
        global $CONFIG, $tmpl, $db;
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect');
        $list = array(
            'view'   => ['name' => 'Основные'],
            'edit'   => ['name' => 'Редактор'],
            'agent'  => ['name' => 'Связь с агентом'],
            'we'     => ['name' => 'Карточка сотрудника'],
            'lh'     => ['name' => 'История входов'],
        );
        switch ($sect) {
            case '':
                $tmpl->addBreadcrumb($this->getName(), '');
                $this->viewList();
                break;
            case 'view':
                $user_id = rcvint('user_id');
                $tmpl->addBreadcrumb('Информация о пользователе с ID ' . $user_id, '');
                $tmpl->setTitle('Информация о пользователе с ID ' . $user_id);
                $tmpl->addTabsWidget($list, $sect, $this->link_prefix . "&amp;user_id=$user_id", 'sect');
                $this->viewProfile($user_id);
                break;
            case 'lh':
                $user_id = rcvint('user_id');
                $tmpl->addBreadcrumb('Информация о пользователе с ID ' . $user_id, '');
                $tmpl->setTitle('Информация о пользователе с ID ' . $user_id);
                $tmpl->addTabsWidget($list, $sect, $this->link_prefix . "&amp;user_id=$user_id", 'sect');
                $this->viewLoginHistory($user_id);
                break;
            case 'we':
                $user_id = rcvint('user_id');
                $tmpl->addBreadcrumb('Информация о пользователе с ID ' . $user_id, '');
                $tmpl->setTitle('Информация о пользователе с ID ' . $user_id);
                $tmpl->addTabsWidget($list, $sect, $this->link_prefix . "&amp;user_id=$user_id", 'sect');
                $this->viewWorkerCardForm($user_id);
                break;
            case 'edit':
                $user_id = rcvint('user_id');
                $tmpl->addBreadcrumb('Информация о пользователе с ID ' . $user_id, '');
                $tmpl->setTitle('Информация о пользователе с ID ' . $user_id);
                $tmpl->addTabsWidget($list, $sect, $this->link_prefix . "&amp;user_id=$user_id", 'sect');
                $this->viewUserEditForm($user_id);
                break;
            case 'agent':
                $user_id = rcvint('user_id');
                $tmpl->addBreadcrumb('Информация о пользователе с ID ' . $user_id, '');
                $tmpl->setTitle('Информация о пользователе с ID ' . $user_id);
                $tmpl->addTabsWidget($list, $sect, $this->link_prefix . "&amp;user_id=$user_id", 'sect');
                $this->viewAgentConnectForm($user_id);
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

}
