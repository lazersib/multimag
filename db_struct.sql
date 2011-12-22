SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


-- -----------------------------------------------------
-- Table `users`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(32) NOT NULL ,
  `pass` VARCHAR(32) NOT NULL ,
  `passch` VARCHAR(32) NOT NULL ,
  `email` VARCHAR(64) NOT NULL ,
  `date_reg` DATETIME NOT NULL ,
  `confirm` VARCHAR(32) NOT NULL ,
  `subscribe` INT(11) NOT NULL COMMENT 'Podpiska na novosti i dr informaciy' ,
  `lastlogin` DATETIME NOT NULL ,
  `rname` VARCHAR(32) NOT NULL ,
  `tel` VARCHAR(16) NOT NULL ,
  `adres` VARCHAR(128) NOT NULL ,
  `worker` TINYINT(4) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `passch` (`passch` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Список пользователей' ;


-- -----------------------------------------------------
-- Table `comments`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `comments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `date` DATETIME NOT NULL ,
  `object_name` VARCHAR(16) NOT NULL COMMENT 'Имя(тип) объекта комментирования' ,
  `object_id` INT(11) NOT NULL COMMENT 'ID объекта комментирования' ,
  `autor_name` VARCHAR(16) NOT NULL COMMENT 'Имя автора (анонимного)' ,
  `autor_email` VARCHAR(32) NOT NULL COMMENT 'Электронная почта анонимного автора' ,
  `autor_id` INT(11) NOT NULL COMMENT 'UID автора' ,
  `text` TEXT NOT NULL COMMENT 'Текст коментария' ,
  `rate` TINYINT(4) NOT NULL COMMENT 'Оценка объекта (0-5)' ,
  `ip` VARCHAR(16) NOT NULL ,
  `user_agent` VARCHAR(128) NOT NULL ,
  `response` VARCHAR(512) NOT NULL COMMENT 'Ответ администрации' ,
  `responser` INT(11) NULL COMMENT 'Автор ответа' ,
  PRIMARY KEY (`id`) ,
  INDEX `object_name` (`object_name` ASC) ,
  INDEX `object_id` (`object_id` ASC) ,
  INDEX `rate` (`rate` ASC) ,
  INDEX `date` (`date` ASC) ,
  INDEX `fk_comments_users1` (`autor_id` ASC) ,
  INDEX `fk_comments_users2` (`responser` ASC) ,
  CONSTRAINT `fk_comments_users1`
    FOREIGN KEY (`autor_id` )
    REFERENCES `users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_comments_users2`
    FOREIGN KEY (`responser` )
    REFERENCES `users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Коментарии к товарам, новостям, статьям и пр.' ;


-- -----------------------------------------------------
-- Table `counter`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `counter` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `date` BIGINT(20) NOT NULL DEFAULT '0' ,
  `ip` VARCHAR(32) NOT NULL DEFAULT '' ,
  `agent` VARCHAR(128) NOT NULL DEFAULT '' ,
  `refer` VARCHAR(128) NOT NULL ,
  `file` VARCHAR(32) NOT NULL DEFAULT '' ,
  `query` VARCHAR(128) NOT NULL DEFAULT '' ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `time` (`date` ASC) ,
  INDEX `ip` (`ip` ASC) ,
  INDEX `agent` (`agent` ASC) ,
  INDEX `refer` (`refer` ASC) ,
  INDEX `file` (`file` ASC) )
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci, 
COMMENT = 'Журнал посещений' ;


-- -----------------------------------------------------
-- Table `currency`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `currency` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(8) NOT NULL ,
  `coeff` DECIMAL(8,4) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  UNIQUE INDEX `name` (`name` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Курсы валют' ;


-- -----------------------------------------------------
-- Table `doc_agent_group`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_agent_group` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(64) NOT NULL ,
  `pid` INT(11) NOT NULL ,
  `desc` VARCHAR(128) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `pid` (`pid` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 3
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Список групп агентов' ;


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


-- -----------------------------------------------------
-- Table `doc_agent_dov`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_agent_dov` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `ag_id` INT(11) NOT NULL ,
  `name` VARCHAR(64) NOT NULL ,
  `name2` VARCHAR(64) NOT NULL ,
  `surname` VARCHAR(64) NOT NULL ,
  `range` VARCHAR(64) NOT NULL ,
  `pasp_ser` VARCHAR(8) NOT NULL ,
  `pasp_num` VARCHAR(16) NOT NULL ,
  `pasp_kem` VARCHAR(128) NOT NULL ,
  `pasp_data` VARCHAR(16) NOT NULL ,
  `mark_del` BIGINT(20) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `ag_id` (`ag_id` ASC) ,
  INDEX `name` (`name` ASC) ,
  INDEX `name2` (`name2` ASC) ,
  INDEX `surname` (`surname` ASC) ,
  INDEX `range` (`range` ASC) ,
  INDEX `pasp_ser` (`pasp_ser` ASC) ,
  INDEX `pasp_num` (`pasp_num` ASC) ,
  INDEX `pasp_kem` (`pasp_kem` ASC) ,
  INDEX `pasp_data` (`pasp_data` ASC) ,
  INDEX `mark_del` (`mark_del` ASC) ,
  CONSTRAINT `doc_agent_dov_ibfk_1`
    FOREIGN KEY (`ag_id` )
    REFERENCES `doc_agent` (`id` ),
  CONSTRAINT `doc_agent_dov_ibfk_1`
    FOREIGN KEY (`ag_id` )
    REFERENCES `doc_agent` (`id` ))
ENGINE = InnoDB
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Список доверенных лиц' ;


-- -----------------------------------------------------
-- Table `doc_units`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_units` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(16) NOT NULL ,
  `printname` VARCHAR(8) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `name` (`name` ASC) ,
  INDEX `printname` (`printname` ASC) ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Единицы измерения' ;


-- -----------------------------------------------------
-- Table `doc_group`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_group` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(64) NOT NULL ,
  `desc` TEXT NOT NULL ,
  `pid` INT(11) NOT NULL ,
  `hidelevel` TINYINT(4) NOT NULL ,
  `printname` VARCHAR(64) NOT NULL ,
  `no_export_yml` TINYINT(4) NOT NULL COMMENT 'Не экспортировать в YML' ,
  UNIQUE INDEX `name` (`name` ASC) ,
  INDEX `pid` (`pid` ASC) ,
  INDEX `hidelevel` (`hidelevel` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 3
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `doc_base`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_base` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `group` INT(11) NOT NULL DEFAULT '0' COMMENT 'ID группы' ,
  `name` VARCHAR(128) NOT NULL COMMENT 'Наименование' ,
  `vc` VARCHAR(32) NOT NULL COMMENT 'Код изготовителя' ,
  `desc` TEXT NOT NULL COMMENT 'Описание' ,
  `cost` DOUBLE NOT NULL DEFAULT '0.00' COMMENT 'Цена' ,
  `stock` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Распродажа' ,
  `proizv` VARCHAR(24) NOT NULL COMMENT 'Производитель' ,
  `likvid` DECIMAL(6,2) NOT NULL DEFAULT '0.00' COMMENT 'Ликвидность' ,
  `cost_date` DATETIME NOT NULL COMMENT 'Дата изменения цены' ,
  `pos_type` TINYINT(4) NOT NULL COMMENT 'Товар - услуга' ,
  `hidden` TINYINT(4) NOT NULL COMMENT 'Индекс сокрытия' ,
  `no_export_yml` TINYINT(4) NOT NULL COMMENT 'Не экспортировать в YML' ,
  `unit` INT(11) NOT NULL COMMENT 'Единица измерения' ,
  `warranty` INT(11) NOT NULL COMMENT 'Гарантийный срок' ,
  `warranty_type` TINYINT(4) NOT NULL COMMENT 'Гарантия производителя' ,
  `rate` TINYINT(4) NOT NULL COMMENT 'Рейтинг товара' ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `group` (`group` ASC) ,
  INDEX `name` (`name` ASC) ,
  INDEX `stock` (`stock` ASC) ,
  INDEX `cost_date` (`cost_date` ASC) ,
  INDEX `hidden` (`hidden` ASC) ,
  INDEX `unit` (`unit` ASC) ,
  INDEX `vc` (`vc` ASC) ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `doc_base_ibfk_2`
    FOREIGN KEY (`unit` )
    REFERENCES `doc_units` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_ibfk_1`
    FOREIGN KEY (`group` )
    REFERENCES `doc_group` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_ibfk_2`
    FOREIGN KEY (`unit` )
    REFERENCES `doc_units` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_ibfk_1`
    FOREIGN KEY (`group` )
    REFERENCES `doc_group` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 3
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `doc_sklady`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_sklady` (
  `id` TINYINT(4) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(64) NOT NULL ,
  `comment` TEXT NOT NULL ,
  `dnc` TINYINT(4) NOT NULL COMMENT 'Не контролоировать остатки' ,
  INDEX `id` (`id` ASC) ,
  INDEX `name` (`name` ASC) ,
  INDEX `dnc` (`dnc` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `doc_base_cnt`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_base_cnt` (
  `id` INT(11) NOT NULL ,
  `sklad` TINYINT(4) NOT NULL ,
  `cnt` DOUBLE NOT NULL ,
  `mesto` INT(11) NOT NULL ,
  `mincnt` INT(11) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `cnt` (`cnt` ASC) ,
  INDEX `mesto` (`mesto` ASC) ,
  INDEX `mincnt` (`mincnt` ASC) ,
  INDEX `sklad` (`sklad` ASC) ,
  CONSTRAINT `doc_base_cnt_ibfk_2`
    FOREIGN KEY (`sklad` )
    REFERENCES `doc_sklady` (`id` ),
  CONSTRAINT `doc_base_cnt_ibfk_1`
    FOREIGN KEY (`id` )
    REFERENCES `doc_base` (`id` ),
  CONSTRAINT `doc_base_cnt_ibfk_2`
    FOREIGN KEY (`sklad` )
    REFERENCES `doc_sklady` (`id` ),
  CONSTRAINT `doc_base_cnt_ibfk_1`
    FOREIGN KEY (`id` )
    REFERENCES `doc_base` (`id` ))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `doc_cost`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_cost` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(24) NOT NULL ,
  `type` VARCHAR(4) NOT NULL ,
  `value` DECIMAL(8,2) NOT NULL COMMENT 'Значение цены' ,
  `vid` TINYINT(4) NOT NULL COMMENT 'Вид цены определяет места её использования' ,
  `accuracy` TINYINT(4) NOT NULL COMMENT 'Точность для округления' ,
  `direction` TINYINT(4) NOT NULL COMMENT 'Направление округления' ,
  UNIQUE INDEX `id` (`id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 5
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `doc_base_cost`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_base_cost` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `pos_id` INT(11) NOT NULL ,
  `cost_id` INT(11) NOT NULL ,
  `type` VARCHAR(8) NOT NULL ,
  `value` DECIMAL(8,2) NOT NULL ,
  `accuracy` TINYINT(4) NOT NULL ,
  `direction` TINYINT(4) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  UNIQUE INDEX `uniq` (`pos_id` ASC, `cost_id` ASC) ,
  INDEX `group_id` (`pos_id` ASC) ,
  INDEX `cost_id` (`cost_id` ASC) ,
  INDEX `value` (`value` ASC) ,
  INDEX `type` (`type` ASC) ,
  CONSTRAINT `doc_base_cost_ibfk_1`
    FOREIGN KEY (`pos_id` )
    REFERENCES `doc_base` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_cost_ibfk_2`
    FOREIGN KEY (`cost_id` )
    REFERENCES `doc_cost` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_cost_ibfk_1`
    FOREIGN KEY (`pos_id` )
    REFERENCES `doc_base` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_cost_ibfk_2`
    FOREIGN KEY (`cost_id` )
    REFERENCES `doc_cost` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `doc_base_dop`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_base_dop` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `type` INT(11) NOT NULL DEFAULT '0' ,
  `d_int` DOUBLE NOT NULL DEFAULT '0' ,
  `d_ext` DOUBLE NOT NULL DEFAULT '0' ,
  `size` DOUBLE NOT NULL DEFAULT '0' ,
  `mass` DOUBLE NOT NULL DEFAULT '0' ,
  `analog` VARCHAR(24) NOT NULL ,
  `koncost` DOUBLE NOT NULL DEFAULT '0' ,
  `strana` VARCHAR(24) NOT NULL ,
  `tranzit` TINYINT(4) NOT NULL ,
  `ntd` VARCHAR(32) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `type` (`type` ASC) ,
  INDEX `d_int` (`d_int` ASC) ,
  INDEX `d_ext` (`d_ext` ASC) ,
  INDEX `size` (`size` ASC) ,
  INDEX `mass` (`mass` ASC) ,
  INDEX `analog` (`analog` ASC) ,
  INDEX `koncost` (`koncost` ASC) ,
  CONSTRAINT `doc_base_dop_ibfk_1`
    FOREIGN KEY (`id` )
    REFERENCES `doc_base` (`id` ),
  CONSTRAINT `doc_base_dop_ibfk_1`
    FOREIGN KEY (`id` )
    REFERENCES `doc_base` (`id` ))
ENGINE = InnoDB
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `doc_base_dop_type`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_base_dop_type` (
  `id` INT(11) NOT NULL ,
  `name` VARCHAR(64) NOT NULL ,
  `desc` TEXT NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `name` (`name` ASC) ,
  CONSTRAINT `doc_base_dop_type_ibfk_1`
    FOREIGN KEY (`id` )
    REFERENCES `doc_base` (`id` ),
  CONSTRAINT `doc_base_dop_type_ibfk_1`
    FOREIGN KEY (`id` )
    REFERENCES `doc_base` (`id` ))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `doc_img`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_img` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(128) NOT NULL ,
  `type` VARCHAR(4) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  UNIQUE INDEX `name` (`name` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `doc_base_img`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_base_img` (
  `img_id` INT(11) NOT NULL ,
  `pos_id` INT(11) NOT NULL ,
  `default` TINYINT(4) NOT NULL ,
  UNIQUE INDEX `pos_id` (`pos_id` ASC, `img_id` ASC) ,
  INDEX `default` (`default` ASC) ,
  INDEX `img_id` (`img_id` ASC) ,
  CONSTRAINT `doc_base_img_ibfk_2`
    FOREIGN KEY (`img_id` )
    REFERENCES `doc_img` (`id` ),
  CONSTRAINT `doc_base_img_ibfk_1`
    FOREIGN KEY (`pos_id` )
    REFERENCES `doc_base` (`id` ),
  CONSTRAINT `doc_base_img_ibfk_2`
    FOREIGN KEY (`img_id` )
    REFERENCES `doc_img` (`id` ),
  CONSTRAINT `doc_base_img_ibfk_1`
    FOREIGN KEY (`pos_id` )
    REFERENCES `doc_base` (`id` ))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `doc_base_kompl`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_base_kompl` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `pos_id` INT(11) NOT NULL COMMENT 'id наименования' ,
  `kompl_id` INT(11) NOT NULL COMMENT 'id комплектующего' ,
  `cnt` INT(11) NOT NULL COMMENT 'количество' ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `kompl_id` (`kompl_id` ASC) ,
  INDEX `cnt` (`cnt` ASC) ,
  INDEX `pos_id` (`pos_id` ASC) ,
  CONSTRAINT `doc_base_kompl_ibfk_2`
    FOREIGN KEY (`kompl_id` )
    REFERENCES `doc_base` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_kompl_ibfk_1`
    FOREIGN KEY (`pos_id` )
    REFERENCES `doc_base` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_kompl_ibfk_2`
    FOREIGN KEY (`kompl_id` )
    REFERENCES `doc_base` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_kompl_ibfk_1`
    FOREIGN KEY (`pos_id` )
    REFERENCES `doc_base` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Комплектующие - из чего состоит эта позиция' ;


-- -----------------------------------------------------
-- Table `doc_base_gparams`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_base_gparams` (
  `id` INT NOT NULL ,
  `name` VARCHAR(64) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `doc_base_params`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_base_params` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `pgroup_id` INT(11) NOT NULL ,
  `param` VARCHAR(32) NOT NULL ,
  `type` VARCHAR(8) NOT NULL ,
  INDEX `param` (`param` ASC) ,
  PRIMARY KEY (`id`) ,
  INDEX `pgroup` (`pgroup_id` ASC) ,
  CONSTRAINT `fk_doc_base_params_doc_base_gparams1`
    FOREIGN KEY (`pgroup_id` )
    REFERENCES `doc_base_gparams` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 6
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `doc_base_values`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_base_values` (
  `id` INT(11) NOT NULL ,
  `param_id` INT(11) NOT NULL ,
  `value` VARCHAR(32) NOT NULL ,
  UNIQUE INDEX `unique` (`id` ASC, `param_id` ASC) ,
  INDEX `id` (`id` ASC) ,
  INDEX `param` (`param_id` ASC) ,
  INDEX `value` (`value` ASC) ,
  CONSTRAINT `doc_base_values_ibfk_1`
    FOREIGN KEY (`id` )
    REFERENCES `doc_base` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_values_ibfk_2`
    FOREIGN KEY (`param_id` )
    REFERENCES `doc_base_params` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_values_ibfk_1`
    FOREIGN KEY (`id` )
    REFERENCES `doc_base` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_base_values_ibfk_2`
    FOREIGN KEY (`param_id` )
    REFERENCES `doc_base_params` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `doc_types`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_types` (
  `id` TINYINT(4) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(32) NOT NULL ,
  INDEX `id` (`id` ASC) ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 18
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Типы документов' ;


-- -----------------------------------------------------
-- Table `doc_vars`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_vars` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `firm_name` VARCHAR(192) NOT NULL ,
  `firm_director` VARCHAR(128) NOT NULL ,
  `firm_director_r` VARCHAR(128) NOT NULL ,
  `firm_manager` VARCHAR(128) NOT NULL ,
  `firm_buhgalter` VARCHAR(128) NOT NULL ,
  `firm_kladovshik` VARCHAR(128) NOT NULL ,
  `firm_kladovshik_id` INT(11) NOT NULL ,
  `firm_bank` VARCHAR(128) NOT NULL ,
  `firm_bank_kor_s` VARCHAR(32) NOT NULL ,
  `firm_bik` VARCHAR(16) NOT NULL ,
  `firm_schet` VARCHAR(32) NOT NULL ,
  `firm_inn` VARCHAR(32) NOT NULL ,
  `firm_adres` VARCHAR(192) NOT NULL ,
  `firm_realadres` VARCHAR(192) NOT NULL ,
  `firm_gruzootpr` VARCHAR(256) NOT NULL ,
  `firm_telefon` VARCHAR(64) NOT NULL ,
  `firm_okpo` VARCHAR(16) NOT NULL ,
  `param_nds` DOUBLE NOT NULL DEFAULT '0' ,
  `firm_skin` VARCHAR(16) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = utf8;


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


-- -----------------------------------------------------
-- Table `doc_dopdata`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_dopdata` (
  `doc` INT(11) NOT NULL ,
  `param` VARCHAR(24) NOT NULL ,
  `value` VARCHAR(192) NOT NULL ,
  UNIQUE INDEX `doc` (`doc` ASC, `param` ASC) ,
  INDEX `value` (`value` ASC) ,
  CONSTRAINT `doc_dopdata_ibfk_1`
    FOREIGN KEY (`doc` )
    REFERENCES `doc_list` (`id` ),
  CONSTRAINT `doc_dopdata_ibfk_1`
    FOREIGN KEY (`doc` )
    REFERENCES `doc_list` (`id` ))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Дополнительные поля документов' ;


-- -----------------------------------------------------
-- Table `doc_group_cost`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_group_cost` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `group_id` INT(11) NOT NULL ,
  `cost_id` INT(11) NOT NULL ,
  `type` VARCHAR(5) NOT NULL ,
  `value` DECIMAL(8,2) NOT NULL ,
  `accuracy` TINYINT(4) NOT NULL COMMENT 'Точность для округления' ,
  `direction` TINYINT(4) NOT NULL COMMENT 'Направление округления' ,
  UNIQUE INDEX `id` (`id` ASC) ,
  UNIQUE INDEX `uniq` (`group_id` ASC, `cost_id` ASC) ,
  INDEX `group_id` (`group_id` ASC) ,
  INDEX `cost_id` (`cost_id` ASC) ,
  INDEX `value` (`value` ASC) ,
  INDEX `type` (`type` ASC) ,
  CONSTRAINT `doc_group_cost_ibfk_1`
    FOREIGN KEY (`group_id` )
    REFERENCES `doc_group` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_group_cost_ibfk_2`
    FOREIGN KEY (`cost_id` )
    REFERENCES `doc_cost` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_group_cost_ibfk_1`
    FOREIGN KEY (`group_id` )
    REFERENCES `doc_group` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_group_cost_ibfk_2`
    FOREIGN KEY (`cost_id` )
    REFERENCES `doc_cost` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `doc_kassa`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_kassa` (
  `ids` VARCHAR(50) CHARACTER SET 'latin1' NOT NULL ,
  `num` INT(11) NOT NULL ,
  `name` VARCHAR(64) NOT NULL ,
  `ballance` DECIMAL(10,2) NOT NULL ,
  `bik` VARCHAR(24) NOT NULL ,
  `rs` VARCHAR(32) NOT NULL ,
  `ks` VARCHAR(32) NOT NULL ,
  `firm_id` INT(11) NOT NULL ,
  UNIQUE INDEX `ids` (`ids` ASC, `num` ASC) ,
  INDEX `fk_doc_kassa_doc_vars1` (`firm_id` ASC) ,
  INDEX `fk_doc_kassa_doc_list1` (`num` ASC) ,
  CONSTRAINT `fk_doc_kassa_doc_vars1`
    FOREIGN KEY (`firm_id` )
    REFERENCES `doc_vars` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_doc_kassa_doc_list1`
    FOREIGN KEY (`num` )
    REFERENCES `doc_list` (`kassa` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Список касс и банков' ;


-- -----------------------------------------------------
-- Table `doc_list_pos`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_list_pos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `doc` INT(11) NOT NULL DEFAULT '0' ,
  `tovar` INT(11) NOT NULL DEFAULT '0' ,
  `cnt` INT(11) NOT NULL DEFAULT '0' ,
  `gtd` VARCHAR(32) NOT NULL ,
  `comm` VARCHAR(64) NOT NULL ,
  `cost` DECIMAL(10,2) NOT NULL DEFAULT '0' ,
  `page` INT(11) NOT NULL DEFAULT '0' ,
  INDEX `id` (`id` ASC) ,
  INDEX `doc` (`doc` ASC) ,
  INDEX `tovar` (`tovar` ASC) ,
  INDEX `page` (`page` ASC) ,
  CONSTRAINT `doc_list_pos_ibfk_2`
    FOREIGN KEY (`tovar` )
    REFERENCES `doc_base` (`id` ),
  CONSTRAINT `doc_list_pos_ibfk_1`
    FOREIGN KEY (`doc` )
    REFERENCES `doc_list` (`id` ),
  CONSTRAINT `doc_list_pos_ibfk_2`
    FOREIGN KEY (`tovar` )
    REFERENCES `doc_base` (`id` ),
  CONSTRAINT `doc_list_pos_ibfk_1`
    FOREIGN KEY (`doc` )
    REFERENCES `doc_list` (`id` ))
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `doc_list_sn`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_list_sn` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `pos_id` INT(11) NOT NULL COMMENT 'ID товара' ,
  `num` VARCHAR(64) NOT NULL COMMENT 'Серийный номер' ,
  `prix_list_pos` INT(11) NOT NULL COMMENT 'Строка поступления' ,
  `rasx_list_pos` INT(11) NULL DEFAULT NULL COMMENT 'Строка реализации' ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `pos_id` (`pos_id` ASC) ,
  INDEX `num` (`num` ASC) ,
  INDEX `prix_list_pos` (`prix_list_pos` ASC) ,
  INDEX `rasx_list_pos` (`rasx_list_pos` ASC) ,
  CONSTRAINT `doc_list_sn_ibfk_4`
    FOREIGN KEY (`prix_list_pos` )
    REFERENCES `doc_list_pos` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `doc_list_sn_ibfk_1`
    FOREIGN KEY (`pos_id` )
    REFERENCES `doc_base` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_list_sn_ibfk_3`
    FOREIGN KEY (`rasx_list_pos` )
    REFERENCES `doc_list_pos` (`id` )
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `doc_list_sn_ibfk_4`
    FOREIGN KEY (`prix_list_pos` )
    REFERENCES `doc_list_pos` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `doc_list_sn_ibfk_1`
    FOREIGN KEY (`pos_id` )
    REFERENCES `doc_base` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_list_sn_ibfk_3`
    FOREIGN KEY (`rasx_list_pos` )
    REFERENCES `doc_list_pos` (`id` )
    ON DELETE SET NULL
    ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Серийные номера' ;


-- -----------------------------------------------------
-- Table `doc_log`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `user` INT(11) NOT NULL ,
  `ip` VARCHAR(20) NOT NULL ,
  `object` VARCHAR(20) NOT NULL ,
  `object_id` INT(11) NOT NULL ,
  `motion` VARCHAR(128) NOT NULL ,
  `desc` VARCHAR(512) NOT NULL ,
  `time` DATETIME NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `user` (`user` ASC) ,
  INDEX `motion` (`motion` ASC) ,
  INDEX `time` (`time` ASC) ,
  INDEX `desc` (`desc`(333) ASC) ,
  INDEX `ip` (`ip` ASC) ,
  CONSTRAINT `fk_doc_log_users1`
    FOREIGN KEY (`user` )
    REFERENCES `users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Журнал изменений объектов документооборота' ;


-- -----------------------------------------------------
-- Table `doc_rasxodi`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_rasxodi` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(64) NOT NULL ,
  `adm` TINYINT(4) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `name` (`name` ASC) ,
  INDEX `adm` (`adm` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Статьи расходов' ;


-- -----------------------------------------------------
-- Table `errorlog`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `errorlog` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `page` VARCHAR(128) NOT NULL ,
  `referer` VARCHAR(128) NOT NULL ,
  `agent` VARCHAR(128) NOT NULL ,
  `ip` VARCHAR(24) NOT NULL ,
  `msg` TEXT NOT NULL ,
  `date` DATETIME NOT NULL ,
  `uid` INT(11) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `page` (`page` ASC) ,
  INDEX `referer` (`referer` ASC) ,
  INDEX `date` (`date` ASC) ,
  INDEX `agent` (`agent` ASC, `ip` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Журнал ошибок' ;


-- -----------------------------------------------------
-- Table `firm_info`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `firm_info` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(128) NOT NULL DEFAULT '' ,
  `signature` VARCHAR(256) NOT NULL DEFAULT '' COMMENT 'Сигнатура для определения принадлежности прайса' ,
  `currency` TINYINT(4) NOT NULL ,
  `coeff` DECIMAL(10,3) NOT NULL ,
  `last_update` DATETIME NOT NULL ,
  `type` INT(11) NULL COMMENT 'Как интерпретировать прайс фирмы' ,
  PRIMARY KEY (`id`) ,
  INDEX `name` (`name` ASC) ,
  INDEX `sign` (`signature` ASC) ,
  INDEX `fk_firm_info_currency1` (`currency` ASC) ,
  CONSTRAINT `fk_firm_info_currency1`
    FOREIGN KEY (`currency` )
    REFERENCES `currency` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Информация об организации - источнике прайса' ;


-- -----------------------------------------------------
-- Table `firm_info_struct`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `firm_info_struct` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `firm_id` INT(11) NOT NULL COMMENT 'Номер фирмы' ,
  `table_name` VARCHAR(64) NOT NULL COMMENT 'Название листа прайса' ,
  `name` MEDIUMINT(9) NOT NULL COMMENT 'N колонки наименований' ,
  `cost` MEDIUMINT(9) NOT NULL ,
  `art` MEDIUMINT(9) NOT NULL ,
  `nal` MEDIUMINT(9) NOT NULL ,
  `currency` MEDIUMINT(9) NOT NULL COMMENT 'Столбец с валютой' ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `firm_id` (`firm_id` ASC) ,
  INDEX `table_name` (`table_name` ASC) ,
  CONSTRAINT `firm_info_struct_ibfk_1`
    FOREIGN KEY (`firm_id` )
    REFERENCES `firm_info` (`id` ),
  CONSTRAINT `firm_info_struct_ibfk_1`
    FOREIGN KEY (`firm_id` )
    REFERENCES `firm_info` (`id` ))
ENGINE = InnoDB
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Информация о структуре прайсов организаций' ;


-- -----------------------------------------------------
-- Table `loginfo`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `loginfo` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `date` DATETIME NOT NULL ,
  `page` VARCHAR(100) NOT NULL ,
  `query` VARCHAR(100) NOT NULL ,
  `mode` VARCHAR(20) NOT NULL ,
  `ip` VARCHAR(30) NOT NULL ,
  `user` INT(11) NOT NULL ,
  `text` VARCHAR(500) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `date` (`date` ASC) ,
  INDEX `page` (`page` ASC) ,
  INDEX `query` (`query` ASC) ,
  INDEX `mode` (`mode` ASC) ,
  INDEX `ip` (`ip` ASC) ,
  INDEX `user` (`user` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `news`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `news` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `type` VARCHAR(8) NOT NULL ,
  `title` VARCHAR(64) NOT NULL ,
  `text` TEXT NOT NULL ,
  `date` DATETIME NOT NULL ,
  `autor` INT(11) NOT NULL ,
  `ex_date` DATE NOT NULL ,
  `img_ext` VARCHAR(4) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `type` (`type` ASC) ,
  INDEX `ex_date` (`ex_date` ASC) ,
  INDEX `fk_news_users1` (`autor` ASC) ,
  CONSTRAINT `fk_news_users1`
    FOREIGN KEY (`autor` )
    REFERENCES `users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Новости, акции, итп' ;


-- -----------------------------------------------------
-- Table `notes`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `notes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `user` INT(11) NOT NULL ,
  `sender` INT(11) NOT NULL ,
  `head` VARCHAR(64) NOT NULL ,
  `msg` TEXT NOT NULL ,
  `senddate` DATETIME NOT NULL ,
  `enddate` DATETIME NOT NULL ,
  `ok` TINYINT(4) NOT NULL ,
  `comment` TEXT NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `user` (`user` ASC) ,
  INDEX `sender` (`sender` ASC) ,
  INDEX `senddate` (`senddate` ASC) ,
  INDEX `enddate` (`enddate` ASC) ,
  CONSTRAINT `fk_notes_users1`
    FOREIGN KEY (`user` )
    REFERENCES `users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_notes_users2`
    FOREIGN KEY (`sender` )
    REFERENCES `users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Заметки и уведомления' ;


-- -----------------------------------------------------
-- Table `price`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `price` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(256) NOT NULL DEFAULT '' ,
  `cost` DOUBLE NOT NULL DEFAULT '0' ,
  `firm` INT(11) NOT NULL DEFAULT '0' ,
  `art` VARCHAR(32) NOT NULL DEFAULT '' ,
  `nal` VARCHAR(16) NOT NULL ,
  `currency` INT(11) NULL ,
  `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ,
  `seeked` INT(11) NOT NULL ,
  INDEX `name` (`name` ASC) ,
  INDEX `cost` (`cost` ASC) ,
  INDEX `firm` (`firm` ASC) ,
  INDEX `art` (`art` ASC) ,
  INDEX `date` (`date` ASC) ,
  INDEX `id` (`id` ASC) ,
  CONSTRAINT `price_ibfk_1`
    FOREIGN KEY (`firm` )
    REFERENCES `firm_info` (`id` ),
  CONSTRAINT `price_ibfk_1`
    FOREIGN KEY (`firm` )
    REFERENCES `firm_info` (`id` ))
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Загруженные наименования прайс - листов' ;


-- -----------------------------------------------------
-- Table `parsed_price`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `parsed_price` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `firm` INT(11) NOT NULL ,
  `pos` INT(11) NOT NULL ,
  `cost` DECIMAL(10,2) NOT NULL ,
  `nal` VARCHAR(16) NOT NULL ,
  `from` INT(11) NOT NULL ,
  `selected` TINYINT(4) NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `firm` (`firm` ASC) ,
  INDEX `pos` (`pos` ASC) ,
  CONSTRAINT `parsed_price_ibfk_2`
    FOREIGN KEY (`pos` )
    REFERENCES `price` (`id` ),
  CONSTRAINT `parsed_price_ibfk_1`
    FOREIGN KEY (`firm` )
    REFERENCES `firm_info` (`id` ),
  CONSTRAINT `parsed_price_ibfk_2`
    FOREIGN KEY (`pos` )
    REFERENCES `price` (`id` ),
  CONSTRAINT `parsed_price_ibfk_1`
    FOREIGN KEY (`firm` )
    REFERENCES `firm_info` (`id` ))
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Соответствия между наименованиями прайс-листов и складом' ;


-- -----------------------------------------------------
-- Table `photogalery`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `photogalery` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `uid` INT(11) NOT NULL DEFAULT '0' ,
  `comment` VARCHAR(64) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `uid` (`uid` ASC) ,
  CONSTRAINT `photogalery_ibfk_1`
    FOREIGN KEY (`uid` )
    REFERENCES `users` (`id` ),
  CONSTRAINT `photogalery_ibfk_1`
    FOREIGN KEY (`uid` )
    REFERENCES `users` (`id` ))
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'ОписанИЯ фотографий галереи' ;


-- -----------------------------------------------------
-- Table `prices_replaces`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `prices_replaces` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `search_str` VARCHAR(16) NOT NULL ,
  `replace_str` VARCHAR(512) NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `search_str` (`search_str` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Список замен для регулярных выражений анализатора прайсов' ;


-- -----------------------------------------------------
-- Table `questions`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `questions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `text` VARCHAR(256) NOT NULL ,
  `mode` INT(11) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `question_answ`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `question_answ` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `q_id` INT(11) NOT NULL ,
  `answer` VARCHAR(500) NOT NULL ,
  `uid` INT(11) NOT NULL ,
  `ip` VARCHAR(15) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `q_id` (`q_id` ASC) ,
  INDEX `uid` (`uid` ASC) ,
  INDEX `ip` (`ip` ASC) ,
  CONSTRAINT `fk_question_answ_users1`
    FOREIGN KEY (`uid` )
    REFERENCES `users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_question_answ_questions1`
    FOREIGN KEY (`q_id` )
    REFERENCES `questions` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `question_ip`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `question_ip` (
  `ip` VARCHAR(15) NOT NULL ,
  `result` INT(11) NOT NULL ,
  UNIQUE INDEX `ip_2` (`ip` ASC) )
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `question_vars`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `question_vars` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `q_id` INT(11) NOT NULL ,
  `var_id` INT(11) NOT NULL ,
  `text` VARCHAR(500) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `q_id` (`q_id` ASC, `var_id` ASC) ,
  INDEX `fk_question_vars_questions1` (`q_id` ASC) ,
  CONSTRAINT `fk_question_vars_questions1`
    FOREIGN KEY (`q_id` )
    REFERENCES `questions` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `seekdata`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `seekdata` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `sql` VARCHAR(256) NOT NULL ,
  `regex` VARCHAR(256) NOT NULL ,
  `regex_neg` VARCHAR(256) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `sql` (`sql` ASC) ,
  INDEX `regex` (`regex` ASC) ,
  PRIMARY KEY (`id`) ,
  CONSTRAINT `fk_seekdata_doc_base1`
    FOREIGN KEY (`id` )
    REFERENCES `doc_base` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Данные для поиска анлизатором прайсов' ;


-- -----------------------------------------------------
-- Table `sys_cli_status`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `sys_cli_status` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `script` VARCHAR(64) NOT NULL ,
  `status` TEXT NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `script` (`script` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `tickets_state`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tickets_state` (
  `id` INT(11) NOT NULL ,
  `name` VARCHAR(32) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `tickets_priority`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tickets_priority` (
  `id` TINYINT(4) NOT NULL ,
  `name` VARCHAR(64) NOT NULL ,
  `color` VARCHAR(8) NOT NULL ,
  `comment` VARCHAR(256) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `name` (`name` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Описания приоритетов задач' ;


-- -----------------------------------------------------
-- Table `tickets`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tickets` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `date` DATETIME NOT NULL ,
  `autor` INT(11) NOT NULL ,
  `priority` TINYINT(4) NOT NULL ,
  `theme` VARCHAR(100) NOT NULL ,
  `text` TEXT NOT NULL ,
  `to_uid` INT(11) NOT NULL ,
  `to_date` DATE NOT NULL ,
  `state` TINYINT(4) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `date` (`date` ASC) ,
  INDEX `autor` (`autor` ASC) ,
  INDEX `theme` (`theme` ASC) ,
  INDEX `to_uid` (`to_uid` ASC) ,
  INDEX `to_date` (`to_date` ASC) ,
  INDEX `fk_tickets_tickets_state1` (`state` ASC) ,
  INDEX `fk_tickets_tickets_priority1` (`priority` ASC) ,
  CONSTRAINT `tickets_ibfk_2`
    FOREIGN KEY (`to_uid` )
    REFERENCES `users` (`id` ),
  CONSTRAINT `tickets_ibfk_1`
    FOREIGN KEY (`autor` )
    REFERENCES `users` (`id` ),
  CONSTRAINT `tickets_ibfk_2`
    FOREIGN KEY (`to_uid` )
    REFERENCES `users` (`id` ),
  CONSTRAINT `tickets_ibfk_1`
    FOREIGN KEY (`autor` )
    REFERENCES `users` (`id` ),
  CONSTRAINT `fk_tickets_tickets_state1`
    FOREIGN KEY (`state` )
    REFERENCES `tickets_state` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_tickets_tickets_priority1`
    FOREIGN KEY (`priority` )
    REFERENCES `tickets_priority` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `tickets_log`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tickets_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `uid` INT(11) NOT NULL ,
  `ticket` INT(11) NOT NULL ,
  `date` DATETIME NOT NULL ,
  `text` TEXT NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `uid` (`uid` ASC, `ticket` ASC, `date` ASC) ,
  INDEX `fk_tickets_log_tickets1` (`ticket` ASC) ,
  INDEX `fk_tickets_log_users1` (`uid` ASC) ,
  CONSTRAINT `fk_tickets_log_tickets1`
    FOREIGN KEY (`ticket` )
    REFERENCES `tickets` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_tickets_log_users1`
    FOREIGN KEY (`uid` )
    REFERENCES `users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `traffic_denyip`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `traffic_denyip` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `ip` VARCHAR(20) NOT NULL ,
  `host` VARCHAR(50) NOT NULL ,
  UNIQUE INDEX `id_2` (`id` ASC) ,
  UNIQUE INDEX `ip` (`ip` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = latin1, 
COMMENT = 'Zapreshennie IP' ;


-- -----------------------------------------------------
-- Table `ulog`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `ulog` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `raw_mac` VARCHAR(80) NULL DEFAULT NULL ,
  `oob_time_sec` INT(10) UNSIGNED NULL DEFAULT NULL ,
  `oob_time_usec` INT(10) UNSIGNED NULL DEFAULT NULL ,
  `oob_prefix` VARCHAR(32) NULL DEFAULT NULL ,
  `oob_mark` INT(10) UNSIGNED NULL DEFAULT NULL ,
  `oob_in` VARCHAR(32) NULL DEFAULT NULL ,
  `oob_out` VARCHAR(32) NULL DEFAULT NULL ,
  `ip_saddr` VARCHAR(15) NULL DEFAULT NULL ,
  `ip_daddr` VARCHAR(15) NULL DEFAULT NULL ,
  `ip_protocol` TINYINT(3) UNSIGNED NULL DEFAULT NULL ,
  `ip_tos` TINYINT(3) UNSIGNED NULL DEFAULT NULL ,
  `ip_ttl` TINYINT(3) UNSIGNED NULL DEFAULT NULL ,
  `ip_totlen` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ,
  `ip_ihl` TINYINT(3) UNSIGNED NULL DEFAULT NULL ,
  `ip_csum` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ,
  `ip_id` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ,
  `ip_fragoff` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ,
  `tcp_sport` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ,
  `tcp_dport` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ,
  `tcp_seq` INT(10) UNSIGNED NULL DEFAULT NULL ,
  `tcp_ackseq` INT(10) UNSIGNED NULL DEFAULT NULL ,
  `tcp_window` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ,
  `tcp_urg` TINYINT(4) NULL DEFAULT NULL ,
  `tcp_urgp` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ,
  `tcp_ack` TINYINT(4) NULL DEFAULT NULL ,
  `tcp_psh` TINYINT(4) NULL DEFAULT NULL ,
  `tcp_rst` TINYINT(4) NULL DEFAULT NULL ,
  `tcp_syn` TINYINT(4) NULL DEFAULT NULL ,
  `tcp_fin` TINYINT(4) NULL DEFAULT NULL ,
  `udp_sport` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ,
  `udp_dport` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ,
  `udp_len` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ,
  `icmp_type` TINYINT(3) UNSIGNED NULL DEFAULT NULL ,
  `icmp_code` TINYINT(3) UNSIGNED NULL DEFAULT NULL ,
  `icmp_echoid` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ,
  `icmp_echoseq` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ,
  `icmp_gateway` INT(10) UNSIGNED NULL DEFAULT NULL ,
  `icmp_fragmtu` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ,
  `pwsniff_user` VARCHAR(30) NULL DEFAULT NULL ,
  `pwsniff_pass` VARCHAR(30) NULL DEFAULT NULL ,
  `ahesp_spi` INT(10) UNSIGNED NULL DEFAULT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `ip_daddr` (`ip_daddr` ASC) ,
  INDEX `ip_saddr` (`ip_saddr` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `users_objects`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `users_objects` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `object` VARCHAR(64) NOT NULL ,
  `desc` VARCHAR(128) NOT NULL ,
  `actions` VARCHAR(128) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `object` (`object` ASC) ,
  CONSTRAINT `fk_users_objects_users1`
    FOREIGN KEY (`id` )
    REFERENCES `users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 0
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Объекты контроля привилегий доступа' ;


-- -----------------------------------------------------
-- Table `users_acl`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `users_acl` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `uid` INT(11) NOT NULL ,
  `object` VARCHAR(64) NOT NULL ,
  `action` VARCHAR(16) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `uid` (`uid` ASC) ,
  INDEX `object` (`object` ASC) ,
  INDEX `action` (`action` ASC) ,
  CONSTRAINT `users_acl_ibfk_1`
    FOREIGN KEY (`uid` )
    REFERENCES `users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `users_acl_ibfk_1`
    FOREIGN KEY (`uid` )
    REFERENCES `users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_users_acl_users_objects1`
    FOREIGN KEY (`object` )
    REFERENCES `users_objects` (`object` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Привилегии пользователей' ;


-- -----------------------------------------------------
-- Table `users_bad_auth`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `users_bad_auth` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `ip` VARCHAR(24) NOT NULL ,
  `time` DOUBLE NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `ip` (`ip` ASC) ,
  INDEX `date` (`time` ASC) ,
  CONSTRAINT `fk_users_bad_auth_users1`
    FOREIGN KEY (`id` )
    REFERENCES `users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = latin1, 
COMMENT = 'Журнал ошибочных аутентификаций' ;


-- -----------------------------------------------------
-- Table `users_data`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `users_data` (
  `uid` INT(11) NOT NULL DEFAULT '0' ,
  `param` VARCHAR(24) NOT NULL ,
  `value` VARCHAR(128) NOT NULL ,
  UNIQUE INDEX `uid` (`uid` ASC, `param` ASC) ,
  INDEX `value` (`value` ASC) ,
  CONSTRAINT `users_data_ibfk_1`
    FOREIGN KEY (`uid` )
    REFERENCES `users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `users_data_ibfk_1`
    FOREIGN KEY (`uid` )
    REFERENCES `users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Дополнительная информация о пользователях' ;


-- -----------------------------------------------------
-- Table `users_grouplist`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `users_grouplist` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(64) NOT NULL ,
  `comment` TEXT NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `name` (`name` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = latin1, 
COMMENT = 'Список групп пользователей' 
PACK_KEYS = 0;


-- -----------------------------------------------------
-- Table `users_groups_acl`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `users_groups_acl` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `gid` INT(11) NOT NULL ,
  `object` VARCHAR(64) NOT NULL ,
  `action` VARCHAR(16) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `gid` (`gid` ASC) ,
  INDEX `object` (`object` ASC) ,
  CONSTRAINT `users_groups_acl_ibfk_1`
    FOREIGN KEY (`gid` )
    REFERENCES `users` (`id` ),
  CONSTRAINT `users_groups_acl_ibfk_1`
    FOREIGN KEY (`gid` )
    REFERENCES `users` (`id` ),
  CONSTRAINT `fk_users_groups_acl_users_objects1`
    FOREIGN KEY (`object` )
    REFERENCES `users_objects` (`object` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = latin1, 
COMMENT = 'Привилегии групп' ;


-- -----------------------------------------------------
-- Table `users_in_group`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `users_in_group` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `uid` INT(11) NOT NULL ,
  `gid` INT(11) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `uid` (`uid` ASC) ,
  INDEX `gid` (`gid` ASC) ,
  CONSTRAINT `users_in_group_ibfk_1`
    FOREIGN KEY (`uid` )
    REFERENCES `users` (`id` ),
  CONSTRAINT `users_in_group_ibfk_2`
    FOREIGN KEY (`gid` )
    REFERENCES `users_grouplist` (`id` ),
  CONSTRAINT `users_in_group_ibfk_1`
    FOREIGN KEY (`uid` )
    REFERENCES `users` (`id` ),
  CONSTRAINT `users_in_group_ibfk_2`
    FOREIGN KEY (`gid` )
    REFERENCES `users_grouplist` (`id` ))
ENGINE = InnoDB
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = latin1, 
COMMENT = 'Соответствие групп и пользователей' ;


-- -----------------------------------------------------
-- Table `wiki`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `wiki` (
  `name` VARCHAR(64) NOT NULL ,
  `date` DATETIME NOT NULL ,
  `autor` INT(11) NOT NULL ,
  `changed` DATETIME NOT NULL ,
  `changeautor` INT(11) NOT NULL ,
  `text` TEXT NOT NULL ,
  `img_ext` VARCHAR(4) NOT NULL ,
  UNIQUE INDEX `name` (`name` ASC) ,
  INDEX `date` (`date` ASC) ,
  INDEX `autor` (`autor` ASC) ,
  INDEX `changed` (`changed` ASC) ,
  INDEX `changeautor` (`changeautor` ASC) ,
  CONSTRAINT `wiki_ibfk_1`
    FOREIGN KEY (`autor` )
    REFERENCES `users` (`id` ),
  CONSTRAINT `wiki_ibfk_1`
    FOREIGN KEY (`autor` )
    REFERENCES `users` (`id` ))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Статьи' ;


-- -----------------------------------------------------
-- Table `wikiphoto`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `wikiphoto` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `uid` INT(11) NOT NULL DEFAULT '0' ,
  `comment` VARCHAR(64) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `fk_wikiphoto_users1` (`uid` ASC) ,
  CONSTRAINT `fk_wikiphoto_users1`
    FOREIGN KEY (`uid` )
    REFERENCES `users` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Фотографии к статьям' ;


-- -----------------------------------------------------
-- Table `db_version`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `db_version` (
  `version` INT NOT NULL )
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci, 
COMMENT = 'Текущая версия базы данных' ;


-- -----------------------------------------------------
-- Table `doc_group_params`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_group_params` (
  `id` INT NOT NULL ,
  `group_id` INT(11) NULL ,
  `param_id` INT(11) NULL ,
  `show_in_filter` TINYINT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_doc_group_params_doc_group1` (`group_id` ASC) ,
  INDEX `fk_doc_group_params_doc_base_params1` (`param_id` ASC) ,
  INDEX `show_in_filter` (`show_in_filter` ASC) ,
  CONSTRAINT `fk_doc_group_params_doc_group1`
    FOREIGN KEY (`group_id` )
    REFERENCES `doc_group` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_doc_group_params_doc_base_params1`
    FOREIGN KEY (`param_id` )
    REFERENCES `doc_base_params` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


ALTER TABLE `doc_agent` ADD CONSTRAINT `doc_agent_ibfk_1` FOREIGN KEY (`p_agent`) REFERENCES `doc_agent` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- Data for table `users`
-- -----------------------------------------------------
START TRANSACTION;
INSERT INTO `users` (`id`, `name`, `pass`, `passch`, `email`, `date_reg`, `confirm`, `subscribe`, `lastlogin`, `rname`, `tel`, `adres`, `worker`) VALUES (0, 'anonymous', '-', NULL, NULL, NULL, '0', 0, NULL, NULL, NULL, NULL, NULL);

COMMIT;

-- -----------------------------------------------------
-- Data for table `doc_units`
-- -----------------------------------------------------
START TRANSACTION;
INSERT INTO `doc_units` (`id`, `name`, `printname`) VALUES (1, 'Штука', 'шт.');
INSERT INTO `doc_units` (`id`, `name`, `printname`) VALUES (2, 'Килограмм', 'кг.');
INSERT INTO `doc_units` (`id`, `name`, `printname`) VALUES (3, 'Грамм', 'гр.');
INSERT INTO `doc_units` (`id`, `name`, `printname`) VALUES (4, 'Литр', 'л.');
INSERT INTO `doc_units` (`id`, `name`, `printname`) VALUES (5, 'Метр', 'м.');
INSERT INTO `doc_units` (`id`, `name`, `printname`) VALUES (6, 'Милиметр', 'мм.');
INSERT INTO `doc_units` (`id`, `name`, `printname`) VALUES (7, 'Упаковка', 'уп.');

COMMIT;

-- -----------------------------------------------------
-- Data for table `doc_sklady`
-- -----------------------------------------------------
START TRANSACTION;
INSERT INTO `doc_sklady` (`id`, `name`, `comment`, `dnc`) VALUES (1, 'Основной склад', NULL, NULL);

COMMIT;

-- -----------------------------------------------------
-- Data for table `doc_cost`
-- -----------------------------------------------------
START TRANSACTION;
INSERT INTO `doc_cost` (`id`, `name`, `type`, `value`, `vid`, `accuracy`, `direction`) VALUES (1, 'Основная', NULL, NULL, NULL, NULL, NULL);

COMMIT;

-- -----------------------------------------------------
-- Data for table `doc_types`
-- -----------------------------------------------------
START TRANSACTION;
INSERT INTO `doc_types` (`id`, `name`) VALUES (1, 'Поступление');
INSERT INTO `doc_types` (`id`, `name`) VALUES (2, 'Реализация');
INSERT INTO `doc_types` (`id`, `name`) VALUES (3, 'Заявка покупателя');
INSERT INTO `doc_types` (`id`, `name`) VALUES (4, 'Банк - приход');
INSERT INTO `doc_types` (`id`, `name`) VALUES (5, 'Банк - расход');
INSERT INTO `doc_types` (`id`, `name`) VALUES (6, 'Касса - приход');
INSERT INTO `doc_types` (`id`, `name`) VALUES (7, 'Касса - расход');
INSERT INTO `doc_types` (`id`, `name`) VALUES (8, 'Перемещение товара');
INSERT INTO `doc_types` (`id`, `name`) VALUES (9, 'Перемещение средств (касса)');
INSERT INTO `doc_types` (`id`, `name`) VALUES (10, 'Доверенность');
INSERT INTO `doc_types` (`id`, `name`) VALUES (11, 'Предложение поставщика');
INSERT INTO `doc_types` (`id`, `name`) VALUES (12, 'Товар в пути');
INSERT INTO `doc_types` (`id`, `name`) VALUES (13, 'Коммерческое предложение');
INSERT INTO `doc_types` (`id`, `name`) VALUES (14, 'Договор');
INSERT INTO `doc_types` (`id`, `name`) VALUES (15, 'Реазизация опер');
INSERT INTO `doc_types` (`id`, `name`) VALUES (16, 'Спецификация');
INSERT INTO `doc_types` (`id`, `name`) VALUES (17, 'Сборка изделия');
INSERT INTO `doc_types` (`id`, `name`) VALUES (18, 'Корректировка долга');

COMMIT;

-- -----------------------------------------------------
-- Data for table `doc_vars`
-- -----------------------------------------------------
START TRANSACTION;
INSERT INTO `doc_vars` (`id`, `firm_name`, `firm_director`, `firm_director_r`, `firm_manager`, `firm_buhgalter`, `firm_kladovshik`, `firm_kladovshik_id`, `firm_bank`, `firm_bank_kor_s`, `firm_bik`, `firm_schet`, `firm_inn`, `firm_adres`, `firm_realadres`, `firm_gruzootpr`, `firm_telefon`, `firm_okpo`, `param_nds`, `firm_skin`) VALUES (1, 'ООО Наша фирма', 'Ктотов И.И.', 'Ктотов И.И.', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

COMMIT;

-- -----------------------------------------------------
-- Data for table `doc_rasxodi`
-- -----------------------------------------------------
START TRANSACTION;
INSERT INTO `doc_rasxodi` (`id`, `name`, `adm`) VALUES (0, 'Прочие расходы', 1);
INSERT INTO `doc_rasxodi` (`id`, `name`, `adm`) VALUES (1, 'Аренда офиса, склада', 1);
INSERT INTO `doc_rasxodi` (`id`, `name`, `adm`) VALUES (2, 'Зарплата, премии, надбавки', 1);
INSERT INTO `doc_rasxodi` (`id`, `name`, `adm`) VALUES (3, 'Канцелярские товары, расходные материалы', 1);
INSERT INTO `doc_rasxodi` (`id`, `name`, `adm`) VALUES (4, 'Представительские расходы', 1);
INSERT INTO `doc_rasxodi` (`id`, `name`, `adm`) VALUES (5, 'Другие (банковские) платежи', 1);
INSERT INTO `doc_rasxodi` (`id`, `name`, `adm`) VALUES (6, 'Закупка товара на склад', 0);
INSERT INTO `doc_rasxodi` (`id`, `name`, `adm`) VALUES (7, 'Закупка товара на продажу', 0);
INSERT INTO `doc_rasxodi` (`id`, `name`, `adm`) VALUES (8, 'Транспортные расходы', 1);
INSERT INTO `doc_rasxodi` (`id`, `name`, `adm`) VALUES (9, 'Расходы на связь', 1);
INSERT INTO `doc_rasxodi` (`id`, `name`, `adm`) VALUES (10, 'Оплата товара на реализации', 0);
INSERT INTO `doc_rasxodi` (`id`, `name`, `adm`) VALUES (11, 'Налоги и сборы', 1);
INSERT INTO `doc_rasxodi` (`id`, `name`, `adm`) VALUES (12, 'Средства под отчёт', 0);
INSERT INTO `doc_rasxodi` (`id`, `name`, `adm`) VALUES (13, 'Расходы на рекламу', 1);

COMMIT;


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

COMMIT;
