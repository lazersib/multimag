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

/**
 * Atol low level protocol v3 connector. Now support TCP/IP socket only.
 */
abstract class LLPv3 {

    protected $socket;      ///< TCP socket
    protected $packet_id;   //< low level protocol packet counter
    protected $socket_timeout;  ///< Network socket timeout

    // Protocol low level v3 constants
    const STX = 0xFE;
    const ESC = 0xFD;
    const TSTX = 0xEE;
    const TESC = 0xED;
    const ASYNC_ID = 0xF0;
    
    /**
     * Receive new async packet from remote device
     */
    abstract protected function asyncReceive(array $data);

    public function __construct() {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) {
            throw new AtolException("Невозможно создать сокет. " . socket_strerror(socket_last_error($this->socket)));
        }
        $this->socket_timeout = 3;
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => $this->socket_timeout, "usec" => 0));
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array("sec" => $this->socket_timeout, "usec" => 0));
        $this->packet_id = 0;
    }

    public function __destruct() {
        socket_close($this->socket);
    }
    
    /**
     * Connect to the remote atol cash register device
     * @param string $connect_line URL of targed device. Example: tcp://192.168.1.10:5555
     * @throws AtolException
     */
    public function connect(string $connect_line) {
        $info = parse_url($connect_line);
        if($info===false) {
            throw new AtolException("Неверная строка подключения");            
        }
        if($info['scheme']!='tcp') {
            throw new AtolException("Не поддерживаемый протокол подключения:". html_out($info['scheme']));
        } 
        $this->connectTCP($info['host'], $info['port']);
    }
    
    /**
     * Connect to the remote atol cash register device via TCP protocol
     * @param string $address Target ip address or hostname
     * @param int $port Target tcp port
     * @throws AtolException
     */
    protected function connectTCP(string $address, int $port) {
        $address = gethostbyname($address);
        $result = socket_connect($this->socket, $address, $port);
        if ($result === false) {
            throw new AtolException("Не могу принять данные: " . socket_strerror(socket_last_error($this->socket)));
        }
    }

    /**
     * Check if there is data from remote device to read
     * @param int $max_time Maximum wait time, in seconds. 0 - return immediately
     * @return bool true, if data may be read, else - false
     * @throws AtolException
     */
    public function checkForRead(int $max_time) {
        $r = [$this->socket];
        $w = null;
        $e = [$this->socket];
        $res = socket_select($r, $w, $e, $max_time);
        if ($res === false) {
            throw new AtolException("Не могу принять данные: " . socket_strerror(socket_last_error($this->socket)));
        }
        return count($r) ? true : false;
    }

    /**
     * Calculate CRC8 checksum for atol protocol
     * @param array $buffer byte buffer of data
     * @return int Checksum
     */
    protected function crc8(array $buffer) {
        $len = count($buffer);
        $crc = 0xFF;
        for ($i = 0; $i < $len; $i++) {
            $crc ^= $buffer[$i];
            for ($j = 0; $j < 8; $j++) {
                $crc = $crc & 0x80 ? ($crc << 1) ^ 0x31 : $crc << 1;
            }
            $crc &= 0xff;
        }
        return $crc;
    }

    /**
     * Byte stuffing for atol protocol
     * @param array $data Input raw byte data
     * @return array Ouutput stuffed byte data
     */
    protected function stuffingData(array $data) {
        $ret = [];
        $len = count($data);
        for ($i = 0; $i < $len; $i++) {
            switch ($data[$i]) {
                case self::ESC:
                    $ret[] = self::ESC;
                    $ret[] = self::TESC;
                    break;
                case self::STX:
                    $ret[] = self::ESC;
                    $ret[] = self::TSTX;
                    break;
                default:
                    $ret[] = $data[$i];
            }
        }
        return $ret;
    }

    /**
     * Build low level v3 packet
     * @param array $data Data to be send
     * @return array low level v3 packet
     */
    protected function buildLLv3packet(array $data) {
        $this->packet_id++;
        $len = count($data);
        $crc_payload = array_merge([$this->packet_id], $data);
        $crc = $this->crc8($crc_payload);
        $m_payload = $this->stuffingData(array_merge($data, [$crc]));

        $ret = [self::STX, $len & 0x7f, $len >> 7];
        $ret[] = $this->packet_id;
        $ret = array_merge($ret, $m_payload);

        if ($this->packet_id > 0xDF) {
            $this->packet_id = 0;
        }
        return $ret;
    }

    /**
     * Convert byte array to byte string
     * @param array $data Byte array
     * @return string Byte string
     */
    protected function arrayToByteString(array $data) {
        $bytestring = '';
        $len = count($data);
        for ($i = 0; $i < $len; $i++) {
            $bytestring .= chr($data[$i]);
        }
        return $bytestring;
    }

    /**
     * Convert byte string to byte array
     * @param string $data Byte string
     * @return array Byte array 
     */
    protected function byteStringToArray(string $data) {
        $ret = [];
        $len = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            $ret[] = ord(substr($data, $i, 1));
        }
        return $ret;
    }

    /**
     * Send data packet to remote device
     * @param array $data Data to be send
     * @return int Length of writed data
     * @throws AtolException
     */
    protected function sendDataPacket(array $data) {
        $fulldata = $this->buildLLv3packet($data);
        $bs = $this->arrayToBytestring($fulldata);
        $len = socket_write($this->socket, $bs, strlen($bs));
        if ($len == false) {
            throw new AtolException("Не могу отправить данные: " . socket_strerror(socket_last_error($this->socket)));
        }
        return $len;
    }
    
    /**
     * Receive data packet from remote device
     * @return array [id of packet, length of packet, packet data, crc]
     * @throws AtolException
     */
    protected function receiveDataPacket() {
        $data = socket_read($this->socket, 4);  // STX, Len, Id
        if ($data == false) {
            throw new AtolException("Не могу принять заголовок пакета: " . socket_strerror(socket_last_error($this->socket)));
        }
        if (strlen($data) < 4) {
            throw new AtolException("Не достаточно данных при приёме");
        }
        $data = $this->byteStringToArray($data);
        if ($data[0] != self::STX) {
            throw new AtolException("Ошибка структуры пакета: STX");
        }

        $p_len = $data[1] | ($data[2] << 7);
        $p_id = $data[3];
        $data = $this->receiveDataPart($p_len);
        list($crc) = $this->receiveDataPart(1);
        $calc_crc = $this->crc8(array_merge([$p_id], $data));
        if ($crc != $calc_crc) {
            throw new AtolException("Ошибка структуры пакета: несовпадение CRC");
        }
        if ($p_len == 0) {
            throw new AtolException("Ошибка протокола: команда не принята");
        }
        return [
            'id' => $p_id,
            'len' => $p_len,
            'data' => $data,
            'crc' => $crc
        ];
    }

    /**
     * Receive and destuffing $len bytes
     * @param type $len
     * @return type
     * @throws \Exception
     * @throws Exception
     */
    protected function receiveDataPart(int $len) {
        $ret = [];
        $esc_flag = false;
        for ($i = 0; $i < $len;) {
            $byte = socket_read($this->socket, 1);
            if ($byte === false) {
                throw new AtolException("Не могу принять данные ($i): " . socket_strerror(socket_last_error($this->socket)));
            }
            $byte = ord($byte);
            if ($esc_flag === false) {
                if ($byte === self::ESC) {
                    $esc_flag = true;
                    echo "rcv esc<br>";
                } else {
                    $ret[$i++] = $byte;
                }
            } else {
                switch ($byte) {
                    case self::TESC:
                        $ret[$i++] = self::ESC;
                        break;
                    case self::TSTX:
                        $ret[$i++] = self::STX;
                        break;
                    default:
                        throw new AtolException("Bytestream error");
                }
            }
        }
        return $ret;
    }
    
    /**
     * Dispatch async data packet from remote device
     * @param int $max_time Max wait time for checkForRead method
     * @return boolean True, if data is reveived, else - false
     * @throws AtolException
     */
    public function dispatchData(int $max_time) {
        $flag = false;
        while($this->checkForRead($max_time)) {
            $data = $this->receiveDataPacket();
            if ($data['id'] == self::ASYNC_ID) { // Async answer
                $this->asyncReceive($data['data']);
                $flag = true;
            } else {
                throw new AtolException("Ошибка протокола: получена не асинхронный ответ вместо асинхронного");
            }
            $max_time = 0;
        }
        return $flag;
    }

    /**
     * Filter for async data packet from remote device. Call asyncReceive, if async packet is received.
     * @return array Filtered received sync data
     */
    protected function filteredReceiveDataPacket() {
        while (1) {
            $data = $this->receiveDataPacket();
            if ($data['id'] == self::ASYNC_ID) { // Async answer
                $this->asyncReceive($data['data']);
            } else {
                return $data['data'];
            }
        }
    }

}
