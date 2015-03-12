SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `mail`
--

CREATE TABLE IF NOT EXISTS `virtual_aliases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alias_prefix` varchar(32) NOT NULL,
  `domain_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `domain_id` (`domain_id`),
  KEY `user_id` (`user_id`),
  KEY `alias_prefix` (`alias_prefix`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `virtual_domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `virtual_aliases`
--
ALTER TABLE `virtual_aliases`
  ADD CONSTRAINT `virtual_aliases_user_id` FOREIGN KEY (`user_id`) REFERENCES `multimag`.`users` (`id`),
  ADD CONSTRAINT `virtual_aliases_ibfk_2` FOREIGN KEY (`domain_id`) REFERENCES `virtual_domains` (`id`);
--
-- Структура таблицы `virtual_domains`
--


--
-- Структура для представления `view_aliases`
--
DROP VIEW IF EXISTS `view_aliases`;
DROP VIEW IF EXISTS `view_users`;
DROP VIEW IF EXISTS `view_users_auth`;

CREATE VIEW `view_first_domain` AS
	SELECT `id`, `name` FROM `virtual_domains` ORDER BY `id` LIMIT 1;

CREATE VIEW `view_users_auth` AS 
	SELECT `mu`.`id` AS `id`, LOWER(`mu`.`name`) AS `user`, CONCAT('{', `mu`.`pass_type`, '}',`mu`.`pass`) AS `password`
		FROM `multimag`.`users` AS `mu`
		INNER JOIN `multimag`. `users_worker_info` AS `mwi` ON `mwi`.`user_id` = `mu`.`id`
			AND `mwi`.`worker` = 1
		WHERE `mu`.`pass_type` != ''
	UNION
	SELECT `mu`.`id` AS `id`, LOWER(`mu`.`name`) AS `user`, CONCAT('{MD5}',`mu`.`pass`) AS `password`
		FROM `multimag`.`users` AS `mu`
		INNER JOIN `multimag`. `users_worker_info` AS `mwi` ON `mwi`.`user_id` = `mu`.`id`
			AND `mwi`.`worker` = 1
		WHERE `mu`.`pass_type` = '';
		
		
CREATE VIEW `view_aliases` AS 
	SELECT CONCAT('all@', `vd`.`name`) AS `email`, CONCAT(LOWER(`mu`.`name`), '@', `vf`.`name`) AS `destination`, LOWER(`mu`.`name`) AS `user`
		FROM `mail`.`virtual_domains` AS `vd`, `mail`.`view_first_domain` AS `vf`, `multimag`.`users` AS `mu`
		INNER JOIN `multimag`.`users_worker_info` AS `uwi`
			ON `uwi`.`user_id` = `mu`.`id` AND `uwi`.`worker` = 1
	UNION
	SELECT CONCAT(LOWER(`mu`.`name`), '@', `vd`.`name`) AS `email`, CONCAT(LOWER(`mu`.`name`), '@', `vf`.`name`) AS `destination`, LOWER(`mu`.`name`) AS `user`
		FROM `mail`.`virtual_domains` AS `vd`, `mail`.`view_first_domain` AS `vf`, `multimag`.`users` AS `mu`
		INNER JOIN `multimag`.`users_worker_info` AS `uwi`
			ON `uwi`.`user_id` = `mu`.`id` AND `uwi`.`worker` = 1
	UNION
	SELECT CONCAT(`va`.`alias_prefix`, '@', `vd`.`name`) AS `email`, CONCAT(LOWER(`mu`.`name`), '@', `vf`.`name`) AS `destination`, LOWER(`mu`.`name`) AS `user`
		FROM `mail`.`view_first_domain` AS `vf`, `virtual_aliases` AS `va`
		INNER JOIN `multimag`.`users` AS `mu`
			ON `mu`.`id` = `va`.`user_id`
		INNER JOIN `multimag`.`users_worker_info` AS `uwi`
			ON `va`.`user_id` = `mu`.`id` AND `uwi`.`worker` = 1
		INNER JOIN `virtual_domains` AS `vd`
			ON `vd`.`id` = `va`.`domain_id`



