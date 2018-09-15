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

class atol {
var $buf;
    var $len;
    var $pass;
    var $recv;
    
    protected $atolBuffer;
    protected $password = [0, 0];    
    protected $cur_tld = 0;
    protected $last_answer = null;
    protected $async_results = [];
    protected $result_flags = self::F_NOFLAGS;
    protected $test_mode = false;
    protected $section = 0;

    // Flags
    const F_NOFLAGS = 0x00;
    const F_NEED_RESULT = 0x01;
    const F_IGNORE_ERROR = 0x02;
    const F_WAIT_ASYNC_DATA = 0x04;
    
    // Check types
    const CT_IN = 1;
    const CT_IN_RETURN = 2;
    const CT_OUT = 4;
    const CT_OUT_RETURN = 5;
    const CT_CORRECTION_IN = 7;
    const CT_CORRECTION_OUT = 8;

    public function __construct() {
        $this->atolBuffer = new AtolBuffer([$this, 'asyncReceiveData'], [$this, 'asyncReceiveError']);
    }

    public function __destruct() {
    }
    
    public function setResultFlags($flags) {
        $this->result_flags = $flags;
    } 

    public function setTestMode(bool $flag) {
        $this->test_mode = $flag;
    }
    
    public function setPassword($password) {
        $this->password = $this->intToBCD($password, 2);
    }
    
    public function setSection($section) {
        $this->section = $section;
    }

    public function asyncReceiveData(int $tld, array $data) {
        $this->async_results[$tld] = [
            'state' => 'result',
            'data' => $data
        ];
    }
    
    public function asyncReceiveError(int $tld, array $data) {
        $this->async_results[$tld] = [
            'state' => 'error',
            'data' => $data
        ];
    }

    public function connect($connect_line) {        
        $this->atolBuffer->connect($connect_line);
        $this->atolBuffer->abort();
    }
    
    public function abortBuffer() {
        $this->atolBuffer->abort();
    }
    
    public function cmdBeep() {
        $data = [$this->password[0], $this->password[1], 0x47];
        $this->cur_tld++;
        $this->atolBuffer->add($this->result_flags, $this->cur_tld, $data);
    }
    
    public function cmdRequestDeviceType() {
        $data = [$this->password[0], $this->password[1], 0xA5];
        $this->cur_tld++;
        return $this->atolBuffer->add($this->result_flags, $this->cur_tld, $data);
    }
    
    public function requestDeviceType() {
        $this->result_flags = self::F_NEED_RESULT;
        $this->cmdRequestDeviceType();        
        $this->atolBuffer->dispatchData(1);
        $ret = $this->getFreeAsyncResult($this->cur_tld);
        if($ret['data'][0]!=0) {
            throw new AtolHLException("Нет связи");
        }
        $ret = $ret['data'];
        $len = count($ret);
        $name = '';
        for($i=11;$i<$len;$i++) {
            $name .= chr($ret[$i]);
        }
        $name = iconv('CP866', 'UTF-8', $name);
        return [
            'protocol' => $ret[1],
            'type' => $ret[2],
            'model' => $ret[3],
            'mode' => (int)sprintf("%02X%02X", $ret[4], $ret[5]),
            'version' => (int)sprintf("%02X%02X%02X%02X%02X", $ret[6], $ret[7], $ret[8], $ret[9], $ret[10]),
            'name' => $name,
        ];
    }
    
    public function cmdGetState() {
        $data = [$this->password[0], $this->password[1], 0x3F];
        $this->cur_tld++;
        return $this->atolBuffer->add($this->result_flags, $this->cur_tld, $data);
    }
    
    public function requestGetState() {
        $this->result_flags = self::F_NEED_RESULT;
        $this->cmdGetState();        
        $this->atolBuffer->dispatchData(1);
        $ret = $this->getFreeAsyncResult($this->cur_tld);
        if($ret['state']=='error') {
            throw new AtolHLException("Ошибка получения статуса");
        }
        $ret = $ret['data'];
        if($ret[0]!=0x44) {
            throw new AtolHLException("Неверная сигнатура ответа");
        }
        return [
            'kashier' => (int)sprintf("%02X", $ret[1]),
            'numInRoom' => $ret[2],
            'date' => sprintf("20%02X-%02X-%02X", $ret[3], $ret[4], $ret[5]),
            'time' => sprintf("%02X:%02X:%02X", $ret[6], $ret[7], $ret[8]),
            'flags' => [
                'isFiscal' => $ret[9] & 0x01 ? true:false,
                'session' => $ret[9] & 0x02 ? true:false,
                'cashDrawerOpen' => $ret[9] & 0x04 ? true:false,
                'paper' => $ret[9] & 0x08 ? true:false,
                'cover' => $ret[9] & 0x20 ? true:false,
                'activeFiscalDrive' => $ret[9] & 0x40 ? true:false,
                'battery' => $ret[9] & 0x80 ? true:false,
            ],
            'factoryNumber' => sprintf("%02X%02X%02X%02X", $ret[10], $ret[11], $ret[12], $ret[13]),
            'model' => $ret[14],
            'state' => $ret[17]%10,
            'substate' => floor($ret[17]/10),
            'lastCheckNumber' => (int)sprintf("%02X%02X", $ret[18], $ret[19]),
            'lastSessionNumber' => (int)sprintf("%02X%02X", $ret[20], $ret[21]),
            'checkState' => $ret[22],
            'checkSum' => (int)sprintf("%02X%02X%02X%02X%02X", $ret[23], $ret[24], $ret[25], $ret[26], $ret[27]),
            'pointPosition' => $ret[28],
            'interface' => $ret[29],
        ];
    }
    
    public function cmdGetStateCode() {
        $data = [$this->password[0], $this->password[1], 0x45];
        $this->cur_tld++;
        return $this->atolBuffer->add($this->result_flags, $this->cur_tld, $data);
    }
    
    public function requestGetStateCode() {
        $this->result_flags = self::F_NEED_RESULT;
        $this->cmdGetStateCode();
        $this->atolBuffer->dispatchData(1);
        $ret = $this->getFreeAsyncResult($this->cur_tld);
        if($ret['state']=='error') {
            throw new AtolHLError($ret['data'][1],$ret['data'][2]);
        }
        $ret = $ret['data'];
        if($ret[0]!=0x55) {
            throw new AtolHLException("Неверная сигнатура ответа");
        }
        return [
            'state' => $ret[1]%10,
            'substate' => floor($ret[1]/10),
            'flags' => [
                'paper' => $ret[2] & 0x01 ? true : false,
                'printerConnected' => $ret[2] & 0x01 ? false : true,
                'cutterError' => $ret[2] & 0x04 ? true : false,
                'printerOverheat' => $ret[2] & 0x08 ? true : false,
                'paper' => $ret[2] & 0x01 ? true : false,
            ]
        ];
    }
    
    public function cmdEnterToMode($mode, $password) {
        $k_pass = $this->intToBCD($password, 4);
        $data = [$this->password[0], $this->password[1], 0x56, $mode];
        $data = array_merge($data, $k_pass);
        $this->cur_tld++;
        return $this->atolBuffer->add($this->result_flags, $this->cur_tld, $data);
    }
    
    public function requestEnterToMode($mode, $password) {
        $this->cmdEnterToMode($mode, $password);
        $this->atolBuffer->dispatchData(3);
        $ret = $this->getFreeAsyncResult($this->cur_tld);
        if($ret['state']=='error') {
            throw new AtolHLError($ret['data'][1],$ret['data'][2]);
        }
        $ret = $ret['data'];
        if($ret[0]!=0x55) {
            throw new AtolHLException("Неверная сигнатура ответа");
        }
        return $ret;
    }
    
    public function cmdExitFromMode() {
        $data = [$this->password[0], $this->password[1], 0x48];
        $this->cur_tld++;
        return $this->atolBuffer->add($this->result_flags, $this->cur_tld, $data);
    }
    
    public function requestExitFromMode() {
        $this->cmdExitFromMode();
        $this->atolBuffer->dispatchData(3);
        $ret = $this->getFreeAsyncResult($this->cur_tld);
        if($ret['state']=='error') {
            throw new AtolHLError($ret['data'][1],$ret['data'][2]);
        }
        $ret = $ret['data'];
        if($ret[0]!=0x55) {
            throw new AtolHLException("Неверная сигнатура ответа");
        }
        return $ret;
    }

    // ----------- Команды режима регистрации -------------------------------
    
    public function cmdNewSession() {
        $flags = 0;
        if($this->test_mode) {
            $flags |= 0x01;
        }
        $data = [$this->password[0], $this->password[1], 0x9A, $flags, 0];
        $this->cur_tld++;
        return $this->atolBuffer->add($this->result_flags, $this->cur_tld, $data);
    }
    
    public function requestNewSession() {
        $this->result_flags = self::F_NEED_RESULT;
        $this->cmdNewSession();
        $this->atolBuffer->dispatchData(3);
        $ret = $this->getFreeAsyncResult($this->cur_tld);
        if($ret['state']=='error') {
            throw new AtolHLError($ret['data'][1],$ret['data'][2]);
        }
        $ret = $ret['data'];
        if($ret[0]!=0x55) {
            throw new AtolHLException("Неверная сигнатура ответа");
        }
        return $ret;
    }
    
    public function cmdOpenCheck(int $type, bool $no_print = false) {
        $flags = 0;
        if($this->test_mode) {
            $flags |= 0x01;
        }
        if($no_print) {
            $flags |= 0x04;
        }
        $data = [$this->password[0], $this->password[1], 0x92, $flags, $type];
        $this->cur_tld++;
        return $this->atolBuffer->add($this->result_flags, $this->cur_tld, $data);
    }
    
    public function requestOpenCheck(int $type, bool $no_print = false) {
        $this->result_flags = self::F_NEED_RESULT;
        $this->cmdOpenCheck($type, $no_print);
        $this->atolBuffer->dispatchData(1);
        $ret = $this->getFreeAsyncResult($this->cur_tld);
        if($ret['state']=='error') {
            throw new AtolHLError($ret['data'][1],$ret['data'][2]);
        }
        $ret = $ret['data'];
        if($ret[0]!=0x55) {
            throw new AtolHLException("Неверная сигнатура ответа");
        }
        return $ret;
    }
    
    public function cmdRegisterNomenclature(string $name, float $price, float $count, int $type = 0, int $sign = 0, int $size = 0, int $tax = 0) {
        $flags = 0;
        if($this->test_mode) {
            $flags |= 0x01;
        }        
        $data = [$this->password[0], $this->password[1], 0xE6, $flags];
        $data = array_merge($data, $this->stringToArray($name, 64));
        $data = array_merge($data, $this->intToBCD($price*100, 6));
        $data = array_merge($data, $this->intToBCD($count*1000, 5));
        $data = array_merge($data, [$type, $sign]);
        $data = array_merge($data, $this->intToBCD($size*100, 6));
        $data = array_merge($data, [$tax, $this->section]);
        $data = array_merge($data, $this->intToBCD(0, 17));
        $this->cur_tld++;
        return $this->atolBuffer->add($this->result_flags, $this->cur_tld, $data);
    }
    
    public function requestRegisterNomenclature(string $name, float $price, float $count, int $type = 0, int $sign = 0, int $size = 0, int $tax = 0) {
        $this->result_flags = self::F_NEED_RESULT;
        $this->cmdRegisterNomenclature($name, $price, $count, $type, $sign, $size, $tax);
        $this->atolBuffer->dispatchData(1);
        $ret = $this->getFreeAsyncResult($this->cur_tld);
        if($ret['state']=='error') {
            throw new AtolHLError($ret['data'][1],$ret['data'][2]);
        }
        $ret = $ret['data'];
        if($ret[0]!=0x55) {
            throw new AtolHLException("Неверная сигнатура ответа");
        }
        return $ret;
    }
    
    public function cmdCloseCheck(int $type, float $sum) {
        $flags = 0;
        if($this->test_mode) {
            $flags |= 0x01;
        }
        $data = [$this->password[0], $this->password[1], 0x4A, $flags, $type];
        $data = array_merge($data, $this->intToBCD($sum*100, 5));
        $this->cur_tld++;
        return $this->atolBuffer->add($this->result_flags, $this->cur_tld, $data);
    }
    
    public function requestCloseCheck(int $type, float $sum) {
        $this->result_flags = self::F_NEED_RESULT;
        $this->cmdCloseCheck($type, $sum);
        $this->atolBuffer->dispatchData(5);
        $ret = $this->getFreeAsyncResult($this->cur_tld);
        if($ret['state']=='error') {
            throw new AtolHLError($ret['data'][1],$ret['data'][2]);
        }
        $ret = $ret['data'];
        if($ret[0]!=0x55) {
            throw new AtolHLException("Неверная сигнатура ответа");
        }
        return $ret;
    }
    
    public function cmdBreakCheck() {
        $data = [$this->password[0], $this->password[1], 0x59];
        $this->cur_tld++;
        return $this->atolBuffer->add($this->result_flags, $this->cur_tld, $data);
    }
    
    public function requestBreakCheck() {
        $this->result_flags = self::F_NEED_RESULT;
        $this->cmdBreakCheck();
        $this->atolBuffer->dispatchData(5);
        $ret = $this->getFreeAsyncResult($this->cur_tld);
        if($ret['state']=='error') {
            throw new AtolHLError($ret['data'][1],$ret['data'][2]);
        }
        $ret = $ret['data'];
        if($ret[0]!=0x55) {
            throw new AtolHLException("Неверная сигнатура ответа");
        }
        return $ret;
    }
    
    public function cmdInCash(float $sum) {
        $flags = 0;
        if($this->test_mode) {
            $flags |= 0x01;
        }
        $data = [$this->password[0], $this->password[1], 0x49, $flags];
        $data = array_merge($data, $this->intToBCD($sum*100, 5));
        $this->cur_tld++;
        return $this->atolBuffer->add($this->result_flags, $this->cur_tld, $data);
    }
    
    public function requestInCash(float $sum) {
        $this->result_flags = self::F_NEED_RESULT;
        $this->cmdInCash($sum);
        $this->atolBuffer->dispatchData(5);
        $ret = $this->getFreeAsyncResult($this->cur_tld);
        if($ret['state']=='error') {
            throw new AtolHLError($ret['data'][1],$ret['data'][2]);
        }
        $ret = $ret['data'];
        if($ret[0]!=0x55) {
            throw new AtolHLException("Неверная сигнатура ответа");
        }
        return $ret;
    }
    
    // -------- Команды режима отчёта без гашения ------------------
    public function cmdXREport($type) {
        settype($type, 'int');
        $data = [$this->password[0], $this->password[1], 0x67, $type];
        $this->cur_tld++;
        return $this->atolBuffer->add($this->result_flags, $this->cur_tld, $data);
    }
    
    public function requestXREport($type) {
        $this->result_flags = self::F_NEED_RESULT;
        $this->cmdXREport($type);
        $this->atolBuffer->dispatchData(1);
        $ret = $this->getFreeAsyncResult($this->cur_tld);
        if($ret['state']=='error') {
            throw new AtolHLError($ret['data'][1],$ret['data'][2]);
        }
        $ret = $ret['data'];
        if($ret[0]!=0x55) {
            throw new AtolHLException("Неверная сигнатура ответа");
        }
        return $ret;
    }
    
    
    // -------- Команды режима отчёта с гашением ------------------
    public function cmdZREport() {
        $data = [$this->password[0], $this->password[1], 0x5A];
        $this->cur_tld++;
        return $this->atolBuffer->add($this->result_flags, $this->cur_tld, $data);
    }
    
    public function requestZREport() {
        $this->result_flags = self::F_NEED_RESULT;
        $this->cmdZREport();
        $this->atolBuffer->dispatchData(1);
        $ret = $this->getFreeAsyncResult($this->cur_tld);
        if($ret['state']=='error') {
            throw new AtolHLError($ret['data'][1],$ret['data'][2]);
        }
        $ret = $ret['data'];
        if($ret[0]!=0x55) {
            throw new AtolHLException("Неверная сигнатура ответа");
        }
        return $ret;
    }
      
    // -------- Команды режима запроса к ФН ------------------
    public function cmdRePrintDocument($num) {
        $data = [$this->password[0], $this->password[1], 0xAB];
        $data = array_merge($data, $this->intToBCD($num, 5));
        $this->cur_tld++;
        return $this->atolBuffer->add($this->result_flags, $this->cur_tld, $data);
    }
    
    public function requestRePrintDocument($num) {
        $this->result_flags = self::F_NEED_RESULT;
        $this->cmdRePrintDocument($num);
        $this->atolBuffer->dispatchData(1);
        $ret = $this->getFreeAsyncResult($this->cur_tld);
        $this->assertErrors($ret);
        return $ret;
    }
    
    protected function assertErrors($res) {
        if($res['state']=='error') {
            throw new AtolHLError($res['data'][1],$res['data'][2]);
        }
        $res = $res['data'];
        if($res[0]!=0x55) {
            throw new AtolHLException("Неверная сигнатура ответа");
        }
    }


    protected function stringToArray(string $str, int $size) {
        $str = iconv('UTF-8', 'CP866', $str);
        $str = substr($str, 0, $size);
        $str = str_split($str);
        foreach($str as $i=>$s) {
            $str[$i] = ord($s);
        }
        while(count($str)<$size) {
            $str[] = 0;
        }
        return $str;
    }


    protected function intToBCD($number, $size) {
        $res = [];
        settype($number, 'int');
        for($i=$size-1;$i>=0;$i--) {
            $code = $number%10 | ((floor($number/10)%10)<<4);
            $number = floor($number/100);
            $res[$i] = $code;
        }
        ksort($res);
        return $res;
    }    

    
    protected function getFreeAsyncResult($tld) {
        if(!array_key_exists($tld, $this->async_results)) {
            throw new AtolException('Нет данных этой команды');
        }        
        $ret = $this->async_results[$this->cur_tld];
        unset($this->async_results[$this->cur_tld]);
        return $ret;
    }
            
 

}

