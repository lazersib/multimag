ALTER TABLE `doc_agent`
	ADD `no_bonuses` TINYINT NOT NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (692);


