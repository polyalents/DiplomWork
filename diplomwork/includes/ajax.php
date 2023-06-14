<?php
class ajax {
	/**
	 * Вывод ошибки для AJAX-запросов
	 * @param $message - сообщение
	 */
	function error($message) {
		$arr = array('message' => $message, 'error' => 1);
		echo json_encode($arr);
		die;
	}
}