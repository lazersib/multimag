<?php

function skin_render($page,$tpl)
{
	global $tmpl, $CONFIG;
	
	$rr=$ll='';
	if(isset($_SESSION['korz_cnt'])) 
	{
		$rr="style='background-color: #f94;'";
		$ll="style='color: #fff; font-weight: bold;'";
	}
	
	$page=$tmpl->page;
	
	ksort($page);
	$sign=array("<!--site-text-->","<!--site-tmenu-->","<!--site-rmenu-->","<!--site-title-->","<!--site-style-->",
	"<!--site-lmenu-->","<!--site-notes-->");
	if(!isset($tmpl->hide_blocks['left'])) $tpl=str_replace("<!--site-text-->","<div id='wiki-menu' class='wiki-menu'><!--site-lmenu--></div><div id='wiki-page' class='wiki-page'><!--site-text--></div>",$tpl);
	else $tpl=str_replace("<!--site-text-->","<div id='wiki-page-nolmenu' class='wiki-page-nolmenu'><!--site-text--></div>",$tpl);
	if(!isset($tmpl->hide_blocks['right'])) $tpl=str_replace("<!--site-rmenu-->","<div id='info-right'><ul><!--site-rmenu--></ul></div>",$tpl);
	$res=str_replace($sign,$page,$tpl);
	return $res;
}


?>
