UPDATE `doc_types` SET `name`='Сборка изделия' WHERE `id`=17;
UPDATE `doc_types` SET `name`='Заявка на производство' WHERE `id`=21;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (634);


