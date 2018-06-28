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

namespace modules\docservice;

/// Обслуживание кассового аппарата
class CashRegister extends \IModule {
    
    public function __construct() {
        parent::__construct();
        $this->acl_object_name = 'service.cashservice';
    }

    public function getName() {
        return 'Обслуживание кассового аппарата';
    }
    
    public function getDescription() {
        return 'Настройки, смены, отчёты...';  
    }
    
    public function viewMainPage() {
        global $db, $tmpl;
        $ldo = new \Models\LDO\CashRegisterNames();  
        $ops = [
            'info' => 'Получение информации',
            'beep' => 'Звуковой тест',
            'testcheck' => 'Тестовый чек',
            'opensession' => 'Открытие смены',
            'xreport' => 'Печать отчёта без гашения',
            'zreport' => 'Закрытие смены и печать отчёта с гашением',
            'incash' => 'Внесение денег',
        ];
        $tmpl->addContent("<form action='{$this->link_prefix}' method='post'>"
        . "<input type='hidden' name='sect' value='action'>"
        . "<label>Кассовый аппарат</label><br>");
        $tmpl->addContent( \widgets::getEscapedSelect('kkm_id', $ldo->getData(), null) );
        $tmpl->addContent("<br>"
        . "<label>Операция</label><br>");
        $tmpl->addContent( \widgets::getEscapedSelect('action', $ops, null) );
        $tmpl->addContent("<br>"
        . "<button type='submit'>Выполнить операцию</button>"
        . "</form>");        
    }
    
    public function executeAction($kkm_id, $action) {
        global $db, $tmpl;
        $type = rcvint('type');
        $sum = rcvint('sum');
        settype($kkm_id, 'int');
        $res = $db->query("SELECT `name`, `connect_line`, `password` FROM `cash_register` WHERE `id`='$kkm_id'");
        $kkm_line = $res->fetch_assoc();
        if(!$kkm_line) {
            throw new \NotFoundException("ID кассового аппарата $kkm_id не найден в базе данных");
        }
        $cr = new \CRI\Atol\Atol();
        $cr->connect($kkm_line['connect_line']); 
        $cr->setPassword($kkm_line['password']);
        
        switch($action) {
            case 'info':
                $this->actionInfo($kkm_line['name'], $cr);
                break;
            case 'beep':
                $this->actionBeep($kkm_line['name'], $cr);
                break;
            case 'testcheck':
                $this->actionTestCheck($kkm_line['name'], $cr);
                break;
            case 'opensession':
                $this->actionOpenSession($kkm_line['name'], $cr);
                break;
            case 'xreport':
                if($type==0) {
                    $this->showXReportForm($kkm_id, $kkm_line['name']);
                }
                else {
                     $this->actionXReport($kkm_line['name'], $cr, $type);
                }
                break;
            case 'zreport':
                $this->actionZReport($kkm_line['name'], $cr);
                break;
            case 'incash':
                if($sum==0) {
                    $this->showInCashForm($kkm_id, $kkm_line['name']);
                }
                else {
                     $this->actionInCash($kkm_line['name'], $cr, $sum);
                }
                break;
            default:
                throw new \NotFoundException("Действие не найдено");
        }
    }
    
    protected function actionOpenSession($name, $cr) {
        global $tmpl;
        $statecode = $cr->requestGetStateCode();
        if($statecode['state']>1) {
            $cr->requestExitFromMode();
            $statecode = $cr->requestGetStateCode();
            if($statecode['state']>1) {
                $cr->requestExitFromMode();
                $statecode = $cr->requestGetStateCode();
            }
        }        
        if($statecode['state']==0) {
            $cr->requestEnterToMode(1, 30);
        }
        else if($statecode['state']!=1) {
            throw new \Exception("Режим: {$statecode['state']} - в нём операция не возможна, и сменить не получается!");
        }
        $state = $cr->requestGetState();
        if($state['flags']['session']==true) {
            throw new \Exception("Смена уже открыта!");
        }
        $cr->requestNewSession();
        $tmpl->msg("Смена открыта!");
    }

    protected function actionInfo($name, $cr) {
        global $tmpl;
        $state = $cr->requestGetState();
        $type = $cr->requestDeviceType();
        $fis = $state['flags']['isFiscal'] ? '<b>Фискализирован</b>' : '<b>Не</b> фискализирован';
        $session = $state['flags']['session'] ? '<b>открыта</b>' : '<b>закрыта</b>';
        $drawer = $state['flags']['cashDrawerOpen'] ? '<b>открыт</b>' : 'закрыт';
        $paper = $state['flags']['paper'] ? 'есть' : '<b>отсутствует</b>';
        $cover = $state['flags']['cover'] ? '<b>открыта</b>' : 'закрыта';
        $fd = $state['flags']['activeFiscalDrive'] ? 'работает' : '<b>не работает</b>';
        $battery = $state['flags']['battery'] ? 'норма' : '<b>разряжена или отсутствует</b>';
        $tmpl->addContent("<h1>Информация о кассовом аппарате ". html_out($name)."</h1>"
        . "<ul>"
        . "<li>наименование: ".html_out($type['name'])."</li>"
        . "<li>Режим: {$state['state']}.{$state['substate']}</li>"
        . "<li>Номер кассира, выполнивший вход: {$state['kashier']}</li>"
        . "<li>Номер кассы в зале: {$state['numInRoom']}</li>"
        . "<li>Дата и время по внутренним часам: {$state['date']} {$state['time']}</li>"
        . "<li>Заводской номер: {$state['factoryNumber']}</li>"
        . "<li>Флаги состояния:<ul>"
            . "<li>$fis</li>"
            . "<li>Смена: $session</li>"
            . "<li>Ящик: $drawer</li>"
            . "<li>Бумага: $paper</li>"
            . "<li>Крышка: $cover</li>"
            . "<li>Фискальный накопитель: $fd</li>"
            . "<li>Батаеря: $battery</li>" 
        . "</ul></li>"
        
        . "</ul>");
    }

        
    protected function actionBeep($name, $cr) {
        global $tmpl;
        $cr->cmdBeep();
        $cr->cmdBeep();
        $cr->cmdBeep();
        $tmpl->msg("Три сигнала прозвучали на аппарате ".html_out($name));
    }
    
        
    protected function actionTestCheck($name, $cr) {
        global $tmpl, $db;
        $tmpl->addContent("<h1>Печать тестового чека на ККМ ".html_out($name)."</h1>");
        $tmpl->addContent("<p>Запрос режима...</p>");
        $statecode = $cr->requestGetStateCode();
        if($statecode['state']>1) {
            $tmpl->addContent("<p>Режим: {$statecode['state']} - выходим...</p>");  
            $cr->requestExitFromMode();
            $tmpl->addContent("<p>Запрос режима...</p>");
            $statecode = $cr->requestGetStateCode();
            if($statecode['state']>1) {
                $tmpl->addContent("<p>Режим: {$statecode['state']} - выходим...</p>");  
                $cr->requestExitFromMode();
                $statecode = $cr->requestGetStateCode();
            }
        }        
        if($statecode['state']==0) {
            $tmpl->addContent("<p>Режим: {$statecode['state']} - меняем на нужный...</p>");  
            $cr->requestEnterToMode(1, 30);
        }
        else if($statecode['state']==1) {
            $tmpl->addContent("<p>Режим: {$statecode['state']}.{$statecode['substate']} - отлично!</p>");  
        }
        else {
            throw new Exception("Режим: {$statecode['state']} - в нём печать не возможна, и сменить не получается!");
        }
        
        try {
            $tmpl->addContent("<p>Открываем чек...</p>");          
            $cr->requestOpenCheck(\CRI\Atol\atol::CT_IN);        
        }
        catch(\CRI\Atol\AtolHLError $e) {
            switch ($e->getCode()) {
                case 130:
                case 135:
                case 137:
                case 155: {
                    $tmpl->addContent("<p>А он уже открыт! Отменяем, на всякий случай...</p>");  
                    $cr->abortBuffer();
                    $cr->requestBreakCheck();
                    $tmpl->addContent("<p>И открываем снова...</p>");          
                    $cr->requestOpenCheck(\CRI\Atol\atol::CT_IN);  
                }
                    break;
                default:
                    throw $e;                    
            }
        }
        
        $tmpl->addContent("<p>Выбираем и печатаем номенклатуру: "); 
        $noms = [];
        $res = $db->query("SELECT `id`, `name`, `cost` AS `price` FROM `doc_base` WHERE `pos_type`=0 ORDER BY `price` DESC LIMIT 250");
        while($line = $res->fetch_assoc()) {
            $noms[] = $line;
        }
        $count = rand(4, 8);
        for($i=0;$i<$count;$i++) {
            $pos = rand(0, count($noms) - 1);
            $tmpl->addContent(html_out($noms[$pos]['name'])); 
            if($i<$count-1) {
                $tmpl->addContent(", "); 
            }
            $cr->cmdRegisterNomenclature($noms[$pos]['name'], $noms[$pos]['price'], rand(1,10));
        }
        $tmpl->addContent("</p>");
        $tmpl->addContent("<p>А теперь отменяем чек...</p>"); 
        $cr->requestBreakCheck();
        //$cr->requestCloseCheck(1, 3500000);
        $tmpl->msg("Операции выполнились без ошибок - проверьте чек в аппарате!");
    }
    
    protected function actionZReport($name, $cr) {
        global $tmpl;
        $tmpl->addContent("<p>Запрос режима...</p>");  
        $statecode = $cr->requestGetStateCode();
        if($statecode['state']!=0) {
            $tmpl->addContent("<p>Режим: {$statecode['state']} - выходим...</p>");  
            $cr->requestExitFromMode();
        }
        $tmpl->addContent("<p>Режим: {$statecode['state']} - меняем на нужный...</p>");  
        $cr->requestEnterToMode(3, 30);
        $tmpl->addContent("<p>Запрашиваем Z-отчёт...</p>");  
        $cr->requestZREport(); 
        $tmpl->addContent("<p>Ждём 3.1 ...</p>"); 
        for($i=0;$i<10;$i++) {
            sleep(1);
            $statecode = $cr->requestGetStateCode();
            if($statecode['state']==3 && $statecode['substate']==2) {
                $tmpl->addContent("<p>Ждём $i раз...</p>"); 
            } else {
                break;
            }
        }
        if($i==10) {
            throw new Exception("Ожидание затянулось. Это ошибка.");
        }
        
        $tmpl->addContent("<p>Ждём 7.1 ...</p>"); 
        for($i=0;$i<10;$i++) {
            sleep(1);
            $statecode = $cr->requestGetStateCode();
            if($statecode['state']==7 && $statecode['substate']==2) {
                $tmpl->addContent("<p>Ждём $i раз...</p>"); 
            } else {
                break;
            }
        }
        if($i==10) {
            throw new Exception("Ожидание затянулось. Это ошибка.");
        }
        
        $tmpl->msg("Операции выполнились без ошибок - проверьте чек в аппарате!");
    }
    
    protected function actionXReport($name, $cr, $type) {
        global $tmpl;
        $tmpl->addContent("<p>Запрос режима...</p>");  
        $statecode = $cr->requestGetStateCode();
        if($statecode['state']!=0) {
            $tmpl->addContent("<p>Режим: {$statecode['state']} - выходим...</p>");  
            $cr->requestExitFromMode();
        }
        $tmpl->addContent("<p>Режим: {$statecode['state']} - меняем на нужный...</p>");  
        $cr->requestEnterToMode(2, 30);
        $tmpl->addContent("<p>Запрашиваем X-отчёт...</p>");  
        $cr->requestXREport($type); 
        $tmpl->addContent("<p>Ждём 2.0 ...</p>"); 
        for($i=0;$i<10;$i++) {
            sleep(1);
            $statecode = $cr->requestGetStateCode();
            if($statecode['state']==2 && $statecode['substate']==2) {
                $tmpl->addContent("<p>Ждём $i раз...</p>"); 
            } else {
                break;
            }
        }
        if($i==10) {
            throw new Exception("Ожидание затянулось. Это ошибка.");
        }
        if($statecode['flags']['papar']==1) {
            throw new \Exception("Закончилась бумага");
        }        
        else if($statecode['flags']['printerConnected']==0) {
            throw new \Exception("нет связи с принтером чека");
        }
        
        $tmpl->msg("Операции выполнились без ошибок - проверьте чек в аппарате!");       
    }

    protected function showXReportForm($kkm_id, $kkm_name) {
        global $tmpl;
        $rtype = [
            1 => 'Суточный отчёт без гашения',
            2 => 'Отчёт по секциям',
            3 => 'Отчёт по кассирам',
            5 => 'Почасовой отчёт',
            7 => 'Отчёт количеств',
            10 => 'Отчёт по товарам',
        ];
        $tmpl->addContent("<form action='{$this->link_prefix}' method='post'>"
        . "<input type='hidden' name='sect' value='action'>"
        . "<input type='hidden' name='kkm_id' value='$kkm_id'>"
        . "<input type='hidden' name='action' value='xreport'>"
        . "<label>Кассовый аппарат: ".html_out($kkm_name)."</label><br>"
        . "<label>Вид отчёта:</label><br>");
        $tmpl->addContent( \widgets::getEscapedSelect('type', $rtype, null) );
        $tmpl->addContent("<br>"
        . "<button type='submit'>Выполнить операцию</button>"
        . "</form>");
    }
    
    protected function showInCashForm($kkm_id, $kkm_name) {
        global $tmpl;
        $tmpl->addContent("<form action='{$this->link_prefix}' method='post'>"
        . "<input type='hidden' name='sect' value='action'>"
        . "<input type='hidden' name='kkm_id' value='$kkm_id'>"
        . "<input type='hidden' name='action' value='incash'>"
        . "<label>Кассовый аппарат: ".html_out($kkm_name)."</label><br>"
        . "<label>Сумма:</label><br>"
        . "<input type='text' name='sum'><br>"
        . "<button type='submit'>Выполнить операцию</button>"
        . "</form>");
    }

    protected function actionInCash($name, $cr, $type) {
        global $tmpl;
        $tmpl->addContent("<p>Запрос режима...</p>");  
        $statecode = $cr->requestGetStateCode();
        if($statecode['state']!=0) {
            $tmpl->addContent("<p>Режим: {$statecode['state']} - выходим...</p>");  
            $cr->requestExitFromMode();
        }
        $tmpl->addContent("<p>Режим: {$statecode['state']} - меняем на нужный...</p>");  
        $cr->requestEnterToMode(1, 30);
        $tmpl->addContent("<p>Вводим сумму...</p>");  
        $cr->requestInCash($type); 
                
        $tmpl->msg("Операции выполнились без ошибок - проверьте чек в аппарате!");       
    }
    
    public function run() {
        global $tmpl, $db;
        $tmpl->addBreadcrumb($this->getName(), $this->link_prefix);
        $sect = request('sect');
        switch ($sect) {
            case '':
                $tmpl->addBreadcrumb($this->getName(), '');
                $this->viewMainPage();
                break;
            case 'action':
                $kkm = rcvint('kkm_id');
                $action = request('action');
                $this->executeAction($kkm, $action);
                break;
            default:
                throw new \NotFoundException("Секция не найдена");
        }
    }

}
