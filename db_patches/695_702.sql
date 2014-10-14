INSERT INTO `users_objects` (`object`,`desc`,`actions`) VALUES ('admin_mailconfig', 'Настройка почтовых ящиков и алиасов', 'view,create,edit,delete');
INSERT INTO `users_objects` (`object`,`desc`,`actions`) VALUES ('doc_factory_builders', 'Справочник сборщиков на производстве', 'view,create,edit,delete');

UPDATE `users_objects` SET `actions`='view,edit,delete' WHERE `object`='admin_users';

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (702);


