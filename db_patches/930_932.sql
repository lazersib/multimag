
CREATE TABLE IF NOT EXISTS `prices_delivery` (
`id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `period` varchar(8) NOT NULL,
  `format` varchar(8) NOT NULL,
  `use_zip` int(11) NOT NULL,
  `filters` text NOT NULL,
  `lettertext` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `prices_delivery`
 ADD PRIMARY KEY (`id`);

ALTER TABLE `prices_delivery`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `prices_delivery_contact` (
`id` int(11) NOT NULL,
  `prices_delivery_id` int(11) NOT NULL,
  `agent_contacts_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `prices_delivery_contact`
 ADD PRIMARY KEY (`id`), ADD KEY `aci` (`agent_contacts_id`), ADD KEY `pdi` (`prices_delivery_id`);

ALTER TABLE `prices_delivery_contact`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (932);
