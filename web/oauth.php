<?php

include("core.php");

try {
    $login_page = new \Modules\Site\oauthLogin();
    $login_page->run();
}
catch(mysqli_sql_exception $e) {
    $id = writeLogException($e);
    $tmpl->msg("Ошибка при регистрации. Порядковый номер - $id<br>Сообщение передано администратору",'err',"Ошибка при регистрации");
    mailto($CONFIG['site']['admin_email'],"ВАЖНО! Ошибка регистрации на ".$CONFIG['site']['name'].". номер в журнале - $id", $e->getMessage());
}
catch(Exception $e) {
    $db->rollback();
    $id = writeLogException($e);
    $tmpl->errorMessage($e->getMessage() . ". Порядковый номер - $id<br>Сообщение передано администратору", "Ошибка при аутентификации");
}

$tmpl->write();
