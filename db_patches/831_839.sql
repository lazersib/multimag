
ALTER TABLE `doc_vars` ADD `firm_type` VARCHAR(4) NOT NULL AFTER `id`;
ALTER TABLE `doc_vars`  ADD `firm_regnum` VARCHAR(16) NOT NULL;
ALTER TABLE `doc_vars` ADD `firm_regdate` DATE NOT NULL ;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (839);
