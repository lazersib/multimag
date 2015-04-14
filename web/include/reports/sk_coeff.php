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
/// Отчет по коэффициентам сложности работы кладовщиков
class Report_sk_coeff extends BaseGSReport {

    function getName($short = 0) {
        if ($short)
            return "По коэффициентам кладовщиков";
        else
            return "Отчет по коэффициентам сложности работы кладовщиков";
    }

    function Form() {
        global $tmpl, $db;
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
		<form action='' method='post'>
		<input type='hidden' name='mode' value='sk_coeff'>
		<input type='hidden' name='opt' value='pdf'>
		Группа товаров:<br>");
        $this->GroupSelBlock();
        $tmpl->addContent("<button type='submit'>Создать отчет</button></form>");
    }
    
    // Получить ID для сложности
    function getPcsId() {
        global $db;
        $res = $db->query("SELECT `id` FROM `doc_base_params` WHERE `param`='pack_complexity_sk'");
        if (!$res->num_rows) {
            $db->query("INSERT INTO `doc_base_params` (`param`, `type`, `pgroup_id`, `system`) VALUES ('pack_complexity_sk', 'float', NULL, 1)");
            throw new \Exception("Параметр начисления зарплаты не был найден. Параметр создан. Перед начислением заработной платы необходимо заполнить свойства номенклатуры.");
        }
        list($param_pcs_id) = $res->fetch_row();
        return $param_pcs_id;
    }

    function MakePDF() {
        global $tmpl, $CONFIG, $db;
        ob_start();
        define('FPDF_FONT_PATH', $CONFIG['site']['location'] . '/fpdf/font/');
        require('fpdf/fpdf_mc.php');

        $pc = PriceCalc::getInstance();

        $pdf = new PDF_MC_Table('P');
        $pdf->Open();
        $pdf->AddFont('Arial', '', 'arial.php');
        $pdf->SetMargins(6, 6);
        $pdf->SetAutoPageBreak(true, 6);
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetFillColor(255);

        $gs = rcvint('gs');
        $g = request('g');
        switch (@$CONFIG['doc']['sklad_default_order']) {
            case 'vc': $order = '`doc_base`.`vc`';
                break;
            case 'cost': $order = '`doc_base`.`cost`';
                break;
            default:$order = '`doc_base`.`name`';
        }

        $headers = array('N');
        $haligns = array('C');
        $aligns = array('L');
        $col_sizes = array(10);
        if ($CONFIG['poseditor']['vc']) {
            $headers[] = 'Код';
            $haligns[] = 'C';
            $aligns[] = 'L';
            $col_sizes[] = 15;
        }
        $headers[] = 'Наименование';
        $headers[] = 'Коэфф.';
        $haligns[] = 'C';
        $haligns[] = 'C';
        $aligns[] = 'L';
        $aligns[] = 'R';
        $col_sizes[] = 96;
        $col_sizes[] = 14;

        $width = array_sum($col_sizes);
        if ($width < 200) {
            $multipler = 200 / $width;
            $pdf->AddPage('P');
        } else {
            $pdf->AddPage('L');
            $multipler = 285 / $width;
        }

        foreach ($col_sizes as $id => $size) {
            $col_sizes[$id] = round($size * $multipler, 1);
        }

        $str = iconv('UTF-8', 'windows-1251', $this->getName());
        $pdf->Cell(0, 5, $str, 0, 1, 'C');

        $pdf->SetAligns($haligns);
        $pdf->SetWidths($col_sizes);
        $pdf->SetHeight(4);
        $pdf->SetLineWidth(0.3);
        $pdf->RowIconv($headers);
        $pdf->SetLineWidth(0.1);
        $pdf->SetAligns($aligns);
        $pdf->SetFont('', '', 8);

        $all_size = array_sum($col_sizes);
        $psc_id = $this->getPcsId();

        $sum = $bsum = $summass = 0;
        $res_group = $db->query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
        while ($group_line = $res_group->fetch_assoc()) {
            if ($gs && is_array($g))
                if (!in_array($group_line['id'], $g))
                    continue;
            $pdf->SetFillColor(192);
            $str = iconv('UTF-8', 'windows-1251', "{$group_line['id']}. {$group_line['name']}");
            $pdf->Cell($all_size, 5, $str, 1, 1, 'L', 1);
            $pdf->SetFillColor(255);


            $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`name`, 
                    `doc_base_values`.`value` AS `pcs`, `doc_base`.`vc`, `doc_base`.`group`, `doc_base`.`bulkcnt`
                FROM `doc_base`
                LEFT JOIN `doc_base_values` ON `doc_base_values`.`id`=`doc_base`.`id` AND `doc_base_values`.`param_id`='$psc_id'
                WHERE `doc_base`.`group`='{$group_line['id']}'
                ORDER BY $order");
            while ($nxt = $res->fetch_assoc()) {
                $line = array($nxt['id']);
                if ($CONFIG['poseditor']['vc']) {
                    $line[] = $nxt['vc'];
                }
                $line[] = $nxt['name'];
                $line[] = $nxt['pcs'];

                $pdf->RowIconv($line);
            }
        }
        $pdf->Output('sk_coeff_report.pdf', 'I');
    }

    function Run($opt) {
        if ($opt == '')
            $this->Form();
        else
            $this->MakePDF();
    }

}

