CREATE TABLE `fabric_builders` (
`id` INT NOT NULL AUTO_INCREMENT,
`name` VARCHAR(32) NOT NULL,
`active` TINYINT NOT NULL,
UNIQUE (`id`)
) ENGINE=innodb CHARSET=utf8;

CREATE TABLE `fabric_data` (
`id` INT NOT NULL AUTO_INCREMENT ,
`sklad_id` INT NOT NULL ,
`builder_id` INT NOT NULL ,
`date` DATE NOT NULL ,
`pos_id` INT NOT NULL ,
`cnt` INT NOT NULL ,
UNIQUE ( `id` )
) ENGINE = InnoDB CHARSET=utf8;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (410);


