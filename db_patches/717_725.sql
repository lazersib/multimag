ALTER TABLE `doc_sklady` ADD `firm_id` INT NULL DEFAULT NULL ,
ADD INDEX ( `firm_id` );
ALTER TABLE `doc_sklady` ENGINE = InnoDB;
ALTER TABLE `doc_sklady` ADD FOREIGN KEY ( `firm_id` ) REFERENCES `doc_vars` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION ;
ALTER TABLE `doc_vars` ADD `firm_store_lock` SMALLINT NOT NULL COMMENT 'Работать только со своими складами';
ALTER TABLE `doc_vars` ADD `firm_bank_lock` SMALLINT NOT NULL COMMENT 'Работать только со своими банками';
ALTER TABLE `doc_vars` ADD `firm_till_lock` SMALLINT NOT NULL COMMENT 'Работать только со своими кассами';

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (725);


