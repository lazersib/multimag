UPDATE `news` SET `type`='novelty' WHERE `type`='';

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (451);


