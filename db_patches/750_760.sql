ALTER TABLE `counter` ADD `session_id` VARCHAR( 64 ) NOT NULL;
ALTER TABLE `users` ADD `last_session_id` VARCHAR( 64 ) NOT NULL;
INSERT INTO `users_objects` (`object`,`desc`,`actions`) VALUES ('doc_1csync', 'Синхронизация с 1С', 'view,edit,exec');

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (760);


