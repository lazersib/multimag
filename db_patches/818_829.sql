START TRANSACTION;

INSERT INTO `doc_types` (`id`, `name`) VALUES ('25', 'Акт корректировки');
INSERT INTO `users_objects` (`object`, `desc`, `actions`) 
    VALUES ('doc_corract', 'Акт корректировки', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel,today_apply,printna');

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (829);

COMMIT;

