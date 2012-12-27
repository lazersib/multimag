ALTER TABLE `doc_base` ADD `transit_cnt` INT(11) NOT NULL DEFAULT 0, ADD INDEX (`transit_cnt`);
ALTER TABLE `doc_base_dop` DROP `tranzit`;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (467);


