<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
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


class Report_dc_book extends BaseReport {
    var $xls_book;      // книга XLS
    var $xls_sheet;     // Лист XLS
    var $xls_line;      // Текущая строка XLS
    var $xls_formats;   // Форматы для XLS
    var $xls_cols;
    
    function getName($short = 0) {
        if ($short) {
            return "Книга доходов и расходов (XLS)";
        } else {
            return "Книга учета доходов и расходов (XLS)";
        }
    }

    function Form() {
        global $tmpl, $db;
        $date_st = date("Y-m-01");
        $date_end = date("Y-m-d");
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <form action='' method='get'>
            <input type='hidden' name='mode' value='dc_book'>
            <input type='hidden' name='opt' value='make'>
            Выберите фирму:<br>
            <select name='firm_id'>");
        $res = $db->query("SELECT `id`, `firm_name` FROM `doc_vars` ORDER BY `id`");
        while ($nxt = $res->fetch_row()) {
            $tmpl->addContent("<option value='$nxt[0]'>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select><br>
            Год формирования:<br>
            <input type='text' name='year' value='".date("Y")."'><br>
            <button type='submit'>Создать отчет</button></form>");
    }
    
    function createXLS() {
        require_once('include/Spreadsheet/Excel/Writer.php');
        global $CONFIG;
        $this->xls_book = new \Spreadsheet_Excel_Writer();
        $this->xls_line = 0;
        $this->xls_cols = 10;
        $normal_font_size = 9;
        
        $xf = $this->xls_book->addFormat();
        $xf->SetSize(7);
        $xf->SetHAlign('right');
        $xf->SetVAlign('vcenter');        
        $this->xls_formats['htitle'] = $xf;
                
        $xf = $this->xls_book->addFormat();
        $xf->SetSize(20);
        $xf->SetHAlign('center');
        $xf->SetVAlign('vcenter');        
        $this->xls_formats['head'] = $xf;     
        
        $xf = $this->xls_book->addFormat();
        $xf->SetSize(14);
        $xf->SetHAlign('center');
        $xf->SetVAlign('vcenter');        
        $this->xls_formats['mhead'] = $xf;
        
        $xf = $this->xls_book->addFormat();
        $xf->SetSize(12);
        $xf->SetHAlign('center');
        $xf->SetVAlign('vcenter');   
        $xf->setBottom(1);
        $this->xls_formats['ulcenterbig'] = $xf;
        
        $xf = $this->xls_book->addFormat();
        $xf->SetSize($normal_font_size);
        $xf->SetHAlign('left');
        $xf->SetVAlign('center');        
        $this->xls_formats['ltext'] = $xf;
        
        $xf = $this->xls_book->addFormat();
        $xf->SetSize($normal_font_size);
        $xf->SetHAlign('right');
        $xf->SetVAlign('vcenter');        
        $this->xls_formats['rtext'] = $xf;
        
        $xf = $this->xls_book->addFormat();
        $xf->SetSize($normal_font_size);
        $xf->SetHAlign('center');
        $xf->SetVAlign('vcenter');        
        $xf->setBorder(1);
        $xf->setTextWrap();
        $xf->setNumFormat('@');
        $this->xls_formats['border'] = $xf;   
        
        $xf = $this->xls_book->addFormat();
        $xf->SetSize($normal_font_size);
        $xf->SetHAlign('center');
        $xf->SetVAlign('vcenter');        
        $xf->setBottom(1);
        $xf->setTop(5);
        $xf->setLeft(5);
        $xf->setRight(5);        
        $this->xls_formats['topsideboldcell'] = $xf;    
        
        $xf = $this->xls_book->addFormat();
        $xf->SetSize($normal_font_size);
        $xf->SetHAlign('center');
        $xf->SetVAlign('vcenter');        
        $xf->setTop(1);
        $xf->setBottom(5);
        $xf->setLeft(5);
        $xf->setRight(5);        
        $this->xls_formats['bodsideboldcell'] = $xf;
        
        $xf = $this->xls_book->addFormat();
        $xf->SetSize($normal_font_size);
        $xf->SetHAlign('center');
        $xf->SetVAlign('vcenter');        
        $xf->setBorder(1);
        $xf->setLeft(5);
        $xf->setRight(5);
        $this->xls_formats['sideboldcell'] = $xf;
        
        $xf = $this->xls_book->addFormat();
        $xf->SetSize($normal_font_size);
        $xf->SetHAlign('center');
        $xf->SetVAlign('vcenter');        
        $xf->setBorder(1);
        $xf->setLeft(5);
        $this->xls_formats['leftbold'] = $xf;
        
        $xf = $this->xls_book->addFormat();
        $xf->SetSize($normal_font_size);
        $xf->SetHAlign('center');
        $xf->SetVAlign('vcenter');        
        $xf->setBorder(1);
        $xf->setRight(5);
        $this->xls_formats['rightbold'] = $xf;
        
        $xf = $this->xls_book->addFormat();
        $xf->SetSize(7);
        $xf->SetHAlign('center');
        $xf->SetVAlign('top');        
        $this->xls_formats['mcentertext'] = $xf;
        
        $xf = $this->xls_book->addFormat();
        $xf->SetSize($normal_font_size);
        $xf->SetHAlign('center');
        $xf->SetVAlign('vcenter');        
        $xf->setBorder(2);
        $xf->setTextWrap();
        $xf->setNumFormat('@');
        $this->xls_formats['boldborder'] = $xf;
        
        $xf = $this->xls_book->addFormat();
        $xf->SetSize($normal_font_size);
        $xf->SetHAlign('right');
        $xf->SetVAlign('vcenter');        
        $xf->setBorder(1);
        $xf->setTextWrap();
        $xf->setNumFormat('@');
        $this->xls_formats['rborder'] = $xf;
        
        $xf = $this->xls_book->addFormat();
        $xf->SetSize($normal_font_size);
        $xf->SetHAlign('right');
        $xf->SetVAlign('vcenter');        
        $xf->setBorder(1);
        $xf->setTextWrap();
        $xf->setNumFormat('### ### ##0.00');
        $this->xls_formats['sum'] = $xf;
        
        $xf = $this->xls_book->addFormat();
        $xf->SetSize($normal_font_size);
        $xf->SetHAlign('right');
        $xf->SetVAlign('vcenter');        
        $xf->setBorder(2);
        $xf->setTextWrap();
        $xf->setNumFormat('### ### ##0.00');
        $this->xls_formats['boldbordersum'] = $xf;
    }
    
    function closeXLS() {
        // sending HTTP headers
        $this->xls_book->send('dc_book.xls');
        $this->xls_book->close();
    }
    
    function addMergedLineXLS($text, $format) {
        $this->xls_sheet->write($this->xls_line, 0, $text, $format);
        $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, $this->xls_cols - 1);
        $this->xls_line++;
    }
    
    function addTitleListXLS($year, $firm_id) {
        global $db;
        settype($year, 'int');
        settype($firm_id, 'int');
        $res = $db->query("SELECT `id`, `firm_name`, `firm_inn`, `firm_director`, `firm_okpo`, `firm_adres` FROM `doc_vars` WHERE `id`=$firm_id");
        if($res->num_rows) {
            $firm_info = $res->fetch_assoc();
        } else {
            throw new Exception("Организация не найдена");
        }
        $bank_list = array();
        $res = $db->query("SELECT `name`, `bik`, `rs` FROM `doc_kassa` WHERE `ids`='bank' AND `firm_id`=$firm_id");
        while($line = $res->fetch_assoc()) {
            $bank_list[] = $line;
        }

        // Creating a worksheet
        $this->xls_sheet = $this->xls_book->addWorksheet('title');
        $this->xls_sheet->setInputEncoding('utf-8');
        $this->xls_line = 0;
        $this->xls_cols = 10;
        
        $text = "Приложение № 1  к Приказу Министерства финансов   Российской Федерации  от 22.10.2012 № 135н";
        $this->addMergedLineXLS($text, $this->xls_formats['htitle']);
        $text = "Книга";
        $this->addMergedLineXLS($text, $this->xls_formats['head']);
        $text = "учета доходов и расходов организаций и индивидуальных предпринимателей,";
        $this->addMergedLineXLS($text, $this->xls_formats['mhead']);
        $text = "применяющих упрощенную систему налогообложения";
        $this->addMergedLineXLS($text, $this->xls_formats['mhead']);
        $text = "на $year год.";
        $this->addMergedLineXLS($text, $this->xls_formats['mhead']);
        
        
        $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, $this->xls_cols - 4);
        $text = "Коды";
        $this->xls_sheet->setMerge($this->xls_line, $this->xls_cols - 3, $this->xls_line, $this->xls_cols - 1);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 3, $text, $this->xls_formats['border']);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 1, '', $this->xls_formats['border']);
        $this->xls_line++;
        
        $text = "Форма по ОКУД";
        $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, $this->xls_cols - 4);
        $this->xls_sheet->write($this->xls_line, 0, $text, $this->xls_formats['rtext']);
        $this->xls_sheet->setMerge($this->xls_line, $this->xls_cols - 3, $this->xls_line, $this->xls_cols - 1);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 3, '', $this->xls_formats['topsideboldcell']);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 1, '', $this->xls_formats['topsideboldcell']);
        $this->xls_line++;
        
        $text = "Дата (год, месяц, число)";
        $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, $this->xls_cols - 4);
        $this->xls_sheet->write($this->xls_line, 0, $text, $this->xls_formats['rtext']);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 3, date("Y"), $this->xls_formats['leftbold']);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 2, date("m"), $this->xls_formats['border']);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 1, date("d"), $this->xls_formats['rightbold']);
        $this->xls_line++;
        
        $text = "Налогоплательщик (наименование организации/фамилия, имя, отчество индивидуального предпринимателя)";
        $this->xls_sheet->write($this->xls_line, 0, $text, $this->xls_formats['ltext']);
        $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, $this->xls_cols - 4);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 3, $firm_info['firm_okpo'], $this->xls_formats['topsideboldcell']);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 1, '', $this->xls_formats['topsideboldcell']);
        $this->xls_sheet->setMerge($this->xls_line, $this->xls_cols - 3, $this->xls_line+1, $this->xls_cols - 1);
        $this->xls_line++;
        
        $text = $firm_info['firm_name'];
        $this->xls_sheet->write($this->xls_line, 0, $text, $this->xls_formats['ulcenterbig']);
        $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, $this->xls_cols - 5);
        $text = "по ОКПО";
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 4, $text, $this->xls_formats['rtext']);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 3, '', $this->xls_formats['sideboldcell']);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 1, '', $this->xls_formats['sideboldcell']);
        $this->xls_line++;
        
        $text = "Идентификационный номер налогоплательщика - организации/";
        $this->xls_sheet->write($this->xls_line, 0, $text, $this->xls_formats['ltext']);
        $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, $this->xls_cols - 4);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 3, '', $this->xls_formats['sideboldcell']);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 1, '', $this->xls_formats['sideboldcell']);
        $this->xls_sheet->setMerge($this->xls_line, $this->xls_cols - 3, $this->xls_line+4, $this->xls_cols - 1);
        $this->xls_line++;    
        
        $text = "код причины постановки на учет в налоговом органе (ИНН/КПП)";
        $this->xls_sheet->write($this->xls_line, 0, $text, $this->xls_formats['ltext']);
        $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, $this->xls_cols - 4);
        $this->xls_line++;
        
        $text = $firm_info['firm_inn'];
        $this->xls_sheet->write($this->xls_line, 0, $text, $this->xls_formats['ulcenterbig']);
        $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, $this->xls_cols - 5);
        $this->xls_line++;
        
        $text = "Идентификационный номер налогоплательщика — индивидуального предпринимателя (ИНН)";
        $this->xls_sheet->write($this->xls_line, 0, $text, $this->xls_formats['ltext']);
        $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, $this->xls_cols - 4);
        $this->xls_line++;
        
        $text = $firm_info['firm_inn'];
        $this->xls_sheet->write($this->xls_line, 0, $text, $this->xls_formats['ulcenterbig']);
        $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, $this->xls_cols - 5);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 3, '', $this->xls_formats['sideboldcell']);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 1, '', $this->xls_formats['sideboldcell']);
        $this->xls_line++;
        
        $text = "Объект налогообложения";
        $this->xls_sheet->write($this->xls_line, 0, $text, $this->xls_formats['ltext']);
        $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, $this->xls_cols - 9);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 8, '', $this->xls_formats['ulcenterbig']);
        $this->xls_sheet->setMerge($this->xls_line, $this->xls_cols - 8, $this->xls_line, $this->xls_cols - 5);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 3, '', $this->xls_formats['sideboldcell']);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 1, '', $this->xls_formats['sideboldcell']);
        $this->xls_sheet->setMerge($this->xls_line, $this->xls_cols - 3, $this->xls_line+3, $this->xls_cols - 1);
        $this->xls_line++;
        
        $text = "(наименование выбранного объекта налогообложения";
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 8, $text, $this->xls_formats['mcentertext']);
        $this->xls_sheet->setMerge($this->xls_line, $this->xls_cols - 8, $this->xls_line, $this->xls_cols - 5);
        $this->xls_line++;
        
        $this->xls_sheet->write($this->xls_line, 0, '', $this->xls_formats['ulcenterbig']);
        $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, $this->xls_cols - 5);
        $this->xls_line++;
        
        $text = "в соответствии со статьей 346.14 Налогового кодекса Российской Федерации)";
        $this->xls_sheet->write($this->xls_line, 0, $text, $this->xls_formats['mcentertext']);
        $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, $this->xls_cols - 5);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 3, '', $this->xls_formats['sideboldcell']);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 1, '', $this->xls_formats['sideboldcell']);
        $this->xls_line++;
        
        $text = "Единица измерения: руб.";
        $this->xls_sheet->write($this->xls_line, 0, $text, $this->xls_formats['ltext']);        
        $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, $this->xls_cols - 5);
        $text = "по ОКЕИ";
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 5, $text, $this->xls_formats['ltext']);  
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 3, '383', $this->xls_formats['bodsideboldcell']);
        $this->xls_sheet->write($this->xls_line, $this->xls_cols - 1, '383', $this->xls_formats['bodsideboldcell']);
        $this->xls_sheet->setMerge($this->xls_line, $this->xls_cols - 3, $this->xls_line, $this->xls_cols - 1);
        $this->xls_line++;
        $this->xls_line++;
        
        $text = "Адрес места нахождения организации (места жительства индивидуального предпринимателя)";
        $this->addMergedLineXLS($text, $this->xls_formats['ltext']);         
        $this->addMergedLineXLS($firm_info['firm_adres'], $this->xls_formats['ulcenterbig']);         
        $text = "Номера расчетных и иных счетов, открытых в учреждениях банков";
        $this->addMergedLineXLS($text, $this->xls_formats['ltext']);          
        foreach($bank_list as $line) {
            $text = "Р/С {$line['rs']} в банке {$line['name']}, БИК:{$line['bik']}";
            $this->addMergedLineXLS($text, $this->xls_formats['ulcenterbig']);        
        }
        $this->xls_sheet->setColumn(0, $this->xls_cols - 5, 14);
        $this->xls_sheet->setColumn($this->xls_cols - 4, $this->xls_cols - 4, 8);
        $this->xls_sheet->setColumn($this->xls_cols - 3, $this->xls_cols - 1, 4); 
        //$this->xls_sheet->printArea(0, 0, $this->xls_line, $this->xls_cols-1);
        $this->xls_sheet->setPrintScale(75);
        $this->xls_sheet->hideGridlines();
    }
    
    function addQuarterList($year, $firm_id, $quarter, $sum_text = '', $prevous = null) {
        global $db;
        settype($year, 'int');
        settype($firm_id, 'int');
        settype($quarter, 'int');
        
        // Creating a worksheet
        $this->xls_sheet = $this->xls_book->addWorksheet($quarter.' quarter');
        $this->xls_sheet->setInputEncoding('utf-8');
        $this->xls_sheet->setPrintScale(80);
        $this->xls_sheet->hideGridlines();
        $this->xls_sheet->setColumn(0, 0, 5);
        $this->xls_sheet->setColumn(1, 1, 25);
        $this->xls_sheet->setColumn(2, 2, 35);
        $this->xls_sheet->setColumn(3, 4, 18);
        $this->xls_line = 0;
        $this->xls_cols = 5;
        
        $text = "I. Доходы и расходы";
        $this->addMergedLineXLS($text, $this->xls_formats['head']);  
        $this->xls_line++;
        $text = "Регистрация";
        $this->xls_sheet->write($this->xls_line, 0, $text, $this->xls_formats['boldborder']);
        $text = "Сумма";
        $this->xls_sheet->write($this->xls_line, 3, $text, $this->xls_formats['boldborder']);
        $this->xls_sheet->write($this->xls_line, 4, $text, $this->xls_formats['boldborder']);
        $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, 2);
        $this->xls_sheet->setMerge($this->xls_line, 3, $this->xls_line, 4);
        $this->xls_line++;
        $lines = array(
            '№ п/п',
            'Дата и номер первичного документа',
            'Содержание операции',
            'Доходы, учитываемые при исчислении налоговой базы',
            'Расходы, учитываемые при исчислении налоговой базы',
        );
        $col = 0;
        foreach($lines as $line) {
            $this->xls_sheet->write($this->xls_line, $col++, $line, $this->xls_formats['boldborder']);
        }
        $this->xls_line++;
        for($col=0;$col<5;$col++) {
            $this->xls_sheet->write($this->xls_line, $col, $col+1, $this->xls_formats['boldborder']);
        }
        $this->xls_line++;
        
        // =======================================================
        $ldodoc = new \Models\LDO\docnames();
        $doc_names = $ldodoc->getData();    
        
        $in_names = array(0 => '(не задано)');
        $out_names = array(0 => '(не задано)');
        $res = $db->query("SELECT `id`, `name` FROM `doc_ctypes` ORDER BY `id`");
        while($line = $res->fetch_assoc()) {
            $in_names[$line['id']] = $line['name'];
        }
        
        $res = $db->query("SELECT `id`, `name`, `adm`, `r_flag` FROM `doc_dtypes` ORDER BY `id`");
        while($line = $res->fetch_assoc()) {
            $out_names[$line['id']] = $line['name'];
        }
        
        $_quarter = $quarter - 1;
        $days = cal_days_in_month(CAL_GREGORIAN, $_quarter*3 + 3, $year);
        $date_st = mktime(0, 0, 0, $_quarter*3 + 1, 1, $year);
        $date_end = mktime(23, 59, 59, $_quarter*3 + 3, $days, $year);
        $res = $db->query("SELECT `doc_list`.`id`,`doc_list`.`type`,`doc_list`.`date`,`doc_list`.`sum`, `doc_list`.`altnum`, 
                `d_table`.`value` AS `out_type`, `c_table`.`value` AS `in_type`
            FROM `doc_list`
            LEFT JOIN `doc_dopdata` AS `d_table` ON `d_table`.`doc`=`doc_list`.`id` AND `d_table`.`param`='rasxodi'
            LEFT JOIN `doc_dopdata` AS `c_table` ON `c_table`.`doc`=`doc_list`.`id` AND `c_table`.`param`='credit_type'
            WHERE `doc_list`.`ok`!='0' AND `doc_list`.`date`>='$date_st' AND `doc_list`.`date`<='$date_end' AND `doc_list`.`firm_id`=$firm_id
                AND `doc_list`.`type` IN (4, 5, 6, 7)");
        $c = 1;
        $sum_in = $sum_out = 0;
        while($line = $res->fetch_assoc()) {
            $oper = $in = $out = '';
            switch ($line['type']) {
                case 4:
                case 6:
                    if(!$line['in_type']) {
                        $line['in_type'] = 0;
                    }
                    $oper = $in_names[$line['in_type']];
                    $in = $line['sum'];
                    break;
                case 5:
                case 7:
                    if(!$line['out_type']) {
                        $line['out_type'] = 0;
                    }
                    $oper = $out_names[$line['out_type']];
                    $out = $line['sum'];
                    break;
                
            }
            
            $doc_info = $doc_names[$line['type']]. ' N' . $line['altnum'].' от '. date('d.m.Y', $line['date']);
            $this->xls_sheet->write($this->xls_line, 0, $c, $this->xls_formats['rborder']);
            $this->xls_sheet->write($this->xls_line, 1, $doc_info, $this->xls_formats['border']);
            $this->xls_sheet->write($this->xls_line, 2, $oper, $this->xls_formats['border']);
            $this->xls_sheet->write($this->xls_line, 3, $in, $this->xls_formats['sum']);
            $this->xls_sheet->write($this->xls_line, 4, $out, $this->xls_formats['sum']);
            $this->xls_line++;
            $c++;
            $sum_in += $in;
            $sum_out += $out;
        }
        // =======================================================
        $text = "Итого за $quarter квартал";
        $this->xls_sheet->write($this->xls_line, 0, $text, $this->xls_formats['boldborder']);
        $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, 2);
        $this->xls_sheet->write($this->xls_line, 3, $sum_in, $this->xls_formats['boldbordersum']);
        $this->xls_sheet->write($this->xls_line, 4, $sum_out, $this->xls_formats['boldbordersum']);
        $this->xls_line++;
        if($prevous) {
            $this->xls_sheet->write($this->xls_line, 0, $sum_text, $this->xls_formats['boldborder']);
            $this->xls_sheet->setMerge($this->xls_line, 0, $this->xls_line, 2);
            $this->xls_sheet->write($this->xls_line, 3, $sum_in + $prevous['sum_in'], $this->xls_formats['boldbordersum']);
            $this->xls_sheet->write($this->xls_line, 4, $sum_out + $prevous['sum_out'], $this->xls_formats['boldbordersum']);
            $this->xls_line++;
            return array(
                'sum_in' => $sum_in + $prevous['sum_in'],
                'sum_out' => $sum_out + $prevous['sum_out']
            );
        }
        return array(
            'sum_in' => $sum_in,
            'sum_out' => $sum_out
        );
        
    }
    
     
    // makeXLS
    function make($engine) {
        $year = rcvint('year');
        $firm_id = rcvint("firm_id");
        $this->createXLS();
        $this->addTitleListXLS($year, $firm_id);
        $prev = $this->addQuarterList($year, $firm_id, 1);
        $prev = $this->addQuarterList($year, $firm_id, 2, 'Итого за полугодие', $prev);
        $prev = $this->addQuarterList($year, $firm_id, 3, 'Итого за 9 месяцев', $prev);
        $prev = $this->addQuarterList($year, $firm_id, 4, 'Итого за год', $prev);
        
        
        $this->closeXLS();
    }

}
