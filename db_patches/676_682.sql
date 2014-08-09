ALTER TABLE `doc_base` ADD `analog_group` VARCHAR( 32 ) NOT NULL,
ADD `mass` DOUBLE NOT NULL,
ADD INDEX ( `analog_group` ),
ADD INDEX ( `mass` );

UPDATE `doc_base`,`doc_base_dop` SET `doc_base`.`analog_group` = `doc_base_dop`.`analog` WHERE `doc_base`.`id`=`doc_base_dop`.`id`;
UPDATE `doc_base`,`doc_base_dop` SET `doc_base`.`mass` = `doc_base_dop`.`mass` WHERE `doc_base`.`id`=`doc_base_dop`.`id`;


TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (682);


