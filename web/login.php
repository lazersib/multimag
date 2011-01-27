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

include_once("core.php");
$login=@htmlentities($_POST['login'],ENT_QUOTES);
$pass=@htmlentities($_POST['pass'],ENT_QUOTES);
session_start();

function attack_test()
{
	$lock=0;
	$captcha=0;
	$ip=getenv("REMOTE_ADDR");
	$sql='SELECT `id` FROM `users_bad_auth`';
	
	$tm=time()-60*60*3;
	$res=mysql_query("$sql WHERE `ip`='$ip' AND `time`>'$tm'");
	
	if(mysql_num_rows($res)>20)	return 2;	// Lock	
	$tm=time()-60*5;
	$res=mysql_query("$sql WHERE `ip`='$ip' AND `time`>'$tm'");
	if(mysql_num_rows($res)>2)	$captcha=1;
	
	$ip_a=explode(".",$ip);
	
	$tm=time()-60*60*3;
	$res=mysql_query("$sql WHERE `ip`='$ip_a[0].$ip_a[1].$ip_a[2].%' AND `time`>'$tm'");
	if(mysql_num_rows($res)>100)	return 3;	// Lock	
	$tm=time()-60*5;
	$res=mysql_query("$sql WHERE `ip`='$ip_a[0].$ip_a[1].$ip_a[2].%' AND `time`>'$tm'");
	if(mysql_num_rows($res)>6)	$captcha=1;
	
	$tm=time()-60*60*3;
	$res=mysql_query("$sql WHERE `ip`='$ip_a[0].$ip_a[1].%' AND `time`>'$tm'");
	if(mysql_num_rows($res)>500)	return 3;	// Lock	
	$tm=time()-60*5;
	$res=mysql_query("$sql WHERE `ip`='$ip_a[0].$ip_a[1].%' AND `time`>'$tm'");
	if(mysql_num_rows($res)>30)	$captcha=1;
	
	$tm=time()-60*5;
	$res=mysql_query("$sql WHERE `time`>'$tm'");
	if(mysql_num_rows($res)>100)	$captcha=1;
	
	return $captcha;	
}



if($mode=='')
{
	$opt=rcv('opt');
	$img=rcv('img');
	$login=rcv('login');
	$pass=rcv('pass');
	if($_SESSION['uid'])
	{
		include("user.php");
		exit();
	}
	
	// Куда переходить после авторизации
	$from=getenv("HTTP_REFERER");
	if($from)
	{
		$froma=explode("/",$from);
		if( ($froma[2]!=$CONFIG['site']['name']) || ($froma[3]=='login.php') || ($froma[3]=='') )	$from="http://".$CONFIG['site']['name'];
		
	}
	$_SESSION['redir_to']=$from;	
	
	$cont=rcv('cont');
	$tmpl->AddText("<h1 id='page-title'>Авторизация</h1>");
	if($cont)	$tmpl->AddText("<div id='page-info'>Для доступа в этот раздел Вам необходимо пройти авторизацию.</div>");

	//$_SESSION['c_str']=strtoupper(keygen_unique(0,5,7));
	$ip=getenv("REMOTE_ADDR");
	$time=time()+60;
	$at=attack_test();
	if($at>1)	mysql_query("INSERT INTO `users_bad_auth` (`ip`, `time`) VALUES ('$ip', '$time')");
	if($at>=3)
	{
		$tmpl->msg("Из-за попыток подбора паролей к сайту доступ с вашей подсети заблокирован! Вы сможете авторизоваться через несколько часов после прекращения попыток подбора пароля. Если Вы не предпринимали попыток подбора пароля, обратитесь к Вашему поставщику интернет-услуг - возможно, кто-то другой пытается подобрать пароль, используя ваш адрес.","err","Доступ заблокирован");
	}
	else if($at==2)
	{
		$tmpl->msg("Из-за попыток подбора паролей к сайту доступ с вашего адреса заблокирован! Вы сможете авторизоваться через несколько часов после прекращения попыток подбора пароля. Если Вы не предпринимали попыток подбора пароля, обратитесь к Вашему поставщику интернет-услуг - возможно, кто-то другой пытается подобрать пароль, используя ваш адрес.","err","Доступ заблокирован");
	}
	else
	{
		if($opt=='login')
		{
			if( ($at==1) && ($_SESSION['captcha_keystring']!=$img) && ($_SESSION['captcha_keystring']!='') )
			{
				$tmpl->msg("Введите правильный код подтверждения, изображенный на картинке", "err");
				mysql_query("INSERT INTO `users_bad_auth` (`ip`, `time`) VALUES ('$ip', '$time')");
			}
			else
			{
				$res=mysql_query("SELECT `users`.`id`, `users`.`name`, `users`.`confirm`, `users_data`.`value` FROM `users`
				LEFT JOIN `users_data` ON `users_data`.`uid`=`users`.`id` AND `users_data`.`param`='firm_id'
				WHERE `name`='$login' AND `pass`=MD5('$pass')");
			
				if(@$nxt=mysql_fetch_row($res))
				{
					if( ($nxt[2]=='') || ($nxt[2]=='0') )
					{
						mysql_query("UPDATE `users` SET `lastlogin`=NOW(), `passch`='' WHERE `id`='$nxt[0]'");
						$_SESSION['uid']=$nxt[0];
						$_SESSION['name']=$nxt[1];
						if($_SESSION['last_page'])	
						{
							$lp=$_SESSION['last_page'];
							unset($_SESSION['last_page']);
							header("Location: ".$lp);
						}
						else if($_SESSION['redir_to'])	header("Location: ".$_SESSION['redir_to']);
						else				header("Location: index.php");
						exit();
					}
					else
					{
						$tmpl->msg("Вы не подтвердили свои регистрационные данные! Проверьте свой почтоый ящик!<br>Если Вы ещё не получили письмо, а с момента регистрации прошло более трёх часов - вероятно Ваш сервер не принимает от нас почту. В таком случае Вам нужно повторно выполнить резистрацию, указав при этом адрес электронной почты, зарегистрированный на другом сервере.!");
					}
				}
				else
				{
					mysql_query("INSERT INTO `users_bad_auth` (`ip`, `time`) VALUES ('$ip', '$time')");
					$tmpl->msg("Неверная пара логин / пароль! Попробуйте снова!","err","Авторизоваться не удалось");
				}
		
		
			}
		}
		$at=attack_test();
		
		if($at>0)
			$m="<tr><td>
			Введите код подтверждения, изображенный на картинке:<br>
			<img src='kcaptcha/index.php' alt='Включите отображение картинок!'><td>
			<input type='text' name='img'>";
		else $m='';
		
		$tmpl->AddText("<form method='post' action='login.php'>
		<input type='hidden' name='opt' value='login'>
		<table>
		<tr><th colspan=2>
		Введите данные:
		<tr><td colspan=2>
		Если у Вас их нет, вы можете <a class='wiki' href='?mode=reg'>зарегистрироваться</a>
		<tr><td>
		Имя:<td>
		<input type='text' name='login' class='text' id='input_name' value='$login'><br>
		<tr><td>Пароль:<td>
		<input type='password' name='pass' class='text'>(<a class='wiki' href='?mode=rem'>Сменить</a>)<br>$m
		<tr><td><td>
		<button type='submit'>Вход!</button> ( <a class='wiki' href='/login.php?mode=rem'>Забыли пароль?</a> )
		</table></form>
	
		<script type=\"text/javascript\">
		
		function focusInput()
		{
		var input_name = document.getElementById('input_name');
		if (input_name.value == '')
			input_name.focus();
		return false;
		}
		
		window.setTimeout('focusInput()', 300);
		</script>");
	}

}
else if($mode=='logout')
{
    unset($_SESSION['uid']);
    unset($_SESSION['name']);
    header("Location: index.php");
    exit();
}
else if($mode=='reg')
{
    if(!$uid)
    {
		$login=rcv('login');
		$email=rcv('email');
		$l=rcv('l');
		$e=rcv('e');
		$i=rcv('i');
		$tmpl->AddText("<h1 id='page-title'>Регистрация</h1>");
		if($l) $lt=" <div style='color: #c00'>Выбранный логин уже занят другим пользователем, используйте другой!</div>";
		if($e) $et=" <div style='color: #c00'>Выбранный адрес уже занят другим пользователем, используйте другой!</div>";
		if($i) $it=" <div style='color: #c00'>Вы неправильно ввели код подтверждения!</div>";
		$tmpl->AddText("<p id='text'>
		Для использования всех возможностей этого сайта, необходимо пройти процедуру регистрации. Регистрация не сложная,
		и займёт всего несколько минут. Все зарегистрированные пользователи автоматически получают возможность приобретать товар по специальным ценам!</p>
		<h2>Для регистрации заполните следующую форму:</h2>
		<form method='post'>
		<input type='hidden' name='mode' value='regs'>
		<table>
		<tr><td width='50%'>
		Ваш login
		<br>имя, которое Вы будете использовать для входа на сайт:
		<td>
		<input type='text' name='login' value='$login'>$lt
		<tr><td>
		Адрес электронной почты e-mail<br>
		<td><input type='text' name='email' value='$email'>$et<br>
		<tr><td><td><input type='checkbox' name='subs' value='1' checked>Подписаться на новости и другую информацию
		<tr><td>
		Введите код подтверждения, изображенный на картинке:<br>
		<img src='/kcaptcha/index.php'><br>
		<td>
		<input type='text' name='img'>$it
		<tr><td style='color: #c00;'><td>
		<button type='submit'>Далее &gt;&gt;</button>
		</form>
		</table>");
	}	else $tmpl->msg("Вы уже являетесь нашим зарегистрированным пользователем. Повторная регистрация не требуется.","info");
}
else if($mode=='regs')
{
	$login=rcv('login');
	$email=rcv('email');
	$img=strtoupper(rcv('img'));
	$subs=rcv('subs');
	if($subs!='0') $subs=1;
	
	$res=mysql_query("SELECT `id` FROM `users` WHERE `name`='$login'");
	$lc=mysql_num_rows($res);
	
	$res=mysql_query("SELECT `id` FROM `users` WHERE `email`='$email'");
	$ec=mysql_num_rows($res);
	
	if($lc||$ec||(strtoupper($_SESSION['captcha_keystring'])!=$img)||(strlen($login)<3))
	{
		$l="&login=$login&email=$email";
		if($lc||(strlen($login)<3)) $l.="&l=1";
		if($ec) $l.="&e=1";
		if($_SESSION['c_str']!=$img) $l.="&i=1";
		header("Location: login.php?mode=reg".$l);
	}
	else
	{
		unset($_SESSION['c_str']);
		$conf=md5(time()+rand(0,1000000));
		$pass=keygen_unique(0,6,9);
$msg="Вы получили это письмо потому, что в заявке на регистрацию на сайте http://{$CONFIG['site']['name']} был указан Ваш адрес электронной почты. Для продолжения регистрации введите следующий код подтверждения:
$conf
или перейдите по ссылке http://{$CONFIG['site']['name']}/login.php?mode=conf&s=$conf .
Если не переходить по ссылке (например, если заявка подана не Вами), то регистрационные данные будут автоматически удалены через неделю.
Ваш аккаунт:
Логин: $login
Пароль: $pass

После подтверждения регистрации Вы сможете получить доступ к расширенным функциям сайта. Неактивные аккаунты удаляются через 6 месяцев.

------------------------------------------------------------------------------------------

You have received this letter because in the form of registration in a site http://{$CONFIG['site']['name']} your e-mail address has been entered. For continuation of registration enter this key:
$conf
or pass under the link http://{$CONFIG['site']['name']}/login.php?mode=conf&s=$conf .  If not going under the reference (for example if the form is submitted not by you) registration data will be automatically removed after a week.
Your account:
login: $login
pass: $pass

After confirmatoin of registration you can get access to the expanded functions of a site. Inactive accounts leave in 6 months.

------------------------------------------------------------------------------------------
Сообщение сгенерировано автоматически, отвечать на него не нужно!
The message is generated automatically, to answer it is not necessary!";
	
	
		if(mailto($email,"Registration on ".$CONFIG['site']['name'], $msg))
		{
			$res=mysql_query("INSERT INTO `users` (`name`,`pass`,`email`,`date_reg`,`confirm`,`subscribe`)
			VALUES ('$login', MD5('$pass'), '$email', NOW(),'$conf','$subs')  ");
			if(mysql_errno())	throw new MysqlException("Не удалось добвать пользователя! Попробуйте позднее!");
			
			$tmpl->AddText("<h1 id='page-title'>Завершение регистрации</h1>
			<form action='/login.php'>
			<input type='hidden' name='mode' value='conf'>
			Для проверки, что указанный адрес электронной почты принадлежит Вам, на него было выслано сообщение.<br>Для завершения регистрации введите полученный код:<br>
			<input type='text' name='s'><button type='submit'>Продолжить</button><br>
			Если Вы не получите письмо в течение трёх часов, возможно ваш сервер не принимает наше сообщение. Сообщите о проблеме администратору своего почтового сервера, или используйте другой!
			</form>");	
		}
		else $tmpl->msg("Не удалось отправить сообщение электронной почты!","err");
	}

}
else if($mode=='conf')
{
	$tmpl->AddText("<h1 id='page-title'>Подтверждение регистрации</h1>");
	$s=rcv('s');
	$res=mysql_query("SELECT `id`, `name` FROM `users` WHERE `confirm`='$s'");
	if($nxt=mysql_fetch_row($res))
	{
		mysql_query("UPDATE `users` SET `confirm`='0' WHERE `id` = '$nxt[0]' ");
		mysql_query("UPDATE `users` SET `lastlogin`=NOW(), `passch`='' WHERE `id`='$nxt[0]'");
		$_SESSION['uid']=$nxt[0];
		$_SESSION['name']=$nxt[1];
		if($_SESSION['last_page'])	
		{
			$lp=$_SESSION['last_page'];
			unset($_SESSION['last_page']);
			header("Location: ".$lp);
		}
		else $tmpl->msg("Регистрация завершена! Теперь можно войти!","ok");
	}
	else $tmpl->msg("Неверный или устаревший код подтверждения!","err");
}
else if($mode=='rem')
{
	$tmpl->SetText("<h1 id='page-title'>Смена пароля</h1>
	<p id='text'>Для начала процедуры смены пароля введите логин на сайте или адрес электронной почты:</p>
	<form method='post'>
	<input type='text' name='login'><br>
	<input type='hidden' name='mode' value='rems'>
	<p id='text'>После нажатия кнопки на адрес электронной почты, указанный при регистрации, будет выслана ссылка для смены пароля.</p>
	<input type='submit' value='Выслать ссылку'>
	</form>");
}
else if($mode=='rems')
{
	$tmpl->SetText("<h1 id='page-title'>Смена пароля</h1>");
	$res=mysql_query("SELECT `id`,`name`,`email`,`confirm`,`date_reg` FROM `users` WHERE `name`='$login' OR `email`='$login'");
	if(@$nxt=mysql_fetch_row($res))
	{
		$key=md5($nxt[0].$nxt[1].$nxt[2].$nxt[4].time());
		mysql_query("UPDATE `users` SET `passch`='$key' WHERE `id`='$nxt[0]'");
		$msg="Поступил запрос на смену пароля доступа к сайту {$CONFIG['site']['name']} для аккаунта $nxt[1].
Если Вы действительно хотите сменить пароль, перейдите по ссылке http://{$CONFIG['site']['name']}/login.php?mode=remn&s=$key
Если Вы не давали запрос на смену пароля, обязательно отмените этот запрос, авторизовавшись на сайте!
----------------------------------------
Сообщение сгенерировано автоматически, отвечать на него не нужно!";
		if(mailto($nxt[2],"Восстановление забытого пароля",$msg))
			$tmpl->msg("Проверьте почтовый ящик!","ok");
		else
			$tmpl->msg("Сообщение не может быть отправлено в данный момент! Попытайтесь позднее!","err");
	}
	else $tmpl->msg("Пользователя с таким именем или адресом электронной почты не найдено! Возможно, он был удален по неактивности.","err");
}
else if($mode=='passch')
{
	$tmpl->AddText("<h1 id='page-title'>Смена пароля</h1>
	<div id='page-info'>Если у Вас есть сомнения в конфеденциальности текущено пароля</div>
	Хороший пароль должен состоять из смеси букв, цифр, и специальных символов (как минимум из смеси букв и цифр), и не являться слованым словом.");
}
else if($mode=='remn')
{
	$key=rcv('s');
	if(strlen($key)!=32) $tmpl->logger("PassRecovery: uncorrect key!");
	else
	{
		$res=mysql_query("SELECT `id`,`name`,`email` FROM `users` WHERE `passch`='$key'");
		if($nxt=mysql_fetch_row($res))
		{
		$pass=keygen_unique(0,6,9);
		mysql_query("UPDATE `users` SET `pass`=md5('$pass'), `passch`='', `confirm`='0' WHERE `id`='$nxt[0]'");
		$_SESSION['uid']=$nxt[0];
		$_SESSION['name']=$nxt[1];
		$msg="Сайт {$CONFIG['site']['name']}\nПароль был успешно изменён! Не забудьте его!\nlogin: $nxt[1]\npass: $pass\n----------------------------------------\nСообщение сгенерировано автоматически, отвечать на него не нужно!";
		mailto($nxt[2],"Информация о смене пароля",$msg);
		$tmpl->AddText("<h1>Завершение смены пароля</h1>
		<p id=text>$nxt[1], ваш новый пароль:<br>
		$pass<br>Не забудьте его! Письмо с новым паролем отправлено Вам по электронной почте!");
		}
		else $tmpl->logger("Ссылка уже не действительна!");
	}
}
else if($mode=='unsubscribe')
{
	$tmpl->SetText("<h1 id='page-title'>Отказ от рассылки</h1>");
	$email=rcv('email');
	$c=0;
	$res=mysql_query("UPDATE `users` SET `subscribe`='0' WHERE `email`='$email'");
	echo mysql_error();
	if(mysql_affected_rows())
	{
		$tmpl->msg("Вы успешно отказались от автоматической рассылки!","ok");
		$c=1;
	}
	
	$res=mysql_query("UPDATE `doc_agent` SET `no_mail`='1' WHERE `email`='$email'");
	echo mysql_error();
	if(mysql_affected_rows())
	{
		$tmpl->msg("В нашей клиентской базе Ваш адрес помечен, как нежелательный для рассылки.","ok");
		$c=1;
	}
	
	if(!$c)	$tmpl->msg("Ваш адрес не найден в наших базах рассылки! Возможно, Вы отказались от рассылки ранее, или не являетесь нашим зарегистрированным пользователем. За разяснением обратитесь по телефону или e-mail, указанному на странице <a class='wiki' href='/wiki/ContactInfo'>Контакты</a>, либо в письме, полученном от нас. Спасибо за понимание!","notify");
}
else $tmpl->logger("Uncorrect mode!");


$tmpl->write();

?>