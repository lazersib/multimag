<?php

include_once('include/doc.poseditor.php');

class SZapPosEditor extends DocPosEditor
{

function Show($param='')
{
	global $CONFIG;
	// Список товаров
	$ret="
	<script src='/css/poseditor.js' type='text/javascript'></script>
	<link href='/css/poseditor.css' rel='stylesheet' type='text/css' media='screen'>
	<table width='100%' id='poslist'><thead><tr>
	<th width='60px' align='left'>№</th>";
	if($this->show_vc>0)	$ret.="<th width='90px' align='left' title='Код изготовителя'>Код</th>";
	$ret.="<th>Наименование</th>
	<th width='90px' title='Выбранная цена'>Выбр. цена</th>
	<th width='90px' class='hl'>Цена</th>
	<th width='60px' class='hl'>Кол-во</th>
	<th width='90px' class='hl'>Стоимость</th>
	<th width='60px' title='Остаток товара на складе'>Остаток</th>
	<th width='90px'>Зарплата</th>";
	$ret.="</tr>
	</thead>
	<tfoot>
	<tr id='pladd'>
	<td><input type='text' id='pos_id' autocomplete='off' tabindex='1'></td>";
	if($this->show_vc>0)	$ret.="<td><input type='text' id='pos_vc' autocomplete='off' tabindex='2'></td>";
	$ret.="<td><input type='text' id='pos_name' autocomplete='off' tabindex='3'></td>
	<td id='pos_scost'></td>
	<td><input type='text' id='pos_cost' autocomplete='off' tabindex='4'></td>
	<td><input type='text' id='pos_cnt' autocomplete='off' tabindex='5'></td>
	<td id='pos_sum'></td>
	<td id='pos_sklad_cnt'></td>
	<td id='pos_mesto'></td>";
	
	$ret.="
	</tr>
	</tfoot>
	<tbody>
	<tr><td colspan='9' style='text-align: center;'><img src='/img/icon_load.gif' alt='Загрузка...'>
 	</tbody>
	</table>
	<p align='right' id='sum'></p>";
	
	$ret.="
	<table id='sklad_view'>
	<tr><td id='groups_list' width='200' valign='top' class='lin0'>";
	$ret.=$this->getGroupsTree();
	$ret.="</td><td valign='top' class='lin1'>	
	<table width='100%' cellspacing='1' cellpadding='2'>
	<tr><thead>
	<th>№";
	if($this->show_vc>0)	$ret.="<th>Код";
	$ret.="<th>Наименование<th>Марка<th>Цена, р.<th>Ликв.<th>Р.цена, р.<th>Аналог";
	if($this->show_tdb>0)	$ret.="<th>Тип<th>d<th>D<th>B<th>Масса";
	if($this->show_rto>0)	$ret.="<th><img src='/img/i_lock.png' alt='В резерве'><th><img src='/img/i_alert.png' alt='Предложений'><th><img src='/img/i_truck.png' alt='В пути'>";
	$ret.="<th>Склад<th>Всего<th>Место
	</thead>
	<tbody id='sklad_list'>
	</tbody>
	</table>
	</td></tr>
	</table>";
	if(!@$CONFIG['poseditor']['need_dialog'])	$CONFIG['poseditor']['need_dialog']=0;
	else						$CONFIG['poseditor']['need_dialog']=1;
	$ret.=@"<script type=\"text/javascript\">
	var poslist=PosEditorInit('/doc_sc.php?mode=srv&sn=sborka_zap&doc={$this->doc}',{$this->editable})
	poslist.show_column['vc']='{$this->show_vc}'

	var skladview=document.getElementById('sklad_view')
	skladview.show_column['vc']='{$this->show_vc}'
	
	skladlist=document.getElementById('sklad_list').needDialog={$CONFIG['poseditor']['need_dialog']};
	</script>";	
	
	return $ret;
}

// Получить весь текущий список товаров (документа)
function GetAllContent()
{
	$res=mysql_query("SELECT `doc_list_pos`.`id` AS `line_id`, `doc_base`.`id` AS `pos_id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`cost` AS `bcost`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto`, `doc_list_pos`.`gtd`
	FROM `doc_list_pos`
	INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
	WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_list_pos`.`page`='0'");
	if(mysql_errno())	throw new MysqlException("Ошибка получения имени");
	$ret='';
	while($nxt=mysql_fetch_assoc($res))
	{
		if($this->cost_id)	$scost=GetCostPos($nxt['pos_id'], $this->cost_id);
		else			$scost=sprintf("%0.2f",$nxt['bcost']);
		$nxt['cost']=		sprintf("%0.2f",$nxt['cost']);
		if($ret)	$ret.=', ';
		
		// Расчитываем зарплату и выводим как место
		$rs=mysql_query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
		LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='{$nxt['pos_id']}'
		WHERE `doc_base_params`.`param`='ZP'");
		if(mysql_errno())	throw new MysqlException("Не удалось выбрать доп.свойство товара");
		if(mysql_num_rows($rs))
		{
			$zp=mysql_result($rs,0,1);
		}
		else $zp='НЕТ';
		
		$ret.="{
		line_id: '{$nxt['line_id']}', pos_id: '{$nxt['pos_id']}', vc: '{$nxt['vc']}', name: '{$nxt['name']} - {$nxt['proizv']}', cnt: '{$nxt['cnt']}', cost: '{$nxt['cost']}', scost: '$scost', sklad_cnt: '{$nxt['sklad_cnt']}', mesto: '$zp', gtd: '{$nxt['gtd']}'";
		
		$ret.="}";

	}
	return $ret;
}

function GetPosInfo($pos)
{
	$ret='{response: 0}';

	$res=mysql_query("SELECT `doc_list_pos`.`id` AS `line_id`, `doc_base`.`id` AS `pos_id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`cost` AS `bcost`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto`, `doc_list_pos`.`gtd`
	FROM `doc_base`
	LEFT JOIN `doc_list_pos` ON `doc_base`.`id`=`doc_list_pos`.`tovar` AND `doc_list_pos`.`doc`='{$this->doc}'
	LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
	WHERE `doc_base`.`id`='$pos'");
	if(mysql_errno())	throw new MysqlException("Ошибка получения имени");
	$ret='';
	if($nxt=mysql_fetch_assoc($res))
	{
		if($this->cost_id)	$scost=GetCostPos($nxt['pos_id'], $this->cost_id);
		else			$scost=sprintf("%0.2f",$nxt['bcost']);
		if(!$nxt['cnt'])	$nxt['cnt']=1;
		if(!$nxt['cost'])	$nxt['cost']=$scost;
		$nxt['cost']=		sprintf("%0.2f",$nxt['cost']);
		
		// Расчитываем зарплату и выводим как место
		$rs=mysql_query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
		LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='{$nxt['pos_id']}'
		WHERE `doc_base_params`.`param`='ZP'");
		if(mysql_errno())	throw new MysqlException("Не удалось выбрать доп.свойство товара");
		if(mysql_num_rows($rs))
		{
			$zp=mysql_result($rs,0,1);
		}
		else $zp='НЕТ';
		
		$ret="{response: 3, data: {
		line_id: '{$nxt['line_id']}', pos_id: '{$nxt['pos_id']}', vc: '{$nxt['vc']}', name: '{$nxt['name']} - {$nxt['proizv']}', cnt: '{$nxt['cnt']}', cost: '{$nxt['cost']}', scost: '$scost', sklad_cnt: '{$nxt['sklad_cnt']}', mesto: '$zp', gtd: '{$nxt['gtd']}'
		} }";
	}

	return $ret;
}


/// Добавляет указанную складскую позицию в список
function AddPos($pos)
{
	$cnt=rcv('cnt');
	$cost=rcv('cost');
	$add=0;
	$ret='';
		
	$res=mysql_query("SELECT `id`, `tovar`, `cnt`, `cost` FROM `doc_list_pos` WHERE `doc`='{$this->doc}' AND `tovar`='$pos'");
	if(mysql_errno())	throw new MysqlException("Не удалось выбрать строку документа!");
	if(mysql_num_rows($res)==0)
	{
		mysql_query("INSERT INTO doc_list_pos (`doc`,`tovar`,`cnt`,`cost`) VALUES ('{$this->doc}','$pos','$cnt','$cost')");
		if(mysql_errno())	throw new MysqlException("Не удалось вставить строку в документ!");
		$pos_line=mysql_insert_id();
		doc_log("UPDATE","add pos: pos:$pos",'doc',$this->doc);
		doc_log("UPDATE","add pos: pos:$pos",'pos',$pos);
		$add=1;
	}
	else
	{
		$nxt=mysql_fetch_row($res);
		$pos_line=$nxt[0];
		mysql_query("UPDATE `doc_list_pos` SET `cnt`='$cnt', `cost`='$cost' WHERE `id`='$nxt[0]'");
		if(mysql_errno())	throw MysqlException("Не удалось вставить строку в документ!");
		doc_log("UPDATE","change cnt: pos:$nxt[1], doc_list_pos:$nxt[0], cnt:$nxt[2]+1",'doc',$this->doc);
		doc_log("UPDATE","change cnt: pos:$nxt[1], doc_list_pos:$nxt[0], cnt:$nxt[2]+1, doc:{$this->doc}",'pos',$nxt[1]);
	}	
	$doc_sum=DocSumUpdate($this->doc);
	
	if($add)
	{
		$res=mysql_query("SELECT `doc_base`.`id`, `doc_base`.`vc`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`cnt` AS `sklad_cnt`, `doc_base_cnt`.`mesto`
		FROM `doc_list_pos`
		INNER JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->sklad_id}'
		WHERE `doc_list_pos`.`id`='$pos_line'");
		if(mysql_errno())	throw MysqlException("Не удалось получить строку документа");
		$line=mysql_fetch_assoc($res);
		
		$rs=mysql_query("SELECT `doc_base_params`.`id`, `doc_base_values`.`value` FROM `doc_base_params`
		LEFT JOIN `doc_base_values` ON `doc_base_values`.`param_id`=`doc_base_params`.`id` AND `doc_base_values`.`id`='{$line['id']}'
		WHERE `doc_base_params`.`param`='ZP'");
		if(mysql_errno())	throw new MysqlException("Не удалось выбрать доп.свойство товара");
		if(mysql_num_rows($rs))
		{
			$zp=mysql_result($rs,0,1);
		}
		else 
		$zp='НЕТ';
		
		$cost=$this->cost_id?GetCostPos($line['id'], $this->cost_id):$line['cost'];
		$ret="{ response: '1', add: { line_id: '$pos_line', pos_id: '{$line['id']}', vc: '{$line['vc']}', name: '{$line['name']} - {$line['proizv']}', cnt: '{$line['cnt']}', scost: '$cost', cost: '{$line['cost']}', sklad_cnt: '{$line['sklad_cnt']}', mesto: '$zp', gtd: '' }, sum: '$doc_sum' }";
	}
	else
	{
		$cost=sprintf("%0.2f",$cost);
		$ret="{ response: '4', update: { line_id: '$pos_line', cnt: '{$cnt}', cost: '{$cost}'}, sum: '$doc_sum' }";
	}
	return $ret;
}



};


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
		$this->ReCalcPosCost($doc,$tov_id);
		$zp=$this->CalcZP($doc);
		$tmpl->AddText("<h1>".$this->getname()."</h1>
		Необходимо выбрать товары, которые будут скомплектованы. Устанавливать цену не требуется - при проведении документа она будет выставлена автоматически исходя из стоимости затраченных ресурсов. Для того, чтобы узнать цены - обновите страницу. После выполнения сценария выбранные товары будут оприходованы на склад, а соответствующее им количество ресурсов, использованных для сборки, будет списано. Попытка провести через этот сценарий товары, не содержащие ресурсов, вызовет ошибку. Если это указано в свойствах товара, от агента-сборщика будет оприходована выбранная услуга для последующей выдачи заработной платы (на данный момент в размере $zp руб.).<br>
		<a href='/doc_sc.php?mode=exec&sn=sborka_zap&doc=$doc&tov_id=$tov_id&agent=$agent&sklad=$sklad&firm=$firm'>Выполнить необходимые действия</a>
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
		$sklad=rcv('sklad');
		$firm=rcv('firm');
		$this->ReCalcPosCost($doc,$tov_id);
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
			if(!isAccess('doc_'.$this->doc_name,'edit'))	throw new AccessException("Недостаточно привилегий");
			$pos=rcv('pos');
			$tmpl->SetText($poseditor->AddPos($pos));
		}
		// Json вариант удаления строки
		else if($opt=='jdel')
		{
			if(!isAccess('doc_'.$this->doc_name,'edit'))	throw new AccessException("Недостаточно привилегий");
			$line_id=rcv('line_id');
			$tmpl->SetText($poseditor->Removeline($line_id));
		}
		// Json вариант обновления
		else if($opt=='jup')
		{
			if(!isAccess('doc_'.$this->doc_name,'edit'))	throw new AccessException("Недостаточно привилегий");
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
			$acp=GetInCost($nxt[1]);
			if($acp>0)	$cost+=$nx[1]*$acp;
			else		$cost+=$nx[1]*$nx[2];
			$cntc=$nxt[2]*$nx[1];
			if($acp>0)	mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`) VALUES ('$doc', '$nx[0]', '$cntc', '$nx[2]', '$nxt[1]')");
			else		mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`) VALUES ('$doc', '$nx[0]', '$cntc', '$acp', '$nxt[1]')");
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
