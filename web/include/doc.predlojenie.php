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

/// Документ *Предложение поставщика*
class doc_Predlojenie extends doc_Nulltype {

    /// Конструктор
    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 11;
        $this->typename = 'predlojenie';
        $this->viewname = 'Предложение поставщика';
        $this->sklad_editor_enable = true;
        $this->header_fields = 'sklad cena separator agent';
    }
    
    function initDefDopdata() {
        $this->def_dop_data = array('cena' => 1);
    }
    
    /// Провести документ
    function docApply($silent = 0) {
        global $db;      
        parent::docApply($silent);

        $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`
            FROM `doc_list_pos`
            LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
            WHERE `doc_list_pos`.`doc`='{$this->id}'");
        $vals = '';
        while ($nxt = $res->fetch_row()) {
            if($vals) {
                $vals .= ',';
            }
            $vals .= "('$nxt[0]', '$nxt[1]')";
            
        }
        if($vals) {
            $db->query("INSERT INTO `doc_base_dop` (`id`, `offer`) VALUES $vals
                ON DUPLICATE KEY UPDATE `offer`=`offer`+VALUES(`offer`)");
        } else {
            throw new Exception("Не удалось провести пустой документ!");
        }
    }
    
    /// Отменить проводку документа
    function docCancel() {
        global $CONFIG, $db;
        if (!$this->doc_data['ok']) {
            throw new Exception('Документ не был проведён');
        }        
        $db->update('doc_list', $this->id, 'ok', 0);
        $this->doc_data['ok'] = 0;

        $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`
            FROM `doc_list_pos`
            LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
            WHERE `doc_list_pos`.`doc`='{$this->id}'");
        $vals = '';
        while ($nxt = $res->fetch_row()) {
            if($vals) {
                $vals .= ',';
            }
            $vals .= "('$nxt[0]', '-$nxt[1]')";
            
        }
        if($vals) {
            $db->query("INSERT INTO `doc_base_dop` (`id`, `offer`) VALUES $vals
                ON DUPLICATE KEY UPDATE `offer`=`offer`+VALUES(`offer`)");
        }
        parent::docCancel();
    }

    /// Формирование другого документа на основании текущего
    function MorphTo($target_type) {
        global $tmpl;

        if ($target_type == '') {
            $tmpl->ajax = 1;
            $tmpl->addContent("<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=1'\">Поступление</div>
			<div onclick=\"window.location='/doc.php?mode=morphto&amp;doc={$this->id}&amp;tt=12'\">Товар в пути</div>");
        } else if ($target_type == 1) {
            \acl::accessGuard('doc.postuplenie', \acl::CREATE);
            $base = $this->Postup();
            redirect('/doc.php?mode=body&doc=' . $base);
        }
        else if ($target_type == 12) {
            \acl::accessGuard('doc.v_puti', \acl::CREATE);
            $base = $this->Vputi();
            redirect('/doc.php?mode=body&doc=' . $base);
        }
    }

// ================== Функции только этого класса ======================================================
    function Postup() {
        global $db;
        $target_type = 1;
        $db->startTransaction();
        $res = $db->query("SELECT `id` FROM `doc_list` WHERE `p_doc`='$this->id' AND `type`='$target_type'");
        if (!$res->num_rows) {
            DocSumUpdate($this->id);
            $new_doc = new doc_Postuplenie();
            $x_doc_num = $new_doc->createFromP($this);
            $new_doc->setDopData('cena', $this->dop_data['cena']);
        } else {
            $x_doc_info = $res->fetch_row();
            $x_doc_num = $x_doc_info[0];
            $new_id = 0;
            $res = $db->query("SELECT `a`.`tovar`, `a`.`cnt`, `a`.`comm`, `a`.`cost`,
			( SELECT SUM(`b`.`cnt`) FROM `doc_list_pos` AS `b`
			  INNER JOIN `doc_list` ON `b`.`doc`=`doc_list`.`id` AND `doc_list`.`p_doc`='{$this->id}'
			  WHERE `b`.`tovar`=`a`.`tovar` )
			FROM `doc_list_pos` AS `a`
			WHERE `a`.`doc`='{$this->id}'
			ORDER BY `doc_list_pos`.`id`");
            while ($nxt = $res->fetch_row()) {
                if ($nxt[4] < $nxt[1]) {
                    if (!$new_id) {
                        $new_doc = new doc_Postuplenie();
                        $new_id = $new_doc->createFrom($this);
                        $new_doc->setDopData('cena', $this->dop_data['cena']);
                    }
                    $n_cnt = $nxt[1] - $nxt[4];
                    $db->query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `comm`, `cost`)
 					VALUES ('$new_id', '$nxt[0]', '$n_cnt', '$nxt[2]', '$nxt[3]' )");
                }
            }
            if ($new_id) {
                $x_doc_num = $new_id;
            }
        }
        $db->commit();
        return $x_doc_num;
    }

    //	================== Функции только этого класса ======================================================
    function Vputi() {
        global $db;
        $target_type = 1;
        $db->startTransaction();
        $res = $db->query("SELECT `id` FROM `doc_list` WHERE `p_doc`='$this->id' AND `type`='$target_type'");
        if (!$res->num_rows) {
            DocSumUpdate($this->id);
            $new_doc = new doc_v_puti();
            $x_doc_num = $new_doc->createFromP($this);
            $new_doc->setDopData('cena', $this->dop_data['cena']);
        } else {
            $x_doc_info = $res->fetch_row();
            $x_doc_num = $x_doc_info[0];
            $new_id = 0;
            $res = $db->query("SELECT `a`.`tovar`, `a`.`cnt`, `a`.`comm`, `a`.`cost`,
			( SELECT SUM(`b`.`cnt`) FROM `doc_list_pos` AS `b`
			  INNER JOIN `doc_list` ON `b`.`doc`=`doc_list`.`id` AND `doc_list`.`p_doc`='{$this->id}'
			  WHERE `b`.`tovar`=`a`.`tovar` )
			FROM `doc_list_pos` AS `a`
			WHERE `a`.`doc`='{$this->id}'
			ORDER BY `doc_list_pos`.`id`");
            while ($nxt = $res->fetch_row()) {
                if ($nxt[4] < $nxt[1]) {
                    if (!$new_id) {
                        $new_doc = new doc_v_puti();
                        $new_id = $new_doc->createFrom($this);
                        $new_doc->setDopData('cena', $this->dop_data['cena']);
                    }
                    $n_cnt = $nxt[1] - $nxt[4];
                    $db->query("INSERT INTO `doc_list_pos` (`doc`, `tovar`, `cnt`, `comm`, `cost`)
 					VALUES ('$new_id', '$nxt[0]', '$n_cnt', '$nxt[2]', '$nxt[3]' )");
                }
            }
            if ($new_id) {
                $x_doc_num = $new_id;
            }
        }
        $db->commit();
        return $x_doc_num;
    }
}
