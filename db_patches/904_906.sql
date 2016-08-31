CREATE TABLE IF NOT EXISTS `asterisk_context` (
`id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL COMMENT 'Наименование',
  `direction` varchar(4) NOT NULL COMMENT 'Направление',
  `group_name` varchar(32) NOT NULL COMMENT 'Имя группы'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `asterisk_context` ADD PRIMARY KEY (`id`);
ALTER TABLE `asterisk_context` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (906);
