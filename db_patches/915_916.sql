REPLACE `doc_textdata` (`doc_id`, `param`, `value`) 
    SELECT `doc_dopdata`.`doc`, 'salary', `doc_dopdata`.`value` 
    FROM `doc_dopdata`
    WHERE `doc_dopdata`.`param`='salary' AND `doc_dopdata`.`value`!='';
DELETE FROM `doc_dopdata` WHERE `param`='salary';

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (916);
