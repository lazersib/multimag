CREATE TABLE IF NOT EXISTS `delivery_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `min_price` int NOT NULL,
  `service_id`  int NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `delivery_regions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_type` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `price` int NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (556);


