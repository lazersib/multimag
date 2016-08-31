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

/// Отчёт с блоком выбора складских групп
class baseGSReport extends BaseReport {

    function draw_groups_tree($level) {
        global $db;
        $ret = '';
        settype($level, 'int');
        $res = $db->query("SELECT `id`, `name`, `desc` FROM `doc_group` WHERE `pid`='$level' ORDER BY `name`");
        $i = 0;
        $r = $cbroot = '';
        if ($level == 0) {
            $r = 'IsRoot';
            $cbroot = " data-isroot='1'";
        }
        $cnt = $res->num_rows;
        while ($nxt = $res->fetch_row()) {
            if ($nxt[0] == 0) {
                continue;
            }
            $item = "<label><input type='checkbox' name='g[]'{$cbroot} value='$nxt[0]' id='cb$nxt[0]' class='cb' checked onclick='CheckCheck($nxt[0])'>$nxt[1]</label>";
            if ($i >= ($cnt - 1)) {
                $r.=" IsLast";
            }
            $tmp = $this->draw_groups_tree($nxt[0]); // рекурсия
            if ($tmp) {
                $ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div><ul class='Container' id='cont$nxt[0]'>" . $tmp . '</ul></li>';
            } else {
                $ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
            }
            $i++;
        }
        return $ret;
    }

    function GroupSelBlock() {
        global $tmpl;
        $tmpl->addStyle(".scroll_block
            {
                    max-height:		250px;
                    overflow:		auto;
            }

            div#sb
            {
                    display:		none;
                    border:			1px solid #888;
            }

            .selmenu
            {
                    background-color:	#888;
                    width:			auto;
                    font-weight:		bold;
                    padding-left:		20px;
            }

            .selmenu a
            {
                    color:			#fff;
                    cursor:			pointer;
            }

            .cb
            {
                    width:			14px;
                    height:			14px;
                    border:			1px solid #ccc;
            }

            ");
        $tmpl->addContent("<script type='text/javascript'>
            function gstoggle()
            {
                    var gs=document.getElementById('cgs').checked;
                    if(gs==true)
                            document.getElementById('sb').style.display='block';
                    else	document.getElementById('sb').style.display='none';
            }

            function SelAll(flag)
            {
                    var elems = document.getElementsByName('g[]');
                    var l = elems.length;
                    for(var i=0; i<l; i++)
                    {
                            elems[i].checked=flag;
                            if(flag) {
                                elems[i].disabled = false;
                            }
                            else {
                                var isroot = elems[i].getAttribute('data-isroot');
                                if(!isroot) {
                                    elems[i].disabled = true;
                                }
                            }
                    }
            }

            function CheckCheck(ids)
            {
                    var cb = document.getElementById('cb'+ids);
                    var cont=document.getElementById('cont'+ids);
                    if(!cont)	return;
                    var elems=cont.getElementsByTagName('input');
                    var l = elems.length;
                    for(var i=0; i<l; i++)
                    {
                            if(!cb.checked)		elems[i].checked=false;
                            elems[i].disabled =! cb.checked;
                    }
            }

            </script>
            <label><input type=checkbox name='gs' id='cgs' value='1' onclick='gstoggle()'>Выбрать группы</label><br>
            <div class='scroll_block' id='sb'>
            <ul class='Container'>
            <div class='selmenu'><a onclick='SelAll(true)'>Выбрать всё<a> | <a onclick='SelAll(false)'>Снять всё</a></div>
            " . $this->draw_groups_tree(0) . "</ul></div>");
    }

}
