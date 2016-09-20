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
    
    protected function extractDocumentId($data) {
        if(!is_array($data) || !isset($data['id'])) {
            throw new \InvalidArgumentException('id документа не задан');
        }
        $doc_id = intval($data['id']);
        if(!$doc_id) {
            throw new \InvalidArgumentException('ID документа не задан');
        }
        return $doc_id;
    }

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
    
    protected function apply($data) {
        $doc_id = $this->extractDocumentId($data);
        $document = \document::getInstanceFromDb($doc_id);
    }

    public function dispatch($action, $data=null) {
        switch($action) {
            case 'get':
                return $this->get($data);
            case 'update':
                return $this->update($data);
            case 'apply':
                return $this->apply($data);
            default:
                throw new \NotFoundException('Некорректное действие');
        }
    }
}