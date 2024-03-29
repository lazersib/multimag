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


/// Документ *Реализация*
class doc_Realizaciya extends doc_Nulltype {
    var $status_list;

    function __construct($doc = 0) {
        parent::__construct($doc);
        $this->doc_type = 2;
        $this->typename = 'realizaciya';
        $this->viewname = 'Реализация товара';
        $this->sklad_editor_enable = true;
        $this->header_fields = 'bank sklad cena separator agent';
        $this->status_list = array(
            'readytomake' => 'Готов к сборке', 
            'in_process' => 'В процессе сборки', 
            'readytoship' => 'Собран и готов к отгрузке', 
            'courier'=>'Передан курьеру', 
            'err' => 'Ошибочный', 
            'shipped'=>'Отгружен'
        );
    }

    /// Получить строку с HTML кодом дополнительных кнопок документа
    protected function getAdditionalButtonsHTML() {
        return "<a href='' onclick=\"ShowPopupWin('/doc.php?mode=srv&amp;opt=dov&amp;doc={$this->id}');"
                . " return false;\" title='Доверенное лицо'><img src='/img/i_users.png' alt='users'></a>".
                "<a href='' onclick=\"deliveryMenu(event, '{$this->id}'); "
                . " return false;\" title='Пометить отправленным'><img src='/img/i_ship.png' alt='users'></a>";
    }

    function initDefDopdata() {
        $this->def_dop_data = array('platelshik' => 0, 'gruzop' => 0, 'status' => '', 'kladovshik' => 0,
            'mest' => '', 'received' => 0, 'return' => 0, 'cena' => 0, 'dov_agent' => 0, 'dov' => '', 'dov_data' => '',
            'buyer_rname' => '',
            'cc_name' => '', 'cc_num' => '', 'cc_price' => '', 'cc_date' => '',  'cc_volume'=>'', 'cc_mass'=>'', );
    }

    // Создать документ с товарными остатками на основе другого документа
    public function createFromP($doc_obj) {
        parent::CreateFromP($doc_obj);
        $this->setDopData('platelshik', $doc_obj->doc_data['agent']);
        $this->setDopData('gruzop', $doc_obj->doc_data['agent']);
        unset($this->doc_data);
        $this->get_docdata();
        return $this->id;
    }
    
    public function getExtControls() {
        return $this->ext_controls = array(
            'platelshik' => [
                'type' => 'InputSelector',
                'label' => 'Плательщик',
                'data_source' => 'agent.shortlist'
            ],
            'gruzop' => [
                'type' => 'InputSelector',
                'label' => 'Грузополучатель',
                'data_source' => 'agent.shortlist'
            ],
            'zinfo' => [
                'type' => 'zinfo',
                'label' => 'Информация о заявке интернет-магазина',
            ],
            'firm_kladovshik_id' => [
                'type' => 'select',
                'label' => 'Кладовщик',
                'data_source' => 'worker.listnames',
            ],
            'mest' => [
                'type' => 'text',
                'label' => 'Количество мест',
            ],
            'received' => [
                'type' => 'checkbox',
                'label' => 'Документы подписаны и получены'
            ],
            'return' => [
                'type' => 'checkbox',
                'label' => 'Возвратный документ'
            ],
        );
    }

    function DopHead() {
        global $tmpl, $db;

        $cur_agent = $this->doc_data['agent'];
        if (!$cur_agent) {
            $cur_agent = 1;
        }
        $klad_id = @$this->dop_data['kladovshik'];
        if (!$klad_id) {
            $klad_id = $this->firm_vars['firm_kladovshik_id'];
        }

        $plat_data = $db->selectRow('doc_agent', $this->dop_data['platelshik']);
        $plat_name = $plat_data ? html_out($plat_data['name']) : '';

        $gruzop_data = $db->selectRow('doc_agent', $this->dop_data['gruzop']);
        $gruzop_name = $gruzop_data ? html_out($gruzop_data['name']) : '';

        $tmpl->addContent("<script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
		Плательщик:<br>
		<input type='hidden' name='plat_id' id='plat_id' value='{$this->dop_data['platelshik']}'>
		<input type='text' id='plat'  style='width: 100%;' value='$plat_name'><br>
		Грузополучатель:<br>
		<input type='hidden' name='gruzop_id' id='gruzop_id' value='{$this->dop_data['gruzop']}'>
		<input type='text' id='gruzop'  style='width: 100%;' value='$gruzop_name'><br>
		Кладовщик:<br><select name='kladovshik'>
		<option value='0'>--не выбран--</option>");

        $res = $db->query("SELECT `user_id`, `worker_real_name` FROM `users_worker_info` WHERE `worker`='1' ORDER BY `worker_real_name`");
        while ($nxt = $res->fetch_row()) {
            $s = ($klad_id == $nxt[0]) ? 'selected' : '';
            $tmpl->addContent("<option value='$nxt[0]' $s>" . html_out($nxt[1]) . "</option>");
        }
        $tmpl->addContent("</select><br>
		Количество мест:<br>
		<input type='text' name='mest' value='{$this->dop_data['mest']}'><br><hr>");
        if($this->doc_data['p_doc']) {
            $parent_doc = \document::getInstanceFromDb($this->doc_data['p_doc']);
            if($parent_doc->typename=='zayavka') {
                if ($parent_doc->dop_data['ishop']) {
                    $tmpl->addContent("<b>К заявке с интернет-витрины</b><br>");
                }
                if ($parent_doc->dop_data['buyer_rname']) {
                    $tmpl->addContent("<b>ФИО: </b>{$this->dop_data['buyer_rname']}<br>");
                }
                if ($parent_doc->dop_data['pay_type']) {
                    $tmpl->addContent("<b>Способ оплаты: </b>");
                    switch ($parent_doc->dop_data['pay_type']) {
                        case 'bank': $tmpl->addContent("безналичный");
                            break;
                        case 'cash': $tmpl->addContent("наличными");
                            break;
                        case 'card': $tmpl->addContent("картой ?");
                            break;
                        case 'card_o': $tmpl->addContent("картой на сайте");
                            break;
                        case 'card_t': $tmpl->addContent("картой при получении");
                            break;
                        case 'wmr': $tmpl->addContent("Webmoney WMR");
                            break;
                        default: $tmpl->addContent("не определён ({$this->dop_data['pay_type']})");
                    }
                    $tmpl->addContent("<br>");
                }
                if($parent_doc->dop_data['buyer_email']) {
                    $tmpl->addContent("<b>e-mail, прикреплённый к заявке:</b> ".html_out($parent_doc->dop_data['buyer_email'])."<br>");
                }
                if($parent_doc->dop_data['buyer_phone']) {
                    $tmpl->addContent("<b>Телефон, прикреплённый к заявке:</b> ".html_out($parent_doc->dop_data['buyer_phone'])."<br>");
                }
                if($parent_doc->dop_data['delivery']) {
                    $tmpl->addContent("<b>Доставка:</b>");
                    $res = $db->query("SELECT `id`, `name` FROM `delivery_types` ORDER BY `id`");
                    while ($nxt = $res->fetch_row()) {
                        if ($nxt[0] == $parent_doc->dop_data['delivery']) {
                            $tmpl->addContent(html_out($nxt[1]));
                        }
                    }
                    $tmpl->addContent("<br>");
                }
                if($parent_doc->dop_data['delivery_region']) {
                    $tmpl->addContent("<b>Регион доставки:</b>");
                    $res = $db->query("SELECT `id`, `name` FROM `delivery_regions` ORDER BY `id`");
                    while ($nxt = $res->fetch_row()) {
                        if ($nxt[0] == $parent_doc->dop_data['delivery_region']) {
                            $tmpl->addContent(html_out($nxt[1]));
                        }
                    }
                    $tmpl->addContent("<br>");
                }
                if($parent_doc->dop_data['delivery_date']) {
                    $tmpl->addContent("<b>Желаемая дата доставки: </b>".html_out($parent_doc->dop_data['delivery_date'])."<br>");
                }
                if ($parent_doc->dop_data['delivery_address']) {
                    $tmpl->addContent("<b>Адрес доставки: </b>{$parent_doc->dop_data['delivery_address']}<br>");
                }
                $tmpl->addContent("<hr>");
            }
        }
                
	$tmpl->addContent("Статус:<br>
		<select name='status'>");
        if ($this->dop_data['status'] == '') {
            $tmpl->addContent("<option value=''>Не задан</option>");
        }
        foreach ($this->status_list as $id => $name) {
            $s = (@$this->dop_data['status'] == $id) ? 'selected' : '';
            $tmpl->addContent("<option value='$id' $s>" . html_out($name) . "</option>");
        }

        $tmpl->addContent("</select><br>

		<script type=\"text/javascript\">
		$(document).ready(function(){
			$(\"#plat\").autocomplete(\"/docs.php\", {
			delay:300,
			minChars:1,
			matchSubset:1,
			autoFill:false,
			selectFirst:true,
			matchContains:1,
			cacheLength:10,
			maxItemsToShow:15,
			formatItem:agliFormat,
			onItemSelect:platselectItem,
			extraParams:{'l':'agent','mode':'srv','opt':'ac'}
			});
			$(\"#gruzop\").autocomplete(\"/docs.php\", {
			delay:300,
			minChars:1,
			matchSubset:1,
			autoFill:false,
			selectFirst:true,
			matchContains:1,
			cacheLength:10,
			maxItemsToShow:15,
			formatItem:agliFormat,
			onItemSelect:gruzopselectItem,
			extraParams:{'l':'agent','mode':'srv','opt':'ac'}
			});
		});

		function platselectItem(li) {
		if( li == null ) var sValue = \"Ничего не выбрано!\";
		if( !!li.extra ) var sValue = li.extra[0];
		else var sValue = li.selectValue;
		document.getElementById('plat_id').value=sValue;
		}

		function gruzopselectItem(li) {
		if( li == null ) var sValue = \"Ничего не выбрано!\";
		if( !!li.extra ) var sValue = li.extra[0];
		else var sValue = li.selectValue;
		document.getElementById('gruzop_id').value=sValue;
		}
		</script>
		");

        $checked_r = $this->dop_data['received'] ? 'checked' : '';
        $tmpl->addContent("<label><input type='checkbox' name='received' value='1' $checked_r>Документы подписаны и получены</label><br>");
        $checked = $this->dop_data['return'] ? 'checked' : '';
        $tmpl->addContent("<label><input type='checkbox' name='return' value='1' $checked>Возвратный документ</label><br>");
    }

    function DopSave() {
        $new_data = array(
            'status' => request('status'),
            'kladovshik' => rcvint('kladovshik'),
            'platelshik' => rcvint('plat_id'),
            'gruzop' => rcvint('gruzop_id'),
            'received' => request('received') ? '1' : '0',
            'return' => request('return') ? '1' : '0',
            'mest' => rcvint('mest')
        );
        $old_data = array_intersect_key($new_data, $this->dop_data);
        if ($this->id && @$old_data['status'] != $new_data['status']) {
            $this->sentZEvent('cstatus:' . $new_data['status']);
        }
        $this->setDopDataA($new_data);
    }

    function dopBody() {
        global $tmpl;
        if ($this->dop_data['received']) {
            $tmpl->addContent("<br><b>Документы подписаны и получены</b><br>");
        }
    }

    /// Выполнение дополнительных проверок доступа для проведения документа
    public function extendedApplyAclCheck() {
        $acl_obj = ['store.global', 'store.'.$this->doc_data['sklad']];      
        if (!\acl::testAccess($acl_obj, \acl::APPLY)) {
           $d_start = date_day(time());
            $d_end = $d_start + 60 * 60 * 24 - 1;
            if (!\acl::testAccess($acl_obj, \acl::TODAY_APPLY)) {
                throw new \AccessException('Не достаточно привилегий для проведения документа с выбранным складом '.$this->doc_data['sklad']);
            } elseif ($this->doc_data['date'] < $d_start || $this->doc_data['date'] > $d_end) {
                throw new \AccessException('Не достаточно привилегий для проведения документа с выбранным складом '.$this->doc_data['sklad'].' произвольной датой');
            }
        }
        parent::extendedApplyAclCheck();
    }
    
    /// Выполнение дополнительных проверок доступа для отмены документа
    public function extendedCancelAclCheck() {
        $acl_obj = ['store.global', 'store.'.$this->doc_data['sklad']];      
        if (!\acl::testAccess($acl_obj, \acl::CANCEL)) {
           $d_start = date_day(time());
            $d_end = $d_start + 60 * 60 * 24 - 1;
            if (!\acl::testAccess($acl_obj, \acl::TODAY_CANCEL)) {
                throw new \AccessException('Не достаточно привилегий для отмены проведения документа с выбранным складом '.$this->doc_data['sklad']);
            } elseif ($this->doc_data['date'] < $d_start || $this->doc_data['date'] > $d_end) {
                throw new \AccessException('Не достаточно привилегий для отмены проведения документа с выбранным складом '.$this->doc_data['sklad'].' произвольной датой');
            }
        }
        parent::extendedCancelAclCheck();
    }
    
    /// Провести документ
    function docApply($silent = 0) {
        global $CONFIG, $db;
        if(!$this->isAltNumUnique() && !$silent) {
            throw new Exception("Номер документа не уникален!");
        }
        $tim = time();
        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`, `doc_list`.`firm_id`,
                `doc_sklady`.`dnc`, `doc_sklady`.`firm_id` AS `store_firm_id`, `doc_agent`.`no_bonuses`, `doc_vars`.`firm_store_lock`, `doc_list`.`p_doc`
            FROM `doc_list`
            INNER JOIN `doc_sklady` ON `doc_sklady`.`id`=`doc_list`.`sklad`
            INNER JOIN `doc_agent` ON `doc_list`.`agent` = `doc_agent`.`id`
            INNER JOIN `doc_vars` ON `doc_list`.`firm_id` = `doc_vars`.`id`
            WHERE `doc_list`.`id`='{$this->id}'");
        $doc_params = $res->fetch_assoc();
        $res->free();
        if ($doc_params['ok'] && (!$silent)) {
            throw new Exception('Документ уже был проведён!');
        }
        if (!$this->dop_data['kladovshik'] && @$CONFIG['doc']['require_storekeeper'] && !$silent) {
            throw new Exception("Кладовщик не выбран!");
        }
        if (!$this->dop_data['mest'] && @$CONFIG['doc']['require_pack_count'] && !$silent) {
            throw new Exception("Количество мест не задано");
        }
        // Запрет на списание со склада другой фирмы
        if($doc_params['store_firm_id']!=null && $doc_params['store_firm_id']!=$doc_params['firm_id']) {
            throw new Exception("Выбранный склад принадлежит другой организации!");
        }
        // Ограничение фирмы списком своих складов
        if($doc_params['firm_store_lock'] && $doc_params['store_firm_id']!=$doc_params['firm_id']) {
            throw new Exception("Выбранная организация может списывать только со своих складов!!");
        }
        
        if (!$silent) {
            $this->sentZEvent('pre-apply'); // В т.ч. для обсчёта резервов
            $ok_time = time();
            $db->update('doc_list', $this->id, 'ok', $ok_time);
            $this->doc_data['ok'] = $ok_time;
        }

        $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base_cnt`.`cnt`, `doc_base`.`name`, `doc_base`.`proizv`,
                `doc_base`.`pos_type`, `doc_list_pos`.`id`, `doc_base`.`vc`, `doc_list_pos`.`cost`
            FROM `doc_list_pos`
            LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`
            LEFT JOIN `doc_base_cnt` ON `doc_base_cnt`.`id`=`doc_base`.`id` AND `doc_base_cnt`.`sklad`='{$doc_params['sklad']}'
        WHERE `doc_list_pos`.`doc`='{$this->id}' AND `doc_base`.`pos_type`='0'");
        $bonus = 0;
        $fail_text = '';
        while ($nxt = $res->fetch_row()) {
            if (!$doc_params['dnc']) {
                if ($nxt[1] > $nxt[2]) {
                    $pos_name = composePosNameStr($nxt[0], $nxt[7], $nxt[3], $nxt[4]);
                    $fail_text .= " - Мало товара '$pos_name' -  есть:{$nxt[2]}, нужно:{$nxt[1]}. \n";
                    continue;
                }
            }

            $db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`-'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='{$doc_params['sklad']}'");

            if (!$doc_params['dnc'] && (!$silent)) {
                $ret = getStoreCntOnDate($nxt[0], $doc_params['sklad'], $doc_params['date'], false, true);
                if ($ret['cnt'] < 0) {
                    $pos_name = composePosNameStr($nxt[0], $nxt[7], $nxt[3], $nxt[4]);
                    $fail_text .= " - Будет ({$ret['cnt']}) мало товара '$pos_name', документ {$ret['doc']} \n";
                    continue;
                }
            }

            if (\cfg::get('poseditor', 'sn_restrict')) {
                $r = $db->query("SELECT COUNT(`doc_list_sn`.`id`) FROM `doc_list_sn` WHERE `rasx_list_pos`='$nxt[6]'");
                list($sn_cnt) = $r->fetch_row();
                if ($sn_cnt != $nxt[1]) {
                    $pos_name = composePosNameStr($nxt[0], $nxt[7], $nxt[3], $nxt[4]);
                    $fail_text .= " - Мало серийных номеров товара '$pos_name' - есть:$sn_cnt, нужно:{$nxt[1]}. \n";
                    continue;
                }
            }
            $bonus+=$nxt[8] * $nxt[1] * \cfg::get('bonus', 'coeff', 0);
        }        
        if($fail_text) {
            throw new Exception("Ошибка в номенклатуре: \n".$fail_text);
        }        
        if ($silent) {
            return;
        }
        $this->fixPrice();

        if (!$doc_params['no_bonuses'] && $bonus > 0) {
            $db->query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES ( '{$this->id}' ,'bonus','$bonus')");
        }

        $this->sentZEvent('apply');
    }

    function docCancel() {
        global $db;

        $res = $db->query("SELECT `doc_list`.`id`, `doc_list`.`date`, `doc_list`.`type`, `doc_list`.`sklad`, `doc_list`.`ok`
		FROM `doc_list` WHERE `doc_list`.`id`='{$this->id}'");
        if (!$res->num_rows) {
            throw new Exception('Документ не найден!');
        }
        $nx = $res->fetch_row();
        if (!$nx[4]) {
            throw new Exception('Документ НЕ проведён!');
        }        
        $res = $db->query("SELECT `id` FROM `doc_list` WHERE `p_doc`='{$this->id}' AND `ok`>'0'");
        if ($res->num_rows) {
            throw new Exception('Нельзя отменять документ с проведёнными подчинёнными документами.');
        }
        
        $this->sentZEvent('pre-cancel'); // В т.ч. для обсчёта резервов
        $db->update('doc_list', $this->id, 'ok', 0);
        $this->doc_data['ok'] = 0;
        
        $res = $db->query("SELECT `doc_list_pos`.`tovar`, `doc_list_pos`.`cnt`, `doc_base`.`pos_type` FROM `doc_list_pos`
            LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_list_pos`.`tovar`	WHERE `doc_list_pos`.`doc`='{$this->id}' AND `doc_base`.`pos_type`='0'");
        while ($nxt = $res->fetch_row()) {
            $db->query("UPDATE `doc_base_cnt` SET `cnt`=`cnt`+'$nxt[1]' WHERE `id`='$nxt[0]' AND `sklad`='$nx[3]'");
        }
        $db->query("REPLACE INTO `doc_dopdata` (`doc`,`param`,`value`)	VALUES ( '{$this->id}' ,'bonus','0')");

        $this->sentZEvent('cancel');
    }
    
    /**
     * Получить список документов, которые можно создать на основе этого
     * @return array Список документов
     */
    public function getMorphList() {
        $morphs = array(
            'permitout' =>      ['name'=>'permitout',   'document' => 'permitout',  'viewname' => 'Пропуск на вывоз', ],
            'pbank' =>          ['name'=>'pbank',       'document' => 'pbank',      'viewname' => 'Приходный банковский ордер', ],
            'pko' =>            ['name'=>'pko',         'document' => 'pko',        'viewname' => 'Приходный кассовый ордер', ],
            'payinfo' =>        ['name'=>'payinfo',     'document' => 'payinfo',    'viewname' => 'Информация о безналичном платеже', ],
            'kordolga' =>       ['name'=>'kordolga',    'document' => 'kordolga',   'viewname' => 'Корректировка долга', ],
        );
        if (!$this->doc_data['p_doc']) {
            $morphs['zayavka'] = ['name'=>'zayavka',    'document' => 'zayavka',    'viewname' => 'Заявка (родительская)', ];
        }
        return $morphs;
    }
    
    /** Создать родительскою заявку
     * 
     * @return \doc_Zayavka Документ заявки, как основание
     * @throws Exception В случае если у документа уже есть основание
     */
    protected function morphTo_zayavka() {
        if($this->doc_data['p_doc']) {
            throw new Exception('У документа уже есть основание.');
        }
        $new_doc = new \doc_Zayavka();
        $dd = $new_doc->CreateParent($this);
        $new_doc->setDopData('cena', $this->dop_data['cena']);
        $this->setDocData('p_doc', $dd);
        return $new_doc;
    }
    
    /** Создать подчинённый приходный кассовый ордер
     * 
     * @return \doc_Pko Подчинённый приходный кассовый ордер
     */
    protected function morphTo_pko() {
    
        $this->recalcSum();
        $new_doc = new \doc_Pko();
        $new_doc->createFrom($this);
        $pref = \pref::getInstance();
        $new_doc->setDocData('kassa', $pref->getSitePref('site_cash_id'));
        $codename = $this->getDopData('return')?'goods_return':'goods_sell';
        $new_doc->setCreditTypeFromCodename($codename);
        return $new_doc;
    }
    
    /** Создать подчинённый информация о платеже
     * 
     * @return \doc_Pko Подчинённый информация о платеже
     */
    protected function morphTo_payinfo() {
    
        $this->recalcSum();
        $new_doc = new \doc_PayInfo();
        $new_doc->createFrom($this);
        $pref = \pref::getInstance();
        $new_doc->setDocData('bank', $this->getDocData('bank'));
        return $new_doc;
    }
    
    /** Создать подчинённый приходный банковский ордер
     * 
     * @return \doc_PbankПодчинённый приходный банковский ордер
     */
    protected function morphTo_pbank() {
        $this->recalcSum();
        $new_doc = new \doc_Pbank();
        $new_doc->createFrom($this);
        $pref = \pref::getInstance();
        $new_doc->setDocData('bank', $pref->getSitePref('site_bank_id'));
        $codename = $this->getDopData('return')?'goods_return':'goods_sell';
        $new_doc->setCreditTypeFromCodename($codename);
        return $new_doc;
    }
    
    /** Создать подчинённую корректировку долга на сумму реализации
     * 
     * @return \doc_Kordolga
     */
    protected function morphTo_kordolga() {
        $this->recalcSum();
        $new_doc = new \doc_Kordolga();
        $new_doc->createFrom($this);
        $new_doc->setDocData('sum', $this->doc_data['sum'] * (-1));
        return $new_doc;
    }
    
    /** Создать подчинённый пропуск на вывоз товара
     * 
     * @return \doc_PermitOut
     */
    protected function morphTo_permitout() {
        $this->recalcSum();
        $new_doc = new \doc_PermitOut();
        $new_doc->createFrom($this);
        return $new_doc;
    }

    protected function getDovForm() {
        global $db;
        $info = $db->selectRowA('doc_agent_dov', $this->dop_data['dov_agent'], array('name', 'surname'));
        $agn = '';
        if ($info['name']) {
            $agn = $info['name'];
        }
        if ($info['surname']) {
            if ($agn) {
                $agn.=' ';
            }
            $agn.=$info['surname'];
        }

        return "<form method='post' action=''>
<input type=hidden name='mode' value='srv'>
<input type=hidden name='opt' value='dovs'>
<input type=hidden name='doc' value='{$this->id}'>
<table>
<tr><th>Доверенное лицо (<a href='docs.php?l=dov&mode=edit&ag_id={$this->doc_data['agent']}' title='Добавить'><img border=0 src='img/i_add.png' alt='add'></a>)
<tr><td><input type=hidden name=dov_agent value='" . $this->dop_data['dov_agent'] . "' id='sid' ><input type=text id='sdata' value='$agn' onkeydown=\"return RequestData('/docs.php?l=dov&mode=srv&opt=popup&ag={$this->doc_data['agent']}')\">
		<div id='popup'></div>
		<div id=status></div>

<tr><th class=mini>Номер доверенности
<tr><td><input type=text name=dov value='" . $this->dop_data['dov'] . "' class=text>

<tr><th>Дата выдачи
<tr><td>
<p class='datetime'>
<input type=text name=dov_data value='" . $this->dop_data['dov_data'] . "' id='id_pub_date_date'  class='vDateField required text' >
</p>

</table>
<input type=submit value='Сохранить'></form>";
    }
    
    public function Service() {
        global $tmpl;

        $tmpl->ajax = 1;
        $opt = request('opt');
        $pos = request('pos');
        
        switch($opt) {
            case 'selfship':
                \acl::accessGuard('doc.'.$this->typename, \acl::UPDATE);
                $this->setDopData('status', 'shipped');            
                $this->sentZEvent('selfship');
                $ret = array(
                    'response' => 'selfship ',
                    'status' => 'ok',
                );
                $tmpl->setContent(json_encode($ret, JSON_UNESCAPED_UNICODE));
                return true;
            case 'ship_info':
                \acl::accessGuard('doc.'.$this->typename, \acl::VIEW);
                $tmpl->ajax = true;
                $ret = array(
                    'response' => 'ship_info',
                    'status' => 'ok',
                    'name' => $this->dop_data['cc_name'],
                    'num' => $this->dop_data['cc_num'],
                    'price' => $this->dop_data['cc_price'],
                    'date' => $this->dop_data['cc_date'],
                    'volume' => $this->dop_data['cc_volume'],
                    'mass' => $this->dop_data['cc_mass'],
                );
                $tmpl->setContent(json_encode($ret, JSON_UNESCAPED_UNICODE));
                return true;
            case 'ship_enter':
                \acl::accessGuard('doc.'.$this->typename, \acl::UPDATE);
                $cc_info = array(
                    'cc_name' => request('cc_name'),
                    'cc_num' => request('cc_num'),
                    'cc_date' => rcvdate('cc_date'),
                    'cc_price' => rcvrounded('cc_price', 2),
                    'status' => 'shipped'
                );
                $this->setDopDataA($cc_info);
                $this->sentZEvent('shipped');
                $ret = array(
                    'response' => 'ship_enter',
                    'status' => 'ok',
                );
                $tmpl->setContent(json_encode($ret, JSON_UNESCAPED_UNICODE));
                return true;
        }
        
        if (parent::_Service($opt, $pos)) {
            return true;
        } 
        
        switch($opt) {
            case 'dov':
                $tmpl->setContent( $this->getDovForm() );
                return true;
            case 'dovs':
                \acl::accessGuard('doc.'.$this->typename, \acl::UPDATE);
                $data = array(
                    'dov' => request('dov'),
                    'dov_agent' => request('dov_agent'),
                    'dov_data' => request('dov_data'),
                );
                $this->setDopDataA($data);
                redirect("/doc.php?mode=body&doc={$this->id}");
                return true;
            default:
                throw new \NotFoundException("Неизвестная опция $opt!");
        }
    }
}
