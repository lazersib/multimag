<?php
include_once("core.php");

$tmpl->SetContent("<h1>Оплата заказа</h1>");

$tmpl->msg("Оплата заказа завершилась неудачно","err");

$tmpl->AddContent("<a href='/vitrina.php?mode=pay'>Попробвать ещё раз</a>");

$tmpl->write();
?>
