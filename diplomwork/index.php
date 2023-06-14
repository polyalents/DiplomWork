<?php
// сигналим, что отсюда можно загружать файлы
const INDEX_LOAD = true;
require_once 'init.php';

$router = new router();
$route = $router->route();
define('SITE_ROUTE', $route);

require_once 'parts/header.php';
switch($route['module']) {
	default:
		// неопознанный модуль - ошибка
		require_once('modules/front_errors.php');
		$error = new front_errors();
		$error->error_404();
		break;
	case 'news':
		// вывод новости
		require_once('modules/front_news.php');
		$news = new front_news();
		$news->render($route['id']);
		break;
	case 'pages':
		// вывод страницы
		require_once('modules/front_pages.php');
		$page = new front_pages();
		$page->render($route['id']);
		break;
	case 'error':
		// вывод ошибки
		require_once('modules/front_errors.php');
		$error = new front_errors();
		$error->{$route['code']}();
		break;
}

require_once 'parts/footer.php';