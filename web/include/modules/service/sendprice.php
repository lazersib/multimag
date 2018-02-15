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
namespace modules\service;

/// Модуль управления рассылкой прайс-листов
class sendprice extends \IModule {

    protected $logdata = array();
    protected $pers = array('daily'=>'Ежедневно', 'weekly'=>'Еженедельно','monthly'=>'Ежемесячно');
    protected $cnts = array('all'=>'Все', 'instock'=>'Только в наличии', 'intransit'=>'В наличии + в пути');
    protected $formats = array('xls','csv');

    public function __construct() {
        parent::__construct();  
        $this->acl_object_name = 'service.sendprice';
        $this->table_name = 'prices_delivery';
    }
    
    function draw_groups_tree($level, $groups, $disabled = false) {
        global $db;
        $ret = '';
        settype($level, 'int');
        $res = $db->query("SELECT `id`, `name`, `desc` FROM `doc_group` WHERE `pid`='$level' ORDER BY `name`");
        $i = 0;
        $r = $cbroot = '';
        if ($level == 0) {
            $r = 'IsRoot';
            $cbroot = " data-isroot='1'";
        }
        $cnt = $res->num_rows;
        while ($nxt = $res->fetch_row()) {
            if ($nxt[0] == 0) {
                continue;
            }
            if(!is_array($groups)) {
                $ch = ' checked';
            }
            else if(in_array($nxt[0], $groups)) {
                $ch = ' checked';
            }
            else {
                $ch = '';
            }
            $dis = $disabled?' disabled':'';
            $item = "<label><input type='checkbox' name='g[]'{$cbroot} value='$nxt[0]' id='cb$nxt[0]' class='cb'{$ch}{$dis} onclick='CheckCheck($nxt[0])'>$nxt[1]</label>";
            if ($i >= ($cnt - 1)) {
                $r.=" IsLast";
            }
            $tmp = $this->draw_groups_tree($nxt[0], $groups, $ch?0:1); // рекурсия
            if ($tmp) {
                $ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div><ul class='Container' id='cont$nxt[0]'>" . $tmp . '</ul></li>';
            } else {
                $ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
            }
            $i++;
        }
        return $ret;
    }

    function groupSelBlock($enabled=false, $groups=false) {
        global $tmpl;
        $sb = ($enabled)?' auto':' none';
        $tmpl->addStyle(".scroll_block{
                    max-height:		250px;
                    overflow:		auto;
            }

            div#sb{
                    display:		$sb;
                    border:			1px solid #888;
            }

            .selmenu{
                    background-color:	#888;
                    width:			auto;
                    font-weight:		bold;
                    padding-left:		20px;
            }

            .selmenu a{
                    color:			#fff;
                    cursor:			pointer;
            }

            .cb{
                    width:			14px;
                    height:			14px;
                    border:			1px solid #ccc;
            }

            ");
        $sel = ($enabled)?' checked':'';
        return "<script type='text/javascript'>
            function gstoggle(){
                    var gs=document.getElementById('cgs').checked;
                    if(gs==true)
                            document.getElementById('sb').style.display='block';
                    else	document.getElementById('sb').style.display='none';
            }

            function SelAll(flag){
                    var elems = document.getElementsByName('g[]');
                    var l = elems.length;
                    for(var i=0; i<l; i++){
                            elems[i].checked=flag;
                            if(flag) {
                                elems[i].disabled = false;
                            }
                            else {
                                var isroot = elems[i].getAttribute('data-isroot');
                                if(!isroot) {
                                    elems[i].disabled = true;
                                }
                            }
                    }
            }

            function CheckCheck(ids){
                    var cb = document.getElementById('cb'+ids);
                    var cont=document.getElementById('cont'+ids);
                    if(!cont)	return;
                    var elems=cont.getElementsByTagName('input');
                    var l = elems.length;
                    for(var i=0; i<l; i++){
                            if(!cb.checked)		elems[i].checked=false;
                            elems[i].disabled =! cb.checked;
                    }
            }

            </script>
            <label><input type=checkbox name='gs' id='cgs' value='1' onclick='gstoggle()'$sel>Выбрать группы</label><br>
            <div class='scroll_block' id='sb'>
            <ul class='Container'>
            <div class='selmenu'><a onclick='SelAll(true)'>Выбрать всё<a> | <a onclick='SelAll(false)'>Снять всё</a></div>
            " . $this->draw_groups_tree(0, $groups) . "</ul></div>";
    }
    
    /// Получить данные элемента справочника
    public function getItem($id) {
        global $db;
        return $db->selectRow($this->table_name, $id);
    }  
    
    /// @brief Возвращает имя элемента
    public function getItemName($item) {
        if (isset($item['name'])) {
            return $item['name'];
        } else if (isset($item['id'])) {
            return $item['id'];
        } else {
            return '???';
        }
    }
    
    public function getList() {
        global $db;
        $ret = array();
        $ldo = new \Models\LDO\pricenames();
        $price_names = $ldo->getData();
        $res = $db->query("SELECT `id`, `name`, `period`, `format`, `use_zip`, `price_id`, `filters` FROM `{$this->table_name}`");
        while($line=$res->fetch_assoc()) {
            $line['filters'] = json_decode($line['filters']);
            $cr = $db->query("SELECT `agent_contacts`.`no_ads`"
                . " FROM `prices_delivery_contact`"
                . " INNER JOIN `agent_contacts` ON `agent_contacts`.`id`=`prices_delivery_contact`.`agent_contacts_id` AND `agent_contacts`.`type`='email'"
                . " WHERE `prices_delivery_contact`.`prices_delivery_id`='{$line['id']}'");
            $line['contacts'] = $line['subscribers'] = $cr->num_rows;
            while($l = $cr->fetch_row()) {
                if($l[0]) {
                    $line['subscribers']--;
                }
            }
            $line['price_name'] = isset($price_names[$line['price_id']])?$price_names[$line['price_id']]:'';
            $ret[$line['id']] = $line;
        }
        return $ret;
    }
    
    /// Отобразить таблицу со списком рассылок прайсов
    public function viewList() {
        global $tmpl;
        $list = $this->getList();
        $tmpl->addContent("<table class='list'><tr><th>Id</th><th>Название</th><th>Периодичность</th><th>Формат</th><th>Цена</th><th>Контактов</th></tr>");
        foreach($list as $id=>$line) {
            if(isset($this->pers[$line['period']])) {
                $p = $this->pers[$line['period']];
            }
            else {
                $p = 'Неизвестно';
            }
            $as = $line['contacts']>$line['subscribers']?" style='color:#f00'":'';                        
            $format = $line['format'];
            if($line['use_zip']) {
                $format .= '.zip';
            }
            $tmpl->addContent("<tr><td><a href='{$this->link_prefix}&amp;sect=edit&amp;id={$line['id']}'>{$line['id']}</a></td>"
            . "<td>".html_out($line['name'])."</td><td>$p</td><td>$format</td><td>".html_out($line['price_name'])."</td>"
            . "<td><a href='{$this->link_prefix}&amp;sect=vs&amp;id={$line['id']}'{$as}>{$line['contacts']}/{$line['subscribers']}</a></td>"
            . "</tr>");
        }
        $tmpl->addContent("</table>");
        $tmpl->addContent("<a href='{$this->link_prefix}&amp;sect=new'>Добавить</a>");
    }
    
    /// Отобразить форму для редактирования элемента
    public function editForm($id) {
        global $tmpl;
        $ret = "<form action='{$this->link_prefix}' method='post'>";
        $ret .= "<input type='hidden' name='sect' value='save'>";

        $item = $this->getItem($id);
        $filters = array('vendor'=>'', 'count'=>'all', 'price_id'=>1);
        if ($item) {
            $ret .= "<input type='hidden' name='id' value='$id'>";
            $name = $this->getItemName($item);
            if($name) {
                $tmpl->addBreadcrumb('Правка рассылки "' . $this->getItemName($item) . '"', '');
            }
            else {
                $tmpl->addBreadcrumb('Правка рассылки #'.$id, '');
            }
            $filters = json_decode($item['filters'], true);
            
        } else {
            $ret .= "<input type='hidden' name='id' value='null'>";
            $tmpl->addBreadcrumb('Новая рассылка', '');
        }
        $ret .= "<table class='list' width='600px'><tr>";
        
        $ret .= "<tr><td align='right'>Название рассылки</td>"
            . "<td><input type='text' name='name' value='" . html_out($item['name']) . "' style='width:95%;'></td>"
            . "</tr>";        
        
        $ret .= "<tr><td align='right'>Периодичность</td><td>";
        foreach($this->pers as $p_id=>$p_value) {
            $sel = ($p_id==$item['period'])?' checked':'';
            $ret .= "<label><input type='radio' name='period' value='$p_id'{$sel}>$p_value</label><br>";
        }
        $ret .= "</td></tr>";
        
        $ret .= "<tr><td align='right'>Формат</td>"
            . "<td>";
        foreach($this->formats as $p_id) {
            $sel = ($p_id==$item['format'])?' checked':'';
            $ret .= "<label><input type='radio' name='format' value='$p_id'{$sel}>$p_id</label><br>";
        }
        $sel = ($item['use_zip'])?' checked':'';
        $ret .= "<label><input type='checkbox' name='use_zip' value='1'{$sel}>Упаковать в ZIP</label><br>";
        $ret .= "</td></tr>";
        $ret .= "<tr><td align='right'>Цена</td><td>";
        $ldo = new \Models\LDO\pricenames();
        $ret .= \widgets::getEscapedSelect('price_id', $ldo->getData(), $item['price_id']);
        $ret .= "</td></tr>";
        $ret .= "<tr><td align='right'>Фильтр по производителю</td>"
            . "<td><input type='text' name='vendor' value='" . html_out($filters['vendor']) . "' style='width:95%;'></td>"
            . "</tr>";
        
        if(!isset($filters['count'])) {
            $filters['count'] = 'all';
        }
        if(!isset($filters['view_pgroup'])) {
            $filters['view_pgroup'] = true;
        }
        if(!isset($filters['view_vendor'])) {
            $filters['view_vendor'] = true;
        }
        $ret .= "<tr><td align='right'>Фильтр по наличию</td><td>";
        foreach($this->cnts as $p_id=>$p_value) {
            $sel = ($p_id==$filters['count'])?' checked':'';
            $ret .= "<label><input type='radio' name='count' value='$p_id'{$sel}>$p_value</label><br>";
        }
        $ret .= "</td></tr>";
        
        if(!isset($filters['groups_only'])) {
            $filters['groups_only'] = false;
            $filters['groups_list'] = false;
        }
        $ret .= "<tr><td align='right'>Фильтр по группам товаров</td>"
            . "<td>".$this->groupSelBlock($filters['groups_only'], $filters['groups_list'])."</td>"
            . "</tr>";
        
        $pgroup_sel = ($filters['view_pgroup'])?' checked':'';
        $vendoe_sel = ($filters['view_vendor'])?' checked':'';
        $ret .= "<tr><td align='right'>Настройки отображения</td>"
            . "<td>"
            . "<label><input type='checkbox' name='view_pgroup' value='1'{$pgroup_sel}>Отображать префиксы групп у товаров</label><br>"
            . "<label><input type='checkbox' name='view_vendor' value='1'{$vendoe_sel}>Отображать производителя у товаров</label><br>"
            . "</td>"
            . "</tr>";
        $ret .= "<tr><td align='right'>Тело письма</td>"
            . "<td><textarea name='lettertext'>" . html_out($item['lettertext']) . "</textarea></td>"
            . "</tr>";
        
        $ret .= "<tr><td>&nbsp;</td><td><button type='submit'>Записать</button></td></tr>";
        $ret .= "</table></form>";
        return $ret;
    }
    
    /// Записать в базу строку справочника
    public function saveItem($id, $data) {
        global $db;
        $write_data = array();
                
        $write_data['name'] = $data['name'];
        $write_data['period'] = $data['period'];
        $write_data['format'] = $data['format'];
        $write_data['lettertext'] = $data['lettertext'];
        $write_data['use_zip'] = $data['use_zip'];
        $write_data['price_id'] = $data['price_id'];
        
        $filters = array (
            'vendor' => $data['vendor'],
            'count' => $data['count'],
            'view_pgroup' => $data['view_pgroup'],
            'view_vendor' => $data['view_vendor'],
        );
        if(isset($data['gs']) && $data['gs'] && is_array($data['groups'])) {
            $filters['groups_only'] = $data['gs'];
            $filters['groups_list'] = $data['groups'];
        }
               
        $write_data['filters'] = json_encode($filters, JSON_UNESCAPED_UNICODE);
        if ($id) {
            \acl::accessGuard($this->acl_object_name, \acl::UPDATE);
            $db->updateA($this->table_name, $id, $write_data);
        } else {
            \acl::accessGuard($this->acl_object_name, \acl::CREATE);
            $id = $db->insertA($this->table_name, $write_data);
        }
        return $id;
    }
    
    protected function getRemoveSubscriberForm($list_id, $contact_id) {
        return "<form action='{$this->link_prefix}' method='post'>"
        . "<input type='hidden' name='sect' value='contactrm'>"
        . "<input type='hidden' name='id' value='$list_id'>"
        . "<input type='hidden' name='contact_id' value='$contact_id'>"
        . "<button type='submit'>Исключить</button>"
        . "</form>";
    }
    
    protected function rmContact($list_id, $contact_id) {
        global $db, $tmpl;
        \acl::accessGuard($this->acl_object_name, \acl::DELETE);
        settype($list_id, 'int');
        settype($contact_id, 'int');
        $db->query("DELETE FROM `prices_delivery_contact` WHERE `prices_delivery_id`='$list_id' AND `agent_contacts_id`='$contact_id'");
        if($db->affected_rows) {
            $tmpl->msg("Контакт удален из рассылки", 'ok');
        }
    }
    
    protected function addAgentToList($list_id, $agent_id) {
        global $db;
        \acl::accessGuard($this->acl_object_name, \acl::UPDATE);
        settype($list_id, 'int');
        settype($agent_id, 'int');
        if($list_id<=0) {
            throw new \Exception("Рассылка не выбрана");
        }
        if($agent_id<=0) {
            throw new \Exception("Агент не выбран");
        }
        $res = $db->query("SELECT `agent_contacts`.`id` AS `agent_contacts_id`, `agent_contacts`.`value`"
            . " FROM `agent_contacts`"
            . " WHERE `agent_contacts`.`agent_id`='$agent_id' AND `agent_contacts`.`type`='email'");
        $count = 0;
        while($line=$res->fetch_assoc()) {
            if(!$line['value']) {
                continue;
            }
            unset($line['value']);
            $line['prices_delivery_id'] = $list_id;
            $db->insertA('prices_delivery_contact',$line);
            $count++;
        }
        return $count;        
    }
    
    protected function viewInsertAgent($list_id, $agent_id) {
        global $tmpl;
        try {
            $count = $this->addAgentToList($list_id, $agent_id);
            if($count>0) {
                $tmpl->msg("Добавлено $count адресов","ok");
            }
            else {
                $tmpl->msg("Не найдено ни одного адреса email у выбранного контакта!","info");
            }
        } catch (\mysqli_sql_exception $e) {
            $id = writeLogException($e);
            $tmpl->errorMessage("Порядковый номер ошибки: $id<br>Сообщение об ошибке занесено в журнал", "Ошибка в базе данных");
        } catch (\Exception $e) {
            $tmpl->errorMessage($e->getMessage());
        }
        $this->viewAgentsSubscribers($list_id);
    }
    
    protected function viewAgentsSubscribers($list_id) {
        global $tmpl, $db;
        $table_header = array('Id', 'Агент', 'email', 'Имя', 'Должность','');
        $table_body = array();
        $exist = false;
        $res = $db->query("SELECT `agent_contacts`.`id`, `agents`.`name`, `agent_contacts`.`value`"
                    . ", `agent_contacts`.`person_name`, `agent_contacts`.`person_post`, `agent_contacts`.`no_ads`"
                . " FROM `prices_delivery_contact`"
                . " INNER JOIN `agent_contacts` ON `agent_contacts`.`id`=`prices_delivery_contact`.`agent_contacts_id` AND `agent_contacts`.`type`='email'"
                . " INNER JOIN `doc_agent` AS `agents` ON `agents`.`id`=`agent_contacts`.`agent_id`"
                . " WHERE `prices_delivery_contact`.`prices_delivery_id`='$list_id'");
        while($line = $res->fetch_assoc()) {
            if($line['no_ads']) {
                $line['value'] = "<span style='color:#f00'>".html_out($line['value'])."</span>";
            }
            else {
                $line['value'] = html_out($line['value']);
            }
            $table_body[] = array(
                $line['id'], html_out($line['name']), $line['value'], html_out($line['person_name']), html_out($line['person_post']),
                $this->getRemoveSubscriberForm($list_id, $line['id'])
            );
            $exist = true;
        }
        if($exist) {
            $tmpl->addTableWidget($table_header, $table_body, 20);
        } else {
            $tmpl->msg("Подписчики отсутствуют");
        }
        $tmpl->addContent("<form method='post' action='{$this->link_prefix}'>
            <script type='text/javascript' src='/css/jquery/jquery.js'></script>
            <script type='text/javascript' src='/css/jquery/jquery.autocomplete.js'></script>
            <input type='hidden' name='sect' value='agentins'>
            <input type='hidden' name='id' value='$list_id'>
            <input type='hidden' name='agent_id' id='agent_id' value='0'>
            <input type='text' id='user_nm' style='width: 450px;' value=''><br>
            <script type=\"text/javascript\">
	        $(document).ready(function(){
	                $(\"#user_nm\").autocomplete(\"/service.php\", {
	                        delay:300,
	                        minChars:1,
	                        matchSubset:1,
	                        autoFill:false,
	                        selectFirst:true,
	                        matchContains:1,
	                        cacheLength:10,
	                        maxItemsToShow:15,
	                        formatItem:usliFormat,
                                onItemSelect:usselectItem,
	                        extraParams:{'mode':'sendprice','sect':'apl'}
	                });
	        });	
	        function usliFormat (row, i, num) {
	                var result = row[0] + \"<em class='qnt'>: \" +
	                row[2] + \"</em> \";
	                return result;
	        }
                function usselectItem(li) {
	                if( li == null ) var sValue = \"Ничего не выбрано!\";
	                if( !!li.extra ) var sValue = li.extra[0];
	                else var sValue = li.selectValue;
	                document.getElementById('agent_id').value=sValue;
	        }
	        </script>"
        . "<button type='submit'>Добавить</button>"
        . "</form>");
    }

    // Получить название модуля
    /// @return Строка с именем
    public function getName() {
        return 'Управление рассылкой прайс-листов';
    }

    /// Получить описание модуля
    /// @return Строка с описанием
    public function getDescription() {
        return 'Модуль позволяет задать правила рассылки прайс-листов выбранным клиентам';
    }

    protected function getAgentsList($str) {
        global $db, $tmpl;
        $tmpl->ajax = 1;
        $s = $db->real_escape_string($str);
        $res=$db->query("SELECT `id`, `name`, `fullname` FROM `doc_agent` WHERE `name` LIKE '%$s%'");
        while($nxt=$res->fetch_row()) {
                echo"$nxt[1]|$nxt[0]|$nxt[2]\n";
        }
    }
    
    protected $items = array(
            'new' => 'Новая рассылка',
            'auth_acl' => 'Привилегии аутентифицированных пользователей',
            'gle' => 'Редактор списка ролей пользователей',
            'groups' => 'Привилегии для ролей пользователей',
        );
    
    /// Запустить модуль на исполнение
    public function run() {
        global $tmpl;
        \acl::accessGuard($this->acl_object_name, \acl::VIEW);
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect', '');
        
        switch ($sect) {
            case '':
                $tmpl->addBreadcrumb($this->getName(), '');    
                $tmpl->addContent($this->getDescription());
                $this->viewList();
                break; 
            case 'new':
                //$tmpl->addBreadcrumb($this->items[$sect], $this->link_prefix . '&amp;sect='.$sect);
                $tmpl->addContent($this->editForm(0));                
                break;
            case 'save':
                $id = rcvint('id');
                $data = requestA( array('name','period','format','use_zip','price_id','vendor','count','lettertext','g','gs','view_pgroup','view_vendor') );
                $data['groups'] = $data['g'];
                $id = $this->saveItem($id, $data);
                $tmpl->msg("Данные сохранены", "ok");
                $tmpl->addContent($this->editForm($id)); 
                break;
            case 'edit':
                $id = rcvint('id');
                $tmpl->addContent($this->editForm($id));                
                break;
            case 'vs':
                $id = rcvint('id');
                $tmpl->addBreadcrumb('Контакты агентов для рассылки #'.$id, '');
                $this->viewAgentsSubscribers($id);
                break;
            case 'apl':
                $str = request('q');
                $this->getAgentsList($str);
                break;
            case 'agentins':
                $id = rcvint('id');
                $agent_id = rcvint('agent_id');
                $tmpl->addBreadcrumb('Контакты агентов для рассылки #'.$id, $this->link_prefix . '&amp;sect=vs');
                $tmpl->addBreadcrumb('Добавление контактов', '');                
                
                $this->viewInsertAgent($id, $agent_id);
                break;
            case 'contactrm':
                $id = rcvint('id');
                $contact_id = rcvint('contact_id');
                $tmpl->addBreadcrumb('Контакты агентов для рассылки #'.$id, $this->link_prefix . '&amp;sect=vs');
                $tmpl->addBreadcrumb('Удаление контактов', '');                
                
                $this->rmContact($id, $contact_id);
                $this->viewAgentsSubscribers($id);
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

        
    
}
