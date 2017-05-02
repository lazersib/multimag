ALTER TABLE `doc_dopdata` CHANGE `value` `value` VARCHAR(192) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

UPDATE `doc_log` SET `motion`='UNMARKDELETE' WHERE `motion`='UNDELETE' AND `object`='doc';

UPDATE `doc_base` SET `nds`='18';

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (950);
