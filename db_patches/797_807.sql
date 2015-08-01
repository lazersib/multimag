UPDATE `users_objects` SET `object`='report_sales_ext', `desc`='Отчёт по движению товара (расширенный)' WHERE `object`='report_sales_china';


TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (807);


