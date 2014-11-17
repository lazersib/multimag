UPDATE `users_objects` SET `actions` = 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel,today_apply'
    WHERE `object` LIKE 'doc_%' AND `actions` LIKE '%apply%';


TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (717);


