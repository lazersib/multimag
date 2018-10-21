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

/// Документ *информация о платеже*
class doc_PayInfo extends doc_credit {
    use \doc\PrintCheck;
    
    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 24;
        $this->typename = 'payinfo';
        $this->viewname = 'Информация о платеже';
        $this->bank_modify = 1;
        $this->header_fields = 'bank kassa sum separator agent';
    }

    function initDefDopdata() {
        $this->def_dop_data = array('cardpay' => '', 'cardholder' => '', 'masked_pan' => '', 'trx_id' => '', 'p_rnn' => '', 'credit_type' => 0, 'print_check' => true);
    }

    function dopHead() {
        global $tmpl;
        if ($this->dop_data['cardpay']) {
            $tmpl->addContent("<b>Владелец карты:</b>{$this->dop_data['cardholder']}><br>
                <b>PAN карты:</b>{$this->dop_data['masked_pan']}><br><b>Транзакция:</b>{$this->dop_data['trx_id']}><br>
                <b>RNN транзакции:</b>{$this->dop_data['p_rnn']}><br>");
        }
        $checked_r = $this->dop_data['print_check'] ? 'checked' : '';
        $tmpl->addContent("<label><input type='checkbox' name='print_check' value='1' $checked_r>Печатать чек при проведении</label><br>");
    }
    
    function dopSave() {
        $new_data = array(
            'print_check' => rcvint('print_check'),
        );
        $this->setDopDataA($new_data);
    }
    
    // Провести
    function docApply($silent = 0) {
        global $db;
        if (!$this->isAltNumUnique() && !$silent) {
            throw new Exception("Номер документа не уникален!");
        }
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`bank`, `doc_list`.`kassa`, `doc_list`.`ok`, `doc_list`.`firm_id`, `doc_list`.`sum`,
                `doc_kassa`.`firm_id` AS `kassa_firm_id`, `doc_vars`.`firm_till_lock`, `doc_kassa`.`cash_register_id` AS `cr_id`
            FROM `doc_list`
            LEFT JOIN `doc_kassa` ON `doc_kassa`.`num`=`doc_list`.`kassa` AND `ids`='kassa'
            INNER JOIN `doc_vars` ON `doc_list`.`firm_id` = `doc_vars`.`id`
            WHERE `doc_list`.`id`='{$this->id}'");
        $doc_params = $res->fetch_assoc();
        $res->free();
        if(!$doc_params) {
            throw new Exception("Не удалось загрузить данные документа");
        }
        if($doc_params['bank']==0) {
            throw new Exception("Банк не задан");
        }
        parent::docApply($silent);
        if (!$silent) {
            //  Напечатать чек при необходимости
            if($doc_params['cr_id']>0 && $this->dop_data['print_check']) {
                $this->printCheck($doc_params['cr_id']);
                $this->setDopData('print_check', false);
            }
            $this->paymentNotify();
        }
    }

}
