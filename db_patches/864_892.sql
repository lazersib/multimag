ALTER TABLE `doc_base` ADD `eol` TINYINT NOT NULL AFTER `no_export_yml`, ADD INDEX (`eol`) ;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (892);
