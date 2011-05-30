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


$doc_types[2]="Реализация";

class doc_Realizaciya extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого

	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=2;
		$this->doc_name				='realizaciya';
		$this->doc_viewname			='Реализация товара';
		$this->sklad_editor_enable		=true;
		$this->sklad_modify			=-1;
		$this->header_fields			='agent cena sklad';
		$this->dop_menu_buttons			="<a href='' onclick=\"ShowPopupWin('/doc.php?mode=srv&amp;opt=dov&amp;doc=$doc'); return false;\" title='Доверенное лицо'><img src='img/i_users.png' alt='users'></a>";
		settype($this->doc,'int');
	}


	function DopHead()
	{
		global $tmpl;
		
		$cur_agent=$this->doc_data['agent'];
		if(!$cur_agent)		$cur_agent=1;
		
		if(!$this->dop_data['platelshik'])	$this->dop_data['platelshik']=$cur_agent;
		if(!$this->dop_data['gruzop'])		$this->dop_data['gruzop']=$cur_agent;
		
		$res=mysql_query("SELECT `name` FROM `doc_agent` WHERE `id`='{$this->dop_data['platelshik']}'");
		if(mysql_errno())	throw new MysqlException('Ошибка выборки имени плательщика');
		$plat_name=mysql_result($res,0,0);
		
		$res=mysql_query("SELECT `name` FROM `doc_agent` WHERE `id`='{$this->dop_data['gruzop']}'");
		if(mysql_errno())	throw new MysqlException('Ошибка выборки имени грузополучателя');
		$gruzop_name=mysql_result($res,0,0);
				
		$tmpl->AddText("<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		Плательщик:<br>
		<input type='hidden' name='plat_id' id='plat_id' value='{$this->dop_data['platelshik']}'>
		<input type='text' id='plat'  style='width: 450px;' value='$plat_name'><br>
		Грузополучатель:<br>
		<input type='hidden' name='gruzop_id' id='gruzop_id' value='{$this->dop_data['gruzop']}'>
		<input type='text' id='gruzop'  style='width: 450px;' value='$gruzop_name'><br>
		<script type=\"text/javascript\">
		$(document).ready(function(){
			$(\"#plat\").autocomplete(\"/docs.php\", {
			delay:300,
			minChars:1,
			matchSubset:1,
			autoFill:false,
			selectFirst:true,
			matchContains:1,
			cacheLength:10,
			maxItemsToShow:15, 	 
			formatItem:agliFormat,
			onItemSelect:platselectItem,
			extraParams:{'l':'agent','mode':'srv','opt':'ac'}
			});
			$(\"#gruzop\").autocomplete(\"/docs.php\", {
			delay:300,
			minChars:1,
			matchSubset:1,
			autoFill:false,
			selectFirst:true,
			matchContains:1,
			cacheLength:10,
			maxItemsToShow:15, 	 
			formatItem:agliFormat,
			onItemSelect:gruzopselectItem,
			extraParams:{'l':'agent','mode':'srv','opt':'ac'}
			});
		});
	
		function platselectItem(li) {
		if( li == null ) var sValue = \"Ничего не выбрано!\";
		if( !!li.extra ) var sValue = li.extra[0];
		else var sValue = li.selectValue;
		document.getElementById('plat_id').value=sValue;
		}
		
		function gruzopselectItem(li) {
		if( li == null ) var sValue = \"Ничего не выбрано!\";
		if( !!li.extra ) var sValue = li.extra[0];
		else var sValue = li.selectValue;
		document.getElementById('gruzop_id').value=sValue;
		}
		</script>
		");	
		$checked=$this->dop_data['received']?'checked':'';
		$tmpl->AddText("<label><input type='checkbox' name='received' value='1' $checked>Документы подписаны и получены</label><br>");	
	}

	function DopSave()
	{
		$plat_id=rcv('plat_id');
		$gruzop_id=rcv('gruzop_id');
		$received=rcv('received');
		
		$doc=$this->doc;
		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
		VALUES ( '{$this->doc}' ,'platelshik','$plat_id'), ( '{$this->doc}' ,'gruzop','$gruzop_id'),  ( '{$this->doc}' ,'received','$received')");
	}
	
	function DopBody()
	{
		global $tmpl;
		if($this->dop_data['received'])
			$tmpl->AddText("<br><b>Документы подписаны и получены</b><br>");
	}

	function DocApply($silent=0)
	{
		global $CONFIG;
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if( !($nx=@mysql_fetch_row($res) ) )	throw new MysqlException('Ошибка выборки данных документа при проведении!');		
		if( $nx[4] && ( !$silent) )		throw new Exception('Документ уже был проведён!');		
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if( !$res )				throw new MysqlException('Ошибка проведения, ошибка установки даты проведения!');
		
		$res=mysql_query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`pos_type`, `doc_list_pos`.`id`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$nx[3]'
		WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_base`.`pos_type`='0'");
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[1]>$nxt[2])	throw new Exception("Недостаточно ($nxt[1]) товара '$nxt[3]:$nxt[4]($nxt[0])': на складе только $nxt[2] шт!");
			if(!$silent)
			{
				$budet=CheckMinus($nxt[0], $nx[3]);
				if( $budet<0)		throw new Exception("Невозможно ($silent), т.к. будет недостаточно ($budet) товара '$nxt[3]:$nxt[4]($nxt[0])'!");
			}
			mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nx[3]'");
			if(mysql_error())	throw new MysqlException('Ошибка проведения, ошибка изменения количества!');
			
			if(@$CONFIG['poseditor']['sn_restrict'])
			{
				$r=mysql_query("SELECT COUNT(`doc_list_sn`.`id`) FROM `doc_list_sn` WHERE `rasx_list_pos`='$nxt[6]'");
				$sn_cnt=mysql_result($r,0,0);
				if($sn_cnt!=$nxt[1])	throw new Exception("Количество серийных номеров товара $nxt[0] ($nxt[1]) не соответствует количеству серийных номеров ($sn_cnt)");
			}
		}
		if($silent)	return;
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if( !$res )				throw new MysqlException('Ошибка проведения, ошибка установки даты проведения!');
	}
	
	function DocCancel()
	{
		global $uid;	
		$tmpl->ajax=1;
		$tim=time();
		$dd=date_day($tim);

		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка выборки данных документа!');
		if(! ($nx=@mysql_fetch_row($res)))	throw new Exception('Документ не найден!');	
		if(! $nx[4])				throw new Exception('Документ НЕ проведён!');
		
		$res=mysql_query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$this->doc}' AND `ok`>'0'");
		if(!$res)				throw new MysqlException('Ошибка выборки потомков документов!');	
		if(mysql_num_rows($res))		throw new Exception('Документ оплачен! Нельзя отменять!');

		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка установки флага!');
		$res=mysql_query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base`.`pos_type` FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`	WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_base`.`pos_type`='0'");
		if(mysql_errno())			throw new MysqlException('Ошибка выбоки товаров документа!');

		while($nxt=mysql_fetch_row($res))
		{
			mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`+'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nx[3]'");
			if(mysql_error())	throw new MysqlException("Ошибка изменения количества товара id:$nxt[0] на складе $nx[3]!");
		}	
	}

	function PrintForm($doc, $opt='')
	{
		global $tmpl;
		if($opt=='')
		{
			
			$tmpl->ajax=1;
			$tmpl->AddText("
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=nak'\">Накладная</div>			
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=kop'\">Копия чека</div>		
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=tg12'\">Форма ТОРГ-12 (УСТАРЕЛО)</div>			
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=tg12_pdf'\">Форма ТОРГ-12 (PDF)</div>			
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=sf_pdf'\">Счёт - фактура (PDF)</div>			
			<div onclick=\"ShowPopupWin('/doc.php?mode=print&amp;doc=$doc&amp;opt=sf_email'); return false;\">Счёт - фактура по e-mail</div>");
		}
		//			<li><a href='?mode=print&amp;doc=$doc&amp;opt=sf'>Счёт - фактура (HTML)</a></li>
		else if($opt=='tg12')
			$this->PrintTg12($doc);
		else if($opt=='tg12_pdf')
		{	
// 			if(!$this->doc_data[6])
// 			{
// 				doc_menu(0,0);
// 				$tmpl->AddText("<h1>Реализация</h1>");
// 				$tmpl->msg("Сначала нужно провести документ!","err");
// 			}
// 			else 
			$this->PrintTg12PDF();
		}	
		else if($opt=='sf')
			$this->PrintSfak($doc);
		else if($opt=='sf_pdf')
			$this->SfakPDF($doc);
		else if($opt=='sf_email')
			$this->SfakEmail($doc);
		else if($opt=='kop')
			$this->PrintKopia($doc);
		else if($opt=='tc')
			$this->PrintTovCheck($doc);
		else
			$this->PrintNakl($doc);
	}
	// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		global $tmpl;
		global $uid;

		if($target_type=='')
		{
			$tmpl->ajax=1;
			$tmpl->AddText("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc=$doc&amp;tt=6'\">Приходный кассовый ордер</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc=$doc&amp;tt=4'\">Приход средств в банк</div>");
		}
		else if($target_type==6)
		{
			$sum=DocSumUpdate($this->doc);
			mysql_query("START TRANSACTION");
			$tm=time();
			$altnum=GetNextAltNum($target_type ,$this->doc_data[10]);
			$res=mysql_query("INSERT INTO `doc_list`
			(`type`, `agent`, `date`, `kassa`, `user`, `altnum`, `subtype`, `p_doc`, `sum`, `firm_id`)
			VALUES ('$target_type', '{$this->doc_data[2]}', '$tm', '1', '$uid', '$altnum', '{$this->doc_data[10]}', '{$this->doc}', '$sum', '{$this->doc_data[17]}')");
			$ndoc= mysql_insert_id();

			if($res)
			{
				mysql_query("COMMIT");
				$ref="Location: doc.php?mode=body&doc=$ndoc";
				header($ref);
			}
			else
			{
				mysql_query("ROLLBACK");
				$tmpl->msg("Не удалось создать подчинённый документ!","err");
			}
		}
		else if($target_type==4)
		{
			$sum=DocSumUpdate($this->doc);
			mysql_query("START TRANSACTION");
			$tm=time();
			$altnum=GetNextAltNum($target_type ,$this->doc_data[10]);
			$res=mysql_query("INSERT INTO `doc_list`
			(`type`, `agent`, `date`, `bank`, `user`, `altnum`, `subtype`, `p_doc`, `sum`, `firm_id`)
			VALUES ('$target_type', '{$this->doc_data[2]}', '$tm', '1', '$uid', '$altnum', '{$this->doc_data[10]}', '{$this->doc}', '$sum', '{$this->doc_data[17]}')");
			$ndoc= mysql_insert_id();
			if($res)
			{
				mysql_query("COMMIT");
				$ref="Location: doc.php?mode=body&doc=$ndoc";
				header($ref);
			}
			else
			{
				mysql_query("ROLLBACK");
				$tmpl->msg("Не удалось создать подчинённый документ!","err");
			}
		}
		else
		{
			$tmpl->msg("В разработке","info");
		}
	}
	// Выполнить удаление документа. Если есть зависимости - удаление не производится.
	function DelExec($doc)
	{
		$res=mysql_query("SELECT `ok` FROM `doc_list` WHERE `id`='$doc'");
		if(!mysql_result($res,0,0)) // Если проведён - нельзя удалять
		{
			$res=mysql_query("SELECT `id`, `mark_del` FROM `doc_list` WHERE `p_doc`='$doc'");
			if(!mysql_num_rows($res)) // Если есть потомки - нельзя удалять
			{
				mysql_query("DELETE FORM `doc_list_pos` WHERE `doc`='$doc'");
				mysql_query("DELETE FROM `doc_dopdata` WHERE `doc`='$doc'");
				mysql_query("DELETE FROM `doc_list` WHERE `id`='$doc'");
				return 0;
			}
		}
		return 1;
   	}
	function Service($doc)
	{
		get_docdata($doc);
		global $tmpl;
		global $uid;

		$tmpl->ajax=1;
		$opt=rcv('opt');
		$pos=rcv('pos');

		{
			if(parent::_Service($opt,$pos))	{}
			else if($opt=='dov')
			{
				$rr=mysql_query("SELECT `name`,`surname` FROM `doc_agent_dov`
				WHERE `id`='".$this->dop_data['dov_agent']."'");
				if(mysql_numrows($rr))
					$agn=mysql_result($rr,0,0)." ".mysql_result($rr,0,1);
				else
					$agn="";

				$tmpl->AddText("<form method='post' action=''>
<input type=hidden name='mode' value='srv'>
<input type=hidden name='opt' value='dovs'>
<input type=hidden name='doc' value='$doc'>
<table>
<tr><th>Доверенное лицо (<a href='docs.php?l=dov&mode=edit&ag_id={$this->doc_data[2]}' title='Добавить'><img border=0 src='img/i_add.png' alt='add'></a>)
<tr><td><input type=hidden name=dov_agent value='".$this->dop_data['dov_agent']."' id='sid' ><input type=text id='sdata' value='$agn' onkeydown=\"return RequestData('/docs.php?l=dov&mode=srv&opt=popup&ag={$this->doc_data[2]}')\">
		<div id='popup'></div>
		<div id=status></div>

<tr><th class=mini>Номер доверенности
<tr><td><input type=text name=dov value='".$this->dop_data['dov']."' class=text>

<tr><th>Дата выдачи
<tr><td>
<p class='datetime'>
<input type=text name=dov_data value='".$this->dop_data['dov_data']."' id='id_pub_date_date'  class='vDateField required text' >
</p>

</table>
<input type=submit value='Сохранить'></form>");

			}
			else if($opt=="dovs")
			{
				if(!isAccess('doc_'.$this->doc_name,'edit'))	throw new AccessException("Недостаточно привилегий");
				$dov=rcv('dov');
				$dov_agent=rcv('dov_agent');
				$dov_data=rcv('dov_data');
				mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)  VALUES ('$doc','dov','$dov')");
				mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)  VALUES ('$doc','dov_agent','$dov_agent')");
				mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)  VALUES ('$doc','dov_data','$dov_data')");
				$ref="Location: doc.php?mode=body&doc=$doc";
				header($ref);
				doc_log("Add doverennost","dov:$dov, dov_agent:$dov_agent, dov_data:$dov_data",'doc', $doc);
			}
			else $tmpl->msg("Неизвестная опция $opt!");
		}
	}
//	================== Функции только этого класса ======================================================

// -- Обычная накладная --------------
	function PrintNakl($doc)
	{
		global $tmpl;
		global $uid;

		$tmpl->LoadTemplate('print');
		$dt=date("d.m.Y",$this->doc_data[5]);

		$tmpl->AddText("<h1>Накладная N {$this->doc_data[9]}{$this->doc_data[10]}, от $dt </h1>
		<b>Поставщик: </b>{$this->firm_vars['firm_name']}<br>
		<b>Покупатель: </b>{$this->doc_data[3]}<br><br>");

		$tmpl->AddText("
		<table width=800 cellspacing=0 cellpadding=0>
		<tr><th>№</th><th width=450>Наименование<th>Место<th>Кол-во<th>Стоимость<th>Сумма</tr>");
		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`mesto`, `doc_units`.`printname` AS `units`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data[7]}'
		LEFT JOIN `doc_units` ON `doc_base`.`unit`=`doc_units`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		$i=0;
		$ii=1;
		$sum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f руб.", $nxt[4]);
			$cost2 = sprintf("%01.2f руб.", $sm);
			$tmpl->AddText("<tr align=right><td>$ii</td><td align=left>$nxt[0] $nxt[1] / $nxt[2]<td>$nxt[5]<td>$nxt[3] $nxt[6]<td>$cost<td>$cost2");
			$i=1-$i;
			$ii++;
			$sum+=$sm;
		}
		$ii--;
		$cost = sprintf("%01.2f руб.", $sum);
		
		$prop='';
		if($sum>0)
		{
			$add='';
			if($nxt[12]) $add=" OR (`p_doc`='{$this->doc_data['p_doc']}' AND (`type`='4' OR `type`='6'))";
			$rs=mysql_query("SELECT SUM(`sum`) FROM `doc_list` WHERE 
			(`p_doc`='{$this->doc}' AND (`type`='4' OR `type`='6'))
			$add
			AND `ok`>0 AND `p_doc`!='0' GROUP BY `p_doc`");
			if(@$prop=mysql_result($rs,0,0))
			{
				$prop=sprintf("<p><b>Оплачено</b> %0.2f руб.</p>",$prop);
			}	
		}
		

		$tmpl->AddText("</table>
		<p>Всего <b>$ii</b> наименований на сумму <b>$cost</b></p>
		<p class=mini>Товар получил, претензий к качеству товара и внешнему виду не имею.</p>
		$prop
		<p>Поставщик:_____________________________________</p>
		<p>Покупатель: ____________________________________</p>");
	}
	
	// -- Копия чека --------------
	function PrintKopia($doc)
	{
		global $tmpl;
		global $uid;

		$tmpl->LoadTemplate('print');
		$dt=date("d.m.Y",$this->doc_data[5]);

		$tmpl->AddText("<h1>Копия чека N {$this->doc_data[9]}{$this->doc_data[10]}, от $dt</h1>
		<b>Поставщик: </b>".$this->firm_vars['firm_name'].", ".$this->firm_vars['firm_adres'].", ".$this->firm_vars['firm_telefon']."<br>
		<br /><br />");

		$tmpl->AddText("
		<table width=800 cellspacing=0 cellpadding=0>
		<tr><th>№</th><th width=450>Наименование<th>Кол-во<th>Стоимость<th>Сумма</tr>");
		$res=mysql_query("SELECT `doc_group`.`printname`,`doc_base`.`name`,`doc_base`.`proizv` ,`doc_list_pos`.`cnt`,`doc_list_pos`.`cost`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		$i=0;
		$ii=1;
		$sum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f руб.", $nxt[4]);
			$cost2 = sprintf("%01.2f руб.", $sm);
			$tmpl->AddText("<tr align=right><td>$ii</td><td align=left>$nxt[0] $nxt[1] / $nxt[2]<td>$nxt[3]<td>$cost<td>$cost2");
			$i=1-$i;
			$ii++;
			$sum+=$sm;
		}
		$ii--;
		$cost = sprintf("%01.2f руб.", $sum);

		$tmpl->AddText("</table>
		<p>Всего <b>$ii</b> наименований на сумму <b>$cost</b></p>
		<p>Поставщик:_____________________________________</p>
		<br><br><p align=right>Место печати</p>");
	}
	
		// -- Обычная накладная --------------
	function PrintTovCheck()
	{
		global $tmpl;
		global $uid;

		$tmpl->LoadTemplate('print');
		$dt=date("d.m.Y",$this->doc_data[5]);

		$tmpl->AddText("<h1>Товарный чек N {$this->doc_data[9]}{$this->doc_data[10]}, от $dt</h1>
		".$this->firm_vars['firm_name']."<br>
		ИНН: ".$this->firm_vars['firm_inn']."<br>
		Содержание хозяйственной операции: продажа товаров за наличный расчёт
		<br><br>");

		$tmpl->AddText("
		<table width=800 cellspacing=0 cellpadding=0>
		<tr><th>N пор.</th><th width=450>Наименование товара<th>Ед. изм.<th>Цена<th>Кол-во<th>Сумма</tr>");
		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_units`.`printname`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_units` ON `doc_base`.`unit`=`doc_units`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		$i=0;
		$ii=1;
		$sum=$cnt=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f", $nxt[4]);
			$cost2 = sprintf("%01.2f", $sm);
			if($nxt[2])	$nxt[1].=' / '.$nxt[2];
			$tmpl->AddText("<tr align=right><td>$ii</td><td align=left>$nxt[0] $nxt[1]<td>$nxt[5]<td>$cost<td>$nxt[3]<td>$cost2");
			$i=1-$i;
			$ii++;
			$sum+=$sm;
			$cnt+=$nxt[3];
		}
		$ii--;
		$cost = sprintf("%01.2f", $sum);
		$sum_p=num2str($sum);
		$tmpl->AddText("
		<tr><td><td colspan='3'><b>Итого:</b><td>$cnt<td>$cost
		</table>
		<p>Всего отпущено и оплачено наличными денежными средствами $ii товаров на сумму:<br>$sum_p</p>
		<p>{$this->firm_vars['firm_name']}: _____________________________________</p>
		");
	}

// -- Накладная торг 12 -------------------
function PrintTg12()
{
	global $tmpl, $uid;
	$doc=$this->doc;

	if(!$this->doc_data[6])
	{
		doc_menu(0,0);
		$tmpl->AddText("<h1>Реализация</h1>");
		$tmpl->msg("Сначала нужно провести документ!","err");
	}
	else
	{
		$tmpl->LoadTemplate('print_tg12');
		$dt=date("d.m.Y",$this->doc_data[5]);

		$res=mysql_query("SELECT `doc_agent`.`gruzopol`, `doc_agent`.`fullname`, `doc_agent`.`adres`,  `doc_agent`.`tel`, `doc_agent`.`inn`, `doc_agent`.`okpo`, `doc_agent`.`okevd`, `doc_agent`.`bik`, `doc_agent`.`rs`, `doc_agent`.`ks`, `doc_agent`.`bank`
		FROM `doc_agent` WHERE `doc_agent`.`id`='{$this->doc_data[2]}'	");

		if($nx=@mysql_fetch_row($res))
		{
			$dt=date("d.m.Y",$this->doc_data[5]);

			$rr=mysql_query("SELECT `surname`,`name`,`name2`,`range` FROM `doc_agent_dov`
			WHERE `id`='".$this->dop_data['dov_agent']."'");
			if($nn=@mysql_fetch_row($rr))
			{
				$dov_agn="$nn[0] $nn[1] $nn[2]";
				$dov_agr=$nn[3];
			}
			else
				$dov_agn=$dov_agr="";
				
			if($this->doc_data[13])
			{
				$rs=mysql_query("SELECT `doc_list`.`sklad`, `doc_kassa`.`name`, `doc_kassa`.`bik`, `doc_kassa`.`rs` FROM `doc_list` 
				LEFT JOIN `doc_kassa` ON `doc_kassa`.`num`=`doc_list`.`bank` AND `doc_kassa`.`ids`='bank'
				WHERE `doc_list`.`id`='{$this->doc_data[13]}'");
				$nnn=mysql_fetch_row($rs);
				$this->firm_vars['firm_schet']=$nnn[3];
				$this->firm_vars['firm_bik']=$nnn[2];
				$this->firm_vars['firm_bank']=$nnn[1];	
			}

                $tmpl->AddText("
        <table width=1200 cellspacing=0 cellpadding=0 border=0 class=ht>
        <tr class=ht>
        <td width=550 class=ht></td>
        <td class=ht align=right>Унифицированная форма ТОРГ-12 Утверждена постановлением госкомстата России от 25.12.98 № 132</td>
        </tr></table>

        <table width=1200 cellspacing=0 cellpadding=0 border=0>
        <tr>
        <td width=900>
        <!--        Shapka+rekvizity        -->
        <table width=100% cellspacing=0 cellpadding=0 border=0>
        <tr><tr><td class=ul>".$this->firm_vars['firm_gruzootpr'].", тел.".$this->firm_vars['firm_telefon']."
         счёт ".$this->firm_vars['firm_schet']." БИК ".$this->firm_vars['firm_bik'].", банк ".$this->firm_vars['firm_bank']."
         </td></tr>
        <tr><td class=microc>грузоотправитель, адрес, номер телефона, банковские реквизиты</td></tr>
        <tr><td class=ul>(отсутствует)</td></tr>
        <tr><td class=microc>структурное подразделение</td></tr>
        </table>

        <br>

        <table width=100% cellspacing=0 cellpadding=0 border=0>
        <tr><tr><td width=200>Грузополучатель</td><td class=ul>$nx[0] </td></tr>
        <tr><tr><td width=200>Поставщик</td><td class=ul>{$this->firm_vars['firm_name']},{$this->firm_vars['firm_adres']}, ИНН/КПП {$this->firm_vars['firm_inn']}, кс {$this->firm_vars['firm_bank_kor_s']}, р/с {$this->firm_vars['firm_schet']}, бик {$this->firm_vars['firm_bik']}, в банке {$this->firm_vars['firm_bank']}</td></tr>
        <tr><tr><td width=200>Плательщик</td><td class=ul>$nx[1], адрес $nx[2], тел. $nx[3], ИНН/КПП $nx[4], ОКПО $nx[5],  ОКВЭД $nx[6], БИК $nx[7], Р/С $nx[8], К/С $nx[9], банк $nx[10]
        <tr><tr><td width=200>Основание</td><td class=ul></td></tr>
        <tr><tr><td width=200></td><td class=microc>договор, заказ-наряд</td></tr>
        </table>

        <br>

        <table class=tn>
        <tr><tr><td class=tl> </td><td class=microc>Номер документа</td><td class=microc>дата составления</td><td  width=300> </td></tr>
        <tr><tr><td  class=tl>ТОВАРНАЯ НАКЛАДНАЯ</td><td class=bc>{$this->doc_data[9]}</td><td class=bc>$dt</td><td  width=300> </td></tr>
        </table>


        </td>
        <td class=ht align=right>
        <!--        Kodi                   -->
        <table class=tn>
        <tr><td></td><td width=30></td><td class=rst>Код</td></tr>
        <tr><td align=right colspan=2>Форма по ОКУД</td><td class=rsh>0330212</td></tr>
        <tr><td></td><td align=right>по ОКПО</td><td class=rsm>".$this->firm_vars['firm_okpo']."</td></tr>
        <tr><td align=right colspan=2>Вид деятельности по ОКДП</td><td class=rsm></td></tr>
        <tr><td></td><td align=right>по ОКПО</td><td class=rsm>$nx[5]</td></tr>
        <tr><td></td><td align=right>по ОКПО</td><td class=rsm>".$this->firm_vars['firm_okpo']."</td></tr>
        <tr><td></td><td align=right>по ОКПО</td><td class=rsm>$nx[5]</td></tr>
        <tr><td></td><td class=rsl>номер</td><td class=rsm></td></tr>
        <tr><td align=right>Транспортная</td><td class=rsl>дата</td><td class=rsm></td></tr>
        <tr><td align=right>накладная</td><td class=rsl>номер</td><td class=rsm></td></tr>
        <tr><td></td><td class=rsl>дата</td><td class=rsm></td></tr>
        <tr><td></td><td class=rslb>Вид операции</td><td class=rsb></td></tr>
        </table>

        </td>
        </tr></table>
<br>


<table class=tm>
<thead>
<tr>
<td rowspan=2>Номер по порядку<td colspan=2>Товар<td colspan=2>Единица измерения<td rowspan=2>Вид упаковки<td colspan=2>Количество
<td rowspan=2>Масса брутто<td rowspan=2>Количество / масса нетто<td rowspan=2>Цена, руб. коп.<td rowspan=2>Сумма без учёта НДС, руб. коп
<td colspan=2>НДС<td rowspan=2>Сумма с учётом НДС, руб. коп.
<tr>
<td>наименование, характеристика, сорт, артикул товара<td>Код<td>наиме- нование<td>код по ОКЕИ<td>в одном месте<td>мест, штук<td>Ставка %<td>Сумма
<tr><td>1<td>2<td id=bb>3<td>4<td id=bb>5<td id=bb>6<td id=bb>7<td id=bb>8<td id=bb>9<td id=bb>10<td id=bb>11<td id=bb>12<td>13<td id=bb>14<td id=bb>15

<tbody>");
                $res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_dop`.`mass`
                FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		WHERE `doc_list_pos`.`doc`='$doc' ");
                $i=0;
                $ii=0;

                $summass=$sum=$sumnaloga=0;
                $cnt=0;
                $nds=$this->firm_vars['param_nds']/100;
                $ndsp=$this->firm_vars['param_nds'];
                while($nxt=mysql_fetch_row($res))
                {
			if($this->doc_data[12])
			{
				$cena = $nxt[4]/(1+$nds);
				$stoimost = $cena*$nxt[3];
				$nalog = ($nxt[4]*$nxt[3])-$stoimost;
				$snalogom = $nxt[4]*$nxt[3];
			}
			else
			{
				$cena = $nxt[4];
				$stoimost = $cena*$nxt[3];
				$nalog = $stoimost*$nds;
				$snalogom = $stoimost+$nalog;
			}

			$i=1-$i;
			$ii++;
			$mass=$nxt[5]*$nxt[3];
			$summass+=$mass;
			$cnt+=$nxt[3];
			$i=1-$i;
			$cena = 	sprintf("%01.2f", $cena);
			$stoimost = sprintf("%01.2f", $stoimost);
			$nalog = 	sprintf("%01.2f", $nalog);
			$snalogom = sprintf("%01.2f", $snalogom);
			$mass = 	sprintf("%01.3f", $mass);
			$mass1 = 	sprintf("%01.3f", $nxt[5]);
			$sum+=$snalogom;
			$sumnaloga+=$nalog;
			$tmpl->AddText("
<tr>
<td>$ii
<td id=bb>$nxt[0] $nxt[1] / $nxt[2]
<td id=bb><td id=bb>шт.
<td>796<td><td><td id=bd><td>$mass1
<td>$nxt[3] / $mass
<td>$cena
<td id=bb>$stoimost
<td id=bb>$ndsp%
<td>$nalog
<td id=bb>$snalogom
");
		}
		$ii--;


                $sumbeznaloga = sprintf("%01.2f", $sum-$sumnaloga);
                $sumnaloga = sprintf("%01.2f", $sumnaloga);
                $sum = sprintf("%01.2f", $sum);
                $summass = sprintf("%01.3f", $summass);

		$cnt_p=num2str($cnt,'sht',0);
		$mass_p=num2str($summass,'kg',3);
		$sum_p=num2str($sum);

                $tmpl->AddText("
<tbody>
<tr class=nb>
<td>
<td>
<td id=bb><td>
<td id=bb colspan=3>Всего по накладной
<td id=bt><td id=bt>$summass
<td id=bt>$cnt / $summass
<td id=bt>
<td id=bt>$sumbeznaloga
<td id=bs>--
<td id=bt>$sumnaloga
<td id=bt>$sum

</table>

<br>

<table class=tb>
<tr><td width=20><td width=420><td class=txt>Масса груза (нетто)<td class=ul>$mass_p<td class=bc>$summass
<tr><td class=txt>Всего мест<td class=ul><td class=txt>Масса груза (брутто)<td class=ul><td class=bc>
</table>

<table class=tb>
<tr><td class=cl>


<table class=tp>
<tr><td colspan=2>Приложение (паспорта, сертификаты, и.т.п.) на
<td width=20><td class=ul>
<td width=20>
<td colspan=2>листах
</tr>
<tr>
<td colspan=7><b>Всего отпущено $cnt_p наименований на сумму $sum_p</b>

<tr>
<td class=fc>Отпуск разрешил
<td class=ul>Директор<td width=20>
<td class=ul><td width=20>
<td class=ul>".$this->firm_vars['firm_director']."<td width=20>

<tr>
<td>
<td class=microc>должность<td width=20>
<td class=microc>подпись<td width=20>
<td class=microc>расшифровка подписи<td width=20>

<tr>
<td colspan=2>Главный (старший) бухгалтер
<td width=20>
<td class=ul><td width=20>
<td class=ul>".$this->firm_vars['firm_buhgalter']."<td width=20>

<tr>
<td><td><td width=20>
<td class=microc>подпись<td width=20>
<td class=microc>расшифровка подписи<td width=20>

<tr>
<td class=fc>Отпуск груза произвёл
<td class=ul>Кладовщик<td width=20>
<td class=ul><td width=20>
<td class=ul>".$this->firm_vars['firm_kladovshik']."<td width=20>

<tr>
<td>
<td class=microc>должность<td width=20>
<td class=microc>подпись<td width=20>
<td class=microc>расшифровка подписи<td width=20>


<tr>
<td align=right>М.П.
<td align=right>\"___\"
<td width=20>
<td class=ul><td width=20>
<td>20__ года<td width=20>

</table>


</td>
<td width=50%>


<table class=tp>
<tr>
<td class=fc>По доверенности №
<td class=ul colspan=5>".$this->dop_data['dov']." от ".$this->dop_data['dov_data']."
<td width=20>

<tr>
<td class=fc>
<td class=microc colspan=5>кем, кому (организация, должность, фамилия и. о.)
<td width=20>

<tr>
<td class=fc>выданной
<td class=ul colspan=5>$dov_agr $dov_agn
<td width=20>

<tr>
<td class=fc><br>Груз принял
<td class=ul><td width=20>
<td class=ul><td width=20>
<td class=ul><td width=20>

<tr>
<td>
<td class=microc>должность<td width=20>
<td class=microc>подпись<td width=20>
<td class=microc>расшифровка подписи<td width=20>

<tr>
<td class=fc>Груз получил грузополучатель
<td class=ul><td width=20>
<td class=ul><td width=20>
<td class=ul><td width=20>

<tr>
<td>
<td class=microc>должность<td width=20>
<td class=microc>подпись<td width=20>
<td class=microc>расшифровка подписи<td width=20>


<tr>
<td align=right>М.П.
<td align=right>\"___\"
<td width=20>
<td class=ul><td width=20>
<td>20__ года<td width=20>

</table>


</td></tr>
</table>
                ");
                }
        }


}

	function SfakEmail($doc, $email='')
	{
		global $tmpl;
		if(!$email)
			$email=rcv('email');
		
		if($email=='')
		{
			$tmpl->ajax=1;
			get_docdata($doc);
			$res=mysql_query("SELECT `email` FROM `doc_agent` WHERE `id`='{$this->doc_data[2]}'");
			$email=mysql_result($res,0,0);
			$tmpl->AddText("<form action=''>
			<input type=hidden name=mode value='print'>
			<input type=hidden name=doc value='$doc'>
			<input type=hidden name=opt value='sf_email'>
			email:<input type=text name=email value='$email'><br>
			Коментарий:<br>
			<textarea name='comm'></textarea><br>
			<input type=submit value='&gt;&gt;'>
			</form>");	
		}
		else
		{
			global $mail;
			$comm=rcv('comm');
			$sender_name=$_SESSION['name'];
			
			$res=mysql_query("SELECT `rname`, `tel`, `email` FROM `users` WHERE `id`='{$this->doc_data[8]}'");
			$manager_name=@mysql_result($res,0,0);	
			$manager_tel=@mysql_result($res,0,1);
			$manager_email=@mysql_result($res,0,2);	
			
			if(!$manager_email)
			{
				$mail->Body = "Доброго времени суток!\nВо вложении находится заказанная Вами счёт-фактура от {$CONFIG['site']['name']}\n\n$comm\n\nСообщение сгенерировано автоматически, отвечать на него не нужно!";
			}
			else
			{
				$mail->Body = "Доброго времени суток!\nВо вложении находится заказанная Вами счёт-фактура от {$CONFIG['site']['name']}\n\n$comm\n\nИсполнительный менеджер $manager_name\nКонтактный телефон: $manager_tel\nЭлектронная почта (e-mail): $manager_email\nОтправитель: $sender_name";
 				$mail->Sender   = $manager_email;  
 				$mail->From     = $manager_email;  
 				//$mail->FromName = "{$mail->FromName} ({$manager_name})";
			}

			$mail->AddAddress($email, $email );  
			$mail->Subject="Счёт-фактура от {$CONFIG['site']['name']}";
			
			$mail->AddStringAttachment($this->SfakPDF($doc, 1), "schet_fak.pdf");  
			if($mail->Send())
				$tmpl->msg("Сообщение отправлено!","ok");
			else
				$tmpl->msg("Ошибка отправки сообщения!",'err');
    		}	
	}

function PrintTg12PDF($to_str=0)
{
	global $CONFIG;
	$st_line=0.2;
	$bold_line=0.6;
	define('FPDF_FONT_PATH',$CONFIG['site']['location'].'/fpdf/font/');
	require('fpdf/fpdf_mysql.php');
	
	$dt=date("d.m.Y",$this->doc_data[5]);
	
	$res=mysql_query("SELECT '', `doc_agent`.`fullname`, `doc_agent`.`adres`,  `doc_agent`.`tel`, `doc_agent`.`inn`, `doc_agent`.`okpo`, `doc_agent`.`okevd`, `doc_agent`.`bik`, `doc_agent`.`rs`, `doc_agent`.`ks`, `doc_agent`.`bank`
	FROM `doc_agent` WHERE `doc_agent`.`id`='{$this->doc_data[2]}'	");
	if(mysql_errno())		throw new MysqlException("Невозможно получить данные агента!");	
	$agent_info=mysql_fetch_array($res);
	if(!$agent_info)		throw new Exception('Агент не найден');	
	
	
	$res=mysql_query("SELECT `doc_agent`.`name`, `doc_agent`.`fullname`, `doc_agent`.`adres`,  `doc_agent`.`tel`, `doc_agent`.`inn`, `doc_agent`.`okpo`, `doc_agent`.`okevd`, `doc_agent`.`bik`, `doc_agent`.`rs`, `doc_agent`.`ks`, `doc_agent`.`bank`
	FROM `doc_agent` WHERE `doc_agent`.`id`='{$this->dop_data['gruzop']}'	");
	if(mysql_errno())		throw new MysqlException("Невозможно получить данные грузополучателя!");	
	$gruzop_info=mysql_fetch_array($res);
	if(!$gruzop_info)		$gruzop_info=array();
	$gruzop='';
	if($gruzop_info['fullname'])	$gruzop.=$gruzop_info['fullname'];
	else				$gruzop.=$gruzop_info['name'];
	if($gruzop_info['adres'])	$gruzop.=', адрес '.$gruzop_info['adres'];
	if($gruzop_info['tel'])		$gruzop.=', тел. '.$gruzop_info['tel'];
	if($gruzop_info['inn'])		$gruzop.=', ИНН/КПП '.$gruzop_info['inn'];
	if($gruzop_info['okevd'])	$gruzop.=', ОКВЭД '.$gruzop_info['okevd'];
	if($gruzop_info['rs'])		$gruzop.=', Р/С '.$gruzop_info['rs'];
	if($gruzop_info['bank'])	$gruzop.=', в банке '.$gruzop_info['bank'];
	if($gruzop_info['bik'])		$gruzop.=', БИК '.$gruzop_info['bik'];
	if($gruzop_info['ks'])		$gruzop.=', К/С '.$gruzop_info['ks'];
	
	
	$res=mysql_query("SELECT `doc_agent`.`fullname`, `doc_agent`.`adres`,  `doc_agent`.`tel`, `doc_agent`.`inn`, `doc_agent`.`okpo`, `doc_agent`.`okevd`, `doc_agent`.`bik`, `doc_agent`.`rs`, `doc_agent`.`ks`, `doc_agent`.`bank`
	FROM `doc_agent` WHERE `doc_agent`.`id`='{$this->dop_data['platelshik']}'	");
	if(mysql_errno())		throw new MysqlException("Невозможно получить данные плательщика");	
	$platelshik_info=mysql_fetch_array($res);
	if(!$platelshik_info)		$platelshik_info=array();
	$platelshik='';
	if($platelshik_info['fullname'])	$platelshik.=$platelshik_info['fullname'];
	else					$platelshik.=$platelshik_info['name'];
	if($platelshik_info['adres'])		$platelshik.=', адрес '.$platelshik_info['adres'];
	if($platelshik_info['tel'])		$platelshik.=', тел. '.$platelshik_info['tel'];
	if($platelshik_info['inn'])		$platelshik.=', ИНН/КПП '.$platelshik_info['inn'];
	if($platelshik_info['okevd'])		$platelshik.=', ОКВЭД '.$platelshik_info['okevd'];
	if($platelshik_info['rs'])		$platelshik.=', Р/С '.$platelshik_info['rs'];
	if($platelshik_info['bank'])		$platelshik.=', в банке '.$platelshik_info['bank'];
	if($platelshik_info['bik'])		$platelshik.=', БИК '.$platelshik_info['bik'];
	if($platelshik_info['ks'])		$platelshik.=', К/С '.$platelshik_info['ks'];
	
	$str = unhtmlentities("{$platelshik_info['fullname']}, адрес {$platelshik_info['adres']}, тел. {$platelshik_info['tel']}, ИНН/КПП {$platelshik_info['inn']}, ОКПО {$platelshik_info['okpo']},  ОКВЭД {$platelshik_info['okevd']}, БИК {$platelshik_info['bik']}, Р/С {$platelshik_info['rs']}, К/С {$platelshik_info['ks']}, банк {$platelshik_info['bank']}");

	$rr=mysql_query("SELECT `surname`,`name`,`name2`,`range` FROM `doc_agent_dov`
	WHERE `id`='{$this->dop_data['dov_agent']}'");
	if(mysql_errno())		throw new MysqlException("Невозможно получить данные доверенного лица!");
	if($nn=@mysql_fetch_row($rr))
	{
		$dov_agn="$nn[0] $nn[1] $nn[2]";
		$dov_agr=$nn[3];
	}
	else	$dov_agn=$dov_agr="";
		
	if($this->doc_data['p_doc'])
	{
		$res=mysql_query("SELECT `doc_list`.`sklad`, `doc_kassa`.`name`, `doc_kassa`.`bik`, `doc_kassa`.`rs` FROM `doc_list` 
		LEFT JOIN `doc_kassa` ON `doc_kassa`.`num`=`doc_list`.`bank` AND `doc_kassa`.`ids`='bank'
		WHERE `doc_list`.`id`='{$this->doc_data[13]}'");
		$bank_data=mysql_fetch_array($res);
		$this->firm_vars['firm_schet']=$bank_data[3];
		$this->firm_vars['firm_bik']=$bank_data[2];
		$this->firm_vars['firm_bank']=$bank_data[1];
	}

	$pdf=new FPDF('P');
	$pdf->Open();
	$pdf->SetAutoPageBreak(1,12);
	$pdf->AddFont('Arial','','arial.php');
	$pdf->tMargin=5;
	$pdf->AddPage('L');
	$pdf->SetFillColor(255);

	$pdf->SetFont('Arial','',7);
	$str = 'Унифицированная форма ТОРГ-12 Утверждена постановлением госкомстата России от 25.12.98 № 132';
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->Cell(0,4,$str,0,0,'R');
	$pdf->Ln();
	$t2_y=$pdf->GetY();
	
	$pdf->SetFont('','',8);
	$str=unhtmlentities($this->firm_vars['firm_gruzootpr'].", тел.".$this->firm_vars['firm_telefon'].", счёт ".$this->firm_vars['firm_schet'].", БИК ".$this->firm_vars['firm_bik'].", банк ".$this->firm_vars['firm_bank']);
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->MultiCell(230,4,$str,0,'L');
	$y=$pdf->GetY();
	$pdf->Line(10, $pdf->GetY(), 230, $pdf->GetY());	
	$pdf->SetFont('','',5);
	$str="грузоотправитель, адрес, номер телефона, банковские реквизиты";
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->Cell(230,2,$str,0,1,'C');
	
	
	$pdf->SetFont('','',8);
	$str="< отсутствует >";
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->Cell(0,4,$str,0,1,'L');		
	$pdf->Line(10, $pdf->GetY(), 230, $pdf->GetY());		
	$pdf->SetFont('','',5);
	$str="структурное подразделение";
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->Cell(220,2,$str,0,1,'C');
	
	$pdf->Ln(5);	
	$pdf->SetFont('','',8);
	
	$str="Грузополучатель";
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->Cell(30,4,$str,0,0,'L');
	$str = iconv('UTF-8', 'windows-1251', unhtmlentities($gruzop));
	$pdf->MultiCell(190,4,$str,0,'L');
	$pdf->Line(40, $pdf->GetY(), 230, $pdf->GetY());
	
	$str="Поставщик";
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->Cell(30,4,$str,0,0,'L');
	$str = unhtmlentities("{$this->firm_vars['firm_name']}, {$this->firm_vars['firm_adres']}, ИНН/КПП {$this->firm_vars['firm_inn']}, кс {$this->firm_vars['firm_bank_kor_s']}, Р/С {$this->firm_vars['firm_schet']}, БИК {$this->firm_vars['firm_bik']}, в банке {$this->firm_vars['firm_bank']}");
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->MultiCell(190,4,$str,0,'L');
	$pdf->Line(40, $pdf->GetY(), 230, $pdf->GetY());
	
	$str="Плательщик";
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->Cell(30,4,$str,0,0,'L');
	$str = iconv('UTF-8', 'windows-1251', unhtmlentities($platelshik));
	$pdf->MultiCell(190,4,$str,0,'L');
	$pdf->Line(40, $pdf->GetY(), 230, $pdf->GetY());
	
	$str="Основание";
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->Cell(30,4,$str,0,0,'L');
	
	$str = "";
	
	$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`date`
	FROM `doc_list`
	WHERE `doc_list`.`agent`='{$this->doc_data[2]}' AND `doc_list`.`type`='14' AND `doc_list`.`ok`>'0'
	ORDER BY  `doc_list`.`date` DESC");
	if(mysql_errno())		throw new MysqlException("Невозможно получить данные договора!");
	
	if($nxt=mysql_fetch_row($res))
	{
		$str.="Договор N$nxt[1] от ".date("d.m.Y",$nxt[2]).", ";	
	}
	
	if($this->doc_data['p_doc'])
	{
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`date`, `doc_list`.`p_doc`, `doc_list`.`type` FROM `doc_list`
		WHERE `id`={$this->doc_data['p_doc']}");
		$nxt=mysql_fetch_row($res);
		if($nxt)
		{
			if($nxt[4]==1)		$str.="Счёт N$nxt[1] от ".date("d.m.Y",$nxt[2]).", ";
			else if($nxt[4]==16)	$str.="Спецификация N$nxt[1] от ".date("d.m.Y",$nxt[2]).", ";
			if($nxt[3])
			{
				$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`date`, `doc_list`.`p_doc` FROM `doc_list`
				WHERE `id`={$nxt[3]} AND `doc_list`.`type`='16'");
				$nxt=mysql_fetch_row($res);
				if($nxt)	$str.="Спецификация N$nxt[1] от ".date("d.m.Y",$nxt[2]).", ";	
			}
		}
	}
	
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->MultiCell(190,4,$str,0,'L');
	$pdf->Line(40, $pdf->GetY(), 230, $pdf->GetY());
	$pdf->SetFont('','',5);
	$str="договор, заказ-наряд";
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->Cell(220,2,$str,0,1,'C');
	
	$t3_y=$pdf->GetY();
	
	$set_x=255;
	$width=17;
	$pdf->SetFont('','',7);
	$pdf->SetY($t2_y);
	$set_x=$pdf->w-$pdf->rMargin-$width;
	
	$str='Коды';
	$pdf->SetX($set_x);
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->Cell($width,4,$str,1,1,'C');
	$set_x=$pdf->w-$pdf->rMargin-$width*2;
	
	$tbt_y=$pdf->GetY();
	
	$lines=array('Форма по ОКУД', 'по ОКПО', 'Вид деятельности по ОКДП', 'по ОКПО', 'по ОКПО', 'по ОКПО');
	foreach($lines as $str)
	{
		$pdf->SetX($set_x);
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($width,4,$str,0,1,'R');
	}
	$lines=array('Номер','Дата','Номер','Дата');
	foreach($lines as $str)
	{
		$pdf->SetX($set_x);
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($width,4,$str,1,1,'R');
	}
	$str='Вид операции';
	$pdf->SetX($set_x);
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->Cell($width,4,$str,0,1,'R');
	
	$tbt_h=$pdf->GetY()-$tbt_y;
	$set_x=$pdf->w-$pdf->rMargin-$width;
	$pdf->SetY($tbt_y);
	$pdf->SetX($pdf->w-$pdf->rMargin-$width);
	$pdf->SetLineWidth($bold_line);
	$pdf->Cell($width,$tbt_h,'',1,1,'R');
	$pdf->SetLineWidth($st_line);
	
	$pdf->SetY($tbt_y);
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	$lines=array('0330212', $this->firm_vars['firm_okpo'], '', $gruzop_info['okpo'], $this->firm_vars['firm_okpo'], $platelshik_info['okpo'], '', '', '', '');
	foreach($lines as $str)
	{
		$pdf->SetX($set_x);
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($width,4,$str,1,1,'C');
	}
	
	$pdf->SetY($tbt_y+4*7+2);
	$pdf->SetX($pdf->w-$pdf->rMargin-$width*3-3);
	$str='Транспортная накладная';
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->MultiCell($width+3,6,$str,0,'R');
	
	$pdf->SetY($t3_y+5);
	$pdf->SetX(40);
	$pdf->Cell(60,4,'',0,0,'R');
	$str='Номер документа';
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->Cell(25,4,$str,1,0,'C');
	$str='Дата составления';
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->Cell(25,4,$str,1,1,'C');
	$pdf->SetX(40);
	$pdf->SetLineWidth($bold_line);
	$pdf->SetFont('','',10);
	$str='ТОВАРНАЯ НАКЛАДНАЯ';
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->Cell(60,4,$str,0,0,'R');
	$pdf->SetFont('','',7);
	$pdf->Cell(25,4,$this->doc_data[9],1,0,'C');
	$pdf->Cell(25,4,$dt,1,1,'C');
	$pdf->Ln(3);

// ====== Основная таблица =============
        $y=$pdf->GetY();
        
        $t_all_offset=array();
        
	$pdf->SetLineWidth($st_line);
	$t_width=array(12,85,29,14,22,14,19,16,18,29,0);
	$t_ydelta=array(2,1,1,3,1,5,2,5,2,1,3);
	$t_text=array(
	'Номер по поряд- ку',
	'Товар',
	'Единица измерения',
	'Вид упаковки',
	'Количество',
	'Масса брутто',
	'Количе- ство (масса нетто)',
	'Цена, руб. коп.',
	'Сумма без учёта НДС, руб. коп',
	'НДС',
	'Сумма с учётом НДС, руб. коп.');
	
	foreach($t_width as $w)
	{
		$pdf->Cell($w,16,'',1,0,'C',0);
	}
	$pdf->Ln();
	$pdf->Ln(0.5);
	$pdf->SetFont('','',8);
	$offset=0;
	foreach($t_width as $i => $w)
	{
		$t_all_offset[$offset]=$offset;
		$pdf->SetY($y+$t_ydelta[$i]+0.2);
		$pdf->SetX($offset+$pdf->lMargin);
		$str = iconv('UTF-8', 'windows-1251', $t_text[$i] );	
		$pdf->MultiCell($w,3,$str,0,'C',0);
		$offset+=$w;
	}
        
        $t2_width=array(73, 12, 15, 14, 11, 11, 15, 14);
        $t2_start=array(1,1,2,2,4,4,9,9);
        $t2_ydelta=array(2,4,2,2,1,3,3,3);
        $t2_text=array(
	'наименование, характеристика, сорт, артикул товара',
	'код',
	'наимено- вание',
	'код по ОКЕИ',
	'в одном месте',
	'мест, штук',
	'ставка %',
	'сумма');
	$offset=0;
	$c_id=0;
	$old_col=0;
	$y+=5;
	
	foreach($t2_width as $i => $w2)
	{
		while($c_id<$t2_start[$i])	
		{
			$t_a[$offset]=$offset;
			$offset+=$t_width[$c_id++];
		}
		
		if($old_col==$t2_start[$i])	$off2+=$t2_width[$i-1];
		else				$off2=0;
		$old_col=$t2_start[$i];
		$t_all_offset[$offset+$off2]=$offset+$off2;
		$pdf->SetY($y);
		$pdf->SetX($offset+$off2+$pdf->lMargin);
		$pdf->Cell($w2,11,'',1,0,'C',0);
		
		$pdf->SetY($y+$t2_ydelta[$i]);
		$pdf->SetX($offset+$off2+$pdf->lMargin);
		$str = iconv('UTF-8', 'windows-1251', $t2_text[$i] );	
		$pdf->MultiCell($w2,3,$str,0,'C',0);
	}
	
	sort ( $t_all_offset, SORT_NUMERIC );
	$pdf->SetY($y+11);
	$t_all_width=array();
	$old_offset=0;
	foreach($t_all_offset as $offset)
	{
		if($offset==0)	continue;
		$t_all_width[]=	$offset-$old_offset;
		$old_offset=$offset;
	}
	$t_all_width[]=0;
	$i=1;
	foreach($t_all_width as $id => $w)
	{
		$pdf->Cell($w,4,$i,1,0,'C',0);
		$i++;
	}
	$pdf->Ln();
	
	$y=$pdf->GetY();
	
	$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_units`.`printname`, `doc_base_dop`.`mass`
	FROM `doc_list_pos`
	LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
	LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `doc_units` ON `doc_base`.`unit`=`doc_units`.`id`
	WHERE `doc_list_pos`.`doc`='{$this->doc}' ");
	$i=0;
	$ii=0;
	$line_height=4;
	$summass=$sum=$sumnaloga=$cnt=0;
	$list_summass=$list_sum=$list_sumnaloga=$list_cnt=0;
	$nds=$this->firm_vars['param_nds']/100;
	$ndsp=$this->firm_vars['param_nds'];
	while($nxt=mysql_fetch_row($res))
	{
		if($this->doc_data[12])
		{
			$cena = $nxt[4]/(1+$nds);
			$stoimost = $cena*$nxt[3];
			$nalog = ($nxt[4]*$nxt[3])-$stoimost;
			$snalogom = $nxt[4]*$nxt[3];
		}
		else
		{
			$cena = $nxt[4];
			$stoimost = $cena*$nxt[3];
			$nalog = $stoimost*$nds;
			$snalogom = $stoimost+$nalog;
		}

		$i=1-$i;
		$ii++;
		$mass=$nxt[6]*$nxt[3];
		$cnt+=$nxt[3];
		$list_cnt+=$nxt[3];
		$i=1-$i;
		$cena = 	sprintf("%01.2f", $cena);
		$stoimost = 	sprintf("%01.2f", $stoimost);
		$nalog = 	sprintf("%01.2f", $nalog);
		$snalogom =	sprintf("%01.2f", $snalogom);
		$mass = 	sprintf("%01.3f", $mass);
		$mass1 = 	sprintf("%01.3f", $nxt[5]);
		$summass+=$mass;
		$list_summass+=$mass;
		$sum+=$snalogom;
		$list_sum+=$snalogom;
		$sumnaloga+=$nalog;
		$list_sumnaloga+=$nalog;
		
		$pdf->Cell($t_all_width[0],$line_height, $ii ,1,0,'R',0);
		$str = iconv('UTF-8', 'windows-1251', "$nxt[0] $nxt[1] / $nxt[2]" );
		$pdf->Cell($t_all_width[1],$line_height, $str ,1,0,'L',0);
		$pdf->Cell($t_all_width[2],$line_height, '-' ,1,0,'C',0);
		$str = iconv('UTF-8', 'windows-1251', $nxt[5] );
		$pdf->Cell($t_all_width[3],$line_height, $str ,1,0,'C',0);
		$pdf->Cell($t_all_width[4],$line_height, '-' ,1,0,'C',0);
		$pdf->Cell($t_all_width[5],$line_height, '-' ,1,0,'C',0);
		$pdf->Cell($t_all_width[6],$line_height, '-' ,1,0,'C',0);
		$pdf->Cell($t_all_width[7],$line_height, '-' ,1,0,'C',0);
		$pdf->Cell($t_all_width[8],$line_height, $mass1 ,1,0,'C',0);
		$pdf->Cell($t_all_width[9],$line_height, "$nxt[3] / $mass" ,1,0,'C',0);
		
		$pdf->Cell($t_all_width[10],$line_height, $cena ,1,0,'C',0);
		$pdf->Cell($t_all_width[11],$line_height, $stoimost ,1,0,'C',0);
		$pdf->Cell($t_all_width[12],$line_height, "$ndsp%" ,1,0,'C',0);
		$pdf->Cell($t_all_width[13],$line_height, $nalog ,1,0,'R',0);
		$pdf->Cell($t_all_width[14],$line_height, $snalogom ,1,0,'R',0);
		$pdf->Ln();
		
		if($pdf->GetY()>190)
		{
			$pdf->SetLineWidth($bold_line);
			$pdf->Rect($t_all_offset[2]+$pdf->lMargin, $y, $t_all_offset[3]-$t_all_offset[2], $pdf->GetY()-$y);
			$pdf->Rect($t_all_offset[4]+$pdf->lMargin, $y, $t_all_offset[12]-$t_all_offset[4], $pdf->GetY()-$y);
			$pdf->Rect($t_all_offset[13]+$pdf->lMargin, $y, $pdf->w-$pdf->rMargin-$pdf->lMargin-$t_all_offset[13], $pdf->GetY()-$y);
			$pdf->SetLineWidth($st_line);
			
			$list_sumbeznaloga = sprintf("%01.2f", $list_sum-$list_sumnaloga);
			$list_sumnaloga = sprintf("%01.2f", $list_sumnaloga);
			$list_sum = sprintf("%01.2f", $list_sum);
			$list_summass = sprintf("%01.3f", $list_summass);
		
			$w=0;
			for($i=0;$i<7;$i++)	$w+=$t_all_width[$i];
			$str = iconv('UTF-8', 'windows-1251', "Всего" );
			$pdf->Cell($w,$line_height, $str ,0,0,'R',0);
			$pdf->Cell($t_all_width[7],$line_height, '-' ,1,0,'C',0);
			$pdf->Cell($t_all_width[8],$line_height, $list_summass ,1,0,'C',0);
			$pdf->Cell($t_all_width[9],$line_height, "$list_cnt / $list_summass" ,1,0,'C',0);
			
			$pdf->Cell($t_all_width[10],$line_height, '' ,1,0,'C',0);
			$pdf->Cell($t_all_width[11],$line_height, $list_sumbeznaloga ,1,0,'C',0);
			$pdf->Cell($t_all_width[12],$line_height, "-" ,1,0,'C',0);
			$pdf->Cell($t_all_width[13],$line_height, $list_sumnaloga ,1,0,'R',0);
			$pdf->Cell($t_all_width[14],$line_height, $list_sum ,1,0,'R',0);
			$pdf->Ln();
			
			$pdf->AddPage('L');
			$y=$pdf->GetY();
			$list_summass=$list_sum=$list_sumnaloga=0;
		}
	}
	
	$pdf->SetLineWidth($bold_line);
	$pdf->Rect($t_all_offset[2]+$pdf->lMargin, $y, $t_all_offset[3]-$t_all_offset[2], $pdf->GetY()-$y);
	$pdf->Rect($t_all_offset[4]+$pdf->lMargin, $y, $t_all_offset[12]-$t_all_offset[4], $pdf->GetY()-$y);
	$pdf->Rect($t_all_offset[13]+$pdf->lMargin, $y, $pdf->w-$pdf->rMargin-$pdf->lMargin-$t_all_offset[13], $pdf->GetY()-$y);
        $pdf->SetLineWidth($st_line);
	
	$list_sumbeznaloga = sprintf("%01.2f", $list_sum-$list_sumnaloga);
	$list_sumnaloga = sprintf("%01.2f", $list_sumnaloga);
	$list_sum = sprintf("%01.2f", $list_sum);
	$list_summass = sprintf("%01.3f", $list_summass);

	$w=0;
	for($i=0;$i<7;$i++)	$w+=$t_all_width[$i];
	$str = iconv('UTF-8', 'windows-1251', "Всего" );
	$pdf->Cell($w,$line_height, $str ,0,0,'R',0);
	$pdf->Cell($t_all_width[7],$line_height, '-' ,1,0,'C',0);
	$pdf->Cell($t_all_width[8],$line_height, $list_summass ,1,0,'C',0);
	$pdf->Cell($t_all_width[9],$line_height, "$list_cnt / $list_summass" ,1,0,'C',0);
	
	$pdf->Cell($t_all_width[10],$line_height, '' ,1,0,'C',0);
	$pdf->Cell($t_all_width[11],$line_height, $list_sumbeznaloga ,1,0,'C',0);
	$pdf->Cell($t_all_width[12],$line_height, "-" ,1,0,'C',0);
	$pdf->Cell($t_all_width[13],$line_height, $list_sumnaloga ,1,0,'R',0);
	$pdf->Cell($t_all_width[14],$line_height, $list_sum ,1,0,'R',0);
	$pdf->Ln();
	
	
	$sumbeznaloga = sprintf("%01.2f", $sum-$sumnaloga);
	$sumnaloga = sprintf("%01.2f", $sumnaloga);
	$sum = sprintf("%01.2f", $sum);
	$summass = sprintf("%01.3f", $summass);

        $w=0;
        for($i=0;$i<7;$i++)	$w+=$t_all_width[$i];
        $str = iconv('UTF-8', 'windows-1251', "Итого по накладной" );
	$pdf->Cell($w,$line_height, $str ,0,0,'R',0);
        $pdf->Cell($t_all_width[7],$line_height, '-' ,1,0,'C',0);
	$pdf->Cell($t_all_width[8],$line_height, $summass ,1,0,'C',0);
	$pdf->Cell($t_all_width[9],$line_height, "$cnt / $summass" ,1,0,'C',0);
	
	$pdf->Cell($t_all_width[10],$line_height, '' ,1,0,'C',0);
	$pdf->Cell($t_all_width[11],$line_height, $sumbeznaloga ,1,0,'C',0);
	$pdf->Cell($t_all_width[12],$line_height, "-" ,1,0,'C',0);
	$pdf->Cell($t_all_width[13],$line_height, $sumnaloga ,1,0,'R',0);
	$pdf->Cell($t_all_width[14],$line_height, $sum ,1,0,'R',0);
        $pdf->Ln();
	
	if($pdf->GetY()>140)
		$pdf->AddPage('L');
	
	$cnt_p=num2str($cnt,'sht',0);
	$mass_p=num2str($summass,'kg',3);
	$sum_p=num2str($sum);
	
	// Левая часть с подписями
	$y=$pdf->GetY();
	$old_rmargin=$pdf->rMargin;
	$pdf->rMargin=round($pdf->w/2);
	$x_end=$pdf->w-$pdf->rMargin;
	
	$pdf->Ln(5);
	$str = iconv('UTF-8', 'windows-1251', "Всего мест" );
	$pdf->Cell(30,$line_height, $str ,0,0,'R',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY()+$line_height, $x_end, $pdf->GetY()+$line_height); 
	$pdf->Ln();
	$pdf->Ln();
	
	$str = iconv('UTF-8', 'windows-1251', "Приложения (паспорта, сертификаты) на" );
	$pdf->Cell(60,$line_height, $str ,0,0,'R',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY()+$line_height, $x_end-10, $pdf->GetY()+$line_height); 
	$str = iconv('UTF-8', 'windows-1251', "листах" );
	$pdf->Cell(0,$line_height, $str ,0,1,'R',0);
	
	//$pdf->SetFont('','',9);
	$str = iconv('UTF-8', 'windows-1251', "Всего отпущено $cnt_p наименований на сумму $sum_p" );
	$pdf->MultiCell(0,$line_height, $str ,0,'L',0);
	
	$s=array(30,30,5,30,5,0);
	$line_m_height=3;
	
	
	$pdf->SetFont('','',8);
	$str = iconv('UTF-8', 'windows-1251', "Отпуск разрешил" );
	$pdf->Cell($s[0],$line_height, $str ,0,0,'L',0);
	$str = iconv('UTF-8', 'windows-1251', "Директор" );
	$pdf->Cell($s[1],$line_height, $str ,0,0,'L',0);	
	$str = iconv('UTF-8', 'windows-1251', $this->firm_vars['firm_director'] );
	$pdf->Cell($s[5],$line_height, $str ,0,1,'R',0);
	
	$pdf->SetFont('','',6);
	$pdf->Cell($s[0],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[1], $pdf->GetY());
	$str = iconv('UTF-8', 'windows-1251', "должность" );
	$pdf->Cell($s[1],$line_m_height, $str ,0,0,'C',0);	
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[3], $pdf->GetY());
	$str = iconv('UTF-8', 'windows-1251', "подпись" );
	$pdf->Cell($s[3],$line_m_height, $str ,0,0,'C',0);
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $x_end, $pdf->GetY());
	$str = iconv('UTF-8', 'windows-1251', "расшифровка подписи" );
	$pdf->Cell($s[5],$line_m_height, $str ,0,1,'C',0);
	
	
	$pdf->SetFont('','',8);
	$str = iconv('UTF-8', 'windows-1251', "Главный (старший) бухгалтер" );
	$pdf->Cell($s[0]+$s[1],$line_height, $str ,0,0,'L',0);
	$str = iconv('UTF-8', 'windows-1251', $this->firm_vars['firm_buhgalter'] );
	$pdf->Cell($s[5],$line_height, $str ,0,1,'R',0);
	
	$pdf->SetFont('','',6);
	$pdf->Cell($s[0]+$s[1],$line_m_height, '' ,0,0,'L',0);
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[3], $pdf->GetY());
	$str = iconv('UTF-8', 'windows-1251', "подпись" );
	$pdf->Cell($s[3],$line_m_height, $str ,0,0,'C',0);
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $x_end, $pdf->GetY());
	$str = iconv('UTF-8', 'windows-1251', "расшифровка подписи" );
	$pdf->Cell($s[5],$line_m_height, $str ,0,1,'C',0);
	
	$pdf->SetFont('','',8);
	$str = iconv('UTF-8', 'windows-1251', "Отпуск груза произвёл" );
	$pdf->Cell($s[0],$line_height, $str ,0,0,'L',0);
	$str = iconv('UTF-8', 'windows-1251', "Кладовщик" );
	$pdf->Cell($s[1],$line_height, $str ,0,0,'L',0);	
	$str = iconv('UTF-8', 'windows-1251', $this->firm_vars['firm_kladovshik'] );
	$pdf->Cell($s[5],$line_height, $str ,0,1,'R',0);
	
	$pdf->SetFont('','',6);
	$pdf->Cell($s[0],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[1], $pdf->GetY());
	$str = iconv('UTF-8', 'windows-1251', "должность" );
	$pdf->Cell($s[1],$line_m_height, $str ,0,0,'C',0);	
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[3], $pdf->GetY());
	$str = iconv('UTF-8', 'windows-1251', "подпись" );
	$pdf->Cell($s[3],$line_m_height, $str ,0,0,'C',0);
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $x_end, $pdf->GetY());
	$str = iconv('UTF-8', 'windows-1251', "расшифровка подписи" );
	$pdf->Cell($s[5],$line_m_height, $str ,0,1,'C',0);
	
	$pdf->Ln();
	$pdf->Ln();
	$pdf->SetFont('','',8);
	$str = iconv('UTF-8', 'windows-1251', "М.П." );
	$pdf->Cell($s[0],$line_height, $str ,0,0,'R',0);
	$str = iconv('UTF-8', 'windows-1251', "\"___\"" );
	$pdf->Cell($s[1],$line_height, $str ,0,0,'R',0);	
	$str = iconv('UTF-8', 'windows-1251', '20___ года' );
	$pdf->Cell($s[5],$line_height, $str ,0,1,'R',0);
	
	$pdf->Line($pdf->GetX()+$s[0]+$s[1]+$s[2], $pdf->GetY(), $pdf->GetX()+$s[0]+$s[1]+$s[2]+$s[3], $pdf->GetY());

	$pdf->Line($x_end+2, $y+15, $x_end+2, $pdf->GetY()+5);

	$pdf->rMargin=$old_rmargin;
	$pdf->lMargin=$x_end+5;
	$pdf->SetY($y+5);
	$pdf->SetX($pdf->lMargin);
	
	$x_end=$pdf->w-$pdf->rMargin;
	
	$str = iconv('UTF-8', 'windows-1251', "Масса груза (нетто):" );
	$pdf->Cell($s[0],$line_height, $str ,0,0,'L',0);
	$str='';
	//$str = iconv('UTF-8', 'windows-1251', $mass_p );
	$pdf->Cell($s[1]+$s[2]+$s[3]+$s[4],$line_height, $str ,0,0,'L',0);
	$pdf->SetLineWidth($bold_line);
	$pdf->Cell($s[5],$line_height, $summass ,1,1,'C',0);
	$pdf->SetLineWidth($st_line);
	
	$str = iconv('UTF-8', 'windows-1251', "Масса груза (брутто):" );
	$pdf->Cell($s[0],$line_height, $str ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[1]+$s[2]+$s[3]+$s[4], $pdf->GetY());
	$pdf->Cell($s[1]+$s[2]+$s[3]+$s[4],$line_height, '' ,0,0,'L',0);
	$pdf->SetLineWidth($bold_line);
	$pdf->Cell($s[5],$line_height, '' ,1,1,'C',0);
	$pdf->SetLineWidth($st_line);
	$pdf->Cell($s[0],$line_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[1]+$s[2]+$s[3]+$s[4], $pdf->GetY());
	$pdf->Ln();
	
	$str = iconv('UTF-8', 'windows-1251', "По доверенности №" );
	$pdf->Cell($s[0],$line_height, $str ,0,0,'L',0);
	$str = iconv('UTF-8', 'windows-1251', $this->dop_data['dov']." от ".$this->dop_data['dov_data'] );
	$pdf->Cell(0,$line_height, $str ,0,1,'L',0);
	
	$pdf->SetFont('','',6);
	$pdf->Cell($s[0],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $x_end, $pdf->GetY());
	$str = iconv('UTF-8', 'windows-1251', "кем, кому (организация, должность, фамилия и. о.)" );
	$pdf->Cell(0,$line_height, $str ,0,1,'C',0);
	
	$pdf->SetFont('','',8);
	$str = iconv('UTF-8', 'windows-1251', "Выданной" );
	$pdf->Cell($s[0],$line_height, $str ,0,0,'L',0);	
	$str = iconv('UTF-8', 'windows-1251', "$dov_agr $dov_agn" );
	$pdf->Cell(0,$line_height, $str ,0,1,'L',0);
	
	$pdf->SetFont('','',6);
	$pdf->Cell($s[0],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $x_end, $pdf->GetY());
	$str = iconv('UTF-8', 'windows-1251', "кем, кому (организация, должность, фамилия и. о.)" );
	$pdf->Cell(0,$line_height, $str ,0,1,'C',0);

	$pdf->SetFont('','',8);
	$str = iconv('UTF-8', 'windows-1251', "Груз принял" );
	$pdf->Cell($s[0],$line_height, $str ,0,1,'L',0);
	
	$pdf->SetFont('','',6);
	$pdf->Cell($s[0],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[1], $pdf->GetY());
	$str = iconv('UTF-8', 'windows-1251', "должность" );
	$pdf->Cell($s[1],$line_m_height, $str ,0,0,'C',0);	
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[3], $pdf->GetY());
	$str = iconv('UTF-8', 'windows-1251', "подпись" );
	$pdf->Cell($s[3],$line_m_height, $str ,0,0,'C',0);
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $x_end, $pdf->GetY());
	$str = iconv('UTF-8', 'windows-1251', "расшифровка подписи" );
	$pdf->Cell($s[5],$line_m_height, $str ,0,1,'C',0);

	$pdf->SetFont('','',8);
	$str = iconv('UTF-8', 'windows-1251', "Груз получил" );
	$pdf->Cell($s[0],$line_height, $str ,0,1,'L',0);
	
	$str = iconv('UTF-8', 'windows-1251', "грузополучатель" );
	$pdf->Cell($s[0],$line_m_height, $str ,0,0,'L',0);
	$pdf->SetFont('','',6);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[1], $pdf->GetY());
	$str = iconv('UTF-8', 'windows-1251', "должность" );
	$pdf->Cell($s[1],$line_m_height, $str ,0,0,'C',0);	
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[3], $pdf->GetY());
	$str = iconv('UTF-8', 'windows-1251', "подпись" );
	$pdf->Cell($s[3],$line_m_height, $str ,0,0,'C',0);
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $x_end, $pdf->GetY());
	$str = iconv('UTF-8', 'windows-1251', "расшифровка подписи" );
	$pdf->Cell($s[5],$line_m_height, $str ,0,1,'C',0);
	
	$pdf->Ln();
	$pdf->Ln();
	$pdf->SetFont('','',8);
	$str = iconv('UTF-8', 'windows-1251', "М.П." );
	$pdf->Cell($s[0],$line_height, $str ,0,0,'R',0);
	$str = iconv('UTF-8', 'windows-1251', "\"___\"" );
	$pdf->Cell($s[1],$line_height, $str ,0,0,'R',0);	
	$str = iconv('UTF-8', 'windows-1251', '20___ года' );
	$pdf->Cell($s[5],$line_height, $str ,0,1,'R',0);

	if($to_str)
		return $pdf->Output('torg12.pdf','S');
	else
		$pdf->Output('torg12.pdf','I');
}

function SfakPDF($doc, $to_str=0)
{
	global $CONFIG;
	define('FPDF_FONT_PATH',$CONFIG['site']['location'].'/fpdf/font/');
	require('fpdf/fpdf_mysql.php');
	global $tmpl, $uid;
	
	if($coeff==0) $coeff=1;
	if(!$to_str) $tmpl->ajax=1;
	
	$dt=date("d.m.Y",$this->doc_data[5]);

	$res=mysql_query("SELECT `doc_agent`.`name`, `doc_agent`.`fullname`, `doc_agent`.`adres`,  `doc_agent`.`tel`, `doc_agent`.`inn`, `doc_agent`.`okpo`, `doc_agent`.`okevd`, `doc_agent`.`bik`, `doc_agent`.`rs`, `doc_agent`.`ks`, `doc_agent`.`bank`
	FROM `doc_agent` WHERE `doc_agent`.`id`='{$this->dop_data['gruzop']}'	");
	if(mysql_errno())		throw new MysqlException("Невозможно получить данные грузополучателя!");	
	$gruzop_info=mysql_fetch_array($res);
	if(!$gruzop_info)		$gruzop_info=array();
	$gruzop='';
	if($gruzop_info['fullname'])	$gruzop.=$gruzop_info['fullname'];
	else				$gruzop.=$gruzop_info['name'];
	if($gruzop_info['adres'])	$gruzop.=', адрес '.$gruzop_info['adres'];
	if($gruzop_info['tel'])		$gruzop.=', тел. '.$gruzop_info['tel'];
	if($gruzop_info['inn'])		$gruzop.=', ИНН/КПП '.$gruzop_info['inn'];
	if($gruzop_info['okevd'])	$gruzop.=', ОКВЭД '.$gruzop_info['okevd'];
	if($gruzop_info['rs'])		$gruzop.=', Р/С '.$gruzop_info['rs'];
	if($gruzop_info['bank'])	$gruzop.=', в банке '.$gruzop_info['bank'];
	if($gruzop_info['bik'])		$gruzop.=', БИК '.$gruzop_info['bik'];
	if($gruzop_info['ks'])		$gruzop.=', К/С '.$gruzop_info['ks'];

	$res=mysql_query("SELECT `doc_agent`.`id`, `doc_agent`.`fullname`, `doc_agent`.`adres`,  `doc_agent`.`tel`, `doc_agent`.`inn` FROM `doc_agent` WHERE `doc_agent`.`id`='{$this->doc_data[2]}'	");

	$nx=@mysql_fetch_row($res);	
	if($this->doc_data[13])
	{
		$rs=@mysql_query("SELECT `id`, `altnum`, `date` FROM `doc_list` WHERE 
		(`p_doc`='{$this->doc}' AND (`type`='4' OR `type`='6')) OR
		(`p_doc`='{$this->doc_data[13]}' AND (`type`='4' OR `type`='6'))
		AND `ok`>'0' AND `p_doc`!='0' GROUP BY `p_doc`");
		$pp=@mysql_result($rs,0,1);
		$ppdt=@date("d.m.Y",mysql_result($rs,0,2));
		if(!$pp) $pp=@mysql_result($rs,0,0);
	}
	if(!$pp) $pp=$ppdt="__________";	
	
	$pdf=new FPDF('P');
	$pdf->Open();
	$pdf->SetAutoPageBreak(1,12);
	$pdf->AddFont('Arial','','arial.php');
	$pdf->tMargin=5;
	$pdf->AddPage('L');
	$pdf->SetFillColor(255);

	$pdf->Setx(150);
	$pdf->SetFont('Arial','',7);
	$str = 'Приложение №1 к Правилам ведения журналов учета полученных и выставленных счетов-фактур, книг покупок и книг продаж при расчетах по налогу на добавленную стоимость, утвержденным постановлением Правительства Российской Федерации от 2 декабря 2000 г. N 914 (в редакции постановлений Правительства Российской Федерации от 15 марта 2001 г. N 189, от 27 июля 2002 г. N 575, от 16 февраля 2004 г. N 84, от 11 мая 2006г. N 283, от 26 мая 2009г. N451, от 27 июля 2010г. N 229)';
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->MultiCell(0,4,$str,0,'R');
	$pdf->Ln();
	$pdf->SetFont('','',16);
	$step=4;
	$str = iconv('UTF-8', 'windows-1251', "Счёт - фактура N {$this->doc_data[9]}, от $dt");
	$pdf->Cell(0,8,$str,0,1,'L');
	$pdf->SetFont('Arial','',10);
	$str = iconv('UTF-8', 'windows-1251', "Продавец: ".unhtmlentities($this->firm_vars['firm_name']));
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "Адрес: ".$this->firm_vars['firm_adres']);
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "ИНН / КПП продавца: ".$this->firm_vars['firm_inn']);
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "Грузоотправитель и его адрес: ".unhtmlentities($this->firm_vars['firm_gruzootpr']));
	$pdf->MultiCell(0,$step,$str,0,'L');
	$str = iconv('UTF-8', 'windows-1251', "Грузополучатель и его адрес: ".unhtmlentities($gruzop));
	$pdf->MultiCell(0,$step,$str,0,'L');
	$str = iconv('UTF-8', 'windows-1251', "К платёжно-расчётному документу № $pp, от $ppdt");
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "Покупатель: ".unhtmlentities($nx[1]));
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "Адрес: ".unhtmlentities($nx[2]).", тел. $nx[3]");
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "ИНН / КПП покупателя: $nx[4]");
	$pdf->Cell(0,$step,$str,0,1,'L');
	
	$str = "";
	
	$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`date`
	FROM `doc_list`
	WHERE `doc_list`.`agent`='{$this->doc_data[2]}' AND `doc_list`.`type`='14' AND `doc_list`.`ok`>'0'
	ORDER BY  `doc_list`.`date` DESC");
	if(mysql_errno())		throw new MysqlException("Невозможно получить данные договора!");
	
	if($nxt=mysql_fetch_row($res))
	{
		$str.="Договор N$nxt[1] от ".date("d.m.Y",$nxt[2]).", ";	
	}
	
	if($this->doc_data['p_doc'])
	{
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`date`, `doc_list`.`p_doc`, `doc_list`.`type` FROM `doc_list`
		WHERE `id`={$this->doc_data['p_doc']}");
		$nxt=mysql_fetch_row($res);
		if($nxt)
		{
			if($nxt[4]==1)		$str.="Счёт N$nxt[1] от ".date("d.m.Y",$nxt[2]).", ";
			else if($nxt[4]==16)	$str.="Спецификация N$nxt[1] от ".date("d.m.Y",$nxt[2]).", ";
			if($nxt[3])
			{
				$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`date`, `doc_list`.`p_doc` FROM `doc_list`
				WHERE `id`={$nxt[3]} AND `doc_list`.`type`='16'");
				$nxt=mysql_fetch_row($res);
				if($nxt)	$str.="Спецификация N$nxt[1] от ".date("d.m.Y",$nxt[2]).", ";	
			}
		}
	}
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "Наименование валюты: руб.");
	$pdf->Cell(0,$step,$str,0,1,'R');
	
	$pdf->Ln(3);
	
	$y=$pdf->GetY();
	$pdf->SetLineWidth(0.5);
	$t_width=array(84,17,10,20,28,10,17,18,28,15,0);
	$t_ydelta=array(5,5,5,3,0,3,5,5,0,3,3);
	$t_text=array(
	'Наименование товара (описание выполненных работ, оказанных услуг, имущественного права)',
	'Единица измерения',
	'Количество',
	'Цена (тариф) за единицу измерения',
	'Стоимость товаров (работ, услуг), имущественных прав, всего без налога',
	'В том числе акциз',
	'Налоговая ставка',
	'Сумма налога',
	'Стоимость товаров (работ, услуг, имущественных прав), всего с учетом налога',
	'Страна происхождения',
	'Номер таможенной декларации');
	foreach($t_width as $w)
	{
		$pdf->Cell($w,16,'',1,0,'C',0);
	}
	$pdf->Ln();
	$pdf->Ln(0.5);
	$pdf->SetFont('','',8);
	$offset=0;
	foreach($t_width as $i => $w)
	{
		$pdf->SetY($y+$t_ydelta[$i]+0.2);
		$pdf->SetX($offset+$pdf->lMargin);
		$str = iconv('UTF-8', 'windows-1251', $t_text[$i] );	
		$pdf->MultiCell($w,3,$str,0,'C',0);
		$offset+=$w;
	}
	
	$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_list_pos`.`sn`, `doc_base_dop`.`strana`, `doc_base_dop`.`ntd`, `doc_units`.`printname`
	FROM `doc_list_pos`
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
	LEFT JOIN `doc_units` ON `doc_base`.`unit`=`doc_units`.`id`
	WHERE `doc_list_pos`.`doc`='{$this->doc}'");
	
	$pdf->SetLineWidth(0.2);
	$pdf->SetY($y+16);
	
	$i=0;
	$ii=1;
	$sum=$sumnaloga=0;
	$nds=$this->firm_vars['param_nds']/100;
	$ndsp=$this->firm_vars['param_nds'];
	while($nxt=mysql_fetch_row($res))
	{
		if($this->doc_data[12])
		{
			$cena = $nxt[4]/(1+$nds);
			$stoimost = $cena*$nxt[3];
			$nalog = ($nxt[4]*$nxt[3])-$stoimost;
			$snalogom = $nxt[4]*$nxt[3];
		}
		else
		{
			$cena = $nxt[4];
			$stoimost = $cena*$nxt[3];
			$nalog = $stoimost*$nds;
			$snalogom = $stoimost+$nalog;
		}
	
		$i=1-$i;
		$ii++;
	
		$cena =		sprintf("%01.2f", $cena);
		$stoimost =	sprintf("%01.2f", $stoimost);
		$nalog = 	sprintf("%01.2f", $nalog);
		$snalogom =	sprintf("%01.2f", $snalogom);
	
		$sum+=$snalogom;
		$sumnaloga+=$nalog;
	
		$y=$pdf->GetY();
		$step=5;
		$pdf->SetFont('','',9);
		$str = iconv('UTF-8', 'windows-1251', "$nxt[0] $nxt[1] / $nxt[2]" );
		$pdf->Cell($t_width[0],$step,$str,1,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', $nxt[8] );
		$pdf->Cell($t_width[1],$step,$str,1,0,'R',0);
		$str = iconv('UTF-8', 'windows-1251', $nxt[3] );
		$pdf->Cell($t_width[2],$step,$str,1,0,'R',0);		
		$pdf->Cell($t_width[3],$step,$cena,1,0,'R',0);
		$pdf->Cell($t_width[4],$step,$stoimost,1,0,'R',0);
		$pdf->Cell($t_width[5],$step,'--',1,0,'C',0);		
		$pdf->Cell($t_width[6],$step,"$ndsp%",1,0,'R',0);
		$pdf->Cell($t_width[7],$step,$nalog,1,0,'R',0);						
		$pdf->Cell($t_width[8],$step,$snalogom,1,0,'R',0);
		$str = iconv('UTF-8', 'windows-1251', $nxt[6] );
		$pdf->SetFont('','',6);
		$pdf->Cell($t_width[9],$step,$str,1,0,'R',0);
		$pdf->Cell($t_width[10],$step,$nxt[7],1,0,'R',0);
		$pdf->Ln();
	}
	
	if($pdf->h<=($pdf->GetY()+60)) $pdf->AddPage('L');		
	$delta=$pdf->h-($pdf->GetY()+55);
	if($delta>7) $delta=7;		

	$sum = sprintf("%01.2f", $sum);
	$sumnaloga = sprintf("%01.2f", $sumnaloga);
	$step=5.5;
	$pdf->SetFont('','',12);
	$pdf->SetLineWidth(0.3);
	$str = iconv('UTF-8', 'windows-1251', "Всего к оплате:" );
	$pdf->Cell($t_width[0]+$t_width[1]+$t_width[2]+$t_width[3],$step,$str,1,0,'L',0);

	$pdf->Cell($t_width[4],$step,'',1,0,'R',0);
	$pdf->Cell($t_width[5],$step,'',1,0,'C',0);		
	$pdf->Cell($t_width[6],$step,'',1,0,'R',0);
	$pdf->Cell($t_width[7],$step,$sumnaloga,1,0,'R',0);						
	$pdf->Cell($t_width[8],$step,$sum,1,0,'R',0);
	$pdf->Cell($t_width[9],$step,'',1,0,'R',0);
	$pdf->Cell($t_width[10],$step,'',1,0,'R',0);
	$pdf->Ln(10);
	
	$pdf->SetFont('','',11);
	$str = iconv('UTF-8', 'windows-1251', "Руководитель организации:______________________ /".$this->firm_vars['firm_director']."/");
	$pdf->Cell(100,$step,$str,0,0,'L',0);
	$str = iconv('UTF-8', 'windows-1251', "Главный бухгалтер: _____________________ /".$this->firm_vars['firm_buhgalter']."/");
	$pdf->Cell(0,$step,$str,0,0,'R',0);
	
	
	$pdf->Ln(10);
	$pdf->SetFont('','',7);
	$str = iconv('UTF-8', 'windows-1251', "ПРИМЕЧАНИЕ. Первый экземпляр (оригинал) - покупателю, второй экземпляр (копия) - продавцу" );
	$pdf->Cell(0,$step,$str,0,0,'R',0);
	
	$pdf->Ln();
	
	if($to_str)	return $pdf->Output('s_faktura.pdf','S');
	else		$pdf->Output('s_faktura.pdf','I');
}

};
?>