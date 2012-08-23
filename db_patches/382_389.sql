UPDATE `users_objects` SET `actions`='view,edit,delete', `desc`='Служебные функции' WHERE `object`='doc_service';
ALTER TABLE `doc_agent` ADD `sms_phone` VARCHAR(16) NOT NULL AFTER `tel` , ADD `fax_phone` VARCHAR(16) NOT NULL AFTER `sms_phone` , ADD `alt_phone` VARCHAR(16) NOT NULL AFTER `fax_phone`;
UPDATE `doc_agent` SET `sms_phone`=`tel`, `fax_phone`=`tel`;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (389);


