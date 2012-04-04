SET UNIQUE_CHECKS=0;
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='TRADITIONAL';

ALTER TABLE `doc_base_dop` CHANGE `type` `type` INT( 11 ) NULL DEFAULT NULL;
ALTER TABLE `doc_base_dop` ADD FOREIGN KEY ( `type` ) REFERENCES `dev`.`doc_base_dop_type` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION ;


ALTER TABLE `counter` CHANGE `query` `query` VARCHAR( 128 ) NOT NULL DEFAULT '',
	CHANGE `refer` `refer` VARCHAR( 512 ) NOT NULL DEFAULT '',
	CHANGE `agent` `agent` VARCHAR( 128 ) NOT NULL DEFAULT '';

CREATE TABLE IF NOT EXISTS `ps_counter` (
  `date` date NOT NULL DEFAULT '1970-01-01',
  `query` int(11) NOT NULL DEFAULT '0',
  `ps` int(11) NOT NULL DEFAULT '0',
  `counter` int(11) NOT NULL,
  PRIMARY KEY (`date`,`query`,`ps`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `ps_parser` (
  `parametr` varchar(24) NOT NULL,
  `data` varchar(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`parametr`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


INSERT INTO `ps_parser` (`parametr`, `data`) VALUES
	('last_time_counter', '0');

CREATE TABLE IF NOT EXISTS `ps_query` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `query` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `ps_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `icon` varchar(4) NOT NULL,
  `name` varchar(16) NOT NULL,
  `template` varchar(128) NOT NULL,
  `template_like` varchar(64) NOT NULL,
  `priority` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;


INSERT INTO `ps_settings` (`id`, `icon`, `name`, `template`, `template_like`, `priority`) VALUES
	(1, 'Y', 'yandex', '/.*?yandex.*?text=[\\. \\s]*(\\w+.*?)[\\. \\s]*($|&.*)/', '%yandex%text=%', 1),
	(2, 'G', 'google', '/.*?google.*?q=[\\. \\s]*(\\w+.*?)[\\. \\s]*($|&.*)/', '%google%q=%', 2),
	(3, 'M', 'mail', '/.*?mail.*?q=[\\. \\s]*(\\w+.*?)[\\. \\s]*($|&.*)/', '%mail%q=%', 3),
	(4, 'R', 'rambler', '/.*?rambler.*?query=[\\. \\s]*(\\w+.*?)[\\. \\s]*($|&.*)/', '%rambler%query=%', 4),
	(5, 'B', 'bing', '/.*?bing.*?q=[\\. \\s]*(\\w+.*?)[\\. \\s]*($|&.*)/', '%bing%q=%', 5),
	(6, 'Q', 'qip', '/.*?qip.*?query=[\\. \\s]*(\\w+.*?)[\\. \\s]*($|&.*)/', '%qip%query=%', 6),
	(7, 'N', 'ngs', '/.*?ngs.*?q=[\\. \\s]*(\\w+.*?)[\\. \\s]*($|&.*)/', '%ngs%q=%', 7);

INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES (NULL, 'generic_tickets', 'Планировщик задач', 'view,edit,create,redirect');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES (NULL, 'sys_ps-stat', 'Статистика переходов с поисковиков', 'view');


START TRANSACTION;
TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (338);

COMMIT;
