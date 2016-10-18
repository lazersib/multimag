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
namespace api; 
include_once("include/doc.nulltype.php");

/// Обработчик API запросов к объектам *документ*. Проверяет необходимиые привилегии перед осуществлением действий.
class document {
    
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
        if (!\acl::testAccess('doc.' . $document->getTypeName(), \acl::APPLY)) {
            if (!\acl::testAccess('doc.' . $document->getTypeName(), \acl::TODAY_APPLY)) {
                throw new \AccessException('Не достаточно привилегий для проведения документа');
            } elseif ($document->doc_data['date'] < $d_start || $document->doc_data['date'] > $d_end) {
                throw new \AccessException('Не достаточно привилегий для проведения документа произвольной датой');
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
        if (!\acl::testAccess('doc.' . $document->getTypeName(), \acl::CANCEL)) {
            if (!\acl::testAccess('doc.' . $document->getTypeName(), \acl::TODAY_CANCEL)) {
                throw new \AccessException('Не достаточно привилегий для отмены документа');
            } elseif ($document->doc_data['date'] < $d_start || $document->doc_data['date'] > $d_end) {
                throw new \AccessException('Не достаточно привилегий для отмены документа произвольной датой');
            }
        }
        $document->extendedCancelAclCheck();
        $document->cancel();
        $db->commit();
        return ['id'=>$doc_id, 'cancel'=>'ok', 'header' => $document->getDocumentHeader()];
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
            default:
                throw new \NotFoundException('Некорректное действие');
        }
    }
}