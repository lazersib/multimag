SET UNIQUE_CHECKS=0;
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='TRADITIONAL';

INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES
(48, 'report_dolgi', 'Отчёт по задолженностям', 'view'),
(49, 'report_pos_nosells', 'Отчёт по номенклатуре без продаж', 'view'),
(50, 'report_store', 'Остатки на складе', 'view'),
(51, 'report_payments', 'Отчёт по проплатам', 'view'),
(52, 'report_agent_nosells', 'Отчёт по агентам без продаж', 'view'),
(53, 'report_agent', 'Отчёт по агенту', 'view'),
(54, 'report_ostatkinadatu', 'Отчёт по остаткам на складе на выбранную дату', 'view'),
(55, 'report_cons_finance', 'Сводный финансовый', 'view'),
(56, 'report_images', 'Отчёт по изображениям складских наименований', 'view'),
(57, 'report_sales', 'Отчёт по движению товара', 'view'),
(58, 'report_pricetags', 'Ценники', 'view'),
(59, 'report_komplekt_zp', 'Отчёт по комплектующим с зарплатой', 'view'),
(60, 'report_bankday', 'Отчёт по банку', 'view'),
(62, 'report_costs', 'Отчёт по ценам', 'view'),
(63, 'report_revision_act', 'Акт сверки', 'view');

START TRANSACTION;
TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (349);

COMMIT;
