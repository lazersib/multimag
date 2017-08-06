<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2017, BlackLight, TND Team, http://tndproject.org
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
namespace doc\printforms\permitout; 

class permitout extends \doc\printforms\iPrintFormInvoicePdf {
    
    public function getName() {
        return "Пропуск на выезд";
    }
    
    protected function addPartnerInfoBlock() {
        $this->addOutPartnerInfoBlock();
        $dop_data = $this->doc->getDopDataA();
        $text = "Гос. номер транспортного средства: ".$dop_data['transport_number'];
        $this->addInfoLine($text);
    }

    /// Добавить блок с заголовком формы
    protected function addFormHeaderBlock() {
        $doc_id = $this->doc->getId();
        $doc_data = $this->doc->getDocDataA();
        
        if($doc_data['p_doc']==0) {
            throw new \Exception('Пропуск должен быть прикреплён к реализации!');
        }
        $pdoc = \document::getInstanceFromDb($doc_data['p_doc']);
        if($pdoc->getTypeName()!='realizaciya') {
            throw new \Exception('Пропуск прикреплён не к реализации!');
        }
        $this->pdoc_data = $pdoc->getDocDataA();
        $this->pdop_data = $pdoc->getDopDataA();
                
        $text = "Пропуск N {$doc_data['altnum']}{$doc_data['subtype']} ($doc_id) от " . date("d.m.Y", $doc_data['date']);
        $this->addHeader($text);  
        $text = "К накладной N {$this->pdoc_data['altnum']}{$this->pdoc_data['subtype']} ({$this->pdoc_data['p_doc']}) от " . date("d.m.Y", $this->pdoc_data['date']);
        $this->addInfoLine($text);  
    }
    
    /// Добавить блок с телом документа
    protected function addDocumentBody() {
        $dop_data = $this->doc->getDopDataA();
        $sum = 0;
        foreach($this->doc->cnt_fields as $id=>$name) {
            if(intval($dop_data[$id])==0) {
                continue;
            }
            $text = "$name: ".$dop_data[$id];
            $this->addInfoLine($text);
            $sum += intval($dop_data[$id]);
        }
        
        $text = "Всего мест к погрузке по накладной ".$this->pdop_data['mest'] . ", по пропуску: ".$sum;
        $this->addInfoLine($text, 11);
        if(intval($this->pdop_data['mest']) != $sum) {
            $text = "количество мест не совпадает!";
            $this->addInfoLine($text, 28);
        }
        if(isset($dop_data['place'])) {
            $text = "Место хранения: ".$dop_data['place'];
            $this->addInfoLine($text);
        }
    }

    /// Добавить блок с подписями
    protected function addSignBlock() {    
        global $db;
        $doc_data = $this->doc->getDocDataA();
        $dop_data = $this->doc->getDopDataA();
        $author_name = $load_permitter = $exit_permitter = '________________';
        $res_uid = $db->query("SELECT `worker_real_name` FROM `users_worker_info`
            WHERE `user_id`='" . $doc_data['user'] . "'");
        if ($res_uid->num_rows) {
            list($author_name) = $res_uid->fetch_row();
        }

        $res_klad = $db->query("SELECT `worker_real_name` FROM `users_worker_info`
            WHERE `user_id`='" . $dop_data['load_permitter'] . "'");
        if ($res_klad->num_rows) {
            list($load_permitter) = $res_klad->fetch_row();
        } 
        
        $res_klad = $db->query("SELECT `worker_real_name` FROM `users_worker_info`
            WHERE `user_id`='" . $dop_data['exit_permitter'] . "'");
        if ($res_klad->num_rows) {
            list($exit_permitter) = $res_klad->fetch_row();
        }

        $text = "Документ создал: _____________________________________ ( $author_name )";
        $this->addSignLine($text);
        $text = "Погрузку разрешил: ___________________________________ ( $load_permitter )";
        $this->addSignLine($text);
        $text = "Выезд разрешил: ___________________________________ ( $exit_permitter )";
        $this->addSignLine($text);
        $text = "Печатная форма сформирована в: ".date("Y-m-d H:i:s");
        $this->addSignLine($text);
    }

    /// Сформировать данные печатной формы
    public function make() {
        $this->pdf->AddPage();
        $this->addTechFooter();
        
        $this->addFormHeaderBlock();      
        $this->addPartnerInfoBlock(); 
        
        
        $this->addDocumentBody();
        $this->pdf->Ln();

        $this->addSignBlock();
        return;
    } 
}
