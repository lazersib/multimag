<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
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
    $tmpl->ajax = 1;

    if (!auth()) {
        throw new AccessException('Не аутентифицирован');
    }
    session_write_close();  /// Чтобы не тормозить загрузку других страниц
    ob_start();
    $starttime = microtime(true);    
    $result = array(
        'object' => $object,
        'action' => $action,
        'response' => 'success',
    );
    if($object=='getmessage') {
        ignore_user_abort(FALSE);
        sleep(10);
        $agent_id = rand(1, 100);
        $content = array(
            'title' => 'Уведомление о звонке',
            'icon' => '/img/i_add.png',
            'message' => 'Лови звонок агента '.$agent_id,
            'link' => '/docs.php?l=agent&mode=srv&opt=ep&pos='.$agent_id,
        );
        $result['content'] = $content;
    } 
    else if($object=='agent') {
        $data = request('data');
        $decoded_data = json_decode($data, true);
        $disp = new \api\agent();
        $result['content'] = $disp->dispatch($action, $decoded_data);        
    }
    else if($object=='document') {
        $data = request('data');
        $decoded_data = json_decode($data, true);
        $disp = new \api\document();
        $result['content'] = $disp->dispatch($action, $decoded_data);        
    }
    else {
        throw new NotFoundException('Неверный объект');
    }
    $exec_time = round(microtime(true) - $starttime, 3);
    $result["exec_time"] = $exec_time;
    $result["user_id"] = $_SESSION['uid'];
    ob_end_clean();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (AccessException $e) {
    ob_end_clean();
    $result = array('object'=>$object, 'response' => 'error', 'errormessage' => 'Нет доступа: ' . $e->getMessage());
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (mysqli_sql_exception $e) {
    writeLogException($e);
    ob_end_clean();
    $result = array('object'=>$object, 'response' => 'error', 'errormessage' => 'Ошибка в базе данных: ' . $e->getMessage());
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    ob_end_clean();
    writeLogException($e);
    $result = array('object'=>$object, 'response' => 'error', 'errormessage' => 'Некорректные данные в запросе: ' . $e->getMessage());
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    ob_end_clean();
    writeLogException($e);
    $result = array('object'=>$object, 'response' => 'error', 'errormessage' => $e->getMessage());
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} 

