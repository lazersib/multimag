ALTER TABLE `doc_base_params` CHANGE `pgroup_id` `pgroup_id` INT( 11 ) NULL DEFAULT NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (750);


