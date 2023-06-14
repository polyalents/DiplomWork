<?php

/**
 * Работа с датами
 * Class dates
 */
class dates {
	/**
	 * Конвертор из локального времени в Гринвич
	 * @param $date - Дата в формате Y-m-d H:i:s
	 * @param string $format - Выходной формат
	 * @return string
	 */
	function local_to_utc($date, $format = 'Y-m-d H:i:s') {
		try {
			$date = new DateTime($date, new DateTimeZone('Europe/Moscow'));
		} catch(Exception $e) {
			return $date;
		}
		$time_zone = new DateTimeZone('UTC');
		
		$date->setTimezone($time_zone);
		return $date->format($format);
	}
	
	/**
	 * Конвертор из Гринвича в локальное время
	 * @param $date - Дата в формате Y-m-d H:i:s
	 * @param string $format - Выходной формат
	 * @return string
	 */
	function utc_to_local($date, $format = 'Y-m-d H:i:s') {
		try {
			$date = new DateTime($date, new DateTimeZone('UTC'));
		} catch(Exception $e) {
			return $date;
		}
		$time_zone = new DateTimeZone('Europe/Moscow');
		
		$date->setTimezone($time_zone);
		
		return $date->format($format);
	}
	
	/**
	 * Вывод дат в читабельный формат
	 * @param $date_in - дата из базы
	 * @param bool $out_time - выводить или не выводить время
	 * @return mixed|string
	 */
	function out_date($date_in, $out_time=true) {
		if (!$date_in) {
			return '';
		}
		if (substr($date_in, 0, 10) == '0000-00-00') {
			return $date_in;
		}

		$date_in = $this->utc_to_local($date_in);

		list($date, $time) = explode(' ', $date_in);
		list($year, $month, $day) = explode('-', $date);
		
		if ($out_time) {
			if ($time) {
				list($hour, $minute) = explode(':', $time);
			} else {
				$hour = $minute = '00';
			}
			return $day.'.'.$month.'.'.$year.', '.$hour.':'.$minute;
		} else {
			return $day.'.'.$month.'.'.$year;
		}
		
	}

}