INSERT INTO `users_objects` (`object`,`desc`,`actions`) VALUES ('report_cons', 'Сводный отчёт', 'view');
INSERT INTO `users_objects` (`object`,`desc`,`actions`) VALUES ('report_buy_book', 'Книга покупок', 'view');
INSERT INTO `users_objects` (`object`,`desc`,`actions`) VALUES ('report_sell_book', 'Книга продаж', 'view');
ALTER TABLE `doc_dtypes` ADD `r_flag` TINYINT NOT NULL ;
ALTER TABLE `doc_dtypes` CHANGE `name` `name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (781);


