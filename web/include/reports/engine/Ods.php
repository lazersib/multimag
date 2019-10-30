<?php
/**
 * Created by PhpStorm.
 * User: stivvi
 * Date: 30.10.2019
 * Time: 16:55
 */

namespace App\Reports\Engine;

use PhpOffice\PhpSpreadsheet\Writer\Ods as OdsWriter;

class Ods extends BaseSheet
{
	public function output($fname)
	{
		$writer = new OdsWriter($this->spreadsheet);
		header('Content-Type: application/vnd.oasis.opendocument.spreadsheet');
		header('Content-Disposition: attachment;filename="'. $fname .'.ods"');
		header('Cache-Control: max-age=0');
		$writer->save('php://output');
		exit;
	}
}