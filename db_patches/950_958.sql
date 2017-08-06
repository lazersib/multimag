CREATE TABLE IF NOT EXISTS `qa` (
`id` int(11) NOT NULL,
  `question` varchar(256) NOT NULL,
  `qu_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `answer` text NOT NULL,
  `au_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Вопрос - ответ';


ALTER TABLE `qa`
 ADD PRIMARY KEY (`id`), ADD KEY `qu_id` (`qu_id`);

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (958);
