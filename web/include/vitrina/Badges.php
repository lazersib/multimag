<?php
//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2019, BlackLight, TND Team, http://tndproject.org
//
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU Affero General Public License as
//	published by the Free Software Foundation, either version 3 of the
//	License, or (at your option) any later version.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU Affero General Public License for more details.
//
//	You should have received a copy of the GNU Affero General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

class Badges
{
	/**
	 * имя get параметра
	 */
	const PARAM_NAME = 'dop_type';
	/**
	 * значения get параметра для сброса фильтров
	 */
	const CLEAR_TRIGGER = 'clear';

	/**
	 * Постоить фильтр по типу товаров
	 * @param $group id группы товаров
	 */
	public static function productTypeFilter($group)
	{
		$dop_type = self::getParam();
		foreach (self::getBadgeData($group) as $badge) {
			self::addBadge($badge['name'], $badge['count'], $badge['link'], in_array($badge['id'], $dop_type));
		}
	}


	/**
	 * Получить значения из get параметра с фитром
	 * @return array|null
	 */
	public static function getParam()
	{
		if($_GET[self::PARAM_NAME] == self::CLEAR_TRIGGER) {
			unset($_GET[self::PARAM_NAME]);
			unset($_SESSION[self::PARAM_NAME]);
		}
		if(isset($_GET[self::PARAM_NAME]) || isset($_SESSION[self::PARAM_NAME])) {
			if(isset($_GET[self::PARAM_NAME])) $_SESSION[self::PARAM_NAME] = $_GET[self::PARAM_NAME];
			$dop_type =  explode(',', $_GET[self::PARAM_NAME] ? $_GET[self::PARAM_NAME] : $_SESSION[self::PARAM_NAME]);
			$dop_type = array_map('intval', $dop_type);
			$dop_type = array_map('strval', $dop_type);
		} else {
			$dop_type = null;
		}
		return $dop_type;
	}

	/**
	 * Выборка из базы
	 * Типы товаров с количеством товаров
	 * @param $group id группы товаров
	 * @return array
	 */
	protected static function getBadgeData($group)
	{
		global $db;
		if (isset($_GET['op'])) {
			$_SESSION['vit_photo_only'] = $_GET['op'] ? 1 : 0;
		}
		$sql_photo_only = @$_SESSION['vit_photo_only'] ? "AND `img_id` IS NOT NULL" : "";
		$sql = "
			SELECT count(`doc_base`.`id`) as count, `doc_base_dop_type`.`name` as `dop_type_name`, `doc_base_dop_type`.`id`
			FROM `doc_base`
			LEFT JOIN `doc_base_dop` ON `doc_base_dop`.`id`=`doc_base`.`id`
			LEFT JOIN `doc_base_dop_type` ON `doc_base_dop_type`.`id`=`doc_base_dop`.`type`
			WHERE `doc_base`.`group`='$group' AND `doc_base`.`hidden`='0' $sql_photo_only GROUP BY `doc_base_dop_type`.`id`
		";
		$res = $db->query($sql);
		if($res->num_rows) {
			while($badge =  mysqli_fetch_assoc($res)) {
				if(!isset($badge['id'])) continue;
				$result[] = [
					'id' => $badge['id'],
					'link' => self::createBadgeUrl($badge['id']),
					'name' => $badge['dop_type_name'],
					'count' => $badge['count'],
				];
			}
		}
		return $result ?? [];
	}

	/**
	 * генерация ссылок по id типа товара
	 * @param $badgeId id тип товара
	 * @return string
	 */
	protected static function createBadgeUrl($badgeId)
	{
		$url = $_SERVER['REQUEST_URI'];
		$path = parse_url( $url, PHP_URL_PATH);
		parse_str( parse_url( $url, PHP_URL_QUERY), $params );
		$types = self::getParam() ?? [];

		if(!in_array($badgeId, $types))  {
			if (($key = array_search(self::CLEAR_TRIGGER, $types)) !== false) {
				unset($types[$key]);
			}
			array_push($types, $badgeId);
		}
		else if(in_array($badgeId, $types)) {
			if (($key = array_search($badgeId, $types)) !== false) {
				unset($types[$key]);
			}
		}

		if($types) {
			$params[self::PARAM_NAME] = implode(',', $types);
		} else {
			unset($params[self::PARAM_NAME]);
		}

		$query = http_build_query ($params);
		return $path . ($params[self::PARAM_NAME]
				? '?' . $query
				: '?'.http_build_query ($params+[self::PARAM_NAME => self::CLEAR_TRIGGER]));
	}



	/**
	 * Добавить кнопку с типом товара и количеством товара
	 * в этой категории
	 * @param $name название категории
	 * @param $count кол-во товара
	 * @param $link ссылка на фильтр по категории
	 * @param $active активна ли ссылка
	 */
	protected static function addBadge($name, $count, $link, $active)
	{
		global $tmpl;
		$active_class =  $active ? 'btn-primary' : 'btn-light';
		$tmpl->addContent("
			<button type=\"button\" class=\"btn $active_class\">
				<a href=\"$link\">$name</a>
	            <span class=\"badge badge-light\">$count</span>
			</button>
		");
	}
}