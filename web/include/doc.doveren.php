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


$doc_types[10]="Доверенность";

class doc_Doveren extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=10;
		$this->doc_name				='doveren';
		$this->doc_viewname			='Доверенность';
		$this->sklad_editor_enable		=true;
		$this->sklad_modify			=0;
		$this->header_fields			='agent';
		settype($this->doc,'int');
	}

	function DopHead()
	{
		global $tmpl;
		$tmpl->AddText("На получение от:<br>
		<input type='text' name='ot' value='{$this->dop_data['ot']}'><br>");	
	}

	function DopSave()
	{
		$ot=rcv('ot');

		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
			VALUES ('{$this->doc}','ot','$ot')");
	}
	
	function DopBody()
	{
		global $tmpl;
		$tmpl->AddText("<b>На получение от:</b> {$this->dop_data['ot']}");
	}

	function DocApply($silent=0)
	{
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)			throw new MysqlException('Ошибка выборки данных документа!');
		$nx=@mysql_fetch_row($res);
		if(!$nx)			throw new Exception('Документ не найден!');
		if( $nx[1] && (!$silent) )	throw new Exception('Документ уже был проведён!');
		if($silent)	return;
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if(!$res)			throw new MysqlException('Ошибка установки даты проведения документа!');	
	}
	
	function DocCancel()
	{
		global $uid;
		$tim=time();
		$dd=date_day($tim);
		$rights=getright('doc_doverennost',$uid);
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка выборки данных документа!');
		if(! ($nx=@mysql_fetch_row($res)))	throw new Exception('Документ не найден!');	
		if(! $nx[4])				throw new Exception('Документ НЕ проведён!');
		if((!$rights['edit'])&&($dd>$nx[1]))	throw new AccessException('');
		
		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка установки флага!');
	}

	function Cancel($doc)
	{
	    global $tmpl;
	    global $uid;

	    $tmpl->ajax=1;
	    
		$rights=getright('doc_doverennost',$uid);

		mysql_query("START TRANSACTION");
		mysql_query("LOCK TABLE `doc_list`, `doc_list_pos`, `doc_base_cnt` READ ");
		$err='';
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='$doc'");
		if($nx=@mysql_fetch_row($res))
		{
			if($nx[4])
			{
				$dd=date_day(time());
				if(($rights['edit'])||($dd<$nx[1]))
				{
			
					$tim=time();
					$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='$doc'");
					if($res)
					{
						if(!$err)
						{
							$tmpl->AddText("<h3>Докумен успешно отменён!</h3>");
							$tmpl->AddText(but_provodka($doc,0));
						}
					}
					else $err="Ошибка отмены проведения, ошибка установки флага";
				}
				else $tmpl->msg("Недостаточно привилегий для выполнения операции!","err");
			}
			else $err="Докумен НЕ проведён!";
		}
		else $err="Ошибка отмены проведения, ошибка выборки";

		if(!$err)
		{
			mysql_query("COMMIT");
			doc_log("Cancel doverennost","doc:$doc");
		}
		else
		{
			mysql_query("ROLLBACK");
			doc_log("ERROR: Cancel doverennost - $err","doc:$doc");
			$tmpl->AddText("<h3>$err</h3>");

		}
		mysql_query("UNLOCK TABLE `doc_list`, `doc_list_pos`, `doc_base`");
	}


	function PrintForm($doc, $opt='')
	{
		if($opt=='')
		{
			global $tmpl;
			$tmpl->ajax=1;
			$tmpl->AddText("<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=dov'\">Доверенность</div>");
		}
		else $this->PrintDov($doc);
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
			$tmpl->AddText("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc=$doc&amp;tt=1'\">
			<li><a href=''>Поступление товара</div>");
		}
		else if($target_type==1)
		{
			$sum=DocSumUpdate($doc);
			mysql_query("START TRANSACTION");
			$tm=time();
			$altnum=GetNextAltNum($target_type ,$doc_data[10]);
			$res=mysql_query("INSERT INTO `doc_list`
			(`type`, `agent`, `date`, `sklad`, `user`, `altnum`, `subtype`, `p_doc`, `sum`)
			VALUES ('$target_type', '$doc_data[2]', '$tm', '1', '$uid', '$altnum', '$doc_data[10]', '$doc', '$sum')");
			$ndoc= mysql_insert_id();

			if($res)
			{
				mysql_query("COMMIT");
				$ref="Location: doc.php?mode=body&doc=$ndoc";
				header($ref);
			}
			else
			{
				mysql_query("ROLLBACK");
				$tmpl->msg("Не удалось создать подчинённый документ!","err");
			}
		}
		else
		{
			$tmpl->msg("В разработке","info");
		}
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
	function Service($doc)
	{
		get_docdata($doc);
		global $tmpl;
		global $uid;
		global $doc_data;
		global $dop_data;

		$tmpl->ajax=1;
		$opt=rcv('opt');
		$pos=rcv('pos');

		$rights=getright('doc_realizaciya',$uid);
		if($rights['write'])
		{
			if($doc_data[6])
				$tmpl->msg("Операция не допускается для проведённого документа!","err");
			else if($doc_data[14])
				$tmpl->msg("Операция не допускается для документа, отмеченного для удаления!","err");
			// Количество
			else if($opt=='cnt')
			{
				$res=mysql_query("SELECT `cnt` FROM `doc_list_pos` WHERE `id`='$pos'");
				$nxt=mysql_fetch_row($res);

				$tmpl->AddText("<form action='' onsubmit=\"EditThisSave('/doc.php?mode=srv&opt=cnts&doc=$doc&pos=$pos','poslist','val'); return false;\" ><input type=text class=mini id=val value='$nxt[0]'><input type=submit value='.'  class=hidden></form>");
			}
			else if($opt=='cnts')
			{
				$s=rcv('s');
				//$tmpl->AddText("<a href='' onclick=\"EditThis('/doc.php?mode=srv&opt=cnt&doc=$doc&pos=$pos','cnt$pos'); return false;\" >$s</a>");
				$res=mysql_query("UPDATE `doc_list_pos` SET `cnt`='$s' WHERE `doc`='$doc' AND `id`='$pos'");
				DocSumUpdate($doc);
				doc_poslist($doc);
			}
			// Комментарий
			else if($opt=='com')
			{
				$res=mysql_query("SELECT `comm` FROM `doc_list_pos` WHERE `id`='$pos'");
				$nxt=mysql_fetch_row($res);

				$tmpl->AddText("<form action='' onsubmit=\"EditThisSave('/doc.php?mode=srv&opt=coms&doc=$doc&pos=$pos','poslist','val'); return false;\" ><input type=text id=val value='$nxt[0]'><input type=submit value='.'  class=hidden></form>");
			}
			else if($opt=='coms')
			{
				$s=rcv('s');
				$res=mysql_query("UPDATE `doc_list_pos` SET `comm`='$s' WHERE `doc`='$doc' AND `id`='$pos'");
				//DocSumUpdate($doc);	- Это не нужно при смене комментария
				doc_poslist($doc);
			}
			// Цена
			else if($opt=='cost')
			{
				$res=mysql_query("SELECT `cost` FROM `doc_list_pos` WHERE `id`='$pos'");
				$nxt=mysql_fetch_row($res);

				$tmpl->AddText("<form action='' onsubmit=\"EditThisSave('/doc.php?mode=srv&opt=costs&doc=$doc&pos=$pos','poslist','val'); return false;\" ><input type=text class=mini id=val value='$nxt[0]'><input type=submit value='.'  class=hidden></form>");
			}
			else if($opt=='costs')
			{
				$s=rcv('s');
				$s_p=sprintf("%01.2f",$s);
				if($s<0) $s=0;
				//$tmpl->AddText("<a href='' onclick=\"EditThis('/doc.php?mode=srv&opt=cost&doc=$doc&pos=$pos','cost$pos'); return false;\" >$s_p</a>");
				$res=mysql_query("UPDATE `doc_list_pos` SET `cost`='$s' WHERE `doc`='$doc' AND `id`='$pos'");
				DocSumUpdate($doc);
				doc_poslist($doc);
			}
			else if($opt=='sklad')
			{
				$group=rcv('group');
				doc_sklad($doc, $group);
			}
			else if($opt=='pos')
			{
				$pos=rcv('pos');
				$res=mysql_query("SELECT `cost` FROM `doc_base` WHERE `id`='$pos'");
				$cost=mysql_result($res,0,0);
				$res=mysql_query("SELECT `doc_ceni`.`coeff` FROM `doc_dopdata`
				LEFT JOIN `doc_ceni` ON `doc_ceni`.`id`=`doc_dopdata`.`value`
				WHERE `doc_dopdata`.`doc`='$doc' AND  `doc_dopdata`.`param`='cena' ");
				$nx=mysql_fetch_row($res);
				$coeff_ceni=$nx[0];
				if(!$coeff_ceni) $coeff_ceni=1;
				$cost*=$coeff_ceni;
				
				$res=mysql_query("SELECT `id` FROM `doc_list_pos` WHERE `doc`='$doc' AND `tovar`='$pos'");
				if(mysql_num_rows($res)==0)
					mysql_query("INSERT INTO doc_list_pos (`doc`,`tovar`,`cnt`,`sn`,`comm`,`cost`) VALUES ('$doc','$pos','1','','','$cost')");
				else
					mysql_query("UPDATE `doc_list_pos` SET `cnt`=`cnt`+'1' WHERE `doc`='$doc' AND `tovar`='$pos'");
				if(mysql_error())
				{
					doc_log("ERROR: realizaciya - insert position - INSERT","$doc");
					$tmpl->msg("Ошибка добавления!","err");
				}
				else
				{
					DocSumUpdate($doc);
					doc_poslist($doc);
					doc_log("Add position","doc:$doc, pos:$pos");
				}

			}
			else if($opt=='del')
			{
				$res=mysql_query("DELETE FROM `doc_list_pos` WHERE `id`='$pos'");
				DocSumUpdate($doc);
				doc_poslist($doc);
				doc_log("Del position","doc:$doc, pos:$pos");
			}
			else $tmpl->AddText("Invalid opt!");
		}
		else $tmpl->msg("Недостаточно привилегий для выполнения операции!","err");
	}
//	================== Функции только этого класса ======================================================

// -- Обычная накладная --------------
	function PrintDov($doc)
	{
		get_docdata($doc);
		global $tmpl;
		global $uid;
		global $doc_data;
		global $dop_data;
		global $dv;

		$tmpl->LoadTemplate('print');
		$dt=date("d.m.Y",$doc_data[5]);
		$dtdo=date("d.m.Y",$doc_data[5]+60*60*24*30);

		$res=mysql_query("SELECT `fullname`, `pdol`, `pasp_num`, `pasp_kem`, `pasp_date` FROM `doc_agent`
		WHERE `id`='$doc_data[2]'");
		$ag=mysql_fetch_row($res);

		$tmpl->AddText("<table width=800 class='nb'>
		<tr><td align='right' class='nb mini'>
		Типовая межотраслевая форма № M-2a<br>
		Утверждена постановлением<br>
		Косгомстата России от 30.10.1997 № 71a<br>
		<table width=300 class='nb mini'>
		<tr><td class='nb'>&nbsp;<td>Коды
		<tr><td class='nb' align='right'>Форма по ОКУД<th>0315002
		<tr><td class='nb' align='right'> по ОКПО<th>71480021
		</table>
		<tr><td class='nb' align='center'>
		<h3>Организация:".$dv['firm_name']."</h3>		
		<h1>Доверенность N $doc_data[9]$doc_data[10]</h1>
		<b>Дата выдачи:</b> $dt<br>
		<b>Действительна до:</b> $dtdo<br>
		<b>Наименование потребителя и его адрес: </b>".$dv['firm_name'].", ".$dv['firm_adres']."<br>
		<b>Наименование плательщика и его адрес: </b>".$dv['firm_name'].", ".$dv['firm_adres']."<br>
		р/с ".$dv['firm_schet'].", в банке ".$dv['firm_bank'].", БИК ".$dv['firm_bik'].", корр.сч. ".$dv['firm_bank_kor_s']."
		</table>
		<br>
		<b>Доверенность выдана для:</b> $ag[1] $ag[0]<br>
		<b>Номер паспорта: </b> $ag[2]<br>
		<b>Кем выдан: </b> $ag[3]<br>
		<b>Дата выдачи: </b> $ag[4]<br>
		На получение от ".$dop_data['ot']." материальных ценностей по № ___________ от ___________ <br>
		
		
		<br><h3>Перечень товарно-материальных ценностей, подлежащих получению</h3>
		<table width=800 cellspacing=0 cellpadding=0>
		<tr><th>№</th><th width=450>Наименование<th>Ед.изм.<th>Кол-во (прописью)");
		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt` FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_list_pos`.`tovar`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE `doc_list_pos`.`doc`='$doc'");
		$i=0;
		$ii=1;
		$sum=0;
		while($nxt=mysql_fetch_row($res))
		{
			$sm=$nxt[3]*$nxt[4];
			$cost = sprintf("%01.2f руб.", $nxt[4]);
			$cost2 = sprintf("%01.2f руб.", $sm);
			$cnt_pr=num2str($nxt[3],"sht",0);
			$tmpl->AddText("<tr align=right><td>$ii</td><td align=left>$nxt[0] $nxt[1] / $nxt[2]<td>Шт.<td>$nxt[3] ($cnt_pr)");
			$i=1-$i;
			$ii++;
			$sum+=$sm;
		}
		$ii--;
		$cost = sprintf("%01.2f руб.", $sum);

		$tmpl->AddText("</table>
		<p>Всего <b>$ii</b> наименований</p>
		<p>Подписль лица, получившего доверенность ______________________ удостоверяем</p>
		<p>Руководитель предприятия:_____________________________________ (".$dv['firm_director'].")</p>
		<p>MП</p>
		<p>Главный бухгалтер: ____________________________________ (".$dv['firm_buhgalter'].")</p>");

	}
	

};
?>