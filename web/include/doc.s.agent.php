<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2010, BlackLight, TND Team, http://tndproject.org
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

// Работа с товарами
$doc_types[2]="Агенты";

/// Редактор справочника агентов
class doc_s_Agent
{
	function View()
	{
		global $tmpl;
		doc_menu(0,0);
		if(!isAccess('list_agent','view'))	throw new AccessException("");
		$tmpl->AddText("<h1>Агенты</h1><table width=100%><tr><td id='groups' width='200' valign='top' class='lin0'>");
		$this->draw_groups(0);
		$tmpl->AddText("<td id='list' valign='top'  class='lin1'>");
		$this->ViewList();
		$tmpl->AddText("</table>");
	}

	function Service()
	{
		global $tmpl;

		$opt=rcv("opt");
		$g=rcv('g');
		if($opt=='pl')
		{
			$s=rcv('s');
			$tmpl->ajax=1;
			if($s)
				$this->ViewListS($g,$s);
			else
				$this->ViewList($g);
		}
		else if($opt=='ep')
		{
			$this->Edit();
		}
		else if($opt=='acost')
		{
			$pos=rcv('pos');
			$tmpl->ajax=1;
			$tmpl->AddText( GetInCost($pos) );
		}
		else if($opt=='popup')
		{
			$s=rcv('s');
			$i=0;
			$tmpl->ajax=1;
			$res=mysql_query("SELECT `id`,`name` FROM `doc_agent` WHERE LOWER(`name`) LIKE LOWER('%$s%') LIMIT 50");
			$row=mysql_numrows($res);
			$tmpl->AddText("Ищем: $s ($row совпадений)<br>");
			while($nxt=mysql_fetch_row($res))
			{
				$i=1;
				$tmpl->AddText("<a onclick=\"return SubmitData('$nxt[1]',$nxt[0]);\">$nxt[1]</a><br>");
			}
			if(!$i) $tmpl->AddText("<b>Искомая комбинация не найдена!");
		}
		else if($opt=='ac')
		{
			$q=rcv('q');
			$i=0;
			$tmpl->ajax=1;
			$res=mysql_query("SELECT `name`, `id`, `tel` FROM `doc_agent` WHERE LOWER(`name`) LIKE LOWER('%$q%') ORDER BY `name`");
			$row=mysql_numrows($res);
			//$tmpl->AddText("$q|0|$row шт.\n");
			while($nxt=mysql_fetch_row($res))
			{
				$i=1;
				$nxt[0]=unhtmlentities($nxt[0]);
				$tmpl->AddText("$nxt[0]|$nxt[1]|$nxt[2]\n");
			}
			//if(!$i) $tmpl->AddText("<b>Искомая комбинация не найдена!");
		}
		else $tmpl->msg("Неверный режим!");
	}

// Служебные функции класса
	function Edit()
	{
		global $tmpl;
		doc_menu();
		$pos=rcv('pos');
		$param=rcv('param');
		$group=rcv('g');
		if(!isAccess('list_agent','view'))	throw new AccessException("");
		if(($pos==0)&&($param!='g')) $param='';

		if($pos!=0)
		{
			$this->PosMenu($pos, $param);
		}

		if($param=='')
		{
			$res=mysql_query("SELECT `group`, `name`, `type`, `email`, `fullname`, `tel`, `adres`, `gruzopol`, `inn`, `rs`, `ks`, `okevd`, `okpo`,  `bank`,  `bik`, `pfio`, `pdol`, `pasp_num`, `pasp_date`, `pasp_kem`, `comment`, `responsible`, `data_sverki`, `dir_fio`, `dir_fio_r`, `dishonest`, `p_agent`, `sms_phone`, `fax_phone`, `alt_phone`
			FROM `doc_agent`
			WHERE `doc_agent`.`id`='$pos'");
			if(mysql_errno())	throw new MysqlException("Выборка информации об агенте не удалась");
			$nxt=@mysql_fetch_array($res);

			$pagent_name='';

			if(!$nxt)	$tmpl->AddText("<h3>Новая запись</h3>");
			else if($nxt[26]>0)
			{
				$r=mysql_query("SELECT  `name` FROM `doc_agent` WHERE `id`='$nxt[26]'");
				$pagent_name=mysql_result($r,0,0);
			}
			$tmpl->SetTitle("Правка агента ".@$nxt[1]);
			$tmpl->AddText("<form action='' method='post' id='agent_edit_form'><table cellpadding=0 width=100%>
			<input type=hidden name=mode value=esave>
			<input type=hidden name=l value=agent>
			<input type=hidden name=pos value=$pos>
			<tr class=lin0><td align=right width=20%>Краткое наименование<br>
			<small>По этому полю выполняется поиск. Не пишите здесь аббревиатуры вроде OOO, ИП, МУП, итд. а так же кавычки и подобные символы!</small>
			<td><input type=text name='pos_name' value='$nxt[1]' style='width: 90%;'>
			<tr class=lin1><td align=right>Тип:
			<td>");
			if($nxt[2]==0)
			{
				$tmpl->AddText("<label><input type='radio' name='type' value='0' checked>Физическое лицо</label><br>
				<label><input type='radio' name='type' value='1'>Юридическое лицо</label>");
			}
			else
			{
				$tmpl->AddText("<label><input type='radio' name='type' value='0'>Физическое лицо</label><br>
				<label><input type='radio' name='type' value='1' checked>Юридическое лицо</label>");
			}
			$tmpl->AddText("
			<tr class=lin0><td align=right>Группа
        		<td><select name='g'>");

			if((($pos!=0)&&($nxt[0]==0))||($group==0)) $i=" selected";
			$tmpl->AddText("<option value='0' $i>--</option>");

			$res=mysql_query("SELECT * FROM `doc_agent_group`");
			while($nx=mysql_fetch_row($res))
			{
				$i="";

				if((($pos!=0)&&($nx[0]==$nxt[0]))||($group==$nx[0])) $i=" selected";
				$tmpl->AddText("<option value='$nx[0]' $i>$nx[1]</option>");
			}

			$ext='';
			if(!isAccess('doc_agent_ext', 'edit')) $ext='disabled';
		
			$tmpl->AddText("</select>
			<tr class=lin1><td align=right>Адрес электронной почты (e-mail)<td><input type=text name='email' value='$nxt[3]' class='validate email'>
			<tr class=lin0><td align=right>Полное название / ФИО:<br><small>Так, как должно быть в документах</small><td><input type=text name='fullname' value='$nxt[4]' style='width: 90%;'>
			<tr class=lin1><td align=right>Телефон:<br><small>В международном формате +XXXXXXXXXXX...<br>без дефисов, пробелов, и пр.символов</small><td><input type=text name='tel' value='$nxt[5]' class='phone validate'>
			<tr class=lin0><td align=right>Телефон / факс:<br><small>В международном формате +XXXXXXXXXXX...<br>без дефисов, пробелов, и пр.символов</small><td><input type=text name='fax_phone' value='{$nxt['fax_phone']}' class='phone validate'>
			<tr class=lin1><td align=right>Телефон для sms:<br><small>В международном формате +XXXXXXXXXXX...<br>без дефисов, пробелов, и пр.символов</small><td><input type=text name='sms_phone' value='{$nxt['sms_phone']}' class='phone validate'>
			<tr class=lin0><td align=right>Дополнительный телефон:<td><input type=text name='alt_phone' value='{$nxt['alt_phone']}'>
			<tr class=lin0><td align=right>Юридический адрес / Адрес прописки<td colspan=2><textarea name='adres'>$nxt[6]</textarea>
			<tr class=lin1><td align=right>Адрес проживания<td colspan=2><textarea name='gruzopol'>$nxt[7]</textarea>
			<tr class=lin0><td align=right>ИНН/КПП или ИНН:<td><input type=text name='inn' value='$nxt[8]' style='width: 40%;' class='inn validate'>
			<tr class=lin1><td align=right>Банк<td><input type=text name='bank' value='$nxt[13]' style='width: 90%;'>
			<tr class=lin0><td align=right>Корр. счет<td><input type=text name='ks' value='$nxt[10]' style='width: 40%;' class='ks validate'>
			<tr class=lin1><td align=right>БИК<td><input type=text name='bik' value='$nxt[14]' class='bik validate'>
			<tr class=lin0><td align=right>Рассчетный счет<br><small>Проверяется на корректность совместно с БИК</small><td><input type=text name='rs' value='$nxt[9]' style='width: 40%;' class='rs validate'>
			<tr class=lin1><td align=right>ОКВЭД<td><input type=text name='okevd' value='$nxt[11]'>
			<tr class=lin0><td align=right>ОКПО<td><input type=text name='okpo' value='$nxt[12]' class='okpo validate'>
			<tr class=lin1><td align=right>ФИО директора<td><input type=text name='dir_fio' value='$nxt[23]'>
			<tr class=lin0><td align=right>ФИО директора в родительном падеже<td><input type=text name='dir_fio_r' value='$nxt[24]'>
			<tr class=lin1><td align=right>Контактное лицо<td><input type=text name='pfio' value='$nxt[15]'>
			<tr class=lin0><td align=right>Должность контактног лица<td><input type=text name='pdol' value='$nxt[16]'>
			<tr class=lin1><td align=right>Паспорт: Номер<td><input type=text name='pasp_num' value='$nxt[17]'>
			<tr class=lin0><td align=right>Паспорт: Дата выдачи<td><input type=text name='pasp_date' value='$nxt[18]' id='pasp_date'>
			<tr class=lin1><td align=right>Паспорт: Кем выдан<td><input type=text name='pasp_kem' value='$nxt[19]'>
			<tr class=lin0><td align=right>Дата последней сверки:<td><input type=text name='data_sverki' value='$nxt[22]' id='data_sverki' $ext>
			<tr class=lin1><td align=right>Ответственный:<td>
			<select name='responsible' $ext>
			<option value='null'>--не назначен--</option>");
			$res=mysql_query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
			while($nx=mysql_fetch_row($res))
			{
				$s='';
				if($nxt[21]==$nx[0])	$s='selected';
				$tmpl->AddText("<option value='$nx[0]' $s>$nx[1]</option>");
			}
			$dish_checked=$nxt[25]?'checked':'';
			$tmpl->AddText("</select>
			<tr class='lin0'><td align='right'>Особые отметки<td><label><input type='checkbox' name='dishonest' value='1' $dish_checked>Недобросовестный агент</label>
			<tr class='lin1'><td align='right'>Связанные пользователи</td><td>");
			$r=mysql_query("SELECT `id`, `name` FROM `users` WHERE `agent_id`='$pos'");
			if(!mysql_num_rows($r))	$tmpl->AddText("отсутствуют");
			else
			{
				while($nn=mysql_fetch_assoc($r))
				{
					$tmpl->AddText("<a href='/adm_users.php?mode=view&amp;id={$nn['id']}'>{$nn['name']} ({$nn['id']})</a>, ");
				}
			}			
			$tmpl->AddText("</td></tr>
			<tr class='lin1'><td align='right'>Относится к<td>
			<input type='hidden' name='p_agent' id='agent_id' value='$nxt[26]'>
			<input type='text' id='agent_nm' name='p_agent_nm'  style='width: 50%;' value='$pagent_name'>
			<div id='agent_info'></div>
			<tr class=lin0><td align=right>Комментарий<td colspan=2><textarea name='comment'>$nxt[20]</textarea>
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
		else if($param=='i')
		{
			$tmpl->AddText("<form action='' method=post enctype='multipart/form-data'>
			<input type=hidden name=mode value=esave>
			<input type=hidden name=l value=sklad>
			<input type=hidden name=pos value=$pos>
			<input type=hidden name=param value=i>
			<table cellpadding=0 width=50%>
			<tr class=lin1><td>Файл картнки:
			<td><input type='hidden' name='MAX_FILE_SIZE' value='1000000'><input name='userfile' type='file'>
			<tr class=lin0><td>Название картинки:
			<td><input type=text name='nm'><br>
			Если написать имя картинки, которая уже есть в базе, то она и будет установлена вне зависимости от того, передан файл или нет.
			<tr class=lin1><td>Дополнительно:
			<td><label><input type='checkbox' name='set_def' value='1'>Установить по умолчанию</label>
			<tr class=lin0><td colspan=2 align=center>
			<input type='submit' value='Сохранить'>
			</table>
			</form><h2>Ассоциированные с товаром картинки</h2>");
			$res=mysql_query("SELECT `doc_base_img`.`img_id`, `doc_img`.`type`
			FROM `doc_base_img`
			LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
			WHERE `doc_base_img`.`pos_id`='$pos'");
			while($nxt=@mysql_fetch_row($res))
			{
				$tmpl->AddText("<img src='img/t/$nxt[0].$nxt[1]'><br>");
			}
		}
		else if($param=='h')
		{
			$tmpl->AddText("<table width='100%'>
			<tr><th>id<th>Действие<th>Описание<th>Дата<th>Пользователь<th>IP");
			$res=mysql_query("SELECT `doc_log`.`id`, `doc_log`.`motion`, `doc_log`.`desc`, `doc_log`.`time`, `users`.`name`, `doc_log`.`ip`
			FROM `doc_log`
			LEFT JOIN `users` ON `users`.`id`=`doc_log`.`user`
			WHERE `object`='AGENT' AND `object_id`='$pos'");
			echo mysql_error();
			while($nxt=@mysql_fetch_row($res))
			{
				$tmpl->AddText("<tr><td>$nxt[0]<td>$nxt[1]<td>$nxt[2]<td>$nxt[3]<td>$nxt[4]<td>$nxt[5]");
			}
			$tmpl->AddText("</table>");
		}
		// Правка описания группы
		else if($param=='g')
		{
			$res=mysql_query("SELECT `id`, `name` , `desc` , `pid`
			FROM `doc_agent_group`
			WHERE `id`='$group'");
			@$nxt=mysql_fetch_row($res);
			$tmpl->AddText("<h1>Описание группы</h1>
			<form action='docs.php'>
			<input type=hidden name=mode value=esave>
			<input type=hidden name=l value=agent>
			<input type=hidden name=g value='$nxt[0]'>
			<input type=hidden name=param value=g>
			<table cellpadding=0 width=50%>
			<tr class=lin1>
			<td>Наименование группы $nxt[0]:
			<td><input type=text name='name' value='$nxt[1]'>
			<tr class=lin0>
			<td>Находится в группе:
			<td><input type=text name='pid' value='$nxt[3]'>
			<tr class=lin1>
			<td>Описание:
			<td><textarea name='desc'>$nxt[2]</textarea>
			<tr class=lin0><td colspan=2 align=center>
			<input type='submit' value='Сохранить'>
			</table>
			</form>");
		}
		else $tmpl->msg("Неизвестная закладка");

	}
	function ESave()
	{
		global $tmpl, $CONFIG;
		doc_menu();
		$pos=rcv('pos');
		$param=rcv('param');
		$group=rcv('g');

		if($pos!=0)
		{
			//$this->PosMenu($pos, $param);

		}

		if($param=='')
		{
			$res=mysql_query("SELECT * FROM `doc_agent`
			WHERE `doc_agent`.`id`='$pos'");
			if(mysql_error())	throw new Exception("Невозможно получить данные агента!");
			$ag_info=@mysql_fetch_assoc($res);

			$log_text='';
			$log_start='U_MOT';

			$pos_name=rcv('pos_name');
			$type=rcv('type');
			$g=rcv('g');
			$email=rcv('email');
			$fullname=rcv('fullname');
			$tel=rcv('tel');
			$fax_phone=rcv('fax_phone');
			$sms_phone=rcv('sms_phone');
			$alt_phone=rcv('alt_phone');
			$adres=rcv('adres');
			$gruzopol=rcv('gruzopol');
			$inn=rcv('inn');
			$rs=rcv('rs');
			$ks=rcv('ks');
			$okevd=rcv('okevd');
			$okpo=rcv('okpo');
			$bank=rcv('bank');
			$bik=rcv('bik');
			$dir_fio=rcv('dir_fio');
			$dir_fio_r=rcv('dir_fio_r');
			$pfio=rcv('pfio');
			$pdol=rcv('pdol');
			$pasp_num=rcv('pasp_num');
			$pasp_date=rcv('pasp_date');
			$pasp_kem=rcv('pasp_kem');
			$comment=rcv('comment');
			$responsible=rcv('responsible');
			$data_sverki=rcv('data_sverki');
			$dishonest=rcv('dishonest');
			if(rcv('p_agent_nm'))
			{
				$p_agent=rcv('p_agent');
				settype($p_agent,'int');
			}
			else $p_agent='NULL';

			settype($g,'int');
			if($responsible!='null')
				settype($responsible,'int');
			else	$responsible='null';
			settype($dishonest,'int');

			if($pos_name!=$ag_info['name'])		$log_text.="name: ( {$ag_info['name']} => $pos_name ), ";
			if($type!=$ag_info['type'])		$log_text.="type: ( {$ag_info['type']} => $type ), ";
			if($g!=$ag_info['group'])		$log_text.="group: ( {$ag_info['group']} => $g ), ";
			if($email!=$ag_info['email'])		$log_text.="email: ( {$ag_info['email']} => $email ), ";
			if($fullname!=$ag_info['fullname'])	$log_text.="fullname: ( {$ag_info['fullname']} => $fullname ), ";
			if($tel!=$ag_info['tel'])		$log_text.="tel: ( {$ag_info['tel']} => $tel ), ";
			if($fax_phone!=$ag_info['fax_phone'])	$log_text.="fax_phone: ( {$ag_info['fax_phone']} => $fax_phone ), ";
			if($sms_phone!=$ag_info['sms_phone'])	$log_text.="sms_phone: ( {$ag_info['sms_phone']} => $sms_phone ), ";
			if($alt_phone!=$ag_info['alt_phone'])	$log_text.="alt_phone: ( {$ag_info['alt_phone']} => $alt_phone ), ";
			if($adres!=$ag_info['adres'])		$log_text.="adres: ( {$ag_info['adres']} => $adres ), ";
			if($gruzopol!=$ag_info['gruzopol'])	$log_text.="gruzopol: ( {$ag_info['gruzopol']} => $gruzopol ), ";
			if($inn!=$ag_info['inn'])		$log_text.="inn: ( {$ag_info['inn']} => $inn ), ";
			if($rs!=$ag_info['rs'])			$log_text.="rs: ( {$ag_info['rs']} => $rs ), ";
			if($ks!=$ag_info['ks'])			$log_text.="ks: ( {$ag_info['ks']} => $ks ), ";
			if($okevd!=$ag_info['okevd'])		$log_text.="okevd: ( {$ag_info['okevd']} => $okevd ), ";
			if($okpo!=$ag_info['okpo'])		$log_text.="okpo: ( {$ag_info['okpo']} => $okpo ), ";
			if($bank!=$ag_info['bank'])		$log_text.="bank: ( {$ag_info['bank']} => $bank ), ";
			if($bik!=$ag_info['bik'])		$log_text.="bik: ( {$ag_info['bik']} => $bik ), ";
			if($dir_fio!=$ag_info['dir_fio'])	$log_text.="dir_fio: ( {$ag_info['dir_fio']} => $dir_fio ), ";
			if($dir_fio_r!=$ag_info['dir_fio_r'])	$log_text.="dir_fio_r: ( {$ag_info['dir_fio_r']} => $dir_fio_r ), ";
			if($pfio!=$ag_info['pfio'])		$log_text.="pfio: ( {$ag_info['pfio']} => $pfio ), ";
			if($pdol!=$ag_info['pdol'])		$log_text.="pdol: ( {$ag_info['pdol']} => $pdol ), ";
			if($pasp_num!=$ag_info['pasp_num'])	$log_text.="pasp_num: ( {$ag_info['pasp_num']} => $pasp_num ), ";
			if($pasp_date!=$ag_info['pasp_date'])	$log_text.="pasp_date: ( {$ag_info['pasp_date']} => $pasp_date ), ";
			if($pasp_kem!=$ag_info['pasp_kem'])	$log_text.="pasp_kem: ( {$ag_info['pasp_kem']} => $pasp_kem ), ";
			if($comment!=$ag_info['comment'])	$log_text.="comment: ( {$ag_info['comment']} => $comment ), ";
			if($dishonest!=$ag_info['dishonest'])	$log_text.="dishonest: ( {$ag_info['dishonest']} => $dishonest ), ";
			if(!$ag_info['p_agent'])	$ag_info['p_agent']='NULL';
			if($p_agent!=$ag_info['p_agent'])	$log_text.="p_agent: ( {$ag_info['p_agent']} => $p_agent ), ";

			if( (!preg_match('/^\w+([-\.\w]+)*\w@\w(([-\.\w])*\w+)*\.\w{2,8}$/', $email)) && ($email!='') )
			{
				$tmpl->msg("Неверный e-mail! Данные не сохранены!","err");
			}
			else if($pos)
			{
				$log_start='UPDATE';

				$sql_add='';
				$rights=getright('doc_agent_ext',@$_SESSION['uid']);
				if($rights['write'])
				{
					if($responsible!=$ag_info['responsible'])	$log_text.="responsible: ( {$ag_info['responsible']} => $responsible ), ";
					if($data_sverki!=$ag_info['data_sverki'])	$log_text.="data_sverki: ( {$ag_info['data_sverki']} => $data_sverki ), ";
					$sql_add=", `responsible`=$responsible, `data_sverki`='$data_sverki'";
				}
				if(!isAccess('list_agent','edit'))	throw new AccessException("");
				$res=mysql_query("UPDATE `doc_agent` SET `name`='$pos_name', `type`='$type', `group`='$g', `email`='$email', `fullname`='$fullname', `tel`='$tel', `fax_phone`='$fax_phone', `sms_phone`='$sms_phone', `alt_phone`='$alt_phone', `adres`='$adres', `gruzopol`='$gruzopol', `inn`='$inn', `rs`='$rs', `ks`='$ks', `okevd`='$okevd', `okpo`='$okpo', `bank`='$bank', `bik`='$bik', `pfio`='$pfio', `pdol`='$pdol', `pasp_num`='$pasp_num', `pasp_date`='$pasp_date', `pasp_kem`='$pasp_kem', `comment`='$comment', `dishonest`='$dishonest', `dir_fio`='$dir_fio', `dir_fio_r`='$dir_fio_r', `p_agent`= $p_agent $sql_add  WHERE `id`='$pos'");
				if(mysql_errno())	throw new MysqlException("Ошибка сохранения данных агента");
				$tmpl->msg("Данные обновлены!");
			}
			else
			{
				$log_start='CREATE';

				$sql_c=$sql_v='';
				$rights=getright('doc_agent_ext',@$_SESSION['uid']);
				$uid=@$_SESSION['uid'];
				if($rights['write'])
				{
					$sql_c=", `data_sverki`";
					$sql_v=", '$data_sverki'";
				}
				if(!isAccess('list_agent','create'))	throw new AccessException("");
				$res=mysql_query("INSERT INTO `doc_agent` (`name`, `fullname`, `tel`, `sms_phone`, `fax_phone`, `alt_phone`, `adres`, `gruzopol`, `inn`, `dir_fio`, `dir_fio_r`, `pfio`, `pdol`, `okevd`, `okpo`, `rs`, `bank`, `ks`, `bik`, `group`, `email`, `type`, `pasp_num`, `pasp_date`, `pasp_kem`, `comment`, `responsible`, `dishonest`, `p_agent` $sql_c  ) VALUES ( '$pos_name', '$fullname', '$tel', '$sms_phone', '$fax_phone', '$alt_phone', '$adres', '$gruzopol', '$inn', '$dir_fio', '$dir_fio_r', '$pfio', '$pdol', '$okevd', '$okpo', '$rs', '$bank', '$ks', '$bik', '$group', '$email', '$type', '$pasp_num', '$pasp_date', '$pasp_kem', '$comment', '$uid', '$dishonest', $p_agent $sql_v )");
				$pos=mysql_insert_id();
				$this->PosMenu($pos, '');
				if($res)
					$tmpl->msg("Добавлена новая запись!");
				else $tmpl->msg("Ошибка сохранения!".mysql_error(),"err");
			}

			doc_log($log_start.' agent', $log_text, 'AGENT', $pos);
		}
		else if($param=='i')
		{
			$id=0;
			$max_size=500;
			$min_pix=50;
			$max_pix=800;
			$nm=rcv('nm');
			$set_def=rcv('set_def');
			if(!isAccess('list_agent','edit'))	throw new AccessException("");
			$res=mysql_query("SELECT `id` FROM `doc_img` WHERE `name`='$nm'");
			if(mysql_num_rows($res))
			{
				$img_id=mysql_result($res,0,0);
				$tmpl->msg("Эта картинка найдена, N $img_id","info");
			}
			else
			{
				if($_FILES['userfile']['size']<=0)
					$tmpl->msg("Забыли выбрать картинку?");
				else
				{
					if($_FILES['userfile']['size']>$max_size*1024)
						$tmpl->msg("Слишком большой файл! Допустимо не более $max_size кб!");
					else
					{
						$iminfo=getimagesize($_FILES['userfile']['tmp_name']);
						switch ($iminfo[2])
						{
							case IMAGETYPE_JPEG: $imtype='jpg'; break;
							case IMAGETYPE_PNG: $imtype='png'; break;
							case IMAGETYPE_GIF: $imtype='gif'; break;
							default: $imtype='';
						}
						if(!$imtype) $tmpl->msg("Файл - не картинка, или неверный формат файла. Рекомендуется PNG и JPG, допустим но не рекомендуется GIF.");
						else if(($iminfo[0]<$min_pix)||($iminfo[1]<$min_pix))
						$tmpl->msg("Слишком мелкая картинка! Минимальный размер - $min_pix пикселей!");
						else if(($iminfo[0]>$max_pix)||($iminfo[1]>$max_pix))
						$tmpl->msg("Слишком большая картинка! Максимальный размер - $max_pix пикселей!");
						else
						{
							mysql_query("INSERT INTO `doc_img` (`name`, `type`)	VALUES ('$nm', '$imtype')");
							$img_id=mysql_insert_id();
							if($img_id)
							move_uploaded_file($_FILES['userfile']['tmp_name'],$CONFIG['site']['location'].'/img/t/'.$img_id.'.'.$imtype);
							$tmpl->msg("Файл загружен, $img_id.$imtype","info");
						}
					}
				}
			}

			//mysql_query("INSERT INTO `doc_base_img` (`pos_id`, `img_id`, `default`) VALUES ('$pos', '$img_id', '$set_def')");

		}
		else if($param=='g')
		{
			$name=rcv('name');
			$desc=rcv('desc');
			$pid=rcv('pid');
			if(!isAccess('list_agent','edit'))	throw new AccessException("");
			if($group)
				$res=mysql_query("UPDATE `doc_agent_group` SET `name`='$name', `desc`='$desc', `pid`='$pid' WHERE `id` = '$group'");
			else
				$res=mysql_query("INSERT INTO `doc_agent_group` (`name`, `desc`, `pid`)
				VALUES ('$name', '$desc', '$pid')");
			if($res) $tmpl->msg("Сохранено!");
			else $tmpl->msg("Ошибка!".mysql_error(),"err");
		}
		else $tmpl->msg("Неизвестная закладка");
	}

	function draw_level($select, $level)
	{
		$ret='';
		$res=mysql_query("SELECT `id`, `name`, `desc` FROM `doc_agent_group` WHERE `pid`='$level' ORDER BY `name`");
		$i=0;
		$r='';
		if($level==0) $r='IsRoot';
		$cnt=mysql_num_rows($res);
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[0]==0) continue;
			$item="<a href='' title='$nxt[2]' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&g=$nxt[0]','list'); return false;\" >$nxt[1]</a>";

			if($i>=($cnt-1)) $r.=" IsLast";

			$tmp=$this->draw_level($select, $nxt[0]); // рекурсия
			if($tmp)
				$ret.="
				<li class='Node ExpandClosed $r'>
			<div class='Expand'></div>
			<div class='Content'>$item
			</div><ul class='Container'>".$tmp.'</ul></li>';
		else
			$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
			$i++;
		}
		return $ret;
	}


	function draw_groups($select)
	{
		global $tmpl;
		$tmpl->AddText("
		Отбор:<input type='text' id='f_search' onkeydown=\"DelayedSave('/docs.php?l=agent&mode=srv&opt=pl','list', 'f_search'); return true;\" ><br>
		<div onclick='tree_toggle(arguments[0])'>
		<div><a href='' title='' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&g=0','list'); return false;\" >Группы</a> (<a href='/docs.php?l=agent&mode=edit&param=g&g=0'><img src='/img/i_add.png' alt=''></a>)</div>
		<ul class='Container'>".$this->draw_level($select,0)."</ul></div>
		<hr>");
		$res=mysql_query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
		while($nx=mysql_fetch_row($res))
		{
			$m=($_SESSION['uid']==$nx[0])?' (МОИ)':'';
			$tmpl->AddText("<a href='' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&resp=$nx[0]','list'); return false;\">Агенты {$nx[1]}{$m}</a><br>");
		}
		$tmpl->AddText("<br><a href='' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&resp=0','list'); return false;\">Непривязанные агенты</a>");
	}

	function ViewList($group=0,$s='')
	{
		global $tmpl;

		if(isset($_GET['resp']) || isset($_POST['resp']))
			$this->ViewListRespFiltered(rcv('resp'));
		else
		{
			if($group)
			{
				$res=mysql_query("SELECT `desc` FROM `doc_agent_group` WHERE `id`='$group'");
				$g_desc=mysql_result($res,0,0);
				if($g_desc) $tmpl->AddText("<h4>$g_desc</h4>");
			}

			$sql="SELECT `doc_agent`.`id`, `doc_agent`.`group`, `doc_agent`.`name`, `doc_agent`.`tel`, `doc_agent`.`email`, `doc_agent`.`type`, `doc_agent`.`fullname`, `doc_agent`.`pfio`, `users`.`name`, `doc_agent`.`dishonest`, `doc_agent`.`fax_phone`, `doc_agent`.`sms_phone`, `doc_agent`.`alt_phone`
			FROM `doc_agent`
			LEFT JOIN `users` ON `doc_agent`.`responsible`=`users`.`id`
			WHERE `doc_agent`.`group`='$group'
			ORDER BY `doc_agent`.`name`";

			$lim=50;
			$page=rcv('p');
			$res=mysql_query($sql);
			$row=mysql_num_rows($res);
			if($row>$lim)
			{
				$dop="g=$group";
				if($page<1) $page=1;
				if($page>1)
				{
					$i=$page-1;
					link_sklad(0, "$dop&p=$i","&lt;&lt;");
				}
				$cp=$row/$lim;
				for($i=1;$i<($cp+1);$i++)
				{
					if($i==$page) $tmpl->AddText(" <b>$i</b> ");
					else $tmpl->AddText("<a href='' onclick=\"EditThis('/docs.php?l=agent&mode=srv&opt=pl&$dop&p=$i','list'); return false;\">$i</a> ");
				}
				if($page<$cp)
				{
					$i=$page+1;
					link_sklad(0, "$dop&p=$i","&gt;&gt;");
				}
				$tmpl->AddText("<br>");
				$sl=($page-1)*$lim;

				$res=mysql_query("$sql LIMIT $sl,$lim");
			}

			if(mysql_num_rows($res))
			{
				$tmpl->AddText("<table width=100% cellspacing=1 cellpadding=2><tr>
				<th>№<th>Название<th>Телефон<th>e-mail<th>Дополнительно<th>Отв.менеджер");
				$this->DrawTable($res,$s);
				$tmpl->AddText("</table>");

			}
			else $tmpl->msg("В выбранной группе записей не найдено!");
			$tmpl->AddText("
			<a href='/docs.php?l=agent&mode=srv&opt=ep&pos=0&g=$group'><img src='/img/i_add.png' alt=''> Добавить</a> |
			<a href='/docs.php?l=agent&mode=edit&param=g&g=$group'><img src='/img/i_edit.png' alt=''> Правка группы</a> |
			<a href='/docs.php?l=agent&mode=search'><img src='/img/i_find.png' alt=''> Расширенный поиск</a>");
		}
	}

	function ViewListS($group=0,$s)
	{
		global $tmpl;
		$tmpl->AddText("<b>Показаны записи изо всех групп!</b><br>");
		$tmpl->AddText("<table width=100% cellspacing=1 cellpadding=2><tr>
		<th>№<th>Название<th>Телефон<th>e-mail<th>Дополнительно<th>Отв.менеджер");

		$sql="SELECT `doc_agent`.`id`, `doc_agent`.`group`, `doc_agent`.`name`, `doc_agent`.`tel`, `doc_agent`.`email`, `doc_agent`.`type`, `doc_agent`.`fullname`, `doc_agent`.`pfio`, `users`.`name`, `doc_agent`.`dishonest`, `doc_agent`.`fax_phone`, `doc_agent`.`sms_phone`, `doc_agent`.`alt_phone`
		FROM `doc_agent`
		LEFT JOIN `users` ON `doc_agent`.`responsible`=`users`.`id`";

        	$sqla=$sql."WHERE `doc_agent`.`name` LIKE '$s%' OR `doc_agent`.`fullname` LIKE '$s%' ORDER BY `doc_agent`.`name` LIMIT 30";
		$res=mysql_query($sqla);
		if($cnt=mysql_num_rows($res))
		{
			$tmpl->AddText("<tr class=lin0><th colspan=16 align=center>Фильтр по названию, начинающемуся на $s: найдено $cnt");
			$this->DrawTable($res,$s);
			$sf=1;
		}

		$sqla=$sql."WHERE (`doc_agent`.`name` LIKE '%$s%' OR `doc_agent`.`fullname` LIKE '%$s%') AND (`doc_agent`.`name` NOT LIKE '$s%' AND `doc_agent`.`fullname` NOT LIKE '$s%') ORDER BY `doc_agent`.`name` LIMIT 30";
		$res=mysql_query($sqla);
		if($cnt=mysql_num_rows($res))
		{
			$tmpl->AddText("<tr class=lin0><th colspan=16 align=center>Фильтр по названию, содержащему $s: найдено $cnt");
			$this->DrawTable($res,$s);
			$sf=1;
		}

		$tmpl->AddText("</table><a href='/docs.php?l=agent&mode=srv&opt=ep&pos=0&g=0'><img src='/img/i_add.png' alt=''> Добавить</a>");

		if($sf==0)	$tmpl->msg("По данным критериям записей не найдено!");
	}

	function ViewListRespFiltered($resp)
	{
		global $tmpl;
		settype($resp,'int');
		$sf=0;
		$tmpl->AddText("<table width=100% cellspacing=1 cellpadding=2><tr>
		<th>№<th>Название<th>Телефон<th>e-mail<th>Дополнительно<th>Отв.менеджер");
		$sql="SELECT `doc_agent`.`id`, `doc_agent`.`group`, `doc_agent`.`name`, `doc_agent`.`tel`, `doc_agent`.`email`, `doc_agent`.`type`, `doc_agent`.`fullname`, `doc_agent`.`pfio`, `users`.`name`, `doc_agent`.`dishonest`, `doc_agent`.`fax_phone`, `doc_agent`.`sms_phone`, `doc_agent`.`alt_phone`
		FROM `doc_agent`
		LEFT JOIN `users` ON `doc_agent`.`responsible`=`users`.`id`
		WHERE `doc_agent`.`responsible`='$resp'";
		$res=mysql_query($sql);
		if($cnt=mysql_num_rows($res))
		{
			$tmpl->AddText("<tr><th colspan='6' align=center>Использован фильтр по ответственному. Найдено: $cnt. ID: $resp");
			$this->DrawTable($res,'');
			$sf=1;
		}
		$tmpl->AddText("</table>");
		if($sf==0)	$tmpl->msg("По данным критериям записей не найдено!");
	}

	function Search()
	{
		global $tmpl;
		$opt=rcv("opt");
		if($opt=='')
		{
			doc_menu();
			$tmpl->AddText("<h1>Расширенный поиск</h1>
			<form action='docs.php' method='post'>
			<input type=hidden name=mode value=search>
			<input type=hidden name=l value=agent>
			<input type=hidden name=opt value=s>
			<table width=100%>
			<tr><th>Наименование
			<th>e-mail
			<th>ИНН
			<th>Телефон
			<tr class=lin1>
			<td><input type=text name='name'>
			<td><input type=text name='mail'>
			<td><input type=text name='inn'>
			<td><input type=text name='tel'>
			<tr>
			<th>Адрес
			<th>Расчетный счет
			<th>Контактное лицо
			<th>Номер паспорта
			<tr>
			<td><input type=text name='adres'>
			<td><input type=text name='rs'>
			<td><input type=text name='kont'>
			<td><input type=text name='pasp_num'>

			<tr>
			<td colspan=5 align=center><input type='submit' value='Найти'>
			</table>
			</form>");
		}
		else if($opt=='s')
		{
			doc_menu();
			$tmpl->AddText("<h1>Результаты</h1>");
			$name=rcv('name');
			$mail=rcv('mail');
			$inn=rcv('inn');
			$tel=rcv('tel');
			$adres=rcv('adres');
			$rs=rcv('rs');
			$kont=rcv('kont');
			$pasp_num=rcv('pasp_num');

			$sql="SELECT `doc_agent`.`id`, `doc_agent`.`group`, `doc_agent`.`name`, `doc_agent`.`tel`, `doc_agent`.`email`, `doc_agent`.`type`, `doc_agent`.`fullname`, `doc_agent`.`pfio`, `users`.`name`, `doc_agent`.`dishonest`, `doc_agent`.`fax_phone`, `doc_agent`.`sms_phone`, `doc_agent`.`alt_phone`
			FROM `doc_agent`
			LEFT JOIN `users` ON `doc_agent`.`responsible`=`users`.`id`
			WHERE 1 ";

			if($name)	$sql.="AND (`doc_agent`.`name` LIKE '%$name%' OR `doc_agent`.`fullname` LIKE '%$name%')";
			if($mail)	$sql.="AND `doc_agent`.`email` LIKE '%$mail%'";
			if($inn)	$sql.="AND `doc_agent`.`inn` LIKE '%$inn%'";
			if($tel)	$sql.="AND `doc_agent`.`tel` LIKE '%$tel%'";
			if($adres)	$sql.="AND `doc_agent`.`adres` LIKE '%$adres%'";
			if($rs)		$sql.="AND `doc_agent`.`rs` LIKE '%$rs%'";
			if($kont)	$sql.="AND `doc_agent`.`kont` LIKE '%$kont%'";
			if($pasp_num)	$sql.="AND `doc_base_dop`.`size` LIKE '%$pasp_num%'";

			$sql.=" ORDER BY `doc_agent`.`name`";

			$tmpl->AddText("<table width=100% cellspacing=1 cellpadding=2><tr>
			<th>№<th>Название<th>Телефон<th>e-mail<th>Дополнительно<th>Отв.менеджер");

			$res=mysql_query($sql);
			echo mysql_error();
			if($cnt=mysql_num_rows($res))
			{
				$tmpl->AddText("<tr class=lin0><th colspan=16 align=center>Параметрический поиск, найдено $cnt");
				$this->DrawTable($res,$name);
				$sf=1;
			}
			$tmpl->AddText("</table>");
		}
	}

	function DrawTable($res,$s)
	{
		global $tmpl;
		$i=1;
		while($nxt=mysql_fetch_array($res))
		{
			$nxt[2]=SearchHilight($nxt[2],$s);
			$i=1-$i;
			if($nxt[5]) $info=$nxt[7];
			else $info=$nxt[6];
			$info=SearchHilight($info,$s);
			$red=$nxt['dishonest']?"style='color: #f00;'":'';
			if($nxt[4]) $nxt[4]="<a href='mailto:$nxt[4]'>$nxt[4]</a>";
			$phone_info='';
			if($nxt['tel'])						$phone_info.='тел. '.formatPhoneNumber($nxt['tel']).' ';
			if($nxt['fax_phone']&& $nxt['fax_phone']!=$nxt['tel'])	$phone_info.='факс '.formatPhoneNumber($nxt['fax_phone']).' ';
			if($nxt['sms_phone']&& $nxt['sms_phone']!=$nxt['tel'])	$phone_info.='sms: '.formatPhoneNumber($nxt['sms_phone']).' ';
			if($nxt['alt_phone']&& $nxt['alt_phone']!=$nxt['tel'])	$phone_info.='доп: '.formatPhoneNumber($nxt['alt_phone']).' ';
			$tmpl->AddText("<tr class='lin$i pointer' align='right' $red oncontextmenu=\"ShowAgentContextMenu(event,$nxt[0]); return false;\">
			<td><a href='/docs.php?l=agent&mode=srv&opt=ep&pos=$nxt[0]'>$nxt[0]</a>
			<a href='' onclick=\"ShowAgentContextMenu(event,$nxt[0]); return false;\" title='Меню' accesskey=\"S\"><img src='img/i_menu.png' alt='Меню' border='0'></a>
			<td align=left>$nxt[2]<td>$phone_info<td>$nxt[4]<td>$info<td>$nxt[8]");
		}
	}

	function PosMenu($pos, $param)
	{
		global $tmpl;
		$sel=array('v'=>'','h'=>'');
		if($param=='')	$param='v';
		$sel[$param]="class='selected'";

		$tmpl->AddText("<ul class='tabs'>
		<li><a {$sel['v']} href='/docs.php?l=agent&amp;mode=srv&amp;opt=ep&amp;pos=$pos'>Основные</a></li>
		<li><a {$sel['h']} href='/docs.php?l=agent&amp;mode=srv&amp;opt=ep&amp;param=h&amp;pos=$pos'>История</a></li>

		</ul>");
	}

};


?>

