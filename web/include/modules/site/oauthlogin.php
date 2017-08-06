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

namespace Modules\Site;

/// Класс модуля регистрации и аутентификации
class oauthLogin extends \IModule {

    public function __construct() {
        parent::__construct();
        $this->link_prefix = '/oauth';
    }
    
    // Получить название модуля
    /// @return Строка с именем
    public function getName() {
        return 'Регистрация и аутентификация при помощи OAuth';
    }
    
    /// Получить описание модуля
    /// @return Строка с описанием
    public function getDescription() {
        return 'Регистрация и аутентификация при помощи OAuth';  
    }
    
    /// Запустить модуль на исполнение
    public function run() {
        global $tmpl, $db;
        $server = '';
        $tmpl->setTitle("OAuth");
        $arr = explode( '/' , $_SERVER['REQUEST_URI'] );
        if(is_array($arr)) {
            if (count($arr) > 2) {
                $server = $arr[2];
            }
        }

        if($server == '') {
            if(@$_SESSION['uid']) {
                $tmpl->setContent("<h1>Прекрепить профиль</h1>");
                $tmpl->setTitle("Прекрепить профиль");
            } else {
                $tmpl->setContent("<h1>Аутентификация</h1>");
                $tmpl->setTitle("Аутентификация");
            }
            $tmpl->addContent( $this->getLoginForm() );
            if(@$_SESSION['uid']) {
                $tmpl->msg("Прикрепление профиля позволит Вам входить на этот сайт, не вводя учётных данных.");
            }
        } elseif(!preg_match('/^\w{2,16}$/i', $server)) {
            throw new \NotFoundException("Запрашиваемое подключение не найдено");
        } else {
            $class_name = '\\oauth\\plugins\\'. $server;
            if(!class_exists($class_name, true)) {
                throw new \NotFoundException("Запрашиваемое подключение не найдено");
            }
            $plugin = new $class_name;
            $plugin->init();
            $plugin->auth();
            if(@$_SESSION['uid']) {
                $ret = $plugin->tryConnect();
                $tmpl->msg($ret, 'info');
            } else {
                $plugin->tryLogin();
            }
        }
    }    

    /// Сформировать HTML код формы аутентификации
    public function getLoginForm($name = 'Войти через') {
        global $CONFIG, $db;

        $dir = $CONFIG['site']['location'] . '/include/oauth/plugins/';
        $plugnis = array();
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                $plugnis = array();
                $servers = array();
                if(@$_SESSION['uid']) {
                    $name = '';
                    $user_id = intval($_SESSION['uid']);
                    $res = $db->query("SELECT `server` FROM `users_oauth` WHERE `user_id`='$user_id'");
                    while($line = $res->fetch_assoc()) {
                        $servers[] = $line['server'];
                    }
                    
                }                
                while (($file = readdir($dh)) !== false) {
                    if (preg_match('/.php$/', $file)) {
                        $cn = explode('.', $file);
                        if(in_array($cn[0], $servers)) {
                            continue;
                        }
                        include_once("$dir/$file");
                        $class_name = '\\oauth\\plugins\\' . $cn[0];
                        $class = new $class_name;
                        if(!$class->isConfigured()) {
                            continue;
                        }
                        $nm = $class->getName();
                        $plugnis[$cn[0]] = $nm;
                    }
                }
                closedir($dh);
                if (count($plugnis) == 0) {
                    return '';
                }
                asort($plugnis);   
                
                $colspan = 4;
                for ($i = 6; $i > 1; $i--) {
                    if (count($plugnis) % $i == 0) {
                        $colspan = $i;
                        break;
                    }
                }
                $ret = "<table width='900px'><tr><th colspan='$colspan'><center>$name</center></th></tr>";
                $col = 0;
                foreach ($plugnis AS $id => $name) {
                    if ($col == 0) {
                        $ret .= '<tr>';
                    }
                    $ret .= "<td><a href='/oauth.php/" . html_out($id) . "/'>"
                        . "<img src='/img/oauth/" . html_out($id) . ".png' alt='" . html_out($name) . "'></a></td>";
                    $col++;
                    if ($col >= $colspan) {
                        $col = 0;
                        $ret .= '</tr>';
                    }
                }
                $ret .= '</table>';
                return $ret;
            }
        }
        return '';
    }

}
