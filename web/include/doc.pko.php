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


$doc_types[6]="Приходный кассовый ордер";

class doc_Pko extends doc_Nulltype
{
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=6;
		$this->doc_name				='pko';
		$this->doc_viewname			='Приходный кассовый ордер';
		$this->sklad_editor_enable		=false;
		$this->ksaas_modify			=1;
		$this->header_fields			='agent sum kassa';
		settype($this->doc,'int');
	}
	
	// Провести
	function DocApply($silent=0)
	{
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`kassa`, `doc_list`.`ok`, `doc_list`.`sum`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)	throw new MysqlException('Ошибка выборки данных документа при проведении!');
		$nx=@mysql_fetch_row($res);
		if(!$nx)	throw new Exception('Документ не найден!');
		if( $nx[3] && (!$silent) )	throw new Exception('Документ уже был проведён!');
			
		$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`+'$nx[4]'
		WHERE `ids`='kassa' AND `num`='$nx[2]'");
		if(!$res)			throw new MysqlException("Ошибка обновления суммы $nx[4] в кассе $nx[2]!");
		if(! mysql_affected_rows())	throw new MysqlException("Cумма в кассе $nx[2] не изменилась!");
		if($silent)	return;
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if(!$res)	throw new MysqlException('Ошибка установки даты проведения документа!');
	}
	
	// Отменить проведение
	function DocCancel()
	{
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`kassa`, `doc_list`.`ok`, `doc_list`.`sum`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)	throw new MysqlException('Ошибка выборки данных документа при проведении!');
		if(!($nx=@mysql_fetch_row($res)))	throw new Exception('Документ не найден!');
		if(!$nx[3])				throw new Exception('Документ не проведён!');		
		$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`-'$nx[4]' WHERE `ids`='kassa' AND `num`='$nx[2]'");
		if(! mysql_affected_rows())		throw new MysqlException("Cумма в кассе $nx[2] не изменилась!");
		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка установки флага!');
		$budet=$this->CheckKassMinus();
		if($budet<0)				throw new Exception("Невозможно, т.к. будет недостаточно ($budet) денег в кассе!");
	}
	// Отменить проведение
	function Cancel($doc)
	{
		global $tmpl;
		global $uid;

		$tmpl->ajax=1;

 		mysql_query("START TRANSACTION");
 		mysql_query("LOCK TABLE `doc_list`, `doc_kassa` READ ");
		$err='';
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`kassa`, `doc_list`.`ok`, `doc_list`.`sum`
		FROM `doc_list` WHERE `doc_list`.`id`='$doc'");
		if($nx=@mysql_fetch_row($res))
		{
			if(($nx[3])||$silent)
			{
				$tim=time();
				$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`-'$nx[4]'
				WHERE `ids`='kassa' AND `num`='$nx[2]'");
				if(mysql_affected_rows())
				{
					$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='$doc'");
					if(!$res)
						$err="Ошибка обновления 2!";
					else
					{
						$budet=$this->CheckKassMinus();
						if($budet<0)
						{
							$err="Невозможно, т.к. будет недостаточно ($budet) денег в кассе!";
						}
					}
				}
				else $err="Ошибка обновления 1!";
			}
			else $err="Документ НЕ проведён!";
		}
		if(!$err)
		{
			mysql_query("COMMIT");
			if(!$silent)
			{
				doc_log("Cancel pko","doc:$doc");
				$tmpl->AddText("<h3>Докумен успешно отменён!</h3>");
			}
		}
		else
		{
			mysql_query("ROLLBACK");
			if(!$silent)
			{
				doc_log("ERROR: Cancel pko - $err","doc:$doc");
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
			$tmpl->AddText("<h1>Приходный кассовый ордер</h1>");

			$tmpl->msg("Сначала нужно провести документ!","err");
		}
		else
		{
			$tmpl->LoadTemplate('print');
			$dt=date("d.m.Y",$doc_data[5]);
			$sum_p=sprintf("%0.2f руб.",$doc_data[11]);
			$sump_p=num2str($doc_data[11]);
			$tmpl->AddText("<h1>Приходный кассовый ордер N $doc_data[9]$doc_data[10], от $dt </h1>
			<b>Получено от: </b>$doc_data[3]<br>
			<b>Сумма:</b> $sum_p ($sump_p)<br>
			<b>Получатель средств: </b>".$dv['firm_name']."<br><br>
			<p>Покупатель:_____________________________ /$doc_data[3]/</p>
			<p>Кассир: _____________________________ /".$dv['firm_buhgalter']."/</p>");
		}

	}
	// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		global $tmpl;
		$tmpl->ajax=1;
		$tmpl->AddText("<div class='disabled'>Не поддерживается для</div><div class='disabled'>данного типа документа</div>");
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
	// Служебные опции
	function Service($doc)
	{
		global $tmpl;
        $tmpl->msg("В процессе разработки!",err);
	}

};


?>