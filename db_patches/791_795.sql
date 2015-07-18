CREATE TABLE IF NOT EXISTS `users_oauth` (
`id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `client_id` varchar(256) NOT NULL,
  `client_login` varchar(128) CHARACTER SET utf8 NOT NULL,
  `server` varchar(16) CHARACTER SET utf8 NOT NULL,
  `access_token` varchar(256) CHARACTER SET utf8 NOT NULL,
  `expire` datetime NOT NULL,
  `creation` datetime NOT NULL,
  `access_token_secret` varchar(256) CHARACTER SET utf8 NOT NULL,
  `refresh_token` varchar(256) CHARACTER SET utf8 NOT NULL,
  `access_token_response` varchar(256) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `users_oauth`
 ADD PRIMARY KEY (`id`),
 ADD KEY `user_id` (`user_id`),
 ADD KEY `server` (`server`), 
 ADD KEY `server_id` (`client_id`), 
 ADD KEY `server_login` (`client_login`),
 MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
 ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (795);


