INSERT INTO `users_objects` (`object`,`desc`,`actions`) VALUES ('report_incost', 'Отчет по себестоимости', 'view');

RENAME TABLE `doc_rasxodi` TO `doc_dtypes` ;
ALTER TABLE `doc_dtypes` ADD `account` VARCHAR( 8 ) NOT NULL COMMENT 'Бух. счет' AFTER `id`;
ALTER TABLE `doc_dtypes` ADD INDEX ( `account` );

CREATE TABLE IF NOT EXISTS `doc_ctypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account` varchar(8) NOT NULL,
  `name` varchar(64) NOT NULL,  
  UNIQUE KEY `id` (`id`),
  KEY `account` (`account`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Статьи доходов' ;

CREATE TABLE IF NOT EXISTS `doc_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account` varchar(8) NOT NULL,
  `name` varchar(64) NOT NULL, 
  `usedby` varchar(64) NOT NULL,  
  UNIQUE KEY `id` (`id`),
  KEY `account` (`account`),
  KEY `name` (`name`),
  KEY `usedby` (`usedby`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Бухгалтерские счета' ;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (695);


