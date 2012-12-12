ALTER TABLE `doc_base` ADD `create_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, ADD `buy_time` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00', ADD INDEX ( `create_time` ), ADD INDEX ( `buy_time`);
UPDATE `doc_dopdata` SET `value`='bank' WHERE `param`='pay_type' AND `value`='bn';
UPDATE `doc_dopdata` SET `value`='cash' WHERE `param`='pay_type' AND `value`='nal';
UPDATE `doc_dopdata` SET `value`='card_t' WHERE `param`='pay_type' AND `value`='card';


TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (456);


