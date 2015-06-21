ALTER TABLE `doc_base_params` CHANGE `param` `name` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Отображаемое наименование параметра';
ALTER TABLE `doc_base_params` CHANGE `pgroup_id` `group_id` INT(11) NULL DEFAULT NULL AFTER `id`;
ALTER TABLE `doc_base_params` CHANGE `system` `hidden` TINYINT(4) NOT NULL COMMENT 'Флаг сокрытия';
ALTER TABLE `doc_base_params` ADD `codename` VARCHAR(32) NOT NULL COMMENT 'Кодовое название для скриптов' AFTER `name`;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (787);


