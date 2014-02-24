<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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
if(!isAccess('doc_list','view'))	throw new AccessException();

SafeLoadTemplate($CONFIG['site']['inner_skin']);
$tmpl->hideBlock('left');

$tmpl->setTitle("Невыполненные заявки");
doc_menu();

$tmpl->msg("Модуль находится в стадии тестирования и анализа удобства. Это значит, что возможности, предоставляемые этим модулем, могут измениться без предупреждения. Вы можете поучаствовать в развиии этого модуля, оставив пожелания <a href='/user.php?mode=frequest'>здесь</a>.");

$tmpl->addContent("<h1 id='page-title'>Невыполненные заявки</h1><div id='page-info'>...........</div>");

$sql="SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`,  `doc_list`.`user`, `doc_agent`.`name` AS `agent_name`, `doc_list`.`sum`, `users`.`name` AS `user_name`, `doc_types`.`name`, `doc_list`.`p_doc`, `dop_delivery`.`value` AS `delivery`, `dop_delivery_date`.`value` AS `delivery_date`, `dop_status`.`value` AS `status`, `dop_pay`.`value` AS `pay_type`, `doc_ishop`.`value` AS `ishop`
FROM `doc_list`
LEFT JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
LEFT JOIN `doc_dopdata` AS `dop_delivery` ON `dop_delivery`.`doc`=`doc_list`.`id` AND `dop_delivery`.`param`='delivery'
LEFT JOIN `doc_dopdata` AS `dop_delivery_date` ON `dop_delivery_date`.`doc`=`doc_list`.`id` AND `dop_delivery_date`.`param`='delivery_date'
LEFT JOIN `doc_dopdata` AS `dop_status` ON `dop_status`.`doc`=`doc_list`.`id` AND `dop_status`.`param`='status'
LEFT JOIN `doc_dopdata` AS `dop_pay` ON `dop_pay`.`doc`=`doc_list`.`id` AND `dop_pay`.`param`='pay_type'
LEFT JOIN `doc_dopdata` AS `doc_ishop` ON `doc_ishop`.`doc`=`doc_list`.`id` AND `doc_ishop`.`param`='ishop'
WHERE `doc_list`.`type`=3 AND `doc_list`.`mark_del`=0
ORDER by `doc_list`.`date` DESC";

$res=$db->query($sql);
$row=$res->num_rows;

$i=0;
$pr=$ras=0;
$tpr=$tras=0;

$tmpl->addContent("<table width='100%' cellspacing='1' class='list'>
<tr>
<th width='70'>№</th><th width='50'>ID</th><th>Статус</th><th>Агент</th><th>Сумма</th><th>Расчёт</th><th>Доставка</th><th>Дата</th><th>С сайта</th><th>Автор</th>
</tr>");
while($line=$res->fetch_assoc())
{
	if($line['status']=='ok' || $line['status']=='err')	continue;
	if(!$line['status'])	$line['status']='new';
	$status=@$CONFIG['doc']['status_list'][$line['status']];
	switch($line['pay_type'])
	{
		case 'bank':	$pay_type="безналичный";	break;
		case 'cash':	$pay_type="наличными";	break;
		case 'card':	$pay_type="платёжной картой";	break;
		case 'card_o':	$pay_type="платёжной картой на сайте";	break;
		case 'card_t':	$pay_type="платёжной картой при получении";	break;
		case 'wmr':	$pay_type="Webmoney WMR";	break;
		default:	$pay_type="не определён ({$line['pay_type']})";
	}

	$date=date('Y-m-d H:i:s',$line['date']);
	$delivery=$line['delivery']?('Да, '.$line['delivery_date']):'Не требуется';
	$ishop=$line['ishop']?'Да':'Нет';
	$link="/doc.php?mode=body&amp;doc=".$line['id'];
	$tmpl->addContent("<tr><td align='right'><a href='$link'>{$line['altnum']}{$line['subtype']}</a></td><td><a href='$link'>{$line['id']}</a></td>
	<td>$status</td><td>{$line['agent_name']}</td><td align='right'>{$line['sum']}</td><td>$pay_type</td>
	<td>$delivery</td>
	<td>$date</td><td>$ishop</td><td><a href='/adm_users.php?mode=view&amp;id={$line['user']}'>{$line['user_name']}</a></td>
	</tr>");
}
$tmpl->addContent("</table>");

$tmpl->addContent("<h2>Реализации на комплектацию</h2>");

$sql="SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`,  `doc_list`.`user`, `doc_agent`.`name` AS `agent_name`, `doc_list`.`sum`, `users`.`name` AS `user_name`, `doc_types`.`name`, `doc_list`.`p_doc`, `dop_status`.`value` AS `status`
FROM `doc_list`
LEFT JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
LEFT JOIN `doc_dopdata` AS `dop_status` ON `dop_status`.`doc`=`doc_list`.`id` AND `dop_status`.`param`='status'
WHERE `doc_list`.`type`=2 AND `doc_list`.`mark_del`=0 AND `doc_list`.`ok`=0 AND `dop_status`.`value`!='ok'
ORDER by `doc_list`.`date` DESC";

$res=$db->query($sql);

$tmpl->addContent("<table width='100%' cellspacing='1' class='list'>
<tr>
<th width='70'>№</th><th width='50'>ID</th><th>К заявке</th><th>Агент</th><th>Сумма</th><th>Дата</th><th>Автор</th>
</tr>");
while($line=$res->fetch_assoc())
{
	$date=date('Y-m-d H:i:s',$line['date']);
	$link="/doc.php?mode=body&amp;doc=".$line['id'];
	if($line['p_doc'])	$z="<a href='/doc.php?mode=body&amp;doc={$line['p_doc']}'>{$line['p_doc']}</a>";
	else			$z='--нет--';
	$tmpl->addContent("<tr><td align='right'><a href='$link'>{$line['altnum']}{$line['subtype']}</a></td><td><a href='$link'>{$line['id']}</a></td>
	<td>$z</td><td>{$line['agent_name']}</td><td align='right'>{$line['sum']}</td>
	<td>$date</td><td><a href='/adm_users.php?mode=view&amp;id={$line['user']}'>{$line['user_name']}</a></td>
	</tr>");
}
$tmpl->addContent("</table>");


$tmpl->addContent("<h2>Готовые к отгрузке реализации</h2>");

$sql="SELECT `doc_list`.`id`, `doc_list`.`altnum`, `doc_list`.`subtype`, `doc_list`.`date`,  `doc_list`.`user`, `doc_agent`.`name` AS `agent_name`, `doc_list`.`sum`, `users`.`name` AS `user_name`, `doc_types`.`name`, `doc_list`.`p_doc`, `dop_status`.`value` AS `status`
FROM `doc_list`
LEFT JOIN `doc_agent` ON `doc_list`.`agent`=`doc_agent`.`id`
LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
LEFT JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
LEFT JOIN `doc_dopdata` AS `dop_status` ON `dop_status`.`doc`=`doc_list`.`id` AND `dop_status`.`param`='status'
WHERE `doc_list`.`type`=2 AND `doc_list`.`mark_del`=0 AND `doc_list`.`ok`=0 AND `dop_status`.`value`='ok'
ORDER by `doc_list`.`date` DESC";

$res=$db->query($sql);
$row=$res->num_rows;

$i=0;
$pr=$ras=0;
$tpr=$tras=0;

$tmpl->addContent("<table width='100%' cellspacing='1' сlass='list'>
<tr>
<th width='70'>№</th><th width='50'>ID</th><th>К заявке</th><th>Агент</th><th>Сумма</th><th>Дата</th><th>Автор</th>
</tr>");
while($line=$res->fetch_assoc())
{
	$date=date('Y-m-d H:i:s',$line['date']);
	$link="/doc.php?mode=body&amp;doc=".$line['id'];
	if($line['p_doc'])	$z="<a href='/doc.php?mode=body&amp;doc={$line['p_doc']}'>{$line['p_doc']}</a>";
	else			$z='--нет--';
	$tmpl->addContent("<tr><td align='right'><a href='$link'>{$line['altnum']}{$line['subtype']}</a></td><td><a href='$link'>{$line['id']}</a></td>
	<td>$z</td><td>{$line['agent_name']}</td><td align='right'>{$line['sum']}</td>
	<td>$date</td><td><a href='/adm_users.php?mode=view&amp;id={$line['user']}'>{$line['user_name']}</a></td>
	</tr>");
}
$tmpl->addContent("</table>");


$tmpl->write();


?>
