 ALTER TABLE `doc_base` CHANGE `unit` `unit` INT( 11 ) NULL COMMENT 'Единица измерения';

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (764);


