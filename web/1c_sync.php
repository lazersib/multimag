<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2015, BlackLight, TND Team, http://tndproject.org
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
include_once("include/doc.core.php");

class NotAuthException extends Exception {
    
}

// Проверка необходимости перехода на https
if(!@$_SERVER['HTTPS'] && (@$CONFIG['site']['force_https'] || @$CONFIG['site']['force_https_login'])) {
    redirect('https://' . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI']);
}

try {
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        throw new \NotAuthException("Authentification cancel by user / Аутентификация отменена пользователем.");
    }
    
    $auth = new \authenticator();
    $ip = getenv("REMOTE_ADDR");
    $at = $auth->attackTest($ip);
    if($at == 'ban_net') {            
        $db->insertA("users_bad_auth", array('ip' => $ip, 'time' => time() + 60) );
        throw new \Exception("Из-за попыток перебора паролей к сайту доступ с вашей подсети заблокирован! Вы сможете авторизоваться через несколько часов после прекращения попыток перебора пароля. Если Вы не предпринимали попыток перебора пароля, обратитесь к Вашему поставщику интернет-услуг - возможно, кто-то другой пытается подобрать пароль, используя ваш адрес.");
    }
    if($at == 'ban_ip') {
        $db->insertA("users_bad_auth", array('ip' => $ip, 'time' => time() + 60) );
        throw new \Exception("Из-за попыток перебора паролей к сайту доступ с вашего адреса заблокирован! Вы сможете авторизоваться через несколько часов после прекращения попыток перебора пароля. Если Вы не предпринимали попыток перебора пароля, обратитесь к Вашему поставщику интернет-услуг - возможно, кто-то другой пытается подобрать пароль, используя ваш адрес.");
    }
    
    if(!$auth->loadDataForLogin($_SERVER['PHP_AUTH_USER'])) {  // Не существует
        $db->insertA("users_bad_auth", array('ip' => getenv("REMOTE_ADDR"), 'time' => time()) );
        throw new \NotAuthException("Неверная пара логин / пароль. Попробуйте снова.");
    }

    if(!$auth->testPassword($_SERVER['PHP_AUTH_PW'])) {   // Неверный пароль
        $db->insertA("users_bad_auth", array('ip' => getenv("REMOTE_ADDR"), 'time' => time()) );
        throw new \NotAuthException("Неверная пара логин / пароль. Попробуйте снова.");
    }

    if ($auth->isDisabled()) {
        throw new \Exception("Пользователь заблокирован (забанен). Причина блокировки: " . $auth->getDisabledReason() );
    }
        
    $user_info = $auth->getUserInfo();
    $auth->addHistoryLine('1c');
    $_SESSION['uid'] = $user_info['id'];
    $_SESSION['name'] = $user_info['name'];
    
    if(!isAccess('doc_1csync', 'exec', true)) {
        throw new \Exception("Отсутствуют необходимые привилегии" );
    }
    
    $partial_time = rcvint('partial_time', 0);              // Если задано, то передаёт только изменения, произошедшие после этой даты
    $start_date = rcvdate('start_date', "1970-01-01");      // Только для полной синхронизации. Начало интервала.    
    $end_date = rcvdate('end_date', date("Y-m-d"));         // Только для полной синхронизации. Конец интервала.
    $mode = request('mode');
    
    if($mode == 'export') {
        $db->startTransaction();    
        $export = new \sync\Xml1cDataExport($db);
        $export->setRefbooksList( request('refbooks', null) );
        $export->setDocTypesList( request('doctypes', null) );
        $export->setPartialTimeshtamp($partial_time);
        $export->setPeriod($start_date, $end_date);
        $data = $export->getData();    
        header("Content-type: application/xml");
        echo $data; 
    } else if($mode=='import') {
        $import = new \sync\simplexml1cdataimport($db);
        /*
        if( isset($_POST['xmlstring']) ) {
            $xmlstring = $_POST['xmlstring'];
            $import->loadFromString($_POST['xmlstring']);
        } elseif (is_uploaded_file(@$_FILES['xmlfile']['tmp_name'])) {
            $import->loadFromFile(@$_FILES['xmlfile']['tmp_name']);
        } else {
            throw new Exception('Данные не получены.');
        }
         */
        $import->loadFromFile('1c.xml');
        $db->startTransaction(); 
        $data = $import->importData();
        header("Content-type: application/xml");
        echo $data;
        //$db->commit();
        
    } else {
        throw new NotFoundException('Неверный параметр');
    }
     
    
} 
catch (NotAuthException $e) {
    header('WWW-Authenticate: Basic realm="' . @$CONFIG['site']['name'] . '"');
    header('HTTP/1.0 401 Unauthorized');
    echo $e->getMessage();
}
catch (Exception $e) {
    $dom = new domDocument("1.0", "utf-8");
    $root = $dom->createElement("multimag_exchange"); // Создаём корневой элемент
    $root->setAttribute('version', '1.0');
    $dom->appendChild($root);
    
    $result = $dom->createElement('result');            // Код возврата
    $result_code = $dom->createElement('status', 'err');
    $result_desc = $dom->createElement('message', $e->getMessage());
    $result->appendChild($result_code);
    $result->appendChild($result_desc);
    $root->appendChild($result);
    
    header("Content-type: application/xml");
    echo $dom->saveXML(); 
}

unset($_SESSION['uid']);
