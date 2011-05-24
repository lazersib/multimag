<?php
require_once("config_all.php"); 

$CONFIG['site']['skin']			= '';		// default по умолчанию
$CONFIG['site']['inner_skin']		= 'default';	// по умолчанию = предыдущему

// Настройки прайса
$CONFIG['site']['price_col_cnt']	= 0;	// 2 по умолчанию
$CONFIG['site']['price_width_cost']	= 0;	// 12 по умлочанию
$CONFIG['site']['price_width_name']	= 0;	// 0 по умолчанию (автоматически)
$CONFIG['site']['price_text']		= array(
'Ваш адрес',
'Ваши телефоны',
'Ваши e-mail, jabber, ICQ',
'Ещё какая-то информация'
);

$CONFIG['site']['doc_header']		= '';	// Картинка - в шапке документов. {FN} будет заменён на номер фирмы
$CONFIG['site']['doc_shtamp']		= '';	// Картинка - штамп в документах. {FN} будет заменён на номер фирмы

$CONFIG['site']['vitrina_glstyle']	= '';	// Стиль списка групп на витрине
$CONFIG['site']['vitrina_plstyle']	= '';	// Стиль списка товаров на витрине
$CONFIG['site']['vitrina_limit']	= '';	// Количество товаров на страницу
$CONFIG['site']['vitrina_nds']		= 1;	// НДС при выписке счёта. 0 - выделять, 1 - включать
$CONFIG['site']['vitrina_pcnt']		= 1;	// Как отображать наличие товара. 0 - цифрой, 1 - звёздочками, 2 - много/мало
$CONFIG['site']['vitrina_pcnt_limit']	= array(1,10,100);	// Лимиты для значений мало/есть/много, либо звёздочек
$CONFIG['site']['recode_enable']	= false;// Разрешить "красивые" ссылки. Необходим mod_recode.

$CONFIG['poseditor']['need_dialog']	= 0;	// Показывать диалог с запросом цены и количества при добавлении позиции 

// Расположение изменяемых доступных данных - изображения, итп 
$CONFIG['site']['var_data_web']		= '/share/var';		// по отношению к корню сайта
$CONFIG['site']['var_data_fs']		= $CONFIG['site']['location'].$CONFIG['site']['var_data_web'];	// по отношению к корню файловой системы

?>
