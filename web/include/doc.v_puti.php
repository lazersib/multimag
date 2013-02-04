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


$doc_types[12]="Товары в пути";

/// Документ *товар в пути*
class doc_v_puti extends doc_Nulltype
{
	// Создание нового документа или редактирование заголовка старого
	function __construct($doc=0)
	{
		parent::__construct($doc);
		$this->doc_type				=12;
		$this->doc_name				='v_puti';
		$this->doc_viewname			='Товары в пути';
		$this->sklad_editor_enable		=true;
		$this->header_fields			='sklad cena separator agent';
		settype($this->doc,'int');
		$this->PDFForms=array(
			array('name'=>'prn','desc'=>'Заявка','method'=>'PrintPDF')		
		);
	}

	function DopHead()
	{
		global $tmpl;
		if(!$this->doc)	$this->dop_data['dataprib']=date("Y-m-d");
		$tmpl->AddText("Ориентировочная дата прибытия:<br><input type='text' name='dataprib'  class='vDateField' value='{$this->dop_data['dataprib']}'>");

		$cur_agent=$this->doc_data['agent'];
		if(!$cur_agent)		$cur_agent=1;

		if(!$this->dop_data['transkom'])	$this->dop_data['transkom']=$cur_agent;

		$res=mysql_query("SELECT `name` FROM `doc_agent` WHERE `id`='{$this->dop_data['transkom']}'");
		if(mysql_errno())	throw new MysqlException('Ошибка выборки имени транспортной компании');
		$transkom_name=mysql_result($res,0,0);


		$tmpl->AddText("<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		<br>Транспортная компания:<br>
		<input type='hidden' name='transkom_id' id='transkom_id' value='{$this->dop_data['transkom']}'>
		<input type='text' id='transkom'  style='width: 100%;' value='$transkom_name'><br>
		<script type=\"text/javascript\">
		$(document).ready(function(){
			$(\"#transkom\").autocomplete(\"/docs.php\", {
			delay:300,
			minChars:1,
			matchSubset:1,
			autoFill:false,
			selectFirst:true,
			matchContains:1,
			cacheLength:10,
			maxItemsToShow:15,
			formatItem:agliFormat,
			onItemSelect:transkomselectItem,
			extraParams:{'l':'agent','mode':'srv','opt':'ac'}
			});
		});

		function transkomselectItem(li) {
		if( li == null ) var sValue = \"Ничего не выбрано!\";
		if( !!li.extra ) var sValue = li.extra[0];
		else var sValue = li.selectValue;
		document.getElementById('transkom_id').value=sValue;
		}


		</script>
		");
	}

	function DopSave()
	{
		$dataprib=rcv('dataprib');
		$transkom_id=rcv('transkom_id');

		mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
			VALUES ('{$this->doc}','dataprib','$dataprib'), ( '{$this->doc}' ,'transkom','$transkom_id')");
		if($this->doc)
		{
			$log_data='';
			if(@$this->dop_data['dataprib']!=$dataprib)		$log_data.=@"dataprib: {$this->dop_data['dataprib']}=>$dataprib, ";
			if(@$this->dop_data['transkom']!=$transkom_id)		$log_data.=@"transkom: {$this->dop_data['transkom']}=>$transkom_id, ";
			
			if($log_data)	doc_log("UPDATE {$this->doc_name}", $log_data, 'doc', $this->doc);
		}
	}

	function DopBody()
	{
		global $tmpl;
		$res=mysql_query("SELECT `value` FROM `doc_dopdata`
		WHERE `doc`='{$this->doc}' AND `param`='dataprib'");
        	$nxt=mysql_fetch_row($res);
		$tmpl->AddText("<b>Ориентировочная дата прибытия:</b> $nxt[0]");

		if(!$this->dop_data['transkom'])	$this->dop_data['transkom']=1;

		$res=mysql_query("SELECT `name` FROM `doc_agent` WHERE `id`='{$this->dop_data['transkom']}'");
		if(mysql_errno())	throw new MysqlException('Ошибка выборки имени транспортной компании');
		$transkom_name=@mysql_result($res,0,0);
		$tmpl->AddText(", <b>Транспортная компания:</b> $transkom_name");
	}
	function DocApply($silent=0)
	{
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)			throw new MysqlException('Ошибка выборки данных документа при проведении!');
		$nx=@mysql_fetch_row($res);
		if(!$nx)			throw new Exception('Документ не найден!');
		if( $nx[1] && (!$silent) )	throw new Exception('Документ уже был проведён!');
		if($silent)	return;
		$res=mysql_query("UPDATE `doc_list` SET `ok`='$tim' WHERE `id`='{$this->doc}'");
		if(!$res)			throw new MysqlException('Ошибка установки даты проведения документа!');
	}

	function DocCancel()
	{
		global $uid;
		$tim=time();
		$res=mysql_query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка выборки данных документа!');
		if(! ($nx=@mysql_fetch_row($res)))	throw new Exception('Документ не найден!');
		if(! $nx[4])				throw new Exception('Документ НЕ проведён!');
		$res=mysql_query("UPDATE `doc_list` SET `ok`='0' WHERE `id`='{$this->doc}'");
		if(!$res)				throw new MysqlException('Ошибка установки флага!');
	}

	function PrintForm($doc, $opt='')
	{
		if($opt=='')
		{
			global $tmpl;
			$tmpl->ajax=1;
			$tmpl->AddText("
			<div onclick=\"window.location='/doc.php?mode=print&amp;doc={$this->doc}&amp;opt=zayavka_pdf'\">Заявка PDF</div>");
		}
		else if($opt=='zayavka_pdf')
			$this->PrintPDF();
	}
	// Формирование другого документа на основании текущего
	function MorphTo($doc, $target_type)
	{
		get_docdata($doc);
		global $tmpl;
		global $uid;
		global $doc_data;


		if($target_type=='')
		{
			$tmpl->ajax=1;
			$tmpl->AddText("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc=$doc&amp;tt=1'\">Поступление</div>");
		}
		else if($target_type==1)
		{
			if(!isAccess('doc_postuplenie','create'))	throw new AccessException("");
			mysql_query("START TRANSACTION");
			$base=$this->Postup($doc);
			if(!$base)
			{
				mysql_query("ROLLBACK");
				$tmpl->msg("Не удалось создать подчинённый документ!","err");
			}
			else
			{
				mysql_query("COMMIT");
				$ref="Location: doc.php?mode=body&doc=$base";
				header($ref);
			}
		}
		else
		{
			$tmpl->msg("В разработке","info");
		}
	}
	// Выполнить удаление документа. Если есть зависимости - удаление не производится.
	function DelExec($doc)
	{
		$res=mysql_query("SELECT `ok` FROM `doc_list` WHERE `id`='$doc'");
		if(!mysql_result($res,0,0)) // Если проведён - нельзя удалять
		{
			$res=mysql_query("SELECT `id`, `mark_del` FROM `doc_list` WHERE `p_doc`='$doc'");
			if(!mysql_num_rows($res)) // Если есть потомки - нельзя удалять
			{
				mysql_query("DELETE FORM `doc_list_pos` WHERE `doc`='$doc'");
				mysql_query("DELETE FROM `doc_dopdata` WHERE `doc`='$doc'");
				mysql_query("DELETE FROM `doc_list` WHERE `id`='$doc'");
				return 0;
			}
		}
		return 1;
   	}

	function Service($doc)
	{
		$tmpl->ajax=1;
		$opt=rcv('opt');
		$pos=rcv('pos');
		parent::_Service($opt,$pos);
	}
//	================== Функции только этого класса ======================================================
	function Postup($doc)
	{
		$target_type=1;
		global $tmpl;
		global $uid;
		global $doc_data;
		global $dop_data;

		$res=mysql_query("SELECT `id` FROM `doc_list` WHERE `p_doc`='$doc' AND `type`='$target_type'");
		@$r_id=mysql_result($res,0,0);
		if(!$r_id)
		{
			$altnum=$this->GetNextAltNum($target_type, $this->doc_data[10]);
			$tm=time();
			$sum=DocSumUpdate($doc);
			$res=mysql_query("INSERT INTO `doc_list`
			(`type`, `agent`, `date`, `sklad`, `user`, `altnum`, `subtype`, `p_doc`, `sum`, `firm_id`)
			VALUES ('$target_type', '$doc_data[2]', '$tm', '1', '$uid', '$altnum', '$doc_data[10]', '$doc', '$sum', '{$this->doc_data['firm_id']}')");
			$r_id= mysql_insert_id();

			if(!$r_id) return 0;
			$cena=1;
			mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
			VALUES ('$r_id','cena','$cena')");

			$res=mysql_query("SELECT `tovar`, `cnt`, `comm`, `cost` FROM `doc_list_pos`
			WHERE `doc_list_pos`.`doc`='$doc'
			ORDER BY `doc_list_pos`.`id`");
			while($nxt=mysql_fetch_row($res))
			{
				mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `comm`, `cost`)
				VALUES ('$r_id', '$nxt[0]', '$nxt[1]', '$nxt[2]', '$nxt[3]')");

			}
		}
		else
		{
			$new_id=0;
			$res=mysql_query("SELECT `a`.`tovar`, `a`.`cnt`, `a`.`comm`, `a`.`cost`,
			( SELECT SUM(`b`.`cnt`) FROM `doc_list_pos` AS `b`
			  INNER JOIN `doc_list` ON `b`.`doc`=`doc_list`.`id` AND `doc_list`.`p_doc`='$doc'
			  WHERE `b`.`tovar`=`a`.`tovar` )
			FROM `doc_list_pos` AS `a`
			WHERE `a`.`doc`='$doc'");
			echo mysql_error();
			while($nxt=mysql_fetch_row($res))
			{
				if($nxt[4]<$nxt[1])
				{
					if(!$new_id)
					{
						$altnum=$this->GetNextAltNum($target_type, $doc_data[10]);
						$tm=time();
						$sum=DocSumUpdate($doc);
						$rs=mysql_query("INSERT INTO `doc_list`
						(`type`, `agent`, `date`, `sklad`, `user`, `altnum`, `subtype`, `p_doc`, `sum`)
						VALUES ('$target_type', '$doc_data[2]', '$tm', '1', '$uid', '$altnum', '$doc_data[10]', '$doc', '$sum')");
						$new_id= mysql_insert_id();

						$cena=$dop_data['cena'];
						mysql_query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)
						VALUES ('$new_id','cena','$cena')");
					}
					$n_cnt=$nxt[1]-$nxt[4];
					mysql_query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `comm`, `cost`)
 					VALUES ('$new_id', '$nxt[0]', '$n_cnt', '$nxt[2]', '$nxt[3]' )");

				}
			}
			if($new_id) $r_id=$new_id;
		}
		DocSumUpdate($r_id);
		return $r_id;
	}

	function PrintPDF($to_str=0)
	{
		define('FPDF_FONT_PATH','/var/www/gate/fpdf/font/');
		require('fpdf/fpdf_mysql.php');
		global $tmpl, $CONFIG, $uid;

		$res=mysql_query("SELECT `adres`, `tel` FROM `doc_agent` WHERE `id`='{$this->doc_data[2]}'");
		$agent_data=mysql_fetch_row($res);

		$dt=date("d.m.Y",$this->doc_data[5]);

		if($coeff==0) $coeff=1;
		if(!$to_str) $tmpl->ajax=1;

		$pdf=new FPDF('P');
		$pdf->Open();
		$pdf->SetAutoPageBreak(1,12);
		$pdf->AddFont('Arial','','arial.php');
		$pdf->tMargin=5;
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
		$pdf->SetFillColor(255);

		if($CONFIG['site']['doc_header'])
		{
			$header_img=str_replace('{FN}', $this->doc_data['firm_id'], $CONFIG['site']['doc_header']);
			$pdf->Image($header_img,8,10, 190);
			$pdf->Sety(54);
		}

		$str = 'Просим рассмотреть возможность поставки следующей продукции:';
		$pdf->SetFont('','U',14);
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,5,$str,0,1,'C',0);

		$old_x=$pdf->GetX();
		$old_y=$pdf->GetY();
		$old_margin=$pdf->lMargin;
		$table_c=110;
		$table_c2=15;

		$pdf->SetFont('','',16);
		$str='Заявка поставщику № '.$this->doc_data[9].', от '.$dt;
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,8,$str,0,1,'L',0);
		$pdf->SetFont('','',8);
		$str='Заказчик: '.unhtmlentities($this->firm_vars['firm_name'].', '.$this->firm_vars['firm_adres'].', тел:'.$this->firm_vars['firm_telefon']);
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0,5,$str,0,1,'L',0);
		$str="Поставщик: {$this->doc_data[3]}, адрес: $agent_data[0], телефон: $agent_data[1]";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->MultiCell(0,5,$str,0,1,'L',0);

		$t_width=array(8,110,20,25,0);
		$pdf->SetFont('','',12);
		$str='№';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[0],5,$str,1,0,'C',0);
		$str='Наименование';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[1],5,$str,1,0,'C',0);
		$str='Кол-во';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[2],5,$str,1,0,'C',0);
		$str='Цена';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[3],5,$str,1,0,'C',0);
		$str='Сумма';
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell($t_width[4],5,$str,1,0,'C',0);
		$pdf->Ln();

		$pdf->SetFont('','',8);

		$res=mysql_query("SELECT `doc_group`.`printname`, `doc_base`.`name`, `doc_base`.`proizv`, `doc_list_pos`.`cnt`, `doc_list_pos`.`cost`, `doc_base_dop`.`mass`
		FROM `doc_list_pos`
		LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `doc_group` ON `doc_group`.`id`=`doc_base`.`group`
		WHERE `doc_list_pos`.`doc`='{$this->doc}'
		ORDER BY `doc_list_pos`.`id`");
		$i=0;
		$sum=$summass=0;
		while($nxt=mysql_fetch_row($res))
		{
			$i++;
			$sm=$nxt[3]*$nxt[4];
			$sum+=$sm;
			$summass+=$nxt[5]*$nxt[3];
			$cost = sprintf("%01.2f р.", $nxt[4]);
			$smcost = sprintf("%01.2f р.", $sm);

			$pdf->Cell($t_width[0],5,$i,1,0,'R',0);
			$str=$nxt[0].' '.$nxt[1];
			if($nxt[2]) $str.='('.$nxt[2].')';
			$str = iconv('UTF-8', 'windows-1251', $str);
			$pdf->Cell($t_width[1],5,$str,1,0,'L',0);
			$pdf->Cell($t_width[2],5,$nxt[3],1,0,'C',0);
			$str = iconv('UTF-8', 'windows-1251', $cost);
			$pdf->Cell($t_width[3],5,$str,1,0,'R',0);
			$str = iconv('UTF-8', 'windows-1251', $smcost);
			$pdf->Cell($t_width[4],5,$str,1,0,'R',0);
			$pdf->Ln();
		}

		$cost = num2str($sum);
		$sumcost = sprintf("%01.2f", $sum);
		$summass = sprintf("%01.3f", $summass);


		if($pdf->h<=($pdf->GetY()+60)) $pdf->AddPage();

		$delta=$pdf->h-($pdf->GetY()+55);
		if($delta>7) $delta=7;

		if($CONFIG['site']['doc_shtamp'])
		{
			$shtamp_img=str_replace('{FN}', $this->doc_data['firm_id'], $CONFIG['site']['doc_shtamp']);
			$pdf->Image($shtamp_img, 4,$pdf->GetY()+$delta, 120);
		}

		$pdf->SetFont('','',8);
		$str="Масса товара: $summass кг.";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,6,$str,0,0,'L',0);

		$nds=$sum/(100+$this->firm_vars['param_nds'])*$this->firm_vars['param_nds'];
		$nds = sprintf("%01.2f", $nds);
		$pdf->SetFont('','',12);
		$str="Итого: $sumcost руб.";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,7,$str,0,1,'R',0);

		$pdf->SetFont('','',8);
		$str="Всего $i наименований, на сумму $sumcost руб. ($cost)";
		$str = iconv('UTF-8', 'windows-1251', $str);
		$pdf->Cell(0,4,$str,0,1,'L',0);


		if($to_str)
			return $pdf->Output('zayavka.pdf','S');
		else
			$pdf->Output('zayavka.pdf','I');
	}



};
?>