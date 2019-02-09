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

class updCsv extends \doc\printforms\iPrintForm {

    protected $buf = '';

    public function __construct() {
        parent::__construct();
        $this->mime = "text/csv";
    }

    public function getName() {
        return "Универсальный передаточный документ";
    }

    /// Инициализация модуля вывода данных
    public function initForm() {
        $this->buf = fopen('php://memory', 'r+');
    }

    protected function out($fields) {
        fputcsv($this->buf, $fields);
    }

    /// Вывод данных
    /// @param $to_str Если истина - вернёт буфер с данными. Иначе - вывод в файл.
    public function outData($to_str=false) {
        global $tmpl;
        rewind($this->buf);
        $csv_data = stream_get_contents($this->buf);
        fclose($this->buf);

        $fname = get_class($this);
        $matches = null;
        if (preg_match('@\\\\([\w]+)$@', $fname, $matches)) {
            $fname = $matches[1];
        }

        if ($to_str) {
            return $csv_data;
        }
        else {
            $tmpl->ajax = 1;
            header("Content-type: 'application/octet-stream'");
            header("Content-Disposition: 'attachment'; filename=$fname.csv;");
            echo $csv_data;
        }
    }

    /// Сформировать данные печатной формы
    public function make() {
        global $db;

        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $firm_vars = $this->doc->getFirmVarsA();

        $this->out(["Счёт - фактура N", $doc_data['altnum'], "от", date("d.m.Y", $doc_data['date'])]);

        $t_text = array(
            'N',
            'Code',
            'Name',
            'TypeCode',
            'UnitCode',
            'UnitName',
            'Count',
            'Price',
            'Sum',
            'Excise',
            'Tax',
            'TaxSum',
            'SumWTax',
            'CountryCode',
            'CountryName',
            'NCD');
        $this->out($t_text);

        // тело таблицы
        $nomenclature = $this->doc->getDocumentNomenclatureWVATandNums();

        $i = 1;
        $sumbeznaloga = $sumnaloga = $sum = $summass = 0;
        foreach ($nomenclature as $line ) {
            $sumbeznaloga += $line['sum_wo_vat'];
            $sum += $line['sum'];
            $sumnaloga += $line['vat_s'];
            $summass += $line['mass']*$line['cnt'];
            if($line['vat_p']>0) {
                $p_vat_p = $line['vat_p'].'%';
                $vat_s_p = sprintf("%01.2f", $line['vat_s']);
            }   else {
                $p_vat_p = $vat_s_p = 'без налога';
            }
            $row = array(
                $i++,
                $line['code'],
                $line['name'],
                '--',
                $line['unit_code'],
                $line['unit_name'],                
                $line['cnt'],
                sprintf("%01.2f", $line['price']),
                sprintf("%01.2f", $line['sum_wo_vat']),
                $line['excise'],
                $p_vat_p,
                $vat_s_p,
                sprintf("%01.2f", $line['sum']),
                $line['country_code'],
                $line['country_name'],
                $line['gtd']);
            $this->out($row);
        }
        $this->out([]);

        // Итоги
        $sum = sprintf("%01.2f", $sum);
        if($sumnaloga>0) {
            $sumnaloga = sprintf("%01.2f", $sumnaloga);
        }   else {
            $sumnaloga = 'без налога';
        }
        $sumbeznaloga = sprintf("%01.2f", $sumbeznaloga);

        $sums = array(
            '',
            '',
            'Итого:',
            '',
            '',
            '',
            '',
            '',
            $sumbeznaloga,
            'X',
            'X',
            $sumnaloga,
            $sum,
            '',
            '',
            '');
        $this->out($sums);
    }
    
    
}
