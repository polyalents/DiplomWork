<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

class admin_header {
	/**
	 * Вывод панели администатора, если пользователь залогинен
	 */
	function render() {
		if (!USER) {
			return;
		}
		
		$edit_button = '';
		if (in_array(SITE_ROUTE['module'], array('pages', 'news')) and SITE_ROUTE['id']) {
			$edit_button = '
			<a href="/admin/?mode='.SITE_ROUTE['module'].'&id='.SITE_ROUTE['id'].'">
				<i class="fa-solid fa-pen"></i>
				<span class="admin-label">Редактировать страницу</span>
			</a>';
		}
		
		echo '
		<header id="admin-header">
			<div id="admin-header-left">
				<a href="/admin">
					<i class="fa-solid fa-gear"></i>
					<span class="admin-label">В администраторский раздел</span>
				</a>
				'.$edit_button.'
			</div>
			<div>
				<span class="admin-label">Добро пожаловать, </span>'.USER['name'].'
				<i class="jsSignOut fa-solid fa-arrow-right-from-bracket"></i>
			</div>
		</header>
		';
	}
	
	/**
	 * Вывод скрипта для выхода, если пользователь залогинен
	 */
	function script() {
		if (!USER) {
			return;
		}
		echo '<script src="/admin/js/sign-out.js"></script>';
	}
}