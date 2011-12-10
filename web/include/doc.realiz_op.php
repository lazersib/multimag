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


$doc_types[15]="Опер. реализация";

class doc_Realiz_op extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого

	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=15;
		$this->doc_name				='realiz_op';
		$this->doc_viewname			='Реализация товара (опер)';
		$this->sklad_editor_enable		=true;
		$this->sklad_modify			=0;
		$this->header_fields			='sklad cena separator agent';
		$this->dop_menu_buttons			="<a href='' onclick=\"ShowPopupWin('/doc.php?mode=srv&amp;opt=dov&amp;doc=$doc'); return false;\" title='Доверенное лицо'><img src='img/i_users.png' alt='users'></a>";
		settype($this->doc,'int');
	}

	function DopHead()
	{
		global $tmpl;
		$checked=$this->dop_data['received']?'checked':'';
		$tmpl->AddText("<label><input type='checkbox' name='received' value='1' $checked>Документы подписаны и получены</label><br>");	
	}

	function DopSave()
	{
		$received=rcv('received');
		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
		VALUES ( '{$this->doc}' ,'received','$received')");
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
		$tim=time();

		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if( !($nx=@mysql_fetch_row($res) ) )	throw new MysqlException('Ошибка выборки данных документа при проведении!');		
		if( $nx[4] && ( !$silent) )		throw new Exception('Документ уже был проведён!');		
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if( !$res )				throw new MysqlException('Ошибка проведения, ошибка установки даты проведения!');
		
		$res=mysql_query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`pos_type`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$nx[3]'
		WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_base`.`pos_type`='0'");
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[1]>$nxt[2])	throw new Exception("Недостаточно ($nxt[1]) товара '$nxt[3]:$nxt[4]': на складе только $nxt[2] шт! $nx[0]");
			$budet=CheckMinus($nxt[0], $nx[3]);
			if( $budet<0)		throw new Exception("Невозможно, т.к. будет недостаточно ($budet) товара '$nxt[3]:$nxt[4]'!");
		}
		if($silent)	return;
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if( !$res )				throw new MysqlException('Ошибка проведения, ошибка установки даты проведения!');
	}

	function PrintForm($doc, $opt='')
	{
		if($opt=='')
		{
			global $tmpl;
			$tmpl->ajax=1;
			$tmpl->AddText("<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=nak'\">Накладная</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=kop'\">Копия чека</div>		
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=tg12'\">Форма ТОРГ-12</div>		
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=sf_pdf'\">Счёт - фактура</div>		
			<div onclick=\"ShowPopupWin('/doc.php?mode=print&amp;doc=$doc&amp;opt=sf_email'); return false;\">Счёт - фактура по e-mail</div>");
		}
		//			<li><a href='?mode=print&amp;doc=$doc&amp;opt=sf'>Счёт - фактура (HTML)</a></li>
		else if($opt=='tg12')
			$this->PrintTg12($doc);
		else if($opt=='sf')
			$this->PrintSfak($doc);
		else if($opt=='sf_pdf')
			$this->SfakPDF($doc);
		else if($opt=='sf_email')
			$this->SfakEmail($doc);
		else if($opt=='kop')
			$this->PrintKopia($doc);
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
		get_docdata($doc);
		global $tmpl;
		global $uid;
		global $doc_data;
		global $dop_data;
		global $dv;

		$tmpl->LoadTemplate('print');
		$dt=date("d.m.Y",$doc_data[5]);

		$tmpl->AddText("<h1>Накладная N $doc_data[9]$doc_data[10], от $dt </h1>
		<b>Поставщик: </b>".$dv['firm_name']."<br>
		<b>Покупатель: </b>$doc_data[3]<br><br>");

		$tmpl->AddText("
		<table width=800 cellspacing=0 cellpadding=0>
		<tr><th>№</th><th width=450>Наименование<th>Место<th>Кол-во<th>Стоимость<th>Сумма</tr>");
		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`mesto`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='$doc_data[7]'
		WHERE `doc_list_pos`.`doc`='$doc'");
		$i=0;
		$ii=1;
		$sum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f руб.", $nxt[4]);
			$cost2 = sprintf("%01.2f руб.", $sm);
			$tmpl->AddText("<tr align=right><td>$ii</td><td align=left>$nxt[0] $nxt[1] / $nxt[2]<td>$nxt[5]<td>$nxt[3]<td>$cost<td>$cost2");
			$i=1-$i;
			$ii++;
			$sum+=$sm;
		}
		$ii--;
		$cost = sprintf("%01.2f руб.", $sum);

		$tmpl->AddText("</table>
		<p>Всего <b>$ii</b> наименований на сумму <b>$cost</b></p>
		<p class=mini>Товар получил, претензий к качеству товара и внешнему виду не имею.</p>
		<p>Поставщик:_____________________________________</p>
		<p>Покупатель: ____________________________________</p>");

	}
	
	// -- Обычная накладная --------------
	function PrintKopia($doc)
	{
		get_docdata($doc);
		global $tmpl;
		global $uid;
		global $doc_data;
		global $dop_data;
		global $dv;

		$tmpl->LoadTemplate('print');
		$dt=date("d.m.Y",$doc_data[5]);

		$tmpl->AddText("<h1>Копия чека N $doc_data[9]$doc_data[10], от $dt</h1>
		<b>Поставщик: </b>".$dv['firm_name'].", ".$dv['firm_adres'].", ".$dv['firm_telefon']."<br>
		<br /><br />");

		$tmpl->AddText("
		<table width=800 cellspacing=0 cellpadding=0>
		<tr><th>№</th><th width=450>Наименование<th>Кол-во<th>Стоимость<th>Сумма</tr>");
		$res=mysql_query("SELECT `doc_group`.`printname`,`doc_base`.`name`,`doc_base`.`proizv` ,`doc_list_pos`.`cnt`,`doc_list_pos`.`cost`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE `doc_list_pos`.`doc`='$doc'");
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

// -- Накладная торг 12 -------------------
function PrintTg12()
{
	global $tmpl;
	global $uid;
	global $dv;
	$doc=$this->doc;
	$doc_data=$this->doc_data;
	$dop_data=$this->dop_data;

	if(!$doc_data[6])
	{
		doc_menu(0,0);
		$tmpl->AddText("<h1>Реализация</h1>");
		$tmpl->msg("Сначала нужно провести документ!","err");
	}
	else
	{
		$tmpl->LoadTemplate('print_tg12');
		$dt=date("d.m.Y",$doc_data[5]);

		$res=mysql_query("SELECT `doc_agent`.`gruzopol`, `doc_agent`.`fullname`, `doc_agent`.`adres`,  `doc_agent`.`tel`, `doc_agent`.`inn`, `doc_agent`.`okpo`, `doc_agent`.`okevd`, `doc_agent`.`bik`, `doc_agent`.`rs`, `doc_agent`.`ks`, `doc_agent`.`bank`
		FROM `doc_agent` WHERE `doc_agent`.`id`='$doc_data[2]'	");

		if($nx=@mysql_fetch_row($res))
		{
			$dt=date("d.m.Y",$doc_data[5]);

			$rr=mysql_query("SELECT `surname`,`name`,`name2`,`range` FROM `doc_agent_dov`
			WHERE `id`='".$dop_data['dov_agent']."'");
			if($nn=@mysql_fetch_row($rr))
			{
				$dov_agn="$nn[0] $nn[1] $nn[2]";
				$dov_agr=$nn[3];
			}
			else
				$dov_agn=$dov_agr="";
				
			if($doc_data[13])
			{
				$rs=mysql_query("SELECT `doc_list`.`sklad`, `doc_kassa`.`name`, `doc_kassa`.`bik`, `doc_kassa`.`rs` FROM `doc_list` 
				LEFT JOIN `doc_kassa` ON `doc_kassa`.`num`=`doc_list`.`bank` AND `doc_kassa`.`ids`='bank'
				WHERE `doc_list`.`id`='$doc_data[13]'");
				$nnn=mysql_fetch_row($rs);
				$dv['firm_schet']=$nnn[3];
				$dv['firm_bik']=$nnn[2];
				$dv['firm_bank']=$nnn[1];	
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
        <tr><tr><td class=ul>".$dv['firm_gruzootpr'].", тел.".$dv['firm_telefon']."
         счёт ".$dv['firm_schet']." БИК ".$dv['firm_bik'].", банк ".$dv['firm_bank']."
         </td></tr>
        <tr><td class=microc>грузоотправитель, адрес, номер телефона, банковские реквизиты</td></tr>
        <tr><td class=ul>(отсутствует)</td></tr>
        <tr><td class=microc>структурное подразделение</td></tr>
        </table>

        <br>

        <table width=100% cellspacing=0 cellpadding=0 border=0>
        <tr><tr><td width=200>Грузополучатель</td><td class=ul>$nx[0] </td></tr>
        <tr><tr><td width=200>Поставщик</td><td class=ul>{$dv['firm_name']},{$dv['firm_adres']}, ИНН/КПП {$dv['firm_inn']}, кс {$dv['firm_bank_kor_s']}, р/с {$dv['firm_schet']}, бик {$dv['firm_bik']}, в банке {$dv['firm_bank']}</td></tr>
        <tr><tr><td width=200>Плательщик</td><td class=ul>$nx[1], адрес $nx[2], тел. $nx[3], ИНН/КПП $nx[4], ОКПО $nx[5],  ОКВЭД $nx[6], БИК $nx[7], Р/С $nx[8], К/С $nx[9], банк $nx[10]
        <tr><tr><td width=200>Основание</td><td class=ul></td></tr>
        <tr><tr><td width=200></td><td class=microc>договор, заказ-наряд</td></tr>
        </table>

        <br>

        <table class=tn>
        <tr><tr><td class=tl> </td><td class=microc>Номер документа</td><td class=microc>дата составления</td><td  width=300> </td></tr>
        <tr><tr><td  class=tl>ТОВАРНАЯ НАКЛАДНАЯ</td><td class=bc>$doc_data[9]</td><td class=bc>$dt</td><td  width=300> </td></tr>
        </table>


        </td>
        <td class=ht align=right>
        <!--        Kodi                   -->
        <table class=tn>
        <tr><td></td><td width=30></td><td class=rst>Код</td></tr>
        <tr><td align=right colspan=2>Форма по ОКУД</td><td class=rsh>0330212</td></tr>
        <tr><td></td><td align=right>по ОКПО</td><td class=rsm>".$dv['firm_okpo']."</td></tr>
        <tr><td align=right colspan=2>Вид деятельности по ОКДП</td><td class=rsm></td></tr>
        <tr><td></td><td align=right>по ОКПО</td><td class=rsm>$nx[5]</td></tr>
        <tr><td></td><td align=right>по ОКПО</td><td class=rsm>".$dv['firm_okpo']."</td></tr>
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
                $res=mysql_query("SELECT `doc_group`.`printname`,`doc_base`.`name`,`doc_base`.`proizv` ,`doc_list_pos`.`cnt`,`doc_list_pos`.`cost`
                ,`doc_base_dop`.`mass`
                FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		WHERE `doc_list_pos`.`doc`='$doc' ");
                $i=0;
                $ii=0;

                $summass=$sum=$sumnaloga=0;
                $cnt=0;
                $nds=$dv['param_nds']/100;
                $ndsp=$dv['param_nds'];
                while($nxt=mysql_fetch_row($res))
                {
			if($doc_data[12])
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
<td class=ul>".$dv['firm_director']."<td width=20>

<tr>
<td>
<td class=microc>должность<td width=20>
<td class=microc>подпись<td width=20>
<td class=microc>расшифровка подписи<td width=20>

<tr>
<td colspan=2>Главный (старший) бухгалтер
<td width=20>
<td class=ul><td width=20>
<td class=ul>".$dv['firm_buhgalter']."<td width=20>

<tr>
<td><td><td width=20>
<td class=microc>подпись<td width=20>
<td class=microc>расшифровка подписи<td width=20>

<tr>
<td class=fc>Отпуск груза произвёл
<td class=ul>Кладовщик<td width=20>
<td class=ul><td width=20>
<td class=ul>".$dv['firm_kladovshik']."<td width=20>

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
<td class=ul colspan=5>".$dop_data['dov']." от ".$dop_data['dov_data']."
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
		global $CONFIG;
		if(!$email)
			$email=rcv('email');
		
		if($email=='')
		{
			$tmpl->ajax=1;
			get_docdata($doc);
			global $doc_data;
			$res=mysql_query("SELECT `email` FROM `doc_agent` WHERE `id`='$doc_data[2]'");
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

function SfakPDF($doc, $to_str=0)
{
	define('FPDF_FONT_PATH','/var/www/gate/fpdf/font/');
	require('fpdf/fpdf_mysql.php');
	get_docdata($doc);
	global $tmpl;
	global $uid;
	global $doc_data;
	global $dop_data;
	global $dv;
	
	if($coeff==0) $coeff=1;
	if(!$to_str) $tmpl->ajax=1;
	
	$dt=date("d.m.Y",$doc_data[5]);

	$res=mysql_query("SELECT `doc_agent`.`gruzopol`, `doc_agent`.`fullname`, `doc_agent`.`adres`,  `doc_agent`.`tel`, `doc_agent`.`inn` FROM `doc_agent` WHERE `doc_agent`.`id`='$doc_data[2]'	");

	$nx=@mysql_fetch_row($res);	
	if($doc_data[13])
	{
		$rs=@mysql_query("SELECT `id`, `altnum`, `date` FROM `doc_list` WHERE 
		(`p_doc`='$doc' AND (`type`='4' OR `type`='6')) OR
		(`p_doc`='$doc_data[13]' AND (`type`='4' OR `type`='6'))
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
	$str = 'Приложение №1 к Правилам ведения журналов учета полученных и выставленных счетов-фактур, книг покупок и книг продаж при расчетах по налогу на добавленную стоимость, утвержденным постановлением Правительства Российской Федерации от 2 декабря 2000 г. N 914 (в редакции постановлений Правительства Российской Федерации от 15 марта 2001 г. N 189, от 27 июля 2002 г. N 575, от 16 февраля 2004 г. N 84, от 11 мая 2006г. N 283)';
	$str = iconv('UTF-8', 'windows-1251', $str);
	$pdf->MultiCell(0,4,$str,0,'R');
	$pdf->Ln();
	$pdf->SetFont('','',16);
	$step=4;
	$str = iconv('UTF-8', 'windows-1251', "Счёт - фактура N $doc_data[9], от $dt");
	$pdf->Cell(0,8,$str,0,1,'L');
	$pdf->SetFont('Arial','',10);
	$str = iconv('UTF-8', 'windows-1251', "Продавец: ".unhtmlentities($dv['firm_name']));
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "Адрес: ".$dv['firm_adres']);
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "ИНН / КПП продавца: ".$dv['firm_inn']);
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "Грузоотправитель и его адрес: ".unhtmlentities($dv['firm_gruzootpr']));
	$pdf->MultiCell(0,$step,$str,0,'L');
	$str = iconv('UTF-8', 'windows-1251', "Грузополучатель и его адрес: ".unhtmlentities(unhtmlentities($nx[0])));
	$pdf->MultiCell(0,$step,$str,0,'L');
	$str = iconv('UTF-8', 'windows-1251', "К платёжно-расчётному документу № $pp, от $ppdt");
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "Покупатель: ".unhtmlentities($nx[1]));
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "Адрес: ".unhtmlentities($nx[2]).", тел. $nx[3]");
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "ИНН / КПП покупателя: $nx[4]");
	$pdf->Cell(0,$step,$str,0,1,'L');
	$pdf->Ln(3);
	
	$y=$pdf->GetY();
	$pdf->SetLineWidth(0.5);
	$t_width=array(85,17,10,20,28,10,17,18,28,16,0);
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
	
	$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_list_pos`.`sn`, `doc_base_dop`.`strana`  FROM `doc_list_pos`
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
	WHERE `doc_list_pos`.`doc`='$doc'");
	
	$pdf->SetLineWidth(0.2);
	$pdf->SetY($y+16);
	
	$i=0;
	$ii=1;
	$sum=$sumnaloga=0;
	$nds=$dv['param_nds']/100;
	$ndsp=$dv['param_nds'];
	while($nxt=mysql_fetch_row($res))
	{
		if($doc_data[12])
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
	
		$cena = 	sprintf("%01.2f", $cena);
		$stoimost = sprintf("%01.2f", $stoimost);
		$nalog = 	sprintf("%01.2f", $nalog);
		$snalogom = sprintf("%01.2f", $snalogom);
	
		$sum+=$snalogom;
		$sumnaloga+=$nalog;
	
		$y=$pdf->GetY();
		$step=5;
		$pdf->SetFont('','',9);
		$str = iconv('UTF-8', 'windows-1251', "$nxt[0] $nxt[1] / $nxt[2]" );
		$pdf->Cell($t_width[0],$step,$str,1,0,'L',0);
		$str = iconv('UTF-8', 'windows-1251', "шт." );
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
		$pdf->SetFont('','',8);
		$pdf->Cell($t_width[9],$step,$str,1,0,'R',0);
		$pdf->Cell($t_width[10],$step,$nxt[5],1,0,'R',0);
		$pdf->Ln();
	}
	
	if($pdf->h<=($pdf->GetY()+60)) $pdf->AddPage('L');		
	$delta=$pdf->h-($pdf->GetY()+55);
	if($delta>7) $delta=7;		
	//$pdf->Image('img/shtamp_pdf.jpg',4,$pdf->GetY()+$delta, 120);

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
	$str = iconv('UTF-8', 'windows-1251', "Руководитель организации:______________________ /".$dv['firm_director']."/");
	$pdf->Cell(100,$step,$str,0,0,'L',0);
	$str = iconv('UTF-8', 'windows-1251', "Главный бухгалтер: _____________________ /".$dv['firm_buhgalter']."/");
	$pdf->Cell(0,$step,$str,0,0,'R',0);
	
	
	$pdf->Ln(10);
	$pdf->SetFont('','',7);
	$str = iconv('UTF-8', 'windows-1251', "ПРИМЕЧАНИЕ. Первый экземпляр (оригинал) - покупателю, второй экземпляр (копия) - продавцу" );
	$pdf->Cell(0,$step,$str,0,0,'R',0);
	

	$pdf->Ln();

	
	
	if($to_str)
		return $pdf->Output('s_faktura.pdf','S');
	else
		$pdf->Output('s_faktura.pdf','I');
}

};
?>