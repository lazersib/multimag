ALTER TABLE `doc_vars` ADD `firm_ogrn` VARCHAR(16) NOT NULL AFTER `firm_okpo`;

REPLACE `doc_textdata` (`doc_id`, `param`, `value`) 
    SELECT `doc_list`.`id`, 'text_header', `doc_dopdata`.`value` 
    FROM `doc_list`
    INNER JOIN `doc_dopdata` ON `doc_dopdata`.`doc`=`doc_list`.`id` AND `doc_dopdata`.`param`='shapka' AND `doc_dopdata`.`value`!=''
    WHERE `type`='13';

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (915);
