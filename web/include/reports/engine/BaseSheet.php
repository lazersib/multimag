<?php
/**
 * Created by PhpStorm.
 * User: stivvi
 * Date: 30.10.2019
 * Time: 17:00
 */

namespace App\Reports\Engine;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

abstract class BaseSheet
{
	protected $sheet;
	protected $spreadsheet;
	protected $maxCell = 'H';

	public function __construct()
	{
		$this->spreadsheet = new Spreadsheet();
		$this->sheet = $this->spreadsheet->getActiveSheet();
		$this->sheet->setTitle('Отчет');
	}

	public function header($text, $type = 1)
	{
		$this->sheet->setCellValue('A1', $text);
		$this->sheet->mergeCells('A1:'.$this->maxCell.'1');
		$this->sheet->getStyle('A1:'.$this->maxCell.'1')
			->getAlignment()
			->setHorizontal(Alignment::HORIZONTAL_CENTER)
			->setVertical(Alignment::VERTICAL_CENTER)
			->setWrapText(true);
		$this->sheet->getStyle('A1:'.$this->maxCell.'1')
			->getFont()
			->setSize(14)
			->setBold(true);
	}

	public function tableBegin($widths){}

	public function tableHeader($cells)
	{
		$this->sheet->fromArray($cells, null, 'A2');
	}

	public function tableRow($cells)
	{
		$this->sheet->fromArray($cells, null, 'A'.($this->sheet->getHighestDataRow()+1));
	}

	public function tableSpannedRow($span_info, $cells) {}

	public function tableEnd()
	{
		foreach (range('A', $this->sheet->getHighestDataColumn()) as $col) {
			$this->sheet
				->getColumnDimension($col)
				->setAutoSize(true);
		}
		foreach($this->sheet->getRowDimensions() as $rd) {
			$rd->setRowHeight(-1);
		}
		$borderStyleArray = [
			'borders' => [
				'outline' => [
					'borderStyle' => Border::BORDER_THIN,
					'color' => ['rgb' => '000000'],
				],
				'horizontal' => [
					'borderStyle' => Border::BORDER_THIN,
					'color' => ['rgb' => '000000'],
				],
				'vertical' => [
					'borderStyle' => Border::BORDER_THIN,
					'color' => ['rgb' => '000000'],
				],
			]
		];
		$this->sheet->getStyle('A1:'.$this->maxCell.''.$this->sheet->getHighestDataRow())
			->applyFromArray($borderStyleArray);
	}

	abstract function output($fname);
}