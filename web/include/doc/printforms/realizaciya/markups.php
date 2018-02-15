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

class markups extends \doc\printforms\iPrintFormInvoicePdf {
    protected $form_basesum;
    
    public function getName() {
        return "Наценки";
    }
    
    protected function addPartnerInfoBlock() {
        $this->addOutPartnerInfoBlock();
    }

    /// Добавить блок с заголовком формы
    protected function addFormHeaderBlock() {
        global $db;
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        $text = "Наценки N  {$doc_data['altnum']}{$doc_data['subtype']} ($doc_id) от " . date("d.m.Y", $doc_data['date']);
        $this->addHeader($text);  
        $text = "К расходной накладной N {$doc_data['altnum']}{$doc_data['subtype']} ({$doc_id})";
        $this->addInfoLine($text);  
        
        $res = $db->query("SELECT `users`.`name`, `users_worker_info`.`worker_real_name`, CONCAT(`doc_list`.`altnum`,`doc_list`.`subtype`) AS `num`,
		`doc_list`.`date`, `doc_list`.`sum`, `doc_list`.`id`
		FROM `doc_list`
		LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
		LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`doc_list`.`user`
		WHERE `doc_list`.`id`='{$doc_data['p_doc']}' AND `doc_list`.`type`='3'");
        if ($res->num_rows) {
            $l = $res->fetch_assoc();
            $l['date'] = date("Y-m-d", $l['date']);
            $str = "К заявке: N{$l['num']}, от {$l['date']} на {$l['sum']}, создал {$l['name']}/{$l['worker_real_name']}";
            $order_id = $l['id'];
            $this->addInfoLine($str);
        } else {
            $order_id = 0;
        }

        $res = $db->query("SELECT `users`.`name`, `users_worker_info`.`worker_real_name`, CONCAT(`doc_list`.`altnum`,`doc_list`.`subtype`) AS `num`,
		`doc_list`.`date`, `doc_list`.`sum`
		FROM `doc_list`
		LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
		LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`doc_list`.`user`
		WHERE (`doc_list`.`p_doc`='{$doc_id}' OR `doc_list`.`p_doc`='$order_id') AND `doc_list`.`type`='4' AND `doc_list`.`p_doc`>0");
        while ($l = $res->fetch_assoc()) {
            $l['date'] = date("Y-m-d", $l['date']);
            $str = "Подчинённый банк-приход: N{$l['num']}, от {$l['date']} на {$l['sum']}, создал {$l['name']}/{$l['worker_real_name']}";
            $this->addInfoLine($str);
        }

        $res = $db->query("SELECT `users`.`name`, `users_worker_info`.`worker_real_name`, CONCAT(`doc_list`.`altnum`,`doc_list`.`subtype`) AS `num`,
		`doc_list`.`date`, `doc_list`.`sum`
		FROM `doc_list`
		LEFT JOIN `users` ON `users`.`id`=`doc_list`.`user`
		LEFT JOIN `users_worker_info` ON `users_worker_info`.`user_id`=`doc_list`.`user`
		WHERE (`doc_list`.`p_doc`='{$doc_id}' OR `doc_list`.`p_doc`='$order_id') AND `doc_list`.`type`='5' AND `doc_list`.`p_doc`>0");
        while ($l = $res->fetch_assoc()) {
            $l['date'] = date("Y-m-d", $l['date']);
            $str = "Подчинённый расходно-кассовый ордер: N{$l['num']}, от {$l['date']} на {$l['sum']}, создал {$l['name']}/{$l['worker_real_name']}";
            $this->addInfoLine($str);
        }
    }
    
    /// Добавить блок с таблицей номенклатуры
    protected function addNomenclatureTableBlock() {
        global $db;
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();

        $nomenclature = $this->doc->getDocumentNomenclature('bulkcnt,base_price,rto,comment');
        $pc = \PriceCalc::getInstance();
        $pc->setFirmId($doc_data['firm_id']);
        $pc->setAgentId($doc_data['agent']);
        $pc->setUserId($doc_data['user']);
        if(isset($dop_data['ishop'])) {
            $pc->setFromSiteFlag($dop_data['ishop']);
        }
        
        $t_width = array(8, 90, 18, 16, 19, 16, 20, 27, 19, 19, 27);
	$t_text = array('№', 'Наименование', 'Кол-во', 'Цена', 'Сумма', 'АЦП', 'Наценка', 'Сум.наценки', 'П/закуп', 'Разница', 'Сум.разницы');
	$aligns = array('R', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R');
        $font_sizes = array(8);

        $this->addTableHeader($t_width, $t_text, $aligns);        
        $this->pdf->SetFSizes($font_sizes);
        $this->pdf->SetHeight(4);
        
        $this->form_linecount = 0;
        $this->form_sum = $this->form_summass = 0;
        $this->form_basesum = 0;
        $snac = $srazn = 0;
        foreach($nomenclature as $line) {
            $this->form_linecount++;
            $price = sprintf("%01.2f", $line['price']);
            $cost2 = sprintf("%01.2f", $line['sum']);
            $act_cost = sprintf('%0.2f', GetInCost($line['pos_id']));
            $nac = sprintf('%0.2f', $price - $act_cost);
            $sum_nac = sprintf('%0.2f', $nac * $line['cnt']);
            $snac+=$sum_nac;

            $r = $db->query("SELECT `doc_list`.`date`, `doc_list_pos`.`cost` FROM `doc_list_pos`
                LEFT JOIN `doc_list` ON `doc_list`.`id`=`doc_list_pos`.`doc`
                WHERE `doc_list`.`ok`>'0' AND `doc_list`.`type`='1' AND `doc_list_pos`.`tovar`='{$line['pos_id']}' AND `doc_list`.`date`<'{$doc_data['date']}'
                ORDER BY `doc_list`.`date` DESC");
            if ($r->num_rows) {
                $rr = $r->fetch_row();
                $zakup = sprintf('%0.2f', $rr[1]);
            } else {
                $zakup = 0;
            }
            $razn = sprintf('%0.2f', $price - $zakup);
            $sum_razn = sprintf('%0.2f', $razn * $line['cnt']);
            $srazn+=$sum_razn;
            $row = array($this->form_linecount, $line['name'], $line['cnt'] . ' ' . $line['unit_name'], $price, $cost2, $act_cost, $nac, $sum_nac, $zakup, $razn, $sum_razn);
            $this->pdf->RowIconv($row);
            $this->form_sum += $line['sum'];
            $this->form_summass += $line['mass'] * $line['cnt'];
            $this->form_basesum += $pc->getPosDefaultPriceValue($line['pos_id'], $line)*$line['cnt'];
        }    
        $pc->setOrderSum($this->form_basesum);   
        $price = sprintf("%01.2f", $this->form_sum);
	$srazn = sprintf("%01.2f", $srazn);
	$snac = sprintf("%01.2f", $snac);
	$row = array('', 'Итого:', '', '', $price, '', '', $snac, '', '', $srazn);
        $this->pdf->RowIconv($row);
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
    
    
    /// Сформировать печатную форму
    public function make() {
        $this->pdf->AddPage('L');
        $this->addTechFooter();
        
        $this->addFormHeaderBlock();      
        $this->addPartnerInfoBlock(); 
        $this->addNomenclatureTableBlock();
        $this->pdf->Ln();
        
        $this->addSummaryBlock();
        $this->addPaymentInfoBlock();
        $this->pdf->Ln();

        $this->addSignBlock();
        return;
    }
}
