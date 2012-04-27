SET UNIQUE_CHECKS=0;
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='TRADITIONAL';

INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES
(64, 'report_profitability', 'Отчёт по рентабельности', 'view');

START TRANSACTION;
TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (353);

COMMIT;
