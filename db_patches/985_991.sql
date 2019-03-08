ALTER TABLE `counter` CHANGE `session_id` `user_id` INT NOT NULL

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (991);
