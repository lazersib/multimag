<?php
require_once("config_all.php");

$CONFIG['site']['skin']			= 'default';		// default по умолчанию
$CONFIG['site']['inner_skin']		= 'inner';	// по умолчанию = предыдущему

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
$CONFIG['site']['vitrina_order']	= 'vc';	// Сортировка по умолчанию для витрины. n - по имени, vc - по коду, c - по цене, s - по наличию
$CONFIG['site']['recode_enable']	= false;// Разрешить "красивые" ссылки. Необходим mod_recode.
$CONFIG['site']['dowload_attach_speed'] = 16;	// Скорость скачивания вложений с сайта(кбайт/сек). Для снижения расхода памяти рекомендуются меньшие значения.

					// Для работы этих опций нужен правильно настроенный SSL. Обе опции умолчанию - false
$CONFIG['site']['force_https']		= false;	// Принудительно использовать https при открытии любой страницы сайта. Желательно включить.
$CONFIG['site']['force_https_login']	= false;	// Принудительно использовать https при аутентификации, регистрации. Не влияет на шаблоны.
							// НАСТОЯТЕЛЬНО РЕКОМЕНДУЕТСЯ ВКЛЮЧИТЬ ПРИ НАЛИЧИИ ТЕХНИЧЕСКОЙ ВОЗМОЖНОСТИ

$CONFIG['poseditor']['sn_enable']	= false;// Включить поддержку работы с серийными номерами
$CONFIG['poseditor']['sn_restrict']	= false;// Включить ограничения на выписку документов без серийных номеров
$CONFIG['poseditor']['need_dialog']	= 0;	// Показывать диалог с запросом цены и количества при добавлении позиции
$CONFIG['poseditor']['vc']		= 0;	// Показывать код производителя
$CONFIG['poseditor']['tdb']		= 0;	// Показывать размеры
$CONFIG['poseditor']['rto']		= 1;	// Показывать резервы/транзиты/заявки
$CONFIG['poseditor']['true_gtd']	= 0;	// Использовать 'правильную' схему учёта ГТД. Иначе - берётся из доп. свойств наименования

$CONFIG['doc']['require_pack_count']	= false;// Не проводить документы, если не указано количество мест
$CONFIG['doc']['require_storekeeper']	= false;// Не проводить документы, если не выбран кладовщик
$CONFIG['doc']['use_persist_altnum']	= true;	// Использовать непрерывную нумерацию документов
$CONFIG['doc']['no_print_vendor']	= false;// Не печатать производителя в документах, прайсах, и пр.
$CONFIG['doc']['mincount_info']		= false;// Показывать информацию о выходе за пределы минимальных остатков
$CONFIG['doc']['update_in_cost']	= 0;	// Обновлять базовую цену при проведении поступления. ВНИМАНИЕ! ПОТЕНЦИАЛЬНО ОПАСНАЯ ФУНКЦИЯ!
						// 0 - не обновлять
						// 1 - обновлять по текущей цене поступления
						// 2 - обновлять по актуальной цене поступления

$CONFIG['stock']['default_cost']	= 0;	// Цена по умолчанию для доп.столбца на складе. По умолчанию 0 - не задано

$CONFIG['images']['watermark']		= 1;	// Показывать ли название сайта поверх изображений. Ещё варианты:
						// $CONFIG['images']['watermark']=array('w'=>0,'p'=>'1','g'=>'1');
$CONFIG['images']['font_watermark']	= '';	// Шрифт текста, накладываемого на изображение
$CONFIG['images']['quality']		= 70;	// Качество (по уровню сжатия) изображений

$CONFIG['noify']['comments']		= true;	// Оповещать о коментариях

// Расположение изменяемых доступных данных - изображения, итп
$CONFIG['site']['var_data_web']		= '/share/var';		// по отношению к корню сайта
$CONFIG['site']['var_data_fs']		= $CONFIG['site']['location'].$CONFIG['site']['var_data_web'];	// по отношению к корню файловой системы

?>
