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
		$this->header_fields			='sklad cena separator agent';
		$this->dop_menu_buttons			="<a href='' onclick=\"ShowPopupWin('/doc.php?mode=srv&amp;opt=dov&amp;doc=$doc'); return false;\" title='Доверенное лицо'><img src='img/i_users.png' alt='users'></a>";
		settype($this->doc,'int');
		$this->PDFForms=array(
			array('name'=>'nak','desc'=>'Накладная','method'=>'PrintNaklPDF'),
			array('name'=>'tc','desc'=>'Товарный чек','method'=>'PrintTcPDF'),
			array('name'=>'tg12','desc'=>'Накладная ТОРГ-12','method'=>'PrintTg12PDF'),
			array('name'=>'nak_kompl','desc'=>'Накладная на комплектацию','method'=>'PrintNaklKomplektPDF'),
			array('name'=>'sfak','desc'=>'Счёт - фактура','method'=>'SfakPDF'),
			array('name'=>'sfak2010','desc'=>'Счёт - фактура 2010','method'=>'Sfak2010PDF')
		);
	}

	// Создать документ с товарными остатками на основе другого документа
	public function CreateFromP($doc_obj)
	{
		parent::CreateFromP($doc_obj);
		$this->SetDopData('platelshik', $doc_obj->doc_data['agent']);
		$this->SetDopData('gruzop', $doc_obj->doc_data['agent']);
		unset($this->doc_data);
		$this->get_docdata();
		return $this->doc;
	}

	function DopHead()
	{
		global $tmpl;

		$cur_agent=$this->doc_data['agent'];
		if(!$cur_agent)		$cur_agent=1;
		$klad_id=@$this->dop_data['kladovshik'];
		if(!$klad_id)	$klad_id=$this->firm_vars['firm_kladovshik_id'];

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
		<input type='text' id='plat'  style='width: 100%;' value='$plat_name'><br>
		Грузополучатель:<br>
		<input type='hidden' name='gruzop_id' id='gruzop_id' value='{$this->dop_data['gruzop']}'>
		<input type='text' id='gruzop'  style='width: 100%;' value='$gruzop_name'><br>
		Кладовщик:<br><select name='kladovshik'>");
		$res=mysql_query("SELECT `id`, `name`, `rname` FROM `users` WHERE `worker`='1' ORDER BY `name`");
		if(mysql_errno())	throw new MysqlException("Не удалось получить имя кладовщика");
		$tmpl->AddText("<option value='0'>--не выбран--</option>");
		while($nxt=mysql_fetch_row($res))
		{
			$s=($klad_id==$nxt[0])?'selected':'';
			$tmpl->AddText("<option value='$nxt[0]' $s>$nxt[1] ($nxt[2])</option>");
		}
		$tmpl->AddText("</select><br>
		Количество мест:<br>
		<input type='text' name='mest' value='{$this->dop_data['mest']}'><br>
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
		$checked=@$this->dop_data['received']?'checked':'';
		$tmpl->AddText("<label><input type='checkbox' name='received' value='1' $checked>Документы подписаны и получены</label><br>");
		$checked=@$this->dop_data['return']?'checked':'';
		$tmpl->AddText("<label><input type='checkbox' name='return' value='1' $checked>Возвратный документ</label><br>");
	}

	function DopSave()
	{
		$plat_id=rcv('plat_id');
		$gruzop_id=rcv('gruzop_id');
		$received=rcv('received');
		$return=rcv('return');
		$kladovshik=rcv('kladovshik');
		$mest=rcv('mest');
		settype($kladovshik, 'int');

		$doc=$this->doc;
		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
		VALUES ( '{$this->doc}' ,'platelshik','$plat_id'),
		( '{$this->doc}' ,'gruzop','$gruzop_id'),
		( '{$this->doc}' ,'received','$received'),
		( '{$this->doc}' ,'return','$return'),
		( '{$this->doc}' ,'kladovshik','$kladovshik'),
		( '{$this->doc}' ,'mest','$mest')");
	}

	function DopBody()
	{
		global $tmpl;
		if(isset($this->dop_data['received']))
			if($this->dop_data['received'])
				$tmpl->AddText("<br><b>Документы подписаны и получены</b><br>");
	}

	function DocApply($silent=0)
	{
		global $CONFIG;
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
		FROM `doc_list`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list`.`id`='{$this->doc}'");
		if( !($nx=@mysql_fetch_assoc($res) ) )	throw new MysqlException('Ошибка выборки данных документа при проведении!');
		if( $nx['ok'] && ( !$silent) )		throw new Exception('Документ уже был проведён!');
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if( !$res )				throw new MysqlException('Ошибка проведения, ошибка установки даты проведения!');
		if(!@$this->dop_data['kladovshik'] && @$CONFIG['doc']['require_storekeeper'] && !$silent)	throw new Exception("Кладовщик не выбран!");
		if(!@$this->dop_data['mest'] && @$CONFIG['doc']['require_pack_count'] && !$silent)	throw new Exception("Количество мест не задано");
		$res=mysql_query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`pos_type`, `doc_list_pos`.`id`, `doc_base`.`vc`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$nx['sklad']}'
		WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_base`.`pos_type`='0'");
		while($nxt=mysql_fetch_row($res))
		{
			if(!$nx['dnc'])
			{
				if($nxt[1]>$nxt[2])	throw new Exception("Недостаточно ($nxt[1]) товара '$nxt[3]:$nxt[4] - $nxt[7]($nxt[0])': на складе только $nxt[2] шт!");
			}
			mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='{$nx['sklad']}'");
			if(mysql_error())	throw new MysqlException('Ошибка проведения, ошибка изменения количества!');

			if(!$nx['dnc'] && (!$silent))
			{
				$budet=getStoreCntOnDate($nxt[0], $nx['sklad']);
				if( $budet<0)		throw new Exception("Невозможно ($silent), т.к. будет недостаточно ($budet) товара '$nxt[3]:$nxt[4] - $nxt[7]($nxt[0])'!");
			}

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
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=nak_pdf'\">Накладная PDF</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=tc'\">Товарный чек</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=tc_pdf'\">Товарный чек PDF</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=nak_kompl_pdf'\">Накладная на комплектацию PDF</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=kop'\">Копия чека</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=kop_np'\">Копия чека (без покупателя)</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=nac'\">Наценки</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=tg12'\">Накладная ТОРГ-12 (УСТАРЕЛО)</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=tg12_pdf'\">Накладная ТОРГ-12 (PDF)</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=sf_pdf'\">Счёт - фактура (PDF)</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=sf2010_pdf'\">Счёт - фактура 2010 (PDF)</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=nvco'\">Накладная c сорт. по коду</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=arab'\">Акт оказаннх услуг</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=warr'\">Гарантийный талон</div>");
		}
		//			<li><a href='?mode=print&amp;doc=$doc&amp;opt=sf'>Счёт - фактура (HTML)</a></li>
		else if($opt=='tg12')
			$this->PrintTg12($doc);
		else if($opt=='tg12_pdf')
			$this->PrintTg12PDF();
		else if($opt=='nac')
			$this->Nacenki();
		else if($opt=='sf')
			$this->PrintSfak($doc);
		else if($opt=='sf_pdf')
			$this->SfakPDF();
		else if($opt=='sf2010_pdf')
			$this->Sfak2010PDF();
		else if($opt=='kop')
			$this->PrintKopia($doc);
		else if($opt=='kop_np')
			$this->PrintKopiaNoPok($doc);
		else if($opt=='tc')
			$this->PrintTovCheck($doc);
		else if($opt=='tc_pdf')
			$this->PrintTcPDF();
		else if($opt=='nvco')
			$this->PrintNaklVCOrdered();
		else if($opt=='arab')
			$this->PrintActRabot();
		else if($opt=='warr')
			$this->PrintWarantyList();
		else if($opt=='nak_pdf')
			$this->PrintNaklPDF();
		else if($opt=='nak_kompl_pdf')
			$this->PrintNaklKomplektPDF();
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
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc=$doc&amp;tt=4'\">Приход средств в банк</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc=$doc&amp;tt=18'\">Корректировка долга</div>");
		}
		else if($target_type==6)
		{
			if(!isAccess('doc_pko','create'))	throw new AccessException("");
			$sum=DocSumUpdate($this->doc);
			mysql_query("START TRANSACTION");
			$tm=time();
			$altnum=GetNextAltNum($target_type ,$this->doc_data['subtype'],0,date("Y-m-d",$this->doc_data['date']), $this->doc_data['firm_id']);
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
			if(!isAccess('doc_pbank','create'))	throw new AccessException("");
			$sum=DocSumUpdate($this->doc);
			mysql_query("START TRANSACTION");
			$tm=time();
			$altnum=GetNextAltNum($target_type ,$this->doc_data['subtype'],0,date("Y-m-d",$this->doc_data['date']), $this->doc_data['firm_id']);
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
		else if($target_type==18)
		{
			$new_doc=new doc_Kordolga();
			$dd=$new_doc->CreateFrom($this);
			$new_doc->SetDocData('sum', $this->doc_data['sum']*(-1));
			header("Location: doc.php?mode=body&doc=$dd");
		}
		else
		{
			$tmpl->msg("В разработке","info");
		}
	}

	function Service($doc)
	{
		get_docdata($doc);
		global $tmpl;

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
		$tmpl->LoadTemplate('print');
		$dt=date("d.m.Y",$this->doc_data[5]);

		$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='1'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить цену по умолчанию");
		$def_cost=mysql_result($res,0,0);
		if(!$def_cost)		throw new Exception("Цена по умолчанию не определена!");

		$tmpl->AddText("<h1>Накладная N {$this->doc_data[9]}{$this->doc_data[10]}, от $dt </h1>
		<b>Поставщик: </b>{$this->firm_vars['firm_name']}<br>
		<b>Покупатель: </b>{$this->doc_data[3]}<br><br>");

		$tmpl->AddText("
		<table width='800' cellspacing='0' cellpadding='0'>
		<tr><th>№</th><th width=450>Наименование<th>Место<th>Кол-во<th>Стоимость<th>Сумма</tr>");
		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`mesto`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`id`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data[7]}'
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$ii=1;
		$sum=0;
		$skid_sum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f руб.", $nxt[4]);
			$cost2 = sprintf("%01.2f руб.", $sm);
			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];
			$tmpl->AddText("<tr align=right><td>$ii</td><td align=left>$nxt[0] $nxt[1]<td>$nxt[5]<td>$nxt[3] $nxt[6]<td>$cost<td>$cost2");
			$i=1-$i;
			$ii++;
			$sum+=$sm;
			$skid_sum+=GetCostPos($nxt[7], $def_cost)*$nxt[3];
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
		<p>Всего <b>$ii</b> наименований на сумму <b>$cost</b>");
		if($this->dop_data['mest'])	$tmpl->AddText(", мест: ".$this->dop_data['mest']);
		$tmpl->AddText("</p>");
		if($sum!=$skid_sum)
		{
			$cost = sprintf("%01.2f руб.", $skid_sum-$sum);
			$tmpl->AddText("<p>Скидка: <b>$cost</b></p>");
		}
		$tmpl->AddText("<p class=mini>Товар получил, претензий к качеству товара и внешнему виду не имею.</p>
		$prop
		<p>Поставщик:_____________________________________</p>
		<p>Покупатель: ____________________________________</p>");
	}

/// Обычная накладная в PDF формате
/// @param to_str Вернуть строку, содержащую данные документа (в противном случае - отправить файлом)
	function PrintNaklPDF($to_str=false)
	{
		define('FPDF_FONT_PATH','/var/www/gate/fpdf/font/');
		require('fpdf/fpdf_mc.php');
		global $tmpl, $CONFIG, $uid;

		if(!$to_str) $tmpl->ajax=1;

		$pdf=new PDF_MC_Table('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(0,10);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=10;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		$dt=date("d.m.Y",$this->doc_data[5]);

		$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='1'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить цену по умолчанию");
		$def_cost=mysql_result($res,0,0);
		if(!$def_cost)		throw new Exception("Цена по умолчанию не определена!");

		$pdf->SetFont('','',16);
		$str="Накладная N {$this->doc_data[9]}{$this->doc_data[10]}, от $dt";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,8,$str,0,1,'C',0);
		$pdf->SetFont('','',10);
		$str="Поставщик: {$this->firm_vars['firm_name']}, тел: {$this->firm_vars['firm_telefon']}";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="Покупатель: {$this->doc_data[3]}";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$pdf->Ln();

		$pdf->SetLineWidth(0.5);
		$t_width=array(8);
		if($CONFIG['poseditor']['vc'])
		{
			$t_width[]=20;
			$t_width[]=91;
		}
		else	$t_width[]=111;
		$t_width=array_merge($t_width, array(12,15,23,23));

		$t_text=array('№');
		if($CONFIG['poseditor']['vc'])
		{
			$t_text[]='Код';
			$t_text[]='Наименование';
		}
		else	$t_text[]='Наименование';
		$t_text=array_merge($t_text, array('Место', 'Кол-во', 'Стоимость', 'Сумма'));

		foreach($t_width as $id=>$w)
		{
			$str = iconv('UTF-8', 'windows-1251', $t_text[$id]);
			$pdf->Cell($w,6,$str,1,0,'C',0);
		}
		$pdf->Ln();
		$pdf->SetWidths($t_width);
		$pdf->SetHeight(3.8);

		$aligns=array('R');
		if($CONFIG['poseditor']['vc'])
		{
			$aligns[]='L';
			$aligns[]='L';
		}
		else	$aligns[]='L';
		$aligns=array_merge($aligns, array('C','R','R','R'));

		$pdf->SetAligns($aligns);
		$pdf->SetLineWidth(0.2);
		$pdf->SetFont('','',8);

		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`mesto`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`id`, `doc_base`.`vc`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data[7]}'
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$ii=1;
		$sum=0;
		$skid_sum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f руб.", $nxt[4]);
			$cost2 = sprintf("%01.2f руб.", $sm);
			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];

			$row=array($ii);
			if($CONFIG['poseditor']['vc'])
			{
				$row[]=$nxt[8];
				$row[]="$nxt[0] $nxt[1]";
			}
			else	$row[]="$nxt[0] $nxt[1]";
			$row=array_merge($row, array($nxt[5], "$nxt[3] $nxt[6]", $cost, $cost2));

			$pdf->RowIconv($row);
			$i=1-$i;
			$ii++;
			$sum+=$sm;
			$skid_sum+=GetCostPos($nxt[7], $def_cost)*$nxt[3];
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
				$prop=sprintf("Оплачено: %0.2f руб.",$prop);
			}
		}
		$pdf->Ln();

		$str="Всего $ii наименований на сумму $cost";
		if($this->dop_data['mest'])	$str.=", мест: ".$this->dop_data['mest'];
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);

		if($sum!=$skid_sum)
		{
			$cost = sprintf("%01.2f руб.", $skid_sum-$sum);
			$str="Скидка: $cost";
			$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
			$pdf->Cell(0,5,$str,0,1,'L',0);
		}

		if($prop)
		{
			$str = iconv('UTF-8', 'windows-1251', unhtmlentities($prop));
			$pdf->Cell(0,5,$str,0,1,'L',0);
		}

		$str="Товар получил, претензий к качеству товара и внешнему виду не имею.";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="Покупатель: ____________________________________";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="Поставщик:_____________________________________";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);

		if($to_str)
			return $pdf->Output('blading.pdf','S');
		else
			$pdf->Output('blading.pdf','I');
	}

/// Товарный чек в PDF формате
/// @param to_str Вернуть строку, содержащую данные документа (в противном случае - отправить файлом)
	function PrintTcPDF($to_str=false)
	{
		define('FPDF_FONT_PATH','/var/www/gate/fpdf/font/');
		require('fpdf/fpdf_mc.php');
		global $tmpl, $CONFIG, $uid;

		if(!$to_str) $tmpl->ajax=1;

		$pdf=new PDF_MC_Table('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(0,10);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=10;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		$dt=date("d.m.Y",$this->doc_data[5]);

		$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='1'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить цену по умолчанию");
		$def_cost=mysql_result($res,0,0);
		if(!$def_cost)		throw new Exception("Цена по умолчанию не определена!");

		$pdf->SetFont('','',16);
		$str="Товарный чек N {$this->doc_data[9]}, от $dt";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,8,$str,0,1,'C',0);
		$pdf->SetFont('','',10);
		$str="Продавец: {$this->firm_vars['firm_name']}, ИНН-{$this->firm_vars['firm_inn']}-КПП, тел: {$this->firm_vars['firm_telefon']}";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="Покупатель: {$this->doc_data[3]}";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$pdf->Ln();

		$pdf->SetLineWidth(0.5);
		$t_width=array(8);
		if($CONFIG['poseditor']['vc'])
		{
			$t_width[]=20;
			$t_width[]=91;
		}
		else	$t_width[]=111;
		$t_width=array_merge($t_width, array(12,15,23,23));

		$t_text=array('№');
		if($CONFIG['poseditor']['vc'])
		{
			$t_text[]='Код';
			$t_text[]='Наименование';
		}
		else	$t_text[]='Наименование';
		$t_text=array_merge($t_text, array('Место', 'Кол-во', 'Стоимость', 'Сумма'));

		foreach($t_width as $id=>$w)
		{
			$str = iconv('UTF-8', 'windows-1251', $t_text[$id]);
			$pdf->Cell($w,6,$str,1,0,'C',0);
		}
		$pdf->Ln();
		$pdf->SetWidths($t_width);
		$pdf->SetHeight(3.8);

		$aligns=array('R');
		if($CONFIG['poseditor']['vc'])
		{
			$aligns[]='L';
			$aligns[]='L';
		}
		else	$aligns[]='L';
		$aligns=array_merge($aligns, array('C','R','R','R'));

		$pdf->SetAligns($aligns);
		$pdf->SetLineWidth(0.2);
		$pdf->SetFont('','',8);

		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`mesto`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`id`, `doc_base`.`vc`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data[7]}'
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$ii=1;
		$sum=0;
		$skid_sum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f руб.", $nxt[4]);
			$cost2 = sprintf("%01.2f руб.", $sm);
			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];

			$row=array($ii);
			if($CONFIG['poseditor']['vc'])
			{
				$row[]=$nxt[8];
				$row[]="$nxt[0] $nxt[1]";
			}
			else	$row[]="$nxt[0] $nxt[1]";
			$row=array_merge($row, array($nxt[5], "$nxt[3] $nxt[6]", $cost, $cost2));

			$pdf->RowIconv($row);
			$i=1-$i;
			$ii++;
			$sum+=$sm;
			$skid_sum+=GetCostPos($nxt[7], $def_cost)*$nxt[3];
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
				$prop=sprintf("Оплачено: %0.2f руб.",$prop);
			}
		}
		$pdf->Ln();

		$str="Всего $ii наименований на сумму $cost";
		if($this->dop_data['mest'])	$str.=", мест: ".$this->dop_data['mest'];
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);

		if($sum!=$skid_sum)
		{
			$cost = sprintf("%01.2f руб.", $skid_sum-$sum);
			$str="Скидка: $cost";
			$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
			$pdf->Cell(0,5,$str,0,1,'L',0);
		}

		if($prop)
		{
			$str = iconv('UTF-8', 'windows-1251', unhtmlentities($prop));
			$pdf->Cell(0,5,$str,0,1,'L',0);
		}


		$str="Продавец:_____________________________________";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);

		if($to_str)
			return $pdf->Output('tc.pdf','S');
		else
			$pdf->Output('tc.pdf','I');
	}

/// Накладная на комплектацию в PDF формате
/// @param to_str Вернуть строку, содержащую данные документа (в противном случае - отправить файлом)
	function PrintNaklKomplektPDF($to_str=false)
	{
		define('FPDF_FONT_PATH','/var/www/gate/fpdf/font/');
		require('fpdf/fpdf_mc.php');
		global $tmpl, $CONFIG, $uid;

		if(!$to_str) $tmpl->ajax=1;

		$pdf=new PDF_MC_Table('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(0,10);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=10;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		$dt=date("d.m.Y",$this->doc_data[5]);

		$res=mysql_query("SELECT `id` FROM `doc_cost` WHERE `vid`='1'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить цену по умолчанию");
		$def_cost=mysql_result($res,0,0);
		if(!$def_cost)		throw new Exception("Цена по умолчанию не определена!");

		$pdf->SetFont('','',16);
		$str="Накладная на комплектацию N {$this->doc_data[9]}{$this->doc_data[10]}, от $dt";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,8,$str,0,1,'C',0);
		$pdf->SetFont('','',10);
		$str="К накладной N {$this->doc_data[9]}{$this->doc_data[10]} ({$this->doc})";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="Поставщик: {$this->firm_vars['firm_name']}";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="Покупатель: {$this->doc_data[3]}";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$pdf->Ln();

		$pdf->SetLineWidth(0.5);
		$t_width=array(8);
		if($CONFIG['poseditor']['vc'])
		{
			$t_width[]=20;
			$t_width[]=76;
		}
		else	$t_width[]=96;
		$t_width=array_merge($t_width, array(17,17,15,13,14,12));

		$t_text=array('№');
		if($CONFIG['poseditor']['vc'])
		{
			$t_text[]='Код';
			$t_text[]='Наименование';
		}
		else	$t_text[]='Наименование';
		$t_text=array_merge($t_text, array('Цена', 'Кол-во', 'Остаток', 'Резерв', 'Масса', 'Место'));

		foreach($t_width as $id=>$w)
		{
			$str = iconv('UTF-8', 'windows-1251', $t_text[$id]);
			$pdf->Cell($w,6,$str,1,0,'C',0);
		}
		$pdf->Ln();
		$pdf->SetWidths($t_width);
		$pdf->SetHeight(4);

		$aligns=array('R');
		if($CONFIG['poseditor']['vc'])
		{
			$aligns[]='R';
			$aligns[]='L';
		}
		else	$aligns[]='L';
		$aligns=array_merge($aligns, array('R','R','R','R','R','R'));

		$pdf->SetAligns($aligns);
		$pdf->SetLineWidth(0.2);
		$pdf->SetFont('','',10);

		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_base_dop`.`mass`, `doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt` AS `base_cnt`, `doc_list_pos`.`tovar`, `doc_list_pos`.`cost`, `doc_base`.`vc`, `class_unit`.`rus_name1` AS `units`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data[7]}'
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$ii=1;
		$sum=0;
		$summass=0;
		while($nxt=mysql_fetch_assoc($res))
		{
			$sm=$nxt['cnt']*$nxt['cost'];
			$cost = sprintf("%01.2f руб.", $nxt['cost']);
			$cost2 = sprintf("%01.2f руб.", $sm);
			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt['proizv'])	$nxt['name'].=' / '.$nxt['proizv'];
			$summass+=$nxt['cnt']*$nxt['mass'];

			$row=array($ii);
			if($CONFIG['poseditor']['vc'])
			{
				$row[]=$nxt['vc'];
				$row[]="{$nxt['printname']} {$nxt['name']}";
			}
			else	$row[]="{$nxt['printname']} {$nxt['name']}";

			$mass=sprintf("%0.3f",$nxt['mass']);
			$rezerv=DocRezerv($nxt['tovar'],$this->doc);

			$row=array_merge($row, array($nxt['cost'], "{$nxt['cnt']} {$nxt['units']}", $nxt['base_cnt'], $rezerv, $mass, $nxt['mesto']));

			$pdf->RowIconv($row);
			$i=1-$i;
			$ii++;
			$sum+=$sm;
		}
		$ii--;
		$cost = sprintf("%01.2f руб.", $sum);

		$mass_p=num2str($summass,'kg',3);
		$summass = sprintf("%01.3f", $summass);

		$res=mysql_query("SELECT `name` FROM `users` WHERE `id`='$uid'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить имя пользователя");
		$vip_name=@mysql_result($res,0,0);

		$res=mysql_query("SELECT `name` FROM `users` WHERE `id`='{$this->doc_data['user']}'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить имя автора");
		$autor_name=@mysql_result($res,0,0);

		$klad_id=$this->dop_data['kladovshik'];
		$res=mysql_query("SELECT `id`, `name`, `rname` FROM `users` WHERE `id`='$klad_id'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить имя кладовщика");
		$nxt=mysql_fetch_row($res);

		$pdf->Ln(5);

		$str="Всего $ii наименований массой $summass кг. на сумму $cost";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);

		$str = iconv('UTF-8', 'windows-1251', $mass_p);
		$pdf->Cell(0,5,$str,0,1,'L',0);

		$str="Заявку принял: _________________________________________ ($autor_name)";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="Документ выписал: ______________________________________ ($vip_name)";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);
		$str="Заказ скомплектовал: ___________________________________ ( $nxt[1] - $nxt[2] )";
		$str = iconv('UTF-8', 'windows-1251', unhtmlentities($str));
		$pdf->Cell(0,5,$str,0,1,'L',0);

		if($to_str)
			return $pdf->Output('blading.pdf','S');
		else
			$pdf->Output('blading.pdf','I');
	}

	// -- Акт выполненных работ --------------
	function PrintActRabot()
	{
		global $tmpl, $CONFIG;

		$tmpl->LoadTemplate('print');
		$dt=date("d.m.Y",$this->doc_data[5]);

		$tmpl->AddText("<h1>Акт об оказанных услугах, работах N {$this->doc_data[9]}, от $dt </h1>
		<b>Исполнитель: </b>{$this->firm_vars['firm_name']}<br>
		<b>Заказчик: </b>{$this->doc_data[3]}<br><br>");

		$tmpl->AddText("
		<table width='800' cellspacing='0' cellpadding='0'>
		<tr><th>№</th><th width=450>Наименование работ, услуг<th>Место<th>Кол-во<th>Стоимость<th>Сумма</tr>");
		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`mesto`, `class_unit`.`rus_name1` AS `units`
		FROM `doc_list_pos`
		INNER JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data[7]}'
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_base`.`pos_type`='1'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$ii=1;
		$sum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f руб.", $nxt[4]);
			$cost2 = sprintf("%01.2f руб.", $sm);
			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];
			$tmpl->AddText("<tr align=right><td>$ii</td><td align=left>$nxt[0] $nxt[1]<td>$nxt[5]<td>$nxt[3] $nxt[6]<td>$cost<td>$cost2");
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
		<p>Всего оказанно услуг <b>$ii</b> на сумму <b>$cost</b></p>
		<p class=mini>Вышеперечисленные услуги выполнены полностью и в срок. Заказчик претензий по объёму, качеству и срокам оказания услуг не имеет.</p>
		$prop
		<p>Исполнитель:_____________________________________</p>
		<p>Заказчик: ____________________________________</p>");
	}

	// -- Гарантийный талон --------------
	function PrintWarantyList()
	{
		global $tmpl, $CONFIG;

		$tmpl->LoadTemplate('print');
		$dt=date("d.m.Y",$this->doc_data[5]);

		$tmpl->AddText("<h2>{$this->firm_vars['firm_name']}</h2>
		<h1>Гарантийный талон N {$this->doc_data[9]}, от $dt </h1>");

		$tmpl->AddText("
		<table width='800' cellspacing='0' cellpadding='0'>
		<tr><th>№</th><th width=450>Наименование<th>Кол-во<th>Серийный номер<th>Гарантия</tr>");
		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, COUNT(`doc_list_sn`.`num`), `doc_list_sn`.`num`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`warranty`
		FROM `doc_list_sn`
		INNER JOIN `doc_list_pos` ON `doc_list_pos`.`id`=`doc_list_sn`.`rasx_list_pos` AND  `doc_list_pos`.`doc`='{$this->doc}'
		INNER JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		GROUP BY `doc_list_sn`.`num`
		");
		$ii=1;
		while($nxt=mysql_fetch_row($res))
		{
			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];
			$tmpl->AddText("<tr align=right><td>$ii</td><td align=left>$nxt[0] $nxt[1]<td>$nxt[3] $nxt[5]<td>$nxt[4]<td>$nxt[6] мес.");
			$ii++;
		}
		$tmpl->AddText("</table>
		<br>
		<h3>Правила гарантийного обслуживания</h3>
		<ol class='mini'>
		<li>Гарантийное обслуживание мониторов, принтеров, копировальных аппаратов, источников бесперебойного питания и любого другого периферийного оборудования, гарантия на которое поддерживается производителем, производится в специализированных сервисных центрах, телефоны которых указанны ниже. Доставка оборудования на гарантийный ремонт осуществляется силами покупателя. Cроки гарантийного обслуживания устанавливаются производителем.</li>
		<li>Гарантийному обслуживанию подлежат ТОЛЬКО товары в полной комплектации. При предъявлении претензии покупатель обязан предоставить коробки, упаковку, инструкции, гарантийные талоны, драйвера, соединительные кабели и пр. т.е. абсолютно все, что входило в комплект поставки. Отсутствие хотя бы одного элемента из состава комплекта поставки может являться основанием для отказа в гарантийном обслуживании. Прием оборудования  на гарантийное обслуживание осуществляется только в том случае, если оборудование предоставляется покупателем в ОРИГИНАЛЬНОЙ упаковке (серийные номера на изделии и упаковке идентичны). Несовпадение номеров может являться основанием для отказа в гарантийном обслуживании. Сильное загрязнение также может послужить причиной отказа в приеме изделия в ремонт.</li>
		<li>Гарантийное обслуживание не распространяется на повреждения, вызванное неправильным подключением, эксплуатацией оборудования в нештатном режиме либо в условиях, не предусмотренных производителем, а также происшедшим вследствие действия сторонних обстоятельств (скачков напряжения, стихийных бедствий и т.д.). Гарантийное обслуживание не распространяется на устройства с механическими повреждениями, электрическими прожогами, а также неисправности, вызванные внесением покупателем конструктивных, программных или иных изменений (в. том числе прошивка  BIOS); повреждения процессора, материнской платы и т.д. вследствие &quot;разгона&quot; (работа на завышенных частотах и/или с повышенным напряжением питания).</li>
		<li>Причину возникновения дефектов определяют специалисты гарантийного отдела {$this->firm_vars['firm_name']}. При несогласии покупателя с их заключением может быть проведена независимая экспертиза в соответствии с законом о Защите прав потребителя.</li>
		<li>Неисправное оборудование, гарантийное обслуживание которого осуществляется за счет {$this->firm_vars['firm_name']}, принимается в ремонт на срок продолжительностью 20 (двадцать) рабочих дней. По истечении этого срока отремонтированное оборудование возвращается покупателю. В том случае, если дефекты оборудования носят неисправимый характер, покупателю выдается оборудование, аналогичное сданному в ремонт по цене, функциональности и потребительским качествам. Если принятое в ремонт оборудование уникально, по выбору покупателя:</li>
		<ol type='a'>
		<li>{$this->firm_vars['firm_name']} предоставляет ближайший аналог с компенсацией разницы по цене</li>
		<li>Покупателю возвращаются деньги</li>
		<li>В том случае, если за время нахождения изделия в ремонте произошло изменение розничных цен, {$this->firm_vars['firm_name']} оставляет за собой право зачесть неисправное оборудование по текущей цене прайс - листа.</li>
		</ol>
		<li>Неработоспособность точек экрана не более 4 (четырех) не является существенным недостатком жидкокристаллического дисплея.</li>
		<li>{$this->firm_vars['firm_name']} не предоставляет гарантию на совместимость приобретаемого оборудования и оборудование потребителя. {$this->firm_vars['firm_name']} гарантирует работоспособность каждого из комплектующих в отдельности, но не несет ответственности за качество их совместного функционирования, кроме случаев, когда приобретается компьютер в сборе. В соответствии с Законом о защите прав потребителя в позднейшей редакции и постановлением Правительства Российской Федерации №55 от 19 января 1998 г. &quot;Перечень непродовольственных товаров надлежащего качества, не подлежащих возврату или обмену на аналогичный товар других размера, формы, габарита, фасона, расцветки или комплектации&quot; (с изменениями на 20 октября 1998года) {$this->firm_vars['firm_name']} не обязана принимать обратно исправное оборудование, если оно по каким-либо причинам не подошло покупателю.</li>
		<li>Гарантийные претензии принимаются только при наличии гарантийного талона в рабочее время.</li>
		<li>Совершение покупки означает согласие покупателя с данными правилами.</li>
		</ol>
		<p>С условиями гарантийного обслуживания ознакомлен. Исправность и комплектность проверена, следов механических и электрических повреждений нет, следов непромышленного ремонта нет.</p>

		<p>Продавец:_____________________________________</p>
		<p>Покупатель: ____________________________________</p>
		<p>Настоящий гарантийный талон является документом, подтверждающим право на гарантийное обслуживание приобретённого в {$this->firm_vars['firm_name']} товара. В случае утери гарантийный талон не восстанавливается.<br>Ремонт и обслуживание техники производится по адресу:<br>{$this->firm_vars['firm_adres']}</p>");
	}


// -- Накладная с сортировкой по коду--------------
	function PrintNaklVCOrdered()
	{
		global $tmpl, $CONFIG;

		$tmpl->LoadTemplate('print');
		$dt=date("d.m.Y",$this->doc_data[5]);

		$tmpl->AddText("<h1>Накладная N {$this->doc_data[9]}{$this->doc_data[10]}, от $dt </h1>
		<b>Поставщик: </b>{$this->firm_vars['firm_name']}<br>
		<b>Покупатель: </b>{$this->doc_data[3]}<br><br>");

		$tmpl->AddText("
		<table width=800 cellspacing=0 cellpadding=0>
		<tr><th>№</th><th>Код</th><th width=450>Наименование<th>Место<th>Кол-во<th>Стоимость<th>Сумма</tr>");
		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`mesto`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`vc`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data[7]}'
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_base`.`vc`");
		$i=0;
		$ii=1;
		$sum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f руб.", $nxt[4]);
			$cost2 = sprintf("%01.2f руб.", $sm);
			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];
			$tmpl->AddText("<tr align=right><td>$ii</td><td>$nxt[7]</td><td align=left>$nxt[0] $nxt[1]<td>$nxt[5]<td>$nxt[3] $nxt[6]<td>$cost<td>$cost2");
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


// -- накладная с наценками --------------
	function Nacenki()
	{
		global $tmpl, $CONFIG;

		$tmpl->LoadTemplate('print');
		$dt=date("d.m.Y",$this->doc_data[5]);

		$tmpl->AddText("<h1>Наценки N {$this->doc_data[9]}{$this->doc_data[10]}, от $dt </h1>
		<b>Поставщик: </b>{$this->firm_vars['firm_name']}<br>
		<b>Покупатель: </b>{$this->doc_data[3]}<br>");

		$res=mysql_query("SELECT `users`.`name`, `users`.`rname` FROM `doc_list`
		LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
		WHERE `doc_list`.`id`='{$this->doc_data['p_doc']}' AND `doc_list`.`type`='3'");
		if(mysql_errno())			throw new MysqlException('Ошибка выбоки автора заявки');
		if(mysql_num_rows($res))
		{
			list($aname, $arname)=mysql_fetch_row($res);
			if($arname)	$arname.=' ('.$aname.')';
			else		$arname=$aname;
			$tmpl->AddText("<b>Автор заявки: </b>$arname<br>");
		}
		else echo $this->doc;

		$tmpl->AddText("<br>
		<table width=800 cellspacing=0 cellpadding=0>
		<tr><th>№</th><th width=450>Наименование<th>Кол-во<th>Стоимость<th>Сумма<th>АЦП<th>Наценка<th>Сумма наценки<th>П/закуп<th>Разница<th>Сумма разницы</tr>");
		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `class_unit`.`rus_name1` AS `units`, `doc_list_pos`.`tovar`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		if(mysql_errno())			throw new MysqlException('Ошибка выбоки товаров документа!');
		$i=0;
		$ii=1;
		$sum=$snac=$srazn=$cnt=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f", $nxt[4]);
			$cost2 = sprintf("%01.2f", $sm);
			$act_cost=sprintf('%0.2f',GetInCost($nxt[6]));
			$nac=sprintf('%0.2f',$cost-$act_cost);
			$sum_nac=sprintf('%0.2f',$nac*$nxt[3]);
			$snac+=$sum_nac;

			$r=mysql_query("SELECT `doc_list`.`date`, `doc_list_pos`.`cost` FROM `doc_list_pos`
			LEFT JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
			WHERE `doc_list`.`ok`>'0' AND `doc_list`.`type`='1' AND `doc_list_pos`.`tovar`='$nxt[6]' AND `doc_list`.`date`<'{$this->doc_data['date']}'
			ORDER BY `doc_list`.`date` DESC");
			if(mysql_errno())			throw new MysqlException('Ошибка поиска поступления');
			if(mysql_num_rows($r))		$zakup=sprintf('%0.2f',mysql_result($r,0,1));
			else				$zakup=0;
			$razn=sprintf('%0.2f',$cost-$zakup);
			$sum_razn=sprintf('%0.2f',$razn*$nxt[3]);
			$srazn+=$sum_razn;
			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];
			$tmpl->AddText("<tr align=right><td>$ii</td><td align=left>$nxt[0] $nxt[1]<td>$nxt[3] $nxt[5]<td>$cost<td>$cost2<td>$act_cost<td>$nac<td>$sum_nac<td>$zakup<td>$razn<td>$sum_razn");
			$i=1-$i;
			$ii++;
			$sum+=$sm;
			$cnt+=$nxt[3];
		}
		$ii--;
		$cost = sprintf("%01.2f", $sum);
		$srazn = sprintf("%01.2f", $srazn);
		$snac = sprintf("%01.2f", $snac);

		$tmpl->AddText("<tr>
		<td colspan='2'><b>ИТОГО:</b><td>$cnt<td><td>$cost<td><td><td>$snac<td><td><td>$srazn
		</table>
		<p>Всего <b>$ii</b> наименований на сумму <b>$cost</b></p>
		");
	}
	// -- Копия чека --------------
	function PrintKopia($doc)
	{
		global $tmpl, $CONFIG;

		$tmpl->LoadTemplate('print');
		$dt=date("d.m.Y",$this->doc_data[5]);

		$tmpl->AddText("<h1>Копия чека N {$this->doc_data[9]}{$this->doc_data[10]}, от $dt</h1>
		<b>Поставщик: </b>".$this->firm_vars['firm_name'].", ".$this->firm_vars['firm_adres'].", ".$this->firm_vars['firm_telefon']."<br>
		<b>Покупатель: </b>{$this->doc_data[3]}<br>
		<br><br>");

		$tmpl->AddText("
		<table width=800 cellspacing=0 cellpadding=0>
		<tr><th>№</th><th width=450>Наименование<th>Кол-во<th>Стоимость<th>Сумма</tr>");
		$res=mysql_query("SELECT `doc_group`.`printname`,`doc_base`.`name`,`doc_base`.`proizv` ,`doc_list_pos`.`cnt`,`doc_list_pos`.`cost`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$ii=1;
		$sum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f руб.", $nxt[4]);
			$cost2 = sprintf("%01.2f руб.", $sm);
			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];
			$tmpl->AddText("<tr align=right><td>$ii</td><td align=left>$nxt[0] $nxt[1]<td>$nxt[3]<td>$cost<td>$cost2");
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

	// -- Копия чека без покупателя --------------
	function PrintKopiaNoPok($doc)
	{
		global $tmpl, $CONFIG;

		$tmpl->LoadTemplate('print');
		$dt=date("d.m.Y",$this->doc_data[5]);

		$tmpl->AddText("<h1>Копия чека N {$this->doc_data[9]}{$this->doc_data[10]}, от $dt</h1>
		<b>Поставщик: </b>".$this->firm_vars['firm_name'].", ".$this->firm_vars['firm_adres'].", ".$this->firm_vars['firm_telefon']."<br>
		<br><br>");

		$tmpl->AddText("
		<table width=800 cellspacing=0 cellpadding=0>
		<tr><th>№</th><th width=450>Наименование<th>Кол-во<th>Стоимость<th>Сумма</tr>");
		$res=mysql_query("SELECT `doc_group`.`printname`,`doc_base`.`name`,`doc_base`.`proizv` ,`doc_list_pos`.`cnt`,`doc_list_pos`.`cost`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$ii=1;
		$sum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f руб.", $nxt[4]);
			$cost2 = sprintf("%01.2f руб.", $sm);
			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];
			$tmpl->AddText("<tr align=right><td>$ii</td><td align=left>$nxt[0] $nxt[1]<td>$nxt[3]<td>$cost<td>$cost2");
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
		// -- Товарный чек --------------
	function PrintTovCheck()
	{
		global $tmpl, $CONFIG;

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
		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `class_unit`.`rus_name1`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$ii=1;
		$sum=$cnt=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f", $nxt[4]);
			$cost2 = sprintf("%01.2f", $sm);
			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];
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
		WHERE `doc_list_pos`.`doc`='$doc'  AND `doc_base`.`pos_type`='0'
		ORDER BY `doc_list_pos`.`id`");
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
			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];
			$tmpl->AddText("
<tr>
<td>$ii
<td id=bb>$nxt[0] $nxt[1]
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

function NaklEmail($email='')
{
	global $tmpl;
	if(!$email)
		$email=rcv('email');

	if($email=='')
	{
		$tmpl->ajax=1;
		$res=mysql_query("SELECT `email` FROM `doc_agent` WHERE `id`='{$this->doc_data[2]}'");
		$email=mysql_result($res,0,0);
		$tmpl->AddText("<form action=''>
		<input type=hidden name=mode value='print'>
		<input type=hidden name=doc value='{$this->doc}'>
		<input type=hidden name=opt value='nak_email'>
		email:<input type=text name=email value='$email'><br>
		Коментарий:<br>
		<textarea name='comm'></textarea><br>
		<input type=submit value='&gt;&gt;'>
		</form>");
	}
	else
	{
		$comm=rcv('comm');
		doc_menu();
		$this->SendDocEMail($email, $comm, 'Счёт-фактура', $this->PrintNaklPDF(1), "blading.pdf");
		$tmpl->msg("Сообщение отправлено!","ok");
	}
}

function SfakEmail($email='')
{
	global $tmpl;
	if(!$email)
		$email=rcv('email');

	if($email=='')
	{
		$tmpl->ajax=1;
		$res=mysql_query("SELECT `email` FROM `doc_agent` WHERE `id`='{$this->doc_data[2]}'");
		$email=mysql_result($res,0,0);
		$tmpl->AddText("<form action=''>
		<input type=hidden name=mode value='print'>
		<input type=hidden name=doc value='{$this->doc}'>
		<input type=hidden name=opt value='sf_email'>
		email:<input type=text name=email value='$email'><br>
		Коментарий:<br>
		<textarea name='comm'></textarea><br>
		<input type=submit value='&gt;&gt;'>
		</form>");
	}
	else
	{
		$comm=rcv('comm');
		doc_menu();
		$this->SendDocEMail($email, $comm, 'Счёт-фактура', $this->SfakPDF(1), "schet-fakt.pdf");
		$tmpl->msg("Сообщение отправлено!","ok");
	}
}

function TgEmail($email='')
{
	global $tmpl;
	if(!$email)	$email=rcv('email');

	if($email=='')
	{
		$tmpl->ajax=1;
		$res=mysql_query("SELECT `email` FROM `doc_agent` WHERE `id`='{$this->doc_data[2]}'");
		$email=mysql_result($res,0,0);
		$tmpl->AddText("<form action=''>
		<input type=hidden name=mode value='print'>
		<input type=hidden name=doc value='{$this->doc}'>
		<input type=hidden name=opt value='tg12_email'>
		email:<input type=text name=email value='$email'><br>
		Коментарий:<br>
		<textarea name='comm'></textarea><br>
		<input type=submit value='&gt;&gt;'>
		</form>");
	}
	else
	{
		$comm=rcv('comm');
		doc_menu();
		$this->SendDocEMail($email, $comm, 'Накладная по форме ТОРГ-12', $this->PrintTg12PDF(1), "torg12.pdf");
		$tmpl->msg("Сообщение отправлено!","ok");
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
	else					$platelshik.=@$platelshik_info['name'];
	if($platelshik_info['adres'])		$platelshik.=', адрес '.$platelshik_info['adres'];
	if($platelshik_info['tel'])		$platelshik.=', тел. '.$platelshik_info['tel'];
	if($platelshik_info['inn'])		$platelshik.=', ИНН/КПП '.$platelshik_info['inn'];
	if($platelshik_info['okevd'])		$platelshik.=', ОКВЭД '.$platelshik_info['okevd'];
	if($platelshik_info['rs'])		$platelshik.=', Р/С '.$platelshik_info['rs'];
	if($platelshik_info['bank'])		$platelshik.=', в банке '.$platelshik_info['bank'];
	if($platelshik_info['bik'])		$platelshik.=', БИК '.$platelshik_info['bik'];
	if($platelshik_info['ks'])		$platelshik.=', К/С '.$platelshik_info['ks'];

	$str = unhtmlentities("{$platelshik_info['fullname']}, адрес {$platelshik_info['adres']}, тел. {$platelshik_info['tel']}, ИНН/КПП {$platelshik_info['inn']}, ОКПО {$platelshik_info['okpo']},  ОКВЭД {$platelshik_info['okevd']}, БИК {$platelshik_info['bik']}, Р/С {$platelshik_info['rs']}, К/С {$platelshik_info['ks']}, банк {$platelshik_info['bank']}");

	if(isset($this->dop_data['dov_agent']))
	{
		$rr=mysql_query("SELECT `surname`,`name`,`name2`,`range` FROM `doc_agent_dov`
		WHERE `id`='{$this->dop_data['dov_agent']}'");
		if(mysql_errno())		throw new MysqlException("Невозможно получить данные доверенного лица!");
		if($nn=@mysql_fetch_row($rr))
		{
			$dov_agn="$nn[0] $nn[1] $nn[2]";
			$dov_agr=$nn[3];
		}
		else	$dov_agn=$dov_agr="";
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
	$str=unhtmlentities($this->firm_vars['firm_gruzootpr'].", тел.".$this->firm_vars['firm_telefon'].", счёт ".$this->firm_vars['firm_schet'].", БИК ".$this->firm_vars['firm_bik'].", банк ".$this->firm_vars['firm_bank'].", К/С {$this->firm_vars['firm_bank_kor_s']}, адрес: {$this->firm_vars['firm_adres']}");
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->MultiCell(230,4,$str,0,'L');
	$y=$pdf->GetY();
	$pdf->Line(10, $pdf->GetY(), 230, $pdf->GetY());
	$pdf->SetFont('','',5);
	$str="грузоотправитель, адрес, номер телефона, банковские реквизиты";
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->Cell(230,2,$str,0,1,'C');


	$pdf->SetFont('','',8);
	$str="";
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
	$str = unhtmlentities("{$this->firm_vars['firm_name']}, {$this->firm_vars['firm_adres']}, ИНН/КПП {$this->firm_vars['firm_inn']}, К/С {$this->firm_vars['firm_bank_kor_s']}, Р/С {$this->firm_vars['firm_schet']}, БИК {$this->firm_vars['firm_bik']}, в банке {$this->firm_vars['firm_bank']}");
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
	$pdf->SetX(50);
	$pdf->SetLineWidth($bold_line);
	$pdf->SetFont('','',10);
	$str='ТОВАРНАЯ НАКЛАДНАЯ';
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->Cell(50,4,$str,0,0,'C');
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
        $t2_ydelta=array(4,4,2,2,1,3,3,3);
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
	$pdf->SetFillColor(255,255,255);
	$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `class_unit`.`rus_name1`, `doc_base_dop`.`mass`, `doc_base`.`vc`, `class_unit`.`number_code`
	FROM `doc_list_pos`
	LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
	LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
	WHERE `doc_list_pos`.`doc`='{$this->doc}'  AND `doc_base`.`pos_type`='0'
	ORDER BY `doc_list_pos`.`id`");
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
		$summass+=$mass;
		$list_summass+=$mass;
		$sum+=$snalogom;
		$list_sum+=$snalogom;
		$sumnaloga+=$nalog;
		$list_sumnaloga+=$nalog;

		$pdf->Cell($t_all_width[0],$line_height, $ii ,1,0,'R',1);
		if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];
		$str = iconv('UTF-8', 'windows-1251', $nxt[0].' '.$nxt[1] );
		$pdf->Cell($t_all_width[1],$line_height, $str ,1,0,'L',1);
		$str = iconv('UTF-8', 'windows-1251', $nxt[7] );
		$pdf->Cell($t_all_width[2],$line_height, $str ,1,0,'L',1);
		$str = iconv('UTF-8', 'windows-1251', $nxt[5] );
		$pdf->Cell($t_all_width[3],$line_height, $str ,1,0,'C',1);
		$pdf->Cell($t_all_width[4],$line_height, $nxt[8] ,1,0,'C',1);
		$pdf->Cell($t_all_width[5],$line_height, '-' ,1,0,'C',1);
		$pdf->Cell($t_all_width[6],$line_height, '-' ,1,0,'C',1);
		$pdf->Cell($t_all_width[7],$line_height, '-' ,1,0,'C',1);
		$pdf->Cell($t_all_width[8],$line_height, $mass ,1,0,'C',1);
		$pdf->Cell($t_all_width[9],$line_height, "$nxt[3] / $mass" ,1,0,'C',1);

		$pdf->Cell($t_all_width[10],$line_height, $cena ,1,0,'C',1);
		$pdf->Cell($t_all_width[11],$line_height, $stoimost ,1,0,'C',1);
		$pdf->Cell($t_all_width[12],$line_height, "$ndsp%" ,1,0,'C',1);
		$pdf->Cell($t_all_width[13],$line_height, $nalog ,1,0,'R',1);
		$pdf->Cell($t_all_width[14],$line_height, $snalogom ,1,0,'R',1);
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
			$pdf->Cell($w,$line_height, $str ,0,0,'R',1);
			$pdf->Cell($t_all_width[7],$line_height, '-' ,1,0,'C',1);
			$pdf->Cell($t_all_width[8],$line_height, $list_summass ,1,0,'C',1);
			$pdf->Cell($t_all_width[9],$line_height, "$list_cnt / $list_summass" ,1,0,'C',1);

			$pdf->Cell($t_all_width[10],$line_height, '' ,1,0,'C',1);
			$pdf->Cell($t_all_width[11],$line_height, $list_sumbeznaloga ,1,0,'C',1);
			$pdf->Cell($t_all_width[12],$line_height, "-" ,1,0,'C',1);
			$pdf->Cell($t_all_width[13],$line_height, $list_sumnaloga ,1,0,'R',1);
			$pdf->Cell($t_all_width[14],$line_height, $list_sum ,1,0,'R',1);
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

	$pdf->Cell($t_all_width[10],$line_height, 'X' ,1,0,'C',0);
	$pdf->Cell($t_all_width[11],$line_height, $list_sumbeznaloga ,1,0,'C',0);
	$pdf->Cell($t_all_width[12],$line_height, "X" ,1,0,'C',0);
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

	$pdf->Cell($t_all_width[10],$line_height, 'X' ,1,0,'C',0);
	$pdf->Cell($t_all_width[11],$line_height, $sumbeznaloga ,1,0,'C',0);
	$pdf->Cell($t_all_width[12],$line_height, "X" ,1,0,'C',0);
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
	$str = iconv('UTF-8', 'windows-1251', "Всего мест: ".$this->dop_data['mest'] );
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
	$str = iconv('UTF-8', 'windows-1251', $this->firm_vars['firm_kladovshik_doljn'] );
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
	$str = @iconv('UTF-8', 'windows-1251', $this->dop_data['dov']." от ".$this->dop_data['dov_data'] );
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

function SfakPDF($to_str=0)
{
	global $CONFIG;
	define('FPDF_FONT_PATH',$CONFIG['site']['location'].'/fpdf/font/');
	require('fpdf/fpdf_mc.php');
	global $tmpl;

	if(!$to_str) $tmpl->ajax=1;

	$dt=date("d.m.Y",$this->doc_data['date']);

	$res=mysql_query("SELECT `doc_agent`.`name`, `doc_agent`.`fullname`, `doc_agent`.`adres`
	FROM `doc_agent` WHERE `doc_agent`.`id`='{$this->dop_data['gruzop']}'	");
	if(mysql_errno())		throw new MysqlException("Невозможно получить данные грузополучателя!");
	$gruzop_info=mysql_fetch_array($res);
	if(!$gruzop_info)		$gruzop_info=array();
	$gruzop='';
	if($gruzop_info['fullname'])	$gruzop.=$gruzop_info['fullname'];
	else				$gruzop.=$gruzop_info['name'];
	if($gruzop_info['adres'])	$gruzop.=', '.$gruzop_info['adres'];

	$res=mysql_query("SELECT `doc_agent`.`id`, `doc_agent`.`fullname`, `doc_agent`.`adres`,  `doc_agent`.`tel`, `doc_agent`.`inn` FROM `doc_agent` WHERE `doc_agent`.`id`='{$this->doc_data[2]}'	");

	$nx=@mysql_fetch_row($res);
	if($this->doc_data[13])
	{
		$rs=@mysql_query("SELECT `id`, `altnum`, `date` FROM `doc_list` WHERE
		(`p_doc`='{$this->doc}' AND (`type`='4' OR `type`='6') AND `date`<='{$this->doc_data['date']}' ) OR
		(`p_doc`='{$this->doc_data['p_doc']}' AND (`type`='4' OR `type`='6') AND `date`<='{$this->doc_data['date']}')
		AND `ok`>'0' AND `p_doc`!='0' GROUP BY `p_doc`");
		$pp=@mysql_result($rs,0,1);
		$ppdt=@date("d.m.Y",mysql_result($rs,0,2));
		if(!$pp) $pp=@mysql_result($rs,0,0);
	}
	if(!@$pp) $pp=$ppdt="__________";

	$pdf=new PDF_MC_Table('P');
	$pdf->Open();
	$pdf->SetAutoPageBreak(1,12);
	$pdf->AddFont('Arial','','arial.php');
	$pdf->tMargin=5;
	$pdf->AddPage('L');
	$pdf->SetFillColor(255);

	$pdf->Setx(150);
	$pdf->SetFont('Arial','',7);
	$str = 'Приложение №1 к постановлению правительства РФ от 26 декабря 2011г N1137';
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->MultiCell(0,4,$str,0,'R');
	$pdf->Ln();
	$pdf->SetFont('','',16);
	$step=4;
	$str = iconv('UTF-8', 'windows-1251', "Счёт - фактура N {$this->doc_data[9]}, от $dt");
	$pdf->Cell(0,6,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "Исправление N ---- от --.--.----");
	$pdf->Cell(0,6,$str,0,1,'L');
	$pdf->Ln(5);
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
	if($str)
	{
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,$step,$str,0,1,'L');
	}
	$str = iconv('UTF-8', 'windows-1251', "Валюта: наименование, код: Российский рубль, 643");
	$pdf->Cell(0,$step,$str,0,1,'L');

	$pdf->Ln(3);

	$y=$pdf->GetY();


	// ====== Основная таблица =============
        $y=$pdf->GetY();

        $t_all_offset=array();

	$pdf->SetLineWidth(0.3);
	$t_width=array(88,22,10,15,20,10,10,16,28,26,0);
	$t_ydelta=array(7,0,5,5,0,6,6,7,3,0,7);
	$t_text=array(
	'Наименование товара (описание выполненных работ, оказанных услуг, имущественного права)',
	'Единица измерения',
	'Количество (объ ём)',
	'Цена (тариф) за единицу измерения',
	'Стоимость товаров (работ, услуг), имуществен- ных прав, всего без налога',
	'В том числе акциз',
	'Нало- говая ставка',
	'Сумма налога',
	'Стоимость товаров (работ, услуг, имущественных прав), всего с учетом налога',
	'Страна происхождения',
	'Номер таможенной декларации');

	foreach($t_width as $w)
	{
		$pdf->Cell($w,20,'',1,0,'C',0);
	}
	$pdf->Ln();
	$pdf->Ln(0.5);
	$pdf->SetFont('','',7);
	$offset=0;
	foreach($t_width as $i => $w)
	{
		$t_all_offset[$offset]=$offset;
		$pdf->SetY($y+$t_ydelta[$i]+0.2);
		$pdf->SetX($offset+$pdf->lMargin);
		$str = iconv('UTF-8', 'windows-1251', $t_text[$i] );
		$pdf->MultiCell($w,2.7,$str,0,'C',0);
		$offset+=$w;
	}

        $t2_width=array(7, 15, 7, 19);
        $t2_start=array(1,1,9,9);
        $t2_ydelta=array(2,1,2,3);
        $t2_text=array(
	"к\nо\nд",
	'условное ообзначение (наци ональное)',
	"к\nо\nд",
	'краткое наименование');
	$offset=0;
	$c_id=0;
	$old_col=0;
	$y+=6;

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
		$pdf->Cell($w2,14,'',1,0,'C',0);

		$pdf->SetY($y+$t2_ydelta[$i]);
		$pdf->SetX($offset+$off2+$pdf->lMargin);
		$str = iconv('UTF-8', 'windows-1251', $t2_text[$i] );
		$pdf->MultiCell($w2,3,$str,0,'C',0);
	}

	$t3_text=array(1,2,'2a',3,4,5,6,7,8,9,10,'10a',11);
	$pdf->SetLineWidth(0.2);
	sort ( $t_all_offset, SORT_NUMERIC );
	$pdf->SetY($y+14);
	$t_all_width=array();
	$old_offset=0;
	foreach($t_all_offset as $offset)
	{
		if($offset==0)	continue;
		$t_all_width[]=	$offset-$old_offset;
		$old_offset=$offset;
	}
	$t_all_width[]=32;
	$i=1;
	foreach($t_all_width as $id => $w)
	{
		$pdf->Cell($w,4,$t3_text[$i-1],1,0,'C',0);
		$i++;
	}

	$pdf->SetWidths($t_all_width);

	$font_sizes=array();
	$font_sizes[0]=8;
	$font_sizes[11]=7;
	$pdf->SetFSizes($font_sizes);
	$pdf->SetHeight(4);

	$aligns=array('L','R','R','R','R','R','C','R','R','R','R','L','R');
	$pdf->SetAligns($aligns);

	$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_list_pos`.`gtd`, `class_country`.`name`, `doc_base_dop`.`ntd`, `class_unit`.`rus_name1`, `doc_list_pos`.`tovar`, `class_unit`.`number_code`, `class_country`.`number_code`
	FROM `doc_list_pos`
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
	LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
	LEFT JOIN `class_country` ON `class_country`.`id`=`doc_base`.`country`
	WHERE `doc_list_pos`.`doc`='{$this->doc}'
	ORDER BY `doc_list_pos`.`id`");
	if(mysql_errno())	throw new MysqlException("Не удалось выбрать список товаров");

	$pdf->SetY($y+18);
	$pdf->SetFillColor(255,255,255);
	$i=0;
	$ii=1;
	$sum=$sumnaloga=$sumbeznaloga=0;
	$nds=$this->firm_vars['param_nds']/100;
	$ndsp=$this->firm_vars['param_nds'];
	while($nxt=mysql_fetch_row($res))
	{
		if(!$nxt[11])	throw new Exception("Не допускается печать счёта-фактуры без указания страны происхождения товара");

		$pdf->SetFont('','',8);
		if(@$CONFIG['poseditor']['true_gtd'])
		{
			$gtd_array=array();
			$gres=mysql_query("SELECT `doc_list`.`type`, `doc_list_pos`.`gtd`, `doc_list_pos`.`cnt` FROM `doc_list_pos`
			INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`type`<='2' AND `doc_list`.`date`<'{$this->doc_data['date']}' AND `doc_list`.`ok`>'0'
			WHERE `doc_list_pos`.`tovar`='$nxt[9]' ORDER BY `doc_list`.`id`");
			if(mysql_errno())	throw MysqlException("Выборка документов не удалась");
			while($line=mysql_fetch_row($gres))
			{
				if($line[0]==1)
					for($i=0;$i<$line[2];$i++)	$gtd_array[]=$line[1];
				else
					for($i=0;$i<$line[2];$i++)	array_shift($gtd_array);
			}

			$unigtd=array();
			for($i=0;$i<$nxt[3];$i++)	@$unigtd[array_shift($gtd_array)]++;

			foreach($unigtd as $gtd => $cnt)
			{
				if($this->doc_data[12])
				{
					$cena = $nxt[4]/(1+$nds);
					$stoimost = $cena*$cnt;
					$nalog = ($nxt[4]*$cnt)-$stoimost;
					$snalogom = $nxt[4]*$cnt;
				}
				else
				{
					$cena = $nxt[4];
					$stoimost = $cena*$cnt;
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
				$sumbeznaloga+=$cena;

				if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];
				$row=array( "$nxt[0] $nxt[1]", $nxt[10], $nxt[8], $cnt, $cena, $stoimost, 'без акциз', "$ndsp%", $nalog, $snalogom, $nxt[11], $nxt[6], $gtd);
				$pdf->RowIconv($row);
			}
		}
		else
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
			$sumbeznaloga+=$cena;

			$row=array( "$nxt[0] $nxt[1] / $nxt[2]", $nxt[10], $nxt[8], $nxt[3], $cena, $stoimost, 'без акциз', "$ndsp%", $nalog, $snalogom, $nxt[11], $nxt[6], $nxt[7]);
			$pdf->RowIconv($row);
		}
	}

	if($pdf->h<=($pdf->GetY()+65)) $pdf->AddPage('L');
	$delta=$pdf->h-($pdf->GetY()+55);
	if($delta>7) $delta=7;

	$sum = sprintf("%01.2f", $sum);
	$sumnaloga = sprintf("%01.2f", $sumnaloga);
	$sumbeznaloga = sprintf("%01.2f", $sumbeznaloga);
	$step=5.5;
	$pdf->SetFont('','',9);
	$pdf->SetLineWidth(0.3);
	$str = iconv('UTF-8', 'windows-1251', "Всего к оплате:" );
	$pdf->Cell($t_all_width[0]+$t_all_width[1]+$t_all_width[2]+$t_all_width[3]+$t_all_width[4],$step,$str,1,0,'L',0);
// +$t_all_width[6]+$t_all_width[7]
	$pdf->Cell($t_all_width[5],$step,$sumbeznaloga,1,0,'R',0);
	$pdf->Cell($t_all_width[6]+$t_all_width[7],$step,'X',1,0,'C',0);
	$pdf->Cell($t_all_width[8],$step,$sumnaloga,1,0,'R',0);
	$pdf->Cell($t_all_width[9],$step,$sum,1,0,'R',0);

	$pdf->Ln(10);

	$pdf->SetFont('','',10);
	$str = iconv('UTF-8', 'windows-1251', "Руководитель организации:");
	$pdf->Cell(50,$step,$str,0,0,'L',0);
	$str='_____________________';
	$pdf->Cell(50,$step,$str,0,0,'L',0);
	$str = iconv('UTF-8', 'windows-1251', "/".$this->firm_vars['firm_director']."/");
	$pdf->Cell(40,$step,$str,0,0,'L',0);

	$str = iconv('UTF-8', 'windows-1251', "Главный бухгалтер:");
	$pdf->Cell(40,$step,$str,0,0,'R',0);
	$str='_____________________';
	$pdf->Cell(50,$step,$str,0,0,'L',0);
	$str = iconv('UTF-8', 'windows-1251', "/".$this->firm_vars['firm_buhgalter']."/");
	$pdf->Cell(0,$step,$str,0,0,'L',0);
	$pdf->Ln(4);
	$pdf->SetFont('','',7);
	$str = iconv('UTF-8', 'windows-1251', "или иное уполномоченное лицо");
	$pdf->Cell(140,3,$str,0,0,'L',0);
	$str = iconv('UTF-8', 'windows-1251', "или иное уполномоченное лицо");
	$pdf->Cell(50,3,$str,0,0,'L',0);
	$pdf->Ln(8);

	$pdf->SetFont('','',10);
	$str = iconv('UTF-8', 'windows-1251', "Индивидуальный предприниматель:______________________ / ____________________________/");
	$pdf->Cell(160,$step,$str,0,0,'L',0);
	$pdf->Cell(0,$step,'____________________________________',0,1,'R',0);

	$pdf->SetFont('','',7);
	$pdf->Cell(160,$step,'',0,0,'L',0);
	$str = iconv('UTF-8', 'windows-1251', "реквизиты свидетельства о государственной регистрации ИП");
	$pdf->Cell(0,3,$str,0,0,'R',0);


	$pdf->Ln(10);
	$pdf->SetFont('','',7);
	$str = iconv('UTF-8', 'windows-1251', "ПРИМЕЧАНИЕ. Первый экземпляр (оригинал) - покупателю, второй экземпляр (копия) - продавцу" );
	$pdf->Cell(0,$step,$str,0,0,'R',0);

	$pdf->Ln();

	if($to_str)	return $pdf->Output('s_faktura.pdf','S');
	else		$pdf->Output('s_faktura.pdf','I');
}

function Sfak2010PDF($to_str=0)
{
	global $CONFIG;
	define('FPDF_FONT_PATH',$CONFIG['site']['location'].'/fpdf/font/');
	require('fpdf/fpdf_mysql.php');
	global $tmpl, $uid;

	if(!$to_str) $tmpl->ajax=1;

	$dt=date("d.m.Y",$this->doc_data['date']);

	$res=mysql_query("SELECT `doc_agent`.`name`, `doc_agent`.`fullname`, `doc_agent`.`adres`
	FROM `doc_agent` WHERE `doc_agent`.`id`='{$this->dop_data['gruzop']}'	");
	if(mysql_errno())		throw new MysqlException("Невозможно получить данные грузополучателя!");
	$gruzop_info=mysql_fetch_array($res);
	if(!$gruzop_info)		$gruzop_info=array();
	$gruzop='';
	if($gruzop_info['fullname'])	$gruzop.=$gruzop_info['fullname'];
	else				$gruzop.=$gruzop_info['name'];
	if($gruzop_info['adres'])	$gruzop.=', '.$gruzop_info['adres'];

	$res=mysql_query("SELECT `doc_agent`.`id`, `doc_agent`.`fullname`, `doc_agent`.`adres`,  `doc_agent`.`tel`, `doc_agent`.`inn` FROM `doc_agent` WHERE `doc_agent`.`id`='{$this->doc_data[2]}'	");

	$nx=@mysql_fetch_row($res);
	if($this->doc_data[13])
	{
		$rs=@mysql_query("SELECT `id`, `altnum`, `date` FROM `doc_list` WHERE
		(`p_doc`='{$this->doc}' AND (`type`='4' OR `type`='6') AND `date`<='{$this->doc_data['date']}' ) OR
		(`p_doc`='{$this->doc_data['p_doc']}' AND (`type`='4' OR `type`='6') AND `date`<='{$this->doc_data['date']}')
		AND `ok`>'0' AND `p_doc`!='0' GROUP BY `p_doc`");
		if(mysql_errno())		throw new MysqlException("Невозможно получить данные связанных документов!");
		$pp=@mysql_result($rs,0,1);
		$ppdt=@date("d.m.Y",mysql_result($rs,0,2));
		if(!$pp) $pp=@mysql_result($rs,0,0);
	}
	if(!@$pp) $pp=$ppdt="__________";

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

	$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_list_pos`.`gtd`, `class_country`.`name` AS `strana`, `doc_base_dop`.`ntd`, `class_unit`.`rus_name1`, `doc_list_pos`.`tovar`
	FROM `doc_list_pos`
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
	LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
	LEFT JOIN `class_country` ON `class_country`.`id`=`doc_base`.`country`
	WHERE `doc_list_pos`.`doc`='{$this->doc}'");
	if(mysql_errno())		throw new MysqlException("Невозможно получить номенклатуру документа!");
	$pdf->SetLineWidth(0.2);
	$pdf->SetY($y+16);
	$pdf->SetFillColor(255,255,255);
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

		$gtd='';
		if(@$CONFIG['poseditor']['true_gtd'])
		{
			$gtd_array=array();
			$gres=mysql_query("SELECT `doc_list`.`type`, `doc_list_pos`.`gtd`, `doc_list_pos`.`cnt` FROM `doc_list_pos`
			INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`type`<='2' AND `doc_list`.`date`<'{$this->doc_data['date']}' AND `doc_list`.`ok`>'0'
			WHERE `doc_list_pos`.`tovar`='$nxt[9]' ORDER BY `doc_list`.`id`");
			if(mysql_errno())	throw MysqlException("Выборка документов не удалась");
			while($line=mysql_fetch_row($gres))
			{
				if($line[0]==1)
					for($i=0;$i<$line[2];$i++)	$gtd_array[]=$line[1];
				else
					for($i=0;$i<$line[2];$i++)	array_shift($gtd_array);
			}
			//$gtd=array_shift($gtd_array);

			$unigtd=array();
			for($i=0;$i<$nxt[3];$i++)
			{
				@$unigtd[array_shift($gtd_array)]++;
			}

			foreach($unigtd as $gtd => $cnt)
			{
				$y=$pdf->GetY();
				$step=5;
				$pdf->SetFont('','',9);
				$str = iconv('UTF-8', 'windows-1251', "$nxt[0] $nxt[1] / $nxt[2]" );
				$pdf->Cell($t_width[0],$step,$str,1,0,'L',1);
				$str = iconv('UTF-8', 'windows-1251', $nxt[8] );
				$pdf->Cell($t_width[1],$step,$str,1,0,'R',1);
				$str = iconv('UTF-8', 'windows-1251', $cnt );
				$pdf->Cell($t_width[2],$step,$str,1,0,'R',1);
				$pdf->Cell($t_width[3],$step,$cena,1,0,'R',1);
				$pdf->Cell($t_width[4],$step,$stoimost,1,0,'R',1);
				$pdf->Cell($t_width[5],$step,'--',1,0,'C',1);
				$pdf->Cell($t_width[6],$step,"$ndsp%",1,0,'R',1);
				$pdf->Cell($t_width[7],$step,$nalog,1,0,'R',1);
				$pdf->Cell($t_width[8],$step,$snalogom,1,0,'R',1);
				$str = iconv('UTF-8', 'windows-1251', $nxt[6] );
				$pdf->SetFont('','',6);
				$pdf->Cell($t_width[9],$step,$str,1,0,'R',1);
				$pdf->Cell($t_width[10],$step,$gtd,1,0,'R',1);
				$pdf->Ln();
			}
		}
		else
		{
			$y=$pdf->GetY();
			$step=5;
			$pdf->SetFont('','',9);
			$str = iconv('UTF-8', 'windows-1251', "$nxt[0] $nxt[1] / $nxt[2]" );
			$pdf->Cell($t_width[0],$step,$str,1,0,'L',1);
			$str = iconv('UTF-8', 'windows-1251', $nxt[8] );
			$pdf->Cell($t_width[1],$step,$str,1,0,'R',1);
			$str = iconv('UTF-8', 'windows-1251', $nxt[3] );
			$pdf->Cell($t_width[2],$step,$str,1,0,'R',1);
			$pdf->Cell($t_width[3],$step,$cena,1,0,'R',1);
			$pdf->Cell($t_width[4],$step,$stoimost,1,0,'R',1);
			$pdf->Cell($t_width[5],$step,'--',1,0,'C',1);
			$pdf->Cell($t_width[6],$step,"$ndsp%",1,0,'R',1);
			$pdf->Cell($t_width[7],$step,$nalog,1,0,'R',1);
			$pdf->Cell($t_width[8],$step,$snalogom,1,0,'R',1);
			$str = iconv('UTF-8', 'windows-1251', $nxt[6] );
			$pdf->SetFont('','',6);
			$pdf->Cell($t_width[9],$step,$str,1,0,'R',1);
			$pdf->Cell($t_width[10],$step,$nxt[7],1,0,'R',1);
			$pdf->Ln();
		}
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