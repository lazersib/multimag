ALTER TABLE `users_worker_info`
    ADD `worker_int_fix_phone` VARCHAR(16) NOT NULL AFTER `worker_phone`,
    ADD `worker_mobile_phone` VARCHAR(16) NOT NULL AFTER `worker_int_fix_phone`,
    ADD `worker_int_mobile_phone` VARCHAR(16) NOT NULL AFTER `worker_mobile_phone`,
    ADD INDEX (`worker_int_fix_phone`),
    ADD INDEX (`worker_mobile_phone`),
    ADD INDEX (`worker_int_mobile_phone`);


TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (929);
