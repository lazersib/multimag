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

if($_SESSION['question_ok'])	header("location: /vitrina.php");
$res=mysql_query("SELECT `ip` FROM `question_ip` WHERE `ip`='$ip'");
if(mysql_num_rows($res)) header("location: /vitrina.php");

$tmpl->SetTitle("Наш опрос");
$tmpl->AddText("<h1>Наш опрос</h1>");
if($mode=='')
{
	$tmpl->AddText("В целях повышения удобства использования нашего сайта, предлагаем Вам ответить на несколько вопросов. Это займёт не более 5 минут. Вы согласны?<br>
	<table width='100%'><tr>
	<td><form action='' method='get'><input type='hidden' name='mode' value='start'><input type='submit' value='Да, конечно!'></form> 
	<td><form action='' method='get'><input type='hidden' name='mode' value='later'><input type='submit' value='Не сейчас!'></form> 
	<td><form action='' method='get'><input type='hidden' name='mode' value='no'><input type='submit' value='Нет, не хочу!'></form>
	</table>");
	$_SESSION['question_ok']=1;
}
else if($mode=='no')
{
	mysql_query("INSERT INTO `question_ip` (`ip`, `result`) VALUES ('$ip', '0')");
	$_SESSION['question_ok']=1;
	header("location: /vitrina.php");
}
else if($mode=='later')
{
	$_SESSION['question_ok']=1;
	header("location: /vitrina.php");
}
else if($mode=='start')
{
	$q=rcv('q');
	if($q<=0)	$q=1;
	
	if($q>1)
	{
		$oq=$q-1;
		$t=rcv('t');
		$res=mysql_query("SELECT `id`, `text`, `mode` FROM `questions` WHERE `id`='$oq'");
		$nx=mysql_fetch_row($res);
		$a='|';
		if($nx[2])	$a.=@$_POST['r'].'|';
		else
		{
			$c=@$_POST['c'];			
			if(is_array($c))
			foreach($c as $line)
				$a.=$line.'|';
		}
		if($t) $a.=$t.'|';
		mysql_query("INSERT INTO `question_answ` (`q_id`, `answer`, `uid`, `ip`) VALUES ('$oq', '$a', '$uid', '$ip')");
	}
	
	
	$res=mysql_query("SELECT `id`, `text`, `mode` FROM `questions` WHERE `id`='$q'");
	if($nx=mysql_fetch_row($res))
	{
		$nq=$q+1;
		$tmpl->AddText("<h2>Вопрос $q</h2>");
		$tmpl->AddText("<h4>$nx[1]</h4>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='start'>
		<input type='hidden' name='q' value='$nq'>");
		$res=mysql_query("SELECT `var_id`, `text` FROM `question_vars` WHERE `q_id`='$nx[0]'");
		if($nx[2]) $tmpl->AddText("Выберите один наиболее подходящий вариант, или напишите свой.<br><br>");
		else $tmpl->AddText("Выберите не более трёх вариантов. Можно не выбирать ничего.<br><br>");
		while($nxt=mysql_fetch_row($res))
		{
			if($nx[2])	$tmpl->AddText("<label><input type='radio' name='r' value='$nxt[0]'>$nxt[1]</label><br>");
			else $tmpl->AddText("<label><input type='checkbox' name='c[]' value='$nxt[0]'>$nxt[1]</label><br>");
		}
		if($nx[2])	$tmpl->AddText("<label><input type='radio' name='r' value='0'>Затрудняюсь с ответом</label><br>");
		$tmpl->AddText("Ваш вариант: <input type='text' name='t'>$nxt[1]<br>
		<input type='submit' value='Далее &gt;&gt;'>
		</form>");
	}
	else
	{
		mysql_query("INSERT INTO `question_ip` (`ip`, `result`) VALUES ('$ip', '$q')");
		$tmpl->msg("Спасибо за участие в нашем опросе! Это поможет повысить удобство обслуживания наших клиентов.");
	}

}


$tmpl->Write();
?>


