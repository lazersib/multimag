ALTER TABLE `errorlog` ADD `code` INT NOT NULL AFTER `referer`,
    ADD `class` VARCHAR( 64 ) NOT NULL AFTER `referer`,
    ADD `file` VARCHAR( 128 ) NOT NULL AFTER `msg`,
    ADD `line` INT NOT NULL AFTER `file`,
    CHANGE `uid` `uid` INT NULL,
    ADD `trace` TEXT NOT NULL AFTER `line`,
    CHANGE `agent` `useragent` VARCHAR( 256 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;


TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (715);


