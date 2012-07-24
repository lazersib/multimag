UPDATE `users_objects` SET `desc`='view,edit,delete' WHERE `object`='doc_service';
ALTER TABLE `doc_base_cnt` CHANGE `mincnt` `mincnt` VARCHAR(8) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (382);


