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

include_once($CONFIG['site']['location']."/include/doc.core.php");

include_once($CONFIG['site']['location']."/include/doc.predlojenie.php");
include_once($CONFIG['site']['location']."/include/doc.v_puti.php");

$doc_types[0]="Неопределённый документ";

/// Базовый класс для всех документов системы. Содержит основные методы для работы с документами.
class doc_Nulltype
{
	protected $doc;				///< ID документа
	protected $doc_type;			///< ID типа документа
	protected $doc_name;			///< Наименование документа	(для контроля прав и пр.)
	protected $doc_viewname;		///< Отображаемое название документа при просмотре и печати
	protected $sklad_editor_enable;		///< Разрешить отображение редактора склада

							// Значение следующих полей: +1 - увеличивает, -1 - уменьшает, 0 - не влияет
							// Документы перемещений должны иметь 0 в соответствующих полях !
	protected $sklad_modify;		///< Изменяет ли общие остатки на складе
	protected $bank_modify;			///< Изменяет ли общие средства в банке
	protected $kassa_modify;		///< Изменяет ли общие средства в кассе

	protected $header_fields;		///< Поля заголовка документа, доступные через форму редактирования
	protected $dop_menu_buttons;		///< Дополнительные кнопки меню
	protected $doc_data;			///< Основные данные документа
	protected $dop_data;			///< Дополнительные данные документа
	protected $firm_vars;			///< информация с данными о фирме

	public function __construct($doc=0)
	{
		$this->doc				=$doc;
		$this->doc_type				=0;
		$this->doc_name				='';
		$this->doc_viewname			='Неопределенный документ';
		$this->sklad_editor_enable		=false;
		$this->sklad_modify			=0;
		$this->bank_modify			=0;
		$this->kassa_modify			=0;
		$this->header_fields			='';
		$this->dop_menu_buttons			='';
		$this->get_docdata();
	}

	public function getDocNum()	{return $this->doc;}
	public function getDocData()	{return $this->doc_data;}
	public function getDopData()	{return $this->dop_data;}
	public function getViewName()	{return $this->doc_viewname;}
	public function SetDocData($name, $value)
	{
		if($this->doc)
		{
			$_name=mysql_real_escape_string($name);
			$_value=mysql_real_escape_string($value);
			mysql_query("UPDATE `doc_list` SET `$_name`='$_value' WHERE `id`='{$this->doc}'");
			if(mysql_errno())		throw new MysqlException("Не удалось сохранить основной параметр документа");
		}
		$this->doc_data[$name]=$value;
	}
	
	/// Установить дополнительные данные текущего документа
	/// TODO: Сделать аналогичную функцию с массивом key=>value изменяемых данных
	public function SetDopData($name, $value)
	{
		if($this->doc && $this->dop_data[$name]!=$value)
		{
			$_name=mysql_real_escape_string($name);
			$_value=mysql_real_escape_string($value);
			$ovalue=mysql_real_escape_string($this->dop_data[$name]);
			mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES ( '{$this->doc}' ,'$_name','$_value')");
			if(mysql_errno())		throw new MysqlException("Не удалось сохранить дополнительный параметр документа");
			doc_log("UPDATE {$this->doc_name}","$_name: ($ovalue => $_value)",'doc',$this->doc);
		}
		$this->dop_data[$name]=$value;
	}

	/// Создать документ с заданными данными
	public function Create($doc_data, $from='')
	{
		//var_dump($doc_data);
		global $uid, $CONFIG;
		if(!isAccess('doc_'.$this->doc_name,'create'))	throw new AccessException("");
		$date=time();
		$doc_data['altnum']=$this->GetNextAltNum($this->doc_type ,$doc_data['subtype'], date("Y-m-d",$doc_data['date']), $doc_data['firm_id']);

		$fields = mysql_list_fields($CONFIG['mysql']['db'], "doc_list");
		if(mysql_errno())	throw new MysqlException("Не удалось получить структуру таблицы документов");
		$columns = mysql_num_fields($fields);
		$col_array=array();
		for ($i = 0; $i < $columns; $i++)	$col_array[mysql_field_name($fields, $i)]=mysql_field_name($fields, $i);
		unset($col_array['id'],$col_array['date'],$col_array['type'],$col_array['user'],$col_array['ok']);



		$sqlinsert_keys="`date`, `type`, `user`";
		$sqlinsert_value="'$date', '".$this->doc_type."', '$uid'";
//  		echo"<br>";
//  		var_dump($col_array);
		foreach($col_array as $key)
		{
			if(isset($doc_data[$key]))
			{
				$sqlinsert_keys.=", `$key`";
				$sqlinsert_value.=", '{$doc_data[$key]}'";
			}
		}
		mysql_query("INSERT INTO `doc_list` ($sqlinsert_keys) VALUES ($sqlinsert_value)");
		if(mysql_errno())	throw new MysqlException("Не удалось создать документ");
		$this->doc=mysql_insert_id();
		doc_log("CREATE", "FROM {$doc_data['p_doc']} {$from}", 'doc', $this->doc);
		$this->get_docdata();
		return $this->doc;
	}
	
	/// Создать документ на основе данных другого документа
	public function CreateFrom($doc_obj)
	{
		$doc_data=$doc_obj->doc_data;
		$doc_data['p_doc']=$doc_obj->doc;
		$this->Create($doc_data);
		return $this->doc;
	}
	
	/// Создать документ с товарными остатками на основе другого документа
	public function CreateFromP($doc_obj)
	{
		$doc_data=$doc_obj->doc_data;
		$doc_data['p_doc']=$doc_obj->doc;
		$this->Create($doc_data);
		if($this->sklad_editor_enable)
		{
			$res=mysql_query("SELECT `tovar`, `cnt`, `cost`, `page`, `comm` FROM `doc_list_pos` WHERE `doc`='{$doc_obj->doc}' ORDER BY `doc_list_pos`.`id`");
			if(mysql_errno())	throw new MysqlException("Не удалось выбрать номенклатуру!");
			while($nxt=mysql_fetch_row($res))
			{
				mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`, `comm`)
				VALUES ('{$this->doc}', '$nxt[0]', '$nxt[1]', '$nxt[2]', '$nxt[3]', '$nxt[4]')");
				if(mysql_errno())	throw new MysqlException("Не удалось сохранить номенклатуру!");
			}
		}
		return $this->doc;
	}
	
	/// Создать несвязанный документ с товарными остатками из другого документа
	public function CreateParent($doc_obj)
	{
		$doc_data=$doc_obj->doc_data;
		$doc_data['p_doc']=0;
		$this->Create($doc_data);
		if($this->sklad_editor_enable)
		{
			$res=mysql_query("SELECT `tovar`, `cnt`, `cost`, `page`, `comm` FROM `doc_list_pos` WHERE `doc`='{$doc_obj->doc}' ORDER BY `doc_list_pos`.`id`");
			if(mysql_errno())	throw new MysqlException("Не удалось выбрать номенклатуру!");
			while($nxt=mysql_fetch_row($res))
			{
				mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`, `comm`)
				VALUES ('{$this->doc}', '$nxt[0]', '$nxt[1]', '$nxt[2]', '$nxt[3]', '$nxt[4]')");
				if(mysql_errno())	throw new MysqlException("Не удалось сохранить номенклатуру!");
			}
		}

		unset($this->doc_data);
		$this->get_docdata();
		return $this->doc;
	}

	/// Создать документ с товарными остатками на основе другого документа
	/// В новый документ войдут только те наименования, которых нет в других подчинённых документах
	public function CreateFromPDiff($doc_obj)
	{
		$doc_data=$doc_obj->doc_data;
		$doc_data['p_doc']=$doc_obj->doc;
		if($this->sklad_editor_enable)
		{
			$res=mysql_query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$doc_obj->doc}' AND `type`='{$this->doc_type}'");
			$child_count=mysql_num_rows($res);
		}
		$this->Create($doc_data);
		if($this->sklad_editor_enable)
		{
			if($child_count<1)
			{
				$res=mysql_query("SELECT `tovar`, `cnt`, `cost`, `page`, `comm` FROM `doc_list_pos` WHERE `doc`='{$doc_obj->doc}' ORDER BY `doc_list_pos`.`id`");
				if(mysql_errno())	throw new MysqlException("Не удалось выбрать номенклатуру!");
				while($nxt=mysql_fetch_row($res))
				{
					mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `cost`, `page`, `comm`)
					VALUES ('{$this->doc}', '$nxt[0]', '$nxt[1]', '$nxt[2]', '$nxt[3]', '$nxt[4]')");
					if(mysql_errno())	throw new MysqlException("Не удалось сохранить номенклатуру!");
				}
			}
			else
			{
				$res=mysql_query("SELECT `a`.`tovar`, `a`.`cnt`, `a`.`comm`, `a`.`cost`,
				( SELECT SUM(`b`.`cnt`) FROM `doc_list_pos` AS `b`
				INNER JOIN `doc_list` ON `b`.`doc`=`doc_list`.`id` AND `doc_list`.`p_doc`='{$doc_obj->doc}' AND `doc_list`.`mark_del`='0'
				WHERE `b`.`tovar`=`a`.`tovar` ), `a`.`page`
				FROM `doc_list_pos` AS `a`
				WHERE `a`.`doc`='{$doc_obj->doc}'
				ORDER BY `a`.`id`");

				while($nxt=mysql_fetch_row($res))
				{
					if($nxt[4]<$nxt[1])
					{
						$n_cnt=$nxt[1]-$nxt[4];
						mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `comm`, `cost`, `page`)
						VALUES ('{$this->doc}', '$nxt[0]', '$n_cnt', '$nxt[2]', '$nxt[3]', '$nxt[5]' )");
						if(mysql_errno())	throw new MysqlException("Не удалось сохранить номенклатуру!");
					}
				}
			}
		}
		return $this->doc;
	}
	
	/// Послать в связанный заказ событие с заданным типом.
	/// Полное название события будет doc:{$docname}:{$event_type}
	/// @param event_type Название события
	public function sentZEvent($event_type)
	{
		$event_name="doc:{$this->doc_name}:$event_type";
		if($this->doc_type==3)	$this->dispatchZEvent($event_name);
		else
		{
			$pdoc=$this->doc_data['p_doc'];
			while($pdoc)
			{
				$res=mysql_query("SELECT `id`, `type` FROM `doc_list` WHERE `id`=$pdoc");
				if(mysql_errno())		throw new MysqlException("Не удалось получить тип документа");
				if(!mysql_num_rows($res))	throw new Exception("Документ не найден");
				list($pdoc_id, $pdoc_type)=mysql_fetch_row($res);
				if($pdoc_type==3)
				{
					$doc=new doc_Zayavka($pdoc_id);
					$doc->dispatchZEvent($event_name);
					return;
				}
				$pdoc=$pdoc_id;
			}
		}
	}

	public function head()
	{
		global $tmpl;
		if($this->doc_type==0)
			$tmpl->msg("Невозможно создать документ без типа!",'err');
		else
		{
			$uid=@$_SESSION['uid'];
			if($this->doc_name) $object='doc_'.$this->doc_name;
			else $object='doc';
			if(!isAccess($object,'view'))	throw new AccessException("view");
			doc_menu($this->dop_buttons());
			$this->DrawHeadformStart();
			$fields=split(' ',$this->header_fields);
			foreach($fields as $f)
			{
				switch($f)
				{
					case 'agent':	$this->DrawAgentField(); break;
					case 'sklad':	$this->DrawSkladField(); break;
					case 'kassa':	$this->DrawKassaField();  break;
					case 'bank':	$this->DrawBankField();  break;
					case 'cena':	$this->DrawCenaField();  break;
					case 'sum':	$this->DrawSumField();  break;
					case 'separator':	$tmpl->AddText("<hr>");  break;
				}
			}
			if(method_exists($this,'DopHead'))	$this->DopHead();

			$this->DrawHeadformEnd();
		}
	}
	
	/// Применить изменения редактирования
	public function head_submit()
	{
		global $tmpl;
		global $uid;

		$doc=$this->doc;
		$type=$this->doc_type;

		if($this->doc_name) $object='doc_'.$this->doc_name;
		else $object='doc';

		$firm_id=rcv('firm');
		settype($firm_id,'int');
		if($firm_id<=0) $firm_id=1;
		$tim=time();

		$agent=rcv('agent');
		$comment=rcv('comment');

		@$date=strtotime(rcv('datetime'));

		$sklad=rcv('sklad');
		$subtype=rcv('subtype');
		$altnum=rcv('altnum');
		$nds=rcv('nds');
		$sum=rcv('sum');
		$bank=rcv('bank');
		$kassa=rcv('kassa');
		$contract=rcv('contract');

		$cena=rcv('cena');
		$cost_recalc=rcv('cost_recalc');

		if($date<=0) $date=time();
		if(!$altnum)	$altnum=$this->GetNextAltNum($this->doc_type, $subtype, date("Y-m-d",$date), $firm_id);

		$sqlupdate="`date`='$date', `firm_id`='$firm_id', `comment`='$comment', `altnum`='$altnum', `subtype`='$subtype'";
		$sqlinsert_keys="`date`, `ok`, `firm_id`, `type`, `comment`, `user`, `altnum`, `subtype`";
		$sqlinsert_value="'$date', '0', '$firm_id', '".$this->doc_type."', '$comment', '$uid', '$altnum', '$subtype'";
		$log_data='';
		
		if($this->doc)
		{
			if($this->doc_data['date']!=$date)		$log_data.="date: {$this->doc_data['date']}=>$date, ";
			if($this->doc_data['firm_id']!=$firm_id)	$log_data.="firm_id: {$this->doc_data['firm_id']}=>$firm_id, ";
			if($this->doc_data['comment']!=$comment)	$log_data.="comment: {$this->doc_data['comment']}=>$comment, ";
			if($this->doc_data['altnum']!=$altnum)		$log_data.="altnum: {$this->doc_data['altnum']}=>$altnum, ";
			if($this->doc_data['subtype']!=$subtype)	$log_data.="subtype: {$this->doc_data['subtype']}=>$subtype, ";
		}

		doc_menu($this->dop_buttons());

		if(@$this->doc_data[6])
			$tmpl->msg("Операция не допускается для проведённого документа!","err");
		else if(@$this->doc_data[14])
			$tmpl->msg("Операция не допускается для документа, отмеченного для удаления!","err");
		else
		{
			$fields=split(' ',$this->header_fields);
			$cena_update=false;
			foreach($fields as $f)
			{
				if($f=='separator')	continue;
				if($f=='cena')
				{
					$cena_update=true;
					$sqlupdate.=", `nds`='$nds'";
					$sqlinsert_keys.=", `nds`";
					$sqlinsert_value.=", '$nds'";
					if($cost_recalc)
					{
						$r=mysql_query("SELECT `id`, `tovar` FROM `doc_list_pos` WHERE `doc`='{$this->doc}'");
						if(mysql_errno())	throw new MysqlException("Не удалось выбрать список наименований документа");
						while($l=mysql_fetch_row($r))
						{
							$newcost=GetCostPos($l[1], $cena);
							mysql_query("UPDATE `doc_list_pos` SET `cost`='$newcost' WHERE `id`='$l[0]'");
							if(mysql_errno())	throw new MysqlException("Не удалось обновть цену строки документа");
						}
						DocSumUpdate($this->doc);
					}
					if($this->doc)
					{
						if($this->doc_data['nds']!=$nds)	$log_data.="nds: {$this->doc_data['nds']}=>$nds, ";
						if($this->dop_data['cena']!=$cena)	$log_data.="cena: {$this->doc_data['cena']}=>$cena, ";
					}
				}
				else if($f=='agent')
				{
					$sqlupdate.=", `$f`='${$f}', `contract`='$contract'";
					$sqlinsert_keys.=", `$f`, `contract`";
					$sqlinsert_value.=", '${$f}', '$contract'";
					if($this->doc)
					{
						if($this->doc_data[$f]!=$$f)			$log_data.="$f: {$this->doc_data[$f]}=>${$f}, ";
						if($this->dop_data['contract']!=$contract)	$log_data.="contract: {$this->doc_data['contract']}=>$cena, ";
					}
				}
				else
				{
					$sqlupdate.=", `$f`='${$f}'";
					$sqlinsert_keys.=", `$f`";
					$sqlinsert_value.=", '${$f}'";
					if($this->doc)
					{
						if($this->doc_data[$f]!=$$f)			$log_data.="$f: {$this->doc_data[$f]}=>${$f}, ";
					}
				}
			}

			if($this->doc)
			{
				if(!isAccess($object,'edit'))	throw new AccessException("");
				$res=mysql_query("UPDATE `doc_list` SET $sqlupdate WHERE `id`='$doc'");
				if(mysql_errno())	throw new MysqlException("Документ не сохранён");
				$link="/doc.php?doc=$doc&mode=body";
				if($log_data)	doc_log("UPDATE {$this->doc_name}", $log_data, 'doc', $this->doc);
			}
			else
			{
				if(!isAccess($object,'create'))	throw new AccessException("");
				$res=mysql_query("INSERT INTO `doc_list` ($sqlinsert_keys) VALUES	($sqlinsert_value)");
				if(mysql_errno())	throw new MysqlException("Документ не сохранён");
				$this->doc=$doc= mysql_insert_id();
				$link="/doc.php?doc=$doc&mode=body";
				doc_log("CREATE {$this->doc_name}","$sqlupdate",'doc',$doc);
			}

			if(method_exists($this,'DopSave'))	$this->DopSave();
			if($cena_update)	mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES ('$doc','cena','$cena')");
			if(mysql_errno())	throw new MysqlException("Цена не сохранена");
			if($link) header("Location: $link");
		}
		return $this->doc=$doc;
	}
	
	/// Сохранение заголовка документа и возврат результата в json формате
	public function json_head_submit()
	{
		global $uid, $tmpl;
		if($this->doc_name) $object='doc_'.$this->doc_name;
		else $object='doc';
		@$date=strtotime(rcv('datetime'));

		$firm_id=rcv('firm');
		settype($firm_id,'int');
		if($firm_id<=0) $firm_id=1;
		$tim=time();

		$agent=rcv('agent');
		$comment=rcv('comment');
		$sklad=rcv('sklad');
		$subtype=rcv('subtype');
		$altnum=rcv('altnum');
		$nds=rcv('nds');
		$sum=rcv('sum');
		$bank=rcv('bank');
		$kassa=rcv('kassa');
		$cena=rcv('cena');
		$contract=rcv('contract');
		settype($contract,'int');
		if($date<=0)	$date=time();

		if(!$altnum)	$altnum=$comment=$this->GetNextAltNum($this->doc_type, $subtype, date("Y-m-d",$date), $firm_id);


		$sqlupdate="`date`='$date', `firm_id`='$firm_id', `comment`='$comment', `altnum`='$altnum', `subtype`='$subtype'";
		$sqlinsert_keys="`date`, `ok`, `firm_id`, `type`, `comment`, `user`, `altnum`, `subtype`";
		$sqlinsert_value="'$date', '0', '$firm_id', '".$this->doc_type."', '$comment', '$uid', '$altnum', '$subtype'";
		$log_data='';
		if($this->doc)
		{
			if($this->doc_data['date']!=$date)		$log_data.="date: {$this->doc_data['date']}=>$date, ";
			if($this->doc_data['firm_id']!=$firm_id)	$log_data.="firm_id: {$this->doc_data['firm_id']}=>$firm_id, ";
			if($this->doc_data['comment']!=$comment)	$log_data.="comment: {$this->doc_data['comment']}=>$comment, ";
			if($this->doc_data['altnum']!=$altnum)		$log_data.="altnum: {$this->doc_data['altnum']}=>$altnum, ";
			if($this->doc_data['subtype']!=$subtype)	$log_data.="subtype: {$this->doc_data['subtype']}=>$subtype, ";
		}

		$tmpl->ajax=1;
		try
		{
			if($this->doc_data[6])		throw new Exception('Операция не допускается для проведённого документа');
			else if($this->doc_data[14])	throw new Exception('Операция не допускается для документа, отмеченного для удаления!');
			else
			{
				$fields=split(' ',$this->header_fields);
				$cena_update=false;
				foreach($fields as $f)
				{
					if($f=='separator')	continue;
					if($f=='cena')
					{
						$cena_update=true;
						$sqlupdate.=", `nds`='$nds'";
						$sqlinsert_keys.=", `nds`";
						$sqlinsert_value.=", '$nds'";
						if($this->doc)
						{
							if($this->doc_data['nds']!=$nds)	$log_data.="nds: {$this->doc_data['nds']}=>$nds, ";
							if($this->dop_data['cena']!=$cena)	$log_data.="cena: {$this->dop_data['cena']}=>$cena, ";
						}
					}
					else if($f=='agent')
					{
						$sqlupdate.=", `$f`='${$f}', `contract`='$contract'";
						$sqlinsert_keys.=", `$f`, `contract`";
						$sqlinsert_value.=", '${$f}', '$contract'";
						if($this->doc)
						{
							if($this->doc_data[$f]!=$$f)			$log_data.="$f: {$this->doc_data[$f]}=>${$f}, ";
							if($this->doc_data['contract']!=$contract)	$log_data.="contract: {$this->doc_data['contract']}=>$contract, ";
						}
					}
					else
					{
						$sqlupdate.=", `$f`='${$f}'";
						$sqlinsert_keys.=", `$f`";
						$sqlinsert_value.=", '${$f}'";
						if($this->doc)
						{
							if($this->doc_data[$f]!=$$f)			$log_data.="$f: {$this->doc_data[$f]}=>${$f}, ";
						}
					}
				}

				if($this->doc)
				{
					if(!isAccess($object,'edit'))	throw new AccessException("Недостаточно прав");
					$res=mysql_query("UPDATE `doc_list` SET $sqlupdate WHERE `id`='{$this->doc}'");
					if(mysql_errno())	throw new MysqlException("Документ не сохранён");
					$link="/doc.php?doc={$this->doc}&mode=body";
					if($log_data)	doc_log("UPDATE {$this->doc_name}", $log_data, 'doc', $this->doc);
				}
				else
				{
					if(!isAccess($object,'create'))	throw new AccessException("Недостаточно прав");
					$res=mysql_query("INSERT INTO `doc_list` ($sqlinsert_keys) VALUES	($sqlinsert_value)");
					if(mysql_errno())	throw new MysqlException("Документ не сохранён");
					$this->doc= mysql_insert_id();
					$link="/doc.php?doc={$this->doc}&mode=body";
					doc_log("CREATE {$this->doc_name}","$sqlupdate",'doc',$this->doc);
				}

				if(method_exists($this,'DopSave'))	$this->DopSave();
				if($cena_update)	mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES ('{$this->doc}','cena','$cena')");
				if(mysql_errno())	throw new MysqlException("Цена не сохранена");
				if($agent)	$b=DocCalcDolg($agent);
				else		$b=0;
				$tmpl->SetText("{response: 'ok', agent_balance: '$b'}");
			}
		}
		catch( Exception $e)
		{
			$tmpl->SetText("{response: 'err', text: '".$e->getMessage()."'}");
		}

	}
	
	/// Редактирование тела докумнета
	public function body()
	{
		global $tmpl, $uid;

		if($this->doc_name) $object='doc_'.$this->doc_name;
		else $object='doc';
		if(!isAccess($object,'view'))	throw new AccessException("");
		$doc_altnum=$this->doc_data[9].$this->doc_data[10];
		$dt=date("Y-m-d H:i:s",$this->doc_data[5]);
		doc_menu($this->dop_buttons());
		$tmpl->AddText("<div id='doc_container'>
		<div id='doc_left_block'>");
		$tmpl->AddText("<h1>{$this->doc_viewname} N{$this->doc}</h1>");

		$this->DrawLHeadformStart();
		$fields=explode(' ',$this->header_fields);
		foreach($fields as $f)
		{
			switch($f)
			{
				case 'agent':	$this->DrawAgentField(); break;
				case 'sklad':	$this->DrawSkladField(); break;
				case 'kassa':	$this->DrawKassaField();  break;
				case 'bank':	$this->DrawBankField();  break;
				case 'cena':	$this->DrawCenaField();  break;
				case 'sum':	$this->DrawSumField();  break;
				case 'separator':	$tmpl->AddText("<hr>");  break;
			}
		}
		if(method_exists($this,'DopHead'))	$this->DopHead();

		$this->DrawLHeadformEnd();

		$res=mysql_query("SELECT `doc_list`.`id`, `doc_types`.`name`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_list`.`ok` FROM `doc_list`
		LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		WHERE `doc_list`.`id`='{$this->doc_data['p_doc']}'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить данные документа-основания");
		if(@$nxt=mysql_fetch_row($res))
		{
			if($nxt[5]) $r='Проведённый';
			else $r='Непроведённый';
			$dt=date("d.m.Y H:i:s",$nxt[4]);
			$tmpl->AddText("<b>Относится к:</b><br>$r <a href='?mode=body&amp;doc=$nxt[0]'>$nxt[1] N$nxt[2]$nxt[3]</a>, от $dt");
		}

		$res=mysql_query("SELECT `doc_list`.`id`, `doc_types`.`name`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_list`.`ok` FROM `doc_list`
		LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
		WHERE `doc_list`.`p_doc`='{$this->doc}'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить данные подчинённых документов");
		$pod='';
		while(@$nxt=mysql_fetch_row($res))
		{
			if($nxt[5]) $r='Проведённый';
			else $r='Непроведённый';
			$dt=date("d.m.Y H:i:s",$nxt[4]);
			//if($pod!='')	$pod.=', ';
			$pod.="$r <a href='?mode=body&amp;doc=$nxt[0]'>$nxt[1] N$nxt[2]$nxt[3]</a>, от $dt<br>";
		}
		if($pod)	$tmpl->AddText("<br><b>Зависящие документы:</b><br>$pod");
		$tmpl->AddText("<br><b>Дата создания:</b>: {$this->doc_data['created']}<br>");
		if($this->doc_data[6])$tmpl->AddText("<b>Дата проведения:</b> ".date("Y-m-d H:i:s",$this->doc_data[6])."<br>");
		$tmpl->AddText("</div>
		<script type=\"text/javascript\">

		addEventListener('load',DocHeadInit,false)

		//DocHeadInit()
		</script>
		<div id='doc_main_block'>");

		if($this->doc_data['agent_dishonest'])
		{
			$tmpl->msg($this->doc_data['agent_comment'].' ','err',"Выбранный вами агент ({$this->doc_data['agent_name']}) - недобросовестный");
		}

		$res=@mysql_query("SELECT `doc_cost`.`name` FROM `doc_cost` WHERE `doc_cost`.`id`='{$this->dop_data['cena']}'");

        	$cena=@mysql_result($res,0,0);

        	$res=mysql_query("SELECT `doc_sklady`.`name` FROM `doc_sklady` WHERE `doc_sklady`.`id`='{$this->doc_data[7]}'");
        	$sklad=@mysql_result($res,0,0);

        	$res=@mysql_query("SELECT `doc_kassa`.`name` FROM `doc_kassa` WHERE `doc_kassa`.`ids`='bank' AND `doc_kassa`.`num`='{$this->doc_data[16]}'");
        	$bank=@mysql_result($res,0,0);

        	$res=@mysql_query("SELECT `doc_kassa`.`name` FROM `doc_kassa` WHERE `doc_kassa`.`ids`='kassa' AND `doc_kassa`.`num`='{$this->doc_data[15]}'");
        	$kassa=@mysql_result($res,0,0);

// 		$tmpl->AddText("<b>Дата:</b> $dt, ");
//
// 		$fields=split(' ',$this->header_fields);
// 		foreach($fields as $f)
// 		{
// 			switch($f)
// 			{
// 				case 'sklad':	$tmpl->AddText("<b>Склад:</b> $sklad, "); break;
// 				case 'cena':	$tmpl->AddText("<b>Цена:</b> $cena, ");  break;
// 				case 'bank':	$tmpl->AddText("<b>банк:</b> $bank, ");  break;
// 				case 'kassa':	$tmpl->AddText("<b>касса:</b> $kassa, ");  break;
// 				case 'sum':	$tmpl->AddText("<b>сумма:</b> ".$this->doc_data[11].", ");  break;
// 			}
// 		}
		$tmpl->AddText("<img src='/img/i_leftarrow.png' onclick='DocLeftToggle()' id='doc_left_arrow'><br>");
		if(strstr($this->header_fields, 'agent'))
		{
			//$tmpl->AddText("<b>Агент-партнер:</b> ".$this->doc_data[3].", ");
			$dolg=DocCalcDolg($this->doc_data[2]);
			if($dolg>0)
				$tmpl->AddText("<b>Общий долг агента:</b> <a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=dolgi&agent={$this->doc_data[2]}'); return false;\"  title='Подробно' href='/docs.php?l=inf&mode=srv&opt=dolgi&agent={$this->doc_data[2]}'><b class=f_red>$dolg</b> рублей</a><br>");
			else if($dolg<0)
				$tmpl->AddText("<b>Наш общий долг:</b> <a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=dolgi&agent={$this->doc_data[2]}'); return false;\"  title='Подробно' href='/docs.php?l=inf&mode=srv&opt=dolgi&agent={$this->doc_data[2]}'>$dolg рублей</a><br>");
		}

 		if(method_exists($this,'DopBody'))
 			$this->DopBody();


		if($this->doc_data[4]) $tmpl->AddText("<b>Примечание:</b> ".$this->doc_data[4]."<br>");
		if($this->sklad_editor_enable)
		{
			include_once('doc.poseditor.php');
			$poseditor=new DocPosEditor($this);
			$poseditor->cost_id=$this->dop_data['cena'];
			$poseditor->sklad_id=$this->doc_data['sklad'];
			$poseditor->SetEditable($this->doc_data[6]?0:1);
			$tmpl->AddText($poseditor->Show());
		}

		$tmpl->AddText("<div id='statusblock'>");
		
		$tmpl->AddText("</div>");
		$tmpl->AddText("<br><br>
		</div></div>");
	}

	public function Apply($doc=0, $silent=0)
	{
		global $tmpl;
		global $uid;
		$tmpl->ajax=1;
		$cnt=0;
		$tim=time();

		try
		{
			mysql_query("START TRANSACTION");
			mysql_query("LOCK TABLE `doc_list`, `doc_list_pos`, `doc_base_cnt`, `doc_kassy` WRITE ");

			if(method_exists($this,'DocApply'))     $this->DocApply($silent);
			else    throw new Exception("Метод проведения данного документа не определён!");
			mysql_query("UPDATE `doc_list` SET `err_flag`='0' WHERE `id`='{$this->doc}'");
		}
		catch(MysqlException $e)
		{
			mysql_query("ROLLBACK");
			if(!$silent)
			{
				$tmpl->AddText("<h3>".$e->getMessage()."</h3>");
				doc_log("ERROR APPLY {$this->doc_name}", $e->getMessage(), 'doc', $this->doc);
			}
			mysql_query("UNLOCK TABLE `doc_list`, `doc_list_pos`, `doc_base`");
			return $e->getMessage().$e->sql_error;
		}
		catch( Exception $e)
		{
			mysql_query("ROLLBACK");
			if(!$silent)
			{
				$tmpl->AddText("<h3>".$e->getMessage()."</h3>");
				doc_log("ERROR APPLY {$this->doc_name}", $e->getMessage(), 'doc', $this->doc);
			}
			mysql_query("UNLOCK TABLE `doc_list`, `doc_list_pos`, `doc_base`");
			return $e->getMessage();
		}

		mysql_query("COMMIT");
		if(!$silent)
		{
			doc_log("APPLY {$this->doc_name}", '', 'doc', $this->doc);
			$tmpl->AddText("<h3>Докумен успешно проведён!</h3>");
		}
		mysql_query("UNLOCK TABLE `doc_list`, `doc_list_pos`, `doc_base`");
		return;
	}

	public function ApplyJson()
	{
		global $uid;
		$tim=time();

		try
		{
			if($this->doc_name) $object='doc_'.$this->doc_name;
			else $object='doc';
			if(!isAccess($object,'apply'))	throw new AccessException("");
			if($this->doc_data['mark_del'])	throw new Exception("Документ помечен на удаление!");
			$res=mysql_query("SELECT `recalc_active` FROM `variables`");
			if(mysql_errno())	throw new MysqlException("Не удалось выбрать данные блокировок!");
			if(mysql_result($res,0,0))	throw new Exception("Идёт пересчёт остатков. Проведение невозможно!");
			if(mysql_errno())	throw new MysqlException("Не удалось получить данные блокировок!");

			mysql_query("START TRANSACTION");
			mysql_query("LOCK TABLE `doc_list`, `doc_list_pos`, `doc_base_cnt`, `doc_kassy` WRITE ");
			if(method_exists($this,'DocApply'))	$this->DocApply(0);
			else	throw new Exception("Метод проведения данного документа не определён!");
			mysql_query("UPDATE `doc_list` SET `err_flag`='0' WHERE `id`='{$this->doc}'");
		}
		catch(MysqlException $e)
		{
			mysql_query("ROLLBACK");
			$e->WriteLog();
			mysql_query("UNLOCK TABLE `doc_list`, `doc_list_pos`, `doc_base`");
			$json=" { \"response\": \"0\", \"message\": \"".$e->getMessage()."\" }";
			return $json;
		}
		catch( Exception $e)
		{
			mysql_query("ROLLBACK");
			mysql_query("UNLOCK TABLE `doc_list`, `doc_list_pos`, `doc_base`");
			$json=" { \"response\": \"0\", \"message\": \"".$e->getMessage()."\" }";
			return $json;
		}

		mysql_query("COMMIT");
		doc_log("APPLY {$this->doc_name}", '', 'doc', $this->doc);
		$json=' { "response": "1", "message": "Документ успешно проведён!", "buttons": "'.$this->cancel_buttons().'", "sklad_view": "hide", "statusblock": "Дата проведения: '.date("Y-m-d H:i:s").'", "poslist": "refresh" }';
		mysql_query("UNLOCK TABLE `doc_list`, `doc_list_pos`, `doc_base`");
		return $json;
	}

	public function CancelJson()
	{
		global $uid;
		$tim=time();
		$dd=date_day($tim);
		if($this->doc_name) $object='doc_'.$this->doc_name;
		else $object='doc';

		try
		{

			if( !isAccess($object,'cancel') )
				if( (!isAccess($object,'cancel')) && ($dd>$this->doc_data['date']) )
					throw new AccessException("");

			$res=mysql_query("SELECT `recalc_active` FROM `variables`");
			if(mysql_errno())	throw new MysqlException("Не удалось выбрать данные блокировок!");
			if(mysql_result($res,0,0))	throw new Exception("Идёт пересчёт остатков. Проведение невозможно!");
			if(mysql_errno())	throw new MysqlException("Не удалось получить данные блокировок!");

			mysql_query("START TRANSACTION");
			mysql_query("LOCK TABLE `doc_list`, `doc_list_pos`, `doc_base_cnt`, `doc_kassy` WRITE ");
			$this->get_docdata();
			if(method_exists($this,'DocCancel'))	$this->DocCancel();
			else	throw new Exception("Метод отмены данного документа не определён!");
			mysql_query("UPDATE `doc_list` SET `err_flag`='0' WHERE `id`='{$this->doc}'");
		}
		catch(MysqlException $e)
		{
			mysql_query("ROLLBACK");
			$e->WriteLog();
			mysql_query("UNLOCK TABLE `doc_list`, `doc_list_pos`, `doc_base`");
			$json=" { \"response\": \"0\", \"message\": \"".$e->getMessage()."\" }";
			return $json;
		}
		catch( AccessException $e)
		{
			mysql_query("ROLLBACK");
			mysql_query("UNLOCK TABLE `doc_list`, `doc_list_pos`, `doc_base`");
			doc_log("CANCEL-DENIED {$this->doc_name}", $e->getMessage(), 'doc', $this->doc);
			$json=" { \"response\": \"0\", \"message\": \"Недостаточно привилегий для выполнения операции!<br>".$e->getMessage()."<br>Вы можете <a href='/message.php?mode=petition&doc={$this->doc}'>попросить руководителя</a> выполнить отмену этого документа.\" }";
			return $json;
		}
		catch( Exception $e)
		{
			mysql_query("ROLLBACK");
			mysql_query("UNLOCK TABLE `doc_list`, `doc_list_pos`, `doc_base`");
			$msg='';
			if( isAccess($object,'forcecancel') )
				$msg="<br>Вы можете <a href='/doc.php?mode=forcecancel&amp;doc={$this->doc}'>принудительно снять проведение</a>.";
			$json=" { \"response\": \"0\", \"message\": \"".$e->getMessage().$msg."\" }";
			return $json;
		}

		mysql_query("COMMIT");
		doc_log("CANCEL {$this->doc_name}", '', 'doc', $this->doc);
		$json=' { "response": "1", "message": "Документ успешно отменен!", "buttons": "'.$this->apply_buttons().'", "sklad_view": "show", "statusblock": "Документ отменён", "poslist": "refresh" }';
		mysql_query("UNLOCK TABLE `doc_list`, `doc_list_pos`, `doc_base`");
		return $json;
	}

	/// Отменить проведение
	function Cancel($doc)
	{
		global $tmpl;
		$tmpl->msg("Неизвестный тип документа, либо документ в процессе разработки!",err);
	}

	/// Отменить проведение, не обращая внимание на структуру подчинённости
	function ForceCancel()
	{
		global $tmpl, $uid;

		if($this->doc_name) $object='doc_'.$this->doc_name;
		else $object='doc';
		if(!isAccess($object,'forcecancel'))	throw new AccessException("");

		$opt=rcv('opt');
		if($opt=='')
		{
			$tmpl->AddText("<h2>Внимание! Опасная операция!</h2>Отмена производится простым снятием отметки проведения, без проверки зависимостией, учета структуры подчинённости и изменения значений счётчиков. Вы приниматете на себя все последствия данного действия. Вы точно хотите это сделать?<br>
			<center>
			<a href='/docj.php' style='color: #0b0'>Нет</a> |
			<a href='/doc.php?mode=forcecancel&amp;opt=yes&amp;doc={$this->doc}' style='color: #f00'>Да</a>
			</center>");
		}
		else
		{
			doc_log("FORCE CANCEL {$this->doc_name}",'', 'doc', $this->doc);
			$res=mysql_query("UPDATE `doc_list` SET `ok`='0', `err_flag`='1' WHERE `id`='{$this->doc}'");
			if(mysql_errno())	throw new MysqlException("Не удалось установить флаги!");
			mysql_query("UPDATE `variables` SET `corrupted`='1'");
			$tmpl->msg("Всё, сделано.","err","Снятие отметки проведения");
		}

	}
	
	/// Отправка документа по факсу
	final function SendFax($opt='')
	{
		global $tmpl;
		$tmpl->ajax=1;
		try
		{
			if($opt=='')
			{
				$str='';
				foreach($this->PDFForms as $form)
				{
					if($str)	$str.=",";
					$str.=" { name: '{$form['name']}', desc: '{$form['desc']}' }";
				}
				$res=mysql_query("SELECT `fax_phone` FROM `doc_agent` WHERE `id`='{$this->doc_data[2]}'");
				$faxnum=@mysql_result($res,0,0);
				$tmpl->SetText("{response: 'item_list', faxnum: '$faxnum', content: [$str]}");
			}
			else
			{
				$faxnum=$_GET['faxnum'];
				if($faxnum=='')		throw new Exception('Номер факса не указан');

				if(!preg_match('/^\+\d{8,15}$/', $faxnum))
					throw new Exception("Номер факса $faxnum указан в недопустимом формате");

				$method='';
				foreach($this->PDFForms as $form)
				{
					if($form['name']==$opt)	$method=$form['method'];
				}
				if(!method_exists($this,$method))	throw new Exception('Печатная форма не зарегистрирована');
				$res=@mysql_query("SELECT `worker_email` FROM `users_worker_info` WHERE `user_id`='{$_SESSION['uid']}'");
				$email=@mysql_result($res,0,0);
				include_once('sendfax.php');
				$fs=new FaxSender();
				$fs->setFileBuf($this->$method(1));
				$fs->setFaxNumber($faxnum);
				$fs->setNotifyMail($email);
				$res=$fs->send();
				$tmpl->SetText("{response: 'send'}");
				doc_log("Send FAX", $faxnum, 'doc', $this->doc);


			}
		}
		catch(Exception $e)
		{
			$tmpl->SetText("{response: 'err', text: '".$e->getMessage()."'}");
		}

	}
	
	/// Отправка документа по электронной почте
	final function SendEMail($opt='')
	{
		global $tmpl;
		$tmpl->ajax=1;
		try
		{
			if($opt=='')
			{
				$str='';
				foreach($this->PDFForms as $form)
				{
					if($str)	$str.=",";
					$str.=" { name: '{$form['name']}', desc: '{$form['desc']}' }";
				}
				$res=mysql_query("SELECT `email` FROM `doc_agent` WHERE `id`='{$this->doc_data[2]}'");
				$email=@mysql_result($res,0,0);
				$tmpl->SetText("{response: 'item_list', email: '$email', content: [$str]}");
			}
			else
			{
				$email=rcv('email');
				$comment=rcv('comment');
				if($email=='')
				{
					$tmpl->SetText("{response: 'err', text: 'Адрес электронной почты не указан!'}");
				}
				else
				{
					$tmpl->ajax=1;
					$method='';
					foreach($this->PDFForms as $form)
					{
						if($form['name']==$opt)	$method=$form['method'];
					}
					if(!method_exists($this,$method))	throw new Exception('Печатная форма не зарегистрирована');
					$this->SendDocEMail($email, $comment, $this->doc_viewname, $this->$method(1), $this->doc_name.".pdf");
					$tmpl->SetText("{response: 'send'}");
					doc_log("Send email", $email, 'doc', $this->doc);

				}
			}
		}
		catch(Exception $e)
		{
			$tmpl->SetText("{response: 'err', text: '".$e->getMessage()."'}");
		}

	}

	/// Печать документа
	function Printform($doc, $opt='')
	{
		global $tmpl;
		$tmpl->ajax=1;
		$tmpl->msg("Неизвестный тип документа, либо документ в процессе разработки!",'err');
	}
	
	/// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		global $tmpl;
		$tmpl->msg("Неизвестный тип документа, либо документ в процессе разработки!",'err');
	}
	
	/// Выполнить удаление документа. Если есть зависимости - удаление не производится.
	function DelExec($doc)
	{
		$res=mysql_query("SELECT `ok` FROM `doc_list` WHERE `id`='$doc'");
		if(mysql_result($res,0,0)) 	throw new Exception("Нельзя удалить проведённый документ");
		$res=mysql_query("SELECT `id`, `mark_del` FROM `doc_list` WHERE `p_doc`='$doc'");
		if(mysql_num_rows($res)) 	throw new Exception("Нельзя удалить документ с неудалёнными потомками");
		mysql_query("DELETE FROM `doc_list_pos` WHERE `doc`='$doc'");
		if(mysql_errno())		throw new MysqlException("Не удалось удалить товарные наименования");
		mysql_query("DELETE FROM `doc_dopdata` WHERE `doc`='$doc'");
		if(mysql_errno())		throw new MysqlException("Не удалось удалить дополнительные данные");
		mysql_query("DELETE FROM `doc_list` WHERE `id`='$doc'");
		if(mysql_errno())		throw new MysqlException("Не удалось удалить документ");
   	}

   	/// Сделать документ потомком указанного документа
   	function Connect($p_doc)
   	{
   		if(!isAccess('doc_'.$this->doc_name,'edit'))	throw new AccessException("Недостаточно привилегий");
   		if($this->doc_data[6])	throw new Exception("Операция не допускается для проведённого документа!");
		if($this->doc_name) $object='doc_'.$this->doc_name;
		else $object='doc';
		if(!isAccess($object,'edit'))	throw new AccessException("");
		mysql_query("UPDATE `doc_list` SET `p_doc`='$p_doc' WHERE `id`='{$this->doc}'");
   		if(mysql_errno())	throw new MysqlException("Не удалось обновить документ!");
   	}

   	function ConnectJson($p_doc)
	{
		try
		{
			$this->Connect($p_doc);
			return " { \"response\": \"1\" }";
		}
		catch(Exception $e)
		{
			return " { \"response\": \"0\", \"message\": \"".$e->getMessage()."\" }";
		}
	}

   	/// Получение информации, не связанной со складом, и допустимых для проведённых докеументов
   	function GetInfo()
   	{
		global $tmpl;
		$opt=rcv('opt');
		$tmpl->ajax=1;
		if( isAccess('doc_'.$this->doc_name,'view') )
		{

			if($opt=='jgetcontracts')
			{

				$agent=rcv('agent');
				$res=mysql_query("SELECT `doc_list`.`id`, `doc_dopdata`.`value`
				FROM `doc_list`
				LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='name'
				WHERE `agent`='$agent' AND `type`='14' AND `firm_id`='{$this->doc_data['firm_id']}'");
				if(mysql_errno())	throw new MysqlException("Не удаётся получить список договоров");
				$list='';
				while($nxt=mysql_fetch_row($res))
				{
					if($list)	$list.=", ";
					$list.="{ id: '$nxt[0]', name: '$nxt[1]' }";
				}
				$str="{ response: 'contract_list', content: [ $list ] }";
				$tmpl->SetText($str);
			}
		}
		else	throw new AccessException('Недостаточно привилегий');
   	}

   	function SendDocEMail($email, $comment, $docname, $data, $filename, $body='')
   	{
		global $CONFIG;
		require_once($CONFIG['location'].'/common/email_message.php');

		$res=mysql_query("SELECT `worker_real_name`, `worker_phone`, `worker_email` FROM `users_worker_info` WHERE `id`='{$this->doc_data['user']}'");
		$doc_autor=@mysql_fetch_assoc($res);

		$res=mysql_query("SELECT `name`, `fullname`, `email` FROM `doc_agent` WHERE `id`='{$this->doc_data['agent']}'");
		$agent=@mysql_fetch_assoc($res);

		$email_message=new email_message_class();
		$email_message->default_charset="UTF-8";
		if($agent['fullname'])	$email_message->SetEncodedEmailHeader("To", $email, $agent['fullname']);
		else if($agent['name'])	$email_message->SetEncodedEmailHeader("To", $email, $agent['name']);
		else			$email_message->SetEncodedEmailHeader("To", $email, $email);

		$email_message->SetEncodedHeader("Subject", "{$CONFIG['site']['display_name']} - $docname ({$CONFIG['site']['name']})");

		if(!@$doc_autor['worker_email'])
		{
			$email_message->SetEncodedEmailHeader("From", $CONFIG['site']['admin_email'], "Почтовый робот {$CONFIG['site']['name']}");
			$email_message->SetHeader("Sender",$CONFIG['site']['admin_email']);
			$text_message = "Здравствуйте, {$agent['fullname']}!\nВо вложении находится заказанный Вами документ ($docname) от {$CONFIG['site']['display_name']} ({$CONFIG['site']['name']})\n\n$comment\n\nСообщение сгенерировано автоматически, отвечать на него не нужно!\nДля переписки используйте адрес, указанный в контактной информации на сайте http://{$CONFIG['site']['name']}!";
		}
		else
		{
			$email_message->SetEncodedEmailHeader("From", $doc_autor['worker_email'], $doc_autor['worker_real_name']);
			$email_message->SetHeader("Sender", $doc_autor['worker_email']);
			$text_message = "Здравствуйте, {$agent['fullname']}!\nВо вложении находится заказанный Вами документ ($docname) от {$CONFIG['site']['name']}\n\n$comment\n\nОтветственный сотрудник: {$doc_autor['worker_real_name']}\nКонтактный телефон: {$doc_autor['worker_phone']}\nЭлектронная почта (e-mail): {$doc_autor['worker_email']}";
			$text_message.="\nОтправитель: {$_SESSION['name']}";
		}
		if($body)	$email_message->AddQuotedPrintableTextPart($body);
		else		$email_message->AddQuotedPrintableTextPart($text_message);

		$text_attachment=array(
			"Data"=>$data,
			"Name"=>$filename,
			"Content-Type"=>"automatic/name",
			"Disposition"=>"attachment"
		);
		$email_message->AddFilePart($text_attachment);

		$error=$email_message->Send();

		if(strcmp($error,""))	throw new Exception($error);
		else			return 0;
   	}

	/// Служебные опции
	function _Service($opt, $pos)
	{
		//if(!$this->sklad_editor_enable) return 0;
		global $tmpl;
		global $uid;
		$tmpl->ajax=1;
		$doc=$this->doc;
		include_once('doc.poseditor.php');
		$poseditor=new DocPosEditor($this);
		$poseditor->cost_id=$this->dop_data['cena'];
		$poseditor->sklad_id=$this->doc_data['sklad'];

		if( isAccess('doc_'.$this->doc_name,'view') )
		{
			// Json-вариант списка товаров
			if($opt=='jget')
			{
				$doc_sum=DocSumUpdate($this->doc);
				$str="{ response: '2', content: [".$poseditor->GetAllContent()."], sum: '$doc_sum' }";
				$tmpl->AddText($str);
			}
			// Снять пометку на удаление
			else if($opt=='jundeldoc')
			{
				try
				{
					if(! isAccess('doc_'.$this->doc_name,'delete') )	throw new AccessException("Недостаточно привилегий");

					$res=mysql_query("UPDATE `doc_list` SET `mark_del`='0' WHERE `id`='{$this->doc}'");
					if(!$res)	throw new MysqlException("Не удалось снять метку");
					doc_log("UNDELETE", '', "doc", $this->doc);
					$json=' { "response": "1", "message": "Пометка на удаление снята!", "buttons": "'.$this->apply_buttons().'", "statusblock": "Документ не будет удалён" }';
					$tmpl->SetText($json);
				}
				catch(Exception $e)
				{
					$tmpl->SetText("{response: 0, message: '".$e->getMessage()."'}");
				}
			}
			/// TODO: Это тоже переделать!
			else if($this->doc_data['ok'])
				throw new Exception("Операция не допускается для проведённого документа!");
			else if($this->doc_data['mark_del'])
				throw new Exception("Операция не допускается для документа, отмеченного для удаления!");
			// Получение данных наименования
			else if($opt=='jgpi')
			{
				$pos=rcv('pos');
				$tmpl->AddText($poseditor->GetPosInfo($pos));
			}
			// Json вариант добавления позиции
			else if($opt=='jadd')
			{
				if(!isAccess('doc_'.$this->doc_name,'edit'))	throw new AccessException("Недостаточно привилегий");
				$pos=rcv('pos');
				$tmpl->SetText($poseditor->AddPos($pos));
			}
			// Json вариант удаления строки
			else if($opt=='jdel')
			{
				if(!isAccess('doc_'.$this->doc_name,'edit'))	throw new AccessException("Недостаточно привилегий");
				$line_id=rcv('line_id');
				$tmpl->SetText($poseditor->Removeline($line_id));
			}
			// Json вариант обновления
			else if($opt=='jup')
			{
				if(!isAccess('doc_'.$this->doc_name,'edit'))	throw new AccessException("Недостаточно привилегий");
				$line_id=rcv('line_id');
				$value=rcv('value');
				$type=rcv('type');
				$tmpl->SetText($poseditor->UpdateLine($line_id, $type, $value));
			}
			// Получение номенклатуры выбранной группы
			else if($opt=='jsklad')
			{
				$group_id=rcv('group_id');
				$str="{ response: 'sklad_list', group: '$group_id',  content: [".$poseditor->GetSkladList($group_id)."] }";
				$tmpl->SetText($str);
			}
			// Поиск по подстроке по складу
			else if($opt=='jsklads')
			{
				$s=rcv('s');
				$str="{ response: 'sklad_list', content: [".$poseditor->SearchSkladList($s)."] }";
				$tmpl->SetText($str);
			}
			// Серийные номера
			else if($opt=='jsn')
			{
				$action=rcv('a');
				$line_id=rcv('line');
				$data=rcv('data');
				$tmpl->SetText($poseditor->SerialNum($action, $line_id, $data) );
			}
			// Сброс цен
			else if($opt=='jrc')
			{
				$this->ResetCost();
				DocSumUpdate($this->doc);
			}
			// Сортировка наименований
			else if($opt=='jorder')
			{
				$by=rcv('by');
				$poseditor->reOrder($by);
			}
			else if($opt=='jdeldoc')
			{
				try
				{
					if(! isAccess('doc_'.$this->doc_name,'delete') )	throw new AccessException("Недостаточно привилегий");
					$tim=time();

					$res=mysql_query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$this->doc}' AND `mark_del`='0'");
					if(!$res)	throw new MysqlException("Не удалось получить список потомков");
					if(!mysql_num_rows($res)) // Если есть потомки - нельзя удалять
					{
						$res=mysql_query("UPDATE `doc_list` SET `mark_del`='$tim' WHERE `id`='{$this->doc}'");
						if(!$res)	throw new MysqlException("Не удалось пометить на удаление");
						doc_log("MARKDELETE",  '', "doc", $this->doc);
						$this->doc_data['mark_del']=$tim;
						$json=' { "response": "1", "message": "Пометка на удаление установлена!", "buttons": "'.$this->apply_buttons().'", "statusblock": "Документ помечен на удаление" }';
						$tmpl->SetText($json);
					}
					else	throw new Exception("Есть подчинённые не удалённые документы. Удаление невозможно.");
				}
				catch(Exception $e)
				{
					$tmpl->SetText("{response: 0, message: '".$e->getMessage()."'}");
				}
			}
			// Не-json обработчики
			// Серийный номер
			else if($opt=='sn')
			{
				if($this->doc_type==1)		$column='prix_list_pos';
				else if($this->doc_type==2)	$column='rasx_list_pos';
				else				throw new Exception("В данном документе серийные номера не используются!");
				$res=mysql_query("SELECT `doc_list_sn`.`id`, `doc_list_sn`.`num`, `doc_list_sn`.`rasx_list_pos` FROM `doc_list_sn` WHERE `$column`='$pos'");
				$tmpl->AddText("<div style='width: 300px; height: 200px; border: 1px solid #ccc; overflow: auto;'><table width='100%' id='sn_list'>
				<tr><td style='width: 20px'><td>");
				while($nxt=mysql_fetch_row($res))
				{
					$tmpl->AddText("<tr id='snl$nxt[0]'><td><img src='/img/i_del.png' alt='Удалить'><td>$nxt[1]");
				}
				$tmpl->AddText("</table></div>
				<input type='text' name='sn' id='sn'><button type='button' onclick='DocSnAdd($doc,$pos);'>&gt;&gt;</button>");
			}
			else if($opt=='snp')
			{
				$pos=rcv('pos');
				$sn=rcv('sn');
				$tmpl->ajax=1;

				$tmpl->SetText("");
				$res=mysql_query("SELECT `tovar` FROM `doc_list_pos` WHERE `id`='$pos'");
				if(mysql_errno())	throw new MysqlException("Не удалось получить строку докумнета!");
				$pos_id=mysql_result($res,0,0);
				$res=mysql_query("SELECT `doc_list_sn`.`id`, `doc_list_sn`.`num`, `doc_list_sn`.`rasx_list_pos` FROM `doc_list_sn`
				INNER JOIN `doc_list_pos` ON `doc_list_pos`.`id`=`doc_list_sn`.`prix_list_pos`
				INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`ok`>'0'
				WHERE `pos_id`='$pos_id'  AND `rasx_list_pos` IS NULL");
				if(mysql_errno())	throw new MysqlException("Не удалось выбрать номер");
				while($nxt=mysql_fetch_row($res))
				{
					$nxt[1]=unhtmlentities($nxt[1]);
					$tmpl->AddText("$nxt[1]|$nxt[0]\n");
				}

			}
			else if($opt=='sns')
			{
				$pos=rcv('pos');
				$sn=rcv('sn');
				$tmpl->ajax=1;
				try
				{
					$res=mysql_query("SELECT `tovar` FROM `doc_list_pos` WHERE `id`='$pos'");
					if(mysql_errno())	throw new MysqlException("Не удалось получить строку докумнета!");
					$pos_id=mysql_result($res,0,0);
					if($this->doc_type==1)
					{
						if($sn=='')		throw new Exception("Серийный номер не заполнен");
						$res=mysql_query("INSERT INTO `doc_list_sn` (`num`, `pos_id`, `prix_list_pos`) VALUES ('$sn', '$pos_id', '$pos')");
						if(mysql_errno())	throw new MysqlException("Не удалось добавить серийный номер");
						$ins_id=mysql_insert_id();
						$tmpl->SetText("{response: 1, sn_id: '$ins_id', sn: '$sn'}");

						//$tmpl->msg("Добавлено!");
					}
					else if($this->doc_type==2)
					{
						$res=mysql_query("SELECT `doc_list_sn`.`id`, `doc_list_sn`.`num`, `doc_list_sn`.`rasx_list_pos` FROM `doc_list_sn`
						INNER JOIN `doc_list_pos` ON `doc_list_pos`.`id`=`doc_list_sn`.`prix_list_pos`
						INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`ok`>'0'
						WHERE `pos_id`='$pos_id' AND `num`='$sn' AND `rasx_list_pos` IS NULL");
						if(mysql_errno())	throw new MysqlException("Не удалось выбрать номер");
						if(!$nxt=mysql_fetch_row($res))
						{
							$tmpl->SetText("{response: 0, message: 'Номер не найден, уже добавлен или находится в непроведённом документе!'}");
						}
						else
						{
							mysql_query("UPDATE `doc_list_sn` SET `rasx_list_pos`='$pos' WHERE `id`='$nxt[0]'");
							if(mysql_errno())	throw new MysqlException("Не удалось записать номер");
							$tmpl->SetText("{response: 1, sn_id: '$nxt[0]', sn: '$sn'}");
						}
					}
					else				throw new Exception("В данном документе серийные номера не используются!");
				}
				catch(Exception $e)
				{
					$tmpl->SetText("{response: 0, message: '".$e->getMessage()."'}");
				}
				//$tmpl->logger("Save sn",0,"doc:$doc, type:".$this->doc_name.", opt:$opt, pos:$pos, mysql:".mysql_error());
			}
			else return 0;
			return 1;
		}
		else $tmpl->msg("Недостаточно привилегий для $uid выполнения операции над $object!","err");
	}

	protected function DrawLHeadformStart()
	{
		$this->DrawHeadformStart('j');
	}
	
	/// Отобразить заголовок шапки документа
	protected function DrawHeadformStart($alt='')
	{
		global $tmpl, $CONFIG;
		if($this->doc_data[5])	$dt=date("Y-m-d H:i:s",$this->doc_data[5]);
		else			$dt=date("Y-m-d H:i:s");
		$tmpl->AddText("<form method='post' action='' id='doc_head_form'>
		<input type='hidden' name='mode' value='{$alt}heads'>
		<input type='hidden' name='type' value='".$this->doc_type."'>");
		if(isset($this->doc_data['id']))
			$tmpl->AddText("<input type='hidden' name='doc' value='".$this->doc_data[0]."'>");
		if(@$this->doc_data['mark_del']) $tmpl->AddText("<h3>Документ помечен на удаление!</h3>");
		$tmpl->AddText("
		<table id='doc_head_main'>
		<tr><td class='altnum'>А. номер</td><td class='subtype'>Подтип</td><td class='datetime'>Дата и время</td><tr>
		<tr class='inputs'>
		<td class='altnum'><input type='text' name='altnum' value='".@$this->doc_data['altnum']."' id='anum'><a href='#' onclick=\"return GetValue('/doc.php?mode=incnum&type=".$this->doc_type."&amp;doc=".$this->doc."', 'anum', 'sudata', 'datetime', 'firm_id')\"><img border=0 src='/img/i_add.png' alt='Новый номер'></a></td>
		<td class='subtype'><input type='text' name='subtype' value='".@$this->doc_data['subtype']."' id='sudata'></td>
		<td class='datetime'><input type='text' name='datetime' value='$dt' id='datetime'></td>
		</tr>
		</table>
		Организация:<br><select name='firm' id='firm_id'>");
		$rs=mysql_query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `firm_name`");

		if(@$this->doc_data['firm_id']==0) $this->doc_data[17]=$CONFIG['site']['default_firm'];

		while($nx=mysql_fetch_row($rs))
		{
			if(@$this->doc_data['firm_id']==$nx[0]) $s=' selected'; else $s='';
			$tmpl->AddText("<option value='$nx[0]' $s>$nx[1] / $nx[0]</option>");
		}
		$tmpl->AddText("</select><br>");
	}

	protected function DrawLHeadformEnd()
	{
		global $tmpl;
		$tmpl->AddText("<br>Комментарий:<br><textarea name='comment'>{$this->doc_data[4]}</textarea></form>");
	}

	protected function DrawHeadformEnd()
	{
		global $tmpl;
		$tmpl->AddText(@"<br>Комментарий:<br><textarea name='comment'>{$this->doc_data['comment']}</textarea><br><input type=submit value='Записать'></form>");
	}

	protected function DrawAgentField()
	{
		global $tmpl;
		$balance=DocCalcDolg($this->doc_data[2]);
		$bonus=DocCalcBonus($this->doc_data[2]);
		$col='';
		if($balance>0)	$col="color: #f00; font-weight: bold;";
		if($balance<0)	$col="color: #f08; font-weight: bold;";

		$res=mysql_query(@"SELECT `doc_list`.`id`, `doc_dopdata`.`value`
		FROM `doc_list`
		LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='name'
		WHERE `agent`='{$this->doc_data[2]}' AND `type`='14' AND `firm_id`='{$this->doc_data['firm_id']}'");
		if(mysql_errno())	throw new MysqlException("Не удаётся получить список договоров");
		$contr_content='';
		while($nxt=mysql_fetch_row($res))
		{
			$selected=($this->doc_data['contract']==$nxt[0])?'selected':'';
			$contr_content.="<option value='$nxt[0]' $selected>N$nxt[0]: $nxt[1]</option>";
		}
		if($contr_content)	$contr_content="Договор:<br><select name='contract'>$contr_content</select>";

		$tmpl->AddText("
		<div>
		<div style='float: right; $col' id='agent_balance_info'>$balance / $bonus</div>
		Агент:
		<a href='/docs.php?l=agent&mode=srv&opt=ep&pos={$this->doc_data[2]}' id='ag_edit_link' target='_blank'><img src='/img/i_edit.png'></a>
		<a href='/docs.php?l=agent&mode=srv&opt=ep' target='_blank'><img src='/img/i_add.png'></a>
		</div>
		<input type='hidden' name='agent' id='agent_id' value='{$this->doc_data[2]}'>
		<input type='text' id='agent_nm'  style='width: 100%;' value='{$this->doc_data[3]}'>
		<div id='agent_contract'>$contr_content</div>
		<br>

		<script type=\"text/javascript\">
		$(document).ready(function(){
			$(\"#agent_nm\").autocomplete(\"/docs.php\", {
				delay:300,
				minChars:1,
				matchSubset:1,
				autoFill:false,
				selectFirst:true,
				matchContains:1,
				cacheLength:10,
				maxItemsToShow:15,
				formatItem:agliFormat,
				onItemSelect:agselectItem,
				extraParams:{'l':'agent','mode':'srv','opt':'ac'}
			});
		});

		function agliFormat (row, i, num) {
			var result = row[0] + \"<em class='qnt'>тел. \" +
			row[2] + \"</em> \";
			return result;
		}

		function agselectItem(li) {
			if( li == null ) var sValue = \"Ничего не выбрано!\";
			if( !!li.extra ) var sValue = li.extra[0];
			else var sValue = li.selectValue;
			document.getElementById('agent_id').value=sValue;
			document.getElementById('ag_edit_link').href='/docs.php?l=agent&mode=srv&opt=ep&pos='+sValue;
			UpdateContractInfo({$this->doc},sValue)
			");
		if(!$this->doc)		$tmpl->AddText("
			var plat_id=document.getElementById('plat_id');
			if(plat_id)	plat_id.value=li.extra[0];
			var plat=document.getElementById('plat');
			if(plat)	plat.value=li.selectValue;
			var gruzop_id=document.getElementById('gruzop_id');
			if(gruzop_id)	gruzop_id.value=li.extra[0];
			var gruzop=document.getElementById('gruzop');
			if(gruzop)	gruzop.value=li.selectValue;");
		$tmpl->AddText("
		}
		</script>");
	}

	protected function DrawSkladField()
	{
		global $tmpl;
		$tmpl->AddText("Склад:<br>
		<select name='sklad'>");
		$res=mysql_query("SELECT `id`,`name` FROM `doc_sklady` ORDER BY `id`");
		if(mysql_errno())	throw new Exception("Не удалось выбрать список складов");
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[0]==$this->doc_data[7])
				$tmpl->AddText("<option value='$nxt[0]' selected>$nxt[1]</option>");
			else
				$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");
		}
		$tmpl->AddText("</select><br>");
	}

	protected function DrawBankField()
	{
		global $tmpl;
		if(@$this->doc_data['firm_id'])	$sql_add="AND ( `firm_id`='0' OR `num`='{$this->doc_data[16]}' OR `firm_id`='{$this->doc_data['firm_id']}' )";
		else				$sql_add='';
		$tmpl->AddText("Банк:<br>
		<select name='bank'>");
		$res=mysql_query("SELECT `num`, `name`, `rs` FROM `doc_kassa` WHERE `ids`='bank'  $sql_add  ORDER BY `num`");
		if(mysql_errno())	throw new Exception("Не удалось выбрать список банков");
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[0]==@$this->doc_data[16])
				$tmpl->AddText("<option value='$nxt[0]' selected>$nxt[1] / $nxt[2]</option>");
			else
				$tmpl->AddText("<option value='$nxt[0]'>$nxt[1] / $nxt[2]</option>");
		}
		$tmpl->AddText("</select><br>");
	}

	protected function DrawKassaField()
	{
		global $tmpl;
		$tmpl->AddText("Касса:<br>
		<select name='kassa'>");
		$res=mysql_query("SELECT `num`, `name` FROM `doc_kassa` WHERE `ids`='kassa' AND `firm_id`='0' OR `num`='{$this->doc_data[16]}' ORDER BY `num`");
		if(mysql_errno())	throw new Exception("Не удалось выбрать список касс");
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[0]==$this->doc_data[15])
				$tmpl->AddText("<option value='$nxt[0]' selected>$nxt[1]</option>");
			else
				$tmpl->AddText("<option value='$nxt[0]'>$nxt[1]</option>");
		}
		$tmpl->AddText("</select><br>");
	}

	protected function DrawSumField()
	{
		global $tmpl;
		$tmpl->AddText(@"Сумма:<br>
		<input type='text' name='sum' value='{$this->doc_data[11]}'><img src='/img/i_+-.png'><br>");
	}

	protected function DrawCenaField()
	{
		global $tmpl;
		$tmpl->AddText("Цена:<a onclick='ResetCost(\"{$this->doc}\"); return false;' id='reset_cost'><img src='/img/i_reload.png'></a><br>
		<select name='cena'>");
		$res=mysql_query("SELECT `id`,`name` FROM `doc_cost` ORDER BY `name`");
		if(mysql_errno())	throw new Exception("Не удалось выбрать список цен");
		while($nxt=mysql_fetch_row($res))
		{
			if($this->dop_data['cena']==$nxt[0]) $s='selected';
			else $s='';
			$tmpl->AddText("<option value='$nxt[0]' $s>$nxt[1]</option>");
		}

		if($this->doc_data[12])
			$tmpl->AddText("<label><input type='radio' name='nds' value='0'>Выделять НДС</label>&nbsp;&nbsp;
			<label><input type='radio' name='nds' value='1' checked>Включать НДС</label><br>");
		else
			$tmpl->AddText("<label><input type='radio' name='nds' value='0' checked>Выделять НДС</label>&nbsp;&nbsp;
			<label><input type='radio' name='nds' value='1'>Включать НДС</label><br>");
		$tmpl->AddText("<br>");
	}

	// ====== Получение данных, связанных с документом =============================
	protected function get_docdata()
	{
		if($this->doc_data) return;
		global $CONFIG;
		if($this->doc)
		{
			$res=mysql_query("SELECT `a`.`id`, `a`.`type`, `a`.`agent`, `b`.`name` AS `agent_name`, `a`.`comment`, `a`.`date`, `a`.`ok`, `a`.`sklad`, `a`.`user`, `a`.`altnum`, `a`.`subtype`, `a`.`sum`, `a`.`nds`, `a`.`p_doc`, `a`.`mark_del`, `a`.`kassa`, `a`.`bank`, `a`.`firm_id`, `b`.`dishonest` AS `agent_dishonest`, `b`.`comment` AS `agent_comment`, `a`.`contract`, `a`.`created`, `b`.`fullname` AS `agent_fullname`
			FROM `doc_list` AS `a`
			LEFT JOIN `doc_agent` AS `b` ON `a`.`agent`=`b`.`id`
			WHERE `a`.`id`='".$this->doc."'");
			if(mysql_errno())	throw new MysqlException('Не удалось получить основные данные документа');
			$this->doc_data=mysql_fetch_array($res);
			$rr=mysql_query("SELECT `param`,`value` FROM `doc_dopdata` WHERE `doc`='".$this->doc."'");
			while($nn=mysql_fetch_row($rr))
			{
				$this->dop_data["$nn[0]"]=$nn[1];
			}
			$res=mysql_query("SELECT * FROM `doc_vars` WHERE `id`='{$this->doc_data[17]}'");
			$this->firm_vars=mysql_fetch_assoc($res);
		}
		else
		{
			$this->doc_data=array();
			$this->doc_data[2]=1;
			$res=mysql_query("SELECT `name` FROM `doc_agent` WHERE `id`='1'");
			if(mysql_errno())	throw new MysqlException("Не удалось получить имя агента по умолчанию");
			$this->doc_data[3]=@mysql_result($res,0,0);
			$this->doc_data[5]=$this->doc_data['date']=time();
			$this->doc_data[7]=1;
			$this->doc_data[12]=1;
			$res=mysql_query("SELECT `id`,`name` FROM `doc_cost` WHERE `vid`='1'");
			$this->dop_data['cena']=@mysql_result($res,0,0);
			$this->doc_data['contract']='';
// 			$res=mysql_query("SELECT * FROM `doc_vars` WHERE `id`='{$this->doc_data['firm_id']}'");
// 			$this->firm_vars=mysql_fetch_assoc($res);
		}
	}

	/// Получение альтернативного порядкового номера документа
	public function GetNextAltNum($doc_type, $subtype, $date, $firm_id)
	{
		global $CONFIG;
		$start_date=strtotime(date("Y-01-01 00:00:00",strtotime($date)));
		$end_date=strtotime(date("Y-12-31 23:59:59",strtotime($date)));
		$res=@mysql_query("SELECT `altnum` FROM `doc_list` WHERE `type`='$doc_type' AND `subtype`='$subtype' AND `id`!='{$this->doc}' AND `date`>='$start_date' AND `date`<='$end_date' AND `firm_id`='$firm_id' ORDER BY `altnum` ASC");
		if(mysql_errno())	throw new MysqlException("Не удалось получить выборку альтернативных номеров");
		$newnum=0;
		while($nxt=mysql_fetch_row($res))
		{
			if(($nxt[0]-1 > $newnum)&& @$CONFIG['doc']['use_persist_altnum'])	break;
			$newnum=$nxt[0];
		}
		$newnum++;
		return $newnum;
	}

	/// Кнопки меню - провети / отменить
	protected function dop_buttons()
	{
		global $tmpl;
		$ret='';
		if($this->doc)
		{
			$ret.="<a href='/docj.php?mode=log&amp;doc={$this->doc}' title='История изменений документа'><img src='img/i_log.png' alt='История'></a>
			<a onclick=\"DocConnect({$this->doc}, {$this->doc_data[13]}); return false;\" title='Связать документ'><img src='img/i_conn.png' alt='Связать'></a>";
			$ret.="<span id='provodki'>";
			if($this->doc_data[6])
				$ret.=$this->cancel_buttons();
			else
// 				$ret.="<a href='?mode=ehead&amp;doc={$this->doc}' title='Правка заголовка'><img src='img/i_docedit.png' alt='Правка'></a>
// 				<a href='?mode=apply&amp;doc={$this->doc}' title='Провести документ' onclick=\"ShowPopupWin('/doc.php?mode=apply&amp;doc={$this->doc}'); return false;\"><img src='img/i_ok.png' alt='Провести'></a>";

			$ret.=$this->apply_buttons();

			$ret.="</span>
			<a href='#' onclick=\"return ShowContextMenu(event, '/doc.php?mode=print&amp;doc={$this->doc}')\" title='Печать накладной'><img src='img/i_print.png' alt='Печать'></a>
			<a href='#' onclick=\"return FaxMenu(event, '{$this->doc}')\" title='Отправить по факсу'><img src='img/i_fax.png' alt='Факс'></a>
			<a href='#' onclick=\"return MailMenu(event, '{$this->doc}')\" title='Отправить по email'><img src='img/i_mailsend.png' alt='email'></a>
			<a href='#' onclick=\"return ShowContextMenu(event, '/doc.php?mode=morphto&amp;doc={$this->doc}')\" title='Создать связанный документ'><img src='img/i_to_new.png' alt='Связь'></a>";
		}

    		if($this->dop_menu_buttons) $ret.=$this->dop_menu_buttons;
    		return $ret;
	}

	protected function apply_buttons()
	{
		if($this->doc_data['mark_del'])	$s="<a href='#' title='Отменить удаление' onclick='unMarkDelDoc({$this->doc}); return false;'><img src='img/i_trash_undo.png' alt='отменить удаление'></a>";
		else	$s="<a href='#' title='Пометить на удаление' onclick='MarkDelDoc({$this->doc}); return false;'><img src='img/i_trash.png' alt='Пометить на удаление'></a>";
		return "$s<a href='#' title='Провести документ' onclick='ApplyDoc({$this->doc}); return false;'><img src='img/i_ok.png' alt='Провести'></a>";
		//<a href='?mode=ehead&amp;doc={$this->doc}' title='Правка заголовка'><img src='img/i_docedit.png' alt='Правка'></a>
	}

	protected function cancel_buttons()
	{
// 		$a='';
		return "<a title='Отменить проводку' onclick='CancelDoc({$this->doc}); return false;'><img src='img/i_revert.png' alt='Отменить' /></a>";
	}
	
	/// Вычисление, можно ли отменить кассовый документ
	protected function CheckKassMinus()
	{
		$sum=0;
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`type`, `doc_list`.`sum`, `doc_list`.`kassa` FROM `doc_list`
		WHERE  `doc_list`.`ok`>'0' AND ( `doc_list`.`type`='6' OR `doc_list`.`type`='7' OR `doc_list`.`type`='9')
		ORDER BY `doc_list`.`date`");
		$i=0;
		while($nxt=mysql_fetch_row($res))
		{
			if($nxt[1]==6)
			{
				if($nxt[3]==$this->doc_data[15])	$sum+=$nxt[2];
			}
			else if($nxt[1]==7)
			{
				if($nxt[3]==$this->doc_data[15])	$sum-=$nxt[2];
			}
			else if($nxt[1]==9)
			{
				if($nxt[3]==$this->doc_data[15])	$sum-=$nxt[2];
				else
				{
					$rr=mysql_query("SELECT `value` FROM `doc_dopdata` WHERE `doc`='$nxt[0]' AND `param`='v_kassu'");
					$vkassu=mysql_result($rr,0,0);
					if($vkassu==$this->doc_data[15])$sum+=$nxt[2];
				}
			}

			$sum = sprintf("%01.2f", $sum);
			if($sum<0) break;
			$i++;
		}
		mysql_free_result($res);
		return $sum;
	}

	/// Сбросить цены в списке товаров документа к ценам по умолчанию
	protected function ResetCost()
	{
		if(!$this->doc)			throw new Exception("Документ не определён!");
		$res=mysql_query("SELECT `id`, `tovar`, `cnt` FROM `doc_list_pos` WHERE `doc`='{$this->doc}'");
		if(mysql_errno())		throw new MysqlException("Не удалось выбрать товар в документе");
		while($nxt=mysql_fetch_row($res))
		{
			$cost=GetCostPos($nxt[1], $this->dop_data['cena']);
			mysql_query("UPDATE `doc_list_pos` SET `cost`='$cost' WHERE `id`='$nxt[0]'");
			if(mysql_errno())	throw new MysqlException("Не удалось сбросить цену документа!");
		}
	}
};





?>