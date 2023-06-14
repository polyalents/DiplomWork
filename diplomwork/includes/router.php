<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}
/**
 * ЧПУ (красивые ссылки)
 */
class router {
	/**
	 * Пытаемся определить какой контент вытаскивать
	 * @return array|string[]
	 */
	public function route() {
		$db = new db();
		
		if (!isset($_SERVER["PATH_INFO"])) {
			// в пути пусто - возвращаем домашнюю
			return $this->route_to_main();
		}
		
		$path = trim($_SERVER["PATH_INFO"], '/');
		if (!$path) {
			// в пути пусто - возвращаем домашнюю
			return $this->route_to_main();
		}
		
		// пытаемся разобрать ссылку через слеши
		$path = explode('/', $path);
		
		// ищем в ссылке новости
		if (isset($path[0]) and $path[0] == 'news' and isset($path[1]) and $path[1]) {
			$link = $db->prepare_str($path[1]);
			$query = $db->query("SELECT `id`, `link` FROM `news` WHERE `link`='$link'");
			$row = mysqli_fetch_assoc($query);
			if ($row) {
				return array('module' => 'news', 'id' => $row['id'], 'link' => $row['link']);
			}
		}
		
		// ищем в ссылке страницу
		if (isset($path[0]) and $path[0]) {
			$link = $db->prepare_str($path[0]);
			$query = $db->query("SELECT `id`, `link`, `main` FROM `pages` WHERE `link`='$link' AND `main`=0");
			$row = mysqli_fetch_assoc($query);
			if ($row) {
				return array('module' => 'pages', 'id' => $row['id'], 'link' => $row['link'], 'main' => $row['main']);
			}
		}
		
		// в ссылке что-то есть, но мы ничего не нашли - это 404
		return array('module' => 'error', 'code' => 'error_404');
	}
	
	/**
	 * Пытаемся вытащить главную страницу
	 * @return array|string[]
	 */
	private function route_to_main() {
		$db = new db();
		$query = $db->query("SELECT `id`, `link`, `main` FROM `pages` WHERE `main`=1");
		$row = mysqli_fetch_assoc($query);
		if ($row) {
			return array('module' => 'pages', 'id' => $row['id'], 'link' => $row['link'], 'main' => $row['main']);
		} else {
			return array('module' => 'error', 'code' => 'error_main');
		}
	}
}