ALTER TABLE `doc_base_dop` ADD `transit` INT NOT NULL ,
    ADD `reserve` INT NOT NULL ,
    ADD `offer` INT NOT NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (770);


