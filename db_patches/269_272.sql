SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

ALTER TABLE `default_schema`.`counter` COLLATE = utf8_general_ci ;

ALTER TABLE `default_schema`.`loginfo` COLLATE = utf8_general_ci ;

ALTER TABLE `default_schema`.`parsed_price` ADD COLUMN `selected` TINYINT(4) NULL DEFAULT NULL  AFTER `from` ;

ALTER TABLE `default_schema`.`db_version` COLLATE = utf8_general_ci ;

ALTER TABLE `default_schema`.`doc_base_gparams` COLLATE = utf8_general_ci ;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

START TRANSACTION;
TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (272);

COMMIT;