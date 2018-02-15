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

namespace doc\printforms\realizaciya;

class label extends \doc\printforms\iPrintFormPdf {

    protected $show_agent = 0;  ///< Выводить ли информацию о агенте-покупателе
    protected $show_disc = 1;   ///< Выводить ли информацию о скидках
    protected $show_kkt = 1;    ///< Выводить ли информацию о работе без использования ККТ

    /// Возвращает имя документа
    public function getName() {
        return "Транспортные этикетки";
    }

    /// Сформировать данные печатной формы
    public function make() {
        global $db;
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $firm_vars = $this->doc->getFirmVarsA();
        
        $this->pdf->AddPage('P');
        $this->addTechFooter();

        $gruzop_info = new \models\agent($dop_data['gruzop']);
        $gruzop = '';
        if ($gruzop_info) {
            if ($gruzop_info->fullname) {
                $gruzop.=$gruzop_info->fullname;
            } else {
                $gruzop.=$gruzop_info->name;
            }
            if ($gruzop_info->inn) {
                $gruzop.=', ИНН ' . $gruzop_info->inn;
            }
            if ($gruzop_info->adres) {
                $gruzop.=', адрес ' . $gruzop_info->adres;
            }
            if ($gruzop_info->getPhone()) {
                $gruzop.=', тел. ' . $gruzop_info->getPhone();
            }
        } else {
            $gruzop = 'не задан';
        }

        $maker = '';
        if ($dop_data['kladovshik']) {
            $res = $db->query("SELECT `worker_real_name`, `worker_phone`, `worker_email`, `worker_post_name` FROM `users_worker_info` WHERE `user_id`='{$dop_data['kladovshik']}'");

            if ($res->num_rows) {
                $author_info = $res->fetch_assoc();

                $maker = $author_info['worker_real_name'];
                if ($author_info['worker_phone']) {
                    $maker .= ", тел: " . $author_info['worker_phone'];
                }
                if ($author_info['worker_email']) {
                    $maker .= ", email: " . $author_info['worker_email'];
                }
            }
        } else {
            throw new \Exception("Кладовщик не задан");
        }

        $pack_cnt = $dop_data['mest'];

        $this->pdf->SetFont('Arial', '', 10);
        $str = "Этикетки к накладной N {$doc_data['altnum']}{$doc_data['subtype']}, от " . date("d.m.Y", $doc_data['date']);
        $this->pdf->CellIconv(0, 6, $str, 0, 1, 'C');
        $this->pdf->ln(10);

        $this->pdf->SetMargins(15, 15, 15);
        $this->pdf->SetFont('', '', 12);
        $this->pdf->SetLineWidth(0.2);
        $cell_height = 0;
        for ($c = 1; $c <= $pack_cnt; $c++) {
            if($c>1) {
                $rest = $this->pdf->h - $this->pdf->bMargin - $this->pdf->y - 5;
                if($rest<$cell_height) {
                    $this->pdf->addPage();
                }
            }
            $start = $this->pdf->y - 5;
            $this->pdf->ln(0);
            $str = "Отправитель: {$firm_vars['firm_gruzootpr']}, ИНН: {$firm_vars['firm_inn']}, тел.: {$firm_vars['firm_telefon']}";
            $this->pdf->MultiCellIconv(0, 4.5, $str, 0, 'L');

            $this->pdf->ln(2);
            $str = "Грузополучатель: " . $gruzop;
            $this->pdf->MultiCellIconv(0, 4.5, $str, 0, 'L');

            $this->pdf->ln(2);
            $str = "Комплектовщик: " . $maker;
            $this->pdf->MultiCellIconv(0, 4.5, $str, 0, 'L');

            $this->pdf->ln(2);
            $str = "Место: $c. Всего мест: $pack_cnt. Упаковано: " . date("d.m.Y H:i") . ". Накладная {$doc_data['altnum']}{$doc_data['subtype']}, от " . date("d.m.Y", $doc_data['date']);
            $this->pdf->MultiCellIconv(0, 4.5, $str, 0, 'L');

            $this->pdf->ln(5);
            $end = $this->pdf->y;
            if($c==1) {
                $cell_height = $end - $start;
            }
            $this->pdf->Rect(10, $start, 190, $end - $start);
            $this->pdf->Rect(9, $start - 1, 192, $end - $start + 2);
            $this->pdf->ln(10);
        }

    }

}
