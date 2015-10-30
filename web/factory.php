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
include_once("core.php");
include_once("include/doc.core.php");

function getSummaryData($sklad, $dt_from, $dt_to, $header='', $sql_add='')
{
	global $db;
	$res=$db->query("SELECT `factory_data`.`id`, `factory_data`.`pos_id`, SUM(`factory_data`.`cnt`) AS `cnt`, `doc_base`.`name`, `doc_base`.`vc`, `doc_base_values`.`value` AS `zp` FROM `factory_data`
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`factory_data`.`pos_id`
	LEFT JOIN `doc_base_params` ON `doc_base_params`.`codename`='ZP'
	LEFT JOIN `doc_base_values` ON `doc_base_values`.`id`=`doc_base`.`id` AND `doc_base_values`.`param_id`=`doc_base_params`.`id`
	WHERE `factory_data`.`sklad_id`=$sklad AND `factory_data`.`date`>='$dt_from' AND `factory_data`.`date`<='$dt_to' $sql_add
	GROUP BY `factory_data`.`pos_id`");

	$i=$sum=$allcnt=0;
	$ret='';
	while($line=$res->fetch_assoc())
	{
		$i++;
		$line['vc']=htmlentities($line['vc'],ENT_QUOTES,"UTF-8");
		$line['name']=htmlentities($line['name'],ENT_QUOTES,"UTF-8");
		$sumline=$line['cnt']*$line['zp'];
		$sum+=$sumline;
		$allcnt+=$line['cnt'];
		$ret.="<tr><td>{$line['vc']}</td><td>{$line['name']}</td><td>{$line['cnt']}</td><td>{$line['zp']}</td><td>$sumline</td></tr>";
	}
	if($header && $ret)	$ret="<tr><td colspan='2'><b>$header</b></td><td>$allcnt</td><td>&nbsp;</td><td>$sum</td></tr>".$ret."<tr><td colspan='5'></td></tr>";
	else	if($ret)	$ret.="<tr><td colspan='2'><b>Итого</b></td><td>$allcnt</td><td></td><td>$sum</td></tr>";
	return $ret;
}

function PDFSummaryData($pdf, $sklad, $dt_from, $dt_to, $header='', $sql_add='')
{
	global $db;
	$res=$db->query("SELECT `factory_data`.`id`, `factory_data`.`pos_id`, SUM(`factory_data`.`cnt`) AS `cnt`, `doc_base`.`name`, `doc_base`.`vc`, `doc_base_values`.`value` AS `zp` FROM `factory_data`
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`factory_data`.`pos_id`
	LEFT JOIN `doc_base_params` ON `doc_base_params`.`codename`='ZP'
	LEFT JOIN `doc_base_values` ON `doc_base_values`.`id`=`doc_base`.`id` AND `doc_base_values`.`param_id`=`doc_base_params`.`id`
	WHERE `factory_data`.`sklad_id`=$sklad AND `factory_data`.`date`>='$dt_from' AND `factory_data`.`date`<='$dt_to' $sql_add
	GROUP BY `factory_data`.`pos_id`");

	$i=$sum=$allcnt=0;
	$ret='';
	if(!$res->num_rows)	return;
	if($header)
	{
		$pdf->SetFillColor(0);
		$pdf->SetTextColor(255);
		$str = iconv('UTF-8', 'windows-1251', $header);
		$pdf->MultiCell(0,4,$str,1,'L',1);
		$pdf->SetFillColor(255);
		$pdf->SetTextColor(0);
	}

	while($line=$res->fetch_assoc())
	{
		$i++;
		$line['vc']=htmlentities($line['vc'],ENT_QUOTES,"UTF-8");
		$line['name']=htmlentities($line['name'],ENT_QUOTES,"UTF-8");
		$sumline=$line['cnt']*$line['zp'];
		$sum+=$sumline;
		$allcnt+=$line['cnt'];
		$pdf->RowIconv( array($line['vc'], $line['name'], $line['cnt'], $line['zp'], $sumline) );
	}

	$pdf->SetFillColor(192);
	$pdf->RowIconv( array('Итого', '', $allcnt, '', $sum) );
	$pdf->SetFillColor(255);
}

try
{
    \acl::accessGuard('service.factory', \acl::VIEW);    

        need_auth($tmpl);
	$tmpl->hideBlock('left');
	SafeLoadTemplate($CONFIG['site']['inner_skin']);
	doc_menu();
	$tmpl->addBreadcrumb('Документы', '/docj_new.php');
	$tmpl->addBreadcrumb('Производственный учет', '/factory.php');
	
	$tmpl->setTitle("Производственный учёт (в разработке)");
        
        $mode = request('mode');
        
	if($mode=='') {
		$tmpl->addBreadcrumb('Производственный учет', '');
		$tmpl->setContent("
		<ul>
		<li><a href='?mode=builder_stores'>Список сборщиков</a></li>
		<li><a href='?mode=prepare'>Внесение данных</a></li>
		<li><a href='?mode=summary'>Сводная информация</a></li>
		<li><a href='?mode=export'>Экспорт</a></li>
		</ul>");
	}
	else if($mode=='builder_stores') {
		$tmpl->addBreadcrumb('Склады сборки', '');
		$res = $db->query("SELECT `id`, `name` FROM `doc_sklady` ORDER BY `id`");
		$tmpl->addContent("<table class='list'><tr><th>id</th><th>Название склада сборки</th></tr>");
		while($line = $res->fetch_assoc()) {
			$tmpl->addContent("<tr><td><a href='/factory.php?mode=builders&amp;store_id={$line['id']}'>{$line['id']}</a></td><td>{$line['name']}</td></tr>");
		}
		$tmpl->addContent("</table><a href='/factory.php?mode=builders'>Показать сборщиков со всех складов</a>");
	}
	else if($mode=='builders') {
		$tmpl->addBreadcrumb('Склады сборки', '/factory.php?mode=builder_stores');
		$editor = new \ListEditors\BuildersListEditor($db);
		$editor->line_var_name = 'id';
		$editor->store_id = rcvint('store_id');
		$editor->link_prefix = '/factory.php?mode=builders&amp;store_id='.$editor->store_id;
                $editor->acl_object_name = 'directory.builder';
		$editor->run();
	}
	else if($mode=='prepare'){
		$tmpl->addBreadcrumb('Выбор даты и склада - Ввод данных', '');
		$tmpl->setContent("
		<script type='text/javascript' src='/js/calendar.js'></script>
		<link rel='stylesheet' type='text/css' href='/css/core.calendar.css'>
		<form method='post'>
		<input type='hidden' name='mode' value='enter_day'>
		Дата:<br>
		<input type='text' name='date' id='date_input' value='".date('Y-m-d')."'><br>
		<script>
		initCalendar('date_input')
		</script>
		Склад сборки:<br>
		<select name='sklad'>");
		$res=$db->query("SELECT `id`, `name` FROM `doc_sklady` ORDER BY `name`");
		while($line=$res->fetch_row())
		{
			$tmpl->addContent("<option value='$line[0]'>".html_out($line[1])."</option>");
		}
		$tmpl->addContent("</select><br>
		<button type='submit'>Далее</button>
		</form>");
	}
	else if($mode=='enter_day')
	{
		$sklad	= rcvint('sklad');
		$date	= rcvdate('date');
		$tmpl->addBreadcrumb("Выбор даты и склада", '/factory.php?mode=prepare');
		$tmpl->addBreadcrumb("Выбор сборщика на дату $date и складе № $sklad", '');
		$res=$db->query("SELECT `id`, `name` FROM `factory_builders` WHERE `active`=1 AND `store_id`=$sklad ORDER BY `name`");
		$tmpl->addContent("<table class='list'><tr><th>Сборщик</th><th>Собрано единиц</th><th>Из них различных</th><th>Вознаграждение</th></tr>");
		$sv=$sc=0;
		while($line=$res->fetch_row())
		{
			$result=$db->query("SELECT `factory_data`.`id`, `factory_data`.`cnt`, `doc_base_values`.`value` AS `zp` FROM `factory_data`
			LEFT JOIN `doc_base` ON `doc_base`.`id`=`factory_data`.`pos_id`
			LEFT JOIN `doc_base_params` ON `doc_base_params`.`codename`='ZP'
			LEFT JOIN `doc_base_values` ON `doc_base_values`.`id`=`doc_base`.`id` AND `doc_base_values`.`param_id`=`doc_base_params`.`id`
			WHERE `factory_data`.`builder_id`=$line[0] AND `factory_data`.`sklad_id`=$sklad AND `factory_data`.`date`='$date'");
			$i=$sum=$cnt=0;
			while($nxt=$result->fetch_assoc())
			{
				$i++;
				$sum+=$nxt['cnt']*$nxt['zp'];
				$cnt+=$nxt['cnt'];
			}
			$sv+=$sum;
			$sc+=$cnt;
			$tmpl->addContent("<tr><td><a href='/factory.php?mode=enter_pos&amp;sklad=$sklad&amp;date=$date&amp;builder=$line[0]'>".html_out($line[1])."</a></td><td>$cnt</td><td>$i</td><td>$sum</td></tr>");
		}
		$tmpl->addContent("<tr><th>Итого:</th><th>$sc</th><th></th><th>$sv</th></table>");
	}
	else if($mode=='enter_pos')
	{
		$builder	= rcvint('builder');
		$sklad		= rcvint('sklad');
		$date		= rcvdate('date');
		$tmpl->addBreadcrumb("Выбор даты и склада", '/factory.php?mode=prepare');
		$tmpl->addBreadcrumb("Выбор сборщика на дату $date и складе № $sklad", '/factory.php?mode=enter_day&amp;date='.$date.'&amp;sklad='.$sklad);
		$tmpl->addBreadcrumb("Ввод собранных наименований сборщика № $builder", '');		
		if(isset($_REQUEST['vc']))
		{
			$vc=$db->real_escape_string($_REQUEST['vc']);
			$cnt=rcvint('cnt');

			$res=$db->query("SELECT `id`, `name` FROM `doc_base` WHERE `vc`='$vc'");
			if($res->num_rows==0)	$tmpl->msg("Наименование с таким кодом отсутствует в базе",'err');
			else
			{
                            \acl::accessGuard('service.factory', \acl::UPDATE); 
                            $line=$res->fetch_row();
                            $r=$db->query("REPLACE INTO `factory_data` (`sklad_id`, `builder_id`, `date`, `pos_id`, `cnt`)
                            VALUES ($sklad, $builder, '$date', $line[0], $cnt)");
			}
		}
		if(isset($_REQUEST['del_id']))
		{
			$del_id=rcvint('del_id');
			$res=$db->query("DELETE FROM `factory_data` WHERE `id`=$del_id");
		}
		$res=$db->query("SELECT `factory_data`.`id`, `factory_data`.`pos_id`, `factory_data`.`cnt`, `doc_base`.`name`, `doc_base`.`vc`, `doc_base_values`.`value` AS `zp` FROM `factory_data`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`factory_data`.`pos_id`
		LEFT JOIN `doc_base_params` ON `doc_base_params`.`codename`='ZP'
		LEFT JOIN `doc_base_values` ON `doc_base_values`.`id`=`doc_base`.`id` AND `doc_base_values`.`param_id`=`doc_base_params`.`id`
		WHERE `factory_data`.`builder_id`=$builder AND `factory_data`.`sklad_id`=$sklad AND `factory_data`.`date`='$date'");

		$tmpl->addContent("<table class='list'><thead><tr><th>N</th><th>Код</th><th>Наименование</th><th>Кол-во</th><th>Вознаграждение</th><th>Сумма</th></tr></thead>
		<tbody>");
		$i=$sum=$allcnt=0;
		while($line=$res->fetch_assoc())
		{
			$i++;
			$sumline=$line['cnt']*$line['zp'];
			$sum+=$sumline;
			$allcnt+=$line['cnt'];
			$tmpl->addContent("<tr><td>$i<a href='/factory.php?mode=enter_pos&amp;builder=$builder&amp;sklad=$sklad&amp;date=$date&amp;del_id={$line['id']}'><img src='/img/i_del.png' alt='del'></a></td><td>".html_out($line['vc'])."</td><td>".html_out($line['name'])."</td><td>{$line['cnt']}</td><td>{$line['zp']}</td><td>$sumline</td></tr>");
		}
		$tmpl->addContent("</tbody>
		<form method='post'>
		<input type='hidden' name='mode' value='enter_pos'>
		<input type='hidden' name='builder' value='$builder'>
		<input type='hidden' name='sklad' value='$sklad'>
		<input type='hidden' name='date' value='$date'>
		<tfoot>
		<tr><th colspan='3'>Итого:</th><th>$allcnt</th><th></th><th>$sum</th></tr>
		<tr><td>+</td><td><input type='text' name='vc'></td><td></td><td><input type='text' name='cnt'></td><td></td><td><button type='submit'>Записать</button></td></tr>
		</tfoot>
		</form>
		</table>");
	}
	else if($mode=='summary')
	{
		$dt_from	= rcvdate('dt_from', date('Y-m-d'));
		$dt_to		= rcvdate('dt_to', date('Y-m-d'));
		$sklad		= rcvint('sklad');
		$det_date	= rcvint('det_date');
		$det_builder	= rcvint('det_builder');
		$print		= request('print')?1:0;
		$det_date_checked=$det_date?' checked':'';
		$det_builder_checked=$det_builder?' checked':'';


		$sel_sklad_name='';
		$tmpl->addBreadcrumb("Cводная информация", '');	
		$tmpl->setContent("
		<script type='text/javascript' src='/js/calendar.js'></script>
		<link rel='stylesheet' type='text/css' href='/css/core.calendar.css'>
		<form method='post'>
		<input type='hidden' name='mode' value='summary'>
		<input type='hidden' name='get' value='1'>
		Период: <input type='text' name='dt_from' id='dt_from' value='$dt_from'> -
		<input type='text' name='dt_to' id='dt_to' value='$dt_to'><br>
		Склад сборки:<br>
		<select name='sklad'>");
		$res=$db->query("SELECT `id`, `name` FROM `doc_sklady` ORDER BY `name`");
		while($line=$res->fetch_row())
		{
			if($line[0]==$sklad)
			{
				$sel=' selected';
				$sel_sklad_name=$line[1];
			}
			else $sel='';
			$tmpl->addContent("<option value='$line[0]'{$sel}>".html_out($line[1])."</option>");
		}
		$tmpl->addContent("</select><br>
		<label><input type='checkbox' name='det_date' value='1'{$det_date_checked}>Детализировать по датам</label><br>
		<label><input type='checkbox' name='det_builder' value='1'{$det_builder_checked}>Детализировать по сборщикам</label><br>
		<label><input type='checkbox' name='print' value='1'>Печатная форма PDF</label><br>
		<script>
		initCalendar('dt_from')
		initCalendar('dt_to')
		</script>
		<button type='submit'>Далее</button>
		</form>");
		if(isset($_POST['get']))
		{
			if(!$print)
			{
				$tmpl->addContent("<table class='list'>
				<tr><th>Код</th><th>Наименование</th><th>Кол-во</th><th>Вознаграждение</th><th>Сумма</th></tr>");
				if($det_date)
				{
					$dres=$db->query("SELECT `factory_data`.`date` FROM `factory_data`
					WHERE `factory_data`.`sklad_id`=$sklad AND `factory_data`.`date`>='$dt_from' AND `factory_data`.`date`<='$dt_to' GROUP BY `factory_data`.`date`");
					while($dline=$dres->fetch_row())
					{
						if($det_builder)
						{
							$res=$db->query("SELECT `id`, `name` FROM `factory_builders` WHERE `active`>'0' ORDER BY `id`");
							while($line=$res->fetch_row())
							{
								$data=getSummaryData($sklad, $dt_from, $dt_to, "$dline[0] - $line[1]", " AND `factory_data`.`date`='$dline[0]' AND `factory_data`.`builder_id`={$line[0]}");
								if($data)	$tmpl->addContent($data);
							}
						}
						else	$tmpl->addContent(getSummaryData($sklad, $dt_from, $dt_to, $dline[0], " AND `factory_data`.`date`='$dline[0]'"));
					}
				}
				else if($det_builder)
				{
					$res=$db->query("SELECT `id`, `name` FROM `factory_builders` WHERE `active`>'0' ORDER BY `id`");
					while($line=$res->fetch_row())
					{
						$data=getSummaryData($sklad, $dt_from, $dt_to, $line[1], "AND `factory_data`.`builder_id`={$line[0]}");
						if($data)	$tmpl->addContent($data);
					}
				}
				else	$tmpl->addContent(getSummaryData($sklad, $dt_from, $dt_to));

				$tmpl->addContent("</table>");
			}
			else
			{
				$tmpl->ajax=1;
				require('fpdf/fpdf_mc.php');
				$header="Сводная информация по производству на складе $sel_sklad_name с $dt_from по $dt_to";
				if($det_builder || $det_date)
				{
					$header.=" с детализацией";
					if($det_date)	$header.=" по датам";
					if($det_builder)	$header.=" по сборщикам";
				}
				$header.=".\nСоздано ".date("Y-m-d H:i:s");
				$pdf=new PDF_MC_Table();
				$pdf->Open();
				$pdf->SetAutoPageBreak(1,12);
				$pdf->AddFont('Arial','','arial.php');
				$pdf->tMargin=5;
				$pdf->AddPage();
				$pdf->SetTextColor(0);
				$pdf->SetFillColor(255);
				$pdf->SetFont('Arial','',16);
				$str = iconv('UTF-8', 'windows-1251', $header);
				$pdf->MultiCell(0,6,$str,0,'C');

				$pdf->Ln(3);

				$pdf->SetLineWidth(0.5);
				$t_width=array(20,110,20,20,20);

				$t_text=array('Код', 'Наименование', 'Кол-во', 'З/П', 'Сумма');
				$pdf->SetFont('','',14);
				foreach($t_width as $id=>$w)
				{
					$str = iconv('UTF-8', 'windows-1251', $t_text[$id]);
					$pdf->Cell($w,6,$str,1,0,'C',0);
				}
				$pdf->Ln();
				$pdf->SetWidths($t_width);
				$pdf->SetHeight(3.8);

				$aligns=array('R','L','R','R','R');

				$pdf->SetAligns($aligns);
				$pdf->SetLineWidth(0.2);
				$pdf->SetFont('','',8);

				if($det_date)
				{
					$dres=$db->query("SELECT `factory_data`.`date` FROM `factory_data`
					WHERE `factory_data`.`sklad_id`=$sklad AND `factory_data`.`date`>='$dt_from' AND `factory_data`.`date`<='$dt_to' GROUP BY `factory_data`.`date`");
					while($dline=$dres->fetch_row())
					{
						if($det_builder)
						{
							$res=$db->query("SELECT `id`, `name` FROM `factory_builders` WHERE `active`>'0' ORDER BY `id`");
							while($line=$res->fetch_row())
							{
								PDFSummaryData($pdf, $sklad, $dt_from, $dt_to, "$dline[0] - $line[1]", " AND `factory_data`.`date`='$dline[0]' AND `factory_data`.`builder_id`={$line[0]}");
							}
						}
						else	PDFSummaryData($pdf, $sklad, $dt_from, $dt_to, $dline[0], " AND `factory_data`.`date`='$dline[0]'");
					}
				}
				else if($det_builder)
				{
					$res=$db->query("SELECT `id`, `name` FROM `factory_builders` WHERE `active`>'0' ORDER BY `id`");
					while($line=$res->fetch_row())
					{
						PDFSummaryData($pdf, $sklad, $dt_from, $dt_to, $line[1], "AND `factory_data`.`builder_id`={$line[0]}");
					}
				}
				else	PDFSummaryData($pdf, $sklad, $dt_from, $dt_to);

				$pdf->Output();
				exit(0);
			}
		}
	}
	else if($mode=='export')
	{
		$tmpl->addBreadcrumb("Экспорт данных", '');	
		$tmpl->setContent("
		<script type='text/javascript' src='/js/calendar.js'></script>
		<script type='text/javascript' src='/css/jquery/jquery.js'></script>
		<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<link rel='stylesheet' type='text/css' href='/css/core.calendar.css'>
		<form method='post'>
		<input type='hidden' name='mode' value='export_submit'>
		Дата:<br>
		<input type='text' name='date' id='date_input' value='".date('Y-m-d')."'><br>
		<script>
		initCalendar('date_input')
		</script>
		Склад сборки:<br>
		<select name='sklad'>");
		$res=$db->query("SELECT `id`, `name` FROM `doc_sklady` ORDER BY `name`");
		while($line=$res->fetch_row())
		{
			$tmpl->addContent("<option value='$line[0]'>".html_out($line[1])."</option>");
		}
		$tmpl->addContent("</select><br>
		Поместить готовую продукцию на склад:<br>
		<select name='nasklad'>");
		$res=$db->query("SELECT `id`, `name` FROM `doc_sklady` ORDER BY `name`");
		while($line=$res->fetch_row())
		{
			$tmpl->addContent("<option value='$line[0]'>".html_out($line[1])."</option>");
		}
		$tmpl->addContent("</select><br>
		Услуга начисления зарплаты:<br>
		<select name='service_id'>");
		$res=$db->query("SELECT `id`,`name` FROM `doc_base` WHERE `pos_type`=1 ORDER BY `name`");
		while($nxt=$res->fetch_row())
		{
			$tmpl->addContent("<option value='$nxt[0]'>".html_out($nxt[1])."</option>");
		}
		$tmpl->addContent("</select><br>
		Организация:<br><select name='firm'>");
		$res=$db->query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `firm_name`");
		while($nx=$res->fetch_row())
		{
			$tmpl->addContent("<option value='$nx[0]'>".html_out($nx[1])."</option>");
		}
		$tmpl->addContent("</select><br>
		Агент:<br>
		<input type='hidden' name='agent' id='agent_id' value=''>
		<input type='text' id='agent_nm'  style='width: 450px;' value=''><br>
                    Кладовщик, принимающий готовый товар на складе:<br><select name='storekeeper_id'>");
                $res = $db->query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
                while ($nxt = $res->fetch_row()) {
                    $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
                }
                $tmpl->addContent("</select><br>
		<button type='submit'>Далее</button>
		</form>
				<script type=\"text/javascript\">
			$(document).ready(function(){
				$(\"#agent_nm\").autocomplete(\"/docs.php\", {
					delay:300,
					minChars:1,
					matchSubset:1,
					autoFill:false,
					selectFirst:true,
					matchContains:1,
					cacheLength:10,
					maxItemsToShow:15,
					formatItem:agliFormat,
					onItemSelect:agselectItem,
					extraParams:{'l':'agent','mode':'srv','opt':'ac'}
				});
			});

			function agliFormat (row, i, num) {
				var result = row[0] + \"<em class='qnt'>тел. \" +
				row[2] + \"</em> \";
				return result;
			}


			function agselectItem(li) {
				if( li == null ) var sValue = \"Ничего не выбрано!\";
				if( !!li.extra ) var sValue = li.extra[0];
				else var sValue = li.selectValue;
				document.getElementById('agent_id').value=sValue;
			}
			</script>
		");
	}
	else if($mode=='export_submit') {
            $agent	= rcvint('agent');
            $sklad	= rcvint('sklad');
            $nasklad= rcvint('nasklad');
            $firm	= rcvint('firm');
            $service_id = rcvint('service_id');
            $date	= rcvdate('date');
            $dt_to	= rcvdate('dt_to');
            $storekeeper_id = rcvint('storekeeper_id');
                
            $doc_data = array(
                'firm_id' => $firm,
                'subtype' => '',
                'sklad' => $sklad,
                'agent' => $agent
            );
            $dop_data = array(
                'cena' => 1,
                'script' => 'sborka_zap',
                'nasklad' => $nasklad,
                'service_id' => $service_id,
                'not_a_p' => 0,
                'storekeeper_id' => $storekeeper_id,
            );     
            \acl::accessGuard('doc.sborka', \acl::CREATE); 
            $doc_obj = new doc_Sborka();
            $doc_id = $doc_obj->create($doc_data);
            $doc_obj->setDopDataA($dop_data); 
            
            $res = $db->query("SELECT `factory_data`.`id`, `factory_data`.`pos_id`, SUM(`factory_data`.`cnt`) AS `cnt`
                FROM `factory_data`
                WHERE `factory_data`.`sklad_id`=$sklad AND `factory_data`.`date`='$date'
                GROUP BY `factory_data`.`pos_id`");
            while ($line = $res->fetch_assoc()) {
                $db->insertA('doc_list_pos', array('doc'=>$doc_id, 'tovar'=>$line['pos_id'], 'cnt'=>$line['cnt'], 'page'=>0));
            }
            redirect("/doc_sc.php?mode=edit&sn=sborka_zap&doc=$doc_id");
	}
}
catch(AccessException $e)
{
	$tmpl->msg($e->getMessage(),'err',"Нет доступа");
}
catch(mysqli_sql_exception $e)
{
    $db->rollback();
    $tmpl->addContent("<br><br>");
    writeLogException($e);
    $tmpl->errorMessage($e->getMessage());
}
catch(Exception $e)
{
    $db->rollback();
    $tmpl->addContent("<br><br>");
    writeLogException($e);
    $tmpl->errorMessage($e->getMessage());
}

$tmpl->write();
