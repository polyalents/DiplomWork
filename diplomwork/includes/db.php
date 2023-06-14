<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

/**
 * Работа с базой данных
 * Class db
 */
class db {
	// тут хранится подключение, чтобы подключаться к базе всего раз
	static $db_link;
	
	/**
	 * Конектимся к БД
	 */
	function connect_db() {
		$db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		if (mysqli_connect_errno()) {
			echo 'Не удалось подключиться к базе данных: '.mysqli_connect_error();
			die();
		}
		if (!mysqli_set_charset($db, "utf8")) {
			echo 'Ошибка базы данных'.mysqli_error($db);
			die();
		}
		self::$db_link = $db;
	}

	/**
	 * mysqli_query с дополнениями
	 * @param $query
	 * @param string $request_label - сюда можно передать уникальную метку, чтобы понять какой из запросов падает
	 * @param bool $die_on_fail - скрипт завершится при ошибке по-умолчанию
	 * @return bool|mysqli_result
	 */
	function query($query, $request_label='', $die_on_fail=true) {
		if ($request_label) {
			$request_label .= ' :: ';
		}
		$result = mysqli_query(self::$db_link, $query);
		$this->check_mysqli_error($request_label.$query, $die_on_fail);
		return $result;
	}
	
	/**
	 * Обычное экрнирование с упращённым синтаксисом
	 * @param $text - строка
	 * @return string
	 */
	function escape($text) {
		return mysqli_real_escape_string(self::$db_link, $text);
	}

	/**
	 * Проверка ошибки после выполения SQL запроса с выводом ошибки
	 * @param $request_label
	 * @param $die_on_fail
	 * @return bool|string
	 */
	private function check_mysqli_error($request_label, $die_on_fail) {
		$error = mysqli_error(self::$db_link);
		$ajax = new ajax();
		if (!$error) {
			return false;
		}
		
		if ($request_label) {
			$error = '<b>'.$request_label.'</b> - '.$error;
		}
		
		if ($die_on_fail) {
			if ($_POST['method'] and $_POST['method'] != 'undefined') {
				$ajax->error($error);
			} else {
				echo $error;
			}
			die;
		} else {
			return $error;
		}
	}
	
	/**
	 * Подготавливает строку для записи в БД
	 * @param $string
	 * @param bool $html - разрешить или запретить HTML в строке
	 * @return string|array
	 */
	function prepare_str($string, $html=false) {
		if (!is_string($string)) {
			return $string;
		}
		$string = trim($string);

		if (!$html) {
			// убираем HTML-теги
			$string = strip_tags($string);
		}
		
		return $this->escape($string);
	}
	
	/**
	 * Подготавливает массив строк для записи в БД
	 * @param $arr
	 * @return mixed
	 */
	function prepare_array($arr) {
		if (!is_array($arr)) {
			return $arr;
		}
		foreach ($arr as $key => $value) {
			$arr[$key] = $this->prepare_str($value);
		}
		return $arr;
	}
	
	/**
	 * Возвращает id только что вставленной записи
	 * @return int|string
	 */
	function get_insert_id() {
		$id = mysqli_insert_id(self::$db_link);
		$this->check_mysqli_error('', true);
		return $id;
	}
	
}