DROP TABLE `questions`;
DROP TABLE `question_answ`;
DROP TABLE `question_ip`;
DROP TABLE `question_vars`;


CREATE TABLE IF NOT EXISTS `survey` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_text` text NOT NULL,
  `end_text` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `start_date` (`start_date`),
  KEY `end_date` (`end_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `survey_answer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) NOT NULL,
  `question_num` int(11) NOT NULL,
  `answer_txt` varchar(64) NOT NULL,
  `answer_int` int(11) NOT NULL,
  `comment` varchar(256) NOT NULL,
  `uid` int(11) DEFAULT NULL,
  `ip_address` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq` (`survey_id`,`question_num`,`uid`,`ip_address`),
  KEY `survey_id` (`survey_id`),
  KEY `question_id` (`question_num`),
  KEY `uid` (`uid`),
  KEY `ip_addres` (`ip_address`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `survey_ok` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) NOT NULL,
  `uid` int(11) DEFAULT NULL,
  `ip` varchar(32) NOT NULL,
  `result` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `survey_id` (`survey_id`),
  KEY `uid` (`uid`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `survey_question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) NOT NULL,
  `question_num` int(11) NOT NULL,
  `text` varchar(256) NOT NULL,
  `type` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `survey_id` (`survey_id`),
  KEY `question_num` (`question_num`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `survey_quest_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_num` int(11) NOT NULL,
  `text` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq` (`survey_id`,`question_id`,`option_num`),
  KEY `survey_id` (`survey_id`),
  KEY `question_id` (`question_id`),
  KEY `num` (`option_num`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `survey_answer`
  ADD CONSTRAINT `survey_answer_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `survey` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `survey_answer_ibfk_3` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `survey_answer_ibfk_4` FOREIGN KEY (`question_num`) REFERENCES `survey_question` (`question_num`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `survey_ok`
  ADD CONSTRAINT `survey_ok_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `survey` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `survey_ok_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

ALTER TABLE `survey_question`
  ADD CONSTRAINT `survey_question_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `survey` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `survey_quest_option`
  ADD CONSTRAINT `survey_quest_option_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `survey` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `survey_quest_option_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `survey_question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;



TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (512);


