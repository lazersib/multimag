CREATE TABLE IF NOT EXISTS `intkb` (
  `type` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `date` datetime NOT NULL,
  `autor` int(11) NOT NULL,
  `changed` datetime NOT NULL,
  `changeautor` int(11) DEFAULT NULL,
  `text` text NOT NULL,
  `img_ext` varchar(4) NOT NULL,
  UNIQUE KEY `name` (`name`),
  KEY `date` (`date`),
  KEY `autor` (`autor`),
  KEY `changed` (`changed`),
  KEY `changeautor` (`changeautor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `intkb`
  ADD CONSTRAINT `intkb_ibfk_1` FOREIGN KEY (`autor`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `intkb_ibfk_2` FOREIGN KEY (`changeautor`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

INSERT INTO `users_objects` (`object`,`desc`,`actions`) VALUES ('generic_intkb', 'Внутренняя база знаний', 'view,create,edit,delete');

ALTER TABLE `doc_types` CHANGE `name` `name` VARCHAR( 48 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
INSERT INTO `doc_types` (`id`, `name`) VALUES ('22', 'Приходный кассовый ордер (оперативный)'); 
INSERT INTO `users_objects` (`object`,`desc`,`actions`) VALUES ('doc_pko_oper', 'Приходный кассовый ордер (оперативный)', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel,today_apply,printna');

INSERT INTO `users_objects` (`object`,`desc`,`actions`) VALUES ('report_sk_coeff', 'Отчет по коэффициентам сложности работы кладовщиков', 'view');

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (767);


