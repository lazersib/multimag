ALTER TABLE `doc_log` CHANGE `desc` `desc` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (896);
