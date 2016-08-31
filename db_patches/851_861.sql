ALTER TABLE `doc_vars` ADD `pricecoeff` DECIMAL(6,3) NOT NULL AFTER `param_nds`;
ALTER TABLE `doc_vars` ADD `no_retailprices` TINYINT NOT NULL AFTER `param_nds`;
ALTER TABLE `doc_kassa` ADD `comment` TEXT NOT NULL ;

ALTER TABLE `doc_sklady` ADD PRIMARY KEY(`id`);

CREATE TABLE IF NOT EXISTS `sites` (
`id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `email` varchar(32) NOT NULL,
  `jid` varchar(32) NOT NULL,
  `short_name` varchar(16) NOT NULL,
  `display_name` varchar(64) NOT NULL,
  `default_firm_id` int(11) NOT NULL,
  `default_bank_id` int(11) NOT NULL,
  `default_cash_id` int(11) NOT NULL,
  `default_agent_id` int(11) NOT NULL,
  `default_store_id` int(11) NOT NULL,
  `default_site` tinyint(4) NOT NULL,
  `site_store_id` INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `sites`
 ADD PRIMARY KEY (`id`),
 ADD UNIQUE KEY `id` (`id`),
 ADD KEY `name` (`name`),
 ADD KEY `default_firm_id` (`default_firm_id`),
 ADD KEY `default_bank_id` (`default_bank_id`),
 ADD KEY `default_agent_id` (`default_agent_id`),
 ADD KEY `default_store_id` (`default_store_id`),
 ADD KEY `default_site` (`default_site`);

ALTER TABLE `sites`
ADD CONSTRAINT `sites_ibfk_3` FOREIGN KEY (`default_store_id`) REFERENCES `doc_sklady` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `sites_ibfk_1` FOREIGN KEY (`default_firm_id`) REFERENCES `doc_vars` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
ADD CONSTRAINT `sites_ibfk_2` FOREIGN KEY (`default_agent_id`) REFERENCES `doc_agent` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (861);
