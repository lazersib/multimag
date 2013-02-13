<?php

include_once('include/doc.zapposeditor.php');

/// Сценарий автоматизации: сборка с перемещением и начислением заработной платы
class ds_sborka_zap
{

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
		Переместить готовый товар на склад:<br>
		<select name='nasklad'>
		<option value='0' selected>--не требуется--</option>");
		$res=mysql_query("SELECT `id`,`name` FROM `doc_sklady` ORDER BY `id`");
		while($nxt=mysql_fetch_row($res))
		{
			$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");
		}
		$tmpl->AddText("</select><br>
		<label><input type='checkbox' name='not_a_p' value='1'>Не проводить перемещение</label><br>

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
		$nasklad=rcv('nasklad');
		$firm=rcv('firm');
		$tov_id=rcv('tov_id');
		$not_a_p=rcv('not_a_p');
		$tim=time();
		$res=mysql_query("INSERT INTO `doc_list` (`date`, `firm_id`, `type`, `user`, `altnum`, `subtype`, `sklad`, `agent`)
				VALUES	('$tim', '$firm', '17', '$uid', '0', 'auto', '$sklad', '$agent')");
		if(mysql_errno())	throw new MysqlException("Не удалось создать документ");
		$doc=mysql_insert_id();
		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES ('$doc','cena','1'), ('$doc','script_mark','ds_sborka_zap'), ('$doc','nasklad','$nasklad'), ('$doc','tov_id','$tov_id'), ('$doc','not_a_p','$not_a_p')");
		header("Location: /doc_sc.php?mode=edit&sn=sborka_zap&doc=$doc&tov_id=$tov_id&agent=$agent&sklad=$sklad&firm=$firm&nasklad=$nasklad&not_a_p=$not_a_p");
	}
	else if($mode=='reopen')
	{
		$tmpl->AddText("<h1>".$this->getname()."</h1>");
		$doc=rcv('doc');
		$res=mysql_query("SELECT `firm_id`, `sklad`, `agent`, `ok` FROM `doc_list` WHERE `id`='$doc'");
		if(mysql_errno())			throw new MysqlException("Не удалось получить документ");
		if(!$nxt=mysql_fetch_assoc($res))	throw new Exception("Документ не найден");
		if($nxt['ok'])				throw new Exception("Операция не допускается для проведённого документа");
		$agent=$nxt['agent'];
		$sklad=$nxt['sklad'];
		$firm=$nxt['firm'];
		$res=mysql_query("SELECT `doc`,`param`,`value` FROM `doc_dopdata` WHERE `doc`='$doc'");
		if(mysql_errno())			throw new MysqlException("Не удалось получить дополниьтельные свойства документа");
		$no_mark=true;
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[1]=='script_mark' && $nxt[2]=='ds_sborka_zap')	$no_mark=false;
			else if($nxt[1]=='nasklad')	$nasklad=$nxt[2];
			else if($nxt[1]=='tov_id')	$tov_id=$nxt[2];
		}
		if($no_mark)	throw new Exception("Этот документ создан вручную, а не через сценарий. Недостаточно информации для редактирования документа через сценарий.");

		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES ('$doc','cena','1')");
		header("Location: /doc_sc.php?mode=edit&amp;sn=sborka_zap&amp;doc=$doc&amp;tov_id=$tov_id&amp;agent=$agent&amp;sklad=$sklad&amp;firm=$firm&amp;nasklad=$nasklad");
	}
	else if($mode=='edit')
	{
		$tov_id=rcv('tov_id');
		$doc=rcv('doc');
		$agent=rcv('agent');
		$sklad=rcv('sklad');
		$firm=rcv('firm');
		$nasklad=rcv('nasklad');
		$not_a_p=rcv('not_a_p');
		$this->ReCalcPosCost($doc,$tov_id);
		$zp=$this->CalcZP($doc);
		$tmpl->AddText("<h1>".$this->getname()."</h1>
		Необходимо выбрать товары, которые будут скомплектованы. Устанавливать цену не требуется - при проведении документа она будет выставлена автоматически исходя из стоимости затраченных ресурсов. Для того, чтобы узнать цены - обновите страницу. После выполнения сценария выбранные товары будут оприходованы на склад, а соответствующее им количество ресурсов, использованных для сборки, будет списано. Попытка провести через этот сценарий товары, не содержащие ресурсов, вызовет ошибку. Если это указано в свойствах товара, от агента-сборщика будет оприходована выбранная услуга для последующей выдачи заработной платы (на данный момент в размере $zp руб.).<br>
		<a href='/doc_sc.php?mode=exec&amp;sn=sborka_zap&amp;doc=$doc&amp;tov_id=$tov_id&amp;agent=$agent&amp;sklad=$sklad&amp;firm=$firm&amp;nasklad=$nasklad&amp;not_a_p=$not_a_p'>Выполнить необходимые действия</a>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>");

		$document=new doc_Sborka($doc);
		$poseditor=new SZapPosEditor($document);
		$dd=$document->getDopData();
		$poseditor->cost_id=$dd['cena'];
		$dd=$document->getDocData();
		$poseditor->SetEditable($dd[6]?0:1);
		$poseditor->sklad_id=$dd['sklad'];
		$tmpl->AddText($poseditor->Show());
	}
	else if($mode=='exec')
	{
		$doc=rcv('doc');
		$tov_id=rcv('tov_id');
		$agent=rcv('agent');
		$sklad=round(rcv('sklad'));
		$firm=rcv('firm');
		$nasklad=round(rcv('nasklad'));
		$not_a_p=rcv('not_a_p');
		$this->ReCalcPosCost($doc,$tov_id);
		$document=AutoDocument($doc);
		$document->DocApply();
		$zp=$this->CalcZP($doc);
		$tim=time();
		// Проверка, создано ли уже поступление зарплаты
		$res=mysql_query("SELECT `id` FROM `doc_list` WHERE `type`='1' AND `p_doc`='$doc'");
		if(mysql_num_rows($res))
		{
			list($post_doc)=mysql_fetch_row($res);
			mysql_query("UPDATE `doc_list_pos` SET `cost`='$zp' WHERE `doc`='$post_doc'");
			if(mysql_errno())	throw new MysqlException("Не удалось обновить услугу");
		}
		else
		{
			$altnum=GetNextAltNum(1,'auto',0,0,1);
			mysql_query("INSERT INTO `doc_list` (`date`, `firm_id`, `type`, `user`, `altnum`, `subtype`, `sklad`, `agent`, `p_doc`, `sum`)
			VALUES	('$tim', '$firm', '1', '$uid', '$altnum', 'auto', '$sklad', '$agent', '$doc', '$zp')");
			if(mysql_errno())	throw new MysqlException("Не удалось создать документ");
			$post_doc=mysql_insert_id();
			mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`) VALUES ('$post_doc', '$tov_id', '1', '$zp')");
			if(mysql_errno())	throw new MysqlException("Не удалось добавить услугу");
			$document2=AutoDocument($post_doc);
			$document2->DocApply();
		}

		mysql_query("UPDATE `doc_list` SET `sum`='$zp' WHERE `id`='$post_doc'");

		// Проверка, создано ли уже перемещение
		$res=mysql_query("SELECT `id` FROM `doc_list` WHERE `type`='8' AND `p_doc`='$doc'");
		if(mysql_num_rows($res))
		{
			list($perem_doc_num)=mysql_fetch_row($res);
			$r=mysql_query("SELECT `value` FROM `doc_dopdata` WHERE `doc`='$perem_doc_num' AND `param`='na_sklad'");
			list($nasklad)=mysql_fetch_row($r);
			$perem_doc=new doc_Peremeshenie($perem_doc_num);
		}
		else if( ($sklad!=$nasklad) && $nasklad)
		{
			$perem_doc=new doc_Peremeshenie();
			$perem_doc->CreateFrom($document);
			$perem_doc->SetDopData('na_sklad',$nasklad);
			$perem_doc->SetDopData('mest',1);
		}

		if( ($sklad!=$nasklad) && $nasklad)
		{
			$docnum=$perem_doc->getDocNum();
			$res=mysql_query("SELECT `tovar`, `cnt`, `cost` FROM `doc_list_pos` WHERE `doc`='$doc' AND `page`='0'");
			if(mysql_errno())	throw new MysqlException("Не удалось выбрать номенклатуру!");
			while($nxt=mysql_fetch_row($res))
			{
				mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`)
				VALUES ('$docnum', '$nxt[0]', '$nxt[1]', '$nxt[2]', '$nxt[3]')");
				if(mysql_errno())	throw new MysqlException("Не удалось сохранить номенклатуру!");
			}
			if(!$not_a_p)	$perem_doc->DocApply();
		}

		$tmpl->ajax=0;
		$tmpl->msg("Все операции выполнены успешно. Размер зарплаты: $zp");
	}
	else if($mode=='srv')
	{
		$opt=rcv('opt');
		$doc=rcv('doc');
		$document=new doc_Sborka($doc);
		$poseditor=new SZapPosEditor($document);
		$dd=$document->getDopData();
		$poseditor->cost_id=$dd['cena'];
		$dd=$document->getDocData();
		$poseditor->sklad_id=$dd['sklad'];
		$tmpl->ajax=1;
		$tmpl->SetText('');

		// Json-вариант списка товаров
		if($opt=='jget')
		{
			$doc_sum=DocSumUpdate($doc);
			$str="{ response: '2', content: [".$poseditor->GetAllContent()."], sum: '$doc_sum' }";
			$tmpl->AddText($str);
		}
		// Получение данных наименования
		else if($opt=='jgpi')
		{
			$pos=rcv('pos');
			$tmpl->AddText($poseditor->GetPosInfo($pos));
		}
		// Json вариант добавления позиции
		else if($opt=='jadd')
		{
			if(!isAccess('doc_sborka','edit'))	throw new AccessException("Недостаточно привилегий");
			$pos=rcv('pos');
			$tmpl->SetText($poseditor->AddPos($pos));
		}
		// Json вариант удаления строки
		else if($opt=='jdel')
		{
			if(!isAccess('doc_sborka','edit'))	throw new AccessException("Недостаточно привилегий");
			$line_id=rcv('line_id');
			$tmpl->SetText($poseditor->Removeline($line_id));
		}
		// Json вариант обновления
		else if($opt=='jup')
		{
			if(!isAccess('doc_sborka','edit'))	throw new AccessException("Недостаточно привилегий");
			$line_id=rcv('line_id');
			$value=rcv('value');
			$type=rcv('type');
			$tmpl->SetText($poseditor->UpdateLine($line_id, $type, $value));
		}
		// Получение номенклатуры выбранной группы
		else if($opt=='jsklad')
		{
			$group_id=rcv('group_id');
			$str="{ response: 'sklad_list', group: '$group_id',  content: [".$poseditor->GetSkladList($group_id)."] }";
			$tmpl->SetText($str);
		}
		// Поиск по подстроке по складу
		else if($opt=='jsklads')
		{
			$s=rcv('s');
			$str="{ response: 'sklad_list', content: [".$poseditor->SearchSkladList($s)."] }";
			$tmpl->SetText($str);
		}
		else if($opt=='jsn')
		{
			$action=rcv('a');
			$line_id=rcv('line');
			$data=rcv('data');
			$tmpl->SetText($poseditor->SerialNum($action, $line_id, $data) );
		}

	}
}

function ReCalcPosCost($doc, $tov_id)
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
			$acp=GetInCost($nx[0],0,true);
			if($acp>0)	$cost+=$nx[1]*$acp;
			else
			$cost+=$nx[1]*$nx[2];
			$cntc=$nxt[2]*$nx[1];
			if($acp>0)	mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`) VALUES ('$doc', '$nx[0]', '$cntc', '$acp', '$nxt[1]')");
			else
			mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`) VALUES ('$doc', '$nx[0]', '$cntc', '$nx[2]', '$nxt[1]')");
			if(mysql_errno())	throw new MysqlException("Не удалось добавить ресурс в документ");
		}

		// Расчитываем зарплату
		$rs=mysql_query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
		LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='$nxt[1]'
		WHERE `doc_base_params`.`param`='ZP'");
		if(mysql_errno())	throw new MysqlException("Не удалось выбрать доп.свойство товара");
		if(mysql_num_rows($rs))
		{
			$zp=mysql_result($rs,0,1);
			mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`) VALUES ('$doc', '$tov_id', '$nxt[2]', '$zp', '$nxt[1]')");
			if(mysql_errno())	throw new MysqlException("Не удалось добавить ресурс зарплаты в документ");
			$cost+=$zp;
		}
		else $zp=0;

		mysql_query("UPDATE `doc_list_pos` SET `cost`='$cost' WHERE `id`='$nxt[0]'");
	}
	DocSumUpdate($doc);
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
		//echo"$nxt[2] * ".mysql_result($rs,0,1)."<br>";
	}
	return $zp;
}

function getName()
{
	return "Сборка с выдачей заработной платы";
}

};

?>
