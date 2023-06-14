<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

// отладка
//ini_set('display_startup_errors',1);
//ini_set('display_errors',1);
//error_reporting(E_ERROR | E_WARNING | E_PARSE);

const DB_HOST = '';
const DB_USER = '';
const DB_PASSWORD = '';
const DB_NAME = '';

// размеры миниатюр, которые создаются при загрузке изображений в разных разделах админки
const THUMBS = array(
	// для новостей
	'news' => array(
		'width' => 420,
		'height' => 280,
		'crop' => true
	),
	// превьюшки для вывода в админском разделе
	'admin' => array(
		'width' => 80,
		'height' => 80,
		'crop' => true
	)
);

// настройка отправки почты
const SENDER_EMAIL = array(
	'name' => 'Имя отправителя',
	'smtp_host' => '',
	'smtp_port' => 465,
	'smtp_login' => '',
	'smtp_password' => '',
);