INSERT INTO `users_objects` (`object`, `desc`, `actions`) VALUES ('report_apay', 'Отчёт по платежам агентов', 'view');
INSERT IGNORE INTO `doc_dopdata` (`doc`, `param`, `value`) SELECT `doc_list`.`id`, 'status', 'ok' FROM `doc_list` WHERE `doc_list`.`type`='3';

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (400);


