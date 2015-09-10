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
namespace Actions;

/// Очистка от старых данных в кеше
class clearCache extends \Action {

    /// @brief Запустить
    public function run() {
        $cache_dir = $this->config['site']['location'].'/share/var/cache/';
        if(isset($this->config['site']['imcache_expire'])) {
            $expire = $this->config['site']['imcache_expire'];
        } else {
            $expire = 60*60*24*15;
        }
        $lasttime = time() - $expire;
        $s_dir = opendir($cache_dir);
        while (($dir = readdir($s_dir)) !== false) {
            if($dir=='.' || $dir == '..') {
                continue;
            }
            echo "dir: $dir : filetype: " . filetype($cache_dir . $dir) . "\n";
            $o_dir = opendir($cache_dir.$dir);
            while (($file = readdir($o_dir)) !== false) {        
                if($file=='.' || $file == '..') {
                    continue;
                }
                $mtime = filemtime($cache_dir.$dir.'/'.$file);
                if($mtime<$lasttime) {
                    unlink($cache_dir.$dir.'/'.$file);
                    echo "unlink: $file\n";
                }
                
            }
            closedir($o_dir);            
        }
        closedir($s_dir);
        exit();
    }

}
