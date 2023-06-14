<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

// отладка
//ini_set('display_startup_errors',1);
//ini_set('display_errors',1);
//error_reporting(E_ERROR | E_WARNING | E_PARSE);

const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASSWORD = 'root';
const DB_NAME = 'polya7';

// размеры миниатюр, которые создаются при загрузке изображений в разных разделах админки
const THUMBS = array(
	// для новостей
	'news' => array(
		'width' => 420,
		'height' => 280,
		'crop' => true,
		'format' => 'webp'
	),
	// превьюшки для вывода в админском разделе
	'admin' => array(
		'width' => 80,
		'height' => 80,
		'crop' => true,
		'format' => 'webp'
	)
);

// настройка отправки почты
const SENDER_EMAIL = array(
	'name' => 'Стройград',
	'smtp_host' => 'ssl://smtp.timeweb.ru',
	'smtp_port' => 465,
	'smtp_login' => 'noreply@mapeirostov.ru',
	'smtp_password' => 'nYj7W4FmyuTJ9cTv',
);