
CREATE TABLE `cash_register` (
  `id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `connect_line` varchar(64) NOT NULL,
  `password` varchar(16) NOT NULL,
  `section` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `cash_register`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `cash_register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `doc_kassa`
 ADD `cash_register_id` INT NULL AFTER `firm_id`,
 ADD INDEX (`cash_register_id`);

ALTER TABLE `doc_kassa` 
    ADD CONSTRAINT `doc_kassa|cash_register` FOREIGN KEY (`cash_register_id`) 
    REFERENCES `cash_register`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `users_worker_info` ADD `cr_password` INT NOT NULL AFTER `worker_post_name`;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (972);
