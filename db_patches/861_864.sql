ALTER TABLE `doc_base_params` ADD `secret` TINYINT NOT NULL , ADD INDEX (`secret`) ;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (864);
