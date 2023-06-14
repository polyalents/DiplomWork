<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}
/**
 * Класс для преобразования строк и чисел в нужный вид
 * Class modify
 */
class modify {
	/**
	 * Генерирует случайную строку заданной длины
	 * @param $length - длина
	 * @param bool $numb_only - использовать ли только цифры
	 * @param bool $lowercase - нужно ли приводить к нижнему регистру
	 * @return string
	 */
	function rand_string($length, $numb_only = false, $lowercase = false) {
		if ($numb_only) {
			$characters = '0123456789';
		} else {
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		}
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, strlen($characters) - 1)];
		}
		
		if ($lowercase) {
			$randomString = strtolower($randomString);
		}
		
		return $randomString;
	}

	/**
	 * Убирает из строки всё кроме цифр
	 * @param $string
	 * @return mixed
	 */
	function only_numbers($string) {
		return filter_var($string, FILTER_VALIDATE_INT);
	}

	/**
	 * Русские слова в латиницу по стандарту Яндекса
	 * @param $title - Входящая строка
	 * @param bool $tolower - нужно ли приобразовать в нижний регистр
	 * @param bool $uri_symbols - нужно ли заменить пробелы на - (для ссылок)
	 * @return mixed|string
	 */
	function cyr_to_lat($title, $tolower = true, $uri_symbols = true) {
		$rus = 'а-б-в-г-д-е-ё-ж-з-и-й-к-л-м-н-о-п-р-с-т-у-ф-х-ц-ч-ш-щ-ъ-ы-ь-э-ю-я';
		$lat = 'a-b-v-g-d-e-yo-zh-z-i-j-k-l-m-n-o-p-r-s-t-u-f-h-c-ch-sh-shch--y--eh-yu-ya';
		$rus_upper = mb_strtoupper($rus);
		$lat_upper = strtoupper($lat);

		$rus = explode('-', $rus);
		$lat = explode('-', $lat);

		if ($tolower) {
			$title = mb_strtolower($title);
		} else {
			$rus_upper = explode('-', $rus_upper);
			$lat_upper = explode('-', $lat_upper);

			foreach ($rus_upper as $key => $value) {
				$title = str_replace($value, $lat_upper[$key], $title);
			}
		}

		foreach ($rus as $key => $value) {
			$title = str_replace($value, $lat[$key], $title);
		}

		if ($uri_symbols) {
			$title = str_replace(' ', '-', $title);
			$title = preg_replace("/[^a-zA-Z0-9_\-]/", '', $title);
		}

		return $title;
	}
}