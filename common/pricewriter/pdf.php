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
namespace pricewriter;

/// Класс формирует прайс-лист в формате PDF
class pdf extends BasePriceWriter {

    var $pdf;

    /// Конструктор
    function __construct($db) {
        parent::__construct($db);
        $this->line = 0;
    }

    /// Сформировать шапку прайса
    function open() {
        global $CONFIG;
        $pref = \pref::getInstance();
        require_once('fpdf/fpdf_mysql.php');
        $this->pdf = new \PDF_MySQL_Table();
        $this->pdf->Open();
        $this->pdf->SetAutoPageBreak(1, 12);
        $this->pdf->AddFont('Arial', '', 'arial.php');
        $this->pdf->tMargin = 5;
        $this->pdf->AddPage();
        if (@$CONFIG['site']['doc_header']) {
            $header_img = str_replace('{FN}', $pref->site_default_firm_id, $CONFIG['site']['doc_header']);
            $this->pdf->Image($header_img, 8, 10, 190);
            $this->pdf->Sety(54);
        }

        $i = 0;
        if (is_array($CONFIG['site']['price_text'])) {
            foreach ($CONFIG['site']['price_text'] as $text) {
                $this->pdf->SetFont('Arial', '', 20 - ($i * 4));
                $str = iconv('UTF-8', 'windows-1251', $text);
                $this->pdf->Cell(0, 7 - $i, $str, 0, 1, 'C');
                $i++;
                if ($i > 4) {
                    $i = 4;
                }
            }
        }

        $this->pdf->SetTextColor(0, 0, 255);
        $this->pdf->SetFont('', 'U', 14);
        $str = 'Прайс загружен с сайта http://' . $pref->site_name;
        $str = iconv('UTF-8', 'windows-1251', $str);
        $this->pdf->Cell(0, 6, $str, 0, 1, 'C', 0, 'http://' . $pref->site_name);
        $this->pdf->SetFont('', '', 10);
        $this->pdf->SetTextColor(0);
        $str = 'При заказе через сайт может быть предоставлена скидка!';
        $str = iconv('UTF-8', 'windows-1251', $str);
        $this->pdf->Cell(0, 5, $str, 0, 1, 'C');

        $dt = date("d.m.Y");
        $str = 'Цены действительны на дату: ' . $dt . '.';
        if (@$CONFIG['site']['grey_price_days']) {
            $str .= ' Цены, выделенные серым цветом, необходимо уточнять.';
        }
        $str = iconv('UTF-8', 'windows-1251', $str);
        $this->pdf->Cell(0, 4, $str, 0, 1, 'C');

        if (is_array($this->view_groups)) {
            $this->pdf->Ln(3);
            $this->pdf->SetFont('', '', 14);
            $this->pdf->SetTextColor(255, 24, 24);
            $str = 'Прайс содержит неполный список позиций, в соответствии с выбранными критериями при его загрузке с сайта.';
            $str = iconv('UTF-8', 'windows-1251', $str);
            $this->pdf->MultiCell(0, 4, $str, 0, 'C');
        }
        $this->pdf->Ln(6);

        $this->pdf->SetTextColor(0);
    }

    /// Сформирвать тело прайса
    /// TODO: здесь количество столбцов задаётся в конфиге, а в других прайсах - параметр самого прайса. привести к единообразию
    function write() {
        global $CONFIG;
        if (!isset($CONFIG['site']['price_width_vc'])) {
            $CONFIG['site']['price_width_vc'] = 0;
        }
        if (!$CONFIG['site']['price_col_cnt']) {
            $CONFIG['site']['price_col_cnt'] = 2;
        }
        if (!$CONFIG['site']['price_width_cost']) {
            $CONFIG['site']['price_width_cost'] = 16;
        }
        if (!$CONFIG['site']['price_width_name']) {
            $CONFIG['site']['price_width_name'] = (194 - $CONFIG['site']['price_width_cost'] * $CONFIG['site']['price_col_cnt'] - $CONFIG['site']['price_width_vc'] * $CONFIG['site']['price_col_cnt'] - $CONFIG['site']['price_col_cnt'] * 2) / $CONFIG['site']['price_col_cnt'];
            settype($CONFIG['site']['price_width_name'], 'int');
        }

        $this->pdf->numCols = $CONFIG['site']['price_col_cnt'];

        if ($CONFIG['site']['price_show_vc']) {
            $str = iconv('UTF-8', 'windows-1251', 'Код');
            $this->pdf->AddCol('vc', $CONFIG['site']['price_width_vc'], $str, '');
        }

        $str = iconv('UTF-8', 'windows-1251', 'Наименование');
        $this->pdf->AddCol('name', $CONFIG['site']['price_width_name'], $str, '');
        $str = iconv('UTF-8', 'windows-1251', 'Цена');
        $this->pdf->AddCol('cost', $CONFIG['site']['price_width_cost'], $str, 'R');
        $prop = array('HeaderColor' => array(255, 150, 100),
            'color1' => array(210, 245, 255),
            'color2' => array(255, 255, 210),
            'padding' => 1,
            'cost_id' => $this->cost_id);
        if (is_array($this->view_groups)) {
            $prop['groups'] = $this->view_groups;
        }



        if ($this->view_proizv) {
            $proizv = '`doc_base`.`proizv`';
        } else {
            $proizv = "''";
        }

        $this->pdf->Table("SELECT `doc_base`.`name`, $proizv, `doc_base`.`id` AS `pos_id` , `doc_base`.`cost_date`, `class_unit`.`rus_name1` AS `units_name`, `doc_base`.`vc`
		FROM `doc_base`
		LEFT JOIN `class_unit` ON `class_unit`.`id`=`doc_base`.`unit` ", $prop);
    }

    /// Сформировать завершающий блок прайса
    function close() {
        $this->pdf->Output();
    }

    /// Сформировать строки прайса
    /// param $group id номенклатурной группы
    /// param $group_name Отображаемое имя номенклатурной группы
    function writepos($group = 0, $group_name = '') {
        
    }

}
