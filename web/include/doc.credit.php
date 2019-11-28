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

/// Класс для документов-приходников
class doc_credit extends \doc_Nulltype {

    /** Установить вид дохода для документа по кодовому имени вида дохода
     * 
     * @param string $codename Кодовое имя вида расхода
     */
    public function setCreditTypeFromCodename($codename) {
        global $db;
        $codename_sql = $db->real_escape_string($codename);
        $resource = $db->query("SELECT `id` FROM `doc_ctypes` WHERE `codename`='$codename_sql'");
        if($resource->num_rows) {
            $result = $resource->fetch_assoc();
            $this->setDopData('credit_type', $result['id']);
        }
    }
    
    /// Выполнение оповещения о поступившем платеже
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

	public function body() {
		global $tmpl, $db;
		\acl::accessGuard('doc.' . $this->typename, \acl::VIEW);
		if ($this->doc_data['firm_id'] > 0) {
			\acl::accessGuard([ 'firm.global', 'firm.' . $this->doc_data['firm_id']], \acl::VIEW);
		}
		$this->extendedViewAclCheck();
		$tmpl->setTitle($this->viewname . ' N' . $this->id);
		$dt = date("Y-m-d H:i:s", $this->doc_data['date']);
		doc_menu($this->getDopButtons());
		$tmpl->addContent("<div id='doc_container'>
		<div id='doc_left_block' class='doc_head'>");
		$tmpl->addContent("<h1>{$this->viewname} N{$this->id}</h1>");

		$this->drawLHeadformStart();
		$fields = explode(' ', $this->header_fields);
		foreach ($fields as $f) {
			switch ($f) {
				case 'agent': $this->DrawAgentField();
					break;
				case 'sklad': $this->DrawSkladField();
					break;
				case 'kassa': $this->drawKassaField();
					break;
				case 'bank': $this->drawBankField();
					break;
				case 'cena': $this->drawPriceField();
					break;
				case 'sum': $this->drawSumField();
					break;
				case 'separator': $tmpl->addContent("<hr>");
					break;
			}
		}
		if (method_exists($this, 'DopHead'))
			$this->DopHead();

		$this->DrawLHeadformEnd();

		$tmpl->addContent("<b>Относится к:</b><br>");
		$this->buildAncestors($this->getAncestors());

		$infol = $this->getSubordinatesInfo();
		if($infol && count($infol)>0) {
			$tmpl->addContent("<br><b>Зависящие документы:</b><br>");
			foreach($infol as $info) {
				if($info['ok']) {
					$str='Проведённый';
				}
				else {
					$str='Непроведённый';
				}
				$str .= " <a href='?mode=body&amp;doc={$info['id']}'>{$info['viewname']} N{$info['altnum']}{$info['subtype']}</a> от {$info['date']}<br>";
				$tmpl->addContent($str);
			}
		}

		$tmpl->addContent("<br><b>Дата создания:</b>: {$this->doc_data['created']}<br>");
		if ($this->doc_data['ok']) {
			$tmpl->addContent("<b>Дата проведения:</b> " . date("Y-m-d H:i:s", $this->doc_data['ok']) . "<br>");
		}
		$tmpl->addContent("</div>
		<script type=\"text/javascript\">
		addEventListener('load',DocHeadInit,false);
                //newDynamicDocHeader('doc_left_block', '{$this->id}');
		</script>");
		$tmpl->addContent("<div id='doc_main_block'>");
		$tmpl->addContent("<img src='/img/i_leftarrow.png' onclick='DocLeftToggle()' id='doc_left_arrow'><br>");

		if (method_exists($this, 'DopBody'))
			$this->DopBody();

		if ($this->sklad_editor_enable) {
			include_once('doc.poseditor.php');
			$poseditor = new DocPosEditor($this);
			$poseditor->cost_id = $this->dop_data['cena'];
			$poseditor->sklad_id = $this->doc_data['sklad'];
			$poseditor->SetEditable($this->doc_data['ok'] ? 0 : 1);
			$tmpl->addContent($poseditor->Show());
		}

		$tmpl->addContent("<div id='statusblock'></div><br><br></div></div>");
	}

	/**
	 * Построить документы от которых зависит текущий
	 * @param $info array массив с данными
	 */
	protected function buildAncestors($info)
	{
		global $tmpl;
		$str = $info['ok'] ? 'Проведённый' : 'Непроведённый';
		$str .= " <a href='?mode=body&amp;doc={$info['id']}'>{$info['viewname']} N{$info['altnum']}{$info['subtype']}</a> от {$info['date']}<br>";
		$tmpl->addContent($str);
		if($info['parent']) $this->buildAncestors($info['parent']);
	}

	/**
	 *  Получить предков документа
	 * @param null $id ид документа
	 * @return array|null
	 */
	public function getAncestors($id = null)
	{
		global $db;
		if(!$id && !$this->doc_data['p_doc']) {
			return null;
		}
		$p_doc = $id ? $id : intval($this->doc_data['p_doc']);
		$res = $db->query("SELECT `id`, `type`, `altnum`, `subtype`, `date`, `ok`, `p_doc` FROM `doc_list`
            WHERE `doc_list`.`id`='{$p_doc}'");
		$row = $res->fetch_assoc();
		if($row) {
			$row['typename'] = self::getNameFromType($row['type']);
			$row['viewname'] = self::getViewNameFromName($row['typename']);
			$row['date'] = date("d.m.Y H:i:s", $row['date']);
			$result[$row['type']] = $row;
			if(!empty($row['p_doc'])) {
				$row['parent'] = $this->getAncestors($row['p_doc']);
			}
		}
		return $row;
	}
}
