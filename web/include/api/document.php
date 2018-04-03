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
namespace api; 
include_once("include/doc.nulltype.php");

/// Обработчик API запросов к объектам *документ*. Проверяет необходимиые привилегии перед осуществлением действий.
class document {
    var $send_file = false;
    
    /// Извлечь ID документа из входных данных. Выбрасывает исключение, если ID не задан или не является положительным числом
    protected function extractDocumentId($data) {
        if(!is_array($data) || !isset($data['id'])) {
            throw new \InvalidArgumentException('id документа не задан');
        }
        $doc_id = intval($data['id']);
        if($doc_id<=0) {
            throw new \InvalidArgumentException('ID документа не задан');
        }
        return $doc_id;
    }
    
    /// Проверка на состояние пересчёта базы данных. Выбрасивает исключение, если установлен враг пересчёта
    protected function checkDbRecalc() {
        global $db;
        $res = $db->query("SELECT `recalc_active` FROM `variables`");
        if ($res->num_rows) {
            list($lock) = $res->fetch_row();
        } else {
            $lock = 0;
        }
        if ($lock) {
            throw new Exception("Идёт актуализация базы данных и перепроводка документов. Изменнеие статуса проведения невозможно!");
        }
    }

    /// Получить данные документа
    protected function get($data) {
        $doc_id = $this->extractDocumentId($data);
        $document = \document::getInstanceFromDb($doc_id);
        $firm_id = $document->getDocData('firm_id');
        \acl::accessGuard('doc.' . $document->getTypeName(), \acl::VIEW);
        if ($firm_id > 0) {
            \acl::accessGuard([ 'firm.global', 'firm.' . $firm_id], \acl::VIEW);
        }
        $ret = [
            'id' => $doc_id,
            'header' => $document->getDocumentHeader(),
            ];
        if ($document->isSkladEditorEnable()) {
            include_once('include/doc.poseditor.php');
            $poseditor = new \DocPosEditor($document);
            $ret['pe_config'] = $poseditor->getInitData();
            
        }
        unset($ret['header']['dop_buttons']);
        return $ret;
    }
    
    /// Обновить данные документа
    protected function update($data) {
        $doc_id = $this->extractDocumentId($data);
        $document = \document::getInstanceFromDb($doc_id);
        $firm_id = $document->getDocData('firm_id');
        \acl::accessGuard('doc.' . $document->getTypeName(), \acl::UPDATE);
        if ($firm_id > 0) {
            \acl::accessGuard([ 'firm.global', 'firm.' . $firm_id], \acl::UPDATE);
        } 
        $document->updateDocumentHeader($data);
        $firm_id = $document->getDocData('firm_id'); // т.к. могло измениться
        $header = null;
        if(\acl::testAccess('doc.' . $document->getTypeName(), \acl::VIEW) && 
            \acl::testAccess([ 'firm.global', 'firm.' . $firm_id], \acl::VIEW)) {
            $header = $document->getDocumentHeader();
        }
        return ['id'=>$doc_id, 'update'=>'ok', 'header'=>$header];
    }
    
    /// Провести документ
    protected function apply($data) {
        global $db;
        $db->startTransaction();
        $this->checkDbRecalc();
        $doc_id = $this->extractDocumentId($data);
        $document = \document::getInstanceFromDb($doc_id);
        $d_start = date_day(time());
        $d_end = $d_start + 60 * 60 * 24 - 1;
        $doc_date = $document->getDocData('date');        
        if (!\acl::testAccess('doc.' . $document->getTypeName(), \acl::APPLY)) {
            if (!\acl::testAccess('doc.' . $document->getTypeName(), \acl::TODAY_APPLY)) {
                throw new \AccessException('Не достаточно привилегий для проведения документа');
            } elseif ($doc_date < $d_start || $doc_date > $d_end) {
                throw new \AccessException('Не достаточно привилегий для проведения документа произвольной датой');
            }
        }
        $doc_firm_id = $document->getDocData('firm_id');
        if ($doc_firm_id > 0) {
            $acl_obj = [ 'firm.global', 'firm.' . $doc_firm_id];
            if (!\acl::testAccess($acl_obj, \acl::APPLY)) {
                if (!\acl::testAccess($acl_obj, \acl::TODAY_APPLY)) {
                    throw new \AccessException('Не достаточно привилегий для проведения документа в выбранной организации');
                } elseif ($doc_date < $d_start || $doc_date > $d_end) {
                    throw new \AccessException('Не достаточно привилегий для проведения документа произвольной датой в выбранной организации');
                }
            }
        }
        $document->extendedApplyAclCheck();
        $document->apply();
        $db->commit();
        return ['id'=>$doc_id, 'apply'=>'ok', 'header' => $document->getDocumentHeader()];
    }
    
    
    /// Отменить проведение документа
    protected function cancel($data) {
        global $db;
        $db->startTransaction();
        $this->checkDbRecalc();
        $doc_id = $this->extractDocumentId($data);
        $document = \document::getInstanceFromDb($doc_id);
        $d_start = date_day(time());
        $d_end = $d_start + 60 * 60 * 24 - 1;
        $doc_date = $document->getDocData('date');  
        if (!\acl::testAccess('doc.' . $document->getTypeName(), \acl::CANCEL)) {
            if (!\acl::testAccess('doc.' . $document->getTypeName(), \acl::TODAY_CANCEL)) {
                throw new \AccessException('Не достаточно привилегий для отмены документа');
            } elseif ($doc_date < $d_start || $doc_date > $d_end) {
                throw new \AccessException('Не достаточно привилегий для отмены документа произвольной датой');
            }
        }
        $doc_firm_id = $document->getDocData('firm_id');
        if ($doc_firm_id > 0) {
            $acl_obj = [ 'firm.global', 'firm.' . $doc_firm_id];
            if (!\acl::testAccess($acl_obj, \acl::CANCEL)) {
                if (!\acl::testAccess($acl_obj, \acl::TODAY_CANCEL)) {
                    throw new \AccessException('Не достаточно привилегий для отмены документа в выбранной организации');
                } elseif ($doc_date < $d_start || $doc_date > $d_end) {
                    throw new \AccessException('Не достаточно привилегий для отмены документа произвольной датой в выбранной организации');
                }
            }
        }
        $document->extendedCancelAclCheck();
        $document->cancel();
        $db->commit();
        return ['id'=>$doc_id, 'cancel'=>'ok', 'header' => $document->getDocumentHeader()];
    }
    
    /// Получить список печатных форм
    protected function getPrintFormList($data) {
        $doc_id = $this->extractDocumentId($data);
        $document = \document::getInstanceFromDb($doc_id);
        if( !\acl::testAccess('doc.' . $document->getTypeName(), \acl::GET_PRINTFORM)
         && !\acl::testAccess('doc.' . $document->getTypeName(), \acl::GET_PRINTDRAFT) ) {
            throw new \AccessException('Не достаточно привилегий для получения печатной формы');
        }
        return ['id'=>$doc_id, 'printforms' => $document->getCSVPrintFormList()];
    }

    /// Получить печатную форму
    protected function getPrintForm($data) {
        if(!isset($data['name'])) {
            throw new \NotFoundException('Имя печатной формы не задано');
        }
        $doc_id = $this->extractDocumentId($data);
        $document = \document::getInstanceFromDb($doc_id);
        $doc_firm_id = $document->getDocData('firm_id');
        if ($document->getDocData('ok')) {
            \acl::accessGuard('doc.' . $document->getTypeName(), \acl::GET_PRINTFORM);
            \acl::accessGuard([ 'firm.global', 'firm.' . $doc_firm_id], \acl::GET_PRINTFORM);
        } else {
            \acl::accessGuard('doc.' . $document->getTypeName(), \acl::GET_PRINTDRAFT);
            \acl::accessGuard([ 'firm.global', 'firm.' . $doc_firm_id], \acl::GET_PRINTDRAFT);
        }
        $document->sentZEvent('print');
        return $document->makePrintFormNoACLTest($data['name']);
    }
    
    /**
     * Отправить факс
     * @param array $data Массив, содержащий ID документа, имя печатной формы, и номер факса
     * @return array Массив с результатом операции
     */
    protected function sendFax($data) {
        if(!isset($data['name'])) {
            throw new \NotFoundException('Имя печатной формы не задано');
        }
        $doc_id = $this->extractDocumentId($data);
        $document = \document::getInstanceFromDb($doc_id);
        $doc_firm_id = $document->getDocData('firm_id');
        if ($document->getDocData('ok')) {
            \acl::accessGuard('doc.' . $document->getTypeName(), \acl::GET_PRINTFORM);
            \acl::accessGuard([ 'firm.global', 'firm.' . $doc_firm_id], \acl::GET_PRINTFORM);
        } else {
            \acl::accessGuard('doc.' . $document->getTypeName(), \acl::GET_PRINTDRAFT);
            \acl::accessGuard([ 'firm.global', 'firm.' . $doc_firm_id], \acl::GET_PRINTDRAFT);
        }
        $document->sentZEvent('sendfax');
        $result = $document->sendFaxTo($data['name'], $data['faxnum']);
        return [ 'id'=>$doc_id, 'result'=>$result ];
    }
    
    /**
     * Отправить печатную форму документа по электронной почте
     * @param array $data Массив, содержащий ID документа, имя печатной формы, email адрес, текст сообщения
     * @return array Массив с результатом операции
     */
    protected function sendEmail($data) {
        if(!isset($data['name'])) {
            throw new \NotFoundException('Имя печатной формы не задано');
        }
        $doc_id = $this->extractDocumentId($data);
        $document = \document::getInstanceFromDb($doc_id);
        $doc_firm_id = $document->getDocData('firm_id');
        if ($document->getDocData('ok')) {
            \acl::accessGuard('doc.' . $document->getTypeName(), \acl::GET_PRINTFORM);
            \acl::accessGuard([ 'firm.global', 'firm.' . $doc_firm_id], \acl::GET_PRINTFORM);
        } else {
            \acl::accessGuard('doc.' . $document->getTypeName(), \acl::GET_PRINTDRAFT);
            \acl::accessGuard([ 'firm.global', 'firm.' . $doc_firm_id], \acl::GET_PRINTDRAFT);
        }
        $document->sentZEvent('sendemail');
        $result = $document->sendEmailTo($data['name'], $data['email'], $data['text']);
        return [ 'id'=>$doc_id, 'result'=>$result ];
    }
    
    /**
     * Установить пометку *на удаление*
     * @param array $data Массив, содержащий ID документа
     * @return array Массив с результатом операции
     */
    protected function markForDelete($data) {
        $doc_id = $this->extractDocumentId($data);
        $document = \document::getInstanceFromDb($doc_id);
        $doc_firm_id = $document->getDocData('firm_id');
        \acl::accessGuard('doc.' . $document->getTypeName(), \acl::DELETE);
        \acl::accessGuard([ 'firm.global', 'firm.' . $doc_firm_id], \acl::DELETE);
        $result = $document->markForDelete();
        return ['id'=>$doc_id, 'result'=>$result];
    }
    
    /**
     * Снять пометку на удаление
     * @param array $data Массив, содержащий ID документа
     * @return array Массив с результатом операции
     */
    protected function unMarkDelete($data) {
        $doc_id = $this->extractDocumentId($data);
        $document = \document::getInstanceFromDb($doc_id);
        $doc_firm_id = $document->getDocData('firm_id');
        \acl::accessGuard('doc.' . $document->getTypeName(), \acl::DELETE);
        \acl::accessGuard([ 'firm.global', 'firm.' . $doc_firm_id], \acl::DELETE);
        $result = $document->unMarkDelete();
        return ['id'=>$doc_id, 'result'=>$result];
    }
    
    /**
     * Сделать документ потомком указанного документа
     * @param array $data Данные родительского документа
     * @return array Массив с результатом операции
     */
    protected function subordinate($data) {
        $doc_id = $this->extractDocumentId($data);
        $document = \document::getInstanceFromDb($doc_id);
        $doc_firm_id = $document->getDocData('firm_id');
        \acl::accessGuard('doc.' . $document->getTypeName(), \acl::UPDATE);
        \acl::accessGuard([ 'firm.global', 'firm.' . $doc_firm_id], \acl::UPDATE);
        if(!isset($data['p_doc'])) {
            $data['p_doc'] = null;
        }
        $result = $document->subordinate($data['p_doc']);
        return ['id'=>$doc_id, 'result'=>$result];
    }
    
    /**
     * Получить список действий для создания новых документов на основании текущего
     * @param array $data Данные родительского документа
     * @return array Массив с результатом операции
     */
    protected function getMorphList($data) {
        $doc_id = $this->extractDocumentId($data);
        $document = \document::getInstanceFromDb($doc_id);
        $doc_firm_id = $document->getDocData('firm_id');
        \acl::accessGuard('doc.' . $document->getTypeName(), \acl::VIEW);
        \acl::accessGuard([ 'firm.global', 'firm.' . $doc_firm_id], \acl::VIEW);
        $result = $document->getMorphList();
        return ['id'=>$doc_id, 'morphlist'=>$result];
    }
    
    protected function morph($data) {
        global $db;
        $doc_id = $this->extractDocumentId($data);
        if(!isset($data['target'])) {
            throw new \InvalidArgumentException('цель морфинга не задана');
        }        
        $target = $data['target'];
        $db->startTransaction();
        $document = \document::getInstanceFromDb($doc_id);        
        $morphs = $document->getMorphList();
        \acl::accessGuard('doc.'.$morphs[$target]['document'], \acl::CREATE);
        $new_doc = $document->morph($target);
        $new_id = $new_doc->getID();
        $db->commit();
        return ['id'=>$doc_id, 'newdoc_id'=>$new_id];
    }

    public function dispatch($action, $data=null) {
        switch($action) {
            case 'get':
                return $this->get($data);
            case 'update':
                return $this->update($data);
            case 'apply':
                return $this->apply($data);
            case 'cancel':
                return $this->cancel($data);
            case 'getprintformlist':
                return $this->getPrintFormList($data);
            case 'markfordelete':
                return $this->markForDelete($data);
            case 'unmarkdelete':
                return $this->unMarkDelete($data);
            case 'getprintform':
                $this->send_file = true;
                return $this->getPrintForm($data);
            case 'sendfax':
                return $this->sendFax($data);
            case 'sendemail':
                return $this->sendEmail($data);
            case 'subordinate':
                return $this->subordinate($data);
            case 'getmorphlist':
                return $this->getMorphList($data);
            case 'morph':
                return $this->morph($data);
            default:
                throw new \NotFoundException('Некорректное действие');
        }
    }
}