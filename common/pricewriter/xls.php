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
    protected $cur_row;  // Номер теекущей строки
    protected $cur_col;  ///< Номер текущей колонки
    var $format_line; // формат для строк наименований прайса
    var $format_greyprice; // формат для строк наименований прайса для серой цены
    var $format_group; // формат для строк групп прайса
    protected $tmp_filename; // Имя временного файла для генерации прайса

    /// Конструктор
    function __construct($db) {
        parent::__construct($db);
        $this->cur_row = 0;
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
      
        $this->format_exists = array();
        $this->format_exists[0] = & $this->workbook->addFormat();
        $this->format_exists[0]->setColor(0);
        $this->format_exists[0]->setFgColor(26);
        $this->format_exists[0]->SetSize(12);
        $this->format_exists[0]->SetAlign('right');
        $this->format_exists[1] = & $this->workbook->addFormat();
        $this->format_exists[1]->setColor(0);
        $this->format_exists[1]->setFgColor(41);
        $this->format_exists[1]->SetSize(12);
        $this->format_exists[1]->SetAlign('right');

        // для серых цен
        $this->format_greyprice = array();
        $this->format_greyprice[0] = & $this->workbook->addFormat();
        $this->format_greyprice[0]->setColor('gray');
        $this->format_greyprice[0]->setFgColor(26);
        $this->format_greyprice[0]->SetSize(12);
        $this->format_greyprice[1] = & $this->workbook->addFormat();
        $this->format_greyprice[1]->setColor('gray');
        $this->format_greyprice[1]->setFgColor(41);
        $this->format_greyprice[1]->SetSize(12);

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

        $name_w = 80;
        if( isset($this->column_list['vc']) ) {
            $name_w -= 10;
        }
        if( isset($this->column_list['vendor']) ) {
            $name_w -= 12;
        }
        if($this->mn_pgroup) {
            $name_w += 30;
        }
        if($this->mn_vendor) {
            $name_w += 10;
        }
        
        $col_conf = [
            'id' => [8, 'N'],
            'vc' => [10, 'Код'],
            'gpn' => [30, 'Категория'],
            'name' => [$name_w, 'Наименование'],
            'vendor' => [12, 'Изгот.'],
            'count' => [10, 'Склад'],
            'price' => [15, 'Цена'],
        ];
        
        $this->column_count = 0;
        foreach ($this->column_list as $id) {
            if(!array_key_exists($id, $col_conf)) {
                unset($this->column_list[$id]);
                continue;
            }
            $this->worksheet->setColumn($this->column_count, $this->column_count, $col_conf[$id][0]);
            $this->column_count++;
        }

        if (is_array($CONFIG['site']['price_text'])) {
            foreach ($CONFIG['site']['price_text'] as $text) {
                $str = iconv('UTF-8', 'windows-1251', $text);
                $this->worksheet->setRow($this->cur_row, 30);
                $this->worksheet->write($this->cur_row, 0, $str, $format_title);
                $this->worksheet->setMerge($this->cur_row, 0, $this->cur_row, $this->column_count - 1);
                $this->cur_row++;
            }
        }

        $str = 'Прайс загружен с сайта http://' . $pref->site_name;
        $str = iconv('UTF-8', 'windows-1251', $str);
        $this->worksheet->write($this->cur_row, 0, $str, $format_info);
        $this->worksheet->setMerge($this->cur_row, 0, $this->cur_row, $this->column_count - 1);
        $this->cur_row++;

        $str = 'При заказе через сайт может быть предоставлена скидка!';
        $str = iconv('UTF-8', 'windows-1251', $str);
        $this->worksheet->write($this->cur_row, 0, $str, $format_info);
        $this->worksheet->setMerge($this->cur_row, 0, $this->cur_row, $this->column_count - 1);
        $this->cur_row++;

        $dt = date("d.m.Y");
        $str = 'Цены действительны на дату: ' . $dt . '.';
        if (@$CONFIG['site']['grey_price_days']) {
            $str .= ' Цены, выделенные серым цветом, необходимо уточнять.';
        }
        $str = iconv('UTF-8', 'windows-1251', $str);
        $this->worksheet->write($this->cur_row, 0, $str, $format_info);
        $this->worksheet->setMerge($this->cur_row, 0, $this->cur_row, $this->column_count - 1);
        $this->cur_row++;

        if (is_array($this->show_groups)) {
            $this->cur_row++;
            $str = 'Прайс содержит неполный список позиций, в соответствии с выбранными критериями при его загрузке с сайта.';
            $str = iconv('UTF-8', 'windows-1251', $str);
            $this->worksheet->write($this->cur_row, 0, $str, $format_info);
            $this->worksheet->setMerge($this->cur_row, 0, $this->cur_row, $this->column_count - 1);
            $this->cur_row++;
        }

        $this->cur_row++;
        $this->worksheet->write(8, 8, ' ');
        
        $headers = [];
        foreach ($this->column_list as $id) {
            $headers[] = iconv('UTF-8', 'windows-1251', $col_conf[$id][1]);
        }
        $this->worksheet->writeRow($this->cur_row, 0, $headers, $format_header);
        $this->cur_row++;
    }

    /// Сформирвать тело прайса
    /// param $group id номенклатурной группы
    /// param $level уровень вложенности
    function write($group = 0, $level = 0) {
        if ($level > 2) {
            $level = 2;
        }
        $res = $this->db->query("SELECT `id`, `name`, `printname` FROM `doc_group` WHERE `pid`='$group' AND `hidelevel`='0' ORDER BY `vieworder`,`name`");
        while ($nxt = $res->fetch_row()) {
            if ($nxt[0] == 0) {
                continue;
            }
            if (is_array($this->show_groups)) {
                if (!in_array($nxt[0], $this->show_groups)) {
                    continue;
                }
            }

            $str = iconv('UTF-8', 'windows-1251', $nxt[1]);
            $this->worksheet->write($this->cur_row, 0, $str, $this->format_group[$level]);
            $this->worksheet->setMerge($this->cur_row, 0, $this->cur_row, $this->column_count - 1);
            $this->cur_row++;

            $this->writePosList($nxt[0], $nxt[2]);
            $this->write($nxt[0], $level + 1);
        }
    }

    /// Сформировать завершающий блок прайса
    function close() {
        $pref = \pref::getInstance();
        $this->cur_row+=5;
        $this->worksheet->write($this->cur_row, 0, "Generated from MultiMag (http://multimag.tndproject.org) via PHPExcelWriter, for http://" . $pref->site_name, $this->format_footer);
        $this->worksheet->setMerge($this->cur_row, 0, $this->cur_row, $this->column_count - 1);
        $this->cur_row++;
        $str = iconv('UTF-8', 'windows-1251', "Прайс создан системой MultiMag (http://multimag.tndproject.org), специально для http://" . $pref->site_name);
        $this->worksheet->write($this->cur_row, 0, $str, $this->format_footer);
        $this->worksheet->setMerge($this->cur_row, 0, $this->cur_row, $this->column_count - 1);
        $this->workbook->close();
        if($this->to_string) {
            $buffer = file_get_contents($this->tmp_filename);
            @unlink($this->tmp_filename);
            return $buffer;
        }
    }
    
    /// Записать текстовое значение в ячейку текущей строки
    protected function writeTextCell($cell_id, $text, $format) {
        $text = @iconv('UTF-8', 'windows-1251', $text);
        $this->worksheet->write($this->cur_row, $cell_id, $text, $format);
    }

    /// Сформировать строки прайса
    /// param $group id номенклатурной группы
    /// param $group_name Отображаемое имя номенклатурной группы
    function writePosList($group = 0, $group_name = '') {
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
        while ($line = $res->fetch_assoc()) {
            if($this->vendor_filter!='' && $line['proizv']!=$this->vendor_filter) {
                continue;
            }
            if($this->count_filter=='instock' && $line['cnt']<=0) {
                continue;
            }
            if($this->count_filter=='intransit' && $line['cnt']<=0 && $line['transit']<=0) {
                continue;
            }            
            $price = $pc->getPosSelectedPriceValue($line['id'], $this->price_id, $line);
            if($price == 0) {
                continue;
            }
            
            $c = 0;
            foreach ($this->column_list as $id) {
                switch ($id) {
                    case 'id':
                        $this->writeTextCell($c++, $line['id'], $this->format_line[$i]);
                        break;
                    case 'vc':
                        $this->writeTextCell($c++, $line['vc'], $this->format_line[$i]);
                        break;
                    case 'gpn':
                        $this->writeTextCell($c++, $group_name, $this->format_line[$i]);
                        break;
                    case 'name':
                        $name = $line['name'];
                        if($this->mn_pgroup && $group_name) {
                            $name = $group_name.' '.$name;
                        }
                        if($this->mn_vendor && $line['proizv']) {
                            $name .= " / {$line['proizv']}";
                        }
                        $this->writeTextCell($c++, $name, $this->format_line[$i]);
                        break;
                    case 'vendor':
                        $this->writeTextCell($c++, $line['proizv'], $this->format_line[$i]);
                        break;
                    case 'count':
                        $count = $this->GetCountInfo($line['cnt'], $line['transit']);
                        $this->writeTextCell($c++, $count, $this->format_exists[$i]);
                        break;
                    case 'price':
                        $format = $this->format_line[$i];            
                        if ($cce_time && strtotime($line['cost_date']) < $cce_time) {
                            $format = $this->format_greyprice[$i];
                        }                        
                        $this->writeTextCell($c++, $price, $format);
                        break;
                }
            }
            $this->cur_row++;
            $i = 1 - $i;
        }
    }

}

