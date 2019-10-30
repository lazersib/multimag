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

use App\Reports\Engine\Xlsx;
use App\Reports\Engine\Ods;

class BaseReport {

    protected $output_format = 'html';
    protected $oe = null;   // output engine

    function __construct() {

    }

/// Выбрать движок вывода
    function loadEngine($engine = 'html') {
        switch ($engine) {
            case 'pdf': $this->output_format = 'pdf';
                $this->oe = new ReportEnginePDF();
                break;
	        case 'xls/xlsx': $this->output_format = 'xls';
		        $this->oe = new Xlsx();
		        break;
	        case 'ods': $this->output_format = 'ods';
		        $this->oe = new Ods();
		        break;
            default: $this->output_format = 'html';
                $this->oe = new ReportEngineHTML();
        }
    }

/// Запустить отчёт
    function Run($opt) {
        if ($opt == '')
            $this->Form();
        else
            $this->Make($opt);
    }

    function header($text, $type = 1) {
        return $this->oe->header($text, $type);
    }

    function tableBegin($widths) {
        return $this->oe->tableBegin($widths);
    }

    function tableHeader($cells) {
        return $this->oe->tableHeader($cells);
    }

    function tableAltStyle($use = true) {
        return $this->oe->tableAltStyle($use);
    }

    function tableRow($cells) {
        return $this->oe->tableRow($cells);
    }

    function tableSpannedRow($si, $cells) {
        return $this->oe->tableSpannedRow($si, $cells);
    }

    function tableEnd() {
        return $this->oe->tableEnd();
    }

    function output($fname = 'report') {
        return $this->oe->output($fname);
    }

}
