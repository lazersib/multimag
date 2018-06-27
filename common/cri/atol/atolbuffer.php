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

namespace CRI\Atol;

class AtolBuffer extends LLPv3 { 
    protected $result_callback;
    protected $error_callback;
    protected $last_data = null;

    // Buffer commands
    const CMD_BUF_ADD = 0xC1;
    const CMD_BUF_ACK = 0xC2;
    const CMD_BUF_REQ = 0xC3;
    const CMD_BUF_ABORT = 0xC4;
    const CMD_BUF_ACKADD = 0xC5;   
    
    // Errors
    const E_OVERFLOW = 0xB1;
    const E_ALREADYEXISTS = 0xB2;
    const E_NOTFOUND = 0xB3;
    const E_ILLEGALVALUE = 0xB4;
    
    // States of task
    const ST_PENDING = 0xA1;
    const ST_INPROGRESS = 0xA2;
    const ST_RESULT = 0xA3;
    const ST_ERROR = 0xA4;
    const ST_STOPPED = 0xA5;
    const ST_ASYNCRESULT = 0xA6;
    const ST_ASYNCERROR = 0xA7;
    const ST_WAITING = 0xA8;

    public function __construct(callable $result_callback, callable $error_callback = null) {
        parent::__construct();
        $this->result_callback = $result_callback;
        $this->error_callback = $error_callback;
    }

    public function __destruct() {
        parent::__destruct();
    }
    
    public function getLastData() {
        return $this->last_data;
    }
    
    /**
     * Add command to buffer
     * @param type $flags
     * @param type $tld
     * @param type $data
     * @return type
     */
    public function add(int $flags, int $tld, array $data) {
        $sdata = array_merge([self::CMD_BUF_ADD, $flags, $tld], $data);
        $this->sendDataPacket($sdata);
        $res = $this->filteredReceiveDataPacket();
        $state = $res[0];
        $this->last_data = array_splice($res, 1);
        switch($state) {
            case self::ST_PENDING:
            case self::ST_INPROGRESS:
            case self::ST_WAITING:
            case self::ST_RESULT:
                return $state;
            default:
                throw new AtolBufferException("Ошибка добавления задания: $state");
        }
    }
    
    /**
     * Request task state for $tld
     * @param type $tld
     */
    public function req(int $tld) {
        $sdata = array_merge([self::CMD_BUF_REQ, $tld]);
        $this->sendDataPacket($sdata);
        $res = $this->filteredReceiveDataPacket();
        $state = $res[0];
        $this->last_data = array_splice($res, 1);
        switch($state) {
            case self::E_ILLEGALVALUE:
            case self::E_NOTFOUND:
                throw new AtolBufferException("Ошибка получения статуса задания: $state");
        }
        return $state;
    }
    
    public function ack(int $tld) {
        $sdata = array_merge([self::CMD_BUF_ACK, $tld]);
        $this->sendDataPacket($sdata);
        $res = $this->filteredReceiveDataPacket();
        $state = $res[0];
        $this->last_data = array_splice($res, 1);
        switch($state) {
            case self::E_ILLEGALVALUE:
            case self::E_NOTFOUND:
                throw new AtolBufferException("Ошибка снятия флага задания: $state");
        }
        return $state;
    }
    
    /**
     * Clear buffer and try to abort current task
     * @return type
     */
    public function abort() {
        $data = [self::CMD_BUF_ABORT];    
        $this->sendDataPacket($data);
        $res = $this->filteredReceiveDataPacket();
        $state = $res[0];
        return $state;
    }
    
    /**
     * Ack prevous command and add new command to buffer
     * @param int $tld_ack
     * @param int $flags
     * @param int $tld_add
     * @param array $data
     * @return type
     * @throws AtolBufferException
     */
    public function ackAdd(int $tld_ack, int $flags, int $tld_add, array $data) {
        $sdata = array_merge([self::CMD_BUF_ACKADD, $tld_ack, $flags, $tld_add], $data);
        $this->sendDataPacket($sdata);
        $res = $this->filteredReceiveDataPacket();
        $stateack = $res[0];
        $stateadd = $res[1];
        $this->last_data = array_splice($res, 2);
        switch($stateack) {
            case self::E_ILLEGALVALUE:
            case self::E_NOTFOUND:
            case self::ST_PENDING:
            case self::ST_INPROGRESS:
            case self::ST_STOPPED:        
                throw new AtolBufferException("Ошибка снятия флага задания: $stateack");
            case self::ST_RESULT:
            case self::ST_ERROR: {
                switch($stateadd) {
                    case self::ST_PENDING:
                    case self::ST_INPROGRESS:
                    case self::ST_WAITING:
                    case self::ST_RESULT:
                        return [
                            'stateack' => $stateack,
                            'stateadd' => $stateadd
                        ];
                    default:
                        throw new AtolBufferException("Ошибка добавления задания: $stateadd");
                }  
            }
            default:
                throw new AtolBufferException("Ошибка добавления задания: $stateack");                
        }
    }
    
    /**
     * Asyncronous receive data packet from remote
     * @param array $data
     * @throws AtolException
     */
    protected function asyncReceive(array $data) {
        if(count($data)<3) {
            throw new AtolException("Ошибка протокола при асинхронном ответе");
        }
        $state = $data[0];
        $tld = $data[1];
        $data = array_slice($data, 2);
        if ($state == self::ST_ASYNCRESULT) {
            if (is_callable($this->result_callback)) {
                call_user_func($this->result_callback, $tld, $data);
            }
        } elseif ($state == self::ST_ASYNCERROR) {
            if (is_callable($this->error_callback)) {
                call_user_func($this->error_callback, $tld, $data);
            }
        }
        else {
            throw new AtolException("Ошибка протокола при асинхронном ответе: ".$state);
        }
    }
   
}
