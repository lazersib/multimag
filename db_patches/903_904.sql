ALTER TABLE `agent_contacts` ADD `person_name` VARCHAR(64) NOT NULL , ADD INDEX (`person_name`) ;
ALTER TABLE `agent_contacts` ADD `person_post` VARCHAR(64) NOT NULL , ADD INDEX (`person_post`) ;
UPDATE `agent_contacts`, `doc_agent` SET `person_name`=`pfio`, `person_post`=`pdol` WHERE `agent_contacts`.`agent_id`=`doc_agent`.`id`;


TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (904);
