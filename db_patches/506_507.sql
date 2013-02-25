CREATE TABLE IF NOT EXISTS `votings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Голосования' ;

CREATE TABLE IF NOT EXISTS `votings_vars` (
  `voting_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `text` varchar(128) NOT NULL,
  UNIQUE KEY `uni` (`voting_id`,`variant_id`),
  KEY `voting_id` (`voting_id`),
  KEY `variant_id` (`variant_id`),
  KEY `text` (`text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `votings_vars`
  ADD CONSTRAINT `votings_vars_ibfk_1` FOREIGN KEY (`voting_id`) REFERENCES `votings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE IF NOT EXISTS `votings_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `voting_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_addr` varchar(32) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq` (`voting_id`,`variant_id`,`user_id`,`ip_addr`),
  KEY `voting_id` (`voting_id`),
  KEY `vars_id` (`variant_id`),
  KEY `user_id` (`user_id`),
  KEY `ip_addr` (`ip_addr`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Голоса';

ALTER TABLE `votings_results`
  ADD CONSTRAINT `votings_results_ibfk_4` FOREIGN KEY (`variant_id`) REFERENCES `votings_vars` (`variant_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `votings_results_ibfk_1` FOREIGN KEY (`voting_id`) REFERENCES `votings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `votings_results_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (507);


