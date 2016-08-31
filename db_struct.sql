-- MySQL dump 10.13  Distrib 5.5.46, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: demo
-- ------------------------------------------------------
-- Server version	5.5.46-0+deb8u1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `agent_banks`
--

DROP TABLE IF EXISTS `agent_banks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_banks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `bik` varchar(16) NOT NULL,
  `ks` varchar(32) NOT NULL,
  `rs` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `agent_id` (`agent_id`),
  KEY `bik` (`bik`),
  KEY `ks` (`ks`),
  KEY `rs` (`rs`),
  CONSTRAINT `agent_banks_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `doc_agent` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agent_banks`
--

LOCK TABLES `agent_banks` WRITE;
/*!40000 ALTER TABLE `agent_banks` DISABLE KEYS */;
/*!40000 ALTER TABLE `agent_banks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agent_contacts`
--

DROP TABLE IF EXISTS `agent_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL,
  `context` varchar(8) NOT NULL,
  `type` varchar(8) NOT NULL,
  `value` varchar(64) NOT NULL,
  `for_fax` tinyint(4) NOT NULL,
  `for_sms` tinyint(4) NOT NULL,
  `no_ads` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `agent_id` (`agent_id`),
  KEY `type` (`type`),
  KEY `value` (`value`),
  KEY `for_fax` (`for_fax`),
  KEY `for_sms` (`for_sms`),
  KEY `no_ads` (`no_ads`),
  CONSTRAINT `agent_contacts_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `doc_agent` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agent_contacts`
--

LOCK TABLES `agent_contacts` WRITE;
/*!40000 ALTER TABLE `agent_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `agent_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `articles`
--

DROP TABLE IF EXISTS `articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `articles` (
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
  KEY `changeautor` (`changeautor`),
  CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`autor`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `articles_ibfk_2` FOREIGN KEY (`changeautor`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `articles`
--

LOCK TABLES `articles` WRITE;
/*!40000 ALTER TABLE `articles` DISABLE KEYS */;
/*!40000 ALTER TABLE `articles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asterisk_queue_log`
--

DROP TABLE IF EXISTS `asterisk_queue_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asterisk_queue_log` (
  `id` tinyint(4) NOT NULL,
  `time` tinyint(4) NOT NULL,
  `callid` tinyint(4) NOT NULL,
  `queuename` tinyint(4) NOT NULL,
  `agent` tinyint(4) NOT NULL,
  `event` tinyint(4) NOT NULL,
  `data` tinyint(4) NOT NULL,
  `data1` tinyint(4) NOT NULL,
  `data2` tinyint(4) NOT NULL,
  `data3` tinyint(4) NOT NULL,
  `data4` tinyint(4) NOT NULL,
  `data5` tinyint(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asterisk_queue_log`
--

LOCK TABLES `asterisk_queue_log` WRITE;
/*!40000 ALTER TABLE `asterisk_queue_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `asterisk_queue_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `async_workers_tasks`
--

DROP TABLE IF EXISTS `async_workers_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `async_workers_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task` varchar(32) NOT NULL,
  `description` varchar(128) NOT NULL,
  `needrun` tinyint(4) NOT NULL DEFAULT '1',
  `textstatus` varchar(128) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `needrun` (`needrun`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `async_workers_tasks`
--

LOCK TABLES `async_workers_tasks` WRITE;
/*!40000 ALTER TABLE `async_workers_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `async_workers_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attachments`
--

DROP TABLE IF EXISTS `attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_filename` varchar(64) NOT NULL,
  `comment` varchar(256) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='ÐŸÑ€Ð¸ÐºÑ€ÐµÐ¿Ð»Ñ‘Ð½Ð½Ñ‹Ðµ Ñ„Ð°Ð¹Ð»Ñ‹';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attachments`
--

LOCK TABLES `attachments` WRITE;
/*!40000 ALTER TABLE `attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class_country`
--

DROP TABLE IF EXISTS `class_country`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `class_country` (
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
) ENGINE=InnoDB AUTO_INCREMENT=249 DEFAULT CHARSET=utf8 COMMENT='Общероссийский классификатор стран мира ОКСМ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_country`
--

LOCK TABLES `class_country` WRITE;
/*!40000 ALTER TABLE `class_country` DISABLE KEYS */;
INSERT INTO `class_country` VALUES (1,'АФГАНИСТАН','Переходное Исламское Государство Афганистан','004','AF','AFG',1,NULL),(2,'АЛБАНИЯ','Республика Албания','008','AL','ALB',1,NULL),(3,'АНТАРКТИДА',NULL,'010','AQ','ATA',1,NULL),(4,'АЛЖИР','Алжирская Народная Демократическая Республика','012','DZ','DZA',1,NULL),(5,'АМЕРИКАНСКОЕ САМОА',NULL,'016','AS','ASM',1,NULL),(6,'АНДОРРА','Княжество Андорра','020','AD','AND',1,NULL),(7,'АНГОЛА','Республика Ангола','024','AO','AGO',1,NULL),(8,'АНТИГУА И БАРБУДА',NULL,'028','AG','ATG',1,NULL),(9,'АЗЕРБАЙДЖАН','Республика Азербайджан','031','AZ','AZE',1,NULL),(10,'АРГЕНТИНА','Аргентинская Республика','032','AR','ARG',1,NULL),(11,'АВСТРАЛИЯ',NULL,'036','AU','AUS',1,NULL),(12,'АВСТРИЯ','Австрийская Республика','040','AT','AUT',1,NULL),(13,'БАГАМЫ','Содружество Багамы','044','BS','BHS',1,NULL),(14,'БАХРЕЙН','Королевство Бахрейн','048','BH','BHR',1,NULL),(15,'БАНГЛАДЕШ','Народная Республика Бангладеш','050','BD','BGD',1,NULL),(16,'АРМЕНИЯ','Республика Армения','051','AM','ARM',1,NULL),(17,'БАРБАДОС',NULL,'052','BB','BRB',1,NULL),(18,'БЕЛЬГИЯ','Королевство Бельгии','056','BE','BEL',1,NULL),(19,'БЕРМУДЫ',NULL,'060','BM','BMU',1,NULL),(20,'БУТАН','Королевство Бутан','064','BT','BTN',1,NULL),(21,'БОЛИВИЯ, МНОГОНАЦИОНАЛЬНОЕ ГОСУДАРСТВО','Многонациональное Государство Боливия','068','BO','BOL',1,NULL),(22,'БОСНИЯ И ГЕРЦЕГОВИНА',NULL,'070','BA','BIH',1,NULL),(23,'БОТСВАНА','Республика Ботсвана','072','BW','BWA',1,NULL),(24,'ОСТРОВ БУВЕ',NULL,'074','BV','BVT',1,NULL),(25,'БРАЗИЛИЯ','Федеративная Республика Бразилия','076','BR','BRA',1,NULL),(26,'БЕЛИЗ',NULL,'084','BZ','BLZ',1,NULL),(27,'БРИТАНСКАЯ ТЕРРИТОРИЯ В ИНДИЙСКОМ ОКЕАНЕ',NULL,'086','IO','IOT',1,NULL),(28,'СОЛОМОНОВЫ ОСТРОВА',NULL,'090','SB','SLB',1,NULL),(29,'ВИРГИНСКИЕ ОСТРОВА, БРИТАНСКИЕ','Британские Виргинские острова','092','VG','VGB',1,NULL),(30,'БРУНЕЙ-ДАРУССАЛАМ',NULL,'096','BN','BRN',1,NULL),(31,'БОЛГАРИЯ','Республика Болгария','100','BG','BGR',1,NULL),(32,'МЬЯНМА','Союз Мьянма','104','MM','MMR',1,NULL),(33,'БУРУНДИ','Республика Бурунди','108','BI','BDI',1,NULL),(34,'БЕЛАРУСЬ','Республика Беларусь','112','BY','BLR',1,NULL),(35,'КАМБОДЖА','Королевство Камбоджа','116','KH','KHM',1,NULL),(36,'КАМЕРУН','Республика Камерун','120','CM','CMR',1,NULL),(37,'КАНАДА',NULL,'124','CA','CAN',1,NULL),(38,'КАБО-ВЕРДЕ','Республика Кабо-Верде','132','CV','CPV',1,NULL),(39,'ОСТРОВА КАЙМАН',NULL,'136','KY','CYM',1,NULL),(40,'ЦЕНТРАЛЬНО-АФРИКАНСКАЯ РЕСПУБЛИКА',NULL,'140','CF','CAF',1,NULL),(41,'ШРИ-ЛАНКА','Демократическая Социалистическая Республика Шри-Ланка','144','LK','LKA',1,NULL),(42,'ЧАД','Республика Чад','148','TD','TCD',1,NULL),(43,'ЧИЛИ','Республика Чили','152','CL','CHL',1,NULL),(44,'КИТАЙ','Китайская Народная Республика','156','CN','CHN',1,NULL),(45,'ТАЙВАНЬ (КИТАЙ)',NULL,'158','TW','TWN',1,NULL),(46,'ОСТРОВ РОЖДЕСТВА',NULL,'162','CX','CXR',1,NULL),(47,'КОКОСОВЫЕ (КИЛИНГ) ОСТРОВА',NULL,'166','CC','CCK',1,NULL),(48,'КОЛУМБИЯ','Республика Колумбия','170','CO','COL',1,NULL),(49,'КОМОРЫ','Союз Коморы','174','KM','COM',1,NULL),(50,'МАЙОТТА',NULL,'175','YT','MYT',1,NULL),(51,'КОНГО','Республика Конго','178','CG','COG',1,NULL),(52,'КОНГО, ДЕМОКРАТИЧЕСКАЯ РЕСПУБЛИКА','Демократическая Республика Конго','180','CD','COD',1,NULL),(53,'ОСТРОВА КУКА',NULL,'184','CK','COK',1,NULL),(54,'КОСТА-РИКА','Республика Коста-Рика','188','CR','CRI',1,NULL),(55,'ХОРВАТИЯ','Республика Хорватия','191','HR','HRV',1,NULL),(56,'КУБА','Республика Куба','192','CU','CUB',1,NULL),(57,'КИПР','Республика Кипр','196','CY','CYP',1,NULL),(58,'ЧЕШСКАЯ РЕСПУБЛИКА',NULL,'203','CZ','CZE',1,NULL),(59,'БЕНИН','Республика Бенин','204','BJ','BEN',1,NULL),(60,'ДАНИЯ','Королевство Дания','208','DK','DNK',1,NULL),(61,'ДОМИНИКА','Содружество Доминики','212','DM','DMA',1,NULL),(62,'ДОМИНИКАНСКАЯ РЕСПУБЛИКА',NULL,'214','DO','DOM',1,NULL),(63,'ЭКВАДОР','Республика Эквадор','218','EC','ECU',1,NULL),(64,'ЭЛЬ-САЛЬВАДОР','Республика Эль-Сальвадор','222','SV','SLV',1,NULL),(65,'ЭКВАТОРИАЛЬНАЯ ГВИНЕЯ','Республика Экваториальная Гвинея','226','GQ','GNQ',1,NULL),(66,'ЭФИОПИЯ','Федеративная Демократическая Республика Эфиопия','231','ET','ETH',1,NULL),(67,'ЭРИТРЕЯ',NULL,'232','ER','ERI',1,NULL),(68,'ЭСТОНИЯ','Эстонская Республика','233','EE','EST',1,NULL),(69,'ФАРЕРСКИЕ ОСТРОВА',NULL,'234','FO','FRO',1,NULL),(70,'ФОЛКЛЕНДСКИЕ ОСТРОВА (МАЛЬВИНСКИЕ)',NULL,'238','FK','FLK',1,NULL),(71,'ЮЖНАЯ ДЖОРДЖИЯ И ЮЖНЫЕ САНДВИЧЕВЫ ОСТРОВА',NULL,'239','GS','SGS',1,NULL),(72,'ФИДЖИ','Республика Островов Фиджи','242','FJ','FJI',1,NULL),(73,'ФИНЛЯНДИЯ','Финляндская Республика','246','FI','FIN',1,NULL),(74,'ЭЛАНДСКИЕ ОСТРОВА',NULL,'248','АХ','ALA',1,NULL),(75,'ФРАНЦИЯ','Французская Республика','250','FR','FRA',1,NULL),(76,'ФРАНЦУЗСКАЯ ГВИАНА',NULL,'254','GF','GUF',1,NULL),(77,'ФРАНЦУЗСКАЯ ПОЛИНЕЗИЯ',NULL,'258','PF','PYF',1,NULL),(78,'ФРАНЦУЗСКИЕ ЮЖНЫЕ ТЕРРИТОРИИ',NULL,'260','TF','ATF',1,NULL),(79,'ДЖИБУТИ','Республика Джибути','262','DJ','DJI',1,NULL),(80,'ГАБОН','Габонская Республика','266','GA','GAB',1,NULL),(81,'ГРУЗИЯ',NULL,'268','GE','GEO',1,NULL),(82,'ГАМБИЯ','Республика Гамбия','270','GM','GMB',1,NULL),(83,'ПАЛЕСТИНСКАЯ ТЕРРИТОРИЯ, ОККУПИРОВАННАЯ','Оккупированная Палестинская территория','275','PS','PSE',1,NULL),(84,'ГЕРМАНИЯ','Федеративная Республика Германия','276','DE','DEU',1,NULL),(85,'ГАНА','Республика Гана','288','GH','GHA',1,NULL),(86,'ГИБРАЛТАР',NULL,'292','GI','GIB',1,NULL),(87,'КИРИБАТИ','Республика Кирибати','296','KI','KIR',1,NULL),(88,'ГРЕЦИЯ','Греческая Республика','300','GR','GRC',1,NULL),(89,'ГРЕНЛАНДИЯ',NULL,'304','GL','GRL',1,NULL),(90,'ГРЕНАДА',NULL,'308','GD','GRD',1,NULL),(91,'ГВАДЕЛУПА',NULL,'312','GP','GLP',1,NULL),(92,'ГУАМ',NULL,'316','GU','GUM',1,NULL),(93,'ГВАТЕМАЛА','Республика Гватемала','320','GT','GTM',1,NULL),(94,'ГВИНЕЯ','Гвинейская Республика','324','GN','GIN',1,NULL),(95,'ГАЙАНА','Республика Гайана','328','GY','GUY',1,NULL),(96,'ГАИТИ','Республика Гаити','332','HT','HTI',1,NULL),(97,'ОСТРОВ ХЕРД И ОСТРОВА МАКДОНАЛЬД',NULL,'334','HM','HMD',1,NULL),(98,'ПАПСКИЙ ПРЕСТОЛ (ГОСУДАРСТВО - ГОРОД ВАТИКАН)',NULL,'336','VA','VAT',1,NULL),(99,'ГОНДУРАС','Республика Гондурас','340','HN','HND',1,NULL),(100,'ГОНКОНГ','Специальный административный регион Китая Гонконг','344','HK','HKG',1,NULL),(101,'ВЕНГРИЯ','Венгерская Республика','348','HU','HUN',1,NULL),(102,'ИСЛАНДИЯ','Республика Исландия','352','IS','ISL',1,NULL),(103,'ИНДИЯ','Республика Индия','356','IN','IND',1,NULL),(104,'ИНДОНЕЗИЯ','Республика Индонезия','360','ID','IDN',1,NULL),(105,'ИРАН, ИСЛАМСКАЯ РЕСПУБЛИКА','Исламская Республика Иран','364','IR','IRN',1,NULL),(106,'ИРАК','Республика Ирак','368','IQ','IRQ',1,NULL),(107,'ИРЛАНДИЯ',NULL,'372','IE','IRL',1,NULL),(108,'ИЗРАИЛЬ','Государство Израиль','376','IL','ISR',1,NULL),(109,'ИТАЛИЯ','Итальянская Республика','380','IT','ITA',1,NULL),(110,'КОТ Д\'ИВУАР','Республика Кот д\'Ивуар','384','CI','CIV',1,NULL),(111,'ЯМАЙКА',NULL,'388','JM','JAM',1,NULL),(112,'ЯПОНИЯ',NULL,'392','JP','JPN',1,NULL),(113,'КАЗАХСТАН','Республика Казахстан','398','KZ','KAZ',1,NULL),(114,'ИОРДАНИЯ','Иорданское Хашимитское Королевство','400','JO','JOR',1,NULL),(115,'КЕНИЯ','Республика Кения','404','KE','KEN',1,NULL),(116,'КОРЕЯ, НАРОДНО-ДЕМОКРАТИЧЕСКАЯ РЕСПУБЛИКА','Корейская Народно-Демократическая Республика','408','KP','PRK',1,NULL),(117,'КОРЕЯ, РЕСПУБЛИКА','Республика Корея','410','KR','KOR',1,NULL),(118,'КУВЕЙТ','Государство Кувейт','414','KW','KWT',1,NULL),(119,'КИРГИЗИЯ','Киргизская Республика','417','KG','KGZ',1,NULL),(120,'ЛАОССКАЯ НАРОДНО-ДЕМОКРАТИЧЕСКАЯ РЕСПУБЛИКА',NULL,'418','LA','LAO',1,NULL),(121,'ЛИВАН','Ливанская Республика','422','LB','LBN',1,NULL),(122,'ЛЕСОТО','Королевство Лесото','426','LS','LSO',1,NULL),(123,'ЛАТВИЯ','Латвийская Республика','428','LV','LVA',1,NULL),(124,'ЛИБЕРИЯ','Республика Либерия','430','LR','LBR',1,NULL),(125,'ЛИВИЙСКАЯ АРАБСКАЯ ДЖАМАХИРИЯ','Социалистическая Народная Ливийская Арабская Джамахирия','434','LY','LBY',1,NULL),(126,'ЛИХТЕНШТЕЙН','Княжество Лихтенштейн','438','LI','LIE',1,NULL),(127,'ЛИТВА','Литовская Республика','440','LT','LTU',1,NULL),(128,'ЛЮКСЕМБУРГ','Великое Герцогство Люксембург','442','LU','LUX',1,NULL),(129,'МАКАО','Специальный административный регион Китая Макао','446','MO','MAC',1,NULL),(130,'МАДАГАСКАР','Республика Мадагаскар','450','MG','MDG',1,NULL),(131,'МАЛАВИ','Республика Малави','454','MW','MWI',1,NULL),(132,'МАЛАЙЗИЯ',NULL,'458','MY','MYS',1,NULL),(133,'МАЛЬДИВЫ','Мальдивская Республика','462','MV','MDV',1,NULL),(134,'МАЛИ','Республика Мали','466','ML','MLI',1,NULL),(135,'МАЛЬТА','Республика Мальта','470','MT','MLT',1,NULL),(136,'МАРТИНИКА',NULL,'474','MQ','MTQ',1,NULL),(137,'МАВРИТАНИЯ','Исламская Республика Мавритания','478','MR','MRT',1,NULL),(138,'МАВРИКИЙ','Республика Маврикий','480','MU','MUS',1,NULL),(139,'МЕКСИКА','Мексиканские Соединенные Штаты','484','MX','MEX',1,NULL),(140,'МОНАКО','Княжество Монако','492','MC','MCO',1,NULL),(141,'МОНГОЛИЯ',NULL,'496','MN','MNG',1,NULL),(142,'МОЛДОВА, РЕСПУБЛИКА','Республика Молдова','498','MD','MDA',1,NULL),(143,'ЧЕРНОГОРИЯ',NULL,'499','ME','MNE',1,NULL),(144,'МОНТСЕРРАТ',NULL,'500','MS','MSR',1,NULL),(145,'МАРОККО','Королевство Марокко','504','MA','MAR',1,NULL),(146,'МОЗАМБИК','Республика Мозамбик','508','MZ','MOZ',1,NULL),(147,'ОМАН','Султанат Оман','512','OM','OMN',1,NULL),(148,'НАМИБИЯ','Республика Намибия','516','NA','NAM',1,NULL),(149,'НАУРУ','Республика Науру','520','NR','NRU',1,NULL),(150,'НЕПАЛ','Федеративная Демократическая Республика Непал','524','NP','NPL',1,NULL),(151,'НИДЕРЛАНДЫ','Королевство Нидерландов','528','NL','NLD',1,NULL),(152,'НИДЕРЛАНДСКИЕ АНТИЛЫ',NULL,'530','AN','ANT',1,NULL),(153,'АРУБА',NULL,'533','AW','ABW',1,NULL),(154,'НОВАЯ КАЛЕДОНИЯ',NULL,'540','NC','NCL',1,NULL),(155,'ВАНУАТУ','Республика Вануату','548','VU','VUT',1,NULL),(156,'НОВАЯ ЗЕЛАНДИЯ',NULL,'554','NZ','NZL',1,NULL),(157,'НИКАРАГУА','Республика Никарагуа','558','NI','NIC',1,NULL),(158,'НИГЕР','Республика Нигер','562','NE','NER',1,NULL),(159,'НИГЕРИЯ','Федеративная Республика Нигерия','566','NG','NGA',1,NULL),(160,'НИУЭ','Республика Ниуэ','570','NU','NIU',1,NULL),(161,'ОСТРОВ НОРФОЛК',NULL,'574','NF','NFK',1,NULL),(162,'НОРВЕГИЯ','Королевство Норвегия','578','NO','NOR',1,NULL),(163,'СЕВЕРНЫЕ МАРИАНСКИЕ ОСТРОВА','Содружество Северных Марианских островов','580','MP','MNP',1,NULL),(164,'МАЛЫЕ ТИХООКЕАНСКИЕ ОТДАЛЕННЫЕ ОСТРОВА СОЕДИНЕННЫХ ШТАТОВ',NULL,'581','UM','UMI',1,NULL),(165,'МИКРОНЕЗИЯ, ФЕДЕРАТИВНЫЕ ШТАТЫ','Федеративные штаты Микронезии','583','FM','FSM',1,NULL),(166,'МАРШАЛЛОВЫ ОСТРОВА','Республика Маршалловы Острова','584','MH','MHL',1,NULL),(167,'ПАЛАУ','Республика Палау','585','PW','PLW',1,NULL),(168,'ПАКИСТАН','Исламская Республика Пакистан','586','PK','PAK',1,NULL),(169,'ПАНАМА','Республика Панама','591','PA','PAN',1,NULL),(170,'ПАПУА-НОВАЯ ГВИНЕЯ',NULL,'598','PG','PNG',1,NULL),(171,'ПАРАГВАЙ','Республика Парагвай','600','PY','PRY',1,NULL),(172,'ПЕРУ','Республика Перу','604','PE','PER',1,NULL),(173,'ФИЛИППИНЫ','Республика Филиппины','608','PH','PHL',1,NULL),(174,'ПИТКЕРН',NULL,'612','PN','PCN',1,NULL),(175,'ПОЛЬША','Республика Польша','616','PL','POL',1,NULL),(176,'ПОРТУГАЛИЯ','Португальская Республика','620','PT','PRT',1,NULL),(177,'ГВИНЕЯ-БИСАУ','Республика Гвинея-Бисау','624','GW','GNB',1,NULL),(178,'ТИМОР-ЛЕСТЕ','Демократическая Республика Тимор-Лесте','626','TL','TLS',1,NULL),(179,'ПУЭРТО-РИКО',NULL,'630','PR','PRI',1,NULL),(180,'КАТАР','Государство Катар','634','QA','QAT',1,NULL),(181,'РЕЮНЬОН',NULL,'638','RE','REU',1,NULL),(182,'РУМЫНИЯ',NULL,'642','RO','ROU',1,NULL),(183,'РОССИЯ','Российская Федерация','643','RU','RUS',1,NULL),(184,'РУАНДА','Руандийская Республика','646','RW','RWA',1,NULL),(185,'СЕН-БАРТЕЛЕМИ',NULL,'652','BL','BLM',1,NULL),(186,'СВЯТАЯ ЕЛЕНА',NULL,'654','SH','SHN',1,NULL),(187,'СЕНТ-КИТС И НЕВИС',NULL,'659','KN','KNA',1,NULL),(188,'АНГИЛЬЯ',NULL,'660','AI','AIA',1,NULL),(189,'СЕНТ-ЛЮСИЯ',NULL,'662','LC','LCA',1,NULL),(190,'СЕН-МАРТЕН',NULL,'663','MF','MAF',1,NULL),(191,'СЕН-ПЬЕР И МИКЕЛОН',NULL,'666','PM','SPM',1,NULL),(192,'СЕНТ-ВИНСЕНТ И ГРЕНАДИНЫ',NULL,'670','VC','VCT',1,NULL),(193,'САН-МАРИНО','Республика Сан-Марино','674','SM','SMR',1,NULL),(194,'САН-ТОМЕ И ПРИНСИПИ','Демократическая Республика Сан-Томе и Принсипи','678','ST','STP',1,NULL),(195,'САУДОВСКАЯ АРАВИЯ','Королевство Саудовская Аравия','682','SA','SAU',1,NULL),(196,'СЕНЕГАЛ','Республика Сенегал','686','SN','SEN',1,NULL),(197,'СЕРБИЯ','Республика Сербия','688','RS','SRB',1,NULL),(198,'СЕЙШЕЛЫ','Республика Сейшелы','690','SC','SYC',1,NULL),(199,'СЬЕРРА-ЛЕОНЕ','Республика Сьерра-Леоне','694','SL','SLE',1,NULL),(200,'СИНГАПУР','Республика Сингапур','702','SG','SGP',1,NULL),(201,'СЛОВАКИЯ','Словацкая Республика','703','SK','SVK',1,NULL),(202,'ВЬЕТНАМ','Социалистическая Республика Вьетнам','704','VN','VNM',1,NULL),(203,'СЛОВЕНИЯ','Республика Словения','705','SI','SVN',1,NULL),(204,'СОМАЛИ','Сомалийская Республика','706','SO','SOM',1,NULL),(205,'ЮЖНАЯ АФРИКА','Южно-Африканская Республика','710','ZA','ZAF',1,NULL),(206,'ЗИМБАБВЕ','Республика Зимбабве','716','ZW','ZWE',1,NULL),(207,'ИСПАНИЯ','Королевство Испания','724','ES','ESP',1,NULL),(208,'ЗАПАДНАЯ САХАРА',NULL,'732','EH','ESH',1,NULL),(209,'СУДАН','Республика Судан','736','SD','SDN',1,NULL),(210,'СУРИНАМ','Республика Суринам','740','SR','SUR',1,NULL),(211,'ШПИЦБЕРГЕН И ЯН МАЙЕН',NULL,'744','SJ','SJM',1,NULL),(212,'СВАЗИЛЕНД','Королевство Свазиленд','748','SZ','SWZ',1,NULL),(213,'ШВЕЦИЯ','Королевство Швеция','752','SE','SWE',1,NULL),(214,'ШВЕЙЦАРИЯ','Швейцарская Конфедерация','756','CH','CHE',1,NULL),(215,'СИРИЙСКАЯ АРАБСКАЯ РЕСПУБЛИКА',NULL,'760','SY','SYR',1,NULL),(216,'ТАДЖИКИСТАН','Республика Таджикистан','762','TJ','TJK',1,NULL),(217,'ТАИЛАНД','Королевство Таиланд','764','TH','THA',1,NULL),(218,'ТОГО','Тоголезская Республика','768','TG','TGO',1,NULL),(219,'ТОКЕЛАУ',NULL,'772','TK','TKL',1,NULL),(220,'ТОНГА','Королевство Тонга','776','TO','TON',1,NULL),(221,'ТРИНИДАД И ТОБАГО','Республика Тринидад и Тобаго','780','TT','TTO',1,NULL),(222,'ОБЪЕДИНЕННЫЕ АРАБСКИЕ ЭМИРАТЫ',NULL,'784','AE','ARE',1,NULL),(223,'ТУНИС','Тунисская Республика','788','TN','TUN',1,NULL),(224,'ТУРЦИЯ','Турецкая Республика','792','TR','TUR',1,NULL),(225,'ТУРКМЕНИЯ','Туркменистан','795','TM','TKM',1,NULL),(226,'ОСТРОВА ТЕРКС И КАЙКОС',NULL,'796','TC','TCA',1,NULL),(227,'ТУВАЛУ',NULL,'798','TV','TUV',1,NULL),(228,'УГАНДА','Республика Уганда','800','UG','UGA',1,NULL),(229,'УКРАИНА',NULL,'804','UA','UKR',1,NULL),(230,'РЕСПУБЛИКА МАКЕДОНИЯ',NULL,'807','MK','MKD',1,NULL),(231,'ЕГИПЕТ','Арабская Республика Египет','818','EG','EGY',1,NULL),(232,'СОЕДИНЕННОЕ КОРОЛЕВСТВО','Соединенное Королевство Великобритании и Северной Ирландии','826','GB','GBR',1,NULL),(233,'ГЕРНСИ',NULL,'831','GG','GGY',1,NULL),(234,'ДЖЕРСИ',NULL,'832','JE','JEY',1,NULL),(235,'ОСТРОВ МЭН',NULL,'833','IM','IMN',1,NULL),(236,'ТАНЗАНИЯ, ОБЪЕДИНЕННАЯ РЕСПУБЛИКА','Объединенная Республика Танзания','834','TZ','TZA',1,NULL),(237,'СОЕДИНЕННЫЕ ШТАТЫ','Соединенные Штаты Америки','840','US','USA',1,NULL),(238,'ВИРГИНСКИЕ ОСТРОВА, США','Виргинские острова Соединенных Штатов','850','VI','VIR',1,NULL),(239,'БУРКИНА-ФАСО',NULL,'854','BF','BFA',1,NULL),(240,'УРУГВАЙ','Восточная Республика Уругвай','858','UY','URY',1,NULL),(241,'УЗБЕКИСТАН','Республика Узбекистан','860','UZ','UZB',1,NULL),(242,'ВЕНЕСУЭЛА БОЛИВАРИАНСКАЯ РЕСПУБЛИКА','Боливарианская Республика Венесуэла','862','VE','VEN',1,NULL),(243,'УОЛЛИС И ФУТУНА',NULL,'876','WF','WLF',1,NULL),(244,'САМОА','Независимое Государство Самоа','882','WS','WSM',1,NULL),(245,'ЙЕМЕН','Йеменская Республика','887','YE','YEM',1,NULL),(246,'ЗАМБИЯ','Республика Замбия','894','ZM','ZMB',1,NULL),(247,'АБХАЗИЯ','Республика Абхазия','895','AB','ABH',1,NULL),(248,'ЮЖНАЯ ОСЕТИЯ','Республика Южная Осетия','896','OS','OST',1,NULL);
/*!40000 ALTER TABLE `class_country` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class_unit`
--

DROP TABLE IF EXISTS `class_unit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `class_unit` (
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
  KEY `class_unit_type_id` (`class_unit_type_id`),
  CONSTRAINT `class_unit_ibfk_1` FOREIGN KEY (`class_unit_group_id`) REFERENCES `class_unit_group` (`id`),
  CONSTRAINT `class_unit_ibfk_2` FOREIGN KEY (`class_unit_type_id`) REFERENCES `class_unit_type` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=431 DEFAULT CHARSET=utf8 COMMENT='Общероссийский классификатор единиц измерения ОКЕИ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_unit`
--

LOCK TABLES `class_unit` WRITE;
/*!40000 ALTER TABLE `class_unit` DISABLE KEYS */;
INSERT INTO `class_unit` VALUES (1,'Миллиметр','003','мм','mm','ММ','MMT',1,1,1,NULL),(2,'Сантиметр','004','см','cm','СМ','CMT',1,1,1,NULL),(4,'Метр','006','м','m','М','MTR',1,1,1,NULL),(9,'Ярд (0,9144 м)','043','ярд','yd','ЯРД','YRD',1,1,1,NULL),(14,'Квадратный метр','055','м2','m2','М2','MTK',2,1,1,NULL),(24,'Литр; кубический дециметр','112','л; дм3','I; L; dm^3','Л; ДМ3','LTR; DMQ',3,1,1,NULL),(37,'Килограмм','166','кг','kg','КГ','KGM',4,1,1,NULL),(114,'Бобина','616','боб','-','БОБ','NBB',7,1,1,NULL),(119,'Изделие','657','изд','-','ИЗД','NAR',7,1,1,NULL),(121,'Набор','704','набор','-','НАБОР','SET',7,1,1,NULL),(122,'Пара (2 шт.)','715','пар','pr; 2','ПАР','NPR',7,1,1,NULL),(128,'Рулон','736','рул','-','РУЛ','NPL',7,1,1,NULL),(132,'Упаковка','778','упак','-','УПАК','NMP',7,1,1,NULL),(135,'Штука','796','шт','pc; 1','ШТ','PCE; NMB',7,1,1,NULL),(155,'Погонный метр','018','пог. м',NULL,'ПОГ М',NULL,1,2,1,NULL),(219,'Байт','255','бай',NULL,'БАЙТ',NULL,5,2,1,NULL),(231,'Рубль','383','руб',NULL,'РУБ',NULL,7,2,1,NULL),(257,'Тонна в смену','536','т/смен',NULL,'Т/СМЕН',NULL,7,2,1,NULL),(260,'Человеко-час','539','чел.ч',NULL,'ЧЕЛ.Ч',NULL,7,2,1,NULL),(285,'Единица','642','ед',NULL,'ЕД',NULL,7,2,1,NULL),(290,'Место','698','мест',NULL,'МЕСТ',NULL,7,2,1,NULL),(304,'Человек','792','чел',NULL,'ЧЕЛ',NULL,7,2,1,NULL),(309,'Ящик','812','ящ',NULL,'ЯЩ',NULL,7,2,1,NULL),(312,'Миллион пар','838','10^6 пар',NULL,'МЛН ПАР',NULL,7,2,1,NULL),(313,'Комплект','839','компл',NULL,'КОМПЛ',NULL,7,2,1,NULL),(323,'Условная единица','876','усл. ед',NULL,'УСЛ ЕД',NULL,7,2,1,NULL),(364,'Смена','917','смен',NULL,'СМЕН',NULL,7,2,1,NULL),(430,'Стандарт','152',NULL,'-',NULL,'WSD',3,3,1,NULL);
/*!40000 ALTER TABLE `class_unit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class_unit_group`
--

DROP TABLE IF EXISTS `class_unit_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `class_unit_group` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT COMMENT 'pk',
  `name` varchar(255) NOT NULL COMMENT 'Наименование группы',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='Группы единиц измерения';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_unit_group`
--

LOCK TABLES `class_unit_group` WRITE;
/*!40000 ALTER TABLE `class_unit_group` DISABLE KEYS */;
INSERT INTO `class_unit_group` VALUES (6,'Единицы времени'),(1,'Единицы длины'),(4,'Единицы массы'),(3,'Единицы объема'),(2,'Единицы площади'),(5,'Технические единицы'),(7,'Экономические единицы');
/*!40000 ALTER TABLE `class_unit_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class_unit_type`
--

DROP TABLE IF EXISTS `class_unit_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `class_unit_type` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT COMMENT 'pk',
  `name` varchar(255) NOT NULL COMMENT 'Наименование раздела/приложения',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='Разделы/приложения, в которые включены единицы измерения';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_unit_type`
--

LOCK TABLES `class_unit_type` WRITE;
/*!40000 ALTER TABLE `class_unit_type` DISABLE KEYS */;
INSERT INTO `class_unit_type` VALUES (1,'Международные единицы измерения, включенные в ЕСКК'),(2,'Национальные единицы измерения, включенные в ЕСКК'),(3,'Международные единицы измерения, не включенные в ЕСКК');
/*!40000 ALTER TABLE `class_unit_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comments` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Коментарии к товарам, новостям, статьям и пр.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `counter`
--

DROP TABLE IF EXISTS `counter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `counter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` bigint(20) NOT NULL DEFAULT '0',
  `ip` varchar(30) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `agent` varchar(128) NOT NULL DEFAULT '',
  `refer` varchar(512) NOT NULL DEFAULT '',
  `file` varchar(20) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `query` varchar(128) NOT NULL DEFAULT '',
  `session_id` varchar(64) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `time` (`date`),
  KEY `ip` (`ip`),
  KEY `agent` (`agent`),
  KEY `refer` (`refer`(333)),
  KEY `file` (`file`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `counter`
--

LOCK TABLES `counter` WRITE;
/*!40000 ALTER TABLE `counter` DISABLE KEYS */;
/*!40000 ALTER TABLE `counter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currency`
--

DROP TABLE IF EXISTS `currency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `currency` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(10) NOT NULL,
  `coeff` decimal(8,4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=36 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currency`
--

LOCK TABLES `currency` WRITE;
/*!40000 ALTER TABLE `currency` DISABLE KEYS */;
INSERT INTO `currency` VALUES (1,'RUB',1.0000),(2,'AUD',56.1073),(3,'AZN',50.3559),(4,'GBP',114.6499),(5,'AMD',15.9767),(6,'BYR',35.9241),(7,'BGN',45.5912),(8,'BRL',20.3445),(9,'HUF',28.6611),(10,'DKK',11.9476),(11,'USD',79.0689),(12,'EUR',89.2213),(13,'INR',11.6398),(14,'KZT',21.9733),(15,'CAD',57.0195),(16,'KGS',10.4727),(17,'CNY',12.0277),(18,'LTL',19.8421),(19,'MDL',39.8935),(20,'NOK',92.3141),(21,'PLN',20.1224),(22,'RON',19.8830),(23,'XDR',110.6672),(24,'SGD',56.8391),(25,'TJS',10.0815),(26,'TRY',27.0035),(27,'TMT',23.2706),(28,'UZS',27.9890),(29,'UAH',30.5285),(30,'CZK',32.9907),(31,'SEK',93.9139),(32,'CHF',81.2129),(33,'ZAR',49.8732),(34,'KRW',66.1537),(35,'JPY',68.9805);
/*!40000 ALTER TABLE `currency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `db_version`
--

DROP TABLE IF EXISTS `db_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `db_version` (
  `version` int(11) NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `db_version`
--

LOCK TABLES `db_version` WRITE;
/*!40000 ALTER TABLE `db_version` DISABLE KEYS */;
INSERT INTO `db_version` VALUES (864);
/*!40000 ALTER TABLE `db_version` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_regions`
--

DROP TABLE IF EXISTS `delivery_regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_regions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_type` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `price` int(11) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_regions`
--

LOCK TABLES `delivery_regions` WRITE;
/*!40000 ALTER TABLE `delivery_regions` DISABLE KEYS */;
/*!40000 ALTER TABLE `delivery_regions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_types`
--

DROP TABLE IF EXISTS `delivery_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `min_price` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_types`
--

LOCK TABLES `delivery_types` WRITE;
/*!40000 ALTER TABLE `delivery_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `delivery_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_accounts`
--

DROP TABLE IF EXISTS `doc_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account` varchar(8) NOT NULL,
  `name` varchar(64) NOT NULL,
  `usedby` varchar(64) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `account` (`account`),
  KEY `name` (`name`),
  KEY `usedby` (`usedby`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Бухгалтерские счета';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_accounts`
--

LOCK TABLES `doc_accounts` WRITE;
/*!40000 ALTER TABLE `doc_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_agent`
--

DROP TABLE IF EXISTS `doc_agent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_agent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `fullname` varchar(200) NOT NULL,
  `tel` varchar(64) NOT NULL,
  `sms_phone` varchar(16) NOT NULL,
  `fax_phone` varchar(16) NOT NULL,
  `alt_phone` varchar(16) NOT NULL,
  `adres` varchar(300) NOT NULL,
  `real_address` varchar(256) NOT NULL,
  `inn` varchar(24) NOT NULL,
  `kpp` varchar(16) NOT NULL,
  `leader_name` varchar(128) NOT NULL,
  `leader_name_r` varchar(128) NOT NULL,
  `pfio` text NOT NULL,
  `pdol` text NOT NULL,
  `okved` varchar(8) NOT NULL,
  `okpo` varchar(10) NOT NULL,
  `ogrn` varchar(16) NOT NULL,
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
  `bonus` decimal(10,2) NOT NULL,
  `avg_sum` int(11) NOT NULL COMMENT 'Средняя сумма оборотов агента за период',
  `price_id` int(11) DEFAULT NULL,
  `no_bulk_prices` tinyint(4) NOT NULL,
  `no_retail_prices` tinyint(4) NOT NULL,
  `no_bonuses` tinyint(4) NOT NULL,
  `region` int(11) DEFAULT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`),
  KEY `fullname` (`fullname`),
  KEY `tel` (`tel`),
  KEY `inn` (`inn`),
  KEY `type` (`type`),
  KEY `pasp_num` (`pasp_num`,`pasp_date`,`pasp_kem`),
  KEY `p_agent` (`p_agent`),
  KEY `kpp` (`kpp`),
  KEY `ogrn` (`ogrn`),
  KEY `region` (`region`),
  CONSTRAINT `doc_agent_ibfk_1` FOREIGN KEY (`region`) REFERENCES `delivery_regions` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='pcomment - printable comment';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_agent`
--

LOCK TABLES `doc_agent` WRITE;
/*!40000 ALTER TABLE `doc_agent` DISABLE KEYS */;
INSERT INTO `doc_agent` VALUES (1,'Частное лицо','','','','','','','','','','','','','','','','','','','','',0,'',1,'','0000-00-00','','',0,0,'0000-00-00',0,NULL,0.00,0,NULL,0,0,0,NULL);
/*!40000 ALTER TABLE `doc_agent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_agent_dov`
--

DROP TABLE IF EXISTS `doc_agent_dov`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_agent_dov` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_agent_dov`
--

LOCK TABLES `doc_agent_dov` WRITE;
/*!40000 ALTER TABLE `doc_agent_dov` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_agent_dov` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_agent_group`
--

DROP TABLE IF EXISTS `doc_agent_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_agent_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `pid` int(11) NOT NULL,
  `desc` varchar(100) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_agent_group`
--

LOCK TABLES `doc_agent_group` WRITE;
/*!40000 ALTER TABLE `doc_agent_group` DISABLE KEYS */;
INSERT INTO `doc_agent_group` VALUES (1,'Покупатели',0,''),(2,'Поставщики',0,'');
/*!40000 ALTER TABLE `doc_agent_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_base`
--

DROP TABLE IF EXISTS `doc_base`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base` (
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
  `unit` int(11) DEFAULT NULL COMMENT 'Единица измерения',
  `warranty` int(11) NOT NULL,
  `warranty_type` tinyint(4) NOT NULL,
  `meta_description` varchar(256) NOT NULL,
  `meta_keywords` varchar(128) NOT NULL,
  `title_tag` varchar(128) NOT NULL,
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `buy_time` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
  `transit_cnt` int(11) NOT NULL DEFAULT '0',
  `mult` int(11) NOT NULL COMMENT 'Кратность',
  `bulkcnt` int(11) NOT NULL COMMENT 'Количество оптом',
  `analog_group` varchar(32) NOT NULL,
  `mass` double NOT NULL,
  `nds` tinyint(4) DEFAULT NULL COMMENT 'Ставка НДС',
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uniq` (`group`,`name`),
  UNIQUE KEY `vc` (`vc`),
  KEY `group` (`group`),
  KEY `name` (`name`),
  KEY `cost_date` (`cost_date`),
  KEY `hidden` (`hidden`),
  KEY `unit` (`unit`),
  KEY `stock` (`stock`),
  KEY `likvid` (`likvid`),
  KEY `country` (`country`),
  KEY `create_time` (`create_time`),
  KEY `buy_time` (`buy_time`),
  KEY `transit_cnt` (`transit_cnt`),
  KEY `analog_group` (`analog_group`),
  KEY `mass` (`mass`),
  CONSTRAINT `doc_base_ibfk_1` FOREIGN KEY (`group`) REFERENCES `doc_group` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_ibfk_2` FOREIGN KEY (`unit`) REFERENCES `class_unit` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_ibfk_3` FOREIGN KEY (`country`) REFERENCES `class_country` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_base`
--

LOCK TABLES `doc_base` WRITE;
/*!40000 ALTER TABLE `doc_base` DISABLE KEYS */;
INSERT INTO `doc_base` VALUES (3,1,'Товар 1','',NULL,'',0.00,0,'',0.00,'0000-00-00 00:00:00',0,0,0,NULL,0,0,'','','','2016-02-11 13:31:24','1970-01-01 00:00:00',0,0,0,'',0,NULL);
/*!40000 ALTER TABLE `doc_base` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_base_attachments`
--

DROP TABLE IF EXISTS `doc_base_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base_attachments` (
  `pos_id` int(11) NOT NULL,
  `attachment_id` int(11) NOT NULL,
  UNIQUE KEY `uni` (`pos_id`,`attachment_id`),
  KEY `attachment_id` (`attachment_id`),
  CONSTRAINT `doc_base_attachments_ibfk_1` FOREIGN KEY (`pos_id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_attachments_ibfk_2` FOREIGN KEY (`attachment_id`) REFERENCES `attachments` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='ÐŸÑ€Ð¸ÐºÑ€ÐµÐ¿Ð»Ñ‘Ð½Ð½Ñ‹Ðµ Ñ„Ð°Ð¹Ð»Ñ‹';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_base_attachments`
--

LOCK TABLES `doc_base_attachments` WRITE;
/*!40000 ALTER TABLE `doc_base_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_base_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_base_cnt`
--

DROP TABLE IF EXISTS `doc_base_cnt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base_cnt` (
  `id` int(11) NOT NULL,
  `sklad` tinyint(4) NOT NULL,
  `cnt` decimal(10,3) NOT NULL,
  `mesto` varchar(32) NOT NULL,
  `mincnt` varchar(8) NOT NULL,
  `revision_date` date NOT NULL COMMENT 'Дата ревизии',
  PRIMARY KEY (`id`,`sklad`),
  KEY `cnt` (`cnt`),
  KEY `mesto` (`mesto`),
  KEY `mincnt` (`mincnt`),
  KEY `revision_date` (`revision_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_base_cnt`
--

LOCK TABLES `doc_base_cnt` WRITE;
/*!40000 ALTER TABLE `doc_base_cnt` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_base_cnt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_base_cost`
--

DROP TABLE IF EXISTS `doc_base_cost`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base_cost` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pos_id` int(11) NOT NULL,
  `cost_id` int(11) NOT NULL,
  `type` varchar(5) NOT NULL,
  `value` decimal(8,2) NOT NULL,
  `accuracy` tinyint(4) NOT NULL,
  `direction` tinyint(4) NOT NULL,
  `rrp_firm_id` int(11) DEFAULT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uniq` (`pos_id`,`cost_id`),
  KEY `group_id` (`pos_id`),
  KEY `cost_id` (`cost_id`),
  KEY `value` (`value`),
  KEY `type` (`type`),
  CONSTRAINT `doc_base_cost_ibfk_1` FOREIGN KEY (`pos_id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_cost_ibfk_2` FOREIGN KEY (`cost_id`) REFERENCES `doc_cost` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_base_cost`
--

LOCK TABLES `doc_base_cost` WRITE;
/*!40000 ALTER TABLE `doc_base_cost` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_base_cost` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_base_dop`
--

DROP TABLE IF EXISTS `doc_base_dop`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base_dop` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) DEFAULT '0',
  `d_int` double NOT NULL DEFAULT '0',
  `d_ext` double NOT NULL DEFAULT '0',
  `size` double NOT NULL DEFAULT '0',
  `mass` double NOT NULL DEFAULT '0',
  `analog` varchar(20) NOT NULL,
  `koncost` double NOT NULL DEFAULT '0',
  `strana` varchar(20) NOT NULL,
  `ntd` varchar(32) NOT NULL,
  `transit` int(11) NOT NULL,
  `reserve` int(11) NOT NULL,
  `offer` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `type` (`type`),
  KEY `d_int` (`d_int`),
  KEY `d_ext` (`d_ext`),
  KEY `size` (`size`),
  KEY `mass` (`mass`),
  KEY `analog` (`analog`),
  KEY `koncost` (`koncost`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_base_dop`
--

LOCK TABLES `doc_base_dop` WRITE;
/*!40000 ALTER TABLE `doc_base_dop` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_base_dop` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_base_dop_type`
--

DROP TABLE IF EXISTS `doc_base_dop_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base_dop_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(70) NOT NULL,
  `desc` text NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_base_dop_type`
--

LOCK TABLES `doc_base_dop_type` WRITE;
/*!40000 ALTER TABLE `doc_base_dop_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_base_dop_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_base_gparams`
--

DROP TABLE IF EXISTS `doc_base_gparams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base_gparams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_base_gparams`
--

LOCK TABLES `doc_base_gparams` WRITE;
/*!40000 ALTER TABLE `doc_base_gparams` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_base_gparams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_base_img`
--

DROP TABLE IF EXISTS `doc_base_img`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base_img` (
  `pos_id` int(11) NOT NULL,
  `img_id` int(11) NOT NULL,
  `default` tinyint(4) NOT NULL,
  UNIQUE KEY `pos_id` (`pos_id`,`img_id`),
  KEY `default` (`default`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_base_img`
--

LOCK TABLES `doc_base_img` WRITE;
/*!40000 ALTER TABLE `doc_base_img` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_base_img` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_base_kompl`
--

DROP TABLE IF EXISTS `doc_base_kompl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base_kompl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pos_id` int(11) NOT NULL COMMENT 'id наименования',
  `kompl_id` int(11) NOT NULL COMMENT 'id комплектующего',
  `cnt` double NOT NULL COMMENT 'количество',
  UNIQUE KEY `id` (`id`),
  KEY `kompl_id` (`kompl_id`),
  KEY `cnt` (`cnt`),
  KEY `pos_id` (`pos_id`),
  CONSTRAINT `doc_base_kompl_ibfk_1` FOREIGN KEY (`pos_id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_kompl_ibfk_2` FOREIGN KEY (`kompl_id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Комплектующие - из чего состоит эта позиция';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_base_kompl`
--

LOCK TABLES `doc_base_kompl` WRITE;
/*!40000 ALTER TABLE `doc_base_kompl` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_base_kompl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_base_links`
--

DROP TABLE IF EXISTS `doc_base_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pos1_id` int(11) NOT NULL,
  `pos2_id` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uni` (`pos1_id`,`pos2_id`),
  KEY `pos1_id` (`pos1_id`),
  KEY `pos2_id` (`pos2_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Связи товаров';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_base_links`
--

LOCK TABLES `doc_base_links` WRITE;
/*!40000 ALTER TABLE `doc_base_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_base_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_base_params`
--

DROP TABLE IF EXISTS `doc_base_params`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base_params` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) DEFAULT NULL,
  `name` varchar(32) NOT NULL COMMENT 'Отображаемое наименование параметра',
  `codename` varchar(32) NOT NULL COMMENT 'Кодовое название для скриптов',
  `type` varchar(8) NOT NULL,
  `unit_id` int(11) DEFAULT NULL COMMENT 'Кодовое название для скриптов',
  `hidden` tinyint(4) NOT NULL COMMENT 'Флаг сокрытия',
  `ym_assign` varchar(128) NOT NULL,
  `secret` tinyint(4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `param` (`name`),
  KEY `pgroup_id` (`group_id`),
  KEY `ym_assign` (`ym_assign`),
  KEY `unit_id` (`unit_id`),
  KEY `hidden` (`hidden`),
  KEY `secret` (`secret`),
  CONSTRAINT `doc_base_params_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `class_unit` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_base_params`
--

LOCK TABLES `doc_base_params` WRITE;
/*!40000 ALTER TABLE `doc_base_params` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_base_params` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_base_pcollections_list`
--

DROP TABLE IF EXISTS `doc_base_pcollections_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base_pcollections_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Наборы свойств складской номенклатуры';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_base_pcollections_list`
--

LOCK TABLES `doc_base_pcollections_list` WRITE;
/*!40000 ALTER TABLE `doc_base_pcollections_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_base_pcollections_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_base_pcollections_set`
--

DROP TABLE IF EXISTS `doc_base_pcollections_set`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base_pcollections_set` (
  `collection_id` int(11) NOT NULL,
  `param_id` int(11) NOT NULL,
  UNIQUE KEY `uniq` (`collection_id`,`param_id`),
  KEY `collection_id` (`collection_id`),
  KEY `param_id` (`param_id`),
  CONSTRAINT `doc_base_pcollections_set_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `doc_base_pcollections_list` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_pcollections_set_ibfk_2` FOREIGN KEY (`param_id`) REFERENCES `doc_base_params` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Список параметров в наборе';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_base_pcollections_set`
--

LOCK TABLES `doc_base_pcollections_set` WRITE;
/*!40000 ALTER TABLE `doc_base_pcollections_set` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_base_pcollections_set` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_base_values`
--

DROP TABLE IF EXISTS `doc_base_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base_values` (
  `id` int(11) NOT NULL,
  `param_id` int(11) NOT NULL,
  `value` varchar(256) NOT NULL,
  `intval` int(11) NOT NULL,
  `doubleval` double NOT NULL,
  `strval` varchar(512) NOT NULL,
  UNIQUE KEY `unique` (`id`,`param_id`),
  KEY `id` (`id`),
  KEY `param` (`param_id`),
  KEY `value` (`value`(255)),
  KEY `intval` (`intval`),
  KEY `doubleval` (`doubleval`),
  KEY `strval` (`strval`(255)),
  CONSTRAINT `doc_base_values_ibfk_1` FOREIGN KEY (`id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_values_ibfk_2` FOREIGN KEY (`param_id`) REFERENCES `doc_base_params` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_base_values`
--

LOCK TABLES `doc_base_values` WRITE;
/*!40000 ALTER TABLE `doc_base_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_base_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_cost`
--

DROP TABLE IF EXISTS `doc_cost`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_cost` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(25) NOT NULL,
  `type` varchar(5) NOT NULL,
  `value` decimal(8,2) NOT NULL COMMENT 'Значение цены',
  `vid` tinyint(4) NOT NULL COMMENT 'Вид цены определяет места её использования',
  `accuracy` int(11) NOT NULL,
  `direction` int(11) NOT NULL,
  `context` varchar(8) NOT NULL COMMENT 'Контекст цены определяет места её использования',
  `priority` tinyint(4) NOT NULL COMMENT 'Приоритет задаёт очерёдность цен с одним контекстом',
  `bulk_threshold` int(11) NOT NULL COMMENT 'Порог включения цены по сумме заказа',
  `acc_threshold` int(11) NOT NULL COMMENT 'Порог включения цены по накопленной сумме',
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_cost`
--

LOCK TABLES `doc_cost` WRITE;
/*!40000 ALTER TABLE `doc_cost` DISABLE KEYS */;
INSERT INTO `doc_cost` VALUES (1,'Филиал','pp',0.00,0,2,0,'',0,0,0),(2,'Оптовая','pp',0.00,1,2,0,'d',0,0,0),(3,'Розничная (+20%)','pp',20.00,0,1,1,'r',0,0,0),(4,'Мелкий опт (до 3%)','pp',-3.00,-1,2,0,'sb',6,10000,50000),(5,'Средний опт (до 5%)','pp',-5.00,-2,2,0,'b',5,20000,100000),(6,'Крупный опт (до 7%)','pp',-7.00,0,2,0,'b',4,50000,200000),(7,'Серебряный партнёр (до 10','pp',-10.00,0,2,0,'b',3,100000,300000),(8,'Золотой партнёр (до 12%)','pp',-12.00,0,2,0,'b',2,150000,500000),(9,'Платиновый партнёр (до 15','pp',-15.00,0,2,0,'b',1,250000,600000);
/*!40000 ALTER TABLE `doc_cost` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_ctypes`
--

DROP TABLE IF EXISTS `doc_ctypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_ctypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account` varchar(8) NOT NULL,
  `name` varchar(128) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `account` (`account`),
  KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='Статьи доходов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_ctypes`
--

LOCK TABLES `doc_ctypes` WRITE;
/*!40000 ALTER TABLE `doc_ctypes` DISABLE KEYS */;
INSERT INTO `doc_ctypes` VALUES (1,'91','Прочие доходы'),(2,'62','Оплата за товар'),(3,'42','переоценка'),(4,'76','Возвраты');
/*!40000 ALTER TABLE `doc_ctypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_dopdata`
--

DROP TABLE IF EXISTS `doc_dopdata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_dopdata` (
  `doc` int(11) NOT NULL,
  `param` varchar(20) NOT NULL,
  `value` varchar(150) NOT NULL,
  UNIQUE KEY `doc` (`doc`,`param`),
  KEY `value` (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_dopdata`
--

LOCK TABLES `doc_dopdata` WRITE;
/*!40000 ALTER TABLE `doc_dopdata` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_dopdata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_dtypes`
--

DROP TABLE IF EXISTS `doc_dtypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_dtypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account` varchar(8) NOT NULL COMMENT 'Бух. счет',
  `name` varchar(128) NOT NULL,
  `adm` tinyint(4) NOT NULL,
  `r_flag` tinyint(4) NOT NULL,
  `codename` varchar(16) DEFAULT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `codename` (`codename`),
  KEY `name` (`name`),
  KEY `adm` (`adm`),
  KEY `account` (`account`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COMMENT='Статьи расходов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_dtypes`
--

LOCK TABLES `doc_dtypes` WRITE;
/*!40000 ALTER TABLE `doc_dtypes` DISABLE KEYS */;
INSERT INTO `doc_dtypes` VALUES (1,'761','Аренда земли, эл /энергия, ',1,0,NULL),(2,'701','Фонд Заработной платы цехов  (по пятницам)',0,0,NULL),(3,'101','Канц,товары, хоз,тов  материалы, и т.д',1,0,NULL),(4,'70','Фонд з/пл офис,склад,хоз отдел',1,0,NULL),(5,'911','Расчетно кассовое обслуживание',1,0,NULL),(6,'60','Закупка товара на склад,транс-ые расходы за сч',0,0,NULL),(7,'4408',' Чай ,продукты в т,ч животным',1,0,NULL),(8,'102','Расходы склада-скотч,картон,перчатки,стрейч,коробки',1,0,NULL),(9,'4406','Расходы на связь, телефония,интернет,мобилные операторы',1,0,NULL);
/*!40000 ALTER TABLE `doc_dtypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_group`
--

DROP TABLE IF EXISTS `doc_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_group` (
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_group`
--

LOCK TABLES `doc_group` WRITE;
/*!40000 ALTER TABLE `doc_group` DISABLE KEYS */;
INSERT INTO `doc_group` VALUES (1,'Товары','',0,0,0,'','','','');
/*!40000 ALTER TABLE `doc_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_group_cost`
--

DROP TABLE IF EXISTS `doc_group_cost`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_group_cost` (
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
  KEY `type` (`type`),
  CONSTRAINT `doc_group_cost_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `doc_group` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `doc_group_cost_ibfk_2` FOREIGN KEY (`cost_id`) REFERENCES `doc_cost` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_group_cost`
--

LOCK TABLES `doc_group_cost` WRITE;
/*!40000 ALTER TABLE `doc_group_cost` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_group_cost` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_group_params`
--

DROP TABLE IF EXISTS `doc_group_params`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_group_params` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) DEFAULT NULL,
  `param_id` int(11) DEFAULT NULL,
  `show_in_filter` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq` (`group_id`,`param_id`),
  KEY `fk_doc_group_params_doc_group1` (`group_id`),
  KEY `fk_doc_group_params_doc_base_params1` (`param_id`),
  CONSTRAINT `fk_doc_group_params_doc_base_params1` FOREIGN KEY (`param_id`) REFERENCES `doc_base_params` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_doc_group_params_doc_group1` FOREIGN KEY (`group_id`) REFERENCES `doc_group` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_group_params`
--

LOCK TABLES `doc_group_params` WRITE;
/*!40000 ALTER TABLE `doc_group_params` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_group_params` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_img`
--

DROP TABLE IF EXISTS `doc_img`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_img` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` varchar(4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_img`
--

LOCK TABLES `doc_img` WRITE;
/*!40000 ALTER TABLE `doc_img` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_img` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_kassa`
--

DROP TABLE IF EXISTS `doc_kassa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_kassa` (
  `ids` varchar(8) CHARACTER SET latin1 NOT NULL,
  `num` int(11) NOT NULL,
  `name` varchar(96) NOT NULL,
  `ballance` decimal(10,2) NOT NULL,
  `bik` varchar(16) NOT NULL,
  `rs` varchar(32) NOT NULL,
  `ks` varchar(32) NOT NULL,
  `firm_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  UNIQUE KEY `ids` (`ids`,`num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_kassa`
--

LOCK TABLES `doc_kassa` WRITE;
/*!40000 ALTER TABLE `doc_kassa` DISABLE KEYS */;
INSERT INTO `doc_kassa` VALUES ('bank',1,'Главный банк',0.00,'','','',NULL,''),('kassa',1,'основная касса',0.00,'','','',NULL,'');
/*!40000 ALTER TABLE `doc_kassa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_list`
--

DROP TABLE IF EXISTS `doc_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_list` (
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
  `p_doc` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_list`
--

LOCK TABLES `doc_list` WRITE;
/*!40000 ALTER TABLE `doc_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_list_pos`
--

DROP TABLE IF EXISTS `doc_list_pos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_list_pos` (
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
  KEY `page` (`page`),
  CONSTRAINT `doc_list_pos_ibfk_2` FOREIGN KEY (`tovar`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `doc_list_pos_ibfk_3` FOREIGN KEY (`doc`) REFERENCES `doc_list` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_list_pos`
--

LOCK TABLES `doc_list_pos` WRITE;
/*!40000 ALTER TABLE `doc_list_pos` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_list_pos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_list_sn`
--

DROP TABLE IF EXISTS `doc_list_sn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_list_sn` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pos_id` int(11) NOT NULL COMMENT 'ID товара',
  `num` varchar(64) NOT NULL COMMENT 'Серийный номер',
  `prix_list_pos` int(11) NOT NULL COMMENT 'Строка поступления',
  `rasx_list_pos` int(11) DEFAULT NULL COMMENT 'Строка реализации',
  UNIQUE KEY `id` (`id`),
  KEY `pos_id` (`pos_id`),
  KEY `num` (`num`),
  KEY `prix_list_pos` (`prix_list_pos`),
  KEY `rasx_list_pos` (`rasx_list_pos`),
  CONSTRAINT `doc_list_sn_ibfk_1` FOREIGN KEY (`pos_id`) REFERENCES `doc_base` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `doc_list_sn_ibfk_3` FOREIGN KEY (`rasx_list_pos`) REFERENCES `doc_list_pos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `doc_list_sn_ibfk_4` FOREIGN KEY (`prix_list_pos`) REFERENCES `doc_list_pos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Серийные номера';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_list_sn`
--

LOCK TABLES `doc_list_sn` WRITE;
/*!40000 ALTER TABLE `doc_list_sn` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_list_sn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_log`
--

DROP TABLE IF EXISTS `doc_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_log` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_log`
--

LOCK TABLES `doc_log` WRITE;
/*!40000 ALTER TABLE `doc_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_sklady`
--

DROP TABLE IF EXISTS `doc_sklady`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_sklady` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `dnc` tinyint(1) NOT NULL DEFAULT '0',
  `firm_id` int(11) DEFAULT NULL,
  `hidden` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `name` (`name`),
  KEY `firm_id` (`firm_id`),
  KEY `firm_id_2` (`firm_id`),
  KEY `hidden` (`hidden`),
  CONSTRAINT `doc_sklady_ibfk_1` FOREIGN KEY (`firm_id`) REFERENCES `doc_vars` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_sklady`
--

LOCK TABLES `doc_sklady` WRITE;
/*!40000 ALTER TABLE `doc_sklady` DISABLE KEYS */;
INSERT INTO `doc_sklady` VALUES (1,'Основной склад',0,NULL,0);
/*!40000 ALTER TABLE `doc_sklady` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_types`
--

DROP TABLE IF EXISTS `doc_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=26 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_types`
--

LOCK TABLES `doc_types` WRITE;
/*!40000 ALTER TABLE `doc_types` DISABLE KEYS */;
INSERT INTO `doc_types` VALUES (1,'Поступление'),(2,'Реализация'),(3,'Заявка покупателя'),(4,'Банк - приход'),(5,'Банк - расход'),(6,'Приходный кассовый ордер'),(7,'Расходный кассовый ордер'),(8,'Перемещение товара'),(9,'Перемещение средств (касса)'),(10,'Доверенность'),(11,'Предложение поставщика'),(12,'Товар в пути'),(13,'Коммерческое предложение'),(14,'Договор'),(15,'Реализация (оперативная)'),(16,'Спецификация'),(17,'Сборка изделия'),(18,'Корректировка долга'),(19,'Корректировка бонусов'),(20,'Реализация за бонусы'),(21,'Заявка на производство'),(22,'Приходный кассовый ордер (оперативный)'),(23,'Пропуск'),(24,'Информация о платеже'),(25,'Акт корректировки');
/*!40000 ALTER TABLE `doc_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_vars`
--

DROP TABLE IF EXISTS `doc_vars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_vars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firm_type` varchar(4) NOT NULL,
  `firm_name` varchar(150) NOT NULL,
  `firm_director` varchar(100) NOT NULL,
  `firm_director_r` varchar(64) NOT NULL,
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
  `no_retailprices` tinyint(4) NOT NULL,
  `pricecoeff` decimal(6,3) NOT NULL,
  `firm_kladovshik_id` int(11) NOT NULL,
  `firm_kladovshik_doljn` varchar(64) NOT NULL,
  `firm_store_lock` smallint(6) NOT NULL COMMENT 'Работать только со своими складами',
  `firm_bank_lock` smallint(6) NOT NULL COMMENT 'Работать только со своими банками',
  `firm_till_lock` smallint(6) NOT NULL COMMENT 'Работать только со своими кассами',
  `firm_regnum` varchar(16) NOT NULL,
  `firm_regdate` date NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_vars`
--

LOCK TABLES `doc_vars` WRITE;
/*!40000 ALTER TABLE `doc_vars` DISABLE KEYS */;
INSERT INTO `doc_vars` VALUES (1,'','Наша Фирма','','','','','','','','','','','','','','','',0,0,0.000,0,'',0,0,0,'','0000-00-00');
/*!40000 ALTER TABLE `doc_vars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `errorlog`
--

DROP TABLE IF EXISTS `errorlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `errorlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page` varchar(128) NOT NULL,
  `referer` varchar(128) NOT NULL,
  `class` varchar(64) NOT NULL,
  `code` int(11) NOT NULL,
  `useragent` varchar(256) NOT NULL,
  `ip` varchar(18) NOT NULL,
  `msg` text NOT NULL,
  `file` varchar(128) NOT NULL,
  `line` int(11) NOT NULL,
  `trace` text NOT NULL,
  `date` datetime NOT NULL,
  `uid` int(11) DEFAULT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `page` (`page`),
  KEY `referer` (`referer`),
  KEY `date` (`date`),
  KEY `agent` (`useragent`,`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `errorlog`
--

LOCK TABLES `errorlog` WRITE;
/*!40000 ALTER TABLE `errorlog` DISABLE KEYS */;
/*!40000 ALTER TABLE `errorlog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `factory_builders`
--

DROP TABLE IF EXISTS `factory_builders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `factory_builders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `active` tinyint(4) NOT NULL,
  `store_id` int(11) DEFAULT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `factory_builders`
--

LOCK TABLES `factory_builders` WRITE;
/*!40000 ALTER TABLE `factory_builders` DISABLE KEYS */;
/*!40000 ALTER TABLE `factory_builders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `factory_data`
--

DROP TABLE IF EXISTS `factory_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `factory_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sklad_id` int(11) NOT NULL,
  `builder_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `pos_id` int(11) NOT NULL,
  `cnt` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uni` (`sklad_id`,`builder_id`,`date`,`pos_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `factory_data`
--

LOCK TABLES `factory_data` WRITE;
/*!40000 ALTER TABLE `factory_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `factory_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `firm_info`
--

DROP TABLE IF EXISTS `firm_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `firm_info` (
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
  `rrp` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `sign` (`signature`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `firm_info`
--

LOCK TABLES `firm_info` WRITE;
/*!40000 ALTER TABLE `firm_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `firm_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `firm_info_struct`
--

DROP TABLE IF EXISTS `firm_info_struct`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `firm_info_struct` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `firm_info_struct`
--

LOCK TABLES `firm_info_struct` WRITE;
/*!40000 ALTER TABLE `firm_info_struct` DISABLE KEYS */;
/*!40000 ALTER TABLE `firm_info_struct` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `intkb`
--

DROP TABLE IF EXISTS `intkb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `intkb` (
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
  KEY `changeautor` (`changeautor`),
  CONSTRAINT `intkb_ibfk_1` FOREIGN KEY (`autor`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `intkb_ibfk_2` FOREIGN KEY (`changeautor`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `intkb`
--

LOCK TABLES `intkb` WRITE;
/*!40000 ALTER TABLE `intkb` DISABLE KEYS */;
/*!40000 ALTER TABLE `intkb` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_call_requests`
--

DROP TABLE IF EXISTS `log_call_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_call_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `phone` varchar(32) NOT NULL,
  `request_date` datetime NOT NULL,
  `call_date` varchar(32) NOT NULL,
  `ip` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_call_requests`
--

LOCK TABLES `log_call_requests` WRITE;
/*!40000 ALTER TABLE `log_call_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_call_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loginfo`
--

DROP TABLE IF EXISTS `loginfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loginfo` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loginfo`
--

LOCK TABLES `loginfo` WRITE;
/*!40000 ALTER TABLE `loginfo` DISABLE KEYS */;
/*!40000 ALTER TABLE `loginfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(8) NOT NULL,
  `title` varchar(64) NOT NULL,
  `text` text NOT NULL,
  `date` datetime NOT NULL,
  `autor` int(11) NOT NULL,
  `ex_date` date NOT NULL,
  `img_ext` varchar(4) NOT NULL,
  `hidden` tinyint(4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `type` (`type`),
  KEY `ex_date` (`ex_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notes` (
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
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notes`
--

LOCK TABLES `notes` WRITE;
/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parsed_price`
--

DROP TABLE IF EXISTS `parsed_price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parsed_price` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firm` int(11) NOT NULL,
  `pos` int(11) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `nal` varchar(10) NOT NULL,
  `from` int(11) NOT NULL,
  `selected` tinyint(4) DEFAULT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parsed_price`
--

LOCK TABLES `parsed_price` WRITE;
/*!40000 ALTER TABLE `parsed_price` DISABLE KEYS */;
/*!40000 ALTER TABLE `parsed_price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `photogalery`
--

DROP TABLE IF EXISTS `photogalery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `photogalery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `comment` varchar(50) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `photogalery`
--

LOCK TABLES `photogalery` WRITE;
/*!40000 ALTER TABLE `photogalery` DISABLE KEYS */;
/*!40000 ALTER TABLE `photogalery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `price`
--

DROP TABLE IF EXISTS `price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `price` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price`
--

LOCK TABLES `price` WRITE;
/*!40000 ALTER TABLE `price` DISABLE KEYS */;
/*!40000 ALTER TABLE `price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prices_replaces`
--

DROP TABLE IF EXISTS `prices_replaces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prices_replaces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `search_str` varchar(16) NOT NULL,
  `replace_str` varchar(256) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `search_str` (`search_str`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Список замен для регулярных выражений анализатора прайсов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prices_replaces`
--

LOCK TABLES `prices_replaces` WRITE;
/*!40000 ALTER TABLE `prices_replaces` DISABLE KEYS */;
/*!40000 ALTER TABLE `prices_replaces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ps_counter`
--

DROP TABLE IF EXISTS `ps_counter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ps_counter` (
  `date` date NOT NULL DEFAULT '0000-00-00',
  `query` int(11) NOT NULL DEFAULT '0',
  `ps` int(11) NOT NULL DEFAULT '0',
  `counter` int(11) NOT NULL,
  PRIMARY KEY (`date`,`query`,`ps`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ps_counter`
--

LOCK TABLES `ps_counter` WRITE;
/*!40000 ALTER TABLE `ps_counter` DISABLE KEYS */;
/*!40000 ALTER TABLE `ps_counter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ps_parser`
--

DROP TABLE IF EXISTS `ps_parser`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ps_parser` (
  `parametr` varchar(20) NOT NULL,
  `data` varchar(50) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ps_parser`
--

LOCK TABLES `ps_parser` WRITE;
/*!40000 ALTER TABLE `ps_parser` DISABLE KEYS */;
/*!40000 ALTER TABLE `ps_parser` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ps_query`
--

DROP TABLE IF EXISTS `ps_query`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ps_query` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `query` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ps_query`
--

LOCK TABLES `ps_query` WRITE;
/*!40000 ALTER TABLE `ps_query` DISABLE KEYS */;
/*!40000 ALTER TABLE `ps_query` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ps_settings`
--

DROP TABLE IF EXISTS `ps_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ps_settings` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `icon` varchar(3) NOT NULL,
  `name` varchar(15) NOT NULL,
  `template` varchar(150) NOT NULL,
  `template_like` varchar(50) NOT NULL,
  `prioritet` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ps_settings`
--

LOCK TABLES `ps_settings` WRITE;
/*!40000 ALTER TABLE `ps_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `ps_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seekdata`
--

DROP TABLE IF EXISTS `seekdata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seekdata` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seekdata`
--

LOCK TABLES `seekdata` WRITE;
/*!40000 ALTER TABLE `seekdata` DISABLE KEYS */;
/*!40000 ALTER TABLE `seekdata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sites`
--

DROP TABLE IF EXISTS `sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `email` varchar(32) NOT NULL,
  `jid` varchar(32) NOT NULL,
  `short_name` varchar(16) NOT NULL,
  `display_name` varchar(64) NOT NULL,
  `default_firm_id` int(11) NOT NULL,
  `default_bank_id` int(11) NOT NULL,
  `default_cash_id` int(11) NOT NULL,
  `default_agent_id` int(11) NOT NULL,
  `default_store_id` int(11) NOT NULL,
  `default_site` tinyint(4) NOT NULL,
  `site_store_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`),
  KEY `default_firm_id` (`default_firm_id`),
  KEY `default_bank_id` (`default_bank_id`),
  KEY `default_agent_id` (`default_agent_id`),
  KEY `default_store_id` (`default_store_id`),
  KEY `default_site` (`default_site`),
  CONSTRAINT `sites_ibfk_1` FOREIGN KEY (`default_firm_id`) REFERENCES `doc_vars` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `sites_ibfk_2` FOREIGN KEY (`default_agent_id`) REFERENCES `doc_agent` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `sites_ibfk_3` FOREIGN KEY (`default_store_id`) REFERENCES `doc_sklady` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sites`
--

LOCK TABLES `sites` WRITE;
/*!40000 ALTER TABLE `sites` DISABLE KEYS */;
/*!40000 ALTER TABLE `sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey`
--

DROP TABLE IF EXISTS `survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_text` text NOT NULL,
  `end_text` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `start_date` (`start_date`),
  KEY `end_date` (`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey`
--

LOCK TABLES `survey` WRITE;
/*!40000 ALTER TABLE `survey` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_answer`
--

DROP TABLE IF EXISTS `survey_answer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_answer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) NOT NULL,
  `question_num` int(11) NOT NULL,
  `answer_txt` varchar(64) NOT NULL,
  `answer_int` int(11) NOT NULL,
  `comment` varchar(256) NOT NULL,
  `uid` int(11) DEFAULT NULL,
  `ip_address` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq` (`survey_id`,`question_num`,`uid`,`ip_address`),
  KEY `survey_id` (`survey_id`),
  KEY `question_id` (`question_num`),
  KEY `uid` (`uid`),
  KEY `ip_addres` (`ip_address`),
  CONSTRAINT `survey_answer_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `survey` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `survey_answer_ibfk_3` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  CONSTRAINT `survey_answer_ibfk_4` FOREIGN KEY (`question_num`) REFERENCES `survey_question` (`question_num`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey_answer`
--

LOCK TABLES `survey_answer` WRITE;
/*!40000 ALTER TABLE `survey_answer` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey_answer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_ok`
--

DROP TABLE IF EXISTS `survey_ok`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_ok` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) NOT NULL,
  `uid` int(11) DEFAULT NULL,
  `ip` varchar(32) NOT NULL,
  `result` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `survey_id` (`survey_id`),
  KEY `uid` (`uid`),
  KEY `ip` (`ip`),
  CONSTRAINT `survey_ok_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `survey` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `survey_ok_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey_ok`
--

LOCK TABLES `survey_ok` WRITE;
/*!40000 ALTER TABLE `survey_ok` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey_ok` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_quest_option`
--

DROP TABLE IF EXISTS `survey_quest_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_quest_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_num` int(11) NOT NULL,
  `text` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq` (`survey_id`,`question_id`,`option_num`),
  KEY `survey_id` (`survey_id`),
  KEY `question_id` (`question_id`),
  KEY `num` (`option_num`),
  CONSTRAINT `survey_quest_option_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `survey` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `survey_quest_option_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `survey_question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey_quest_option`
--

LOCK TABLES `survey_quest_option` WRITE;
/*!40000 ALTER TABLE `survey_quest_option` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey_quest_option` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_question`
--

DROP TABLE IF EXISTS `survey_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) NOT NULL,
  `question_num` int(11) NOT NULL,
  `text` varchar(256) NOT NULL,
  `type` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `survey_id` (`survey_id`),
  KEY `question_num` (`question_num`),
  CONSTRAINT `survey_question_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `survey` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey_question`
--

LOCK TABLES `survey_question` WRITE;
/*!40000 ALTER TABLE `survey_question` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey_question` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_cli_status`
--

DROP TABLE IF EXISTS `sys_cli_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sys_cli_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `script` varchar(64) NOT NULL,
  `status` text NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `script` (`script`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_cli_status`
--

LOCK TABLES `sys_cli_status` WRITE;
/*!40000 ALTER TABLE `sys_cli_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_cli_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `autor` int(11) NOT NULL,
  `priority` tinyint(4) NOT NULL,
  `theme` varchar(100) NOT NULL,
  `text` text NOT NULL,
  `to_date` date NOT NULL,
  `state` varchar(16) NOT NULL,
  `resolution` varchar(16) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `date` (`date`),
  KEY `autor` (`autor`),
  KEY `theme` (`theme`),
  KEY `to_date` (`to_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets`
--

LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets_log`
--

DROP TABLE IF EXISTS `tickets_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `ticket` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `text` text NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `uid` (`uid`,`ticket`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets_log`
--

LOCK TABLES `tickets_log` WRITE;
/*!40000 ALTER TABLE `tickets_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `tickets_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets_priority`
--

DROP TABLE IF EXISTS `tickets_priority`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets_priority` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `color` varchar(6) NOT NULL,
  `comment` varchar(200) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets_priority`
--

LOCK TABLES `tickets_priority` WRITE;
/*!40000 ALTER TABLE `tickets_priority` DISABLE KEYS */;
/*!40000 ALTER TABLE `tickets_priority` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets_responsibles`
--

DROP TABLE IF EXISTS `tickets_responsibles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets_responsibles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uni` (`ticket_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets_responsibles`
--

LOCK TABLES `tickets_responsibles` WRITE;
/*!40000 ALTER TABLE `tickets_responsibles` DISABLE KEYS */;
/*!40000 ALTER TABLE `tickets_responsibles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets_state`
--

DROP TABLE IF EXISTS `tickets_state`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets_state` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets_state`
--

LOCK TABLES `tickets_state` WRITE;
/*!40000 ALTER TABLE `tickets_state` DISABLE KEYS */;
/*!40000 ALTER TABLE `tickets_state` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `traffic_denyip`
--

DROP TABLE IF EXISTS `traffic_denyip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `traffic_denyip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(20) NOT NULL,
  `host` varchar(50) NOT NULL,
  UNIQUE KEY `id_2` (`id`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Zapreshennie IP';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traffic_denyip`
--

LOCK TABLES `traffic_denyip` WRITE;
/*!40000 ALTER TABLE `traffic_denyip` DISABLE KEYS */;
/*!40000 ALTER TABLE `traffic_denyip` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ulog`
--

DROP TABLE IF EXISTS `ulog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ulog` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ulog`
--

LOCK TABLES `ulog` WRITE;
/*!40000 ALTER TABLE `ulog` DISABLE KEYS */;
/*!40000 ALTER TABLE `ulog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
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
  `last_session_id` varchar(64) NOT NULL,
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
  KEY `agent_id` (`agent_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `doc_agent` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Spisok pol''zovatelei';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_acl`
--

DROP TABLE IF EXISTS `users_acl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_acl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `object` varchar(64) NOT NULL,
  `value` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uni` (`uid`,`object`),
  KEY `uid` (`uid`),
  KEY `object` (`object`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_acl`
--

LOCK TABLES `users_acl` WRITE;
/*!40000 ALTER TABLE `users_acl` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_acl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_bad_auth`
--

DROP TABLE IF EXISTS `users_bad_auth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_bad_auth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(24) NOT NULL,
  `time` double NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `ip` (`ip`),
  KEY `date` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_bad_auth`
--

LOCK TABLES `users_bad_auth` WRITE;
/*!40000 ALTER TABLE `users_bad_auth` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_bad_auth` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_basket`
--

DROP TABLE IF EXISTS `users_basket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_basket` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `pos_id` int(11) NOT NULL,
  `cnt` int(11) NOT NULL,
  `comment` varchar(128) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uni` (`user_id`,`pos_id`),
  KEY `user_id` (`user_id`),
  KEY `pos_id` (`pos_id`),
  CONSTRAINT `users_basket_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `users_basket_ibfk_2` FOREIGN KEY (`pos_id`) REFERENCES `doc_base` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Сохранённые корзины пользователей';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_basket`
--

LOCK TABLES `users_basket` WRITE;
/*!40000 ALTER TABLE `users_basket` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_basket` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_data`
--

DROP TABLE IF EXISTS `users_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_data` (
  `uid` int(11) NOT NULL DEFAULT '0',
  `param` varchar(25) NOT NULL,
  `value` varchar(256) NOT NULL,
  UNIQUE KEY `uid` (`uid`,`param`),
  KEY `value` (`value`(255)),
  CONSTRAINT `users_data_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_data`
--

LOCK TABLES `users_data` WRITE;
/*!40000 ALTER TABLE `users_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_grouplist`
--

DROP TABLE IF EXISTS `users_grouplist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_grouplist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `comment` text CHARACTER SET utf8 NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 PACK_KEYS=0 COMMENT='Spisok grupp';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_grouplist`
--

LOCK TABLES `users_grouplist` WRITE;
/*!40000 ALTER TABLE `users_grouplist` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_grouplist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_groups_acl`
--

DROP TABLE IF EXISTS `users_groups_acl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_groups_acl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gid` int(11) NOT NULL,
  `object` varchar(64) NOT NULL,
  `value` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uni` (`gid`,`object`),
  KEY `gid` (`gid`),
  KEY `object` (`object`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Привилегии групп';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_groups_acl`
--

LOCK TABLES `users_groups_acl` WRITE;
/*!40000 ALTER TABLE `users_groups_acl` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_groups_acl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_in_group`
--

DROP TABLE IF EXISTS `users_in_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_in_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `gid` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `uid` (`uid`),
  KEY `gid` (`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Соответствие групп и пользователей';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_in_group`
--

LOCK TABLES `users_in_group` WRITE;
/*!40000 ALTER TABLE `users_in_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_in_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_login_history`
--

DROP TABLE IF EXISTS `users_login_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_login_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `ip` varchar(32) NOT NULL,
  `useragent` varchar(128) NOT NULL,
  `method` varchar(8) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_login_history`
--

LOCK TABLES `users_login_history` WRITE;
/*!40000 ALTER TABLE `users_login_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_login_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_oauth`
--

DROP TABLE IF EXISTS `users_oauth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_oauth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `client_id` varchar(256) NOT NULL,
  `client_login` varchar(128) NOT NULL,
  `server` varchar(16) NOT NULL,
  `access_token` varchar(256) NOT NULL,
  `expire` datetime NOT NULL,
  `creation` datetime NOT NULL,
  `access_token_secret` varchar(256) NOT NULL,
  `refresh_token` varchar(256) NOT NULL,
  `access_token_response` varchar(256) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `server` (`server`),
  KEY `server_id` (`client_id`(255)),
  KEY `server_login` (`client_login`),
  CONSTRAINT `users_oauth_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_oauth`
--

LOCK TABLES `users_oauth` WRITE;
/*!40000 ALTER TABLE `users_oauth` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_oauth` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_objects`
--

DROP TABLE IF EXISTS `users_objects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_objects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `object` varchar(32) NOT NULL,
  `desc` varchar(128) NOT NULL,
  `actions` varchar(128) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `object` (`object`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_objects`
--

LOCK TABLES `users_objects` WRITE;
/*!40000 ALTER TABLE `users_objects` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_objects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_openid`
--

DROP TABLE IF EXISTS `users_openid`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_openid` (
  `user_id` int(11) NOT NULL,
  `openid_identify` varchar(192) NOT NULL,
  `openid_type` int(16) NOT NULL,
  UNIQUE KEY `openid_identify` (`openid_identify`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `users_openid_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Привязка к openid';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_openid`
--

LOCK TABLES `users_openid` WRITE;
/*!40000 ALTER TABLE `users_openid` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_openid` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_unsubscribe_log`
--

DROP TABLE IF EXISTS `users_unsubscribe_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_unsubscribe_log` (
  `email` varchar(64) NOT NULL,
  `phone` varchar(32) NOT NULL,
  `time` datetime NOT NULL,
  `source` varchar(32) NOT NULL,
  `is_user` int(11) NOT NULL,
  KEY `email` (`email`),
  KEY `phone` (`phone`),
  KEY `time` (`time`),
  KEY `source` (`source`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_unsubscribe_log`
--

LOCK TABLES `users_unsubscribe_log` WRITE;
/*!40000 ALTER TABLE `users_unsubscribe_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_unsubscribe_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_worker_info`
--

DROP TABLE IF EXISTS `users_worker_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_worker_info` (
  `user_id` int(11) NOT NULL,
  `worker` tinyint(4) NOT NULL,
  `worker_email` varchar(64) NOT NULL,
  `worker_phone` varchar(16) NOT NULL,
  `worker_jid` varchar(32) NOT NULL,
  `worker_real_name` varchar(64) NOT NULL,
  `worker_real_address` varchar(256) NOT NULL,
  `worker_post_name` varchar(64) NOT NULL COMMENT 'Должность',
  UNIQUE KEY `user_id` (`user_id`),
  KEY `worker_email` (`worker_email`),
  KEY `worker_phone` (`worker_phone`),
  KEY `worker_jid` (`worker_jid`),
  CONSTRAINT `users_worker_info_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_worker_info`
--

LOCK TABLES `users_worker_info` WRITE;
/*!40000 ALTER TABLE `users_worker_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_worker_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `variables`
--

DROP TABLE IF EXISTS `variables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `variables` (
  `corrupted` tinyint(4) NOT NULL COMMENT 'Признак нарушения целостности',
  `recalc_active` int(9) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `variables`
--

LOCK TABLES `variables` WRITE;
/*!40000 ALTER TABLE `variables` DISABLE KEYS */;
INSERT INTO `variables` VALUES (0,0);
/*!40000 ALTER TABLE `variables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `votings`
--

DROP TABLE IF EXISTS `votings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `votings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Голосования';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `votings`
--

LOCK TABLES `votings` WRITE;
/*!40000 ALTER TABLE `votings` DISABLE KEYS */;
/*!40000 ALTER TABLE `votings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `votings_results`
--

DROP TABLE IF EXISTS `votings_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `votings_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `voting_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_addr` varchar(32) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq` (`voting_id`,`variant_id`,`user_id`,`ip_addr`),
  KEY `voting_id` (`voting_id`),
  KEY `vars_id` (`variant_id`),
  KEY `user_id` (`user_id`),
  KEY `ip_addr` (`ip_addr`),
  CONSTRAINT `votings_results_ibfk_1` FOREIGN KEY (`voting_id`) REFERENCES `votings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `votings_results_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  CONSTRAINT `votings_results_ibfk_4` FOREIGN KEY (`variant_id`) REFERENCES `votings_vars` (`variant_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Голоса';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `votings_results`
--

LOCK TABLES `votings_results` WRITE;
/*!40000 ALTER TABLE `votings_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `votings_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `votings_vars`
--

DROP TABLE IF EXISTS `votings_vars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `votings_vars` (
  `voting_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `text` varchar(128) NOT NULL,
  UNIQUE KEY `uni` (`voting_id`,`variant_id`),
  KEY `voting_id` (`voting_id`),
  KEY `variant_id` (`variant_id`),
  KEY `text` (`text`),
  CONSTRAINT `votings_vars_ibfk_1` FOREIGN KEY (`voting_id`) REFERENCES `votings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `votings_vars`
--

LOCK TABLES `votings_vars` WRITE;
/*!40000 ALTER TABLE `votings_vars` DISABLE KEYS */;
/*!40000 ALTER TABLE `votings_vars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wikiphoto`
--

DROP TABLE IF EXISTS `wikiphoto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wikiphoto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ext` varchar(4) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
  `comment` varchar(50) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wikiphoto`
--

LOCK TABLES `wikiphoto` WRITE;
/*!40000 ALTER TABLE `wikiphoto` DISABLE KEYS */;
/*!40000 ALTER TABLE `wikiphoto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'demo'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2016-02-11 19:34:27
