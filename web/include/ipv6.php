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

/// Класс для работы с IP адресами IPv6
class ipv6 {

    /// Является ли строка IPv6 адресом ?
    function is_ipv6($ip = "") {
        if ($ip == '') {
            return false;
        }
        if (substr_count($ip, ":") > 0 && substr_count($ip, ".") == 0) {
            return true;
        } else {
            return false;
        }
    }

    /// Является ли строка IPv4 адресом ?
    function is_ipv4($ip = "") {
        if ($ip == '') {
            return false;
        }
        return !ipv6::is_ipv6($ip);
    }

    /// Возвращает IP адрес клиента
    function get_ip() {
        return getenv("REMOTE_ADDR");
    }

    /// Преобразует заданный IPv6 адрес в полную форму
    function uncompress_ipv6($ip = "") {
        if ($ip == '') {
            return false;
        }
        if (strstr($ip, "::")) {
            $e = explode(":", $ip);
            $s = 8 - sizeof($e) + 1;
            foreach ($e as $key => $val) {
                if ($val == "") {
                    for ($i = 0; $i <= $s; $i++) {
                        $newip[] = 0;
                    }
                } else {
                    $newip[] = $val;
                }
            }
            $ip = implode(":", $newip);
        }
        return $ip;
    }

    /// Преобразует заданный IPv6 адрес в краткую форму
    function compress_ipv6($ip = "") {
        if ($ip == '') {
            return false;
        }
        if (!strstr($ip, "::")) {
            $e = explode(":", $ip);
            $zeros = array(0);
            $result = array_intersect($e, $zeros);
            if (sizeof($result) >= 6) {
                if ($e[0] == 0) {
                    $newip[] = "";
                }
                foreach ($e as $key => $val) {
                    if ($val !== "0") {
                        $newip[] = $val;
                    }
                }
                $ip = implode("::", $newip);
            }
        }
        return $ip;
    }
}
