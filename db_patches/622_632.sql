ALTER TABLE `doc_base`	ADD `mult` INT NOT NULL COMMENT 'Кратность',
			ADD `bulkcnt` INT NOT NULL COMMENT 'Количество оптом';

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (632);


