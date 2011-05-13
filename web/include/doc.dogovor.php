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


$doc_types[14]="Договор";

class doc_Dogovor extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=14;
		$this->doc_name				='dogovor';
		$this->doc_viewname			='Договор';
		$this->sklad_editor_enable		=false;
		$this->header_fields			='agent';
		settype($this->doc,'int');
		if(!$doc)
		{
			$this->doc_data[4]="
= Договор поставки № {{DOCNUM}} =
г. Новосибирск, {{DOCDATE}} .

{{FIRMNAME}}, именуемое в дальнейшем «Поставщик», в лице директора {{FIRMDIRECTOR}}, действующего на основании Устава, с одной стороны и {{AGENT}}, именуемое в дальнейшем «Покупатель», в лице {{AGENTDOL}} {{AGENTFIO}}, действующего на основании Устава, с другой стороны, заключили настоящий договор о нижеследующем:
# Предмет договора
## В соответствии с настоящим договором «Поставщик» обязуется поставить, а «Покупатель» принять и оплатить товар (далее по тексту «Товар»).
## Количество, ассортимент и цена товара указываются в спецификации к договору, которая является неотъемлемой частью настоящего договора, либо в счете, с указанием номера договора и даты его подписания.
# Качество товара
## Качество поставляемого товара должно соответствовать требованиям действующего ГОСТа, Нормативно-технической документации (НТД) на данный товар и подтверждаться соответствующими документами (сертификат качества и пр.) При передаче товара Покупателю Поставщик передает Покупателю документы, подтверждающие надлежащее качество, комплектность товара, а также счет- фактуру. Приемка - передача товара оформляется товарной накладной формы ТОРГ-12.
## Приемка товара по количеству производится в момент отпуска со склада Поставщика на автотранспорт Покупателя путем пересчета тарных (упаковочных) мест и по количеству товара внутри тарных мест согласно количеству, указанному в маркировочном ярлыке. Приемка товара по качеству, комплектности, фактическому количеству Товара внутри тарного (упаковочного) места производится на складе Покупателя. В случае установления Покупателем несоответствия качества, комплектности товара данным, указанным в сопровождающей документации, либо несоответствия количества товара внутри тарных (упаковочных) мест, Покупатель обязан известить Поставщика в течение 48 часов с момента обнаружения несоответствий. При неявке уполномоченного представителя Поставщика для участия в приемке Товара и составлении Акта в течение 48 часов с момента уведомления Покупателем, последний вправе произвести приемку Товара по количеству, качеству, комплектности и составление Акта в одностороннем порядке, либо по своему усмотрению привлечь для участия в приемке и составлении Акта об установленном несоответствии компетентного представителя незаинтересованной организации.
## В случае поставки некачественного, некомплектного товара, несоответствия количества товара, данным, указанным в сопроводительной документации, Поставщик обязуется за свой счет и своими силами, в течение двадцати дней с момента предъявления требований Покупателем, произвести замену на качественный товар, восполнить недостающий, доукомплектовать товар, либо в течение семи дней возвратить произведенную Покупателем оплату.
# Цена и порядок расчетов
## Цена на товар определяется в спецификации, которая является неотъемлемой частью договора, либо в счете, с указанием номера договора и даты его подписания.
## Покупатель производит оплату за Товар в течение 10(десять) рабочих дней с момента передачи его Покупателю, на основании счета- фактуры Поставщика. Оплата производится путем перечисления денежных средств на расчетный счет Поставщика. Датой оплаты считается дата списания денежных средств с расчетного счета Покупателя, согласно отметки банка Покупателя.
## Цена на Товар, согласованная в спецификации (счете), изменению не подлежит.
# Порядок поставки
## Поставка Товара производится в течение трех дней с момента подписания сторонами спецификации к договору, путем отпуска со склада «Поставщика» на автотранспорт «Покупателя» (самовывоз), либо путем доставки автотранспортом Поставщика на склад Покупателя. Иной срок поставки может быть согласован сторонами в спецификации.
## Отгрузка Товара производится в таре, предотвращающей порчу, повреждение товара при его транспортировке и хранении.
## Каждое тарное, упаковочное место должно иметь маркировку, с указанием наименования, даты изготовления товара, ГОСТ, количества товара в упаковочном месте.
# Ответственность сторон
## В случае неисполнения или ненадлежащего исполнения обязательств по договору, стороны несут ответственность в соответствии с действующим законодательством РФ.
## Во всем, что не предусмотрено настоящим договором Стороны руководствуются действующим законодательством РФ.
# Форс-мажор
## Ни одна из Сторон не будет нести ответственности за полное или частичное неисполнение любой из своих обязанностей, если неисполнение будет являться следствием таких обстоятельств как наводнение, пожар, землетрясение, война, военные действия, блокада, забастовки, акты или действия государственных органов, возникшие после заключения договора, которые прямо или косвенно повлияли на исполнение Сторонами своих обязательств по договору. При этом срок исполнения обязательств по настоящему договору соразмерно отодвигается на время действия таких обстоятельств.
# Разрешение споров
## Все споры между сторонами настоящего Договора, по этому договору или в связи с ним, в том числе, касающиеся его существования, действительности, изменения, исполнения, прекращения, в том числе по обязательствам, возникшим по настоящему Договору и договорам, обеспечивающим их исполнение, подлежат рассмотрению в Сибирском третейском суде (г. Новосибирск) в соответствии с его регламентом и действующим законодательством. Решение Сибирского третейского суда (г. Новосибирск) является окончательным.
# Прочие условия
## Все изменения и дополнения к настоящему договору оформляются в виде дополнительных соглашений, подписываемых уполномоченными представителями сторон.
## Настоящий договор вступает в силу с момента подписания и действует до 31 декабря 2011 г. Если за 30-дней до окончания срока действия настоящего договора ни одна из сторон не заявит о его прекращении, действие договора считается продленным на следующий календарный год.
## Окончание срока действия договора влечет за собой прекращение обязательств сторон по нему, но не освобождает стороны от ответственности за его нарушения, если таковые имели место.
# Адреса и реквизиты сторон
{{REKVIZITY}}
";
		}
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
		global $wikiparser;
		if($this->dop_data['received'])
			$tmpl->AddText("<br><b>Документы подписаны и получены</b><br>");
		if($this->doc_data[4])
		{
		$res=mysql_query("SELECT `doc_agent`.`gruzopol`, `doc_agent`.`fullname`, `doc_agent`.`adres`,  `doc_agent`.`tel`, `doc_agent`.`inn`, `doc_agent`.`okpo`, `doc_agent`.`okevd`, `doc_agent`.`bik`, `doc_agent`.`rs`, `doc_agent`.`ks`, `doc_agent`.`bank`, `doc_agent`.`dir_fio`, `doc_agent`.`dir_fio_r`
		FROM `doc_agent` WHERE `doc_agent`.`id`='{$this->doc_data[2]}'	");
		if(mysql_errno())		throw new MysqlException("Невозможно получить данные агента!");
		
		$agent_info=mysql_fetch_array($res);
		
$str="==== Покупатель: {$agent_info[1]} ====
$agent_info[2], тел. $agent_info[3]<br>
ИНН/КПП $agent_info[4], ОКПО $agent_info[5], ОКВЭД $agent_info[6]<br>
Р/С $agent_info[8], в банке $agent_info[10]<br>
К/С $agent_info[9], БИК $agent_info[7]<br>
==== Поставщик: {$this->firm_vars['firm_name']} ====
{$this->firm_vars['firm_adres']}<br>
ИНН/КПП {$this->firm_vars['firm_inn']}<br>
Р/С {$this->firm_vars['firm_schet']}, в банке {$this->firm_vars['firm_bank']}<br>
К/С {$this->firm_vars['firm_bank_kor_s']}, БИК {$this->firm_vars['firm_bik']}";
	
			$rekv=$wikiparser->parse(html_entity_decode($str,ENT_QUOTES,"UTF-8"));
	
			$wikiparser->AddVariable('REKVIZITY', $rekv);
			$wikiparser->AddVariable('DOCNUM', $this->doc_data[9]);
			$wikiparser->AddVariable('DOCDATE', date("d.m.Y",$this->doc_data[5]));
			$wikiparser->AddVariable('AGENT', $agent_info[1]);
			$wikiparser->AddVariable('AGENTDOL', 'директора');
			$wikiparser->AddVariable('AGENTFIO', $agent_info['dir_fio_r']);
			$wikiparser->AddVariable('FIRMNAME', unhtmlentities($this->firm_vars['firm_name']));
			$wikiparser->AddVariable('FIRMDIRECTOR', unhtmlentities($this->firm_vars['firm_director_r']));
			$text=$wikiparser->parse(html_entity_decode($this->doc_data[4],ENT_QUOTES,"UTF-8"));
			$tmpl->AddText("<b>Текст договора (форматирование может отличаться от форматирования при печати):</b> <p>$text</p>");
			$this->doc_data[4]='';
		}
		else 	$tmpl->AddText("<br><b style='color: #f00'>ВНИМАНИЕ! Текст договора не указан!</b><br>");	
	}

	function DocApply($silent=0)
	{
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)			throw new MysqlException('Ошибка выборки данных документа при проведении!');
		$nx=@mysql_fetch_row($res);
		if(!$nx)			throw new Exception('Документ не найден!');
		if( $nx[1] && (!$silent) )	throw new Exception('Документ уже был проведён!');
		if($silent)	return;
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if(!$res)			throw new MysqlException('Ошибка установки даты проведения документа!');	
	}

	function DocCancel($doc)
	{
		global $uid;
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");		
		if(!$res)				throw new MysqlException('Ошибка выборки данных документа!');
		if(! ($nx=@mysql_fetch_row($res)))	throw new Exception('Документ не найден!');
		if(! $nx[4])				throw new Exception('Документ НЕ проведён!');
		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка установки флага!');
	}
	
	function PrintForm($doc, $opt='')
	{
		if($opt=='')
		{
			global $tmpl;
			$tmpl->ajax=1;
			$tmpl->AddText("
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=pdf'\">Договор PDF</div>
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=html'\">Договор HTML</div>
			");
		}
		else if($opt=='pdf')
			$this->DogovorPDF();
		else if($opt=='html')
			$this->DogovorHTML();
		else $tmpl->logger("Запрошена неизвестная опция!");
	}

	// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		get_docdata($doc);
		global $tmpl;
		global $uid;
		global $doc_data;
		$tmpl->ajax=1;
		if($target_type=='')
		{
			$tmpl->ajax=1;
			$tmpl->AddText("<div onclick=\"window.location='?mode=morphto&amp;doc={$this->doc}&amp;tt=16'\">Спецификация</div>");
		}
		else if($target_type==16)
		{
			mysql_query("START TRANSACTION");
			$tm=time();
			$altnum=GetNextAltNum($target_type ,$this->doc_data[10]);
			$res=mysql_query("INSERT INTO `doc_list`
			(`type`, `agent`, `date`, `kassa`, `user`, `altnum`, `subtype`, `p_doc`, `sum`, `firm_id`)
			VALUES ('$target_type', '{$this->doc_data[2]}', '$tm', '1', '$uid', '$altnum', '{$this->doc_data[10]}', '{$this->doc}', '0', '{$this->doc_data[17]}')");
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
	}

	// Выполнить удаление документа. Если есть зависимости - удаление не производится.
	function DelExec($doc)
	{
		$res=mysql_query("SELECT `ok` FROM `doc_list` WHERE `id`='{$this->doc}'");
		if(!mysql_result($res,0,0)) // Если проведён - нельзя удалять
		{
			$res=mysql_query("SELECT `id`, `mark_del` FROM `doc_list` WHERE `p_doc`='{$this->doc}'");
			if(!mysql_num_rows($res)) // Если есть потомки - нельзя удалять
			{
				mysql_query("DELETE FORM `doc_list_pos` WHERE `doc`='{$this->doc}'");
				mysql_query("DELETE FROM `doc_dopdata` WHERE `doc`='{$this->doc}'");
				mysql_query("DELETE FROM `doc_list` WHERE `id`='{$this->doc}'");
				return 0;
			}
		}
		return 1;
   	}

//	================== Функции только этого класса ======================================================

	function DogovorHTML($to_str=0)
	{
		global $CONFIG, $tmpl;

		$tmpl->LoadTemplate('print');
		global $wikiparser;
		$rekv=$wikiparser->parse(html_entity_decode("''Поставщик: {$this->doc_data[3]}''",ENT_QUOTES,"UTF-8"));
		$wikiparser->AddVariable('REKVIZITY', $rekv);
		
		$text=$wikiparser->parse(html_entity_decode($this->doc_data[4],ENT_QUOTES,"UTF-8"));
		$tmpl->AddText("$text");
	}	

	function DogovorPDF($to_str=0)
	{
		global $CONFIG;
		define('FPDF_FONT_PATH',$CONFIG['site']['location'].'/fpdf/font/');
		require('fpdf/html2pdf.php');
		
		global $tmpl;
		global $uid;
		global $wikiparser;
		
		$dt=date("d.m.Y",$this->doc_data[5]);
		
		if($coeff==0) $coeff=1;
		if(!$to_str) $tmpl->ajax=1;
		
// 		$pdf=new FPDF('P');
// 		$pdf->Open();
// 		$pdf->SetAutoPageBreak(1,12);
// 		$pdf->AddFont('Arial','','arial.php');
// 		$pdf->tMargin=10;
// 		$pdf->AddPage();
// 
// 		$pdf->SetFont('Arial','',16);
// 		$str = iconv('UTF-8', 'windows-1251', "Договор N {$this->doc_data[9]}");
// 		$pdf->Cell(0,6,$str,0,1,'C',0);
// 		
// 		$pdf->SetFont('Arial','',12);
// 		$str = iconv('UTF-8', 'windows-1251', $this->doc_data[4]);	
// 		$pdf->Write(4,$str,'');

		$res=mysql_query("SELECT `doc_agent`.`gruzopol`, `doc_agent`.`fullname`, `doc_agent`.`adres`,  `doc_agent`.`tel`, `doc_agent`.`inn`, `doc_agent`.`okpo`, `doc_agent`.`okevd`, `doc_agent`.`bik`, `doc_agent`.`rs`, `doc_agent`.`ks`, `doc_agent`.`bank`, `doc_agent`.`dir_fio`, `doc_agent`.`dir_fio_r`
		FROM `doc_agent` WHERE `doc_agent`.`id`='{$this->doc_data[2]}'	");
		if(mysql_errno())		throw new MysqlException("Невозможно получить данные агента!");
		
		$agent_info=mysql_fetch_array($res);
		
$str="==== Покупатель: {$agent_info[1]} ====
$agent_info[2], тел. $agent_info[3]<br>
ИНН/КПП $agent_info[4], ОКПО $agent_info[5], ОКВЭД $agent_info[6]<br>
Р/С $agent_info[8], в банке $agent_info[10]<br>
К/С $agent_info[9], БИК $agent_info[7]<br>
От покупателя: _____________________________ ( {$agent_info['dir_fio']} )<br>
==== Поставщик: {$this->firm_vars['firm_name']} ====
{$this->firm_vars['firm_adres']}<br>
ИНН/КПП {$this->firm_vars['firm_inn']}<br>
Р/С {$this->firm_vars['firm_schet']}, в банке {$this->firm_vars['firm_bank']}<br>
К/С {$this->firm_vars['firm_bank_kor_s']}, БИК {$this->firm_vars['firm_bik']}<br>
От поставщика: _____________________________ ( ".unhtmlentities($this->firm_vars['firm_director']).")<br>";

		$rekv=$wikiparser->parse(html_entity_decode($str,ENT_QUOTES,"UTF-8"));

		$wikiparser->AddVariable('REKVIZITY', $rekv);
		$wikiparser->AddVariable('DOCNUM', $this->doc_data[9]);
		$wikiparser->AddVariable('DOCDATE', date("d.m.Y",$this->doc_data[5]));
		$wikiparser->AddVariable('AGENT', $agent_info[1]);
		$wikiparser->AddVariable('AGENTDOL', 'директора' );
		$wikiparser->AddVariable('AGENTFIO', $agent_info['dir_fio_r']);
		$wikiparser->AddVariable('FIRMNAME', unhtmlentities($this->firm_vars['firm_name']));
		$wikiparser->AddVariable('FIRMDIRECTOR', unhtmlentities($this->firm_vars['firm_director_r']));

		$text=$wikiparser->parse(html_entity_decode($this->doc_data[4],ENT_QUOTES,"UTF-8"));
		$pdf=new createPDF($text,'','','','');

		$pdf->run();

		
		if($to_str)
			return $pdf->Output('dogovor.pdf','S');
		else
			$pdf->Output('dogovor.pdf','I');
	}

};
?>