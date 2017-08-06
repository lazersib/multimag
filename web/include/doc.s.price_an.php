<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2017, BlackLight, TND Team, http://tndproject.org
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

include_once("include/doc.s.sklad.php");

/// Служебный справочник для анализатора прайс-листов
class doc_s_Price_an extends doc_s_Sklad /// Наследование от doc_s_sklad для PosMenu
{
	function View() {
		global $tmpl;
		doc_menu();
		\acl::accessGuard('service.pricean', \acl::VIEW);
		$tmpl->addStyle("
		.tlist{border: 1px solid #bbb; width: 100%; border-collapse: collapse;}
		.tlist tr:nth-child(2n) {background: #e0f0ff; } 
		.tlist td{border: 1px solid #bbb;}
		");
		$tmpl->addContent("<table width='100%'><tr><td width='300'><h1>Анализатор прайсов</h1>
		<td align='right'></table><table width='100%'><tr><td id='groups' width='200' valign='top' class='lin0'>");
		$this->draw_groups(0);
		$tmpl->addContent("<td id='sklad' valign='top' >");
		$this->ViewBase();
		$tmpl->addContent("</table>");
	}
	
	// Служебные функции
	function Service() {
		global $tmpl, $db;
		$opt = request("opt");
		$g = rcvint('g');
		if($opt == 'pl') {
			$s = request('s');
			$tmpl->ajax = 1;
			if($s)	$this->ViewBaseS($s);
			else	$this->ViewBase($g);
		}
		else if($opt == 'ep') 
			$this->Edit();
		else if($opt == 'acost')	{
			$pos = rcvint('pos');
			$tmpl->ajax = 1;
			$tmpl->addContent( getInCost($pos) );
		}
		else if($opt == 'ceni') {
			$pos = rcvint('pos');
			$tmpl->ajax = 1;
			$res = $db->query("SELECT `firm_info`.`name`, `parsed_price`.`cost`, `parsed_price`.`nal`, `parsed_price`.`selected`,
				`firm_info`.`delivery_info` FROM `parsed_price`
				LEFT JOIN `firm_info` ON `firm_info`.`id`=`parsed_price`.`firm`
				WHERE `pos`='$pos'");
			$tmpl->addContent("<table class='list' width='100%'><tr><th>Фирма</th><th>Цена</th><th>Наличие</th><th>Доставка</th></tr>");
			while($nxt = $res->fetch_row()) {
				$sel = $nxt[3]?"style='background-color: #cfc'":'';
				$tmpl->addContent("<tr $sel><td>".html_out($nxt[0])."</td><td>$nxt[1]</td><td>".html_out($nxt[2])."</td><td>"
					.html_out($nxt[4])."</td></tr>");
			}	
			$tmpl->addContent("</table>");
		}
		else $tmpl->msg("Неверный режим!");
	}
        
    // Форма редактирвоания регулярных выражений анализа
    public function getRegExpEditForm($pos_id) {
        global $db;
        $res = $db->query("SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`proizv`, `seekdata`.`sql`, `seekdata`.`regex`,
            `seekdata`.`regex_neg`
            FROM `doc_base`
            LEFT JOIN `seekdata` ON `seekdata`.`id`=`doc_base`.`id`
            WHERE `doc_base`.`id`='$pos_id'");
        if (!$res->num_rows) {
            throw new \Exception('Объект не найден');
        }
        $nxt = $res->fetch_assoc();

        return "<form action='' method='post'><table cellpadding='0' width='100%' class='list'>
            <input type='hidden' name='mode' value='esave'>
            <input type='hidden' name='param' value='a'>
            <input type='hidden' name='l' value='pran'>
            <input type='hidden' name='pos' value='$pos_id'>
            <tr><td align='right' width='20%'>Наименование</td><td>" . html_out($nxt['name']) . "</td></tr>
            <tr><td align='right'>Производитель</td><td>" . html_out($nxt['proizv']) . "</td></tr>
            <tr><td align='right'><b style='color: #f00;'>*</b> Строка поиска совпадений:<td><input type='text' name='sql' value='" . html_out($nxt['sql']) . "' style='width: 95%' id='str' onkeydown=\"PriceRegTest('/docs.php?l=pran&amp;mode=edit&amp;param=ss');\">
            <tr><td align='right'>Регулярное выражение поиска:<td><input type='text' name='regex' value='" . html_out($nxt['regex']) . "' style='width: 95%'
             id='regex' onkeydown=\"PriceRegTest('/docs.php?l=pran&amp;mode=edit&amp;param=ss');\" >
            <tr><td align='right'>Регулярное выражение отрицания:<td><input type='text' name='regex_neg' value='" . html_out($nxt['regex_neg']) . "' style='width: 95%'
             id='regex_neg' onkeydown=\"PriceRegTest('/docs.php?l=pran&amp;mode=edit&amp;param=ss');\">
            <tr><td><td><button type='submit'>Сохранить</button></td></tr>
            </table></form>
            <div id='regex_result'></div>";
    }

    /// Редактирование параметров анализа
    function Edit() {
        global $tmpl, $db;
        $pos = rcvint('pos');
        $param = request('param');

        if ($param != 'ss') {
            doc_menu();
        }

        if ($pos != 0) {
            $this->PosMenu($pos, $param);
        }

        if ($param == 'a') {
            $tmpl->addContent($this->getRegExpEditForm($pos));
        } else if ($param == 'ss') {
            $tmpl->ajax = 1;

            $str = request('str');
            $regex = request('regex');
            $regex_neg = request('regex_neg');

            $res = $db->query("SELECT `id`, `search_str`, `replace_str` FROM `prices_replaces`");
            while ($nxt = $res->fetch_row()) {
                $regex = str_replace("{{{$nxt[1]}}}", $nxt[2], $regex);
                $regex_neg = str_replace("{{{$nxt[1]}}}", $nxt[2], $regex_neg);
            }

            if ($str == '') {
                $tmpl->msg("Строка поиска совпадений пуста!", "err", "Ошибка введённых данных!");
            } else if (@preg_match("/$regex/", 'abc') === FALSE) {
                $tmpl->msg("Регулярное выражение поиска составлено неверно!", "err", "Ошибка в регулярном выражении!");
            } else if (@preg_match("/$regex_neg/", 'abc') === FALSE) {
                $tmpl->msg("Регулярное выражение отрицания составлено неверно!", "err", "Ошибка в регулярном выражении!");
            } else {
                $str_array = preg_split("/( OR | AND )/", $str, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
                $i = 1;
                $sql_add = '';
                $conn = '';
                foreach ($str_array as $str_l) {
                    $str_l_sql = $db->real_escape_string($str_l);
                    $conn_sql = $db->real_escape_string($conn);
                    if ($i) {
                        $sql_add.=" $conn_sql (`price`.`name` LIKE '%$str_l_sql%' OR `price`.`art` LIKE '%$str_l_sql%')";
                    } else {
                        $conn = $str_l;
                    }
                    $i = 1 - $i;
                }

                $res = $db->query("SELECT `price`.`id`, `price`.`name`, `firm_info`.`name`, `price`.`cost`, `price`.`art` FROM `price`
				LEFT JOIN `firm_info` ON `firm_info`.`id`=`price`.`firm`
				WHERE $sql_add");
                $cnt = $res->num_rows;

                $tmpl->addContent("<b>Результаты отбора - $cnt совпадений со строкой *" . html_out($str) . "*:</b>
				<br>Выражение поиска:</b> " . html_out($regex) . "<br><b>Выражение отрицания:</b> " . html_out($regex_neg) . "<br>");
                $tmpl->addContent("<table class='list' width='100%'><tr><th>Price_id</th><th>Что</th><th>Где</th><th>Цена</th><th>Артикул</th></tr>");
                while ($nxt = $res->fetch_row()) {
                    $name_style = $art_style = $style = '';
                    if ($regex) {
                        $ns = 0;
                        if (preg_match("/$regex/", $nxt[1])) {
                            $name_style = 'background-color: #afa; ';
                            $ns = 1;
                        }
                        if (preg_match("/$regex/", $nxt[4])) {
                            $art_style = 'background-color: #afa; ';
                            $ns = 1;
                        }
                        if (!$ns) {
                            continue;
                        }
                    }

                    if ($regex_neg) {
                        $ns = 0;
                        if (preg_match("/$regex_neg/", $nxt[1])) {
                            $name_style .= 'text-decoration: line-through;';
                            $ns = 1;
                        }
                        if (preg_match("/$regex_neg/", $nxt[4])) {
                            $art_style .= 'text-decoration: line-through;';
                            $ns = 1;
                        }
                        if ($ns) {
                            $style = 'background-color: #faa; ';
                        }
                    }
                    $tmpl->addContent("<tr style='$style'><td>$nxt[0]</td><td style='$name_style'>$nxt[1]</td><td>" . html_out($nxt[2])
                        . "</td><td>" . html_out($nxt[3]) . "</td><td  style='$art_style'>" . html_out($nxt[4]) . "</td></tr>");
                }
                $tmpl->addContent("</table>");
            }
        } else {
            throw new \NotFoundException("Неизвестная закладка");
        }
    }

    /// Сохранение параметров анализа
    function ESave() {
        global $tmpl, $db;
        doc_menu();
        $pos = rcvint('pos');
        $param = request('param');
        \acl::accessGuard('service.pricean', \acl::UPDATE);
        if ($pos != 0)
            $this->PosMenu($pos, $param);

        if ($param == 'a') {
            try {
                $sql = request('sql');
                $regex = request('regex');
                $regex_neg = request('regex_neg');
                if ($sql == '') {
                    throw new ErrorException("Строка поиска совпадений пуста!");
                }
                if (preg_match("/$regex/", 'abc') === FALSE) {
                    throw new ErrorException("Регулярное выражение поиска составлено неверно!");
                }
                if (preg_match("/$regex_neg/", 'abc') === FALSE) {
                    throw new ErrorException("Регулярное выражение отрицания составлено неверно!");
                }
                $sql_sql = $db->real_escape_string($sql);
                $regex_sql = $db->real_escape_string($regex);
                $regex_neg_sql = $db->real_escape_string($regex_neg);
                $db->query("REPLACE `seekdata` (`id`, `sql`, `regex`, `regex_neg`) VALUES ('$pos', '$sql_sql', '$regex_sql', '$regex_neg_sql')");
                $tmpl->msg("Данные сохранены!");
            }
            catch(ErrorException $e) {
                $db->rollback();
                writeLogException($e);
                $tmpl->errorMessage($e->getMessage());
            }
            $tmpl->addContent($this->getRegExpEditForm($pos));
        } else {
            throw new \NotFoundException("Неизвестная закладка");
        }
    }

    function draw_level($select, $level) {
		global $db;
		$ret = '';
		settype($level, 'int');
		$res = $db->query("SELECT `id`, `name`, `desc` FROM `doc_group` WHERE `pid`='$level' ORDER BY `id`");
		$i=0;
		$r='';
		if($level == 0) $r = 'IsRoot';
		while($nxt = $res->fetch_row()) {
			if($nxt[0] == 0) continue;
			$item="<a href='' title='$nxt[2]' onclick=\"EditThis('/docs.php?l=pran&amp;mode=srv&amp;opt=pl&amp;g=$nxt[0]','sklad'); return false;\" >"
				.html_out($nxt[1])."</a>";
			if($i>=($res->num_rows-1)) $r .= " IsLast";
	
			$tmp = $this->draw_level($select, $nxt[0]);
			if($tmp)
				$ret.="<li class='Node ExpandClosed $r'><div class='Expand'></div><div class='Content'>$item</div><ul class='Container'>".$tmp.'</ul></li>';
			else
				$ret.="<li class='Node ExpandLeaf $r'><div class='Expand'></div><div class='Content'>$item</div></li>";
			$i++;
		}
		return $ret;
	}
	
	function draw_groups($select) {
		global $tmpl;
		$tmpl->addContent("
		<div onclick='tree_toggle(arguments[0])'>
		<div><a href='' title='' onclick=\"EditThis('/docs.php?l=pran&amp;mode=srv&amp;opt=pl&amp;g=0','sklad'); return false;\" >Группы</a></div>
		<ul class='Container'>".$this->draw_level($select,0)."</ul></div>
		Или отбор:<input type='text' id='sklsearch' onkeydown=\"DelayedSave('/docs.php?l=pran&amp;mode=srv&amp;opt=pl','sklad', 'sklsearch'); return true;\" >
		");
	}

	function ViewBase($group=0, $s='') {
		global $tmpl, $db;
		settype($group, 'int');
		if($group) {
			$desc_data = $db->selectRow('doc_group', $group);
			if($desc_data['desc']) $tmpl->addContent(html_out($desc_data['desc']).'<br>');
		}
        
		$sql = "SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost`, `parsed_price`.`cost`, `parsed_price`.`nal`,
		`firm_info`.`name`, `firm_info`.`coeff`, `currency`.`coeff`, `price`.`name`, `price`.`cost`, `price`.`art`,
                `doc_base_dop`.`reserve`, `doc_base_dop`.`transit`, `doc_base_dop`.`offer`
		FROM `doc_base`
                LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_list_pos`.`tovar`
		LEFT JOIN `parsed_price` ON `doc_base`.`id`=`parsed_price`.`pos`
		LEFT JOIN `firm_info` ON `firm_info`.`id`=`parsed_price`.`firm`
		LEFT JOIN `currency` ON `firm_info`.`currency`=`currency`.`id`
		LEFT JOIN `price` ON `price`.`id`=`parsed_price`.`from`
		WHERE  `doc_base`.`group`='$group'
		ORDER BY `doc_base`.`id`, `parsed_price`.`cost`";

		$lim = 50;
		$page = rcvint('p');
		$res = $db->query($sql);
		$row = $res->num_rows;
		if($row>$lim)
		{
			$dop = "g=$group";
			if($page<1) $page = 1;
			if($page>1) {
				$i = $page-1;
				$tmpl->addContent("<a href='' onclick=\"EditThis('/docs.php?l=pran&amp;mode=srv&amp;opt=pl&amp;$dop&amp;p=$i','sklad'); return false;\">&lt;&lt;</a> ");
			}
			$cp = $row/$lim;
			for($i=1;$i<($cp+1);$i++) {
				if($i==$page) $tmpl->addContent(" <b>$i</b> ");
				else $tmpl->addContent("<a href='' onclick=\"EditThis('/docs.php?l=pran&amp;mode=srv&amp;opt=pl&amp;$dop&amp;p=$i','sklad'); return false;\">$i</a> ");
			}
			if($page<$cp) {
				$i = $page+1;
				$tmpl->addContent("<a href='' onclick=\"EditThis('/docs.php?l=pran&amp;mode=srv&amp;opt=pl&amp;$dop&amp;p=$i','sklad'); return false;\">&gt;&gt;</a> ");
			}
			$tmpl->addContent("<br>");
			$sl = ($page-1)*$lim;
	
			$res->data_seek($sl);
		}

		if($row) {
			$tmpl->addContent("<table class='tlist' cellspacing='1'><tr>
			<th>№<th>Наименование</th><th>Наша цена</th><th>Цена</th><th>Наличие</th><th>Фирма</th></tr>");
			$i = 0;
			$this->DrawSkladTable($res, $s, $lim);
			$tmpl->addContent("</table>");
		}
		else $tmpl->msg("В выбранной группе товаров не найдено!");
	}
	
	function ViewBaseS($s) {
		global $tmpl, $db;
		$sf = 0;
		$s_sql = $db->real_escape_string($s);
		$tmpl->addContent("<b>Показаны наименования изо всех групп!</b><br>");
		$tmpl->addContent("<table width='100%' cellspacing='1' cellpadding='2' border='1' class='list'>
		<tr><th>№</th><th>Наименование</th><th>Наша цена</th><th>Цена</th><th>Наличие</th><th>Фирма</th></tr>");
		
		$sql = "SELECT `doc_base`.`id`, `doc_base`.`name`, `doc_base`.`cost`, `parsed_price`.`cost`, `parsed_price`.`nal`,
		`firm_info`.`name`, `firm_info`.`coeff`, `currency`.`coeff`, `price`.`name`, `price`.`cost`, `price`.`art`,
                `doc_base_dop`.`reserve`, `doc_base_dop`.`transit`, `doc_base_dop`.`offer`
		FROM `doc_base`
		LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
		LEFT JOIN `parsed_price` ON `doc_base`.`id`=`parsed_price`.`pos`
		LEFT JOIN `firm_info` ON `firm_info`.`id`=`parsed_price`.`firm`
		LEFT JOIN `currency` ON `firm_info`.`currency`=`currency`.`id`
		LEFT JOIN `price` ON `price`.`id`=`parsed_price`.`from` ";
        	
        	$limit = 100;
		$sqla = $sql."WHERE `doc_base`.`name` LIKE '$s_sql%'
		ORDER BY `doc_base`.`id`, `parsed_price`.`cost`
 		LIMIT $limit";
		$res = $db->query($sqla);

		if($res->num_rows) {
			$tmpl->addContent("<tr><th colspan='18' align='center'>Поиск по названию, начинающемуся на ".html_out($s)
				.": найдено {$res->num_rows}, $limit максимум");
			$this->DrawSkladTable($res, $s, $limit);
			$sf = 1;
		}

		$limit = 30;
		$sqla = $sql."WHERE `doc_base`.`name` LIKE '%$s_sql%' AND `doc_base`.`name` NOT LIKE '$s_sql%' ORDER BY `doc_base`.`name` LIMIT $limit";
		$res = $db->query($sqla);
		if($res->num_rows) {
			$tmpl->addContent("<tr class='lin0'><th colspan='18' align='center'>Поиск по названию, содержащему ".html_out($s)
				.": найдено {$res->num_rows}, $limit максимум");
			$this->DrawSkladTable($res, $s, $limit);
			$sf = 1;
		}
		
		$sqla = $sql."
		WHERE `doc_base_dop`.`analog` LIKE '%$s_sql%' AND `doc_base`.`name` NOT LIKE '%$s_sql%' ORDER BY `doc_base`.`name` LIMIT $limit";
		$res = $db->query($sqla);
		if($res->num_rows) {
			$tmpl->addContent("<tr class='lin0'><th colspan='18' align='center'>Поиск аналога, для ".html_out($s)
				.": найдено {$res->num_rows}, $limit максимум");
			$this->DrawSkladTable($res, $s, $limit);
			$sf = 1;
		}
		if($sf == 0)	$tmpl->msg("По данным критериям товаров не найдено!");
	}
	
	function DrawSkladTable($res, $s = '', $limit = 1000000) {
		global $tmpl, $CONFIG;
		$i = $c = 0;
		$old_id = $old_cost = 0;
		$lin = $old_name = '';
		while ($nxt = $res->fetch_array()) {
			$rezerv = $CONFIG['poseditor']['rto'] ? $nxt['reserve'] : '';
			$pod_zakaz = $CONFIG['poseditor']['rto'] ? $nxt['offer'] : '';
			$v_puti = $CONFIG['poseditor']['rto'] ? $nxt['transit'] : '';

			if ($rezerv)
				$rezerv = "<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=rezerv&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$rezerv</a>";
			if ($pod_zakaz)
				$pod_zakaz = "<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$pod_zakaz</a>";
			if ($v_puti)
				$v_puti = "<a onclick=\"ShowPopupWin('/docs.php?l=inf&mode=srv&opt=vputi&pos=$nxt[0]'); return false;\"  title='Отобразить документы' href='/docs.php?l=inf&mode=srv&opt=p_zak&pos=$nxt[0]'>$v_puti</a>";

			// Дата цены $nxt[5]
			$dcc = strtotime($nxt[6]);
			$cc = '';
			if ($dcc > (time() - 60 * 60 * 24 * 30 * 3))
				$cc = "class=f_green";
			else if ($dcc > (time() - 60 * 60 * 24 * 30 * 6))
				$cc = "class=f_purple";
			else if ($dcc > (time() - 60 * 60 * 24 * 30 * 9))
				$cc = "class=f_brown";
			else if ($dcc > (time() - 60 * 60 * 24 * 30 * 12))
				$cc = "class=f_more";
			$end = date("Y-m-d");

			if ($nxt[0] != $old_id) {
				$i = 1 - $i;
				if ($old_id)
					$tmpl->addContent("<tr><td rowspan='$c'><a href='/docs.php?mode=srv&amp;l=pran&amp;opt=ep&amp;param=a&amp;pos=$old_id'>$old_id</a></td><td align='left' rowspan='$c'>".html_out($old_name)."</td><td rowspan='$c'>$old_cost $lin");
				$old_id = $nxt[0];
				$old_cost = $nxt[2];
				$lin = '';
				$c = 0;
				$old_name = $nxt[1];
			}

			if ($lin)
				$lin.="<tr>";
			if ($nxt[6] == 0)
				$nxt[6] = 1;
			if ($nxt[7] == 0)
				$nxt[7] = 1;
			$coeff = $nxt[6] * $nxt[7];
			if ($nxt[9] != '')
				$lin.="<td title='".html_out($nxt[8])."'>$nxt[3] ($nxt[9]*$coeff)<td>$nxt[4]<td>$nxt[5] ($nxt[10])";
			else
				$lin.="<td>-<td>-<td>-";
			$c++;
			if ($c++ >= $limit)
				break;
		} {
			$i = 1 - $i;
			if ($old_id)
				$tmpl->addContent("<tr><td rowspan='$c'><a href='/docs.php?l=pran&amp;mode=srv&amp;opt=ep&amp;pos=$old_id'>$old_id</a><td align=left rowspan='$c'>".html_out($old_name)."<td rowspan='$c'>$old_cost $lin");
			$old_id = $nxt[0];
			$lin = '';
			$c = 0;
			$old_name = $nxt[1];
		}
	}
	
}