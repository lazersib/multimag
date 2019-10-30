<?php
/**
 * Created by PhpStorm.
 * User: stivvi
 * Date: 30.10.2019
 * Time: 15:21
 */

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