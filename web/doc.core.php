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

include_once("function.php");
$GLOBALS['m_right']=0;
$GLOBALS['m_left']=0;

$docnames[0]="НУЛЕВОЙ ДОКУМЕНТ";
$docnames[1]="Поступление";
$docnames[2]="Реализация";
$docnames[3]="Заявка покупателя";
$docnames[4]="Банк - приход";
$docnames[5]="Банк - расход";
$docnames[6]="Касса - приход";
$docnames[7]="Касса - расход";

function doc_log($motion,$desc)
{
	global $uid;
	$uid=mysql_escape_string($uid);
	$motion=mysql_escape_string($motion);
	$desc=mysql_escape_string($desc);
	mysql_query("INSERT INTO `doc_log` (`user`,`time`,`motion`,`desc`)
	VALUES ('$uid',NOW(),'$motion','$desc')");
}

function doc_menu($dop="")
{
    echo"<table width=100% bgcolor=ddddee><tr><td>
    <a href='' title='Назад' onclick=\"history.go(-1);\"><img src='img/i_back.png' alt='Журнал документов' border=0></a>

    <img src='img/i_separator.png'>

    <a href='doc_journal.php' title='Журнал документов' accesskey=\"D\"><img src='img/i_journal.png' alt='Журнал документов' border=0></a>
    <a href='doc_agent.php' title='Журнал агентов' accesskey=\"A\"><img src='img/i_user.png' alt='Журнал агентов' border=0></a>
    <a href='doc_agent_dov.php' title='Работа с доверенными лицами'><img src='img/i_users.png' alt='лица' border=0></a>
    <a href='docs.php?l=sklad' title='Склад' accesskey=\"S\"><img src='img/i_sklad.png' alt='Склад' border=0></a>
    <a href='doc_sklad.php' title='Склад'>Старый</a>

    <img src='img/i_separator.png'>

    <a href='doc.php' title='Новый документ' accesskey=\"N\"><img src='img/i_new.png' alt='Новый' border=0></a>
    <a href='doc.php?mode=new&amp;type=1' title='Поступление товара на склад'><img src='img/i_new_post.png' alt='Поступление товара на склад' border=0></a>
    <a href='doc.php?mode=new&amp;type=2' title='Реализация товара' accesskey=\"R\"><img src='img/i_new_real.png' alt='Реализация товара' border=0></a>
    <a href='doc.php?mode=new&amp;type=3' title='Заявка покупателя' accesskey=\"Z\"><img src='img/i_new_schet.png' alt='Заявка покупателя' border=0></a>
    <a href='doc.php?mode=new&amp;type=4' title='Поступление средств в банк'><img src='img/i_new_pbank.png' alt='Поступление средств в банк' border=0></a>
    <a href='doc.php?mode=new&amp;type=5' title='Вывод средств из банка'><img src='img/i_new_rbank.png' alt='Вывод средств из банка' border=0></a>
    <a href='doc.php?mode=new&amp;type=6' title='Приходный кассовый ордер'><img src='img/i_new_pko.png' alt='Приходный кассовый ордер' border=0></a>
    <a href='doc.php?mode=new&amp;type=7' title='Расходный кассовый ордер'><img src='img/i_new_rko.png' alt='Расходный кассовый ордер' border=0></a>

    <img src='img/i_separator.png'>

    <a href='' onclick=\"MakeContextMen('/doc_service.php?mode=reports'); return false;\"  title='Отчеты'><img src='img/i_report.png' alt='Отчеты' border=0></a>
    <a href='doc_vars.php' title='Настройка'><img src='img/i_config.png' alt='Настройка' border=0></a>

    ";
    if($dop) echo"<img src='img/i_separator.png'> $dop";

    echo"</table>";
    $res=mysql_query("SELECT `name`,`cnt`,`cnt2`,`mincnt` FROM `doc_base` WHERE (`cnt`+`cnt2`)<`mincnt` LIMIT 100");
    $row=mysql_num_rows($res);
    if($row)
    {
    	mysql_data_seek($res,rand(0,$row-1));
    	$nxt=mysql_fetch_row($res);
    	$col=$nxt[1]+$nxt[2];
    	msg("По крайней мере, у $row товаров, количество на складе меньше минимально рекомендуемого!<br>Например $nxt[0] в наличии всего $col штук, вместо $nxt[3] рекомендуемых!","err","Недостаток товара на складе!");
    }
}

// Получить следующий по порядку альтернативный номер
// $type - тип документа
// $subtype - подтип
// $cur - текущий альтернативный номер
function GetNextAltNum($type,$subtype,$cur=0)
{
	$res=mysql_query("SELECT `altnum` FROM `doc_list` WHERE `type`='$type' AND `subtype`='$subtype' AND `altnum`!='$cur' ORDER BY `altnum` DESC");
	$nxt=mysql_fetch_row($res);
	$newnum=$nxt[0]+1;
	return $newnum;
}


function doc_oper($type,$oper,$params)
{
    if($type==1)
    {
        doc_agent($oper,$params);
    }
    else if($type==2)
    {
        doc_postuplenie($oper,$params);
    }
    else if($type==10)
    {
        doc_sysfunct($oper,$params);
    }
    else
    {
        $GLOBALS['last_error']="Неверный тип документа($type,$oper,$params)!";
        return -1;
    }

}


// Функции работы с базой
// Операции:
// 1- Форма ввода новых данных
// 2- Проверка правильности данных для сохранения
// 3- Сохранение
// 4- Редактирование

function doc_agent($oper,$params)
{
    doc_menu();
    if($oper==1)
    {
        zstart("Ввод нового агента","100%");
        echo"<form action='doc_new.php' method='post'>
        <input type=hidden name=type value='1'>
        <input type=hidden name=oper value='2'>
        <input type=hidden name=params value='$params'>
        Наименование предприятия или ФИО:<br>
        <input type='text' name='name' value=''><br>
        Контактный телефон:<br>
        <input type='text' name='tel' value=''><br>
        Адрес:<br>
        <input type='text' name='adr' value=''><br>
        Комментарий:<br>
        <input type='text' name='comm' value=''><br>
        Комментарий 2:<br>
        <input type='text' name='comm2' value=''><br>
        <input type=submit value='Записать'>
        ";
        zend();
        $GLOBALS['last_error']="Всё ок!";
        return 0;
    }
    else if($oper==2)
    {
        zstart("Сохранение нового агента","100%");
        $name=rcv('name');
        $tel=rcv('tel');
        $adr=rcv('adr');
        $comm=rcv('comm');
        $comm2=rcv('comm2');
        $res=mysql_query("SELECT * FROM doc_agent WHERE `name`='$name'");
        if(@mysql_num_rows($res))
        {
            echo"<b>Агент с таким именем ('$name') уже существует! Вернитесь назад и исправьте ошибку!</b>";
        }
        else
        {
            $res=mysql_query("INSERT INTO doc_agent (`name`,`tel`,`adres`,`comment`,`pcomment`)
                                VALUES ('$name','$tel','$adr','$comm','$comm2')");
            if($res) echo"<b>Агент с именем *'$name'* создан!</b>";
        }
        zend();
    }
    else
    {
        $GLOBALS['last_error']="Создание агента: неверный шаг($oper,$params)!";
        return -1;
    }

}


function doc_sysfunct($oper,$params)
{
    $tm=time();
    if($oper==1)
    {
        doc_sklad("doc_new.php?type=2&oper=3&addcnt=1&doc_id=$params");
    }
}

function doc_sklad($params,$targ="_self",$ri)
{
//     $res=mysql_query("SELECT `id`,`name`,`proizv`,`cost`,`cnt`,`cnt2` FROM doc_base ORDER by 'name'");
//     echo"<table width=100% cellspacing=0 cellpadding=0>
//     <tr><td align=left>№<td>Наименование<td>Производитель<td>Стоимость<td>Склад 1<td>Склад 2";
//     $i=0;
//     while($nxt=mysql_fetch_row($res))
//     {
//         echo"<tr class=lin$i><td><a href='$params&addpos=$nxt[0]&cost=$nxt[3]' target='$targ'>+</a><td>$nxt[1]<td>$nxt[2]<td>$nxt[3]<td>$nxt[4]";
//         $i=1-$i;
//     }
//     echo"</table>";

    $gr=rcv('gr');
    settype($gr,"integer");
    $res=mysql_query("SELECT * FROM `doc_group` WHERE `id`>'0' order by `desc` DESC");
    echo"<table width=100% cellpadding=3 cellspacing=0>
    <tr valign=top><td width=150 class=lin1>Ручной ввод<br>
    <form action='doc_service.php'><input type=text name=nn></form>
    <b>Группы</b><br>";
    if($gr==0) echo"<a href='doc_sklad.php?gr=$nx[0]'><b>*..*</b></a><br>";
    else echo"<a href='doc_sklad.php?gr=$nx[0]'>..</a><br>";
    while($nx=mysql_fetch_row($res))
    {
        echo"<a href='doc_sklad.php?gr=$nx[0]'>";
        if($gr==$nx[0]) echo"<b>*$nx[1]*</b>";
        else echo"$nx[1]";
        echo"</a><br>";
    }
    $res=mysql_query("SELECT `doc_base`.`id`,`doc_base`.`group`,`doc_base`.`name`,`doc_base`.`desc`,`doc_base`.`cost`,
    `doc_base`.`cnt`,`doc_base`.`cnt2`,`doc_base`.`type`,`doc_base`.`d_int`,`doc_base`.`d_ext`,`doc_base`.`size`,
    `doc_base`.`mass`,`doc_base`.`analog`,`doc_base`.`mesto`,`doc_base`.`proizv`,`doc_base`.`koncost`,`doc_base`.`mincnt` FROM `doc_base` WHERE `doc_base`.`group`='$gr'
        ORDER BY `doc_base`.`name` ASC");

    echo"<td class=lin0>
    <table width=100% cellspacing=0 cellpadding=0 border=1>
    <tr align=center><td align=left>№<td>Номер<td>Произв.<td>Цена<td>Конк.цена<td>Имп. аналог<td>Тип<td>d<td>D<td>B
    <td>Масса<td>Скл.1<td>Скл.2<td>Место";
    $i=0;
    while($nxt=mysql_fetch_row($res))
    {
        $cost = sprintf("%01.2f р.", $nxt[4]);
        $koncost = sprintf("%01.2f р.", $nxt[15]);
        $cc=$cc2=$cc3="";
        if(($nxt[5]+$nxt[6])<$nxt[16]) $cc2=$cc3="class=u_blue";
        if($nxt[4]>$nxt[15]) $cc="class=u_stop";
        if($nxt[5]<0) $cc2="class=u_stop";
        echo"<tr class=lin$i align=center><td>$nxt[0]<td><a href='$params&addpos=$nxt[0]&cost=$nxt[3]' target='$targ'>$nxt[2]</a>
        <td>$nxt[14]<td $cc>$cost<td>$koncost<td>$nxt[12]&nbsp;<td>$nxt[7]<td>$nxt[8]<td>$nxt[9]<td>$nxt[10]
        <td>$nxt[11]<td $cc2>$nxt[5]<td $cc3>$nxt[6]<td>$nxt[13]";
        $i=1-$i;
    }
    echo"</table><br><a href='doc_sklad.php?mode=pos'>Добавить</a>
    </table>";
}

function copy_doc_list($doc_dst,$doc_src)
{
	$res=mysql_query("INSERT INTO `doc_list_pos` ( `doc` , `tovar` , `cnt` , `sn` , `comm` , `cost`)
	SELECT '$doc_dst' , `tovar` , `cnt` , `sn` , `comm` , `cost` FROM `doc_list_pos`
	WHERE `doc`='$doc_src'");
	return $res;
}

$res=mysql_query("SELECT * FROM `doc_vars`");
$dv=mysql_fetch_assoc($res);


?>
