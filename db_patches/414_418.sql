ALTER TABLE `users` CHANGE `name` `name` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `users` CHANGE `pass` `pass` VARCHAR(192) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `users` ADD `pass_type`  VARCHAR(8) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL COMMENT 'тип хэша' AFTER `pass`;
ALTER TABLE `users` CHANGE `passch` `pass_change` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `users` CHANGE `email` `reg_email` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `users` CHANGE `confirm` `reg_email_confirm` VARCHAR(16) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `reg_email`;
ALTER TABLE `users` CHANGE `tel` `reg_phone` VARCHAR(16) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `reg_email_confirm`;
ALTER TABLE `users` CHANGE `subscribe` `reg_email_subscribe` TINYINT NOT NULL AFTER `reg_email_confirm`;
ALTER TABLE `users` ADD `reg_phone_confirm` VARCHAR(8) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `reg_phone`;
ALTER TABLE `users` ADD `reg_phone_subscribe` TINYINT NOT NULL AFTER `reg_phone`;
ALTER TABLE `users` ADD `pass_date_change` DATETIME NOT NULL AFTER `pass_change`;
ALTER TABLE `users` ADD `pass_expired` TINYINT NOT NULL DEFAULT 0 AFTER `pass_change`;
ALTER TABLE `users` CHANGE `date_reg` `reg_date` DATETIME NOT NULL AFTER `reg_phone_confirm`;
ALTER TABLE `users` ADD `disabled` TINYINT NOT NULL DEFAULT 0 AFTER `reg_date`;
ALTER TABLE `users` ADD `disabled_reason` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `disabled`;
ALTER TABLE `users` ADD `bifact_auth` TINYINT NOT NULL DEFAULT 0 AFTER `disabled_reason`;
ALTER TABLE `users` CHANGE `rname` `real_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `bifact_auth`;
ALTER TABLE `users` CHANGE `adres` `real_address` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `real_name`;
ALTER TABLE `users` ADD `type`  VARCHAR(4) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL COMMENT 'физ/юр';
ALTER TABLE `users` ADD `agent_id` INT NULL;

ALTER TABLE `users` ADD INDEX (`name`), ADD INDEX (`reg_email`), ADD INDEX (`reg_email_confirm`), ADD INDEX (`reg_phone`), ADD INDEX (`reg_phone_confirm`), ADD INDEX (`pass_date_change`), ADD INDEX (`pass_expired`), ADD INDEX (`disabled`), ADD INDEX (`reg_email_subscribe`), ADD INDEX (`reg_phone_subscribe`), ADD INDEX (`jid`), ADD INDEX (`agent_id`); 

ALTER TABLE `users` ADD FOREIGN KEY (`agent_id`) REFERENCES `doc_agent` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION ;

UPDATE `users` SET `reg_email_confirm`='1' WHERE `reg_email_confirm`='0';
UPDATE `users` SET `pass_date_change`=NOW();

CREATE TABLE `users_openid` (
`user_id` INT NOT NULL ,
`openid_identify` VARCHAR(192) NOT NULL ,
`openid_type` INT( 16 ) NOT NULL,
INDEX (`user_id`),
UNIQUE (`openid_identify`)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT = 'Привязка к openid';
ALTER TABLE `users_openid` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE ;

CREATE TABLE `users_worker_info` (
`user_id` INT NOT NULL ,
`worker` TINYINT NOT NULL,
`worker_email` VARCHAR( 64 ) NOT NULL ,
`worker_phone` VARCHAR( 16 ) NOT NULL ,
`worker_jid` VARCHAR( 32 ) NOT NULL ,
`worker_real_name` VARCHAR( 64 ) NOT NULL ,
`worker_real_address` VARCHAR( 256 ) NOT NULL ,
UNIQUE (`user_id`),
INDEX (`worker_email`),
INDEX (`worker_phone`),
INDEX (`worker_jid`)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `users_worker_info` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE ;

INSERT INTO `users_worker_info` (`user_id`, `worker`, `worker_real_name`, `worker_email`, `worker_phone`, `worker_real_address`, `worker_jid`)
SELECT `id`, `worker`, `real_name`, `reg_email`, `reg_phone`, `real_address`, `jid` FROM `users` WHERE `worker`='1';

ALTER TABLE `users` DROP `worker`;

CREATE TABLE `users_login_history` (
`id` INT NOT NULL AUTO_INCREMENT,
`user_id` INT NOT NULL ,
`date` DATETIME NOT NULL,
`ip` VARCHAR(32) NOT NULL ,
`useragent` VARCHAR(128) NOT NULL ,
`method` VARCHAR(8) NOT NULL ,
UNIQUE (`id`)
) ENGINE = myisam CHARACTER SET utf8 COLLATE utf8_general_ci;

INSERT INTO `users_login_history` (`user_id`, `date`) SELECT `id`, `lastlogin` FROM `users`;
ALTER TABLE `users` DROP `lastlogin`;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (418);


