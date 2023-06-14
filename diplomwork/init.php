<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

// подключаем составные части проекта
require_once('config.php');
require_once('includes/db.php');
require_once('includes/modify.php');
require_once('includes/dates.php');
require_once('includes/ajax.php');
// вывод атрибутов страницы
require_once('includes/page_settings.php');

if (defined('DASHBOARD')) {
	// только для админки
	require_once('admin/includes/files.php');
} else {
	// ЧПУ (красивые ссылки)
	require_once('includes/router.php');
	// вывод навигации
	require_once('parts/front_nav.php');
	// панель администратора
	require_once('parts/admin_header.php');
	// отправка почты (файл сгенерировал Composer)
	require_once('vendor/autoload.php');
	// отправка почты (мой файл)
	require_once('includes/mailer.php');
}

// находим абсолютный путь к корню сайта
$path = pathinfo(__FILE__);
$path = $path['dirname'];
$path = str_replace("\\", '/', $path);
define('ROOT_DIR', $path);

// коннектимся к базе
$db = new db();
$db->connect_db();

// проверяем авторизацию
$auth = '';
$user = null;

// смотрим есть ли у юзера ключ авторизации в сессии или куке
session_start();
if (isset($_SESSION['auth'])) {
	$auth = $db->escape($_SESSION['auth']);
} elseif (isset($_COOKIE['auth'])) {
	$auth = $db->escape($_COOKIE['auth']);
}
session_write_close();

if ($auth) {
	// проверяем есть ли такой ключ в базе
	$query = $db->query("SELECT `id`, `name` FROM `admins` WHERE `auth`='$auth'");
	$row = mysqli_fetch_assoc($query);
	if (isset($row['id']) and $row['id']) {
		// вытаскиваем юзера, если нашли по ключу
		$user = $row;
	}
}

// null - если юзер не залогинен, массив с данными юзера - если залогинен
define('USER', $user);

// список разрешённых модулей (для защиты)
const ALLOWED_MODULES = array(
	'admins',
	'news',
	'pages',
	'nav',
	'settings',
	'calls'
);