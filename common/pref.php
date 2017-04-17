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


/// @brief Класс доступа к опциям программы
/// Используется для загрузки сайт-специфичных настроек
/// Синглтон
class pref {

    protected static $_instance;    ///< Экземпляр для синглтона
    protected $sites_data;               ///< Настройки сайтов (из базы)
    protected $default_site;        ///< Настройки сайта по умолчанию
    protected $current_site;

    /// Конструктор копирования запрещён
    final private function __clone() {        
    }

    /// Конструктор
    final private function __construct() {
        global $db;
        $this->sites_data = array();
        $this->default_site = null;
        
        $res = $db->query("SELECT * FROM `sites`");
        while ($line = $res->fetch_assoc()) {
            $this->sites_data[$line['id']] = $line;
            if($line['default_site']) {
                $this->default_site = $line;
            }            
        }
        if(!$this->default_site) {
            /// Загружаем из конфигурационного файла (для совместимости)
            $this->default_site = array(
                'name' => \cfg::get('site', 'name'),
                'email' => \cfg::get('site', 'admin_email'),
                'jid' => \cfg::get('site', 'doc_adm_jid'),
                'short_name' => 'Главный сайт',
                'display_name' => \cfg::get('site', 'display_name', \cfg::get('site', 'name') ),
                'default_firm_id' => \cfg::get('site', 'default_firm'),
                'default_bank_id' => \cfg::get('site', 'default_bank'),
                'default_cash_id' => \cfg::get('site', 'default_kass'),
                'default_agent_id' => \cfg::get('site', 'default_agent_id'),
                'default_store_id' => \cfg::get('site', 'default_sklad'),
                'site_store_id' => \cfg::get('site', 'vitrina_sklad'),
            );
        }
        $this->detectSite();
    }
  
    /// Получить экземпляр класса
    /// @return pref
    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    protected function detectSite() {
        $this->current_site = null;
        if(isset($_SERVER['SERVER_NAME'])) {
            $site = $_SERVER['SERVER_NAME'];
            foreach($this->sites_data as $id => $line) {
                if($site===$line['name']) {
                    $this->current_site = $line;
                    break;
                }
            }
        }
        if(!$this->current_site) {
            $this->current_site = $this->default_site;
        }
    }

    public function __get($name) {
        switch($name) {
            case 'site':
                return $this->current_site;
            case 'sites':
                return $this->sites_data;
            case 'site_display_name':
                return $this->current_site['display_name'];
            case 'site_email':
                return $this->current_site['email'];
            case 'site_name':
                return $this->current_site['name'];
            case 'site_default_firm_id':
                return $this->current_site['default_firm_id'];
            default:
                return null;
        }
    }

    public function getSitePref($name) {
        if(isset($this->current_site[$name])) {
            return $this->current_site[$name];
        } else {
            return null;
        }
    }
}
