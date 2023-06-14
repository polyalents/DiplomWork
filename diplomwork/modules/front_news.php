<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

class front_news {
	/**
	 * Вывод одиночной новости на собственной странице
	 * @param $page_id
	 */
	public function render($page_id) {
		$db = new db();
		$dates = new dates();
		
		$query = $db->query("SELECT * FROM `news` WHERE `id`=$page_id");
		$row = mysqli_fetch_assoc($query);
		
		if ($row['image']) {
			$image = '/uploads/thumbs/'.$row['image'].'-news.webp';
		} else {
			$image = '/css/images/placeholder.webp';
		}
		
		echo '
		<section id="news-single-wrap">
			<div class="container">
				<h1>'.$row['title'].'</h1>
				<p class="text-small">'.$dates->out_date($row['date'], false).'</p>
				
				<div class="news-single">
					<div class="image-wrap"><img src="'.$image.'" alt=""></div>
					<article>
						'.$row['text'].'
						<a href="" onclick="history.back()">&larr;&nbsp;Вернуться назад</a>
					</article>
					<div class="clear"></div>
				</div>
			</div>
		</section>
		';
	}
	
	/**
	 * Вывод списка новостей (виджет для страницы)
	 */
	function news_widget($widget) {
		$db = new db();
		$dates = new dates();
		
		$widget['options'] = unserialize($widget['options']);
		if (!$widget['options']) {
			$widget['options']['spoiler'] = 0;
		}
		
		$content = '';
		$n = 0;
		$query = $db->query("SELECT * FROM `news` ORDER BY `date` DESC");
		while ($row = mysqli_fetch_assoc($query)) {
			$n++;
			if ($row['image']) {
				$image = '/uploads/thumbs/'.$row['image'].'-news.webp';
			} else {
				$image = '/css/images/placeholder.webp';
			}
			
			$max_length = 255;
			if (mb_strlen($row['text']) > $max_length) {
				$row['text'] = mb_substr($row['text'], 0, $max_length).'...<br><a href="/news/'.$row['link'].'">Читать далее&nbsp;&rarr;</a>';
			}
			
			$content .= '
			<div class="news-row">
				<div class="title-wrap">
					<h4><a href="/news/'.$row['link'].'">'.$row['title'].'</a></h4>
					<span class="news-date">'.$dates->out_date($row['date'], false).'</span>
				</div>
				<div class="content-wrap">'.$row['text'].'</div>
				<a href="/news/'.$row['link'].'" class="image-wrap"><img class="news-image" src="'.$image.'" alt="'.$row['image'].'"></a>
			</div>
			';
		}
		
		$all_news_btn = '';
		$spoiler_class = '';
		if (($n > 1) and $widget['options']['spoiler']) {
			$spoiler_class = 'under-spoiler';
			$all_news_btn = '
			<div class="align-right">
				<span class="jsNewsSpoiler">
					<span>Все новости</span>
					<i class="fa-solid fa-chevron-down"></i>
				</span>
			</div>';
		}
		
		return '
		<div id="news-wrap" class="'.$spoiler_class.'">'.$content.'</div>'.$all_news_btn;
	}
}