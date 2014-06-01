CREATE TABLE IF NOT EXISTS `users_basket` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `pos_id` int(11) NOT NULL,
  `cnt` int(11) NOT NULL,
  `comment` varchar(128) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uni` (`user_id`,`pos_id`),
  KEY `user_id` (`user_id`),
  KEY `pos_id` (`pos_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Сохранённые корзины пользователей';

ALTER TABLE `users_basket` ADD FOREIGN KEY ( `user_id` ) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE ;
ALTER TABLE `users_basket` ADD FOREIGN KEY ( `pos_id` ) REFERENCES `doc_base` (`id`) ON DELETE CASCADE ON UPDATE CASCADE ;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (656);


