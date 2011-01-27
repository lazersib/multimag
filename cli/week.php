#!/usr/bin/php
<?php
// Автоочистка базы от устаревшей информации: неактивированные пользователи, итп

$c=explode('/',__FILE__);$base_path='';
for($i=0;$i<(count($c)-2);$i++)	$base_path.=$c[$i].'/';
include_once("$base_path/config_cli.php");



?>
