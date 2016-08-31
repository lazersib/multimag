DROP TABLE `users_acl`;

CREATE TABLE `users_acl` (
`id` int(11) NOT NULL  AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `object` varchar(64) NOT NULL,
  `value` int(11) NOT NULL,
 UNIQUE KEY `id` (`id`), 
 UNIQUE KEY `uni` (`uid`,`object`), 
KEY `uid` (`uid`), 
KEY `object` (`object`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DELETE FROM `users_grouplist` WHERE `id`=0;

DROP TABLE `users_groups_acl`;
CREATE TABLE `users_groups_acl` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `gid` int(11) NOT NULL,
  `object` varchar(64) NOT NULL,
  `value` int(11) NOT NULL,
 UNIQUE KEY `id` (`id`),
 UNIQUE KEY `uni` (`gid`,`object`),
 KEY `gid` (`gid`),
 KEY `object` (`object`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Привилегии групп';


DELETE FROM `users_in_group` WHERE `uid`=0 OR `gid` = 0;


TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (846);
