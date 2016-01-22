ALTER TABLE `doc_base_params` ADD `secret` TINYINT NOT NULL , ADD INDEX (`secret`) ;
ALTER TABLE `doc_sklady` ADD `hidden` TINYINT NOT NULL , ADD INDEX (`hidden`);
ALTER TABLE `sites` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `tickets` CHANGE `state` `state` VARCHAR(16) NOT NULL;
ALTER TABLE `tickets` ADD `resolution` VARCHAR(16) NOT NULL ;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (864);
