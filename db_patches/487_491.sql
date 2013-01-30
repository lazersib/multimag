CREATE TABLE IF NOT EXISTS `doc_list_sn` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pos_id` int(11) NOT NULL COMMENT 'ID товара',
  `num` varchar(64) NOT NULL COMMENT 'Серийный номер',
  `prix_list_pos` int(11) NOT NULL COMMENT 'Строка поступления',
  `rasx_list_pos` int(11) DEFAULT NULL COMMENT 'Строка реализации',
  UNIQUE KEY `id` (`id`),
  KEY `pos_id` (`pos_id`),
  KEY `num` (`num`),
  KEY `prix_list_pos` (`prix_list_pos`),
  KEY `rasx_list_pos` (`rasx_list_pos`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Серийные номера';


ALTER TABLE `doc_list_sn`
  ADD CONSTRAINT IF NOT EXISTS `doc_list_sn_ibfk_1` FOREIGN KEY (`pos_id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT IF NOT EXISTS `doc_list_sn_ibfk_3` FOREIGN KEY (`rasx_list_pos`) REFERENCES `doc_list_pos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT IF NOT EXISTS `doc_list_sn_ibfk_4` FOREIGN KEY (`prix_list_pos`) REFERENCES `doc_list_pos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;


TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (491);


