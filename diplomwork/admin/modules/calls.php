<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

class calls {
	/**
	 * Вывод списка
	 */
	public function render() {
		if (isset($_GET['read'])) {
			$this->output_list(1);
		} else {
			$this->output_list(0);
		}
	}
	
	function output_list($read=0) {
		$db = new db();
		$dates = new dates();
		$content = '';
		
		$query = $db->query("SELECT * FROM `calls` WHERE `read`=$read ORDER BY `date` DESC LIMIT 50");
		while ($row = mysqli_fetch_assoc($query)) {
			if ($read) {
				$read_btn = '
				<i class="fa-solid fa-rotate-left jsUnreadCall rel pointer rel" data-id="'.$row['id'].'">
					<span class="tip">В непрочитанные</span>
				</i>';
			} else {
				$read_btn = '
				<i class="fa-solid fa-check jsReadCall rel pointer rel" data-id="'.$row['id'].'">
					<span class="tip">В прочитанные</span>
				</i>';
			}
			
			$content .= '
			<tr>
				<td>'.$dates->out_date($row['date']).'</td>
				<td>'.$row['name'].'</td>
				<td>'.$row['phone'].'</td>
				<td>'.$row['note'].'</td>
				<td style="width: 1%">
					<div class="flex-50">
						'.$read_btn.'
						<i class="fa-solid fa-minus jsDeleteCall rel pointer rel" data-id="'.$row['id'].'">
							<span class="tip">Удалить заявку</span>
						</i>
					</div>
				</td>
			</tr>';
		}
		
		echo '<h1 class="centered">Заявки на обратный звонок</h1>';
		if ($read) {
			echo '
			<ul class="sub-nav">
				<li><a href="/admin/?mode=calls">Непрочитанные</a></li>
				<li>Прочитанные</li>
			</ul>';
		} else {
			echo '
			<ul class="sub-nav">
				<li>Непрочитанные</li>
				<li><a href="/admin/?mode=calls&read=1">Прочитанные</a></li>
			</ul>';
		}
		
		if ($content) {
			echo '
			<table class="typ-table">
				<thead>
					<th>Дата</th>
					<th>Имя</th>
					<th>Телефон</th>
					<th>Примечание</th>
					<th style="width: 1%"><i class="fa-solid fa-gears"></i></th>
				</thead>
				<tbody>
					'.$content.'
				</tbody>
			</table>
			';
		} else {
			echo '<div class="error">Не найдено заявок.</div>';
		}
	}

	/**
	 * Добавление новой заявки с формы на сайте
	 * @return array
	 */
	function ajax_add_new() {
		$db = new db();
		$ajax = new ajax();
		$mailer = new mailer();
		
		if (!isset($_POST['agreement']) or $_POST['agreement'] != 'on') {
			$ajax->error('Вы должны согласиться с условиями обработки персональных данных.');
		}
		
		// подготавливаем данные для записи в БД
		$data = array();
		
		$data['name'] = $db->prepare_str($_POST['name']);
		if (!$data['name']) {
			$ajax->error('Вы не ввели имя.');
		}
		
		$data['phone'] = $db->prepare_str($_POST['phone']);
		if (!$data['phone']) {
			$ajax->error('Вы не ввели телефон.');
		}
		
		$data['note'] = $db->prepare_str($_POST['note']);
		// пишем дату по Гринвичу
		$data['date'] = gmdate('Y-m-d H:i:s');
		
		$db->query("
		INSERT INTO `calls` SET
			`name`='$data[name]',
			`phone`='$data[phone]',
			`note`='$data[note]',
		    `date`='$data[date]',
			`read`=0
		");
		
		// отправляем письмо администратору
		$query = $db->query("SELECT `value` FROM `settings` WHERE `key`='call_back_emails'");
		$row = mysqli_fetch_assoc($query);
		if (!$row or !filter_var($row['value'], FILTER_VALIDATE_EMAIL)) {
			$ajax->error('Администратор не выбрал Email для приёма писем.');
		}
		
		$message = '
		<b>Имя</b>: '.$_POST['name'].'<br>
		<b>Телефон</b>: '.$_POST['phone'].'<br>
		';
		if (trim($_POST['note'])) {
			$message .= '<b>Примечание</b>: '.$_POST['note'];
		}
		
		$mailer->send_email($row['value'], $message, 'Заявка на обратный звонок');
		
		return array('error' => 0, 'message' => 'Ваша заявка принята!');
	}
	
	/**
	 * Удаление заявки
	 * @return int[]
	 */
	function ajax_delete() {
		$db = new db();
		$modify = new modify();
		$ajax = new ajax();
		
		$call_id = $modify->only_numbers($_POST['id']);
		if (!$call_id) {
			$ajax->error('Не получен ID заявки');
		}
		
		$db->query("DELETE FROM `calls` WHERE `id`=$call_id");
		
		return array('error' => 0);
	}
	
	function ajax_to_read($read=1) {
		$db = new db();
		$modify = new modify();
		$ajax = new ajax();
		
		$call_id = $modify->only_numbers($_POST['id']);
		if (!$call_id) {
			$ajax->error('Не получен ID заявки');
		}
		
		$db->query("UPDATE `calls` SET `read`=$read WHERE `id`=$call_id");
		
		return array('error' => 0);
	}
	
	function ajax_to_unread() {
		return $this->ajax_to_read(0);
	}
}