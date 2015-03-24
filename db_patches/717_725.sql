ALTER TABLE `doc_kassa`  CHANGE `firm_id` `firm_id` INT NULL DEFAULT NULL;
ALTER TABLE `doc_sklady` ENGINE = InnoDB;
ALTER TABLE `doc_vars` ENGINE = InnoDB;
ALTER TABLE `doc_sklady` ADD FOREIGN KEY ( `firm_id` ) REFERENCES `doc_vars` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION ;
ALTER TABLE `doc_vars` ADD `firm_bank_lock` SMALLINT NOT NULL COMMENT 'Работать только со своими банками';
ALTER TABLE `doc_vars` ADD `firm_till_lock` SMALLINT NOT NULL COMMENT 'Работать только со своими кассами';
UPDATE `doc_kassa` SET `firm_id`=null WHERE `firm_id`=0;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (725);


