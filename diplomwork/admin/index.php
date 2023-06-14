<?php
// сигналим, что отсюда можно загружать файлы
const INDEX_LOAD = true;
// сигналим, что это админка
const DASHBOARD = true;
require_once '../init.php';
if (USER) {
	// юзер залогинен
	require_once 'views/dashboard.php';
} else {
	// юзер не залогинен
	// на странице авторизации у нас загружено меньше скриптов, стилей и библиотек
	require_once 'views/auth.php';
}