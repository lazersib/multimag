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

namespace doc\printforms\rko;

class order extends \doc\printforms\iPrintFormPdf {

    public function getName() {
        return "Расходный кассовый ордер";
    }

    /// Сформировать данные печатной формы
    public function make() {
        global $db;
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $firm_vars = $this->doc->getFirmVarsA();

        $this->pdf->AddPage('P');
        $this->addTechFooter();
        
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->SetFillColor(255);

        $this->pdf->Rect(136, 10, 3, 130);

        $this->pdf->lMargin = 5;
        $this->pdf->rMargin = 75;

        $this->pdf->SetFont('', '', 6);
        $str = "Унифицированная форма № КО-2\nУтверждена постановлением Госкомстата\nРоссии от 18.08.1998г. №88";
        $this->pdf->MultiCellIconv(0, 3, $str, 0, 'R', 0);

        $this->pdf->SetX(120);
        $str = iconv('UTF-8', 'windows-1251', "Код");
        $this->pdf->Cell(0, 4, $str, 1, 1, 'C', 0);
        $y = $this->pdf->GetY();
        $this->pdf->SetLineWidth(0.5);
        $this->pdf->SetX(120);
        $this->pdf->Cell(0, 16, '', 1, 1, 'C', 0);
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->SetY($y);

        $str = iconv('UTF-8', 'windows-1251', "Форма по ОКУД");
        $this->pdf->Cell(115, 4, $str, 0, 0, 'R', 0);
        $this->pdf->Cell(0, 4, '0310001', 1, 1, 'C', 0);

        $str = iconv('UTF-8', 'windows-1251', $firm_vars['firm_name']);
        $this->pdf->Cell(95, 4, $str, 0, 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', "по ОКПО");
        $this->pdf->Cell(20, 4, $str, 0, 0, 'R', 0);
        $this->pdf->Cell(0, 4, $firm_vars['firm_okpo'], 1, 1, 'C', 0);

        $this->pdf->SetFont('', '', 5);
        $this->pdf->Line(5, $this->pdf->GetY(), 100, $this->pdf->GetY());
        $str = iconv('UTF-8', 'windows-1251', "организация");
        $this->pdf->Cell(115, 2, $str, 0, 0, 'C', 0);
        $this->pdf->Cell(0, 4, '', 1, 1, 'C', 0);

        $this->pdf->Cell(115, 4, '', 0, 1, 'C', 0);
        $this->pdf->Line(5, $this->pdf->GetY(), 100, $this->pdf->GetY());
        $str = iconv('UTF-8', 'windows-1251', "структурное подразделение");
        $this->pdf->Cell(115, 2, $str, 0, 1, 'C', 0);


        $this->pdf->Cell(85, 4, '', 0, 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', "Номер документа");
        $this->pdf->Cell(18, 3, $str, 1, 0, 'C', 0);
        $str = iconv('UTF-8', 'windows-1251', "Дата составления");
        $this->pdf->Cell(0, 3, $str, 1, 1, 'C', 0);

        $this->pdf->SetLineWidth(0.5);
        $this->pdf->SetFont('', '', 14);
        $str = iconv('UTF-8', 'windows-1251', "Расходный кассовый ордер");
        $this->pdf->Cell(85, 4, $str, 0, 0, 'C', 0);
        $this->pdf->SetFont('', '', 7);
        $this->pdf->Cell(18, 4, $doc_data['altnum'], 1, 0, 'C', 0);
        $date = date("d.m.Y", $doc_data['date']);
        $this->pdf->Cell(0, 4, $date, 1, 1, 'C', 0);
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->Ln();


        $y = $this->pdf->GetY();

        $t_all_offset = array();
        $this->pdf->SetFont('', '', 10);
        $t_width = array(88, 16, 18, 8);
        $t_ydelta = array(1, 4, 2, 0);
        $t_text = array(
            'Дебет',
            'Сумма руб, коп',
            'Код целевого назначения',
            '');

        foreach ($t_width as $w) {
            $this->pdf->Cell($w, 16, '', 1, 0, 'C', 0);
        }
        $this->pdf->Ln();
        $this->pdf->Ln(0.5);
        $this->pdf->SetFont('', '', 8);
        $offset = 0;
        foreach ($t_width as $i => $w) {
            $t_all_offset[$offset] = $offset;
            $this->pdf->SetY($y + $t_ydelta[$i] + 0.2);
            $this->pdf->SetX($offset + $this->pdf->lMargin);
            $str = iconv('UTF-8', 'windows-1251', $t_text[$i]);
            $this->pdf->MultiCell($w, 3, $str, 0, 'C', 0);
            $offset+=$w;
        }

        $t2_width = array(8, 24, 20, 20, 16);
        $t2_start = array(0, 0, 0, 0, 0);
        $t2_ydelta = array(2, 1, 1, 1, 3);
        $t2_text = array(
            '',
            'код структурного подразделения',
            'корреспондирующий счёт, субсчёт',
            'код аналитичес- кого учёта',
            'кредит');
        $offset = 0;
        $c_id = 0;
        $old_col = 0;
        $y+=5;

        foreach ($t2_width as $i => $w2) {
            while ($c_id < $t2_start[$i]) {
                $t_a[$offset] = $offset;
                $offset+=$t_width[$c_id++];
            }

            if ($old_col == $t2_start[$i] && $i > 0) {
                $off2+=$t2_width[$i - 1];
            } else {
                $off2 = 0;
            }
            $old_col = $t2_start[$i];
            $t_all_offset[$offset + $off2] = $offset + $off2;
            $this->pdf->SetY($y);
            $this->pdf->SetX($offset + $off2 + $this->pdf->lMargin);
            $this->pdf->Cell($w2, 11, '', 1, 0, 'C', 0);

            $this->pdf->SetY($y + $t2_ydelta[$i]);
            $this->pdf->SetX($offset + $off2 + $this->pdf->lMargin);
            $str = iconv('UTF-8', 'windows-1251', $t2_text[$i]);
            $this->pdf->MultiCell($w2, 3, $str, 0, 'C', 0);
        }

        sort($t_all_offset, SORT_NUMERIC);
        $this->pdf->SetY($y + 11);
        $t_all_width = array();
        $old_offset = 0;
        foreach ($t_all_offset as $offset) {
            if ($offset == 0) {
                continue;
            }
            $t_all_width[] = $offset - $old_offset;
            $old_offset = $offset;
        }
        $t_all_width[] = 0;
        $i = 1;
        $this->pdf->SetLineWidth(0.4);
        foreach ($t_all_width as $id => $w) {
            if ($id == 5) {
                $str = $doc_data['sum'];
            } else {
                $str = '';
            }
            $this->pdf->Cell($w, 4, $str, 1, 0, 'C', 0);
            $i++;
        }
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->Ln(6);
        $this->pdf->SetFont('', '', 7);

        $res = $db->query("SELECT `doc_agent`.`fullname` FROM `doc_agent` WHERE `doc_agent`.`id`='{$doc_data['agent']}'");
        $agent_info = $res->fetch_assoc();
        if (!$agent_info) {
            throw new Exception('Агент не найден');
        }

        $str = iconv('UTF-8', 'windows-1251', "Выдать");
        $this->pdf->Cell(20, 4, $str, 0, 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', $agent_info['fullname']);
        $this->pdf->Cell(0, 4, $str, 'B', 1, 'L', 0);

        if ($doc_data['p_doc']) {
            $res = $db->query("SELECT `doc_types`.`name`, `doc_list`.`altnum`, `doc_list`.`date`  FROM `doc_list`
                INNER JOIN `doc_types` ON `doc_types`.`id`=`doc_list`.`type`
                WHERE `doc_list`.`id`='{$doc_data['p_doc']}'");
            $data = $res->fetch_array();
            $ddate = date("d.m.Y", $data['date']);
            $str_osn = "Оплата за {$data['name']} №{$data['altnum']} от $ddate";
            $str_osn = iconv('UTF-8', 'windows-1251', $str_osn);
        } else {
            $str_osn = '';
        }
        $str = iconv('UTF-8', 'windows-1251', "Основание:");
        $this->pdf->Cell(20, 4, $str, 'B', 0, 'L', 0);
        $this->pdf->Cell(0, 4, $str_osn, 'B', 1, 'L', 0);

        $str = iconv('UTF-8', 'windows-1251', "Сумма");
        $this->pdf->Cell(15, 4, $str, 'B', 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', num2str($doc_data['sum']));
        $this->pdf->Cell(0, 4, $str, 'B', 1, 'L', 0);

        $sum_r = floor($doc_data['sum']);
        $sum_c = round(($doc_data['sum'] - $sum_r) * 100);
        $str = iconv('UTF-8', 'windows-1251', "Сумма");
        $this->pdf->Cell(90, 4, '', 'B', 0, 'L', 0);
        $this->pdf->Cell(20, 4, $sum_r, 'B', 0, 'R', 0);
        $str = iconv('UTF-8', 'windows-1251', "руб.");
        $this->pdf->Cell(10, 4, $str, 0, 0, 'C', 0);
        $this->pdf->Cell(5, 4, $sum_c, 'B', 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', "коп.");
        $this->pdf->Cell(0, 4, $str, 0, 1, 'L', 0);

        $str = iconv('UTF-8', 'windows-1251', "Приложение");
        $this->pdf->Cell(20, 4, $str, 0, 0, 'L', 0);
        $this->pdf->Cell(0, 4, '', 'B', 1, 'L', 0);

        $this->pdf->Ln(3);
        $str = iconv('UTF-8', 'windows-1251', "Руководитель организации");
        $this->pdf->Cell(40, 4, $str, 0, 0, 'L', 0);
        $this->pdf->Cell(40, 4, '', 'B', 0, 'L', 0);
        $this->pdf->Cell(5, 4, '', 0, 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', $firm_vars['firm_director']);
        $this->pdf->Cell(0, 4, $str, 'B', 1, 'L', 0);

        $this->pdf->SetFont('', '', 5);
        $this->pdf->Cell(40, 2, '', 0, 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', "(подпись)");
        $this->pdf->Cell(40, 2, $str, 0, 0, 'C', 0);
        $this->pdf->Cell(5, 2, '', 0, 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', "(расшифровка подписи)");
        $this->pdf->Cell(0, 2, $str, 0, 1, 'C', 0);
        $this->pdf->SetFont('', '', 7);

        $str = iconv('UTF-8', 'windows-1251', "Главный (старший) бухгалтер");
        $this->pdf->Cell(40, 4, $str, 0, 0, 'L', 0);
        $this->pdf->Cell(40, 4, '', 'B', 0, 'L', 0);
        $this->pdf->Cell(5, 4, '', 0, 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', $firm_vars['firm_buhgalter']);
        $this->pdf->Cell(0, 4, $str, 'B', 1, 'L', 0);

        $this->pdf->SetFont('', '', 5);
        $this->pdf->Cell(40, 2, '', 0, 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', "(подпись)");
        $this->pdf->Cell(40, 2, $str, 0, 0, 'C', 0);
        $this->pdf->Cell(5, 2, '', 0, 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', "(расшифровка подписи)");
        $this->pdf->Cell(0, 2, $str, 0, 1, 'C', 0);
        $this->pdf->SetFont('', '', 7);

        $str = iconv('UTF-8', 'windows-1251', "Получил");
        $this->pdf->Cell(20, 4, $str, 0, 0, 'L', 0);
        $this->pdf->Cell(0, 4, '', 'B', 1, 'L', 0);
        $this->pdf->SetFont('', '', 5);
        $str = iconv('UTF-8', 'windows-1251', "(сумма прописью)");
        $this->pdf->Cell(0, 2, $str, 0, 1, 'C', 0);
        $this->pdf->SetFont('', '', 7);

        $str = iconv('UTF-8', 'windows-1251', "Сумма");
        $this->pdf->Cell(90, 4, '', 'B', 0, 'L', 0);
        $this->pdf->Cell(20, 4, '', 'B', 0, 'R', 0);
        $str = iconv('UTF-8', 'windows-1251', "руб.");
        $this->pdf->Cell(10, 4, $str, 0, 0, 'C', 0);
        $this->pdf->Cell(5, 4, '', 'B', 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', "коп.");
        $this->pdf->Cell(0, 4, $str, 0, 1, 'L', 0);

        $this->pdf->Ln(1);

        $this->pdf->Cell(7, 4, '"', 0, 0, 'R', 0);
        $this->pdf->Cell(5, 4, '', 'B', 0, 'R', 0);
        $this->pdf->Cell(3, 4, '"', 0, 0, 'L', 0);
        $this->pdf->Cell(30, 4, '', 'B', 0, 'R', 0);
        $this->pdf->Cell(20, 4, '', 0, 0, 'R', 0);

        $str = iconv('UTF-8', 'windows-1251', "Подпись");
        $this->pdf->Cell(10, 4, $str, 0, 0, 'R', 0);
        $this->pdf->Cell(25, 4, '', 'B', 0, 'L', 0);
        $this->pdf->Cell(0, 4, '', 0, 1, 'L', 0);

        $str = iconv('UTF-8', 'windows-1251', "По");
        $this->pdf->Cell(5, 4, $str, 0, 0, 'L', 0);
        $this->pdf->Cell(0, 4, '', 'B', 1, 'L', 0);
        $this->pdf->SetFont('', '', 5);
        $str = iconv('UTF-8', 'windows-1251', "(наименование, номер, дата и место выдачи документа, удостоверяющего личность получателя)");
        $this->pdf->Cell(0, 2, $str, 0, 1, 'C', 0);
        $this->pdf->SetFont('', '', 7);

        $this->pdf->Ln(2);

        $res = $db->query("SELECT `worker_real_name` FROM `users_worker_info` WHERE `user_id`='{$doc_data['user']}'");
        list($name) = $res->fetch_row();
        if (!$name)
            $name = $firm_vars['firm_buhgalter'];

        $str = iconv('UTF-8', 'windows-1251', "Выдал кассир");
        $this->pdf->Cell(20, 4, $str, 0, 0, 'L', 0);
        $this->pdf->Cell(40, 4, '', 'B', 0, 'L', 0);
        $this->pdf->Cell(5, 4, '', 0, 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', $name);
        $this->pdf->Cell(0, 4, $str, 'B', 1, 'L', 0);

        $this->pdf->SetFont('', '', 5);
        $this->pdf->Cell(20, 2, '', 0, 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', "(подпись)");
        $this->pdf->Cell(40, 2, $str, 0, 0, 'C', 0);
        $this->pdf->Cell(5, 2, '', 0, 0, 'L', 0);
        $str = iconv('UTF-8', 'windows-1251', "(расшифровка подписи)");
        $this->pdf->Cell(0, 2, $str, 0, 1, 'C', 0);
        $this->pdf->SetFont('', '', 7);

        $this->pdf->lMargin = 140;
        $this->pdf->rMargin = 5;
        $this->pdf->SetY(5);
        $this->pdf->Ln();
    }

}
