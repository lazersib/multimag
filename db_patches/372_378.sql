SET CHARACTER SET UTF8;

ALTER TABLE `doc_base_params` ADD `ym_assign` VARCHAR( 128 ) NOT NULL,
ADD INDEX ( `ym_assign` );

ALTER TABLE `doc_base_values` ADD `intval` INT NOT NULL,
ADD `doubleval` DOUBLE NOT NULL,
ADD `strval` VARCHAR( 512 ) NOT NULL ,
ADD INDEX ( `intval` ), ADD INDEX ( `doubleval` ), ADD INDEX ( `strval` );

CREATE TABLE `doc_base_pcollections_list` (
`id` INT NOT NULL AUTO_INCREMENT,
`name` VARCHAR(128) NOT NULL,
PRIMARY KEY (`id`) ,
UNIQUE (`name`)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT = 'Наборы свойств складской номенклатуры';

CREATE TABLE `multimag`.`doc_base_pcollections_set` (
`collection_id` INT NOT NULL ,
`param_id` INT NOT NULL,
INDEX (`collection_id`),
INDEX (`param_id`),
UNIQUE `uniq` (`collection_id`, `param_id`)
) ENGINE = InnoDB COMMENT = 'Список параметров в наборе';

 ALTER TABLE `multimag`.`doc_group_params` ADD UNIQUE `uniq` ( `group_id` , `param_id` ) ;

ALTER TABLE `doc_base_pcollections_set` ADD FOREIGN KEY ( `collection_id` ) REFERENCES `doc_base_pcollections_list` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION ;
ALTER TABLE `doc_base_pcollections_set` ADD FOREIGN KEY ( `param_id` ) REFERENCES `doc_base_params` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION ;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (378);


