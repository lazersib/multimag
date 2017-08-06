<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2017, BlackLight, TND Team, http://tndproject.org
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

// Документация: http://multimag.tndproject.org/wiki/userdoc/json_api

include_once("core.php");

try {
    $object = request('object');
    $action = request('action');
    $data = request('data');    
    $tmpl->ajax = 1;

    if (!auth()) {
        throw new \LoginException('Не аутентифицирован');
    }
    session_write_close();  /// Чтобы не тормозить загрузку других страниц
    ob_start();
    $starttime = microtime(true);    
    $result = array(
        'object' => $object,
        'action' => $action,
        'response' => 'success',
    );
    if(!preg_match('/^\\w+$/', $object)) {
        throw new \InvalidArgumentException('Некорректный объект '.$object);
    }
    $class_name = '\\api\\' . $object;
    if(!class_exists($class_name)) {
        throw new \NotFoundException('Отсутствует обработчик для '.$object);
    }
    $disp = new $class_name;
    $decoded_data = json_decode($data, true);
    $db->startTransaction();
    $result['content'] = $disp->dispatch($action, $decoded_data);  
    $db->commit();
    if($disp->send_file) {
        ob_end_flush();
    }
    else {
        $exec_time = round(microtime(true) - $starttime, 3);
        $result["exec_time"] = $exec_time;
        $result["user_id"] = $_SESSION['uid'];
        ob_end_clean();
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
} catch (LoginException $e) {
    ob_end_clean();
    $result = array(
        'object'=>$object,
        'action' => $action,
        'response' => 'error',
        'errortype' => 'LoginException',
        'errorcode' => $e->getCode(),
        'errormessage' => $e->getMessage()
    );
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (AccessException $e) {
    ob_end_clean();
    $result = array(
        'object'=>$object,
        'action' => $action,
        'response' => 'error',
        'errortype' => 'AccessException',
        'errorcode' => $e->getCode(),
        'errormessage' => $e->getMessage()
    );
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} 
catch (mysqli_sql_exception $e) {
    writeLogException($e);
    ob_end_clean();
    $result = array(
        'object'=>$object,
        'action' => $action,
        'response' => 'error',
        'errortype' => 'DbException',
        'errorcode' => $e->getCode(),
        'errormessage' => 'Ошибка в базе данных: ' . $e->getMessage()
    );
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    ob_end_clean();
    writeLogException($e);
    $result = array(
        'object'=>$object,
        'action' => $action,
        'response' => 'error',
        'errortype' => 'InvalidArgumentException',
        'errorcode' => $e->getCode(),
        'errormessage' => 'Некорректные данные в запросе: ' . $e->getMessage()
    );
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    ob_end_clean();
    writeLogException($e);
    $result = array(
        'object'=>$object,
        'action' => $action,
        'response' => 'error',
        'errortype' => get_class($e),
        'errorcode' => $e->getCode(),
        'errormessage' => $e->getMessage()
    );
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} 

