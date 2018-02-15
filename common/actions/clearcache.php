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

/// Очистка от старых данных в кеше
class clearCache extends \Action {
    
    /// Конструктор
    public function __construct($config, $db) {
        parent::__construct($config, $db);
        $this->interval = self::DAILY;
    }

    /// Получить название действия
    public function getName() {
        return "Очистка кеша изображений";
    }    
    
    /// Проверить, разрешен ли периодический запуск действия
    public function isEnabled() {
        return \cfg::get('auto', 'clear_image_cahe');
    }

    /// @brief Запустить
    public function run() {
        $cache_dir = \cfg::get('site', 'location').'/share/var/cache/';
        $expire = \cfg::get('site', 'imcache_expire', 60*60*24*15);
        $lasttime = time() - $expire;
        $s_dir = opendir($cache_dir);
        while (($dir = readdir($s_dir)) !== false) {
            if($dir=='.' || $dir == '..') {
                continue;
            }
            if(!is_dir($cache_dir . $dir)) {
                continue;
            }
            if($this->verbose) {
                echo "dir: $dir : filetype: " . filetype($cache_dir . $dir) . "\n";
            }
            $o_dir = opendir($cache_dir.$dir);
            while (($file = readdir($o_dir)) !== false) {        
                if($file=='.' || $file == '..') {
                    continue;
                }
                $mtime = filemtime($cache_dir.$dir.'/'.$file);
                if($mtime<$lasttime) {
                    unlink($cache_dir.$dir.'/'.$file);
                    if($this->verbose) {
                        echo "unlink: $file\n";
                    }
                }                
            }
            closedir($o_dir); 
        }
        closedir($s_dir);
    }
}
