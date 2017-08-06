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
//
//

/// Отчет по ликвидности товара
class Report_Liquidity extends BaseGSReport {

    function getName($short = 0) {
        if ($short) {
            return "По ликвидности";
        } else {
            return "Отчет по ликвидности товара";
        }
    }

    function Form() {
        global $tmpl, $db;
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='liquidity'>
            <input type='hidden' name='opt' value='pdf'>
            Показать с ликвидностью не более:<br>
            <input type='text' name='l_max' value='100'><br>
            Показать с ликвидностью не менее:<br>
            <input type='text' name='l_min' value='0'><br>
            Упорядочить:<br>
            <select name='order'>
            <option value='l'>По ликвидности</option>
            <option value='i'>По id</option>
            <option value='n'>По наименованию</option>
            <option value='v'>По коду производителя</option>
            <option value='p'>По производителю</option>
            </select>
            <select name='dir'>
            <option value='a'>По возрастанию</option>
            <option value='d'>По убыванию</option>
            </select><br>
            <label><input type='checkbox' name='exist_only' value='1'>Только в наличиии</label><br>");
        $this->GroupSelBlock();
        $tmpl->addContent("<button type='submit'>Создать отчет</button></form>");
    }

    function MakePDF() {
        global $CONFIG, $db;
        ob_start();
        define('FPDF_FONT_PATH', $CONFIG['site']['location'] . '/fpdf/font/');
        require('fpdf/fpdf_mc.php');

        $pdf = new PDF_MC_Table('P');
        $pdf->Open();
        $pdf->AddFont('Arial', '', 'arial.php');
        $pdf->SetMargins(6, 6);
        $pdf->SetAutoPageBreak(true, 6);
        $pdf->SetFont('Arial', '', 16);
        $pdf->SetFillColor(255);
        $pdf->AddPage('P');
        $text = "Отчет по ликвидности на (" . date("Y-m-d H:i:s") . ")";

        $str = iconv('UTF-8', 'windows-1251', $text);
        $pdf->Cell(0, 8, $str, 0, 1, 'C');

        $gs = rcvint('gs');
        $l_min = rcvint('l_max');
        $l_max = rcvint('l_min');
        $order = request('order');
        $dir = request('dir');
        $exist_only = rcvint('exist_only');
        $g = request('g');

        switch ($order) {
            case 'l': $order = '`doc_base`.`likvid`';
                break;
            case 'i': $order = '`doc_base`.`id`';
                break;
            case 'n': $order = '`doc_base`.`name`';
                break;
            case 'v': $order = '`doc_base`.`vc`';
                break;
            case 'p': $order = '`doc_base`.`proizv`';
                break;
            default:$order = '`doc_base`.`name`';
        }
        
        $dir = $dir == 'a' ? 'ASC':'DESC';
        
        $headers = array('N');
        $haligns = array('C');
        $aligns = array('L');
        $col_sizes = array(11);

        if (@$CONFIG['poseditor']['vc']) {
            $headers[] = 'Код';
            $haligns[] = 'C';
            $aligns[] = 'R';
            $col_sizes[] = 14;
            $headers[] = 'Наименование';
            $haligns[] = 'C';
            $aligns[] = 'L';
            $col_sizes[] = 105;
        } else {
            $headers[] = 'Наименование';
            $haligns[] = 'C';
            $aligns[] = 'L';
            $col_sizes[] = 119;
        }

        $headers[] = 'Произв.';
        $haligns[] = 'C';
        $aligns[] = 'L';
        $col_sizes[] = 20;

        $headers[] = 'Ликв.';
        $haligns[] = 'C';
        $aligns[] = 'R';
        $col_sizes[] = 15;

        $headers[] = 'Цена';
        $haligns[] = 'C';
        $aligns[] = 'R';
        $col_sizes[] = 15;

        $headers[] = 'Кол-во';
        $haligns[] = 'C';
        $aligns[] = 'R';
        $col_sizes[] = 15;

        $pdf->SetFont('', '', 10);
        $pdf->SetAligns($haligns);
        $pdf->SetWidths($col_sizes);
        $pdf->SetHeight(6);
        $pdf->SetLineWidth(0.3);
        $pdf->RowIconv($headers);
        $pdf->SetLineWidth(0.1);
        $pdf->SetAligns($aligns);
        $pdf->SetFont('', '', 8);
        $pdf->SetHeight(4);

        $all_size = array_sum($col_sizes);

        $res_group = $db->query("SELECT `id`, `name` FROM `doc_group` ORDER BY `id`");
        while ($group_line = $res_group->fetch_assoc()) {
            if ($gs && is_array($g)) {
                if (!in_array($group_line['id'], $g)) {
                    continue;
                }
            }
            $pdf->SetFillColor(192);
            $str = iconv('UTF-8', 'windows-1251', "{$group_line['id']}. {$group_line['name']}");
            $pdf->Cell($all_size, 5, $str, 1, 1, 'L', 1);
            $pdf->SetFillColor(255);


            $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`proizv` AS `vendor`, `doc_base`.`cost`, `doc_base`.`vc`,
                    `doc_base`.`likvid`, (SELECT SUM(`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` GROUP BY `doc_base_cnt`.`id`) AS `cnt`
                FROM `doc_base`
                WHERE `doc_base`.`group`='{$group_line['id']}'
                ORDER BY $order $dir");
            while ($nxt = $res->fetch_assoc()) {
                if ($nxt['cnt'] == 0 && $exist_only) {
                    continue;
                }
                if ($l_min < $nxt['likvid'] || $l_max > $nxt['likvid']) {
                    continue;
                }
                $line = array($nxt['id']);

                if ($CONFIG['poseditor']['vc']) {
                    $line[] = $nxt['vc'];
                }

                $line[] = $nxt['name'];
                $line[] = $nxt['vendor'];
                $line[] = $nxt['likvid'];
                $line[] = $nxt['cost'];
                $line[] = $nxt['cnt'];

                $pdf->RowIconv($line);
            }
        }
        $pdf->Output('liquidity_report.pdf', 'I');
    }

    function Run($opt) {
        if ($opt == '') {
            $this->Form();
        } else {
            $this->MakePDF();
        }
    }

}
