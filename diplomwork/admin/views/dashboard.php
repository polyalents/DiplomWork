<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

$modify = new modify();
$db = new db();

if (!isset($_GET['mode']) or !$_GET['mode']) {
	// модуль по-умолчанию
	$_GET['mode'] = 'pages';
}

// подсветка активной кнопки
$selected = array();
foreach(ALLOWED_MODULES as $allowed_module) {
	// прописываем пустые значения всем, чтобы избавиться от ошибок ниже
	$selected[$allowed_module] = '';
}

// вешаем класс на активную кнопку
if (isset($selected[$_GET['mode']])) {
	$selected[$_GET['mode']] = 'class="selected"';
}

// достаём логотип и favicon
$settings = new page_settings();

// формируем ссылку "просмотр страницы" для страниц и новостей
$view_page_button = '';
if (
	isset($_GET['mode'])
	and in_array($_GET['mode'], array('pages', 'news'))
	and isset($_GET['id'])
	and $page_id = $modify->only_numbers($_GET['id'])
) {
	$link = '';
	if ($_GET['mode'] == 'pages') {
		// это страница
		$query = $db->query("SELECT `link`, `main` FROM `pages` WHERE `id`='$page_id'");
		$row = mysqli_fetch_assoc($query);
		if ($row) {
			if ($row['main']) {
				// главная страница
				$link = '/';
			} else {
				// не главная страница
				$link = '/'.$row['link'];
			}
		}
	} else {
		// это новость
		$query = $db->query("SELECT `link` FROM `news` WHERE `id`='$page_id'");
		$row = mysqli_fetch_assoc($query);
		if ($row) {
			// типичная ссылка на типичную новость
			$link = '/news/'.$row['link'];
		}
	}
	
	// если вытащили ссылку - показываем
	if ($link) {
		$view_page_button = '
		<a href="'.$link.'">
			<i class="fa-solid fa-eye"></i>
			<span class="admin-label">Просмотр страницы</span>
		</a>';
	}
}


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
	<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css" />
	
	<script src="/assets/jquery.min.js"></script>

	<script src="/admin/assets/ckeditor/ckeditor.js"></script>
	<script src="/admin/assets/ace/ace.js"></script>
	<script src="/admin/assets/ace/ext-language_tools.js"></script>
	<script src="/admin/assets/ace/theme-merbivore_soft.js"></script>

	<script src="/admin/assets/jquery-ui-1.13.2.custom/jquery-ui.min.js"></script>
	<link rel="stylesheet" href="/admin/assets/jquery-ui-1.13.2.custom/jquery-ui.min.css" />

	<script src="/admin/js/dashboard.js"></script>
	<script src="/admin/js/sign-out.js"></script>
	<link rel="stylesheet" href="/admin/css/styles.css">
</head>
<body>
<header id="admin-header">
	<div id="admin-header-left">
		<a href="/">
			<i class="fa-solid fa-arrow-left-long"></i>
			Вернуться на сайт
		</a>
		<?php echo $view_page_button; ?>
	</div>
	<div>
		Добро пожаловать, <?php echo USER['name']; ?>
		<i class="jsSignOut fa-solid fa-arrow-right-from-bracket"></i>
	</div>
</header>

<aside id="side-nav">
	<img id="side-logo" src="<?php $settings->site_logo(); ?>" alt="logo">

	<ul>
		<li>
			<a href="/admin" <?php echo $selected['pages']; ?>>
				<i class="fa-solid fa-layer-group"></i>
				Страницы
			</a>
		</li>
		<li>
			<a href="/admin?mode=news" <?php echo $selected['news']; ?>>
				<i class="fa-solid fa-newspaper"></i>
				Новости
			</a>
		</li>
		<li>
			<a href="/admin?mode=nav" <?php echo $selected['nav']; ?>>
				<i class="fa-solid fa-bars"></i>
				Навигация
			</a>
		</li>
		<li>
			<a href="/admin?mode=calls" <?php echo $selected['calls']; ?>>
				<i class="fa-regular fa-envelope"></i>
				Заявки
			</a>
		</li>
	</ul>
	<ul>
		<li>
			<a href="/admin?mode=admins" <?php echo $selected['admins']; ?>>
				<i class="fa-solid fa-user-tie"></i>
				Админы
			</a>
		</li>
		<li>
			<a href="/admin?mode=settings" <?php echo $selected['settings']; ?>>
				<i class="fa-solid fa-hammer"></i>
				Настройки
			</a>
		</li>
	</ul>
</aside>
<main id="admin-content">
	<div class="container">
<?php
if (in_array($_GET['mode'], ALLOWED_MODULES)) {
	// подключаем файл по параметру "mode" из списка разрешённых
	require_once 'modules/'.$_GET['mode'].'.php';

	// инициализируем класс (он с таким же названием, как и файл)
	$module = $_GET['mode'];
	$module = new $module();

	// у каждого модуля есть метод "render" для вывода HTML
	$module->render();
} else {
	// модуля нет в списке разрешённых
	echo '<div class="error">Нет такого модуля.</div>';
}

?>
	</div><!-- .container -->
</main><!-- #admin-content -->

<!-- вёрстка модального окна -->
<div id="modal" style="display: none;">
	<div id="modal-wrap">
		<i class="fa-solid fa-xmark jsCloseModal"></i>
		<div id="modal-content"></div>
	</div>
</div>
</body>
</html>