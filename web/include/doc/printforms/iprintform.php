<?php
//	MultiMag v0.2 - Complex sales system
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

namespace doc\printforms; 

/// Абстрактный класс печатной формы
abstract class iPrintForm {
    protected $doc;   //< Ссылка на документ с данными для печати
    protected $pdf;   //< Объект FPDF
    
    // Параметы форм
    protected $line_normal_w = 0.25;   // Стандартная толщина линии
    protected $line_bold_w = 0.6;   // Толщина жирной линии
    protected $line_thin_w = 0.18;   // Толщина тонкой линии
    
    
    /// Установить ссылку на распечатываемый документ
    public function setDocument($doc) {
        $this->doc = $doc;
    }
             
    /// Инициализация модуля вывода данных
    public function initForm() {
        require('fpdf/fpdf_mc.php');
        $this->pdf = new \PDF_MC_Table();
        $this->pdf->Open();
        $this->pdf->SetAutoPageBreak(1, 5);
        $this->pdf->AddFont('Arial', '', 'arial.php');
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->tMargin = 5;
        $this->pdf->SetFillColor(255);
    }
    
    protected function addInfoFooter() {
        $x = $this->pdf->getX();
        $y = $this->pdf->getY();
        $this->pdf->SetX($this->pdf->rMargin - 50);
        $this->pdf->SetY($this->pdf->h - $this->pdf->bMargin - $this->pdf->tMargin);
        $this->pdf->SetFont('Arial', '', 2);
        $str = 'Подготовлено в multimag v:'.MULTIMAG_VERSION.' ('.get_class($this).')';
        $this->pdf->CellIconv(0, 4, $str, 0, 0, 'R');
        $this->pdf->SetX($x);
        $this->pdf->SetY($y);
    }


    /// Вывод данных
    /// @param $to_str Если истина - вернёт буфер с данными. Иначе - вывод в файл.
    public function outData($to_str=false) {
        $fname = get_class($this);
        $matches = null;
        if (preg_match('@\\\\([\w]+)$@', $fname, $matches)) {
            $fname = $matches[1];
        }
        
        if ($to_str) {
            return $this->pdf->Output($fname.'.pdf', 'S');
        }
        else {
            $this->pdf->Output($fname.'.pdf', 'I');
        }
    }
    
    /// Сформировать данные печатной формы
    abstract public function make();
}