ALTER TABLE `firm_info` ADD `rrp` INT(11) NOT NULL DEFAULT 0;

ALTER TABLE `doc_base_cost` ADD `rrp_firm_id` INT(11) NULL DEFAULT NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (562);


