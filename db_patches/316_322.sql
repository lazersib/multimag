SET UNIQUE_CHECKS=0;
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='TRADITIONAL';

CREATE TABLE `attachments` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`original_filename` VARCHAR( 64 ) NOT NULL ,
`comment` VARCHAR( 256 ) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET=utf8 COMMENT = 'Прикреплённые файлы';

CREATE TABLE IF NOT EXISTS `doc_base_attachments` (
  `pos_id` int(11) NOT NULL,
  `attachment_id` int(11) NOT NULL,
  UNIQUE KEY `uni` (`pos_id`,`attachment_id`),
  KEY `attachment_id` (`attachment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Прикреплённые файлы';

ALTER TABLE `doc_base_attachments`
  ADD CONSTRAINT `doc_base_attachments_ibfk_2` FOREIGN KEY (`attachment_id`) REFERENCES `attachments` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `doc_base_attachments_ibfk_1` FOREIGN KEY (`pos_id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

START TRANSACTION;
TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (322);

COMMIT;
