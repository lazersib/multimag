ALTER TABLE `users_groups_acl` CHANGE `gid` `gid` INT(11) NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (911);
