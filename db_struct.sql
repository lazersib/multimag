-- MySQL dump 10.13  Distrib 5.1.49, for debian-linux-gnu (i486)
--
-- Host: localhost    Database: mmag_demo
-- ------------------------------------------------------
-- Server version	5.1.49-3

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
-- Table structure for table `active_user`
--

DROP TABLE IF EXISTS `active_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `active_user` (
  `id` int(11) NOT NULL DEFAULT '0',
  `uid` varchar(40) NOT NULL DEFAULT '',
  `date` double NOT NULL DEFAULT '0',
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `active_user`
--

LOCK TABLES `active_user` WRITE;
/*!40000 ALTER TABLE `active_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `active_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bad`
--

DROP TABLE IF EXISTS `bad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment` text NOT NULL,
  `date` double NOT NULL DEFAULT '0',
  `num` int(11) NOT NULL DEFAULT '0',
  `autor` varchar(30) NOT NULL DEFAULT '',
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bad`
--

LOCK TABLES `bad` WRITE;
/*!40000 ALTER TABLE `bad` DISABLE KEYS */;
/*!40000 ALTER TABLE `bad` ENABLE KEYS */;
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
  `ip` varchar(30) NOT NULL DEFAULT '',
  `agent` varchar(150) NOT NULL,
  `refer` varchar(200) NOT NULL,
  `file` varchar(20) NOT NULL DEFAULT '',
  `query` varchar(50) NOT NULL DEFAULT '',
  UNIQUE KEY `id` (`id`),
  KEY `time` (`date`),
  KEY `ip` (`ip`),
  KEY `agent` (`agent`),
  KEY `refer` (`refer`),
  KEY `file` (`file`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

-- -----------------------------------------------------
-- Table `doc_agent`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_agent` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `group` INT(11) NOT NULL ,
  `name` VARCHAR(128) NOT NULL ,
  `fullname` VARCHAR(256) NOT NULL ,
  `tel` VARCHAR(64) NOT NULL ,
  `adres` VARCHAR(512) NOT NULL ,
  `gruzopol` VARCHAR(512) NOT NULL ,
  `inn` VARCHAR(24) NOT NULL ,
  `dir_fio` VARCHAR(128) NOT NULL ,
  `dir_fio_r` VARCHAR(128) NOT NULL ,
  `pfio` VARCHAR(128) NOT NULL ,
  `pdol` VARCHAR(128) NOT NULL ,
  `okevd` VARCHAR(8) NOT NULL ,
  `okpo` VARCHAR(16) NOT NULL ,
  `rs` VARCHAR(32) NOT NULL ,
  `bank` VARCHAR(64) NOT NULL ,
  `ks` VARCHAR(32) NOT NULL ,
  `bik` INT(11) NOT NULL ,
  `email` VARCHAR(64) NOT NULL ,
  `type` TINYINT(4) NOT NULL DEFAULT '1' ,
  `pasp_num` VARCHAR(12) NOT NULL ,
  `pasp_date` DATE NOT NULL ,
  `pasp_kem` VARCHAR(64) NOT NULL ,
  `comment` TEXT NOT NULL ,
  `no_mail` TINYINT(4) NOT NULL ,
  `responsible` INT(11) NULL ,
  `data_sverki` DATE NOT NULL ,
  `dishonest` TINYINT(4) NOT NULL COMMENT 'Недобросовестный' ,
  `p_agent` int(11) DEFAULT NULL COMMENT 'Подчинение другому агенту',
  UNIQUE INDEX `id` (`id` ASC) ,
  UNIQUE INDEX `uniq_name` (`group` ASC, `name` ASC) ,
  INDEX `name` (`name` ASC) ,
  INDEX `fullname` (`fullname`(255) ASC) ,
  INDEX `tel` (`tel` ASC) ,
  INDEX `inn` (`inn` ASC) ,
  INDEX `type` (`type` ASC) ,
  INDEX `pasp_num` (`pasp_num` ASC, `pasp_date` ASC, `pasp_kem` ASC) ,
  INDEX `group` (`group` ASC) ,
  INDEX `fk_doc_agent_users1` (`responsible` ASC) ,
  CONSTRAINT `doc_agent_ibfk_1`
    FOREIGN KEY (`group` )
    REFERENCES `doc_agent_group` (`id` ),
  CONSTRAINT `doc_agent_ibfk_1`
    FOREIGN KEY (`group` )
    REFERENCES `doc_agent_group` (`id` ),
  CONSTRAINT `fk_doc_agent_users1`
    FOREIGN KEY (`responsible` )
    REFERENCES `users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 3
DEFAULT CHARACTER SET = utf8,
COMMENT = 'Список агентов' ;


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
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currency`
--

LOCK TABLES `currency` WRITE;
/*!40000 ALTER TABLE `currency` DISABLE KEYS */;
INSERT INTO `currency` VALUES (0,'RUR','1.0000'),(1,'USD','30.9190'),(2,'EUR','41.7035'),(3,'AUD','30.9190'),(4,'BYR','35.2957'),(5,'CAD','30.0535'),(6,'CHF','33.7102'),(7,'CNY','48.6270'),(8,'DKK','56.1378'),(9,'XDR','48.2865'),(10,'GBP','48.7964'),(11,'ISK','23.8315'),(12,'JPY','40.2251'),(13,'KZT','20.8884'),(14,'NOK','53.3804'),(15,'SEK','45.6019'),(16,'SGD','23.8370'),(17,'TRY','17.0447'),(18,'UAH','38.5548');
/*!40000 ALTER TABLE `currency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `db_version`
--

DROP TABLE IF EXISTS `db_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `db_version` (
  `version` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Текущая версия базы данных';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `db_version`
--

LOCK TABLES `db_version` WRITE;
/*!40000 ALTER TABLE `db_version` DISABLE KEYS */;
INSERT INTO `db_version` VALUES (272);
/*!40000 ALTER TABLE `db_version` ENABLE KEYS */;
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
  `adres` varchar(300) NOT NULL,
  `gruzopol` varchar(300) NOT NULL,
  `inn` varchar(24) NOT NULL,
  `dir_fio` varchar(128) NOT NULL,
  `dir_fio_r` varchar(128) NOT NULL,
  `pfio` text NOT NULL,
  `pdol` text NOT NULL,
  `okevd` varchar(10) NOT NULL,
  `okpo` varchar(10) NOT NULL,
  `rs` varchar(22) NOT NULL,
  `bank` varchar(60) NOT NULL,
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
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`),
  KEY `fullname` (`fullname`),
  KEY `tel` (`tel`),
  KEY `inn` (`inn`),
  KEY `type` (`type`),
  KEY `pasp_num` (`pasp_num`,`pasp_date`,`pasp_kem`),
  KEY `responsible` (`responsible`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='pcomment - printable comment';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_agent`
--

LOCK TABLES `doc_agent` WRITE;
/*!40000 ALTER TABLE `doc_agent` DISABLE KEYS */;
INSERT INTO `doc_agent` VALUES (1,'Частное лицо','Частное лицо','+7 383 000 00 00','г. Новосибирск, ул. Новосибирская 1/11','','','','','','','','','','','','',1,'',1,'','0000-00-00','','',1,0,'0000-00-00',0),(2,'Иванов И.И.','Иванов И.И.','+7 383 999 99 99','г. Новосибирск, ул. Новосибирская 1/12','','','','','','','','','','','','',2,'',1,'','0000-00-00','','',1,0,'0000-00-00',0);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 PACK_KEYS=0;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_agent_group`
--

LOCK TABLES `doc_agent_group` WRITE;
/*!40000 ALTER TABLE `doc_agent_group` DISABLE KEYS */;
INSERT INTO `doc_agent_group` VALUES (1,'Группа 1',0,''),(2,'Группа 2',0,'');
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
  `vc` varchar(32) NOT NULL,
  `desc` text NOT NULL,
  `cost` double(10,2) NOT NULL DEFAULT '0.00',
  `stock` tinyint(1) NOT NULL,
  `proizv` varchar(20) NOT NULL,
  `likvid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cost_date` datetime NOT NULL,
  `pos_type` tinyint(4) NOT NULL,
  `hidden` tinyint(4) NOT NULL,
  `no_export_yml` tinyint(4) NOT NULL,
  `unit` int(11) NOT NULL,
  `warranty` int(11) NOT NULL,
  `warranty_type` tinyint(4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `group` (`group`),
  KEY `name` (`name`),
  KEY `cost_date` (`cost_date`),
  KEY `hidden` (`hidden`),
  KEY `unit` (`unit`),
  KEY `stock` (`stock`),
  CONSTRAINT `doc_base_ibfk_1` FOREIGN KEY (`group`) REFERENCES `doc_group` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_base`
--

LOCK TABLES `doc_base` WRITE;
/*!40000 ALTER TABLE `doc_base` DISABLE KEYS */;
INSERT INTO `doc_base` VALUES (1,1,'Сапоги зимние','SAPOG','',1000.00,0,'QUXEWX','0.00','0000-00-00 00:00:00',0,0,1,1,0,0),(2,2,'синяя меховая','KU','',12000.00,0,'AKEDAYNX','0.00','0000-00-00 00:00:00',0,0,1,1,0,0),(3,1,'Сапоги летние','SAPOG2','',2000.00,0,'QUXEWX','0.00','0000-00-00 00:00:00',0,0,1,1,0,0);
/*!40000 ALTER TABLE `doc_base` ENABLE KEYS */;
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
  `cnt` double NOT NULL,
  `mesto` int(11) NOT NULL,
  `mincnt` int(11) NOT NULL,
  PRIMARY KEY (`id`,`sklad`),
  KEY `cnt` (`cnt`),
  KEY `mesto` (`mesto`),
  KEY `mincnt` (`mincnt`)
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
  `type` int(11) NOT NULL DEFAULT '0',
  `d_int` double NOT NULL DEFAULT '0',
  `d_ext` double NOT NULL DEFAULT '0',
  `size` double NOT NULL DEFAULT '0',
  `mass` double NOT NULL DEFAULT '0',
  `analog` varchar(30) NOT NULL,
  `koncost` double NOT NULL DEFAULT '0',
  `strana` varchar(20) NOT NULL,
  `tranzit` int(11) NOT NULL,
  `ntd` varchar(32) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `type` (`type`),
  KEY `d_int` (`d_int`),
  KEY `d_ext` (`d_ext`),
  KEY `size` (`size`),
  KEY `mass` (`mass`),
  KEY `analog` (`analog`),
  KEY `koncost` (`koncost`),
  KEY `ntd` (`ntd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_base_dop`
--

LOCK TABLES `doc_base_dop` WRITE;
/*!40000 ALTER TABLE `doc_base_dop` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_base_dop` ENABLE KEYS */;
UNLOCK TABLES;


-- -----------------------------------------------------
-- Table `doc_list`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_list` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `type` TINYINT(4) NOT NULL DEFAULT '0' ,
  `agent` INT(11) NOT NULL DEFAULT '0' ,
  `contract` INT(11) NULL DEFAULT NULL ,
  `comment` TEXT NOT NULL ,
  `date` BIGINT(20) NOT NULL DEFAULT '0' ,
  `ok` BIGINT(20) NOT NULL DEFAULT '0' ,
  `sklad` TINYINT(4) NOT NULL DEFAULT '0' ,
  `kassa` TINYINT(4) NOT NULL DEFAULT '0' ,
  `bank` TINYINT(4) NOT NULL DEFAULT '0' ,
  `user` INT(11) NOT NULL DEFAULT '0' ,
  `altnum` INT(11) NOT NULL ,
  `subtype` VARCHAR(4) NOT NULL ,
  `sum` DECIMAL(10,2) NOT NULL DEFAULT '0.00' ,
  `nds` INT(11) NOT NULL DEFAULT '0' ,
  `p_doc` INT(11) NOT NULL ,
  `mark_del` BIGINT(20) NOT NULL ,
  `firm_id` INT(11) NOT NULL DEFAULT '1' ,
  `err_flag` TINYINT(4) NOT NULL DEFAULT '0' ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `type` (`type` ASC) ,
  INDEX `agent` (`agent` ASC) ,
  INDEX `contract` (`contract` ASC) ,
  INDEX `date` (`date` ASC) ,
  INDEX `altnum` (`altnum` ASC) ,
  INDEX `p_doc` (`p_doc` ASC) ,
  INDEX `ok` (`ok` ASC) ,
  INDEX `sklad` (`sklad` ASC) ,
  INDEX `user` (`user` ASC) ,
  INDEX `subtype` (`subtype` ASC) ,
  INDEX `mark_del` (`mark_del` ASC) ,
  INDEX `firm_id` (`firm_id` ASC) ,
  INDEX `kassa` (`kassa` ASC, `bank` ASC) ,
  CONSTRAINT `doc_list_ibfk_5`
    FOREIGN KEY (`type` )
    REFERENCES `doc_types` (`id` ),
  CONSTRAINT `doc_list_ibfk_1`
    FOREIGN KEY (`agent` )
    REFERENCES `doc_agent` (`id` ),
  CONSTRAINT `doc_list_ibfk_2`
    FOREIGN KEY (`user` )
    REFERENCES `users` (`id` ),
  CONSTRAINT `doc_list_ibfk_3`
    FOREIGN KEY (`sklad` )
    REFERENCES `doc_sklady` (`id` ),
  CONSTRAINT `doc_list_ibfk_4`
    FOREIGN KEY (`firm_id` )
    REFERENCES `doc_vars` (`id` ),
  CONSTRAINT `doc_list_ibfk_5`
    FOREIGN KEY (`type` )
    REFERENCES `doc_types` (`id` ),
  CONSTRAINT `doc_list_ibfk_1`
    FOREIGN KEY (`agent` )
    REFERENCES `doc_agent` (`id` ),
  CONSTRAINT `doc_list_ibfk_2`
    FOREIGN KEY (`user` )
    REFERENCES `users` (`id` ),
  CONSTRAINT `doc_list_ibfk_3`
    FOREIGN KEY (`sklad` )
    REFERENCES `doc_sklady` (`id` ),
  CONSTRAINT `doc_list_ibfk_4`
    FOREIGN KEY (`firm_id` )
    REFERENCES `doc_vars` (`id` ))
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


DROP TABLE IF EXISTS `doc_base_dop_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base_dop_type` (
  `id` int(11) NOT NULL,
  `name` varchar(70) CHARACTER SET utf8 NOT NULL,
  `desc` text CHARACTER SET utf8 NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=ucs2;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_base_dop_type`
--

LOCK TABLES `doc_base_dop_type` WRITE;
/*!40000 ALTER TABLE `doc_base_dop_type` DISABLE KEYS */;
INSERT INTO `doc_base_dop_type` VALUES (-1,'',''),(0,'Радиальный шариковый однорядный типовой',''),(1,'Радиальный шариковый сферический',''),(2,'Радиальный роликовый с короткими цилиндрическими роликами',''),(3,'Радиальные роликовые сферические',''),(4,'Радиальные роликовые с игольчатыми роликами',''),(5,'Радиальные роликовые с витыми роликами',''),(6,'Радиально-упорные шариковые',''),(7,'Радиально-упорные роликовые конические',''),(8,'Шариковые упорные',''),(9,'Роликовые упорные','');
/*!40000 ALTER TABLE `doc_base_dop_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_base_gparams`
--

DROP TABLE IF EXISTS `doc_base_gparams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base_gparams` (
  `id` int(11) NOT NULL,
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
-- Table structure for table `doc_base_params`
--

DROP TABLE IF EXISTS `doc_base_params`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base_params` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `param` varchar(32) NOT NULL,
  `type` varchar(8) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `param` (`param`)
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
-- Table structure for table `doc_base_values`
--

DROP TABLE IF EXISTS `doc_base_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_base_values` (
  `id` int(11) NOT NULL,
  `param_id` int(11) NOT NULL,
  `value` varchar(32) NOT NULL,
  UNIQUE KEY `unique` (`id`,`param_id`),
  KEY `id` (`id`),
  KEY `param` (`param_id`),
  KEY `value` (`value`),
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
  `accuracy` tinyint(4) NOT NULL,
  `direction` tinyint(4) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_cost`
--

LOCK TABLES `doc_cost` WRITE;
/*!40000 ALTER TABLE `doc_cost` DISABLE KEYS */;
INSERT INTO `doc_cost` VALUES (1,'Оптовая','pp','0.00',1,2,0),(2,'Розничная','pp','15.00',0,2,0),(3,'Корпоративная','pp','-7.00',-2,2,0),(4,'Со скидкой','pp','-5.00',-1,2,0);
/*!40000 ALTER TABLE `doc_cost` ENABLE KEYS */;
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
-- Table structure for table `doc_group`
--

DROP TABLE IF EXISTS `doc_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `desc` text NOT NULL,
  `pid` int(11) NOT NULL,
  `hidelevel` tinyint(4) NOT NULL,
  `printname` varchar(50) NOT NULL,
  `no_export_yml` tinyint(4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `pid` (`pid`),
  KEY `hidelevel` (`hidelevel`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 PACK_KEYS=1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_group`
--

LOCK TABLES `doc_group` WRITE;
/*!40000 ALTER TABLE `doc_group` DISABLE KEYS */;
INSERT INTO `doc_group` VALUES (1,'Одежда','',0,0,'Куртка',0),(2,'Обувь','',0,0,'Ботинки',0);
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
  `id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `param_id` int(11) DEFAULT NULL,
  `show_in_filter` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_doc_group_params_doc_group1` (`group_id`),
  KEY `fk_doc_group_params_doc_base_params1` (`param_id`),
  KEY `show_in_filter` (`show_in_filter`),
  CONSTRAINT `fk_doc_group_params_doc_base_params1` FOREIGN KEY (`param_id`) REFERENCES `doc_base_params` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_doc_group_params_doc_group1` FOREIGN KEY (`group_id`) REFERENCES `doc_group` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_kassa`
--

LOCK TABLES `doc_kassa` WRITE;
/*!40000 ALTER TABLE `doc_kassa` DISABLE KEYS */;
INSERT INTO `doc_kassa` VALUES ('bank',1,'OАО \"Надёжный банк\"','0.00','546547','356757357536735','35735675675673563',0),('kassa',1,'Основная касса','0.00','','','',0);
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
  `err_flag` tinyint(4) NOT NULL,
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
  CONSTRAINT `doc_list_ibfk_1` FOREIGN KEY (`firm_id`) REFERENCES `doc_vars` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
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
  `cnt` int(11) NOT NULL DEFAULT '0',
  `gtd` varchar(32) NOT NULL,
  `comm` varchar(50) NOT NULL,
  `cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `page` int(11) NOT NULL DEFAULT '0',
  KEY `id` (`id`),
  KEY `doc` (`doc`),
  KEY `tovar` (`tovar`),
  KEY `sklad` (`page`)
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
  KEY `ip` (`ip`),
  KEY `object` (`object`),
  KEY `object_id` (`object_id`)
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
-- Table structure for table `doc_rasxodi`
--

DROP TABLE IF EXISTS `doc_rasxodi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_rasxodi` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `adm` tinyint(4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`),
  KEY `adm` (`adm`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 PACK_KEYS=0 COMMENT='Статьи расходов';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_rasxodi`
--

LOCK TABLES `doc_rasxodi` WRITE;
/*!40000 ALTER TABLE `doc_rasxodi` DISABLE KEYS */;
INSERT INTO `doc_rasxodi` VALUES (0,'Прочие расходы',1),(1,'Аренда офиса, склада',1),(2,'Зарплата, премии, надбавки',1),(3,'Канцелярские товары, расходные материалы',1),(4,'Представительские расходы',1),(5,'Другие (банковские) платежи',1),(6,'Закупка товара на склад',0),(7,'Закупка товара на продажу',0),(8,'Транспортные расходы',1),(9,'Расходы на связь',1),(10,'Оплата товара на реализации',0),(11,'Налоги и сборы',1),(12,'Средства под отчёт',0),(13,'Расходы на рекламу',1),(14,'Возврат товара',0);
/*!40000 ALTER TABLE `doc_rasxodi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_sklady`
--

DROP TABLE IF EXISTS `doc_sklady`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_sklady` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `comment` text NOT NULL,
  `dnc` tinyint(4) NOT NULL,
  KEY `id` (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_sklady`
--

LOCK TABLES `doc_sklady` WRITE;
/*!40000 ALTER TABLE `doc_sklady` DISABLE KEYS */;
INSERT INTO `doc_sklady` VALUES (1,'Основной склад','Это первый склад',0),(2,'Левый склад','Это второй склад',0);
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
  `name` varchar(30) NOT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 PACK_KEYS=0;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_types`
--

LOCK TABLES `doc_types` WRITE;
/*!40000 ALTER TABLE `doc_types` DISABLE KEYS */;
INSERT INTO `doc_types` VALUES (1,'Поступление'),(2,'Реализация'),(3,'Заявка покупателя'),(4,'Банк - приход'),(5,'Банк - расход'),(6,'Касса - приход'),(7,'Касса - расход'),(8,'Перемещение товара'),(9,'Перемещение средств (касса)'),(10,'Доверенность'),(11,'Предложение поставщика'),(12,'Товар в пути'),(13,'Коммерческое предложение'),(14,'Договор'),(15,'Реализация (оперативная)'),(16,'Спецификация');
/*!40000 ALTER TABLE `doc_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_units`
--

DROP TABLE IF EXISTS `doc_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_units` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(16) NOT NULL,
  `printname` varchar(8) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`),
  KEY `printname` (`printname`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_units`
--

LOCK TABLES `doc_units` WRITE;
/*!40000 ALTER TABLE `doc_units` DISABLE KEYS */;
INSERT INTO `doc_units` VALUES (1,'Штука','шт.'),(2,'Килограмм','кг.'),(3,'Грамм','гр.'),(4,'Литр','л.'),(5,'Метр','м.'),(6,'Милиметр','мм.'),(7,'Упаковка','уп.');
/*!40000 ALTER TABLE `doc_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_vars`
--

DROP TABLE IF EXISTS `doc_vars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_vars` (
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_vars`
--

LOCK TABLES `doc_vars` WRITE;
/*!40000 ALTER TABLE `doc_vars` DISABLE KEYS */;
INSERT INTO `doc_vars` VALUES (1,'ООО Главная фирма','Иванов И.И.','Иванова И.И.','Пертов В.В.','Иванов И.И.','Пертов В.В.',1,'','','','','','630083, г. Новосибирск, ул. Большевистская, 1','ООО Главная фирма 630083, г. Новосибирск, ул. Большевистская, 1','ООО Главная фирма 630083, г. Новосибирск, ул. Большевистская, 1','+7(383) 0000000 11111111','278373278',18,'');
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
  `msg` text NOT NULL,
  `date` datetime NOT NULL,
  `ip` varchar(18) NOT NULL,
  `agent` varchar(128) NOT NULL,
  `uid` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


ALTER TABLE `doc_agent` ADD CONSTRAINT `doc_agent_ibfk_1` FOREIGN KEY (`p_agent`) REFERENCES `doc_agent` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;



LOCK TABLES `errorlog` WRITE;
/*!40000 ALTER TABLE `errorlog` DISABLE KEYS */;
/*!40000 ALTER TABLE `errorlog` ENABLE KEYS */;
UNLOCK TABLES;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;


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
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `sign` (`signature`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `firm_info`
--

LOCK TABLES `firm_info` WRITE;
/*!40000 ALTER TABLE `firm_info` DISABLE KEYS */;
INSERT INTO `firm_info` VALUES (1,'СБС  ГРУПП',0,0,0,0,'sbs-group.ru',0,'1.000','2010-03-09 12:21:38');
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
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `firm_info_struct`
--

LOCK TABLES `firm_info_struct` WRITE;
/*!40000 ALTER TABLE `firm_info_struct` DISABLE KEYS */;
INSERT INTO `firm_info_struct` VALUES (1,1,'',1,4,0,3);
/*!40000 ALTER TABLE `firm_info_struct` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `sender` int(11) NOT NULL,
  `head` varchar(50) NOT NULL,
  `msg` text NOT NULL,
  `senddate` datetime NOT NULL,
  `ok` tinyint(4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `user` (`user`),
  KEY `sender` (`sender`),
  KEY `senddate` (`senddate`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
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
  UNIQUE KEY `id` (`id`),
  KEY `type` (`type`),
  KEY `ex_date` (`ex_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Dumping data for table `news`
--


INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(1, 'doc', 'Документы', '');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(2, 'doc_list', 'Журнал документов', 'view,delete');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(3, 'doc_postuplenie', 'Поступление', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(4, 'generic_articles', 'Доступ к статьям', 'view,edit,create,delete');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(5, 'sys', 'Системные объекты', '');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(6, 'generic', 'Общие объекты', '');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(7, 'sys_acl', 'Управление привилегиями', 'view,edit');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(8, 'doc_realizaciya', 'Реализация', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(9, 'doc_zayavka', 'Документ заявки', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(10, 'doc_kompredl', 'Коммерческое предложение', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(11, 'doc_dogovor', 'Договор', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(12, 'doc_doveren', 'Доверенность', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(13, 'doc_pbank', 'Приход средств в банк', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(14, 'doc_peremeshenie', 'Перемещение товара', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(15, 'doc_perkas', 'Перемещение средств в кассе', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(16, 'doc_predlojenie', 'Предложение поставщика', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(17, 'doc_rbank', 'Расход средств из банка', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(18, 'doc_realiz_op', 'Оперативная реализация', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(19, 'doc_rko', 'Расходный кассовый ордер', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(20, 'doc_sborka', 'Сборка изделия', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(21, 'doc_specific', 'Спецификация', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(22, 'doc_v_puti', 'Товар в пути', 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(23, 'list', 'Списки', '');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(24, 'list_agent', 'Агенты', 'create,edit,view');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(25, 'list_sklad', 'Склад', 'create,edit,view');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(26, 'list_price_an', 'Анализатор прайсов', 'create,edit,view,delete');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(27, 'list_agent_dov', 'Доверенные лица', 'create,edit,view');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(28, 'report', 'Отчёты', '');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(29, 'report_cash', 'Кассовый отчёт', 'view');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(30, 'generic_news', 'Новости', 'view,create,edit,delete');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(31, 'doc_service', 'Служебные функции', 'view');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(32, 'doc_scropts', 'Сценарии и операции', 'view,exec');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(33, 'log', 'Системные журналы', '');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(34, 'log_browser', 'Статистирка броузеров', 'view');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(35, 'log_error', 'Журнал ошибок', 'view');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(36, 'log_access', 'Журнал посещений', 'view');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(37, 'sys_async_task', 'Ассинхронные задачи', 'view,exec');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(38, 'sys_ip-blacklist', 'Чёрный список IP адресов', 'view,create,delete');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(39, 'sys_ip-log', 'Журнал обращений к ip адресам', 'view');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(40, 'generic_price_an', 'Анализатор прайсов', 'view');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(41, 'generic_galery', 'Фотогалерея', 'view,create,edit,delete');
INSERT INTO `users_objects` (`id`, `object`, `desc`, `actions`) VALUES(42, 'doc_pko', 'Приходный кассовый ордер' 'view,edit,create,apply,cancel,forcecancel,delete,today_cancel');

-- -----------------------------------------------------
-- Data for table `db_version`
-- -----------------------------------------------------
START TRANSACTION;
INSERT INTO `db_version` (`version`) VALUES (289);
commit;

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
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
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parsed_price`
--

LOCK TABLES `parsed_price` WRITE;
/*!40000 ALTER TABLE `parsed_price` DISABLE KEYS */;
INSERT INTO `parsed_price` VALUES (1,1,2124,'130.00','15',13);
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
-- Table structure for table `priv_message`
--

DROP TABLE IF EXISTS `priv_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `priv_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL DEFAULT '0',
  `usersend` varchar(20) NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  `date` double NOT NULL DEFAULT '0',
  `themes` varchar(30) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `priv_message`
--

LOCK TABLES `priv_message` WRITE;
/*!40000 ALTER TABLE `priv_message` DISABLE KEYS */;
/*!40000 ALTER TABLE `priv_message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_answ`
--

DROP TABLE IF EXISTS `question_answ`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `question_answ` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `q_id` int(11) NOT NULL,
  `answer` varchar(500) NOT NULL,
  `uid` int(11) NOT NULL,
  `ip` varchar(15) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `q_id` (`q_id`),
  KEY `uid` (`uid`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_answ`
--

LOCK TABLES `question_answ` WRITE;
/*!40000 ALTER TABLE `question_answ` DISABLE KEYS */;
/*!40000 ALTER TABLE `question_answ` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_ip`
--

DROP TABLE IF EXISTS `question_ip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `question_ip` (
  `ip` varchar(15) NOT NULL,
  `result` int(11) NOT NULL,
  UNIQUE KEY `ip_2` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_ip`
--

LOCK TABLES `question_ip` WRITE;
/*!40000 ALTER TABLE `question_ip` DISABLE KEYS */;
/*!40000 ALTER TABLE `question_ip` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_vars`
--

DROP TABLE IF EXISTS `question_vars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `question_vars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `q_id` int(11) NOT NULL,
  `var_id` int(11) NOT NULL,
  `text` varchar(500) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `q_id` (`q_id`,`var_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_vars`
--

LOCK TABLES `question_vars` WRITE;
/*!40000 ALTER TABLE `question_vars` DISABLE KEYS */;
/*!40000 ALTER TABLE `question_vars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` varchar(200) NOT NULL,
  `mode` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions`
--

LOCK TABLES `questions` WRITE;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seekdata`
--

DROP TABLE IF EXISTS `seekdata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seekdata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `sql` varchar(200) NOT NULL,
  `regex` varchar(256) NOT NULL,
  `group` int(11) NOT NULL,
  `regex_neg` varchar(256) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`),
  KEY `sql` (`sql`),
  KEY `regex` (`regex`)
) ENGINE=MyISAM AUTO_INCREMENT=6671 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seekdata`
--

LOCK TABLES `seekdata` WRITE;
/*!40000 ALTER TABLE `seekdata` DISABLE KEYS */;
INSERT INTO `seekdata` VALUES (2124,'','411','411',0,''),(6670,'','52714','',0,'');
/*!40000 ALTER TABLE `seekdata` ENABLE KEYS */;
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
  `to_uid` int(11) NOT NULL,
  `to_date` date NOT NULL,
  `state` tinyint(4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `date` (`date`),
  KEY `autor` (`autor`),
  KEY `theme` (`theme`),
  KEY `to_uid` (`to_uid`),
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
  `id` tinyint(4) NOT NULL,
  `name` varchar(50) NOT NULL,
  `color` varchar(6) NOT NULL,
  `comment` varchar(200) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets_priority`
--

LOCK TABLES `tickets_priority` WRITE;
/*!40000 ALTER TABLE `tickets_priority` DISABLE KEYS */;
INSERT INTO `tickets_priority` VALUES (-3,'Не важный','aaa',''),(-2,'Незначительный','0bd',''),(-1,'Низкий','55f',''),(0,'Обычный','0a0',''),(1,'Важный','b0b',''),(2,'Срочный','f92',''),(3,'Критический','f00','');
/*!40000 ALTER TABLE `tickets_priority` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets_state`
--

DROP TABLE IF EXISTS `tickets_state`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets_state` (
  `id` int(11) NOT NULL,
  `name` varchar(30) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets_state`
--

LOCK TABLES `tickets_state` WRITE;
/*!40000 ALTER TABLE `tickets_state` DISABLE KEYS */;
INSERT INTO `tickets_state` VALUES (0,'Новый'),(1,'В процессе'),(2,'Ошибочный'),(3,'Готово');
/*!40000 ALTER TABLE `tickets_state` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `traffic_declog`
--

DROP TABLE IF EXISTS `traffic_declog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `traffic_declog` (
  `uid` int(11) NOT NULL DEFAULT '0',
  `time` bigint(20) NOT NULL DEFAULT '0',
  `decball` double NOT NULL DEFAULT '0',
  `ip` varchar(15) NOT NULL DEFAULT '',
  `dest` varchar(15) NOT NULL DEFAULT '',
  `input` bigint(20) NOT NULL DEFAULT '0',
  `output` bigint(20) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traffic_declog`
--

LOCK TABLES `traffic_declog` WRITE;
/*!40000 ALTER TABLE `traffic_declog` DISABLE KEYS */;
/*!40000 ALTER TABLE `traffic_declog` ENABLE KEYS */;
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
-- Table structure for table `traffic_onoff`
--

DROP TABLE IF EXISTS `traffic_onoff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `traffic_onoff` (
  `uid` int(11) NOT NULL DEFAULT '0',
  `time` bigint(20) NOT NULL DEFAULT '0',
  `onoff` tinyint(4) NOT NULL DEFAULT '0',
  `ballance` double NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traffic_onoff`
--

LOCK TABLES `traffic_onoff` WRITE;
/*!40000 ALTER TABLE `traffic_onoff` DISABLE KEYS */;
/*!40000 ALTER TABLE `traffic_onoff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `traffic_stat`
--

DROP TABLE IF EXISTS `traffic_stat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `traffic_stat` (
  `time` bigint(20) NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL DEFAULT '0',
  `decball` double NOT NULL DEFAULT '0',
  `traffic` int(11) NOT NULL DEFAULT '0',
  `ip` varchar(16) NOT NULL DEFAULT '',
  `dest` varchar(16) NOT NULL DEFAULT '',
  `output` double NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `traffic_stat`
--

LOCK TABLES `traffic_stat` WRITE;
/*!40000 ALTER TABLE `traffic_stat` DISABLE KEYS */;
/*!40000 ALTER TABLE `traffic_stat` ENABLE KEYS */;
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
  KEY `ip_saddr` (`ip_saddr`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ulog`
--

LOCK TABLES `ulog` WRITE;
/*!40000 ALTER TABLE `ulog` DISABLE KEYS */;
/*!40000 ALTER TABLE `ulog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_badlist`
--

DROP TABLE IF EXISTS `user_badlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_badlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(30) NOT NULL DEFAULT '',
  `pass` varchar(30) NOT NULL DEFAULT '',
  `pin` varchar(30) NOT NULL DEFAULT '',
  `date` double NOT NULL DEFAULT '0',
  `ip` varchar(15) NOT NULL DEFAULT '',
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_badlist`
--

LOCK TABLES `user_badlist` WRITE;
/*!40000 ALTER TABLE `user_badlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_badlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_log`
--

DROP TABLE IF EXISTS `user_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_log` (
  `uid` int(11) NOT NULL DEFAULT '0',
  `time` bigint(20) NOT NULL DEFAULT '0',
  `motion` int(11) NOT NULL DEFAULT '0',
  `comp` int(11) NOT NULL DEFAULT '0',
  `ballance` double NOT NULL DEFAULT '0',
  `pft` double NOT NULL DEFAULT '0',
  KEY `time` (`time`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_log`
--

LOCK TABLES `user_log` WRITE;
/*!40000 ALTER TABLE `user_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `pass` varchar(32) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `date_reg` datetime NOT NULL,
  `a_manager` tinyint(4) NOT NULL DEFAULT '0',
  `a_club_admin` tinyint(4) NOT NULL DEFAULT '0',
  `a_superuser` tinyint(4) NOT NULL DEFAULT '0',
  `a_lan_user` tinyint(4) NOT NULL DEFAULT '0',
  `a_temp_user` tinyint(4) NOT NULL DEFAULT '0',
  `ballance` double NOT NULL DEFAULT '0',
  `skidka` tinyint(4) NOT NULL DEFAULT '0',
  `comment` varchar(150) NOT NULL,
  `mbcost` double NOT NULL DEFAULT '3.5',
  `active` smallint(6) NOT NULL DEFAULT '0',
  `mac` varchar(20) NOT NULL,
  `payfortime` double NOT NULL DEFAULT '0',
  `lastip` varchar(15) NOT NULL,
  `needstart` tinyint(4) NOT NULL DEFAULT '0',
  `endlocktime` bigint(20) NOT NULL DEFAULT '0',
  `stseans` bigint(15) NOT NULL DEFAULT '0',
  `tel` varchar(15) NOT NULL,
  `rname` varchar(40) NOT NULL,
  `credit` int(11) NOT NULL DEFAULT '0',
  `tarif` int(11) NOT NULL DEFAULT '0',
  `channel` int(11) NOT NULL DEFAULT '0',
  `icq` int(11) NOT NULL DEFAULT '0',
  `lastlogin` datetime NOT NULL,
  `email` varchar(50) NOT NULL,
  `confirm` varchar(35) NOT NULL,
  `subscribe` int(11) NOT NULL,
  `passch` varchar(35) NOT NULL,
  `jid` varchar(40) NOT NULL,
  `worker` tinyint(4) NOT NULL,
  `adres` varchar(256) NOT NULL,
  UNIQUE KEY `name` (`name`),
  KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 PACK_KEYS=1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (0,'anonymous','','0000-00-00 00:00:00',0,0,0,0,0,0,0,'',3.5,0,'',0,'',0,0,0,'','',0,0,0,0,'0000-00-00 00:00:00','root@localhost','',0,'','',0,''),(1,'demo','fe01ce2a7fbac8fafaed7c982a04e229','0000-00-00 00:00:00',0,0,0,0,0,0,0,'',3.5,0,'',0,'',0,0,0,'','',0,0,0,0,'2012-01-05 00:03:46','','0',0,'','',0,'');
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
  `uid` int(11) NOT NULL,
  `object` varchar(64) NOT NULL,
  `action` varchar(16) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `uid` (`uid`),
  KEY `object` (`object`),
  KEY `action` (`action`)
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
-- Table structure for table `users_data`
--

DROP TABLE IF EXISTS `users_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_data` (
  `uid` int(11) NOT NULL DEFAULT '0',
  `param` varchar(25) NOT NULL,
  `value` varchar(100) CHARACTER SET utf8 NOT NULL,
  UNIQUE KEY `uid` (`uid`,`param`),
  KEY `value` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
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
  `comment` text NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 PACK_KEYS=0 COMMENT='Spisok grupp';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_grouplist`
--

LOCK TABLES `users_grouplist` WRITE;
/*!40000 ALTER TABLE `users_grouplist` DISABLE KEYS */;
INSERT INTO `users_grouplist` VALUES (0,'anonymous','Группа незарегистрированных'),(1,'root','Группа администраторов'),(2,'manager','Группа менеджеров'),(3,'sklad','Кладовщики'),(4,'buhgalter','Бухгалтерия, работа с банками, кассой');
/*!40000 ALTER TABLE `users_grouplist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_grouprights`
--

DROP TABLE IF EXISTS `users_grouprights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_grouprights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gid` int(11) NOT NULL,
  `object` varchar(50) NOT NULL,
  `a_read` tinyint(4) NOT NULL,
  `a_write` tinyint(4) NOT NULL,
  `a_edit` tinyint(4) NOT NULL,
  `a_delete` tinyint(4) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `gid` (`gid`),
  KEY `object` (`object`)
) ENGINE=MyISAM AUTO_INCREMENT=60 DEFAULT CHARSET=utf8 PACK_KEYS=0 COMMENT='Prava grupp na dostup k objectam';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_grouprights`
--

LOCK TABLES `users_grouprights` WRITE;
/*!40000 ALTER TABLE `users_grouprights` DISABLE KEYS */;
INSERT INTO `users_grouprights` VALUES (1,2,'doc_list',1,1,1,0),(2,2,'doc_postuplenie',1,1,1,0),(3,1,'doc_list',1,1,1,1),(4,1,'doc_postuplenie',1,1,1,1),(5,1,'rights',1,1,1,1),(8,2,'doc',1,0,0,0),(9,1,'doc',1,1,1,1),(10,1,'doc_realizaciya',1,1,1,1),(11,1,'doc_otchet',1,1,1,1),(12,1,'doc_zayavka',1,1,1,1),(13,2,'doc_zayavka',1,1,1,1),(14,2,'doc_realizaciya',1,1,0,0),(15,2,'doc_otchet',1,0,0,0),(16,2,'doc_journal',1,0,0,0),(17,1,'doc_settings',1,1,1,1),(22,2,'doc_predlojenie',1,1,1,0),(21,1,'doc_predlojenie',1,1,1,1),(23,1,'doc_v_puti',1,1,1,1),(24,1,'doc_peremeshenie',1,1,1,1),(25,2,'wiki',1,1,1,1),(26,2,'doc_v_puti',1,1,1,1),(27,2,'doc_peremeshenie',1,1,1,0),(28,2,'doc_kompredl',1,1,1,1),(29,2,'doc_rbank',1,1,0,0),(30,2,'doc_pbank',1,1,1,0),(31,2,'doc_pko',1,1,1,0),(32,2,'doc_rko',1,1,1,0),(33,2,'tickets',1,1,0,0),(34,1,'tickets',1,1,1,1),(35,1,'doc_pko',1,1,1,1),(36,1,'doc_rko',1,1,1,1),(37,1,'doc_rbank',1,1,1,1),(38,1,'doc_pbank',1,1,1,1),(39,1,'doc_kompredl',1,1,1,1),(40,1,'deny_ip',1,1,1,1),(41,1,'doc_error_create',1,1,1,1),(42,1,'doc_agent_ext',1,1,1,1),(43,2,'price_analyzer',1,1,1,1),(44,3,'doc_postuplenie',1,1,1,0),(45,3,'doc_list',1,1,1,0),(46,3,'doc',1,0,0,0),(47,3,'tickets',1,1,0,0),(48,1,'doc_dogovor',1,1,1,1),(49,3,'doc_v_puti',1,1,1,0),(50,1,'doc_service',1,1,1,0),(51,2,'doc_error_create',0,0,0,0),(52,2,'doc_dogovor',1,1,1,0),(53,2,'doc_specific',1,1,1,0),(54,3,'doc_peremeshenie',0,0,0,0),(55,2,'doc_sklad_groups',1,1,1,0),(56,3,'doc_realizaciya',0,0,0,0),(57,3,'doc_predlojenie',1,1,1,0),(58,3,'doc_rko',1,1,1,0),(59,3,'doc_pko',1,1,1,0);
/*!40000 ALTER TABLE `users_grouprights` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_groups`
--

DROP TABLE IF EXISTS `users_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `gid` int(11) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `uid` (`uid`),
  KEY `gid` (`gid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Sootvetstvie grupp i pol''zovatelei';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_groups`
--

LOCK TABLES `users_groups` WRITE;
/*!40000 ALTER TABLE `users_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_groups` ENABLE KEYS */;
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
  `action` varchar(16) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `gid` (`gid`),
  KEY `object` (`object`)
) ENGINE=InnoDB AUTO_INCREMENT=3103 DEFAULT CHARSET=latin1 COMMENT='Привилегии групп';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_groups_acl`
--

LOCK TABLES `users_groups_acl` WRITE;
/*!40000 ALTER TABLE `users_groups_acl` DISABLE KEYS */;
INSERT INTO `users_groups_acl` VALUES (173,0,'generic_articles','view'),(174,0,'generic_galery','view'),(175,0,'generic_news','view'),(1738,1,'doc_dogovor','view'),(1739,1,'doc_dogovor','edit'),(1740,1,'doc_dogovor','create'),(1741,1,'doc_dogovor','apply'),(1742,1,'doc_dogovor','cancel'),(1743,1,'doc_dogovor','forcecancel'),(1744,1,'doc_dogovor','delete'),(1745,1,'doc_dogovor','today_cancel'),(1746,1,'doc_doveren','view'),(1747,1,'doc_doveren','edit'),(1748,1,'doc_doveren','create'),(1749,1,'doc_doveren','apply'),(1750,1,'doc_doveren','cancel'),(1751,1,'doc_doveren','forcecancel'),(1752,1,'doc_doveren','delete'),(1753,1,'doc_doveren','today_cancel'),(1754,1,'doc_kompredl','view'),(1755,1,'doc_kompredl','edit'),(1756,1,'doc_kompredl','create'),(1757,1,'doc_kompredl','apply'),(1758,1,'doc_kompredl','cancel'),(1759,1,'doc_kompredl','forcecancel'),(1760,1,'doc_kompredl','delete'),(1761,1,'doc_kompredl','today_cancel'),(1762,1,'doc_list','view'),(1763,1,'doc_list','delete'),(1764,1,'doc_pbank','view'),(1765,1,'doc_pbank','edit'),(1766,1,'doc_pbank','create'),(1767,1,'doc_pbank','apply'),(1768,1,'doc_pbank','cancel'),(1769,1,'doc_pbank','forcecancel'),(1770,1,'doc_pbank','delete'),(1771,1,'doc_pbank','today_cancel'),(1772,1,'doc_peremeshenie','view'),(1773,1,'doc_peremeshenie','edit'),(1774,1,'doc_peremeshenie','create'),(1775,1,'doc_peremeshenie','apply'),(1776,1,'doc_peremeshenie','cancel'),(1777,1,'doc_peremeshenie','forcecancel'),(1778,1,'doc_peremeshenie','delete'),(1779,1,'doc_peremeshenie','today_cancel'),(1780,1,'doc_perkas','view'),(1781,1,'doc_perkas','edit'),(1782,1,'doc_perkas','create'),(1783,1,'doc_perkas','apply'),(1784,1,'doc_perkas','cancel'),(1785,1,'doc_perkas','forcecancel'),(1786,1,'doc_perkas','delete'),(1787,1,'doc_perkas','today_cancel'),(1788,1,'doc_pko','view'),(1789,1,'doc_pko','edit'),(1790,1,'doc_pko','create'),(1791,1,'doc_pko','apply'),(1792,1,'doc_pko','cancel'),(1793,1,'doc_pko','forcecancel'),(1794,1,'doc_pko','delete'),(1795,1,'doc_pko','today_cancel'),(1796,1,'doc_postuplenie','view'),(1797,1,'doc_postuplenie','edit'),(1798,1,'doc_postuplenie','create'),(1799,1,'doc_postuplenie','apply'),(1800,1,'doc_postuplenie','cancel'),(1801,1,'doc_postuplenie','forcecancel'),(1802,1,'doc_postuplenie','delete'),(1803,1,'doc_postuplenie','today_cancel'),(1804,1,'doc_predlojenie','view'),(1805,1,'doc_predlojenie','edit'),(1806,1,'doc_predlojenie','create'),(1807,1,'doc_predlojenie','apply'),(1808,1,'doc_predlojenie','cancel'),(1809,1,'doc_predlojenie','forcecancel'),(1810,1,'doc_predlojenie','delete'),(1811,1,'doc_predlojenie','today_cancel'),(1812,1,'doc_rbank','view'),(1813,1,'doc_rbank','edit'),(1814,1,'doc_rbank','create'),(1815,1,'doc_rbank','apply'),(1816,1,'doc_rbank','cancel'),(1817,1,'doc_rbank','forcecancel'),(1818,1,'doc_rbank','delete'),(1819,1,'doc_rbank','today_cancel'),(1820,1,'doc_realizaciya','view'),(1821,1,'doc_realizaciya','edit'),(1822,1,'doc_realizaciya','create'),(1823,1,'doc_realizaciya','apply'),(1824,1,'doc_realizaciya','cancel'),(1825,1,'doc_realizaciya','forcecancel'),(1826,1,'doc_realizaciya','delete'),(1827,1,'doc_realizaciya','today_cancel'),(1828,1,'doc_realiz_op','view'),(1829,1,'doc_realiz_op','edit'),(1830,1,'doc_realiz_op','create'),(1831,1,'doc_realiz_op','apply'),(1832,1,'doc_realiz_op','cancel'),(1833,1,'doc_realiz_op','forcecancel'),(1834,1,'doc_realiz_op','delete'),(1835,1,'doc_realiz_op','today_cancel'),(1836,1,'doc_rko','view'),(1837,1,'doc_rko','edit'),(1838,1,'doc_rko','create'),(1839,1,'doc_rko','apply'),(1840,1,'doc_rko','cancel'),(1841,1,'doc_rko','forcecancel'),(1842,1,'doc_rko','delete'),(1843,1,'doc_rko','today_cancel'),(1844,1,'doc_sborka','view'),(1845,1,'doc_sborka','edit'),(1846,1,'doc_sborka','create'),(1847,1,'doc_sborka','apply'),(1848,1,'doc_sborka','cancel'),(1849,1,'doc_sborka','forcecancel'),(1850,1,'doc_sborka','delete'),(1851,1,'doc_sborka','today_cancel'),(1852,1,'doc_scropts','view'),(1853,1,'doc_scropts','exec'),(1854,1,'doc_service','view'),(1855,1,'doc_specific','view'),(1856,1,'doc_specific','edit'),(1857,1,'doc_specific','create'),(1858,1,'doc_specific','apply'),(1859,1,'doc_specific','cancel'),(1860,1,'doc_specific','forcecancel'),(1861,1,'doc_specific','delete'),(1862,1,'doc_specific','today_cancel'),(1863,1,'doc_v_puti','view'),(1864,1,'doc_v_puti','edit'),(1865,1,'doc_v_puti','create'),(1866,1,'doc_v_puti','apply'),(1867,1,'doc_v_puti','cancel'),(1868,1,'doc_v_puti','forcecancel'),(1869,1,'doc_v_puti','delete'),(1870,1,'doc_v_puti','today_cancel'),(1871,1,'doc_zayavka','view'),(1872,1,'doc_zayavka','edit'),(1873,1,'doc_zayavka','create'),(1874,1,'doc_zayavka','apply'),(1875,1,'doc_zayavka','cancel'),(1876,1,'doc_zayavka','forcecancel'),(1877,1,'doc_zayavka','delete'),(1878,1,'doc_zayavka','today_cancel'),(1879,1,'generic_articles','view'),(1880,1,'generic_articles','edit'),(1881,1,'generic_articles','create'),(1882,1,'generic_articles','delete'),(1883,1,'generic_galery','view'),(1884,1,'generic_galery','edit'),(1885,1,'generic_galery','create'),(1886,1,'generic_galery','delete'),(1887,1,'generic_news','view'),(1888,1,'generic_news','edit'),(1889,1,'generic_news','create'),(1890,1,'generic_news','delete'),(1891,1,'generic_price_an','view'),(1892,1,'list_agent','view'),(1893,1,'list_agent','edit'),(1894,1,'list_agent','create'),(1895,1,'list_agent_dov','view'),(1896,1,'list_agent_dov','edit'),(1897,1,'list_agent_dov','create'),(1898,1,'list_price_an','view'),(1899,1,'list_price_an','edit'),(1900,1,'list_price_an','create'),(1901,1,'list_price_an','delete'),(1902,1,'list_sklad','view'),(1903,1,'list_sklad','edit'),(1904,1,'list_sklad','create'),(1905,1,'log_access','view'),(1906,1,'log_browser','view'),(1907,1,'log_error','view'),(1908,1,'report_cash','view'),(1909,1,'sys_acl','view'),(1910,1,'sys_acl','edit'),(1911,1,'sys_async_task','view'),(1912,1,'sys_async_task','exec'),(1913,1,'sys_ip-blacklist','view'),(1914,1,'sys_ip-blacklist','create'),(1915,1,'sys_ip-blacklist','delete'),(1916,1,'sys_ip-log','view'),(2170,2,'doc_dogovor','view'),(2171,2,'doc_dogovor','edit'),(2172,2,'doc_dogovor','create'),(2173,2,'doc_dogovor','apply'),(2174,2,'doc_dogovor','cancel'),(2175,2,'doc_dogovor','today_cancel'),(2176,2,'doc_doveren','view'),(2177,2,'doc_doveren','edit'),(2178,2,'doc_doveren','create'),(2179,2,'doc_doveren','apply'),(2180,2,'doc_doveren','cancel'),(2181,2,'doc_doveren','today_cancel'),(2182,2,'doc_kompredl','view'),(2183,2,'doc_kompredl','edit'),(2184,2,'doc_kompredl','create'),(2185,2,'doc_kompredl','apply'),(2186,2,'doc_kompredl','cancel'),(2187,2,'doc_kompredl','today_cancel'),(2188,2,'doc_list','view'),(2189,2,'doc_pbank','view'),(2190,2,'doc_perkas','view'),(2191,2,'doc_pko','view'),(2192,2,'doc_pko','edit'),(2193,2,'doc_pko','create'),(2194,2,'doc_pko','apply'),(2195,2,'doc_postuplenie','view'),(2196,2,'doc_predlojenie','view'),(2197,2,'doc_rbank','view'),(2198,2,'doc_realizaciya','view'),(2199,2,'doc_realizaciya','edit'),(2200,2,'doc_realizaciya','create'),(2201,2,'doc_realizaciya','apply'),(2202,2,'doc_realizaciya','today_cancel'),(2203,2,'doc_realiz_op','view'),(2204,2,'doc_rko','view'),(2205,2,'doc_rko','edit'),(2206,2,'doc_rko','create'),(2207,2,'doc_rko','apply'),(2208,2,'doc_sborka','view'),(2209,2,'doc_sborka','edit'),(2210,2,'doc_sborka','create'),(2211,2,'doc_sborka','apply'),(2212,2,'doc_sborka','cancel'),(2213,2,'doc_specific','view'),(2214,2,'doc_specific','edit'),(2215,2,'doc_specific','create'),(2216,2,'doc_specific','apply'),(2217,2,'doc_specific','cancel'),(2218,2,'doc_specific','today_cancel'),(2219,2,'doc_v_puti','view'),(2220,2,'doc_zayavka','view'),(2221,2,'doc_zayavka','edit'),(2222,2,'doc_zayavka','create'),(2223,2,'doc_zayavka','apply'),(2224,2,'doc_zayavka','cancel'),(2225,2,'doc_zayavka','today_cancel'),(2226,2,'generic_articles','view'),(2227,2,'generic_articles','edit'),(2228,2,'generic_articles','create'),(2229,2,'generic_galery','view'),(2230,2,'generic_galery','edit'),(2231,2,'generic_galery','create'),(2232,2,'generic_news','view'),(2233,2,'generic_news','edit'),(2234,2,'generic_news','create'),(2235,2,'generic_price_an','view'),(2236,2,'list_agent','view'),(2237,2,'list_agent','edit'),(2238,2,'list_agent','create'),(2239,2,'list_agent_dov','view'),(2240,2,'list_agent_dov','edit'),(2241,2,'list_agent_dov','create'),(2242,2,'list_price_an','view'),(2243,2,'list_sklad','view'),(2244,2,'list_sklad','edit'),(2245,2,'list_sklad','create'),(2246,2,'log_access','view'),(2247,2,'log_browser','view'),(2248,2,'log_error','view'),(2249,2,'report_cash','view'),(2250,2,'sys_acl','view'),(2251,2,'sys_async_task','view'),(2252,2,'sys_ip-blacklist','view'),(2253,2,'sys_ip-log','view'),(2897,4,'doc_dogovor','view'),(2898,4,'doc_dogovor','edit'),(2899,4,'doc_dogovor','create'),(2900,4,'doc_dogovor','apply'),(2901,4,'doc_dogovor','today_cancel'),(2902,4,'doc_doveren','view'),(2903,4,'doc_doveren','edit'),(2904,4,'doc_doveren','create'),(2905,4,'doc_doveren','apply'),(2906,4,'doc_doveren','today_cancel'),(2907,4,'doc_kompredl','view'),(2908,4,'doc_kompredl','edit'),(2909,4,'doc_kompredl','create'),(2910,4,'doc_kompredl','apply'),(2911,4,'doc_kompredl','today_cancel'),(2912,4,'doc_list','view'),(2913,4,'doc_pbank','view'),(2914,4,'doc_pbank','edit'),(2915,4,'doc_pbank','create'),(2916,4,'doc_pbank','apply'),(2917,4,'doc_pbank','today_cancel'),(2918,4,'doc_perkas','view'),(2919,4,'doc_perkas','edit'),(2920,4,'doc_perkas','create'),(2921,4,'doc_perkas','apply'),(2922,4,'doc_perkas','today_cancel'),(2923,4,'doc_pko','view'),(2924,4,'doc_pko','edit'),(2925,4,'doc_pko','create'),(2926,4,'doc_pko','apply'),(2927,4,'doc_postuplenie','view'),(2928,4,'doc_postuplenie','edit'),(2929,4,'doc_postuplenie','create'),(2930,4,'doc_postuplenie','apply'),(2931,4,'doc_postuplenie','today_cancel'),(2932,4,'doc_predlojenie','view'),(2933,4,'doc_predlojenie','edit'),(2934,4,'doc_predlojenie','create'),(2935,4,'doc_predlojenie','apply'),(2936,4,'doc_predlojenie','today_cancel'),(2937,4,'doc_rbank','view'),(2938,4,'doc_rbank','edit'),(2939,4,'doc_rbank','create'),(2940,4,'doc_rbank','apply'),(2941,4,'doc_rbank','today_cancel'),(2942,4,'doc_realizaciya','view'),(2943,4,'doc_realizaciya','edit'),(2944,4,'doc_realizaciya','create'),(2945,4,'doc_realizaciya','apply'),(2946,4,'doc_realizaciya','today_cancel'),(2947,4,'doc_realiz_op','view'),(2948,4,'doc_realiz_op','edit'),(2949,4,'doc_realiz_op','create'),(2950,4,'doc_realiz_op','apply'),(2951,4,'doc_realiz_op','today_cancel'),(2952,4,'doc_rko','view'),(2953,4,'doc_sborka','view'),(2954,4,'doc_sborka','edit'),(2955,4,'doc_sborka','create'),(2956,4,'doc_sborka','apply'),(2957,4,'doc_sborka','today_cancel'),(2958,4,'doc_scropts','view'),(2959,4,'doc_scropts','exec'),(2960,4,'doc_service','view'),(2961,4,'doc_specific','view'),(2962,4,'doc_specific','edit'),(2963,4,'doc_specific','create'),(2964,4,'doc_specific','apply'),(2965,4,'doc_specific','today_cancel'),(2966,4,'doc_v_puti','view'),(2967,4,'doc_v_puti','today_cancel'),(2968,4,'doc_zayavka','view'),(2969,4,'doc_zayavka','edit'),(2970,4,'doc_zayavka','create'),(2971,4,'doc_zayavka','apply'),(2972,4,'doc_zayavka','today_cancel'),(2973,4,'generic_articles','view'),(2974,4,'generic_articles','edit'),(2975,4,'generic_articles','create'),(2976,4,'generic_galery','view'),(2977,4,'generic_galery','edit'),(2978,4,'generic_galery','create'),(2979,4,'generic_news','view'),(2980,4,'generic_news','edit'),(2981,4,'generic_news','create'),(2982,4,'generic_price_an','view'),(2983,4,'list_agent','view'),(2984,4,'list_agent','edit'),(2985,4,'list_agent','create'),(2986,4,'list_agent_dov','view'),(2987,4,'list_agent_dov','edit'),(2988,4,'list_agent_dov','create'),(2989,4,'list_price_an','view'),(2990,4,'list_price_an','edit'),(2991,4,'list_price_an','create'),(2992,4,'list_sklad','view'),(2993,4,'list_sklad','edit'),(2994,4,'list_sklad','create'),(2995,4,'log_access','view'),(2996,4,'log_browser','view'),(2997,4,'log_error','view'),(2998,4,'report_cash','view'),(2999,4,'sys_acl','view'),(3000,4,'sys_async_task','view'),(3001,4,'sys_async_task','exec'),(3002,4,'sys_ip-blacklist','view'),(3003,4,'sys_ip-log','view'),(3053,3,'doc_kompredl','view'),(3054,3,'doc_list','view'),(3055,3,'doc_pbank','view'),(3056,3,'doc_peremeshenie','view'),(3057,3,'doc_peremeshenie','edit'),(3058,3,'doc_peremeshenie','create'),(3059,3,'doc_pko','view'),(3060,3,'doc_postuplenie','view'),(3061,3,'doc_postuplenie','edit'),(3062,3,'doc_postuplenie','create'),(3063,3,'doc_postuplenie','apply'),(3064,3,'doc_predlojenie','view'),(3065,3,'doc_predlojenie','edit'),(3066,3,'doc_predlojenie','create'),(3067,3,'doc_predlojenie','apply'),(3068,3,'doc_predlojenie','cancel'),(3069,3,'doc_predlojenie','today_cancel'),(3070,3,'doc_rbank','view'),(3071,3,'doc_realizaciya','view'),(3072,3,'doc_realiz_op','view'),(3073,3,'doc_rko','view'),(3074,3,'doc_sborka','view'),(3075,3,'doc_sborka','edit'),(3076,3,'doc_sborka','create'),(3077,3,'doc_sborka','apply'),(3078,3,'doc_service','view'),(3079,3,'doc_specific','view'),(3080,3,'doc_v_puti','view'),(3081,3,'doc_v_puti','edit'),(3082,3,'doc_v_puti','create'),(3083,3,'doc_v_puti','apply'),(3084,3,'doc_v_puti','cancel'),(3085,3,'doc_v_puti','today_cancel'),(3086,3,'doc_zayavka','view'),(3087,3,'doc_zayavka','edit'),(3088,3,'doc_zayavka','create'),(3089,3,'generic_articles','view'),(3090,3,'generic_galery','view'),(3091,3,'generic_news','view'),(3092,3,'list_agent','view'),(3093,3,'list_agent','edit'),(3094,3,'list_agent','create'),(3095,3,'list_price_an','view'),(3096,3,'list_sklad','view'),(3097,3,'list_sklad','edit'),(3098,3,'list_sklad','create'),(3099,3,'sys_ip-blacklist','view'),(3100,3,'sys_ip-blacklist','create'),(3101,3,'sys_ip-blacklist','delete'),(3102,3,'sys_ip-log','view');
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
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_objects`
--

LOCK TABLES `users_objects` WRITE;
/*!40000 ALTER TABLE `users_objects` DISABLE KEYS */;
INSERT INTO `users_objects` VALUES (1,'doc','Документы',''),(2,'doc_list','Журнал документов','view,delete'),(3,'doc_postuplenie','Поступление','view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),(4,'generic_articles','Доступ к статьям','view,edit,create,delete'),(5,'sys','Системные объекты',''),(6,'generic','Общие объекты',''),(7,'sys_acl','Управление привилегиями','view,edit'),(8,'doc_realizaciya','Реализация','view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),(9,'doc_zayavka','Документ заявки','view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),(10,'doc_kompredl','Коммерческое предложение','view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),(11,'doc_dogovor','Договор','view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),(12,'doc_doveren','Доверенность','view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),(13,'doc_pbank','Приход средств в банк','view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),(14,'doc_peremeshenie','Перемещение товара','view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),(15,'doc_perkas','Перемещение средств в кассе','view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),(16,'doc_predlojenie','Предложение поставщика','view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),(17,'doc_rbank','Расход средств из банка','view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),(18,'doc_realiz_op','Оперативная реализация','view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),(19,'doc_rko','Расходный кассовый ордер','view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),(20,'doc_sborka','Сборка изделия','view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),(21,'doc_specific','Спецификация','view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),(22,'doc_v_puti','Товар в пути','view,edit,create,apply,cancel,forcecancel,delete,today_cancel'),(23,'list','Списки',''),(24,'list_agent','Агенты','create,edit,view'),(25,'list_sklad','Склад','create,edit,view'),(26,'list_price_an','Анализатор прайсов','create,edit,view,delete'),(27,'list_agent_dov','Доверенные лица','create,edit,view'),(28,'report','Отчёты',''),(29,'report_cash','Кассовый отчёт','view'),(30,'generic_news','Новости','view,create,edit,delete'),(31,'doc_service','Служебные функции','view'),(32,'doc_scropts','Сценарии и операции','view,exec'),(33,'log','Системные журналы',''),(34,'log_browser','Статистирка броузеров','view'),(35,'log_error','Журнал ошибок','view'),(36,'log_access','Журнал посещений','view'),(37,'sys_async_task','Ассинхронные задачи','view,exec'),(38,'sys_ip-blacklist','Чёрный список IP адресов','view,create,delete'),(39,'sys_ip-log','Журнал обращений к ip адресам','view'),(40,'generic_price_an','Анализатор прайсов','view'),(41,'generic_galery','Фотогалерея','view,create,edit,delete'),(42,'doc_pko','Приходный кассовый ордер','view,edit,create,apply,cancel,forcecancel,delete,today_cancel');
/*!40000 ALTER TABLE `users_objects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `variables`
--

DROP TABLE IF EXISTS `variables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `variables` (
  `counter` int(11) NOT NULL DEFAULT '0',
  `nightdate` bigint(20) NOT NULL DEFAULT '0',
  `sel` int(11) NOT NULL DEFAULT '0',
  `asel` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `variables`
--

LOCK TABLES `variables` WRITE;
/*!40000 ALTER TABLE `variables` DISABLE KEYS */;
INSERT INTO `variables` VALUES (8742,1158944400,0,0);
/*!40000 ALTER TABLE `variables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki`
--

DROP TABLE IF EXISTS `wiki`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki` (
  `name` varchar(50) NOT NULL,
  `date` datetime NOT NULL,
  `autor` int(11) NOT NULL,
  `changed` datetime NOT NULL,
  `changeautor` int(11) NOT NULL,
  `text` text NOT NULL,
  `img_ext` varchar(4) NOT NULL,
  UNIQUE KEY `name` (`name`),
  KEY `date` (`date`),
  KEY `autor` (`autor`),
  KEY `changed` (`changed`),
  KEY `changeautor` (`changeautor`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki`
--

LOCK TABLES `wiki` WRITE;
/*!40000 ALTER TABLE `wiki` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wikiphoto`
--

DROP TABLE IF EXISTS `wikiphoto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wikiphoto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
-- Dumping routines for database 'mmag_demo'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-01-05  0:07:34
