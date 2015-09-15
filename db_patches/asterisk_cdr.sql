CREATE TABLE IF NOT EXISTS `asterisk_queue_log` (
`id` int(10) unsigned NOT NULL,
  `time` datetime DEFAULT NULL,
  `callid` varchar(32) NOT NULL DEFAULT '',
  `queuename` varchar(32) NOT NULL DEFAULT '',
  `agent` varchar(32) NOT NULL DEFAULT '',
  `event` varchar(32) NOT NULL DEFAULT '',
  `data` varchar(255) NOT NULL DEFAULT '',
  `data1` varchar(20) NOT NULL,
  `data2` varchar(20) NOT NULL,
  `data3` varchar(20) NOT NULL,
  `data4` varchar(20) NOT NULL,
  `data5` varchar(20) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

ALTER TABLE `asterisk_queue_log`
 ADD PRIMARY KEY (`id`), ADD KEY `time` (`time`), ADD KEY `callid` (`callid`), ADD KEY `queuename` (`queuename`), ADD KEY `event` (`event`);

ALTER TABLE `asterisk_queue_log`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `asterisk_cdr` (
`id` int(9) unsigned NOT NULL,
  `calldate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `clid` varchar(80) NOT NULL DEFAULT '',
  `src` varchar(80) NOT NULL DEFAULT '',
  `dst` varchar(80) NOT NULL DEFAULT '',
  `dcontext` varchar(80) NOT NULL DEFAULT '',
  `channel` varchar(80) NOT NULL DEFAULT '',
  `dstchannel` varchar(80) NOT NULL DEFAULT '',
  `lastapp` varchar(80) NOT NULL DEFAULT '',
  `lastdata` varchar(80) NOT NULL DEFAULT '',
  `duration` int(11) NOT NULL DEFAULT '0',
  `billsec` int(11) NOT NULL DEFAULT '0',
  `disposition` varchar(45) NOT NULL DEFAULT '',
  `amaflags` int(11) NOT NULL DEFAULT '0',
  `accountcode` varchar(20) NOT NULL DEFAULT '',
  `uniqueid` varchar(32) NOT NULL DEFAULT '',
  `userfield` varchar(255) NOT NULL DEFAULT '',
  `filename` varchar(255) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

ALTER TABLE `asterisk_cdr`
 ADD PRIMARY KEY (`id`), ADD KEY `calldate` (`calldate`), ADD KEY `accountcode` (`accountcode`), ADD KEY `uniqueid` (`uniqueid`), ADD KEY `dst` (`dst`), ADD KEY `src` (`src`);

ALTER TABLE `asterisk_cdr`
MODIFY `id` int(9) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;



CREATE TABLE IF NOT EXISTS `agent_banks` (
`id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `bik` varchar(16) NOT NULL,
  `ks` varchar(32) NOT NULL,
  `rs` varchar(32) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

ALTER TABLE `agent_banks`
 ADD PRIMARY KEY (`id`), ADD KEY `agent_id` (`agent_id`), ADD KEY `bik` (`bik`), ADD KEY `ks` (`ks`), ADD KEY `rs` (`rs`);

ALTER TABLE `agent_banks`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;

ALTER TABLE `agent_banks`
ADD CONSTRAINT `agent_banks_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `doc_agent` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;





CREATE TABLE IF NOT EXISTS `agent_contacts` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `context` varchar(8) NOT NULL,
  `type` varchar(8) NOT NULL,
  `value` varchar(64) NOT NULL,
  `for_fax` tinyint(4) NOT NULL,
  `for_sms` tinyint(4) NOT NULL,
  `no_ads` tinyint(4) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `agent_contacts`
--
ALTER TABLE `agent_contacts`
 ADD PRIMARY KEY (`id`), ADD KEY `agent_id` (`agent_id`), ADD KEY `type` (`type`), ADD KEY `value` (`value`), ADD KEY `for_fax` (`for_fax`), ADD KEY `for_sms` (`for_sms`), ADD KEY `no_ads` (`no_ads`);


ALTER TABLE `agent_contacts`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;

ALTER TABLE `agent_contacts`
ADD CONSTRAINT `agent_contacts_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `doc_agent` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

INSERT INTO `agent_contacts` (`agent_id`, `context`, `type`, `value`) 
    SELECT `id`, 'work', 'phone',`tel` FROM `doc_agent` WHERE `tel`!='';

INSERT INTO `agent_contacts` (`agent_id`, `context`, `type`, `value`, `for_fax`) 
    SELECT `id`, 'work', 'phone',`fax_phone`,1 FROM `doc_agent` WHERE `fax_phone`!='';

INSERT INTO `agent_contacts` (`agent_id`, `context`, `type`, `value`, `for_sms`) 
    SELECT `id`, 'mobile', 'phone',`sms_phone`,1 FROM `doc_agent` WHERE `sms_phone`!='';

INSERT INTO `agent_contacts` (`agent_id`, `context`, `type`, `value`) 
    SELECT `id`, 'home', 'phone',`alt_phone` FROM `doc_agent` WHERE `alt_phone`!='';

INSERT INTO `agent_contacts` (`agent_id`, `context`, `type`, `value`, `no_ads`) 
	SELECT `id`, 'work', 'email',`email`, `no_mail` FROM `doc_agent` WHERE `email`!='';

