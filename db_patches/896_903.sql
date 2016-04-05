ALTER TABLE `doc_agent` CHANGE `dir_fio` `leader_name` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `doc_agent` CHANGE `dir_fio_r` `leader_name_r` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `doc_agent` ADD `leader_post` VARCHAR(64) NOT NULL AFTER `leader_name`, ADD `leader_reason` VARCHAR(32) NOT NULL AFTER `leader_post`;
ALTER TABLE `doc_agent` ADD `leader_post_r` VARCHAR(64) NOT NULL AFTER `leader_name_r`, ADD `leader_reason_r` VARCHAR(32) NOT NULL AFTER `leader_post_r`;



TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (903);
