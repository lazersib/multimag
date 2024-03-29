ALTER TABLE `doc_vars` ADD `firm_leader_post` VARCHAR(32) NOT NULL AFTER `firm_director_r`,
    ADD `firm_leader_post_r` VARCHAR(32) NOT NULL AFTER `firm_leader_post`,
    ADD `firm_leader_reason` VARCHAR(32) NOT NULL AFTER `firm_leader_post_r`, 
    ADD `firm_leader_reason_r` VARCHAR(32) NOT NULL AFTER `firm_leader_reason`;

CREATE TABLE IF NOT EXISTS `intfiles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `attached_to` varchar(48) NOT NULL,
    `description` varchar(128) NOT NULL,
    `original_filename` varchar(128) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `size` bigint(20) NOT NULL,
    `date` DATETIME NOT NULL,
    PRIMARY KEY (`id`), 
    KEY `attached_to` (`attached_to`), 
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `intfiles`
    ADD CONSTRAINT `intfiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `attachments`
    ADD `date` DATETIME NOT NULL,
    ADD `attached_to` varchar(48) NOT NULL,
    ADD `user_id` int(11) DEFAULT NULL,
    ADD `size` bigint(20) NOT NULL,
    CHANGE `comment` `description` VARCHAR(128),
    ADD KEY `attached_to` (`attached_to`),     
    ADD KEY `user_id` (`user_id`),
    ADD CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;


CREATE TABLE IF NOT EXISTS `doc_textdata` (
  `doc_id` int(11) NOT NULL,
  `param` varchar(32) NOT NULL,
  `value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `doc_textdata`
    ADD UNIQUE KEY `doc` (`doc_id`,`param`);

INSERT INTO `doc_textdata` (`doc_id`, `param`, `value`) SELECT `id`, 'contract_text', `comment` FROM `doc_list` WHERE `type`='14' AND `comment`!='';


CREATE TABLE IF NOT EXISTS `contract_templates` (
`id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `text` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `contract_templates`
    ADD PRIMARY KEY (`id`), 
    ADD UNIQUE KEY `name` (`name`),
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

INSERT INTO `contract_templates` (`name`, `text`) VALUES ('Образец договора', '= Договор № {{DOC_NUM}} =
г. Новосибирск, {{DOC_DATE}} .

{{FIRM_NAME}}, именуемый в дальнейшем "Поставщик", в лице {{FIRM_DIRECTOR_R}}, действующего на основании устава, с одной стороны и {{AGENT_FULLNAME}}, именуемый в дальнейшем "Покупатель", в лице {{AGENT_LEADER_POST_R}} {{AGENT_LEADER_NAME_R}}, действующего на основании {{AGENT_LEADER_REASON_R}}, с  другой стороны, заключили настоящий Договор о нижеследующим:
# Предмет Договора
##  Поставщик обязуется поставить, а Покупатель принять и оплатить запасные части и расходные материалы, далее именуемые «Товар» согласовываемый сторонами договора по номенклатуре, количеству и цене, и указываемый в счетах, выставляемых  Поставщиком на основании заявок Покупателя. Товар поставляется отдельными партиями по мере поступления заявок от Покупателя и оплаты выставляемых Поставщиком счетов. 
# Цены и порядок расчетов по договору.
## Поставляемый по настоящему договору Товар оплачивается по ценам согласованным сторонами и указанными в счете, выставленным Поставщиком.
## Цена устанавливается в российских рублях,  включает НДС и действительна на условиях самовывоза Товара Покупателем со склада Поставщика в г. Новосибирск, или Транспортной компанией за счет Покупателя.
## Оплата счета подтверждает факт согласия покупателя с ценой, предлагаемой к поставке товара и соответствия номенклатуры и количеству поставляемого товара заявке Покупателя. Сумма договора определяется суммой фактически осуществленных поставок.                                                                               
## Оплата товара производится путем перевода денежных средств на расчетный счет Поставщика. Платеж считается произведенным в момент зачисления денежных средств на расчетный счет Поставщика. Допускается отсрочка платежа на сумму не более {{DEBT_SIZE}} рублей, на срок не более {{PAY_DEFERMENT}} дней.
## В случае отправки товара Транспортной компанией, право собственности на Товар переходит к Покупателю с момента передачи Товара в Транспортную компанию.
# Условия поставки и передачи.
## Срок поставки Товара указывается в выставляемом счете. Возможна досрочная и частичная поставка товара. 
## Отгрузка Товара происходит только после получения 100% оплаты счета на расчетный счет Поставщика.
# Ответственность сторон.
## При нарушении сроков поставки Товара (указанного в счете) Покупатель имеет право требовать от Поставщика выплаты пени в размере 0,05% за каждый день нарушения срока, но не более 10% от общей суммы поставки.
## В случае необоснованного отказа Покупателя от приемки Товара, за исключением случаев установленных законодательством РФ, Покупатель возмещает Поставщику убытки в части реального ущерба, вызванные необоснованным отказом.
## Неустойка (штрафы, пени), предусмотренная настоящим договором, подлежит начислению и выплате в течение 10 (десяти) рабочих дней с момента получения от одной из сторон соответствующего извещения об уплате неустойки (включая дату получения).
## При наступлении форс-мажорных обстоятельств, стороны освобождаются от своих обязательств до окончания указанных обстоятельств, если сторона, для которой они наступили, в течение 5 дней в письменной форме уведомляет другую сторону о причинах невыполнения условий договора с приложением соответствующих доказательств.
## Под форс-мажорными обстоятельствами понимаются природные явления, пожары, аварии, в том числе на транспорте, забастовки, военные действия, постановления, распоряжения, эмбарго, иные действия органов государственной власти РФ или субъектов РФ.
## За неисполнение или ненадлежащее исполнение обязательств по настоящему договору, стороны несут ответственность в соответствии с действующим законодательством.
## Уплата неустойки не освобождает стороны от исполнения своих обязательств.
# Срок действия договора
## Договор вступает в силу с момента подписания обеими сторонами и действует до {{END_DATE}},  а в части взаиморасчетов до  полного исполнения сторонами своих обязательств.
## Лимит оборотов по договору - {{CONTRACT_LIMIT}} рублей.
## Договор продлевается автоматически на каждый последующий годичный срок, если ни одна из Сторон не позднее, чем за 15 (пятнадцать) дней до завершения действия Договора не заявит о своем желании расторгнуть договор. 
# Прочие положения
## Споры и разногласия, которые могут возникнуть при исполнении настоящего Договора или в связи с ним, решаются сторонами путем переговоров,   
## В случае невозможности разрешения споров и разногласий путём переговоров, они подлежат рассмотрению в Арбитражном суде по месту нахождения истца.
## Стороны признают копии надлежащим образом, подписанных документов, в том числе настоящего договора, до получения оригиналов документов, с официальных электронных адресов Поставщика {{FIRM_EMAIL}} и Покупателя {{AGENT_EMAIL}} и на их официальные электронные адреса считаются действительными до получения оригиналов документов и имеют юридическую силу, с правом их использования, как доказательство при разрешении Сторонами спорных вопросов, как в судебном, так и в досудебном порядке.
## Ни одна из сторон не имеет права передать третьему лицу свои права и обязанности по настоящему Договору без письменного согласия другой стороны.
## Покупатель в течении 10 дней, после получения товара, возвращает Поставщику подписанные оригинал договора и товарно-транспортную накладную почтой, заказным письмом, с уведомлением.
## Все дополнения и изменения к настоящему договору действительны лишь после подписания уполномоченными представителями сторон.
## Приемка продукции по количеству и качеству производится в соответствии с Инструкциями о порядке приемки продукции производственно-технического назначения и товаров народного потребления по количеству и качеству №  П-6 и П-7 (утв. Пост. Госарбитража при СМ СССР от 15.06.65 г. и 25.04.66 г. с последующими изменениями и дополнениями).
##  Риск случайной гибели, порчи, повреждения или утраты Товара  переходит от Поставщика к Покупателю с момента передачи Поставщиком Товара в Транспортную компанию.
## Ни в каком случае не правомерны требования Покупателя по возмещению ущерба, возникшего не в прямой связи с поставкой, как например, производственные простои, упущенная прибыль, потеря заказов, понесенные потери, как и другие прямые и непрямые убытки.
# Юридические адреса сторон
');

TRUNCATE `db_version`;
INSERT INTO `db_version` (`version`) VALUES (912);
