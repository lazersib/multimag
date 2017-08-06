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

namespace modules\admin;

/// Журнал обращений/посещений
class browserLog extends \IModule {

    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'admin.browserlog';
    }

    public function getName() {
        return 'Статистика по броузерам';
    }
    
    public function getDescription() {
        return 'Модуль для просмотра статистики посещений сайта.';  
    }
    

    protected function viewLog() {
        global $db, $tmpl;
        $starttime = time() - 60 * 60 * 24 * 30;
        $res = $db->query("SELECT `id`, `ip`, `agent`, `refer` FROM `counter` WHERE `date`>'$starttime' GROUP by `ip`");
        $max = 0;
        $sum = 0;
        $browsers = array();
        $tmpl->setTitle("Статистика по броузерам");
        $tmpl->addContent("<h1>Статистика по броузерам</h1>");
        $others = array();
        $useragents = array(
            'spam,viruses,scanners' => ["Toata dragostea", "NetcraftSurveyAgent", "Scanner", "SurveyBot"],
            'Opera mobile' => ["Opera mobi"],
            'Opera' => "Opera",
            'Mozilla'=> ["firefox", "Iceweasel"],
            'wget'=> "wget",
            'avant'=> "Avant",
            'Internet Explorer'=> "MSIE",
            'BOT: yandex' => "Yandex",
            'BOT: msnbot'=> "msnbot",
            'BOT: googlebot'=> "googlebot",
            'BOT: yahoo'=> "Yahoo",
            'BOT: Baidu.jp'=> "Baiduspider",
            'BOT: other'=> ["Bot","Spider"],
            'Web Archive'=> "web.archive",
            'konqueror'=> "Konqueror",
            'Google Chrome'=> "Chrome",
            'mail_ru browser' => "Mail.Ru",            
        );

        while ($line = $res->fetch_assoc()) {
            $browser = 'other';
            foreach($useragents as $agent => $list) {
                if(is_array($list)) {
                    foreach($list as $b) {
                        if (stripos($line['agent'], $b)!==false) {
                            $browser = $agent;
                            break;
                        }
                    }
                } else {
                    if (stripos($line['agent'], $list)!==false) {
                        $browser = $agent;
                    }
                }
                if($browser!='other') {
                    break;
                }
            }
            if(isset($browsers[$browser])) {
                $browsers[$browser] ++;
            } else {
                $browsers[$browser] = 1;
            }

            if ($max < $browsers[$browser]) {
                $max = $browsers[$browser];
            }
            $sum++;
        }
        $coeff = 100 / $max;
        $coeff_p = 100 / $sum;
        ksort($browsers);
        foreach ($browsers as $cur => $cnt) {
            $ln = $cnt * $coeff * 10;
            $pp = $coeff_p * $cnt * 100;
            settype($pp, "int");
            $pp/=100;
            settype($ln, "int");
            $color = rand(0, 9) . rand(0, 9) . rand(0, 9);
            $tmpl->addContent("$cur - $pp% <div style='width: $ln" . "px; height: 10px; background-color: #$color; color: #ccc'></div><br>");
        }
        $tmpl->addContent("<hr>");
        foreach ($others as $cur => $cnt) {
            $tmpl->addContent("$cur - $cnt<br>");
        }
    }

    public function run() {
        global $CONFIG, $tmpl, $db;
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect');
        switch ($sect) {
            case '':
                $tmpl->addBreadcrumb($this->getName(), '');
                $tmpl->addContent("<p>".$this->getDescription()."</p>");
                $this->viewLog();
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

}
