ALTER TABLE `doc_cost` ADD `context` VARCHAR( 8 ) NOT NULL COMMENT 'Контекст цены определяет места её использования';
ALTER TABLE `doc_cost` ADD `priority` TINYINT NOT NULL COMMENT 'Приоритет задаёт очерёдность цен с одним контекстом' AFTER `context`;
ALTER TABLE `doc_cost` ADD `bulk_threshold` INT NOT NULL COMMENT 'Порог включения цены по сумме заказа';
ALTER TABLE `doc_cost` ADD `acc_threshold` INT NOT NULL COMMENT 'Порог включения цены по накопленной сумме';
ALTER TABLE `doc_agent` ADD `avg_sum` INT NOT NULL COMMENT 'Средняя сумма оборотов агента за период';

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (640);


