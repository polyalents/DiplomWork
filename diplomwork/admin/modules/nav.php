<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

class nav {
	/**
	 * Вывод списка навигации с формой для редактирования
	 */
	public function render() {
		$db = new db();
		$content = '';

		$n = 0;
		$query = $db->query("SELECT * FROM `nav` ORDER BY `order`");
		while ($row = mysqli_fetch_assoc($query)) {
			$content .= '
			<li class="nav-item" data-id="'.$row['id'].'">
				<input type="hidden" name="id['.$n.']" value="'.$row['id'].'">
				<input type="hidden" class="jsOrderHolder" name="order['.$n.']" value="'.$row['order'].'">
				
				<label>
					<span class="text-small">Текст:</span><br>
					<input type="text" name="name['.$n.']" value="'.htmlentities($row['name']).'">
				</label>
				<label>
					<span class="text-small">Ссылка:</span><br>
					<input type="text" name="href['.$n.']" value="'.htmlentities($row['href']).'"></a>
				</label>
				<div class="nav-tools-wrap">
					<span class="text-small">&nbsp;</span><br>
					<div class="nav-tools">
						<i class="fa-solid fa-minus jsNavDelete rel pointer"><span class="tip">Удалить элемент</span></i>
						<i class="fa-solid fa-up-down-left-right jsNavHandle rel grab"><span class="tip">Переместить</span></i>
					</div>
				</div>
				
			</li>';
			$n++;
		}

		if ($content) {
			$content = '
			<ul class="jsNav">
				'.$content.'
			</ul>';
			$save_btn = '<button type="submit" name="submit"><i class="fa-solid fa-floppy-disk"></i> Сохранить</button>';
		} else {
			$content = '<div class="error">Навигация пуста.</div>';
			$save_btn = '';
		}

		echo '
		<h1 class="centered">Навигация</h1>
		<form action="" method="post" class="jsSaveNav">
			'.$content.'
			<br>
			<div class="centered">
				<a class="typ-btn-gray jsNavAdd" href=""><i class="fa-solid fa-plus"></i> Добавить</a>
				'.$save_btn.'
			</div>
		</form>
		';
	}
	
	/**
	 * Форма добавления элемента навигации (выводится в модальном окне)
	 * держим форму здесь, а не в JS, чтобы кэш браузера не делал мозг, если мы захотим внести сюда изменения
	 * @return array
	 */
	function ajax_add_form() {
		return array('error' => 0, 'html' => '
		<form class="jsNavAddSubmit" action="/admin/?mode=nav&action=add" method="post">
			<label>
				<span class="text-small">Текст:</span><br>
				<input type="text" name="name">
			</label>
			<label>
				<span class="text-small">Ссылка:</span><br>
				<input type="text" name="href"></a>
			</label>
			<br>
			<div class="centered">
				<button type="submit" name="submit"><i class="fa-solid fa-floppy-disk"></i> Сохранить</button>
			</div>
		</form>
		');
	}
	
	/**
	 * Добавление элемента навигации (обработчик формы)
	 * @return int[]
	 */
	function ajax_add() {
		$db = new db();
		$ajax = new ajax();
		$data = $db->prepare_array($_POST);

		if (!$data['name']) {
			$ajax->error('Вы должны ввести текст ссылки.');
		}

		if (!$data['href']) {
			$ajax->error('Вы должны ввести ссылку.');
		}

		$order = 0;
		$query = $db->query("SELECT `order` FROM `nav` ORDER BY `order` DESC LIMIT 1");
		$row = mysqli_fetch_assoc($query);
		if ($row) {
			$order = $row['order'] + 1;
		}

		$db->query("INSERT INTO `nav` SET `name`='$data[name]', `href`='$data[href]', `order`=$order");

		return array('error' => 0);
	}
	
	/**
	 * Сохранение всех элементов навигации при редактировании (обработчик формы)
	 * @return int[]
	 */
	function ajax_save() {
		$db = new db();
		$modify = new modify();
		$ajax = new ajax();
		
		if (!isset($_POST['id']) or !$_POST['id'] or !is_array($_POST['id'])) {
			return array('error' => 0);
		}

		foreach($_POST['id'] as $key => $id) {
			$id = $modify->only_numbers($id);
			if (!$id) {
				$ajax->error('Получен id в неправильном формате.');
			}
			
			$name = '';
			if (isset($_POST['name'][$key]) and is_string($_POST['name'][$key])) {
				$name = $db->prepare_str($_POST['name'][$key]);
			}
			
			$href = '';
			if (isset($_POST['href'][$key]) and is_string($_POST['href'][$key])) {
				$href = $db->prepare_str($_POST['href'][$key]);
			}
			
			$order = 0;
			if (isset($_POST['order'][$key]) and is_string($_POST['order'][$key])) {
				$order = $modify->only_numbers($_POST['order'][$key]);
			}

			$db->query("UPDATE `nav` SET `name`='$name', `href`='$href', `order`=$order WHERE `id`=$id");
		}

		return array('error' => 0);
	}
	
	/**
	 * Удаление элемента навигации
	 * @return int[]
	 */
	function ajax_nav_delete() {
		$modify = new modify();
		$db = new db();
		$ajax = new ajax();

		$id = $modify->only_numbers($_POST['id']);
		if (!$id) {
			$ajax->error('Не получен id элемента.');
		}

		$db->query("DELETE FROM `nav` WHERE `id`=$id");

		return array('error' => 0);
	}
}