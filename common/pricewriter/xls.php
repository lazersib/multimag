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
namespace pricewriter;

/// Класс формирует прайс-лист в формате XLS
class xls extends BasePriceWriter {

    var $workbook;  // книга XLS
    var $worksheet;  // Лист XLS
    var $line;  // Текущая строка
    var $format_line; // формат для строк наименований прайса
    var $a_format_line; // формат для строк наименований прайса для серой цены
    var $format_group; // формат для строк групп прайса
    protected $tmp_filename; // Имя временного файла для генерации прайса

    /// Конструктор

    function __construct($db) {
        parent::__construct($db);
        $this->line = 0;
    }

    /// Сформировать шапку прайса
    function open() {
        //require_once('Spreadsheet/Excel/Writer.php');
        global $CONFIG;
        $pref = \pref::getInstance();        
        
        if($this->to_string) {
            $tmp_dir = sys_get_temp_dir();
            $this->tmp_filename = tempnam($tmp_dir, "price_");
            $this->workbook = new \excel\writerHelper($this->tmp_filename);
        }
        else {
            $this->workbook = new \excel\writerHelper('');
            // sending HTTP headers
            $this->workbook->send('price.xls');
        }

        // Creating a worksheet
        $this->worksheet = & $this->workbook->addWorksheet($pref->site_name);

        $this->format_footer = & $this->workbook->addFormat();
        $this->format_footer->SetAlign('center');
        $this->format_footer->setColor(39);
        $this->format_footer->setFgColor(27);
        $this->format_footer->SetSize(8);

        $this->format_line = array();
        $this->format_line[0] = & $this->workbook->addFormat();
        $this->format_line[0]->setColor(0);
        $this->format_line[0]->setFgColor(26);
        $this->format_line[0]->SetSize(12);
        $this->format_line[1] = & $this->workbook->addFormat();
        $this->format_line[1]->setColor(0);
        $this->format_line[1]->setFgColor(41);
        $this->format_line[1]->SetSize(12);

        // для серых цен
        $this->a_format_line = array();
        $this->a_format_line[0] = & $this->workbook->addFormat();
        $this->a_format_line[0]->setColor('gray');
        $this->a_format_line[0]->setFgColor(26);
        $this->a_format_line[0]->SetSize(12);
        $this->a_format_line[1] = & $this->workbook->addFormat();
        $this->a_format_line[1]->setColor('gray');
        $this->a_format_line[1]->setFgColor(41);
        $this->a_format_line[1]->SetSize(12);

        $this->format_group = array();
        $this->format_group[0] = & $this->workbook->addFormat();
        $this->format_group[0]->setColor(0);
        $this->format_group[0]->setFgColor(53);
        $this->format_group[0]->SetSize(14);
        $this->format_group[0]->SetAlign('center');
        $this->format_group[1] = & $this->workbook->addFormat();
        $this->format_group[1]->setColor(0);
        $this->format_group[1]->setFgColor(52);
        $this->format_group[1]->SetSize(14);
        $this->format_group[1]->SetAlign('center');
        $this->format_group[2] = & $this->workbook->addFormat();
        $this->format_group[2]->setColor(0);
        $this->format_group[2]->setFgColor(51);
        $this->format_group[2]->SetSize(14);
        $this->format_group[2]->SetAlign('center');


        $format_title = & $this->workbook->addFormat();
        $format_title->setBold();
        $format_title->setColor('blue');
        $format_title->setPattern(1);
        $format_title->setFgColor('yellow');
        $format_title->SetSize(26);

        $format_info = & $this->workbook->addFormat();
        //$format_info->setBold();
        $format_info->setColor('blue');
        $format_info->setPattern(1);
        $format_info->setFgColor('yellow');
        $format_info->SetSize(16);

        $format_header = & $this->workbook->addFormat();
        $format_header->setBold();
        $format_header->setColor(1);
        $format_header->setPattern(1);
        $format_header->setFgColor(63);
        $format_header->SetSize(16);
        $format_header->SetAlign('center');
        $format_header->SetAlign('vcenter');
        // Настройка ширины столбцов

        if (@$CONFIG['site']['price_show_vc']) {
            $column_width = array(8, 8, 112, 15, 15);
        } else {
            $column_width = array(8, 120, 15, 15);
        }
        foreach ($column_width as $id => $width) {
            $this->worksheet->setColumn($id, $id, $width);
        }
        $this->column_count = count($column_width);

        if (is_array($CONFIG['site']['price_text'])) {
            foreach ($CONFIG['site']['price_text'] as $text) {
                $str = iconv('UTF-8', 'windows-1251', $text);
                $this->worksheet->setRow($this->line, 30);
                $this->worksheet->write($this->line, 0, $str, $format_title);
                $this->worksheet->setMerge($this->line, 0, $this->line, $this->column_count - 1);
                $this->line++;
            }
        }

        $str = 'Прайс загружен с сайта http://' . $pref->site_name;
        $str = iconv('UTF-8', 'windows-1251', $str);
        $this->worksheet->write($this->line, 0, $str, $format_info);
        $this->worksheet->setMerge($this->line, 0, $this->line, $this->column_count - 1);
        $this->line++;

        $str = 'При заказе через сайт может быть предоставлена скидка!';
        $str = iconv('UTF-8', 'windows-1251', $str);
        $this->worksheet->write($this->line, 0, $str, $format_info);
        $this->worksheet->setMerge($this->line, 0, $this->line, $this->column_count - 1);
        $this->line++;

        $dt = date("d.m.Y");
        $str = 'Цены действительны на дату: ' . $dt . '.';
        if (@$CONFIG['site']['grey_price_days']) {
            $str .= ' Цены, выделенные серым цветом, необходимо уточнять.';
        }
        $str = iconv('UTF-8', 'windows-1251', $str);
        $this->worksheet->write($this->line, 0, $str, $format_info);
        $this->worksheet->setMerge($this->line, 0, $this->line, $this->column_count - 1);
        $this->line++;

        if (is_array($this->view_groups)) {
            $this->line++;
            //$this->Ln(3);
            //$this->SetFont('','',14);
            //$this->SetTextColor(255,24,24);
            $str = 'Прайс содержит неполный список позиций, в соответствии с выбранными критериями при его загрузке с сайта.';
            $str = iconv('UTF-8', 'windows-1251', $str);
            $this->worksheet->write($this->line, 0, $str, $format_info);
            $this->worksheet->setMerge($this->line, 0, $this->line, $this->column_count - 1);
            $this->line++;
        }

        $this->line++;
        $this->worksheet->write(8, 8, ' ');
        if (@$CONFIG['site']['price_show_vc']) {
            $headers = array("N", "Код", "Наименование", "Наличие", "Цена");
        } else {
            $headers = array("N", "Наименование", "Наличие", "Цена");
        }
        foreach ($headers as $id => $item) {
            $headers[$id] = iconv('UTF-8', 'windows-1251', $item);
        }
        $this->worksheet->writeRow($this->line, 0, $headers, $format_header);
        $this->line++;
    }

    /// Сформирвать тело прайса
    /// param $group id номенклатурной группы
    /// param $level уровень вложенности
    function write($group = 0, $level = 0) {
        if ($level > 2) {
            $level = 2;
        }
        $res = $this->db->query("SELECT `id`, `name`, `printname` FROM `doc_group` WHERE `pid`='$group' AND `hidelevel`='0' ORDER BY `id`");
        while ($nxt = $res->fetch_row()) {
            if ($nxt[0] == 0) {
                continue;
            }
            if (is_array($this->view_groups)) {
                if (!in_array($nxt[0], $this->view_groups)) {
                    continue;
                }
            }

            $str = iconv('UTF-8', 'windows-1251', $nxt[1]);
            $this->worksheet->write($this->line, 0, $str, $this->format_group[$level]);
            $this->worksheet->setMerge($this->line, 0, $this->line, $this->column_count - 1);
            $this->line++;

            $this->writepos($nxt[0], $nxt[2]);
            $this->write($nxt[0], $level + 1);
        }
    }

    /// Сформировать завершающий блок прайса
    function close() {
        $pref = \pref::getInstance();
        $this->line+=5;
        $this->worksheet->write($this->line, 0, "Generated from MultiMag (http://multimag.tndproject.org) via PHPExcelWriter, for http://" . $pref->site_name, $this->format_footer);
        $this->worksheet->setMerge($this->line, 0, $this->line, $this->column_count - 1);
        $this->line++;
        $str = iconv('UTF-8', 'windows-1251', "Прайс создан системой MultiMag (http://multimag.tndproject.org), специально для http://" . $pref->site_name);
        $this->worksheet->write($this->line, 0, $str, $this->format_footer);
        $this->worksheet->setMerge($this->line, 0, $this->line, $this->column_count - 1);
        $this->workbook->close();
        if($this->to_string) {
            $buffer = file_get_contents($this->tmp_filename);
            @unlink($this->tmp_filename);
            return $buffer;
        }
    }

    /// Сформировать строки прайса
    /// param $group id номенклатурной группы
    /// param $group_name Отображаемое имя номенклатурной группы
    function writepos($group = 0, $group_name = '') {
        global $CONFIG;
        $pref = \pref::getInstance();
        $cnt_where = $pref->getSitePref('site_store_id') ? (" AND `doc_base_cnt`.`sklad`=" . intval($pref->getSitePref('site_store_id')) . " ") : '';

        $res = $this->db->query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost_date` , `doc_base`.`proizv`, `doc_base`.`vc`,		
			( SELECT SUM(`doc_base_cnt`.`cnt`) FROM `doc_base_cnt` WHERE `doc_base_cnt`.`id`=`doc_base`.`id` $cnt_where) AS `cnt`,
				`doc_base_dop`.`transit`, `doc_base`.`cost` AS `base_price`, `doc_base`.`bulkcnt`, `doc_base`.`group`
		FROM `doc_base`
                LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		LEFT JOIN `doc_group` ON `doc_base`.`group`=`doc_group`.`id`
		WHERE `doc_base`.`group`='$group' AND `doc_base`.`hidden`='0' ORDER BY `doc_base`.`name`");
        $i = 0;

        $cce_time = \cfg::get('site', 'grey_price_days', 0) * 60 * 60 * 24;

        $pc = \PriceCalc::getInstance();
        $pref = \pref::getInstance();
        $pc->setFirmId($pref->getSitePref('default_firm_id'));
        while ($nxt = $res->fetch_assoc()) {
            if($this->vendor_filter!='' && $nxt['proizv']!=$this->vendor_filter) {
                continue;
            }
            if($this->count_filter=='instock' && $nxt['cnt']<=0) {
                continue;
            }
            if($this->count_filter=='intransit' && $nxt['cnt']<=0 && $nxt['transit']<=0) {
                continue;
            }
            $c = 0;
            $this->worksheet->write($this->line, $c++, $nxt['id'], $this->format_line[$i]); // номер

            if (@$CONFIG['site']['price_show_vc']) {
                $str = iconv('UTF-8', 'windows-1251', $nxt['vc']);
                $this->worksheet->write($this->line, $c++, $str, $this->format_line[$i]); // код производителя
            }
            
            $name = $nxt['name'];
            if($this->view_groupname) {
                $name = $group_name .' '.$name;
            }
            if($this->view_proizv && $nxt['proizv']) {
                $name .= " ({$nxt['proizv']})";
            }
            $name = iconv('UTF-8', 'windows-1251', $name);
            $this->worksheet->write($this->line, $c++, $name, $this->format_line[$i]); // наименование

            $nal = $this->GetCountInfo($nxt['cnt'], $nxt['transit']);
            $str = iconv('UTF-8', 'windows-1251', $nal);
            $this->worksheet->write($this->line, $c++, $str, $this->format_line[$i]);  // наличие - пока не отображается

            $cost = $pc->getPosSelectedPriceValue($nxt['id'], $this->cost_id, $nxt);
            if ($cost == 0) {
                continue;
            }
            $str = iconv('UTF-8', 'windows-1251', $cost);

            $format = $this->format_line[$i];
            
            if($cce_time) {
                if (strtotime($nxt['cost_date']) < $cce_time) {
                    $format = $this->a_format_line[$i];
                }
            }

            $this->worksheet->write($this->line, $c++, $str, $format);  // цена

            $this->line++;
            $i = 1 - $i;
        }
    }

}

