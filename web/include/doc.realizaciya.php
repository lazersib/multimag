<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2015, BlackLight, TND Team, http://tndproject.org
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
class doc_Realizaciya extends doc_Nulltype {
    var $status_list;

    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 2;
        $this->typename = 'realizaciya';
        $this->viewname = 'Реализация товара';
        $this->sklad_editor_enable = true;
        $this->sklad_modify = -1;
        $this->header_fields = 'bank sklad cena separator agent';
        $this->PDFForms = array(
            array('name' => 'tc', 'desc' => 'Товарный чек', 'method' => 'PrintTcPDF'),
            array('name' => 'tcnkkt', 'desc' => 'Товарный чек без ККТ', 'method' => 'PrintTcPDFnkkt'),
            array('name' => 'tcna', 'desc' => 'Товарный чек без агента', 'method' => 'PrintTcPDFna'),
            array('name' => 'tcnd', 'desc' => 'Товарный чек без скидки', 'method' => 'PrintTcPDFnd'),
            array('name' => 'nak_kompl', 'desc' => 'Накладная на комплектацию', 'method' => 'PrintNaklKomplektPDF'),
            array('name' => 'nacenki', 'desc' => 'Наценки', 'method' => 'Nacenki'),
            array('name' => 'skidki', 'desc' => 'Скидки', 'method' => 'PrintSkidkiPDF'),
            array('name' => 'label', 'desc' => 'Этикетки на упаковку', 'method' => 'PrintLabel')
        );
        $this->status_list = array('in_process' => 'В процессе', 'ok' => 'Готов к отгрузке', 'err' => 'Ошибочный');
    }
    
    /// Получить строку с HTML кодом дополнительных кнопок документа
    protected function getAdditionalButtonsHTML() {
         return "<a href='' onclick=\"ShowPopupWin('/doc.php?mode=srv&amp;opt=dov&amp;doc={$this->id}');"
         . " return false;\" title='Доверенное лицо'><img src='/img/i_users.png' alt='users'></a>";
    }
        
    function initDefDopdata() {
        $this->def_dop_data = array('platelshik' => 0, 'gruzop' => 0, 'status' => '', 'kladovshik' => 0,
            'mest' => '', 'received' => 0, 'return' => 0, 'cena' => 0, 'dov_agent' => 0, 'dov' => '', 'dov_data' => '');
    }

    // Создать документ с товарными остатками на основе другого документа
	public function createFromP($doc_obj) {
		parent::CreateFromP($doc_obj);
		$this->setDopData('platelshik', $doc_obj->doc_data['agent']);
		$this->setDopData('gruzop', $doc_obj->doc_data['agent']);
		unset($this->doc_data);
		$this->get_docdata();
		return $this->id;
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
		if($this->id)
		{
			$log_data = getCompareStr($old_data, $new_data);
			if(@$old_data['status'] != $new_data['status'])
				$this->sentZEvent('cstatus:'.$new_data['status']);
		}
		$this->setDopDataA($new_data);
		if($log_data)	doc_log("UPDATE {$this->typename}", $log_data, 'doc', $this->id);
	}

    function dopBody() {
        global $tmpl;
        if ($this->dop_data['received']) {
            $tmpl->addContent("<br><b>Документы подписаны и получены</b><br>");
        }
    }

    /// Провести документ
    function docApply($silent = 0) {
        global $CONFIG, $db;
        if(!$this->isAltNumUnique() && !$silent) {
            throw new Exception("Номер документа не уникален!");
        }
        $tim = time();
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_list`.`firm_id`,
                `doc_sklady`.`dnc`, `doc_sklady`.`firm_id` AS `store_firm_id`, `doc_agent`.`no_bonuses`, `doc_vars`.`firm_store_lock`, `doc_list`.`p_doc`
            FROM `doc_list`
            INNER JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
            INNER JOIN `doc_agent` ON `doc_list`.`agent` = `doc_agent`.`id`
            INNER JOIN `doc_vars` ON `doc_list`.`firm_id` = `doc_vars`.`id`
            WHERE `doc_list`.`id`='{$this->id}'");
        $doc_params = $res->fetch_assoc();
        $res->free();
        if ($doc_params['ok'] && (!$silent)) {
            throw new Exception('Документ уже был проведён!');
        }
        if (!$this->dop_data['kladovshik'] && @$CONFIG['doc']['require_storekeeper'] && !$silent) {
            throw new Exception("Кладовщик не выбран!");
        }
        if (!$this->dop_data['mest'] && @$CONFIG['doc']['require_pack_count'] && !$silent) {
            throw new Exception("Количество мест не задано");
        }
        // Запрет на списание со склада другой фирмы
        if($doc_params['store_firm_id']!=null && $doc_params['store_firm_id']!=$doc_params['firm_id']) {
            throw new Exception("Выбранный склад принадлежит другой организации!");
        }
        // Ограничение фирмы списком своих складов
        if($doc_params['firm_store_lock'] && $doc_params['store_firm_id']!=$doc_params['firm_id']) {
            throw new Exception("Выбранная организация может списывать только со своих складов!!");
        }
        
        if (!$silent) {
            $db->query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->id}'");
        }

        $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`,
                `doc_base`.`pos_type`, `doc_list_pos`.`id`, `doc_base`.`vc`, `doc_list_pos`.`cost`
            FROM `doc_list_pos`
            LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
            LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$doc_params['sklad']}'
        WHERE `doc_list_pos`.`doc`='{$this->id}' AND `doc_base`.`pos_type`='0'");
        $bonus = 0;
        $fail_text = '';
        while ($nxt = $res->fetch_row()) {
            if (!$doc_params['dnc']) {
                if ($nxt[1] > $nxt[2]) {
                    $pos_name = composePosNameStr($nxt[0], $nxt[7], $nxt[3], $nxt[4]);
                    $fail_text .= " - Мало товара '$pos_name' -  есть:{$nxt[2]}, нужно:{$nxt[1]}. \n";
                    continue;
                }
            }

            $db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='{$doc_params['sklad']}'");

            if (!$doc_params['dnc'] && (!$silent)) {
                $budet = getStoreCntOnDate($nxt[0], $doc_params['sklad'], $doc_params['date']);
                if ($budet < 0) {
                    $pos_name = composePosNameStr($nxt[0], $nxt[7], $nxt[3], $nxt[4]);
                    $t = $budet + $nxt[1];
                    $fail_text .= " - Будет мало товара '$pos_name' - есть:$t, нужно:{$nxt[1]}. \n";
                    continue;
                }
            }

            if (@$CONFIG['poseditor']['sn_restrict']) {
                $r = $db->query("SELECT COUNT(`doc_list_sn`.`id`) FROM `doc_list_sn` WHERE `rasx_list_pos`='$nxt[6]'");
                list($sn_cnt) = $r->fetch_row();
                if ($sn_cnt != $nxt[1]) {
                    $pos_name = composePosNameStr($nxt[0], $nxt[7], $nxt[3], $nxt[4]);
                    $fail_text .= " - Мало серийных номеров товара '$pos_name' - есть:$sn_cnt, нужно:{$nxt[1]}. \n";
                    continue;
                }
            }
            $bonus+=$nxt[8] * $nxt[1] * (@$CONFIG['bonus']['coeff']);
        }        
        if($fail_text) {
            throw new Exception("Ошибка в номенклатуре: \n".$fail_text);
        }        
        if ($silent) {
            return;
        }
        // Резервы
        if($doc_params['p_doc']) {
            $res = $db->query("SELECT `id`, `ok` FROM `doc_list` WHERE `ok`>0 AND `type`=3 AND `id`={$doc_params['p_doc']}");
            if (!$res->num_rows) {
                $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`
                    FROM `doc_list_pos`
                    LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
                    WHERE `doc_list_pos`.`doc`='{$doc_params['p_doc']}'");
                $vals = '';
                while ($nxt = $res->fetch_row()) {
                    if ($vals) {
                        $vals .= ',';
                    }
                    $vals .= "('$nxt[0]', '$nxt[1]')";
                }
                if($vals) {
                    $db->query("INSERT INTO `doc_base_dop` (`id`, `reserve`) VALUES $vals
                        ON DUPLICATE KEY UPDATE `reserve`=`reserve`-VALUES(`reserve`)");
                } else {
                    throw new Exception("Не удалось провести пустой документ!");
                }
            }
        }
        if (!$doc_params['no_bonuses'] && $bonus>0)
            $db->query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES ( '{$this->id}' ,'bonus','$bonus')");

        $this->sentZEvent('apply');
    }

    function docCancel() {
        global $db;

        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->id}'");
        if (!$res->num_rows) {
            throw new Exception('Документ не найден!');
        }
        $nx = $res->fetch_row();
        if (!$nx[4]) {
            throw new Exception('Документ НЕ проведён!');
        }

        $res = $db->query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$this->id}' AND `ok`>'0'");
        if ($res->num_rows) {
            throw new Exception('Нельзя отменять документ с проведёнными подчинёнными документами.');
        }

        $db->query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->id}'");
        $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base`.`pos_type` FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`	WHERE `doc_list_pos`.`doc`='{$this->id}' AND `doc_base`.`pos_type`='0'");

        while ($nxt = $res->fetch_row()) {
            $db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`+'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nx[3]'");
        }
        $db->query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES ( '{$this->id}' ,'bonus','0')");
        // Резервы
        if($this->doc_data['p_doc']) {
            $res = $db->query("SELECT `id`, `ok` FROM `doc_list` WHERE `ok`>0 AND `type`=3 AND `id`={$this->doc_data['p_doc']}");
            if (!$res->num_rows) {
                $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`
                    FROM `doc_list_pos`
                    LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
                    WHERE `doc_list_pos`.`doc`='{$this->doc_data['p_doc']}'");
                $vals = '';
                while ($nxt = $res->fetch_row()) {
                    if ($vals) {
                        $vals .= ',';
                    }
                    $vals .= "('$nxt[0]', '$nxt[1]')";
                }
                if($vals) {
                    $db->query("INSERT INTO `doc_base_dop` (`id`, `reserve`) VALUES $vals
                        ON DUPLICATE KEY UPDATE `reserve`=`reserve`+VALUES(`reserve`)");
                }
            }
        }
        $this->sentZEvent('cancel');
    }

    /// Формирование другого документа на основании текущего
	/// @param target_type ID типа создаваемого документа
	function MorphTo($target_type) {
		global $tmpl, $db;
	
		if($target_type=='')
		{
			$tmpl->ajax=1;
			$tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=6'\">Приходный кассовый ордер</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=4'\">Приход средств в банк</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=18'\">Корректировка долга</div>");
			if(!$this->doc_data['p_doc'])	$tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=1'\">Заявка (родительская)</div>");
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
			$new_doc = new doc_Pbank();
			$dd = $new_doc->createFrom($this);
			$new_doc->setDocData('bank', 1);
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
			$info = $db->selectRowA('doc_agent_dov', $this->dop_data['dov_agent'], array('name', 'surname'));
			$agn = '';
			if($info['name'])
				$agn = $info['name'];
			if($info['surname']) {
				if($agn)$agn.=' ';
				$agn.=$info['surname'];
			}

			$tmpl->addContent("<form method='post' action=''>
<input type=hidden name='mode' value='srv'>
<input type=hidden name='opt' value='dovs'>
<input type=hidden name='doc' value='{$this->id}'>
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
			if(!isAccess('doc_'.$this->typename, 'edit'))	throw new AccessException();
			$this->setDopData('dov', request('dov'));
			$this->setDopData('dov_agent', request('dov_agent'));
			$this->setDopData('dov_data', request('dov_data'));
			$ref="Location: doc.php?mode=body&doc={$this->id}";
			header($ref);
			doc_log("UPDATE","dov:".request('dov').", dov_agent:".request('dov_agent').", dov_data:".request('dov_data'),'doc', $this->id);
		}
		else $tmpl->msg("Неизвестная опция $opt!");
		
	}
//	================== Функции только этого класса ======================================================
	
	/// Обычная накладная в PDF формате
/// @param to_str Вернуть строку, содержащую данные документа (в противном случае - отправить файлом)
	function PrintSkidkiPDF($to_str=false) {
		require('fpdf/fpdf_mc.php');
		global $tmpl, $CONFIG, $db;

		if(!$to_str) $tmpl->ajax=1;

		$pdf=new PDF_MC_Table('L');
		$pdf->Open();
		$pdf->SetAutoPageBreak(0,12);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=10;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		$dt = date("d.m.Y", $this->doc_data['date']);

		$pdf->SetFont('','',16);
		$str="Информация о скидках к накладной N {$this->doc_data['altnum']}{$this->doc_data['subtype']} ({$this->id}), от $dt";
		$pdf->CellIconv(0,8,$str,0,1,'C',0);
		$pdf->SetFont('','',10);
		$str="Поставщик: {$this->firm_vars['firm_name']}, тел: {$this->firm_vars['firm_telefon']}";
		$pdf->MultiCellIconv(0,5,$str,0,'L',0);
		$str="Покупатель: {$this->doc_data['agent_fullname']}";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);
		$pdf->Ln();

		$pdf->SetLineWidth(0.5);
		$t_width=array(8);
		if($CONFIG['poseditor']['vc']) {
			$t_width[]=15;
			$t_width[]=96;
		}
		else	$t_width[]=111;
		$t_width=array_merge($t_width, array(16,20,20,15,15,26,23,26));

		$t_text=array('№');
		if($CONFIG['poseditor']['vc']) {
			$t_text[]='Код';
			$t_text[]='Наименование';
		}
		else	$t_text[]='Наименование';
		$t_text=array_merge($t_text, array('Кол-во', 'Цена б/ск.', 'Цена со ск.', 'Ск. р.', 'Ск., %', 'Сумма б/ск.', 'Сумма ск.', 'Сумма со ск.'));

		foreach($t_width as $id=>$w) {
			$pdf->CellIconv($w,6,$t_text[$id],1,0,'C',0);
		}
		$pdf->Ln();
		$pdf->SetWidths($t_width);
		$pdf->SetHeight(3.8);

		$aligns=array('R');
		if($CONFIG['poseditor']['vc']) {
			$aligns[]='L';
			$aligns[]='L';
		}
		else	$aligns[]='L';
		$aligns=array_merge($aligns, array('R','R','R','R','R','R','R','R'));

		$pdf->SetAligns($aligns);
		$pdf->SetLineWidth(0.2);
		$pdf->SetFont('','',8);

		$res = $db->query("SELECT `doc_group`.`printname` AS `g_printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `class_unit`.`rus_name1` AS `units`, `doc_base`.`id`, `doc_base`.`vc`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data['sklad']}'
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->id}'
		ORDER BY `doc_list_pos`.`id`");
		$i = 0;
		$ii = 1;
		$sum = 0;
		$base_sum = 0;
		$pc = PriceCalc::getInstance();
		$pc->setAgentId($this->doc_data['agent']);
		$pc->setFromSiteFlag(@$this->dop_data['ishop']);
		
		while($nxt = $res->fetch_assoc()) {
			$row = array($ii);
			
			if ($CONFIG['poseditor']['vc'])
				$row[] = $nxt['vc'];
			
			if($nxt['g_printname'])
				$nxt['name'] = $nxt['g_printname'].' '.$nxt['name'];			
			if (!@$CONFIG['doc']['no_print_vendor'] && $nxt['proizv'])
				$nxt['name'].=' / ' . $nxt['proizv'];			
			$row[] = $nxt['name'];
			
			$def_price = $pc->getPosDefaultPriceValue($nxt['id']);
			$skid = round($def_price-$nxt['cost'],2);
			$skid_p = round($skid/$def_price*100,2);
			$sum_line = $nxt['cnt'] * $nxt['cost'];
			$def_sum_line = $nxt['cnt'] * $def_price;
			$skid_sum_line = $nxt['cnt'] * $skid;
			
			$def_price_s = sprintf("%01.2f руб.", $def_price);
			$price_s = sprintf("%01.2f руб.", $nxt['cost']);
			$skid_s = sprintf("%01.2f руб.", $skid);
			$skid_p_s = sprintf("%01.2f %%", $skid_p);
			$sum_line_s = sprintf("%01.2f руб.", $sum_line);
			$def_sum_line_s = sprintf("%01.2f руб.", $def_sum_line);
			$skid_sum_line_s = sprintf("%01.2f руб.", $skid_sum_line);
			
			$row = array_merge($row, array($nxt['cnt'].' '.$nxt['units'], $def_price_s, $price_s, $skid_s, $skid_p_s, $def_sum_line_s,
			    $skid_sum_line_s, $sum_line_s));

			$pdf->RowIconv($row);
			$i = 1 - $i;
			$ii++;
			$sum += $sum_line;
			$base_sum += $def_sum_line;
		}
		$pc->setOrderSum($base_sum);
		
		$ii--;

		$pay_info='';
		if($sum>0) {
			$add='';
			if($nxt[12]) $add=" OR (`p_doc`='{$this->doc_data['p_doc']}' AND (`type`='4' OR `type`='6'))";
			$rs = $db->query("SELECT SUM(`sum`) FROM `doc_list` WHERE
			(`p_doc`='{$this->id}' AND (`type`='4' OR `type`='6'))
			$add
			AND `ok`>0 AND `p_doc`!='0' GROUP BY `p_doc`");
			if($rs->num_rows)
			{
				$prop_data = $rs->fetch_row();
				$pay_p = number_format($prop_data[0], 2, '.', ' ');
				$pay_info = ", оплачено: $pay_p руб.";
			}
		}
		$pdf->Ln();
		$pdf->SetFont('','',10);
		$sum_p = number_format($sum, 2, '.', ' ');
		$str="Всего наименований: $ii, на сумму $sum_p руб.";
		if($pay_info)
			$str.=$pay_info;
		if($this->dop_data['mest'])	$str.=", мест: ".$this->dop_data['mest'];
		$pdf->CellIconv(0,4,$str,0,1,'L',0);
		
		$pdf->SetFont('','',7);
		if($sum < $base_sum) {
			if($this->dop_data['cena'])
				$price_name = '';
			else	$price_name = 'Ваша цена: '.$pc->getCurrentPriceName().'. ';
			$sk_s = number_format($base_sum-$sum, 2, '.', ' ');
			$sk_p_s = number_format(($base_sum-$sum)/$base_sum*100, 2, '.', ' ');
			$str = "{$price_name}Размер скидки: $sk_s руб. ( $sk_p_s % ).";
			$pdf->CellIconv(0,3,$str,0,1,'L',0);
		}
		
		$next_price_info = $pc->getNextPriceInfo();
		if($next_price_info) {
			if($next_price_info['incsum']<($base_sum/5)) {	// Если надо докупить на сумму менее 20%
				$next_sum_p = number_format($next_price_info['incsum'], 2, '.', ' ');
				$str = "При увеличении суммы покупки на $next_sum_p руб., вы можете получить цену \"{$next_price_info['name']}\"!";
				$pdf->CellIconv(0, 3,$str,0,1,'L',0);
			}
		}
		
		$next_periodic_price_info = $pc->getNextPeriodicPriceInfo();
		if($next_periodic_price_info) {
			if($next_periodic_price_info['incsum']<($base_sum/5)) {	// Если надо докупить на сумму менее 20%
				$next_sum_p = number_format($next_periodic_price_info['incsum'], 2, '.', ' ');
				$str = "При осуществлении дополнительных оплат за {$next_periodic_price_info['period']} на $next_sum_p руб., вы получите цену \"{$next_periodic_price_info['name']}\"!";
				$pdf->CellIconv(0, 3,$str,0,1,'L',0);
			}
		}

		
		
		$pdf->SetFont('','',10);
		$pdf->ln(5);
		$str="Поставщик:_____________________________________";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);
		$pdf->ln();
		if($this->dop_data['mest'])
			$str = "Получил {$this->dop_data['mest']} мест товара, ";
		else	$str = "Товар получил, ";
		$str .= "претензий к качеству товара и внешнему виду не имею.";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);
		$str="Покупатель: ____________________________________";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);

		if($to_str)	return $pdf->Output('skidki.pdf','S');
		else		$pdf->Output('skidki.pdf','I');
	}

        /// Товарный чек в PDF формате
        /// @param to_str Вернуть строку, содержащую данные документа (в противном случае - отправить файлом)
        function PrintTcPDF($to_str=false) {
            return $this->makeTcPDF($to_str);            
        }
        
        /// Товарный чек в PDF формате без ККТ
        /// @param to_str Вернуть строку, содержащую данные документа (в противном случае - отправить файлом)
        function PrintTcPDFnkkt($to_str=false) {
            return $this->makeTcPDF($to_str, false, true, true);            
        }
        
        /// Товарный чек в PDF формате без скидки
        /// @param to_str Вернуть строку, содержащую данные документа (в противном случае - отправить файлом)
        function PrintTcPDFnd($to_str=false) {
            return $this->makeTcPDF($to_str, false);            
        }
        
        /// Товарный чек в PDF формате без агента
        /// @param to_str Вернуть строку, содержащую данные документа (в противном случае - отправить файлом)
        function PrintTcPDFna($to_str=false) {
            return $this->makeTcPDF($to_str, true, false);            
        }
        
        /// Генерация товарного чека в PDF формате с параметрами
        /// @param $to_str Вернуть строку, содержащую данные документа (в противном случае - отправить файлом)
        /// @param $show_disc Выводить ли информацию о скидках
        /// @param $show_agent Выводить ли информацию о агенте-покупателе
        /// @param $show_kkt Выводить ли информацию о работе без использования ККТ
	protected function makeTcPDF($to_str = false, $show_disc = true, $show_agent = true, $show_kkt = false) {
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

		$pdf->SetFont('','',16);
		$str="Товарный чек N {$this->doc_data['altnum']}, от $dt";
		$pdf->CellIconv(0,8,$str,0,1,'C',0);
		$pdf->SetFont('','',10);
		$str="Продавец: {$this->firm_vars['firm_name']}, ИНН-{$this->firm_vars['firm_inn']}-КПП, тел: {$this->firm_vars['firm_telefon']}";
		$pdf->MultiCellIconv(0,5,$str,0,'L',0);
                if($show_agent) {
                    $str="Покупатель: {$this->doc_data['agent_fullname']}";
                    $pdf->CellIconv(0,5,$str,0,1,'L',0);
                }
		$pdf->Ln();

		$pdf->SetLineWidth(0.5);
		$t_width=array(8);
		if($CONFIG['poseditor']['vc'])	{
			$t_width[]=20;
			$t_width[]=91;
		}
		else	$t_width[]=111;
		$t_width=array_merge($t_width, array(12,15,23,23));

		$t_text=array('№');
		if($CONFIG['poseditor']['vc'])	{
			$t_text[]='Код';
			$t_text[]='Наименование';
		}
		else	$t_text[]='Наименование';
		$t_text=array_merge($t_text, array('Место', 'Кол-во', 'Стоимость', 'Сумма'));

		foreach($t_width as $id=>$w) {
			$str = iconv('UTF-8', 'windows-1251', $t_text[$id]);
			$pdf->CellIconv($w,6,$t_text[$id],1,0,'C',0);
		}
		$pdf->Ln();
		$pdf->SetWidths($t_width);
		$pdf->SetHeight(3.8);

		$aligns=array('R');
		if($CONFIG['poseditor']['vc']) {
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
		WHERE `doc_list_pos`.`doc`='{$this->id}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$ii=1;
		$sum=0;
		$disc_sum=0;
		$pc = PriceCalc::getInstance();
		while($nxt = $res->fetch_row())	{
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
			$disc_sum += $pc->getPosDefaultPriceValue($nxt[7])*$nxt[3];
		}
		$ii--;
		$cost = sprintf("%01.2f руб.", $sum);

		$prop='';
		if($sum>0) {
			$add='';
			if($nxt[12]) $add=" OR (`p_doc`='{$this->doc_data['p_doc']}' AND (`type`='4' OR `type`='6'))";
			$rs = $db->query("SELECT SUM(`sum`) FROM `doc_list` WHERE
			(`p_doc`='{$this->id}' AND (`type`='4' OR `type`='6'))
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

		if($sum!=$disc_sum && $show_disc) {
			$cost = sprintf("%01.2f руб.", $disc_sum-$sum);
			$str="Скидка: $cost";
			$pdf->CellIconv(0,5,$str,0,1,'L',0);
		}

		if($prop) {
			$pdf->CellIconv(0,5,$prop,0,1,'L',0);
		}
                
                if($show_kkt) {
                    $str = "Работа осуществляется без применения контрольно-кассовой техники в соответствии с ФЗ 162 от 07/07/2009.";
                    $pdf->CellIconv(0,6,$str,0,1,'L',0);
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

		$pdf->SetFont('','',16);
		$str="Накладная на комплектацию N {$this->doc_data['altnum']}{$this->doc_data['subtype']}, от $dt";
		$pdf->CellIconv(0,8,$str,0,1,'C',0);
		$pdf->SetFont('','',10);
		$str="К накладной N {$this->doc_data['altnum']}{$this->doc_data['subtype']} ({$this->id})";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);
		$str="Поставщик: {$this->firm_vars['firm_name']}";
		$pdf->CellIconv(0,5,$str,0,1,'L',0);
		$str="Покупатель: {$this->doc_data['agent_fullname']}";
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

		$res = $db->query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_base`.`mass`,
                    `doc_base_cnt`.`mesto`, `doc_base_cnt`.`cnt` AS `base_cnt`, `doc_list_pos`.`tovar`, `doc_list_pos`.`cost`, `doc_base`.`vc`,
                    `class_unit`.`rus_name1` AS `units`, `doc_list_pos`.`comm`,
                        `doc_base_dop`.`reserve`, `doc_base_dop`.`transit`, `doc_base_dop`.`offer`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_list_pos`.`tovar` AND `doc_base_cnt`.`sklad`='{$this->doc_data['sklad']}'
		LEFT JOIN `class_unit` ON `doc_base`.`unit`=`class_unit`.`id`
		WHERE `doc_list_pos`.`doc`='{$this->id}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$ii=1;
		$sum=0;
		$summass=0;
		while($nxt = $res->fetch_assoc())
		{
			$sm=$nxt['cnt']*$nxt['cost'];
			$cost = sprintf("%01.2f руб.", $nxt['cost']);
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
			$row=array_merge($row, array($nxt['cost'], "{$nxt['cnt']} {$nxt['units']}", $nxt['base_cnt'], $nxt['reserve'], $mass, $nxt['mesto']));
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
	$str = "Покупатель: {$this->doc_data['agent_fullname']}";
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
		WHERE (`doc_list`.`p_doc`='{$this->id}' OR `doc_list`.`p_doc`='$z_id') AND `doc_list`.`type`='4' AND `doc_list`.`p_doc`>0");
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
		WHERE (`doc_list`.`p_doc`='{$this->id}' OR `doc_list`.`p_doc`='$z_id') AND `doc_list`.`type`='5' AND `doc_list`.`p_doc`>0");
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
		WHERE `doc_list_pos`.`doc`='{$this->id}'
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
	$row = array('', 'Итого:', '', '', $cost, '', '', $snac, '', '', $srazn);
	$pdf->RowIconv($row);

//	$tmpl->AddText("<tr>
//<td colspan='2'><b>ИТОГО:</b><td>$cnt<td><td>$cost<td><td><td>$snac<td><td><td>$srazn
//</table>
//<p>Всего <b>$ii</b> наименований на сумму <b>$cost</b></p>
//");
	if($to_str)	return $pdf->Output('extra.pdf','S');
	else		$pdf->Output('extra.pdf','I');
}

function PrintLabel($to_str=0) {
	global $tmpl, $db;
	if (!$to_str)	$tmpl->ajax = 1;

	require('fpdf/fpdf.php');
	
	$gruzop_info = $db->selectRow('doc_agent', $this->dop_data['gruzop']);
	$gruzop='';
	if($gruzop_info) {
		if($gruzop_info['fullname'])	$gruzop.=$gruzop_info['fullname'];
		else				$gruzop.=$gruzop_info['name'];
		if($gruzop_info['inn'])		$gruzop.=', ИНН '.$gruzop_info['inn'];
		if($gruzop_info['adres'])	$gruzop.=', адрес '.$gruzop_info['adres'];
		if($gruzop_info['tel'])		$gruzop.=', тел. '.$gruzop_info['tel'];		
	}
	else	$gruzop = 'не задан';
	
	$maker = '';
	if($this->dop_data['kladovshik']) {
		$res = $db->query("SELECT `worker_real_name`, `worker_phone`, `worker_email`, `worker_post_name` FROM `users_worker_info` WHERE `user_id`='{$this->dop_data['kladovshik']}'");

		if($res->num_rows) {
			$author_info = $res->fetch_assoc();

			$maker = $author_info['worker_real_name'];
			if($author_info['worker_phone'])
				$maker .= ", тел: ".$author_info['worker_phone'];
			if($author_info['worker_email'])
				$maker .= ", email: ".$author_info['worker_email'];
		}
	}
	else	throw new Exception("Кладовщик не задан");
	
	$pack_cnt = $this->dop_data['mest'];

	$pdf = new FPDF();
	$pdf->Open();
	$pdf->SetAutoPageBreak(1, 12);
	$pdf->AddFont('Arial', '', 'arial.php');
	$pdf->tMargin = 5;
	$pdf->AddPage('P');
	$pdf->SetFillColor(255);

	$pdf->SetFont('Arial','',10);
	$str = "Этикетки к накладной N {$this->doc_data['altnum']}{$this->doc_data['subtype']}, от ".date("d.m.Y", $this->doc_data['date']);
	$pdf->CellIconv(0,6,$str,0,1,'C');
	$pdf->ln(10);
	
	$pdf->SetMargins(15, 15, 15 );
	$pdf->SetFont('','',12);
	$pdf->SetLineWidth(0.2);
	
	for($c=1;$c<=$pack_cnt;$c++) {
		$start = $pdf->y -5;
		$pdf->ln(0);
		$str = "Отправитель: {$this->firm_vars['firm_gruzootpr']}, ИНН: {$this->firm_vars['firm_inn']}, тел.: {$this->firm_vars['firm_telefon']}";
		$pdf->MultiCellIconv(0, 5, $str, 0, 'L');
		
		$pdf->ln(2);
		$str = "Грузополучатель: ".$gruzop;
		$pdf->MultiCellIconv(0, 5, $str, 0, 'L');

		$pdf->ln(2);
		$str = "Комплектовщик: ".$maker;
		$pdf->MultiCellIconv(0, 5, $str, 0, 'L');
		
		$pdf->ln(2);
		$str = "Место: $c. Всего мест: $pack_cnt. Упаковано: ".date("d.m.Y H:i").". Накладная {$this->doc_data['altnum']}{$this->doc_data['subtype']}, от ".date("d.m.Y", $this->doc_data['date']);
		$pdf->MultiCellIconv(0, 5, $str, 0, 'L');
		
		$pdf->ln(5);
		$end = $pdf->y;
		$pdf->Rect(10, $start, 190, $end - $start);
		$pdf->Rect(9, $start-1, 192, $end - $start + 2);
		$pdf->ln(10);
	}

	if($to_str)	return $pdf->Output('labels.pdf','S');
	else		$pdf->Output('labels.pdf','I');
}

}
