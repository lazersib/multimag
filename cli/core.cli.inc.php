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

include_once($CONFIG['location']."/common/core.common.php");

/// Пустой шаблон для подавления предупреждений
class ntpl {}
$tmpl = new ntpl();


$db = new MysqiExtended(\cfg::get('mysql', 'host'), \cfg::get('mysql', 'login'), \cfg::get('mysql', 'pass'), \cfg::get('mysql', 'db'));

if ($db->connect_error) {
    die("Ошибка соединения с базой данных");
}

// Включаем автоматическую генерацию исключений для mysql
mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);

if (!$db->set_charset("utf8")) {
    die("Невозможно задать кодировку соединения с базой данных: " . $db->error);
}

function run_periodically_actions($interval) {
    global $executed, $verbose;
    $verbose = 0;

    try {
        if ($_SERVER['argc'] > 1) {
            for ($i = 0; $i < $_SERVER['argc']; $i++) {
                switch ($_SERVER['argv'][$i]) {
                    case '-v':
                    case '--verbose':
                        $verbose = 1;
                        break;
                }
            }
        }

        $executed = array();

        function excecute_action($action_name, $interval) {
            global $executed, $CONFIG, $db, $verbose;
            if(in_array($action_name, $executed)) { 
                return true;
            }
            $class_name = 'Actions\\' . $action_name;
            try {
                $action = new $class_name($CONFIG, $db);
                if($action->getInterval()!=$interval) {
                    return false;
                }
                $nm = $action->getName();
                if($verbose) {
                    echo $nm . '...';
                }                
                if(!$action->isEnabled()) {
                    if($verbose) {
                        echo " ОТКЛЮЧЕНО.\n";
                    }
                    return false;
                }    
                foreach ($action->getDepends() as $dep_name) {
                    $dep_name = strtolower($dep_name);
                    if(!excecute_action($dep_name, $interval)) {
                        return false;
                    }
                }
                
                $action->setVerbose();
                $action->run();
                $executed[] = $action_name;
                if($verbose) {
                    echo " Выполнено.\n";
                }
            }
            catch (\XMPPHP\Exception $e) {
                if (\cfg::get('site', 'admin_email')) {
                    mailto(\cfg::get('site', 'admin_email'), "XMPP exception in daily.php", $e->getMessage());
                }
                echo "XMPP exception: " . $e->getMessage() . "\n";
            } 
            catch (mysqli_sql_exception $e) {
                if (\cfg::get('site', 'admin_email')) {
                    mailto(\cfg::get('site', 'admin_email'), "Mysql exception in daily.php", $e->getMessage());
                }
                echo "Mysql exception: " . $e->getMessage() . "\n";
            }
            catch (Exception $e) {
                if (\cfg::get('site', 'admin_email')) {
                    mailto(\cfg::get('site', 'admin_email'), "General exception in daily.php", $e->getMessage());
                }
                echo "General exception: " . $e->getMessage() . "\n";
            }    
            return true;
        }

        $dir = \cfg::getroot('location').'/common/actions/';
        if (is_dir($dir)) {
            $dh = opendir($dir);
            if ($dh) {
                while (($file = readdir($dh)) !== false) {
                    if (preg_match('/.php$/', $file)) {
                        $cn = explode('.', $file);             
                        excecute_action($cn[0], $interval);
                    }
                }
                closedir($dh);
            }
        }

    } catch (\XMPPHP\Exception $e) {
        if (\cfg::get('site', 'admin_email')) {
            mailto(\cfg::get('site', 'admin_email'), "Global XMPP exception in daily.php", $e->getMessage());
        }
        echo "Global XMPP exception: " . $e->getMessage() . "\n";
    } catch (mysqli_sql_exception $e) {
        if (\cfg::get('site', 'admin_email')) {
            mailto(\cfg::get('site', 'admin_email'), "Global Mysql exception in daily.php", $e->getMessage());
        }
        echo "Global Mysql exception: " . $e->getMessage() . "\n";
    } catch (Exception $e) {
        if (\cfg::get('site', 'admin_email')) {
            mailto(\cfg::get('site', 'admin_email'), "Global general exception in daily.php", $e->getMessage());
        }
        echo "Global general exception: " . $e->getMessage() . "\n";
    }

}