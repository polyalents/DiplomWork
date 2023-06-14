<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

class front_nav {
	function render() {
		$db = new db();
		
		$content = '';
		$query = $db->query("SELECT * FROM `nav` ORDER BY `order`");
		
		while($row = mysqli_fetch_assoc($query)) {
			// подсвечиваем активную кнопку
			$active = '';
			$route = SITE_ROUTE;
			
			// страницы с какой-то ссылкой
			$button_module = trim($row['href'], '/');
			if (isset($route['link']) and $button_module == $route['link']) {
				$active = 'active';
			}
			
			$button_module = explode('/', $button_module);
			$button_module = $button_module[0];
			
			if (isset($route['module']) and $button_module == $route['module']) {
				$active = 'active';
			}
			
			// главная страница
			if (isset($route['main']) and $route['main'] and !trim($row['href'], '/')) {
				$active = 'active';
			}
			$content .= '<li id="nav-link-'.$row['id'].'"><a href="'.$row['href'].'" class="'.$active.'">'.$row['name'].'</a></li>';
		}
		
		if ($content) {
			$content = '
			<nav id="site-nav">
				<ul>'.$content.'</ul>
				<span id="mobile-menu-close"><i class="fa-solid fa-xmark"></i></span>
			</nav>';
		}
		
		echo $content;
	}
}