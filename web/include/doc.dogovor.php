<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2013, BlackLight, TND Team, http://tndproject.org
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


/// Документ *договор*
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
		$this->header_fields			='separator agent cena';
		settype($this->doc,'int');
		$this->PDFForms=array(
			array('name'=>'dog','desc'=>'Договор','method'=>'DogovorPDF')
		);
		if(!$doc)
		{
			$this->doc_data['comment']="
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
## Все споры между сторонами настоящего Договора, по этому договору или в связи с ним, в том числе, касающиеся его существования, действительности, изменения, исполнения, прекращения, в том числе по обязательствам, возникшим по настоящему Договору и договорам, обеспечивающим их исполнение, подлежат рассмотрению в арбитражном суде в соответствии с его регламентом и действующим законодательством. Решение арбитражного суда является окончательным.
# Прочие условия
## Все изменения и дополнения к настоящему договору оформляются в виде дополнительных соглашений, подписываемых уполномоченными представителями сторон.
## Настоящий договор вступает в силу с момента подписания и действует до {{ENDDATE}}. Если за 30-дней до окончания срока действия настоящего договора ни одна из сторон не заявит о его прекращении, действие договора считается продленным на следующий календарный год.
## Окончание срока действия договора влечет за собой прекращение обязательств сторон по нему, но не освобождает стороны от ответственности за его нарушения, если таковые имели место.
# Адреса и реквизиты сторон
{{REKVIZITY}}
";
		}
	}

	function initDefDopdata() {
		$this->def_dop_data = array('name'=>'', 'end_date'=>'', 'debt_control'=>0, 'debt_size'=>0, 'limit'=>0, 'received'=>0);
	}
	
	function DopHead()
	{
		global $tmpl;
		if($this->doc)	$end_date=@$this->dop_data['end_date'];
		else		$end_date=date("Y-12-31");
		$name = $this->dop_data['name'];
		$dchecked = $this->dop_data['debt_control']?'checked':'';
		$debt_size = $this->dop_data['debt_size'];
		$limit = $this->dop_data['limit'];
		$checked = $this->dop_data['received']?'checked':'';
		$tmpl->addContent("
		Отображаемое наименование:<br>
		<input type='text' name='name' value='$name'><br>
		Дата истечения:<br>
		<input type='text' name='end_date' value='$end_date'><br>
		<label><input type='checkbox' name='debt_control' value='1' $dchecked>Контроль задолженности</label><br>
		<input type='text' name='debt_size' value='$debt_size'><br>
		Лимит оборотов по договору:<br>
		<input type='text' name='limit' value='$limit'><br>
		<label><input type='checkbox' name='received' value='1' $checked>Документы подписаны и получены</label><br>");
	}

	function DopSave() {
		$new_data = array(
			'received' => request('received'),
			'end_date' => rcvdate('end_date'),
			'debt_control' => rcvint('debt_control')?'1':'0',
			'debt_size' => rcvint('debt_size'),
			'name' => request('name'),
			'limit' => rcvint('limit'),
			'received' => rcvint('received')?'1':'0'
		);
		$old_data = array_intersect_key($new_data, $this->dop_data);
		
		$log_data='';
		if($this->doc)
		{
			$log_data = getCompareStr($old_data, $new_data);
		}
		$this->setDopDataA($new_data);
		if($log_data)	doc_log("UPDATE {$this->doc_name}", $log_data, 'doc', $this->doc);
	}

	function DopBody() {
		global $tmpl, $wikiparser, $db;
		if($this->dop_data['received'])
			$tmpl->addContent("<br><b>Документы подписаны и получены</b><br>");
		if($this->doc_data['comment'])
		{
			$agent_info = $db->selectRow('doc_agent', $this->doc_data['agent']);

$str="==== Покупатель: {$agent_info['fullname']} ====
{$agent_info['adres']}, тел. {$agent_info['tel']}<br>
ИНН/КПП {$agent_info['inn']}, ОКПО {$agent_info['okpo']}, ОКВЭД {$agent_info['okevd']}<br>
Р/С {$agent_info['rs']}, в банке {$agent_info['bank']}<br>
К/С {$agent_info['ks']}, БИК {$agent_info['bik']}<br>
==== Поставщик: {$this->firm_vars['firm_name']} ====
{$this->firm_vars['firm_adres']}<br>
ИНН/КПП {$this->firm_vars['firm_inn']}<br>
Р/С {$this->firm_vars['firm_schet']}, в банке {$this->firm_vars['firm_bank']}<br>
К/С {$this->firm_vars['firm_bank_kor_s']}, БИК {$this->firm_vars['firm_bik']}";

			$rekv = $wikiparser->parse(html_entity_decode($str,ENT_QUOTES,"UTF-8"));

			$wikiparser->AddVariable('REKVIZITY', $rekv);
			$wikiparser->AddVariable('DOCNUM', $this->doc_data['altnum']);
			$wikiparser->AddVariable('DOCDATE', date("d.m.Y",$this->doc_data['date']));
			$wikiparser->AddVariable('AGENT', $agent_info['fullname']);
			$wikiparser->AddVariable('AGENTDOL', 'директора');
			$wikiparser->AddVariable('AGENTFIO', $agent_info['dir_fio_r']);
			$wikiparser->AddVariable('FIRMNAME', $this->firm_vars['firm_name']);
			$wikiparser->AddVariable('FIRMDIRECTOR', $this->firm_vars['firm_director_r']);
			$wikiparser->AddVariable('ENDDATE', @$this->dop_data['end_date']);
			$text=$wikiparser->parse($this->doc_data['comment'],ENT_QUOTES,"UTF-8");
			$tmpl->addContent("<b>Текст договора (форматирование может отличаться от форматирования при печати):</b> <p>$text</p>");
			$this->doc_data['comment']='';
		}
		else 	$tmpl->addContent("<br><b style='color: #f00'>ВНИМАНИЕ! Текст договора не указан!</b><br>");
	}

	/// Формирование другого документа на основании текущего
	function MorphTo($target_type)
	{
		global $tmpl, $uid, $db;
		$tmpl->ajax=1;
		if($target_type=='') {
			$tmpl->ajax=1;
			$tmpl->addContent("<div onclick=\"window.location='?mode=morphto&amp;doc={$this->doc}&amp;tt=16'\">Спецификация</div>");
		}
		else if($target_type == 16) {
			if(!isAccess('doc_specific','create'))	throw new AccessException();
			$new_doc = new doc_Specific();
			$dd = $new_doc->createFrom($this);
			$this->sentZEvent('morph_specific');
			header("Location: doc.php?mode=body&doc=$dd");		
		}
	}

	function Service($doc) {
		$tmpl->ajax=1;
		$opt=request('opt');
		$pos=rcvint('pos');
		parent::_Service($opt,$pos);
	}

	function DogovorPDF($to_str=0)
	{
		global $CONFIG, $db;
		define('FPDF_FONT_PATH',$CONFIG['site']['location'].'/fpdf/font/');
		require('fpdf/html2pdf.php');

		global $tmpl;
		global $uid;
		global $wikiparser;

		$dt=date("d.m.Y",$this->doc_data['date']);

		if(!$to_str) $tmpl->ajax=1;

		$agent_info = $db->selectRow('doc_agent', $this->doc_data['agent']);

$str="==== Покупатель: {$agent_info['fullname']} ====
{$agent_info['adres']}, тел. {$agent_info['tel']}<br>
ИНН/КПП {$agent_info['inn']}, ОКПО {$agent_info['okpo']}, ОКВЭД {$agent_info['okevd']}<br>
Р/С {$agent_info['rs']}, в банке {$agent_info['bank']}<br>
К/С {$agent_info['ks']}, БИК {$agent_info['bik']}<br>
==== Поставщик: {$this->firm_vars['firm_name']} ====
{$this->firm_vars['firm_adres']}<br>
ИНН/КПП {$this->firm_vars['firm_inn']}<br>
Р/С {$this->firm_vars['firm_schet']}, в банке {$this->firm_vars['firm_bank']}<br>
К/С {$this->firm_vars['firm_bank_kor_s']}, БИК {$this->firm_vars['firm_bik']}";

		$rekv=$wikiparser->parse(html_entity_decode($str,ENT_QUOTES,"UTF-8"));

		$wikiparser->AddVariable('REKVIZITY', $rekv);
		$wikiparser->AddVariable('DOCNUM', $this->doc_data['altnum']);
		$wikiparser->AddVariable('DOCDATE', date("d.m.Y",$this->doc_data['date']));
		$wikiparser->AddVariable('AGENT', $agent_info['fullname']);
		$wikiparser->AddVariable('AGENTDOL', 'директора' );
		$wikiparser->AddVariable('AGENTFIO', $agent_info['dir_fio_r']);
		$wikiparser->AddVariable('FIRMNAME', $this->firm_vars['firm_name']);
		$wikiparser->AddVariable('FIRMDIRECTOR', $this->firm_vars['firm_director_r']);
		$wikiparser->AddVariable('ENDDATE', @$this->dop_data['end_date']);

		$text=$wikiparser->parse($this->doc_data['comment']);
		$pdf=new createPDF($text,'','','','');

		$pdf->run();


		if($to_str)
			return $pdf->Output('dogovor.pdf','S');
		else
			$pdf->Output('dogovor.pdf','I');
	}

};
?>