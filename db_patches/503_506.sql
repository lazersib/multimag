ALTER TABLE `log_call_requests` CHANGE `phone` `phone` VARCHAR( 32 ) NOT NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (506);


