ALTER TABLE `users_worker_info` ADD `worker_post_name` VARCHAR( 64 ) NOT NULL COMMENT 'Должность';

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (620);


