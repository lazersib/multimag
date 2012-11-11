ALTER TABLE `doc_group` ADD `meta_description` VARCHAR(256) NOT NULL, ADD `meta_keywords` VARCHAR(128) NOT NULL, ADD `title_tag` VARCHAR(128) NOT NULL;
ALTER TABLE `doc_base` ADD `meta_description` VARCHAR(256) NOT NULL, ADD `meta_keywords` VARCHAR(128) NOT NULL, ADD `title_tag` VARCHAR(128) NOT NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (438);


