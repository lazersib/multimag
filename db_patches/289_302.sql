SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

DROP TABLE `doc_units`;

CREATE TABLE class_country (
  id smallint(6) NOT NULL auto_increment COMMENT 'pk',
  `name` varchar(255) NOT NULL COMMENT 'Наименование страны',
  full_name varchar(255) default NULL COMMENT 'Полное наименование страны',
  number_code varchar(4) NOT NULL COMMENT 'Числовой код',
  alfa2 varchar(2) NOT NULL COMMENT 'Код альфа-2',
  alfa3 varchar(3) NOT NULL COMMENT 'Код альфа-3',
  visible tinyint(4) NOT NULL default '1' COMMENT 'Видимость',
  `comment` varchar(255) default NULL COMMENT 'Комментарий',
  PRIMARY KEY  (id),
  UNIQUE KEY number_code (number_code),
  UNIQUE KEY alfa2 (alfa2),
  UNIQUE KEY alfa3 (alfa3)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Общероссийский классификатор стран мира ОКСМ' AUTO_INCREMENT=249 ;


START TRANSACTION;
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(1, 'АФГАНИСТАН', 'Переходное Исламское Государство Афганистан', '004', 'AF', 'AFG', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(2, 'АЛБАНИЯ', 'Республика Албания', '008', 'AL', 'ALB', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(3, 'АНТАРКТИДА', NULL, '010', 'AQ', 'ATA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(4, 'АЛЖИР', 'Алжирская Народная Демократическая Республика', '012', 'DZ', 'DZA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(5, 'АМЕРИКАНСКОЕ САМОА', NULL, '016', 'AS', 'ASM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(6, 'АНДОРРА', 'Княжество Андорра', '020', 'AD', 'AND', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(7, 'АНГОЛА', 'Республика Ангола', '024', 'AO', 'AGO', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(8, 'АНТИГУА И БАРБУДА', NULL, '028', 'AG', 'ATG', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(9, 'АЗЕРБАЙДЖАН', 'Республика Азербайджан', '031', 'AZ', 'AZE', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(10, 'АРГЕНТИНА', 'Аргентинская Республика', '032', 'AR', 'ARG', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(11, 'АВСТРАЛИЯ', NULL, '036', 'AU', 'AUS', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(12, 'АВСТРИЯ', 'Австрийская Республика', '040', 'AT', 'AUT', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(13, 'БАГАМЫ', 'Содружество Багамы', '044', 'BS', 'BHS', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(14, 'БАХРЕЙН', 'Королевство Бахрейн', '048', 'BH', 'BHR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(15, 'БАНГЛАДЕШ', 'Народная Республика Бангладеш', '050', 'BD', 'BGD', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(16, 'АРМЕНИЯ', 'Республика Армения', '051', 'AM', 'ARM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(17, 'БАРБАДОС', NULL, '052', 'BB', 'BRB', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(18, 'БЕЛЬГИЯ', 'Королевство Бельгии', '056', 'BE', 'BEL', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(19, 'БЕРМУДЫ', NULL, '060', 'BM', 'BMU', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(20, 'БУТАН', 'Королевство Бутан', '064', 'BT', 'BTN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(21, 'БОЛИВИЯ, МНОГОНАЦИОНАЛЬНОЕ ГОСУДАРСТВО', 'Многонациональное Государство Боливия', '068', 'BO', 'BOL', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(22, 'БОСНИЯ И ГЕРЦЕГОВИНА', NULL, '070', 'BA', 'BIH', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(23, 'БОТСВАНА', 'Республика Ботсвана', '072', 'BW', 'BWA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(24, 'ОСТРОВ БУВЕ', NULL, '074', 'BV', 'BVT', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(25, 'БРАЗИЛИЯ', 'Федеративная Республика Бразилия', '076', 'BR', 'BRA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(26, 'БЕЛИЗ', NULL, '084', 'BZ', 'BLZ', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(27, 'БРИТАНСКАЯ ТЕРРИТОРИЯ В ИНДИЙСКОМ ОКЕАНЕ', NULL, '086', 'IO', 'IOT', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(28, 'СОЛОМОНОВЫ ОСТРОВА', NULL, '090', 'SB', 'SLB', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(29, 'ВИРГИНСКИЕ ОСТРОВА, БРИТАНСКИЕ', 'Британские Виргинские острова', '092', 'VG', 'VGB', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(30, 'БРУНЕЙ-ДАРУССАЛАМ', NULL, '096', 'BN', 'BRN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(31, 'БОЛГАРИЯ', 'Республика Болгария', '100', 'BG', 'BGR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(32, 'МЬЯНМА', 'Союз Мьянма', '104', 'MM', 'MMR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(33, 'БУРУНДИ', 'Республика Бурунди', '108', 'BI', 'BDI', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(34, 'БЕЛАРУСЬ', 'Республика Беларусь', '112', 'BY', 'BLR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(35, 'КАМБОДЖА', 'Королевство Камбоджа', '116', 'KH', 'KHM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(36, 'КАМЕРУН', 'Республика Камерун', '120', 'CM', 'CMR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(37, 'КАНАДА', NULL, '124', 'CA', 'CAN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(38, 'КАБО-ВЕРДЕ', 'Республика Кабо-Верде', '132', 'CV', 'CPV', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(39, 'ОСТРОВА КАЙМАН', NULL, '136', 'KY', 'CYM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(40, 'ЦЕНТРАЛЬНО-АФРИКАНСКАЯ РЕСПУБЛИКА', NULL, '140', 'CF', 'CAF', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(41, 'ШРИ-ЛАНКА', 'Демократическая Социалистическая Республика Шри-Ланка', '144', 'LK', 'LKA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(42, 'ЧАД', 'Республика Чад', '148', 'TD', 'TCD', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(43, 'ЧИЛИ', 'Республика Чили', '152', 'CL', 'CHL', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(44, 'КИТАЙ', 'Китайская Народная Республика', '156', 'CN', 'CHN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(45, 'ТАЙВАНЬ (КИТАЙ)', NULL, '158', 'TW', 'TWN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(46, 'ОСТРОВ РОЖДЕСТВА', NULL, '162', 'CX', 'CXR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(47, 'КОКОСОВЫЕ (КИЛИНГ) ОСТРОВА', NULL, '166', 'CC', 'CCK', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(48, 'КОЛУМБИЯ', 'Республика Колумбия', '170', 'CO', 'COL', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(49, 'КОМОРЫ', 'Союз Коморы', '174', 'KM', 'COM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(50, 'МАЙОТТА', NULL, '175', 'YT', 'MYT', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(51, 'КОНГО', 'Республика Конго', '178', 'CG', 'COG', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(52, 'КОНГО, ДЕМОКРАТИЧЕСКАЯ РЕСПУБЛИКА', 'Демократическая Республика Конго', '180', 'CD', 'COD', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(53, 'ОСТРОВА КУКА', NULL, '184', 'CK', 'COK', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(54, 'КОСТА-РИКА', 'Республика Коста-Рика', '188', 'CR', 'CRI', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(55, 'ХОРВАТИЯ', 'Республика Хорватия', '191', 'HR', 'HRV', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(56, 'КУБА', 'Республика Куба', '192', 'CU', 'CUB', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(57, 'КИПР', 'Республика Кипр', '196', 'CY', 'CYP', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(58, 'ЧЕШСКАЯ РЕСПУБЛИКА', NULL, '203', 'CZ', 'CZE', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(59, 'БЕНИН', 'Республика Бенин', '204', 'BJ', 'BEN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(60, 'ДАНИЯ', 'Королевство Дания', '208', 'DK', 'DNK', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(61, 'ДОМИНИКА', 'Содружество Доминики', '212', 'DM', 'DMA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(62, 'ДОМИНИКАНСКАЯ РЕСПУБЛИКА', NULL, '214', 'DO', 'DOM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(63, 'ЭКВАДОР', 'Республика Эквадор', '218', 'EC', 'ECU', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(64, 'ЭЛЬ-САЛЬВАДОР', 'Республика Эль-Сальвадор', '222', 'SV', 'SLV', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(65, 'ЭКВАТОРИАЛЬНАЯ ГВИНЕЯ', 'Республика Экваториальная Гвинея', '226', 'GQ', 'GNQ', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(66, 'ЭФИОПИЯ', 'Федеративная Демократическая Республика Эфиопия', '231', 'ET', 'ETH', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(67, 'ЭРИТРЕЯ', NULL, '232', 'ER', 'ERI', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(68, 'ЭСТОНИЯ', 'Эстонская Республика', '233', 'EE', 'EST', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(69, 'ФАРЕРСКИЕ ОСТРОВА', NULL, '234', 'FO', 'FRO', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(70, 'ФОЛКЛЕНДСКИЕ ОСТРОВА (МАЛЬВИНСКИЕ)', NULL, '238', 'FK', 'FLK', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(71, 'ЮЖНАЯ ДЖОРДЖИЯ И ЮЖНЫЕ САНДВИЧЕВЫ ОСТРОВА', NULL, '239', 'GS', 'SGS', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(72, 'ФИДЖИ', 'Республика Островов Фиджи', '242', 'FJ', 'FJI', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(73, 'ФИНЛЯНДИЯ', 'Финляндская Республика', '246', 'FI', 'FIN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(74, 'ЭЛАНДСКИЕ ОСТРОВА', NULL, '248', 'АХ', 'ALA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(75, 'ФРАНЦИЯ', 'Французская Республика', '250', 'FR', 'FRA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(76, 'ФРАНЦУЗСКАЯ ГВИАНА', NULL, '254', 'GF', 'GUF', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(77, 'ФРАНЦУЗСКАЯ ПОЛИНЕЗИЯ', NULL, '258', 'PF', 'PYF', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(78, 'ФРАНЦУЗСКИЕ ЮЖНЫЕ ТЕРРИТОРИИ', NULL, '260', 'TF', 'ATF', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(79, 'ДЖИБУТИ', 'Республика Джибути', '262', 'DJ', 'DJI', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(80, 'ГАБОН', 'Габонская Республика', '266', 'GA', 'GAB', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(81, 'ГРУЗИЯ', NULL, '268', 'GE', 'GEO', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(82, 'ГАМБИЯ', 'Республика Гамбия', '270', 'GM', 'GMB', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(83, 'ПАЛЕСТИНСКАЯ ТЕРРИТОРИЯ, ОККУПИРОВАННАЯ', 'Оккупированная Палестинская территория', '275', 'PS', 'PSE', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(84, 'ГЕРМАНИЯ', 'Федеративная Республика Германия', '276', 'DE', 'DEU', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(85, 'ГАНА', 'Республика Гана', '288', 'GH', 'GHA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(86, 'ГИБРАЛТАР', NULL, '292', 'GI', 'GIB', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(87, 'КИРИБАТИ', 'Республика Кирибати', '296', 'KI', 'KIR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(88, 'ГРЕЦИЯ', 'Греческая Республика', '300', 'GR', 'GRC', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(89, 'ГРЕНЛАНДИЯ', NULL, '304', 'GL', 'GRL', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(90, 'ГРЕНАДА', NULL, '308', 'GD', 'GRD', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(91, 'ГВАДЕЛУПА', NULL, '312', 'GP', 'GLP', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(92, 'ГУАМ', NULL, '316', 'GU', 'GUM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(93, 'ГВАТЕМАЛА', 'Республика Гватемала', '320', 'GT', 'GTM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(94, 'ГВИНЕЯ', 'Гвинейская Республика', '324', 'GN', 'GIN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(95, 'ГАЙАНА', 'Республика Гайана', '328', 'GY', 'GUY', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(96, 'ГАИТИ', 'Республика Гаити', '332', 'HT', 'HTI', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(97, 'ОСТРОВ ХЕРД И ОСТРОВА МАКДОНАЛЬД', NULL, '334', 'HM', 'HMD', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(98, 'ПАПСКИЙ ПРЕСТОЛ (ГОСУДАРСТВО - ГОРОД ВАТИКАН)', NULL, '336', 'VA', 'VAT', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(99, 'ГОНДУРАС', 'Республика Гондурас', '340', 'HN', 'HND', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(100, 'ГОНКОНГ', 'Специальный административный регион Китая Гонконг', '344', 'HK', 'HKG', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(101, 'ВЕНГРИЯ', 'Венгерская Республика', '348', 'HU', 'HUN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(102, 'ИСЛАНДИЯ', 'Республика Исландия', '352', 'IS', 'ISL', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(103, 'ИНДИЯ', 'Республика Индия', '356', 'IN', 'IND', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(104, 'ИНДОНЕЗИЯ', 'Республика Индонезия', '360', 'ID', 'IDN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(105, 'ИРАН, ИСЛАМСКАЯ РЕСПУБЛИКА', 'Исламская Республика Иран', '364', 'IR', 'IRN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(106, 'ИРАК', 'Республика Ирак', '368', 'IQ', 'IRQ', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(107, 'ИРЛАНДИЯ', NULL, '372', 'IE', 'IRL', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(108, 'ИЗРАИЛЬ', 'Государство Израиль', '376', 'IL', 'ISR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(109, 'ИТАЛИЯ', 'Итальянская Республика', '380', 'IT', 'ITA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(110, 'КОТ Д''ИВУАР', 'Республика Кот д''Ивуар', '384', 'CI', 'CIV', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(111, 'ЯМАЙКА', NULL, '388', 'JM', 'JAM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(112, 'ЯПОНИЯ', NULL, '392', 'JP', 'JPN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(113, 'КАЗАХСТАН', 'Республика Казахстан', '398', 'KZ', 'KAZ', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(114, 'ИОРДАНИЯ', 'Иорданское Хашимитское Королевство', '400', 'JO', 'JOR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(115, 'КЕНИЯ', 'Республика Кения', '404', 'KE', 'KEN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(116, 'КОРЕЯ, НАРОДНО-ДЕМОКРАТИЧЕСКАЯ РЕСПУБЛИКА', 'Корейская Народно-Демократическая Республика', '408', 'KP', 'PRK', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(117, 'КОРЕЯ, РЕСПУБЛИКА', 'Республика Корея', '410', 'KR', 'KOR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(118, 'КУВЕЙТ', 'Государство Кувейт', '414', 'KW', 'KWT', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(119, 'КИРГИЗИЯ', 'Киргизская Республика', '417', 'KG', 'KGZ', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(120, 'ЛАОССКАЯ НАРОДНО-ДЕМОКРАТИЧЕСКАЯ РЕСПУБЛИКА', NULL, '418', 'LA', 'LAO', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(121, 'ЛИВАН', 'Ливанская Республика', '422', 'LB', 'LBN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(122, 'ЛЕСОТО', 'Королевство Лесото', '426', 'LS', 'LSO', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(123, 'ЛАТВИЯ', 'Латвийская Республика', '428', 'LV', 'LVA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(124, 'ЛИБЕРИЯ', 'Республика Либерия', '430', 'LR', 'LBR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(125, 'ЛИВИЙСКАЯ АРАБСКАЯ ДЖАМАХИРИЯ', 'Социалистическая Народная Ливийская Арабская Джамахирия', '434', 'LY', 'LBY', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(126, 'ЛИХТЕНШТЕЙН', 'Княжество Лихтенштейн', '438', 'LI', 'LIE', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(127, 'ЛИТВА', 'Литовская Республика', '440', 'LT', 'LTU', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(128, 'ЛЮКСЕМБУРГ', 'Великое Герцогство Люксембург', '442', 'LU', 'LUX', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(129, 'МАКАО', 'Специальный административный регион Китая Макао', '446', 'MO', 'MAC', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(130, 'МАДАГАСКАР', 'Республика Мадагаскар', '450', 'MG', 'MDG', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(131, 'МАЛАВИ', 'Республика Малави', '454', 'MW', 'MWI', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(132, 'МАЛАЙЗИЯ', NULL, '458', 'MY', 'MYS', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(133, 'МАЛЬДИВЫ', 'Мальдивская Республика', '462', 'MV', 'MDV', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(134, 'МАЛИ', 'Республика Мали', '466', 'ML', 'MLI', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(135, 'МАЛЬТА', 'Республика Мальта', '470', 'MT', 'MLT', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(136, 'МАРТИНИКА', NULL, '474', 'MQ', 'MTQ', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(137, 'МАВРИТАНИЯ', 'Исламская Республика Мавритания', '478', 'MR', 'MRT', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(138, 'МАВРИКИЙ', 'Республика Маврикий', '480', 'MU', 'MUS', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(139, 'МЕКСИКА', 'Мексиканские Соединенные Штаты', '484', 'MX', 'MEX', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(140, 'МОНАКО', 'Княжество Монако', '492', 'MC', 'MCO', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(141, 'МОНГОЛИЯ', NULL, '496', 'MN', 'MNG', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(142, 'МОЛДОВА, РЕСПУБЛИКА', 'Республика Молдова', '498', 'MD', 'MDA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(143, 'ЧЕРНОГОРИЯ', NULL, '499', 'ME', 'MNE', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(144, 'МОНТСЕРРАТ', NULL, '500', 'MS', 'MSR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(145, 'МАРОККО', 'Королевство Марокко', '504', 'MA', 'MAR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(146, 'МОЗАМБИК', 'Республика Мозамбик', '508', 'MZ', 'MOZ', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(147, 'ОМАН', 'Султанат Оман', '512', 'OM', 'OMN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(148, 'НАМИБИЯ', 'Республика Намибия', '516', 'NA', 'NAM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(149, 'НАУРУ', 'Республика Науру', '520', 'NR', 'NRU', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(150, 'НЕПАЛ', 'Федеративная Демократическая Республика Непал', '524', 'NP', 'NPL', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(151, 'НИДЕРЛАНДЫ', 'Королевство Нидерландов', '528', 'NL', 'NLD', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(152, 'НИДЕРЛАНДСКИЕ АНТИЛЫ', NULL, '530', 'AN', 'ANT', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(153, 'АРУБА', NULL, '533', 'AW', 'ABW', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(154, 'НОВАЯ КАЛЕДОНИЯ', NULL, '540', 'NC', 'NCL', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(155, 'ВАНУАТУ', 'Республика Вануату', '548', 'VU', 'VUT', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(156, 'НОВАЯ ЗЕЛАНДИЯ', NULL, '554', 'NZ', 'NZL', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(157, 'НИКАРАГУА', 'Республика Никарагуа', '558', 'NI', 'NIC', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(158, 'НИГЕР', 'Республика Нигер', '562', 'NE', 'NER', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(159, 'НИГЕРИЯ', 'Федеративная Республика Нигерия', '566', 'NG', 'NGA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(160, 'НИУЭ', 'Республика Ниуэ', '570', 'NU', 'NIU', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(161, 'ОСТРОВ НОРФОЛК', NULL, '574', 'NF', 'NFK', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(162, 'НОРВЕГИЯ', 'Королевство Норвегия', '578', 'NO', 'NOR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(163, 'СЕВЕРНЫЕ МАРИАНСКИЕ ОСТРОВА', 'Содружество Северных Марианских островов', '580', 'MP', 'MNP', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(164, 'МАЛЫЕ ТИХООКЕАНСКИЕ ОТДАЛЕННЫЕ ОСТРОВА СОЕДИНЕННЫХ ШТАТОВ', NULL, '581', 'UM', 'UMI', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(165, 'МИКРОНЕЗИЯ, ФЕДЕРАТИВНЫЕ ШТАТЫ', 'Федеративные штаты Микронезии', '583', 'FM', 'FSM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(166, 'МАРШАЛЛОВЫ ОСТРОВА', 'Республика Маршалловы Острова', '584', 'MH', 'MHL', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(167, 'ПАЛАУ', 'Республика Палау', '585', 'PW', 'PLW', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(168, 'ПАКИСТАН', 'Исламская Республика Пакистан', '586', 'PK', 'PAK', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(169, 'ПАНАМА', 'Республика Панама', '591', 'PA', 'PAN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(170, 'ПАПУА-НОВАЯ ГВИНЕЯ', NULL, '598', 'PG', 'PNG', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(171, 'ПАРАГВАЙ', 'Республика Парагвай', '600', 'PY', 'PRY', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(172, 'ПЕРУ', 'Республика Перу', '604', 'PE', 'PER', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(173, 'ФИЛИППИНЫ', 'Республика Филиппины', '608', 'PH', 'PHL', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(174, 'ПИТКЕРН', NULL, '612', 'PN', 'PCN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(175, 'ПОЛЬША', 'Республика Польша', '616', 'PL', 'POL', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(176, 'ПОРТУГАЛИЯ', 'Португальская Республика', '620', 'PT', 'PRT', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(177, 'ГВИНЕЯ-БИСАУ', 'Республика Гвинея-Бисау', '624', 'GW', 'GNB', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(178, 'ТИМОР-ЛЕСТЕ', 'Демократическая Республика Тимор-Лесте', '626', 'TL', 'TLS', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(179, 'ПУЭРТО-РИКО', NULL, '630', 'PR', 'PRI', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(180, 'КАТАР', 'Государство Катар', '634', 'QA', 'QAT', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(181, 'РЕЮНЬОН', NULL, '638', 'RE', 'REU', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(182, 'РУМЫНИЯ', NULL, '642', 'RO', 'ROU', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(183, 'РОССИЯ', 'Российская Федерация', '643', 'RU', 'RUS', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(184, 'РУАНДА', 'Руандийская Республика', '646', 'RW', 'RWA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(185, 'СЕН-БАРТЕЛЕМИ', NULL, '652', 'BL', 'BLM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(186, 'СВЯТАЯ ЕЛЕНА', NULL, '654', 'SH', 'SHN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(187, 'СЕНТ-КИТС И НЕВИС', NULL, '659', 'KN', 'KNA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(188, 'АНГИЛЬЯ', NULL, '660', 'AI', 'AIA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(189, 'СЕНТ-ЛЮСИЯ', NULL, '662', 'LC', 'LCA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(190, 'СЕН-МАРТЕН', NULL, '663', 'MF', 'MAF', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(191, 'СЕН-ПЬЕР И МИКЕЛОН', NULL, '666', 'PM', 'SPM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(192, 'СЕНТ-ВИНСЕНТ И ГРЕНАДИНЫ', NULL, '670', 'VC', 'VCT', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(193, 'САН-МАРИНО', 'Республика Сан-Марино', '674', 'SM', 'SMR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(194, 'САН-ТОМЕ И ПРИНСИПИ', 'Демократическая Республика Сан-Томе и Принсипи', '678', 'ST', 'STP', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(195, 'САУДОВСКАЯ АРАВИЯ', 'Королевство Саудовская Аравия', '682', 'SA', 'SAU', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(196, 'СЕНЕГАЛ', 'Республика Сенегал', '686', 'SN', 'SEN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(197, 'СЕРБИЯ', 'Республика Сербия', '688', 'RS', 'SRB', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(198, 'СЕЙШЕЛЫ', 'Республика Сейшелы', '690', 'SC', 'SYC', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(199, 'СЬЕРРА-ЛЕОНЕ', 'Республика Сьерра-Леоне', '694', 'SL', 'SLE', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(200, 'СИНГАПУР', 'Республика Сингапур', '702', 'SG', 'SGP', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(201, 'СЛОВАКИЯ', 'Словацкая Республика', '703', 'SK', 'SVK', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(202, 'ВЬЕТНАМ', 'Социалистическая Республика Вьетнам', '704', 'VN', 'VNM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(203, 'СЛОВЕНИЯ', 'Республика Словения', '705', 'SI', 'SVN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(204, 'СОМАЛИ', 'Сомалийская Республика', '706', 'SO', 'SOM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(205, 'ЮЖНАЯ АФРИКА', 'Южно-Африканская Республика', '710', 'ZA', 'ZAF', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(206, 'ЗИМБАБВЕ', 'Республика Зимбабве', '716', 'ZW', 'ZWE', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(207, 'ИСПАНИЯ', 'Королевство Испания', '724', 'ES', 'ESP', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(208, 'ЗАПАДНАЯ САХАРА', NULL, '732', 'EH', 'ESH', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(209, 'СУДАН', 'Республика Судан', '736', 'SD', 'SDN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(210, 'СУРИНАМ', 'Республика Суринам', '740', 'SR', 'SUR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(211, 'ШПИЦБЕРГЕН И ЯН МАЙЕН', NULL, '744', 'SJ', 'SJM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(212, 'СВАЗИЛЕНД', 'Королевство Свазиленд', '748', 'SZ', 'SWZ', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(213, 'ШВЕЦИЯ', 'Королевство Швеция', '752', 'SE', 'SWE', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(214, 'ШВЕЙЦАРИЯ', 'Швейцарская Конфедерация', '756', 'CH', 'CHE', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(215, 'СИРИЙСКАЯ АРАБСКАЯ РЕСПУБЛИКА', NULL, '760', 'SY', 'SYR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(216, 'ТАДЖИКИСТАН', 'Республика Таджикистан', '762', 'TJ', 'TJK', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(217, 'ТАИЛАНД', 'Королевство Таиланд', '764', 'TH', 'THA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(218, 'ТОГО', 'Тоголезская Республика', '768', 'TG', 'TGO', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(219, 'ТОКЕЛАУ', NULL, '772', 'TK', 'TKL', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(220, 'ТОНГА', 'Королевство Тонга', '776', 'TO', 'TON', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(221, 'ТРИНИДАД И ТОБАГО', 'Республика Тринидад и Тобаго', '780', 'TT', 'TTO', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(222, 'ОБЪЕДИНЕННЫЕ АРАБСКИЕ ЭМИРАТЫ', NULL, '784', 'AE', 'ARE', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(223, 'ТУНИС', 'Тунисская Республика', '788', 'TN', 'TUN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(224, 'ТУРЦИЯ', 'Турецкая Республика', '792', 'TR', 'TUR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(225, 'ТУРКМЕНИЯ', 'Туркменистан', '795', 'TM', 'TKM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(226, 'ОСТРОВА ТЕРКС И КАЙКОС', NULL, '796', 'TC', 'TCA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(227, 'ТУВАЛУ', NULL, '798', 'TV', 'TUV', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(228, 'УГАНДА', 'Республика Уганда', '800', 'UG', 'UGA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(229, 'УКРАИНА', NULL, '804', 'UA', 'UKR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(230, 'РЕСПУБЛИКА МАКЕДОНИЯ', NULL, '807', 'MK', 'MKD', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(231, 'ЕГИПЕТ', 'Арабская Республика Египет', '818', 'EG', 'EGY', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(232, 'СОЕДИНЕННОЕ КОРОЛЕВСТВО', 'Соединенное Королевство Великобритании и Северной Ирландии', '826', 'GB', 'GBR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(233, 'ГЕРНСИ', NULL, '831', 'GG', 'GGY', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(234, 'ДЖЕРСИ', NULL, '832', 'JE', 'JEY', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(235, 'ОСТРОВ МЭН', NULL, '833', 'IM', 'IMN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(236, 'ТАНЗАНИЯ, ОБЪЕДИНЕННАЯ РЕСПУБЛИКА', 'Объединенная Республика Танзания', '834', 'TZ', 'TZA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(237, 'СОЕДИНЕННЫЕ ШТАТЫ', 'Соединенные Штаты Америки', '840', 'US', 'USA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(238, 'ВИРГИНСКИЕ ОСТРОВА, США', 'Виргинские острова Соединенных Штатов', '850', 'VI', 'VIR', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(239, 'БУРКИНА-ФАСО', NULL, '854', 'BF', 'BFA', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(240, 'УРУГВАЙ', 'Восточная Республика Уругвай', '858', 'UY', 'URY', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(241, 'УЗБЕКИСТАН', 'Республика Узбекистан', '860', 'UZ', 'UZB', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(242, 'ВЕНЕСУЭЛА БОЛИВАРИАНСКАЯ РЕСПУБЛИКА', 'Боливарианская Республика Венесуэла', '862', 'VE', 'VEN', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(243, 'УОЛЛИС И ФУТУНА', NULL, '876', 'WF', 'WLF', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(244, 'САМОА', 'Независимое Государство Самоа', '882', 'WS', 'WSM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(245, 'ЙЕМЕН', 'Йеменская Республика', '887', 'YE', 'YEM', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(246, 'ЗАМБИЯ', 'Республика Замбия', '894', 'ZM', 'ZMB', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(247, 'АБХАЗИЯ', 'Республика Абхазия', '895', 'AB', 'ABH', 1, NULL);
INSERT INTO class_country (id, `name`, full_name, number_code, alfa2, alfa3, visible, `comment`) VALUES(248, 'ЮЖНАЯ ОСЕТИЯ', 'Республика Южная Осетия', '896', 'OS', 'OST', 1, NULL);
COMMIT;

--
-- Структура таблицы 'class_unit'
--

CREATE TABLE class_unit (
  id smallint(6) NOT NULL auto_increment COMMENT 'pk',
  `name` varchar(255) NOT NULL COMMENT 'Наименование единицы измерения',
  number_code varchar(5) NOT NULL COMMENT 'Код',
  rus_name1 varchar(50) default NULL COMMENT 'Условное обозначение национальное',
  eng_name1 varchar(50) default NULL COMMENT 'Условное обозначение международное',
  rus_name2 varchar(50) default NULL COMMENT 'Кодовое буквенное обозначение национальное',
  eng_name2 varchar(50) default NULL COMMENT 'Кодовое буквенное обозначение международное',
  class_unit_group_id tinyint(4) NOT NULL COMMENT 'Группа единиц измерения',
  class_unit_type_id tinyint(4) NOT NULL COMMENT 'Раздел/приложение в которое входит единица измерения',
  visible tinyint(4) NOT NULL default '1' COMMENT 'Видимость',
  `comment` varchar(255) default NULL COMMENT 'Комментарий',
  PRIMARY KEY  (id),
  UNIQUE KEY number_code (number_code),
  KEY class_unit_group_id (class_unit_group_id),
  KEY class_unit_type_id (class_unit_type_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Общероссийский классификатор единиц измерения ОКЕИ' AUTO_INCREMENT=461 ;

--
-- Дамп данных таблицы `class_unit`
--

INSERT INTO `class_unit` (`id`, `name`, `number_code`, `rus_name1`, `eng_name1`, `rus_name2`, `eng_name2`, `class_unit_group_id`, `class_unit_type_id`, `visible`, `comment`) VALUES
(1, 'Миллиметр', '003', 'мм', 'mm', 'ММ', 'MMT', 1, 1, 1, NULL),
(2, 'Сантиметр', '004', 'см', 'cm', 'СМ', 'CMT', 1, 1, 1, NULL),
(3, 'Дециметр', '005', 'дм', 'dm', 'ДМ', 'DMT', 1, 1, 1, NULL),
(4, 'Метр', '006', 'м', 'm', 'М', 'MTR', 1, 1, 1, NULL),
(5, 'Километр; тысяча метров', '008', 'км; 10^3 м', 'km', 'КМ; ТЫС М', 'KMT', 1, 1, 1, NULL),
(6, 'Мегаметр; миллион метров', '009', 'Мм; 10^6 м', 'Mm', 'МЕГАМ; МЛН М', 'MAM', 1, 1, 1, NULL),
(7, 'Дюйм (25,4 мм)', '039', 'дюйм', 'in', 'ДЮЙМ', 'INH', 1, 1, 1, NULL),
(8, 'Фут (0,3048 м)', '041', 'фут', 'ft', 'ФУТ', 'FOT', 1, 1, 1, NULL),
(9, 'Ярд (0,9144 м)', '043', 'ярд', 'yd', 'ЯРД', 'YRD', 1, 1, 1, NULL),
(10, 'Морская миля (1852 м)', '047', 'миля', 'n mile', 'МИЛЬ', 'NMI', 1, 1, 1, NULL),
(11, 'Квадратный миллиметр', '050', 'мм2', 'mm2', 'ММ2', 'MMK', 2, 1, 1, NULL),
(12, 'Квадратный сантиметр', '051', 'см2', 'cm2', 'СМ2', 'CMK', 2, 1, 1, NULL),
(13, 'Квадратный дециметр', '053', 'дм2', 'dm2', 'ДМ2', 'DMK', 2, 1, 1, NULL),
(14, 'Квадратный метр', '055', 'м2', 'm2', 'М2', 'MTK', 2, 1, 1, NULL),
(15, 'Тысяча квадратных метров', '058', '10^3 м^2', 'daa', 'ТЫС М2', 'DAA', 2, 1, 1, NULL),
(16, 'Гектар', '059', 'га', 'ha', 'ГА', 'HAR', 2, 1, 1, NULL),
(17, 'Квадратный километр', '061', 'км2', 'km2', 'КМ2', 'KMK', 2, 1, 1, NULL),
(18, 'Квадратный дюйм (645,16 мм2)', '071', 'дюйм2', 'in2', 'ДЮЙМ2', 'INK', 2, 1, 1, NULL),
(19, 'Квадратный фут (0,092903 м2)', '073', 'фут2', 'ft2', 'ФУТ2', 'FTK', 2, 1, 1, NULL),
(20, 'Квадратный ярд (0,8361274 м2)', '075', 'ярд2', 'yd2', 'ЯРД2', 'YDK', 2, 1, 1, NULL),
(21, 'Ар (100 м2)', '109', 'а', 'a', 'АР', 'ARE', 2, 1, 1, NULL),
(22, 'Кубический миллиметр', '110', 'мм3', 'mm3', 'ММ3', 'MMQ', 3, 1, 1, NULL),
(23, 'Кубический сантиметр; миллилитр', '111', 'см3; мл', 'cm3; ml', 'СМ3; МЛ', 'CMQ; MLT', 3, 1, 1, NULL),
(24, 'Литр; кубический дециметр', '112', 'л; дм3', 'I; L; dm^3', 'Л; ДМ3', 'LTR; DMQ', 3, 1, 1, NULL),
(25, 'Кубический метр', '113', 'м3', 'm3', 'М3', 'MTQ', 3, 1, 1, NULL),
(26, 'Децилитр', '118', 'дл', 'dl', 'ДЛ', 'DLT', 3, 1, 1, NULL),
(27, 'Гектолитр', '122', 'гл', 'hl', 'ГЛ', 'HLT', 3, 1, 1, NULL),
(28, 'Мегалитр', '126', 'Мл', 'Ml', 'МЕГАЛ', 'MAL', 3, 1, 1, NULL),
(29, 'Кубический дюйм (16387,1 мм3)', '131', 'дюйм3', 'in3', 'ДЮЙМ3', 'INQ', 3, 1, 1, NULL),
(30, 'Кубический фут (0,02831685 м3)', '132', 'фут3', 'ft3', 'ФУТ3', 'FTQ', 3, 1, 1, NULL),
(31, 'Кубический ярд (0,764555 м3)', '133', 'ярд3', 'yd3', 'ЯРД3', 'YDQ', 3, 1, 1, NULL),
(32, 'Миллион кубических метров', '159', '10^6 м3', '10^6 m3', 'МЛН М3', 'HMQ', 3, 1, 1, NULL),
(33, 'Гектограмм', '160', 'гг', 'hg', 'ГГ', 'HGM', 4, 1, 1, NULL),
(34, 'Миллиграмм', '161', 'мг', 'mg', 'МГ', 'MGM', 4, 1, 1, NULL),
(35, 'Метрический карат', '162', 'кар', 'МС', 'КАР', 'CTM', 4, 1, 1, NULL),
(36, 'Грамм', '163', 'г', 'g', 'Г', 'GRM', 4, 1, 1, NULL),
(37, 'Килограмм', '166', 'кг', 'kg', 'КГ', 'KGM', 4, 1, 1, NULL),
(38, 'Тонна; метрическая тонна (1000 кг)', '168', 'т', 't', 'Т', 'TNE', 4, 1, 1, NULL),
(39, 'Килотонна', '170', '10^3 т', 'kt', 'КТ', 'KTN', 4, 1, 1, NULL),
(40, 'Сантиграмм', '173', 'сг', 'cg', 'СГ', 'CGM', 4, 1, 1, NULL),
(41, 'Брутто-регистровая тонна (2,8316 м3)', '181', 'БРТ', '-', 'БРУТТ. РЕГИСТР Т', 'GRT', 4, 1, 1, NULL),
(42, 'Грузоподъемность в метрических тоннах', '185', 'т грп', '-', 'Т ГРУЗОПОД', 'CCT', 4, 1, 1, NULL),
(43, 'Центнер (метрический) (100 кг); гектокилограмм; квинтал1 (метрический); децитонна', '206', 'ц', 'q; 10^2 kg', 'Ц', 'DTN', 4, 1, 1, NULL),
(44, 'Ватт', '212', 'Вт', 'W', 'ВТ', 'WTT', 5, 1, 1, NULL),
(45, 'Киловатт', '214', 'кВт', 'kW', 'КВТ', 'KWT', 5, 1, 1, NULL),
(46, 'Мегаватт; тысяча киловатт', '215', 'МВт; 10^3 кВт', 'MW', 'МЕГАВТ; ТЫС КВТ', 'MAW', 5, 1, 1, NULL),
(47, 'Вольт', '222', 'В', 'V', 'В', 'VLT', 5, 1, 1, NULL),
(48, 'Киловольт', '223', 'кВ', 'kV', 'КВ', 'KVT', 5, 1, 1, NULL),
(49, 'Киловольт-ампер', '227', 'кВ.А', 'kV.A', 'КВ.А', 'KVA', 5, 1, 1, NULL),
(50, 'Мегавольт-ампер (тысяча киловольт-ампер)', '228', 'МВ.А', 'MV.A', 'МЕГАВ.А', 'MVA', 5, 1, 1, NULL),
(51, 'Киловар', '230', 'квар', 'kVAR', 'КВАР', 'KVR', 5, 1, 1, NULL),
(52, 'Ватт-час', '243', 'Вт.ч', 'W.h', 'ВТ.Ч', 'WHR', 5, 1, 1, NULL),
(53, 'Киловатт-час', '245', 'кВт.ч', 'kW.h', 'КВТ.Ч', 'KWH', 5, 1, 1, NULL),
(54, 'Мегаватт-час; 1000 киловатт-часов', '246', 'МВт.ч; 10^3 кВт.ч', 'МW.h', 'МЕГАВТ.Ч; ТЫС КВТ.Ч', 'MWH', 5, 1, 1, NULL),
(55, 'Гигаватт-час (миллион киловатт-часов)', '247', 'ГВт.ч', 'GW.h', 'ГИГАВТ.Ч', 'GWH', 5, 1, 1, NULL),
(56, 'Ампер', '260', 'А', 'A', 'А', 'AMP', 5, 1, 1, NULL),
(57, 'Ампер-час (3,6 кКл)', '263', 'А.ч', 'A.h', 'А.Ч', 'AMH', 5, 1, 1, NULL),
(58, 'Тысяча ампер-часов', '264', '10^3 А.ч', '10^3 A.h', 'ТЫС А.Ч', 'TAH', 5, 1, 1, NULL),
(59, 'Кулон', '270', 'Кл', 'C', 'КЛ', 'COU', 5, 1, 1, NULL),
(60, 'Джоуль', '271', 'Дж', 'J', 'ДЖ', 'JOU', 5, 1, 1, NULL),
(61, 'Килоджоуль', '273', 'кДж', 'kJ', 'КДЖ', 'KJO', 5, 1, 1, NULL),
(62, 'Ом', '274', 'Ом', '<омега>', 'ОМ', 'OHM', 5, 1, 1, NULL),
(63, 'Градус Цельсия', '280', 'град. C', 'град. C', 'ГРАД ЦЕЛЬС', 'CEL', 5, 1, 1, NULL),
(64, 'Градус Фаренгейта', '281', 'град. F', 'град. F', 'ГРАД ФАРЕНГ', 'FAN', 5, 1, 1, NULL),
(65, 'Кандела', '282', 'кд', 'cd', 'КД', 'CDL', 5, 1, 1, NULL),
(66, 'Люкс', '283', 'лк', 'lx', 'ЛК', 'LUX', 5, 1, 1, NULL),
(67, 'Люмен', '284', 'лм', 'lm', 'ЛМ', 'LUM', 5, 1, 1, NULL),
(68, 'Кельвин', '288', 'K', 'K', 'К', 'KEL', 5, 1, 1, NULL),
(69, 'Ньютон', '289', 'Н', 'N', 'Н', 'NEW', 5, 1, 1, NULL),
(70, 'Герц', '290', 'Гц', 'Hz', 'ГЦ', 'HTZ', 5, 1, 1, NULL),
(71, 'Килогерц', '291', 'кГц', 'kHz', 'КГЦ', 'KHZ', 5, 1, 1, NULL),
(72, 'Мегагерц', '292', 'МГц', 'MHz', 'МЕГАГЦ', 'MHZ', 5, 1, 1, NULL),
(73, 'Паскаль', '294', 'Па', 'Pa', 'ПА', 'PAL', 5, 1, 1, NULL),
(74, 'Сименс', '296', 'См', 'S', 'СИ', 'SIE', 5, 1, 1, NULL),
(75, 'Килопаскаль', '297', 'кПа', 'kPa', 'КПА', 'KPA', 5, 1, 1, NULL),
(76, 'Мегапаскаль', '298', 'МПа', 'MPa', 'МЕГАПА', 'MPA', 5, 1, 1, NULL),
(77, 'Физическая атмосфера (101325 Па)', '300', 'атм', 'atm', 'АТМ', 'ATM', 5, 1, 1, NULL),
(78, 'Техническая атмосфера (98066,5 Па)', '301', 'ат', 'at', 'АТТ', 'ATT', 5, 1, 1, NULL),
(79, 'Гигабеккерель', '302', 'ГБк', 'GBq', 'ГИГАБК', 'GBQ', 5, 1, 1, NULL),
(80, 'Милликюри', '304', 'мКи', 'mCi', 'МКИ', 'MCU', 5, 1, 1, NULL),
(81, 'Кюри', '305', 'Ки', 'Ci', 'КИ', 'CUR', 5, 1, 1, NULL),
(82, 'Грамм делящихся изотопов', '306', 'г Д/И', 'g fissile isotopes', 'Г ДЕЛЯЩ ИЗОТОП', 'GFI', 5, 1, 1, NULL),
(83, 'Миллибар', '308', 'мб', 'mbar', 'МБАР', 'MBR', 5, 1, 1, NULL),
(84, 'Бар', '309', 'бар', 'bar', 'БАР', 'BAR', 5, 1, 1, NULL),
(85, 'Гектобар', '310', 'гб', 'hbar', 'ГБАР', 'HBA', 5, 1, 1, NULL),
(86, 'Килобар', '312', 'кб', 'kbar', 'КБАР', 'KBA', 5, 1, 1, NULL),
(87, 'Фарад', '314', 'Ф', 'F', 'Ф', 'FAR', 5, 1, 1, NULL),
(88, 'Килограмм на кубический метр', '316', 'кг/м3', 'kg/m3', 'КГ/М3', 'KMQ', 5, 1, 1, NULL),
(89, 'Беккерель', '323', 'Бк', 'Bq', 'БК', 'BQL', 5, 1, 1, NULL),
(90, 'Вебер', '324', 'Вб', 'Wb', 'ВБ', 'WEB', 5, 1, 1, NULL),
(91, 'Узел (миля/ч)', '327', 'уз', 'kn', 'УЗ', 'KNT', 5, 1, 1, NULL),
(92, 'Метр в секунду', '328', 'м/с', 'm/s', 'М/С', 'MTS', 5, 1, 1, NULL),
(93, 'Оборот в секунду', '330', 'об/с', 'r/s', 'ОБ/С', 'RPS', 5, 1, 1, NULL),
(94, 'Оборот в минуту', '331', 'об/мин', 'r/min', 'ОБ/МИН', 'RPM', 5, 1, 1, NULL),
(95, 'Километр в час', '333', 'км/ч', 'km/h', 'КМ/Ч', 'KMH', 5, 1, 1, NULL),
(96, 'Метр на секунду в квадрате', '335', 'м/с2', 'm/s2', 'М/С2', 'MSK', 5, 1, 1, NULL),
(97, 'Кулон на килограмм', '349', 'Кл/кг', 'C/kg', 'КЛ/КГ', 'CKG', 5, 1, 1, NULL),
(98, 'Секунда', '354', 'с', 's', 'С', 'SEC', 6, 1, 1, NULL),
(99, 'Минута', '355', 'мин', 'min', 'МИН', 'MIN', 6, 1, 1, NULL),
(100, 'Час', '356', 'ч', 'h', 'Ч', 'HUR', 6, 1, 1, NULL),
(101, 'Сутки', '359', 'сут; дн', 'd', 'СУТ; ДН', 'DAY', 6, 1, 1, NULL),
(102, 'Неделя', '360', 'нед', '-', 'НЕД', 'WEE', 6, 1, 1, NULL),
(103, 'Декада', '361', 'дек', '-', 'ДЕК', 'DAD', 6, 1, 1, NULL),
(104, 'Месяц', '362', 'мес', '-', 'МЕС', 'MON', 6, 1, 1, NULL),
(105, 'Квартал', '364', 'кварт', '-', 'КВАРТ', 'QAN', 6, 1, 1, NULL),
(106, 'Полугодие', '365', 'полгода', '-', 'ПОЛГОД', 'SAN', 6, 1, 1, NULL),
(107, 'Год', '366', 'г; лет', 'a', 'ГОД; ЛЕТ', 'ANN', 6, 1, 1, NULL),
(108, 'Десятилетие', '368', 'деслет', '-', 'ДЕСЛЕТ', 'DEC', 6, 1, 1, NULL),
(109, 'Килограмм в секунду', '499', 'кг/с', '-', 'КГ/С', 'KGS', 7, 1, 1, NULL),
(110, 'Тонна пара в час', '533', 'т пар/ч', '-', 'Т ПАР/Ч', 'TSH', 7, 1, 1, NULL),
(111, 'Кубический метр в секунду', '596', 'м3/с', 'm3/s', 'М3/С', 'MQS', 7, 1, 1, NULL),
(112, 'Кубический метр в час', '598', 'м3/ч', 'm3/h', 'М3/Ч', 'MQH', 7, 1, 1, NULL),
(113, 'Тысяча кубических метров в сутки', '599', '10^3 м3/сут', '-', 'ТЫС М3/СУТ', 'TQD', 7, 1, 1, NULL),
(114, 'Бобина', '616', 'боб', '-', 'БОБ', 'NBB', 7, 1, 1, NULL),
(115, 'Лист', '625', 'л.', '-', 'ЛИСТ', 'LEF', 7, 1, 1, NULL),
(116, 'Сто листов', '626', '100 л.', '-', '100 ЛИСТ', 'CLF', 7, 1, 1, NULL),
(117, 'Тысяча стандартных условных кирпичей', '630', 'тыс станд. усл. кирп', '-', 'ТЫС СТАНД УСЛ КИРП', 'MBE', 7, 1, 1, NULL),
(118, 'Дюжина (12 шт.)', '641', 'дюжина', 'Doz; 12', 'ДЮЖИНА', 'DZN', 7, 1, 1, NULL),
(119, 'Изделие', '657', 'изд', '-', 'ИЗД', 'NAR', 7, 1, 1, NULL),
(120, 'Сто ящиков', '683', '100 ящ.', 'Hbx', '100 ЯЩ', 'HBX', 7, 1, 1, NULL),
(121, 'Набор', '704', 'набор', '-', 'НАБОР', 'SET', 7, 1, 1, NULL),
(122, 'Пара (2 шт.)', '715', 'пар', 'pr; 2', 'ПАР', 'NPR', 7, 1, 1, NULL),
(123, 'Два десятка', '730', '20', '20', '2 ДЕС', 'SCO', 7, 1, 1, NULL),
(124, 'Десять пар', '732', '10 пар', '-', 'ДЕС ПАР', 'TPR', 7, 1, 1, NULL),
(125, 'Дюжина пар', '733', 'дюжина пар', '-', 'ДЮЖИНА ПАР', 'DPR', 7, 1, 1, NULL),
(126, 'Посылка', '734', 'посыл', '-', 'ПОСЫЛ', 'NPL', 7, 1, 1, NULL),
(127, 'Часть', '735', 'часть', '-', 'ЧАСТЬ', 'NPT', 7, 1, 1, NULL),
(128, 'Рулон', '736', 'рул', '-', 'РУЛ', 'NPL', 7, 1, 1, NULL),
(129, 'Дюжина рулонов', '737', 'дюжина рул', '-', 'ДЮЖИНА РУЛ', 'DRL', 7, 1, 1, NULL),
(130, 'Дюжина штук', '740', 'дюжина шт', '-', 'ДЮЖИНА ШТ', 'DPC', 7, 1, 1, NULL),
(131, 'Элемент', '745', 'элем', 'CI', 'ЭЛЕМ', 'NCL', 7, 1, 1, NULL),
(132, 'Упаковка', '778', 'упак', '-', 'УПАК', 'NMP', 7, 1, 1, NULL),
(133, 'Дюжина упаковок', '780', 'дюжина упак', '-', 'ДЮЖИНА УПАК', 'DZP', 7, 1, 1, NULL),
(134, 'Сто упаковок', '781', '100 упак', '-', '100 УПАК', 'CNP', 7, 1, 1, NULL),
(135, 'Штука', '796', 'шт', 'pc; 1', 'ШТ', 'PCE; NMB', 7, 1, 1, NULL),
(136, 'Сто штук', '797', '100 шт', '100', '100 ШТ', 'CEN', 7, 1, 1, NULL),
(137, 'Тысяча штук', '798', 'тыс. шт; 1000 шт', '1000', 'ТЫС ШТ', 'MIL', 7, 1, 1, NULL),
(138, 'Миллион штук', '799', '10^6 шт', '10^6', 'МЛН ШТ', 'MIO', 7, 1, 1, NULL),
(139, 'Миллиард штук', '800', '10^9 шт', '10^9', 'МЛРД ШТ', 'MLD', 7, 1, 1, NULL),
(140, 'Биллион штук (Европа); триллион штук', '801', '10^12 шт', '10^12', 'БИЛЛ ШТ (ЕВР); ТРИЛЛ ШТ', 'BIL', 7, 1, 1, NULL),
(141, 'Квинтильон штук (Европа)', '802', '10^18 шт', '10^18', 'КВИНТ ШТ', 'TRL', 7, 1, 1, NULL),
(142, 'Крепость спирта по массе', '820', 'креп. спирта по массе', '% mds', 'КРЕП СПИРТ ПО МАССЕ', 'ASM', 7, 1, 1, NULL),
(143, 'Крепость спирта по объему', '821', 'креп. спирта по объему', '% vol', 'КРЕП СПИРТ ПО ОБЪЕМ', 'ASV', 7, 1, 1, NULL),
(144, 'Литр чистого (100%) спирта', '831', 'л 100% спирта', '-', 'Л ЧИСТ СПИРТ', 'LPA', 7, 1, 1, NULL),
(145, 'Гектолитр чистого (100%) спирта', '833', 'Гл 100% спирта', '-', 'ГЛ ЧИСТ СПИРТ', 'HPA', 7, 1, 1, NULL),
(146, 'Килограмм пероксида водорода', '841', 'кг H2О2', '-', 'КГ ПЕРОКСИД ВОДОРОДА', '-', 7, 1, 1, NULL),
(147, 'Килограмм 90%-го сухого вещества', '845', 'кг 90% с/в', '-', 'КГ 90 ПРОЦ СУХ ВЕЩ', 'KSD', 7, 1, 1, NULL),
(148, 'Тонна 90%-го сухого вещества', '847', 'т 90% с/в', '-', 'Т 90 ПРОЦ СУХ ВЕЩ', 'TSD', 7, 1, 1, NULL),
(149, 'Килограмм оксида калия', '852', 'кг К2О', '-', 'КГ ОКСИД КАЛИЯ', 'KPO', 7, 1, 1, NULL),
(150, 'Килограмм гидроксида калия', '859', 'кг КОН', '-', 'КГ ГИДРОКСИД КАЛИЯ', 'KPH', 7, 1, 1, NULL),
(151, 'Килограмм азота', '861', 'кг N', '-', 'КГ АЗОТ', 'KNI', 7, 1, 1, NULL),
(152, 'Килограмм гидроксида натрия', '863', 'кг NaOH', '-', 'КГ ГИДРОКСИД НАТРИЯ', 'KSH', 7, 1, 1, NULL),
(153, 'Килограмм пятиокиси фосфора', '865', 'кг Р2О5', '-', 'КГ ПЯТИОКИСЬ ФОСФОРА', 'KPP', 7, 1, 1, NULL),
(154, 'Килограмм урана', '867', 'кг U', '-', 'КГ УРАН', 'KUR', 7, 1, 1, NULL),
(155, 'Погонный метр', '018', 'пог. м', NULL, 'ПОГ М', NULL, 1, 2, 1, NULL),
(156, 'Тысяча погонных метров', '019', '10^3 пог. м', NULL, 'ТЫС ПОГ М', NULL, 1, 2, 1, NULL),
(157, 'Условный метр', '020', 'усл. м', NULL, 'УСЛ М', NULL, 1, 2, 1, NULL),
(158, 'Тысяча условных метров', '048', '10^3 усл. м', NULL, 'ТЫС УСЛ М', NULL, 1, 2, 1, NULL),
(159, 'Километр условных труб', '049', 'км усл. труб', NULL, 'КМ УСЛ ТРУБ', NULL, 1, 2, 1, NULL),
(160, 'Тысяча квадратных дециметров', '054', '10^3 дм2', NULL, 'ТЫС ДМ2', NULL, 2, 2, 1, NULL),
(161, 'Миллион квадратных дециметров', '056', '10^6 дм2', NULL, 'МЛН ДМ2', NULL, 2, 2, 1, NULL),
(162, 'Миллион квадратных метров', '057', '10^6 м2', NULL, 'МЛН М2', NULL, 2, 2, 1, NULL),
(163, 'Тысяча гектаров', '060', '10^3 га', NULL, 'ТЫС ГА', NULL, 2, 2, 1, NULL),
(164, 'Условный квадратный метр', '062', 'усл. м2', NULL, 'УСЛ М2', NULL, 2, 2, 1, NULL),
(165, 'Тысяча условных квадратных метров', '063', '10^3 усл. м2', NULL, 'ТЫС УСЛ М2', NULL, 2, 2, 1, NULL),
(166, 'Миллион условных квадратных метров', '064', '10^6 усл. м2', NULL, 'МЛН УСЛ М2', NULL, 2, 2, 1, NULL),
(167, 'Квадратный метр общей площади', '081', 'м2 общ. пл', NULL, 'М2 ОБЩ ПЛ', NULL, 2, 2, 1, NULL),
(168, 'Тысяча квадратных метров общей площади', '082', '10^3 м2 общ. пл', NULL, 'ТЫС М2 ОБЩ ПЛ', NULL, 2, 2, 1, NULL),
(169, 'Миллион квадратных метров общей площади', '083', '10^6 м2 общ. пл', NULL, 'МЛН М2. ОБЩ ПЛ', NULL, 2, 2, 1, NULL),
(170, 'Квадратный метр жилой площади', '084', 'м2 жил. пл', NULL, 'М2 ЖИЛ ПЛ', NULL, 2, 2, 1, NULL),
(171, 'Тысяча квадратных метров жилой площади', '085', '10^3 м2 жил. пл', NULL, 'ТЫС М2 ЖИЛ ПЛ', NULL, 2, 2, 1, NULL),
(172, 'Миллион квадратных метров жилой площади', '086', '10^6 м2 жил. пл', NULL, 'МЛН М2 ЖИЛ ПЛ', NULL, 2, 2, 1, NULL),
(173, 'Квадратный метр учебно-лабораторных зданий', '087', 'м2 уч. лаб. здан', NULL, 'М2 УЧ.ЛАБ ЗДАН', NULL, 2, 2, 1, NULL),
(174, 'Тысяча квадратных метров учебно-лабораторных зданий', '088', '10^3 м2 уч. лаб. здан', NULL, 'ТЫС М2 УЧ. ЛАБ ЗДАН', NULL, 2, 2, 1, NULL),
(175, 'Миллион квадратных метров в двухмиллиметровом исчислении', '089', '10^6 м2 2 мм исч', NULL, 'МЛН М2 2ММ ИСЧ', NULL, 2, 2, 1, NULL),
(176, 'Тысяча кубических метров', '114', '10^3 м3', NULL, 'ТЫС М3', NULL, 3, 2, 1, NULL),
(177, 'Миллиард кубических метров', '115', '10^9 м3', NULL, 'МЛРД М3', NULL, 3, 2, 1, NULL),
(178, 'Декалитр', '116', 'дкл', NULL, 'ДКЛ', NULL, 3, 2, 1, NULL),
(179, 'Тысяча декалитров', '119', '10^3 дкл', NULL, 'ТЫС ДКЛ', NULL, 3, 2, 1, NULL),
(180, 'Миллион декалитров', '120', '10^6 дкл', NULL, 'МЛН ДКЛ', NULL, 3, 2, 1, NULL),
(181, 'Плотный кубический метр', '121', 'плотн. м3', NULL, 'ПЛОТН М3', NULL, 3, 2, 1, NULL),
(182, 'Условный кубический метр', '123', 'усл. м3', NULL, 'УСЛ М3', NULL, 3, 2, 1, NULL),
(183, 'Тысяча условных кубических метров', '124', '10^3 усл. м3', NULL, 'ТЫС УСЛ М3', NULL, 3, 2, 1, NULL),
(184, 'Миллион кубических метров переработки газа', '125', '10^6 м3 перераб. газа', NULL, 'МЛН М3 ПЕРЕРАБ ГАЗА', NULL, 3, 2, 1, NULL),
(185, 'Тысяча плотных кубических метров', '127', '10^3 плотн. м3', NULL, 'ТЫС ПЛОТН М3', NULL, 3, 2, 1, NULL),
(186, 'Тысяча полулитров', '128', '10^3 пол. л', NULL, 'ТЫС ПОЛ Л', NULL, 3, 2, 1, NULL),
(187, 'Миллион полулитров', '129', '10^6 пол. л', NULL, 'МЛН ПОЛ Л', NULL, 3, 2, 1, NULL),
(188, 'Тысяча литров; 1000 литров', '130', '10^3 л; 1000 л', NULL, 'ТЫС Л', NULL, 3, 2, 1, NULL),
(189, 'Тысяча каратов метрических', '165', '10^3 кар', NULL, 'ТЫС КАР', NULL, 4, 2, 1, NULL),
(190, 'Миллион каратов метрических', '167', '10^6 кар', NULL, 'МЛН КАР', NULL, 4, 2, 1, NULL),
(191, 'Тысяча тонн', '169', '10^3 т', NULL, 'ТЫС Т', NULL, 4, 2, 1, NULL),
(192, 'Миллион тонн', '171', '10^6 т', NULL, 'МЛН Т', NULL, 4, 2, 1, NULL),
(193, 'Тонна условного топлива', '172', 'т усл. топл', NULL, 'Т УСЛ ТОПЛ', NULL, 4, 2, 1, NULL),
(194, 'Тысяча тонн условного топлива', '175', '10^3 т усл. топл', NULL, 'ТЫС Т УСЛ ТОПЛ', NULL, 4, 2, 1, NULL),
(195, 'Миллион тонн условного топлива', '176', '10^6 т усл. топл', NULL, 'МЛН Т УСЛ ТОПЛ', NULL, 4, 2, 1, NULL),
(196, 'Тысяча тонн единовременного хранения', '177', '10^3 т единовр. хран', NULL, 'ТЫС Т ЕДИНОВР ХРАН', NULL, 4, 2, 1, NULL),
(197, 'Тысяча тонн переработки', '178', '10^3 т перераб', NULL, 'ТЫС Т ПЕРЕРАБ', NULL, 4, 2, 1, NULL),
(198, 'Условная тонна', '179', 'усл. т', NULL, 'УСЛ Т', NULL, 4, 2, 1, NULL),
(199, 'Тысяча центнеров', '207', '10^3 ц', NULL, 'ТЫС Ц', NULL, 4, 2, 1, NULL),
(200, 'Вольт-ампер', '226', 'В.А', NULL, 'В.А', NULL, 5, 2, 1, NULL),
(201, 'Метр в час', '231', 'м/ч', NULL, 'М/Ч', NULL, 5, 2, 1, NULL),
(202, 'Килокалория', '232', 'ккал', NULL, 'ККАЛ', NULL, 5, 2, 1, NULL),
(203, 'Гигакалория', '233', 'Гкал', NULL, 'ГИГАКАЛ', NULL, 5, 2, 1, NULL),
(204, 'Тысяча гигакалорий', '234', '10^3 Гкал', NULL, 'ТЫС ГИГАКАЛ', NULL, 5, 2, 1, NULL),
(205, 'Миллион гигакалорий', '235', '10^6 Гкал', NULL, 'МЛН ГИГАКАЛ', NULL, 5, 2, 1, NULL),
(206, 'Калория в час', '236', 'кал/ч', NULL, 'КАЛ/Ч', NULL, 5, 2, 1, NULL),
(207, 'Килокалория в час', '237', 'ккал/ч', NULL, 'ККАЛ/Ч', NULL, 5, 2, 1, NULL),
(208, 'Гигакалория в час', '238', 'Гкал/ч', NULL, 'ГИГАКАЛ/Ч', NULL, 5, 2, 1, NULL),
(209, 'Тысяча гигакалорий в час', '239', '10^3 Гкал/ч', NULL, 'ТЫС ГИГАКАЛ/Ч', NULL, 5, 2, 1, NULL),
(210, 'Миллион ампер-часов', '241', '10^6 А.ч', NULL, 'МЛН А.Ч', NULL, 5, 2, 1, NULL),
(211, 'Миллион киловольт-ампер', '242', '10^6 кВ.А', NULL, 'МЛН КВ.А', NULL, 5, 2, 1, NULL),
(212, 'Киловольт-ампер реактивный', '248', 'кВ.А Р', NULL, 'КВ.А Р', NULL, 5, 2, 1, NULL),
(213, 'Миллиард киловатт-часов', '249', '10^9 кВт.ч', NULL, 'МЛРД КВТ.Ч', NULL, 5, 2, 1, NULL),
(214, 'Тысяча киловольт-ампер реактивных', '250', '10^3 кВ.А Р', NULL, 'ТЫС КВ.А Р', NULL, 5, 2, 1, NULL),
(215, 'Лошадиная сила', '251', 'л. с', NULL, 'ЛС', NULL, 5, 2, 1, NULL),
(216, 'Тысяча лошадиных сил', '252', '10^3 л. с', NULL, 'ТЫС ЛС', NULL, 5, 2, 1, NULL),
(217, 'Миллион лошадиных сил', '253', '10^6 л. с', NULL, 'МЛН ЛС', NULL, 5, 2, 1, NULL),
(218, 'Бит', '254', 'бит', NULL, 'БИТ', NULL, 5, 2, 1, NULL),
(219, 'Байт', '255', 'бай', NULL, 'БАЙТ', NULL, 5, 2, 1, NULL),
(220, 'Килобайт', '256', 'кбайт', NULL, 'КБАЙТ', NULL, 5, 2, 1, NULL),
(221, 'Мегабайт', '257', 'Мбайт', NULL, 'МБАЙТ', NULL, 5, 2, 1, NULL),
(222, 'Бод', '258', 'бод', NULL, 'БОД', NULL, 5, 2, 1, NULL),
(223, 'Генри', '287', 'Гн', NULL, 'ГН', NULL, 5, 2, 1, NULL),
(224, 'Тесла', '313', 'Тл', NULL, 'ТЛ', NULL, 5, 2, 1, NULL),
(225, 'Килограмм на квадратный сантиметр', '317', 'кг/см^2', NULL, 'КГ/СМ2', NULL, 5, 2, 1, NULL),
(226, 'Миллиметр водяного столба', '337', 'мм вод. ст', NULL, 'ММ ВОД СТ', NULL, 5, 2, 1, NULL),
(227, 'Миллиметр ртутного столба', '338', 'мм рт. ст', NULL, 'ММ РТ СТ', NULL, 5, 2, 1, NULL),
(228, 'Сантиметр водяного столба', '339', 'см вод. ст', NULL, 'СМ ВОД СТ', NULL, 5, 2, 1, NULL),
(229, 'Микросекунда', '352', 'мкс', NULL, 'МКС', NULL, 6, 2, 1, NULL),
(230, 'Миллисекунда', '353', 'млс', NULL, 'МЛС', NULL, 6, 2, 1, NULL),
(231, 'Рубль', '383', 'руб', NULL, 'РУБ', NULL, 7, 2, 1, NULL),
(232, 'Тысяча рублей', '384', '10^3 руб', NULL, 'ТЫС РУБ', NULL, 7, 2, 1, NULL),
(233, 'Миллион рублей', '385', '10^6 руб', NULL, 'МЛН РУБ', NULL, 7, 2, 1, NULL),
(234, 'Миллиард рублей', '386', '10^9 руб', NULL, 'МЛРД РУБ', NULL, 7, 2, 1, NULL),
(235, 'Триллион рублей', '387', '10^12 руб', NULL, 'ТРИЛЛ РУБ', NULL, 7, 2, 1, NULL),
(236, 'Квадрильон рублей', '388', '10^15 руб', NULL, 'КВАДР РУБ', NULL, 7, 2, 1, NULL),
(237, 'Пассажиро-километр', '414', 'пасс.км', NULL, 'ПАСС.КМ', NULL, 7, 2, 1, NULL),
(238, 'Пассажирское место (пассажирских мест)', '421', 'пасс. мест', NULL, 'ПАСС МЕСТ', NULL, 7, 2, 1, NULL),
(239, 'Тысяча пассажиро-километров', '423', '10^3 пасс.км', NULL, 'ТЫС ПАСС.КМ', NULL, 7, 2, 1, NULL),
(240, 'Миллион пассажиро-километров', '424', '10^6 пасс. км', NULL, 'МЛН ПАСС.КМ', NULL, 7, 2, 1, NULL),
(241, 'Пассажиропоток', '427', 'пасс.поток', NULL, 'ПАСС.ПОТОК', NULL, 7, 2, 1, NULL),
(242, 'Тонно-километр', '449', 'т.км', NULL, 'Т.КМ', NULL, 7, 2, 1, NULL),
(243, 'Тысяча тонно-километров', '450', '10^3 т.км', NULL, 'ТЫС Т.КМ', NULL, 7, 2, 1, NULL),
(244, 'Миллион тонно-километров', '451', '10^6 т. км', NULL, 'МЛН Т.КМ', NULL, 7, 2, 1, NULL),
(245, 'Тысяча наборов', '479', '10^3 набор', NULL, 'ТЫС НАБОР', NULL, 7, 2, 1, NULL),
(246, 'Грамм на киловатт-час', '510', 'г/кВт.ч', NULL, 'Г/КВТ.Ч', NULL, 7, 2, 1, NULL),
(247, 'Килограмм на гигакалорию', '511', 'кг/Гкал', NULL, 'КГ/ГИГАКАЛ', NULL, 7, 2, 1, NULL),
(248, 'Тонно-номер', '512', 'т.ном', NULL, 'Т.НОМ', NULL, 7, 2, 1, NULL),
(249, 'Автотонна', '513', 'авто т', NULL, 'АВТО Т', NULL, 7, 2, 1, NULL),
(250, 'Тонна тяги', '514', 'т.тяги', NULL, 'Т ТЯГИ', NULL, 7, 2, 1, NULL),
(251, 'Дедвейт-тонна', '515', 'дедвейт.т', NULL, 'ДЕДВЕЙТ.Т', NULL, 7, 2, 1, NULL),
(252, 'Тонно-танид', '516', 'т.танид', NULL, 'Т.ТАНИД', NULL, 7, 2, 1, NULL),
(253, 'Человек на квадратный метр', '521', 'чел/м2', NULL, 'ЧЕЛ/М2', NULL, 7, 2, 1, NULL),
(254, 'Человек на квадратный километр', '522', 'чел/км2', NULL, 'ЧЕЛ/КМ2', NULL, 7, 2, 1, NULL),
(255, 'Тонна в час', '534', 'т/ч', NULL, 'Т/Ч', NULL, 7, 2, 1, NULL),
(256, 'Тонна в сутки', '535', 'т/сут', NULL, 'Т/СУТ', NULL, 7, 2, 1, NULL),
(257, 'Тонна в смену', '536', 'т/смен', NULL, 'Т/СМЕН', NULL, 7, 2, 1, NULL),
(258, 'Тысяча тонн в сезон', '537', '10^3 т/сез', NULL, 'ТЫС Т/СЕЗ', NULL, 7, 2, 1, NULL),
(259, 'Тысяча тонн в год', '538', '10^3 т/год', NULL, 'ТЫС Т/ГОД', NULL, 7, 2, 1, NULL),
(260, 'Человеко-час', '539', 'чел.ч', NULL, 'ЧЕЛ.Ч', NULL, 7, 2, 1, NULL),
(261, 'Человеко-день', '540', 'чел.дн', NULL, 'ЧЕЛ.ДН', NULL, 7, 2, 1, NULL),
(262, 'Тысяча человеко-дней', '541', '10^3 чел.дн', NULL, 'ТЫС ЧЕЛ.ДН', NULL, 7, 2, 1, NULL),
(263, 'Тысяча человеко-часов', '542', '10^3 чел.ч', NULL, 'ТЫС ЧЕЛ.Ч', NULL, 7, 2, 1, NULL),
(264, 'Тысяча условных банок в смену', '543', '10^3 усл. банк/ смен', NULL, 'ТЫС УСЛ БАНК/СМЕН', NULL, 7, 2, 1, NULL),
(265, 'Миллион единиц в год', '544', '10^6 ед/год', NULL, 'МЛН ЕД/ГОД', NULL, 7, 2, 1, NULL),
(266, 'Посещение в смену', '545', 'посещ/смен', NULL, 'ПОСЕЩ/СМЕН', NULL, 7, 2, 1, NULL),
(267, 'Тысяча посещений в смену', '546', '10^3 посещ/смен', NULL, 'ТЫС ПОСЕЩ/ СМЕН', NULL, 7, 2, 1, NULL),
(268, 'Пара в смену', '547', 'пар/смен', NULL, 'ПАР/СМЕН', NULL, 7, 2, 1, NULL),
(269, 'Тысяча пар в смену', '548', '10^3 пар/смен', NULL, 'ТЫС ПАР/СМЕН', NULL, 7, 2, 1, NULL),
(270, 'Миллион тонн в год', '550', '10^6 т/год', NULL, 'МЛН Т/ГОД', NULL, 7, 2, 1, NULL),
(271, 'Тонна переработки в сутки', '552', 'т перераб/сут', NULL, 'Т ПЕРЕРАБ/СУТ', NULL, 7, 2, 1, NULL),
(272, 'Тысяча тонн переработки в сутки', '553', '10^3 т перераб/ сут', NULL, 'ТЫС Т ПЕРЕРАБ/СУТ', NULL, 7, 2, 1, NULL),
(273, 'Центнер переработки в сутки', '554', 'ц перераб/сут', NULL, 'Ц ПЕРЕРАБ/СУТ', NULL, 7, 2, 1, NULL),
(274, 'Тысяча центнеров переработки в сутки', '555', '10^3 ц перераб/ сут', NULL, 'ТЫС Ц ПЕРЕРАБ/СУТ', NULL, 7, 2, 1, NULL),
(275, 'Тысяча голов в год', '556', '10^3 гол/год', NULL, 'ТЫС ГОЛ/ГОД', NULL, 7, 2, 1, NULL),
(276, 'Миллион голов в год', '557', '10^6 гол/год', NULL, 'МЛН ГОЛ/ГОД', NULL, 7, 2, 1, NULL),
(277, 'Тысяча птицемест', '558', '10^3 птицемест', NULL, 'ТЫС ПТИЦЕМЕСТ', NULL, 7, 2, 1, NULL),
(278, 'Тысяча кур-несушек', '559', '10^3 кур. несуш', NULL, 'ТЫС КУР. НЕСУШ', NULL, 7, 2, 1, NULL),
(279, 'Минимальная заработная плата', '560', 'мин. заработн. плат', NULL, 'МИН ЗАРАБОТН ПЛАТ', NULL, 7, 2, 1, NULL),
(280, 'Тысяча тонн пара в час', '561', '10^3 т пар/ч', NULL, 'ТЫС Т ПАР/Ч', NULL, 7, 2, 1, NULL),
(281, 'Тысяча прядильных веретен', '562', '10^3 пряд.верет', NULL, 'ТЫС ПРЯД ВЕРЕТ', NULL, 7, 2, 1, NULL),
(282, 'Тысяча прядильных мест', '563', '10^3 пряд.мест', NULL, 'ТЫС ПРЯД МЕСТ', NULL, 7, 2, 1, NULL),
(283, 'Доза', '639', 'доз', NULL, 'ДОЗ', NULL, 7, 2, 1, NULL),
(284, 'Тысяча доз', '640', '10^3 доз', NULL, 'ТЫС ДОЗ', NULL, 7, 2, 1, NULL),
(285, 'Единица', '642', 'ед', NULL, 'ЕД', NULL, 7, 2, 1, NULL),
(286, 'Тысяча единиц', '643', '10^3 ед', NULL, 'ТЫС ЕД', NULL, 7, 2, 1, NULL),
(287, 'Миллион единиц', '644', '10^6 ед', NULL, 'МЛН ЕД', NULL, 7, 2, 1, NULL),
(288, 'Канал', '661', 'канал', NULL, 'КАНАЛ', NULL, 7, 2, 1, NULL),
(289, 'Тысяча комплектов', '673', '10^3 компл', NULL, 'ТЫС КОМПЛ', NULL, 7, 2, 1, NULL),
(290, 'Место', '698', 'мест', NULL, 'МЕСТ', NULL, 7, 2, 1, NULL),
(291, 'Тысяча мест', '699', '10^3 мест', NULL, 'ТЫС МЕСТ', NULL, 7, 2, 1, NULL),
(292, 'Тысяча номеров', '709', '10^3 ном', NULL, 'ТЫС НОМ', NULL, 7, 2, 1, NULL),
(293, 'Тысяча гектаров порций', '724', '10^3 га порц', NULL, 'ТЫС ГА ПОРЦ', NULL, 7, 2, 1, NULL),
(294, 'Тысяча пачек', '729', '10^3 пач', NULL, 'ТЫС ПАЧ', NULL, 7, 2, 1, NULL),
(295, 'Процент', '744', '%', NULL, 'ПРОЦ', NULL, 7, 2, 1, NULL),
(296, 'Промилле (0,1 процента)', '746', 'промилле', NULL, 'ПРОМИЛЛЕ', NULL, 7, 2, 1, NULL),
(297, 'Тысяча рулонов', '751', '10^3 рул', NULL, 'ТЫС РУЛ', NULL, 7, 2, 1, NULL),
(298, 'Тысяча станов', '761', '10^3 стан', NULL, 'ТЫС СТАН', NULL, 7, 2, 1, NULL),
(299, 'Станция', '762', 'станц', NULL, 'СТАНЦ', NULL, 7, 2, 1, NULL),
(300, 'Тысяча тюбиков', '775', '10^3 тюбик', NULL, 'ТЫС ТЮБИК', NULL, 7, 2, 1, NULL),
(301, 'Тысяча условных тубов', '776', '10^3 усл.туб', NULL, 'ТЫС УСЛ ТУБ', NULL, 7, 2, 1, NULL),
(302, 'Миллион упаковок', '779', '10^6 упак', NULL, 'МЛН УПАК', NULL, 7, 2, 1, NULL),
(303, 'Тысяча упаковок', '782', '10^3 упак', NULL, 'ТЫС УПАК', NULL, 7, 2, 1, NULL),
(304, 'Человек', '792', 'чел', NULL, 'ЧЕЛ', NULL, 7, 2, 1, NULL),
(305, 'Тысяча человек', '793', '10^3 чел', NULL, 'ТЫС ЧЕЛ', NULL, 7, 2, 1, NULL),
(306, 'Миллион человек', '794', '10^6 чел', NULL, 'МЛН ЧЕЛ', NULL, 7, 2, 1, NULL),
(307, 'Миллион экземпляров', '808', '10^6 экз', NULL, 'МЛН ЭКЗ', NULL, 7, 2, 1, NULL),
(308, 'Ячейка', '810', 'яч', NULL, 'ЯЧ', NULL, 7, 2, 1, NULL),
(309, 'Ящик', '812', 'ящ', NULL, 'ЯЩ', NULL, 7, 2, 1, NULL),
(310, 'Голова', '836', 'гол', NULL, 'ГОЛ', NULL, 7, 2, 1, NULL),
(311, 'Тысяча пар', '837', '10^3 пар', NULL, 'ТЫС ПАР', NULL, 7, 2, 1, NULL),
(312, 'Миллион пар', '838', '10^6 пар', NULL, 'МЛН ПАР', NULL, 7, 2, 1, NULL),
(313, 'Комплект', '839', 'компл', NULL, 'КОМПЛ', NULL, 7, 2, 1, NULL),
(314, 'Секция', '840', 'секц', NULL, 'СЕКЦ', NULL, 7, 2, 1, NULL),
(315, 'Бутылка', '868', 'бут', NULL, 'БУТ', NULL, 7, 2, 1, NULL),
(316, 'Тысяча бутылок', '869', '10^3 бут', NULL, 'ТЫС БУТ', NULL, 7, 2, 1, NULL),
(317, 'Ампула', '870', 'ампул', NULL, 'АМПУЛ', NULL, 7, 2, 1, NULL),
(318, 'Тысяча ампул', '871', '10^3 ампул', NULL, 'ТЫС АМПУЛ', NULL, 7, 2, 1, NULL),
(319, 'Флакон', '872', 'флак', NULL, 'ФЛАК', NULL, 7, 2, 1, NULL),
(320, 'Тысяча флаконов', '873', '10^3 флак', NULL, 'ТЫС ФЛАК', NULL, 7, 2, 1, NULL),
(321, 'Тысяча тубов', '874', '10^3 туб', NULL, 'ТЫС ТУБ', NULL, 7, 2, 1, NULL),
(322, 'Тысяча коробок', '875', '10^3 кор', NULL, 'ТЫС КОР', NULL, 7, 2, 1, NULL),
(323, 'Условная единица', '876', 'усл. ед', NULL, 'УСЛ ЕД', NULL, 7, 2, 1, NULL),
(324, 'Тысяча условных единиц', '877', '10^3 усл. ед', NULL, 'ТЫС УСЛ ЕД', NULL, 7, 2, 1, NULL),
(325, 'Миллион условных единиц', '878', '10^6 усл. ед', NULL, 'МЛН УСЛ ЕД', NULL, 7, 2, 1, NULL),
(326, 'Условная штука', '879', 'усл. шт', NULL, 'УСЛ ШТ', NULL, 7, 2, 1, NULL),
(327, 'Тысяча условных штук', '880', '10^3 усл. шт', NULL, 'ТЫС УСЛ ШТ', NULL, 7, 2, 1, NULL),
(328, 'Условная банка', '881', 'усл. банк', NULL, 'УСЛ БАНК', NULL, 7, 2, 1, NULL),
(329, 'Тысяча условных банок', '882', '10^3 усл. банк', NULL, 'ТЫС УСЛ БАНК', NULL, 7, 2, 1, NULL),
(330, 'Миллион условных банок', '883', '10^6 усл. банк', NULL, 'МЛН УСЛ БАНК', NULL, 7, 2, 1, NULL),
(331, 'Условный кусок', '884', 'усл. кус', NULL, 'УСЛ КУС', NULL, 7, 2, 1, NULL),
(332, 'Тысяча условных кусков', '885', '10^3 усл. кус', NULL, 'ТЫС УСЛ КУС', NULL, 7, 2, 1, NULL),
(333, 'Миллион условных кусков', '886', '10^6 усл. кус', NULL, 'МЛН УСЛ КУС', NULL, 7, 2, 1, NULL),
(334, 'Условный ящик', '887', 'усл. ящ', NULL, 'УСЛ ЯЩ', NULL, 7, 2, 1, NULL),
(335, 'Тысяча условных ящиков', '888', '10^3 усл. ящ', NULL, 'ТЫС УСЛ ЯЩ', NULL, 7, 2, 1, NULL),
(336, 'Условная катушка', '889', 'усл. кат', NULL, 'УСЛ КАТ', NULL, 7, 2, 1, NULL),
(337, 'Тысяча условных катушек', '890', '10^3 усл. кат', NULL, 'ТЫС УСЛ КАТ', NULL, 7, 2, 1, NULL),
(338, 'Условная плитка', '891', 'усл. плит', NULL, 'УСЛ ПЛИТ', NULL, 7, 2, 1, NULL),
(339, 'Тысяча условных плиток', '892', '10^3 усл. плит', NULL, 'ТЫС УСЛ ПЛИТ', NULL, 7, 2, 1, NULL),
(340, 'Условный кирпич', '893', 'усл. кирп', NULL, 'УСЛ КИРП', NULL, 7, 2, 1, NULL),
(341, 'Тысяча условных кирпичей', '894', '10^3 усл. кирп', NULL, 'ТЫС УСЛ КИРП', NULL, 7, 2, 1, NULL),
(342, 'Миллион условных кирпичей', '895', '10^6 усл. кирп', NULL, 'МЛН УСЛ КИРП', NULL, 7, 2, 1, NULL),
(343, 'Семья', '896', 'семей', NULL, 'СЕМЕЙ', NULL, 7, 2, 1, NULL),
(344, 'Тысяча семей', '897', '10^3 семей', NULL, 'ТЫС СЕМЕЙ', NULL, 7, 2, 1, NULL),
(345, 'Миллион семей', '898', '10^6 семей', NULL, 'МЛН СЕМЕЙ', NULL, 7, 2, 1, NULL),
(346, 'Домохозяйство', '899', 'домхоз', NULL, 'ДОМХОЗ', NULL, 7, 2, 1, NULL),
(347, 'Тысяча домохозяйств', '900', '10^3 домхоз', NULL, 'ТЫС ДОМХОЗ', NULL, 7, 2, 1, NULL),
(348, 'Миллион домохозяйств', '901', '10^6 домхоз', NULL, 'МЛН ДОМХОЗ', NULL, 7, 2, 1, NULL),
(349, 'Ученическое место', '902', 'учен. мест', NULL, 'УЧЕН МЕСТ', NULL, 7, 2, 1, NULL),
(350, 'Тысяча ученических мест', '903', '10^3 учен. мест', NULL, 'ТЫС УЧЕН МЕСТ', NULL, 7, 2, 1, NULL),
(351, 'Рабочее место', '904', 'раб. мест', NULL, 'РАБ МЕСТ', NULL, 7, 2, 1, NULL),
(352, 'Тысяча рабочих мест', '905', '10^3 раб. мест', NULL, 'ТЫС РАБ МЕСТ', NULL, 7, 2, 1, NULL),
(353, 'Посадочное место', '906', 'посад. мест', NULL, 'ПОСАД МЕСТ', NULL, 7, 2, 1, NULL),
(354, 'Тысяча посадочных мест', '907', '10^3 посад. мест', NULL, 'ТЫС ПОСАД МЕСТ', NULL, 7, 2, 1, NULL),
(355, 'Номер', '908', 'ном', NULL, 'НОМ', NULL, 7, 2, 1, NULL),
(356, 'Квартира', '909', 'кварт', NULL, 'КВАРТ', NULL, 7, 2, 1, NULL),
(357, 'Тысяча квартир', '910', '10^3 кварт', NULL, 'ТЫС КВАРТ', NULL, 7, 2, 1, NULL),
(358, 'Койка', '911', 'коек', NULL, 'КОЕК', NULL, 7, 2, 1, NULL),
(359, 'Тысяча коек', '912', '10^3 коек', NULL, 'ТЫС КОЕК', NULL, 7, 2, 1, NULL),
(360, 'Том книжного фонда', '913', 'том книжн. фонд', NULL, 'ТОМ КНИЖН ФОНД', NULL, 7, 2, 1, NULL),
(361, 'Тысяча томов книжного фонда', '914', '10^3 том. книжн. фонд', NULL, 'ТЫС ТОМ КНИЖН ФОНД', NULL, 7, 2, 1, NULL),
(362, 'Условный ремонт', '915', 'усл. рем', NULL, 'УСЛ РЕМ', NULL, 7, 2, 1, NULL),
(363, 'Условный ремонт в год', '916', 'усл. рем/год', NULL, 'УСЛ РЕМ/ГОД', NULL, 7, 2, 1, NULL),
(364, 'Смена', '917', 'смен', NULL, 'СМЕН', NULL, 7, 2, 1, NULL),
(365, 'Лист авторский', '918', 'л. авт', NULL, 'ЛИСТ АВТ', NULL, 7, 2, 1, NULL),
(366, 'Лист печатный', '920', 'л. печ', NULL, 'ЛИСТ ПЕЧ', NULL, 7, 2, 1, NULL),
(367, 'Лист учетно-издательский', '921', 'л. уч.-изд', NULL, 'ЛИСТ УЧ.ИЗД', NULL, 7, 2, 1, NULL),
(368, 'Знак', '922', 'знак', NULL, 'ЗНАК', NULL, 7, 2, 1, NULL),
(369, 'Слово', '923', 'слово', NULL, 'СЛОВО', NULL, 7, 2, 1, NULL),
(370, 'Символ', '924', 'символ', NULL, 'СИМВОЛ', NULL, 7, 2, 1, NULL),
(371, 'Условная труба', '925', 'усл. труб', NULL, 'УСЛ ТРУБ', NULL, 7, 2, 1, NULL),
(372, 'Тысяча пластин', '930', '10^3 пласт', NULL, 'ТЫС ПЛАСТ', NULL, 7, 2, 1, NULL),
(373, 'Миллион доз', '937', '10^6 доз', NULL, 'МЛН ДОЗ', NULL, 7, 2, 1, NULL),
(374, 'Миллион листов-оттисков', '949', '10^6 лист.оттиск', NULL, 'МЛН ЛИСТ.ОТТИСК', NULL, 7, 2, 1, NULL),
(375, 'Вагоно(машино)-день', '950', 'ваг (маш).дн', NULL, 'ВАГ (МАШ).ДН', NULL, 7, 2, 1, NULL),
(376, 'Тысяча вагоно-(машино)-часов', '951', '10^3 ваг (маш).ч', NULL, 'ТЫС ВАГ (МАШ).Ч', NULL, 7, 2, 1, NULL),
(377, 'Тысяча вагоно-(машино)-километров', '952', '10^3 ваг (маш).км', NULL, 'ТЫС ВАГ (МАШ).КМ', NULL, 7, 2, 1, NULL),
(378, 'Тысяча место-километров', '953', '10 ^3мест.км', NULL, 'ТЫС МЕСТ.КМ', NULL, 7, 2, 1, NULL),
(379, 'Вагоно-сутки', '954', 'ваг.сут', NULL, 'ВАГ.СУТ', NULL, 7, 2, 1, NULL),
(380, 'Тысяча поездо-часов', '955', '10^3 поезд.ч', NULL, 'ТЫС ПОЕЗД.Ч', NULL, 7, 2, 1, NULL),
(381, 'Тысяча поездо-километров', '956', '10^3 поезд.км', NULL, 'ТЫС ПОЕЗД.КМ', NULL, 7, 2, 1, NULL),
(382, 'Тысяча тонно-миль', '957', '10^3 т.миль', NULL, 'ТЫС Т.МИЛЬ', NULL, 7, 2, 1, NULL),
(383, 'Тысяча пассажиро-миль', '958', '10^3 пасс.миль', NULL, 'ТЫС ПАСС.МИЛЬ', NULL, 7, 2, 1, NULL),
(384, 'Автомобиле-день', '959', 'автомоб.дн', NULL, 'АВТОМОБ.ДН', NULL, 7, 2, 1, NULL),
(385, 'Тысяча автомобиле-тонно-дней', '960', '10^3 автомоб.т.дн', NULL, 'ТЫС АВТОМОБ.Т.ДН', NULL, 7, 2, 1, NULL),
(386, 'Тысяча автомобиле-часов', '961', '10^3 автомоб.ч', NULL, 'ТЫС АВТОМОБ.Ч', NULL, 7, 2, 1, NULL),
(387, 'Тысяча автомобиле-место-дней', '962', '10^3 автомоб.мест. дн', NULL, 'ТЫС АВТОМОБ.МЕСТ. ДН', NULL, 7, 2, 1, NULL),
(388, 'Приведенный час', '963', 'привед.ч', NULL, 'ПРИВЕД.Ч', NULL, 7, 2, 1, NULL),
(389, 'Самолето-километр', '964', 'самолет.км', NULL, 'САМОЛЕТ.КМ', NULL, 7, 2, 1, NULL),
(390, 'Тысяча километров', '965', '10^3 км', NULL, 'ТЫС КМ', NULL, 7, 2, 1, NULL),
(391, 'Тысяча тоннаже-рейсов', '966', '10^3 тоннаж. рейс', NULL, 'ТЫС ТОННАЖ. РЕЙС', NULL, 7, 2, 1, NULL),
(392, 'Миллион тонно-миль', '967', '10^6 т. миль', NULL, 'МЛН Т. МИЛЬ', NULL, 7, 2, 1, NULL),
(393, 'Миллион пассажиро-миль', '968', '10^6 пасс. миль', NULL, 'МЛН ПАСС. МИЛЬ', NULL, 7, 2, 1, NULL),
(394, 'Миллион тоннаже-миль', '969', '10^6 тоннаж. миль', NULL, 'МЛН ТОННАЖ. МИЛЬ', NULL, 7, 2, 1, NULL),
(395, 'Миллион пассажиро-место-миль', '970', '10^6 пасс. мест. миль', NULL, 'МЛН ПАСС. МЕСТ. МИЛЬ', NULL, 7, 2, 1, NULL),
(396, 'Кормо-день', '971', 'корм. дн', NULL, 'КОРМ. ДН', NULL, 7, 2, 1, NULL),
(397, 'Центнер кормовых единиц', '972', 'ц корм ед', NULL, 'Ц КОРМ ЕД', NULL, 7, 2, 1, NULL),
(398, 'Тысяча автомобиле-километров', '973', '10^3 автомоб. км', NULL, 'ТЫС АВТОМОБ. КМ', NULL, 7, 2, 1, NULL),
(399, 'Тысяча тоннаже-сут', '974', '10^3 тоннаж. сут', NULL, 'ТЫС ТОННАЖ. СУТ', NULL, 7, 2, 1, NULL),
(400, 'Суго-сутки', '975', 'суго. сут.', NULL, 'СУГО. СУТ', NULL, 7, 2, 1, NULL),
(401, 'Штук в 20-футовом эквиваленте (ДФЭ)', '976', 'штук в 20-футовом эквиваленте', NULL, 'ШТ В 20 ФУТ ЭКВИВ', NULL, 7, 2, 1, NULL),
(402, 'Канало-километр', '977', 'канал. км', NULL, 'КАНАЛ. КМ', NULL, 7, 2, 1, NULL),
(403, 'Канало-концы', '978', 'канал. конц', NULL, 'КАНАЛ. КОНЦ', NULL, 7, 2, 1, NULL),
(404, 'Тысяча экземпляров', '979', '10^3 экз', NULL, 'ТЫС ЭКЗ', NULL, 7, 2, 1, NULL),
(405, 'Тысяча долларов', '980', '10^3 доллар', NULL, 'ТЫС ДОЛЛАР', NULL, 7, 2, 1, NULL),
(406, 'Тысяча тонн кормовых единиц', '981', '10^3 корм ед', NULL, 'ТЫС Т КОРМ ЕД', NULL, 7, 2, 1, NULL),
(407, 'Миллион тонн кормовых единиц', '982', '10^6 корм ед', NULL, 'МЛН Т КОРМ ЕД', NULL, 7, 2, 1, NULL),
(408, 'Судо-сутки', '983', 'суд.сут', NULL, 'СУД.СУТ', NULL, 7, 2, 1, NULL),
(409, 'Гектометр', '017', NULL, 'hm', NULL, 'HMT', 1, 3, 1, NULL),
(410, 'Миля (уставная) (1609,344 м)', '045', NULL, 'mile', NULL, 'SMI', 1, 3, 1, NULL),
(411, 'Акр (4840 квадратных ярдов)', '077', NULL, 'acre', NULL, 'ACR', 2, 3, 1, NULL),
(412, 'Квадратная миля', '079', NULL, 'mile2', NULL, 'MIK', 2, 3, 1, NULL),
(413, 'Жидкостная унция СК (28,413 см3)', '135', NULL, 'fl oz (UK)', NULL, 'OZI', 3, 3, 1, NULL),
(414, 'Джилл СК (0,142065 дм3)', '136', NULL, 'gill (UK)', NULL, 'GII', 3, 3, 1, NULL),
(415, 'Пинта СК (0,568262 дм3)', '137', NULL, 'pt (UK)', NULL, 'PTI', 3, 3, 1, NULL),
(416, 'Кварта СК (1,136523 дм3)', '138', NULL, 'qt (UK)', NULL, 'QTI', 3, 3, 1, NULL),
(417, 'Галлон СК (4,546092 дм3)', '139', NULL, 'gal (UK)', NULL, 'GLI', 3, 3, 1, NULL),
(418, 'Бушель СК (36,36874 дм3)', '140', NULL, 'bu (UK)', NULL, 'BUI', 3, 3, 1, NULL),
(419, 'Жидкостная унция США (29,5735 см3)', '141', NULL, 'fl oz (US)', NULL, 'OZA', 3, 3, 1, NULL),
(420, 'Джилл США (11,8294 см3)', '142', NULL, 'gill (US)', NULL, 'GIA', 3, 3, 1, NULL),
(421, 'Жидкостная пинта США (0,473176 дм3)', '143', NULL, 'liq pt (US)', NULL, 'PTL', 3, 3, 1, NULL),
(422, 'Жидкостная кварта США (0,946353 дм3)', '144', NULL, 'liq qt (US)', NULL, 'QTL', 3, 3, 1, NULL),
(423, 'Жидкостный галлон США (3,78541 дм3)', '145', NULL, 'gal (US)', NULL, 'GLL', 3, 3, 1, NULL),
(424, 'Баррель (нефтяной) США (158,987 дм3)', '146', NULL, 'barrel (US)', NULL, 'BLL', 3, 3, 1, NULL),
(425, 'Сухая пинта США (0,55061 дм3)', '147', NULL, 'dry pt (US)', NULL, 'PTD', 3, 3, 1, NULL),
(426, 'Сухая кварта США (1,101221 дм3)', '148', NULL, 'dry qt (US)', NULL, 'QTD', 3, 3, 1, NULL),
(427, 'Сухой галлон США (4,404884 дм3)', '149', NULL, 'dry gal (US)', NULL, 'GLD', 3, 3, 1, NULL),
(428, 'Бушель США (35,2391 дм3)', '150', NULL, 'bu (US)', NULL, 'BUA', 3, 3, 1, NULL),
(429, 'Сухой баррель США (115,627 дм3)', '151', NULL, 'bbl (US)', NULL, 'BLD', 3, 3, 1, NULL),
(430, 'Стандарт', '152', NULL, '-', NULL, 'WSD', 3, 3, 1, NULL),
(431, 'Корд (3,63 м3)', '153', NULL, '-', NULL, 'WCD', 3, 3, 1, NULL),
(432, 'Тысячи бордфутов (2,36 м3)', '154', NULL, '-', NULL, 'MBF', 3, 3, 1, NULL),
(433, 'Нетто-регистровая тонна', '182', NULL, '-', NULL, 'NTT', 4, 3, 1, NULL),
(434, 'Обмерная (фрахтовая) тонна', '183', NULL, '-', NULL, 'SHT', 4, 3, 1, NULL),
(435, 'Водоизмещение', '184', NULL, '-', NULL, 'DPT', 4, 3, 1, NULL),
(436, 'Фунт СК, США (0,45359237 кг)', '186', NULL, 'lb', NULL, 'LBR', 4, 3, 1, NULL),
(437, 'Унция СК, США (28,349523 г)', '187', NULL, 'oz', NULL, 'ONZ', 4, 3, 1, NULL),
(438, 'Драхма СК (1,771745 г)', '188', NULL, 'dr', NULL, 'DRI', 4, 3, 1, NULL),
(439, 'Гран СК, США (64,798910 мг)', '189', NULL, 'gn', NULL, 'GRN', 4, 3, 1, NULL),
(440, 'Стоун СК (6,350293 кг)', '190', NULL, 'st', NULL, 'STI', 4, 3, 1, NULL),
(441, 'Квартер СК (12,700586 кг)', '191', NULL, 'qtr', NULL, 'QTR', 4, 3, 1, NULL),
(442, 'Центал СК (45,359237 кг)', '192', NULL, '-', NULL, 'CNT', 4, 3, 1, NULL),
(443, 'Центнер США (45,3592 кг)', '193', NULL, 'cwt', NULL, 'CWA', 4, 3, 1, NULL),
(444, 'Длинный центнер СК (50,802345 кг)', '194', NULL, 'cwt (UK)', NULL, 'CWI', 4, 3, 1, NULL),
(445, 'Короткая тонна СК, США (0,90718474 т) [2*]', '195', NULL, 'sht', NULL, 'STN', 4, 3, 1, NULL),
(446, 'Длинная тонна СК, США (1,0160469 т) [2*]', '196', NULL, 'lt', NULL, 'LTN', 4, 3, 1, NULL),
(447, 'Скрупул СК, США (1,295982 г)', '197', NULL, 'scr', NULL, 'SCR', 4, 3, 1, NULL),
(448, 'Пеннивейт СК, США (1,555174 г)', '198', NULL, 'dwt', NULL, 'DWT', 4, 3, 1, NULL),
(449, 'Драхма СК (3,887935 г)', '199', NULL, 'drm', NULL, 'DRM', 4, 3, 1, NULL),
(450, 'Драхма США (3,887935 г)', '200', NULL, '-', NULL, 'DRA', 4, 3, 1, NULL),
(451, 'Унция СК, США (31,10348 г); тройская унция', '201', NULL, 'apoz', NULL, 'APZ', 4, 3, 1, NULL),
(452, 'Тройский фунт США (373,242 г)', '202', NULL, '-', NULL, 'LBT', 4, 3, 1, NULL),
(453, 'Эффективная мощность (245,7 ватт)', '213', NULL, 'B.h.p.', NULL, 'BHP', 5, 3, 1, NULL),
(454, 'Британская тепловая единица (1,055 кДж)', '275', NULL, 'Btu', NULL, 'BTU', 5, 3, 1, NULL),
(455, 'Гросс (144 шт.)', '638', NULL, 'gr; 144', NULL, 'GRO', 7, 3, 1, NULL),
(456, 'Большой гросс (12 гроссов)', '731', NULL, '1728', NULL, 'GGR', 7, 3, 1, NULL);
INSERT INTO `class_unit` (`id`, `name`, `number_code`, `rus_name1`, `eng_name1`, `rus_name2`, `eng_name2`, `class_unit_group_id`, `class_unit_type_id`, `visible`, `comment`) VALUES
(457, 'Короткий стандарт (7200 единиц)', '738', NULL, '-', NULL, 'SST', 7, 3, 1, NULL),
(458, 'Галлон спирта установленной крепости', '835', NULL, '-', NULL, 'PGL', 7, 3, 1, NULL),
(459, 'Международная единица', '851', NULL, '-', NULL, 'NIU', 7, 3, 1, NULL),
(460, 'Сто международных единиц', '853', NULL, '-', NULL, 'HIU', 7, 3, 1, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы 'class_unit_group'
--

CREATE TABLE class_unit_group (
  id tinyint(4) NOT NULL auto_increment COMMENT 'pk',
  `name` varchar(255) NOT NULL COMMENT 'Наименование группы',
  PRIMARY KEY  (id),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Группы единиц измерения' AUTO_INCREMENT=8 ;


--
-- Дамп данных таблицы `class_unit_group`
--

INSERT INTO `class_unit_group` (`id`, `name`) VALUES
(6, 'Единицы времени'),
(1, 'Единицы длины'),
(4, 'Единицы массы'),
(3, 'Единицы объема'),
(2, 'Единицы площади'),
(5, 'Технические единицы'),
(7, 'Экономические единицы');

-- --------------------------------------------------------

--
-- Структура таблицы 'class_unit_type'
--

CREATE TABLE class_unit_type (
  id tinyint(4) NOT NULL auto_increment COMMENT 'pk',
  `name` varchar(255) NOT NULL COMMENT 'Наименование раздела/приложения',
  PRIMARY KEY  (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Разделы/приложения, в которые включены единицы измерения' AUTO_INCREMENT=4 ;

--
-- Дамп данных таблицы `class_unit_type`
--

INSERT INTO `class_unit_type` (`id`, `name`) VALUES
(1, 'Международные единицы измерения, включенные в ЕСКК'),
(2, 'Национальные единицы измерения, включенные в ЕСКК'),
(3, 'Международные единицы измерения, не включенные в ЕСКК');



--
-- Ограничения внешнего ключа таблицы `class_unit`
--
ALTER TABLE `class_unit`
  ADD CONSTRAINT class_unit_ibfk_2 FOREIGN KEY (class_unit_type_id) REFERENCES class_unit_type (id),
  ADD CONSTRAINT class_unit_ibfk_1 FOREIGN KEY (class_unit_group_id) REFERENCES class_unit_group (id);



START TRANSACTION;
TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (302);

COMMIT;