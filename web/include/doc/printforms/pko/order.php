<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2018, BlackLight, TND Team, http://tndproject.org
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

namespace doc\printforms\pko;

class order extends \doc\printforms\iPrintFormPdf {

    public function getName() {
        return "Приходный кассовый ордер";
    }

    /// Сформировать данные печатной формы
    public function make() {
        global $db;
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $firm_vars = $this->doc->getFirmVarsA();

        $this->pdf->AddPage('P');
        $this->addTechFooter();
        
        $this->pdf->SetFillColor(255);
        $this->pdf->Rect(136, 3, 3, 120);

        $this->pdf->lMargin = 5;
        $this->pdf->rMargin = 75;

        $this->pdf->SetFontSize(6);
        $str = "Унифицированная форма № КО-1\nУтверждена постановлением Госкомстата\nРоссии от 18.08.1998г. №88";
        $this->pdf->MultiCellIconv(0, 3, $str, 0, 'R', 0);

        $this->pdf->SetX(120);
        $str = "Код";
        $this->pdf->CellIconv(0, 4, $str, 1, 1, 'C', 0);
        $y = $this->pdf->GetY();
        $this->pdf->SetLineWidth(0.5);
        $this->pdf->SetX(120);
        $this->pdf->Cell(0, 16, '', 1, 1, 'C', 0);
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->SetY($y);

        $str = "Форма по ОКУД";
        $this->pdf->CellIconv(115, 4, $str, 0, 0, 'R', 0);
        $this->pdf->Cell(0, 4, '0310001', 1, 1, 'C', 0);

        $this->pdf->CellIconv(95, 4, $firm_vars['firm_name'], 0, 0, 'L', 0);
        $str = "по ОКПО";
        $this->pdf->CellIconv(20, 4, $str, 0, 0, 'R', 0);
        $this->pdf->CellIconv(0, 4, $firm_vars['firm_okpo'], 1, 1, 'C', 0);

        $this->pdf->SetFontSize(5);
        $this->pdf->Line(5, $this->pdf->GetY(), 100, $this->pdf->GetY());
        $str = "организация";
        $this->pdf->CellIconv(115, 2, $str, 0, 0, 'C', 0);
        $this->pdf->Cell(0, 4, '', 1, 1, 'C', 0);

        $this->pdf->Cell(115, 4, '', 0, 1, 'C', 0);
        $this->pdf->Line(5, $this->pdf->GetY(), 100, $this->pdf->GetY());
        $str = "структурное подразделение";
        $this->pdf->CellIconv(115, 2, $str, 0, 1, 'C', 0);

        $this->pdf->Cell(85, 4, '', 0, 0, 'L', 0);
        $str = "Номер документа";
        $this->pdf->CellIconv(18, 3, $str, 1, 0, 'C', 0);
        $str = "Дата составления";
        $this->pdf->CellIconv(0, 3, $str, 1, 1, 'C', 0);

        $this->pdf->SetLineWidth(0.5);
        $this->pdf->SetFont('', '', 14);
        $str = iconv('UTF-8', 'windows-1251', "Приходный кассовый ордер");
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
        $t_width = array(10, 70, 23, 20, 7);
        $t_ydelta = array(2, 1, 6, 4, 1);
        $t_text = array(
            'Дебет',
            'Кредит',
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

        $t2_width = array(25, 25, 20);
        $t2_start = array(1, 1, 1);
        $t2_ydelta = array(2, 1, 1);
        $t2_text = array(
            'код структурного подразделения',
            'корреспондиру- ющий счёт, субсчёт',
            'код аналитичес- кого учёта');
        $offset = 0;
        $c_id = 0;
        $old_col = 0;
        $y+=5;

        foreach ($t2_width as $i => $w2) {
            while ($c_id < $t2_start[$i]) {
                $t_a[$offset] = $offset;
                $offset+=$t_width[$c_id++];
            }

            if ($old_col == $t2_start[$i]) {
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
            $this->pdf->MultiCellIconv($w2, 3, $t2_text[$i], 0, 'C', 0);
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
        
        $cred_account = '';
        $res = $db->query("SELECT `account` FROM `doc_ctypes` WHERE `id`='{$dop_data['credit_type']}'");
        if($res->num_rows) {
            list($cred_account) = $res->fetch_row();
        }
        
        foreach ($t_all_width as $id => $w) {
            switch($id) {
                case 0:
                    $str = $dop_data['account'];
                    break;
                case 2:
                    $str = $cred_account;
                    break;
                case 4:
                    $str = $doc_data['sum'];
                    break;
                default:
                    $str = '-';
            }
            $this->pdf->Cell($w, 4, $str, 1, 0, 'C', 0);
            $i++;
        }
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->Ln(6);
        $this->pdf->SetFont('', '', 7);
        $res = $db->query("SELECT `doc_agent`.`fullname` FROM `doc_agent` WHERE `doc_agent`.`id`='{$doc_data['agent']}'");
        $agent_info = $res->fetch_assoc();
        if (!$agent_info)
            throw new Exception('Агент не найден');

        $str = "Принято от";
        $this->pdf->CellIconv(20, 4, $str, 'B', 0, 'L', 0);
        $this->pdf->CellIconv(0, 4, $agent_info['fullname'], 'B', 1, 'L', 0);

        if ($doc_data['p_doc']) {
            $res = $db->query("SELECT `doc_list`.`altnum`, `doc_list`.`date` FROM `doc_list`
			WHERE `doc_list`.`id`='{$doc_data['p_doc']}'");
            $data = $res->fetch_assoc();
            $ddate = date("d.m.Y", $data['date']);
            $str_osn = "Оплата к с/ф №{$data['altnum']} от $ddate";
        } else {
            $str_osn = '';
        }
        $str = "Основание:";
        $this->pdf->CellIconv(20, 4, $str, 'B', 0, 'L', 0);
        $this->pdf->CellIconv(0, 4, $str_osn, 'B', 1, 'L', 0);

        $str = "Сумма";
        $this->pdf->CellIconv(15, 4, $str, 'B', 0, 'L', 0);
        $this->pdf->CellIconv(0, 4, num2str($doc_data['sum']), 'B', 1, 'L', 0);

        $sum_r = floor($doc_data['sum']);
        $sum_c = round(($doc_data['sum'] - $sum_r) * 100);

        $this->pdf->Cell(90, 4, '', 'B', 0, 'L', 0);
        $this->pdf->CellIconv(20, 4, $sum_r, 'B', 0, 'R', 0);
        $this->pdf->CellIconv(10, 4, "руб.", 0, 0, 'C', 0);
        $this->pdf->Cell(5, 4, $sum_c, 'B', 0, 'L', 0);
        $this->pdf->CellIconv(0, 4, "коп.", 0, 1, 'L', 0);

        $this->pdf->CellIconv(20, 4, "В том числе", 0, 0, 'L', 0);
        $this->pdf->Cell(0, 4, '', 'B', 1, 'L', 0);

        $this->pdf->CellIconv(20, 4, "Приложение", 0, 0, 'L', 0);
        $this->pdf->Cell(0, 4, '', 'B', 1, 'L', 0);

        $this->pdf->Ln(3);
        $this->pdf->CellIconv(20, 4, "Бухгалтер", 0, 0, 'L', 0);
        $this->pdf->Cell(40, 4, '', 'B', 0, 'L', 0);
        $this->pdf->Cell(5, 4, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv(0, 4, $firm_vars['firm_buhgalter'], 'B', 1, 'L', 0);

        $this->pdf->SetFont('', '', 5);
        $this->pdf->Cell(20, 2, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv(40, 2, "(подпись)", 0, 0, 'C', 0);
        $this->pdf->Cell(5, 2, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv(0, 2, "(расшифровка подписи)", 0, 1, 'C', 0);
        $this->pdf->SetFont('', '', 7);

        $res = $db->query("SELECT `worker_real_name` FROM `users_worker_info` WHERE `user_id`='{$doc_data['user']}'");
        if ($res->num_rows) {
            $worker_info = $res->fetch_assoc();
            $name = $worker_info['worker_real_name'];
        } else {
            $name = $firm_vars['firm_buhgalter'];
        }

        $this->pdf->CellIconv(20, 4, "Получил кассир", 0, 0, 'L', 0);
        $this->pdf->Cell(40, 4, '', 'B', 0, 'L', 0);
        $this->pdf->Cell(5, 4, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv(0, 4, $name, 'B', 1, 'L', 0);

        $this->pdf->SetFont('', '', 5);
        $this->pdf->Cell(20, 2, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv(40, 2, "(подпись)", 0, 0, 'C', 0);
        $this->pdf->Cell(5, 2, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv(0, 2, "(расшифровка подписи)", 0, 1, 'C', 0);
        $this->pdf->SetFont('', '', 7);

        $this->pdf->lMargin = 140;
        $this->pdf->rMargin = 5;
        $this->pdf->SetY(5);
        $this->pdf->Ln();

        $this->pdf->MultiCellIconv(0, 4, $firm_vars['firm_name'], 'B', 'L', 0);

        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv(0, 2, "организация", 0, 1, 'C', 0);

        $this->pdf->SetFont('', '', 14);
        $this->pdf->CellIconv(0, 12, "Квитанция", 0, 1, 'C', 0);

        $this->pdf->SetFont('', '', 7);
        $this->pdf->CellIconv(40, 4, "К приходно-кассовому ордеру №", 0, 0, 'L', 0);
        $this->pdf->Cell(0, 4, $doc_data['altnum'], 'B', 1, 'C', 0);

        $date = date("d.m.Y", $doc_data['date']);
        $this->pdf->CellIconv(0, 4, "От $date", 'B', 1, 'L', 0);

        $this->pdf->CellIconv(20, 4, "Принято от", 0, 0, 'L', 0);
        $this->pdf->Cell(0, 4, '', 'B', 1, 'L', 0);

        $y = $this->pdf->GetY();
        $this->pdf->Cell(0, 4, '', 'B', 1, 'L', 0);
        $this->pdf->Cell(0, 4, '', 'B', 1, 'L', 0);
        $this->pdf->SetY($y);
        $this->pdf->MultiCellIconv(0, 4, $agent_info['fullname'], 'B', 'L', 0);
        $this->pdf->SetY($y + 8);
        $this->pdf->CellIconv(20, 4, "Основание:", 0, 0, 'L', 0);
        $this->pdf->Cell(0, 4, '', 'B', 1, 'L', 0);
        $y = $this->pdf->GetY();
        $this->pdf->Cell(0, 4, '', 'B', 1, 'L', 0);
        $this->pdf->Cell(0, 4, '', 'B', 1, 'L', 0);
        $this->pdf->SetY($y);
        $this->pdf->MultiCellIconv(0, 4, $str_osn, 0, 'L', 0);
        $this->pdf->SetY($y + 8);

        $this->pdf->CellIconv(10, 4, "Сумма", 0, 0, 'L', 0);
        $this->pdf->CellIconv(30, 4, $sum_r, 'B', 0, 'R', 0);
        $this->pdf->CellIconv(10, 4, "руб.", 0, 0, 'C', 0);
        $this->pdf->CellIconv(5, 4, $sum_c, 'B', 0, 'L', 0);
        $this->pdf->CellIconv(0, 4, "коп.", 0, 1, 'L', 0);
        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv(0, 2, "цифрами", 0, 1, 'C', 0);
        $this->pdf->SetFont('', '', 7);


        $str = iconv('UTF-8', 'windows-1251', num2str($doc_data['sum']));
        $y = $this->pdf->GetY();
        $this->pdf->Cell(0, 4, '', 'B', 1, 'L', 0);
        $this->pdf->Cell(0, 4, '', 'B', 1, 'L', 0);
        $this->pdf->SetY($y);
        $this->pdf->MultiCell(0, 4, $str, 0, 'L', 0);
        $this->pdf->SetY($y + 8);


        $this->pdf->SetFont('', '', 5);
        $this->pdf->CellIconv(0, 2, "прописью", 0, 1, 'C', 0);
        $this->pdf->SetFont('', '', 7);

        $this->pdf->CellIconv(20, 4, "В том числе", 0, 0, 'L', 0);
        $this->pdf->Cell(0, 4, '', 'B', 1, 'L', 0);

        $date = date("d.m.Y", $doc_data['date']);
        $this->pdf->CellIconv(0, 6, $date, 0, 1, 'L', 0);
        $this->pdf->CellIconv(0, 6, "МП (штампа)", 0, 1, 'C', 0);

        $this->pdf->Ln(3);
        $this->pdf->CellIconv(14, 4, "Бухгалтер", 0, 0, 'L', 0);
        $this->pdf->Cell(20, 4, '', 'B', 0, 'L', 0);
        $this->pdf->Cell(5, 4, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv(0, 4, $firm_vars['firm_buhgalter'], 'B', 1, 'L', 0);

        $this->pdf->SetFont('', '', 5);
        $this->pdf->Cell(14, 2, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv(20, 2, "(подпись)", 0, 0, 'C', 0);
        $this->pdf->Cell(5, 2, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv(0, 2, "(расшифровка подписи)", 0, 1, 'C', 0);
        $this->pdf->SetFont('', '', 7);

        $res = $db->query("SELECT `worker_real_name` FROM `users_worker_info` WHERE `user_id`='{$doc_data['user']}'");
        if ($res->num_rows) {
            $worker_info = $res->fetch_assoc();
            $name = $worker_info['worker_real_name'];
        } else {
            $name = $firm_vars['firm_buhgalter'];
        }

        $this->pdf->CellIconv(10, 4, "Кассир", 0, 0, 'L', 0);
        $this->pdf->Cell(20, 4, '', 'B', 0, 'L', 0);
        $this->pdf->Cell(5, 4, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv(0, 4, $name, 'B', 1, 'L', 0);

        $this->pdf->SetFont('', '', 5);
        $this->pdf->Cell(10, 2, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv(20, 2, "(подпись)", 0, 0, 'C', 0);
        $this->pdf->Cell(5, 2, '', 0, 0, 'L', 0);
        $this->pdf->CellIconv(0, 2, "(расшифровка подписи)", 0, 1, 'C', 0);
    }

}
