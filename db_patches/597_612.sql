CREATE TABLE IF NOT EXISTS `tickets_responsibles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uni` (`ticket_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `tickets_responsibles` (`ticket_id`, `user_id`) SELECT `id`, `to_uid` FROM `tickets`;

ALTER TABLE `tickets` DROP `to_uid`;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (612);


