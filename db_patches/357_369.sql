
CREATE TABLE `variables` (`corrupted` TINYINT NOT NULL COMMENT 'Признак нарушения целостности') ENGINE = MYISAM ;
INSERT INTO `variables` VALUES (0);


TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (369);


