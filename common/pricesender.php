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


/// Модуль рассылки прайс-листов
class priceSender {
    protected $options;
    protected $text;
    protected $format;
    protected $contactlist;
    protected $price_content;
    protected $zip;
    protected $price_id = 1;

    public function __construct() {
        $pc = \PriceCalc::getInstance();
        $pref = \pref::getInstance();
        $pc->setFirmId($pref->site_default_firm_id);
        $this->price_id = $pc->getDefaultPriceId();
    }

    public function setOptions($options) {
        $this->options = $options;
    }
    
    public function setText($text) {
        $this->text = $text;
    }
    
    public function setZip($zip) {
        $this->zip = $zip;
    }
    
    public function setFormat($format) {
        $this->format = $format;
    }
    
    public function setContactList($contactlist) {
        $this->contactlist = $contactlist;
    }
    
    public function setPriceId($price_id) {
        $this->price_id = $price_id;
    }
    
    public function run() {
        $this->preparePriceList();
        $this->prepareEmail();
        $this->sendEmails();
    }
    
    public function out() {
        $this->preparePriceList();
        if($this->zip) {
            header("Content-type: application/zip");
            header("Content-Disposition: attachment; filename=\"price.zip\"");
        }
        else {
            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=\"price.xls\"");
        }
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header("Pragma: public");
        echo $this->price_content;
    }
    
    protected function preparePriceList() {
        global $db;
        switch ($this->format) {
            case 'xls':
                $pricewriter = new \pricewriter\xls($db);
                break;
            case 'csv':
                $pricewriter = new \pricewriter\csv($db);
                break;
            default:
                throw new \Exception('Запрошенный формат не поддерживатеся модулем рассылки прайс-листов');
        }
       
	$pricewriter->setPriceId($this->price_id);
        if( is_array($this->options)) {
            if(is_array($this->options['filter'])) {
                $f = $this->options['filter'];
                if( isset($f['groups_only']) && $f['groups_only'] && is_array($f['groups_list'])) {
                    $pricewriter->setGroupsFilter($f['groups_list']);
                }
                if( isset($f['count']) ) {
                    $pricewriter->setCountFilter($f['count']);
                }
                if( isset($f['vendor']) ) {
                    $pricewriter->setVendorFilter($f['vendor']);
                }
            }
            if(is_array($this->options['modname'])) {
                $mn = $this->options['modname'];
                if( isset($mn['pgroup']) ) {
                    $pricewriter->showGroupName($mn['pgroup']);
                }
                else {
                    $pricewriter->showGroupName(false);
                }
                if( isset($mn['vendor']) ) {
                    $pricewriter->showProizv($mn['vendor']);
                }
                else {
                    $pricewriter->showProizv(false);
                }
            }
            else {
                $pricewriter->showGroupName(false);
                $pricewriter->showProizv(false);
            }
            if(is_array($this->options['columns'])) {
                $pricewriter->setColumnList($this->options['columns']);
            }
        }
	$this->price_content = $pricewriter->get();   
        
        if($this->zip) {
            $tmp_dir = sys_get_temp_dir();
            $ztmp_filename = tempnam($tmp_dir, "zip_");

            $zip = new ZipArchive();
            $opened = $zip->open( $ztmp_filename, ZIPARCHIVE::OVERWRITE );
            if( $opened !== true ){
                throw new Exception("cannot open {$ztmp_filename} for writing.");
            }
            $zip->addFromString('price.'.$this->format, $this->price_content );
            $zip->close();
            $this->price_content = file_get_contents($ztmp_filename);
            unlink($ztmp_filename);
            $this->format = 'zip';
        }
    }
    
    protected function prepareEmail() {
        global $db;
        $site_firm_id = \cfg::get('site', 'default_firm');
        $res = $db->query("SELECT `firm_name` FROM `doc_vars` WHERE `id`='{$site_firm_id}'");
        list($this->firm_name) = $res->fetch_row();
    }
    
    protected function sendEmails() {
        foreach ($this->contactlist as $contact) {
            $this->sendEmail($contact);
        }
    }

    protected function sendEmail($mail_info) {
        $pref = \pref::getInstance();
        $site_dname = \cfg::get('site', 'display_name');
        $site_name = \cfg::get('site', 'name');
        $email_message = new \email_message();
        $email_message->default_charset = "UTF-8";
        if ($mail_info['person_name']) {
            $email_message->SetEncodedEmailHeader("To", $mail_info['email'], $mail_info['person_name']);
        } else {
            $email_message->SetEncodedEmailHeader("To", $mail_info['email'], $mail_info['email']);
        }
        $email_message->SetEncodedHeader("Subject", "Свежий прайс-лист от {$pref->site_display_name} ({$pref->site_name})");
        $email_message->SetEncodedEmailHeader("From", $pref->site_email, "Почтовый робот {$pref->site_name}");
        $email_message->SetHeader("Sender", $pref->site_email);
        
        $text_message = <<<HEREDOCMAIL
Здравствуйте, {$mail_info['person_name']}!
------------------------------------------

{$this->text}

------------------------------------------

Вы получили это письмо потому что подписаны на рассылку прайс-листов сайта {$site_dname} ( http://{$site_name}?from=email ), либо являетесь клиентом {$this->firm_name}.
Отказаться от рассылки можно, перейдя по ссылке http://{$site_name}/login.php?mode=unsubscribe&email={$mail_info['email']}&from=email
HEREDOCMAIL;
        $email_message->AddQuotedPrintableTextPart($text_message);

        $text_attachment = array(
            "Data" => $this->price_content,
            "Name" => 'price.'.$this->format,
            "Content-Type" => "automatic/name",
            "Disposition" => "attachment"
        );
        $email_message->AddFilePart($text_attachment);

        $error = $email_message->Send();

        if (strcmp($error, "")) {
            throw new Exception($error."; email: ".$mail_info['email']);
        }
    }
}
