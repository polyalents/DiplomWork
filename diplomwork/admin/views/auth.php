<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

$db = new db();

// достаём логотип и favicon
$settings = new page_settings();

?><!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport"
		  content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<link rel="shortcut icon" href="<?php $settings->favicon(); ?>" type="image/png">
	
	<title><?php $settings->site_title();?> - Админка</title>
	
	<link rel="stylesheet" href="/assets/montserrat/montserrat.css" >
	<script src="/assets/jquery.min.js"></script>
	
	<script src="/admin/js/auth.js"></script>
	<link rel="stylesheet" href="/admin/css/styles.css">
</head>
<body>
<div class="admin-login-wrap">
	<a id="site_logo" href="/">
		<img src="<?php $settings->site_logo(); ?>" alt="logo">
	</a>
	<form class="admin-login-form">
		<div class="jsResult" style="display: none"></div>

		<label>
			<span>Логин:</span>
			<input type="text" maxlength="255" name="login">
		</label>
		<label>
			<span>Пароль:</span>
			<input type="password" name="password">
		</label>

		<div class="centered"><button type="submit">Войти</button></div>
	</form>
</div>
</body>
</html>