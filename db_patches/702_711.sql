ALTER TABLE `news` ADD `hidden` TINYINT NOT NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (711);


