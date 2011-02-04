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


$doc_types[1]="Поступление";

class doc_Postuplenie extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=1;
		$this->doc_name				='postuplenie';
		$this->doc_viewname			='Поступление товара на склад';
		$this->sklad_editor_enable		=true;
		$this->sklad_modify			=1;
		$this->header_fields			='agent cena sklad';
		settype($this->doc,'int');
	}

	protected function DocApply($silent=0)
	{
		global $tmpl;
		global $uid;
		$tim=time();
		
		
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)	throw new MysqlException('Ошибка выборки данных документа при проведении!');
		$nx=@mysql_fetch_row($res);
		if(!$nx)	throw new Exception("Документ {$this->doc} не найден!");
		if( $nx[4] && (!$silent) )	throw new Exception('Документ уже был проведён!');


		$res=mysql_query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base`.`pos_type`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		WHERE `doc_list_pos`.`doc`='{$this->doc}' AND `doc_base`.`pos_type`='0'");
		if(!$res)	throw new MysqlException('Ошибка выборки номенклатуры документа при проведении!');
		while($nxt=mysql_fetch_row($res))
		{
			$rs=mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`+'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nx[3]'");
			if(!$rs)	throw new MysqlException("Ошибка изменения количества товара $nxt[0] ($nxt[1]) на складе $nx[3] при проведении!");
			// Если это первое поступление
			if(mysql_affected_rows()==0) 
			{
				$rs=mysql_query("INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`) VALUES ('$nxt[0]', '$nx[3]', '$nxt[1]')");
				if(!$rs)	throw new MysqlException("Ошибка записи количества товара $nxt[0] ($nxt[1]) на складе $nx[3] при проведении!");
			}
		}
		if($silent)	return;
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if(!$res)	throw new MysqlException('Ошибка установки даты проведения документа!');
	}
	
	function DocCancel()
	{
		global $tmpl;
		global $uid;
		$dd=date_day(time());
		$tim=time();

		$rights=getright('doc_'.$this->doc_name,$uid);

		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)				throw new MysqlException("Ошибка получения данных документа!");
		if(!($nx=@mysql_fetch_row($res)))	throw new Exception("Документ {$this->doc} не найден!");
		if(!$nx[4])				throw new Exception("Документ ещё не проведён!");
		if( (!$rights['delete']) && (! ($rights['edit']&& ($dd<$nx[1]) )) )
							throw new AccessException('');
		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException("Ошибка установки даты проведения!");

		$sc="";
		if($nx[3]==2) $sc=2;
		$res=mysql_query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`pos_type`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$nx[3]'
		WHERE `doc_list_pos`.`doc`='{$this->doc}'");
		if(!$res)				throw new MysqlException("Ошибка получения номенклатуры документа!");
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[5]==0)
			{
				if($nxt[1]>$nxt[2])
				{
					$budet=$nxt[2]-$nxt[1];
					$badpos=$nxt[0];
					throw new Exception("Невозможно, т.к. будет недостаточно ($budet) товара '$nxt[3]:$nxt[4]' на складе!");
				}
				
				$budet=CheckMinus($nxt[0], $nx[3]);
				if($budet<0)
				{
					$badpos=$nxt[0];
					throw new Exception("Невозможно, т.к. будет недостаточно ($budet) товара '$nxt[3]:$nxt[4]' !");
				}
				mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nx[3]'");
				if(mysql_error())	throw new Exception("Ошибка отмены проведения, ошибка изменения количества!");
			}
		}


	}

	function Cancel($doc)
	{
		global $tmpl;
		global $uid;
	
		$tmpl->ajax=1;
		$dd=date_day(time());
		$tim=time();

		$rights=getright('doc_'.$this->doc_name,$uid);

		try
		{
			mysql_query("START TRANSACTION");
			mysql_query("LOCK TABLE `doc_list`, `doc_list_pos`, `doc_base_cnt` READ ");
			$err='';
			$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
			FROM `doc_list` WHERE `doc_list`.`id`='$doc'");
			if(!$res)				throw new MysqlException("Ошибка получения данных документа!");
			if(!($nx=@mysql_fetch_row($res)))	throw new MysqlException("Документ не найден!");
			if(!$nx[4])				throw new Exception("Документ ещё не проведён!");
			if( (!$rights['delete']) && (! ($rights['edit']&& ($dd<$nx[1]) )) )
								throw new GetRightException('');
			$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='$doc'");
			if(!$res)				throw new MysqlException("Ошибка установки даты проведения!");

			$sc="";
			if($nx[3]==2) $sc=2;
			$res=mysql_query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_base`.`pos_type`
			FROM `doc_list_pos`
			LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$nx[3]'
			WHERE `doc_list_pos`.`doc`='$doc'");
			if(!$res)				throw new MysqlException("Ошибка получения номенклатуры документа!");
			while($nxt=mysql_fetch_row($res))
			{
				if($nxt[5]==0)
				{
					if($nxt[1]>$nxt[2])
					{
						$budet=$nxt[2]-$nxt[1];
						$badpos=$nxt[0];
						throw new Exception("Невозможно, т.к. будет недостаточно ($budet) товара '$nxt[3]:$nxt[4]' на складе!");
					}
					
					$budet=CheckMinus($nxt[0], $nx[3]);
					if($budet<0)
					{
						$badpos=$nxt[0];
						throw new Exception("Невозможно, т.к. будет недостаточно ($budet) товара '$nxt[3]:$nxt[4]' !");
					}
					mysql_query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nx[3]'");
					if(mysql_error())	throw new Exception("Ошибка отмены проведения, ошибка изменения количества!");
				}
			}
		}
		
		catch(GetRightException $e)
		{
			mysql_query("ROLLBACK");
			$tmpl->AddText("<form action='/message.php' method='post'>
			<input type='hidden' name='mode' value='petition'>
			<input type='hidden' name='doc' value='{$this->doc}'>
			<fieldset><label>Запрос на отмену документа {$this->doc_viewname} N{$this->doc}</label>
			Опишите причину необходимости отмены документа:<br>
			<textarea name='comment'></textarea><br>
			<input type='submit' value='Послать запрос'>
			</fieldset></form>");
			mysql_query("UNLOCK TABLE `doc_list`, `doc_list_pos`, `doc_base`");
			return $e->getMessage().$e->sql_error;
		}
		catch(MysqlException $e)
		{
			mysql_query("ROLLBACK");
			$e->WriteLog();
			if(!$silent)	$tmpl->AddText("<h3>".$e->getMessage()."</h3><a onclick=\"ShowPopupWin('/doc.php?mode=forcecancel&amp;doc={$this->doc}'); return false;\" style='color: #888'>Принудительное снятие отметки проведения</a>");
			mysql_query("UNLOCK TABLE `doc_list`, `doc_list_pos`, `doc_base`");
			return $e->getMessage().$e->sql_error;
		}
		catch( Exception $e)
		{
			mysql_query("ROLLBACK");
			if(!$silent)
			{
				$tmpl->AddText("<h3>".$e->getMessage()."</h3><a onclick=\"ShowPopupWin('/doc.php?mode=forcecancel&amp;doc={$this->doc}'); return false;\" style='color: #888'>Принудительное снятие отметки проведения</a>");
				doc_log("ERROR CANCEL {$this->doc_name}",$err, 'doc', $this->doc);
			}
			mysql_query("UNLOCK TABLE `doc_list`, `doc_list_pos`, `doc_base`");
			return $e->getMessage();
		}
		
		mysql_query("COMMIT");
		if(!$silent)
		{
			doc_log("APPLY {$this->doc_name}", '', 'doc', $this->doc);
			$tmpl->AddText("<h3>Докумен успешно отменён!</h3>");
		}
		mysql_query("UNLOCK TABLE `doc_list`, `doc_list_pos`, `doc_base`");
		return;

	}

	function PrintForm($doc, $opt='')
	{
		global $tmpl;
		if($opt=='')
		{
			$tmpl->ajax=1;
			$tmpl->AddText("
			<a href='?mode=print&amp;doc=".$this->doc."&amp;opt=nak'><div>Накладная</div></a>
			<a href='?mode=print&amp;doc=".$this->doc."&amp;opt=nac'><div>Наценки</div></a>
			");
		}
		else if($opt=='nac')	$this->PrintNacenki($this->doc);
		else $this->PrintNakl($this->doc);
	}
	
	function PrintNakl($doc)
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
			$tmpl->AddText("<h1>Поступление товара на склад</h1>");

			$tmpl->msg("Сначала нужно провести документ!","err");
		}
		else
		{
			$tmpl->LoadTemplate('print');
			$dt=date("d.m.Y",$doc_data[5]);

			$tmpl->AddText("<h1>Накладная N $doc_data[9], от $dt </h1>
			<b>Поставщик: </b>$doc_data[3]<br>
			<b>Покупатель: </b>{$this->firm_vars['firm_name']}<br><br>");

			$tmpl->AddText("
			<table width=800 cellspacing=0 cellpadding=0>
			<tr><th>№</th><th width=450>Наименование<th>Место<th width=80>Масса<th>Кол-во<th>Стоимость<th width=75>Сумма</tr>");
			$res=mysql_query("SELECT `doc_group`.`printname`,`doc_base`.`name`,`doc_base`.`proizv` ,`doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_cnt`.`mesto`, `doc_base_dop`.`mass`
			FROM `doc_list_pos`
			LEFT JOIN `doc_base`  ON `doc_list_pos`.`tovar`=`doc_base`.`id`
			LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$doc_data[7]'
			WHERE `doc_list_pos`.`doc`='$doc'");
			$i=0;
			$ii=1;
			$sum=$summass=0;
			while($nxt=mysql_fetch_row($res))
			{
				$sm=$nxt[3]*$nxt[4];
				$cost = sprintf("%01.2f р.", $nxt[4]);
				$cost2 = sprintf("%01.2f р.", $sm);
				$mass = sprintf("%0.3f кг.", $nxt[6]);
				
				$tmpl->AddText("<tr align=right><td>$ii</td><td align=left>$nxt[0] $nxt[1] / $nxt[2]<td>$nxt[5]<td>$mass<td>$nxt[3]<td>$cost<td>$cost2");
				$i=1-$i;
				$ii++;
				$sum+=$sm;
				$summass+=$nxt[6]*$nxt[3];
			}
			$ii--;
			$cost = sprintf("%01.2f руб.", $sum);

			$tmpl->AddText("</table>
			<p>Всего <b>$ii</b> наименований на сумму <b>$cost</b> массой <b>$summass</b> кг.</p>
			<p class=mini>Товар получил, претензий к качеству товара и внешнему виду не имею.</p>
			<p>Поставщик:_____________________________________</p>
			<p>Покупатель: ____________________________________</p>");
			doc_log("PRINT {$this->doc_name}",'Накладная','doc',$doc);
		}
	}
	
	function PrintNacenki($doc)
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
			$tmpl->AddText("<h1>Поступление товара на склад</h1>");

			$tmpl->msg("Сначала нужно провести документ!","err");
		}
		else
		{
			$tmpl->LoadTemplate('print');
			$dt=date("d.m.Y",$doc_data[5]);

			$tmpl->AddText("<h1>Наценки для поступления N $doc_data[9], от $dt </h1>
			<b>Поставщик: </b>$doc_data[3]<br>
			<b>Покупатель: </b>".$dv['firm_name']."<br><br>");

			$tmpl->AddText("
			<table width=800 cellspacing=0 cellpadding=0>
			<tr><th>№</th><th width=450>Наименование<th>Кол-во<th>Стоимость<th>Базовая цена<th>Наценка<th width=75>Сумма</tr>");
			$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`,`doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base`.`cost`
			FROM `doc_list_pos`
			LEFT JOIN `doc_base`  ON `doc_list_pos`.`tovar`=`doc_base`.`id`
			LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='$doc_data[7]'
			WHERE `doc_list_pos`.`doc`='$doc'");
			$i=0;
			$ii=1;
			$sum=$sumnac=0;
			while($nxt=mysql_fetch_row($res))
			{
				$sm=$nxt[3]*$nxt[4];
				$cost = sprintf("%01.2f р.", $nxt[4]);
				$bcost = sprintf("%01.2f р.", $nxt[5]);
				$nac = sprintf("%01.2f р.", $nxt[5]-$nxt[4]);
				$cost2 = sprintf("%01.2f р.", $sm);
				
				$tmpl->AddText("<tr align=right><td>$ii</td><td align=left>$nxt[0] $nxt[1] / $nxt[2]<td>$nxt[3]<td>$cost<td>$bcost<td>$nac<td>$cost2");
				$i=1-$i;
				$ii++;
				$sum+=$sm;
				$sumnac+=($nxt[5]-$nxt[4]);
			}
			$ii--;
			$cost = sprintf("%01.2f руб.", $sum);
			$nac = sprintf("%01.2f руб.", $sumnac);

			$tmpl->AddText("</table>
			<p>Всего <b>$ii</b> наименований на сумму <b>$cost</b><br>
			Наценка по документу: $nac</p>
			");
			doc_log("PRINT {$this->doc_name}",'Наценки','doc',$doc);
		}
	}
	// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		get_docdata($doc);
		global $tmpl;
		global $uid;
		global $doc_data;


		if($target_type=='')
		{
			$tmpl->ajax=1;
			$tmpl->AddText("<a href='?mode=morphto&amp;doc=$doc&amp;tt=2'><div>Реализация</div></a>");
			$tmpl->AddText("<a href='?mode=morphto&amp;doc=$doc&amp;tt=7'><div>Расходный кассовый ордер</div></a>");
		}
		else if($target_type==2)
		{
			mysql_query("START TRANSACTION");
			$base=$this->Otgruzka($this->doc);
			if(!$base)
			{
				mysql_query("ROLLBACK");
				$tmpl->msg("Не удалось создать подчинённый документ!","err");
			}
			else
			{
				mysql_query("COMMIT");
				$ref="Location: doc.php?mode=body&doc=$base";
				header($ref);
			}
		}
		else if($target_type==7)
		{
			$sum=DocSumUpdate($doc);
			mysql_query("START TRANSACTION");
			$tm=time();
			$altnum=GetNextAltNum($target_type ,$doc_data[10]);
			$res=mysql_query("INSERT INTO `doc_list`
			(`type`, `agent`, `date`, `sklad`, `kassa`, `user`, `altnum`, `subtype`, `p_doc`, `sum`)
			VALUES ('$target_type', '$doc_data[2]', '$tm', '1', '1', '$uid', '$altnum', '$doc_data[10]', '$doc', '$sum')");
			if(mysql_errno())	throw new MysqlException("Не удалось создать подчинённый документ");
			$ndoc= mysql_insert_id();
			// Вид расхода - закуп товара на продажу
			mysql_query("INSERT INTO `doc_dopdata` (`doc`,`param`,`value`)
			VALUES ('$ndoc','rasxodi','6')");
			if(mysql_errno())	throw new MysqlException("Не удалось записать вид расходов");
			mysql_query("COMMIT");
			$ref="Location: doc.php?mode=body&doc=$ndoc";
			header($ref);
		}
		else
		{
			$tmpl->msg("В разработке","info");
		}
	}
	
	//	================== Функции только этого класса ======================================================
	function Otgruzka()
	{
		$target_type=2;
		global $tmpl;
		global $uid;

		$res=mysql_query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$this->doc}' AND `type`='$target_type'");
		@$r_id=mysql_result($res,0,0);
		if(!$r_id)
		{
			$altnum=GetNextAltNum($target_type, $this->doc_data[10]);
			$tm=time();
			$sum=DocSumUpdate($this->doc);
			$res=mysql_query("INSERT INTO `doc_list`
			(`type`, `agent`, `date`, `sklad`, `user`, `altnum`, `subtype`, `p_doc`, `sum`, `nds`, `firm_id`)
			VALUES ('$target_type', '{$this->doc_data[2]}', '$tm', '{$this->doc_data[7]}', '$uid', '$altnum', '{$this->doc_data[10]}', '{$this->doc}', '$sum', '{$this->doc_data[12]}', '{$this->doc_data[17]}')");
			
			$r_id= mysql_insert_id();

			if(!$r_id) return 0;
			
			doc_log("CREATE", "FROM {$this->doc_name} {$this->doc_name}", 'doc', $r_id);
			
			mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
			VALUES ('$r_id','cena','{$this->dop_data['cena']}')");

			$res=mysql_query("SELECT `tovar`, `cnt`, `sn`, `comm`, `cost` FROM `doc_list_pos`
			WHERE `doc_list_pos`.`doc`='{$this->doc}'");
			while($nxt=mysql_fetch_row($res))
			{
				mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `sn`, `comm`, `cost`)
				VALUES ('$r_id', '$nxt[0]', '$nxt[1]', '$nxt[2]', '$nxt[3]', '$nxt[4]' )");
			}
		}
		else
		{
			$new_id=0;
			$res=mysql_query("SELECT `a`.`tovar`, `a`.`cnt`, `a`.`sn`, `a`.`comm`, `a`.`cost`,
			( SELECT SUM(`b`.`cnt`) FROM `doc_list_pos` AS `b`
			INNER JOIN `doc_list` ON `b`.`doc`=`doc_list`.`id` AND `doc_list`.`p_doc`='{$this->doc}' AND `doc_list`.`mark_del`='0'
			WHERE `b`.`tovar`=`a`.`tovar` )
			FROM `doc_list_pos` AS `a`
			WHERE `a`.`doc`='{$this->doc}'");

			while($nxt=mysql_fetch_row($res))
			{
				//echo"$nxt[5] - $nxt[1]<br>";
				if($nxt[5]<$nxt[1])
				{
					
					if(!$new_id)
					{
						$altnum=GetNextAltNum($target_type, $this->doc_data[10]);
						$tm=time();
						$sum=DocSumUpdate($this->doc);
						$rs=mysql_query("INSERT INTO `doc_list`
						(`type`, `agent`, `date`, `sklad`, `user`, `altnum`, `subtype`, `p_doc`, `sum`, `nds`, `firm_id`)
						VALUES ('$target_type', '{$this->doc_data[2]}', '$tm', '{$this->doc_data[7]}', '$uid', '$altnum', '{$this->doc_data[10]}', '{$this->doc}', '$sum', '{$this->doc_data[12]}', '{$this->doc_data[17]}')");
						$new_id= mysql_insert_id();
						
						mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
						VALUES ('$new_id','cena','{$this->dop_data['cena']}')");
					}
					$n_cnt=$nxt[1]-$nxt[5];
					mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `sn`, `comm`, `cost`)
 					VALUES ('$new_id', '$nxt[0]', '$n_cnt', '$nxt[2]', '$nxt[3]', '$nxt[4]' )");
				}
			}
			if($new_id)
			{
				$r_id=$new_id;
				DocSumUpdate($new_id);
			}
		}

		return $r_id;
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
				doc_log("DELETE {$this->doc_name}",'','doc',$doc);
				return 0;
			}
		}
		return 1;
   	}
   	
	function Service($doc)
	{
		$tmpl->ajax=1;
		$opt=rcv('opt');
		$pos=rcv('pos');
		parent::_Service($opt,$pos);
	}

};


?>