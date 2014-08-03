CREATE TABLE IF NOT EXISTS `doc_base_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pos1_id` int(11) NOT NULL,
  `pos2_id` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uni` (`pos1_id`,`pos2_id`),
  KEY `pos1_id` (`pos1_id`),
  KEY `pos2_id` (`pos2_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Связи товаров';

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (676);


