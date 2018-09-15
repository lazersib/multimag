<?php

require('fpdf.php');

class PDF_MC_Table extends FPDF {

    var $widths;
    var $aligns;
    var $line_height = 5;
    var $fsizes;

    /// Set the array of column widths
    function setWidths($w) {        
        $this->widths = $w;
    }
    
    /// Set the line height
    function setHeight($h) {        
        $this->line_height = $h;
    }

    /// Set the array of column alignments
    function SetAligns($a) {        
        $this->aligns = $a;
    }

    /// Set the array of column font sizes
    function SetFSizes($fs) {        
        $this->fsizes = $fs;
    }

    /// Calculate the height of the row
    function Row($data) {        
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            if (isset($this->fsizes[$i])) {
                //$this->SetFont('','',$this->fsizes[$i]);
                // Быстрый метод
                if ($this->fsizes[$i] == 0)
                    $this->fsizes[$i] = $this->FontSizePt;
                $this->FontSizePt = $this->fsizes[$i];
                $this->FontSize = $this->fsizes[$i] / $this->k;
            }
            $nb = @max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        $h = $this->line_height * $nb;
        //Issue a page break first if needed
        $this->CheckPageBreak($h);
        //Draw the cells of the row
        for ($i = 0; $i < count($data); $i++) {
            $w = @$this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            if (isset($this->fsizes[$i])) {
                //$this->SetFont('','',$this->fsizes[$i]);
                // Быстрый метод
                if ($this->fsizes[$i] == 0)
                    $this->fsizes[$i] = $this->FontSizePt;
                $this->FontSizePt = $this->fsizes[$i];
                $this->FontSize = $this->fsizes[$i] / $this->k;
                if ($this->page > 0)
                    $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
            }
            //Save the current position
            $x = $this->GetX();
            $y = $this->GetY();
            //Draw the border
            $this->Rect($x, $y, $w, $h, 'DF');
            //Print the text
            $this->MultiCell($w, $this->line_height, @$data[$i], 0, $a);
            //Put the position to the right of the cell
            $this->SetXY($x + $w, $y);
        }
        //Go to the next line
        $this->Ln($h);
    }
    
    function rowCommented($data, $comments = null) {
        //Calculate the height of the row
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            if (isset($this->fsizes[$i])) {
                //$this->SetFont('','',$this->fsizes[$i]);
                // Быстрый метод
                if ($this->fsizes[$i] == 0)
                    $this->fsizes[$i] = $this->FontSizePt;
                $this->FontSizePt = $this->fsizes[$i];
                $this->FontSize = $this->fsizes[$i] / $this->k;
            }
            $lines = $this->NbLines($this->widths[$i], $data[$i]);
            if (isset($this->fsizes[$i])) {
                $font = round($this->fsizes[$i] / 1.5);
                $this->FontSizePt = $font;
                $this->FontSize = $font / $this->k;
            }
            if ($comments[$i])
                $lines += $this->NbLines($this->widths[$i], $comments[$i]);
            $nb = max($nb, $lines);
        }
        $h = $this->line_height * $nb;
        //Issue a page break first if needed
        $this->CheckPageBreak($h);
        //Draw the cells of the row
        for ($i = 0; $i < count($data); $i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            if (isset($this->fsizes[$i])) {
                //$this->SetFont('','',$this->fsizes[$i]);
                // Быстрый метод
                if ($this->fsizes[$i] == 0)
                    $this->fsizes[$i] = $this->FontSizePt;
                $this->FontSizePt = $this->fsizes[$i];
                $this->FontSize = $this->fsizes[$i] / $this->k;
                if ($this->page > 0)
                    $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
            }
            //Save the current position
            $x = $this->GetX();
            $y = $this->GetY();
            //Draw the border
            $this->Rect($x, $y, $w, $h, 'DF');
            //Print the text
            $this->MultiCell($w, $this->line_height, $data[$i], 0, $a);
            if ($comments[$i]) {
                $oldfont = $this->FontSizePt;
                $this->FontSizePt /= 1.5;
                $this->FontSize = $this->FontSizePt / $this->k;
                if ($this->page > 0)
                    $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));

                $this->SetX($x);
                $this->MultiCell($w, $this->line_height, $comments[$i], 0, $a);

                $this->FontSizePt = $oldfont;
                $this->FontSize = $this->FontSizePt / $this->k;
                if ($this->page > 0)
                    $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
            }
            //Put the position to the right of the cell
            $this->SetXY($x + $w, $y);
        }
        //Go to the next line
        $this->Ln($h);
    }

    function rowIconv($_data) {
        $data = array();
        foreach ($_data as $i => $s) {
            $data[$i] = @iconv('UTF-8', 'windows-1251', $s);
        }
        $this->Row($data);
    }

    function rowIconvCommented($_data, $_comments) {
        $data = array();
        foreach ($_data as $i => $s) {
            $data[$i] = iconv('UTF-8', 'windows-1251', $s);
        }
        $comments = array();
        foreach ($_comments as $i => $s) {
            $comments[$i] = iconv('UTF-8', 'windows-1251', $s);
        }
        $this->RowCommented($data, $comments);
    }

    function checkPageBreak($h) {
        //If the height h would cause an overflow, add a new page immediately
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
        }
    }

    function nbLines($w, $txt) {
        //Computes the number of lines a MultiCell of width w will take
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 and $s[$nb - 1] == "\n") {
            $nb--;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }

}
