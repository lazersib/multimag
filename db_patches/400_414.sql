CREATE TABLE `async_workers_tasks` (
`id` INT NOT NULL AUTO_INCREMENT,
`task` VARCHAR(32) NOT NULL,
`description` VARCHAR(128) NOT NULL,
`needrun` TINYINT DEFAULT 1 NOT NULL,
`textstatus` VARCHAR(128) NOT NULL,
UNIQUE (`id`),
INDEX (`needrun`)
) ENGINE=innodb CHARSET=utf8;


TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (414);


