ALTER TABLE `doc_base_dop_type` CHANGE `id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (622);


