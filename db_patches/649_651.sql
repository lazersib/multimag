ALTER TABLE `doc_agent` ADD `kpp` VARCHAR( 16 ) NOT NULL AFTER `inn`, ADD INDEX ( `kpp` );
UPDATE `doc_agent` SET `kpp` = SUBSTR( `inn` , LOCATE( '/', `inn` )+1 ) WHERE `inn` LIKE '%/%';
UPDATE `doc_agent` SET `inn` = LEFT( `inn` , LOCATE( '/', `inn` )-1 ) WHERE `inn` LIKE '%/%';
ALTER TABLE `doc_agent` CHANGE `okevd` `okved` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE `doc_agent` ADD `ogrn` VARCHAR( 16 ) NOT NULL AFTER `okpo`, ADD INDEX ( `ogrn` );

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (651);


