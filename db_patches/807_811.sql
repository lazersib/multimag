INSERT INTO `doc_types` (`id`, `name`) VALUES ('23', 'Пропуск');
INSERT INTO `users_objects` (`object`, `desc`, `actions`) 
    VALUES ('doc_permitout', 'Пропуск', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel,today_apply,printna');

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (811);


