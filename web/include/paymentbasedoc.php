<?php

//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2016, BlackLight, TND Team, http://tndproject.org
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
/// Документ-основа для приходников
class paymentbasedoc extends doc_Nulltype {

    /// Оповещение о поступившем платеже
    protected function paymentNotify() {
        $pref = \pref::getInstance();
        if(!\cfg::get('notify', 'payment') ) {
            return false;
        }
        $text = $smstext = 'Поступил платёж на сумму {SUM} р.';
        $text = \cfg::get('notify', 'payment_text', $text);
        $smstext = \cfg::get('notify', 'payment_smstext', $smstext);
 
        $s = array('{DOC}', '{SUM}', '{DATE}');
        $r = array($this->id, $this->doc_data['sum'], date('Y-m-d', $this->doc_data['date']));
        foreach($this->doc_data as $name => $value) {
            $s[] = '{'.strtoupper($name).'}';
            $r[] = $value;
        }
        foreach($this->dop_data as $name => $value) {
            $s[] = '{DOP_'.strtoupper($name).'}';
            $r[] = $value;
        }
        $text = str_replace($s, $r, $text);
        $smstext = str_replace($s, $r, $smstext);
        $zdoc = $this->getZDoc();
        if(!$zdoc) {
            $zdoc=$this;
            $zdoc->sendEmailNotify($text, "Поступила оплата N {$this->id} на {$pref->site_name}");
        } else {
            $zdoc->sendEmailNotify($text, "Поступила оплата к заказу N {$zdoc->id} на {$pref->site_name}");
        }
        $zdoc->sendSMSNotify($smstext);        
        $zdoc->sendXMPPNotify($text);
    }

}
