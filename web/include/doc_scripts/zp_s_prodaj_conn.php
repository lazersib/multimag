<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2012, BlackLight, TND Team, http://tndproject.org
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

/// Расчёт заработной платы по финансовым поступленям от прикреплённых к сотруднику агентов
/// Заработная плата расчитывается только для прикреплённых к сотруднику агентов.
/// Позволятет настроить различные ставки для старых и новых агентов
class ds_zp_s_prodaj_conn
{
	var $new_coeff=0.04;
	var $old_coeff=0.02;
	var $new_days=90;

	function __construct()
	{	
		global $tmpl, $CONFIG;
		$tmpl->HideBlock('left');
		if(isset($CONFIG['doc_scripts']['zp_s_prodaj_conn.new_coeff']))	$this->new_coeff=$CONFIG['doc_scripts']['zp_s_prodaj_conn.new_coeff'];
		if(isset($CONFIG['doc_scripts']['zp_s_prodaj_conn.new_days']))	$this->new_days=$CONFIG['doc_scripts']['zp_s_prodaj_conn.new_days'];
		if(isset($CONFIG['doc_scripts']['zp_s_prodaj_conn.old_coeff']))	$this->old_coeff=$CONFIG['doc_scripts']['zp_s_prodaj_conn.old_coeff'];
	}

function Run($mode)
{
	global $tmpl, $uid;
	
	if($mode=='view')
	{
		$curdate=date("Y-m-d");
		$tmpl->AddText("<h1>".$this->getname()."</h1>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<script src='/js/calendar.js'></script>
		<form action='' method='post' enctype='multipart/form-data'>
		<input type='hidden' name='mode' value='create'>
		<input type='hidden' name='param' value='i'>
		<input type='hidden' name='sn' value='zp_s_prodaj_conn'>
		Услуга начисления зарплаты:<br>
		<input type='hidden' name='tov_id' id='tov_id' value=''>
		<input type='text' id='tov'  style='width: 400px;' value=''><br>
		Рассчитывать с:<br>
		<input type='text' name='date_f' id='datepicker_f' value='$curdate'><br>
		По:<br>
		<input type='text' name='date_t' id='datepicker_t' value='$curdate'><br>
		Сотрудник:<br><select name='user_id'>");
		$res=mysql_query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить имя сотрудника");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");
		}
		$tmpl->AddText("</select><br>

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
		<p>Установлены следующие настройки:<br>
		Новым считать агента, первый документ у которого создан не ранее, чем за <b>{$this->new_days}</b> дней до даты оплачиваемго документа.<br>
		За реализацию новому агенту начисляется <b>{$this->new_coeff}</b> от суммы оплаченной реализации.<br>
		За реализацию старому агенту начисляется <b>{$this->old_coeff}</b> от суммы оплаченной реализации.</p>
		");
	}
	else if($mode=='create')
	{
		$tov_id=rcv('tov_id');
		$date_f=strtotime(rcv('date_f'));
		$date_t=strtotime(rcv('date_t'))+60*60*24-1;
		$user_id=$_REQUEST['user_id'];
		settype($user_id,'int');

		$tmpl->AddText("<h1>".$this->getname()."</h1>");
		if(!$tov_id)				throw new Exception("Не указана услуга!");

		$res=mysql_query("SELECT `agent_id` FROM `users` WHERE `id`='$user_id'");
		if(!$res)	throw new MysqlException("Не удалость запросить привязку агентов!");
		if(!mysql_num_rows($res))		throw new Exception("Сотрудник на найден!");
		list($worker_id)=mysql_fetch_row($res);
		if(!$worker_id)	$tmpl->msg("Пользователь не привязан к агенту. Вы не сможете начислить заработную плату!",'err');
		
		$tmpl->AddText("
		<form action='' method='post' enctype='multipart/form-data'>
		<input type='hidden' name='mode' value='exec'>
		<input type='hidden' name='param' value='i'>
		<input type='hidden' name='sn' value='zp_s_prodaj_conn'>
		<input type='hidden' name='tov_id' id='tov_id' value='$tov_id'>
		<input type='hidden' name='user_id' id='tov_id' value='$user_id'>
		<table width='100%' class='list'>
		<tr><th>ID</th><th>Автор</th><th>Дата</th><th>Сумма</th><th>К начислению</th></tr>");
		
		// Получаем список агентов сотрудника
		$ag_res=mysql_query("SELECT `id`, `name` FROM `doc_agent` WHERE `responsible`='$user_id'");
		if(!$ag_res)	throw new MysqlException("Не удалость запросить список агентов");

		while($agent_info=mysql_fetch_assoc($ag_res))
		{
			// Получение даты первого документа
			$fd_res=mysql_query("SELECT `date` FROM `doc_list` WHERE `agent`='{$agent_info['id']}' ORDER BY `date` LIMIT 1");
			if(!$fd_res)	throw new MysqlException("Не удалось получить дату первого документа");
			if(!mysql_num_rows($fd_res))	continue;	// Заодно не будет агентов без движения
			list($fd_date)	=mysql_fetch_row($fd_res);
		
			$tmpl->AddText("<tr><th colspan='5'>{$agent_info['name']}</td></tr>");
			$doc_res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`user`, `doc_list`. `date`, `doc_list`.`sum`, `n_data`.`value` AS `zp_s_finansov`, `users`.`name` AS `user_name`, `doc_list`.`p_doc`, `doc_list`.`agent` AS `agent_id`
			FROM `doc_list`
			INNER JOIN `users`			ON `users`.`id`=`doc_list`.`user`
			LEFT JOIN `doc_dopdata` AS `n_data`	ON `n_data`.`doc`=`doc_list`.`id` AND `n_data`.`param`='zp_s_finansov'
			WHERE `doc_list`.`ok`>'0' AND (`doc_list`.`type`='4' OR `doc_list`.`type`='6') AND `doc_list`.`date`>='$date_f' AND `doc_list`.`date`<='$date_t'
			AND `doc_list`.`agent`='{$agent_info['id']}'");
			if(!$doc_res)	throw new MysqlException("Не удалось получить список документов");
			while($doc_info=mysql_fetch_assoc($doc_res))
			{
				if($doc_info['date'] > ($fd_date+60*60*24*$this->new_days))	$coeff=$this->old_coeff;
				else								$coeff=$this->new_coeff;
				
				$date=date("Y-m-d H:i:s", $doc_info['date']);
				$nach_sum=sprintf("%0.2f",$doc_info['sum']*$coeff);
				
				$tmpl->AddText("<tr>
				<td><a href='/doc.php?mode=body&amp;doc={$doc_info['id']}'>{$doc_info['id']}</a></td>
				<td><a href='/adm_users.php?mode=view&amp;id={$doc_info['user_name']}'>{$doc_info['user_name']}</a></td>
				<td>$date</td><td>{$doc_info['sum']}</td>");

				if(!$doc_info['zp_s_finansov'])	$tmpl->AddText("<td><input type='text' name='sum_doc[{$doc_info['id']}]' value='$nach_sum'></td>");
				else				$tmpl->AddText("<td>{$doc_info['zp_s_finansov']}</td>");
				
				$tmpl->AddText("</tr>");
			}
		}
 		$but_disabled='';
 		if(!$worker_id)	$but_disabled='disabled';
		$tmpl->AddText("</table><button $but_disabled>Начислить зарплату</button></form>");
	}
	else if($mode=='exec')
	{
		$tov_id=intval($_REQUEST['tov_id']);
		$user_id=intval($_REQUEST['user_id']);

		if(!is_array($_REQUEST['sum_doc']))	throw new Exception("Нечего начислять!");
		if(!$user_id)				throw new Exception("Некому начислять!");
		if(!$tov_id)				throw new Exception("Не указана услуга!");

		$res=mysql_query("SELECT `agent_id` FROM `users` WHERE `id`='$user_id'");
		if(!$res)	throw new MysqlException("Не удалость запросить привязку агентов!");
		if(!mysql_num_rows($res))		throw new Exception("Сотрудник на найден!");
		list($worker_id)=mysql_fetch_row($res);
		if(!$worker_id)	throw new Exception("Необходимо привязать пользователя к агенту!");
		mysql_query("START TRANSACTION");
		$all_sum=0;
		foreach($_REQUEST['sum_doc'] as $doc=>$sum)
		{
			$sum=round($sum,2);
			settype($doc,'int');
			if(!$sum)	continue;
			$all_sum+=$sum;
			mysql_query("INSERT INTO `doc_dopdata` (`doc`, `param`, `value`) VALUES ('$doc', 'zp_s_finansov', '$sum')");
			if(mysql_errno())	throw new MysqlException("Не удалось установить пометку о выданной зарплате".mysql_error());
		}

		$tim=time();
		$uid=$_SESSION['uid'];
		$altnum=GetNextAltNum(1,'auto',0,$tim,1);
		mysql_query("INSERT INTO `doc_list` (`date`, `firm_id`, `type`, `user`, `altnum`, `subtype`, `sklad`, `agent`, `p_doc`, `sum`)
		VALUES	('$tim', '1', '1', '$uid', '$altnum', 'auto', '1', '$worker_id', '0', '$all_sum')");
		if(mysql_errno())	throw new MysqlException("Не удалось создать документ");
		$post_doc=mysql_insert_id();
		mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`) VALUES ('$post_doc', '$tov_id', '1', '$all_sum')");
		if(mysql_errno())	throw new MysqlException("Не удалось добавить услугу");
		mysql_query("COMMIT");
		header("location: /doc.php?mode=body&doc=$post_doc");
	}
}

function getName()
{
	return "Расчёт заработной платы по финансовым поступленям от прикреплённых к сотруднику агентов";
}

};

?>
