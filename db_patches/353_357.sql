
RENAME TABLE `wiki` TO `articles`;
ALTER TABLE `articles` ENGINE = InnoDB;
ALTER TABLE `articles` ADD `type` INT NOT NULL FIRST;
ALTER TABLE `articles` CHANGE `name` `name` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `articles` CHANGE `changeautor` `changeautor` INT( 11 ) NULL DEFAULT NULL;
ALTER TABLE `articles` ADD FOREIGN KEY ( `autor` ) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE `articles` ADD FOREIGN KEY ( `changeautor` ) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

START TRANSACTION;
TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (357);

COMMIT;
