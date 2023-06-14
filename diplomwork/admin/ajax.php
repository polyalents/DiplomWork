<?php
// этот файл запускается прямым обращением из JS
// поэтому, надо инициализировать все базовые штуки по новой, как в index.php
const INDEX_LOAD = true;
const DASHBOARD = true;
require_once '../init.php';

// для вывода ошибок
$ajax = new ajax();

if (!isset($_POST['module'])) {
	$ajax->error('Не получен ключ module.');
}

if (!in_array($_POST['module'], ALLOWED_MODULES)) {
	$ajax->error('Неразрешённый ключ module.');
}

if (!isset($_POST['method']) or !$_POST['method']) {
	$ajax->error('Не получен ключ method.');
}

// незалогиненные ничего не могут делать в админке
if (!USER) {
	$ajax->error('У вас нет прав на выполнение этого действия.');
}

// подключаем модуль, куда ссылается ajax-запрос
$module_file = ROOT_DIR.'/admin/modules/'.$_POST['module'].'.php';
if (!file_exists($module_file)) {
	$ajax->error('Файл модуля не существует: '.$module_file);
}
require_once $module_file;

// запускаем модуль
$module = new $_POST['module']();
if (!method_exists($module, $_POST['method'])) {
	$ajax->error('Метод не существует: '.$_POST['method']);
}

// запускаем метод
$result_array = $module->{$_POST['method']}();
echo json_encode($result_array);