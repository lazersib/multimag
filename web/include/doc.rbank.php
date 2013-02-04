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

$doc_types[5]="Расход средств из банка";

/// Документ *Расход средств из банка*
class doc_RBank extends doc_Nulltype
{
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=5;
		$this->doc_name				='rbank';
		$this->doc_viewname			='Расход средств из банка';
		$this->sklad_editor_enable		=false;
		$this->bank_modify			=-1;
		$this->header_fields			='bank sum separator agent';
		settype($this->doc,'int');
	}
	
	function DopHead()
	{
		global $tmpl;
		$tmpl->AddText("Вид расхода:<br><select name=v_rasx>");
		$res=mysql_query("SELECT * FROM `doc_rasxodi` WHERE `id`>'0'");
		while($nxt=mysql_fetch_row($res))
			if($nxt[0]==@$this->dop_data['rasxodi'])
				$tmpl->AddText("<option value='$nxt[0]' selected>$nxt[1]</option>");
			else
				$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");
		
		$tmpl->AddText("</select>");	
	}

	function DopSave()
	{
		$v_rasx=rcv('v_rasx');

		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
			VALUES ('{$this->doc}','rasxodi','$v_rasx')");
		if($this->doc)
		{
			$log_data='';
			if(@$this->dop_data['rasxodi']!=$v_rasx)			$log_data.=@"rasxodi: {$this->dop_data['rasxodi']}=>$v_rasx, ";
			if($log_data)	doc_log("UPDATE {$this->doc_name}", $log_data, 'doc', $this->doc);
		}
	}
	
	function DopBody()
	{
		global $tmpl;
		$res=mysql_query("SELECT `doc_rasxodi`.`name` FROM `doc_rasxodi`
		WHERE `doc_rasxodi`.`id`='{$this->dop_data['rasxodi']}'");
			
        	$nxt=mysql_fetch_row($res);
		$tmpl->AddText("<b>Статья расходов:</b> $nxt[0]");
	}
	
	// Провести
	function DocApply($silent=0)
	{
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`bank`, `doc_list`.`ok`, `doc_list`.`sum`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)			throw new MysqlException('Ошибка выборки данных документа при проведении!');
		$nx=@mysql_fetch_row($res);
		if(!$nx)			throw new Exception('Документ не найден!');
		if( $nx[3] && (!$silent) )	throw new Exception('Документ уже был проведён!');

		$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`-'$nx[4]'
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
		global $uid;
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`bank`, `doc_list`.`ok`, `doc_list`.`sum`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка выборки данных документа!');
		if(!($nx=@mysql_fetch_row($res)))	throw new Exception('Документ не найден!');
		if(!$nx[3])				throw new Exception('Документ не проведён!');
		$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`+'$nx[4]'
		WHERE `ids`='bank' AND `num`='$nx[2]'");
		if(!mysql_affected_rows())		throw new MysqlException('Ошибка обновления суммы в банке!');
		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка установки флага отмены!');
	}

	// Печать документа
	function Printform($doc, $opt='')
	{
		get_docdata($doc);
		global $tmpl;
		global $uid;
		global $doc_data;
		global $dop_data;
		global $dv;

		if(!$doc_data[6])
		{
			doc_menu(0,0);
			$tmpl->AddText("<h1>Строка выписки банка - приход</h1>");
			$tmpl->msg("Сначала нужно провести документ!","err");
		}
		else
		{
			$tmpl->LoadTemplate('print');
			$dt=date("d.m.Y",$doc_data[5]);
			$sum_p=sprintf("%0.2f руб.",$doc_data[11]);
			$sump_p=num2str($doc_data[11]);
			$tmpl->AddText("<h1>Строка выписки банка - приход N $doc_data[9], от $dt </h1>
			<b>Поступило от от: </b>$doc_data[3]<br>
			<b>Сумма:</b> $sum_p ($sump_p)<br>
			<b>Получатель средств: </b>".$dv['firm_name']);
		}
	}
	// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		global $tmpl;
		$tmpl->ajax=1;
		$tmpl->AddText("<div class='disabled'>Не поддерживается для</div><div class='disabled'>данного типа документа</div>");
	}

};


?>