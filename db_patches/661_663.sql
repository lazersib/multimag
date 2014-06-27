ALTER TABLE `doc_agent`
	CHANGE `gruzopol` `real_address` VARCHAR( 256 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	ADD `price_id` INT NULL ,
	ADD `no_bulk_prices` TINYINT NOT NULL ,
	ADD `no_retail_prices` TINYINT NOT NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (663);


