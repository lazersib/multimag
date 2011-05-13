-- phpMyAdmin SQL Dump
-- version 3.3.7
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Май 06 2011 г., 13:44
-- Версия сервера: 5.1.49
-- Версия PHP: 5.3.3-7

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `dev`
--

-- --------------------------------------------------------

--
-- Структура таблицы `counter`
--

CREATE TABLE IF NOT EXISTS `counter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` bigint(20) NOT NULL DEFAULT '0',
  `ip` varchar(32) NOT NULL DEFAULT '',
  `agent` varchar(128) NOT NULL DEFAULT '',
  `refer` varchar(128) NOT NULL,
  `file` varchar(32) NOT NULL DEFAULT '',
  `query` varchar(128) NOT NULL DEFAULT '',
  UNIQUE KEY `id` (`id`),
  KEY `time` (`date`),
  KEY `ip` (`ip`),
  KEY `agent` (`agent`),
  KEY `refer` (`refer`),
  KEY `file` (`file`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

--
-- Дамп данных таблицы `counter`
--


-- --------------------------------------------------------

--
-- Структура таблицы `currency`
--

CREATE TABLE IF NOT EXISTS `currency` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(8) NOT NULL,
  `coeff` decimal(8,4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `currency`
--


-- --------------------------------------------------------

--
-- Структура таблицы `doc_agent`
--

CREATE TABLE IF NOT EXISTS `doc_agent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `fullname` varchar(256) NOT NULL,
  `tel` varchar(64) NOT NULL,
  `adres` varchar(512) NOT NULL,
  `gruzopol` varchar(512) NOT NULL,
  `inn` varchar(24) NOT NULL,
  `dir_fio` varchar(128) NOT NULL,
  `dir_fio_r` varchar(128) NOT NULL,
  `pfio` varchar(128) NOT NULL,
  `pdol` varchar(128) NOT NULL,
  `okevd` varchar(8) NOT NULL,
  `okpo` varchar(16) NOT NULL,
  `rs` varchar(32) NOT NULL,
  `bank` varchar(64) NOT NULL,
  `ks` varchar(32) NOT NULL,
  `bik` int(11) NOT NULL,
  `email` varchar(64) NOT NULL,
  `type` tinyint(4) NOT NULL DEFAULT '1',
  `pasp_num` varchar(12) NOT NULL,
  `pasp_date` date NOT NULL,
  `pasp_kem` varchar(64) NOT NULL,
  `comment` text NOT NULL,
  `no_mail` tinyint(4) NOT NULL,
  `responsible` int(11) NOT NULL,
  `data_sverki` date NOT NULL,
  `dishonest` tinyint(4) NOT NULL COMMENT 'Недобросовестный',
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uniq_name` (`group`,`name`),
  KEY `name` (`name`),
  KEY `fullname` (`fullname`(255)),
  KEY `tel` (`tel`),
  KEY `inn` (`inn`),
  KEY `type` (`type`),
  KEY `pasp_num` (`pasp_num`,`pasp_date`,`pasp_kem`),
  KEY `group` (`group`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='pcomment - printable comment' AUTO_INCREMENT=3 ;

--
-- Дамп данных таблицы `doc_agent`
--

INSERT INTO `doc_agent` (`id`, `group`, `name`, `fullname`, `tel`, `adres`, `gruzopol`, `inn`, `dir_fio`, `dir_fio_r`, `pfio`, `pdol`, `okevd`, `okpo`, `rs`, `bank`, `ks`, `bik`, `email`, `type`, `pasp_num`, `pasp_date`, `pasp_kem`, `comment`, `no_mail`, `responsible`, `data_sverki`, `dishonest`) VALUES
(1, 1, 'ЧЛ', 'Частное Лицо', '+12561126', 'г. Малый, ул. Большая, д.6', 'г. Малый, ул. Зелёная, д.124', '123456', 'Иванов И.И.', 'Иванова Ивана Игоревича', '', '', '52727', '3873838738', '9852183838383873', 'ЗАО Надёжный банк', '383838938389383838', 873838, 'cl@example.com', 1, '22872788937', '1970-01-01', 'УФМС г. Малый', '', 1, 0, '0000-00-00', 0),
(2, 1, 'Ещё Один', 'Ещё Один Агент', '', '', '1564653', '', '', '', '', '', '', 'regre', '', '', '', 0, 'user@example.com', 1, '', '0000-00-00', '', 'dfgreg', 0, 1, '0000-00-00', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `doc_agent_dov`
--

CREATE TABLE IF NOT EXISTS `doc_agent_dov` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ag_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `name2` varchar(64) NOT NULL,
  `surname` varchar(64) NOT NULL,
  `range` varchar(64) NOT NULL,
  `pasp_ser` varchar(8) NOT NULL,
  `pasp_num` varchar(16) NOT NULL,
  `pasp_kem` varchar(128) NOT NULL,
  `pasp_data` varchar(16) NOT NULL,
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
  `name` varchar(64) NOT NULL,
  `pid` int(11) NOT NULL,
  `desc` varchar(128) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Дамп данных таблицы `doc_agent_group`
--

INSERT INTO `doc_agent_group` (`id`, `name`, `pid`, `desc`) VALUES
(1, 'Покупатели', 0, ''),
(2, 'Поставщики', 0, '');

-- --------------------------------------------------------

--
-- Структура таблицы `doc_base`
--

CREATE TABLE IF NOT EXISTS `doc_base` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group` int(11) NOT NULL DEFAULT '0' COMMENT 'ID группы',
  `name` varchar(128) NOT NULL COMMENT 'Наименование',
  `vc` varchar(32) NOT NULL COMMENT 'Код изготовителя',
  `desc` text NOT NULL COMMENT 'Описание',
  `cost` double(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Цена',
  `stock` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Распродажа',
  `proizv` varchar(24) NOT NULL COMMENT 'Производитель',
  `likvid` decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT 'Ликвидность',
  `cost_date` datetime NOT NULL COMMENT 'Дата изменения цены',
  `pos_type` tinyint(4) NOT NULL COMMENT 'Товар - услуга',
  `hidden` tinyint(4) NOT NULL COMMENT 'Индекс сокрытия',
  `unit` int(11) NOT NULL COMMENT 'Единица измерения',
  `warranty` int(11) NOT NULL COMMENT 'Гарантийный срок',
  `warranty_type` tinyint(4) NOT NULL COMMENT 'Гарантия производителя',
  UNIQUE KEY `id` (`id`),
  KEY `group` (`group`),
  KEY `name` (`name`),
  KEY `stock` (`stock`),
  KEY `cost_date` (`cost_date`),
  KEY `hidden` (`hidden`),
  KEY `unit` (`unit`),
  KEY `vc` (`vc`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Дамп данных таблицы `doc_base`
--

INSERT INTO `doc_base` (`id`, `group`, `name`, `vc`, `desc`, `cost`, `stock`, `proizv`, `likvid`, `cost_date`, `pos_type`, `hidden`, `unit`, `warranty`, `warranty_type`) VALUES
(1, 1, 'Товар', '', '', 100.00, 0, '', 0.00, '2010-07-13 16:48:01', 0, 0, 1, 0, 0),
(2, 1, 'Товар ещё один', '', '', 0.00, 0, '', 0.00, '2010-06-09 16:44:09', 0, 0, 1, 0, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_cnt`
--

CREATE TABLE IF NOT EXISTS `doc_base_cnt` (
  `id` int(11) NOT NULL,
  `sklad` tinyint(4) NOT NULL,
  `cnt` double NOT NULL,
  `mesto` int(11) NOT NULL,
  `mincnt` int(11) NOT NULL,
  PRIMARY KEY (`id`,`sklad`),
  KEY `cnt` (`cnt`),
  KEY `mesto` (`mesto`),
  KEY `mincnt` (`mincnt`),
  KEY `sklad` (`sklad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `doc_base_cnt`
--

INSERT INTO `doc_base_cnt` (`id`, `sklad`, `cnt`, `mesto`, `mincnt`) VALUES
(1, 1, 0, 0, 0);

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

--
-- Дамп данных таблицы `doc_base_cost`
--


-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_dop`
--

CREATE TABLE IF NOT EXISTS `doc_base_dop` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) NOT NULL DEFAULT '0',
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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `doc_base_dop`
--

INSERT INTO `doc_base_dop` (`id`, `type`, `d_int`, `d_ext`, `size`, `mass`, `analog`, `koncost`, `strana`, `tranzit`, `ntd`) VALUES
(1, 0, 3, 4, 5, 6, '1', 2, '7', 0, '8');

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

--
-- Дамп данных таблицы `doc_base_dop_type`
--


-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_img`
--

CREATE TABLE IF NOT EXISTS `doc_base_img` (
  `pos_id` int(11) NOT NULL,
  `img_id` int(11) NOT NULL,
  `default` tinyint(4) NOT NULL,
  UNIQUE KEY `pos_id` (`pos_id`,`img_id`),
  KEY `default` (`default`),
  KEY `img_id` (`img_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `doc_base_img`
--


-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_kompl`
--

CREATE TABLE IF NOT EXISTS `doc_base_kompl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pos_id` int(11) NOT NULL COMMENT 'id наименования',
  `kompl_id` int(11) NOT NULL COMMENT 'id комплектующего',
  `cnt` int(11) NOT NULL COMMENT 'количество',
  UNIQUE KEY `id` (`id`),
  KEY `kompl_id` (`kompl_id`),
  KEY `cnt` (`cnt`),
  KEY `pos_id` (`pos_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Комплектующие - из чего состоит эта позиция' AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `doc_base_kompl`
--


-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_params`
--

CREATE TABLE IF NOT EXISTS `doc_base_params` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `param` varchar(32) NOT NULL,
  `type` varchar(8) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `param` (`param`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Дамп данных таблицы `doc_base_params`
--

INSERT INTO `doc_base_params` (`id`, `param`, `type`) VALUES
(1, 'Толщина', 'double'),
(2, 'Ширина', 'double'),
(3, 'Цвет', 'text'),
(4, 'Материал', 'text'),
(5, 'Допустимая температура', 'int');

-- --------------------------------------------------------

--
-- Структура таблицы `doc_base_values`
--

CREATE TABLE IF NOT EXISTS `doc_base_values` (
  `id` int(11) NOT NULL,
  `param_id` int(11) NOT NULL,
  `value` varchar(32) NOT NULL,
  UNIQUE KEY `unique` (`id`,`param_id`),
  KEY `id` (`id`),
  KEY `param` (`param_id`),
  KEY `value` (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `doc_base_values`
--

INSERT INTO `doc_base_values` (`id`, `param_id`, `value`) VALUES
(1, 1, '5000'),
(1, 4, 'Кремний');

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
  `accuracy` tinyint(4) NOT NULL COMMENT 'Точность для округления',
  `direction` tinyint(4) NOT NULL COMMENT 'Направление округления',
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Дамп данных таблицы `doc_cost`
--

INSERT INTO `doc_cost` (`id`, `name`, `type`, `value`, `vid`, `accuracy`, `direction`) VALUES
(1, 'Оптовая', 'pp', 10.00, 1, 0, 0),
(2, 'Розничная', 'pp', 0.00, 0, 0, 0),
(3, 'Корпоративная', 'abs', 100.00, -2, 0, 0),
(4, 'Со скидкой', 'abs', -1.00, -1, 0, 0);

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

--
-- Дамп данных таблицы `doc_dopdata`
--


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
  `printname` varchar(64) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `pid` (`pid`),
  KEY `hidelevel` (`hidelevel`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Дамп данных таблицы `doc_group`
--

INSERT INTO `doc_group` (`id`, `name`, `desc`, `pid`, `hidelevel`, `printname`) VALUES
(1, 'Группа 1', '', 0, 0, ''),
(2, 'Группа 2', '', 0, 0, '');

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
  `accuracy` tinyint(4) NOT NULL COMMENT 'Точность для округления',
  `direction` tinyint(4) NOT NULL COMMENT 'Направление округления',
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uniq` (`group_id`,`cost_id`),
  KEY `group_id` (`group_id`),
  KEY `cost_id` (`cost_id`),
  KEY `value` (`value`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `doc_group_cost`
--


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `doc_img`
--


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
('bank', 1, 'Главный банк', 0.00, '', '', '', 0),
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
  KEY `kassa` (`kassa`,`bank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `doc_list`
--


-- --------------------------------------------------------

--
-- Структура таблицы `doc_list_pos`
--

CREATE TABLE IF NOT EXISTS `doc_list_pos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doc` int(11) NOT NULL DEFAULT '0',
  `tovar` int(11) NOT NULL DEFAULT '0',
  `cnt` int(11) NOT NULL DEFAULT '0',
  `sn` varchar(15) NOT NULL,
  `comm` varchar(50) NOT NULL,
  `cost` double NOT NULL DEFAULT '0',
  `page` int(11) NOT NULL DEFAULT '0',
  KEY `id` (`id`),
  KEY `doc` (`doc`),
  KEY `tovar` (`tovar`),
  KEY `page` (`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `doc_list_pos`
--


-- --------------------------------------------------------

--
-- Структура таблицы `doc_list_sn`
--

CREATE TABLE IF NOT EXISTS `doc_list_sn` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pos_id` int(11) NOT NULL COMMENT 'ID товара',
  `num` varchar(64) NOT NULL COMMENT 'Серийный номер',
  `prix_list_pos` int(11) NOT NULL COMMENT 'Строка поступления',
  `rasx_list_pos` int(11) DEFAULT NULL COMMENT 'Строка реализации',
  UNIQUE KEY `id` (`id`),
  KEY `pos_id` (`pos_id`),
  KEY `num` (`num`),
  KEY `prix_list_pos` (`prix_list_pos`),
  KEY `rasx_list_pos` (`rasx_list_pos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Серийные номера' AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `doc_list_sn`
--


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

--
-- Дамп данных таблицы `doc_log`
--


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
(2, 'Зарплата, премии, надбавки', 1),
(3, 'Канцелярские товары, расходные материалы', 1),
(4, 'Представительские расходы', 1),
(5, 'Другие (банковские) платежи', 1),
(6, 'Закупка товара на склад', 0),
(7, 'Закупка товара на продажу', 0),
(8, 'Транспортные расходы', 1),
(9, 'Расходы на связь', 1),
(10, 'Оплата товара на реализации', 0),
(11, 'Налоги и сборы', 1),
(12, 'Средства под отчёт', 0),
(13, 'Расходы на рекламу', 1),
(14, 'Возврат товара', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `doc_sklady`
--

CREATE TABLE IF NOT EXISTS `doc_sklady` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `comment` text NOT NULL,
  KEY `id` (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Дамп данных таблицы `doc_sklady`
--

INSERT INTO `doc_sklady` (`id`, `name`, `comment`) VALUES
(1, 'Склад 1', ''),
(2, 'Склад 2', '');

-- --------------------------------------------------------

--
-- Структура таблицы `doc_types`
--

CREATE TABLE IF NOT EXISTS `doc_types` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=18 ;

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
(17, 'Сборка изделия');

-- --------------------------------------------------------

--
-- Структура таблицы `doc_units`
--

CREATE TABLE IF NOT EXISTS `doc_units` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(16) NOT NULL,
  `printname` varchar(8) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`),
  KEY `printname` (`printname`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

--
-- Дамп данных таблицы `doc_units`
--

INSERT INTO `doc_units` (`id`, `name`, `printname`) VALUES
(1, 'Штука', 'шт.'),
(2, 'Килограмм', 'кг.'),
(3, 'Грамм', 'гр.'),
(4, 'Литр', 'л.'),
(5, 'Метр', 'м.'),
(6, 'Милиметр', 'мм.'),
(7, 'Упаковка', 'уп.');

-- --------------------------------------------------------

--
-- Структура таблицы `doc_vars`
--

CREATE TABLE IF NOT EXISTS `doc_vars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firm_name` varchar(150) NOT NULL,
  `firm_director` varchar(100) NOT NULL,
  `firm_director_r` varchar(100) NOT NULL,
  `firm_manager` varchar(100) NOT NULL,
  `firm_buhgalter` varchar(100) NOT NULL,
  `firm_kladovshik` varchar(100) NOT NULL,
  `firm_kladovshik_id` int(11) NOT NULL,
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
  `firm_skin` varchar(16) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `doc_vars`
--

INSERT INTO `doc_vars` (`id`, `firm_name`, `firm_director`, `firm_director_r`, `firm_manager`, `firm_buhgalter`, `firm_kladovshik`, `firm_kladovshik_id`, `firm_bank`, `firm_bank_kor_s`, `firm_bik`, `firm_schet`, `firm_inn`, `firm_adres`, `firm_realadres`, `firm_gruzootpr`, `firm_telefon`, `firm_okpo`, `param_nds`, `firm_skin`) VALUES
(1, 'ООО Первая Фирма', '', '', '', '', '', 0, '', '', '', '', '', '', '', '', '', '', 0, '');

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

--
-- Дамп данных таблицы `errorlog`
--


-- --------------------------------------------------------

--
-- Структура таблицы `firm_info`
--

CREATE TABLE IF NOT EXISTS `firm_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `signature` varchar(200) NOT NULL DEFAULT '' COMMENT 'Сигнатура для определения принадлежности прайса',
  `currency` tinyint(4) NOT NULL,
  `coeff` decimal(10,3) NOT NULL,
  `last_update` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `sign` (`signature`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `firm_info`
--

INSERT INTO `firm_info` (`id`, `name`, `signature`, `currency`, `coeff`, `last_update`) VALUES
(1, 'test', 'test@example.com', 0, 0.000, '0000-00-00 00:00:00');

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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

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
  `page` varchar(100) NOT NULL,
  `query` varchar(100) NOT NULL,
  `mode` varchar(20) NOT NULL,
  `ip` varchar(30) NOT NULL,
  `user` int(11) NOT NULL,
  `text` varchar(500) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `date` (`date`),
  KEY `page` (`page`),
  KEY `query` (`query`),
  KEY `mode` (`mode`),
  KEY `ip` (`ip`),
  KEY `user` (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `loginfo`
--


-- --------------------------------------------------------

--
-- Структура таблицы `news`
--

CREATE TABLE IF NOT EXISTS `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(64) NOT NULL,
  `text` text NOT NULL,
  `date` datetime NOT NULL,
  `owner` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `news`
--


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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `notes`
--


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
  PRIMARY KEY (`id`),
  KEY `firm` (`firm`),
  KEY `pos` (`pos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `parsed_price`
--


-- --------------------------------------------------------

--
-- Структура таблицы `photogalery`
--

CREATE TABLE IF NOT EXISTS `photogalery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `comment` varchar(50) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `photogalery`
--


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `price`
--


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

--
-- Дамп данных таблицы `prices_replaces`
--


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

--
-- Дамп данных таблицы `questions`
--


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

--
-- Дамп данных таблицы `question_answ`
--


-- --------------------------------------------------------

--
-- Структура таблицы `question_ip`
--

CREATE TABLE IF NOT EXISTS `question_ip` (
  `ip` varchar(15) NOT NULL,
  `result` int(11) NOT NULL,
  UNIQUE KEY `ip_2` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `question_ip`
--


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

--
-- Дамп данных таблицы `question_vars`
--


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

--
-- Дамп данных таблицы `seekdata`
--


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

--
-- Дамп данных таблицы `sys_cli_status`
--


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `tickets`
--


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

--
-- Дамп данных таблицы `tickets_log`
--


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

--
-- Дамп данных таблицы `tickets_priority`
--


-- --------------------------------------------------------

--
-- Структура таблицы `tickets_state`
--

CREATE TABLE IF NOT EXISTS `tickets_state` (
  `id` int(11) NOT NULL,
  `name` varchar(30) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `tickets_state`
--


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

--
-- Дамп данных таблицы `traffic_denyip`
--


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
  KEY `ip_saddr` (`ip_saddr`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Дамп данных таблицы `ulog`
--


-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `pass` varchar(35) NOT NULL,
  `passch` varchar(35) NOT NULL,
  `email` varchar(50) NOT NULL,
  `date_reg` datetime NOT NULL,
  `confirm` varchar(32) NOT NULL,
  `subscribe` int(11) NOT NULL COMMENT 'Podpiska na novosti i dr informaciy',
  `lastlogin` datetime NOT NULL,
  `rname` varchar(32) NOT NULL,
  `tel` varchar(15) NOT NULL,
  `adres` varchar(100) NOT NULL,
  `worker` tinyint(4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `passch` (`passch`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Spisok pol''zovatelei' AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `pass`, `passch`, `email`, `date_reg`, `confirm`, `subscribe`, `lastlogin`, `rname`, `tel`, `adres`, `worker`) VALUES
(0, 'anonymous', 'NULL', '', '', '0000-00-00 00:00:00', '0', 0, '0000-00-00 00:00:00', 'anonymous', '', 'nothing', 0),
(1, 'root', '63a9f0ea7bb98050796b649e85481845', '', '', '0000-00-00 00:00:00', '0', 0, '2011-01-24 12:23:30', '', '', '', 0);

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

--
-- Дамп данных таблицы `users_acl`
--


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

--
-- Дамп данных таблицы `users_bad_auth`
--


-- --------------------------------------------------------

--
-- Структура таблицы `users_data`
--

CREATE TABLE IF NOT EXISTS `users_data` (
  `uid` int(11) NOT NULL DEFAULT '0',
  `param` varchar(25) NOT NULL,
  `value` varchar(128) NOT NULL,
  UNIQUE KEY `uid` (`uid`,`param`),
  KEY `value` (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `users_data`
--


-- --------------------------------------------------------

--
-- Структура таблицы `users_grouplist`
--

CREATE TABLE IF NOT EXISTS `users_grouplist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `comment` text NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 PACK_KEYS=0 COMMENT='Spisok grupp' AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `users_grouplist`
--

INSERT INTO `users_grouplist` (`id`, `name`, `comment`) VALUES
(1, 'root', '');

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Привилегии групп' AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `users_groups_acl`
--

INSERT INTO `users_groups_acl` (`id`, `gid`, `object`, `action`) VALUES
(1, 1, 'doc_list', '');

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Соответствие групп и пользователей' AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `users_in_group`
--

INSERT INTO `users_in_group` (`id`, `uid`, `gid`) VALUES
(1, 1, 1);

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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=45;

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
(8, 'doc_realiz', 'Реализация', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(9, 'doc_zayavka', 'Документ заявки', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(10, 'doc_kompredl', 'Коммерческое предложение', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(11, 'doc_dogovor', 'Договор', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(12, 'doc_doveren', 'Доверенность', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(13, 'doc_pbank', 'Приход средств в банк', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
(14, 'doc_pertemeshenie', 'Перемещение товара', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),
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
(31, 'doc_service', 'Служебные функции', 'view'),
(32, 'doc_scropts', 'Сценарии и операции', 'view,exec'),
(33, 'log', 'Системные журналы', ''),
(34, 'log_browser', 'Статистирка броузеров', 'view'),
(35, 'log_error', 'Журнал ошибок', 'view'),
(36, 'log_access', 'Журнал посещений', 'view'),
(37, 'sys_async_task', 'Ассинхронные задачи', 'view,exec'),
(38, 'sys_ip-blacklist', 'Чёрный список IP адресов', 'view,create,delete'),
(39, 'sys_ip-log', 'Журнал обращений к ip адресам', 'view'),
(40, 'generic_price_an', 'Анализатор прайсов', 'view'),
(41, 'generic_galery', 'Фотогалерея', 'view,create,edit,delete');

-- --------------------------------------------------------

--
-- Структура таблицы `wiki`
--

CREATE TABLE IF NOT EXISTS `wiki` (
  `name` varchar(50) NOT NULL,
  `date` datetime NOT NULL,
  `autor` int(11) NOT NULL,
  `changed` datetime NOT NULL,
  `changeautor` int(11) NOT NULL,
  `text` text NOT NULL,
  UNIQUE KEY `name` (`name`),
  KEY `date` (`date`),
  KEY `autor` (`autor`),
  KEY `changed` (`changed`),
  KEY `changeautor` (`changeautor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `wiki`
--

INSERT INTO `wiki` (`name`, `date`, `autor`, `changed`, `changeautor`, `text`) VALUES
('main', '2010-06-22 10:49:25', 1, '2010-06-30 16:06:48', 1, '(:title Сайт в разработке:)\r\n\r\n');

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
-- Дамп данных таблицы `wikiphoto`
--


--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `doc_agent`
--
ALTER TABLE `doc_agent`
  ADD CONSTRAINT `doc_agent_ibfk_1` FOREIGN KEY (`group`) REFERENCES `doc_agent_group` (`id`);

--
-- Ограничения внешнего ключа таблицы `doc_agent_dov`
--
ALTER TABLE `doc_agent_dov`
  ADD CONSTRAINT `doc_agent_dov_ibfk_1` FOREIGN KEY (`ag_id`) REFERENCES `doc_agent` (`id`);

--
-- Ограничения внешнего ключа таблицы `doc_base`
--
ALTER TABLE `doc_base`
  ADD CONSTRAINT `doc_base_ibfk_2` FOREIGN KEY (`unit`) REFERENCES `doc_units` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `doc_base_ibfk_1` FOREIGN KEY (`group`) REFERENCES `doc_group` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `doc_base_cnt`
--
ALTER TABLE `doc_base_cnt`
  ADD CONSTRAINT `doc_base_cnt_ibfk_2` FOREIGN KEY (`sklad`) REFERENCES `doc_sklady` (`id`),
  ADD CONSTRAINT `doc_base_cnt_ibfk_1` FOREIGN KEY (`id`) REFERENCES `doc_base` (`id`);

--
-- Ограничения внешнего ключа таблицы `doc_base_cost`
--
ALTER TABLE `doc_base_cost`
  ADD CONSTRAINT `doc_base_cost_ibfk_1` FOREIGN KEY (`pos_id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `doc_base_cost_ibfk_2` FOREIGN KEY (`cost_id`) REFERENCES `doc_cost` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `doc_base_dop`
--
ALTER TABLE `doc_base_dop`
  ADD CONSTRAINT `doc_base_dop_ibfk_1` FOREIGN KEY (`id`) REFERENCES `doc_base` (`id`);

--
-- Ограничения внешнего ключа таблицы `doc_base_dop_type`
--
ALTER TABLE `doc_base_dop_type`
  ADD CONSTRAINT `doc_base_dop_type_ibfk_1` FOREIGN KEY (`id`) REFERENCES `doc_base` (`id`);

--
-- Ограничения внешнего ключа таблицы `doc_base_img`
--
ALTER TABLE `doc_base_img`
  ADD CONSTRAINT `doc_base_img_ibfk_2` FOREIGN KEY (`img_id`) REFERENCES `doc_img` (`id`),
  ADD CONSTRAINT `doc_base_img_ibfk_1` FOREIGN KEY (`pos_id`) REFERENCES `doc_base` (`id`);

--
-- Ограничения внешнего ключа таблицы `doc_base_kompl`
--
ALTER TABLE `doc_base_kompl`
  ADD CONSTRAINT `doc_base_kompl_ibfk_2` FOREIGN KEY (`kompl_id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `doc_base_kompl_ibfk_1` FOREIGN KEY (`pos_id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `doc_base_values`
--
ALTER TABLE `doc_base_values`
  ADD CONSTRAINT `doc_base_values_ibfk_1` FOREIGN KEY (`id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `doc_base_values_ibfk_2` FOREIGN KEY (`param_id`) REFERENCES `doc_base_params` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `doc_dopdata`
--
ALTER TABLE `doc_dopdata`
  ADD CONSTRAINT `doc_dopdata_ibfk_1` FOREIGN KEY (`doc`) REFERENCES `doc_list` (`id`);

--
-- Ограничения внешнего ключа таблицы `doc_group_cost`
--
ALTER TABLE `doc_group_cost`
  ADD CONSTRAINT `doc_group_cost_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `doc_group` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `doc_group_cost_ibfk_2` FOREIGN KEY (`cost_id`) REFERENCES `doc_cost` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `doc_list`
--
ALTER TABLE `doc_list`
  ADD CONSTRAINT `doc_list_ibfk_5` FOREIGN KEY (`type`) REFERENCES `doc_types` (`id`),
  ADD CONSTRAINT `doc_list_ibfk_1` FOREIGN KEY (`agent`) REFERENCES `doc_agent` (`id`),
  ADD CONSTRAINT `doc_list_ibfk_2` FOREIGN KEY (`user`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `doc_list_ibfk_3` FOREIGN KEY (`sklad`) REFERENCES `doc_sklady` (`id`),
  ADD CONSTRAINT `doc_list_ibfk_4` FOREIGN KEY (`firm_id`) REFERENCES `doc_vars` (`id`);

--
-- Ограничения внешнего ключа таблицы `doc_list_pos`
--
ALTER TABLE `doc_list_pos`
  ADD CONSTRAINT `doc_list_pos_ibfk_2` FOREIGN KEY (`tovar`) REFERENCES `doc_base` (`id`),
  ADD CONSTRAINT `doc_list_pos_ibfk_1` FOREIGN KEY (`doc`) REFERENCES `doc_list` (`id`);

--
-- Ограничения внешнего ключа таблицы `doc_list_sn`
--
ALTER TABLE `doc_list_sn`
  ADD CONSTRAINT `doc_list_sn_ibfk_4` FOREIGN KEY (`prix_list_pos`) REFERENCES `doc_list_pos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `doc_list_sn_ibfk_1` FOREIGN KEY (`pos_id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `doc_list_sn_ibfk_3` FOREIGN KEY (`rasx_list_pos`) REFERENCES `doc_list_pos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `firm_info_struct`
--
ALTER TABLE `firm_info_struct`
  ADD CONSTRAINT `firm_info_struct_ibfk_1` FOREIGN KEY (`firm_id`) REFERENCES `firm_info` (`id`);

--
-- Ограничения внешнего ключа таблицы `parsed_price`
--
ALTER TABLE `parsed_price`
  ADD CONSTRAINT `parsed_price_ibfk_2` FOREIGN KEY (`pos`) REFERENCES `price` (`id`),
  ADD CONSTRAINT `parsed_price_ibfk_1` FOREIGN KEY (`firm`) REFERENCES `firm_info` (`id`);

--
-- Ограничения внешнего ключа таблицы `photogalery`
--
ALTER TABLE `photogalery`
  ADD CONSTRAINT `photogalery_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `price`
--
ALTER TABLE `price`
  ADD CONSTRAINT `price_ibfk_1` FOREIGN KEY (`firm`) REFERENCES `firm_info` (`id`);

--
-- Ограничения внешнего ключа таблицы `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`to_uid`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`autor`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `users_acl`
--
ALTER TABLE `users_acl`
  ADD CONSTRAINT `users_acl_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `users_data`
--
ALTER TABLE `users_data`
  ADD CONSTRAINT `users_data_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Ограничения внешнего ключа таблицы `users_groups_acl`
--
ALTER TABLE `users_groups_acl`
  ADD CONSTRAINT `users_groups_acl_ibfk_1` FOREIGN KEY (`gid`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `users_in_group`
--
ALTER TABLE `users_in_group`
  ADD CONSTRAINT `users_in_group_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `users_in_group_ibfk_2` FOREIGN KEY (`gid`) REFERENCES `users_grouplist` (`id`);

--
-- Ограничения внешнего ключа таблицы `wiki`
--
ALTER TABLE `wiki`
  ADD CONSTRAINT `wiki_ibfk_1` FOREIGN KEY (`autor`) REFERENCES `users` (`id`);
