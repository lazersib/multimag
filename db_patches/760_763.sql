INSERT INTO `users_objects` (`object`,`desc`,`actions`) VALUES ('report_salary', 'Отчет по расчётным вознаграждениям', 'view');
INSERT INTO `users_objects` (`object`,`desc`,`actions`) VALUES ('report_salaryok', 'Отчет по начисленным вознаграждениям', 'view');

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (763);


