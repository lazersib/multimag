<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2010, BlackLight, TND Team, http://tndproject.org
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


// Невыполненные заявки
include_once("core.php");
include_once("include/doc.core.php");
need_auth();
if(!isAccess('doc_list','view'))	throw new AccessException("");

SafeLoadTemplate($CONFIG['site']['inner_skin']);
$tmpl->HideBlock('left');

$tmpl->SetTitle("Невыполненные заявки");
doc_menu("");
$tmpl->AddText("<h1 id='page-title'>Невыполненные заявки</h1><div id='page-info'>...........</div>");
$tmpl->msg("Модуль находится в стадии тестирования и анализа удобства. Это значит, что возможности, предоставляемые этим модулем, могут измениться без предупреждения. Вы можете повлиять на развиие этого модуля, оставив пожелания <a href='/user.php?mode=frequest'>здесь</a>.");

$sql="SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`,  `doc_list`.`user`, `doc_agent`.`name` AS `agent_name`, `doc_list`.`sum`, `users`.`name` AS `user_name`, `doc_types`.`name`, `doc_list`.`p_doc`, `dop_delivery`.`value` AS `delivery`, `dop_delivery_date`.`value` AS `delivery_date`, `dop_status`.`value` AS `status`
FROM `doc_list`
LEFT JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
LEFT JOIN `doc_dopdata` AS `dop_delivery` ON `dop_delivery`.`doc`=`doc_list`.`id` AND `dop_delivery`.`param`='delivery'
LEFT JOIN `doc_dopdata` AS `dop_delivery_date` ON `dop_delivery_date`.`doc`=`doc_list`.`id` AND `dop_delivery_date`.`param`='delivery_date'
LEFT JOIN `doc_dopdata` AS `dop_status` ON `dop_status`.`doc`=`doc_list`.`id` AND `dop_status`.`param`='status'
WHERE `doc_list`.`type`=3 AND `doc_list`.`mark_del`=0
ORDER by `doc_list`.`date` DESC";
// `doc_list`.`ok`!=0 ?
$res=mysql_query($sql);
if(mysql_errno())	throw new MysqlException("Не удалось получить список документов!");
$row=mysql_num_rows($res);

$i=0;
$pr=$ras=0;
$tpr=$tras=0;

$tmpl->AddText("<table width='100%' cellspacing='1' id='doc_list' class='list'>
<tr>
<th width='70'>№</th><th width='50'>ID</th><th>Статус</th><th>Агент</th><th>Сумма</th><th>Доставка</th><th>Дата</th><th>Автор</th>
</tr>");
while($line=mysql_fetch_assoc($res))
{
	if($line['status']=='ok' || $line['status']=='err')	continue;
	$status='';
	switch($line['status'])
	{
		case 'inproc':
			$status='В процессе';
			break;
		case 'ready':
			$status='Готов';
			break;
		default:
			$status='Новый';
	}
	// Новый
	// Ошибочный <-не отображается
	// В обработке
	// Готов
	// Отдан <-не отображается
	$date=date('Y-m-d H:i:s',$line['date']);
	$delivery=$line['delivery']?('Да, '.$line['delivery_date']):'Не требуется';
	$link="/doc.php?mode=body&amp;doc=".$line['id'];
	$tmpl->AddText("<tr><td align='right'><a href='$link'>{$line['altnum']}{$line['subtype']}</a></td><td><a href='$link'>{$line['id']}</a></td>
	<td>$status</td><td>{$line['agent_name']}</td><td align='right'>{$line['sum']}</td>
	<td>$delivery</td>
	<td>$date</td><td>{$line['user_name']}</td>
	</tr>");


}
$tmpl->AddText("</table>");




$tmpl->write();


?>
