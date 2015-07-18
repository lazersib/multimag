ALTER TABLE `doc_base_params` ADD `unit_id` INT NULL COMMENT 'Кодовое название для скриптов' AFTER `type`;
ALTER TABLE `doc_base_params` ADD INDEX(`unit_id`);
ALTER TABLE `doc_base_params` ADD INDEX(`hidden`);
ALTER TABLE `doc_base_params` ADD FOREIGN KEY (`unit_id`) REFERENCES `class_unit`(`id`) ON DELETE SET NULL ON UPDATE SET NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (791);


