ALTER TABLE `doc_agent` ADD `bonus` DECIMAL( 10, 2 ) NOT NULL;
INSERT INTO `doc_types` (`id`,`name`) VALUES ('19', 'Корректировка бонусов'), ('20', 'Реализация за бонусы');
INSERT INTO `users_objects` (`object`, `desc`, `actions`) VALUES('doc_korbonus', 'Корректировка бонусного баланса', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
INSERT INTO `users_objects` (`object`, `desc`, `actions`) VALUES('doc_realiz_bonus', 'Реализация за бонусы', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (478);


