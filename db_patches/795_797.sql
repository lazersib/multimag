INSERT IGNORE INTO `users_objects` (`object`,`desc`,`actions`) VALUES ('report_sales_china', 'Отчёт по движению товара (китайский)', 'view');

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (797);


