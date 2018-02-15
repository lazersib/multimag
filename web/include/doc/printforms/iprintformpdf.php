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

namespace doc\printforms; 

/// Абстрактный класс печатной формы
abstract class iPrintFormPdf {
    protected $doc;   //< Ссылка на документ с данными для печати
    protected $pdf;   //< Объект FPDF
    
    // Параметы форм
    protected $line_normal_w = 0.25;   // Стандартная толщина линии
    protected $line_bold_w = 0.6;   // Толщина жирной линии
    protected $line_thin_w = 0.18;   // Толщина тонкой линии
    
    public $mime = "application/pdf";
    
    /// Установить ссылку на распечатываемый документ
    public function setDocument($doc) {
        $this->doc = $doc;
    }
    
    public function getMimeType() {
        return $this->mime;
    }
             
    /// Инициализация модуля вывода данных
    public function initForm() {
        require('fpdf/fpdf_mc.php');
        $this->pdf = new \PDF_MC_Table('P');
        $this->pdf->Open();
        $this->pdf->SetAutoPageBreak(1, 5);
        $this->pdf->AddFont('Arial', '', 'arial.php');
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->tMargin = 5;
        $this->pdf->SetFillColor(255);
    }
    
    /// Добавить к документу футер с технической информацией
    protected function addTechFooter() {
        $x = $this->pdf->getX();
        $y = $this->pdf->getY();
        $this->pdf->SetX($this->pdf->rMargin - 50);
        $this->pdf->SetY($this->pdf->h - $this->pdf->bMargin - $this->pdf->tMargin);
        $old_font_size = $this->pdf->FontSizePt;
        $this->pdf->SetFontSize(2.5);
        $str = 'Подготовлено в multimag v:'.MULTIMAG_VERSION.' ('.get_class($this).'), док.'.$this->doc->getId();
        $this->pdf->CellIconv(0, 2, $str, 0, 1, 'R');
        $this->pdf->SetFontSize($old_font_size);
        $this->pdf->SetX($x);
        $this->pdf->SetY($y);
    }
    
    /// Добавить стандартный заголовок формы
    protected function addHeader($text) {
        $this->pdf->SetFontSize(18);
        $this->pdf->MultiCellIconv(0, 8, $text, 0, 'C');
    }
    
    /// Добавить уменьшенный заголовок
    protected function addMiniHeader($text) {
        $this->pdf->SetFontSize(16);
        $this->pdf->MultiCellIconv(0, 8, $text, 0, 'C');
    }
    
    /// Добавить стандартную информационную строку
    protected function addInfoLine($text, $font_size = 10) {
        $this->pdf->SetFontSize($font_size);
        $this->pdf->MultiCellIconv(0, 5, $text, 0, 'L');
    }
    
    /// Добавить стандартную строку подписи
    protected function addSignLine($text) {
        $this->pdf->SetFontSize(10);
        $this->pdf->CellIconv(0, 7, $text, 0, 1, 'L');
    }
    
    /// Добавить стандартный заголовок таблицы
    protected function addTableHeader($th_widths, $th_texts, $tbody_aligns = null) {
        $this->pdf->SetFontSize(10);
        $this->pdf->SetLineWidth($this->line_bold_w);
        foreach ($th_widths as $id => $w) {
            $this->pdf->CellIconv($w, 6, $th_texts[$id], 1, 0, 'C', 0);
        }        
        $this->pdf->Ln();
        $this->pdf->SetWidths($th_widths);
        $this->pdf->SetHeight(4);
        $this->pdf->SetLineWidth($this->line_normal_w);
        if($tbody_aligns) {
            $this->pdf->SetAligns($tbody_aligns);
        }
        $this->pdf->SetFontSize(8);
    }
    
    /// Добавить изображение шапки
    protected function addHeadBanner($firm_id) {
        global $CONFIG;
        if (@$CONFIG['site']['doc_header']) {
            $header_img = str_replace('{FN}', $firm_id, $CONFIG['site']['doc_header']);
            $size = getimagesize($header_img);
            if (!$size) {
                throw new \Exception("Не удалось открыть файл изображения");
            }
            if ($size[2] != IMAGETYPE_JPEG) {
                throw new Exception("Файл изображения не в jpeg формате");
            }
            if ($size[0] < 800) {
                throw new \Exception("Разрешение изображения слишком мало! Допустимя ширина - не менее 800px");
            }
            $width = 190;
            $offset_y = $size[1] / $size[0] * $width + 14;
            $this->pdf->Image($header_img, 8, 10, $width);
            $this->pdf->Sety($offset_y);
        }
    }
    
    /// Добавить информацию о сайте
    protected function addSiteBanner() {
        global $CONFIG;
        $pref = \pref::getInstance();
        $this->pdf->SetFontSize(12);
        $str = "Система интернет-заказов для постоянных клиентов доступна на нашем сайте";
        $this->pdf->CellIconv(0, 5, $str, 0, 1, 'C', 0);

        $this->pdf->SetTextColor(0, 0, 192);
        $this->pdf->SetFont('', 'UI', 18);
        $this->pdf->Cell(0, 7, 'http://' . $pref->site_name, 0, 1, 'C', 0, 'http://' . $pref->site_name);

        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('', '', 12);
        $str = "При оформлении заказа через сайт предоставляются специальные цены!";
        $this->pdf->CellIconv(0, 8, $str, 0, 1, 'C', 0);
    }
    
    /// Добавить информацию о сторуднике в нижний правый угол страницы
    protected function addWorkerInfo($doc_data) {
        global $db;
        $footer_info = array();
        $res = $db->query("SELECT `worker_real_name`, `worker_post_name`, `worker_phone`, `worker_email`, `worker_jid`"
            . " FROM `users_worker_info`"
            . " WHERE `user_id`='{$doc_data['user']}'");
        if ($res->num_rows) {
            $worker_info = $res->fetch_assoc();
            if ($worker_info['worker_post_name']) {
                $footer_info[] = $worker_info['worker_post_name'] . ' ' . $worker_info['worker_real_name'];
            } else {
                $footer_info[] = "Сотрудник " . $worker_info['worker_real_name'];
            }
            if ($worker_info['worker_phone']) {
                $footer_info[] = "Контактный телефон: " . $worker_info['worker_phone'];
            }
            if ($worker_info['worker_email']) {
                $footer_info[] = "email адрес: " . $worker_info['worker_email'];
            }
            if ($worker_info['worker_jid']) {
                $footer_info[] = "Идентификатор jabber/xmpp: " . $worker_info['worker_jid'];
            }
        }
        else {       
            $footer_info[] = "Login автора: " . $_SESSION['name'];
        } 
        $line_width = 4;  
        $this->pdf->SetAutoPageBreak(0, 10);
        $this->pdf->SetFontSize(10);
        $this->pdf->SetY($this->pdf->h - 5 - count($footer_info)*$line_width);
        $this->pdf->Ln(1);
        
        foreach($footer_info as $text) {            
            $this->pdf->CellIconv(0, 4, $text, 0, 1, 'R', 0);
        }
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
    
    /// Возвращает имя документа
    abstract public function getName();
}