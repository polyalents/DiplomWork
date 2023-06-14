<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

class page_settings {
	static $site_title = '';
	static $page_title = '';
	static $site_logo = '';
	static $favicon = '';
	static $site_descr = '';
	static $site_tags = '';
	static $page_image = '';
	
	function __construct() {
		$this->init_page_settings();
	}
	
	function page_styles() {
		if (SITE_ROUTE['module'] != 'pages') {
			return;
		}
		
		$db = new db();
		
		$page_id = SITE_ROUTE['id'];
		$query = $db->query("SELECT `css_ver` FROM `pages` WHERE `id`=$page_id");
		$row = mysqli_fetch_assoc($query);
		
		$file_path = ROOT_DIR.'/css/pages/'.$page_id.'.css';
		if (file_exists($file_path)) {
			echo '<link rel="stylesheet" href="/css/pages/'.$page_id.'.css?ver='.$row['css_ver'].'">';
		}
	}
	
	private function init_page_settings() {
		$db = new db();
		
		$query = $db->query("SELECT * FROM `settings` WHERE `key` IN('site_title', 'site_logo', 'site_descr', 'site_tags', 'favicon')");
		while($row = mysqli_fetch_assoc($query)) {
			switch($row['key']) {
				case 'site_title':
					self::$site_title = $row['value'];
					break;
				case 'site_logo':
					self::$site_logo = '/uploads/'.$row['value'].'.webp';
					self::$page_image = '/uploads/thumbs/'.$row['value'].'-og.png';
					break;
				case 'site_descr':
					self::$site_descr = $row['value'];
					break;
				case 'site_tags':
					self::$site_tags = $row['value'];
					break;
				case 'favicon':
					self::$favicon = '/uploads/'.$row['value'].'.png';
					break;
			}
		}
	}
	
	public function init_front_page_title() {
		$db = new db();
		switch(SITE_ROUTE['module']) {
			case 'pages':
				if (!SITE_ROUTE['id']) {
					break;
				}
				
				$id = SITE_ROUTE['id'];
				$query = $db->query("SELECT `title` FROM `pages` WHERE `id` = $id");
				$row = mysqli_fetch_assoc($query);
				if ($row) {
					self::$page_title = $row['title'];
				}
				
				break;
			case 'news':
				if (!SITE_ROUTE['id']) {
					break;
				}
				
				$id = SITE_ROUTE['id'];
				$query = $db->query("SELECT `title`, `image` FROM `news` WHERE `id` = $id");
				$row = mysqli_fetch_assoc($query);
				if ($row) {
					self::$page_title = $row['title'];
					
					if ($row['image']) {
						// выводим тумбу, а не полное изображение
						// используем png, потому что в OpenGraph нельзя выводить webp
						self::$page_image = '/uploads/thumbs/'.$row['image'].'-og-news.png';
					}
				}
				
				break;
			case 'error':
				if (SITE_ROUTE['code'] == 'error_404') {
					self::$page_title = 'Страница не существует';
				} else {
					self::$page_title = 'Ошибка';
				}
				break;
		}
	}
	
	function site_html_title() {
		$parts = array();
		if (self::$page_title) {
			$parts[] = self::$page_title;
		}
		if (self::$site_title) {
			$parts[] = self::$site_title;
		}
		
		$parts = implode(' - ', $parts);
		echo $parts;
	}
	
	function site_title() {
		echo htmlentities(self::$site_title);
	}
	
	function page_title() {
		echo htmlentities(self::$page_title);
	}
	
	function site_tags() {
		self::$site_tags = explode(',', self::$site_tags);
		foreach(self::$site_tags as $key => $site_tag) {
			self::$site_tags[$key] = htmlentities($site_tag);
		}
		self::$site_tags = implode(',', self::$site_tags);
		
		echo self::$site_tags;
	}
	
	function site_logo() {
		echo self::$site_logo;
	}
	
	function site_descr() {
		echo htmlentities(self::$site_descr);
	}
	
	function page_image() {
		echo self::$page_image;
	}
	
	function favicon() {
		echo self::$favicon;
	}
}