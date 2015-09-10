INSERT INTO `doc_types` (`id`, `name`) VALUES ('24', 'Информация о платеже');
INSERT INTO `users_objects` (`object`, `desc`, `actions`) 
    VALUES ('doc_payinfo', 'Информация о платеже', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel,today_apply,printna');

ALTER TABLE `doc_dtypes` ADD `codename` VARCHAR(16) NULL , ADD UNIQUE (`codename`) ;


TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (818);


