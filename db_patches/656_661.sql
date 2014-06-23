INSERT INTO `users_objects` (`object`, `desc`, `actions`)
	VALUES ('report_noimg', 'Отчёт по товарам без изображений', 'view');

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (661);


