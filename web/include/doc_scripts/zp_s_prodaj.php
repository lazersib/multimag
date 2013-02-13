<?php

class ds_zp_s_prodaj
{
	var $coeff=0.05;

function Run($mode)
{
	global $tmpl, $uid, $CONFIG;
	if(isset($CONFIG['doc_scripts']['zp_s_prodaj.coeff']))	$this->coeff=$CONFIG['doc_scripts']['zp_s_prodaj.coeff'];
	$tmpl->HideBlock('left');
	if($mode=='view')
	{
		$curdate=date("Y-m-d");
		$tmpl->AddText("<h1>".$this->getname()."</h1>
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
		$res=mysql_query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить имя кладовщика");
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
		");
	}
	else if($mode=='create')
	{
		$tov_id=rcv('tov_id');
		$date_f=strtotime(rcv('date_f'));
		$date_t=strtotime(rcv('date_t'));
		$user_id=$_REQUEST['user_id'];
		settype($user_id,'int');

		$tmpl->AddText("<h1>".$this->getname()."</h1>");
		if(!$tov_id)				throw new Exception("Не указана услуга!");

		$res=mysql_query("SELECT `agent_id` FROM `users` WHERE `id`='$user_id'");
		if(!$res)	throw new MysqlException("Не удалость запросить привязку агентов!");
		if(!mysql_num_rows($res))		throw new Exception("Сотрудник на найден!");
		list($agent_id)=mysql_fetch_row($res);
		if(!$agent_id)	$tmpl->msg("Пользователь не привязан к агенту. Вы не сможете начислить заработную плату!",'err');

		$res=mysql_query("SELECT `curlist`.`id`, `curlist`.`user`, `doc_agent`.`name` AS `agent_name`, `curlist`.`date`, `curlist`.`sum`, `curusers`.`name` AS `ruser_name`, `zlist`.`user` AS `zuser`, `zusers`.`name` AS `zuser_name`, `curlist`.`p_doc`, `rkolist`.`sum` AS `ag_sum`, `curlist`.`agent` AS `agent_id`, `n_data`.`value` AS `zp_s_prodaj`
		FROM `doc_list` AS `curlist`
		INNER JOIN `doc_agent` ON		`doc_agent`.`id`=`curlist`.`agent`
		INNER JOIN `users` AS `curusers`	ON `curusers`.`id`=`curlist`.`user`
		LEFT JOIN `doc_list` AS `zlist`		ON `zlist`.`id`=`curlist`.`p_doc` AND `zlist`.`type`='3'
		LEFT JOIN `doc_list` AS `rkolist`	ON `rkolist`.`p_doc`=`curlist`.`id` AND `rkolist`.`type`='7'
		LEFT JOIN `users` AS `zusers`		ON `zusers`.`id`=`zlist`.`user`
		LEFT JOIN `doc_list` AS `pay_doc`	ON `pay_doc`.`p_doc`=`curlist`.`id`
		LEFT JOIN `doc_dopdata` AS `n_data`	ON `n_data`.`doc`=`curlist`.`id` AND `n_data`.`param`='zp_s_prodaj'
		WHERE `curlist`.`ok`>'0' AND `curlist`.`type`='2' AND `curlist`.`date`>='$date_f' AND `curlist`.`date`<='$date_t'
		AND `curlist`.`id` NOT IN (SELECT `doc` FROM `doc_dopdata` WHERE `param`='nzp')  AND `zlist`.`user`=$user_id");
		if(!$res)	throw new MysqlException("Не удалось получить список документов");
		/// nsp (param) - начислена зарплата
		$tmpl->AddText("
		<form action='' method='post' enctype='multipart/form-data'>
		<input type='hidden' name='mode' value='exec'>
		<input type='hidden' name='param' value='i'>
		<input type='hidden' name='sn' value='zp_s_prodaj'>
		<input type='hidden' name='tov_id' id='tov_id' value='$tov_id'>
		<input type='hidden' name='user_id' id='tov_id' value='$user_id'>
		<table width='100%'>
		<tr><th>ID<th>Автор<th>Агент<th>Дата<th>Сумма<th>Агентские<th>К начислению");
		$i=0;
		$users=array();
		while($nxt=mysql_fetch_assoc($res))
		{
			if($nxt['zuser']>0)
			{
				if(!isset($users[$nxt['zuser']]))
				{
					$users[$nxt['zuser']]=array();
					$users[$nxt['zuser']]['name']=$nxt['zuser_name'];
					$users[$nxt['zuser']]['sum']=0;
					$users[$nxt['zuser']]['nsum']=0;
				}
			}
			else
			{
				if(!isset($users[$nxt['user']]))
				{
					$users[$nxt['user']]=array();
					$users[$nxt['user']]['name']=$nxt['ruser_name'];
					$users[$nxt['user']]['sum']=0;
					$users[$nxt['user']]['nsum']=0;
				}

			}
			$nxt['ag_sum']=sprintf("%0.2f",$nxt['ag_sum']);

			// Расчёт входящей стоимости
			$res_tov=mysql_query("SELECT `doc_list_pos`.`id`, `doc_list_pos`.`tovar`, `doc_list_pos`.`cost`, `doc_list_pos`.`cnt`
			FROM `doc_list_pos`
			WHERE `doc_list_pos`.`doc`='{$nxt['id']}'");
			$nach_sum=0;
			while($nxt_tov=mysql_fetch_assoc($res_tov))
			{
				$incost = GetInCost($nxt_tov['tovar'], $nxt['date']);
				$nach_sum+=($nxt_tov['cost']-$incost)*$this->coeff*$nxt_tov['cnt'];
			}
			$nach_sum-=$nxt['ag_sum']*$this->coeff;
			$nach_sum=sprintf("%0.2f",$nach_sum);
			// Проверка факта оплаты
			$add='';
			if($nxt['p_doc']) $add=" OR (`p_doc`='{$nxt['p_doc']}' AND (`type`='4' OR `type`='6'))";
			$rs=mysql_query("SELECT SUM(`sum`) FROM `doc_list` WHERE
			(`p_doc`='{$nxt['id']}' AND (`type`='4' OR `type`='6'))
			$add AND `ok`>0 AND `p_doc`!='0' GROUP BY `p_doc`");
			$disable='';
			if(@$prop=mysql_result($rs,0,0))
			{
				$prop=sprintf("%0.2f",$prop);
				if($prop>=$nxt['sum'])		$cl='f_green';
				else
				{
					$cl='f_brown';
					$disable='disabled';
				}
			}
			else
			{
				if(DocCalcDolg($nxt['agent_id'])<=0)
				{
					$cl='f_green';
				}
				else
				{
					$cl='f_red';
					$disable='disabled';
				}

			}

			$date=date("Y-m-d H:i:s", $nxt['date']);

			$tmpl->AddText("<tr class='lin$i $cl'><td><a href='/doc.php?mode=body&doc={$nxt['id']}'>{$nxt['id']}</a>
			<td>{$nxt['ruser_name']} / {$nxt['zuser_name']}<td>{$nxt['agent_name']} <td>$date<td>{$nxt['sum']}<td>{$nxt['ag_sum']}<td>");
			if(!$nxt['zp_s_prodaj'])	$tmpl->AddText("{$nxt['zp_s_prodaj']} <input type='text' name='sum_doc[{$nxt['id']}]' value='$nach_sum' $disable>");
			else
			{
				$tmpl->AddText("{$nxt['zp_s_prodaj']}");
			}
			$i=1-$i;
			if($disable=='')
			{
				if($nxt['zuser']>0)
				{
					$users[$nxt['zuser']]['sum']+=$nach_sum;
					if(!$nxt['zp_s_prodaj'])
						$users[$nxt['zuser']]['nsum']+=$nach_sum;
				}
				else	$users[$nxt['user']]['sum']+=$nach_sum;
			}
		}
		$but_disabled='';
		if(!$agent_id)	$but_disabled='disabled';

		$tmpl->AddText("</table>
		<button $but_disabled>Начислить зарплату</button>
		</form>
		<table>
		<tr><th>Сотрудник</th><th>Расчёт</th><th>К начислению</th></tr>");
		foreach($users as $id=>$data)
		{
			$tmpl->AddText("<tr><td>{$data['name']}</td><td>{$data['sum']}</td><td>{$data['nsum']}</td></tr>");
		}
		$tmpl->AddText("</table>");
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
		list($agent_id)=mysql_fetch_row($res);
		if(!$agent_id)	throw new Exception("Необходимо привязать пользователя к агенту!");
		mysql_query("START TRANSACTION");
		$all_sum=0;
		foreach($_REQUEST['sum_doc'] as $doc=>$sum)
		{
			$sum=round($sum,2);
			settype($doc,'int');
			if(!$sum)	continue;
			$all_sum+=$sum;
			mysql_query("INSERT INTO `doc_dopdata` (`doc`, `param`, `value`) VALUES ('$doc', 'zp_s_prodaj', '$sum')");
			if(mysql_errno())	throw new MysqlException("Не удалось установить пометку о выданной зарплате".mysql_error());
		}

		$tim=time();
		$uid=$_SESSION['uid'];
		$altnum=GetNextAltNum(1,'auto',0,$tim,1);
		mysql_query("INSERT INTO `doc_list` (`date`, `firm_id`, `type`, `user`, `altnum`, `subtype`, `sklad`, `agent`, `p_doc`, `sum`)
		VALUES	('$tim', '1', '1', '$uid', '$altnum', 'auto', '1', '$agent_id', '0', '$all_sum')");
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
	return "Расчёт и выплата зарплаты с продаж";
}

};

?>
