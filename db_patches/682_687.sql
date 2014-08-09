ALTER TABLE `fabric_builders` ADD `store_id` INT NULL DEFAULT NULL, ADD INDEX ( `store_id` );
UPDATE `users_objects` SET `object`='doc_factory' WHERE `object`='doc_fabric';
UPDATE `users_groups_acl` SET `object`='doc_factory' WHERE `object`='doc_fabric';

RENAME TABLE `fabric_builders` TO `factory_builders`;
RENAME TABLE `fabric_data` TO `factory_data` ;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (687);


