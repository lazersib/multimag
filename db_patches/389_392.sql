ALTER TABLE `variables` ADD `recalc_active` INT(9) NOT NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (392);


