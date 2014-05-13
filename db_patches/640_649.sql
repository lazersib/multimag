CREATE TABLE IF NOT EXISTS `users_unsubscribe_log` (
  `email` varchar(64) NOT NULL,
  `phone` varchar(32) NOT NULL,
  `time` datetime NOT NULL,
  `source` varchar(32) NOT NULL,
  `is_user` int NOT NULL,
  KEY `email` (`email`),
  KEY `phone` (`phone`),
  KEY `time` (`time`),
  KEY `source` (`source`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (649);


