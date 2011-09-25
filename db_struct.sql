SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


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
  `responser` INT(11) NOT NULL COMMENT 'Автор ответа' ,
  PRIMARY KEY (`id`) ,
  INDEX `object_name` (`object_name` ASC) ,
  INDEX `object_id` (`object_id` ASC) ,
  INDEX `rate` (`rate` ASC) ,
  INDEX `date` (`date` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
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
DEFAULT CHARACTER SET = latin1;


-- -----------------------------------------------------
-- Table `currency`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `currency` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(8) NOT NULL ,
  `coeff` DECIMAL(8,4) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  UNIQUE INDEX `name` (`name` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


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
DEFAULT CHARACTER SET = utf8;


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
  `responsible` INT(11) NOT NULL ,
  `data_sverki` DATE NOT NULL ,
  `dishonest` TINYINT(4) NOT NULL COMMENT 'Недобросовестный' ,
  UNIQUE INDEX `id` (`id` ASC) ,
  UNIQUE INDEX `uniq_name` (`group` ASC, `name` ASC) ,
  INDEX `name` (`name` ASC) ,
  INDEX `fullname` (`fullname`(255) ASC) ,
  INDEX `tel` (`tel` ASC) ,
  INDEX `inn` (`inn` ASC) ,
  INDEX `type` (`type` ASC) ,
  INDEX `pasp_num` (`pasp_num` ASC, `pasp_date` ASC, `pasp_kem` ASC) ,
  INDEX `group` (`group` ASC) ,
  CONSTRAINT `doc_agent_ibfk_1`
    FOREIGN KEY (`group` )
    REFERENCES `doc_agent_group` (`id` ),
  CONSTRAINT `doc_agent_ibfk_1`
    FOREIGN KEY (`group` )
    REFERENCES `doc_agent_group` (`id` ))
ENGINE = InnoDB
AUTO_INCREMENT = 3
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'pcomment - printable comment' ;


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
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `doc_units`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_units` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(16) NOT NULL ,
  `printname` VARCHAR(8) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `name` (`name` ASC) ,
  INDEX `printname` (`printname` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 8
DEFAULT CHARACTER SET = utf8;


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
  UNIQUE INDEX `id` (`id` ASC) ,
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
  `name` VARCHAR(100) NOT NULL ,
  `comment` TEXT NOT NULL ,
  `dnc` TINYINT(4) NOT NULL COMMENT 'Не контролоировать остатки' ,
  INDEX `id` (`id` ASC) ,
  INDEX `name` (`name` ASC) ,
  INDEX `dnc` (`dnc` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 3
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
  PRIMARY KEY (`id`, `sklad`) ,
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
  `name` VARCHAR(25) NOT NULL ,
  `type` VARCHAR(5) NOT NULL ,
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
  `type` VARCHAR(5) NOT NULL ,
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
  `analog` VARCHAR(20) NOT NULL ,
  `koncost` DOUBLE NOT NULL DEFAULT '0' ,
  `strana` VARCHAR(20) NOT NULL ,
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
  `name` VARCHAR(70) NOT NULL ,
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
  `name` VARCHAR(100) NOT NULL ,
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
  `pos_id` INT(11) NOT NULL ,
  `img_id` INT(11) NOT NULL ,
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
-- Table `doc_base_params`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_base_params` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `param` VARCHAR(32) NOT NULL ,
  `type` VARCHAR(8) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `param` (`param` ASC) )
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
  `name` VARCHAR(30) NOT NULL ,
  INDEX `id` (`id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 18
DEFAULT CHARACTER SET = utf8;


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
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Список пользователей' ;


-- -----------------------------------------------------
-- Table `doc_vars`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_vars` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `firm_name` VARCHAR(150) NOT NULL ,
  `firm_director` VARCHAR(100) NOT NULL ,
  `firm_director_r` VARCHAR(100) NOT NULL ,
  `firm_manager` VARCHAR(100) NOT NULL ,
  `firm_buhgalter` VARCHAR(100) NOT NULL ,
  `firm_kladovshik` VARCHAR(100) NOT NULL ,
  `firm_kladovshik_id` INT(11) NOT NULL ,
  `firm_bank` VARCHAR(100) NOT NULL ,
  `firm_bank_kor_s` VARCHAR(25) NOT NULL ,
  `firm_bik` VARCHAR(15) NOT NULL ,
  `firm_schet` VARCHAR(25) NOT NULL ,
  `firm_inn` VARCHAR(25) NOT NULL ,
  `firm_adres` VARCHAR(150) NOT NULL ,
  `firm_realadres` VARCHAR(150) NOT NULL ,
  `firm_gruzootpr` VARCHAR(300) NOT NULL ,
  `firm_telefon` VARCHAR(60) NOT NULL ,
  `firm_okpo` VARCHAR(10) NOT NULL ,
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
  `comment` TEXT NOT NULL ,
  `date` BIGINT(20) NOT NULL DEFAULT '0' ,
  `ok` BIGINT(20) NOT NULL DEFAULT '0' ,
  `sklad` TINYINT(4) NOT NULL DEFAULT '0' ,
  `kassa` TINYINT(4) NOT NULL DEFAULT '0' ,
  `bank` TINYINT(4) NOT NULL DEFAULT '0' ,
  `user` INT(11) NOT NULL DEFAULT '0' ,
  `altnum` INT(11) NOT NULL ,
  `subtype` VARCHAR(5) NOT NULL ,
  `sum` DECIMAL(10,2) NOT NULL DEFAULT '0.00' ,
  `nds` INT(11) NOT NULL DEFAULT '0' ,
  `p_doc` INT(11) NOT NULL ,
  `mark_del` BIGINT(20) NOT NULL ,
  `firm_id` INT(11) NOT NULL DEFAULT '1' ,
  `err_flag` TINYINT(4) NOT NULL DEFAULT '0' ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `type` (`type` ASC) ,
  INDEX `fio` (`agent` ASC) ,
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
  `param` VARCHAR(20) NOT NULL ,
  `value` VARCHAR(150) NOT NULL ,
  UNIQUE INDEX `doc` (`doc` ASC, `param` ASC) ,
  INDEX `value` (`value` ASC) ,
  CONSTRAINT `doc_dopdata_ibfk_1`
    FOREIGN KEY (`doc` )
    REFERENCES `doc_list` (`id` ),
  CONSTRAINT `doc_dopdata_ibfk_1`
    FOREIGN KEY (`doc` )
    REFERENCES `doc_list` (`id` ))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


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
  `name` VARCHAR(50) NOT NULL ,
  `ballance` DECIMAL(10,2) NOT NULL ,
  `bik` VARCHAR(20) NOT NULL ,
  `rs` VARCHAR(30) NOT NULL ,
  `ks` VARCHAR(30) NOT NULL ,
  `firm_id` INT(11) NOT NULL ,
  UNIQUE INDEX `ids` (`ids` ASC, `num` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


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
  `motion` VARCHAR(100) NOT NULL ,
  `desc` VARCHAR(500) NOT NULL ,
  `time` DATETIME NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `user` (`user` ASC) ,
  INDEX `motion` (`motion` ASC) ,
  INDEX `time` (`time` ASC) ,
  INDEX `desc` (`desc`(333) ASC) ,
  INDEX `ip` (`ip` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `doc_rasxodi`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `doc_rasxodi` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(50) NOT NULL ,
  `adm` TINYINT(4) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `name` (`name` ASC) ,
  INDEX `adm` (`adm` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 15
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
  `ip` VARCHAR(18) NOT NULL ,
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
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `firm_info`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `firm_info` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(100) NOT NULL DEFAULT '' ,
  `signature` VARCHAR(200) NOT NULL DEFAULT '' COMMENT 'Сигнатура для определения принадлежности прайса' ,
  `currency` TINYINT(4) NOT NULL ,
  `coeff` DECIMAL(10,3) NOT NULL ,
  `last_update` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `name` (`name` ASC) ,
  INDEX `sign` (`signature` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `firm_info_struct`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `firm_info_struct` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `firm_id` INT(11) NOT NULL COMMENT 'Номер фирмы' ,
  `table_name` VARCHAR(50) NOT NULL COMMENT 'Название листа прайса' ,
  `name` MEDIUMINT(9) NOT NULL COMMENT 'N колонки наименований' ,
  `cost` MEDIUMINT(9) NOT NULL ,
  `art` MEDIUMINT(9) NOT NULL ,
  `nal` MEDIUMINT(9) NOT NULL ,
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
DEFAULT CHARACTER SET = utf8;


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
DEFAULT CHARACTER SET = latin1;


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
  INDEX `ex_date` (`ex_date` ASC) )
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `notes`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `notes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `user` INT(11) NOT NULL ,
  `sender` INT(11) NOT NULL ,
  `head` VARCHAR(50) NOT NULL ,
  `msg` TEXT NOT NULL ,
  `senddate` DATETIME NOT NULL ,
  `enddate` DATETIME NOT NULL ,
  `ok` TINYINT(4) NOT NULL ,
  `comment` TEXT NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `user` (`user` ASC) ,
  INDEX `sender` (`sender` ASC) ,
  INDEX `senddate` (`senddate` ASC) ,
  INDEX `enddate` (`enddate` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `price`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `price` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(200) NOT NULL DEFAULT '' ,
  `cost` DOUBLE NOT NULL DEFAULT '0' ,
  `firm` INT(11) NOT NULL DEFAULT '0' ,
  `art` VARCHAR(20) NOT NULL DEFAULT '' ,
  `nal` VARCHAR(20) NOT NULL ,
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
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `parsed_price`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `parsed_price` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `firm` INT(11) NOT NULL ,
  `pos` INT(11) NOT NULL ,
  `cost` DECIMAL(10,2) NOT NULL ,
  `nal` VARCHAR(10) NOT NULL ,
  `from` INT(11) NOT NULL ,
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
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `photogalery`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `photogalery` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `uid` INT(11) NOT NULL DEFAULT '0' ,
  `comment` VARCHAR(50) NOT NULL ,
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
DEFAULT CHARACTER SET = utf8;


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
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8, 
COMMENT = 'Список замен для регулярных выражений анализатора прайсов' ;


-- -----------------------------------------------------
-- Table `questions`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `questions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `text` VARCHAR(200) NOT NULL ,
  `mode` INT(11) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) )
ENGINE = MyISAM
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
  INDEX `ip` (`ip` ASC) )
ENGINE = MyISAM
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
  INDEX `q_id` (`q_id` ASC, `var_id` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `seekdata`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `seekdata` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(200) NOT NULL ,
  `sql` VARCHAR(200) NOT NULL ,
  `regex` VARCHAR(200) NOT NULL ,
  `group` INT(11) NOT NULL ,
  `regex_neg` VARCHAR(256) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `name` (`name` ASC) ,
  INDEX `sql` (`sql` ASC) ,
  INDEX `regex` (`regex` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


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
    REFERENCES `users` (`id` ))
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
  INDEX `uid` (`uid` ASC, `ticket` ASC, `date` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `tickets_priority`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tickets_priority` (
  `id` TINYINT(4) NOT NULL ,
  `name` VARCHAR(50) NOT NULL ,
  `color` VARCHAR(6) NOT NULL ,
  `comment` VARCHAR(200) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `name` (`name` ASC) )
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `tickets_state`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tickets_state` (
  `id` INT(11) NOT NULL ,
  `name` VARCHAR(30) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) )
ENGINE = MyISAM
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
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `users_bad_auth`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `users_bad_auth` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `ip` VARCHAR(24) NOT NULL ,
  `time` DOUBLE NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `ip` (`ip` ASC) ,
  INDEX `date` (`time` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = latin1;


-- -----------------------------------------------------
-- Table `users_data`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `users_data` (
  `uid` INT(11) NOT NULL DEFAULT '0' ,
  `param` VARCHAR(25) NOT NULL ,
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
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `users_grouplist`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `users_grouplist` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(50) NOT NULL ,
  `comment` TEXT NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `name` (`name` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = latin1, 
COMMENT = 'Spisok grupp' 
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
    REFERENCES `users` (`id` ))
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
-- Table `users_objects`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `users_objects` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `object` VARCHAR(32) NOT NULL ,
  `desc` VARCHAR(128) NOT NULL ,
  `actions` VARCHAR(128) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) ,
  INDEX `object` (`object` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 45
DEFAULT CHARACTER SET = utf8;


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
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `wikiphoto`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `wikiphoto` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `uid` INT(11) NOT NULL DEFAULT '0' ,
  `comment` VARCHAR(64) NOT NULL ,
  UNIQUE INDEX `id` (`id` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
