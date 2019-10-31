<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2019, BlackLight, TND Team, http://tndproject.org
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
/// Генерация xlsx из PhpSpreadsheet\Spreadsheet
///

namespace App\Reports\Engine;

use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class Xlsx extends BaseSheet
{
	public function output($fname)
	{
		$writer = new XlsxWriter($this->spreadsheet);
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'. $fname .'.xlsx"');
		header('Cache-Control: max-age=0');
		$writer->save('php://output');
		exit;
	}
}