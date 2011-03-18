<?php

class ds_zp_s_prodaj
{
	var $coeff=0.1;

function Run($mode)
{
	global $tmpl, $uid;
	$tmpl->HideBlock('left');
	if($mode=='view')
	{
		$tmpl->AddText("<h1>".$this->getname()."</h1>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<form action='' method='post' enctype='multipart/form-data'>
		<input type='hidden' name='mode' value='create'>
		<input type='hidden' name='param' value='i'>
		<input type='hidden' name='sn' value='zp_s_prodaj'>
		Услуга начисления зарплаты:<br>
		<input type='hidden' name='tov_id' id='tov_id' value=''>
		<input type='text' id='tov'  style='width: 400px;' value=''><br>
		<script type=\"text/javascript\">
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
		$tmpl->AddText("<h1>".$this->getname()."</h1>");
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`user`, `doc_agent`.`name` AS `agent_name`, `doc_list`.`date`, `doc_list`.`sum`, `users`.`name` AS `user_name`
		FROM `doc_list`
		LEFT JOIN `doc_agent` ON `doc_agent`.`id`=`doc_list`.`agent`
		LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
		WHERE `doc_list`.`ok`>'0' AND `doc_list`.`type`='2'
		LIMIT 100");
		$tmpl->AddText("<table width='100%'>
		<tr><th>ID<th>Автор<th>Агент<th>Дата<th>Сумма<th>К начислению");
		$i=0;
		$users=array();
		while($nxt=mysql_fetch_assoc($res))
		{
			if(!isset($users[$nxt['user']]))
			{
				$users[$nxt['user']]=array();
				$users[$nxt['user']]['name']=$nxt['user_name'];
				$users[$nxt['user']]['sum']=0;
			}
			$res_tov=mysql_query("SELECT `doc_list_pos`.`id`, `doc_list_pos`.`tovar`, `doc_list_pos`.`cost`, `doc_list_pos`.`cnt`
			FROM `doc_list_pos`
			WHERE `doc_list_pos`.`doc`='{$nxt['id']}'");
			$nach_sum=0;
			while($nxt_tov=mysql_fetch_assoc($res_tov))
			{
				$incost = GetInCost($nxt_tov['tovar']);
				$nach_sum+=($nxt_tov['cost']-$incost)*$this->coeff;
			}
			$date=date("Y-m-d H:i:s", $nxt['date']);
			$tmpl->AddText("<tr class='lin$i'><td><a href='/doc.php?mode=body&doc={$nxt['id']}'>{$nxt['id']}</a>
			<td>{$nxt['user_name']}<td>{$nxt['agent_name']} <td>$date<td>{$nxt['sum']}<td><input type='text' value='$nach_sum'>");
			$i=1-$i;
			$users[$nxt['user']]['sum']=$nach_sum;
		}
		$tmpl->AddText("</table>
		Выплаты:<br>");
		foreach($users as $id=>$data)
		{
			$tmpl->AddText("$id({$data['name']}) - {$data['sum']} руб.<br>");
		}
	}
}


function getName()
{
	return "Расчёт и выплата зарплаты с продаж";
}

};

?>
