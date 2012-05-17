SET UNIQUE_CHECKS=0;
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='TRADITIONAL';

ALTER TABLE `doc_base_params` ADD `system` TINYINT NOT NULL COMMENT 'Служебный параметр. Нигде не отображается.';
UPDATE `doc_base_params` SET `system`='1' WHERE `param`='ZP';

START TRANSACTION;
TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (333);

COMMIT;
