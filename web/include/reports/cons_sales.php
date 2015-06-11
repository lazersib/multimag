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


class Report_Cons_Sales extends BaseReport {
	function getName($short=0) {
		if($short)	return "Сводный по продажам";
		else		return "Сводный отчет по продажам";
	}
	

	function Form()	{
		global $tmpl;
		$date_st=date("Y-m-01");
		$date_end=date("Y-m-d");
		$tmpl->addContent("<h1>".$this->getName()."</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='cons_sales'>
		<input type='hidden' name='opt' value='make'>
		<p class='datetime'>
		Дата от:<input type='text' id='id_pub_date_date' class='vDateField required' name='date_st' size='10' value='$date_st' maxlength='10' /><br>
		до:<input type='text' id='id_pub_date_date' class='vDateField required' name='date_end' size='10' value='$date_end' maxlength='10' />
		</p><button type='submit'>Создать отчет</button></form>");	
	}
        
    function generate($date_start, $date_end) {
        global $db;
        $date_st = strtotime( rcvdate('date_st'));
        $date_end = strtotime( rcvdate('date_end'))+60*60*24-1;
        if (!$date_end) {
            $date_end = time();
        }
        $in_st = array();
        $out_st = array();
        $in_ag = array();
        $out_ag = array();
        $res = $db->query("SELECT `doc_list`.`id`,`doc_list`.`type`,`doc_list`.`date`,`doc_list`.`sum`, `doc_list`.`subtype`, 
                `doc_list`.`firm_id`, `doc_list`.`agent`
            FROM `doc_list`
            WHERE `doc_list`.`ok`!='0' AND `doc_list`.`date`>='$date_st' AND `doc_list`.`date`<='$date_end'");
        while($line = $res->fetch_assoc()) {
            if($line['type']==1) {
                if(!isset($in_st[$line['firm_id']][$line['subtype']])) {
                    $in_st[$line['firm_id']][$line['subtype']] = $line['sum'];
                } else {
                    $in_st[$line['firm_id']][$line['subtype']] += $line['sum'];
                }
                if(!isset($in_ag[$line['agent']])) {
                    $in_ag[$line['agent']] = $line['sum'];
                } else {
                    $in_ag[$line['agent']] += $line['sum'];
                }
            } elseif($line['type']==2) {
                if(!isset($out_st[$line['firm_id']][$line['subtype']])) {
                    $out_st[$line['firm_id']][$line['subtype']] = $line['sum'];
                } else {
                    $out_st[$line['firm_id']][$line['subtype']] += $line['sum'];
                }
                if(!isset($out_ag[$line['agent']])) {
                    $out_ag[$line['agent']] = $line['sum'];
                } else {
                    $out_ag[$line['agent']] += $line['sum'];
                }
            } 
        }
        return array(
            'in_st' => $in_st,
            'in_agents' => $in_ag,
            'out_st' => $out_st,
            'out_agents' => $out_ag
        );
    }
	
	function MakePDF() {
		global $tmpl, $db;
                $tmpl->ajax = 1;
                require('fpdf/fpdf_mc.php');
                $date_st = strtotime( rcvdate('date_st'));
                $date_end = strtotime( rcvdate('date_end'))+60*60*24-1;
                if(!$date_end) $date_end = time();
                
                $data = $this->generate($date_st, $date_end);
                var_dump($data);

                $pdf = new PDF_MC_Table('P');
                $pdf->Open();
                $pdf->AddFont('Arial', '', 'arial.php');
                $pdf->SetMargins(6, 6);
                $pdf->SetAutoPageBreak(true, 6);
                $pdf->SetFont('Arial', '', 10);
                $pdf->SetFillColor(255);       
		
                
		$date_st_print = date("d.m.Y H:i:s",$date_st);
		$date_end_print = date("d.m.Y H:i:s",$date_end);

		$text = $this->getName()." c $date_st_print по $date_end_print";
                
		$pdf->CellIconv(0, 5, $text, 0, 1, 'C');
                
                $pdf->Output();
	}
	
	function Run($opt)
	{
		if($opt=='')	$this->Form();
		else		$this->MakePDF();	
	}
}



