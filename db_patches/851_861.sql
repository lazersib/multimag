ALTER TABLE `doc_vars` ADD `param_pricecoeff` DECIMAL(6,3) NOT NULL AFTER `param_nds`;
ALTER TABLE `doc_kassa` ADD `comment` TEXT NOT NULL ;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (861);
