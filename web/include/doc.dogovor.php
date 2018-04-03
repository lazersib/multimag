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

/// Документ *договор*
class doc_Dogovor extends doc_Nulltype {

    // Создание нового документа или редактирование заголовка старого
    function __construct($doc = 0) {
        global $CONFIG;
        parent::__construct($doc);
        $this->doc_type = 14;
        $this->typename = 'dogovor';
        $this->viewname = 'Договор';
        $this->sklad_editor_enable = false;
        $this->header_fields = 'bank separator agent cena';
    }

    function initDefDopdata() {
        $this->def_dop_data = array('name' => '', 'end_date' => '', 'debt_control' => 0, 'debt_size' => 0, 'limit' => 0, 'received' => 0
            , 'cena' => 0, 'deferment'=>0, 'template'=>0);
    }

    function DopHead() {
        global $tmpl;
        if ($this->id) {
            $end_date = @$this->dop_data['end_date'];
        } else {
            $end_date = date("Y-12-31");
        }
        $name = $this->dop_data['name'];
        $dchecked = $this->dop_data['debt_control'] ? 'checked' : '';
        $debt_size = $this->dop_data['debt_size'];
        $limit = $this->dop_data['limit'];
        $deferment = $this->dop_data['deferment'];
        $checked = $this->dop_data['received'] ? 'checked' : '';
        if(!$this->id) {
            $ldo = new \Models\LDO\ctemplates();
            $ctemplates = $ldo->getData();
            $tmpl->addContent("Шаблон текста договора:<br><select name='template'>");
            foreach ($ctemplates as $id=>$value) {
                $tmpl->addContent("<option value='$id'>".html_out($value).'</option>');
            }
            $tmpl->addContent("</select><br>");
        }
        
        $tmpl->addContent("
            Отображаемое наименование:<br>
            <input type='text' name='name' value='$name'><br>
            Дата истечения:<br>
            <input type='text' name='end_date' value='$end_date'><br>
            <label><input type='checkbox' name='debt_control' value='1' $dchecked>Контроль задолженности</label><br>
            <input type='text' name='debt_size' value='$debt_size'><br>
            Максимальная отсрочка платежа, дней:<br>
            <input type='text' name='deferment' value='$deferment'><br>
            Лимит оборотов по договору:<br>
            <input type='text' name='limit' value='$limit'><br>
            <label><input type='checkbox' name='received' value='1' $checked>Документы подписаны и получены</label><br>");
    }

    function DopSave() {
        global $db;
        $new_data = array(
            'received' => request('received'),
            'end_date' => rcvdate('end_date'),
            'debt_control' => rcvint('debt_control') ? '1' : '0',
            'debt_size' => rcvint('debt_size'),
            'name' => request('name'),
            'limit' => rcvint('limit'),
            'deferment' => rcvint('deferment'),
            'received' => rcvint('received') ? '1' : '0'
        );
        $template = rcvint('template');
        if($template) {
            $res = $db->query("SELECT `text` FROM `contract_templates` WHERE `id`=$template");
            if($res->num_rows>0) {
                list($t_text) = $res->fetch_row();
                $this->setTextData('contract_text', $t_text);
            }
        }
        $this->setDopDataA($new_data);
    }

    /// Получить список шаблонных полей договора
    public function getVariables() {
        $agent = new \models\agent($this->doc_data['agent']);
        return array(
            'DOC_NUM' => [
                'name' => 'Номер договора',
                'value' => $this->doc_data['altnum']
            ],
            'DOC_DATE' => [
                'name' => 'Дата договора',
                'value' => date("Y-m-d", $this->doc_data['date'])
            ],
            'DOC_NAME' => [
                'name' => 'Наименование договора',
                'value' => $this->getDopData('name')
            ],
            'AGENT_FULLNAME' => [
                'name' => 'Полное имя агента',
                'value' => $agent->fullname
            ],
            'AGENT_LEADER_NAME' => [
                'name' => 'ФИО руководителя',
                'value' => $agent->leader_name
            ],
            'AGENT_LEADER_NAME_R' => [
                'name' => 'ФИО руководителя в родительном падеже',
                'value' => $agent->leader_name_r
            ],
            'AGENT_LEADER_POST' => [
                'name' => 'Должность руководителя агента',
                'value' => $agent->leader_post
            ],
            'AGENT_LEADER_POST_R' => [
                'name' => 'Должность руководителя агента в родительном падеже',
                'value' => $agent->leader_post_r
            ],
            'AGENT_LEADER_REASON' => [
                'name' => 'Основание деятельности руководителя агента',
                'value' => $agent->leader_reason
            ],
            'AGENT_LEADER_REASON_R' => [
                'name' => 'Основание деятельности руководителя агента в родительном падеже',
                'value' => $agent->leader_reason_r
            ],
            'AGENT_EMAIL' => [
                'name' => 'Основной email агента',
                'value' => $agent->getEmail(),
            ],
            'END_DATE' => [
                'name' => 'Дата окончания действия договора',
                'value' => $this->getDopData('end_date')
            ],
            'DEBT_SIZE' => [
                'name' => 'Максимально допустимый размер задолженности',
                'value' => $this->getDopData('debt_size')
            ],
            'PAY_DEFERMENT' => [
                'name' => 'Отсрочка платежа (дней)',
                'value' => $this->getDopData('deferment')
            ],
            'CONTRACT_LIMIT' => [
                'name' => 'Лимит оборотов по договору',
                'value' => $this->getDopData('limit')
            ],
            'FIRM_NAME' => [
                'name' => 'Наименование собственной организации',
                'value' => $this->firm_vars['firm_name']
            ],
            'FIRM_EMAIL' => [
                'name' => 'email собственной организации',
                'value' => \cfg::get('site', 'admin_email')
            ],
            'FIRM_LEADER_POST' => [
                'name' => 'Должность руководителя собственной организации',
                'value' => $this->firm_vars['firm_leader_post']
            ],
            'FIRM_LEADER_POST_R' => [
                'name' => 'Должность руководителя собственной организации в родительном падеже',
                'value' => $this->firm_vars['firm_leader_post_r']
            ],
            'FIRM_LEADER_NAME' => [
                'name' => 'ФИО руководителя собственной организации',
                'value' => $this->firm_vars['firm_director']
            ],
            'FIRM_LEADER_NAME_R' => [
                'name' => 'ФИО руководителя собственной организации в родительном падеже',
                'value' => $this->firm_vars['firm_director_r']
            ],
            'FIRM_LEADER_REASON' => [
                'name' => 'Основание деятельности руководителя собственной организации',
                'value' => $this->firm_vars['firm_leader_reason']
            ],
            'FIRM_LEADER_REASON_R' => [
                'name' => 'Основание деятельности руководителя собственной организации в родительном падеже',
                'value' => $this->firm_vars['firm_leader_reason_r']
            ],
        );
    }
    
    function DopBody() {
        global $tmpl;
        if ($this->dop_data['received']) {
            $tmpl->addContent("<br><b>Документы подписаны и получены</b><br>");
        }
        $contract_text = html_out($this->getTextData('contract_text'));
        $tmpl->addContent("<b>Текст договора:</b><br>"
            . "<textarea class='wikieditor big' cols='80' rows='20' id='contract_text_editor'>$contract_text</textarea><br>"
            . "<button id='contract_text_submit' style='display:none'>Сохранить текст</button><br>"
            . "<script type='text/javascript'>"
            . "contractTextSaverInit('{$this->id}','contract_text_editor', 'contract_text_submit');"
            . "</script>");
        
        $vars = $this->getVariables();
        $tmpl->addContent("<h2>Выражения подстановки, которые возможно использовать в текста договора:</h2>"
            . "<table class='list'><tr><th>Выражение</th><th>Описание</th><th>Текущее значение</th></tr>");
        foreach($vars as $var => $obj) {
            $tmpl->addContent("<tr><td>{{".$var."}}</td><td>".html_out($obj['name'])."</td><td>".html_out($obj['value'])."</td></tr>");
        }
        $tmpl->addContent("</table>");
        $tmpl->addContent("<p>Для просмотра текста договора используйте печатную форму.</p>");
    }

    /**
     * Получить список документов, которые можно создать на основе этого
     * @return array Список документов
     */
    public function getMorphList() {
        $morphs = array(
            'specific' =>   ['name'=>'specific', 'document' => 'specific',    'viewname' => 'Спецификация', ],
        );
        return $morphs;
    }
    
    protected function morphTo_specific() {
        $new_doc = new doc_Specific();
        $new_doc->createFromP($this);
        $new_doc->setDopData('cena', $this->dop_data['cena']);
        return $new_doc;
    }

    public function Service() {
        global $tmpl;

        $tmpl->ajax = 1;
        $opt = request('opt');
        $pos = request('pos');
        
        if (parent::_Service($opt, $pos)) {
            return true;
        } 
        
        switch($opt) {
            case 'jcts':
                \acl::accessGuard('doc.'.$this->typename, \acl::UPDATE);
                $text = request('text');
                $this->setTextData('contract_text', $text);
                $ret = array(
                    'response' => $opt,
                    'status' => 'ok',
                );
                $tmpl->setContent(json_encode($ret, JSON_UNESCAPED_UNICODE));
                return true;
            default:
                throw new \NotFoundException("Неизвестная опция $opt!");
        }
    }

}
