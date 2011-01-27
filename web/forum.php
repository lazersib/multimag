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


// СТАРЫЙ ФОРУМ, НЕОБХОДИМА ПОЛНАЯ ПЕРЕРАБОТКА

include_once("function.php");





function forum_rights($r,$right_str)
{
    $rstr="0".$right_str."00000000000000000000000000000000000000000000000000000000000000000000";
    if($r=="forum_createkey") return $rstr[1];
    if($r=="forum_createtheme") return $rstr[2];
    if($r=="forum_postmessage") return $rstr[3];
    if($r=="forum_delkey") return $rstr[4];
    if($r=="forum_deltheme") return $rstr[5];
    if($r=="forum_delmymessage") return $rstr[6];
    if($r=="forum_delmessage") return $rstr[7];
    if($r=="forum_closetheme") return $rstr[8];
    if($r=="forum_lockteme") return $rstr[9];
    if($r=="forum_giverightsusers") return $rstr[10];
    if($r=="forum_giveban") return $rstr[11];
    if($r=="forum_giverightsthemes") return $rstr[12];
    if($r=="forum_view") return $rstr[13];
    
    echo"<h1>Uncorrect rights!</h1>";
    return 0;
}

function userinfo($userid,$kinf="")
{
    $res=mysql_query("SELECT name,a_manager,a_club_admin,a_lan_user,icq FROM users WHERE `id`='$userid'");
    if($nxt=mysql_fetch_row($res))
    {
        $ava="";
        $rs=mysql_query("SELECT forum_count FROM users_data WHERE `id`='$userid'");
        $nx=@mysql_fetch_row($rs);
        if(file_exists("/var/wwwroot/gate/img/avatar/$userid.gif"))
        $ava="<img src='img/avatar/$userid.gif'>";
        else if(file_exists(strtolower("/var/wwwroot/gate/cphoto/$nxt[0].jpg")))
        $ava=strtolower("<img src='cphoto/$nxt[0].jpg' width=90>");
        echo"<b>$nxt[0]</b><br>$ava<br><div class=mini>";
        if($nxt[2]) echo"Администратор клуба<br>";
        if($nxt[1]) echo"Менеджер<br>";
        if($nxt[3]) echo"Из сетки<br>";
        if($userid==@$kinf[4]) echo"Модератор раздела<br>";
        if(@$nx[0]>0) echo"Сообщений: $nx[0]<br>";
        echo"</div>";
        if($nxt[4]) echo"<br>UIN:$nxt[4]<br>";
    }
    else
    {
        echo"<b>Некто</b><br>";
    }
}

$GLOBALS['m_left']=0;

$mode=rcv('mode');
$mod=rcv('mod');
$t=rcv('t');
$k=rcv('k');
$m=rcv('m');
$txt=rcv('txt');
$txt2=rcv('txt2');
if($t=="") $t=0;
if($k=="") $k=0;
$ll=auth();
$defrights="000000000000000000000000000000000000000";
$urights="000000000000000000000000000000000000000";
if($ll) $urights="011000000000100000000000000000000000000"; 
if($t!=0)
{
    $res=mysql_query("SELECT * FROM forum_themes WHERE `id`='$t'");
    $tinf=@mysql_fetch_row($res);
    $k=$tinf[1];
    $defrights=$tinf[6]."000000000000000000000000000000000000000";
}

if($k!=0)
{
    $res=mysql_query("SELECT * FROM forum_keys WHERE `id`='$k'");
    $kinf=mysql_fetch_row($res);
    if($t==0) $defrights=$kinf[3]."000000000000000000000000000000000000000";
}



$uid=0;
if($ll) $uid=$GLOBALS['id'];
$res=mysql_query("SELECT * FROM forum_rights WHERE `uid`='$uid' AND `key`='0'");
$nxt=mysql_fetch_row($res);
if(@$nxt[2]) $urights=$urights|$nxt[2];

if($k!=0)
{
    $res=@mysql_query("SELECT * FROM forum_rights WHERE `uid`='$uid' AND `key`='$k'");
    $nxt=@mysql_fetch_row($res);
    if(@$nxt[2]) $urights=$urights|$nxt[2];
    if($uid!=0) 
    {
        $res=@mysql_query("SELECT * FROM forum_rights WHERE `uid`='0' AND `key`='$k'");
        $nxt=@mysql_fetch_row($res);
        if(@$nxt[2]) $urights=$urights|$nxt[2];    
    }
}

$arights=$urights|$defrights;


top("Форум");
    echo"$arights<br>";
echo"<a href='forum.php?mode=new'>Новые темы</a> | <a href='text.php?mid=pravilaforuma'>Правила форума</a><br>";

if($mode=="") // ********************************* разделы
{
    $res=mysql_query("SELECT * FROM forum_keys");
    echo"Форум САЛЯРИС<br>";
    echo"Разделы:<table cellSpacing=0 cellPadding=2 width='100%' border=0>";
    $i=0;
    while($nxt=mysql_fetch_row($res))
    { 
        $i=1-$i;
        $nm="";
        if($nxt[4])
        {           
           $rs=mysql_query("SELECT name FROM users WHERE id='$nxt[4]'");
           $nm=@mysql_result($rs,0,0);
        }
        if($nm=="") $nm="Модератор: admin";
        else $nm="Модератор: $nm";
        echo"<tr class=lin$i><td><a href='forum.php?mode=lt&k=$nxt[0]'>
        <b>$nxt[1]</b></a><br>$nxt[2]<div class=mini>$nm</div></td></tr>";
    }
    echo"</table>";
    if(forum_rights("forum_createkey",$arights))
    {
        echo"Создать раздел:<br><form action='forum.php'>       
        <input type='hidden' name='mode' value='ck'>
        <input type='text' name='txt'><br>
        <input type='text' name='txt2'><br>
        <input type='submit' value='Создать'>
        </form>";   
    }
    else if($ll) echo"У тебя нет прав для создания разделов!";
    else echo"Создавать темы в этом разделе могут только зарегистрированные пользователи!";
}
else if($mode=="lt") // ****************************** темы
{
    $res=mysql_query("SELECT * FROM forum_themes WHERE id_key='$k' ORDER BY last_date DESC");
    echo"<a href='forum.php'>Форум САЛЯРИС</a> -&gt $kinf[1]<br>";
    echo"Темы раздела:<table cellSpacing=0 cellPadding=2 width='100%' border=0>";
    echo"<tr class=lin0><td>Тема</a></td><td width=100>Создана</td><td width=100>Ответов</td><td width=100>Последний</td></tr>";
    
    $i=0;
    while($nxt=mysql_fetch_row($res))
    { 
        $i=1-$i;
        $rs=mysql_query("SELECT name FROM users WHERE id='$nxt[4]'");
        $nm=@mysql_result($rs,0,0);
        if($nm=="") $nm="некто";
        $rs=mysql_query("SELECT name FROM users WHERE id='$nxt[11]'");
        $nm2=@mysql_result($rs,0,0);
        if($nm2=="") $nm2="некто";
        $tt=date("d.m.y H:i:s",$nxt[5]);
        $tt2=date("d.m.y H:i:s",$nxt[8]);
        if($nxt[7]) $nn="$nm2<br>$tt2"; else $nn="---";
        echo"<tr class=lin$i><td><a href='forum.php?mode=vt&t=$nxt[0]'>$nxt[2]</a></td>
        <td>$nm<br>$tt</td><td>$nxt[7]</td><td>$nn</td></tr>";
    }
    echo"</table>";
    if(forum_rights("forum_createtheme",$arights))
    {
        echo"Создать тему:<br><form action='forum.php'>       
        <input type='hidden' name='mode' value='ct'>
        <input type='hidden' name='k' value='$k'>
        <input type='text' name='txt'><br>
        <textarea name='txt2'></textarea>
        <input type='submit' value='Создать'>
        </form>";   
    }
    else if($ll) echo"У тебя нет прав для создания тем в этом разделе!";
    else echo"Создавать темы в этом разделе могут только зарегистрированные пользователи!";

}
else if($mode=="vt") // ****************************** сообщения
{
    if($tinf[0])
    {
        echo"<a href='forum.php'>Форум САЛЯРИС</a> -&gt <a href='forum.php?mode=lt&k=$kinf[0]'>$kinf[1]</a> -&gt $tinf[2]<br>";
        echo"<table cellSpacing=0 cellPadding=2 width='100%' border=0>
        <tr><td width=200 class=lin1 valign=top>";
        userinfo($tinf[4],$kinf);
        $tt=date("d.m.y H:i:s",$tinf[5]);
        $tx=text_out($tinf[3]);
        if($tinf[13]) 
        {  
           $tr=date("Y.m.d H:i:s",$tinf[13]); 
           $tx.="<br><br><i>Отредактировано: $tr";
        }
        if($ll) if($tinf[4]==@$GLOBALS['id'])
           $tx.="<div width=100% align=right><a href='forum.php?mode=et&t=$tinf[0]'>Редактировать</a></div>";
        echo"<br>$tt<br>$tinf[12]</td><td class=lin0 valign=top>$tx</td></tr>
        </table>";       
        $s="";
        if(($mod=="n")&&($ll))
        {
           $ld=@$GLOBALS['lastdate'];
           $s="AND date>'$ld'";
           echo"<center> ~~~~~~~~~~~~~~~ Новые сообщения в этой теме: ~~~~~~~~~~~~~~~ </center>";        
        }
        $res=mysql_query("SELECT * FROM forum_messages WHERE id_t='$t'$s ORDER BY date");
        $i=0;
        while($nx=mysql_fetch_row($res))
        {
            $i=1-$i;
            echo"
            <table cellSpacing=0 cellPadding=2 width='100%' border=0 class=lin0>
            <tr height=2><td></td></tr>
            </table>
            <table cellSpacing=0 cellPadding=2 width='100%' border=0>
            <tr><td width=200 class=lin1 valign=top>";
            userinfo($nx[3],$kinf);
            $tt=date("d.m.y H:i:s",$nx[4]);
            $tx=text_out($nx[2]);
            if($nx[6]) 
            {  
                $tr=date("Y.m.d H:i:s",$nx[6]); 
                $tx.="<br><br><i>Отредактировано: $tr";
            }
            if($ll) if($nx[3]==@$GLOBALS['id'])
               $tx.="<div width=100% align=right><a href='forum.php?mode=em&m=$nx[0]'>Редактировать</a></div>";
            echo"<br>$tt<br>$nx[5]</td><td class=lin$i valign=top>$tx</td></tr>
            </table>";
        }
        if(!$tinf[10])
	{
		if(forum_rights("forum_postmessage",$arights))
		{  
		
		echo"Ответить:<br><form action='forum.php'>       
		<input type='hidden' name='mode' value='ms'>
		<input type='hidden' name='t' value='$t'>
		
		<textarea name='txt'></textarea>
		<input type='submit' value='Отправить'>
		</form>";   
		}
		else if($ll) echo"У тебя нет прав для написания ответа в этой теме!";
		else echo"Писать ответы в этой теме могут только зарегистрированные пользователи!";
	}
	else echo"<br><b>Тема закрыта, писать ответы нельзя!</b>";

    }      
    else msg("Тема не найдена!");
}
else if($mode=="ms") // ****************************** отправка сообщения
{
    echo"<a href='forum.php'>Форум САЛЯРИС</a> -&gt <a href='forum.php?mode=lt&t=$kinf[0]'>$kinf[1]</a> -&gt
    <a href='forum.php?mode=vt&t=$tinf[0]'>$tinf[2]</a><br>";
    if(forum_rights("forum_postmessage",$arights))
    {
        $tm=time();
        $res=mysql_query("INSERT INTO forum_messages (`id_t`,`text`,`autor`,`date`,`ip`) VALUES ('$t','$txt','$uid','$tm','$ip')");    
        $res=mysql_query("UPDATE forum_themes SET msg_cnt=msg_cnt+'1', last_date='$tm', last_msg='$uid' WHERE id='$t'");    
        $res=mysql_query("INSERT INTO users_data (id,forum_count) VALUES ('$uid','0')");
	$res=mysql_query("UPDATE users_data SET forum_count=forum_count+'1' WHERE id='$uid'"); 

        msg("Сообщение добавлено!");
    }
    else msg("У тебя нет прав на добавление cообщений!","error"); 
}
else if($mode=="ct") // ******************************* создание темы
{
    echo"<a href='forum.php'>Форум САЛЯРИС</a> -&gt <a href='forum.php?mode=lt&k=$kinf[0]'>$kinf[1]</a><br>";
    if(forum_rights("forum_createtheme",$arights))
    {
        $tm=time();
	$res=mysql_query("INSERT INTO users_data (id,forum_count) VALUES ('$uid','0')");
	$res=mysql_query("UPDATE users_data SET forum_count=forum_count+'1' WHERE id='$uid'"); 
        $res=mysql_query("INSERT INTO forum_themes (`id_key`,`name`,`text`,`autor`,`date`,`last_date`,`def_rights`,`ip`) VALUES ('$k','$txt','$txt2','$uid','$tm','$tm',$defrights,'$ip')");    
        
        if($res)msg("Тема создана!");
        else msg("Ошибка создания темы!");
    }
else msg("У тебя нет прав на создание темы!","error"); 
}
else if($mode=="ck") // ******************************** создание раздела 
{
    echo"<a href='forum.php'>Форум САЛЯРИС</a><br>";
    if(forum_rights("forum_createkey",$arights))
    {
        $tm=time();
        $res=mysql_query("INSERT INTO forum_keys (`name`,`desc`,`def_rights`) VALUES ('$txt','$txt2',$defrights)");    
        
        if($res)msg("Раздел создан!");
        else msg("Ошибка создания раздела!");
    }
else msg("У тебя нет прав на создание разделов!","error"); 
}
else if($mode=="new") // ****************************** новые темы
{
    $lastdate=@$GLOBALS['lastdate'];
    $res=mysql_query("SELECT `forum_themes`.`id`,`forum_themes`.`id_key`,`forum_themes`.`name`,
    `forum_themes`.`autor`,`forum_themes`.`date`,`forum_themes`.`msg_cnt`,`forum_themes`.`last_date`,
    `forum_themes`.`lock`,`forum_themes`.`close`,`forum_themes`.`last_msg`,`forum_keys`.`name`
     FROM `forum_themes`,`forum_keys` WHERE last_date>'$lastdate' AND `forum_themes`.`id_key`=`forum_keys`.`id`
     ORDER BY `forum_themes`.`last_date` DESC");
    echo"<a href='forum.php'>Форум САЛЯРИС</a> -&gt Темы с новыми сообщениями<br>";
    echo"Новые темы:<table cellSpacing=0 cellPadding=2 width='100%' border=0>";
    echo"<tr class=lin0><td>Тема</a></td><td width=100>Создана</td><td width=100>Ответов</td><td width=100>Последний</td></tr>";
    
    $i=0;
    while($nxt=mysql_fetch_row($res))
    { 
        $i=1-$i;
        $rs=mysql_query("SELECT name FROM users WHERE id='$nxt[3]'");
        $nm=@mysql_result($rs,0,0);
        if($nm=="") $nm="некто";
        $rs=mysql_query("SELECT name FROM users WHERE id='$nxt[9]'");
        $nm2=@mysql_result($rs,0,0);
        if($nm2=="") $nm2="некто";
        $tt=date("d.m.y H:i:s",$nxt[4]);
        $tt2=date("d.m.y H:i:s",$nxt[6]);
        if($nxt[9]) $nn="$nm2<br>$tt2"; else $nn="---";
        echo"<tr class=lin$i><td><a href='forum.php?mode=vt&t=$nxt[0]&mod=n'>$nxt[2]</a><br>$nxt[10]</td><td>$nm<br>$tt</td><td>$nxt[5]</td><td>$nn</td></tr>";
    }
    echo"</table>";
}
else if($mode=="et")
{
    echo"<a href='forum.php'>Форум САЛЯРИС</a> -&gt <a href='forum.php?mode=lt&t=$kinf[0]'>$kinf[1]</a> -&gt
    <a href='forum.php?mode=vt&t=$tinf[0]'>$tinf[2]</a><br>";
    
    if($ll&&($tinf[4]==@$GLOBALS['id'])&&($t))
    {          
        echo"Редактировать тему:<br><form action='forum.php'>       
        <input type='hidden' name='mode' value='ets'>
        <input type='hidden' name='t' value='$t'>
        <textarea name='txt2'>$tinf[3]</textarea><br>
        <input type='submit' value='Сохранить'>
        </form>";   
    }
}
else if($mode=="ets")
{
    if($ll&&($tinf[4]==@$GLOBALS['id'])&&($t))
    { 
       $tm=time();
       $res=mysql_query("UPDATE forum_themes SET text='$txt2', edited='$tm' WHERE `id`='$t'");
       if($res)msg("Изменения сохранены!");
       else msg("Ошибка сохранения изменений!");   
    }
}
else if($mode=="em")
{
    if($m&&$ll)
    {
        $res=mysql_query("SELECT * FROM forum_messages WHERE id='$m'");
        if($nxt=@mysql_fetch_row($res))
        {
            if($nxt[3]==@$GLOBALS['id'])
            {          
                echo"Редактировать сообщение:<br><form action='forum.php'>       
                <input type='hidden' name='mode' value='ems'>
                <input type='hidden' name='m' value='$m'>
                <textarea name='txt2'>$nxt[2]</textarea><br>
                <input type='submit' value='Сохранить'>
                </form>";   
            }
            else msg("Внутренняя ошибка! Если ошибка будет повторяться - обратитесь к администратору!");
        }
        else msg("Внутренняя ошибка! Если ошибка будет повторяться - обратитесь к администратору!");
        
    }
    else 
    {
        msg("Внутренняя ошибка! Если ошибка будет повторяться - обратитесь к администратору!");
        logadd("forum.php - incorrect mode ($mode), t=$t,k=$k,m=$m,ll=$ll,uid=$uid,ip=$ip");
    }
}
else if($mode=="ems")
{
    if($m&&$ll)
    {
        $res=mysql_query("SELECT * FROM forum_messages WHERE id='$m'");
        if($nxt=@mysql_fetch_row($res))
        {
            if($nxt[3]==@$GLOBALS['id'])
            {  
                $tm=time();
                $res=mysql_query("UPDATE forum_messages SET text='$txt2', edited='$tm' WHERE `id`='$m'");
                if($res)msg("Изменения сохранены!");
                else msg("Ошибка сохранения изменений!");  
            }
            else msg("Внутренняя ошибка! Если ошибка будет повторяться - обратитесь к администратору!");
        
        }
        else msg("Внутренняя ошибка! Если ошибка будет повторяться - обратитесь к администратору!");
         
    }
    else msg("Внутренняя ошибка! Если ошибка будет повторяться - обратитесь к администратору!");
        
}
else 
{
   msg("Внутренняя ошибка! Если ошибка будет повторяться - обратитесь к администратору!");
   logadd("forum.php - incorrect mode ($mode), t=$t,k=$k,m=$m,ll=$ll,uid=$uid,ip=$ip");
}
echo"<br><br><a href='forum.php?mode=new'>Новые темы</a> | <a href='text.php?mid=pravilaforuma'>Правила форума</a>";

bottom();
?>