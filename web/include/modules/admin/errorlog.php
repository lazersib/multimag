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

namespace modules\admin;

/// Журнал ошибок
class errorLog extends \IModule {

    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'admin.errorlog';
    }

    public function getName() {
        return 'Журнал ошибок';
    }
    
    public function getDescription() {
        return 'Модуль для просмотра журнала ошибок сайта.';  
    }
    

    protected function viewLog() {
        global $tmpl, $db;
        $p = rcvint('p', 1);
        if ($p <= 0) {
            $p = 1;
        }
        $lines = 250;
        $from=($p-1)*$lines;
        $res = $db->query("SELECT SQL_CALC_FOUND_ROWS `id`, `class`, `page`, `referer`, `code`, `msg`, `file`, `line`, `date`, `ip`, `useragent`, `uid` "
            . "FROM `errorlog` "
            . "ORDER BY `id` DESC LIMIT $from, $lines");
        $fr = $db->query('SELECT FOUND_ROWS()');
        list($total) = $fr->fetch_row();
        $tmpl->addContent("<table width='100%' class='list'>
        <tr><th>Дата</th><th>Класс</th><th>Код</th><th>Ошибка</th><th>Файл:строка</th><th>Страница</th><th>ID</th></tr>");
        $i=0;
        while($line = $res->fetch_assoc()) {
            $line['date'] = str_replace(' ', '&nbsp', html_out($line['date']));
            $tmpl->addContent('<tr>'
            . '<td>'.$line['date'].'</td>'
            . '<td>'.html_out($line['class']).'</td>'
            . '<td>'.$line['code'].'</td>'
            . '<td>'.html_out($line['msg']).'</td>'
            . '<td>'.html_out(basename($line['file'])).':'.$line['line'].'</td>'
            . '<td>'.html_out($line['page']).'</td>'
            . '<td><a href="'.$this->link_prefix.'&amp;sect=detail&amp;id='.$line['id'].'">'.$line['id'].'</a></td>'
            . '</tr>');
        }
        $tmpl->addContent('</table>');

        $pages_count = ceil($total/$lines);
        if ($pages_count > 1) {
            $tmpl->addContent('<p>Страницы: ');
            for ($i = 1; $i <= $pages_count; ++$i) {
                if ($i == $p) {
                    $tmpl->addContent("<b>$i</b> ");
                } else {
                    $tmpl->addContent("<a href='{$this->link_prefix}&amp;p=$i'>$i</a> ");
                }
            }
            $tmpl->addContent("</p>");
        }
    }

    protected function viewDetail($id) {
        global $tmpl, $db, $CONFIG;
        $tmpl->setContent("<h1>Детализация ошибки $id</h1>");
        $tmpl->addBreadcrumb('Детализация ошибки '.$id, '');
        $line = $db->selectRow('errorlog', $id);
        $line['trace'] = str_replace("\n", '</li><li>', html_out($line['trace']));
        $pref_len = strlen($CONFIG['location']);
        $fname = substr($line['file'], $pref_len);
        $link = 'http://multimag.tndproject.org/browser/trunk'.$fname.'?rev='.MULTIMAG_REV.'#L'.$line['line'];
        $tmpl->addContent("<ui class='items'>"
            . "<li>id: {$line['id']}</li>"
            . "<li>Сообщение: ".html_out($line['msg'])."</li>"
            . "<li>Класс: ".html_out($line['class'])."</li>"
            . "<li>Кoд: ".html_out($line['code'])."</li>"
            . "<li>Файл: <a href='$link'>".html_out($line['file'])."</a></li>"
            . "<li>Строка: ".html_out($line['line'])."</li>"
            . "<li>Страница: ".html_out($line['page'])."</li>"
            . "<li>Ссылка: ".html_out($line['referer'])."</li>"
            . "<li>Дата: ".html_out($line['date'])."</li>"
            . "<li>IP: ".html_out($line['ip'])."</li>"
            . "<li>Броузер: ".html_out($line['useragent'])."</li>"
            . "<li>ID пользователя: ".html_out($line['uid'])."</li>"
            . "<li>Стек:<ul><li>".$line['trace']."</li></ul></li>"
            . "</ul>");
    }
    
    public function run() {
        global $tmpl;
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect');
        switch ($sect) {
            case '':
                $tmpl->addBreadcrumb($this->getName(), '');
                $tmpl->addContent("<p>".$this->getDescription()."</p>");
                $this->viewLog();
                break;
            case 'detail':
                $id = rcvint('id');
                $this->viewDetail($id);
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

}
