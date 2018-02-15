<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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
namespace Actions;

/// Управление записями телефонных разговоров
class CallsRecordsManager extends \Action {

    /// Конструктор
    public function __construct($config, $db) {
        parent::__construct($config, $db);
        $this->interval = self::DAILY;
    }

    /// Получить название действия
    public function getName() {
        return "Управление записями телефонных разговоров";
    }

    /// Проверить, разрешен ли периодический запуск действия
    public function isEnabled() {
        return  \cfg::get('auto', 'calls_transcode') ||
                \cfg::get('auto', 'calls_delete');
    }
    
    /// Перекодировать разговоры
    protected function transcode() {
        $dir = \cfg::get('service_cdr', 'file_path', '/var/spool/asterisk/monitor');
        $days_notr = \cfg::get('service_cdr', 'no_transcode_period', 30);
        $bitrate = \cfg::get('service_cdr', 'transcode_bitrate', 8);
        $format = \cfg::get('service_cdr', 'transcode_format', 'opus');
        $limit = \cfg::get('service_cdr', 'transcode_limit', 500);

        $files = scandir($dir);
        $count = 0;
        $time_first = time()-60*60*24*$days_notr;
        foreach($files as $fname) {
            $fullname = $dir.'/'.$fname;
            if($count>$limit) break;  // Защита
            $mtime = filemtime($fullname);
            if($mtime>$time_first)   continue;
            $finfo = pathinfo($fullname);
            if($finfo['extension']!='wav')  continue;

            $count++;            
            $from_size = filesize($fullname);
            if($from_size>100) {
                $to = $dir.'/'.$finfo['filename'].'.opus';
                `opusenc --bitrate $bitrate --framesize 60 --downmix-mono --discard-comments --quiet $fullname $to`;
                touch($to, $mtime);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       
            }                                                                                                                                                                                                                                                                          
            unlink($fullname);                                                                                                                                                                                                                                                             
        }   
    }

    /// @brief Запустить
    public function run() {
        // Перекодирование записей
        if (\cfg::get('auto', 'calls_transcode')) {
            $this->transcode();
        }
    }

}
