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

/// Документ *информация о платеже*
class doc_PayInfo extends paymentbasedoc {

    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 24;
        $this->typename = 'payinfo';
        $this->viewname = 'Информация о платеже';
        $this->bank_modify = 1;
        $this->header_fields = 'bank sum separator agent';
    }

    function initDefDopdata() {
        $this->def_dop_data = array('cardpay' => '', 'cardholder' => '', 'masked_pan' => '', 'trx_id' => '', 'p_rnn' => '', 'credit_type' => 0);
    }

    function dopHead() {
        global $tmpl;
        if ($this->dop_data['cardpay']) {
            $tmpl->addContent("<b>Владелец карты:</b>{$this->dop_data['cardholder']}><br>
                <b>PAN карты:</b>{$this->dop_data['masked_pan']}><br><b>Транзакция:</b>{$this->dop_data['trx_id']}><br>
                <b>RNN транзакции:</b>{$this->dop_data['p_rnn']}><br>");
        }
    }
    
    // Провести
    function docApply($silent = 0) {
        parent::docApply($silent);
        if (!$silent) {
            $this->paymentNotify();
        }
    }

}
