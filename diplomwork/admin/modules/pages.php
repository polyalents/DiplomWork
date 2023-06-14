<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

class pages {
	public function render() {
		$modify = new modify();
		
		if (isset($_GET['id']) and $page_id = $modify->only_numbers($_GET['id'])) {
			switch($_GET['action']) {
				default:
					$this->single_page($page_id);
					break;
				case 'settings':
					$this->single_settings($page_id);
					break;
				case 'css':
					$this->single_css($page_id);
					break;
			}
		} else {
			$this->output_list();
		}
	}
	
	private function sub_nav($page_id) {
		$main_link = array(
			'open' => '<a href="/admin/?mode=pages&id='.$page_id.'">',
			'close' => '</a>'
		);
		
		$settings = array(
			'open' => '<a href="/admin/?mode=pages&action=settings&id='.$page_id.'">',
			'close' => '</a>'
		);
		
		$css = array(
			'open' => '<a href="/admin/?mode=pages&action=css&id='.$page_id.'">',
			'close' => '</a>'
		);
		
		switch($_GET['action']) {
			default:
				$main_link = array(
					'open' => '',
					'close' => ''
				);
				break;
			case 'settings':
				$settings = array(
					'open' => '',
					'close' => ''
				);
				break;
			case 'css':
				$css = array(
					'open' => '',
					'close' => ''
				);
				break;
		}
		
		return '
		<ul class="sub-nav">
			<li>'.$main_link['open'].'Редактировать'.$main_link['close'].'</li>
			<li>'.$settings['open'].'Свойства'.$settings['close'].'</li>
			<li>'.$css['open'].'Стили'.$css['close'].'</li>
		</ul>';
	}
	
	private function single_css($page_id) {
		$db = new db();
		$ajax = new ajax();
		
		$query = $db->query("SELECT `title` FROM `pages` WHERE `id`=$page_id");
		$page = mysqli_fetch_assoc($query);
		if (!$page) {
			echo '<div class="error">Нет такой страницы.</div>';
			return;
		}
		
		$file_path = ROOT_DIR.'/css/pages/'.$page_id.'.css';
		if (file_exists($file_path)) {
			$css = file_get_contents($file_path);
		} else {
			$result = file_put_contents($file_path, '');
			if (!$result) {
				$ajax->error('Не удалось записать файл.');
			}
			$css = '';
		}
		
		echo '
		<h1 class="centered jsPageIdHolder" data-id="'.$page_id.'">Страница: '.$page['title'].'</h1>
		'.$this->sub_nav($page_id).'
		<div id="aceEditor">'.$css.'</div>
		<br>
		<div class="centered">
			<a class="typ-btn jsSaveCSS">
				<i class="fa-solid fa-floppy-disk"></i>
				Сохранить
			</a>
		</div>';
	}

	private function single_settings($page_id) {
		$db = new db();
		$dates = new dates();
		
		// достаём свойства страницы
		$query = $db->query("
			SELECT `pages`.*, `admins`.`name` AS `author`
				FROM `pages`
				LEFT JOIN `admins` ON `admins`.`id` = `pages`.`author`
			WHERE `pages`.`id`=$page_id
		");
		$page = mysqli_fetch_assoc($query);
		if (!$page) {
			echo '<div class="error">Нет такой страницы.</div>';
			return;
		}
		
		if ($page['main']) {
			$page['main'] = 'checked';
			$link_disabled = 'disabled';
		} else {
			$page['main'] = '';
			$link_disabled = '';
		}
		
		echo '
		<h1 class="centered jsPageIdHolder" data-id="'.$page_id.'">Страница: '.$page['title'].'</h1>
		'.$this->sub_nav($page_id).'
		<form class="jsSavePage">
		
			<table class="typ-table">
				<tbody>
					<tr>
						<th style="width: 30%">Заголовок</th>
						<td><input name="title" type="text" value="'.$page['title'].'"></td>
					</tr>
					<tr>
						<th>
							Ярлык
							<br><span class="text-small text-gray">Используется для ссылки. Оставьте пустым для авто-заполнения.</span>
						</th>
						<td><input type="text" name="link" value="'.$page['link'].'" '.$link_disabled.' /></td>
					</tr>
					
					<tr>
						<th>Дата публикации</th>
						<td><input type="datetime-local" name="date" value="'.$dates->utc_to_local($page['date']).'" /></td>
					</tr>
					
					<tr>
						<th>Главная страница</th>
						<td><label><input type="checkbox" name="main" '.$page['main'].' /> Да</label></td>
					</tr>
				</tbody>
			</table>
			
			<br>
		
			<div class="centered">
				<button type="submit" name="submit">
					<i class="fa-solid fa-floppy-disk"></i>
					Сохранить
				</button>
			</div>
		</form>';
	}
	
	/**
	 * Вывод списка
	 */
	private function output_list() {
		$db = new db();
		$dates = new dates();
		$content = '';
		
		$query = $db->query("
			SELECT `pages`.*, `admins`.`name` AS `author`
				FROM `pages`
				LEFT JOIN `admins` ON `admins`.`id`=`pages`.`author`
			ORDER BY `pages`.`date` DESC
		");
		while ($row = mysqli_fetch_assoc($query)) {
			if ($row['main']) {
				$row['main'] = '<div class="centered"><i class="fa-solid fa-check"></i></div>';
			} else {
				$row['main'] = '';
			}
			
			$content .= '
			<tr>
				<td>
				<a href="/admin/?mode=pages&id='.$row['id'].'" class="jsLinkHolder">
					'.$row['title'].'
				</a>
				</td>
				<td>'.$dates->out_date($row['date']).'</td>
				<td>'.$row['author'].'</td>
				<td>'.$row['main'].'</td>
				<td style="width: 1%">
					<i class="fa-solid fa-minus jsDeletePage rel pointer rel" data-id="'.$row['id'].'">
						<span class="tip">Удалить страницу</span>
					</i>
				</td>
			</tr>';
		}
		
		if ($content) {
			$content = '
			<h1 class="centered">Страницы</h1>
			<table class="typ-table jsLinkHolderTable">
				<thead>
					<th>Заголовок</th>
					<th>Дата публикации</th>
					<th>Автор</th>
					<th>Главная</th>
					<th style="width: 1%"><i class="fa-solid fa-gears"></i></th>
				</thead>
				<tbody>
					'.$content.'
				</tbody>
			</table>
			<br>
			<div class="centered"><a class="typ-btn jsNewPageBtn" href=""><i class="fa-solid fa-plus"></i> Добавить</a></div>
			';
			echo $content;
		} else {
			echo '<div class="error">Не найдено страниц в базе.</div>';
		}
	}
	
	private function single_page($page_id) {
		$db = new db();
		
		$query = $db->query("SELECT `title` FROM `pages` WHERE `id`=$page_id");
		$page = mysqli_fetch_assoc($query);
		if (!$page) {
			echo '<div class="error">Нет такой страницы.</div>';
			return;
		}
		
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
						$output['widgets'] .= $this->widget_html($widget);
					}
					
					//  HTML колонки - кидаем внутрь все виджеты
					$output['columns'] .= '
					<div class="section-col" style="width:'.$percent.'%" data-numb="'.$column_numb.'">
						<div class="page-tools">
							<span class="page-ids rel">
								<span>#row-'.$row['id'].' .col-'.$column_numb.'</span>
								<span class="tip">CSS-селектор колонки</span>
							</span>
						</div>
						<div class="section-content-dropzone">
							'.$output['widgets'].'
						</div>
						<div class="centered">
							<i class="fa-solid fa-plus jsNewWidget rel pointer">
								<span class="tip">Добавить виджет</span>
							</i>
						</div>
					</div>';
				}
				
				//  HTML ряда - кидаем внутрь все колонки
				$output['rows'] .= '
				<div class="section-row" data-id="'.$row['id'].'">
					<div class="page-tools">
						<span class="page-ids rel">
							<span>#row-'.$row['id'].'</span>
							<span class="tip">CSS-селектор ряда</span>
						</span>
						<div class="page-tools-inner">
							<i class="fa-solid fa-minus jsDeleteRow rel pointer"><span class="tip">Удалить ряд</span></i>
							<i class="fa-solid fa-up-down-left-right jsRowHandle rel grab"><span class="tip">Переместить</span></i>
						</div>
					</div>
					<div class="section-row-content">
						'.$output['columns'].'
					</div>
				</div>';
			}
			
			//  HTML секции - кидаем внутрь все ряды
			$output['sections'] .= '
			<section class="page-row" data-id="'.$section['id'].'">
				<div class="page-tools">
					<span class="page-ids rel">
						<span>#section-'.$section['id'].'</span>
						<span class="tip">CSS-селектор секции</span>
					</span>
					<div class="page-tools-inner">
						<i class="fa-solid fa-minus jsDeleteSection rel pointer"><span class="tip">Удалить секцию</span></i>
						<i class="fa-solid fa-up-down-left-right jsSectionHandle rel grab"><span class="tip">Переместить</span></i>
					</div>
				</div>
				<div class="section-content">
					'.$output['rows'].'
					<div class="add-row-wrap">
						<i class="fa-solid fa-plus jsNewRow rel pointer"><span class="tip">Добавить ряд</span></i>
					</div>
				</div>
			</section>';
		}
		
		// высвобождаем память - нам нужны только секции
		$output = $output['sections'];
		
		if (!$output) {
			$output = '<div class="error">На странице нет секций.</div>';
		}
		
		echo '
		<h1 class="centered jsPageIdHolder" data-id="'.$page_id.'">Страница: '.$page['title'].'</h1>
		'.$this->sub_nav($page_id).'
		<div class="page-section-wrap">
			'.$output.'
		</div>
		<div class="centered"><i class="fa-solid fa-plus jsNewSection rel pointer"><span class="tip">Добавить секцию</span></i></div>';
	}
	
	/**
	 * HTML виджетов вынесен в отдельный метод, чтобы также обращаться к нему из AJAX
	 * (виджеты добавляются без перезагрузки страницы, чтобы можно было сразу открывать их редактирование)
	 * @param $widget - сюда может приходить либо массив, либо id виджета
	 * @return string
	 */
	private function widget_html($widget) {
		$ajax = new ajax();
		
		// если в переменную $widget приходит id, то вытаскиваем из базы данные виджета как массив
		if (is_numeric($widget) or is_string($widget)) {
			$modify = new modify();
			$db = new db();
			
			$widget = $modify->only_numbers($widget);
			if (!$widget) {
				$ajax->error('Не получен id виджета.');
			}
			
			$query = $db->query("SELECT * FROM `widgets` WHERE `id`=$widget");
			$widget = mysqli_fetch_assoc($query);
		}
		
		// разные виджеты выводятся по разному
		switch($widget['type']) {
			default:
				$ajax->error('Неопознанный тип виджета.');
				die;
			case 'text':
				$widget_preview = $widget['content'];
				break;
			case 'button':
				$button = unserialize($widget['content']);
				if (!$button) {
					$button = array('text' => 'Кнопка', 'href' => '');
				}
				
				$widget_preview = '<div><a class="jsPreviewBtn typ-btn" href="'.$button['href'].'">'.$button['text'].'</a></div>';
				break;
			case 'html':
				$widget_preview = '
				<div class="html-wrap">
					'.$widget['content'].'
				</div>';
				break;
			case 'image':
				if ($widget['content']) {
					// выводим тумбу, а не полное изображение
					$widget['content'] = '/uploads/thumbs/'.$widget['content'].'-pages.webp';
				} else {
					// заглушка
					$widget['content'] = '/css/images/placeholder.webp';
				}
				
				$widget_preview = '<div><img class="image" src="'.$widget['content'].'" alt=""></div>';
				break;
			case 'news':
				$widget_preview = '<div class="centered">Здесь будут выводиться новости</div>';
				break;
		}
		
		return '
		<div class="widget-content" data-widget-id="'.$widget['id'].'">
			<div class="page-tools">
				<span class="page-ids rel">
					<span>#widget-'.$widget['id'].'</span>
					<span class="tip">CSS-селектор виджета</span>
				</span>
				<div class="page-tools-inner">
					<i class="fa-regular fa-pen-to-square jsEditWidget rel pointer"><span class="tip">Редактировать</span></i>
					<i class="fa-solid fa-minus jsDeleteWidget rel pointer"><span class="tip">Удалить виджет</span></i>
					<i class="fa-solid fa-up-down-left-right jsWidgetHandle rel grab"><span class="tip">Переместить</span></i>
				</div>
			</div>
			'.$widget_preview.'
		</div>
		';
	}
	
	/**
	 * содержимое модального окна при редактировании виджета
	 * @return array
	 */
	function ajax_edit_widget_form() {
		$modify = new modify();
		$db = new db();
		$ajax = new ajax();

		$widget_id = $modify->only_numbers($_POST['widget_id']);
		if (!$widget_id) {
			$ajax->error('Не получен id элемента.');
		}

		// вытаскиваем старое изображение
		$query = $db->query("SELECT * FROM `widgets` WHERE `id`=$widget_id");
		$row = mysqli_fetch_assoc($query);
		if (!$row) {
			$ajax->error('Виджет не найден в базе.');
		}

		switch($row['type']) {
			default:
				$ajax->error('Неопознанный тип виджета.');
				die;
			case 'text':
				$widget_edit = '<textarea class="editor-preload" name="content">'.htmlentities($row['content']).'</textarea>';
				break;
			case 'button':
				$button = unserialize($row['content']);
				if (!$button) {
					$button = array('text' => '', 'href' => '');
				}
				
				$widget_edit = '
				<div class="nav-item">
					<label>
						<span class="text-small">Текст:</span><br>
						<input type="text" name="name" value="'.htmlentities($button['text']).'">
					</label>
					<label>
						<span class="text-small">Ссылка:</span><br>
						<input type="text" name="href" value="'.htmlentities($button['href']).'"></a>
					</label>
				</div>';
				break;
			case 'html':
				$widget_edit = '<textarea class="jsCodeContainer" name="content" rows="7">'.htmlentities($row['content']).'</textarea>';
				break;
			case 'image':
				if ($row['content']) {
					// выводим тумбу, а не полное изображение
					$row['content'] = '/uploads/thumbs/'.$row['content'].'-dashboard.webp';
				} else {
					// заглушка
					$row['content'] = '/css/images/placeholder.webp';
				}
				
				$options = unserialize($row['options']);
				if (!$options) {
					$options = array();
				}
				// настройки по-умолчанию
				if (!isset($options['width'])) {
					$options['width'] = 550;
				}
				if (!isset($options['height'])) {
					$options['height'] = 0;
				}
				if (!isset($options['crop'])) {
					$options['crop'] = 0;
				}
				if (!isset($options['fancybox'])) {
					$options['fancybox'] = 1;
				}
				
				// выставляем в HTML выбранные настройки
				if ($options['crop']) {
					$crop[0] = '';
					$crop[1] = 'checked';
				} else {
					$crop[0] = 'checked';
					$crop[1] = '';
				}
				
				if ($options['fancybox']) {
					$fancybox = 'checked';
				} else {
					$fancybox = '';
				}

				$widget_edit = '
				<div>
					<div class="flex-50">
						<label class="thumb-wrap">
							<img src="'.$row['content'].'" alt="">
						</label>
						<label>
							<p><b>Заменить изображение:</b></p>
							<input name="image" type="file">
						</label>
					</div>
					<br>
					<hr>
					<br>
					
					<p><b>Размер миниатюры:</b></p>
					<div class="flex-50">
						<label>
							<span>Ширина миниатюры (0 = авто):</span>
							<input name="width" type="text" value="'.$options['width'].'">
						</label>
						<label>
							<span>Высота миниатюры (0 = авто):</span>
							<input name="height" type="text" value="'.$options['height'].'">
						</label>
					</div>
					<br>
					<hr>
					<br>
					
					<div>
						<p><b>Режим создания миниатюры:</b></p>
						<label style="margin-right: 20px;">
							<input name="crop" type="radio" value="0" '.$crop[0].'>
							<span>Втиснуть в размеры</span>
						</label>
						<label>
							<input name="crop" type="radio" value="1" '.$crop[1].'>
							<span>Обрезать под размеры</span>
						</label>
					</div>
					<br>
					<hr>
					<br>
					
					<div>
						<label style="margin-right: 20px;">
							<input name="fancybox" type="checkbox" '.$fancybox.'>
							<span>Открывать полную версию при клике</span>
						</label>
					</div>
					<br>
					<hr>
				</div>';

				break;
			case 'news':
				
				$row['options'] = unserialize($row['options']);
				if (!$row['options']) {
					$row['options']['spoiler'] = 0;
				}
				
				if ($row['options']['spoiler']) {
					$options = array(0 => '', 1 => 'checked');
				} else {
					$options = array(0 => 'checked', 1 => '');
				}
				
				$widget_edit = '
				<div>
					<h4>Выберите режим отображения новостей:</h4>
					<label>
						<input type="radio" name="spoiler" value="0" '.$options[0].'>
						<span>Выводить все новости</span>
					</label>
					<br>
					<label>
						<input type="radio" name="spoiler" value="1" '.$options[1].'>
						<span>Спрятать под спойлер все, кроме первой</span>
					</label>
				</div>';
				break;
		}

		return array('error' => 0, 'html' => '
		<form action="" enctype="multipart/form-data" method="post" class="jsEditWidgetSubmit">
			<input type="hidden" name="widget_id" value="'.$row['id'].'">
			'.$widget_edit.'
			<br>
			<div class="centered">
				<button type="submit" name="submit">
					<i class="fa-solid fa-floppy-disk"></i>
					Сохранить
				</button>
			</div>
		</form>
		');

	}

	/**
	 * содержимое модального окна при добавлении виджета
	 * @return array
	 */
	function ajax_add_widget_form() {
		$db = new db();
		$ajax = new ajax();
		$modify = new modify();
		
		$page_id = $modify->only_numbers($_POST['page_id']);
		if (!$page_id) {
			$ajax->error('Не получен ID страницы.');
		}
		
		// разрешаем выводить только один виджет новостей
		$query = $db->query("
			SELECT `widgets`.`id`
				FROM `widgets`
				JOIN `rows` on `widgets`.`row_id` = `rows`.`id`
				JOIN `sections` on `sections`.`id` = `rows`.`section_id`
				JOIN `pages` on `sections`.`page_id` = `pages`.`id`
			WHERE `widgets`.`type`='news' AND `pages`.`id`=$page_id
			LIMIT 1
		");
		$row = mysqli_fetch_assoc($query);
		if ($row) {
			$news_option = '';
		} else {
			$news_option = '<option value="news">Новости</option>';
		}
		
		return array('error' => 0, 'html' => '
		<form class="jsNewWidgetSubmit">
			<h4>Выберите тип виджета</h4>
			<select name="widget_type">
				<option value="text">Текст</option>
				<option value="button">Кнопка</option>
				<option value="image">Изображение</option>
				<option value="html">HTML</option>
				'.$news_option.'
			</select>
			<br>
			<div class="centered"><button type="submit" class="typ-btn">Выбрать</button></div>
		</form>
		');
	}
	
	/**
	 * содержимое модального окна при добавлении нового ряда
	 * @return array
	 */
	function ajax_add_row_form() {
		$modify = new modify();
		$ajax = new ajax();
		
		// передаём id секции
		$section_id = $modify->only_numbers($_POST['section_id']);
		if (!$section_id) {
			$ajax->error('Не получен id секции');
		}
		$input = '<input type="hidden" name="section_id" value="'.$section_id.'">';
		
		// возвращаем ту же самую вёрстку, что при добавлении секции,
		// только другой класс на кнопке, чтобы привязать на него другой обработчик в JS
		return $this->ajax_add_section_form('jsNewRowSubmit', $input);
	}

	/**
	 * содержимое модального окна при добавлении новой секции
	 * @return array
	 */
	function ajax_add_section_form($submit_class='jsNewSectionSubmit', $more_inputs='') {
		// держим форму здесь, а не в JS, чтобы кэш браузера не делал мозг, если мы захотим внести сюда изменения
		return array('error' => 0, 'html' => '
		<h4>Выберите количество колонок</h4>
		
		<form class="'.$submit_class.'">
			'.$more_inputs.'
			<div class="centered">
				<label for="sections1">
					<input type="radio" id="sections1" name="columns" value="1">
					1
				</label>
				
				<label for="sections2">
					<input type="radio" id="sections2" name="columns" value="2" checked>
					2
				</label>
				
				<label for="sections3">
					<input type="radio" id="sections3" name="columns" value="3">
					3
				</label>
				
				<label for="sections4">
					<input type="radio" id="sections4" name="columns" value="4">
					4
				</label>
			</div>
			<br>
			<div class="centered"><button type="submit">Выбрать</button></div>
		</form>
		');
	}
	
	/**
	 * Сохранение виджета (обработчик формы)
	 * @return int[]
	 */
	function ajax_save_widget() {
		$modify = new modify();
		$db = new db();
		$files = new files();
		$ajax = new ajax();

		$widget_id = $modify->only_numbers($_POST['widget_id']);
		if (!$widget_id) {
			$ajax->error('Не получен id элемента.');
		}

		// вытаскиваем старое изображение
		$query = $db->query("SELECT * FROM `widgets` WHERE `id`=$widget_id");
		$row = mysqli_fetch_assoc($query);
		if (!$row) {
			$ajax->error('Виджет не найден в базе.');
		}

		switch($row['type']) {
			default:
				$ajax->error('Неопознанный тип виджета.');
				break;
			case 'text':
			case 'html':
				$text = $db->prepare_str($_POST['content'], true);
				$db->query("UPDATE `widgets` SET `content`='$text' WHERE `id`=$widget_id");
				break;
			case 'button':
				$text = $db->prepare_str($_POST['name'], true);
				if (!$text) {
					$ajax->error('Текст кнопки не может быть пустым');
				}
				$href = $db->prepare_str($_POST['href'], true);
				
				$text = array('text' => $text, 'href' => $href);
				$text = serialize($text);
				
				$db->query("UPDATE `widgets` SET `content`='$text' WHERE `id`=$widget_id");
				break;
			case 'image':
				// проверяем новые настройки
				if (isset($_POST['crop']) and $_POST['crop']) {
					$_POST['crop'] = 1;
				} else {
					$_POST['crop'] = 0;
				}
				if (isset($_POST['fancybox'])) {
					$_POST['fancybox'] = 1;
				} else {
					$_POST['fancybox'] = 0;
				}
				
				$_POST['width'] = trim($_POST['width']);
				$_POST['height'] = trim($_POST['height']);
				
				$options = array(
					'width' => $_POST['width'],
					'height' => $_POST['height'],
					'crop' => $_POST['crop'],
					'fancybox' => $_POST['fancybox'],
				);
				
				// выбрано новое изображение
				if (isset($_FILES['image']['name']) and $_FILES['image']['name']) {
					if ($row['content']) {
						// удаляем старое, если файл существует
						$files->delete_image($row['content']);
						// удаляем из базы
						$db->query("UPDATE `widgets` SET `content`='', `options`='' WHERE `id`=$widget_id");
					}
	
					// загружаем изображение
					$thumbs = array(
						'pages' => array (
							'width' => $_POST['width'],
							'height' => $_POST['height'],
							'crop' => $_POST['crop']
						)
					);
				
					$image = $files->upload_image('image', $thumbs);
					if ($image['error']) {
						$ajax->error($image['message']);
					}
				} else {
					// нет нового изображения - проверяем есть ли старое
					if (!$row['content']) {
						$ajax->error('#2: Вы не выбрали изображение для загрузки.');
					}
					// передаём ниже имя старого изображения
					$image = array('image_name' => $row['content']);
					
					// достаём настройки старого изображения
					$old_options = unserialize($row['options']);
					
					// если нет старых настроек, то заполняем их на любые, которые не совпадут с новыми
					if (!is_array($old_options) or !$old_options) {
						$old_options = array(
							'width' => 'нет значения',
							'height' => 'нет значения',
							'crop' => 'нет значения',
						);
					}
					
					// проверяем отличаются ли новые настройки от старых
					if ($options['width'] != $old_options['width']
					or $options['height'] != $old_options['height']
					or $options['crop'] != $old_options['crop']) {
						// перенарезаем миниатюру, если настройки отличаются
						$files->create_thumb(
							ROOT_DIR.'/uploads/'.$image['image_name'],
							$image['image_name'],
							$options['width'],
							$options['height'],
							$options['crop'],
							'-pages'
						);
					}
				}

				// пишем в базу название файла и его настройки
				$image['image_name'] = $db->prepare_str($image['image_name']);
				$options = serialize($options);
				
				$db->query("
				UPDATE `widgets` SET
					`content`='$image[image_name]',
					`options`='$options'
				WHERE `id`=$widget_id");
				break;
			case 'news':
				if ($_POST['spoiler']) {
					$options = array('spoiler' => 1);
				} else {
					$options = array('spoiler' => 0);
				}
				
				$options = serialize($options);
				
				$db->query("
				UPDATE `widgets` SET
					`options`='$options'
				WHERE `id`=$widget_id");
				break;

		}

		return array('error' => 0);
	}
	
	/**
	 * Удаление виджета (обработчик)
	 * @param string $widget_id
	 * @return int[]
	 */
	function ajax_widget_delete($widget_id='') {
		$modify = new modify();
		$db = new db();
		$ajax = new ajax();
		$files = new files();

		if (!$widget_id) {
			$widget_id = $modify->only_numbers($_POST['widget_id']);
		}
		if (!$widget_id) {
			$ajax->error('Не получен id виджета.');
		}
		
		$query = $db->query("SELECT `content`, `type` FROM `widgets` WHERE `id`=$widget_id");
		$row = mysqli_fetch_assoc($query);
		if (!$row) {
			$ajax->error('Виджет не найден в базе.');
		}
		
		if ($row['type'] == 'image' and $row['content']) {
			$files->delete_image($row['content']);
		}

		$db->query("DELETE FROM `widgets` WHERE `id`=$widget_id");

		return array('error' => 0);
	}
	
	/**
	 * Добавление виджета (обработчик формы)
	 * @return array
	 */
	function ajax_widget_add() {
		$modify = new modify();
		$db = new db();
		$ajax = new ajax();
		
		$options = '';
		
		$page_id = $modify->only_numbers($_POST['page_id']);
		if (!$page_id) {
			$ajax->error('Не получен id страницы');
		}

		$row_id = $modify->only_numbers($_POST['row_id']);
		if (!$row_id) {
			$ajax->error('Не получен id ряда.');
		}

		$col_numb = $modify->only_numbers($_POST['col_numb']);
		if (!$col_numb) {
			$ajax->error('Не получен номер колонки.');
		}

		$widget_type = $_POST['widget_type'];
		if (!in_array($widget_type, array('text', 'button', 'image', 'news', 'html'))) {
			$ajax->error('Выбран недопустимый тип виджета.');
		}
		
		// разрешаем выводить только один виджет новостей
		if ($_POST['widget_type'] == 'news') {
			$query = $db->query("
				SELECT `widgets`.`id`
					FROM `widgets`
					JOIN `rows` on `widgets`.`row_id` = `rows`.`id`
					JOIN `sections` on `sections`.`id` = `rows`.`section_id`
					JOIN `pages` on `sections`.`page_id` = `pages`.`id`
				WHERE `widgets`.`type`='news' AND `pages`.`id`=$page_id
				LIMIT 1
			");
			$row = mysqli_fetch_assoc($query);
			if ($row) {
				$ajax->error('На странице уже есть виджет новостей.');
			}
			
			$options = array('spoiler' => 0);
			$options = serialize($options);
		}

		// если в этой колонке уже есть виджеты, находим порядок последнего
		$order = 0;
		$query = $db->query("
			SELECT `order` FROM `widgets`
			WHERE `row_id`=$row_id AND `column_numb`=$col_numb
			ORDER BY `order` DESC LIMIT 1
		");
		$row = mysqli_fetch_assoc($query);
		if ($row) {
			$order = $row['order'] + 1;
		}

		$db->query("
		INSERT INTO `widgets` SET
			`row_id`=$row_id,
			`column_numb`=$col_numb,
			`type`='$widget_type',
			`content`='',
		    `options`='$options',
			`order`=$order
		");
		
		$widget_id = $db->get_insert_id();
		$widget_html = $this->widget_html($widget_id);

		return array('error' => 0, 'widget_html' => $widget_html, 'widget_id' => $widget_id, 'row_id' => $row_id, 'column_numb' => $col_numb);
	}
	
	/**
	 * Удаление секции (обработчик)
	 * @return int[]
	 */
	function ajax_section_delete() {
		$modify = new modify();
		$db = new db();
		$ajax = new ajax();

		$section_id = $modify->only_numbers($_POST['section_id']);
		if (!$section_id) {
			$ajax->error('Не получен id секции.');
		}
		
		$row_ids = array();
		$query = $db->query("SELECT `id` FROM `rows` WHERE `section_id`=$section_id");
		while ($row = mysqli_fetch_assoc($query)) {
			$row_ids[] = $row['id'];
			
			$query2 = $db->query("SELECT `id` FROM `widgets` WHERE `type`='image' AND `row_id`=$row[id]");
			while ($widget = mysqli_fetch_assoc($query2)) {
				// удаляем каждый виджет с картинкой отдельно через этот метод, потому что он также удаляет файлы
				$this->ajax_widget_delete($widget['id']);
			}
		}
		
		$row_ids = implode(',', $row_ids);
		if ($row_ids) {
			// удаляем остальные виджеты, без картинок
			$db->query("DELETE FROM `widgets` WHERE `row_id` IN($row_ids)");
		}
		// удаляем ряды
		$db->query("DELETE FROM `rows` WHERE `section_id`=$section_id");
		// удаляем саму секцию
		$db->query("DELETE FROM `sections` WHERE `id`=$section_id");

		return array('error' => 0);
	}
	
	/**
	 * Добавление секции (обработчик формы)
	 * @return int[]
	 */
	function ajax_section_add() {
		$modify = new modify();
		$db = new db();
		$ajax = new ajax();
		
		$page_id = $modify->only_numbers($_POST['page_id']);
		if (!$page_id) {
			$ajax->error('Не получен ID страницы.');
		}

		$columns = $modify->only_numbers($_POST['columns']);
		if (!$columns or $columns < 1 or $columns > 4) {
			$ajax->error('Нужно ввести цифру от 1 до 4.');
		}
		
		// находим последний порядковый номер и увеличиваем на 1
		$order = 0;
		$query = $db->query("SELECT `order` FROM `sections` WHERE `page_id`=$page_id ORDER BY `order` DESC LIMIT 1");
		$row = mysqli_fetch_assoc($query);
		if ($row) {
			$order = $row['order'] + 1;
		}

		$db->query("INSERT INTO `sections` SET `order`=$order, `page_id`=$page_id");
		$section_id = $db->get_insert_id();
		$db->query("INSERT INTO `rows` SET `columns`=$columns, `section_id`=$section_id, `order`=0");

		return array('error' => 0);
	}
	
	/**
	 * Удаление ряда (обработчик)
	 * @return int[]
	 */
	function ajax_row_delete() {
		$modify = new modify();
		$db = new db();
		$ajax = new ajax();

		$row_id = $modify->only_numbers($_POST['row_id']);
		if (!$row_id) {
			$ajax->error('Не получен id ряда.');
		}
		
		$query2 = $db->query("SELECT `id` FROM `widgets` WHERE `type`='image' AND `row_id`=$row_id");
		while ($widget = mysqli_fetch_assoc($query2)) {
			// удаляем каждый виджет с картинкой отдельно через этот метод, потому что он также удаляет файлы
			$this->ajax_widget_delete($widget['id']);
		}
		
		// удаляем остальные виджеты, без картинок
		$db->query("DELETE FROM `widgets` WHERE `row_id`=$row_id");
		// удаляем сам ряд
		$db->query("DELETE FROM `rows` WHERE `id`=$row_id");

		return array('error' => 0);
	}
	
	/**
	 * Добавление ряда (обработчик)
	 * @return int[]
	 */
	function ajax_plus_row() {
		$modify = new modify();
		$db = new db();
		$ajax = new ajax();

		$section_id = $modify->only_numbers($_POST['section_id']);
		if (!$section_id) {
			$ajax->error('Не получен id секции.');
		}
		
		$columns = $modify->only_numbers($_POST['columns']);
		if (!$columns) {
			$ajax->error('Не получено количество колонок.');
		}

		// находим последний порядковый номер и увеличиваем на 1
		$order = 0;
		$query = $db->query("SELECT `order` FROM `rows` WHERE `section_id`='$section_id' ORDER BY `order` DESC LIMIT 1");
		$row = mysqli_fetch_assoc($query);
		if ($row) {
			$order = $row['order'];
			$order++;
		}

		$db->query("INSERT INTO `rows` SET `columns`=$columns, `section_id`=$section_id, `order`=$order");

		return array('error' => 0);
	}

	/**
	 * сортировка виджетов
	 */
	function ajax_widgets_sort() {
		$modify = new modify();
		$db = new db();
		$ajax = new ajax();

		$row_id = $modify->only_numbers($_POST['row_id']);
		if (!$row_id) {
			$ajax->error('Не получен id ряда.');
		}

		$column_numb = $modify->only_numbers($_POST['column_numb']);
		if (!$column_numb) {
			$ajax->error('Не получен номер колонки.');
		}

		// удаляем первый "-" вначале
		$items = substr($_POST['order'], 1);

		// разбиваем строку на массив
		$items = explode('-', $items);
		if (!is_array($items)) {
			$ajax->error('Не получен массив значений.');
		}

		// переписываем order для всех итемов, увеличивая на 1 в новом порядке
		$order = 0;
		foreach ($items as $widget_id) {
			$widget_id = $modify->only_numbers($widget_id);
			if (!$widget_id) {
				$ajax->error('Не получен id виджета.');
			}
			$db->query("
			UPDATE `widgets` SET
				`order`=$order,
				`column_numb`=$column_numb,
				`row_id`=$row_id
			WHERE `id`=$widget_id");
			$order++;
		}

		return array('error' => 0);
	}

	/**
	 * сортировка секций
	 */
	function ajax_sections_sort() {
		$modify = new modify();
		$db = new db();
		$ajax = new ajax();

		// удаляем первый "-" вначале
		$items = substr($_POST['order'], 1);

		// разбиваем строку на массив
		$items = explode('-', $items);
		if (!is_array($items)) {
			$ajax->error('Не получен массив значений.');
		}

		// переписываем order для всех итемов, увеличивая на 1 в новом порядке
		$order = 0;
		foreach ($items as $section_id) {
			$section_id = $modify->only_numbers($section_id);
			if (!$section_id) {
				$ajax->error('Не получен id секции.');
			}
			$db->query("UPDATE `sections` SET `order`=$order WHERE `id`=$section_id");
			$order++;
		}

		return array('error' => 0);
	}
	
	/**
	 * сортировка рядов
	 */
	function ajax_rows_sort() {
		$modify = new modify();
		$db = new db();
		$ajax = new ajax();
		
		$section_id = $modify->only_numbers($_POST['section_id']);
		if (!$section_id) {
			$ajax->error('Не получен id секции.');
		}
		
		// удаляем первый "-" вначале
		$items = substr($_POST['order'], 1);
		
		// разбиваем строку на массив
		$items = explode('-', $items);
		if (!is_array($items)) {
			$ajax->error('Не получен массив значений.');
		}
		
		// переписываем order для всех итемов, увеличивая на 1 в новом порядке
		$order = 0;
		foreach ($items as $row_id) {
			$row_id = $modify->only_numbers($row_id);
			if (!$row_id) {
				$ajax->error('Не получен id ряда.');
			}
			$db->query("UPDATE `rows` SET `order`=$order, `section_id`=$section_id WHERE `id`=$row_id");
			$order++;
		}
		
		return array('error' => 0);
	}
	
	/**
	 * Сохранение свойств страницы
	 * @return int[]
	 */
	function ajax_save_page() {
		$modify = new modify();
		$ajax = new ajax();
		$dates = new dates();
		$db = new db();
		$data = $_POST;
		
		$page_id = $modify->only_numbers($data['page_id']);
		if (!$page_id) {
			$ajax->error('Не получен ID страницы.');
		}
		
		if (isset($data['main']) and $data['main'] == 'on') {
			$data['main'] = 1;
			$link_sql = "";
		} else {
			$data['main'] = 0;
			
			if (!trim($data['link'])) {
				$data['link'] = $modify->cyr_to_lat($data['title']);
			}
			
			if (!trim($data['link'])) {
				$ajax->error('Вы должны ввести ярлык или заголовок');
			}
			
			$data['link'] = $modify->cyr_to_lat($data['link']);
			if (!$data['link']) {
				$ajax->error('Ярлык может содержать только латинские буквы, цифры и символ "-"');
			}
			
			if ($data['link'] == 'admin') {
				$ajax->error('Ярлык "'.$data['link'].'" не разрешён.');
			}
			
			$data['link'] = $db->prepare_str($data['link']);
			
			$query = $db->query("SELECT `id` FROM `pages` WHERE `link`='$data[link]' AND `id` != $page_id");
			$row = mysqli_fetch_assoc($query);
			if ($row) {
				$ajax->error('Этот ярлык уже используется на другой странице');
			}
			
			$link_sql = ", `link`='$data[link]'";
		}
		
		$data['date'] = $dates->local_to_utc($data['date']);
		$data['title'] = $db->prepare_str($data['title']);
		
		$db->query("
		UPDATE `pages` SET
			`title`='$data[title]',
			`date`='$data[date]',
			`main`=$data[main]
			$link_sql
		WHERE `id`=$page_id
		");
		
		// может быть только одна главная страница
		if ($data['main']) {
			$db->query("
			UPDATE `pages` SET
				`main`=0
			WHERE `main`=1 AND `id` != $page_id
			");
		}
		
		return array('error' => 0);
	}
	
	/**
	 * Создание новой страницы (форма)
	 * @return int[]
	 */
	function ajax_new_page() {
		$html = '
		<form class="jsNewPage">
			<label>
				<span>Введите название страницы:</span>
				<input type="text" name="title" />
			</label>
			<br>
			
			<div class="centered">
				<button type="submit" name="submit">
					<i class="fa-solid fa-floppy-disk"></i>
					Сохранить
				</button>
			</div>
		</form>
		';
		
		return array('error' => 0, 'html' => $html);
	}
	
	/**
	 * Создание новой страницы (обработчик формы)
	 * @return int[]
	 */
	function ajax_new_page_submit() {
		$modify = new modify();
		$ajax = new ajax();
		$db = new db();
		
		$data = array();
		
		$data['title'] = $db->prepare_str($_POST['title']);
		if (!$data['title']) {
			$ajax->error('Вы не ввели название страницы.');
		}
		
		// пишем дату по Гринвичу
		$data['date'] = gmdate('Y-m-d H:i:s');
		
		$data['link'] = $modify->cyr_to_lat($data['title']);
		$data['author'] = USER['id'];
		
		$db->query("
		INSERT INTO `pages` SET
			`title`='$data[title]',
			`author`='$data[author]',
			`date`='$data[date]',
			`main`=0,
			`link`='$data[link]'
		");
		
		$page_id = $db->get_insert_id();
		if (!$page_id) {
			$ajax->error('Не получен ID новой страницы.');
		}
		
		// сразу вставляем одну секцию
		$db->query("
		INSERT INTO `sections` SET
			`page_id`=$page_id,
			`order`=0
		");
		
		$section_id = $db->get_insert_id();
		if (!$section_id) {
			$ajax->error('Не получен ID новой секции.');
		}
		
		return array('error' => 0);
	}
	
	/**
	 * Удаление страницы
	 * @return int[]
	 */
	function ajax_delete_page() {
		$modify = new modify();
		$ajax = new ajax();
		$db = new db();
		
		$page_id = $modify->only_numbers($_POST['id']);
		if (!$page_id) {
			$ajax->error('Не получен ID страницы.');
		}
		
		$row_ids = array();
		$section_ids = array();
		
		// достаём секции
		$query = $db->query("SELECT `id` FROM `sections` WHERE `page_id`=$page_id");
		while ($section = mysqli_fetch_assoc($query)) {
			$section_ids[] = $section['id'];
			
			// достаём ряды
			$query2 = $db->query("SELECT `id` FROM `rows` WHERE `section_id`=$section[id]");
			while($row = mysqli_fetch_assoc($query2)) {
				$row_ids[] = $row['id'];
				
				// достаём виджеты
				$query3 = $db->query("SELECT `id` FROM `widgets` WHERE `type`='image' AND `row_id`=$row[id]");
				while($widget = mysqli_fetch_assoc($query3)) {
					// удаляем каждый виджет с картинкой отдельно через этот метод, потому что он также удаляет файлы
					$this->ajax_widget_delete($widget['id']);
				}
			}
		}
		
		$row_ids = implode(',', $row_ids);
		if ($row_ids) {
			// удаляем остальные виджеты, без картинок
			$db->query("DELETE FROM `widgets` WHERE `row_id` IN($row_ids)");
		}
		$section_ids = implode(',', $section_ids);
		if ($section_ids) {
			// удаляем ряды
			$db->query("DELETE FROM `rows` WHERE `section_id` IN($section_ids)");
		}
		// удаляем секции
		$db->query("DELETE FROM `sections` WHERE `page_id`=$page_id");
		
		// удаляем саму страницу
		$db->query("DELETE FROM `pages` WHERE `id`=$page_id");
		
		// удаляем стили страницы
		$file_path = ROOT_DIR.'/css/pages/'.$page_id.'.css';
		if (file_exists($file_path)) {
			unlink($file_path);
		}
		
		return array('error' => 0);
	}
	
	function ajax_save_css() {
		$modify = new modify();
		$ajax = new ajax();
		$db = new db();
		
		$page_id = $modify->only_numbers($_POST['page_id']);
		if (!$page_id) {
			$ajax->error('Не получен ID страницы.');
		}
		
		$query = $db->query("SELECT `css_ver` FROM `pages` WHERE `id`=$page_id");
		$row = mysqli_fetch_assoc($query);
		if (!$row) {
			$ajax->error('Страница не найдена в базе.');
		}
		
		$result = file_put_contents(ROOT_DIR.'/css/pages/'.$page_id.'.css', $_POST['css']);
		if (!$result) {
			$ajax->error('Не удалось записать файл.');
		}
		
		$row['css_ver']++;
		$db->query("UPDATE `pages` SET `css_ver`=$row[css_ver] WHERE `id`=$page_id");
		
		return array('error' => 0);
	}
}