CREATE TABLE IF NOT EXISTS `doc_base_types` (
`id` int(11) NOT NULL,
  `account` varchar(8) NOT NULL,
  `name` varchar(32) NOT NULL,
  `service` tinyint(1) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

INSERT INTO `doc_base_types` (`id`, `account`, `name`, `service`) VALUES
(1, '41', 'Товар', 0),
(2, '44', 'Услуга', 1),
(3, '10', 'Расходный материал', 0);

ALTER TABLE `doc_base_types`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `doc_base` ADD `type_id` INT NULL  DEFAULT NULL AFTER `group`, ADD INDEX (`type_id`) ;

ALTER TABLE `doc_base` ADD FOREIGN KEY (`type_id`) REFERENCES `doc_base_types`(`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

REPLACE `doc_dopdata` (`doc`,`param`,`value`)
    SELECT `doc`, 'sf_num', `value` FROM `doc_dopdata` WHERE `param`='input_doc' AND `value`!='';

REPLACE `doc_dopdata` (`doc`,`param`,`value`)
    SELECT `doc`, 'sf_date', `value` FROM `doc_dopdata` WHERE `param`='input_date' AND `value`!='';

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (942);
