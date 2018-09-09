ALTER TABLE `doc_group` ADD `vieworder` INT NOT NULL DEFAULT '9999'  AFTER `desc`;


TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (982);
