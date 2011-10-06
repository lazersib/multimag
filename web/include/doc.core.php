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

include_once($CONFIG['site']['location']."/include/doc.tovary.php");
include_once($CONFIG['site']['location']."/include/doc.nulltype.php");

function __autoload($class_name)
{
	global $CONFIG;
	
	$class_name= strtolower($class_name);
	$nm2=split('_',$class_name,2);
	if(is_array($nm2))
	{
		list($class_type, $class_name)=$nm2;
		if($class_type=='doc')		include_once $CONFIG['site']['location']."/include/doc.".$class_name.'.php';
		else if($class_type=='report')	include_once $CONFIG['site']['location']."/include/reports/".$class_name.'.php';	
	}
	@include_once $CONFIG['site']['location']."/gate/include/doc.s.".$class_name.'.php';
	@include_once $CONFIG['site']['location']."/include/".$class_name.'.php';
	
}

function num2str_semantic($i,&$words,&$fem,$f)
{
	$_1_2[1]="одна ";
	$_1_2[2]="две ";

	$_1_19[1]="один ";
	$_1_19[2]="два ";
	$_1_19[3]="три ";
	$_1_19[4]="четыре ";
	$_1_19[5]="пять ";
	$_1_19[6]="шесть ";
	$_1_19[7]="семь ";
	$_1_19[8]="восемь ";
	$_1_19[9]="девять ";
	$_1_19[10]="десять ";

	$_1_19[11]="одиннацать ";
	$_1_19[12]="двенадцать ";
	$_1_19[13]="тринадцать ";
	$_1_19[14]="четырнадцать ";
	$_1_19[15]="пятнадцать ";
	$_1_19[16]="шестнадцать ";
	$_1_19[17]="семнадцать ";
	$_1_19[18]="восемнадцать ";
	$_1_19[19]="девятнадцать ";

	$des[2]="двадцать ";
	$des[3]="тридцать ";
	$des[4]="сорок ";
	$des[5]="пятьдесят ";
	$des[6]="шестьдесят ";
	$des[7]="семьдесят ";
	$des[8]="восемдесят ";
	$des[9]="девяносто ";

	$hang[1]="сто ";
	$hang[2]="двести ";
	$hang[3]="триста ";
	$hang[4]="четыреста ";
	$hang[5]="пятьсот ";
	$hang[6]="шестьсот ";
	$hang[7]="семьсот ";
	$hang[8]="восемьсот ";
	$hang[9]="девятьсот ";

	$words="";
	$fl=0;
	if($i >= 100)
	{
		$jkl = intval($i / 100);
		$words.=$hang[$jkl];
		$i%=100;
	}
	if($i >= 20)
	{
		$jkl = intval($i / 10);
		$words.=$des[$jkl];
		$i%=10;
		$fl=1;
	}
	switch($i)
	{
		case 1: $fem=1; break;
		case 2:
		case 3:
		case 4: $fem=2; break;
		default: $fem=3; break;
	}
	if($i)
	{
		if($i<3 && $f>0)
		{
			if($f>=2)
			{
				$words.=$_1_19[$i];
			}
			else
			{
				$words.=$_1_2[$i];
			}
		}
		else
		{
			$words.=$_1_19[$i];
		}
	}
}

// Число прописью
function num2str($L, $ed='rub', $sot=2)
{
	if($ed=='kg')
	{
		$namerub[1]="килограмм ";
		$namerub[2]="килограмма ";
		$namerub[3]="килограммов ";

		$kopeek[1]="грамм ";
		$kopeek[2]="грамма ";
		$kopeek[3]="граммов ";
	}
	else if($ed=='sht')
	{
		$namerub[1]="штука ";
		$namerub[2]="штуки ";
		$namerub[3]="штук ";

		$kopeek[1]="сотая ";
		$kopeek[2]="сотые ";
		$kopeek[3]="сотых ";
	}
	else if($ed=='nul')
	{
		$namerub[1]=", ";
		$namerub[2]=", ";
		$namerub[3]=", ";
		if($sot==1)
		{
			$kopeek[1]=" десятых";
			$kopeek[2]=" десятых";
			$kopeek[3]=" десятых";
		}
		else if($ost==3)
		{
			$kopeek[1]=" тысячных";
			$kopeek[2]=" тысячных";
			$kopeek[3]=" тысячных";
		}
		else
		{
			$kopeek[1]=" сотых";
			$kopeek[2]=" сотых";
			$kopeek[3]=" сотых";
		}
		
	}
	else
	{
		$namerub[1]="рубль ";
		$namerub[2]="рубля ";
		$namerub[3]="рублей ";

		$kopeek[1]="копейка ";
		$kopeek[2]="копейки ";
		$kopeek[3]="копеек ";
	}


	$nametho[1]="тысяча ";
	$nametho[2]="тысячи ";
	$nametho[3]="тысяч ";

	$namemil[1]="миллион ";
	$namemil[2]="миллиона ";
	$namemil[3]="миллионов ";

	$namemrd[1]="миллиард ";
	$namemrd[2]="миллиарда ";
	$namemrd[3]="миллиардов ";

	$s=" ";
	$s1=" ";
	$s2=" ";
	$krat=1;
	for($i=0;$i<$sot;$i++,$krat*=10);

	$kop=intval( ( $L*$krat - intval( $L )*$krat ));
	$L=intval($L);
	if($L>=1000000000)
	{
		$many=0;
		num2str_semantic(intval($L / 1000000000),$s1,$many,3);
		$s.=$s1.$namemrd[$many];
		$L%=1000000000;
	}

	if($L >= 1000000)
	{
		$many=0;
		num2str_semantic(intval($L / 1000000),$s1,$many,2);
		$s.=$s1.$namemil[$many];
		$L%=1000000;
		if($L==0)
		{
			$s.=$namerub[3];
		}
	}

	if($L >= 1000)
	{
		$many=0;
		num2str_semantic(intval($L / 1000),$s1,$many,1);
		$s.=$s1.$nametho[$many];
		$L%=1000;
		if($L==0)
		{
			$s.=$namerub[3];
		}
	}

	if($L != 0)
	{
		$many=0;
		num2str_semantic($L,$s1,$many,0);
		$s.=$s1.$namerub[$many];
	}

	if($sot)
	{
		if($kop > 0)
		{
			$many=0;
			num2str_semantic($kop,$s1,$many,1);
			$s.=$s1.$kopeek[$many];
		}
		else
		{
			$s.=" 00 $kopeek[3]";
		}
	}
	return $s;
}

$firm_id=@$_SESSION['firm'];
//if(!$firm_id) $firm_id=$_SESSION['firm']=1;
//if($firm_id>1) $tmpl->LoadTemplate('default2');

// =========== Установки документов - УСТАРЕЛО - УБРАТЬ ПОСЛЕ ТОГО, КАК НЕ БУДЕТ НИГДЕ ИСПОЛЬЗОВАТЬСЯ ================================
global $dv;

$res=mysql_query("SELECT * FROM `doc_vars` WHERE `id`='$firm_id'");
$dv=mysql_fetch_assoc($res);


function GetCostPos($pos_id, $cost_id)
{
	$res=mysql_query("SELECT `doc_base`.`cost`, `doc_base`.`group` FROM `doc_base` WHERE `doc_base`.`id`='$pos_id'");
	if(mysql_errno())		throw new MysqlException("Не удалось получить базовую цену товара");
	if(!mysql_num_rows($res))	throw new Exception("Товар ID:$pos_id не найден!");
	$base_cost=mysql_result($res,0,0);
	$base_group=mysql_result($res,0,1);
	$res=mysql_query("SELECT `doc_cost`.`id`, `doc_base_cost`.`id`, `doc_cost`.`type`, `doc_cost`.`value`, `doc_base_cost`.`type`, `doc_base_cost`.`value`, `doc_base_cost`.`accuracy`, `doc_base_cost`.`direction`, `doc_cost`.`accuracy`, `doc_cost`.`direction`
	FROM `doc_cost`
	LEFT JOIN `doc_base_cost` ON `doc_cost`.`id`=`doc_base_cost`.`cost_id` AND `doc_base_cost`.`pos_id`='$pos_id'
	WHERE `doc_cost`.`id`='$cost_id'");
	if(mysql_errno())		throw new MysqlException("Не удалось получить цену из справочника цен товара");
	if(!mysql_num_rows($res))	throw new Exception("Цена ID:$cost_id не найдена!");
	$nxt=mysql_fetch_row($res);

	if($nxt[1])
	{
		if($nxt[4]=='pp')	$cena= $base_cost+$base_cost*$nxt[5]/100;
		else if($nxt[4]=='abs')	$cena= $base_cost+$nxt[5];
		else if($nxt[4]=='fix')	$cena= $nxt[5];
		else			$cena= 0;
		
		$direction=pow(10,$nxt[6]*(-1))*0.4999*$nxt[7];		
		if($cena)
		{
			return sprintf("%0.2f",round($cena+$direction,$nxt[6]));
		}
		else return "Уточняйте";
	}
	
	while($base_group)
	{
		$res=mysql_query("SELECT `doc_group`.`id`, `doc_group_cost`.`id`, `doc_group_cost`.`type`, `doc_group_cost`.`value`, `doc_group`.`pid`, `doc_group_cost`.`accuracy`, `doc_group_cost`.`direction`
		FROM `doc_group`
		LEFT JOIN `doc_group_cost` ON `doc_group`.`id`=`doc_group_cost`.`group_id`  AND `doc_group_cost`.`cost_id`='$cost_id'
		WHERE `doc_group`.`id`='$base_group'");
		if(mysql_errno())		throw new MysqlException("Не удалось получить цену из справочника цен группы");
		if(!mysql_num_rows($res))	throw new Exception("Группа ID:$base_group не найдена");
		$gdata=mysql_fetch_row($res);
		if($gdata[1])
		{
			if($gdata[2]=='pp')		$cena= $base_cost+$base_cost*$gdata[3]/100;
			else if($gdata[2]=='abs')	$cena= $base_cost+$gdata[3];
			else if($gdata[2]=='fix')	$cena= $gdata[3];
			else				$cena= 0;
			
			$direction=pow(10,$gdata[5]*(-1))*0.4999*$gdata[6];	
			if($cena)
			{
				return sprintf("%0.2f",round($cena+$direction,$gdata[5]));
			}
			else return "Уточняйте";
		}
		$base_group=$gdata[4];
	}
	
	if($nxt[2]=='pp')	$cena= $base_cost+$base_cost*$nxt[3]/100;
	else if($nxt[2]=='abs')	$cena= $base_cost+$nxt[3];
	else if($nxt[2]=='fix')	$cena= $nxt[3];
	else			$cena= 0;
	
	$direction=pow(10,$nxt[8]*(-1))*0.4999*$nxt[9];		
	if($cena)
	{
		return sprintf("%0.2f",round($cena+$direction,$nxt[8]));
	}
	else return "Уточняйте";

}

// =========== Запись событий документов в лог ======================
function doc_log($motion,$desc,$object='',$object_id=0)
{
	$uid=mysql_real_escape_string(@$_SESSION['uid']);
	$motion=mysql_real_escape_string($motion);
	$desc=mysql_real_escape_string($desc);
	$ip=getenv("REMOTE_ADDR");
	mysql_query("INSERT INTO `doc_log` (`user`, `ip`, `time`,`motion`,`desc`, `object`, `object_id`)
	VALUES ('$uid', '$ip', NOW(),'$motion','$desc', '$object', '$object_id')");
}

// == УСТАРЕЛО - УБРАТЬ ПОСЛЕ ТОГО, КАК НЕ БУДЕТ НИГДЕ ИСПОЛЬЗОВАТЬСЯ ===
function but_provodka($doc,$ok)
{
	if($ok)
		return "<a href='?mode=cancel&amp;doc=$doc' title='Отменить проводку' onclick=\"ShowPopupWin('/doc.php?mode=cancel&amp;doc=$doc'); return false;\"><img src='img/i_revert.png' alt='Отменить' /></a>";
	else
		return "<a href='?mode=ehead&amp;doc=$doc' title='Правка заголовка'><img src='img/i_docedit.png' alt='Правка' /></a>
		<a href='?mode=apply&amp;doc=$doc' title='Провести документ' onclick=\"ShowPopupWin('/doc.php?mode=apply&amp;doc=$doc'); return false;\"><img src='img/i_ok.png' alt='Провести' /></a>";

}

function doc_menu($dop="", $nd=1, $doc=0)
{
	global $tmpl;
	global $doc_data;
	if($doc)	get_docdata($doc);

	$context="<a href=\'/index.php\'><div>Главная</div></a><a href=\'/vitrina.php\'><div>Витрина</div></a>";

	$tmpl->AddText("<table width='100%' style='background-color: #e0f0ff; border: 0;' ><tr><td>
	<a href='' title='Назад' onclick=\"history.go(-1);\"><img src='img/i_back.png' alt='Назад' border='0'></a>
	
	<img src='img/i_separator.png' alt=''>
	
	<a href='docj.php' title='Журнал документов' accesskey=\"D\"><img src='img/i_journal.png' alt='Журнал документов' border='0'></a>
	<a href='docs.php?l=agent' title='Журнал агентов' accesskey=\"A\"><img src='img/i_user.png' alt='Журнал агентов' border='0'></a>
	<a href='docs.php?l=dov' title='Работа с доверенными лицами'><img src='img/i_users.png' alt='лица' border='0'></a>
	<a href='docs.php?l=sklad' title='Склад' accesskey=\"S\"><img src='img/i_sklad.png' alt='Склад' border='0'></a>
	<a href='docs.php?l=pran' onclick=\"return ShowContextMenu(event, '/priceload.php?mode=menu')\" title='Анализ прайсов' accesskey=\"S\"><img src='img/i_analiz.png' alt='Анализ прайсов' border='0'></a>
	<img src='img/i_separator.png' alt=''>
	
	<a href='doc.php' title='Новый документ' accesskey=\"N\"><img src='img/i_new.png' alt='Новый' border='0'></a>
	<a href='doc.php?mode=new&amp;type=1' title='Поступление товара на склад'><img src='img/i_new_post.png' alt='Поступление товара на склад' border='0'></a>
	<a href='doc.php?mode=new&amp;type=2' title='Реализация товара' accesskey=\"R\"><img src='img/i_new_real.png' alt='Реализация товара' border='0'></a>
	<a href='doc.php?mode=new&amp;type=3' title='Заявка покупателя' accesskey=\"Z\"><img src='img/i_new_schet.png' alt='Заявка покупателя' border='0'></a>
	<a href='doc.php?mode=new&amp;type=4' title='Поступление средств в банк'><img src='img/i_new_pbank.png' alt='Поступление средств в банк' border='0'></a>
	<a href='doc.php?mode=new&amp;type=5' title='Вывод средств из банка'><img src='img/i_new_rbank.png' alt='Вывод средств из банка' border='0'></a>
	<a href='doc.php?mode=new&amp;type=6' title='Приходный кассовый ордер'><img src='img/i_new_pko.png' alt='Приходный кассовый ордер' border='0'></a>
	<a href='doc.php?mode=new&amp;type=7' title='Расходный кассовый ордер'><img src='img/i_new_rko.png' alt='Расходный кассовый ордер' border='0'></a>
	
	<img src='img/i_separator.png' alt=''>
	
	<a href='' onclick=\"return ShowContextMenu(event, '/doc_otchet.php?mode=pmenu')\"  title='Отчеты'><img src='img/i_report.png' alt='Отчеты' border='0'></a>
	<a href='doc_service.php' title='Служебные функции'><img src='img/i_config.png' alt='Служебные функции' border='0'></a>
	<a href='doc_sc.php' title='Сценарии и операции'><img src='img/i_launch.png' alt='Сценарии и операции' border='0'></a>");
	if($dop) $tmpl->AddText("<img src='img/i_separator.png' alt=''>");
	
	if($dop) $tmpl->AddText($dop);
	
	$tmpl->AddText("</table>");
	
	if(0)
	//if($nd)
	{
			$res=mysql_query("SELECT `doc_base`.`name`, `doc_base_cnt`.`cnt`, `doc_base_cnt`.`mincnt`, `doc_sklady`.`name` FROM `doc_base`
			LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id`
			LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_base_cnt`.`sklad`
			WHERE `doc_base_cnt`.`cnt`<`doc_base_cnt`.`mincnt` LIMIT 100");
			$row=mysql_num_rows($res);
			if($row)
			{
				mysql_data_seek($res,rand(0,$row-1));
				$nxt=mysql_fetch_row($res);
				if($nxt[1]) $nxt[1]='всего '.$nxt[1].' штук';
				else $nxt[1]='отсутствует';
				$tmpl->msg("По крайней мере, у $row товаров, количество на складе меньше минимально рекомендуемого!<br>Например $nxt[0] на складе *$nxt[3]* $nxt[1], вместо $nxt[2] рекомендуемых!","err","Мало товара на складе!");
			}
	}
}

// ======== УСТАРЕЛО - УБРАТЬ ПОСЛЕ ТОГО, КАК НЕ БУДЕТ НИГДЕ ИСПОЛЬЗОВАТЬСЯ === ==========
function GetNextAltNum($type,$subtype,$cur=0)
{
	$res=mysql_query("SELECT `altnum` FROM `doc_list` WHERE `type`='$type' AND `subtype`='$subtype' AND `altnum`!='$cur' ORDER BY `altnum` DESC");
	$nxt=mysql_fetch_row($res);
	$newnum=$nxt[0]+1;
	return $newnum;
}

// ====== Получение данных, связанных с документом =============================
// ======== УСТАРЕЛО - УБРАТЬ ПОСЛЕ ТОГО, КАК НЕ БУДЕТ НИГДЕ ИСПОЛЬЗОВАТЬСЯ === ==========
function get_docdata($doc)
{
	global $doc_data;
	global $dop_data;
	if($doc_data) return;

	if($doc)
	{
		$res=mysql_query("SELECT `a`.`id`, `a`.`type`, `a`.`agent`, `b`.`name`, `a`.`comment`, `a`.`date`, `a`.`ok`, `a`.`sklad`, `a`.`user`, `a`.`altnum`, `a`.`subtype`, `a`.`sum`, `a`.`nds`, `a`.`p_doc`, `a`.`mark_del`
		FROM `doc_list` AS `a`
		LEFT JOIN `doc_agent` AS `b` ON `a`.`agent`=`b`.`id`
		WHERE `a`.`id`='$doc'");
		$doc_data=mysql_fetch_row($res);
		$rr=mysql_query("SELECT `param`,`value` FROM `doc_dopdata` WHERE `doc`='$doc'");
		while($nn=mysql_fetch_row($rr))
		{
			$dop_data["$nn[0]"]=$nn[1];
		}
	}
	else
	{
		$doc_data=array();
		$doc_data[2]=641;
		$doc_data[3]="Частное лицо";
	}
}

// ======== УСТАРЕЛО - УБРАТЬ ПОСЛЕ ТОГО, КАК НЕ БУДЕТ НИГДЕ ИСПОЛЬЗОВАТЬСЯ === ==========
function DocInfo($p_doc)
{
	$res=mysql_query("SELECT `doc_list`.`id`, `doc_types`.`name`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`, `doc_list`.`ok` FROM `doc_list`
	LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
	WHERE `doc_list`.`id`='$p_doc'");
	if(@$nxt=mysql_fetch_row($res))
	{
		if($nxt[5]) $r='Проведённый';
		else $r='Непроведённый';
		$dt=date("d.m.Y H:i:s",$nxt[4]);
		return "<b>Относится к:</b> $r <a href='?mode=body&amp;doc=$nxt[0]'>$nxt[1] N$nxt[2]$nxt[3]</a>, от $dt";
	}
	return '';
}

// =========== Определение типа документа и создание соответствующего класса ====================
function AutoDocumentType($doc_type, $doc)
{
	switch($doc_type)
	{
		case 1:
			return new doc_Postuplenie($doc);
		case 2:
			return new doc_Realizaciya($doc);
		case 3:
			return new doc_Zayavka($doc);
		case 4:
			return new doc_PBank($doc);
		case 5:
			return new doc_RBank($doc);
		case 6:
			return new doc_Pko($doc);
		case 7:
			return new doc_Rko($doc);
		case 8:
			return new doc_Peremeshenie($doc);
		case 9:
			return new doc_PerKas($doc);
		case 10:
			return new doc_Doveren($doc);
		case 11:
			return new doc_Predlojenie($doc);
		case 12:
			return new doc_v_puti($doc);
		case 13:
			return new doc_Kompredl($doc);
		case 14:
			return new doc_Dogovor($doc);
		case 15:
			return new doc_Realiz_op($doc);
		case 16:
			return new doc_Specific($doc);
		case 17:
			return new doc_Sborka($doc);
		case 18:
			return new doc_Kordolga($doc);
		default:
			return new doc_Nulltype();
	}
}

// ========== Расчет и обновление суммы документа ===============================================
function DocSumUpdate($doc)
{
	$sum=0;
	$res=mysql_query("SELECT `cnt`, `cost` FROM `doc_list_pos` WHERE `doc`='$doc' AND `page`='0'");
	if(mysql_errno())	throw new MysqlException("Не удалось получить список товаров");
	while($nxt=mysql_fetch_row($res))
		$sum+=$nxt[0]*$nxt[1];
	if($sum!=0)
		mysql_query("UPDATE `doc_list` SET `sum`='$sum' WHERE `id`='$doc'");
	if(mysql_errno())	throw new MysqlException("Не удалось обновить сумму документа");
	return $sum;
}

function DocCalcDolg($agent, $print=0, $firm_id=0)
{
	global $tmpl;
	$dolg=0;
	$sql_add=$firm_id?"AND `firm_id`='$firm_id'":'';
	$res=mysql_query("SELECT `type`, `sum` FROM `doc_list` WHERE `ok`>'0' AND `agent`='$agent' AND `mark_del`='0' $sql_add");
	while($nxt=mysql_fetch_row($res))
	{
		switch($nxt[0])
		{
			case 1: $dolg-=$nxt[1]; break;
			case 2: $dolg+=$nxt[1]; break;
			case 4: $dolg-=$nxt[1]; break;
			case 5: $dolg+=$nxt[1]; break;
			case 6: $dolg-=$nxt[1]; break;
			case 7: $dolg+=$nxt[1]; break;
			case 18: $dolg+=$nxt[1]; break;
		}
	}

	$dolg=sprintf("%0.2f", $dolg);
	return $dolg;
}

// Расчёт актуальной входящей цены
function GetInCost($pos_id, $limit_date=0)
{
	$cnt=$cost=0;
	$sql_add='';
	if($limit_date)	$sql_add="AND `doc_list`.`date`<='$limit_date'";
	$res=mysql_query("SELECT `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_list`.`type`, `doc_list_pos`.`page`
	FROM `doc_list_pos`
	INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND (`doc_list`.`type`<='2' OR `doc_list`.`type`='17')
	WHERE `doc_list_pos`.`tovar`='$pos_id' AND `doc_list`.`ok`>'0' $sql_add ORDER BY `doc_list`.`date`");
	
	while($nxt=mysql_fetch_row($res))
	{
		if(($nxt[2]==2) || ($nxt[2]==17) && ($nxt[3]!='0'))	$nxt[0]=$nxt[0]*(-1);
		if( ($cnt+$nxt[0])==0)	{}
		else if($nxt[0]>0)
			$cost=( ($cnt*$cost)+($nxt[0]*$nxt[1])) / ($cnt+$nxt[0]);
		$cnt+=$nxt[0];	
	}
	return round($cost,2);
}

// Кол-во товара в резерве
function DocRezerv($pos,$doc=0)
{
	// $doc - номер исключенного документа

	$rs=mysql_query("SELECT SUM(`doc_list_pos`.`cnt`) FROM `doc_list_pos`
	INNER JOIN `doc_list` ON `doc_list`.`type`='3' AND `doc_list`.`ok`>'0' AND `doc_list`.`id`=`doc_list_pos`.`doc` 
	AND `doc_list`.`id` NOT IN (SELECT DISTINCT `p_doc` FROM `doc_list` 
	INNER JOIN `doc_list_pos` ON `doc_list`.`id`=`doc_list_pos`.`doc`
	WHERE `ok` != '0' AND `type`='2' AND `doc_list_pos`.`tovar`='$pos' )
	WHERE `doc_list_pos`.`tovar`='$pos'
	GROUP BY `doc_list_pos`.`tovar`");
	return @$rezerv=mysql_result($rs,0,0);

}

// Кол-во товара под заказ
function DocPodZakaz($pos,$doc=0)
{
	// $doc - номер исключенного документа
	$rt=time()-60*60*24*365;
	$rs=mysql_query("SELECT SUM(`doc_list_pos`.`cnt`) FROM `doc_list_pos`
	INNER JOIN `doc_list` ON `doc_list`.`type`='11' AND `doc_list`.`ok`>'0' AND `doc_list`.`id`!='$doc' AND `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`id` NOT IN (SELECT DISTINCT `p_doc` FROM `doc_list` WHERE `ok` != '0' AND `type`='1' )
	WHERE `doc_list_pos`.`tovar`='$pos'
	GROUP BY `doc_list_pos`.`tovar`");
	return @$rezerv=mysql_result($rs,0,0);
}

// Кол-во товара в пути
function DocVPuti($pos,$doc=0)
{
	// $doc - номер исключенного документа
	$rt=time()-60*60*24*30;
	$rs=mysql_query("SELECT SUM(`doc_list_pos`.`cnt`) FROM `doc_list_pos`
	INNER JOIN `doc_list` ON `doc_list`.`type`='12' AND `doc_list`.`ok`>'0' AND `doc_list`.`id`!='$doc'
	AND `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`id` NOT IN (SELECT DISTINCT `p_doc` FROM `doc_list` WHERE `ok` != '0' AND `type`='1' )
	WHERE `doc_list_pos`.`tovar`='$pos'
	GROUP BY `doc_list_pos`.`tovar`");
	return @$rezerv=mysql_result($rs,0,0);
}

function AutoDocument($doc)
{
	get_docdata($doc);
	global $doc_data;
	global $dop_data;
	return AutoDocumentType($doc_data[1], $doc);
}



?>