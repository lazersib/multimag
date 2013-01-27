INSERT INTO `users_objects` (`object`,`desc`,`actions`)VALUES ('log_call_request', 'Журнал запрошенных звонков', 'view,edit');

CREATE TABLE `multimag`.`log_call_requests` (`id` INT NOT NULL AUTO_INCREMENT ,
`name` VARCHAR( 64 ) NOT NULL ,
`phone` INT( 32 ) NOT NULL ,
`request_date` DATETIME NOT NULL ,
`call_date` VARCHAR( 32 ) NOT NULL ,
`ip` VARCHAR( 32 ) NOT NULL ,
PRIMARY KEY ( `id` ) ,
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (487);


