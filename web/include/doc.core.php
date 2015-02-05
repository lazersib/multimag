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

include_once($CONFIG['site']['location']."/include/doc.nulltype.php");

/// Автозагрузка классов документов
/// TODO: Перенести автозагрузку в ядро, реализовать автозагрузку максимально возможного количества классов
function doc_autoload($class_name) {
	global $CONFIG;

	$class_name= strtolower($class_name);
	$nm2=explode('_',$class_name,2);
	if(is_array($nm2)) {
		if(count($nm2)>1) {
			list($class_type, $class_name)=$nm2;
			if($class_type=='doc')		include_once $CONFIG['site']['location']."/include/doc.".$class_name.'.php';
			else if($class_type=='report')	include_once $CONFIG['site']['location']."/include/reports/".$class_name.'.php';
		}
	}
	@include_once $CONFIG['site']['location']."/include/".$class_name.'.php';
}

spl_autoload_register('doc_autoload');

/// Вывод числа прописью. Для внутреннего использования.
/// @sa num2str
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

/// Возвращает число прописью
/// @param L 	Число
/// @param ed	Единица измерения
/// @param sot	Кол-во знаков после запятой
function num2str($L, $ed='rub', $sot=2)
{
	$ff=1;
	if($ed=='kg')
	{
		$namerub[1]="килограмм ";
		$namerub[2]="килограмма ";
		$namerub[3]="килограммов ";

		$kopeek[1]="грамм ";
		$kopeek[2]="грамма ";
		$kopeek[3]="граммов ";
		$ff=0;
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
			num2str_semantic($kop,$s1,$many,$ff);
			$s.=$s1.$kopeek[$many];
		}
		else
		{
			$s.=" 00 $kopeek[3]";
		}
	}
	return $s;
}



/// Запись событий документов в лог
/// @param motion	Выполненное действие
/// @param desc		Описание выполненного действия
/// @param object	Тип объекта, с которым выполнено действие
/// @param oblect_id	ID объекта, с которым выполено действие
function doc_log($motion, $desc, $object='', $object_id=0) {
    global $db;
    $uid = intval(@$_SESSION['uid']);
    $motion = $db->real_escape_string($motion);
    $desc = $db->real_escape_string($desc);
    $object = $db->real_escape_string($object);
    $object_id = intval($object_id);
    $ip = $db->real_escape_string(getenv("REMOTE_ADDR"));
    $res = $db->query("INSERT INTO `doc_log` (`user`, `ip`, `time`,`motion`,`desc`, `object`, `object_id`)
	VALUES ('$uid', '$ip', NOW(),'$motion','$desc', '$object', '$object_id')");
}

function doc_menu($dop = "", $nd = 1, $doc = 0) {
	global $tmpl, $CONFIG, $db;
	// Индикатор нарушения целостности проводок
	// Устанавливается при ошибке при проверке целостности и при принудительной отмене
	// Снимается, если проверка целостности завершилась успешно
	$err = '';
	$res = $db->query("SELECT `corrupted` FROM `variables`");
	if ($res) {
		$row = $res->fetch_row();
		if ($row[0])
			$err = "class='error'";
		$res->free();
	}
	else
		$err = "class='error'";

	$tmpl->addTop("<div id='doc_menu' $err>
	<div id='doc_menu_container'>
	<div id='doc_menu_r'>
	<!--<input type='text' id='quicksearch'>
	<script>
	var ac=initAutocomplete('quicksearch','/docs.php?l=sklad&mode=srv&opt=acj')
	</script>
	-->
	<a href='/user.php' title='Возможности пользователя'><img src='/img/i_users.png' alt='Возможности пользователя' border='0'></a>
	<a href='/login.php?mode=logout' title='Выход'><img src='/img/i_logout.png' alt='Выход'></a>
	</div>
	<a href='/' title='Главная'><img src='/img/i_home.png' alt='Главная' border='0'></a>

	<img src='/img/i_separator.png' alt=''>

	<a href='/docj_new.php' title='Журнал документов' accesskey=\"D\"><img src='/img/i_journal.png' alt='Журнал документов' border='0'></a>
	<a href='/incomp_orders.php' title='Журнал невыполненных заявок' accesskey=\"D\"><img src='/img/i_incomp_orders.png' alt='Журнал невыполненных заявок' border='0'></a>
	<a href='/docs.php?l=agent' title='Журнал агентов' accesskey=\"A\"><img src='/img/i_user.png' alt='Журнал агентов' border='0'></a>
	<a href='/docs.php?l=dov' title='Работа с доверенными лицами'><img src='/img/i_users.png' alt='лица' border='0'></a>
	<a href='/docs.php?l=sklad' title='Склад' accesskey=\"S\"><img src='/img/i_sklad.png' alt='Склад' border='0'></a>
	<a href='/factory.php' title='Производство' accesskey=\"S\"><img src='/img/i_factory.png' alt='Производство' border='0'></a>
	<a href='/docs.php?l=pran' onclick=\"return ShowContextMenu(event, '/priceload.php?mode=menu')\" title='Анализ прайсов' accesskey=\"S\"><img src='/img/i_analiz.png' alt='Анализ прайсов' border='0'></a>
	<img src='img/i_separator.png' alt=''>

	<a href='/doc.php' title='Новый документ' accesskey=\"N\"><img src='/img/i_new.png' alt='Новый' border='0'></a>
	<a href='/doc.php?mode=new&amp;type=1' title='Поступление товара на склад'><img src='/img/i_new_post.png' alt='Поступление товара на склад' border='0'></a>
	<a href='/doc.php?mode=new&amp;type=2' title='Реализация товара' accesskey=\"R\"><img src='/img/i_new_real.png' alt='Реализация товара' border='0'></a>
	<a href='/doc.php?mode=new&amp;type=3' title='Заявка покупателя' accesskey=\"Z\"><img src='/img/i_new_schet.png' alt='Заявка покупателя' border='0'></a>
	<a href='/doc.php?mode=new&amp;type=4' title='Поступление средств в банк'><img src='/img/i_new_pbank.png' alt='Поступление средств в банк' border='0'></a>
	<a href='/doc.php?mode=new&amp;type=5' title='Вывод средств из банка'><img src='/img/i_new_rbank.png' alt='Вывод средств из банка' border='0'></a>
	<a href='/doc.php?mode=new&amp;type=6' title='Приходный кассовый ордер'><img src='/img/i_new_pko.png' alt='Приходный кассовый ордер' border='0'></a>
	<a href='/doc.php?mode=new&amp;type=7' title='Расходный кассовый ордер'><img src='/img/i_new_rko.png' alt='Расходный кассовый ордер' border='0'></a>
	<a href='/doc.php?mode=new&amp;type=12' title='Товар в пути'><img src='/img/i_new_tp.png' alt='Товар в пути' border='0'></a>
	<img src='/img/i_separator.png' alt=''>

	<a href='' onclick=\"return ShowContextMenu(event, '/doc_reports.php?mode=pmenu')\"  title='Отчеты'><img src='img/i_report.png' alt='Отчеты' border='0'></a>
	<a href='/doc_service.php' title='Служебные функции'><img src='/img/i_config.png' alt='Служебные функции' border='0'></a>
	<a href='/doc_sc.php' title='Сценарии и операции'><img src='/img/i_launch.png' alt='Сценарии и операции' border='0'></a>");
	if ($dop)
		$tmpl->addTop("<img src='/img/i_separator.png' alt=''> $dop");

	$tmpl->addTop("</div></div>");

	if ($nd && @$CONFIG['doc']['mincount_info']) {
            $res = $db->query("SELECT SQL_CALC_FOUND_ROWS `doc_base`.`name`, `doc_base_cnt`.`cnt`, `doc_base_cnt`.`mincnt`, `doc_sklady`.`name`
                FROM `doc_base`
                LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id`
                LEFT JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_base_cnt`.`sklad`
                WHERE `doc_base_cnt`.`cnt`<`doc_base_cnt`.`mincnt` LIMIT 1000");
            if ($res->num_rows) {
                $res->data_seek(rand(0, $res->num_rows - 1));
                $nxt = $res->fetch_row();
                $info_res = $db->query("SELECT FOUND_ROWS()");
                list($all_cnt) = $info_res->fetch_row();
                if ($nxt[1]) {
                    $nxt[1] = 'всего ' . $nxt[1] . ' штук';
                } else {
                    $nxt[1] = 'отсутствует';
                }
                $tmpl->addContent("<div class='warn_bar'>Количество у $all_cnt товаров на складе меньше минимально рекомендуемого. Например, &quot;".
                    html_out($nxt[0])."&quot; в наличии ".html_out($nxt[1]).", вместо $nxt[2] рекомендуемых!</div>");
            }
            $res->free();
	}
}

/// ======== УСТАРЕЛО - УБРАТЬ ПОСЛЕ ТОГО, КАК НЕ БУДЕТ НИГДЕ ИСПОЛЬЗОВАТЬСЯ =============
function GetNextAltNum($type, $subtype, $doc, $date, $firm)
{
	global $CONFIG,$db;
	$start_date=strtotime(date("Y-01-01 00:00:00",strtotime($date)));
	$end_date=strtotime(date("Y-12-31 23:59:59",strtotime($date)));
	$res=$db->query("SELECT `altnum` FROM `doc_list` WHERE `type`='$type' AND `subtype`='$subtype' AND `id`!='$doc' AND `date`>='$start_date' AND `date`<='$end_date' AND `firm_id`='$firm' ORDER BY `altnum` ASC");
	$newnum=0;
	while($nxt=$res->fetch_row())
	{
		if(($nxt[0]-1 > $newnum)&& @$CONFIG['doc']['use_persist_altnum'])	break;
		$newnum=$nxt[0];
	}
	$newnum++;
	$res->free();
	return $newnum;
}

/// ====== Получение данных, связанных с документом =============================


/// Определение типа документа по ID типа и создание соответствующего класса
/// @param doc_type ID типа документа
/// @param doc ID документа
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
		case 19:
			return new doc_Korbonus($doc);
		case 20:
			return new doc_Realiz_bonus($doc);
		case 21:
			return new doc_ZSbor($doc);
		default:
			return new doc_Nulltype();
	}
}

/// Расчет и обновление суммы документа
/// @param doc ID документа
/// TODO: убрать в doc_Nulltype
function DocSumUpdate($doc)
{
	global $db;
	settype($doc,'int');
	$sum=0;
	$res=$db->query("SELECT `cnt`, `cost` FROM `doc_list_pos` WHERE `doc`='$doc' AND `page`='0'");
	while($nxt=$res->fetch_row())
		$sum+=$nxt[0]*$nxt[1];
	$res->free();
	if($sum!=0)
	{
		$res=$db->query("UPDATE `doc_list` SET `sum`='$sum' WHERE `id`='$doc'");
	}
	return $sum;
}

/// Расчёт бонусного баланса агента. Бонусы начисляются за поступления средств на баланс агента
/// @param agent_id	ID агента, для которого расчитывается баланс
/// @param no_cache	Не брать данные расчёта из кеша
function docCalcBonus($agent_id, $no_cache=0) {
	global $tmpl, $db, $doc_agent_dolg_cache_storage;
	settype($agent_id,'int');
	if(!$no_cache && isset($doc_agent_bonus_cache_storage[$agent_id]))	return $doc_agent_dolg_cache_storage[$agent_id];

	$bonus=0;
	$res=$db->query("SELECT `doc_list`.`type`, `doc_list`.`sum`, `doc_dopdata`.`value` AS `bonus` FROM `doc_list`
	LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='bonus'
	WHERE `ok`>'0' AND `agent`='$agent_id' AND `mark_del`='0'");
	while($nxt=$res->fetch_row())
	{
		switch($nxt[0])
		{
			case 2:	$bonus+=$nxt[2]; break;
			case 19:$bonus+=$nxt[1]; break;
			case 20:$bonus-=$nxt[1]; break;
		}
	}
	$res->free();
	$bonus=sprintf("%0.2f", $bonus);
	$doc_agent_bonus_cache_storage[$agent_id]=$bonus;
	return $bonus;
}

/// Расчёт актуальной входящей цены
/// @param pos_id 	ID складского наименования, для которого производится расчёт
/// @param limit_date	Ограничить период расчёта указанной датой. Расчёт цены выполняется на указанную дату.
/// @param serv_mode	Если true - функция возвращает для услуг их базовую цену. Иначе возвращает 0.
function getInCost($pos_id, $limit_date=0, $serv_mode=0)
{
	global $db;
	settype($pos_id,'int');
	settype($limit_date,'int');
	$cnt=$cost=0;
	$sql_add='';
	$res=$db->query("SELECT `pos_type`, `cost` FROM `doc_base` WHERE `id`=$pos_id");
	list($type, $cost)=$res->fetch_row();
	if($type==1)	return $serv_mode?$cost:0;

	if($limit_date)	$sql_add="AND `doc_list`.`date`<='$limit_date'";
	$res=$db->query("SELECT `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_list`.`type`, `doc_list_pos`.`page`, `doc_dopdata`.`value`
	FROM `doc_list_pos`
	INNER JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc` AND (`doc_list`.`type`<='2' OR `doc_list`.`type`='17')
	LEFT JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list_pos`.`doc` AND `doc_dopdata`.`param`='return'
	WHERE `doc_list_pos`.`tovar`='$pos_id' AND `doc_list`.`ok`>'0' $sql_add ORDER BY `doc_list`.`date`");
	while($nxt=$res->fetch_row())
	{
		if(($nxt[2]==2) || ($nxt[2]==17) && ($nxt[3]!='0'))	$nxt[0]=$nxt[0]*(-1);
		if( ($cnt+$nxt[0])==0)	{}
		else if($nxt[0]>0 && $nxt[4]!=1)
		{
			if($cnt>0)	$cost=( ($cnt*$cost)+($nxt[0]*$nxt[1])) / ($cnt+$nxt[0]);
			else		$cost=$nxt[1];
		}
		$cnt+=$nxt[0];
	}
	$res->free();
	return round($cost,2);
}

/// Получить количество товара на складе на заданную дату
/// @param pos_id 		ID складского наименования, для которого производится расчёт
/// @param sklad_id		ID склада, для которого производится расчёт
/// @param unixtime		Дата, на которую производится расчёт в формате unixtime. Если не задан - расчитывается остаток на дату последнего документа.
/// @param noBreakIfMinus	Если true - расчёт не будет прерван, если на каком-то из этапов расчёта остаток станет отрицательным.
function getStoreCntOnDate($pos_id, $sklad_id, $unixtime=null, $noBreakIfMinus=0)
{
    global $db;
    settype($pos_id, 'int');
    settype($sklad_id, 'int');
    settype($unixtime, 'int');
    $cnt = 0;
    $sql_add = ($unixtime !== null) ? "AND `doc_list`.`date`<=$unixtime" : '';
    $res = $db->query("SELECT `doc_list_pos`.`cnt`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`id`, `doc_list_pos`.`page` FROM `doc_list_pos`
	LEFT JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
	WHERE  `doc_list`.`ok`>'0' AND `doc_list_pos`.`tovar`=$pos_id AND (`doc_list`.`type`=1 OR `doc_list`.`type`=2 OR `doc_list`.`type`=8 OR `doc_list`.`type`=17) $sql_add
	ORDER BY `doc_list`.`date`");
    while ($nxt = $res->fetch_row()) {
        if ($nxt[1] == 1) {
            if ($nxt[2] == $sklad_id)
                $cnt+=$nxt[0];
        }
        else if ($nxt[1] == 2 || $nxt[1] == 20) {
            if ($nxt[2] == $sklad_id)
                $cnt-=$nxt[0];
        }
        else if ($nxt[1] == 8) {
            if ($nxt[2] == $sklad_id)
                $cnt-=$nxt[0];
            else {
                $r = $db->query("SELECT `value` FROM `doc_dopdata` WHERE `doc`=$nxt[3] AND `param`='na_sklad'");
                if (!$r->num_rows)
                    throw new Exception("Cклад назначения в перемещении $nxt[3] не задан");
                list($nasklad) = $r->fetch_row();
                if (!$nasklad)
                    throw new Exception("Нулевой склад назначения в перемещении $nxt[3] при проверке на отрицательные остатки");
                if ($nasklad == $sklad_id)
                    $cnt+=$nxt[0];
                $r->free();
            }
        }
        else if ($nxt[1] == 17) {
            if ($nxt[2] == $sklad_id) {
                if ($nxt[4] == 0)
                    $cnt+=$nxt[0];
                else
                    $cnt-=$nxt[0];
            }
        }
        $cnt = round($cnt, 3);
        if ($cnt < 0 && $noBreakIfMinus == 0)
            break;
    }
    $res->free();
    return $cnt;
}

/// Возвращает количество товара в резерве
/// @param pos_id ID товара. Для услуг поведение не определено.
/// @param doc_id ID исключаемого документа
/// @sa DocPodZakaz DocVPuti
/// TODO: реализовать кеширование
function DocRezerv($pos_id,$doc_id=0)
{
	global $db;
	settype($pos_id,'int');
	settype($doc_id,'int');

	$res=$db->query("SELECT SUM(`doc_list_pos`.`cnt`) FROM `doc_list_pos`
	INNER JOIN `doc_list` ON `doc_list`.`type`='3' AND `doc_list`.`ok`>'0' AND `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`id`!=$doc_id
	AND `doc_list`.`id` NOT IN (SELECT DISTINCT `p_doc` FROM `doc_list`
	INNER JOIN `doc_list_pos` ON `doc_list`.`id`=`doc_list_pos`.`doc`
	WHERE `ok` != '0' AND `type`='2' AND `doc_list_pos`.`tovar`=$pos_id )
	WHERE `doc_list_pos`.`tovar`=$pos_id
	GROUP BY `doc_list_pos`.`tovar`");
	if($res->num_rows)	list($reserved)=$res->fetch_row();
	else			$reserved=0;
	$res->free();
	return $reserved;

}

/// Возвращает количество товара, доступного по данным предложений поставщиков
/// @param pos_id ID товара
/// @param doc_id ID исключаемого документа
/// @sa DocRezerv DocVPuti
/// TODO: реализовать кеширование
function DocPodZakaz($pos_id,$doc_id=0)
{
	global $db, $CONFIG;
	settype($pos_id,'int');
	settype($doc_id,'int');
	if(@$CONFIG['doc']['op_time'])	$rt=time()-60*60*24*$CONFIG['doc']['op_time'];
	else				$rt=time()-60*60*24*365;
	$res=$db->query("SELECT SUM(`doc_list_pos`.`cnt`) FROM `doc_list_pos`
	INNER JOIN `doc_list` ON `doc_list`.`type`='11' AND `doc_list`.`ok`>'0' AND `doc_list`.`id`!=$doc_id AND `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`id` NOT IN (SELECT DISTINCT `p_doc` FROM `doc_list` WHERE `ok` != '0' AND `type`='1' )
	WHERE `doc_list_pos`.`tovar`=$pos_id
	GROUP BY `doc_list_pos`.`tovar`");
	if($res->num_rows)	list($available)=$res->fetch_row();
	else			$available=0;
	$res->free();
	return $available;
}

/// Возвращает количество товара, находящегося в пути, по данным документов *товар в пути*
/// @param pos_id ID товара
/// @param doc_id ID исключаемого документа
/// @sa DocPodZakaz DocVPuti
/// TODO: реализовать кеширование
function DocVPuti($pos_id,$doc_id=0)
{
	global $db;
	settype($pos_id,'int');
	settype($doc_id,'int');

	$res=$db->query("SELECT SUM(`doc_list_pos`.`cnt`) FROM `doc_list_pos`
	INNER JOIN `doc_list` ON `doc_list`.`type`='12' AND `doc_list`.`ok`>'0' AND `doc_list`.`id`!=$doc_id
	AND `doc_list`.`id`=`doc_list_pos`.`doc` AND `doc_list`.`id` NOT IN (SELECT DISTINCT `p_doc` FROM `doc_list` WHERE `ok` != '0' AND `type`='1' )
	WHERE `doc_list_pos`.`tovar`=$pos_id
	GROUP BY `doc_list_pos`.`tovar`");
	if($res->num_rows)	list($transit)=$res->fetch_row();
	else			$transit=0;
	$res->free();
	return $transit;
}

/// Создаёт класс документа по ID документа, используя AutoDocumentType
/// @sa AutoDocumentType
function AutoDocument($doc_id)
{
	global $db;
	settype($doc_id,'int');
	$res=$db->query("SELECT `type` FROM `doc_list` WHERE `id`=$doc_id");
	if(!$res->num_rows)	throw new Exception("Документ не найден");
	list($type)=$res->fetch_row();
	return AutoDocumentType($type, $doc_id);
}

/// Для внутреннего использования
/// @sa selectAgentGroup
function selectAgentGroupRecursive($group_id, $prefix, $selected, $leaf_only) {
	global $db;
	// Нет смысла в проверке входных параметров, т.к. функция вызывается только из selectAgentGroup
	$res = $db->query("SELECT `id`, `name` FROM `doc_agent_group` WHERE `pid`='$group_id' ORDER BY `id`");
	$ret = '';
	while($line = $res->fetch_row()) {
		$sel = ($selected==$line[0])?' selected':'';
		$deep = selectAgentGroupRecursive($line[0], $prefix.'|&nbsp;&nbsp;', $selected, $leaf_only);
		$dis = ($deep!='' && $leaf_only)?' disabled':'';
		$ret .= "<option value='$line[0]'{$sel}{$dis}>{$prefix}".htmlentities($line[1],ENT_QUOTES,"UTF-8")."</option>";
		$ret .= $deep;
	}
	$res->free();
	return $ret;
}

/// Создаёт HTML код элемента select со списком групп агентов
/// @param select_name 	Имя элемента select
/// @param selected	ID выбранного элемента
/// @param not_select	Если true - в выпадающий список будет добавлен пункт 'не выбран'
/// @param select_id	Содержимое html аттрибута id элемента select
/// @param select_class	Содержимое html аттрибута class элемента select
/// @param leaf_only	Флаг возможности выбора только "листьев" в дереве групп
/// @sa selectGroupPos
function selectAgentGroup($select_name, $selected=0, $not_select=0, $select_id='', $select_class='', $leaf_only=false) {
	$ret="<select name='$select_name' id='$select_id' class='$select_class'>";
	if($not_select)	$ret.="<option value='0'>***не выбрана***</option>";
	$ret.=selectAgentGroupRecursive(0, '', $selected, $leaf_only);
	$ret.="</select>";
	return $ret;
}

/// Для внутреннего использования
/// @sa selectGroupPos
function selectGroupPosRecursive($group_id, $prefix, $selected, $leaf_only) {
	global $db;
	// Нет смысла в проверке входных параметров, т.к. функция вызывается только из selectGroupPos
	$res = $db->query("SELECT `id`, `name` FROM `doc_group` WHERE `pid`='$group_id' ORDER BY `id`");
	$ret = '';
	while($line = $res->fetch_row()) {
		$sel = ($selected==$line[0])?' selected':'';
		$deep = selectGroupPosRecursive($line[0], $prefix.'|&nbsp;&nbsp;', $selected, $leaf_only);
		$dis = ($deep!='' && $leaf_only)?' disabled':'';
		$ret .= "<option value='$line[0]'{$sel}{$dis}>{$prefix}".htmlentities($line[1],ENT_QUOTES,"UTF-8")."</option>";
		$ret .= $deep;
	}
	$res->free();
	return $ret;
}

/// Создаёт HTML код элемента select со списком групп наименований
/// @param select_name 	Имя элемента select
/// @param selected	ID выбранного элемента
/// @param not_select	Если true - в выпадающий список будет добавлен пункт 'не выбран'
/// @param select_id	Содержимое html аттрибута id элемента select
/// @param select_class	Содержимое html аттрибута class элемента select
/// @param leaf_only	Флаг возможности выбора только "листьев" в дереве групп
/// @sa selectAgentGroup
function selectGroupPos($select_name, $selected=0, $not_select=false, $select_id='', $select_class='', $leaf_only=false)
{
	$ret="<select name='$select_name' id='$select_id' class='$select_class'>";
	if($not_select)	$ret.="<option value='0'>***не выбран***</option>";
	$ret.=selectGroupPosRecursive(0, '', $selected, $leaf_only);
	$ret.="</select>";
	return $ret;
}

/// @brief Возвращает строку с информацией о различиях между двумя наборами данных в массивах
/// Массив new должен содержать все индексы массива old
/// Используется для внесения информации в журнал
/// @param old	Старый набор данных (массив)
/// @param new	Новый набор данных (массив)
function getCompareStr($old, $new) {
	$ret = '';
	foreach ($old as $key => $value) {
		if ($new[$key] !== $value) {
			if ($ret)
				$ret.=", $key: ( $value => {$new[$key]})";
			else {
				$ret = ", $key: ( $value => {$new[$key]})";
			}
		}
	}
	return $ret;
}

?>