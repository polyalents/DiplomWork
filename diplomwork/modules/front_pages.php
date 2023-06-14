<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

class front_pages {
	public function render($page_id) {
		$db = new db();
		// визуальщина - эти переменные лучше видно, если засунуть их в массив
		$output = array(
			'sections' => '',
			'rows' => '',
			'columns' => '',
			'widgets' => '',
		);
		
		// проходимся по всем секциям
		$query = $db->query("SELECT * FROM `sections` WHERE `page_id`=$page_id ORDER BY `order`");
		while ($section = mysqli_fetch_assoc($query)) {
			// сбрасываем HTML рядов для каждой новой секции
			$output['rows'] = '';
			
			// проходимся по всем рядам в секции
			$query2 = $db->query("SELECT * FROM `rows` WHERE `section_id`=$section[id] ORDER BY `order`");
			while ($row = mysqli_fetch_assoc($query2)) {
				// сбрасываем HTML колонок для каждого нового ряда
				$output['columns'] = '';
				// сбрасываем нумерацию колонки для каждого нового ряда
				$column_numb = 0;
				
				// проходимся по всем колонкам в ряде
				// (у колонок нет таблицы в БД, так как их не надо двигать и удалять отдельно)
				for($i = 0; $i < $row['columns']; $i++) {
					// сбрасываем HTML виджетов для каждой новой колонки
					$output['widgets'] = '';
					// увеличиваем нумерацию колонки
					$column_numb++;
					// ширина колонок зависит от их количества
					$percent = 100 / $row['columns'];
					
					// проходимя по всем виджетам в колонке
					$query3 = $db->query("SELECT * FROM `widgets` WHERE `row_id`=$row[id] AND `column_numb`=$column_numb ORDER BY `order`");
					while ($widget = mysqli_fetch_assoc($query3)) {
						
						switch($widget['type']) {
							default:
								$widget_view = '';
								break;
							case 'text':
								$widget_view = $widget['content'];
								break;
							case 'button':
								$button = unserialize($widget['content']);
								if (!$button) {
									$button = array('text' => 'Кнопка', 'href' => '');
								}
								
								$widget_view = '<div><a class="btn" href="'.$button['href'].'">'.$button['text'].'</a></div>';
								break;
							case 'html':
								$widget_view = '
								<div class="html-wrap">
									'.$widget['content'].'
								</div>';
								break;
							case 'image':
								if ($widget['content']) {
									// достаём настройки fancybox
									$fancybox = false;
									$widget['options'] = unserialize($widget['options']);
									if (is_array($widget['options']) and $widget['options']) {
										if ($widget['options']['fancybox']) {
											$fancybox = true;
										}
									}
									
									// выводим тумбу, а не полное изображение
									$thumb_name = $widget['content'].'-pages.webp';
									$widget_view = '<img class="image" src="/uploads/thumbs/'.$thumb_name.'" alt="'.$thumb_name.'">';
									if ($fancybox) {
										$widget_view = '
										<a href="/uploads/'.$widget['content'].'.webp" data-fancybox>
											'.$widget_view.'
										</a>';
									}
								} else {
									// заглушка
									$widget_view = '<img class="image" src="/css/images/placeholder.webp" alt="">';
								}
								
								break;
							case 'news':
								require_once ROOT_DIR.'/modules/front_news.php';
								$news = new front_news();
								$widget_view = $news->news_widget($widget);
								break;
						}
						
						//  HTML виджета
						$output['widgets'] .= '
						<div class="widget" id="widget-'.$widget['id'].'">
							'.$widget_view.'
						</div>
						';
					}
					
					//  HTML колонки - кидаем внутрь все виджеты
					$output['columns'] .= '
					<div class="col col-'.$column_numb.'">
						'.$output['widgets'].'
					</div>';
				}
				
				//  HTML ряда - кидаем внутрь все колонки
				$output['rows'] .= '
				<div id="row-'.$row['id'].'" class="row columns'.$row['columns'].'">
					'.$output['columns'].'
				</div>';
			}
			
			//  HTML секции - кидаем внутрь все ряды
			$output['sections'] .= '
			<section id="section-'.$section['id'].'">
				<div class="container">'.$output['rows'].'</div>
			</section>';
		}
		
		// высвобождаем память - нам нужны только секции
		$output = $output['sections'];
		echo $output;
	}
}