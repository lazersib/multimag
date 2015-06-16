INSERT IGNORE INTO `users_objects` (`object`,`desc`,`actions`) VALUES ('report_dc_book', 'Книга учета доходов и расходов', 'view');

ALTER TABLE `doc_types` CHANGE `name` `name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
UPDATE `doc_types` SET `name`="Приходный кассовый ордер" WHERE `id`=6;
UPDATE `doc_types` SET `name`="Расходный кассовый ордер" WHERE `id`=7;

ALTER TABLE `doc_dtypes` CHANGE `name` `name` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `doc_ctypes` CHANGE `name` `name` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (782);


