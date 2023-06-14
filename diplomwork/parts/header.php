<?php
// составные части страницы
$settings = new page_settings();
$settings->init_front_page_title();

$nav = new front_nav();
$admin_header = new admin_header();

?><!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport"
		  content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<link rel="shortcut icon" href="<?php $settings->favicon(); ?>" type="image/png">
	
	<title><?php $settings->site_html_title();?></title>
	<meta name="description" content="<?php $settings->site_descr(); ?>">
	<meta name="keywords" content="<?php $settings->site_tags(); ?>">
	
	<!-- Open Graph -->
	<meta property="og:title" content="<?php $settings->page_title(); ?>">
	<meta property="og:site_name" content="<?php $settings->site_title(); ?>">
	<meta property="og:description" content="<?php $settings->site_descr(); ?>">
	<meta property="og:image" content="<?php $settings->page_image(); ?>">
	
	<link rel="stylesheet" href="/assets/montserrat/montserrat.css" >
	<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css" />
	
	<script src="/assets/jquery.min.js"></script>
	<script src="/assets/fancybox/jquery.fancybox.min.js"></script>
	<link rel="stylesheet" href="/assets/fancybox/jquery.fancybox.min.css" />
	
	<script src="/assets/jquery.inputmask.min.js" ></script>
	
	<script src="/js/scripts.js"></script>
	<?php $admin_header->script(); ?>
	<link rel="stylesheet" href="/css/styles.css">
	<?php $settings->page_styles(); ?>
</head>
<body id="top">
<?php $admin_header->render(); ?>
<header id="main-header">
	<div class="container">
		<a id="site_logo" href="/">
			<img src="<?php $settings->site_logo(); ?>" alt="logo">
		</a>
		<i class="fa-solid fa-bars jsMobileNav hidden"></i>
		<?php $nav->render(); ?>
	</div>
</header>
<main>