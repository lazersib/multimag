ALTER TABLE `doc_ctypes` ADD COLUMN `codename` VARCHAR(16) DEFAULT NULL;
ALTER TABLE `doc_ctypes` ADD UNIQUE INDEX (`codename`);

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (880);