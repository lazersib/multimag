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
	if(!is_array($ip_a))	return $captcha;
	if(count($ip_a)<2)	return $captcha;

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

function regMsg($login, $pass, $conf)
{
	global $CONFIG;
	$proto='http';
	if($CONFIG['site']['force_https_login'] || $CONFIG['site']['force_https'])	$proto='https';
return "Вы получили это письмо потому, что в заявке на регистрацию на сайте http://{$CONFIG['site']['name']} был указан Ваш адрес электронной почты. Для продолжения регистрации введите следующий код подтверждения:
$conf
или перейдите по ссылке $proto://{$CONFIG['site']['name']}/login.php?mode=conf&login=$login&e=$conf .
Если не переходить по ссылке (например, если заявка подана не Вами), то регистрационные данные будут автоматически удалены через неделю.
Ваш аккаунт:
Логин: $login
Пароль: $pass

После подтверждения регистрации Вы сможете получить доступ к расширенным функциям сайта. Неактивные аккаунты удаляются через 6 месяцев.

------------------------------------------------------------------------------------------

You have received this letter because in the form of registration in a site http://{$CONFIG['site']['name']} your e-mail address has been entered. For continuation of registration enter this key:
$conf
or pass under the link $proto://{$CONFIG['site']['name']}/login.php?mode=conf&login=$login&e=$conf .  If not going under the reference (for example if the form is submitted not by you) registration data will be automatically removed after a week.
Your account:
login: $login
pass: $pass

After confirmatoin of registration you can get access to the expanded functions of a site. Inactive accounts leave in 6 months.

------------------------------------------------------------------------------------------
Сообщение сгенерировано автоматически, отвечать на него не нужно!
The message is generated automatically, to answer it is not necessary!";
}

class RegException extends Exception
{
	var $target;
	function __construct($text='', $target='')
	{
		parent::__construct($text);
		$this->target=$target;
	}

}

function RegForm($err_target='', $err_msg='')
{
	global $CONFIG, $tmpl;
	$login=rcv('login');
	$email=rcv('email');
	$phone=rcv('phone');

	$err_msgs=array('login'=>'', 'email'=>'','img'=>'','phone'=>'');
	$err_msgs[$err_target]="<div style='color: #c00'>$err_msg</div>";

	$form_action='/login.php';
	if($CONFIG['site']['force_https_login'])
	{
		$host=$_SERVER['HTTP_HOST'];
		$form_action='https://'.$host.'/login.php';
	}
	
	$tmpl->AddText("<p id='text'>
	Для использования всех возможностей этого сайта, необходимо пройти процедуру регистрации. Регистрация не сложная,
	и займёт всего несколько минут. Все зарегистрированные пользователи автоматически получают возможность приобретать товар по специальным ценам!</p>
	<p>Регистрируясь, Вы даёте согласие на хранение, обработку и публикацию своей персональной информации, в соответствии с законом &quot;О персональных данных&quot;.</p>
	<form action='$form_action' method='post' id='reg-form'>
	<h2>Для регистрации заполните следующую форму:</h2>
	<input type='hidden' name='mode' value='regs'>
	<table cellspacing='15'>
	
	<tr><td>
	<b>Ваш логин</b><br>
	<small>латинские буквы, цифры, длина от 3 до 24 символов	</small>
	<td>
	<input type='text' name='login' value='$login' id='login'><br>
	<span id='login_valid' style='color: #c00'>{$err_msgs['login']}</span>");
	
	if(@$CONFIG['site']['allow_phone_regist'])
		$tmpl->AddText("<tr><td colspan='2'>Заполните хотя бы одно из полей: номер телефона и e-mail");
	
	$tmpl->AddText("<tr><td>
	<b>Адрес электронной почты e-mail</b><br>
	<small>в формате user@host.zone</small>
	<td><input type='text' name='email' value='$email' id='email'><br>
	<span id='email_valid'>{$err_msgs['email']}</span>");
	
	if(@$CONFIG['site']['allow_phone_regist'])
		$tmpl->AddText("
	<tr><td><b>Мобильный телефон: <span id='phone_num'></span></b><br>
	<small>Российский, 10 цифр, без +7 или 8</small>
	<td>
	+7<input type='text' name='phone' value='$phone' maxlength='10' placeholder='Номер' id='phone'><br>
	<span id='phone_valid'>{$err_msgs['phone']}</span>");
	
	$tmpl->AddText("<tr><td colspan='2'><input type='checkbox' name='subs' value='1' checked>Подписаться на новости и другую информацию
	<tr><td colspan='2'><b>Подтвердите что вы не робот, введя текст с картинки:</b>
	<tr><td>
	<img src='/kcaptcha/index.php'><br>
	<td>
	<input type='text' name='img'>{$err_msgs['img']}
	<tr><td style='color: #c00;'><td>
	<button type='submit'>Далее &gt;&gt;</button>
	</form>
	</table>
	<b>Примечание:</b> Если Вы хоте зарегистрироваться, используя свой OpenID, Вам <a href='/login_oid.php'>сюда</a>!<br>
	<script type='text/javascript'>
	");
	
	if(@$CONFIG['site']['allow_phone_regist'])
		$tmpl->AddText("
	var phone=document.getElementById('phone')
	var phone_num=document.getElementById('phone_num')
	var phone_valid=document.getElementById('phone_valid')
	function updatePhoneNum()
	{
		phone_num.innerHTML='+7'+phone.value
		var regexp=/^9\d{9}$/
		if(!regexp.test(phone.value))
		{
			phone.style.borderColor=\"#f00\"
			phone.style.color=\"#f00\"
			phone_valid.innerHTML=''
		}
		else
		{
			phone.style.borderColor=\"\"
			phone.style.color=\"\"
			phone_valid.innerHTML='Введено верно'
			phone_valid.style.color=\"#0c0\"			
		}
	}
	phone.onkeyup=updatePhoneNum");

	$tmpl->AddText("
	var email=document.getElementById('email')
	var email_valid=document.getElementById('email_valid')
	function updateEmail()
	{
		var regexp=/^\w+([+-\.\w]+)*\w@\w(([-\.\w])*\w+)*\.\w{2,8}$/
		if(!regexp.test(email.value))
		{
			email.style.borderColor=\"#f00\"
			email.style.color=\"#f00\"
			email_valid.innerHTML=''
		}
		else
		{
			email.style.borderColor=\"\"
			email.style.color=\"\"
			email_valid.innerHTML='Введено верно'
			email_valid.style.color=\"#0c0\"			
		}
	}
	email.onkeyup=updateEmail

	var login=document.getElementById('login')
	var login_valid=document.getElementById('login_valid')
	function updateLogin()
	{
		var regexp=/^[a-zA-Z\d]{3,24}$/
		if(!regexp.test(login.value))
		{
			login.style.borderColor=\"#f00\"
			login.style.color=\"#f00\"
			login_valid.innerHTML='Заполнено неверно'
		}
		else
		{
			login.style.borderColor=\"\"
			login.style.color=\"\"
			login_valid.innerHTML=''	
		}
	}
	login.onkeyup=updateLogin

	</script>
	");
}

if($mode=='')
{
	$opt=rcv('opt');
	$img=rcv('img');
	$login=rcv('login');
	$pass=rcv('pass');
	if(@$_SESSION['uid'])
	{
		include("user.php");
		exit();
	}

	// Куда переходить после авторизации
	$from=getenv("HTTP_REFERER");
	if($from)
	{
		$froma=explode("/",$from);
		$proto=@$_SERVER['HTTPS']?'https':'http';
		if( ($froma[2]!=$_SERVER['HTTP_HOST']) || ($froma[3]=='login.php') || ($froma[3]=='') )	$from="$proto://".$_SERVER['HTTP_HOST'];
	}
	$_SESSION['redir_to']=$from;

	$cont=rcv('cont');
	$tmpl->AddText("<h1 id='page-title'>Аутентификация</h1>");
	$tmpl->SetTitle("Аутентификация");
	if($cont)	$tmpl->AddText("<div id='page-info'>Для доступа в этот раздел Вам необходимо пройти аутентификацию.</div>");

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
			if( ($at==1) && ( (strtoupper($_SESSION['captcha_keystring'])!=strtoupper($img)) || ($_SESSION['captcha_keystring']=='') ) )
			{
				$tmpl->msg("Введите правильный код подтверждения, изображенный на картинке", "err");
				mysql_query("INSERT INTO `users_bad_auth` (`ip`, `time`) VALUES ('$ip', '$time')");
			}
			else
			{
				$res=mysql_query("SELECT `users`.`id`, `users`.`name`, `users`.`pass`, `users`.`pass_type`, `users`.`reg_email_confirm`, `users`.`reg_phone_confirm`, `users`.`disabled`, `users`.`disabled_reason`, `users`.`bifact_auth` FROM `users`
				WHERE `name`='$login'");
				if(mysql_errno())	throw new MysqlException("Не удалось получить данные");
				if(@$nxt=mysql_fetch_assoc($res))
				{
					if($nxt['disabled'])	throw new Exception("Пользователь заблокирован (забанен). Причина блокировки: ".$nxt['disabled_reason']);
					
					
					$pass_ok=0;
					if($nxt['pass_type']=='CRYPT')
					{
						if(crypt($pass, $nxt['pass']) == $nxt['pass'])	$pass_ok=1;
						else echo crypt($pass, $nxt['pass']);
					}					
					else if($nxt['pass_type']=='SHA1')
					{
						if(SHA1($pass) == $nxt['pass'])	$pass_ok=1;
					}
					else
					{
						if(MD5($pass) == $nxt['pass'])	$pass_ok=1;
					}
					
					if(!$pass_ok)
					{
						mysql_query("INSERT INTO `users_bad_auth` (`ip`, `time`) VALUES ('$ip', '$time')");
						$tmpl->msg("Неверная пара логин / пароль! Попробуйте снова!","err","Авторизоваться не удалось");
					}
					else
					{
					
						if( ($nxt['reg_email_confirm']=='1') || ($nxt['reg_phone_confirm']=='1') )
						{
							$ip=mysql_real_escape_string(getenv("REMOTE_ADDR"));
							$ua=mysql_real_escape_string($_SERVER['HTTP_USER_AGENT']);
							mysql_query("INSERT INTO `users_login_history` (`user_id`, `date`, `ip`, `useragent`, `method`)
							VALUES ({$nxt['id']}, NOW(), '$ip', '$ua', 'password')");
							if(mysql_errno())	throw new MysqlException("Не удалось выполнить вход.");
							$_SESSION['uid']=$nxt['id'];
							$_SESSION['name']=$nxt['name'];
							if($_SESSION['last_page'])
							{
								$lp=$_SESSION['last_page'];
								unset($_SESSION['last_page']);
								header("Location: ".$lp);
							}
							else if($_SESSION['redir_to'])	header("Location: ".$_SESSION['redir_to']);
							else				header("Location: user.php");
							exit();
						}
						else
						{
							header("Location: login.php?mode=conf&login=$login");
							exit();
						}
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

		$form_action='/login.php';
		if($CONFIG['site']['force_https_login'])
		{
			$host=$_SERVER['HTTP_HOST'];
			$form_action='https://'.$host.'/login.php';
		}
		$tmpl->AddText("
		<form method='post' action='$form_action' id='login-form' name='fefe'>
		<input type='hidden' name='opt' value='login'>
		<table id='login-table'>
		<tr><th colspan='2'>
		Введите данные:
		<tr><td colspan='2'>
		Если у Вас их нет, вы можете <a class='wiki' href='/login.php?mode=reg'>зарегистрироваться</a>
		<tr><td>
		Имя:<td>
		<input type='text' name='login' class='text' id='input_name' value='$login'>
		<tr><td>Пароль:<td>
		<input type='password' name='pass' class='text'>(<a class='wiki' href='?mode=rem'>Сменить</a>)<br>$m
		<tr><td><td>
		<button type='submit'>Вход!</button> ( <a class='wiki' href='/login.php?mode=rem'>Забыли пароль?</a> )
		</table></form>
		<table style='width: 800px'>
		<tr><th colspan='4'><center>Войти через</center></th></tr>
		<tr>
		<td><a href='/login_oid.php?oid=https://www.google.com/accounts/o8/id'><img src='/img/oid/google.png' alt='Войти через Google'></a></td>
		<td><a href='/login_oid.php?oid=ya.ru'><img src='/img/oid/yandex.png' alt='Войти через Яндекс'></a></td>
		<td><a href='/login_oid.php?oid=vkontakteid.ru'><img src='/img/oid/vkontakte.png' alt='Войти через Вконтакте'></a></td>
		<td><a href='/login_oid.php?oid=loginza.ru'><img src='/img/oid/loginza.png' alt='Войти через Loginza'></a></td>
		</tr>
		</table>
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
		$tmpl->SetTitle("Регистрация");
		$tmpl->AddText("<h1 id='page-title'>Регистрация</h1>");
		RegForm();
	}	else $tmpl->msg("Вы уже являетесь нашим зарегистрированным пользователем. Повторная регистрация не требуется.","info");
}
else if($mode=='regs')
{
	try
	{
		$login=@$_POST['login'];
		$email=@$_POST['email'];
		$phone=@$_POST['phone'];
		$img=strtoupper(@$_POST['img']);
		$subs=@$_POST['subs'];
		$subs=$subs?1:0;

		if($login=='')
			throw new RegException('Поле login не заполнено','login');
		if(strlen($login)<3)
			throw new RegException('login слишком короткий','login');
		if(strlen($login)>24)
			throw new RegException('login слишком длинный','login');
		if( !preg_match('/^[a-zA-Z\d]*$/', $login))
			throw new RegException('login должен состоять из латинских букв и цифр','login');

		$res=mysql_query("SELECT `id` FROM `users` WHERE `name`='$login'");
		if(mysql_num_rows($res))
			throw new RegException('Такой login занят. Используйте другой.','login');

		if(@$CONFIG['site']['allow_phone_regist'])
		{
			if($email=='' && $phone=='')
				throw new RegException('Нужно заполнить телефон или email','email');
		}
		else
		{
			if($email=='')
				throw new RegException('Поле email не заполнено','email');
		}
		
		
		if($email!='')
		{
			if( !preg_match('/^\w+([-\.\w]+)*\w@\w(([-\.\w])*\w+)*\.\w{2,8}$/', $email))
				throw new RegException('Неверный формат адреса e-mail. Адрес должен быть в формате user@host.zone','email');
			$res=mysql_query("SELECT `id` FROM `users` WHERE `reg_email`='$email'");
			if(mysql_errno())	throw new MysqlException("Не удалось проверить уникальность email");
			if(mysql_num_rows($res))
				throw new RegException('Пользователь с таким email уже зарегистрирован. Используйте другой.','email');
		}
		
		if($phone!='')
		{
			$phone='+7'.$phone;
			if( !preg_match('/^\+79\d{9}$/', $phone))
				throw new RegException('Неверный формат телефона. Номер должен быть в федеральном формате +79XXXXXXXXX '.$phone,'phone');
			$res=mysql_query("SELECT `id` FROM `users` WHERE `reg_phone`='$phone'");
			if(mysql_errno())	throw new MysqlException("Не удалось проверить уникальность телефона");
			if(mysql_num_rows($res))
				throw new RegException('Пользователь с таким email уже зарегистрирован. Используйте другой.','email');
		}
		
		if($img=='')
			throw new RegException('Код подтверждения не введён','img');
		if(strtoupper($_SESSION['captcha_keystring'])!=strtoupper($img))
			throw new RegException('Код подтверждения введён неверно','img');

		$email_conf=$email?substr(MD5(time()+rand(0,1000000)),0,8):'';
		$phone_conf=$phone?rand(1000,99999):'';
		$pass=keygen_unique(0,8,11);
		$sql_email=mysql_real_escape_string($email);
		$sql_phone=mysql_real_escape_string($phone);
		
		$sql_login=mysql_real_escape_string($login);
		if($CONFIG['site']['pass_type']=='MD5')
		{
			$sql_pass_hash=MD5($pass);
			$sql_pass_type='MD5';
		}
		else if($CONFIG['site']['pass_type']=='SHA1')
		{
			$sql_pass_hash=SHA1($pass);
			$sql_pass_type='SHA1';
		}
		else
		{
			if(CRYPT_SHA256 == 1)
			{
				$sql_pass_hash=crypt($pass, '$5$'.substr(MD5($login.rand(0,1000000)),0,16).'$');
			}
			else	$sql_pass_hash=crypt($pass);
			$sql_pass_type='CRYPT';
		}
		
		$res=mysql_query("INSERT INTO `users` (`name`, `pass`, `pass_type`, `pass_date_change`, `reg_email`, `reg_email_confirm`, `reg_phone`, `reg_phone_confirm`, `reg_date`, `reg_email_subscribe`, `reg_phone_subscribe`)
		VALUES ('$sql_login', '$sql_pass_hash', '$sql_pass_type',  NOW(), '$sql_email', '$email_conf', '$sql_phone', '$phone_conf', NOW(), $subs, $subs )");
		if(mysql_errno())	throw new MysqlException("Не удалось добвать пользователя! Попробуйте позднее!");
		
		if($email)
		{
			$msg=regMsg($login, $pass, $email_conf);
			mailto($email,"Регистрация на ".$CONFIG['site']['name'], $msg);
		}
		
		if($phone)
		{
			require_once('include/sendsms.php');
			$sender=new SMSSender();
			$sender->setNumber($phone);
			$sender->setText("Ваш код: $phone_conf\nЛогин:$login\nПароль:$pass\n{$CONFIG['site']['name']}");
			$sender->send();
		}
		

		
		
		
		$tmpl->AddText("<h1 id='page-title'>Завершение регистрации</h1>
		<form action='/login.php' method='post'>
		<input type='hidden' name='mode' value='conf'>
		<input type='hidden' name='login' value='$login'>");
		if($email)
			$tmpl->AddText("Для проверки, что указанный адрес электронной почты принадлежит Вам, на него было выслано сообщение.<br><b>Введите код, полученный по email:</b><br>
			<input type='text' name='e'><br>Если Вы не получите письмо в течение трёх часов, возможно ваш сервер не принимает наше сообщение. Сообщите о проблеме администратору своего почтового сервера, или используйте другой!<br><br>");
		if($phone)
			$tmpl->AddText("Для проверки, что номер телефона принадлежит Вам, на него было выслано сообщение.<br><b>Введите код, полученный по SMS:</b><br>
			<input type='text' name='p'><br>SMS сообщения обычно приходят в течение 1 часа.<br><br>");
		$tmpl->AddText("<button type='submit'>Продолжить</button>		
		</form>");

	}
	catch(MysqlException $e)
	{
		$tmpl->msg($e->getMessage()."<br>Сообщение передано администратору",'err',"Ошибка при регистрации");
		mailto($CONFIG['site']['admin_email'],"ВАЖНО! Ошибка регистрации на ".$CONFIG['site']['name'], $e->getMessage());
	}
	catch(RegException $e)
	{
		mysql_query("ROLLBACK");
		$tmpl->SetTitle("Регистрация");
		$tmpl->SetText("<h1 id='page-title'>Регистрация</h1>");
		$tmpl->msg("Проверьте данные! ".$e->getMessage(),"err","Неверный ввод!");
		RegForm($e->target, $e->getMessage());

	}
	catch(Exception $e)
	{
		mysql_query("ROLLBACK");
		$tmpl->msg($e->getMessage(),"err","Ошибка при регистрации");
	}

}
else if($mode=='conf')
{
	$login=$_REQUEST['login'];
	$e=@$_REQUEST['e'];
	$p=@$_REQUEST['p'];
	
	
try
{
	$sql_login=mysql_real_escape_string($login);
	$res=mysql_query("SELECT `id`, `name`, `reg_email`, `reg_email_confirm`, `reg_phone`, `reg_phone_confirm` FROM `users` WHERE `name`='$sql_login'");
	if(mysql_errno())	throw new MysqlException("Не удалось получить данные. Попробуйте позднее!");
	if($nxt=mysql_fetch_assoc($res))
	{
		$e_key=$p_key=0;
		if($e && $e==$nxt['reg_email_confirm'])
		{
			mysql_query("UPDATE `users` SET `reg_email_confirm`='1' WHERE `id` = '{$nxt['id']}' ");
		}
		else if($e) $e_key=1;
		
		if($p && $p==$nxt['reg_phone_confirm'])
		{
			mysql_query("UPDATE `users` SET `reg_phone_confirm`='1' WHERE `id` = '{$nxt['id']}' ");
		}
		else if($p) $p_key=1;
		
		$res=mysql_query("SELECT `id`, `name`, `reg_email`, `reg_email_confirm`, `reg_phone`, `reg_phone_confirm` FROM `users` WHERE `name`='$sql_login'");
		if(mysql_errno())	throw new MysqlException("Не удалось получить данные. Попробуйте позднее!");
		$nxt=mysql_fetch_assoc($res);
		
		if(($nxt['reg_email_confirm']!='1' && $nxt['reg_email_confirm']!='0') || ($nxt['reg_phone_confirm']!='1' && $nxt['reg_phone_confirm']!='0') )
		{
			$tmpl->AddText("<h1 id='page-title'>Завершение регистрации</h1>
			<form action='/login.php' method='post'>
			<input type='hidden' name='mode' value='conf'>
			<input type='hidden' name='login' value='$login'>");
			if($nxt['reg_email_confirm']!='1' && $nxt['reg_email_confirm']!='0')
			{
				$tmpl->AddText("Для проверки, что указанный адрес электронной почты принадлежит Вам, на него было выслано сообщение.<br><b>Введите код, полученный по email:</b><br>
				<input type='text' name='e'>");
				if($e_key)	$tmpl->AddText("<br><span style='color: #f00;'>Вы ввели неверный код подтверждения!");
				$tmpl->AddText("<br>Если Вы не получите письмо в течение трёх часов, возможно ваш сервер не принимает наше сообщение. Сообщите о проблеме администратору своего почтового сервера, или используйте другой!<br><br>");
			}
			if($nxt['reg_phone_confirm']!='1' && $nxt['reg_phone_confirm']!='0')
			{
				$tmpl->AddText("Для проверки, что номер телефона принадлежит Вам, на него было выслано сообщение.<br><b>Введите код, полученный по SMS:</b><br>
				<input type='text' name='p'>");
				if($e_key)	$tmpl->AddText("<br><span style='color: #f00;'>Вы ввели неверный код подтверждения!");
				$tmpl->AddText("<br>SMS сообщения обычно приходят в течение 1 часа.<br><br>");
			}
			$tmpl->AddText("<button type='submit'>Продолжить</button>		
			</form>");		
		}
		else
		{
			$ip=mysql_real_escape_string(getenv("REMOTE_ADDR"));
			$ua=mysql_real_escape_string($_SERVER['HTTP_USER_AGENT']);
			mysql_query("INSERT INTO `users_login_history` (`user_id`, `date`, `ip`, `useragent`, `method`)
			VALUES ({$nxt['id']}, NOW(), '$ip', '$ua', 'register')");
			if(mysql_errno())	throw new MysqlException("Не удалось выполнить вход.");
			$_SESSION['uid']=$nxt['id'];
			$_SESSION['name']=$nxt['name'];
// 			if($_SESSION['last_page'])
// 			{
// 				$lp=$_SESSION['last_page'];
// 				unset($_SESSION['last_page']);
// 				header("Location: ".$lp);
// 			}
			//else 
			$tmpl->msg("Регистрация завершена! Теперь Вам доступны новые возможности!","ok");
		}
	}
	else $tmpl->msg("Пользователь не найден в базе","err");
}
catch(MysqlException $e)
{
	global $CONFIG;
	$tmpl->msg($e->getMessage()."<br>Сообщение передано администратору",'err',"Ошибка при регистрации");
	mailto($CONFIG['site']['admin_email'],"ВАЖНО! Ошибка регистрации на ".$CONFIG['site']['name'], $e->getMessage());
}
catch(RegException $e)
{
	mysql_query("ROLLBACK");
	$tmpl->SetTitle("Регистрация");
	$tmpl->SetText("<h1 id='page-title'>Регистрация</h1>");
	$tmpl->msg("Проверьте данные! ".$e->getMessage(),"err","Неверный ввод!");
	RegForm($e->target, $e->getMessage());

}
catch(Exception $e)
{
	mysql_query("ROLLBACK");
	$tmpl->msg($e->getMessage(),"err","Ошибка при регистрации");
}

}
/// До сюда сделано
else if($mode=='rem')
{
	if(!isset($_REQUEST['login']))
	{
		$tmpl->SetText("<h1 id='page-title'>Восстановление пароля</h1>
		<p id='text'>Для начала процедуры смены пароля введите логин на сайте, номер телефона, или адрес электронной почты, указанный при регистрации:</p>
		<form method='post'>
		<input type='hidden' name='mode' value='rem'>
		<input type='text' name='login'><br>
		Подтвердите, что вы не робот, введите текст с картинки:<br>
		<img src='/kcaptcha/index.php'><br>
		<input type='text' name='img'><br>
		<button type='submit'>Далее</button>
		</form>");
	}
	else
	{
		$login=$_REQUEST['login'];
		if(@$_REQUEST['img']=='')
			throw new Exception('Код подтверждения не введён');
		if(strtoupper($_SESSION['captcha_keystring'])!=strtoupper($_REQUEST['img']))
			throw new Exception('Код подтверждения введён неверно');
			
		$sql_login=mysql_real_escape_string($login);
		$res=mysql_query("SELECT `id`, `name`, `reg_email`, `reg_email_confirm`, `reg_phone`, `reg_phone_confirm`, `disabled`, `disabled_reason` FROM `users` WHERE `name`='$sql_login' OR `reg_email`='$sql_login' OR `reg_phone`='$sql_login'");
		if(mysql_errno())		throw new MysqlException("Не удалось получить данные пользователя");
		if(!mysql_num_rows($res))	throw new Exception("Пользователь не найден!");
		$user_info=mysql_fetch_assoc($res);
		if($user_info['disabled'])	throw new Exception("Пользователь заблокирован (забанен). Причина блокировки: ".$user_info['disabled_reason']);
		
		if(!isset($_REQUEST['method']))
		{
			$tmpl->AddText("<h1 id='page-title'>Восстановление пароля - шаг 2</h1>
			<form method='post'>
			<input type='hidden' name='mode' value='rem'>
			<input type='hidden' name='login' value='$login'>
			<input type='hidden' name='img' value='{$_REQUEST['img']}'>
			<fieldset><legend>Восстановить пароль при помощи</legend>");
			if($user_info['reg_email']!='' && $user_info['reg_email_confirm']=='1')
				$tmpl->AddText("<label><input type='radio' name='method' value='email'>Электронной почты</label><br>");
			if(preg_match('/^\+79\d{9}$/', $user_info['reg_phone']) && $user_info['reg_phone_confirm']=='1' && @$CONFIG['site']['allow_phone_regist'])
				$tmpl->AddText("<label><input type='radio' name='method' value='sms'>SMS на мобильный телефон</label><br>");
			if(@$CONFIG['site']['allow_openid'])
			{
				$res=mysql_query("SELECT `openid_identify` FROM `users_openid` WHERE `user_id`={$user_info['id']}");
				if(mysql_errno())		throw new MysqlException("Не удалось получить данные openid");
				while($openid_info=mysql_fetch_row($res))
				{
					$oid=htmlentities($openid_info[0],ENT_QUOTES);
					$tmpl->AddText("<label><input type='radio' name='method' value='$oid'>OpenID аккаунта $oid</label><br>");
				}
			}
			$tmpl->AddText("</fieldset>
			<br><button type='submit'>Далее</button>
			</form>");
		}
		else
		{
			$method=$_REQUEST['method'];
			if($method=='email')
			{
				$key=substr(md5($user_info[0].$user_info[1].$user_info[2].$user_info[4].time()),8);
				$proto='http';
				if($CONFIG['site']['force_https_login'] || $CONFIG['site']['force_https'])	$proto='https';

				mysql_query("UPDATE `users` SET `pass_change`='$key' WHERE `id`='{$user_info['id']}'");
				$msg="Поступил запрос на смену пароля доступа к сайту {$CONFIG['site']['name']} для аккаунта {$user_info['name']}.
				Если Вы действительно хотите сменить пароль, перейдите по ссылке $proto://{$CONFIG['site']['name']}/login.php?mode=remn&amp;login={$user_info['name']}&amp;s=$key
				
				----------------------------------------
				Сообщение сгенерировано автоматически, отвечать на него не нужно!";
				mailto($nxt[2],"Восстановление забытого пароля",$msg);
				$tmpl->msg("Проверьте почтовый ящик!","ok");			
			}
			else throw new Exception("Метод не реализован или не доступен");
		}
	
	}
}
else if($mode=='rems')
{
	$tmpl->SetText("<h1 id='page-title'>Смена пароля</h1>");
	$res=mysql_query("SELECT `id`,`name`,`email`,`confirm`,`date_reg` FROM `users` WHERE `name`='$login' OR `email`='$login'");
	if(@$nxt=mysql_fetch_row($res))
	{
		$key=md5($nxt[0].$nxt[1].$nxt[2].$nxt[4].time());
		$proto='http';
		if($CONFIG['site']['force_https_login'] || $CONFIG['site']['force_https'])	$proto='https';

		mysql_query("UPDATE `users` SET `passch`='$key' WHERE `id`='$nxt[0]'");
		$msg="Поступил запрос на смену пароля доступа к сайту {$CONFIG['site']['name']} для аккаунта $nxt[1].
Если Вы действительно хотите сменить пароль, перейдите по ссылке $proto://{$CONFIG['site']['name']}/login.php?mode=remn&s=$key
Если Вы не давали запрос на смену пароля, обязательно отмените этот запрос, авторизовавшись на сайте!
----------------------------------------
Сообщение сгенерировано автоматически, отвечать на него не нужно!";
		mailto($nxt[2],"Восстановление забытого пароля",$msg);
		$tmpl->msg("Проверьте почтовый ящик!","ok");
	}
	else $tmpl->msg("Пользователя с таким именем или адресом электронной почты не найдено! Возможно, он был удален по неактивности.","err");
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
	$res=mysql_query("UPDATE `users` SET `reg_email_subscribe`='0' WHERE `reg_email`='$email'");
	if(mysql_errno())		throw new MysqlException("Не удалось отписаться. Сообщите администратору о проблеме.");
	if(mysql_affected_rows())
	{
		$tmpl->msg("Вы успешно отказались от автоматической рассылки!","ok");
		$c=1;
	}

	$res=mysql_query("UPDATE `doc_agent` SET `no_mail`='1' WHERE `email`='$email'");
	if(mysql_errno())		throw new MysqlException("Не удалось отписаться. Сообщите администратору о проблеме.");
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