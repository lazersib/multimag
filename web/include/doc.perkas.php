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


$doc_types[9]="Перемещение средств (касса)";

class doc_PerKas extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=9;
		$this->doc_name				='perkas';
		$this->doc_viewname			='Перемещение средств (касса)';
		$this->sklad_editor_enable		=false;
		$this->kassa_modify			=0;
		$this->header_fields			='sum separator kassa';
		settype($this->doc,'int');
	}

	function DopHead()
	{
		global $tmpl;
		$tmpl->AddText("В кассу:<br>
		<select name='v_kassu'>");
		$res=mysql_query("SELECT `num`, `name`, `ballance` FROM `doc_kassa` WHERE `ids`='kassa' ORDER BY `name`");
		while($nxt=mysql_fetch_row($res))
		{
			$bal_p=sprintf("%0.2f р.",$nxt[2]);
			if($nxt[0]==$this->dop_data['v_kassu'])
				$tmpl->AddText("<option value='$nxt[0]' selected>$nxt[1] ($bal_p)</option>");
			else
				$tmpl->AddText("<option value='$nxt[0]'>$nxt[1] ($bal_p)</option>");
		}
		$tmpl->AddText("</select>");
	}

	function DopSave()
	{
		$v_kassu=rcv('v_kassu');
		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
			VALUES ('{$this->doc}','v_kassu','$v_kassu')");
	}

	function DopBody()
	{
		global $tmpl;
		$res=mysql_query("SELECT `doc_kassa`.`name` FROM `doc_kassa`
		WHERE `doc_kassa`.`num`='{$this->dop_data['v_kassu']}' AND `doc_kassa`.`ids`='kassa'");
        	$nxt=mysql_fetch_row($res);
		$tmpl->AddText("<b>В кассу:</b> $nxt[0]");
	}

	function DocApply($silent=0)
	{
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`kassa`, `doc_list`.`ok`, `doc_list`.`sum`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)	throw new MysqlException('Ошибка выборки данных документа при проведении!');
		$nx=@mysql_fetch_row($res);
		if(!$nx)	throw new Exception('Документ не найден!');
		if( $nx[3] && (!$silent) )	throw new Exception('Документ уже был проведён!');


		$res=mysql_query("SELECT `ballance` FROM `doc_kassa` WHERE `ids`='kassa' AND `num`='$nx[2]'");
		if(!$res)		throw new MysqlException('Ошибка запроса суммы кассы!');
		$nxt=mysql_fetch_row($res);
		if(!$nxt)		throw new Exception('Ошибка получения суммы кассы!');
		if($nxt[0]<$nx[4])	throw new Exception("Не хватает денег в кассе N$nx[2] ($nxt[0]<$nx[4])!");


		$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`-'$nx[4]'
		WHERE `ids`='kassa' AND `num`='$nx[2]'");
		if(!$res)			throw new MysqlException('Ошибка обновления кассы-источника!');
		if(! mysql_affected_rows())	throw new Exception('Ошибка обновления кассы-источника!');

		$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`+'$nx[4]'
		WHERE `ids`='kassa' AND `num`='{$this->dop_data['v_kassu']}'");
		if(!$res)			throw new MysqlException('Ошибка обновления кассы назначения!');
		if(! mysql_affected_rows())	throw new Exception('Ошибка обновления кассы назначения!');
		if($silent)	return;
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if(!$res)			throw new MysqlException('Ошибка установки даты проведения документа!');
	}

	function DocCancel()
	{
		global $uid;
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`kassa`, `doc_list`.`ok`, `doc_list`.`sum`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка выборки данных документа!');
		if(! ($nx=@mysql_fetch_row($res)))	throw new Exception('Документ не найден!');
		if(! $nx[3])				throw new Exception('Документ НЕ проведён!');

		$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`+'$nx[4]' WHERE `ids`='kassa' AND `num`='$nx[2]'");
		if(! mysql_affected_rows())		throw new MysqlException("Cумма в кассе $nx[2] не изменилась!");
		$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`-'$nx[4]' WHERE `ids`='kassa' AND `num`='{$this->dop_data['v_kassu']}'");
		if(! mysql_affected_rows())		throw new MysqlException("Cумма в кассе {$this->dop_data['v_kassu']} не изменилась!");
		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка установки флага!');
	}

	// Отменить проведение
	function Cancel($doc)
	{
		mysql_query("START TRANSACTION");
 		mysql_query("LOCK TABLE `doc_list`, `doc_kassa` READ ");

		get_docdata($doc);
		global $tmpl;
		global $uid;
		global $doc_data;
		global $dop_data;

		$tmpl->ajax=1;

		$err='';
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`kassa`, `doc_list`.`ok`, `doc_list`.`sum`
		FROM `doc_list` WHERE `doc_list`.`id`='$doc'");
        if($nx=@mysql_fetch_row($res))
        {
			if(($nx[3])||$silent)
			{
				$tim=time();
				$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`+'$nx[4]'
				WHERE `ids`='kassa' AND `num`='$nx[2]'");
				if(mysql_affected_rows())
				{
					$res=mysql_query("UPDATE `doc_kassa` SET `ballance`=`ballance`-'$nx[4]'
					WHERE `ids`='kassa' AND `num`='".$dop_data['v_kassu']."'");
					if(mysql_affected_rows())
					{
						$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='$doc'");
						if(!$res)
							$err="Ошибка обновления 3!";
					}
					else $err="Ошибка обновления 2!";
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
				doc_log("Cancel perkas","$doc");
				$tmpl->AddText("<h3>Докумен успешно отменён!</h3>");
			}
		}
		else
		{
			mysql_query("ROLLBACK");
			if(!$silent)
			{
				doc_log("ERROR: Cancel perkas - $err","$doc");
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
			$tmpl->AddText("<h1>Перемещение средств (касса)</h1>");

			$tmpl->msg("Сначала нужно провести документ!","err");
		}
		else
		{
			$tmpl->LoadTemplate('print');
			$dt=date("d.m.Y",$doc_data[5]);
			$sum_p=sprintf("%0.2f руб.",$doc_data[11]);
			$sump_p=num2str($doc_data[11]);

			$res=mysql_query("SELECT `doc_kassa`.`name` FROM `doc_kassa`
			WHERE `doc_kassa`.`num`='$doc_data[7]' AND `doc_kassa`.`ids`='kassa'");
			$nxt=mysql_fetch_row($res);
			$res=mysql_query("SELECT `doc_kassa`.`name` FROM `doc_kassa`
			WHERE `doc_kassa`.`num`='".$dop_data['v_kassu']."' AND `doc_kassa`.`ids`='kassa'");
			$nx2=mysql_fetch_row($res);

			$tmpl->AddText("<h1>Перемещение средств (касса) N $doc_data[9]$doc_data[10], от $dt </h1>
			<b>Сумма:</b> $sum_p ($sump_p)<br>
			<b>Из кассы:</b> $nxt[0]<br><b>В кассу:</b> $nx2[0]<br>
			<p>Кассир: _____________________________ /".$dv['firm_buhgalter']."/</p>");
		}

	}
	// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		global $tmpl;
        $tmpl->AddText("Не поддерживается для данного типа документа");
	}

	// Служебные опции
	function Service($doc)
	{
		global $tmpl;
        $tmpl->msg("В процессе разработки!",err);
	}

};


?>