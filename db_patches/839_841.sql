
ALTER TABLE `doc_base_cnt` ADD `revision_date` DATE NOT NULL COMMENT 'Дата ревизии' , ADD INDEX (`revision_date`) ;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (841);
