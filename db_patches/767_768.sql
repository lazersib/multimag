ALTER TABLE `doc_list` CHANGE `p_doc` `p_doc` INT( 11 ) NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (768);


