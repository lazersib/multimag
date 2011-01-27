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
		$this->header_fields			='agent sum bank';
		settype($this->doc,'int');
	}

	function body()
	{
		global $tmpl;
		parent::body();
		$res=@mysql_query("SELECT `value` FROM `doc_dopdata` WHERE `doc` ='{$this->doc}' AND `param`='unique'");
		if(!$res)			throw new MysqlException('Ошибка выборки дополнительных данных документа!');
		if(mysql_num_rows($res))
		{
			$val=mysql_result($res,0,0);
			$tmpl->AddText("<b>Номер из клиент-банка:</b> $val<br>");
		}
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
		$rights=getright('doc_'.$this->doc_name,$uid);
		$dd=date_day($tim);
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`bank`, `doc_list`.`ok`, `doc_list`.`sum`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка выборки данных документа!');
		if(!($nx=@mysql_fetch_row($res)))	throw new Exception('Документ не найден!');
		if((!$rights['edit'])&&($dd>$nx[1]))	throw new AccessException('');		
		if(!$nx[3])				throw new Exception('Документ не проведён!');
		$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`-'$nx[4]'
		WHERE `ids`='bank' AND `num`='$nx[2]'");
		if(!mysql_affected_rows())		throw new MysqlException('Ошибка обновления суммы в банке!');
		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка установки флага отмены!');
	}
	
	function Cancel($doc)
	{
		global $tmpl;
		global $uid;

		$tmpl->ajax=1;

		mysql_query("START TRANSACTION");
		mysql_query("LOCK TABLE `doc_list`, `doc_kassa` READ ");
		$err='';
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`bank`, `doc_list`.`ok`, `doc_list`.`sum`
		FROM `doc_list` WHERE `doc_list`.`id`='$doc'");
		if($nx=@mysql_fetch_row($res))
		{
			$rights=getright('doc_'.$this->doc_name,$uid);
			$dd=date_day(time());
			if(($rights['edit'])||($dd<$nx[1]))
			{
				if(($nx[3])||$silent)
				{
					$tim=time();
					$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`-'$nx[4]'
					WHERE `ids`='bank' AND `num`='$nx[2]'");
					if(mysql_affected_rows())
					{
						$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='$doc'");
						if(!$res)
							$err="Ошибка обновления 2!";
					}
					else $err="Ошибка обновления 1!";
				}
				else $err="Документ НЕ проведён!";
				
			}
			else 
			{
				$tmpl->AddText("<form action='/message.php' method='post'>
				<input type='hidden' name='mode' value='petition'>
				<input type='hidden' name='doc' value='$doc'>
				<fieldset><label>Запрос на отмену документа</label>
				Опишите причину необходимости отмены документа:<br>
				<textarea name='comment'></textarea><br>
				<input type='submit' value='Послать запрос'>
				</fieldset></form>");
				$err="Докумен НЕ отменён!";	
			}
				
		}
		if(!$err)
		{
			mysql_query("COMMIT");
			if(!$silent)
			{
				doc_log("Cancel {$this->doc_name}","doc:$doc");
				$tmpl->AddText("<h3>Докумен успешно отменён!</h3>");
			}
		}
		else
		{
			mysql_query("ROLLBACK");
			if(!$silent)
			{
				doc_log("ERROR: Cancel {$this->doc_name} - $err","doc:$doc");
				$tmpl->AddText("<h3>$err</h3>");
			}
		}
		mysql_query("UNLOCK TABLE `doc_list`, `doc_kassa`");
	
			
		return $err;
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
        	$tmpl->AddText("Не поддерживается для данного типа документа");
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
};


?>