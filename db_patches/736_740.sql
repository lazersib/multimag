ALTER TABLE `doc_base` ADD `nds` TINYINT NULL COMMENT 'Ставка НДС';


TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (740);


