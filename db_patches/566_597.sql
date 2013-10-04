INSERT INTO `doc_types` (`id`, `name`) VALUES ('21', 'Заявка на сборку');

INSERT INTO `users_objects` (`object`,`desc`,`actions`)VALUES ('doc_zsbor', 'Заявка на сборку', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');



TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (597);


