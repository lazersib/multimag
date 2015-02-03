INSERT INTO `users_objects` (`object`,`desc`,`actions`) VALUES ('report_agent_resp', 'Отчет по агентам ответственного сотрудника', 'view');
UPDATE `users_objects` SET `actions`=CONCAT(`actions`, ',printna') WHERE `object` LIKE 'doc_%' AND `actions` LIKE '%cancel%' AND `actions` NOT LIKE '%printna%';

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (744);


