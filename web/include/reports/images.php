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


class Report_Images extends BaseGSReport {

    function getName($short = 0) {
        if ($short) {
            return "По изображениям";
        } else {
            return "Отчёт по изображениям складских наименований";
        }
    }

    function Form() {
        global $tmpl;
        $tmpl->addContent("<h1>" . $this->getName() . "</h1>
            <form action='' method='post'>
            <input type='hidden' name='mode' value='images'>
            <input type='hidden' name='opt' value='ok'>
            <fieldset><legend>Эскизы изображений</legend>
            <label><input type='radio' name='show_img' value='0' checked>Не показывать</label><br>
            <label><input type='radio' name='show_img' value='1'>Высота:24, качество:30% (ускорение загрузки, экономия трафика)</label><br>
            <label><input type='radio' name='show_img' value='2'>Высота:64, качество:70% (больше трафика)</label><br>
            <label><input type='radio' name='show_img' value='3'>Высота:128, качество:70% (ещё больше трафика)</label>
            </fieldset>            
            <fieldset><legend>Сортировать по</legend>
            <label><input type='radio' name='order' value='img' checked>Названиям изображений</label><br>                
            <label><input type='radio' name='order' value='pos'>Товарным наименованиям</label><br>
            <label><input type='radio' name='order' value='vc'>Товарным кодам</label><br>
            </fieldset>
            <fieldset><legend>Фильтр по привязке</legend>
            <label><input type='radio' name='assign' value='all' checked>Все</label><br>
            <label><input type='radio' name='assign' value='no'>Непривязанные</label><br>
            <label><input type='radio' name='assign' value='yes'>Привязанные</label><br>
            <label><input type='radio' name='assign' value='multi'>Привязанные к нескольким</label><br>
            </fieldset>");
        $this->GroupSelBlock();
        $tmpl->addContent("<button type='submit'>Сформировать</button></form>");
    }
    
    protected function getImageOrderedData() {
        global $db;
        $ret = array();
        $res = $db->query("SELECT `doc_img`.`id` AS `img_id`, `doc_img`.`name`, `doc_img`.`type`
            FROM `doc_img` ORDER BY `doc_img`.`id`");
        while ($img_info = $res->fetch_array()) {
            $r = $db->query("SELECT `doc_base_img`.`pos_id`, `doc_base_img`.`default`, `doc_base`.`vc`, `doc_base`.`group`
                , CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name`
                FROM `doc_base_img`
                LEFT JOIN `doc_base` ON `doc_base`.`id`=`doc_base_img`.`pos_id`
                WHERE `doc_base_img`.`img_id`='{$img_info['img_id']}'");
            $pos_rows = array();
            $c = 0;
            $add_flag = !$this->gs;
            while ($n = $r->fetch_array()) {
                $pos_rows[] = $n;
                $c++;
                if(in_array($n['group'], $this->groups)) {
                    $add_flag = 1;
                }
            }
            switch($this->assign) {
                case 'no':
                    $add_flag = !$c;
                    break;
                case 'multi':
                    if($c<2) {
                        $add_flag = 0;
                    }
                    break;
                case 'yes':
                   if(!$c) {
                        $add_flag = 0;
                    } 
                    break;
            }
            
            if( $add_flag ) {
                $img_info['products'] = $pos_rows;
                $ret[] = $img_info;
            }
        }
        return $ret;
    }
    
    protected function getImageOrderedHTMLTable($data) {
        $img_col = $this->show_img ? '<th>Эскиз</th>' : '';
        $ret = "<table width='100%'>"
            . "<tr><th>ID</th>$img_col<th>Изображение</th><th>Тип</th><th>Умолч.</th><th>ID товара</th><th>Код</th><th>Наименование / произв.</th></tr>";
        foreach($data as $img_info) {
            if ($this->show_img) {
                $img = new ImageProductor($img_info['img_id'], 'p', $img_info['type']);
                switch($this->show_img) {
                    case 1:
                        $img->SetY(24);
                        $img->SetQuality(30);
                        break;
                    case 2:
                        $img->SetY(64);
                        $img->SetQuality(70);
                        break;
                    default:
                        $img->SetY(128);
                        $img->SetQuality(70);
                        break;
                }
                $img_tag = "<img src='" . $img->GetURI() . "' alt=''>";
            } else {
                $img_tag = '';
            }
            $count = count($img_info['products']);
            if ($count>0) {
                $need_tr = 0;
                if ($this->show_img) {
                    $img_tag = "<td rowspan='$count' align='center'>" . $img_tag;
                }
                $ret .= "<tr><td rowspan='$count'>{$img_info['img_id']}$img_tag</td>"
                    . "<td rowspan='$count'>" . html_out($img_info['name'])."</td><td rowspan='$count'>{$img_info['type']}</td>";
                foreach ($img_info['products'] as $line) {
                    if ($need_tr) {
                        $ret .="<tr>";
                    }
                    $def = $line['default']?'Да':'Нет';
                    $ret .="<td>$def</td><td>{$line['pos_id']}</td><td>{$line['vc']}</td><td>{$line['name']}</td></tr>\n";
                    $need_tr = 1;
                }
            }
            else {
                if ($this->show_img) {
                    $img_tag = "<td align='center'>" . $img_tag;
                }
                $ret .="<tr><td>{$img_info['img_id']} $img_tag<td>" . html_out($img_info['name'])."</td><td>{$img_info['type']}</td><td colspan='5'></td></tr>\n";
            }
        }
        return $ret.'</table>';
    }
    
    private function processGroup($group_id) {
        global $db;
        settype($group_id, 'int');
        $sql_header = "SELECT `doc_base`.`id`, `doc_base`.`vc`, CONCAT(`doc_base`.`name`, ' - ', `doc_base`.`proizv`) AS `name` FROM `doc_base`";
        $order = $this->order=='vc' ? 'vc':'name';
        $res = $db->query( $sql_header
            . " WHERE `doc_base`.`group`='{$group_id}'"
            . " ORDER BY `doc_base`.`$order`");
        while ($pos_info = $res->fetch_assoc()) {
            $img_res = $db->query("SELECT `doc_img`.`id` AS `img_id`, `doc_base_img`.`default`, `doc_img`.`name`, `doc_img`.`type`
                FROM `doc_base_img`
                LEFT JOIN `doc_img` ON `doc_img`.`id`=`doc_base_img`.`img_id`
                WHERE `doc_base_img`.`pos_id`='{$pos_info['id']}'");
            $count = $img_res->num_rows;
            $add_flag = true;
            switch($this->assign) {
                case 'yes':
                    if(!$count) {
                        $add_flag = false;
                    }
                    break;
                case 'no':
                    if($count) {
                        $add_flag = false;
                    }
                    break;
                case 'multi':
                    if($count<2) {
                        $add_flag = false;
                    }
                    break;
            }
            if($add_flag) {
                $img_rows = array();
                while($img_info = $img_res->fetch_assoc() ) {
                    $img_rows[] = $img_info;
                }
                $pos_info['type'] = 'item';
                $pos_info['images'] = $img_rows;
                $this->ret[] = $pos_info;
            }
        }
    }
        
    protected function walkGroup($pgroup_id=0, $level=0) {
        global $db;
        settype($pgroup_id, 'int');
        $res_group = $db->query("SELECT `id`, `name` FROM `doc_group` WHERE `pid`=$pgroup_id ORDER BY `id`");
        while ($group_line = $res_group->fetch_assoc()) {  
            if($this->gs) {
                if(!in_array($group_line['id'], $this->groups)) {
                    continue;
                }
            }
            $group_line['type'] = 'group';
            $group_line['level'] = $level;
            $this->ret[] = $group_line;
            $this->processGroup($group_line['id']);
            $this->walkGroup($group_line['id'], $level+1);
        }
    }
    
    protected function getPosOrderedData() {
        $this->ret = array();
        $this->walkGroup();        
        return $this->ret;
    }
    
    protected function getPosOrderedHTMLTable($data) {
        $col_cnt = 7;
        $img_col = $this->show_img ? '<th>Эскиз</th>' : '';
        if($this->show_img) {
            $col_cnt++;
        }
        $ret = "<table width='100%'>"
            . "<tr><th>ID товара</th><th>Код</th><th>Наименование / произв.</th><th>ID изобр.</th>$img_col<th>Изображение</th><th>Тип</th><th>Умолч.</th></tr>";
        foreach($data as $pos_info) {
            if($pos_info['type']=='group') {
                $level = $pos_info['level'];
                if($level>4) {
                    $level = 4;
                }
                $ret .= "<tr><td colspan='{$col_cnt}' class='m{$level}'>".html_out($pos_info['name'])."</td></tr>";
            } else {
                $count = count($pos_info['images']);
                if ($count>0) {
                    $need_tr = 0;
                    
                    $ret .= "<tr><td rowspan='$count'>{$pos_info['id']}</td>"
                        . "<td rowspan='$count'>".html_out($pos_info['vc'])."</td>"
                        . "<td rowspan='$count'>".html_out($pos_info['name'])."</td>";
                    foreach ($pos_info['images'] as $line) {
                        if ($need_tr) {
                            $ret .="<tr>";
                        }
                        $img_tag = '';
                        if ($this->show_img) {
                            $img = new ImageProductor($line['img_id'], 'p', $line['type']);
                            switch($this->show_img) {
                                case 1:
                                    $img->SetY(24);
                                    $img->SetQuality(30);
                                    break;
                                case 2:
                                    $img->SetY(64);
                                    $img->SetQuality(70);
                                    break;
                                default:
                                    $img->SetY(128);
                                    $img->SetQuality(70);
                                    break;
                            }
                            $img_tag = "<td align='center'><img src='" . $img->GetURI() . "' alt=''></td>";
                        }
                        $def = $line['default']?'Да':'Нет';
                        $ret .="<td>{$line['img_id']}</td>$img_tag<td>{$line['name']}</td><td>{$line['type']}</td><td>$def</td></tr>\n";
                        $need_tr = 1;
                    }
                }
                else {
                    $ts = $col_cnt - 3;
                    $ret .="<tr><td>{$pos_info['id']}</td><td>".html_out($pos_info['vc'])."</td><td>".html_out($pos_info['name'])."</td><td colspan='$ts'></td></tr>\n";
                }
                
                
            }
            
            /*
            if ($this->show_img) {
                $img = new ImageProductor($img_info['img_id'], 'p', $img_info['type']);
                if ($this->show_img == 1) {
                    $img->SetY(24);
                    $img->SetQuality(20);
                } else {
                    $img->SetY(64);
                    $img->SetQuality(75);
                }
                $img_tag = "<img src='" . $img->GetURI() . "' alt=''>";
            } else {
                $img_tag = '';
            }
            
            
             * 
             */
        }
        return $ret.'</table>';
    }
    
    function MakeHTML() {
        global $tmpl, $db;
        $this->show_img = rcvint('show_img');
        $this->assign = request('assign');
        $this->groups = request('g', []);
        $this->gs = rcvint('gs');
        $this->order = request('order');
        
        $tmpl->loadTemplate('print');
        $tmpl->setContent("<h1>Отчёт по изображениям</h1>");
        
        switch ($this->order) {
            case 'img':
                $data = $this->getImageOrderedData();
                $tmpl->addContent( $this->getImageOrderedHTMLTable($data) );
                break;
            case 'pos':
            case 'vc':
                $data = $this->getPosOrderedData();
                $tmpl->addContent( $this->getPosOrderedHTMLTable($data) );
                break;
        }
    }

    function Run($opt) {
        if ($opt == '') {
            $this->Form();
        } else {
            $this->MakeHTML();
        }
    }

}


