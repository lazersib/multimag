SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `multimag`
--

-- --------------------------------------------------------

--
-- Структура таблицы `articles`
--

CREATE TABLE IF NOT EXISTS `articles` (
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

-- --------------------------------------------------------

--
-- Структура таблицы `async_workers_tasks`
--

CREATE TABLE IF NOT EXISTS `async_workers_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task` varchar(32) NOT NULL,
  `description` varchar(128) NOT NULL,
  `needrun` tinyint(4) NOT NULL DEFAULT '1',
  `textstatus` varchar(128) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `needrun` (`needrun`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `attachments`
--

CREATE TABLE IF NOT EXISTS `attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_filename` varchar(64) NOT NULL,
  `comment` varchar(256) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Вложения' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `class_country`
--

CREATE TABLE IF NOT EXISTS `class_country` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'pk',
  `name` varchar(255) NOT NULL COMMENT 'Наименование страны',
  `full_name` varchar(255) DEFAULT NULL COMMENT 'Полное наименование страны',
  `number_code` varchar(4) NOT NULL COMMENT 'Числовой код',
  `alfa2` varchar(2) NOT NULL COMMENT 'Код альфа-2',
  `alfa3` varchar(3) NOT NULL COMMENT 'Код альфа-3',
  `visible` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Видимость',
  `comment` varchar(255) DEFAULT NULL COMMENT 'Комментарий',
  PRIMARY KEY (`id`),
  UNIQUE KEY `number_code` (`number_code`),
  UNIQUE KEY `alfa2` (`alfa2`),
  UNIQUE KEY `alfa3` (`alfa3`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Общероссийский классификатор стран мира ОКСМ' AUTO_INCREMENT=249 ;

--
-- Дамп данных таблицы `class_country`
--

INSERT INTO `class_country` (`id`, `name`, `full_name`, `number_code`, `alfa2`, `alfa3`, `visible`, `comment`) VALUES
(1, 'АФГАНИСТАН', 'Переходное Исламское Государство Афганистан', '004', 'AF', 'AFG', 1, NULL),
(2, 'АЛБАНИЯ', 'Республика Албания', '008', 'AL', 'ALB', 1, NULL),
(3, 'АНТАРКТИДА', NULL, '010', 'AQ', 'ATA', 1, NULL),
(4, 'АЛЖИР', 'Алжирская Народная Демократическая Республика', '012', 'DZ', 'DZA', 1, NULL),
(5, 'АМЕРИКАНСКОЕ САМОА', NULL, '016', 'AS', 'ASM', 1, NULL),
(6, 'АНДОРРА', 'Княжество Андорра', '020', 'AD', 'AND', 1, NULL),
(7, 'АНГОЛА', 'Республика Ангола', '024', 'AO', 'AGO', 1, NULL),
(8, 'АНТИГУА И БАРБУДА', NULL, '028', 'AG', 'ATG', 1, NULL),
(9, 'АЗЕРБАЙДЖАН', 'Республика Азербайджан', '031', 'AZ', 'AZE', 1, NULL),
(10, 'АРГЕНТИНА', 'Аргентинская Республика', '032', 'AR', 'ARG', 1, NULL),
(11, 'АВСТРАЛИЯ', NULL, '036', 'AU', 'AUS', 1, NULL),
(12, 'АВСТРИЯ', 'Австрийская Республика', '040', 'AT', 'AUT', 1, NULL),
(13, 'БАГАМЫ', 'Содружество Багамы', '044', 'BS', 'BHS', 1, NULL),
(14, 'БАХРЕЙН', 'Королевство Бахрейн', '048', 'BH', 'BHR', 1, NULL),
(15, 'БАНГЛАДЕШ', 'Народная Республика Бангладеш', '050', 'BD', 'BGD', 1, NULL),
(16, 'АРМЕНИЯ', 'Республика Армения', '051', 'AM', 'ARM', 1, NULL),
(17, 'БАРБАДОС', NULL, '052', 'BB', 'BRB', 1, NULL),
(18, 'БЕЛЬГИЯ', 'Королевство Бельгии', '056', 'BE', 'BEL', 1, NULL),
(19, 'БЕРМУДЫ', NULL, '060', 'BM', 'BMU', 1, NULL),
(20, 'БУТАН', 'Королевство Бутан', '064', 'BT', 'BTN', 1, NULL),
(21, 'БОЛИВИЯ, МНОГОНАЦИОНАЛЬНОЕ ГОСУДАРСТВО', 'Многонациональное Государство Боливия', '068', 'BO', 'BOL', 1, NULL),
(22, 'БОСНИЯ И ГЕРЦЕГОВИНА', NULL, '070', 'BA', 'BIH', 1, NULL),
(23, 'БОТСВАНА', 'Республика Ботсвана', '072', 'BW', 'BWA', 1, NULL),
(24, 'ОСТРОВ БУВЕ', NULL, '074', 'BV', 'BVT', 1, NULL),
(25, 'БРАЗИЛИЯ', 'Федеративная Республика Бразилия', '076', 'BR', 'BRA', 1, NULL),
(26, 'БЕЛИЗ', NULL, '084', 'BZ', 'BLZ', 1, NULL),
(27, 'БРИТАНСКАЯ ТЕРРИТОРИЯ В ИНДИЙСКОМ ОКЕАНЕ', NULL, '086', 'IO', 'IOT', 1, NULL),
(28, 'СОЛОМОНОВЫ ОСТРОВА', NULL, '090', 'SB', 'SLB', 1, NULL),
(29, 'ВИРГИНСКИЕ ОСТРОВА, БРИТАНСКИЕ', 'Британские Виргинские острова', '092', 'VG', 'VGB', 1, NULL),
(30, 'БРУНЕЙ-ДАРУССАЛАМ', NULL, '096', 'BN', 'BRN', 1, NULL),
(31, 'БОЛГАРИЯ', 'Республика Болгария', '100', 'BG', 'BGR', 1, NULL),
(32, 'МЬЯНМА', 'Союз Мьянма', '104', 'MM', 'MMR', 1, NULL),
(33, 'БУРУНДИ', 'Республика Бурунди', '108', 'BI', 'BDI', 1, NULL),
(34, 'БЕЛАРУСЬ', 'Республика Беларусь', '112', 'BY', 'BLR', 1, NULL),
(35, 'КАМБОДЖА', 'Королевство Камбоджа', '116', 'KH', 'KHM', 1, NULL),
(36, 'КАМЕРУН', 'Республика Камерун', '120', 'CM', 'CMR', 1, NULL),
(37, 'КАНАДА', NULL, '124', 'CA', 'CAN', 1, NULL),
(38, 'КАБО-ВЕРДЕ', 'Республика Кабо-Верде', '132', 'CV', 'CPV', 1, NULL),
(39, 'ОСТРОВА КАЙМАН', NULL, '136', 'KY', 'CYM', 1, NULL),
(40, 'ЦЕНТРАЛЬНО-АФРИКАНСКАЯ РЕСПУБЛИКА', NULL, '140', 'CF', 'CAF', 1, NULL),
(41, 'ШРИ-ЛАНКА', 'Демократическая Социалистическая Республика Шри-Ланка', '144', 'LK', 'LKA', 1, NULL),
(42, 'ЧАД', 'Республика Чад', '148', 'TD', 'TCD', 1, NULL),
(43, 'ЧИЛИ', 'Республика Чили', '152', 'CL', 'CHL', 1, NULL),
(44, 'КИТАЙ', 'Китайская Народная Республика', '156', 'CN', 'CHN', 1, NULL),
(45, 'ТАЙВАНЬ (КИТАЙ)', NULL, '158', 'TW', 'TWN', 1, NULL),
(46, 'ОСТРОВ РОЖДЕСТВА', NULL, '162', 'CX', 'CXR', 1, NULL),
(47, 'КОКОСОВЫЕ (КИЛИНГ) ОСТРОВА', NULL, '166', 'CC', 'CCK', 1, NULL),
(48, 'КОЛУМБИЯ', 'Республика Колумбия', '170', 'CO', 'COL', 1, NULL),
(49, 'КОМОРЫ', 'Союз Коморы', '174', 'KM', 'COM', 1, NULL),
(50, 'МАЙОТТА', NULL, '175', 'YT', 'MYT', 1, NULL),
(51, 'КОНГО', 'Республика Конго', '178', 'CG', 'COG', 1, NULL),
(52, 'КОНГО, ДЕМОКРАТИЧЕСКАЯ РЕСПУБЛИКА', 'Демократическая Республика Конго', '180', 'CD', 'COD', 1, NULL),
(53, 'ОСТРОВА КУКА', NULL, '184', 'CK', 'COK', 1, NULL),
(54, 'КОСТА-РИКА', 'Республика Коста-Рика', '188', 'CR', 'CRI', 1, NULL),
(55, 'ХОРВАТИЯ', 'Республика Хорватия', '191', 'HR', 'HRV', 1, NULL),
(56, 'КУБА', 'Республика Куба', '192', 'CU', 'CUB', 1, NULL),
(57, 'КИПР', 'Республика Кипр', '196', 'CY', 'CYP', 1, NULL),
(58, 'ЧЕШСКАЯ РЕСПУБЛИКА', NULL, '203', 'CZ', 'CZE', 1, NULL),
(59, 'БЕНИН', 'Республика Бенин', '204', 'BJ', 'BEN', 1, NULL),
(60, 'ДАНИЯ', 'Королевство Дания', '208', 'DK', 'DNK', 1, NULL),
(61, 'ДОМИНИКА', 'Содружество Доминики', '212', 'DM', 'DMA', 1, NULL),
(62, 'ДОМИНИКАНСКАЯ РЕСПУБЛИКА', NULL, '214', 'DO', 'DOM', 1, NULL),
(63, 'ЭКВАДОР', 'Республика Эквадор', '218', 'EC', 'ECU', 1, NULL),
(64, 'ЭЛЬ-САЛЬВАДОР', 'Республика Эль-Сальвадор', '222', 'SV', 'SLV', 1, NULL),
(65, 'ЭКВАТОРИАЛЬНАЯ ГВИНЕЯ', 'Республика Экваториальная Гвинея', '226', 'GQ', 'GNQ', 1, NULL),
(66, 'ЭФИОПИЯ', 'Федеративная Демократическая Республика Эфиопия', '231', 'ET', 'ETH', 1, NULL),
(67, 'ЭРИТРЕЯ', NULL, '232', 'ER', 'ERI', 1, NULL),
(68, 'ЭСТОНИЯ', 'Эстонская Республика', '233', 'EE', 'EST', 1, NULL),
(69, 'ФАРЕРСКИЕ ОСТРОВА', NULL, '234', 'FO', 'FRO', 1, NULL),
(70, 'ФОЛКЛЕНДСКИЕ ОСТРОВА (МАЛЬВИНСКИЕ)', NULL, '238', 'FK', 'FLK', 1, NULL),
(71, 'ЮЖНАЯ ДЖОРДЖИЯ И ЮЖНЫЕ САНДВИЧЕВЫ ОСТРОВА', NULL, '239', 'GS', 'SGS', 1, NULL),
(72, 'ФИДЖИ', 'Республика Островов Фиджи', '242', 'FJ', 'FJI', 1, NULL),
(73, 'ФИНЛЯНДИЯ', 'Финляндская Республика', '246', 'FI', 'FIN', 1, NULL),
(74, 'ЭЛАНДСКИЕ ОСТРОВА', NULL, '248', 'АХ', 'ALA', 1, NULL),
(75, 'ФРАНЦИЯ', 'Французская Республика', '250', 'FR', 'FRA', 1, NULL),
(76, 'ФРАНЦУЗСКАЯ ГВИАНА', NULL, '254', 'GF', 'GUF', 1, NULL),
(77, 'ФРАНЦУЗСКАЯ ПОЛИНЕЗИЯ', NULL, '258', 'PF', 'PYF', 1, NULL),
(78, 'ФРАНЦУЗСКИЕ ЮЖНЫЕ ТЕРРИТОРИИ', NULL, '260', 'TF', 'ATF', 1, NULL),
(79, 'ДЖИБУТИ', 'Республика Джибути', '262', 'DJ', 'DJI', 1, NULL),
(80, 'ГАБОН', 'Габонская Республика', '266', 'GA', 'GAB', 1, NULL),
(81, 'ГРУЗИЯ', NULL, '268', 'GE', 'GEO', 1, NULL),
(82, 'ГАМБИЯ', 'Республика Гамбия', '270', 'GM', 'GMB', 1, NULL),
(83, 'ПАЛЕСТИНСКАЯ ТЕРРИТОРИЯ, ОККУПИРОВАННАЯ', 'Оккупированная Палестинская территория', '275', 'PS', 'PSE', 1, NULL),
(84, 'ГЕРМАНИЯ', 'Федеративная Республика Германия', '276', 'DE', 'DEU', 1, NULL),
(85, 'ГАНА', 'Республика Гана', '288', 'GH', 'GHA', 1, NULL),
(86, 'ГИБРАЛТАР', NULL, '292', 'GI', 'GIB', 1, NULL),
(87, 'КИРИБАТИ', 'Республика Кирибати', '296', 'KI', 'KIR', 1, NULL),
(88, 'ГРЕЦИЯ', 'Греческая Республика', '300', 'GR', 'GRC', 1, NULL),
(89, 'ГРЕНЛАНДИЯ', NULL, '304', 'GL', 'GRL', 1, NULL),
(90, 'ГРЕНАДА', NULL, '308', 'GD', 'GRD', 1, NULL),
(91, 'ГВАДЕЛУПА', NULL, '312', 'GP', 'GLP', 1, NULL),
(92, 'ГУАМ', NULL, '316', 'GU', 'GUM', 1, NULL),
(93, 'ГВАТЕМАЛА', 'Республика Гватемала', '320', 'GT', 'GTM', 1, NULL),
(94, 'ГВИНЕЯ', 'Гвинейская Республика', '324', 'GN', 'GIN', 1, NULL),
(95, 'ГАЙАНА', 'Республика Гайана', '328', 'GY', 'GUY', 1, NULL),
(96, 'ГАИТИ', 'Республика Гаити', '332', 'HT', 'HTI', 1, NULL),
(97, 'ОСТРОВ ХЕРД И ОСТРОВА МАКДОНАЛЬД', NULL, '334', 'HM', 'HMD', 1, NULL),
(98, 'ПАПСКИЙ ПРЕСТОЛ (ГОСУДАРСТВО - ГОРОД ВАТИКАН)', NULL, '336', 'VA', 'VAT', 1, NULL),
(99, 'ГОНДУРАС', 'Республика Гондурас', '340', 'HN', 'HND', 1, NULL),
(100, 'ГОНКОНГ', 'Специальный административный регион Китая Гонконг', '344', 'HK', 'HKG', 1, NULL),
(101, 'ВЕНГРИЯ', 'Венгерская Республика', '348', 'HU', 'HUN', 1, NULL),
(102, 'ИСЛАНДИЯ', 'Республика Исландия', '352', 'IS', 'ISL', 1, NULL),
(103, 'ИНДИЯ', 'Республика Индия', '356', 'IN', 'IND', 1, NULL),
(104, 'ИНДОНЕЗИЯ', 'Республика Индонезия', '360', 'ID', 'IDN', 1, NULL),
(105, 'ИРАН, ИСЛАМСКАЯ РЕСПУБЛИКА', 'Исламская Республика Иран', '364', 'IR', 'IRN', 1, NULL),
(106, 'ИРАК', 'Республика Ирак', '368', 'IQ', 'IRQ', 1, NULL),
(107, 'ИРЛАНДИЯ', NULL, '372', 'IE', 'IRL', 1, NULL),
(108, 'ИЗРАИЛЬ', 'Государство Израиль', '376', 'IL', 'ISR', 1, NULL),
(109, 'ИТАЛИЯ', 'Итальянская Республика', '380', 'IT', 'ITA', 1, NULL),
(110, 'КОТ Д''ИВУАР', 'Республика Кот д''Ивуар', '384', 'CI', 'CIV', 1, NULL),
(111, 'ЯМАЙКА', NULL, '388', 'JM', 'JAM', 1, NULL),
(112, 'ЯПОНИЯ', NULL, '392', 'JP', 'JPN', 1, NULL),
(113, 'КАЗАХСТАН', 'Республика Казахстан', '398', 'KZ', 'KAZ', 1, NULL),
(114, 'ИОРДАНИЯ', 'Иорданское Хашимитское Королевство', '400', 'JO', 'JOR', 1, NULL),
(115, 'КЕНИЯ', 'Республика Кения', '404', 'KE', 'KEN', 1, NULL),
(116, 'КОРЕЯ, НАРОДНО-ДЕМОКРАТИЧЕСКАЯ РЕСПУБЛИКА', 'Корейская Народно-Демократическая Республика', '408', 'KP', 'PRK', 1, NULL),
(117, 'КОРЕЯ, РЕСПУБЛИКА', 'Республика Корея', '410', 'KR', 'KOR', 1, NULL),
(118, 'КУВЕЙТ', 'Государство Кувейт', '414', 'KW', 'KWT', 1, NULL),
(119, 'КИРГИЗИЯ', 'Киргизская Республика', '417', 'KG', 'KGZ', 1, NULL),
(120, 'ЛАОССКАЯ НАРОДНО-ДЕМОКРАТИЧЕСКАЯ РЕСПУБЛИКА', NULL, '418', 'LA', 'LAO', 1, NULL),
(121, 'ЛИВАН', 'Ливанская Республика', '422', 'LB', 'LBN', 1, NULL),
(122, 'ЛЕСОТО', 'Королевство Лесото', '426', 'LS', 'LSO', 1, NULL),
(123, 'ЛАТВИЯ', 'Латвийская Республика', '428', 'LV', 'LVA', 1, NULL),
(124, 'ЛИБЕРИЯ', 'Республика Либерия', '430', 'LR', 'LBR', 1, NULL),
(125, 'ЛИВИЙСКАЯ АРАБСКАЯ ДЖАМАХИРИЯ', 'Социалистическая Народная Ливийская Арабская Джамахирия', '434', 'LY', 'LBY', 1, NULL),
(126, 'ЛИХТЕНШТЕЙН', 'Княжество Лихтенштейн', '438', 'LI', 'LIE', 1, NULL),
(127, 'ЛИТВА', 'Литовская Республика', '440', 'LT', 'LTU', 1, NULL),
(128, 'ЛЮКСЕМБУРГ', 'Великое Герцогство Люксембург', '442', 'LU', 'LUX', 1, NULL),
(129, 'МАКАО', 'Специальный административный регион Китая Макао', '446', 'MO', 'MAC', 1, NULL),
(130, 'МАДАГАСКАР', 'Республика Мадагаскар', '450', 'MG', 'MDG', 1, NULL),
(131, 'МАЛАВИ', 'Республика Малави', '454', 'MW', 'MWI', 1, NULL),
(132, 'МАЛАЙЗИЯ', NULL, '458', 'MY', 'MYS', 1, NULL),
(133, 'МАЛЬДИВЫ', 'Мальдивская Республика', '462', 'MV', 'MDV', 1, NULL),
(134, 'МАЛИ', 'Республика Мали', '466', 'ML', 'MLI', 1, NULL),
(135, 'МАЛЬТА', 'Республика Мальта', '470', 'MT', 'MLT', 1, NULL),
(136, 'МАРТИНИКА', NULL, '474', 'MQ', 'MTQ', 1, NULL),
(137, 'МАВРИТАНИЯ', 'Исламская Республика Мавритания', '478', 'MR', 'MRT', 1, NULL),
(138, 'МАВРИКИЙ', 'Республика Маврикий', '480', 'MU', 'MUS', 1, NULL),
(139, 'МЕКСИКА', 'Мексиканские Соединенные Штаты', '484', 'MX', 'MEX', 1, NULL),
(140, 'МОНАКО', 'Княжество Монако', '492', 'MC', 'MCO', 1, NULL),
(141, 'МОНГОЛИЯ', NULL, '496', 'MN', 'MNG', 1, NULL),
(142, 'МОЛДОВА, РЕСПУБЛИКА', 'Республика Молдова', '498', 'MD', 'MDA', 1, NULL),
(143, 'ЧЕРНОГОРИЯ', NULL, '499', 'ME', 'MNE', 1, NULL),
(144, 'МОНТСЕРРАТ', NULL, '500', 'MS', 'MSR', 1, NULL),
(145, 'МАРОККО', 'Королевство Марокко', '504', 'MA', 'MAR', 1, NULL),
(146, 'МОЗАМБИК', 'Республика Мозамбик', '508', 'MZ', 'MOZ', 1, NULL),
(147, 'ОМАН', 'Султанат Оман', '512', 'OM', 'OMN', 1, NULL),
(148, 'НАМИБИЯ', 'Республика Намибия', '516', 'NA', 'NAM', 1, NULL),
(149, 'НАУРУ', 'Республика Науру', '520', 'NR', 'NRU', 1, NULL),
(150, 'НЕПАЛ', 'Федеративная Демократическая Республика Непал', '524', 'NP', 'NPL', 1, NULL),
(151, 'НИДЕРЛАНДЫ', 'Королевство Нидерландов', '528', 'NL', 'NLD', 1, NULL),
(152, 'НИДЕРЛАНДСКИЕ АНТИЛЫ', NULL, '530', 'AN', 'ANT', 1, NULL),
(153, 'АРУБА', NULL, '533', 'AW', 'ABW', 1, NULL),
(154, 'НОВАЯ КАЛЕДОНИЯ', NULL, '540', 'NC', 'NCL', 1, NULL),
(155, 'ВАНУАТУ', 'Республика Вануату', '548', 'VU', 'VUT', 1, NULL),
(156, 'НОВАЯ ЗЕЛАНДИЯ', NULL, '554', 'NZ', 'NZL', 1, NULL),
(157, 'НИКАРАГУА', 'Республика Никарагуа', '558', 'NI', 'NIC', 1, NULL),
(158, 'НИГЕР', 'Республика Нигер', '562', 'NE', 'NER', 1, NULL),
(159, 'НИГЕРИЯ', 'Федеративная Республика Нигерия', '566', 'NG', 'NGA', 1, NULL),
(160, 'НИУЭ', 'Республика Ниуэ', '570', 'NU', 'NIU', 1, NULL),
(161, 'ОСТРОВ НОРФОЛК', NULL, '574', 'NF', 'NFK', 1, NULL),
(162, 'НОРВЕГИЯ', 'Королевство Норвегия', '578', 'NO', 'NOR', 1, NULL),
(163, 'СЕВЕРНЫЕ МАРИАНСКИЕ ОСТРОВА', 'Содружество Северных Марианских островов', '580', 'MP', 'MNP', 1, NULL),
(164, 'МАЛЫЕ ТИХООКЕАНСКИЕ ОТДАЛЕННЫЕ ОСТРОВА СОЕДИНЕННЫХ ШТАТОВ', NULL, '581', 'UM', 'UMI', 1, NULL),
(165, 'МИКРОНЕЗИЯ, ФЕДЕРАТИВНЫЕ ШТАТЫ', 'Федеративные штаты Микронезии', '583', 'FM', 'FSM', 1, NULL),
(166, 'МАРШАЛЛОВЫ ОСТРОВА', 'Республика Маршалловы Острова', '584', 'MH', 'MHL', 1, NULL),
(167, 'ПАЛАУ', 'Республика Палау', '585', 'PW', 'PLW', 1, NULL),
(168, 'ПАКИСТАН', 'Исламская Республика Пакистан', '586', 'PK', 'PAK', 1, NULL),
(169, 'ПАНАМА', 'Республика Панама', '591', 'PA', 'PAN', 1, NULL),
(170, 'ПАПУА-НОВАЯ ГВИНЕЯ', NULL, '598', 'PG', 'PNG', 1, NULL),
(171, 'ПАРАГВАЙ', 'Республика Парагвай', '600', 'PY', 'PRY', 1, NULL),
(172, 'ПЕРУ', 'Республика Перу', '604', 'PE', 'PER', 1, NULL),
(173, 'ФИЛИППИНЫ', 'Республика Филиппины', '608', 'PH', 'PHL', 1, NULL),
(174, 'ПИТКЕРН', NULL, '612', 'PN', 'PCN', 1, NULL),
(175, 'ПОЛЬША', 'Республика Польша', '616', 'PL', 'POL', 1, NULL),
(176, 'ПОРТУГАЛИЯ', 'Португальская Республика', '620', 'PT', 'PRT', 1, NULL),
(177, 'ГВИНЕЯ-БИСАУ', 'Республика Гвинея-Бисау', '624', 'GW', 'GNB', 1, NULL),
(178, 'ТИМОР-ЛЕСТЕ', 'Демократическая Республика Тимор-Лесте', '626', 'TL', 'TLS', 1, NULL),
(179, 'ПУЭРТО-РИКО', NULL, '630', 'PR', 'PRI', 1, NULL),
(180, 'КАТАР', 'Государство Катар', '634', 'QA', 'QAT', 1, NULL),
(181, 'РЕЮНЬОН', NULL, '638', 'RE', 'REU', 1, NULL),
(182, 'РУМЫНИЯ', NULL, '642', 'RO', 'ROU', 1, NULL),
(183, 'РОССИЯ', 'Российская Федерация', '643', 'RU', 'RUS', 1, NULL),
(184, 'РУАНДА', 'Руандийская Республика', '646', 'RW', 'RWA', 1, NULL),
(185, 'СЕН-БАРТЕЛЕМИ', NULL, '652', 'BL', 'BLM', 1, NULL),
(186, 'СВЯТАЯ ЕЛЕНА', NULL, '654', 'SH', 'SHN', 1, NULL),
(187, 'СЕНТ-КИТС И НЕВИС', NULL, '659', 'KN', 'KNA', 1, NULL),
(188, 'АНГИЛЬЯ', NULL, '660', 'AI', 'AIA', 1, NULL),
(189, 'СЕНТ-ЛЮСИЯ', NULL, '662', 'LC', 'LCA', 1, NULL),
(190, 'СЕН-МАРТЕН', NULL, '663', 'MF', 'MAF', 1, NULL),
(191, 'СЕН-ПЬЕР И МИКЕЛОН', NULL, '666', 'PM', 'SPM', 1, NULL),
(192, 'СЕНТ-ВИНСЕНТ И ГРЕНАДИНЫ', NULL, '670', 'VC', 'VCT', 1, NULL),
(193, 'САН-МАРИНО', 'Республика Сан-Марино', '674', 'SM', 'SMR', 1, NULL),
(194, 'САН-ТОМЕ И ПРИНСИПИ', 'Демократическая Республика Сан-Томе и Принсипи', '678', 'ST', 'STP', 1, NULL),
(195, 'САУДОВСКАЯ АРАВИЯ', 'Королевство Саудовская Аравия', '682', 'SA', 'SAU', 1, NULL),
(196, 'СЕНЕГАЛ', 'Республика Сенегал', '686', 'SN', 'SEN', 1, NULL),
(197, 'СЕРБИЯ', 'Республика Сербия', '688', 'RS', 'SRB', 1, NULL),
(198, 'СЕЙШЕЛЫ', 'Республика Сейшелы', '690', 'SC', 'SYC', 1, NULL),
(199, 'СЬЕРРА-ЛЕОНЕ', 'Республика Сьерра-Леоне', '694', 'SL', 'SLE', 1, NULL),
(200, 'СИНГАПУР', 'Республика Сингапур', '702', 'SG', 'SGP', 1, NULL),
(201, 'СЛОВАКИЯ', 'Словацкая Республика', '703', 'SK', 'SVK', 1, NULL),
(202, 'ВЬЕТНАМ', 'Социалистическая Республика Вьетнам', '704', 'VN', 'VNM', 1, NULL),
(203, 'СЛОВЕНИЯ', 'Республика Словения', '705', 'SI', 'SVN', 1, NULL),
(204, 'СОМАЛИ', 'Сомалийская Республика', '706', 'SO', 'SOM', 1, NULL),
(205, 'ЮЖНАЯ АФРИКА', 'Южно-Африканская Республика', '710', 'ZA', 'ZAF', 1, NULL),
(206, 'ЗИМБАБВЕ', 'Республика Зимбабве', '716', 'ZW', 'ZWE', 1, NULL),
(207, 'ИСПАНИЯ', 'Королевство Испания', '724', 'ES', 'ESP', 1, NULL),
(208, 'ЗАПАДНАЯ САХАРА', NULL, '732', 'EH', 'ESH', 1, NULL),
(209, 'СУДАН', 'Республика Судан', '736', 'SD', 'SDN', 1, NULL),
(210, 'СУРИНАМ', 'Республика Суринам', '740', 'SR', 'SUR', 1, NULL),
(211, 'ШПИЦБЕРГЕН И ЯН МАЙЕН', NULL, '744', 'SJ', 'SJM', 1, NULL),
(212, 'СВАЗИЛЕНД', 'Королевство Свазиленд', '748', 'SZ', 'SWZ', 1, NULL),
(213, 'ШВЕЦИЯ', 'Королевство Швеция', '752', 'SE', 'SWE', 1, NULL),
(214, 'ШВЕЙЦАРИЯ', 'Швейцарская Конфедерация', '756', 'CH', 'CHE', 1, NULL),
(215, 'СИРИЙСКАЯ АРАБСКАЯ РЕСПУБЛИКА', NULL, '760', 'SY', 'SYR', 1, NULL),
(216, 'ТАДЖИКИСТАН', 'Республика Таджикистан', '762', 'TJ', 'TJK', 1, NULL),
(217, 'ТАИЛАНД', 'Королевство Таиланд', '764', 'TH', 'THA', 1, NULL),
(218, 'ТОГО', 'Тоголезская Республика', '768', 'TG', 'TGO', 1, NULL),
(219, 'ТОКЕЛАУ', NULL, '772', 'TK', 'TKL', 1, NULL),
(220, 'ТОНГА', 'Королевство Тонга', '776', 'TO', 'TON', 1, NULL),
(221, 'ТРИНИДАД И ТОБАГО', 'Республика Тринидад и Тобаго', '780', 'TT', 'TTO', 1, NULL),
(222, 'ОБЪЕДИНЕННЫЕ АРАБСКИЕ ЭМИРАТЫ', NULL, '784', 'AE', 'ARE', 1, NULL),
(223, 'ТУНИС', 'Тунисская Республика', '788', 'TN', 'TUN', 1, NULL),
(224, 'ТУРЦИЯ', 'Турецкая Республика', '792', 'TR', 'TUR', 1, NULL),
(225, 'ТУРКМЕНИЯ', 'Туркменистан', '795', 'TM', 'TKM', 1, NULL),
(226, 'ОСТРОВА ТЕРКС И КАЙКОС', NULL, '796', 'TC', 'TCA', 1, NULL),
(227, 'ТУВАЛУ', NULL, '798', 'TV', 'TUV', 1, NULL),
(228, 'УГАНДА', 'Республика Уганда', '800', 'UG', 'UGA', 1, NULL),
(229, 'УКРАИНА', NULL, '804', 'UA', 'UKR', 1, NULL),
(230, 'РЕСПУБЛИКА МАКЕДОНИЯ', NULL, '807', 'MK', 'MKD', 1, NULL),
(231, 'ЕГИПЕТ', 'Арабская Республика Египет', '818', 'EG', 'EGY', 1, NULL),
(232, 'СОЕДИНЕННОЕ КОРОЛЕВСТВО', 'Соединенное Королевство Великобритании и Северной Ирландии', '826', 'GB', 'GBR', 1, NULL),
(233, 'ГЕРНСИ', NULL, '831', 'GG', 'GGY', 1, NULL),
(234, 'ДЖЕРСИ', NULL, '832', 'JE', 'JEY', 1, NULL),
(235, 'ОСТРОВ МЭН', NULL, '833', 'IM', 'IMN', 1, NULL),
(236, 'ТАНЗАНИЯ, ОБЪЕДИНЕННАЯ РЕСПУБЛИКА', 'Объединенная Республика Танзания', '834', 'TZ', 'TZA', 1, NULL),
(237, 'СОЕДИНЕННЫЕ ШТАТЫ', 'Соединенные Штаты Америки', '840', 'US', 'USA', 1, NULL),
(238, 'ВИРГИНСКИЕ ОСТРОВА, США', 'Виргинские острова Соединенных Штатов', '850', 'VI', 'VIR', 1, NULL),
(239, 'БУРКИНА-ФАСО', NULL, '854', 'BF', 'BFA', 1, NULL),
(240, 'УРУГВАЙ', 'Восточная Республика Уругвай', '858', 'UY', 'URY', 1, NULL),
(241, 'УЗБЕКИСТАН', 'Республика Узбекистан', '860', 'UZ', 'UZB', 1, NULL),
(242, 'ВЕНЕСУЭЛА БОЛИВАРИАНСКАЯ РЕСПУБЛИКА', 'Боливарианская Республика Венесуэла', '862', 'VE', 'VEN', 1, NULL),
(243, 'УОЛЛИС И ФУТУНА', NULL, '876', 'WF', 'WLF', 1, NULL),
(244, 'САМОА', 'Независимое Государство Самоа', '882', 'WS', 'WSM', 1, NULL),
(245, 'ЙЕМЕН', 'Йеменская Республика', '887', 'YE', 'YEM', 1, NULL),
(246, 'ЗАМБИЯ', 'Республика Замбия', '894', 'ZM', 'ZMB', 1, NULL),
(247, 'АБХАЗИЯ', 'Республика Абхазия', '895', 'AB', 'ABH', 1, NULL),
(248, 'ЮЖНАЯ ОСЕТИЯ', 'Республика Южная Осетия', '896', 'OS', 'OST', 1, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `class_unit`
--

CREATE TABLE IF NOT EXISTS `class_unit` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'pk',
  `name` varchar(255) NOT NULL COMMENT 'Наименование единицы измерения',
  `number_code` varchar(5) NOT NULL COMMENT 'Код',
  `rus_name1` varchar(50) DEFAULT NULL COMMENT 'Условное обозначение национальное',
  `eng_name1` varchar(50) DEFAULT NULL COMMENT 'Условное обозначение международное',
  `rus_name2` varchar(50) DEFAULT NULL COMMENT 'Кодовое буквенное обозначение национальное',
  `eng_name2` varchar(50) DEFAULT NULL COMMENT 'Кодовое буквенное обозначение международное',
  `class_unit_group_id` tinyint(4) NOT NULL COMMENT 'Группа единиц измерения',
  `class_unit_type_id` tinyint(4) NOT NULL COMMENT 'Раздел/приложение в которое входит единица измерения',
  `visible` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Видимость',
  `comment` varchar(255) DEFAULT NULL COMMENT 'Комментарий',
  PRIMARY KEY (`id`),
  UNIQUE KEY `number_code` (`number_code`),
  KEY `class_unit_group_id` (`class_unit_group_id`),
  KEY `class_unit_type_id` (`class_unit_type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Общероссийский классификатор единиц измерения ОКЕИ' AUTO_INCREMENT=431 ;

--
-- Дамп данных таблицы `class_unit`
--

INSERT INTO `class_unit` (`id`, `name`, `number_code`, `rus_name1`, `eng_name1`, `rus_name2`, `eng_name2`, `class_unit_group_id`, `class_unit_type_id`, `visible`, `comment`) VALUES
(1, 'Миллиметр', '003', 'мм', 'mm', 'ММ', 'MMT', 1, 1, 1, NULL),
(2, 'Сантиметр', '004', 'см', 'cm', 'СМ', 'CMT', 1, 1, 1, NULL),
(4, 'Метр', '006', 'м', 'm', 'М', 'MTR', 1, 1, 1, NULL),
(9, 'Ярд (0,9144 м)', '043', 'ярд', 'yd', 'ЯРД', 'YRD', 1, 1, 1, NULL),
(14, 'Квадратный метр', '055', 'м2', 'm2', 'М2', 'MTK', 2, 1, 1, NULL),
(24, 'Литр; кубический дециметр', '112', 'л; дм3', 'I; L; dm^3', 'Л; ДМ3', 'LTR; DMQ', 3, 1, 1, NULL),
(37, 'Килограмм', '166', 'кг', 'kg', 'КГ', 'KGM', 4, 1, 1, NULL),
(114, 'Бобина', '616', 'боб', '-', 'БОБ', 'NBB', 7, 1, 1, NULL),
(119, 'Изделие', '657', 'изд', '-', 'ИЗД', 'NAR', 7, 1, 1, NULL),
(121, 'Набор', '704', 'набор', '-', 'НАБОР', 'SET', 7, 1, 1, NULL),
(122, 'Пара (2 шт.)', '715', 'пар', 'pr; 2', 'ПАР', 'NPR', 7, 1, 1, NULL),
(128, 'Рулон', '736', 'рул', '-', 'РУЛ', 'NPL', 7, 1, 1, NULL),
(132, 'Упаковка', '778', 'упак', '-', 'УПАК', 'NMP', 7, 1, 1, NULL),
(135, 'Штука', '796', 'шт', 'pc; 1', 'ШТ', 'PCE; NMB', 7, 1, 1, NULL),
(155, 'Погонный метр', '018', 'пог. м', NULL, 'ПОГ М', NULL, 1, 2, 1, NULL),
(219, 'Байт', '255', 'бай', NULL, 'БАЙТ', NULL, 5, 2, 1, NULL),
(231, 'Рубль', '383', 'руб', NULL, 'РУБ', NULL, 7, 2, 1, NULL),
(257, 'Тонна в смену', '536', 'т/смен', NULL, 'Т/СМЕН', NULL, 7, 2, 1, NULL),
(260, 'Человеко-час', '539', 'чел.ч', NULL, 'ЧЕЛ.Ч', NULL, 7, 2, 1, NULL),
(285, 'Единица', '642', 'ед', NULL, 'ЕД', NULL, 7, 2, 1, NULL),
(290, 'Место', '698', 'мест', NULL, 'МЕСТ', NULL, 7, 2, 1, NULL),
(304, 'Человек', '792', 'чел', NULL, 'ЧЕЛ', NULL, 7, 2, 1, NULL),
(309, 'Ящик', '812', 'ящ', NULL, 'ЯЩ', NULL, 7, 2, 1, NULL),
(312, 'Миллион пар', '838', '10^6 пар', NULL, 'МЛН ПАР', NULL, 7, 2, 1, NULL),
(313, 'Комплект', '839', 'компл', NULL, 'КОМПЛ', NULL, 7, 2, 1, NULL),
(323, 'Условная единица', '876', 'усл. ед', NULL, 'УСЛ ЕД', NULL, 7, 2, 1, NULL),
(364, 'Смена', '917', 'смен', NULL, 'СМЕН', NULL, 7, 2, 1, NULL),
(430, 'Стандарт', '152', NULL, '-', NULL, 'WSD', 3, 3, 1, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `class_unit_group`
--

CREATE TABLE IF NOT EXISTS `class_unit_group` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT COMMENT 'pk',
  `name` varchar(255) NOT NULL COMMENT 'Наименование группы',
  PRIMARY KEY (`id`),
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
-- Структура таблицы `class_unit_type`
--

CREATE TABLE IF NOT EXISTS `class_unit_type` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT COMMENT 'pk',
  `name` varchar(255) NOT NULL COMMENT 'Наименование раздела/приложения',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Разделы/приложения, в которые включены единицы измерения' AUTO_INCREMENT=4 ;

--
-- Дамп данных таблицы `class_unit_type`
--

INSERT INTO `class_unit_type` (`id`, `name`) VALUES
(1, 'Международные единицы измерения, включенные в ЕСКК'),
(2, 'Национальные единицы измерения, включенные в ЕСКК'),
(3, 'Международные единицы измерения, не включенные в ЕСКК');

-- --------------------------------------------------------

--
-- Структура таблицы `comments`
--

CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `object_name` varchar(16) NOT NULL COMMENT 'Имя(тип) объекта комментирования',
  `object_id` int(11) NOT NULL COMMENT 'ID объекта комментирования',
  `autor_name` varchar(16) NOT NULL COMMENT 'Имя автора (анонимного)',
  `autor_email` varchar(32) NOT NULL COMMENT 'Электронная почта анонимного автора',
  `autor_id` int(11) NOT NULL COMMENT 'UID автора',
  `text` text NOT NULL COMMENT 'Текст коментария',
  `rate` tinyint(4) NOT NULL COMMENT 'Оценка объекта (0-5)',
  `ip` varchar(16) NOT NULL,
  `user_agent` varchar(128) NOT NULL,
  `response` varchar(512) NOT NULL COMMENT 'Ответ администрации',
  `responser` int(11) NOT NULL COMMENT 'Автор ответа',
  PRIMARY KEY (`id`),
  KEY `object_name` (`object_name`),
  KEY `object_id` (`object_id`),
  KEY `rate` (`rate`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Коментарии к товарам, новостям, статьям и пр.' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `counter`
--

CREATE TABLE IF NOT EXISTS `counter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` bigint(20) NOT NULL DEFAULT '0',
  `ip` varchar(30) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `agent` varchar(128) NOT NULL DEFAULT '',
  `refer` varchar(512) NOT NULL DEFAULT '',
  `file` varchar(20) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `query` varchar(128) NOT NULL DEFAULT '',
  UNIQUE KEY `id` (`id`),
  KEY `time` (`date`),
  KEY `ip` (`ip`),
  KEY `agent` (`agent`),
  KEY `refer` (`refer`(333)),
  KEY `file` (`file`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `currency`
--

CREATE TABLE IF NOT EXISTS `currency` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(10) NOT NULL,
  `coeff` decimal(8,4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Дамп данных таблицы `currency`
--

INSERT INTO `currency` (`id`, `name`, `coeff`) VALUES
(1, 'RUR', 1.0000),
(2, 'USD', 30.7194);

-- --------------------------------------------------------

--
-- Структура таблицы `db_version`
--

CREATE TABLE IF NOT EXISTS `db_version` (
  `version` int(11) NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Дамп данных таблицы `db_version`
--

INSERT INTO `db_version` (`version`) VALUES
(456);

-- --------------------------------------------------------

--
-- Структура таблицы `doc_agent`
--

CREATE TABLE IF NOT EXISTS `doc_agent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `fullname` varchar(200) NOT NULL,
  `tel` varchar(64) NOT NULL,
  `sms_phone` varchar(16) NOT NULL,
  `fax_phone` varchar(16) NOT NULL,
  `alt_phone` varchar(16) NOT NULL,
  `adres` varchar(300) NOT NULL,
  `gruzopol` varchar(300) NOT NULL,
  `inn` varchar(24) NOT NULL,
  `dir_fio` varchar(128) NOT NULL,
  `dir_fio_r` varchar(128) NOT NULL,
  `pfio` text NOT NULL,
  `pdol` text NOT NULL,
  `okevd` varchar(5) NOT NULL,
  `okpo` varchar(10) NOT NULL,
  `rs` varchar(22) NOT NULL,
  `bank` varchar(50) NOT NULL,
  `ks` varchar(50) NOT NULL,
  `bik` varchar(12) NOT NULL,
  `group` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `type` tinyint(4) NOT NULL DEFAULT '1',
  `pasp_num` varchar(12) NOT NULL,
  `pasp_date` date NOT NULL,
  `pasp_kem` varchar(60) NOT NULL,
  `comment` text NOT NULL,
  `no_mail` tinyint(4) NOT NULL,
  `responsible` int(11) NOT NULL,
  `data_sverki` date NOT NULL,
  `dishonest` tinyint(4) NOT NULL,
  `p_agent` int(11) DEFAULT NULL COMMENT 'Подчинение другому агенту',
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`),
  KEY `fullname` (`fullname`),
  KEY `tel` (`tel`),
  KEY `inn` (`inn`),
  KEY `type` (`type`),
  KEY `pasp_num` (`pasp_num`,`pasp_date`,`pasp_kem`),
  KEY `p_agent` (`p_agent`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='pcomment - printable comment' AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `doc_agent`
--

INSERT INTO `doc_agent` (`id`, `name`, `fullname`, `tel`, `sms_phone`, `fax_phone`, `alt_phone`, `adres`, `gruzopol`, `inn`, `dir_fio`, `dir_fio_r`, `pfio`, `pdol`, `okevd`, `okpo`, `rs`, `bank`, `ks`, `bik`, `group`, `email`, `type`, `pasp_num`, `pasp_date`, `pasp_kem`, `comment`, `no_mail`, `responsible`, `data_sverki`, `dishonest`, `p_agent`) VALUES
(1, 'Частное лицо', 'Частное лицо', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 1, '', 1, '', '0000-00-00', '', '', 1, 0, '0000-00-00', 0, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `doc_agent_dov`
--

CREATE TABLE IF NOT EXISTS `doc_agent_dov` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ag_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `name2` varchar(50) NOT NULL,
  `surname` varchar(50) NOT NULL,
  `range` varchar(50) NOT NULL,
  `pasp_ser` varchar(5) NOT NULL,
  `pasp_num` varchar(10) NOT NULL,
  `pasp_kem` varchar(100) NOT NULL,
  `pasp_data` varchar(15) NOT NULL,
  `mark_del` bigint(20) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `ag_id` (`ag_id`),
  KEY `name` (`name`),
  KEY `name2` (`name2`),
  KEY `surname` (`surname`),
  KEY `range` (`range`),
  KEY `pasp_ser` (`pasp_ser`),
  KEY `pasp_num` (`pasp_num`),
  KEY `pasp_kem` (`pasp_kem`),
  KEY `pasp_data` (`pasp_data`),
  KEY `mark_del` (`mark_del`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `doc_agent_dov`
--

INSERT INTO `doc_agent_dov` (`id`, `ag_id`, `name`, `name2`, `surname`, `range`, `pasp_ser`, `pasp_num`, `pasp_kem`, `pasp_data`, `mark_del`) VALUES
(1, 1, 'Тест', 'Тестович', 'Тестов', '', '', '', '', '', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `doc_agent_group`
--

CREATE TABLE IF NOT EXISTS `doc_agent_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `pid` int(11) NOT NULL,
  `desc` varchar(100) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `doc_agent_group`
--

INSERT INTO `doc_agent_group` (`id`, `name`, `pid`, `desc`) VALUES
(1, 'Покупатели', 0, '');

-- --------------------------------------------------------

--
-- Структура таблицы `doc_base`
--

CREATE TABLE IF NOT EXISTS `doc_base` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group` int(11) NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL,
  `vc` varchar(32) NOT NULL COMMENT 'Код производителя',
  `country` int(11) DEFAULT NULL,
  `desc` text NOT NULL,
  `cost` double(10,2) NOT NULL DEFAULT '0.00',
  `stock` tinyint(1) NOT NULL,
  `proizv` varchar(20) NOT NULL,
  `likvid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cost_date` datetime NOT NULL,
  `pos_type` tinyint(4) NOT NULL,
  `hidden` tinyint(4) NOT NULL,
  `no_export_yml` tinyint(4) NOT NULL,
  `unit` int(11) NOT NULL COMMENT 'Единица измерения',
  `warranty` int(11) NOT NULL,
  `warranty_type` tinyint(4) NOT NULL,
  `meta_description` varchar(256) NOT NULL,
  `meta_keywords` varchar(128) NOT NULL,
  `title_tag` varchar(128) NOT NULL,
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `buy_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uniq` (`group`,`name`),
  KEY `group` (`group`),
  KEY `name` (`name`),
  KEY `cost_date` (`cost_date`),
  KEY `hidden` (`hidden`),
  KEY `unit` (`unit`),
  KEY `stock` (`stock`),
  KEY `likvid` (`likvid`),
  KEY `country` (`country`),
  KEY `create_time` (`create_time`),
  KEY `buy_time` (`buy_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT AUTO_INCREMENT=3 ;

--
-- Дамп данных таблицы `doc_base`
--

INSERT INTO `doc_base` (`id`, `group`, `name`, `vc`, `country`, `desc`, `cost`, `stock`, `proizv`, `likvid`, `cost_date`, `pos_type`, `hidden`, `no_export_yml`, `unit`, `warranty`, `warranty_type`, `meta_description`, `meta_keywords`, `title_tag`, `create_time`, `buy_time`) VALUES
(1, 1, 'Первый товар', '', 1, '', 0.00, 0, '', 0.00, '0000-00-00 00:00:00', 0, 0, 0, 260, 0, 0, '', '', '', '2012-12-23 23:51:33', '1970-01-01 00:00:00'),
(2, 2, 'Первая услуга', '', 1, '', 12.00, 0, '', 0.00, '0000-00-00 00:00:00', 1, 0, 0, 285, 0, 0, '', '', '', '2012-12-23 23:52:31', '1970-01-01 00:00:00');

-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_attachments`
--

CREATE TABLE IF NOT EXISTS `doc_base_attachments` (
  `pos_id` int(11) NOT NULL,
  `attachment_id` int(11) NOT NULL,
  UNIQUE KEY `uni` (`pos_id`,`attachment_id`),
  KEY `attachment_id` (`attachment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Присоединённые файлы';

-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_cnt`
--

CREATE TABLE IF NOT EXISTS `doc_base_cnt` (
  `id` int(11) NOT NULL,
  `sklad` tinyint(4) NOT NULL,
  `cnt` double NOT NULL,
  `mesto` varchar(32) NOT NULL,
  `mincnt` varchar(8) NOT NULL,
  PRIMARY KEY (`id`,`sklad`),
  KEY `cnt` (`cnt`),
  KEY `mesto` (`mesto`),
  KEY `mincnt` (`mincnt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_cost`
--

CREATE TABLE IF NOT EXISTS `doc_base_cost` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pos_id` int(11) NOT NULL,
  `cost_id` int(11) NOT NULL,
  `type` varchar(5) NOT NULL,
  `value` decimal(8,2) NOT NULL,
  `accuracy` tinyint(4) NOT NULL,
  `direction` tinyint(4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uniq` (`pos_id`,`cost_id`),
  KEY `group_id` (`pos_id`),
  KEY `cost_id` (`cost_id`),
  KEY `value` (`value`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_dop`
--

CREATE TABLE IF NOT EXISTS `doc_base_dop` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) DEFAULT '0',
  `d_int` double NOT NULL DEFAULT '0',
  `d_ext` double NOT NULL DEFAULT '0',
  `size` double NOT NULL DEFAULT '0',
  `mass` double NOT NULL DEFAULT '0',
  `analog` varchar(20) NOT NULL,
  `koncost` double NOT NULL DEFAULT '0',
  `strana` varchar(20) NOT NULL,
  `tranzit` tinyint(4) NOT NULL,
  `ntd` varchar(32) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `type` (`type`),
  KEY `d_int` (`d_int`),
  KEY `d_ext` (`d_ext`),
  KEY `size` (`size`),
  KEY `mass` (`mass`),
  KEY `analog` (`analog`),
  KEY `koncost` (`koncost`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_dop_type`
--

CREATE TABLE IF NOT EXISTS `doc_base_dop_type` (
  `id` int(11) NOT NULL,
  `name` varchar(70) NOT NULL,
  `desc` text NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_gparams`
--

CREATE TABLE IF NOT EXISTS `doc_base_gparams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_img`
--

CREATE TABLE IF NOT EXISTS `doc_base_img` (
  `pos_id` int(11) NOT NULL,
  `img_id` int(11) NOT NULL,
  `default` tinyint(4) NOT NULL,
  UNIQUE KEY `pos_id` (`pos_id`,`img_id`),
  KEY `default` (`default`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_kompl`
--

CREATE TABLE IF NOT EXISTS `doc_base_kompl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pos_id` int(11) NOT NULL COMMENT 'id наименования',
  `kompl_id` int(11) NOT NULL COMMENT 'id комплектующего',
  `cnt` double NOT NULL COMMENT 'количество',
  UNIQUE KEY `id` (`id`),
  KEY `kompl_id` (`kompl_id`),
  KEY `cnt` (`cnt`),
  KEY `pos_id` (`pos_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Комплектующие - из чего состоит эта позиция' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_params`
--

CREATE TABLE IF NOT EXISTS `doc_base_params` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `param` varchar(32) NOT NULL,
  `type` varchar(8) NOT NULL,
  `pgroup_id` int(11) NOT NULL,
  `system` tinyint(4) NOT NULL COMMENT 'Служебный параметр. Нигде не отображается.',
  `ym_assign` varchar(128) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `param` (`param`),
  KEY `pgroup_id` (`pgroup_id`),
  KEY `ym_assign` (`ym_assign`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_pcollections_list`
--

CREATE TABLE IF NOT EXISTS `doc_base_pcollections_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Наборы свойств складской номенклатуры' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_pcollections_set`
--

CREATE TABLE IF NOT EXISTS `doc_base_pcollections_set` (
  `collection_id` int(11) NOT NULL,
  `param_id` int(11) NOT NULL,
  UNIQUE KEY `uniq` (`collection_id`,`param_id`),
  KEY `collection_id` (`collection_id`),
  KEY `param_id` (`param_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Список параметров в наборе';

-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_values`
--

CREATE TABLE IF NOT EXISTS `doc_base_values` (
  `id` int(11) NOT NULL,
  `param_id` int(11) NOT NULL,
  `value` varchar(32) NOT NULL,
  `intval` int(11) NOT NULL,
  `doubleval` double NOT NULL,
  `strval` varchar(512) NOT NULL,
  UNIQUE KEY `unique` (`id`,`param_id`),
  KEY `id` (`id`),
  KEY `param` (`param_id`),
  KEY `value` (`value`),
  KEY `intval` (`intval`),
  KEY `doubleval` (`doubleval`),
  KEY `strval` (`strval`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `doc_cost`
--

CREATE TABLE IF NOT EXISTS `doc_cost` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(25) NOT NULL,
  `type` varchar(5) NOT NULL,
  `value` decimal(8,2) NOT NULL COMMENT 'Значение цены',
  `vid` tinyint(4) NOT NULL COMMENT 'Вид цены определяет места её использования',
  `accuracy` int(11) NOT NULL,
  `direction` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Дамп данных таблицы `doc_cost`
--

INSERT INTO `doc_cost` (`id`, `name`, `type`, `value`, `vid`, `accuracy`, `direction`) VALUES
(1, 'Для зарегистрированных', 'pp', 0.00, -1, 2, 0),
(2, 'По умолчанию', 'pp', 0.00, 1, 2, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `doc_dopdata`
--

CREATE TABLE IF NOT EXISTS `doc_dopdata` (
  `doc` int(11) NOT NULL,
  `param` varchar(20) NOT NULL,
  `value` varchar(150) NOT NULL,
  UNIQUE KEY `doc` (`doc`,`param`),
  KEY `value` (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `doc_group`
--

CREATE TABLE IF NOT EXISTS `doc_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `desc` text NOT NULL,
  `pid` int(11) NOT NULL,
  `hidelevel` tinyint(4) NOT NULL,
  `no_export_yml` tinyint(4) NOT NULL,
  `printname` varchar(64) NOT NULL,
  `meta_description` varchar(256) NOT NULL,
  `meta_keywords` varchar(128) NOT NULL,
  `title_tag` varchar(128) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `pid` (`pid`),
  KEY `hidelevel` (`hidelevel`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Дамп данных таблицы `doc_group`
--

INSERT INTO `doc_group` (`id`, `name`, `desc`, `pid`, `hidelevel`, `no_export_yml`, `printname`, `meta_description`, `meta_keywords`, `title_tag`) VALUES
(1, 'Товары', '', 0, 0, 0, '', '', '', ''),
(2, 'Услуги', '', 0, 0, 0, '', '', '', '');

-- --------------------------------------------------------

--
-- Структура таблицы `doc_group_cost`
--

CREATE TABLE IF NOT EXISTS `doc_group_cost` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `cost_id` int(11) NOT NULL,
  `type` varchar(5) NOT NULL,
  `value` decimal(8,2) NOT NULL,
  `accuracy` tinyint(4) NOT NULL,
  `direction` tinyint(4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uniq` (`group_id`,`cost_id`),
  KEY `group_id` (`group_id`),
  KEY `cost_id` (`cost_id`),
  KEY `value` (`value`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `doc_group_params`
--

CREATE TABLE IF NOT EXISTS `doc_group_params` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) DEFAULT NULL,
  `param_id` int(11) DEFAULT NULL,
  `show_in_filter` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq` (`group_id`,`param_id`),
  KEY `fk_doc_group_params_doc_group1` (`group_id`),
  KEY `fk_doc_group_params_doc_base_params1` (`param_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `doc_img`
--

CREATE TABLE IF NOT EXISTS `doc_img` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` varchar(4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `doc_kassa`
--

CREATE TABLE IF NOT EXISTS `doc_kassa` (
  `ids` varchar(50) CHARACTER SET latin1 NOT NULL,
  `num` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `ballance` decimal(10,2) NOT NULL,
  `bik` varchar(20) NOT NULL,
  `rs` varchar(30) NOT NULL,
  `ks` varchar(30) NOT NULL,
  `firm_id` int(11) NOT NULL,
  UNIQUE KEY `ids` (`ids`,`num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `doc_kassa`
--

INSERT INTO `doc_kassa` (`ids`, `num`, `name`, `ballance`, `bik`, `rs`, `ks`, `firm_id`) VALUES
('bank', 1, 'Основной банк', 0.00, '000000000', '00000000000000000000', '00000000000000000000', 0),
('kassa', 1, 'Основная касса', 0.00, '', '', '', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `doc_list`
--

CREATE TABLE IF NOT EXISTS `doc_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(4) NOT NULL DEFAULT '0',
  `agent` int(11) NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  `date` bigint(20) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ok` bigint(20) NOT NULL DEFAULT '0',
  `sklad` tinyint(4) NOT NULL DEFAULT '0',
  `kassa` tinyint(4) NOT NULL DEFAULT '0',
  `bank` tinyint(4) NOT NULL DEFAULT '0',
  `user` int(11) NOT NULL DEFAULT '0',
  `altnum` int(11) NOT NULL,
  `subtype` varchar(5) NOT NULL,
  `sum` decimal(10,2) NOT NULL DEFAULT '0.00',
  `nds` int(11) NOT NULL DEFAULT '0',
  `p_doc` int(11) NOT NULL,
  `mark_del` bigint(20) NOT NULL,
  `firm_id` int(11) NOT NULL DEFAULT '1',
  `err_flag` tinyint(4) NOT NULL DEFAULT '0',
  `contract` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `type` (`type`),
  KEY `fio` (`agent`),
  KEY `date` (`date`),
  KEY `altnum` (`altnum`),
  KEY `p_doc` (`p_doc`),
  KEY `ok` (`ok`),
  KEY `sklad` (`sklad`),
  KEY `user` (`user`),
  KEY `subtype` (`subtype`),
  KEY `mark_del` (`mark_del`),
  KEY `firm_id` (`firm_id`),
  KEY `kassa` (`kassa`,`bank`),
  KEY `contract` (`contract`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `doc_list_pos`
--

CREATE TABLE IF NOT EXISTS `doc_list_pos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doc` int(11) NOT NULL DEFAULT '0',
  `tovar` int(11) NOT NULL DEFAULT '0',
  `cnt` double NOT NULL DEFAULT '0',
  `gtd` varchar(32) NOT NULL,
  `comm` varchar(128) NOT NULL,
  `cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `page` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uni_pos` (`doc`,`tovar`,`page`),
  KEY `doc` (`doc`),
  KEY `tovar` (`tovar`),
  KEY `page` (`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `doc_log`
--

CREATE TABLE IF NOT EXISTS `doc_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `ip` varchar(20) NOT NULL,
  `object` varchar(20) NOT NULL,
  `object_id` int(11) NOT NULL,
  `motion` varchar(100) NOT NULL,
  `desc` varchar(500) NOT NULL,
  `time` datetime NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `user` (`user`),
  KEY `motion` (`motion`),
  KEY `time` (`time`),
  KEY `desc` (`desc`(333)),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `doc_rasxodi`
--

CREATE TABLE IF NOT EXISTS `doc_rasxodi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `adm` tinyint(4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`),
  KEY `adm` (`adm`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Статьи расходов' AUTO_INCREMENT=15 ;

--
-- Дамп данных таблицы `doc_rasxodi`
--

INSERT INTO `doc_rasxodi` (`id`, `name`, `adm`) VALUES
(0, 'Прочие расходы', 1),
(1, 'Аренда офиса, склада', 1),
(2, 'Средства под отчёт', 1),
(3, 'Канцелярские товары, хозяйственные материалы', 1),
(4, 'Зарплата, премии (офис)', 1),
(5, 'Расчетно кассовое обслуживание', 1),
(6, 'Закупка товара на склад', 0),
(7, ' Расходы Офиса', 1),
(8, 'Расходы Склада', 1),
(9, 'Расходы на связь', 1),
(10, 'Расходы на автотранспорт (бензин, запчасти)', 1),
(11, 'Налоги и сборы', 1),
(12, 'Кредиты', 1),
(13, 'Непредвиденные расходы', 0),
(14, 'Премии по итогам квартала и года', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `doc_sklady`
--

CREATE TABLE IF NOT EXISTS `doc_sklady` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `dnc` tinyint(1) NOT NULL DEFAULT '0',
  KEY `id` (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `doc_sklady`
--

INSERT INTO `doc_sklady` (`id`, `name`, `dnc`) VALUES
(1, 'Основной склад', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `doc_types`
--

CREATE TABLE IF NOT EXISTS `doc_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=19 ;

--
-- Дамп данных таблицы `doc_types`
--

INSERT INTO `doc_types` (`id`, `name`) VALUES
(1, 'Поступление'),
(2, 'Реализация'),
(3, 'Заявка покупателя'),
(4, 'Банк - приход'),
(5, 'Банк - расход'),
(6, 'Касса - приход'),
(7, 'Касса - расход'),
(8, 'Перемещение товара'),
(9, 'Перемещение средств (касса)'),
(10, 'Доверенность'),
(11, 'Предложение поставщика'),
(12, 'Товар в пути'),
(13, 'Коммерческое предложение'),
(14, 'Договор'),
(15, 'Реализация (оперативная)'),
(16, 'Спецификация'),
(17, 'Сборка'),
(18, 'Корректировка долга');

-- --------------------------------------------------------

--
-- Структура таблицы `doc_vars`
--

CREATE TABLE IF NOT EXISTS `doc_vars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firm_name` varchar(150) NOT NULL,
  `firm_director` varchar(100) NOT NULL,
  `firm_manager` varchar(100) NOT NULL,
  `firm_buhgalter` varchar(100) NOT NULL,
  `firm_kladovshik` varchar(100) NOT NULL,
  `firm_bank` varchar(100) NOT NULL,
  `firm_bank_kor_s` varchar(25) NOT NULL,
  `firm_bik` varchar(15) NOT NULL,
  `firm_schet` varchar(25) NOT NULL,
  `firm_inn` varchar(25) NOT NULL,
  `firm_adres` varchar(150) NOT NULL,
  `firm_realadres` varchar(150) NOT NULL,
  `firm_gruzootpr` varchar(300) NOT NULL,
  `firm_telefon` varchar(60) NOT NULL,
  `firm_okpo` varchar(10) NOT NULL,
  `param_nds` double NOT NULL DEFAULT '0',
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `doc_vars`
--

INSERT INTO `doc_vars` (`id`, `firm_name`, `firm_director`, `firm_manager`, `firm_buhgalter`, `firm_kladovshik`, `firm_bank`, `firm_bank_kor_s`, `firm_bik`, `firm_schet`, `firm_inn`, `firm_adres`, `firm_realadres`, `firm_gruzootpr`, `firm_telefon`, `firm_okpo`, `param_nds`) VALUES
(1, 'ООО Первая Фирма', 'Аноним И.О.', 'Аноним И.О.', 'Аноним И.О.', 'Аноним И.О.', '', '', '', '', '', '', '', '', '', '', 18);

-- --------------------------------------------------------

--
-- Структура таблицы `errorlog`
--

CREATE TABLE IF NOT EXISTS `errorlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page` varchar(128) NOT NULL,
  `referer` varchar(128) NOT NULL,
  `agent` varchar(128) NOT NULL,
  `ip` varchar(18) NOT NULL,
  `msg` text NOT NULL,
  `date` datetime NOT NULL,
  `uid` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `page` (`page`),
  KEY `referer` (`referer`),
  KEY `date` (`date`),
  KEY `agent` (`agent`,`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `fabric_builders`
--

CREATE TABLE IF NOT EXISTS `fabric_builders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `active` tinyint(4) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `fabric_data`
--

CREATE TABLE IF NOT EXISTS `fabric_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sklad_id` int(11) NOT NULL,
  `builder_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `pos_id` int(11) NOT NULL,
  `cnt` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uni` (`sklad_id`,`builder_id`,`date`,`pos_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `firm_info`
--

CREATE TABLE IF NOT EXISTS `firm_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `num_name` int(11) NOT NULL DEFAULT '0' COMMENT 'Номер колонки с наименованиями в прайсе',
  `num_cost` int(11) NOT NULL DEFAULT '0',
  `num_art` int(11) NOT NULL DEFAULT '0',
  `num_nal` tinyint(4) NOT NULL,
  `signature` varchar(200) NOT NULL DEFAULT '' COMMENT 'Сигнатура для определения принадлежности прайса',
  `currency` tinyint(4) NOT NULL,
  `coeff` decimal(10,3) NOT NULL,
  `last_update` datetime NOT NULL,
  `delivery_info` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `sign` (`signature`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `firm_info`
--

INSERT INTO `firm_info` (`id`, `name`, `num_name`, `num_cost`, `num_art`, `num_nal`, `signature`, `currency`, `coeff`, `last_update`, `delivery_info`) VALUES
(1, 'test', 1, 2, 3, 4, 'test@example.com', 0, 0.000, '0000-00-00 00:00:00', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `firm_info_struct`
--

CREATE TABLE IF NOT EXISTS `firm_info_struct` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firm_id` int(11) NOT NULL COMMENT 'Номер фирмы',
  `table_name` varchar(50) NOT NULL COMMENT 'Название листа прайса',
  `name` mediumint(9) NOT NULL COMMENT 'N колонки наименований',
  `cost` mediumint(9) NOT NULL,
  `art` mediumint(9) NOT NULL,
  `nal` mediumint(9) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `firm_id` (`firm_id`),
  KEY `table_name` (`table_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `firm_info_struct`
--

INSERT INTO `firm_info_struct` (`id`, `firm_id`, `table_name`, `name`, `cost`, `art`, `nal`) VALUES
(1, 1, 'test', 2, 3, 1, 4);

-- --------------------------------------------------------

--
-- Структура таблицы `loginfo`
--

CREATE TABLE IF NOT EXISTS `loginfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `page` varchar(100) CHARACTER SET latin1 NOT NULL,
  `query` varchar(100) CHARACTER SET latin1 NOT NULL,
  `mode` varchar(20) CHARACTER SET latin1 NOT NULL,
  `ip` varchar(30) CHARACTER SET latin1 NOT NULL,
  `user` int(11) NOT NULL,
  `text` varchar(500) CHARACTER SET latin1 NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `date` (`date`),
  KEY `page` (`page`),
  KEY `query` (`query`),
  KEY `mode` (`mode`),
  KEY `ip` (`ip`),
  KEY `user` (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `news`
--

CREATE TABLE IF NOT EXISTS `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(8) NOT NULL,
  `title` varchar(64) NOT NULL,
  `text` text NOT NULL,
  `date` datetime NOT NULL,
  `autor` int(11) NOT NULL,
  `ex_date` date NOT NULL,
  `img_ext` varchar(4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `type` (`type`),
  KEY `ex_date` (`ex_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `notes`
--

CREATE TABLE IF NOT EXISTS `notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `sender` int(11) NOT NULL,
  `head` varchar(50) NOT NULL,
  `msg` text NOT NULL,
  `senddate` datetime NOT NULL,
  `enddate` datetime NOT NULL,
  `ok` tinyint(4) NOT NULL,
  `comment` text NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `user` (`user`),
  KEY `sender` (`sender`),
  KEY `senddate` (`senddate`),
  KEY `enddate` (`enddate`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `parsed_price`
--

CREATE TABLE IF NOT EXISTS `parsed_price` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firm` int(11) NOT NULL,
  `pos` int(11) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `nal` varchar(10) NOT NULL,
  `from` int(11) NOT NULL,
  `selected` tinyint(4) DEFAULT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `photogalery`
--

CREATE TABLE IF NOT EXISTS `photogalery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `comment` varchar(50) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `price`
--

CREATE TABLE IF NOT EXISTS `price` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `cost` double NOT NULL DEFAULT '0',
  `firm` int(11) NOT NULL DEFAULT '0',
  `art` varchar(20) NOT NULL DEFAULT '',
  `nal` varchar(20) NOT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `seeked` int(11) NOT NULL,
  KEY `name` (`name`),
  KEY `cost` (`cost`),
  KEY `firm` (`firm`),
  KEY `art` (`art`),
  KEY `date` (`date`),
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `prices_replaces`
--

CREATE TABLE IF NOT EXISTS `prices_replaces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `search_str` varchar(16) NOT NULL,
  `replace_str` varchar(256) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `search_str` (`search_str`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Список замен для регулярных выражений анализатора прайсов' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `ps_counter`
--

CREATE TABLE IF NOT EXISTS `ps_counter` (
  `date` date NOT NULL DEFAULT '0000-00-00',
  `query` int(11) NOT NULL DEFAULT '0',
  `ps` int(11) NOT NULL DEFAULT '0',
  `counter` int(11) NOT NULL,
  PRIMARY KEY (`date`,`query`,`ps`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `ps_parser`
--

CREATE TABLE IF NOT EXISTS `ps_parser` (
  `parametr` varchar(20) NOT NULL,
  `data` varchar(50) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `ps_parser`
--

INSERT INTO `ps_parser` (`parametr`, `data`) VALUES
('last_time_counter', '0');

-- --------------------------------------------------------

--
-- Структура таблицы `ps_query`
--

CREATE TABLE IF NOT EXISTS `ps_query` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `query` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `ps_settings`
--

CREATE TABLE IF NOT EXISTS `ps_settings` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `icon` varchar(3) NOT NULL,
  `name` varchar(15) NOT NULL,
  `template` varchar(150) NOT NULL,
  `template_like` varchar(50) NOT NULL,
  `prioritet` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

--
-- Дамп данных таблицы `ps_settings`
--

INSERT INTO `ps_settings` (`id`, `icon`, `name`, `template`, `template_like`, `prioritet`) VALUES
(1, 'Y', 'yandex', '/.*?yandex.*?text=[\\.\\s]*([-a-zа-я0-9"''_!?()\\/\\\\:;]+[-a-zа-я0-9.\\s,"''_!?()\\/\\\\:;]*).*[\\.\\s]*($|&.*)/ui', '%yandex%text=%', 1),
(2, 'G', 'google', '/.*?google.*?q=[\\.\\s]*([-a-zа-я0-9"''_!?()\\/\\\\:;]+[-a-zа-я0-9.\\s,"''_!?()\\/\\\\:;]*).*[\\.\\s]*($|&.*)/ui', '%google%q=%', 2),
(3, 'M', 'mail', '/.*?mail.*?q=[\\.\\s]*([-a-zа-я0-9"''_!?()\\/\\\\:;]+[-a-zа-я0-9.\\s,"''_!?()\\/\\\\:;]*).*[\\.\\s]*($|&.*)/ui', '%mail%q=%', 3),
(4, 'R', 'rambler', '/.*?rambler.*?query=[\\.\\s]*([-a-zа-я0-9"''_!?()\\/\\\\:;]+[-a-zа-я0-9.\\s,"''_!?()\\/\\\\:;]*).*[\\.\\s]*($|&.*)/ui', '%rambler%query=%', 4),
(5, 'B', 'bing', '/.*?bing.*?q=[\\.\\s]*([-a-zа-я0-9"''_!?()\\/\\\\:;]+[-a-zа-я0-9.\\s,"''_!?()\\/\\\\:;]*).*[\\.\\s]*($|&.*)/ui', '%bing%q=%', 5),
(6, 'Q', 'qip', '/.*?qip.*?query=[\\.\\s]*([-a-zа-я0-9"''_!?()\\/\\\\:;]+[-a-zа-я0-9.\\s,"''_!?()\\/\\\\:;]*).*[\\.\\s]*($|&.*)/ui', '%qip%query=%', 6),
(7, 'N', 'ngs', '/.*?ngs.*?q=[\\.\\s]*([-a-zа-я0-9"''_!?()\\/\\\\:;]+[-a-zа-я0-9.\\s,"''_!?()\\/\\\\:;]*).*[\\.\\s]*($|&.*)/ui', '%ngs%q=%', 7);

-- --------------------------------------------------------

--
-- Структура таблицы `questions`
--

CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` varchar(200) NOT NULL,
  `mode` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `question_answ`
--

CREATE TABLE IF NOT EXISTS `question_answ` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `q_id` int(11) NOT NULL,
  `answer` varchar(500) NOT NULL,
  `uid` int(11) NOT NULL,
  `ip` varchar(15) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `q_id` (`q_id`),
  KEY `uid` (`uid`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `question_ip`
--

CREATE TABLE IF NOT EXISTS `question_ip` (
  `ip` varchar(15) NOT NULL,
  `result` int(11) NOT NULL,
  UNIQUE KEY `ip_2` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `question_vars`
--

CREATE TABLE IF NOT EXISTS `question_vars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `q_id` int(11) NOT NULL,
  `var_id` int(11) NOT NULL,
  `text` varchar(500) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `q_id` (`q_id`,`var_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `seekdata`
--

CREATE TABLE IF NOT EXISTS `seekdata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `sql` varchar(200) NOT NULL,
  `regex` varchar(200) NOT NULL,
  `group` int(11) NOT NULL,
  `regex_neg` varchar(256) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`),
  KEY `sql` (`sql`),
  KEY `regex` (`regex`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sys_cli_status`
--

CREATE TABLE IF NOT EXISTS `sys_cli_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `script` varchar(64) NOT NULL,
  `status` text NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `script` (`script`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `tickets`
--

CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `autor` int(11) NOT NULL,
  `priority` tinyint(4) NOT NULL,
  `theme` varchar(100) NOT NULL,
  `text` text NOT NULL,
  `to_uid` int(11) NOT NULL,
  `to_date` date NOT NULL,
  `state` tinyint(4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `date` (`date`),
  KEY `autor` (`autor`),
  KEY `theme` (`theme`),
  KEY `to_uid` (`to_uid`),
  KEY `to_date` (`to_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `tickets_log`
--

CREATE TABLE IF NOT EXISTS `tickets_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `ticket` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `text` text NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `uid` (`uid`,`ticket`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `tickets_priority`
--

CREATE TABLE IF NOT EXISTS `tickets_priority` (
  `id` tinyint(4) NOT NULL,
  `name` varchar(50) NOT NULL,
  `color` varchar(6) NOT NULL,
  `comment` varchar(200) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `tickets_state`
--

CREATE TABLE IF NOT EXISTS `tickets_state` (
  `id` int(11) NOT NULL,
  `name` varchar(30) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `traffic_denyip`
--

CREATE TABLE IF NOT EXISTS `traffic_denyip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(20) NOT NULL,
  `host` varchar(50) NOT NULL,
  UNIQUE KEY `id_2` (`id`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Zapreshennie IP' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `ulog`
--

CREATE TABLE IF NOT EXISTS `ulog` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `raw_mac` varchar(80) DEFAULT NULL,
  `oob_time_sec` int(10) unsigned DEFAULT NULL,
  `oob_time_usec` int(10) unsigned DEFAULT NULL,
  `oob_prefix` varchar(32) DEFAULT NULL,
  `oob_mark` int(10) unsigned DEFAULT NULL,
  `oob_in` varchar(32) DEFAULT NULL,
  `oob_out` varchar(32) DEFAULT NULL,
  `ip_saddr` varchar(15) DEFAULT NULL,
  `ip_daddr` varchar(15) DEFAULT NULL,
  `ip_protocol` tinyint(3) unsigned DEFAULT NULL,
  `ip_tos` tinyint(3) unsigned DEFAULT NULL,
  `ip_ttl` tinyint(3) unsigned DEFAULT NULL,
  `ip_totlen` smallint(5) unsigned DEFAULT NULL,
  `ip_ihl` tinyint(3) unsigned DEFAULT NULL,
  `ip_csum` smallint(5) unsigned DEFAULT NULL,
  `ip_id` smallint(5) unsigned DEFAULT NULL,
  `ip_fragoff` smallint(5) unsigned DEFAULT NULL,
  `tcp_sport` smallint(5) unsigned DEFAULT NULL,
  `tcp_dport` smallint(5) unsigned DEFAULT NULL,
  `tcp_seq` int(10) unsigned DEFAULT NULL,
  `tcp_ackseq` int(10) unsigned DEFAULT NULL,
  `tcp_window` smallint(5) unsigned DEFAULT NULL,
  `tcp_urg` tinyint(4) DEFAULT NULL,
  `tcp_urgp` smallint(5) unsigned DEFAULT NULL,
  `tcp_ack` tinyint(4) DEFAULT NULL,
  `tcp_psh` tinyint(4) DEFAULT NULL,
  `tcp_rst` tinyint(4) DEFAULT NULL,
  `tcp_syn` tinyint(4) DEFAULT NULL,
  `tcp_fin` tinyint(4) DEFAULT NULL,
  `udp_sport` smallint(5) unsigned DEFAULT NULL,
  `udp_dport` smallint(5) unsigned DEFAULT NULL,
  `udp_len` smallint(5) unsigned DEFAULT NULL,
  `icmp_type` tinyint(3) unsigned DEFAULT NULL,
  `icmp_code` tinyint(3) unsigned DEFAULT NULL,
  `icmp_echoid` smallint(5) unsigned DEFAULT NULL,
  `icmp_echoseq` smallint(5) unsigned DEFAULT NULL,
  `icmp_gateway` int(10) unsigned DEFAULT NULL,
  `icmp_fragmtu` smallint(5) unsigned DEFAULT NULL,
  `pwsniff_user` varchar(30) DEFAULT NULL,
  `pwsniff_pass` varchar(30) DEFAULT NULL,
  `ahesp_spi` int(10) unsigned DEFAULT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `ip_daddr` (`ip_daddr`),
  KEY `ip_saddr` (`ip_saddr`),
  KEY `oob_time_sec` (`oob_time_sec`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `pass` varchar(192) NOT NULL,
  `pass_type` varchar(8) NOT NULL COMMENT 'тип хэша',
  `pass_change` varchar(64) NOT NULL,
  `pass_expired` tinyint(4) NOT NULL DEFAULT '0',
  `pass_date_change` datetime NOT NULL,
  `reg_email` varchar(64) NOT NULL,
  `reg_email_confirm` varchar(16) NOT NULL,
  `reg_email_subscribe` tinyint(4) NOT NULL,
  `reg_phone` varchar(16) NOT NULL,
  `reg_phone_subscribe` tinyint(4) NOT NULL,
  `reg_phone_confirm` varchar(8) NOT NULL,
  `reg_date` datetime NOT NULL,
  `disabled` tinyint(4) NOT NULL DEFAULT '0',
  `disabled_reason` varchar(128) NOT NULL,
  `bifact_auth` tinyint(4) NOT NULL DEFAULT '0',
  `real_name` varchar(64) NOT NULL,
  `real_address` varchar(256) NOT NULL,
  `jid` varchar(32) NOT NULL,
  `type` varchar(4) NOT NULL COMMENT 'физ/юр',
  `agent_id` int(11) DEFAULT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `passch` (`pass_change`),
  KEY `name` (`name`),
  KEY `reg_email` (`reg_email`),
  KEY `reg_email_confirm` (`reg_email_confirm`),
  KEY `reg_phone` (`reg_phone`),
  KEY `reg_phone_confirm` (`reg_phone_confirm`),
  KEY `pass_date_change` (`pass_date_change`),
  KEY `pass_expired` (`pass_expired`),
  KEY `disabled` (`disabled`),
  KEY `reg_email_subscribe` (`reg_email_subscribe`),
  KEY `reg_phone_subscribe` (`reg_phone_subscribe`),
  KEY `jid` (`jid`),
  KEY `agent_id` (`agent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Spisok pol''zovatelei' AUTO_INCREMENT=3 ;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `pass`, `pass_type`, `pass_change`, `pass_expired`, `pass_date_change`, `reg_email`, `reg_email_confirm`, `reg_email_subscribe`, `reg_phone`, `reg_phone_subscribe`, `reg_phone_confirm`, `reg_date`, `disabled`, `disabled_reason`, `bifact_auth`, `real_name`, `real_address`, `jid`, `type`, `agent_id`) VALUES
(0, 'anonymous', '--not-valid--', '', '', 0, '0000-00-00 00:00:00', '', '', 0, '', 0, '', '0000-00-00 00:00:00', 0, '', 0, '', '', '', '', 1),
(2, 'root', 'enter_md5_password', '', '', 0, '0000-00-00 00:00:00', '', '', 0, '', 0, '', '0000-00-00 00:00:00', 0, '', 0, '', '', '', '', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `users_acl`
--

CREATE TABLE IF NOT EXISTS `users_acl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `object` varchar(64) NOT NULL,
  `action` varchar(16) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `uid` (`uid`),
  KEY `object` (`object`),
  KEY `action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `users_bad_auth`
--

CREATE TABLE IF NOT EXISTS `users_bad_auth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(24) NOT NULL,
  `time` double NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `ip` (`ip`),
  KEY `date` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `users_data`
--

CREATE TABLE IF NOT EXISTS `users_data` (
  `uid` int(11) NOT NULL DEFAULT '0',
  `param` varchar(25) NOT NULL,
  `value` varchar(100) NOT NULL,
  UNIQUE KEY `uid` (`uid`,`param`),
  KEY `value` (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `users_grouplist`
--

CREATE TABLE IF NOT EXISTS `users_grouplist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `comment` text CHARACTER SET utf8 NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 PACK_KEYS=0 COMMENT='Spisok grupp' AUTO_INCREMENT=6 ;

--
-- Дамп данных таблицы `users_grouplist`
--

INSERT INTO `users_grouplist` (`id`, `name`, `comment`) VALUES
(0, 'anonymous', 'Гости'),
(1, 'root', 'Администраторы'),
(2, 'seo', 'Специалисты продвижения сайта'),
(3, 'sklad', 'Кладовщики'),
(4, 'manager', 'Управленцы'),
(5, 'buhgalter', 'Бухгалтерия');

-- --------------------------------------------------------

--
-- Структура таблицы `users_groups_acl`
--

CREATE TABLE IF NOT EXISTS `users_groups_acl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gid` int(11) NOT NULL,
  `object` varchar(64) NOT NULL,
  `action` varchar(16) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `gid` (`gid`),
  KEY `object` (`object`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Привилегии групп' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `users_in_group`
--

CREATE TABLE IF NOT EXISTS `users_in_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `gid` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `uid` (`uid`),
  KEY `gid` (`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Соответствие групп и пользователей' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `users_login_history`
--

CREATE TABLE IF NOT EXISTS `users_login_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `ip` varchar(32) NOT NULL,
  `useragent` varchar(128) NOT NULL,
  `method` varchar(8) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `users_objects`
--

CREATE TABLE IF NOT EXISTS `users_objects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `object` varchar(32) NOT NULL,
  `desc` varchar(128) NOT NULL,
  `actions` varchar(128) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `object` (`object`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=74 ;

--
-- Дамп данных таблицы `users_objects`
--

INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES
(1, 'doc', 'Документы', ''),
(2, 'doc_list', 'Журнал документов', 'view,delete'),
(3, 'doc_postuplenie', 'Поступление', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(4, 'generic_articles', 'Доступ к статьям', 'view,edit,create,delete'),
(5, 'sys', 'Системные объекты', ''),
(6, 'generic', 'Общие объекты', ''),
(7, 'sys_acl', 'Управление привилегиями', 'view,edit'),
(8, 'doc_realizaciya', 'Реализация', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(9, 'doc_zayavka', 'Документ заявки', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(10, 'doc_kompredl', 'Коммерческое предложение', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(11, 'doc_dogovor', 'Договор', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(12, 'doc_doveren', 'Доверенность', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(13, 'doc_pbank', 'Приход средств в банк', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(14, 'doc_peremeshenie', 'Перемещение товара', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(15, 'doc_perkas', 'Перемещение средств в кассе', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(16, 'doc_predlojenie', 'Предложение поставщика', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(17, 'doc_rbank', 'Расход средств из банка', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(18, 'doc_realiz_op', 'Оперативная реализация', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(19, 'doc_rko', 'Расходный кассовый ордер', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(20, 'doc_sborka', 'Сборка изделия', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(21, 'doc_specific', 'Спецификация', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(22, 'doc_v_puti', 'Товар в пути', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(23, 'list', 'Списки', ''),
(24, 'list_agent', 'Агенты', 'create,edit,view'),
(25, 'list_sklad', 'Склад', 'create,edit,view'),
(26, 'list_price_an', 'Анализатор прайсов', 'create,edit,view,delete'),
(27, 'list_agent_dov', 'Доверенные лица', 'create,edit,view'),
(28, 'report', 'Отчёты', ''),
(29, 'report_cash', 'Кассовый отчёт', 'view'),
(30, 'generic_news', 'Новости', 'view,create,edit,delete'),
(31, 'doc_service', 'Служебные функции', 'view,edit,delete'),
(32, 'doc_scripts', 'Сценарии и операции', 'view,exec'),
(33, 'log', 'Системные журналы', ''),
(34, 'log_browser', 'Статистирка броузеров', 'view'),
(35, 'log_error', 'Журнал ошибок', 'view'),
(36, 'log_access', 'Журнал посещений', 'view'),
(37, 'sys_async_task', 'Ассинхронные задачи', 'view,exec'),
(38, 'sys_ip-blacklist', 'Чёрный список IP адресов', 'view,create,delete'),
(39, 'sys_ip-log', 'Журнал обращений к ip адресам', 'view'),
(40, 'generic_price_an', 'Анализатор прайсов', 'view'),
(41, 'generic_galery', 'Фотогалерея', 'view,create,edit,delete'),
(42, 'doc_pko', 'Приходный кассовый ордер', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(43, 'doc_kordolga', 'Корректировка долга', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(45, 'sys_ps-stat', 'Статистика переходов с поисковиков', 'view'),
(46, 'report_move_nocost', 'Отчёт по движению товаров (без цен)', 'view'),
(47, 'report_kassday', 'Отчёт по кассе за день', 'view'),
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
(63, 'report_revision_act', 'Акт сверки', 'view'),
(64, 'report_balance', 'Состояние счетов и касс', 'view'),
(65, 'report_profitability', 'Отчёт по рентабельности', 'view'),
(66, 'report_kladovshik', 'Отчёт по кладовщикам в реализациях', 'view'),
(67, 'report_apay', 'Отчёт по платежам агентов', 'view'),
(68, 'doc_fabric', 'Учёт производства', 'view,edit'),
(69, 'admin_users', 'Администрирование пользователей', 'view,edit'),
(70, 'report_mincnt', 'Отчёт по минимальному количеству', 'view'),
(71, 'report_mincnt', 'Отчёт по минимальному количеству', 'view'),
(72, 'report_pos_komplekt', 'Отчёт по остаткам комплектующих', 'view'),
(73, 'report_ved_agentov', 'Ведомость по агентам', 'view');

-- --------------------------------------------------------

--
-- Структура таблицы `users_openid`
--

CREATE TABLE IF NOT EXISTS `users_openid` (
  `user_id` int(11) NOT NULL,
  `openid_identify` varchar(192) NOT NULL,
  `openid_type` int(16) NOT NULL,
  UNIQUE KEY `openid_identify` (`openid_identify`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Привязка к openid';

-- --------------------------------------------------------

--
-- Структура таблицы `users_worker_info`
--

CREATE TABLE IF NOT EXISTS `users_worker_info` (
  `user_id` int(11) NOT NULL,
  `worker` tinyint(4) NOT NULL,
  `worker_email` varchar(64) NOT NULL,
  `worker_phone` varchar(16) NOT NULL,
  `worker_jid` varchar(32) NOT NULL,
  `worker_real_name` varchar(64) NOT NULL,
  `worker_real_address` varchar(256) NOT NULL,
  UNIQUE KEY `user_id` (`user_id`),
  KEY `worker_email` (`worker_email`),
  KEY `worker_phone` (`worker_phone`),
  KEY `worker_jid` (`worker_jid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `variables`
--

CREATE TABLE IF NOT EXISTS `variables` (
  `corrupted` tinyint(4) NOT NULL COMMENT 'Признак нарушения целостности',
  `recalc_active` int(9) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Дамп данных таблицы `variables`
--

INSERT INTO `variables` (`corrupted`, `recalc_active`) VALUES
(0, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `wikiphoto`
--

CREATE TABLE IF NOT EXISTS `wikiphoto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `comment` varchar(50) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`autor`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `articles_ibfk_2` FOREIGN KEY (`changeautor`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `class_unit`
--
ALTER TABLE `class_unit`
  ADD CONSTRAINT `class_unit_ibfk_1` FOREIGN KEY (`class_unit_group_id`) REFERENCES `class_unit_group` (`id`),
  ADD CONSTRAINT `class_unit_ibfk_2` FOREIGN KEY (`class_unit_type_id`) REFERENCES `class_unit_type` (`id`);

--
-- Ограничения внешнего ключа таблицы `doc_base`
--
ALTER TABLE `doc_base`
  ADD CONSTRAINT `doc_base_ibfk_1` FOREIGN KEY (`group`) REFERENCES `doc_group` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `doc_base_ibfk_2` FOREIGN KEY (`unit`) REFERENCES `class_unit` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `doc_base_ibfk_3` FOREIGN KEY (`country`) REFERENCES `class_country` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `doc_base_attachments`
--
ALTER TABLE `doc_base_attachments`
  ADD CONSTRAINT `doc_base_attachments_ibfk_1` FOREIGN KEY (`pos_id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `doc_base_attachments_ibfk_2` FOREIGN KEY (`attachment_id`) REFERENCES `attachments` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `doc_base_cost`
--
ALTER TABLE `doc_base_cost`
  ADD CONSTRAINT `doc_base_cost_ibfk_1` FOREIGN KEY (`pos_id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `doc_base_cost_ibfk_2` FOREIGN KEY (`cost_id`) REFERENCES `doc_cost` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `doc_base_kompl`
--
ALTER TABLE `doc_base_kompl`
  ADD CONSTRAINT `doc_base_kompl_ibfk_1` FOREIGN KEY (`pos_id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `doc_base_kompl_ibfk_2` FOREIGN KEY (`kompl_id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `doc_base_pcollections_set`
--
ALTER TABLE `doc_base_pcollections_set`
  ADD CONSTRAINT `doc_base_pcollections_set_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `doc_base_pcollections_list` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `doc_base_pcollections_set_ibfk_2` FOREIGN KEY (`param_id`) REFERENCES `doc_base_params` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `doc_base_values`
--
ALTER TABLE `doc_base_values`
  ADD CONSTRAINT `doc_base_values_ibfk_1` FOREIGN KEY (`id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `doc_base_values_ibfk_2` FOREIGN KEY (`param_id`) REFERENCES `doc_base_params` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `doc_group_cost`
--
ALTER TABLE `doc_group_cost`
  ADD CONSTRAINT `doc_group_cost_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `doc_group` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `doc_group_cost_ibfk_2` FOREIGN KEY (`cost_id`) REFERENCES `doc_cost` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `doc_group_params`
--
ALTER TABLE `doc_group_params`
  ADD CONSTRAINT `fk_doc_group_params_doc_base_params1` FOREIGN KEY (`param_id`) REFERENCES `doc_base_params` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_doc_group_params_doc_group1` FOREIGN KEY (`group_id`) REFERENCES `doc_group` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `doc_list_pos`
--
ALTER TABLE `doc_list_pos`
  ADD CONSTRAINT `doc_list_pos_ibfk_2` FOREIGN KEY (`tovar`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `doc_list_pos_ibfk_3` FOREIGN KEY (`doc`) REFERENCES `doc_list` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `doc_agent` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `users_data`
--
ALTER TABLE `users_data`
  ADD CONSTRAINT `users_data_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `users_openid`
--
ALTER TABLE `users_openid`
  ADD CONSTRAINT `users_openid_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `users_worker_info`
--
ALTER TABLE `users_worker_info`
  ADD CONSTRAINT `users_worker_info_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
