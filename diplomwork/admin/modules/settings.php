<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

class settings {
	/**
	 * Вывод списка настроек в форме
	 */
	public function render() {
		$db = new db();
		$modify = new modify();
		$content = '';

		$query = $db->query("SELECT * FROM `settings` ORDER BY `order`");
		while ($row = mysqli_fetch_assoc($query)) {
			$id = $modify->only_numbers($row['id']);
			
			// если это файл - выводим также картинку
			$image = '';
			if ($row['input_type'] == 'file') {
				if ($row['value']) {
					$image = '<img class="settings-img" src="/uploads/thumbs/'.$row['value'].'-dashboard.webp" alt="">';
				} else {
					// заглушка
					$image = '<img class="settings-img" src="/css/images/placeholder.webp" alt="">';
				}
			}
			
			$content .= '
			<tr data-id="'.$id.'">
				<td style="width: 30%">
					'.$row['descr'].'
				</td>
				<td>
					<div class="upload-wrap">
						'.$image.'
						<input type="'.$row['input_type'].'" name="'.$row['key'].'" value="'.$row['value'].'">
					</div>
				</td>
			</tr>';
		}

		if (!$content) {
			echo '<div class="error">Настройки не найдены.</div>';
			return;
		}

		echo '
		<h1 class="centered">Настройки сайта</h1>
		<form action="" method="post" enctype="multipart/form-data" class="jsSettingsTable">
		<table class="typ-table">
			<tbody>
				'.$content.'
			</tbody>
		</table>
		<br>
		<div class="centered">
			<button type="submit" name="submit">
				<i class="fa-solid fa-floppy-disk"></i>
				Сохранить
			</button>
		</div>
		';
	}
	
	/**
	 * Сохранение настроек (обработчик формы)
	 * @return int[]
	 */
	function ajax_save() {
		$db = new db();
		$ajax = new ajax();

		// подготавливаем данные для записи в БД
		$data = array();

		$data['site_title'] = $db->prepare_str($_POST['site_title']);
		if (!$data['site_title']) {
			$ajax->error('Вы не ввели название сайта.');
		}
		
		$data['site_descr'] = $db->prepare_str($_POST['site_descr']);
		$data['site_tags'] = $db->prepare_str($_POST['site_tags']);

		$data['call_back_emails'] = $db->prepare_str($_POST['call_back_emails']);
		if (!$data['call_back_emails']) {
			$ajax->error('Вы не ввели Email.');
		}
		
		if (!filter_var($data['call_back_emails'], FILTER_VALIDATE_EMAIL)) {
			$ajax->error('Неправильный формат Email.');
		}

		// первым делом, сохраняем строки, чтобы они не потерялись из-за ошибок ниже
		$db->query("UPDATE `settings` SET `value`='$data[site_title]' WHERE `key`='site_title'");
		$db->query("UPDATE `settings` SET `value`='$data[site_descr]' WHERE `key`='site_descr'");
		$db->query("UPDATE `settings` SET `value`='$data[site_tags]' WHERE `key`='site_tags'");
		$db->query("UPDATE `settings` SET `value`='$data[call_back_emails]' WHERE `key`='call_back_emails'");
		
		// загрузка изображений, если они есть
		$this->upload_image('site_logo');
		$this->upload_image('favicon');

		return array('error' => 0);
	}
	
	private function upload_image($image_key) {
		if ((!isset($_FILES[$image_key]['tmp_name']) or !$_FILES[$image_key]['tmp_name'])) {
			return;
		}
		
		$db = new db();
		$ajax = new ajax();
		$files = new files();
		
		// находим старое
		$query = $db->query("SELECT `value` FROM `settings` WHERE `key`='$image_key'");
		$row = mysqli_fetch_assoc($query);
		if (!$row) {
			$ajax->error('Опция "'.$image_key.'" не найдена в базе');
		}
		
		// удаляем старое, если файл существует
		if ($row['value']) {
			$files->delete_image($row['value']);
			
			// удаляем из базы
			$db->query("UPDATE `settings` SET `value`='' WHERE `key`='$image_key'");
		}
		
		switch($image_key) {
			default:
				break;
			case 'site_logo':
				// миниатюра в формате png для Open Graph
				$thumbs = array(
					'og' => array(
						'width' => 420,
						'height' => 0,
						'crop' => false,
						'format' => 'png'
					),
				);
				// загружаем изображение
				$image = $files->upload_image($image_key, $thumbs);
				if ($image['error']) {
					$ajax->error($image['message']);
				}
				break;
			case 'favicon':
				// загружаем изображение
				$image = $files->upload_image($image_key, array(), 512, 512, true, 'png');
				if ($image['error']) {
					$ajax->error($image['message']);
				}
				break;
		}
		
		// пишем в базу название нового файла
		$image['image_name'] = $db->prepare_str($image['image_name']);
		$db->query("UPDATE `settings` SET `value`='$image[image_name]' WHERE `key`='$image_key'");
	}

}