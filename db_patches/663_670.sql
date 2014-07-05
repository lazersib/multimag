ALTER TABLE `wikiphoto` ADD `ext` VARCHAR( 4 ) NOT NULL AFTER `id`;
UPDATE `wikiphoto` SET `ext`='jpg';

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (670);


