<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

class news {
	public function render() {
		// вывод страниц
		$modify = new modify();
		if (isset($_GET['id']) and $news_id = $modify->only_numbers($_GET['id'])) {
			// редактирование новости
			$this->add_edit($news_id);
		} elseif (isset($_GET['action']) and $_GET['action'] == 'new') {
			// добавление новой
			$this->add_edit();
		} else {
			// вывод списка
			$this->output_list();
		}
	}

	/**
	 * Вывод списка новостей
	 */
	private function output_list() {
		$db = new db();
		$modify = new modify();
		$dates = new dates();
		$content = '';

		$query = $db->query("
			SELECT `news`.`id`, `news`.`date`, `news`.`title`, `admins`.`name` AS `author`
			FROM `news`
			LEFT JOIN `admins` ON `admins`.`id` = `news`.`author`
			ORDER BY `news`.`date` DESC
		");
		while ($row = mysqli_fetch_assoc($query)) {
			$id = $modify->only_numbers($row['id']);
			$content .= '
			<tr data-id="'.$id.'">
				<td>
					<a href="/admin/?mode=news&id='.$id.'" class="jsLinkHolder">
						'.$row['title'].'
					</a>
				</td>
				<td>'.$dates->out_date($row['date']).'</td>
				<td>'.$row['author'].'</td>
				<td style="width: 1%"><i class="fa-solid fa-minus jsDeleteNews rel pointer rel" data-id="'.$row['id'].'"><span class="tip">Удалить новость</span></i></td>
			</tr>';
		}

		if (!$content) {
			echo '<div class="error">Не найдено новостей в базе.</div>';
			return;
		}

		echo '
		<h1 class="centered">Новости</h1>
		<table class="typ-table jsLinkHolderTable">
			<thead>
				<th>Заголовок</th>
				<th>Дата публикации</th>
				<th>Автор</th>
				<th style="width: 1%"><i class="fa-solid fa-gears"></i></th>
			</thead>
			<tbody>
				'.$content.'
			</tbody>
		</table>
		<br>
		<div class="centered"><a class="typ-btn" href="/admin/?mode=news&action=new"><i class="fa-solid fa-plus"></i> Добавить</a></div>
		';
	}

	/**
	 * Добавление и редактирование новости (форма)
	 */
	private function add_edit($news_id=0) {
		$db = new db();
		$modify = new modify();
		$news_id = $modify->only_numbers($news_id);
		$dates = new dates();

		if ($news_id) {
			$js_trigger = 'jsNewsEditSubmit';
			$page_title = 'Редактировать новость';

			// вытаскиваем данные при редактировании
			$query = $db->query("
				SELECT
					`news`.*, `admins`.`name` AS `author`
				FROM `news`
					LEFT JOIN `admins` ON `admins`.`id` = `news`.`author`
				WHERE `news`.`id` = $news_id
			");
			$row = mysqli_fetch_assoc($query);

			if (!$row) {
				echo '<div class="error">Новость не найдена в базе.</div>';
				return;
			}

			// дата редактируется уже после, но при добавлении прописывается автоматически
			$row['date'] = '
			<tr>
				<th>Дата публикации</th>
				<td><input name="date" type="datetime-local" value="'.$dates->utc_to_local($row['date']).'"></td>
			</tr>';

			// автор не редактируется, а просто показывается при редактировании
			$row['author'] = '
			<tr>
				<th>Автор</th>
				<td>'.$row['author'].'</td>
			</tr>';
		} else {
			$js_trigger = 'jsNewsAddSubmit';
			$page_title = 'Добавить новость';

			// пустые значения при добавлении
			$row = array(
				'id' => '',
				'title' => '',
				'text' => '',
				'date' => '',
				'author' => '',
				'image' => '',
			);
		}

		if ($row['image']) {
			// выводим тумбу, а не полное изображение
			$row['image'] = '/uploads/thumbs/'.$row['image'].'-dashboard.webp';
			// выводим чекбокс для удаления изображения
			$delete_image = '
			<input type="checkbox" id="delete_image" name="delete_image">
			<label for="delete_image">Удалить изображение</label>';
		} else {
			// заглушка
			$row['image'] = '/css/images/placeholder.webp';
			$delete_image = '';
		}

		echo '<h1 class="centered">'.$page_title.'</h1>
		<form action="" method="post" enctype="multipart/form-data" class="'.$js_trigger.'">
			<input type="hidden" name="id" value="'.$row['id'].'">
		
			<table class="typ-table">
				<tbody>
					<tr>
						<th>Заголовок</th>
						<td><input name="title" type="text" value="'.htmlentities($row['title']).'"></td>
					</tr>
					<tr>
						<th>
							Ярлык
							<br><span class="text-small text-gray">Используется для ссылки. Оставьте пустым для авто-заполнения.</span>
						</th>
						<td><input name="link" type="text" value="'.htmlentities($row['link']).'"></td>
					</tr>
					<tr>
						<th>Текст</th>
						<td><textarea id="editor" name="text">'.htmlentities($row['text']).'</textarea></td>
					</tr>
					<tr>
						<th>Изображение</th>
						<td>
							<div class="upload-wrap">
								<label class="thumb-wrap">
									<img src="'.$row['image'].'" alt="">
								</label>
								<input name="image" type="file" id="image">
							</div>
							'.$delete_image.'
						</td>
					</tr>
					'.$row['date'].'
					'.$row['author'].'
				</tbody>
			</table>
			
			<br>
			<div class="centered">
				<a class="typ-btn-gray" href="/admin/?mode=news">
					<i class="fa-solid fa-arrow-left-long"></i>
					Назад
				</a>
				<button type="submit" name="submit">
					<i class="fa-solid fa-floppy-disk"></i>
					Сохранить
				</button>
			</div>
		</form>
		';
	}
	
	/**
	 * Добавление и редактирование новости (обработчик формы)
	 * @return int[]
	 * @throws Exception
	 */
	function ajax_edit_save() {
		$db = new db();
		$dates = new dates();
		$modify = new modify();
		$files = new files();
		$ajax = new ajax();

		// подготавливаем данные для записи в БД
		$data = array();
		$data['id'] = $modify->only_numbers($_POST['id']);
		if (!$data['id']) {
			$ajax->error('Не получен ID новости.');
		}
		
		$data['link'] = $modify->cyr_to_lat($_POST['link']);
		if (!$data['link']) {
			$data['link'] = $modify->cyr_to_lat($_POST['title']);
		}

		$data['title'] = $db->prepare_str($_POST['title']);
		if (!$data['title']) {
			$ajax->error('Вы не ввели заголовок.');
		}
		
		if (!trim($data['link'])) {
			$ajax->error('Вы должны ввести ярлык или заголовок');
		}
		
		if ($data['link'] == 'admin') {
			$ajax->error('Ярлык "'.$data['link'].'" не разрешён.');
		}
		
		$query = $db->query("SELECT `id` FROM `news` WHERE `link`='$data[link]' AND `id` != $data[id]");
		$row = mysqli_fetch_assoc($query);
		if ($row) {
			$ajax->error('Этот ярлык уже используется в другой новости');
		}
		
		$data['text'] = $db->prepare_str($_POST['text'], true);
		if (!$data['text']) {
			$ajax->error('Вы не ввели текст новости.');
		}

		$data['date'] = $dates->local_to_utc($_POST['date']);

		// первым делом, сохраняем текст, чтобы он не потерялся из-за ошибок ниже
		$db->query("
		UPDATE `news` SET
			`title`='$data[title]',
			`text`='$data[text]',
			`date`='$data[date]',
		    `link`='$data[link]'
		WHERE `id`=$data[id]
		");

		if (!isset($_POST['delete_image'])) {
			$_POST['delete_image'] = false;
		}
		// есть новое изображение
		if ((isset($_FILES['image']['tmp_name']) and $_FILES['image']['tmp_name']) or ($_POST['delete_image'])) {

			// находим старое
			$query = $db->query("SELECT `id`, `image` FROM `news` WHERE `id`=$data[id]");
			$row = mysqli_fetch_assoc($query);
			if (!$row) {
				$ajax->error('Новость не найдена в базе');
			}

			// удаляем старое, если файл существует
			if ($row['image']) {
				$files->delete_image($row['image']);

				// удаляем из базы
				$db->query("
				UPDATE `news` SET `image`='' WHERE `id`=$data[id]");
			}

			// не загружаем новое, если юзер отметил, что надо удалить
			if ($_POST['delete_image']) {
				return array('error' => 0);
			}
			
			// загружаем изображение
			$thumbs = array(
				'news' => THUMBS['news'],
				'og-news' => array(
					'width' => 420,
					'height' => 0,
					'crop' => false,
					'format' => 'png'
				),
			);
			
			$image = $files->upload_image('image', $thumbs);
			if ($image['error']) {
				$ajax->error($image['message']);
			}
			
			// пишем в базу название нового файла
			$image['image_name'] = $db->prepare_str($image['image_name']);
			$db->query("UPDATE `news` SET `image`='$image[image_name]' WHERE `id`=$data[id]");
		}

		return array('error' => 0);
	}
	
	/**
	 * Добавление новости (обработчик формы)
	 * @return array
	 */
	function ajax_add_new() {
		$db = new db();
		$files = new files();
		$ajax = new ajax();
		$modify = new modify();

		// подготавливаем данные для записи в БД
		$data = array();
		
		$data['link'] = $modify->cyr_to_lat($_POST['link']);
		if (!$data['link']) {
			$data['link'] = $modify->cyr_to_lat($_POST['title']);
		}
		
		$data['title'] = $db->prepare_str($_POST['title']);
		if (!$data['title']) {
			$ajax->error('Вы не ввели заголовок.');
		}
		
		if (!trim($data['link'])) {
			$ajax->error('Вы должны ввести ярлык или заголовок');
		}
		
		if ($data['link'] == 'admin') {
			$ajax->error('Ярлык "'.$data['link'].'" не разрешён.');
		}
		
		$data['link'] = $db->prepare_str($data['link']);
		
		$query = $db->query("SELECT `id` FROM `news` WHERE `link`='$data[link]'");
		$row = mysqli_fetch_assoc($query);
		if ($row) {
			$ajax->error('Этот ярлык уже используется в другой новости');
		}

		$data['text'] = $db->prepare_str($_POST['text'], true);
		if (!$data['text']) {
			$ajax->error('Вы не ввели текст новости.');
		}

		// пишем дату по Гринвичу
		$data['date'] = gmdate('Y-m-d H:i:s');
		$user_id = USER['id'];

		// первым делом, сохраняем текст, чтобы он не потерялся из-за ошибок ниже
		$db->query("
		INSERT INTO `news` SET
			`title`='$data[title]',
			`text`='$data[text]',
			`date`='$data[date]',
		    `image`='',
		    `link`='$data[link]',
		    `author`=$user_id
		");
		$news_id = $db->get_insert_id();
		if (!$news_id) {
			$ajax->error('Не получен ID новой записи.');
		}

		// есть новое изображение
		if (isset($_FILES['image']['tmp_name']) and $_FILES['image']['tmp_name']) {
			
			// загружаем изображение
			$thumbs = array(
				'news' => THUMBS['news'],
				'og-news' => array(
					'width' => 420,
					'height' => 0,
					'crop' => false,
					'format' => 'png'
				),
			);
			$result = $files->upload_image('image', $thumbs);
			if ($result['error']) {
				$result['id'] = $news_id;
				return $result;
			}
			$new_image_path = $result['new_image_path'];

			// пишем в базу название нового файла
			$new_image = pathinfo($new_image_path);
			$new_image_name = $db->prepare_str($new_image['filename']);
			$db->query("UPDATE `news` SET `image`='$new_image_name' WHERE `id`=$news_id");
		}

		return array('error' => 0, 'id' => $news_id);
	}
	
	/**
	 * Удаление новости
	 * @return int[]
	 */
	function ajax_delete_news() {
		$db = new db();
		$files = new files();
		$modify = new modify();
		$ajax = new ajax();

		$news_id = $modify->only_numbers($_POST['id']);
		if (!$news_id) {
			$ajax->error('Не получен ID новости');
		}

		// находим новость
		$query = $db->query("SELECT `id`, `image` FROM `news` WHERE `id`=$news_id");
		$row = mysqli_fetch_assoc($query);
		if (!$row) {
			$ajax->error('Новость не найдена в базе');
		}

		// удаляем её изображения
		$files->delete_image($row['image']);

		// удаляем саму новость
		$db->query("DELETE FROM `news` WHERE `id`=$news_id");

		return array('error' => 0);
	}

}