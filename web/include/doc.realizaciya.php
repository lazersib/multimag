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


/// Документ *Реализация*
class doc_Realizaciya extends doc_Nulltype
{
	var $status_list;

	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				= 2;
		$this->doc_name				= 'realizaciya';
		$this->doc_viewname			= 'Реализация товара';
		$this->sklad_editor_enable		= true;
		$this->sklad_modify			= -1;
		$this->header_fields			= 'sklad cena separator agent';
		$this->dop_menu_buttons			= "<a href='' onclick=\"ShowPopupWin('/doc.php?mode=srv&amp;opt=dov&amp;doc=$doc'); return false;\" title='Доверенное лицо'><img src='img/i_users.png' alt='users'></a>";
		$this->PDFForms				= array(
			array('name'=>'nak','desc'=>'Накладная','method'=>'PrintNaklPDF'),
			array('name'=>'tc','desc'=>'Товарный чек','method'=>'PrintTcPDF'),
			array('name'=>'tg12','desc'=>'Накладная ТОРГ-12','method'=>'PrintTg12PDF'),
			array('name'=>'nak_kompl','desc'=>'Накладная на комплектацию','method'=>'PrintNaklKomplektPDF'),
			array('name'=>'sfak','desc'=>'Счёт - фактура','method'=>'SfakPDF'),
			array('name'=>'sfak2010','desc'=>'Счёт - фактура 2010','method'=>'Sfak2010PDF'),
			array('name'=>'nacenki','desc'=>'Наценки','method'=>'Nacenki')		    
		);
		$this->status_list			= array('in_process'=>'В процессе', 'ok'=>'Готов к отгрузке', 'err'=>'Ошибочный');
	}
	
	function initDefDopdata() {
		$this->def_dop_data = array('platelshik'=>0, 'gruzop'=>0, 'status'=>'', 'kladovshik'=>0,
			'mest'=>'', 'received'=>0, 'return'=>0, 'cena'=>0, 'dov_agent'=>0, 'dov'=>'', 'dov_data'=>'');
	}

	// Создать документ с товарными остатками на основе другого документа
	public function createFromP($doc_obj) {
		parent::CreateFromP($doc_obj);
		$this->setDopData('platelshik', $doc_obj->doc_data['agent']);
		$this->setDopData('gruzop', $doc_obj->doc_data['agent']);
		unset($this->doc_data);
		$this->get_docdata();
		return $this->doc;
	}

	function DopHead()
	{
		global $tmpl, $db;

		$cur_agent = $this->doc_data['agent'];
		if(!$cur_agent)		$cur_agent=1;
		$klad_id=@$this->dop_data['kladovshik'];
		if(!$klad_id)	$klad_id=$this->firm_vars['firm_kladovshik_id'];

		$plat_data = $db->selectRow('doc_agent', $this->dop_data['platelshik']);
		$plat_name = $plat_data?html_out($plat_data['name']):'';
		
		$gruzop_data = $db->selectRow('doc_agent', $this->dop_data['gruzop']);
		$gruzop_name = $gruzop_data?html_out($gruzop_data['name']):'';
		
		$tmpl->addContent("<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		Плательщик:<br>
		<input type='hidden' name='plat_id' id='plat_id' value='{$this->dop_data['platelshik']}'>
		<input type='text' id='plat'  style='width: 100%;' value='$plat_name'><br>
		Грузополучатель:<br>
		<input type='hidden' name='gruzop_id' id='gruzop_id' value='{$this->dop_data['gruzop']}'>
		<input type='text' id='gruzop'  style='width: 100%;' value='$gruzop_name'><br>
		Кладовщик:<br><select name='kladovshik'>
		<option value='0'>--не выбран--</option>");
		
		$res = $db->query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
		while($nxt = $res->fetch_row())
		{
			$s=($klad_id==$nxt[0])?'selected':'';
			$tmpl->addContent("<option value='$nxt[0]' $s>".html_out($nxt[1])."</option>");
		}
		$tmpl->addContent("</select><br>
		Количество мест:<br>
		<input type='text' name='mest' value='{$this->dop_data['mest']}'><br>

		<br><hr>
		Статус:<br>
		<select name='status'>");
		if($this->dop_data['status']=='')	$tmpl->addContent("<option value=''>Не задан</option>");
		foreach($this->status_list as $id => $name)
		{
			$s=(@$this->dop_data['status']==$id)?'selected':'';
			$tmpl->addContent("<option value='$id' $s>".html_out($name)."</option>");
		}

		$tmpl->addContent("</select><br>

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
		
		$checked_r = $this->dop_data['received']?'checked':'';
		$tmpl->addContent("<label><input type='checkbox' name='received' value='1' $checked_r>Документы подписаны и получены</label><br>");
		$checked = $this->dop_data['return']?'checked':'';
		$tmpl->addContent("<label><input type='checkbox' name='return' value='1' $checked>Возвратный документ</label><br>");
	}

	function DopSave()
	{
		$new_data = array(
			'status' => request('status'),
			'kladovshik' => rcvint('kladovshik'),
			'platelshik' => rcvint('plat_id'),
			'gruzop' => rcvint('gruzop_id'),
			'received' => request('received')?'1':'0',
			'return' => request('return')?'1':'0',
			'mest' => rcvint('mest')
		);
		$old_data = array_intersect_key($new_data, $this->dop_data);
		
		$log_data='';
		if($this->doc)
		{
			$log_data = getCompareStr($old_data, $new_data);
			if(@$old_data['status'] != $new_data['status'])
				$this->sentZEvent('cstatus:'.$new_data['status']);
		}
		$this->setDopDataA($new_data);
		if($log_data)	doc_log("UPDATE {$this->doc_name}", $log_data, 'doc', $this->doc);
	}

	function DopBody()
	{
		global $tmpl;
		if($this->dop_data['received'])
			$tmpl->addContent("<br><b>Документы подписаны и получены</b><br>");
	}

	function DocApply($silent=0)
	{
		global $CONFIG, $db;
		$tim = time();
		$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_sklady`.`dnc`
		FROM `doc_list`
		LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
		WHERE `doc_list`.`id`='{$this->doc}'");
		$nx = $res->fetch_assoc();
		$res->free();
		if( $nx['ok'] && ( !$silent) )
			throw new Exception('Документ уже был проведён!');

		if(!@$this->dop_data['kladovshik'] && @$CONFIG['doc']['require_storekeeper'] && !$silent)	throw new Exception("Кладовщик не выбран!");
		if(!@$this->dop_data['mest'] && @$CONFIG['doc']['require_pack_count'] && !$silent)	throw new Exception("Количество мест не задано");
		$res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`pos_type`, `doc_list_pos`.`id`, `doc_base`.`vc`, `doc_list_pos`.`cost`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$nx['sklad']}'
		WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_base`.`pos_type`='0'");
		$bonus=0;
		while($nxt = $res->fetch_row())
		{
			if(!$nx['dnc'])
			{
				if($nxt[1]>$nxt[2])	throw new Exception("Недостаточно ($nxt[1]) товара '$nxt[3]:$nxt[4] - $nxt[7]($nxt[0])': на складе только $nxt[2] шт!");
			}
			$db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='{$nx['sklad']}'");

			if(!$nx['dnc'] && (!$silent))
			{
				$budet = getStoreCntOnDate($nxt[0], $nx['sklad']);
				if( $budet<0)		throw new Exception("Невозможно ($silent), т.к. будет недостаточно ($budet) товара '$nxt[3]:$nxt[4] - $nxt[7]($nxt[0])'!");
			}

			if(@$CONFIG['poseditor']['sn_restrict'])
			{
				$r = $db->query("SELECT COUNT(`doc_list_sn`.`id`) FROM `doc_list_sn` WHERE `rasx_list_pos`='$nxt[6]'");
				list($sn_cnt) = $r->fetch_row();
				if($sn_cnt!=$nxt[1])	throw new Exception("Количество серийных номеров товара $nxt[0] ($nxt[1]) не соответствует количеству серийных номеров ($sn_cnt)");
			}
			$bonus+=$nxt[8]*$nxt[1]*(@$CONFIG['bonus']['coeff']);
		}

// 		if(@$CONFIG['bonus']['enable'] && $bonus>0)
// 		{
// 			mysql _query("UPDATE `doc_agent` SET `bonus`='$bonus' WHERE `id`='{$this->doc}'");
// 			if(mysql _errno())				throw new MysqlException('Ошибка проведения, ошибка начисления бонусного вознаграждения');
// 		}

		if($silent)	return;
		$db->query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES ( '{$this->doc}' ,'bonus','$bonus')");
		$db->query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		$this->sentZEvent('apply');
	}

	function DocCancel()
	{
		global $db;

		$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res->num_rows)			throw new Exception('Документ не найден!');
		$nx = $res->fetch_row();
		if(! $nx[4])				throw new Exception('Документ НЕ проведён!');

		$res = $db->query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$this->doc}' AND `ok`>'0'");
		if($res->num_rows)		throw new Exception('Нельзя отменять документ с проведёнными подчинёнными документами.');

		$db->query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		$res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base`.`pos_type` FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`	WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_base`.`pos_type`='0'");

		while($nxt = $res->fetch_row())
			$db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`+'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nx[3]'");
		$this->sentZEvent('cancel');
	}

	// Формирование другого документа на основании текущего
	function MorphTo($target_type)
	{
		global $tmpl, $db;
	
		if($target_type=='')
		{
			$tmpl->ajax=1;
			$tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=6'\">Приходный кассовый ордер</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=4'\">Приход средств в банк</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=18'\">Корректировка долга</div>");
			if(!$this->doc_data['p_doc'])	$tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->doc}&amp;tt=1'\">Заявка (родительская)</div>");
		}
		else if($target_type=='1')
		{
			$new_doc=new doc_Zayavka();
			$dd=$new_doc->CreateParent($this);
			$new_doc->setDopData('cena',$this->dop_data['cena']);
			$this->setDocData('p_doc',$dd);
			header("Location: doc.php?mode=body&doc=$dd");
		}
		else if($target_type==6)
		{
			if(!isAccess('doc_pko','create'))	throw new AccessException("");
			$sum = $this->recalcSum();
			$db->startTransaction();			
			$new_doc = new doc_Pko();
			$dd = $new_doc->createFrom($this);
			$new_doc->setDocData('kassa', 1);
			$db->commit();
			$ref="Location: doc.php?mode=body&doc=".$dd;
			header($ref);
		}
		else if($target_type==4)
		{
			if(!isAccess('doc_pbank','create'))	throw new AccessException("");
			$sum = $this->recalcSum();
			$db->startTransaction();			
			$new_doc = new doc_Pko();
			$dd = $new_doc->createFrom($this);
			$new_doc->setDocData('kassa', 1);
			$db->commit();
			$ref="Location: doc.php?mode=body&doc=".$dd;
			header($ref);
		}
		else if($target_type==18)
		{
			$new_doc=new doc_Kordolga();
			$dd=$new_doc->createFrom($this);
			$new_doc->setDocData('sum', $this->doc_data['sum']*(-1));
			header("Location: doc.php?mode=body&doc=$dd");
		}
		else
		{
			$tmpl->msg("В разработке","info");
		}
	}

	function Service() {
		global $tmpl, $db;

		$tmpl->ajax=1;
		$opt = request('opt');
		$pos = request('pos');

		
		if(parent::_Service($opt,$pos))	{}
		else if($opt=='dov')
		{
			$res = $db->selectRowA('doc_agent_dov', $this->dop_data['dov_agent'], array('name', 'surname'));
			$agn = $res?html_out($res['name'].' ' .$res['surname']):'';

			$tmpl->addContent("<form method='post' action=''>
<input type=hidden name='mode' value='srv'>
<input type=hidden name='opt' value='dovs'>
<input type=hidden name='doc' value='{$this->doc}'>
<table>
<tr><th>Доверенное лицо (<a href='docs.php?l=dov&mode=edit&ag_id={$this->doc_data['agent']}' title='Добавить'><img border=0 src='img/i_add.png' alt='add'></a>)
<tr><td><input type=hidden name=dov_agent value='".$this->dop_data['dov_agent']."' id='sid' ><input type=text id='sdata' value='$agn' onkeydown=\"return RequestData('/docs.php?l=dov&mode=srv&opt=popup&ag={$this->doc_data['agent']}')\">
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
			if(!isAccess('doc_'.$this->doc_name, 'edit'))	throw new AccessException();
			$this->setDopData('dov', request('dov'));
			$this->setDopData('dov_agent', request('dov_agent'));
			$this->setDopData('dov_data', request('dov_data'));
			$ref="Location: doc.php?mode=body&doc={$this->doc}";
			header($ref);
			doc_log("UPDATE","dov:$dov, dov_agent:$dov_agent, dov_data:$dov_data",'doc', $this->doc);
		}
		else $tmpl->msg("Неизвестная опция $opt!");
		
	}
//	================== Функции только этого класса ======================================================

/// Обычная накладная в PDF формате
/// @param to_str Вернуть строку, содержащую данные документа (в противном случае - отправить файлом)
	function PrintNaklPDF($to_str=false)
	{
		require('fpdf/fpdf_mc.php');
		global $tmpl, $CONFIG, $db;

		if(!$to_str) $tmpl->ajax=1;

		$pdf=new PDF_MC_Table('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(0,10);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=10;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		$dt = date("d.m.Y", $this->doc_data['date']);

		$res = $db->query("SELECT `id` FROM `doc_cost` WHERE `vid`='1'");
		if(!$res->num_rows)			throw new Exception ("Цена по умолчанию не найдена");
		$cost_row = $res->fetch_row();
		$def_cost = $cost_row[0];

		$pdf->SetFont('','',16);
		$str="Накладная N {$this->doc_data['altnum']}{$this->doc_data['subtype']} ({$this->doc}), от $dt";
		$pdf->CellIconv(0,8,$str,0,1,'C',0);
		$pdf->SetFont('','',10);
		$str="Поставщик: {$this->firm_vars['firm_name']}, тел: {$this->firm_vars['firm_telefon']}";
		$pdf->MultiCellIconv(0,5,$str,0,'L',0);
		$str="Покупатель: {$this->doc_data['agent_fullname']}";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);
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

		foreach($t_width as $id=>$w) {
			$pdf->CellIconv($w,6,$t_text[$id],1,0,'C',0);
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

		$res = $db->query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`mesto`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`id`, `doc_base`.`vc`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data['sklad']}'
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$ii=1;
		$sum=0;
		$skid_sum=0;
		while($nxt = $res->fetch_row())
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
			$skid_sum+=getCostPos($nxt[7], $def_cost)*$nxt[3];
		}
		$ii--;
		$cost = sprintf("%01.2f руб.", $sum);

		$prop='';
		if($sum>0)
		{
			$add='';
			if($nxt[12]) $add=" OR (`p_doc`='{$this->doc_data['p_doc']}' AND (`type`='4' OR `type`='6'))";
			$rs = $db->query("SELECT SUM(`sum`) FROM `doc_list` WHERE
			(`p_doc`='{$this->doc}' AND (`type`='4' OR `type`='6'))
			$add
			AND `ok`>0 AND `p_doc`!='0' GROUP BY `p_doc`");
			if($rs->num_rows)
			{
				$prop_data = $rs->fetch_row();
				$prop = sprintf("Оплачено: %0.2f руб.",$prop_data[0]);
			}
		}
		$pdf->Ln();

		$str="Всего $ii наименований на сумму $cost";
		if($this->dop_data['mest'])	$str.=", мест: ".$this->dop_data['mest'];
		$pdf->CellIconv(0,5,$str,0,1,'L',0);

		if($sum!=$skid_sum)
		{
			$cost = sprintf("%01.2f руб.", $skid_sum-$sum);
			$str="Скидка: $cost";
			$pdf->CellIconv(0,5,$str,0,1,'L',0);
		}

		if($prop)
		{
			$pdf->CellIconv(0,5,$prop,0,1,'L',0);
		}

		$str="Поставщик:_____________________________________";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);
		$str="Товар получил, претензий к качеству товара и внешнему виду не имею.";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);
		$str="Покупатель: ____________________________________";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);
		

		if($to_str)	return $pdf->Output('blading.pdf','S');
		else		$pdf->Output('blading.pdf','I');
	}

/// Товарный чек в PDF формате
/// @param to_str Вернуть строку, содержащую данные документа (в противном случае - отправить файлом)
	function PrintTcPDF($to_str=false) {
		require('fpdf/fpdf_mc.php');
		global $tmpl, $CONFIG, $db;

		if(!$to_str) $tmpl->ajax=1;

		$pdf=new PDF_MC_Table('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(0,10);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=10;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		$dt=date("d.m.Y",$this->doc_data['date']);

		$res = $db->query("SELECT `id` FROM `doc_cost` WHERE `vid`='1'");
		if(!$res->num_rows)			throw new Exception ("Цена по умолчанию не найдена");
		$cost_row = $res->fetch_row();
		$def_cost = $cost_row[0];

		$pdf->SetFont('','',16);
		$str="Товарный чек N {$this->doc_data['altnum']}, от $dt";
		$pdf->CellIconv(0,8,$str,0,1,'C',0);
		$pdf->SetFont('','',10);
		$str="Продавец: {$this->firm_vars['firm_name']}, ИНН-{$this->firm_vars['firm_inn']}-КПП, тел: {$this->firm_vars['firm_telefon']}";
		$pdf->MultiCellIconv(0,5,$str,0,'L',0);
		$str="Покупатель: {$this->doc_data['agent_name']}";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);
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
			$pdf->CellIconv($w,6,$t_text[$id],1,0,'C',0);
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

		$res = $db->query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`mesto`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`id`, `doc_base`.`vc`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data['sklad']}'
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$ii=1;
		$sum=0;
		$skid_sum=0;
		while($nxt = $res->fetch_row())
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f руб.", $nxt[4]);
			$cost2 = sprintf("%01.2f руб.", $sm);
			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];

			$row=array($ii);
			if(@$CONFIG['poseditor']['vc'])
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
			$skid_sum+=getCostPos($nxt[7], $def_cost)*$nxt[3];
		}
		$ii--;
		$cost = sprintf("%01.2f руб.", $sum);

		$prop='';
		if($sum>0)
		{
			$add='';
			if($nxt[12]) $add=" OR (`p_doc`='{$this->doc_data['p_doc']}' AND (`type`='4' OR `type`='6'))";
			$rs = $db->query("SELECT SUM(`sum`) FROM `doc_list` WHERE
			(`p_doc`='{$this->doc}' AND (`type`='4' OR `type`='6'))
			$add
			AND `ok`>0 AND `p_doc`!='0' GROUP BY `p_doc`");
			if($rs->num_rows){
				$prop_data = $rs->fetch_row();
				$prop = sprintf("Оплачено: %0.2f руб.",$prop_data[0]);
			}
		}
		$pdf->Ln();

		$str="Всего $ii наименований на сумму $cost";
		if($this->dop_data['mest'])	$str.=", мест: ".$this->dop_data['mest'];
		$pdf->CellIconv(0,5,$str,0,1,'L',0);

		if($sum!=$skid_sum)
		{
			$cost = sprintf("%01.2f руб.", $skid_sum-$sum);
			$str="Скидка: $cost";
			$pdf->CellIconv(0,5,$str,0,1,'L',0);
		}

		if($prop)
		{
			$pdf->CellIconv(0,5,$prop,0,1,'L',0);
		}


		$str="Продавец:_____________________________________";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);

		if($to_str)	return $pdf->Output('tc.pdf','S');
		else		$pdf->Output('tc.pdf','I');
	}

/// Накладная на комплектацию в PDF формате
/// @param to_str Вернуть строку, содержащую данные документа (в противном случае - отправить файлом)
	function PrintNaklKomplektPDF($to_str=false)
	{
		require('fpdf/fpdf_mc.php');
		global $tmpl, $CONFIG, $db;

		if(!$to_str) $tmpl->ajax=1;

		$pdf=new PDF_MC_Table('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(0,10);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=10;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		$dt=date("d.m.Y",$this->doc_data['date']);

		$res = $db->query("SELECT `id` FROM `doc_cost` WHERE `vid`='1'");
		if(!$res->num_rows)			throw new Exception ("Цена по умолчанию не найдена");
		$cost_row = $res->fetch_row();
		$def_cost = $cost_row[0];

		$pdf->SetFont('','',16);
		$str="Накладная на комплектацию N {$this->doc_data['altnum']}{$this->doc_data['subtype']}, от $dt";
		$pdf->CellIconv(0,8,$str,0,1,'C',0);
		$pdf->SetFont('','',10);
		$str="К накладной N {$this->doc_data['altnum']}{$this->doc_data['subtype']} ({$this->doc})";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);
		$str="Поставщик: {$this->firm_vars['firm_name']}";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);
		$str="Покупатель: {$this->doc_data['agent_name']}";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);
		$pdf->Ln();

		$pdf->SetLineWidth(0.5);
		$t_width=array(8);
		if($CONFIG['poseditor']['vc'])
		{
			$t_width[]=20;
			$t_width[]=72;
		}
		else	$t_width[]=92;
		$t_width=array_merge($t_width, array(17,17,15,13,14,16));

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
			$pdf->CellIconv($w,6,$t_text[$id],1,0,'C',0);
		}
		$pdf->Ln();
		$pdf->SetWidths($t_width);
		$pdf->SetHeight(5);

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

		$res = $db->query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_base_dop`.`mass`, `doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt` AS `base_cnt`, `doc_list_pos`.`tovar`, `doc_list_pos`.`cost`, `doc_base`.`vc`, `class_unit`.`rus_name1` AS `units`, `doc_list_pos`.`comm`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data['sklad']}'
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$ii=1;
		$sum=0;
		$summass=0;
		while($nxt = $res->fetch_assoc())
		{
			$sm=$nxt['cnt']*$nxt['cost'];
			$cost = sprintf("%01.2f руб.", $nxt['cost']);
			$cost2 = sprintf("%01.2f руб.", $sm);
			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt['proizv'])	$nxt['name'].=' / '.$nxt['proizv'];
			$summass+=$nxt['cnt']*$nxt['mass'];

			$row=array($ii);
			$rowc=array('');
			if($CONFIG['poseditor']['vc'])
			{
				$row[]=$nxt['vc'];
				$row[]="{$nxt['printname']} {$nxt['name']}";
				$rowc[]='';
				$rowc[]=$nxt['comm'];
			}
			else
			{
				$row[]="{$nxt['printname']} {$nxt['name']}";
				$rowc[]=$nxt['comm'];
			}

			$mass=sprintf("%0.3f",$nxt['mass']);
			$rezerv=DocRezerv($nxt['tovar'],$this->doc);

			$row=array_merge($row, array($nxt['cost'], "{$nxt['cnt']} {$nxt['units']}", $nxt['base_cnt'], $rezerv, $mass, $nxt['mesto']));
			$rowc=array_merge($rowc, array('', '', '', '', '', ''));
			$pdf->RowIconvCommented($row,$rowc);
			$i=1-$i;
			$ii++;
			$sum+=$sm;
		}
		$ii--;
		$cost = sprintf("%01.2f руб.", $sum);

		$mass_p=num2str($summass,'kg',3);
		$summass = sprintf("%01.3f", $summass);
		
		$res_uid = $db->query("SELECT `worker_real_name` FROM `users_worker_info`
			WHERE `user_id`='".$_SESSION['uid']."'");
		if($res_uid->num_rows) {
			$line = $res_uid->fetch_row();
			$vip_name = $line[0];
		}
		else $vip_name = '';
		
		$res_autor = $db->query("SELECT `worker_real_name` FROM `users_worker_info`
			WHERE `user_id`='".$this->doc_data['user']."'");
		if($res_autor->num_rows) {
			$line = $res_autor->fetch_row();
			$autor_name = $line[0];
		}
		else $autor_name = '';
		
		$res_klad = $db->query("SELECT `worker_real_name` FROM `users_worker_info`
			WHERE `user_id`='".$this->dop_data['kladovshik']."'");
		if($res_klad->num_rows) {
			$line = $res_klad->fetch_row();
			$klad_name = $line[0];
		}
		else $klad_name = '';

		$pdf->Ln(5);

		$str="Всего $ii наименований массой $summass кг. на сумму $cost";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);

		$pdf->CellIconv(0,5,$mass_p,0,1,'L',0);

		if($this->doc_data['comment'])
		{
			$pdf->Ln(5);
			$str="Комментарий : ".$this->doc_data['comment'];
			$pdf->MultiCellIconv(0,5,$str,0,'L',0);
			$pdf->Ln(5);
		}

		$str="Заявку принял: _________________________________________ ($autor_name)";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);
		$str="Документ выписал: ______________________________________ ($vip_name)";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);
		$str="Заказ скомплектовал: ___________________________________ ( $klad_name )";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);

		if($to_str)
			return $pdf->Output('blading.pdf','S');
		else
			$pdf->Output('blading.pdf','I');
	}
	
/// Товарная накладная ТОРГ-12 в PDF формате
/// @param to_str Вернуть строку, содержащую данные документа (в противном случае - отправить файлом)
function PrintTg12PDF($to_str=0)
{
	global $CONFIG, $db;
	$st_line = 0.2;
	$bold_line = 0.6;
	
	$dt = date("d.m.Y",$this->doc_data['date']);

	$agent_info = $db->selectRow('doc_agent', $this->doc_data['agent']);
	$gruzop_info = $db->selectRow('doc_agent', $this->dop_data['gruzop']);
	$gruzop='';
	if($gruzop_info) {
		if($gruzop_info['fullname'])	$gruzop.=$gruzop_info['fullname'];
		else				$gruzop.=$gruzop_info['name'];
		if($gruzop_info['adres'])	$gruzop.=', адрес '.$gruzop_info['adres'];
		if($gruzop_info['tel'])		$gruzop.=', тел. '.$gruzop_info['tel'];
		if($gruzop_info['inn'])		$gruzop.=', ИНН/КПП '.$gruzop_info['inn'];
		if($gruzop_info['okpo'])	$gruzop.=', ОКПО '.$gruzop_info['okpo'];
		if($gruzop_info['okevd'])	$gruzop.=', ОКВЭД '.$gruzop_info['okevd'];
		if($gruzop_info['rs'])		$gruzop.=', Р/С '.$gruzop_info['rs'];
		if($gruzop_info['bank'])	$gruzop.=', в банке '.$gruzop_info['bank'];
		if($gruzop_info['bik'])		$gruzop.=', БИК '.$gruzop_info['bik'];
		if($gruzop_info['ks'])		$gruzop.=', К/С '.$gruzop_info['ks'];
	}

	$platelshik_info = $db->selectRow('doc_agent', $this->dop_data['platelshik']);
	$platelshik='';
	if($platelshik_info) {
		if($platelshik_info['fullname'])	$platelshik.=$platelshik_info['fullname'];
		else					$platelshik.=$platelshik_info['name'];
		if($platelshik_info['adres'])		$platelshik.=', адрес '.$platelshik_info['adres'];
		if($platelshik_info['tel'])		$platelshik.=', тел. '.$platelshik_info['tel'];
		if($platelshik_info['inn'])		$platelshik.=', ИНН/КПП '.$platelshik_info['inn'];
		if($platelshik_info['okpo'])		$platelshik.=', ОКПО '.$platelshik_info['okpo'];
		if($platelshik_info['okevd'])		$platelshik.=', ОКВЭД '.$platelshik_info['okevd'];
		if($platelshik_info['rs'])		$platelshik.=', Р/С '.$platelshik_info['rs'];
		if($platelshik_info['bank'])		$platelshik.=', в банке '.$platelshik_info['bank'];
		if($platelshik_info['bik'])		$platelshik.=', БИК '.$platelshik_info['bik'];
		if($platelshik_info['ks'])		$platelshik.=', К/С '.$platelshik_info['ks'];
	}
	
	
	if(isset($this->dop_data['dov_agent']))	{
		$dov_data = $db->selectRow('doc_agent_dov', $this->dop_data['dov_agent']);
		if($dov_data) {
			$dov_agn = $dov_data['surname'].' '.$dov_data['name'].' '.$dov_data['name2'];
			$dov_agr = $dov_data['range'];
		}
		else	$dov_agn=$dov_agr="";
	}
	else	$dov_agn=$dov_agr="";

	if($this->doc_data['p_doc']) {
		$res = $db->query("SELECT `doc_list`.`sklad`, `doc_kassa`.`name`, `doc_kassa`.`bik`, `doc_kassa`.`rs` FROM `doc_list`
		LEFT JOIN `doc_kassa` ON `doc_kassa`.`num`=`doc_list`.`bank` AND `doc_kassa`.`ids`='bank'
		WHERE `doc_list`.`id`='{$this->doc_data['p_doc']}'");
		$bank_data = $res->fetch_assoc();
		if($bank_data) {
			$this->firm_vars['firm_schet'] = $bank_data['rs'];
			$this->firm_vars['firm_bik'] = $bank_data['bik'];
			$this->firm_vars['firm_bank'] = $bank_data['name'];
		}
	}
	
	define('FPDF_FONT_PATH',$CONFIG['site']['location'].'/fpdf/font/');
	require('fpdf/fpdf_mysql.php');

	$pdf=new FPDF('P');
	$pdf->Open();
	$pdf->SetAutoPageBreak(1,12);
	$pdf->AddFont('Arial','','arial.php');
	$pdf->tMargin=5;
	$pdf->AddPage('L');
	$pdf->SetFillColor(255);

	$pdf->SetFont('Arial','',7);
	$str = 'Унифицированная форма ТОРГ-12 Утверждена постановлением госкомстата России от 25.12.98 № 132';
	$pdf->CellIconv(0,4,$str,0,0,'R');
	$pdf->Ln();
	$t2_y=$pdf->GetY();

	$pdf->SetFont('','',8);
	$str = $this->firm_vars['firm_gruzootpr'].", тел.".$this->firm_vars['firm_telefon'].", счёт ".$this->firm_vars['firm_schet'].", БИК ".$this->firm_vars['firm_bik'].", банк ".$this->firm_vars['firm_bank'].", К/С {$this->firm_vars['firm_bank_kor_s']}, адрес: {$this->firm_vars['firm_adres']}";
	$pdf->MultiCellIconv(230,4,$str,0,'L');
	$y=$pdf->GetY();
	$pdf->Line(10, $pdf->GetY(), 230, $pdf->GetY());
	$pdf->SetFont('','',5);
	$str="грузоотправитель, адрес, номер телефона, банковские реквизиты";
	$pdf->CellIconv(230,2,$str,0,1,'C');

	$pdf->SetFont('','',8);
	$pdf->Cell(0,4,'',0,1,'L');
	$pdf->Line(10, $pdf->GetY(), 230, $pdf->GetY());
	$pdf->SetFont('','',5);
	$str="структурное подразделение";
	$pdf->CellIconv(220,2,$str,0,1,'C');

	$pdf->Ln(5);
	$pdf->SetFont('','',8);
	$str="Грузополучатель";
	$pdf->CellIconv(30,4,$str,0,0,'L');
	$pdf->MultiCellIconv(190,4,$gruzop,0,'L');
	$pdf->Line(40, $pdf->GetY(), 230, $pdf->GetY());

	$str="Поставщик";
	$pdf->CellIconv(30,4,$str,0,0,'L');
	$str = "{$this->firm_vars['firm_name']}, {$this->firm_vars['firm_adres']}, ИНН/КПП {$this->firm_vars['firm_inn']}, К/С {$this->firm_vars['firm_bank_kor_s']}, Р/С {$this->firm_vars['firm_schet']}, БИК {$this->firm_vars['firm_bik']}, в банке {$this->firm_vars['firm_bank']}";
	$pdf->MultiCellIconv(190,4,$str,0,'L');
	$pdf->Line(40, $pdf->GetY(), 230, $pdf->GetY());

	$str="Плательщик";
	$pdf->CellIconv(30,4,$str,0,0,'L');
	$pdf->MultiCellIconv(190,4,$platelshik,0,'L');
	$pdf->Line(40, $pdf->GetY(), 230, $pdf->GetY());

	$str="Основание";
	$pdf->CellIconv(30,4,$str,0,0,'L');

	$str = "";

	$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`date`
	FROM `doc_list`
	WHERE `doc_list`.`agent`='{$this->doc_data['agent']}' AND `doc_list`.`type`='14' AND `doc_list`.`ok`>'0'
	ORDER BY  `doc_list`.`date` DESC");
	if($res->num_rows){
		$nxt = $res->fetch_row();
		$str.="Договор N$nxt[1] от ".date("d.m.Y",$nxt[2]).", ";
	}

	if($this->doc_data['p_doc']) {
		$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`date`, `doc_list`.`p_doc`, `doc_list`.`type` FROM `doc_list`
		WHERE `id`={$this->doc_data['p_doc']}");
		if($res->num_rows) {
			$nxt = $res->fetch_row();
			if($nxt[4]==1)		$str.="Счёт N$nxt[1] от ".date("d.m.Y",$nxt[2]).", ";
			else if($nxt[4]==16)	$str.="Спецификация N$nxt[1] от ".date("d.m.Y",$nxt[2]).", ";
			if($nxt[3])
			{
				$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`date`, `doc_list`.`p_doc` FROM `doc_list`
				WHERE `id`={$nxt[3]} AND `doc_list`.`type`='16'");
				if($res->num_rows) {
					$nxt = $res->fetch_row();
					$str.="Спецификация N$nxt[1] от ".date("d.m.Y",$nxt[2]).", ";
				}
			}
		}
	}
	$pdf->MultiCellIconv(190,4,$str,0,'L');
	$pdf->Line(40, $pdf->GetY(), 230, $pdf->GetY());
	$pdf->SetFont('','',5);
	$str="договор, заказ-наряд";
	$pdf->CellIconv(220,2,$str,0,1,'C');

	$t3_y=$pdf->GetY();

	$set_x=255;
	$width=17;
	$pdf->SetFont('','',7);
	$pdf->SetY($t2_y);
	$set_x=$pdf->w-$pdf->rMargin-$width;

	$str='Коды';
	$pdf->SetX($set_x);
	$pdf->CellIconv($width,4,$str,1,1,'C');
	$set_x=$pdf->w-$pdf->rMargin-$width*2;

	$tbt_y=$pdf->GetY();

	$lines=array('Форма по ОКУД', 'по ОКПО', 'Вид деятельности по ОКДП', 'по ОКПО', 'по ОКПО', 'по ОКПО');
	foreach($lines as $str)	{
		$pdf->SetX($set_x);
		$pdf->CellIconv($width,4,$str,0,1,'R');
	}
	$lines=array('Номер','Дата','Номер','Дата');
	foreach($lines as $str)	{
		$pdf->SetX($set_x);
		$pdf->CellIconv($width,4,$str,1,1,'R');
	}
	$str='Вид операции';
	$pdf->SetX($set_x);
	$pdf->CellIconv($width,4,$str,0,1,'R');

	$tbt_h=$pdf->GetY()-$tbt_y;
	$set_x=$pdf->w-$pdf->rMargin-$width;
	$pdf->SetY($tbt_y);
	$pdf->SetX($pdf->w-$pdf->rMargin-$width);
	$pdf->SetLineWidth($bold_line);
	$pdf->Cell($width,$tbt_h,'',1,1,'R');
	$pdf->SetLineWidth($st_line);

	$pdf->SetY($tbt_y);
	$lines=array('0330212', $this->firm_vars['firm_okpo'], '', $gruzop_info['okpo'], $this->firm_vars['firm_okpo'], $platelshik_info['okpo'], '', '', '', '');
	foreach($lines as $str)	{
		$pdf->SetX($set_x);
		$pdf->CellIconv($width,4,$str,1,1,'C');
	}

	$pdf->SetY($tbt_y+4*7+2);
	$pdf->SetX($pdf->w-$pdf->rMargin-$width*3-3);
	$str='Транспортная накладная';
	$pdf->MultiCellIconv($width+3,6,$str,0,'R');

	$pdf->SetY($t3_y+5);
	$pdf->SetX(40);
	$pdf->Cell(60,4,'',0,0,'R');
	$str='Номер документа';
	$pdf->CellIconv(25,4,$str,1,0,'C');
	$str='Дата составления';
	$pdf->CellIconv(25,4,$str,1,1,'C');
	$pdf->SetX(50);
	$pdf->SetLineWidth($bold_line);
	$pdf->SetFont('','',10);
	$str='ТОВАРНАЯ НАКЛАДНАЯ';
	$pdf->CellIconv(50,4,$str,0,0,'C');
	$pdf->SetFont('','',7);
	$pdf->Cell(25,4,$this->doc_data['altnum'],1,0,'C');
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
		$pdf->MultiCellIconv($w,3,$t_text[$i],0,'C',0);
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
		$pdf->MultiCellIconv($w2,3, $t2_text[$i],0,'C',0);
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
	$res = $db->query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `class_unit`.`rus_name1`, `doc_base_dop`.`mass`, `doc_base`.`vc`, `class_unit`.`number_code`
	FROM `doc_list_pos`
	LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
	LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
	WHERE `doc_list_pos`.`doc`='{$this->doc}'  AND `doc_base`.`pos_type`='0'
	ORDER BY `doc_list_pos`.`id`");
	$ii=0;
	$line_height=4;
	$summass=$sum=$sumnaloga=$cnt=0;
	$list_summass=$list_sum=$list_sumnaloga=$list_cnt=0;
	$nds=$this->firm_vars['param_nds']/100;
	$ndsp=$this->firm_vars['param_nds'];
	while($nxt = $res->fetch_row())
	{
		if($this->doc_data['nds'])
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

		$ii++;
		$mass		= $nxt[6]*$nxt[3];
		$cnt		+=$nxt[3];
		$list_cnt	+=$nxt[3];
		$cena		= sprintf("%01.2f", $cena);
		$stoimost	= sprintf("%01.2f", $stoimost);
		$nalog		= sprintf("%01.2f", $nalog);
		$snalogom	= sprintf("%01.2f", $snalogom);
		$mass		= sprintf("%01.3f", $mass);
		$summass	+=$mass;
		$list_summass	+=$mass;
		$sum		+=$snalogom;
		$list_sum	+=$snalogom;
		$sumnaloga	+=$nalog;
		$list_sumnaloga	+=$nalog;

		$pdf->Cell($t_all_width[0],$line_height, $ii ,1,0,'R',1);
		if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];
		$pdf->CellIconv($t_all_width[1],$line_height, $nxt[0].' '.$nxt[1],1,0,'L',1);
		$pdf->CellIconv($t_all_width[2],$line_height, $nxt[7] ,1,0,'L',1);
		$pdf->CellIconv($t_all_width[3],$line_height, $nxt[5] ,1,0,'C',1);
		$pdf->Cell($t_all_width[4],$line_height, $nxt[8] ,1,0,'C',1);
		$pdf->Cell($t_all_width[5],$line_height, '-' ,1,0,'C',1);
		$pdf->Cell($t_all_width[6],$line_height, '-' ,1,0,'C',1);
		$pdf->Cell($t_all_width[7],$line_height, '-' ,1,0,'C',1);
		$pdf->Cell($t_all_width[8],$line_height, $mass ,1,0,'C',1);
		$pdf->Cell($t_all_width[9],$line_height, $nxt[3] ,1,0,'C',1);

		$pdf->Cell($t_all_width[10],$line_height, $cena ,1,0,'C',1);
		$pdf->Cell($t_all_width[11],$line_height, $stoimost ,1,0,'C',1);
		$pdf->Cell($t_all_width[12],$line_height, "$ndsp%" ,1,0,'C',1);
		$pdf->Cell($t_all_width[13],$line_height, $nalog ,1,0,'R',1);
		$pdf->Cell($t_all_width[14],$line_height, $snalogom ,1,0,'R',1);
		$pdf->Ln();

		if($pdf->GetY()>190) {
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
			$pdf->CellIconv($w,$line_height, "Всего" ,0,0,'R',1);
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
	$pdf->CellIconv($w,$line_height, "Всего" ,0,0,'R',0);
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
	$pdf->CellIconv($w,$line_height, "Итого по накладной" ,0,0,'R',0);
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
	$str = "Всего мест: ".$this->dop_data['mest'];
	$pdf->CellIconv(30,$line_height, $str ,0,0,'R',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY()+$line_height, $x_end, $pdf->GetY()+$line_height);
	$pdf->Ln();
	$pdf->Ln();

	$str = "Приложения (паспорта, сертификаты) на";
	$pdf->CellIconv(60,$line_height, $str ,0,0,'R',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY()+$line_height, $x_end-10, $pdf->GetY()+$line_height);
	$str = "листах";
	$pdf->CellIconv(0,$line_height, $str ,0,1,'R',0);

	//$pdf->SetFont('','',9);
	$str = "Всего отпущено $cnt_p наименований на сумму $sum_p";
	$pdf->MultiCellIconv(0,$line_height, $str ,0,'L',0);

	$s=array(30,30,5,30,5,0);
	$line_m_height=3;


	$pdf->SetFont('','',8);
	$pdf->CellIconv($s[0],$line_height, "Отпуск разрешил" ,0,0,'L',0);
	$pdf->CellIconv($s[1],$line_height, "Директор" ,0,0,'L',0);
	$pdf->CellIconv($s[5],$line_height, $this->firm_vars['firm_director'] ,0,1,'R',0);

	$pdf->SetFont('','',6);
	$pdf->Cell($s[0],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[1], $pdf->GetY());
	$pdf->CellIconv($s[1],$line_m_height, "должность" ,0,0,'C',0);
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[3], $pdf->GetY());
	$pdf->CellIconv($s[3],$line_m_height, "подпись" ,0,0,'C',0);
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $x_end, $pdf->GetY());
	$pdf->CellIconv($s[5],$line_m_height, "расшифровка подписи" ,0,1,'C',0);

	$pdf->SetFont('','',8);
	$pdf->CellIconv($s[0]+$s[1],$line_height, "Главный (старший) бухгалтер" ,0,0,'L',0);
	$pdf->CellIconv($s[5],$line_height, $this->firm_vars['firm_buhgalter'] ,0,1,'R',0);

	$pdf->SetFont('','',6);
	$pdf->Cell($s[0]+$s[1],$line_m_height, '' ,0,0,'L',0);
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[3], $pdf->GetY());
	$pdf->CellIconv($s[3],$line_m_height, "подпись" ,0,0,'C',0);
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $x_end, $pdf->GetY());
	$pdf->CellIconv($s[5],$line_m_height, "расшифровка подписи" ,0,1,'C',0);

	$pdf->SetFont('','',8);
	$pdf->CellIconv($s[0],$line_height, "Отпуск груза произвёл" ,0,0,'L',0);
	$pdf->CellIconv($s[1],$line_height, $this->firm_vars['firm_kladovshik_doljn'] ,0,0,'L',0);
	$pdf->CellIconv($s[5],$line_height, $this->firm_vars['firm_kladovshik'] ,0,1,'R',0);

	$pdf->SetFont('','',6);
	$pdf->Cell($s[0],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[1], $pdf->GetY());
	$pdf->CellIconv($s[1],$line_m_height, "должность" ,0,0,'C',0);
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[3], $pdf->GetY());
	$pdf->CellIconv($s[3],$line_m_height, "подпись" ,0,0,'C',0);
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $x_end, $pdf->GetY());
	$pdf->CellIconv($s[5],$line_m_height, "расшифровка подписи" ,0,1,'C',0);

	$pdf->Ln();
	$pdf->Ln();
	$pdf->SetFont('','',8);
	$pdf->CellIconv($s[0],$line_height, "М.П." ,0,0,'R',0);
	$pdf->CellIconv($s[1],$line_height, '"___"' ,0,0,'R',0);
	$pdf->CellIconv($s[5],$line_height, '20___ года' ,0,1,'R',0);

	$pdf->Line($pdf->GetX()+$s[0]+$s[1]+$s[2], $pdf->GetY(), $pdf->GetX()+$s[0]+$s[1]+$s[2]+$s[3], $pdf->GetY());

	$pdf->Line($x_end+2, $y+15, $x_end+2, $pdf->GetY()+5);

	$pdf->rMargin=$old_rmargin;
	$pdf->lMargin=$x_end+5;
	$pdf->SetY($y+5);
	$pdf->SetX($pdf->lMargin);

	$x_end=$pdf->w-$pdf->rMargin;

	$pdf->CellIconv($s[0],$line_height, "Масса груза (нетто):"  ,0,0,'L',0);
	$pdf->Cell($s[1]+$s[2]+$s[3]+$s[4],$line_height, '' ,0,0,'L',0);
	$pdf->SetLineWidth($bold_line);
	$pdf->Cell($s[5],$line_height, $summass ,1,1,'C',0);
	$pdf->SetLineWidth($st_line);

	$pdf->CellIconv($s[0],$line_height, "Масса груза (брутто):" ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[1]+$s[2]+$s[3]+$s[4], $pdf->GetY());
	$pdf->Cell($s[1]+$s[2]+$s[3]+$s[4],$line_height, '' ,0,0,'L',0);
	$pdf->SetLineWidth($bold_line);
	$pdf->Cell($s[5],$line_height, '' ,1,1,'C',0);
	$pdf->SetLineWidth($st_line);
	$pdf->Cell($s[0],$line_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[1]+$s[2]+$s[3]+$s[4], $pdf->GetY());
	$pdf->Ln();

	$pdf->CellIconv($s[0],$line_height, "По доверенности №" ,0,0,'L',0);
	$pdf->CellIconv(0,$line_height, $this->dop_data['dov']." от ".$this->dop_data['dov_data'] ,0,1,'L',0);

	$pdf->SetFont('','',6);
	$pdf->Cell($s[0],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $x_end, $pdf->GetY());
	$pdf->CellIconv(0,$line_height, "кем, кому (организация, должность, фамилия и. о.)" ,0,1,'C',0);

	$pdf->SetFont('','',8);
	$pdf->CellIconv($s[0],$line_height, "Выданной" ,0,0,'L',0);
	$pdf->CellIconv(0,$line_height, $dov_agr.' '.$dov_agn ,0,1,'L',0);

	$pdf->SetFont('','',6);
	$pdf->Cell($s[0],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $x_end, $pdf->GetY());
	$pdf->CellIconv(0,$line_height, "кем, кому (организация, должность, фамилия и. о.)" ,0,1,'C',0);

	$pdf->SetFont('','',8);
	$pdf->CellIconv($s[0],$line_height, "Груз принял",0,1,'L',0);

	$pdf->SetFont('','',6);
	$pdf->Cell($s[0],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[1], $pdf->GetY());
	$pdf->CellIconv($s[1],$line_m_height, "должность" ,0,0,'C',0);
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[3], $pdf->GetY());
	$pdf->CellIconv($s[3],$line_m_height, "подпись",0,0,'C',0);
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $x_end, $pdf->GetY());
	$pdf->CellIconv($s[5],$line_m_height, "расшифровка подписи" ,0,1,'C',0);

	$pdf->SetFont('','',8);
	$pdf->CellIconv($s[0],$line_height, "Груз получил" ,0,1,'L',0);
	$pdf->CellIconv($s[0],$line_m_height, "грузополучатель" ,0,0,'L',0);
	$pdf->SetFont('','',6);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[1], $pdf->GetY());
	$pdf->CellIconv($s[1],$line_m_height, "должность" ,0,0,'C',0);
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX()+$s[3], $pdf->GetY());
	$pdf->CellIconv($s[3],$line_m_height, "подпись" ,0,0,'C',0);
	$pdf->Cell($s[2],$line_m_height, '' ,0,0,'L',0);
	$pdf->Line($pdf->GetX(), $pdf->GetY(), $x_end, $pdf->GetY());
	$pdf->CellIconv($s[5],$line_m_height, "расшифровка подписи" ,0,1,'C',0);

	$pdf->Ln();
	$pdf->Ln();
	$pdf->SetFont('','',8);
	$pdf->CellIconv($s[0],$line_height, "М.П." ,0,0,'R',0);
	$pdf->CellIconv($s[1],$line_height, "\"___\"" ,0,0,'R',0);
	$pdf->CellIconv($s[5],$line_height, '20___ года' ,0,1,'R',0);

	if($to_str)
		return $pdf->Output('torg12.pdf','S');
	else
		$pdf->Output('torg12.pdf','I');
}

function SfakPDF($to_str=0)
{
	global $CONFIG, $tmpl, $db;
	if(!$to_str) $tmpl->ajax=1;

	$dt = date("d.m.Y",$this->doc_data['date']);

	$gruzop_info = $db->selectRow('doc_agent', $this->dop_data['gruzop']);
	$gruzop='';
	if($gruzop_info) {
		if($gruzop_info['fullname'])	$gruzop.=$gruzop_info['fullname'];
		else				$gruzop.=$gruzop_info['name'];
		if($gruzop_info['adres'])	$gruzop.=', адрес '.$gruzop_info['adres'];
	}

	$agent_info = $db->selectRow('doc_agent', $this->doc_data['agent']);
	if(!$agent_info)	throw new Exception('Агент не найден');
	
	if($this->doc_data['p_doc'])	{
		$rs = $db->query("SELECT `id`, `altnum`, `date` FROM `doc_list` WHERE
		(`p_doc`='{$this->doc}' AND (`type`='4' OR `type`='6') AND `date`<='{$this->doc_data['date']}' ) OR
		(`p_doc`='{$this->doc_data['p_doc']}' AND (`type`='4' OR `type`='6') AND `date`<='{$this->doc_data['date']}')
		AND `ok`>'0' AND `p_doc`!='0' GROUP BY `p_doc`");
		if($res->num_rows)
		{
			$line = $res->fetch_row();
			$pp = $line[1];
			$ppdt = date("d.m.Y", $line[2]);
			if(!$pp) $pp = $line[0];
		}
		else $pp=$ppdt="__________";
	}
	else $pp=$ppdt="__________";

	define('FPDF_FONT_PATH',$CONFIG['site']['location'].'/fpdf/font/');
	require('fpdf/fpdf_mc.php');
	
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
	$str = iconv('UTF-8', 'windows-1251', "Счёт - фактура N {$this->doc_data['altnum']}, от $dt");
	$pdf->Cell(0,6,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "Исправление N ---- от --.--.----");
	$pdf->Cell(0,6,$str,0,1,'L');
	$pdf->Ln(5);
	$pdf->SetFont('Arial','',10);
	$str = iconv('UTF-8', 'windows-1251', "Продавец: ".$this->firm_vars['firm_name']);
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "Адрес: ".$this->firm_vars['firm_adres']);
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "ИНН / КПП продавца: ".$this->firm_vars['firm_inn']);
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "Грузоотправитель и его адрес: ".$this->firm_vars['firm_gruzootpr']);
	$pdf->MultiCell(0,$step,$str,0,'L');
	$str = iconv('UTF-8', 'windows-1251', "Грузополучатель и его адрес: ".$gruzop);
	$pdf->MultiCell(0,$step,$str,0,'L');
	$str = iconv('UTF-8', 'windows-1251', "К платёжно-расчётному документу № $pp, от $ppdt");
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "Покупатель: ".$agent_info['fullname']);
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "Адрес: ".$agent_info['adres']);
	$pdf->Cell(0,$step,$str,0,1,'L');
	$str = iconv('UTF-8', 'windows-1251', "ИНН / КПП покупателя: ".$agent_info['inn']);
	$pdf->Cell(0,$step,$str,0,1,'L');

	$str = "";

	$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`date` FROM `doc_list`
	WHERE `doc_list`.`agent`='{$this->doc_data['agent']}' AND `doc_list`.`type`='14' AND `doc_list`.`ok`>'0'
	ORDER BY  `doc_list`.`date` DESC");

	if($res->num_rows)	{
		$nxt = $res->fetch_row();
		$str.="Договор N$nxt[1] от ".date("d.m.Y",$nxt[2]).", ";
	}

	if($this->doc_data['p_doc']) {
		$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`date`, `doc_list`.`p_doc`, `doc_list`.`type` FROM `doc_list`
		WHERE `id`={$this->doc_data['p_doc']}");
		if($res->num_rows)
		{
			$nxt = $res->fetch_row();
			if($nxt[4]==1)		$str.="Счёт N$nxt[1] от ".date("d.m.Y",$nxt[2]).", ";
			else if($nxt[4]==16)	$str.="Спецификация N$nxt[1] от ".date("d.m.Y",$nxt[2]).", ";
			if($nxt[3])
			{
				$res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`date`, `doc_list`.`p_doc` FROM `doc_list`
				WHERE `id`={$nxt[3]} AND `doc_list`.`type`='16'");
				if($res->num_rows) {
					$nxt = $res->fetch_row();
					$str.="Спецификация N$nxt[1] от ".date("d.m.Y",$nxt[2]).", ";
				}
			}
		}
	}
	
	if($str) {
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
	'условное обозначение (наци ональное)',
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

	$res = $db->query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_list_pos`.`gtd`, `class_country`.`name`, `doc_base_dop`.`ntd`, `class_unit`.`rus_name1`, `doc_list_pos`.`tovar`, `class_unit`.`number_code`, `class_country`.`number_code`
	FROM `doc_list_pos`
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
	LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
	LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
	LEFT JOIN `class_country` ON `class_country`.`id`=`doc_base`.`country`
	WHERE `doc_list_pos`.`doc`='{$this->doc}'
	ORDER BY `doc_list_pos`.`id`");

	$pdf->SetY($y+18);
	$pdf->SetFillColor(255,255,255);
	$i=0;
	$ii=1;
	$sum=$sumnaloga=$sumbeznaloga=0;
	$nds=$this->firm_vars['param_nds']/100;
	$ndsp=$this->firm_vars['param_nds'];
	while($nxt = $res->fetch_row())
	{
		if(!$nxt[11])	throw new Exception("Не допускается печать счёта-фактуры без указания страны происхождения товара");

		$pdf->SetFont('','',8);
		if(@$CONFIG['poseditor']['true_gtd'])
		{
			$gtd_array=array();
			$gres = $db->query("SELECT `doc_list`.`type`, `doc_list_pos`.`gtd`, `doc_list_pos`.`cnt` FROM `doc_list_pos`
			INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`type`<='2' AND `doc_list`.`date`<'{$this->doc_data['date']}' AND `doc_list`.`ok`>'0'
			WHERE `doc_list_pos`.`tovar`='$nxt[9]' ORDER BY `doc_list`.`id`");
			while($line = $gres->fetch_row())
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
				if($this->doc_data['nds'])
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
				$sumbeznaloga+=$stoimost;

				if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];
				$row=array( "$nxt[0] $nxt[1]", $nxt[10], $nxt[8], $cnt, $cena, $stoimost, 'без акциз', "$ndsp%", $nalog, $snalogom, $nxt[11], $nxt[6], $gtd);
				$pdf->RowIconv($row);
			}
		}
		else
		{
			if($this->doc_data['nds'])
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
			$sumbeznaloga+=$stoimost;

			if(!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])	$nxt[1].=' / '.$nxt[2];
			$row=array( "$nxt[0] $nxt[1]", $nxt[10], $nxt[8], $nxt[3], $cena, $stoimost, 'без акциз', "$ndsp%", $nalog, $snalogom, $nxt[11], $nxt[6], $nxt[7]);
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

function Nacenki($to_str=0)
{
	global $tmpl, $CONFIG, $db;
	if (!$to_str)	$tmpl->ajax = 1;

	define('FPDF_FONT_PATH', $CONFIG['site']['location'] . '/fpdf/font/');
	require('fpdf/fpdf_mc.php');

	$pdf = new PDF_MC_Table('P');
	$pdf->Open();
	$pdf->SetAutoPageBreak(1, 12);
	$pdf->AddFont('Arial', '', 'arial.php');
	$pdf->tMargin = 5;
	$pdf->AddPage('L');
	$pdf->SetFillColor(255);

	$pdf->SetFont('Arial','',16);
	$str = iconv('UTF-8', 'windows-1251', "Наценки N {$this->doc_data['altnum']}{$this->doc_data['subtype']}, от ".date("d.m.Y", $this->doc_data['date']));
	$pdf->Cell(0,6,$str,0,1,'C');
	$pdf->SetFont('','',12);
	
	$str = "Поставщик: {$this->firm_vars['firm_name']}";
	$pdf->CellIconv(0,5,$str,0,1,'L');
	$str = "Покупатель: {$this->doc_data['agent_name']}";
	$pdf->CellIconv(0,5,$str,0,1,'L');
	$pdf->ln();
	
	$res = $db->query("SELECT `users`.`name`, `users_worker_info`.`worker_real_name`, CONCAT(`doc_list`.`altnum`,`doc_list`.`subtype`) AS `num`,
		`doc_list`.`date`, `doc_list`.`sum`, `doc_list`.`id`
		FROM `doc_list`
		LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
		LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`doc_list`.`user`
		WHERE `doc_list`.`id`='{$this->doc_data['p_doc']}' AND `doc_list`.`type`='3'");
	if ($res->num_rows) {
		$l = $res->fetch_assoc();
		$l['date'] = date("Y-m-d", $l['date']);
		$str = "К заявке: N{$l['num']}, от {$l['date']} на {$l['sum']}, создал {$l['name']}/{$l['worker_real_name']}";
		$z_id = $l['id'];
		$pdf->CellIconv(0,5,$str,0,1,'L');
	}
	else	$z_id = 0;

	$res = $db->query("SELECT `users`.`name`, `users_worker_info`.`worker_real_name`, CONCAT(`doc_list`.`altnum`,`doc_list`.`subtype`) AS `num`,
		`doc_list`.`date`, `doc_list`.`sum`
		FROM `doc_list`
		LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
		LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`doc_list`.`user`
		WHERE (`doc_list`.`p_doc`='{$this->doc}' OR `doc_list`.`p_doc`='$z_id') AND `doc_list`.`type`='4' AND `doc_list`.`p_doc`>0");
	while($l = $res->fetch_assoc()) {
		$l['date'] = date("Y-m-d", $l['date']);		
		$str = "Подчинённый банк-приход: N{$l['num']}, от {$l['date']} на {$l['sum']}, создал {$l['name']}/{$l['worker_real_name']}";
		$pdf->CellIconv(0,5,$str,0,1,'L');
	}

	$res = $db->query("SELECT `users`.`name`, `users_worker_info`.`worker_real_name`, CONCAT(`doc_list`.`altnum`,`doc_list`.`subtype`) AS `num`,
		`doc_list`.`date`, `doc_list`.`sum`
		FROM `doc_list`
		LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
		LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`doc_list`.`user`
		WHERE (`doc_list`.`p_doc`='{$this->doc}' OR `doc_list`.`p_doc`='$z_id') AND `doc_list`.`type`='5' AND `doc_list`.`p_doc`>0");
	while($l = $res->fetch_assoc()) {
		$l['date'] = date("Y-m-d", $l['date']);
		$str = "Подчинённый расходно-кассовый ордер: N{$l['num']}, от {$l['date']} на {$l['sum']}, создал {$l['name']}/{$l['worker_real_name']}";
		$pdf->CellIconv(0,5,$str,0,1,'L');
	}

	$pdf->SetLineWidth(0.7);
	$t_width = array(8, 90, 18, 16, 19, 16, 20, 27, 19, 19, 27);
	$t_text = array('№', 'Наименование', 'Кол-во', 'Цена', 'Сумма', 'АЦП', 'Наценка', 'Сум.наценки', 'П/закуп', 'Разница', 'Сум.разницы');
	$aligns = array('R', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R');
	
	foreach ($t_width as $id => $w) {
		$pdf->CellIconv($w, 6, $t_text[$id], 1, 0, 'C', 0);
	}
	$pdf->Ln();
	$pdf->SetWidths($t_width);
	$pdf->SetHeight(5);

	$pdf->SetAligns($aligns);
	$pdf->SetLineWidth(0.2);
	$pdf->SetFont('', '', 9);
	
	$res = $db->query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`,
		`class_unit`.`rus_name1` AS `units`, `doc_list_pos`.`tovar`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
	$i = 0;
	$ii = 1;
	$sum = $snac = $srazn = $cnt = 0;
	while ($nxt = $res->fetch_row()) {
		$sm = $nxt[3] * $nxt[4];
		$cost = sprintf("%01.2f", $nxt[4]);
		$cost2 = sprintf("%01.2f", $sm);
		$act_cost = sprintf('%0.2f', GetInCost($nxt[6]));
		$nac = sprintf('%0.2f', $cost - $act_cost);
		$sum_nac = sprintf('%0.2f', $nac * $nxt[3]);
		$snac+=$sum_nac;

		$r = $db->query("SELECT `doc_list`.`date`, `doc_list_pos`.`cost` FROM `doc_list_pos`
			LEFT JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
			WHERE `doc_list`.`ok`>'0' AND `doc_list`.`type`='1' AND `doc_list_pos`.`tovar`='$nxt[6]' AND `doc_list`.`date`<'{$this->doc_data['date']}'
			ORDER BY `doc_list`.`date` DESC");
		if ($r->num_rows) {
			$rr = $r->fetch_row();
			$zakup = sprintf('%0.2f', $rr[1]);
		}
		else	$zakup = 0;
		$razn = sprintf('%0.2f', $cost - $zakup);
		$sum_razn = sprintf('%0.2f', $razn * $nxt[3]);
		$srazn+=$sum_razn;
		if (!@$CONFIG['doc']['no_print_vendor'] && $nxt[2])
			$nxt[1].=' / ' . $nxt[2];
		if($nxt[0]) $nxt[1] = $nxt[0].' '.$nxt[1];
		//$tmpl->AddText("<tr align=right><td>$ii</td><td align=left>$nxt[0] $nxt[1]<td>$nxt[3] $nxt[5]<td>$cost<td>$cost2<td>$act_cost<td>$nac<td>$sum_nac<td>$zakup<td>$razn<td>$sum_razn");
		
		$row = array($ii, $nxt[1], $nxt[3].' '.$nxt[5], $cost, $cost2, $act_cost, $nac, $sum_nac, $zakup, $razn, $sum_razn);
		
		$pdf->RowIconv($row);
		
		$i = 1 - $i;
		$ii++;
		$sum+=$sm;
		$cnt+=$nxt[3];
	}
	$ii--;
	$cost = sprintf("%01.2f", $sum);
	$srazn = sprintf("%01.2f", $srazn);
	$snac = sprintf("%01.2f", $snac);

//	$tmpl->AddText("<tr>
//<td colspan='2'><b>ИТОГО:</b><td>$cnt<td><td>$cost<td><td><td>$snac<td><td><td>$srazn
//</table>
//<p>Всего <b>$ii</b> наименований на сумму <b>$cost</b></p>
//");
	if($to_str)	return $pdf->Output('extra.pdf','S');
	else		$pdf->Output('extra.pdf','I');
}

};
?>