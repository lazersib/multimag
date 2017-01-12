ALTER TABLE `prices_delivery` ADD `price_id` INT NOT NULL DEFAULT '1' AFTER `use_zip`, ADD INDEX (`price_id`) ;
ALTER TABLE `prices_delivery` ADD FOREIGN KEY (`price_id`) REFERENCES `nskps`.`doc_cost`(`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (935);
