<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}
/**
 * Работа с файлами
 * Class files
 */
class files {
	/**
	 * Загрузка изображения
	 * @param $files_name - название ключа в переменной $_FILES
	 * @param array $thumbs - массив с параметрами миниатюр
	 * @return array
	 */
	function upload_image($files_name, $thumbs=array(), $orig_width=1920, $orig_height=0, $orig_crop=false, $orig_format='webp') {
		$modify = new modify();
		$ajax = new ajax();
		
		// дополнительная миниатюра для админки
		$thumbs['dashboard'] = array(
			'width' => THUMBS['admin']['width'],
			'height' => THUMBS['admin']['height'],
			'crop' => true,
			'format' => 'webp'
		);
		
		if (!isset($_FILES[$files_name]['name']) or !$_FILES[$files_name]['name']) {
			$ajax->error('#1: Вы не выбрали изображение для загрузки.');
		}

		// проверяем новое изображение
		$new_image_info = pathinfo($_FILES[$files_name]['name']);
		$new_image_info['extension'] = strtolower($new_image_info['extension']);
		if (!in_array($new_image_info['extension'], array('jpg', 'jpeg', 'png', 'gif', 'webp'))) {
			$ajax->error('Вы можете загружать только изображения в форматах jpg, png, gif, webp');
		}

		// русские буквы в латинские
		$new_image_info['filename'] = $modify->cyr_to_lat($new_image_info['filename']);
		
		// добавляем к имени файла циферку, если изображение с таким именем уже есть
		$new_image_path = ROOT_DIR.'/uploads/'.$new_image_info['filename'].'.'.$orig_format;
		while(file_exists($new_image_path)) {
			$new_image_info['filename'] .= $modify->rand_string(1, true);
			$new_image_path = ROOT_DIR.'/uploads/'.$new_image_info['filename'].'.'.$orig_format;
		}
		
		// создаём миниатюры
		foreach($thumbs as $thumb_postfix => $thumb) {
			if ($thumb_postfix) {
				$thumb_postfix = '-'.$thumb_postfix;
			}
			
			if (!isset($thumb['format'])) {
				$thumb['format'] = 'webp';
			}
			
			$this->create_thumb(
				$_FILES[$files_name]['tmp_name'],
				$new_image_info['filename'],
				$thumb['width'],
				$thumb['height'],
				$thumb['crop'],
				$thumb_postfix,
				$thumb['format']
			);
		}
		
		// оригиналы в большом размере не нужны, для сайта достаточно ширины 1920
		// поэтому, загружаем оригинал также, как миниатюру
		$this->create_thumb(
			$_FILES[$files_name]['tmp_name'],
			$new_image_info['filename'],
			$orig_width,
			$orig_height,
			$orig_crop,
			'',
			$orig_format
		);
		
		return array('error' => 0, 'image_name' => $new_image_info['filename']);
	}
	
	/**
	 * Обработка ошибок при создании миниатюр
	 * @param $source_image - полный путь к оригинальному файлу
	 * @param $new_image_filename - конечное имя файла (без разширения)
	 * @param $width - ширина (0 = авто)
	 * @param $height - высота (0 = авто)
	 * @param false $crop - true = обрезать, false = втиснуть в размеры
	 * @param string $thumb_postfix - что дописать к имени миниарюты
	 */
	public function create_thumb($source_image, $new_image_filename, $width, $height, $crop=false, $thumb_postfix='', $format='webp') {
		try {
			$this->_create_thumb($source_image, $new_image_filename, $width, $height, $crop, $thumb_postfix, $format);
		} catch(ImagickException $e) {
			$ajax = new ajax();
			$ajax->error(var_export($e, true));
		}
	}
	
	/**
	 * Создание миниатюры
	 * @param $source_image - полный путь к оригинальному файлу
	 * @param $new_image_filename - конечное имя файла (без разширения)
	 * @param $width - ширина (0 = авто)
	 * @param $height - высота (0 = авто)
	 * @param false $crop - true = обрезать, false = втиснуть в размеры
	 * @param string $thumb_postfix - что дописать к имени миниарюты
	 * @throws ImagickException
	 */
	private function _create_thumb($source_image, $new_image_filename, $width, $height, $crop, $thumb_postfix, $format) {
		$ajax = new ajax();
		$modify = new modify();
		
		if ($thumb_postfix) {
			// нарезка миниатюр
			$upload_dir = ROOT_DIR.'/uploads/thumbs/';
		} else {
			// нарезка оригинала
			$upload_dir = ROOT_DIR.'/uploads/';
		}
		if (!file_exists($source_image)) {
			$ajax->error('Не найдено оригинальное изображение: '.$source_image);
		}
		
		$width = $modify->only_numbers($width);
		$height = $modify->only_numbers($height);
		
		$crop = $modify->only_numbers($crop);
		if ($crop) {
			$crop = true;
		} else {
			$crop = false;
		}
		
		// пробуем загрузить изображение в библиотеку
		$im = new Imagick($source_image);
		
		// выдёргиваем первый кадр у gif
		if (strtolower($im->getImageFormat()) == 'gif') {
			$im = $im->coalesceImages();
			foreach ($im as $frame) {
				$im = $frame;
				break;
			}
		}

		// пробуем получить оригинальную ширину
		$image_width = $im->getImageWidth();
		
		// пробуем получить оригинальную высоту
		$image_height = $im->getImageHeight();
		
		if ($crop) {
			// обрезаем
			if (!$width or !$height) {
				$ajax->error('В режиме обрезания надо ввести и ширину и высоту.');
			}
			
			if (($image_width > $width) or ($image_height > $height)) {
				if ($image_width < $width) {
					// обрезание лишней высоты без растягивания по ширине
					$y = ($image_height - $height) / 2;
					$y = round($y);
					
					$im->cropImage($image_width, $height, 0, $y);
				} elseif ($image_height < $height) {
					// обрезание лишней ширины без растягивания по высоте
					$x = ($image_width - $width) / 2;
					$x = round($x);
					$im->cropImage($width, $image_height, $x, 0);
				} else {
					// изображение больше по ширине и высоте - значит заполняем
					$im->cropThumbnailImage($width, $height);
				}
			}
			// если изображение влезло в заданные размеры, то просто сохраняем оригинал
		} else {
			// втискиваем
			if (!$width and !$height) {
				$ajax->error('Вы должны ввести ширину или высоту.');
			}
			
			// ширина - авто
			if (!$width) {
				$aspect = $image_width / $image_height;
				$width = round($aspect * $height);
			}
			
			// высота - авто
			if (!$height) {
				$aspect = $image_height / $image_width;
				$height = round($aspect * $width);
			}
			
			// без кропа - втискиваем в размеры
			if (($image_width > $width) or ($image_height > $height)) {
				$im->scaleImage($width, $height, true);
			}
		}
		
		// путь к новому файлу
		$new_image_name = $new_image_filename.$thumb_postfix.'.'.$format;
		
		$im->setImageFormat($format);
		if ($format == 'webp') {
			$im->setOption('webp:method', '6');
			$im->setImageCompressionQuality(85);
//			$im->setOption('webp:lossless', 'true');
		}
		
		// пробуем сохранить
		$im->writeImage($upload_dir.$new_image_name);
		
		$im->clear();
		$im->destroy();
	}
	
	/**
	 * Удаляем изображение со всеми миниатюрами
	 * @param $image_name - имя файла без пути
	 */
	function delete_image($image_name) {
		// изображение не найдено
		if (!$image_name) {
			return;
		}
		
		$formats = array('webp', 'png');

		// удаляем оригинал
		foreach($formats as $format) {
			$old_image_path = ROOT_DIR.'/uploads/'.$image_name.'.'.$format;
			if (file_exists($old_image_path)) {
				unlink($old_image_path);
			}
		}

		// удаляем тумбы
		$thumbs = array('pages', 'news', 'dashboard', 'og-news', 'og');
		foreach($thumbs as $thumb_postfix) {
			foreach($formats as $format) {
				$old_thumb_path = ROOT_DIR.'/uploads/thumbs/'.$image_name.'-'.$thumb_postfix.'.'.$format;
				if (file_exists($old_thumb_path)) {
					unlink($old_thumb_path);
				}
			}
		}
	}
	
}