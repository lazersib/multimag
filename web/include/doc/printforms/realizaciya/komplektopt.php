<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2015, BlackLight, TND Team, http://tndproject.org
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

class komplektopt extends \doc\printforms\iPrintFormInvoicePdf {
    protected $form_basesum;
    
    public function getName() {
        return "Накладная на комплектацию (опт)";
    }
    
    /// Инициализация модуля вывода данных
    public function initForm() {
        require('fpdf/fpdf_mc.php');
        $this->pdf = new \PDF_MC_Table('P');
        $this->pdf->Open();
        $this->pdf->SetAutoPageBreak(1, 5);
        $this->pdf->AddFont('Arial', '', 'arial.php');
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->tMargin = 5;
        $this->pdf->SetFillColor(255);
    }
    
    protected function addPartnerInfoBlock() {
        $this->addOutPartnerInfoBlock();
    }

    /// Добавить блок с заголовком формы
    protected function addFormHeaderBlock() {
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $text = "Накладная на комплектацию N  {$doc_data['altnum']}{$doc_data['subtype']} ($doc_id) от " . date("d.m.Y", $doc_data['date']);
        $this->addHeader($text);  
        $text = "К расходной накладной N {$doc_data['altnum']}{$doc_data['subtype']} ({$doc_id})";
        $this->addInfoLine($text);           
    }
    
    /// Добавить блок с таблицей номенклатуры
    protected function addNomenclatureTableBlock() {
        global $CONFIG;
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();

        $nomenclature = $this->doc->getDocumentNomenclature('bulkcnt,base_price,rto,bigpack,comment');
        $pc = \PriceCalc::getInstance();
        $pc->setAgentId($doc_data['agent']);
        $pc->setUserId($doc_data['user']);
        if(isset($dop_data['ishop'])) {
            $pc->setFromSiteFlag($dop_data['ishop']);
        }
        
        $th_widths = array(6);
        $th_texts = array('№');
        $font_sizes = array(8);
        $tbody_aligns = array('R');
        if ($CONFIG['poseditor']['vc']) {
            $th_widths[] = 10;
            $th_texts[] = 'Код';
            $font_sizes[] = 7;
            $tbody_aligns[] = 'R';
            $th_widths[] = 80;
        } else {
            $th_widths[] = 100;
        }
        $th_texts[] = 'Наименование';
        $tbody_aligns[] = 'L';
        $font_sizes[] = 14;

        $th_widths = array_merge($th_widths, array(10, 14, 12, 14, 14, 14, 20));
        $th_texts = array_merge($th_texts, array('Ед.', 'Кол-во', 'Склад', 'В м.уп.', 'В б.уп.', 'Резерв', 'Место'));
        $tbody_aligns = array_merge($tbody_aligns, array('C', 'R', 'R', 'R', 'R', 'R', 'R'));
        $this->addTableHeader($th_widths, $th_texts, $tbody_aligns);        
        $font_sizes = array_merge($font_sizes, array(8, 11, 8, 8, 8, 8, 8));
        $this->pdf->SetFSizes($font_sizes);
        $this->pdf->SetHeight(6);
        
        $this->form_linecount = 0;
        $this->form_sum = $this->form_summass = 0;
        $this->form_basesum = 0;
        foreach($nomenclature as $line) {
            $this->form_linecount++;
            $price = sprintf("%01.2f р.", $line['price']);
            $sum_line = sprintf("%01.2f р.", $line['sum']);
            $row = array($this->form_linecount);
            $rowc=array('');
            if (@$CONFIG['poseditor']['vc']) {
                $row[] = $line['vc'];
                $rowc[] = '';
            }
            $row[] = $line['name'];
            $rowc[] = $line['comment'];
            $row = array_merge($row, 
                array(
                    $line['unit_name'],
                    $line['cnt'],
                    round($line['base_cnt']),  
                    $line['mult'],
                    $line['bigpack_cnt'],
                    $line['reserve'],
                    $line['place'],
                ) );
            $rowc = array_merge($rowc, array('','','', '','','','') );
            if ($this->pdf->h <= ($this->pdf->GetY() + 18 )) {
                $this->pdf->AddPage();
                $this->addTechFooter();
            }
            $this->pdf->SetFont('', '', 8);
            $this->pdf->RowIconvCommented($row, $rowc);
            $this->form_sum += $line['sum'];
            $this->form_summass += $line['mass'] * $line['cnt'];
            $this->form_basesum += $pc->getPosDefaultPriceValue($line['pos_id'], $line)*$line['cnt'];
        }    
        $pc->setOrderSum($this->form_basesum);   
    }
    
    /// Добавить блок с информацией о сумме документа
    protected function addSummaryBlock() {
        parent::addSummaryBlock();
        $doc_data = $this->doc->getDocDataA();
        if($doc_data['comment']) {
            $this->addInfoLine("Комментарий к документу: " . $doc_data['comment']);
        }
    }
    
    /// Добавить блок с информацией об оплатах
    protected function addPaymentInfoBlock() {
    } 
    
    /// Добавить блок с подписями
    protected function addSignBlock() {    
        global $db;
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $vip_name = $autor_name = $klad_name = '________________';
        $res_uid = $db->query("SELECT `worker_real_name` FROM `users_worker_info`
            WHERE `user_id`='" . $_SESSION['uid'] . "'");
        if ($res_uid->num_rows) {
            list($vip_name) = $res_uid->fetch_row();
        }

        $res_autor = $db->query("SELECT `worker_real_name` FROM `users_worker_info`
            WHERE `user_id`='" . $doc_data['user'] . "'");
        if ($res_autor->num_rows) {
            list($autor_name) = $res_autor->fetch_row();
        }

        $res_klad = $db->query("SELECT `worker_real_name` FROM `users_worker_info`
            WHERE `user_id`='" . $dop_data['kladovshik'] . "'");
        if ($res_klad->num_rows) {
            list($klad_name) = $res_klad->fetch_row();
        } 

        $text = "Документ подготовил: _________________________________________ ( $autor_name )";
        $this->addSignLine($text);
        $text = "Документ выписал: _____________________________________ ( $vip_name )";
        $this->addSignLine($text);
        $text = "Комплектацию выполнил: ___________________________________ ( $klad_name )";
        $this->addSignLine($text);
    }
    
}
