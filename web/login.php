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
$login=request('login');
$pass=request('pass');
$mode=request('mode');

function attack_test()
{
	global $db;
	$lock=0;
	$captcha=0;
	$ip=getenv("REMOTE_ADDR");

	$sql='SELECT `id` FROM `users_bad_auth`';

	$tm = time()-60*60*3;
	$res = $db->query("$sql WHERE `ip`='$ip' AND `time`>'$tm'");
	if($res->num_rows > 20)		return 2;	// Более 20 ошибок вводе пароля c данного IP за последние 3 часа. Блокируем аутентификацию.
	$tm = time()-60*30;
	$res = $db->query("$sql WHERE `ip`='$ip' AND `time`>'$tm'");
	if($res->num_rows > 2)		$captcha=1;	// Более двух ошибок ввода пароля c данного IP за последние 30 минут. Планируем запрос captcha.

	$ip_a = explode(".",$ip);
	if(!is_array($ip_a))	return $captcha;	// Если IP не удаётся разделить на элементы - завершаем тест
	if(count($ip_a) < 2)	return $captcha;	// Если IP не удаётся разделить на элементы - завершаем тест

	$tm = time()-60*60*3;
	$res = $db->query("$sql WHERE `ip`='$ip_a[0].$ip_a[1].$ip_a[2].%' AND `time`>'$tm'");
	if($res->num_rows > 100)	return 3;	// Более 100 ошибок вводе пароля c подсети /24 за последние 3 часа. Блокируем аутентификацию.
	$tm = time()-60*30;
	$res = $db->query("$sql WHERE `ip`='$ip_a[0].$ip_a[1].$ip_a[2].%' AND `time`>'$tm'");
	if($res->num_rows > 6)		$captcha=1;	// Более 6 ошибок ввода пароля c подсети /24 за последние 30 минут. Планируем запрос captcha.

	$tm = time()-60*60*3;
	$res = $db->query("$sql WHERE `ip`='$ip_a[0].$ip_a[1].%' AND `time`>'$tm'");
	if($res->num_rows > 500)	return 3;	// Более 500 ошибок вводе пароля c подсети /16 за последние 3 часа. Блокируем аутентификацию.
	$tm = time()-60*30;
	$res = $db->query("$sql WHERE `ip`='$ip_a[0].$ip_a[1].%' AND `time`>'$tm'");
	if($res->num_rows > 30)		$captcha=1;	// Более 30 ошибок ввода пароля c подсети /16 за последние 30 минут. Планируем запрос captcha.

	$tm = time()-60*15;
	$res = $db->query("$sql WHERE `time`>'$tm'");
	if($res->num_rows > 100)	$captcha=1;	// Более 100 ошибок ввода пароля со всей сети за последние 15 минут. Планируем запрос captcha.

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
Login: $login
Pass: $pass

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
	$login	= html_out(request('login'));
	$pass	= html_out(request('pass'));
	$phone	= html_out(request('phone'));
	$email	= html_out(request('email'));

	$err_msgs=array('login'=>'', 'email'=>'','img'=>'','phone'=>'');
	$err_msgs[$err_target]="<div style='color: #c00'>$err_msg</div>";

	$form_action='/login.php';
	if($CONFIG['site']['force_https_login'])
	{
		$host=$_SERVER['HTTP_HOST'];
		$form_action='https://'.$host.'/login.php';
	}

	$tmpl->addContent("<p id='text'>
	Для использования всех возможностей этого сайта, необходимо пройти процедуру регистрации. Регистрация не сложная,
	и займёт всего несколько минут. Все зарегистрированные пользователи автоматически получают возможность приобретать товар по специальным ценам!</p>
	<p>Регистрируясь, Вы даёте согласие на хранение, обработку и публикацию своей персональной информации, в соответствии с законом РФ &quot;О персональных данных&quot;.</p>
	<form action='$form_action' method='post' id='reg-form'>
	<h2>Для регистрации заполните следующую форму:</h2>
	<input type='hidden' name='mode' value='regs'>
	<table cellspacing='15'>

	<tr><td>
	<b>Желаемый логин</b><br>
	<small>латинские буквы, цифры, длина от 3 до 24 символов	</small>
	<td>
	<input type='text' name='login' value='$login' id='login'><br>
	<span id='login_valid' style='color: #c00'>{$err_msgs['login']}</span>");

	if(@$CONFIG['site']['allow_phone_regist'])
		$tmpl->addContent("<tr><td colspan='2'>Заполните хотя бы одно из полей: номер телефона и e-mail");

	$tmpl->addContent("<tr><td>
	<b>Адрес электронной почты e-mail</b><br>
	<small>в формате user@host.zone</small>
	<td><input type='text' name='email' value='$email' id='email'><br>
	<span id='email_valid'>{$err_msgs['email']}</span>");

	if(@$CONFIG['site']['allow_phone_regist'])
		$tmpl->addContent("
	<tr><td><b>Мобильный телефон: <span id='phone_num'></span></b><br>
	<small>Российский, 10 цифр, без +7 или 8</small>
	<td>
	+7<input type='text' name='phone' value='$phone' maxlength='10' placeholder='Номер' id='phone'><br>
	<span id='phone_valid'>{$err_msgs['phone']}</span>");

	$tmpl->addContent("<tr><td colspan='2'><input type='checkbox' name='subs' value='1' checked>Подписаться на новости и другую информацию
	<tr><td colspan='2'><b>Подтвердите что вы не робот, введя текст с картинки:</b>
	<tr><td>
	<img src='/kcaptcha/index.php'><br>
	<td>
	<input type='text' name='img'>{$err_msgs['img']}
	<tr><td style='color: #c00;'><td>
	<button type='submit'>Далее &gt;&gt;</button>
	</form>
	</table>");
	if(@$CONFIG['site']['allow_openid'])
		$tmpl->addContent("<b>Примечание:</b> Если Вы хоте зарегистрироваться, используя свой OpenID, Вам <a href='/login_oid.php'>сюда</a>!<br>");
	$tmpl->addContent("<script type='text/javascript'>
	");

	if(@$CONFIG['site']['allow_phone_regist'])
		$tmpl->addContent("
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

	$tmpl->addContent("
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

try
{

if(!isset($_REQUEST['mode']))
{
	if(@$_SESSION['uid'])
	{
		include("user.php");
		exit();
	}
	$pass = @$_REQUEST['pass'];

	// Куда переходить после авторизации
	$from=getenv("HTTP_REFERER");
	if($from)
	{
		$froma=explode("/",$from);
		$proto=@$_SERVER['HTTPS']?'https':'http';
		if( ($froma[2]!=$_SERVER['HTTP_HOST']) || ($froma[3]=='login.php') || ($froma[3]=='') )	$from="$proto://".$_SERVER['HTTP_HOST'];
	}
	$_SESSION['redir_to']=$from;

	$tmpl->addContent("<h1 id='page-title'>Аутентификация</h1>");
	$tmpl->setTitle("Аутентификация");
	if(isset($_REQUEST['cont']))	$tmpl->addContent("<div id='page-info'>Для доступа в этот раздел Вам необходимо пройти аутентификацию.</div>");

	$ip = $db->real_escape_string(getenv("REMOTE_ADDR"));
	$time = time()+60;
	$at = attack_test();
	if($at > 1)
	{
		$res = $db->query("INSERT INTO `users_bad_auth` (`ip`, `time`) VALUES ('$ip', '$time')");
	}
	if($at >= 3)
	{
		$tmpl->msg("Из-за попыток подбора паролей к сайту доступ с вашей подсети заблокирован! Вы сможете авторизоваться через несколько часов после прекращения попыток подбора пароля. Если Вы не предпринимали попыток подбора пароля, обратитесь к Вашему поставщику интернет-услуг - возможно, кто-то другой пытается подобрать пароль, используя ваш адрес.","err","Доступ заблокирован");
	}
	else if($at == 2)
	{
		$tmpl->msg("Из-за попыток подбора паролей к сайту доступ с вашего адреса заблокирован! Вы сможете авторизоваться через несколько часов после прекращения попыток подбора пароля. Если Вы не предпринимали попыток подбора пароля, обратитесь к Вашему поставщику интернет-услуг - возможно, кто-то другой пытается подобрать пароль, используя ваш адрес.","err","Доступ заблокирован");
	}
	else
	{
		if(@$_REQUEST['opt']=='login')
		{
			if( ($at == 1) && ( (strtoupper($_SESSION['captcha_keystring'])!=strtoupper(@$_REQUEST['img'])) || ($_SESSION['captcha_keystring']=='') ) )
			{
				$tmpl->msg("Введите правильный код подтверждения, изображенный на картинке", "err");
				$res = $db->query("INSERT INTO `users_bad_auth` (`ip`, `time`) VALUES ('$ip', '$time')");
			}
			else
			{
				$login_sql = $db->real_escape_string($_REQUEST['login']);
				$res = $db->query("SELECT `users`.`id`, `users`.`name`, `users`.`pass`, `users`.`pass_type`, `users`.`reg_email_confirm`, `users`.`reg_phone_confirm`, `users`.`disabled`, `users`.`disabled_reason`, `users`.`bifact_auth` FROM `users`
				WHERE `name`='$login_sql'");
				if($res->num_rows)
				{
					$nxt = $res->fetch_assoc();
					if($nxt['disabled'])	throw new Exception("Пользователь заблокирован (забанен). Причина блокировки: ".$nxt['disabled_reason']);

					$pass_ok=0;
					if($nxt['pass_type']=='CRYPT')
					{
						if(crypt($pass, $nxt['pass']) == $nxt['pass'])	$pass_ok=1;
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
						$res = $db->query("INSERT INTO `users_bad_auth` (`ip`, `time`) VALUES ('$ip', '$time')");
						$tmpl->msg("Неверная пара логин / пароль! Попробуйте снова!","err","Авторизоваться не удалось");
					}
					else
					{

						if( ($nxt['reg_email_confirm']=='1') || ($nxt['reg_phone_confirm']=='1') )
						{
							$ip=$db->real_escape_string(getenv("REMOTE_ADDR"));
							$ua=$db->real_escape_string($_SERVER['HTTP_USER_AGENT']);
							$res = $db->query("INSERT INTO `users_login_history` (`user_id`, `date`, `ip`, `useragent`, `method`)
							VALUES ({$nxt['id']}, NOW(), '$ip', '$ua', 'password')");
							$_SESSION['uid'] = $nxt['id'];
							$_SESSION['name'] = $nxt['name'];
							if(@$_SESSION['last_page'])
							{
								$lp=$_SESSION['last_page'];
								unset($_SESSION['last_page']);
								header("Location: ".$lp);
                                                        }
							else if(@$_SESSION['redir_to'])	header("Location: ".$_SESSION['redir_to']);
							else				header("Location: user.php");
							exit();
						}
						else
						{
							header("Location: login.php?mode=conf&login=".urlencode($_REQUEST['login']));
							exit();
						}
					}
				}
				else
				{
					$res = $db->query("INSERT INTO `users_bad_auth` (`ip`, `time`) VALUES ('$ip', '$time')");
					$tmpl->msg("Неверная пара логин / пароль! Попробуйте снова!","err","Авторизоваться не удалось");
				}
			}
		}
		$at = attack_test();

		if($at > 0)
			$m="<tr><td>
			Введите код подтверждения, изображенный на картинке:<br>
			<img src='/kcaptcha/index.php' alt='Включите отображение картинок!'><td>
			<input type='text' name='img'>";
		else $m='';

		$form_action='/login.php';
		if($CONFIG['site']['force_https_login'])
		{
			$host=$_SERVER['HTTP_HOST'];
			$form_action='https://'.$host.'/login.php';
		}
		$login_html=html_out(request('login'));
		$tmpl->addContent("
		<form method='post' action='$form_action' id='login-form' name='fefe'>
		<input type='hidden' name='opt' value='login'>
		<table id='login-table'>
		<tr><th colspan='2'>
		Введите данные:
		<tr><td colspan='2'>
		Если у Вас их нет, вы можете <a class='wiki' href='/login.php?mode=reg'>зарегистрироваться</a>
		<tr><td>
		Имя:<td>
		<input type='text' name='login' class='text' id='input_name' value='$login_html'>
		<tr><td>Пароль:<td>
		<input type='password' name='pass' class='text'>(<a class='wiki' href='?mode=rem'>Сменить</a>)<br>$m
		<tr><td><td>
		<button type='submit'>Вход!</button> ( <a class='wiki' href='/login.php?mode=rem'>Забыли пароль?</a> )
		</table></form>");
		if(@$CONFIG['site']['allow_openid'])
			$tmpl->addContent("
			<table style='width: 800px'>
			<tr><th colspan='4'><center>Войти через</center></th></tr>
			<tr>
			<td><a href='/login_oid.php?oid=https://www.google.com/accounts/o8/id'><img src='/img/oid/google.png' alt='Войти через Google'></a></td>
			<td><a href='/login_oid.php?oid=ya.ru'><img src='/img/oid/yandex.png' alt='Войти через Яндекс'></a></td>
			<td><a href='/login_oid.php?oid=vkontakteid.ru'><img src='/img/oid/vkontakte.png' alt='Войти через Вконтакте'></a></td>
			<td><a href='/login_oid.php?oid=loginza.ru'><img src='/img/oid/loginza.png' alt='Войти через Loginza'></a></td>
			</tr>
			</table>");
		$tmpl->addContent("
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
		$tmpl->setTitle("Регистрация");
		$tmpl->addContent("<h1 id='page-title'>Регистрация</h1>");
		RegForm();
	}	else $tmpl->msg("Вы уже являетесь нашим зарегистрированным пользователем. Повторная регистрация не требуется.","info");
}
else if($mode=='regs')
{
	try
	{
		$login = request('login');
		$email = request('email');
		$phone = request('phone');
		$img = strtoupper(@$_REQUEST['img']);
		$subs = @$_REQUEST['subs'];
		$subs = $subs?1:0;
		$db->query("START TRANSACTION");
		if($login=='')
			throw new RegException('Поле login не заполнено','login');
		if(strlen($login)<3)
			throw new RegException('login слишком короткий','login');
		if(strlen($login)>24)
			throw new RegException('login слишком длинный','login');
		if( !preg_match('/^[a-zA-Z\d]*$/', $login))
			throw new RegException('login должен состоять из латинских букв и цифр','login');

		$sql_login = $db->real_escape_string($login);
		$res=$db->query("SELECT `id` FROM `users` WHERE `name`='$sql_login'");
		if($res->num_rows)
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
			$res = $db->query("SELECT `id` FROM `users` WHERE `reg_email`='$email'");
			if($res->num_rows)
				throw new RegException('Пользователь с таким email уже зарегистрирован. Используйте другой.','email');
		}

		if($phone!='')
		{
			$phone='+7'.$phone;
			if( !preg_match('/^\+79\d{9}$/', $phone))
				throw new RegException('Неверный формат телефона. Номер должен быть в федеральном формате +79XXXXXXXXX '.$phone,'phone');
			$res=$db->query("SELECT `id` FROM `users` WHERE `reg_phone`='$phone'");
			if($res->num_rows)
				throw new RegException('Пользователь с таким телефоном уже зарегистрирован. Используйте другой.','phone');
		}

		if($img=='')
			throw new RegException('Код подтверждения не введён','img');
		if(strtoupper($_SESSION['captcha_keystring'])!=strtoupper($img))
			throw new RegException('Код подтверждения введён неверно','img');

		$email_conf=$email?substr(MD5(time()+rand(0,1000000)),0,8):'';
		$phone_conf=$phone?rand(1000,99999):'';
		$pass=keygen_unique(0,8,11);
		$sql_email=$db->real_escape_string($email);
		$sql_phone=$db->real_escape_string($phone);

		$sql_login=$db->real_escape_string($login);
		if(@$CONFIG['site']['pass_type']=='MD5')
		{
			$sql_pass_hash=MD5($pass);
			$sql_pass_type='MD5';
		}
		else if(@$CONFIG['site']['pass_type']=='SHA1')
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

		$res=$db->query("INSERT INTO `users` (`name`, `pass`, `pass_type`, `pass_date_change`, `reg_email`, `reg_email_confirm`, `reg_phone`, `reg_phone_confirm`, `reg_date`, `reg_email_subscribe`, `reg_phone_subscribe`)
		VALUES ('$sql_login', '$sql_pass_hash', '$sql_pass_type',  NOW(), '$sql_email', '$email_conf', '$sql_phone', '$phone_conf', NOW(), $subs, $subs )");
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
			$sender->setContent("Ваш код: $phone_conf\r\nЛогин:$login\r\nПароль:$pass\r\n{$CONFIG['site']['name']}");
			$sender->send();
		}
		$db->query("COMMIT");
		$tmpl->addContent("<h1 id='page-title'>Завершение регистрации</h1>
		<form action='/login.php' method='post'>
		<input type='hidden' name='mode' value='conf'>
		<input type='hidden' name='login' value='$login'>");
		if($email)
			$tmpl->addContent("Для проверки, что указанный адрес электронной почты принадлежит Вам, на него было выслано сообщение.<br><b>Введите код, полученный по email:</b><br>
			<input type='text' name='e'><br>Если Вы не получите письмо в течение трёх часов, возможно ваш сервер не принимает наше сообщение. Сообщите о проблеме администратору своего почтового сервера, или используйте другой!<br><br>");
		if($phone)
			$tmpl->addContent("Для проверки, что номер телефона принадлежит Вам, на него было выслано сообщение.<br><b>Введите код, полученный по SMS:</b><br>
			<input type='text' name='p'><br>SMS сообщения обычно приходят в течение 1 часа.<br><br>");
		$tmpl->addContent("<button type='submit'>Продолжить</button>
		</form>");


	}
	catch(mysqli_sql_exception $e)
	{
		$id = $tmpl->logger($e->getMessage(), 1);
		$tmpl->msg("Ошибка при регистрации. Порядковый номер - $id<br>Сообщение передано администратору",'err',"Ошибка при регистрации");
		mailto($CONFIG['site']['admin_email'],"ВАЖНО! Ошибка регистрации на ".$CONFIG['site']['name'].". номер в журнале - $id", $e->getMessage());
	}
	catch(RegException $e)
	{
		$db->query("ROLLBACK");
		$tmpl->setTitle("Регистрация");
		$tmpl->setContent("<h1 id='page-title'>Регистрация</h1>");
		$tmpl->msg("Проверьте данные! ".$e->getMessage(),"err","Неверный ввод!");
		RegForm($e->target, $e->getMessage());

	}
	catch(Exception $e)
	{
		$db->query("ROLLBACK");
		$tmpl->msg($e->getMessage(),"err","Ошибка при регистрации");
	}

}
else if($mode=='conf')
{
	$login=$_REQUEST['login'];
	$e=@$_REQUEST['e'];
	$p=@$_REQUEST['p'];

	$sql_login=$db->real_escape_string($login);
	$res=$db->query("SELECT `id`, `name`, `reg_email`, `reg_email_confirm`, `reg_phone`, `reg_phone_confirm` FROM `users` WHERE `name`='$sql_login'");
	if($nxt=$res->fetch_assoc())
	{
		$e_key=$p_key=0;
		if($e && $e==$nxt['reg_email_confirm'])
		{
			$r=$db->query("UPDATE `users` SET `reg_email_confirm`='1' WHERE `id` = '{$nxt['id']}' ");
		}
		else if($e) $e_key=1;

		if($p && $p==$nxt['reg_phone_confirm'])
		{
			$r=$db->query("UPDATE `users` SET `reg_phone_confirm`='1' WHERE `id` = '{$nxt['id']}' ");
		}
		else if($p) $p_key=1;

		$res=$db->query("SELECT `id`, `name`, `reg_email`, `reg_email_confirm`, `reg_phone`, `reg_phone_confirm` FROM `users` WHERE `name`='$sql_login'");
		$nxt=$res->fetch_assoc();

		if(($nxt['reg_email_confirm']!='1' && $nxt['reg_email_confirm']!='') || ($nxt['reg_phone_confirm']!='1' && $nxt['reg_phone_confirm']!='') )
		{
			$tmpl->addContent("<h1 id='page-title'>Завершение регистрации</h1>
			<form action='/login.php' method='post'>
			<input type='hidden' name='mode' value='conf'>
			<input type='hidden' name='login' value='$login'>");
			if($nxt['reg_email_confirm']!='1' && $nxt['reg_email_confirm']!='')
			{
				$tmpl->addContent("Для проверки, что указанный адрес электронной почты принадлежит Вам, на него было выслано сообщение.<br><b>Введите код, полученный по email:</b><br>
				<input type='text' name='e'>");
				if($e_key)	$tmpl->addContent("<br><span style='color: #f00;'>Вы ввели неверный код подтверждения!");
				$tmpl->addContent("<br>Если Вы не получите письмо в течение трёх часов, возможно ваш сервер не принимает наше сообщение. Сообщите о проблеме администратору своего почтового сервера, или используйте другой!<br><br>");
			}
			if($nxt['reg_phone_confirm']!='1' && $nxt['reg_phone_confirm']!='')
			{
				$tmpl->addContent("Для проверки, что номер телефона принадлежит Вам, на него было выслано сообщение.<br><b>Введите код, полученный по SMS:</b><br>
				<input type='text' name='p'>");
				if($e_key)	$tmpl->addContent("<br><span style='color: #f00;'>Вы ввели неверный код подтверждения!");
				$tmpl->addContent("<br>SMS сообщения обычно приходят в течение 1 часа.<br><br>");
			}
			$tmpl->addContent("<button type='submit'>Продолжить</button>
			</form>");
		}
		else
		{
			$ip=$db->real_escape_string(getenv("REMOTE_ADDR"));
			$ua=$db->real_escape_string($_SERVER['HTTP_USER_AGENT']);
			$res=$db->query("INSERT INTO `users_login_history` (`user_id`, `date`, `ip`, `useragent`, `method`)
			VALUES ({$nxt['id']}, NOW(), '$ip', '$ua', 'register')");
			$_SESSION['uid']=$nxt['id'];
			$_SESSION['name']=$nxt['name'];

			$tmpl->msg("Регистрация завершена! Теперь Вам доступны новые возможности!","ok");
		}
	}
	else $tmpl->msg("Пользователь не найден в базе","err");
}

else if($mode=='rem')
{

	if(!isset($_REQUEST['login']))
	{
		$tmpl->setContent("<h1 id='page-title'>Восстановление доступа</h1>
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

		$sql_login=$db->real_escape_string($login);
		$res=$db->query("SELECT `id`, `name`, `reg_email`, `reg_email_confirm`, `reg_phone`, `reg_phone_confirm`, `disabled`, `disabled_reason` FROM `users` WHERE `name`='$sql_login' OR `reg_email`='$sql_login' OR `reg_phone`='$sql_login'");
		if(! $res->num_rows )	throw new Exception("Пользователь не найден!");
		$user_info=$res->fetch_assoc();
		if($user_info['disabled'])	throw new Exception("Пользователь заблокирован (забанен). Причина блокировки: ".$user_info['disabled_reason']);

		if(!isset($_REQUEST['method']))
		{
			$tmpl->addContent("<h1 id='page-title'>Восстановление доступа - шаг 2</h1>
			<form method='post'>
			<input type='hidden' name='mode' value='rem'>
			<input type='hidden' name='login' value='$login'>
			<input type='hidden' name='img' value='{$_REQUEST['img']}'>
			<fieldset><legend>Восстановить доступ при помощи</legend>");
			if($user_info['reg_email']!='' && $user_info['reg_email_confirm']=='1')
				$tmpl->addContent("<label><input type='radio' name='method' value='email'>Электронной почты</label><br>");
			if(preg_match('/^\+79\d{9}$/', $user_info['reg_phone']) && $user_info['reg_phone_confirm']=='1' && @$CONFIG['site']['allow_phone_regist'])
				$tmpl->addContent("<label><input type='radio' name='method' value='sms'>SMS на мобильный телефон</label><br>");
			if(@$CONFIG['site']['allow_openid'])
			{
				$res=$db->query("SELECT `openid_identify` FROM `users_openid` WHERE `user_id`={$user_info['id']}");
				while($openid_info=$res->fetch_row())
				{
					$oid=htmlentities($openid_info[0],ENT_QUOTES);
					$tmpl->addContent("<label><input type='radio' name='method' value='$oid'>OpenID аккаунта $oid</label><br>");
				}
			}
			$tmpl->addContent("</fieldset>
			<br><button type='submit'>Далее</button>
			</form>");
		}
		else
		{
			$method=$_REQUEST['method'];
			if($method=='email')
			{
				$db->query("START TRANSACTION");
				$key=substr(md5($user_info['id'].$user_info['name'].$user_info['reg_email'].time().rand(0,1000000)),8);
				$proto='http';
				if($CONFIG['site']['force_https_login'] || $CONFIG['site']['force_https'])	$proto='https';

				$res=$db->query("UPDATE `users` SET `pass_change`='$key' WHERE `id`='{$user_info['id']}'");
				$msg="Поступил запрос на смену пароля доступа к сайту {$CONFIG['site']['name']} для аккаунта {$user_info['name']}.
				Если Вы действительно хотите сменить пароль, перейдите по ссылке $proto://{$CONFIG['site']['name']}/login.php?mode=remn&login={$user_info['name']}&s=$key

				----------------------------------------
				Сообщение сгенерировано автоматически, отвечать на него не нужно!";
				mailto($user_info['reg_email'],"Восстановление забытого пароля",$msg);
				$tmpl->msg("Код для смены пароля выслан Вам по электронной почте. Проверьте почтовый ящик.","ok");
				$db->query("COMMIT");
			}
			else if($method=='sms')
			{
				require_once('include/sendsms.php');
				$db->query("START TRANSACTION");
				$key=rand(100000,99999999);
				$res=$db->query("UPDATE `users` SET `pass_change`='$key' WHERE `id`='{$user_info['id']}'");

				$sender=new SMSSender();
				$sender->setNumber($user_info['reg_phone']);
				$sender->setContent("Ваш код: $key\n{$CONFIG['site']['name']}");
				$sender->send();
				$res->query("COMMIT");
				$tmpl->msg("Код для смены пароля выслан Вам по SMS","ok");
			}
			else if(@$CONFIG['site']['allow_openid'])
			{
				header("Location: /login_oid.php?oid=$method");
				exit();
			}
			else throw new Exception("Метод не реализован или не доступен");

			if($method!='openid')
			{
				$tmpl->addContent("<h1 id='page-title'>Восстановление доступа - шаг 3</h1>
				<form method='post'>
				<input type='hidden' name='mode' value='remn'>
				<input type='hidden' name='login' value='$login'>
				Введите полученный код:<br>
				<input type='text' name='s'><br>
				<br><button type='submit'>Далее</button>
				</form>");
			}
		}

	}
}
else if($mode=='remn')
{
	$sql_key=$db->real_escape_string(@$_REQUEST['s']);
	$sql_login=$db->real_escape_string(@$_REQUEST['login']);
	$res=$db->query("SELECT `id`, `name` FROM `users` WHERE `pass_change`='$sql_key' AND `name`='$sql_login'");
	if($nxt=$res->fetch_row())
	{
		$pass=keygen_unique(0,8,11);

		if(@$CONFIG['site']['pass_type']=='MD5')
		{
			$sql_pass_hash=MD5($pass);
			$sql_pass_type='MD5';
		}
		else if(@$CONFIG['site']['pass_type']=='SHA1')
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

		$res=$db->query("UPDATE `users` SET `pass`='$sql_pass_hash', `pass_type`='$sql_pass_type', `pass_change`='', `pass_date_change`=NOW() WHERE `id`='$nxt[0]'");
		$_SESSION['uid']=$nxt[0];
		$_SESSION['name']=$nxt[1];
		$tmpl->addContent("<h1>Завершение смены пароля</h1>
		$nxt[1], ваш новый пароль:<br>
		$pass<br>Не забудьте его!");
	}
	else
	{
		$res=$db->query("UPDATE `users` SET `pass_change`='' WHERE `login`='$sql_login'");
		$tmpl->msg("Код неверен или устарел","err");
	}
}
else if($mode=='unsubscribe')
{
	$tmpl->setContent("<h1 id='page-title'>Отказ от рассылки</h1>");
	$email=$db->real_escape_string($_REQUEST['email']);
	$c=0;
	$res=$db->query("UPDATE `users` SET `reg_email_subscribe`='0' WHERE `reg_email`='$email'");
	if($db->affected_rows())
	{
		$tmpl->msg("Вы успешно отказались от автоматической рассылки!","ok");
		$c=1;
	}

	$res=$db->query("UPDATE `doc_agent` SET `no_mail`='1' WHERE `email`='$email'");
	if($db->affected_rows)
	{
		$tmpl->msg("В нашей клиентской базе Ваш адрес помечен, как нежелательный для рассылки.","ok");
		$c=1;
	}

	if(!$c)	$tmpl->msg("Ваш адрес не найден в наших базах рассылки! Возможно, Вы отказались от рассылки ранее, или не являетесь нашим зарегистрированным пользователем. За разяснением обратитесь по телефону или e-mail, указанному на странице <a class='wiki' href='/article/ContactInfo'>Контакты</a>, либо в письме, полученном от нас. Спасибо за понимание!","notify");
}
else $tmpl->logger("Uncorrect mode!");

}
catch(mysqli_sql_exception $e)
{
	$id = $tmpl->logger($e->getMessage(), 1);
	$tmpl->msg("Ошибка при регистрации. Порядковый номер - $id<br>Сообщение передано администратору",'err',"Ошибка при регистрации");
	mailto($CONFIG['site']['admin_email'],"ВАЖНО! Ошибка регистрации на ".$CONFIG['site']['name'].". номер в журнале - $id", $e->getMessage());
	$db->rollback();
}
catch(Exception $e)
{
	$db->query("ROLLBACK");
	$tmpl->msg($e->getMessage(),"err","Ошибка при регистрации");
}

$tmpl->write();

?>