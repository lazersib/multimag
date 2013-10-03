<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2013, BlackLight, TND Team, http://tndproject.org
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
	/// Просмотр списка агентов
	function __construct()	{
		$this->agent_vars = array('group', 'name', 'type', 'email', 'fullname', 'tel', 'adres', 'gruzopol', 'inn', 'rs', 'ks', 'okevd', 'okpo',  'bank',  'bik', 'pfio', 'pdol', 'pasp_num', 'pasp_date', 'pasp_kem', 'comment', 'responsible', 'data_sverki', 'dir_fio', 'dir_fio_r', 'dishonest', 'p_agent', 'sms_phone', 'fax_phone', 'alt_phone');
	}
	function View() {
		global $tmpl;
		doc_menu(0,0);
		if(!isAccess('list_agent','view'))	throw new AccessException();
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
		if($opt=='pl') {
			$s = request('s');
			$tmpl->ajax=1;
			if($s)
				$this->ViewListS($s);
			else
				$this->ViewList($g);
		}
		else if($opt=='ep') {
			$this->Edit();
		}
		else if($opt=='acost') {
			$pos = rcvint('pos');
			$tmpl->ajax = 1;
			$tmpl->addContent( getInCost($pos) );
		}
		else if($opt=='popup') {
			$s = request('s');
			$tmpl->ajax = 1;
			$s_sql = $db->real_escape_string($s);
			$res = $db->query("SELECT `id`,`name` FROM `doc_agent` WHERE LOWER(`name`) LIKE LOWER('%$s_sql%') LIMIT 50");
			if($res->num_rows) {
				$tmpl->addContent("Ищем: $s ({$res->num_rows} совпадений)<br>");
				while($nxt = $res->fetch_row())
					$tmpl->addContent("<a onclick=\"return SubmitData('$nxt[1]',$nxt[0]);\">".html_out($nxt[1])."</a><br>");
			}
			else $tmpl->addContent("<b>Искомая комбинация не найдена!");
		}
		else if($opt=='ac') {
			$q = request('q');
			$tmpl->ajax = 1;
			$q_sql = $db->real_escape_string($q);
			$res = $db->query("SELECT `name`, `id`, `tel` FROM `doc_agent` WHERE LOWER(`name`) LIKE LOWER('%$q%') ORDER BY `name`");
			while($nxt = $res->fetch_row())
				$tmpl->addContent("$nxt[0]|$nxt[1]|$nxt[2]\n");
		}
		else $tmpl->msg("Неверный режим!");
	}

	// Редактирование справочника
	function Edit() {
		global $tmpl, $db;
		doc_menu();
		$pos = rcvint('pos');
		$param = request('param');
		$group = rcvint('g');
		if(!isAccess('list_agent','view'))	throw new AccessException();
		if(($pos==0)&&($param!='g')) $param='';

		if($pos!=0)
			$this->PosMenu($pos, $param);

		if($param=='') {
			$ares = $db->query("SELECT * FROM `doc_agent` WHERE `id` = $pos");
			if($ares->num_rows)
				$agent_info = $ares->fetch_assoc();
			else {
				$tmpl->addContent("<h3>Новая запись</h3>");
				$agent_info = array();
				foreach ($this->agent_vars as $value)
					$agent_info[$value] = '';				
			}

			$html_pagent_name='';
	
			if($agent_info['p_agent']>0) {
				$pagent_info = $db->selectRowA('doc_agent', $agent_info['p_agent'], array('name'));
				$html_pagent_name = html_out($pagent_info[0]);
			}
			
			$tmpl->setTitle("Правка агента ".html_out($agent_info['name']));
			$tmpl->addContent("<form action='' method='post' id='agent_edit_form'>
			<table cellpadding='0' width='100%' class='list'>
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='agent'>
			<input type='hidden' name='pos' value='$pos'>
			<tr><td align='right' width='20%'>Краткое наименование<br>
			<small>По этому полю выполняется поиск. Не пишите здесь аббревиатуры вроде OOO, ИП, МУП, итд. а так же кавычки и подобные символы!</small>
			<td><input type='text' name='name' value='".html_out($agent_info['name'])."' style='width: 90%;'>
			<tr><td align=right>Тип:
			<td>");
			if($agent_info['type']==0)
				$tmpl->addContent("<label><input type='radio' name='type' value='0' checked>Физическое лицо</label><br>
				<label><input type='radio' name='type' value='1'>Юридическое лицо</label>");
			else
				$tmpl->addContent("<label><input type='radio' name='type' value='0'>Физическое лицо</label><br>
				<label><input type='radio' name='type' value='1' checked>Юридическое лицо</label>");
			$tmpl->addContent("<tr><td align='right'>Группа</td>
        		<td><select name='g'>");

			if((($pos!=0)&&($agent_info['group']==0))||($group==0)) $i=" selected";
			$tmpl->addContent("<option value='NULL' $i>--</option>");

			$res = $db->query("SELECT * FROM `doc_agent_group`");
			while($nx = $res->fetch_row()) {
				$i="";
				if((($pos!=0)&&($nx[0]==$agent_info['group']))||($group==$nx[0])) $i=" selected";
				$tmpl->addContent("<option value='$nx[0]'$i>".html_out($nx[1])."</option>");
			}

			$ext='';
			if(!isAccess('doc_agent_ext', 'edit')) $ext='disabled';

			$tmpl->addContent("</select>
			<tr class=lin1><td align=right>Адрес электронной почты (e-mail)<td><input type=text name='email' value='".html_out($agent_info['email'])."' class='validate email'>
			<tr class=lin0><td align=right>Полное название / ФИО:<br><small>Так, как должно быть в документах</small><td><input type=text name='fullname' value='".html_out($agent_info['fullname'])."' style='width: 90%;'>
			<tr class=lin1><td align=right>Телефон:<br><small>В международном формате +XXXXXXXXXXX...<br>без дефисов, пробелов, и пр.символов</small><td><input type=text name='tel' value='".html_out($agent_info['tel'])."' class='phone validate'>
			<tr class=lin0><td align=right>Телефон / факс:<br><small>В международном формате +XXXXXXXXXXX...<br>без дефисов, пробелов, и пр.символов</small><td><input type=text name='fax_phone' value='".html_out($agent_info['fax_phone'])."' class='phone validate'>
			<tr class=lin1><td align=right>Телефон для sms:<br><small>В международном формате +XXXXXXXXXXX...<br>без дефисов, пробелов, и пр.символов</small><td><input type=text name='sms_phone' value='".html_out($agent_info['sms_phone'])."' class='phone validate'>
			<tr class=lin0><td align=right>Дополнительный телефон:<td><input type=text name='alt_phone' value='".html_out($agent_info['alt_phone'])."'>
			<tr class=lin0><td align=right>Юридический адрес / Адрес прописки<td colspan=2><textarea name='adres'>".html_out($agent_info['adres'])."</textarea>
			<tr class=lin1><td align=right>Адрес проживания<td colspan=2><textarea name='gruzopol'>".html_out($agent_info['gruzopol'])."</textarea>
			<tr class=lin0><td align=right>ИНН/КПП или ИНН:<td><input type=text name='inn' value='".html_out($agent_info['inn'])."' style='width: 40%;' class='inn validate'>
			<tr class=lin1><td align=right>Банк<td><input type=text name='bank' value='".html_out($agent_info['bank'])."' style='width: 90%;'>
			<tr class=lin0><td align=right>Корр. счет<td><input type=text name='ks' value='".html_out($agent_info['ks'])."' style='width: 40%;' class='ks validate'>
			<tr class=lin1><td align=right>БИК<td><input type=text name='bik' value='".html_out($agent_info['bik'])."' class='bik validate'>
			<tr class=lin0><td align=right>Рассчетный счет<br><small>Проверяется на корректность совместно с БИК</small><td><input type=text name='rs' value='".html_out($agent_info['rs'])."' style='width: 40%;' class='rs validate'>
			<tr class=lin1><td align=right>ОКВЭД<td><input type=text name='okevd' value='".html_out($agent_info['okevd'])."'>
			<tr class=lin0><td align=right>ОКПО<td><input type=text name='okpo' value='".html_out($agent_info['okpo'])."' class='okpo validate'>
			<tr class=lin1><td align=right>ФИО директора<td><input type=text name='dir_fio' value='".html_out($agent_info['dir_fio'])."'>
			<tr class=lin0><td align=right>ФИО директора в родительном падеже<td><input type=text name='dir_fio_r' value='".html_out($agent_info['dir_fio_r'])."'>
			<tr class=lin1><td align=right>Контактное лицо<td><input type=text name='pfio' value='".html_out($agent_info['pfio'])."'>
			<tr class=lin0><td align=right>Должность контактног лица<td><input type=text name='pdol' value='".html_out($agent_info['pdol'])."'>
			<tr class=lin1><td align=right>Паспорт: Номер<td><input type=text name='pasp_num' value='".html_out($agent_info['pasp_num'])."'>
			<tr class=lin0><td align=right>Паспорт: Дата выдачи<td><input type=text name='pasp_date' value='".html_out($agent_info['pasp_date'])."' id='pasp_date'>
			<tr class=lin1><td align=right>Паспорт: Кем выдан<td><input type=text name='pasp_kem' value='".html_out($agent_info['pasp_kem'])."'>
			<tr class=lin0><td align=right>Дата последней сверки:<td><input type=text name='data_sverki' value='".html_out($agent_info['data_sverki'])."' id='data_sverki' $ext>
			<tr class=lin1><td align=right>Ответственный:<td>
			<select name='responsible' $ext>
			<option value='null'>--не назначен--</option>");
			$rres = $db->query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
			while($nx = $rres->fetch_row()) {
				$s=($agent_info['responsible']==$nx[0])?'selected':'';
				$tmpl->addContent("<option value='$nx[0]' $s>".  html_out($nx[1])."</option>");
			}
			$dish_checked=$agent_info['dishonest']?'checked':'';
			$tmpl->addContent("</select>
			<tr class='lin0'><td align='right'>Особые отметки<td><label><input type='checkbox' name='dishonest' value='1' $dish_checked>Недобросовестный агент</label>
			<tr class='lin1'><td align='right'>Связанные пользователи</td><td>");
			$r = $db->query("SELECT `id`, `name` FROM `users` WHERE `agent_id`=$pos");
			if(!$r->num_rows)	$tmpl->addContent("отсутствуют");
			else {
				while($nn = $r->fetch_assoc())
					$tmpl->addContent("<a href='/adm_users.php?mode=view&amp;id={$nn['id']}'>".html_out($nn['name'])." ({$nn['id']})</a>, ");
			}
			$tmpl->addContent("</td></tr>
			<tr class='lin1'><td align='right'>Относится к<td>
			<input type='hidden' name='p_agent' id='agent_id' value='{$agent_info['p_agent']}'>
			<input type='text' id='agent_nm' name='p_agent_nm'  style='width: 50%;' value='$html_pagent_name'>
			<div id='agent_info'></div>
			<tr class=lin0><td align=right>Комментарий<td colspan=2><textarea name='comment'>".html_out($agent_info['comment'])."</textarea>
			<tr class=lin1><td><td><button type='submit' id='b_submit'>Сохранить</button>
			</table></form>

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
					formatItem:agliFormat,
					onItemSelect:agselectItem,
					extraParams:{'l':'agent','mode':'srv','opt':'ac'}
				});
			});

			function agliFormat (row, i, num) {
				var result = row[0] + \"<em class='qnt'>тел. \" +
				row[2] + \"</em> \";
				return result;
			}

			function agselectItem(li) {
				if( li == null ) var sValue = \"Ничего не выбрано!\";
				if( !!li.extra ) var sValue = li.extra[0];
				else var sValue = li.selectValue;
				document.getElementById('agent_id').value=sValue;
			}
			initCalendar('pasp_date')
			initCalendar('data_sverki')

			var valid=form_validator('agent_edit_form')


			</script>

			");

		}
		else if($param=='h') {
			$tmpl->addContent("<table width='100%'>
			<tr><th>id<th>Действие<th>Описание<th>Дата<th>Пользователь<th>IP");
			$res = $db->query("SELECT `doc_log`.`id`, `doc_log`.`motion`, `doc_log`.`desc`, `doc_log`.`time`, `users`.`name`, `doc_log`.`ip`
			FROM `doc_log`
			LEFT JOIN `users` ON `users`.`id`=`doc_log`.`user`
			WHERE `object`='AGENT' AND `object_id`='$pos'");
			while($nxt = $res->fetch_row())
				$tmpl->addContent('<tr><td>'.$nxt[0].'</td><td>'.html_out($nxt[1]).'</td><td>'.html_out($nxt[2]).'</td><td>'.html_out($nxt[3]).'</td><td>'.html_out($nxt[4]).'</td><td>'.html_out($nxt[5]).'</td></tr>');
			$tmpl->addContent("</table>");
		}
		// Правка описания группы
		else if($param=='g') {
			$res = $db->query("SELECT `id`, `name`, `desc`, `pid` FROM `doc_agent_group` WHERE `id`='$group'");
			$nxt = $res->fetch_row();
			$tmpl->addContent("<h1>Описание группы</h1>
			<form action='docs.php' method='post'>
			<input type='hidden' name='mode' value='esave'>
			<input type='hidden' name='l' value='agent'>
			<input type='hidden' name='g' value='$nxt[0]'>
			<input type='hidden' name='param' value='g'>
			<table cellpadding='0' width='50%'>
			<tr><td>Наименование группы $nxt[0]:</td><td><input type=text name='name' value='".html_out($nxt[1])."'></td></tr>
			<tr class=lin0>
			<td>Находится в группе:
			<td><input type=text name='pid' value='$nxt[3]'>
			<tr class=lin1>
			<td>Описание:
			<td><textarea name='desc'>".html_out($nxt[2])."</textarea>
			<tr class=lin0><td colspan=2 align=center>
			<input type='submit' value='Сохранить'>
			</table>
			</form>");
		}
		else $tmpl->msg("Неизвестная закладка");

	}
	function ESave()
	{
		global $tmpl, $db;
		doc_menu();
		$pos = rcvint('pos');
		$param = request('param');
		$group = rcvint('g');

		if($param=='') {
			$ag_info = $db->selectRowA('doc_agent', $pos, $this->agent_vars);
			unset($ag_info['id']);
			if(!$ag_info['p_agent'])	$ag_info['p_agent']='NULL';
			
			$new_agent_info = array();
			foreach ($this->agent_vars as $value)
				$new_agent_info[$value] = request($value);
			if(request('p_agent_nm'))
				$new_agent_info['p_agent'] = rcvint('p_agent');
			else $new_agent_info['p_agent']='NULL';
			
			
			settype($ag_info['group'],'int');
			settype($ag_info['dishonest'],'int');
			settype($new_agent_info['group'],'int');
			settype($new_agent_info['dishonest'],'int');
			
			if(!isAccess('doc_agent_ext', 'edit'))	{
				unset($new_agent_info['responsible']);
				unset($new_agent_info['data_sverki']);
				unset($ag_info['responsible']);
				unset($ag_info['data_sverki']);
			}

			$log_text = getCompareStr($ag_info, $new_agent_info);
			
			if( (!preg_match('/^\w+([-\.\w]+)*\w@\w(([-\.\w])*\w+)*\.\w{2,8}$/', $new_agent_info['email'])) && ($new_agent_info['email']!='') )
				throw new Exception("Неверный e-mail!");
			if($pos) {
				if(!isAccess('list_agent','edit'))	throw new AccessException();
				$log_start='UPDATE';				
				$db->updateA('doc_agent', $pos, $new_agent_info);
				$this->PosMenu($pos, '');
				$tmpl->msg("Данные обновлены!");
			}
			else {
				$log_start='CREATE';
				$new_agent_info['responsible'] = $_SESSION['uid'];
				if(!isAccess('list_agent','create'))	throw new AccessException();
				
				$pos = $db->insertA('doc_agent', $new_agent_info);
				$this->PosMenu($pos, '');
				$tmpl->msg("Добавлена новая запись!");
			}

			doc_log($log_start, $log_text, 'agent', $pos);
		}
		else if($param=='g') {
			$new_data = array(
				'name' => request('name'),
				'desc' => request('desc'),
				'pid' => rcvint('pid')
			);
			if($group){
				if(!isAccess('list_agent', 'edit'))	throw new AccessException();
				$old_data = $db->selectRowAi('doc_agent_group', $group, $new_data);
				$log_text = getCompareStr($old_data, $new_data);
				$db->updateA('doc_agent_group', $group,$new_data);
				doc_log('UPDATE', $log_text, 'agent_group', $group);
			}
			else {
				if(!isAccess('list_agent', 'create'))	throw new AccessException();
				$old_data = array();
				foreach ($new_data as $id => $value)
					$old_data[$id]='';
				$log_text = getCompareStr($old_data, $new_data);
				$db->insertA('doc_agent_group', $new_data);
				doc_log('CREATE', $log_text, 'agent_group', $group);
			}
			$tmpl->msg("Сохранено!");
		}
		else $tmpl->msg("Неизвестная закладка");
	}

	/// Сформировать один и все вложенные уровни списка групп агентов
	function draw_level($select, $level) {
		global $db;
		settype($level, 'int');
		$ret = '';
		$res = $db->query("SELECT `id`, `name`, `desc` FROM `doc_agent_group` WHERE `pid`='$level' ORDER BY `name`");
		$i = 0;
		$r = '';
		if ($level == 0)
			$r = 'IsRoot';
		while ($nxt = $res->fetch_row()) {
			if ($nxt[0] == 0)	continue;
			$item = "<a href='' title='$nxt[2]' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&g=$nxt[0]','list'); return false;\" >".html_out($nxt[1])."</a>";
			if ($i >= ($res->num_rows - 1))
				$r.=" IsLast";

			$tmp = $this->draw_level($select, $nxt[0]); // рекурсия
			if ($tmp)
				$ret.="
				<li class='Node ExpandClosed $r'>
			<div class='Expand'></div>
			<div class='Content'>$item
			</div><ul class='Container'>" . $tmp . '</ul></li>';
			else
				$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
			$i++;
		}
		return $ret;
	}

	/// Отобразить список групп агентов
	function draw_groups($select)	{
		global $tmpl, $db;
		$tmpl->addContent("
		Отбор:<input type='text' id='f_search' onkeydown=\"DelayedSave('/docs.php?l=agent&mode=srv&opt=pl','list', 'f_search'); return true;\" ><br>
		<div onclick='tree_toggle(arguments[0])'>
		<div><a href='' title='' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&g=0','list'); return false;\" >Группы</a> (<a href='/docs.php?l=agent&mode=edit&param=g&g=0'><img src='/img/i_add.png' alt=''></a>)</div>
		<ul class='Container'>".$this->draw_level($select,0)."</ul></div>
		<hr>");
		$res = $db->query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
		while($nx = $res->fetch_row()) {
			$m=($_SESSION['uid']==$nx[0])?' (МОИ)':'';
			$tmpl->addContent("<a href='' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&resp=$nx[0]','list'); return false;\">Агенты ".html_out($nx[1])."{$m}</a><br>");
		}
		$tmpl->addContent("<br><a href='' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&resp=0','list'); return false;\">Непривязанные агенты</a>");
	}
	
	/// Отобразить список агентов из заданной группы
	function ViewList($group=0) {
		global $tmpl, $db;

		if(isset($_REQUEST['resp']))
			$this->ViewListRespFiltered(request('resp'));
		else {
			if($group) {
				$desc_data = $db->selectRow('doc_agent_group', $group);
				if($desc_data['desc']) $tmpl->addContent('<p>'.html_out($desc_data['desc']).'</p>');
			}

			$sql = "SELECT `doc_agent`.`id`, `doc_agent`.`group`, `doc_agent`.`name`, `doc_agent`.`tel`, `doc_agent`.`email`, `doc_agent`.`type`, `doc_agent`.`fullname`, `doc_agent`.`pfio`, `users`.`name` AS `responsible_name`, `doc_agent`.`dishonest`, `doc_agent`.`fax_phone`, `doc_agent`.`sms_phone`, `doc_agent`.`alt_phone`
			FROM `doc_agent`
			LEFT JOIN `users` ON `doc_agent`.`responsible`=`users`.`id`
			WHERE `doc_agent`.`group`='$group'
			ORDER BY `doc_agent`.`name`";

			$lim=50;
			$page = rcvint('p');
			$res = $db->query($sql);
			$row = $res->num_rows;
			if($row>$lim) {
				$dop="g=$group";
				if($page<1) $page=1;
				if($page>1) {
					$i=$page-1;
					$tmpl->addContent("<a href='' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&$dop&p=$i','list'); return false;\">&lt;&lt;</a> ");
				}
				$cp=$row/$lim;
				for($i=1;$i<($cp+1);$i++) {
					if($i==$page) $tmpl->addContent(" <b>$i</b> ");
					else $tmpl->addContent("<a href='' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&$dop&p=$i','list'); return false;\">$i</a> ");
				}
				if($page<$cp) {
					$i=$page+1;
					$tmpl->addContent("<a href='' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&$dop&p=$i','list'); return false;\">&gt;&gt;</a> ");
				}
				$tmpl->addContent("<br>");
				$sl=($page-1)*$lim;

				$res->data_seek($sl);
			}

			if($row) {
				$tmpl->addContent("<table class='list' width='100%' cellspacing='1' cellpadding='2'>
				<tr><th>№</th><th>Название</th><th>Телефон</th><th>e-mail</th><th>Дополнительно</th><th>Отв.менеджер</th></tr>");
				$this->DrawTable($res, '', $lim);
				$tmpl->addContent("</table>");
			}
			else $tmpl->msg("В выбранной группе записей не найдено!");
			$tmpl->addContent("
			<a href='/docs.php?l=agent&mode=srv&opt=ep&pos=0&g=$group'><img src='/img/i_add.png' alt=''> Добавить</a> |
			<a href='/docs.php?l=agent&mode=edit&param=g&g=$group'><img src='/img/i_edit.png' alt=''> Правка группы</a> |
			<a href='/docs.php?l=agent&mode=search'><img src='/img/i_find.png' alt=''> Расширенный поиск</a>");
		}
	}

	/// Отобразить список агентов, отфильторванный по заданной строке
	function ViewListS($s='') {
		global $tmpl, $db;
		$tmpl->addContent("<b>Показаны записи изо всех групп!</b><br>");
		$tmpl->addContent("<table class='list' width='100%' cellspacing='1' cellpadding='2'>
		<tr><th>№</th><th>Название</th><th>Телефон</th><th>e-mail</th><th>Дополнительно</th><th>Отв.менеджер</th></tr>");
		$s_sql = $db->real_escape_string($s);
		$sql = "SELECT `doc_agent`.`id`, `doc_agent`.`group`, `doc_agent`.`name`, `doc_agent`.`tel`, `doc_agent`.`email`, `doc_agent`.`type`, `doc_agent`.`fullname`, `doc_agent`.`pfio`, `users`.`name` AS `responsible_name`, `doc_agent`.`dishonest`, `doc_agent`.`fax_phone`, `doc_agent`.`sms_phone`, `doc_agent`.`alt_phone`
		FROM `doc_agent`
		LEFT JOIN `users` ON `doc_agent`.`responsible`=`users`.`id`";

        	$sqla = $sql."WHERE `doc_agent`.`name` LIKE '$s_sql%' OR `doc_agent`.`fullname` LIKE '$s_sql%' ORDER BY `doc_agent`.`name` LIMIT 30";
		$res = $db->query($sqla);
		if($res->num_rows) {
			$tmpl->addContent("<tr><th colspan='16' align='center'>Фильтр по названию, начинающемуся на ".html_out($s).": {$res->num_rows} строк найдено</th></tr>");
			$this->DrawTable($res, $s);
			$sf = 1;
		}

		$sqla = $sql."WHERE (`doc_agent`.`name` LIKE '%$s%' OR `doc_agent`.`fullname` LIKE '%$s_sql%') AND (`doc_agent`.`name` NOT LIKE '$s_sql%' AND `doc_agent`.`fullname` NOT LIKE '$s_sql%') ORDER BY `doc_agent`.`name` LIMIT 30";
		$res = $db->query($sqla);
		if($res->num_rows) {
			$tmpl->addContent("<tr><th colspan='16' align='center'>Фильтр по названию, содержащему ".html_out($s).": {$res->num_rows}  строк найдено</th></tr>");
			$this->DrawTable($res, $s);
			$sf = 1;
		}

		$tmpl->addContent("</table><a href='/docs.php?l=agent&mode=srv&opt=ep&pos=0&g=0'><img src='/img/i_add.png' alt=''> Добавить</a>");

		if($sf==0)	$tmpl->msg("По данным критериям записей не найдено!");
	}
	
	/// Список агентов с фильтрацией по ответственному сотруднику
	function ViewListRespFiltered($resp) {
		global $tmpl, $db;
		settype($resp,'int');
		$sf=0;
		$tmpl->addContent("<table class='list' width='100%' cellspacing='1' cellpadding='2'>
		<tr><th>№</th><th>Название</th><th>Телефон</th><th>e-mail</th><th>Дополнительно</th><th>Ответственный</th></tr>");
		$sql = "SELECT `doc_agent`.`id`, `doc_agent`.`group`, `doc_agent`.`name`, `doc_agent`.`tel`, `doc_agent`.`email`, `doc_agent`.`type`, `doc_agent`.`fullname`, `doc_agent`.`pfio`, `users`.`name` AS `responsible_name`, `doc_agent`.`dishonest`, `doc_agent`.`fax_phone`, `doc_agent`.`sms_phone`, `doc_agent`.`alt_phone`
		FROM `doc_agent`
		LEFT JOIN `users` ON `doc_agent`.`responsible`=`users`.`id`
		WHERE `doc_agent`.`responsible`='$resp'";
		$res = $db->query($sql);
		if($res->num_rows) {
			$tmpl->addContent("<tr><th colspan='6' align='center'>Использован фильтр по ответственному. Найдено: {$res->num_rows}. ID: $resp");
			$this->DrawTable($res, '');
			$sf = 1;
		}
		$tmpl->addContent("</table>");
		if($sf == 0)	$tmpl->msg("По данным критериям записей не найдено!");
	}
	
	/// Расширенный поиск агентов
	function Search() {
		global $tmpl, $db;
		$opt = request("opt");
		if($opt=='') {
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
		}
		else if($opt=='s') {
			doc_menu();
			$tmpl->addContent("<h1>Результаты</h1>");
			$name	= $db->real_escape_string( request('name') );
			$mail	= $db->real_escape_string(request('mail') );
			$inn	= $db->real_escape_string( request('inn') );
			$tel	= $db->real_escape_string( request('tel') );
			$adres	= $db->real_escape_string( request('adres') );
			$rs	= $db->real_escape_string( request('rs') );
			$kont	= $db->real_escape_string( request('kont') );
			$pasp_num = rcvint('pasp_num');

			$sql = "SELECT `doc_agent`.`id`, `doc_agent`.`group`, `doc_agent`.`name`, `doc_agent`.`tel`, `doc_agent`.`email`, `doc_agent`.`type`, `doc_agent`.`fullname`, `doc_agent`.`pfio`, `users`.`name`, `doc_agent`.`dishonest`, `doc_agent`.`fax_phone`, `doc_agent`.`sms_phone`, `doc_agent`.`alt_phone`
			FROM `doc_agent`
			LEFT JOIN `users` ON `doc_agent`.`responsible`=`users`.`id`
			WHERE 1";

			if($name)	$sql.=" AND (`doc_agent`.`name` LIKE '%$name%' OR `doc_agent`.`fullname` LIKE '%$name%')";
			if($mail)	$sql.=" AND `doc_agent`.`email` LIKE '%$mail%'";
			if($inn)	$sql.=" AND `doc_agent`.`inn` LIKE '%$inn%'";
			if($tel)	$sql.=" AND `doc_agent`.`tel` LIKE '%$tel%'";
			if($adres)	$sql.=" AND `doc_agent`.`adres` LIKE '%$adres%'";
			if($rs)		$sql.=" AND `doc_agent`.`rs` LIKE '%$rs%'";
			if($kont)	$sql.=" AND `doc_agent`.`kont` LIKE '%$kont%'";
			if($pasp_num)	$sql.=" AND `doc_base_dop`.`size` LIKE '%$pasp_num%'";

			$sql.=" ORDER BY `doc_agent`.`name`";

			$tmpl->addContent("<table class='list' width='100%' cellspacing='1' cellpadding='2'><tr>
			<th>№</th><th>Название</th><th>Телефон</th><th>e-mail</th><th>Дополнительно</th><th>Ответственный</th></tr>");
			$res = $db->query($sql);
			if($res->num_rows) {
				$tmpl->addContent("<tr><th colspan='16' align='center'>Параметрический поиск, найдено {$res->num_rows} агентов</th></tr>");
				$this->DrawTable($res, request('name'));
			}
			else $tmpl->msg("По данным критериям записей не найдено!");
			$tmpl->addContent("</table>");
		}
	}

	/// Отобразить строки таблицы агентов
	/// @param res		Объект mysqli_result, возвращенный запросом списка агентов
	/// @param s		Строка поиска. Будет подсвечена в данных
	/// @param limit	Ограничение на количество выводимых строк
	function DrawTable($res, $s, $limit=1000) {
		global $tmpl;
		$c=0;
		while($nxt = $res->fetch_array()) {
			$name = SearchHilight( html_out($nxt['name']), $s);
			if($nxt['type'])$info = $nxt['pfio'];
			else		$info = $nxt['fullname'];
			$info = SearchHilight( html_out($info), $s);
			$red = $nxt['dishonest']?"style='color: #f00;'":'';
			if($nxt['email'])	$email="<a href='mailto:".html_out($nxt['email'])."'>".html_out($nxt['email'])."</a>";
			else			$email='';
			$phone_info='';
			if($nxt['tel'])						$phone_info.='тел. '.formatPhoneNumber($nxt['tel']).' ';
			if($nxt['fax_phone']&& $nxt['fax_phone']!=$nxt['tel'])	$phone_info.='факс '.formatPhoneNumber($nxt['fax_phone']).' ';
			if($nxt['sms_phone']&& $nxt['sms_phone']!=$nxt['tel'])	$phone_info.='sms: '.formatPhoneNumber($nxt['sms_phone']).' ';
			if($nxt['alt_phone']&& $nxt['alt_phone']!=$nxt['tel'])	$phone_info.='доп: '.formatPhoneNumber($nxt['alt_phone']).' ';
			$tmpl->addContent("<tr class='pointer' align='right' $red oncontextmenu=\"ShowAgentContextMenu(event,$nxt[0]); return false;\">
			<td><a href='/docs.php?l=agent&mode=srv&opt=ep&pos=$nxt[0]'>$nxt[0]</a>
			<a href='' onclick=\"ShowAgentContextMenu(event,$nxt[0]); return false;\" title='Меню' accesskey=\"S\"><img src='img/i_menu.png' alt='Меню' border='0'></a></td>
			<td align='left'>$name<td>$phone_info</td><td>$email</td><td>$info</td><td>".html_out($nxt['responsible_name'])."</td></tr>");
			if( $c++ >= $limit)				break;
		}
	}
	
	/// Меню наименования (закладки)
	function PosMenu($pos, $param) {
		global $tmpl;
		$sel = array('v' => '', 'h' => '');
		if ($param == '')
			$param = 'v';
		$sel[$param] = "class='selected'";

		$tmpl->addContent("<ul class='tabs'>
		<li><a {$sel['v']} href='/docs.php?l=agent&amp;mode=srv&amp;opt=ep&amp;pos=$pos'>Основные</a></li>
		<li><a {$sel['h']} href='/docs.php?l=agent&amp;mode=srv&amp;opt=ep&amp;param=h&amp;pos=$pos'>История</a></li>

		</ul>");
	}

};


?>