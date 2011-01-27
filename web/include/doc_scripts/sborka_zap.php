<?php

class ds_sborka_zap
{

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
		<input type='hidden' name='sn' value='sborka_zap'>
		Склад:<br>
		<select name='sklad'>");
		$res=mysql_query("SELECT `id`,`name` FROM `doc_sklady` ORDER BY `id`");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");
		}
		$tmpl->AddText("</select><br>
		Организация:<br><select name='firm'>");
		$rs=mysql_query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `firm_name`");
		while($nx=mysql_fetch_row($rs))
		{
			$tmpl->AddText("<option value='$nx[0]'>$nx[1]</option>");		
		}		
		$tmpl->AddText("</select><br>
		Агент:<br>
		<input type='hidden' name='agent' id='agent_id' value=''>
		<input type='text' id='agent_nm'  style='width: 450px;' value=''><br>
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
		$agent=rcv('agent');
		$sklad=rcv('sklad');
		$firm=rcv('firm');
		$tov_id=rcv('tov_id');
		$tim=time();
		$res=mysql_query("INSERT INTO `doc_list` (`date`, `firm_id`, `type`, `user`, `altnum`, `subtype`, `sklad`, `agent`)
				VALUES	('$tim', '$firm', '17', '$uid', '0', 'auto', '$sklad', '$agent')");
		if(mysql_errno())	throw new MysqlException("Не удалось создать документ");
		$doc=mysql_insert_id();
		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES ('$doc','cena','1')");
		header("Location: /doc_sc.php?mode=edit&sn=sborka_zap&doc=$doc&tov_id=$tov_id&agent=$agent&sklad=$sklad&firm=$firm");
	}
	else if($mode=='edit')
	{
		$tov_id=rcv('tov_id');
		$doc=rcv('doc');
		$agent=rcv('agent');
		$sklad=rcv('sklad');
		$firm=rcv('firm');
		$this->ReCalcPosCost($doc);
		$zp=$this->CalcZP($doc);
		$tmpl->AddText("<h1>".$this->getname()."</h1>
		Необходимо выбрать товары, которые будут скомплектованы. Устанавливать цену не требуется - при проведении документа она будет выставлена автоматически исходя из стоимости затраченных ресурсов. Для того, чтобы узнать цены - обновите страницу. После выполнения сценария выбранные товары будут оприходованы на склад, а соответствующее им количество ресурсов, использованных для сборки, будет списано. Попытка провести через этот сценарий товары, не содержащие ресурсов, вызовет ошибку. Если это указано в свойствах товара, от агента-сборщика будет оприходована выбранная услуга для последующей выдачи заработной платы (на данный момент в размере $zp руб.).<br>
		<a href='/doc_sc.php?mode=exec&sn=sborka_zap&doc=$doc&tov_id=$tov_id&agent=$agent&sklad=$sklad&firm=$firm'>Выполнить необходимые действия</a>");
		
		doc_poslist($doc);
		
		$tmpl->AddText("<script type=\"text/javascript\">
		window.document.onkeydown = OnEnterBlur; 
		</script>
		<table width=100% id='sklad_editor'>
		<tr><td id='groups' width=200 valign='top' class='lin0>'");
		doc_groups($doc);
		$tmpl->AddText("<td id='sklad' valign='top' class='lin1'>");
		doc_sklad($doc,0);
		$tmpl->AddText("</table>");
	}	
	else if($mode=='exec')
	{
		$doc=rcv('doc');
		$tov_id=rcv('tov_id');
		$agent=rcv('agent');
		$sklad=rcv('sklad');
		$firm=rcv('firm');
		$this->ReCalcPosCost($doc);
		$document=AutoDocument($doc);
		$document->ApplyJson();
		$zp=$this->CalcZP($doc);
		$tim=time();
		mysql_query("INSERT INTO `doc_list` (`date`, `firm_id`, `type`, `user`, `altnum`, `subtype`, `sklad`, `agent`, `p_doc`, `sum`)
		VALUES	('$tim', '$firm', '1', '$uid', '0', 'auto', '$sklad', '$agent', '$doc', '$zp')");
		if(mysql_errno())	throw new MysqlException("Не удалось создать документ");
		$doc2=mysql_insert_id();
		mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`) VALUES ('$doc2', '$tov_id', '1', '$zp')");
		if(mysql_errno())	throw new MysqlException("Не удалось добавить услугу");
		$document2=AutoDocument($doc2);
		$document2->ApplyJson();
		mysql_query("UPDATE `doc_list` SET `sum`='$zp' WHERE `id`='$doc2'");
		$tmpl->ajax=0;
		$tmpl->msg("Все операции выполнены успешно. Размер зарплаты: $zp");
	}
}

function ReCalcPosCost($doc)
{
	mysql_query("DELETE FROM `doc_list_pos`	WHERE `doc`='$doc' AND `page`!='0'");
	if(mysql_errno())	throw new MysqlException("Не удалось очистить документ от комплектующих");
	$res=mysql_query("SELECT `id`, `tovar`, `cnt` FROM `doc_list_pos`
	WHERE `doc`='$doc' AND `page`='0'");
	if(mysql_errno())	throw new MysqlException("Не удалось получить список товаров документа");
	while($nxt=mysql_fetch_row($res))
	{
		$cost=0;
		$rs=mysql_query("SELECT `doc_base_kompl`.`kompl_id`, `doc_base_kompl`.`cnt`, `doc_base`.`cost` FROM `doc_base_kompl`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_base_kompl`.`kompl_id`
		WHERE `doc_base_kompl`.`pos_id`='$nxt[1]'");
		if(mysql_errno())		throw new MysqlException("Не удалось получить список комплектующих товара $nxt[1]");
		if(mysql_num_rows($rs)==0)	throw new Exception("У товара $nxt[1] не заданы комплектующие");
		while($nx=mysql_fetch_row($rs))
		{
			$cost+=$nx[1]*$nx[2];
			$cntc=$nxt[2]*$nx[1];
			mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`) VALUES ('$doc', '$nx[0]', '$cntc', '$nx[2]', '$nxt[1]')");
			if(mysql_errno())	throw new MysqlException("Не удалось добавить ресурс в документ");
		}
		mysql_query("UPDATE `doc_list_pos` SET `cost`='$cost' WHERE `id`='$nxt[0]'");
	}
}

function CalcZP($doc)
{
	$zp=0;
	$res=mysql_query("SELECT `id`, `tovar`, `cnt` FROM `doc_list_pos`
	WHERE `doc`='$doc' AND `page`='0'");
	if(mysql_errno())	throw new MysqlException("Не удалось получить список товаров документа");
	while($nxt=mysql_fetch_row($res))
	{		
		$rs=mysql_query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
		LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='$nxt[1]'
		WHERE `doc_base_params`.`param`='ZP'");
		if(mysql_errno())	throw new MysqlException("Не удалось выбрать доп.свойство товара");
		if(!mysql_num_rows($res))	continue;
		$zp+=$nxt[2]*mysql_result($rs,0,1);
	}
	return $zp;
}

function getName()
{
	return "Сборка с выдачей заработной платы";
}

};

?>
