<?php

class ds_zp_s_prodaj
{
	var $coeff=0.05;

function Run($mode)
{
	global $tmpl, $uid;
	if($mode=='view')
	{
		$tmpl->AddText("<h1>".$this->getname()."</h1>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<form action='' method='post' enctype='multipart/form-data'>
		<input type='hidden' name='mode' value='create'>
		<input type='hidden' name='param' value='i'>
		<input type='hidden' name='sn' value='zp_s_prodaj'>
		Организация:<br><select name='firm'>");
		$rs=mysql_query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `firm_name`");
		while($nx=mysql_fetch_row($rs))
		{
			$tmpl->AddText("<option value='$nx[0]'>$nx[1]</option>");		
		}		
		$tmpl->AddText("</select><br>
		Услуга начисления зарплаты:<br>
		<input type='hidden' name='tov_id' id='tov_id' value=''>
		<input type='text' id='tov'  style='width: 400px;' value=''><br>
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
		while($nxt=mysql_fetch_assoc($res))
		{
			$date=date("Y-m-d H:i:s", $nxt['date']);
			$nach_sum=sprintf("%0.2f",$nxt['sum']*$this->coeff);
			$tmpl->AddText("<tr><td>{$nxt['id']}<td>{$nxt['user_name']}<td>{$nxt['agent_name']} <td>$date<td>{$nxt['sum']}<td>$nach_sum");
		}
		$tmpl->AddText("</table>");
	}
}


function getName()
{
	return "Расчёт и выплата зарплаты с продаж";
}

};

?>
