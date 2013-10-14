<?php
//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2012, BlackLight, TND Team, http://tndproject.org
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
	$res=$db->query("SELECT `fabric_data`.`id`, `fabric_data`.`pos_id`, SUM(`fabric_data`.`cnt`) AS `cnt`, `doc_base`.`name`, `doc_base`.`vc`, `doc_base_values`.`value` AS `zp` FROM `fabric_data`
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`fabric_data`.`pos_id`
	LEFT JOIN `doc_base_params` ON `doc_base_params`.`param`='ZP'
	LEFT JOIN `doc_base_values` ON `doc_base_values`.`id`=`doc_base`.`id` AND `doc_base_values`.`param_id`=`doc_base_params`.`id`
	WHERE `fabric_data`.`sklad_id`=$sklad AND `fabric_data`.`date`>='$dt_from' AND `fabric_data`.`date`<='$dt_to' $sql_add
	GROUP BY `fabric_data`.`pos_id`");

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
	$res=$db->query("SELECT `fabric_data`.`id`, `fabric_data`.`pos_id`, SUM(`fabric_data`.`cnt`) AS `cnt`, `doc_base`.`name`, `doc_base`.`vc`, `doc_base_values`.`value` AS `zp` FROM `fabric_data`
	LEFT JOIN `doc_base` ON `doc_base`.`id`=`fabric_data`.`pos_id`
	LEFT JOIN `doc_base_params` ON `doc_base_params`.`param`='ZP'
	LEFT JOIN `doc_base_values` ON `doc_base_values`.`id`=`doc_base`.`id` AND `doc_base_values`.`param_id`=`doc_base_params`.`id`
	WHERE `fabric_data`.`sklad_id`=$sklad AND `fabric_data`.`date`>='$dt_from' AND `fabric_data`.`date`<='$dt_to' $sql_add
	GROUP BY `fabric_data`.`pos_id`");

	$i=$sum=$allcnt=0;
	$ret='';
	if(!$res->num_rows)	return;
	if($header)
	{
		$pdf->SetFillColor(0);
		$pdf->SetContentColor(255);
		$str = iconv('UTF-8', 'windows-1251', $header);
		$pdf->MultiCell(0,4,$str,1,'L',1);
		$pdf->SetFillColor(255);
		$pdf->SetContentColor(0);
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
	if(!isAccess('doc_fabric','view'))	throw new AccessException('');
	need_auth($tmpl);
	$tmpl->hideBlock('left');
	$tmpl->setTitle("Производственный учёт (в разработке)");
	if($mode=='')
	{
		$tmpl->setContent("<h1 id='page-title'>Производственный учёт</h1>
		<ul>
		<li><a href='?mode=builders'>Список сборщиков</a></li>
		<li><a href='?mode=prepare'>Внесение данных</a></li>
		<li><a href='?mode=summary'>Сводная информация</a></li>
		<li><a href='?mode=export'>Экспорт</a></li>
		</ul>");
	}
	else if($mode=='builders')
	{
		$tmpl->setContent("<h1 id='page-title'>Производственный учёт - сборщики</h1>
		<div id='page-info'><a href='/fabric.php'>Назад</a></div>
		<form method='post'>
		<input type='hidden' name='mode' value='builders'>
		<input type='hidden' name='opt' value='save'>");

		if(isset($_POST['name']))
		if(is_array($_POST['name']))
		{
			$res=$db->query("SELECT `id`, `active`, `name` FROM `fabric_builders` ORDER BY `id`");
			$f=0;
			while($line=$res->fetch_row())
			{
				$upd='';
				$active=@$_POST['active'][$line[0]]?1:0;
				$name=@$_POST['name'][$line[0]];

				if($name!=$line[2])	$upd="`name`='".$db->real_escape_string($name)."'";
				if($active!=$line[1])
				{
					if($upd)	$upd.=',';
					$upd.="`active`=$active";
				}
				if($upd)
				{
					if(!isAccess('doc_fabric','edit'))	throw new AccessException('');
					$r=$db->query("UPDATE `fabric_builders` SET $upd WHERE `id`=$line[0]");
					$f=1;
				}
			}
			if($f)	$tmpl->msg("Данные обновлены","ok");
		}
		if(@$_POST['name_new'])
		{
			if(!isAccess('doc_fabric','edit'))	throw new AccessException('');
			$active=@$_POST['active_new']?1:0;
			$name=$db->real_escape_string($_POST['name_new']);
			$res=$db->query("INSERT INTO `fabric_builders` (`active`,`name`) VALUES ($active, '$name')");
			if($f)	$tmpl->msg("Сборщик добавлен","ok");
		}
		$res=$db->query("SELECT `id`, `active`, `name` FROM `fabric_builders` ORDER BY `id`");
		$tmpl->addContent("<table class='list'>
		<tr><th>ID</th><th>&nbsp;</th><th>Имя</th></tr>");
		while($line=$res->fetch_row())
		{
			$checked=$line[1]?'checked':'';
			$tmpl->addContent("<tr><td>$line[0]</td><td><input type='checkbox' name='active[$line[0]]' value='1' $checked></td><td><input type='text' name='name[$line[0]]' value='".html_out($line[2])."' maxlength='32'></td></tr>");
		}
		$tmpl->addContent("<tr><td>новый</td><td><input type='checkbox' name='active_new' value='1' checked></td><td><input type='text' name='name_new' value='' maxlength='32'></td></tr>");
		$tmpl->addContent("</table><button type='submit'>Сохранить</button></form>");
	}
	else if($mode=='prepare')
	{
		$tmpl->setContent("<h1 id='page-title'>Производственный учёт - ввод данных</h1>
		<div id='page-info'><a href='/fabric.php'>Назад</a></div>
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
		$tmpl->setContent("<h1 id='page-title'>Производственный учёт - ввод данных</h1>
		<div id='page-info'><a href='/fabric.php?mode=prepare'>Назад</a></div>");
		$res=$db->query("SELECT `id`, `name` FROM `fabric_builders` WHERE `active`=1 ORDER BY `name`");
		$tmpl->addContent("<table class='list'><tr><th>Сборщик</th><th>Собрано единиц</th><th>Из них различных</th><th>Вознаграждение</th></tr>");
		$sv=$sc=0;
		while($line=$res->fetch_row())
		{
			$result=$db->query("SELECT `fabric_data`.`id`, `fabric_data`.`cnt`, `doc_base_values`.`value` AS `zp` FROM `fabric_data`
			LEFT JOIN `doc_base` ON `doc_base`.`id`=`fabric_data`.`pos_id`
			LEFT JOIN `doc_base_params` ON `doc_base_params`.`param`='ZP'
			LEFT JOIN `doc_base_values` ON `doc_base_values`.`id`=`doc_base`.`id` AND `doc_base_values`.`param_id`=`doc_base_params`.`id`
			WHERE `fabric_data`.`builder_id`=$line[0] AND `fabric_data`.`sklad_id`=$sklad AND `fabric_data`.`date`='$date'");
			$i=$sum=$cnt=0;
			while($nxt=$result->fetch_assoc())
			{
				$i++;
				$sum+=$nxt['cnt']*$nxt['zp'];
				$cnt+=$nxt['cnt'];
			}
			$sv+=$sum;
			$sc+=$cnt;
			$tmpl->addContent("<tr><td><a href='/fabric.php?mode=enter_pos&amp;sklad=$sklad&amp;date=$date&amp;builder=$line[0]'>".html_out($line[1])."</a></td><td>$cnt</td><td>$i</td><td>$sum</td></tr>");
		}
		$tmpl->addContent("<tr><th>Итого:</th><th>$sc</th><th></th><th>$sv</th></table>");
	}
	else if($mode=='enter_pos')
	{
		$builder	= rcvint('builder');
		$sklad		= rcvint('sklad');
		$date		= rcvdate('date');
		$tmpl->setContent("<h1 id='page-title'>Производственный учёт - ввод данных</h1>
		<div id='page-info'><a href='/fabric.php?mode=enter_day&amp;sklad=$sklad&amp;date=$date'>Назад</a></div>");
		if(isset($_REQUEST['vc']))
		{
			$vc=$db->real_escape_string($_REQUEST['vc']);
			$cnt=rcvint('cnt');

			$res=$db->query("SELECT `id`, `name` FROM `doc_base` WHERE `vc`='$vc'");
			if($res->num_rows==0)	$tmpl->msg("Наименование с таким кодом отсутствует в базе",'err');
			else
			{
				if(!isAccess('doc_fabric','edit'))	throw new AccessException('');
				$line=$res->fetch_row();
				$r=$db->query("REPLACE INTO `fabric_data` (`sklad_id`, `builder_id`, `date`, `pos_id`, `cnt`)
				VALUES ($sklad, $builder, '$date', $line[0], $cnt)");
			}
		}
		if(isset($_REQUEST['del_id']))
		{
			$del_id=rcvint('del_id');
			$res=$db->query("DELETE FROM `fabric_data` WHERE `id`=$del_id");
		}
		$res=$db->query("SELECT `fabric_data`.`id`, `fabric_data`.`pos_id`, `fabric_data`.`cnt`, `doc_base`.`name`, `doc_base`.`vc`, `doc_base_values`.`value` AS `zp` FROM `fabric_data`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`fabric_data`.`pos_id`
		LEFT JOIN `doc_base_params` ON `doc_base_params`.`param`='ZP'
		LEFT JOIN `doc_base_values` ON `doc_base_values`.`id`=`doc_base`.`id` AND `doc_base_values`.`param_id`=`doc_base_params`.`id`
		WHERE `fabric_data`.`builder_id`=$builder AND `fabric_data`.`sklad_id`=$sklad AND `fabric_data`.`date`='$date'");

		$tmpl->addContent("<table class='list'><thead><tr><th>N</th><th>Код</th><th>Наименование</th><th>Кол-во</th><th>Вознаграждение</th><th>Сумма</th></tr></thead>
		<tbody>");
		$i=$sum=$allcnt=0;
		while($line=$res->fetch_assoc())
		{
			$i++;
			$sumline=$line['cnt']*$line['zp'];
			$sum+=$sumline;
			$allcnt+=$line['cnt'];
			$tmpl->addContent("<tr><td>$i<a href='/fabric.php?mode=enter_pos&amp;builder=$builder&amp;sklad=$sklad&amp;date=$date&amp;del_id={$line['id']}'><img src='/img/i_del.png' alt='del'></a></td><td>".html_out($line['vc'])."</td><td>".html_out($line['name'])."</td><td>{$line['cnt']}</td><td>{$line['zp']}</td><td>$sumline</td></tr>");
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

		$tmpl->setContent("<h1 id='page-title'>Производственный учёт - сводная информация</h1>
		<div id='page-info'><a href='/fabric.php'>Назад</a></div>
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
					$dres=$db->query("SELECT `fabric_data`.`date` FROM `fabric_data`
					WHERE `fabric_data`.`sklad_id`=$sklad AND `fabric_data`.`date`>='$dt_from' AND `fabric_data`.`date`<='$dt_to' GROUP BY `fabric_data`.`date`");
					while($dline=$dres->fetch_row())
					{
						if($det_builder)
						{
							$res=$db->query("SELECT `id`, `name` FROM `fabric_builders` WHERE `active`>'0' ORDER BY `id`");
							while($line=$res->fetch_row())
							{
								$data=getSummaryData($sklad, $dt_from, $dt_to, "$dline[0] - $line[1]", " AND `fabric_data`.`date`='$dline[0]' AND `fabric_data`.`builder_id`={$line[0]}");
								if($data)	$tmpl->addContent($data);
							}
						}
						else	$tmpl->addContent(getSummaryData($sklad, $dt_from, $dt_to, $dline[0], " AND `fabric_data`.`date`='$dline[0]'"));
					}
				}
				else if($det_builder)
				{
					$res=$db->query("SELECT `id`, `name` FROM `fabric_builders` WHERE `active`>'0' ORDER BY `id`");
					while($line=$res->fetch_row())
					{
						$data=getSummaryData($sklad, $dt_from, $dt_to, $line[1], "AND `fabric_data`.`builder_id`={$line[0]}");
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
					$dres=$db->query("SELECT `fabric_data`.`date` FROM `fabric_data`
					WHERE `fabric_data`.`sklad_id`=$sklad AND `fabric_data`.`date`>='$dt_from' AND `fabric_data`.`date`<='$dt_to' GROUP BY `fabric_data`.`date`");
					while($dline=$dres->fetch_row())
					{
						if($det_builder)
						{
							$res=$db->query("SELECT `id`, `name` FROM `fabric_builders` WHERE `active`>'0' ORDER BY `id`");
							while($line=$res->fetch_row())
							{
								PDFSummaryData($pdf, $sklad, $dt_from, $dt_to, "$dline[0] - $line[1]", " AND `fabric_data`.`date`='$dline[0]' AND `fabric_data`.`builder_id`={$line[0]}");
							}
						}
						else	PDFSummaryData($pdf, $sklad, $dt_from, $dt_to, $dline[0], " AND `fabric_data`.`date`='$dline[0]'");
					}
				}
				else if($det_builder)
				{
					$res=$db->query("SELECT `id`, `name` FROM `fabric_builders` WHERE `active`>'0' ORDER BY `id`");
					while($line=$res->fetch_row())
					{
						PDFSummaryData($pdf, $sklad, $dt_from, $dt_to, $line[1], "AND `fabric_data`.`builder_id`={$line[0]}");
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
		$tmpl->setContent("<h1 id='page-title'>Производственный учёт - экспорт данных</h1>
		<div id='page-info'><a href='/fabric.php'>Назад</a></div>
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
		<select name='tov_id'>");
		$res=$db->query("SELECT `id`,`name` FROM `doc_base` WHERE `pos_type`=1 ORDER BY `name`");
		while($nxt=$res->fetch_row())
		{
			$tmpl->addContent("<option value='$nxt[0]'>".html_out($nxt[1])."</option>");
		}
		$tmpl->addContent("</select><br>
		Организация:<br><select name='firm'>");
		$rs=$db->query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `firm_name`");
		while($nx=$res->fetch_row())
		{
			$tmpl->addContent("<option value='$nx[0]'>".html_out($nx[1])."</option>");
		}
		$tmpl->addContent("</select><br>
		Агент:<br>
		<input type='hidden' name='agent' id='agent_id' value=''>
		<input type='text' id='agent_nm'  style='width: 450px;' value=''><br>
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
	else if($mode=='export_submit')
	{
		$agent	= rcvint('agent');
		$sklad	= rcvint('sklad');
		$nasklad= rcvint('nasklad');
		$firm	= rcvint('firm');
		$tov_id = rcvint('tov_id');
		$date	= rcvdate('date');
		$dt_to	= rcvdate('dt_to');

		$tim=time();
		$res=$db->query("INSERT INTO `doc_list` (`date`, `firm_id`, `type`, `user`, `altnum`, `subtype`, `sklad`, `agent`)
				VALUES	('$tim', '$firm', '17', '$uid', '0', 'auto', '$sklad', '$agent')");
		$doc=$db->insert_id;
		$res=$db->query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES ('$doc','cena','1'), ('$doc','script_mark','ds_sborka_zap'), ('$doc','nasklad','$nasklad'), ('$doc','tov_id','$tov_id'), ('$doc','not_a_p','0')");

		$res=$db->query("SELECT `fabric_data`.`id`, `fabric_data`.`pos_id`, SUM(`fabric_data`.`cnt`) AS `cnt`, `doc_base_values`.`value` AS `zp` FROM `fabric_data`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`fabric_data`.`pos_id`
		LEFT JOIN `doc_base_params` ON `doc_base_params`.`param`='ZP'
		LEFT JOIN `doc_base_values` ON `doc_base_values`.`id`=`doc_base`.`id` AND `doc_base_values`.`param_id`=`doc_base_params`.`id`
		WHERE `fabric_data`.`sklad_id`=$sklad AND `fabric_data`.`date`='$date' $sql_add
		GROUP BY `fabric_data`.`pos_id`");
		$ret='';
		while($line=$res->fetch_assoc())
		{
			$r=$db->query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `page`) VALUES ($doc, {$line['pos_id']}, {$line['cnt']}, 0)");
		}

		header("Location: /doc_sc.php?mode=edit&sn=sborka_zap&doc=$doc&tov_id=$tov_id&agent=$agent&sklad=$sklad&firm=$firm&nasklad=$nasklad&not_a_p=$not_a_p");
	}
}
catch(AccessException $e)
{
	$tmpl->msg($e->getMessage(),'err',"Нет доступа");
}
catch(mysqli_sql_exception $e)
{
	$e->db->rollback();
	$tmpl->addContent("<br><br>");
	$tmpl->logger($e->getMessage(), false, " - sqlstate:".$e->sqlstate);
}
catch(Exception $e)
{
	$db->rollback();
	$tmpl->addContent("<br><br>");
	$tmpl->logger($e->getMessage());
}

$tmpl->write();
?>