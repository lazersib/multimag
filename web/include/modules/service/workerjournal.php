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

namespace modules\service;

/// Рабочий журнал сотрудника
class workerjournal extends \IModule {
    
    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'service.orders';
    }

    public function getName() {
        return 'Рабочий журнал сотрудника';
    }
    
    public function getDescription() {
        return 'Рабочий журнал сотрудника';  
    }
    
    /// Просмотреть текущие задачи кладовщика
    public function viewMySKOrders() {
        global $db, $tmpl;
        $doc_types = \document::getListTypes();
        $sql = "SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`,  `doc_list`.`user`, `doc_agent`.`name` AS `agent_name`,
                `doc_list`.`sum`, `users`.`name` AS `user_name`, `doc_types`.`name`, `doc_list`.`p_doc`, `dop_status`.`value` AS `status`, `doc_list`.`firm_id`, `doc_list`.`type`,
                `dop_sk`.`value` AS `sk_id`
        FROM `doc_list`
        LEFT JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
        LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
        LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
        LEFT JOIN `doc_dopdata` AS `dop_status` ON `dop_status`.`doc`=`doc_list`.`id` AND `dop_status`.`param`='status'
        LEFT JOIN `doc_dopdata` AS `dop_sk` ON `dop_sk`.`doc`=`doc_list`.`id` AND `dop_sk`.`param`='kladovshik'
        WHERE (`doc_list`.`type`=2 OR `doc_list`.`type`=20) AND `doc_list`.`mark_del`=0 AND `doc_list`.`ok`=0 AND `dop_status`.`value`='in_process'
        ORDER by `doc_list`.`date` ASC";

        $res = $db->query($sql);

        $tmpl->addContent("<table width='100%' cellspacing='1' class='list'><tr>
        <th width='70'>№</th><th width='55'>ID</th><th width='55'>Счет</th><th>Агент</th><th width='90'>Сумма</th><th width='150'>Дата</th><th>Автор</th></tr>");
        while ($line = $res->fetch_assoc()) {
            /// Если уже взял заявку - права не проверяем - должен видеть в любом случае
            $date = date('Y-m-d H:i:s', $line['date']);
            $link = "/doc.php?mode=body&amp;doc=" . $line['id'];
            if ($line['p_doc'])
                    $z = "<a href='/doc.php?mode=body&amp;doc={$line['p_doc']}'>{$line['p_doc']}</a>";
            else
                    $z = '--нет--';
            $tmpl->addContent("<tr><td align='right'><a href='$link'>{$line['altnum']}{$line['subtype']}</a></td><td><a href='$link'>{$line['id']}</a></td>
            <td>$z</td><td>{$line['agent_name']}</td><td align='right'>{$line['sum']}</td>
            <td>$date</td><td><a href='/adm.php?mode=users&amp;sect=view&amp;user_id={$line['user']}'>{$line['user_name']}</a></td>
            </tr>");
        }
        $tmpl->addContent("</table>");
        $tmpl->msg("В списке отображаются реализации со статусом &quot;в процессе сборки&quot;");
    }
    
    public function run() {
        global $tmpl, $db;
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect');
        switch ($sect) {
            case '':
                $tmpl->addBreadcrumb($this->getName(), '');
                $this->viewImagesList();
                break;
            case 'edit':
                $image_id = rcvint('img');
                $this->viewImageEdit($image_id);
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

}
