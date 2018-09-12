ALTER TABLE `prices_delivery` CHANGE `filters` `options` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (985);
