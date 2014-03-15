<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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

/// Документ *приход средств в банк*
class doc_PBank extends doc_Nulltype
{
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=4;
		$this->doc_name				='pbank';
		$this->doc_viewname			='Приход средств в банк';
		$this->bank_modify			=1;
		$this->header_fields			='bank sum separator agent';
	}

	function initDefDopdata() {
		$this->def_dop_data = array('unique'=>'', 'cardpay'=>'', 'cardholder'=>'', 'masked_pan'=>'', 'trx_id'=>'', 'p_rnn'=>'');
	}
	
	function DopHead()
	{
		global $tmpl;
		$tmpl->addContent("Номер документа клиента банка:<br><input type='text' name='unique' value='{$this->dop_data['unique']}'><br>");
		if($this->dop_data['cardpay']) {
			$tmpl->addContent("<b>Владелец карты:</b>{$this->dop_data['cardholder']}><br>
			<b>PAN карты:</b>{$this->dop_data['masked_pan']}><br><b>Транзакция:</b>{$this->dop_data['trx_id']}><br>
			<b>RNN транзакции:</b>{$this->dop_data['p_rnn']}><br>");
		}
	}

	function DopSave() {
		$unique = request('unique');
		if($unique)
		{
			$this->setDopData('unique', $unique);
			if($this->doc)	{
				if($this->dop_data['unique']!=$unique)
				{
					$log_data="unique: {$this->dop_data['unique']}=>$unique, ";
					doc_log("UPDATE {$this->doc_name}", $log_data, 'doc', $this->doc);
				}
			}
		}
	}

	function DopBody() {
		global $tmpl;
		if($this->dop_data['unique'])
			$tmpl->addContent("<b>Номер документа клиента банка:</b> {$this->dop_data['unique']}");
	}

	// Провести
	function DocApply($silent=0) {
		global $db;
		
		$data = $db->selectRow('doc_list', $this->doc);
		if(!$data)
			throw new Exception('Ошибка выборки данных документа при проведении!');
		if($data['ok'] && (!$silent))
			throw new Exception('Документ уже проведён!');
		
		$res = $db->query("UPDATE `doc_kassa` SET `ballance`=`ballance`+'{$data['sum']}' WHERE `ids`='bank' AND `num`='{$data['bank']}'");
		if(!$db->affected_rows)	throw new Exception("Cумма в банке {$data['bank']} не изменилась!");
		
		if($silent)	return;
		$db->update('doc_list', $this->doc, 'ok', time() );
		$this->sentZEvent('apply');
	}

	// Отменить проведение
	function DocCancel() {
		global $db;
		$data = $db->selectRow('doc_list', $this->doc);
		if(!$data)
			throw new Exception('Ошибка выборки данных документа!');
		if(!$data['ok'])
			throw new Exception('Документ не проведён!');
		
		$res = $db->query("UPDATE `doc_kassa` SET `ballance`=`ballance`-'{$data['sum']}' WHERE `ids`='bank' AND `num`='{$data['bank']}'");
		if(!$db->affected_rows)	throw new Exception("Cумма в банке {$data['bank']} не изменилась!");
		
		$db->update('doc_list', $this->doc, 'ok', 0 );
		$this->sentZEvent('cancel');
	}

};


?>