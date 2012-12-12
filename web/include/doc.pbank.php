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

$doc_types[4]="Приход средств в банк";

class doc_PBank extends doc_Nulltype
{
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=4;
		$this->doc_name				='pbank';
		$this->doc_viewname			='Приход средств в банк';
		$this->sklad_editor_enable		=false;
		$this->bank_modify			=1;
		$this->header_fields			='bank sum separator agent';
		settype($this->doc,'int');
	}

	function DopHead()
	{
		global $tmpl;
		$tmpl->AddText(@"Номер документа клиента банка:<br><input type='text' name='unique' value='{$this->dop_data['unique']}'><br>");
		if($this->dop_data['cardpay'])
		{
			$tmpl->AddText(@"<b>Владелец карты:</b>{$this->dop_data['cardholder']}><br>
			<b>PAN карты:</b>{$this->dop_data['masked_pan']}><br><b>Транзакция:</b>{$this->dop_data['trx_id']}><br><b>RNN транзакции:</b>{$this->dop_data['p_rnn']}><br>");
		}
	}

	function DopSave()
	{
		$unique=rcv('unique');
		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
		VALUES ( '{$this->doc}' ,'unique','$unique')");
	}

	function DopBody()
	{
		global $tmpl;
		if($this->dop_data['unique'])
			$tmpl->AddText("<b>Номер документа клиента банка:</b> {$this->dop_data['unique']}");
	}

	// Провести
	function DocApply($silent=0)
	{
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`bank`, `doc_list`.`ok`, `doc_list`.`sum`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)			throw new MysqlException('Ошибка выборки данных документа!');
		$nx=@mysql_fetch_row($res);
		if(!$nx)			throw new Exception('Документ не найден!');
		if( $nx[3] && (!$silent) )	throw new Exception('Документ уже был проведён!');

		$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`+'$nx[4]'
		WHERE `ids`='bank' AND `num`='$nx[2]'");
		if(!$res)			throw new MysqlException("Ошибка обновления суммы $nx[4] в банке $nx[2]!");
		if(! mysql_affected_rows())	throw new MysqlException("Cумма в банке $nx[2] не изменилась!");
		if($silent)	return;
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if(!$res)	throw new MysqlException('Ошибка установки даты проведения документа!');
	}

	// Отменить проведение
	function DocCancel()
	{
		$uid=@$_SESSION['uid'];
		$tim=time();
		$dd=date_day($tim);
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`bank`, `doc_list`.`ok`, `doc_list`.`sum`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка выборки данных документа!');
		if(!($nx=@mysql_fetch_row($res)))	throw new Exception('Документ не найден!');
		if(!$nx[3])				throw new Exception('Документ не проведён!');
		$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`-'$nx[4]'
		WHERE `ids`='bank' AND `num`='$nx[2]'");
		if(!mysql_affected_rows())		throw new MysqlException('Ошибка обновления суммы в банке!');
		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка установки флага отмены!');
	}

	// Печать документа
	function Printform($doc, $opt='')
	{
		global $tmpl, $uid;
		$opt=rcv('opt');

		if(!$this->doc_data[6])
		{
			$tmpl->ajax=1;
			$tmpl->msg("Сначала нужно провести документ!","err");
		}
		else
		{
			if($opt=='')
			{
				$tmpl->ajax=1;
				$tmpl->AddText("<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=prn'\">Выписка</div>");
			}
			else
			{
				$tmpl->LoadTemplate('print');
				$dt=date("d.m.Y",$this->doc_data[5]);
				$sum_p=sprintf("%0.2f руб.",$this->doc_data[11]);
				$sump_p=num2str($this->doc_data[11]);
				$tmpl->AddText("<h1>Строка выписки банка - приход N {$this->doc_data[9]}, от $dt </h1>
				<b>Поступило от от: </b>{$this->doc_data[3]}<br>
				<b>Сумма:</b> $sum_p ($sump_p)<br>
				<b>Получатель средств: </b>".$this->firm_vars['firm_name']);
			}
		}
	}
	// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		global $tmpl;
        	$tmpl->AddText("Не поддерживается для данного типа документа");
	}

};


?>