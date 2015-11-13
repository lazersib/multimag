ALTER TABLE `doc_agent` ADD `region` INT NULL ; 
ALTER TABLE `doc_agent` ADD INDEX(`region`);
ALTER TABLE `doc_agent` ADD FOREIGN KEY (`region`) REFERENCES `delivery_regions`(`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (851);
