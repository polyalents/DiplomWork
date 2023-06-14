<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

class admins {
	private $password_plug = '**********';
	
	public function render() {
		// вывод страниц
		$modify = new modify();
		if (isset($_GET['id']) and $admin_id = $modify->only_numbers($_GET['id'])) {
			// редактирование
			$this->add_edit($admin_id);
		} elseif (isset($_GET['action']) and $_GET['action'] == 'new') {
			// добавление
			$this->add_edit();
		} else {
			// вывод списка
			$this->output_list();
		}
	}
	
	/**
	 * Добавление и редактирование
	 */
	private function add_edit($admin_id=0) {
		$db = new db();
		$modify = new modify();
		$admin_id = $modify->only_numbers($admin_id);
		
		if ($admin_id) {
			// редактирование
			$js_trigger = 'jsAdminEditSubmit';
			$page_title = 'Редактировать администратора';
			
			// вытаскиваем данные при редактировании
			$query = $db->query("SELECT * FROM `admins` WHERE `id` = $admin_id");
			$row = mysqli_fetch_assoc($query);
			
			if (!$row) {
				echo '<div class="error">Администратор не найден в базе.</div>';
				return;
			}
			
			// пароль мы не храним, мы храним его MD5-хеш, по которому и сверяем
			// поэтому, вывести пароль не можем
			$row['password'] = $this->password_plug;
		} else {
			// добавление
			$js_trigger = 'jsAdminAddSubmit';
			$page_title = 'Добавить администратора';
			
			// пустые значения при добавлении
			$row = array(
				'id' => '',
				'name' => '',
				'email' => '',
				'password' => '',
				'auth' => ''
			);
		}
		
		echo '<h1 class="centered">'.$page_title.'</h1>
		<form action="" method="post" enctype="multipart/form-data" class="'.$js_trigger.'">
			<input type="hidden" name="id" value="'.$row['id'].'">
		
			<table class="typ-table">
				<tbody>
					<tr>
						<th>Логин</th>
						<td><input name="name" type="text" value="'.htmlentities($row['name']).'"></td>
					</tr>
					<tr>
						<th>Email</th>
						<td><input name="email" type="text" value="'.htmlentities($row['email']).'"></td>
					</tr>
					<tr>
						<th>Пароль</th>
						<td><input name="password" type="password" value="'.$row['password'].'"</td>
					</tr>
					<tr>
						<th>Повторите пароль</th>
						<td><input name="password2" type="password" value="'.$row['password'].'"</td>
					</tr>
				</tbody>
			</table>
			
			<br>
			<div class="centered">
				<a class="typ-btn-gray" href="/admin/?mode=admins">
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
	 * Вывод списка админов
	 */
	private function output_list() {
		$db = new db();
		$content = '';
		
		$query = $db->query("SELECT * FROM `admins` ORDER BY `name`");
		while ($row = mysqli_fetch_assoc($query)) {
			$content .= '
			<tr>
				<td>
				<a href="/admin/?mode=admins&id='.$row['id'].'" class="jsLinkHolder">
					'.$row['name'].'
				</a>
				</td>
				<td>'.$row['email'].'</td>
					<td style="width: 1%">
						<i class="fa-solid fa-minus jsDeleteAdmin rel pointer rel" data-id="'.$row['id'].'">
							<span class="tip">Удалить админа</span>
						</i>
					</td>
			</tr>';
		}
		
		if ($content) {
			$content = '
			<h1 class="centered">Администраторы</h1>
			<table class="typ-table jsLinkHolderTable">
				<thead>
					<th>Логин</th>
					<th>Email</th>
					<th style="width: 1%"><i class="fa-solid fa-gears"></i></th>
				</thead>
				<tbody>
					'.$content.'
				</tbody>
			</table>
			<br>
			<div class="centered"><a class="typ-btn" href="/admin/?mode=admins&action=new"><i class="fa-solid fa-plus"></i> Добавить</a></div>
			';
			echo $content;
		} else {
			echo '<div class="error">Не найдено админов в базе.</div>';
		}
	}

	/**
	 * Авторизация
	 * @return array|int[]
	 */
	function ajax_auth() {
		$db = new db();
		$ajax = new ajax();

		$login = trim($_POST['login']);
		$password = $db->escape($_POST['password']);

		if (!$login) {
			$ajax->error('Необходимо ввести логин.');
		}

		if (!$password) {
			$ajax->error('Необходимо ввести пароль.');
		}

		// обезопашиваем логин
		$login = $db->prepare_str($login);
		// сверяем не пароль, а его md5-хэш
		$password = md5($password);

		$query = $db->query("SELECT `id` FROM `admins` WHERE LOWER(`name`)=LOWER('$login') AND `password`='$password'");
		$row = mysqli_fetch_assoc($query);
		if (!isset($row['id'])) {
			$ajax->error('Неправильно введён логин или пароль.');
		}
		$user_id = $row['id'];
		
		// генерируем ключ авторизации и проверяем, чтобы он не был занят
		$modify = new modify();
		$error = true;
		$auth = '';
		while($error) {
			$auth = $modify->rand_string(64);
			$query = $db->query("SELECT `id` FROM `admins` WHERE `auth`='$auth'");
			$row = mysqli_fetch_assoc($query);
			if (!isset($row['id'])) {
				$error = false;
				break;
			}
		}

		// пишем новый ключ авторизации в базу
		$db->query("UPDATE `admins` SET `auth`='$auth' WHERE `id`=$user_id");

		// пишем ключ в сессию, на случай если кука перестанет работать из-за ужесточения правил браузеров
		session_start();
		$_SESSION['auth'] = $auth;
		session_write_close();

		// пишем ключ в куку
		setcookie('auth', $auth, time() + 86400, '/', '', false, true);

		return array('error' => 0, 'auth' => $auth);
	}

	/**
	 * Выход
	 * @return int[]
	 */
	function ajax_sign_out() {
		// удаляем из сессии
		session_start();
		unset($_SESSION['auth']);
		session_write_close();

		// удаляем из кук
		unset($_COOKIE['auth']);
		setcookie('auth', '1', time() - 86400, '/', '', false, true);
		return array('error' => 0);
	}
	
	/**
	 * Добавление нового админа
	 * @return array
	 */
	function ajax_add_new() {
		$db = new db();
		$ajax = new ajax();
		
		// подготавливаем данные для записи в БД
		$data = array();
		
		$data['name'] = $db->prepare_str($_POST['name']);
		if (!$data['name']) {
			$ajax->error('Вы не ввели логин.');
		}
		
		$data['email'] = $db->prepare_str($_POST['email']);
		if (!$data['email']) {
			$ajax->error('Вы не ввели Email.');
		}
		
		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			$ajax->error('Неправильный формат Email.');
		}
		
		if (!trim($_POST['password'])) {
			$ajax->error('Пароль не может быть пустым.');
		}
		
		if (trim($_POST['password']) !== trim($_POST['password2'])) {
			$ajax->error('Введённые пароли не совпадают.');
		}
		
		// не храним пароль в базе, храним его md5-хэш
		$data['password'] = md5($_POST['password']);
		
		$query = $db->query("SELECT `id` FROM `admins` WHERE LOWER(`name`)=LOWER('$data[name]')");
		$row = mysqli_fetch_assoc($query);
		if ($row) {
			$ajax->error('Администратор с таким именем уже существует.');
		}
		
		$query = $db->query("SELECT `id` FROM `admins` WHERE LOWER(`email`)=LOWER('$data[email]')");
		$row = mysqli_fetch_assoc($query);
		if ($row) {
			$ajax->error('Администратор с такой почтой уже существует.');
		}
		
		$db->query("
		INSERT INTO `admins` SET
			`name`='$data[name]',
			`email`='$data[email]',
			`password`='$data[password]',
		    `auth`=''
		");
		$admin_id = $db->get_insert_id();
		if (!$admin_id) {
			$ajax->error('Не получен ID новой записи.');
		}
		
		return array('error' => 0, 'id' => $admin_id);
	}
	
	/**
	 * Сохранение при редактировании
	 * @return int[]
	 */
	function ajax_edit_save() {
		$db = new db();
		$modify = new modify();
		$ajax = new ajax();
		
		// подготавливаем данные для записи в БД
		$data = array();
		
		$admin_id = $modify->only_numbers($_POST['id']);
		if (!$admin_id) {
			$ajax->error('Не получен id администратора.');
		}
		
		// моя учётка - не даём заказчику изменять
		// эту проверку надо убрать после сдачи работы
		if ($admin_id == 3 and USER['id'] != 3) {
			$ajax->error('Вы не можете редактировать этого пользователя.');
		}
		
		$data['name'] = $db->prepare_str($_POST['name']);
		if (!$data['name']) {
			$ajax->error('Вы не ввели логин.');
		}
		
		$data['email'] = $db->prepare_str($_POST['email']);
		if (!$data['email']) {
			$ajax->error('Вы не ввели Email.');
		}
		
		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			$ajax->error('Неправильный формат Email.');
		}
		
		if (trim($_POST['password']) !== trim($_POST['password2'])) {
			$ajax->error('Введённые пароли не совпадают.');
		}
		
		if ($_POST['password'] and $_POST['password'] != $this->password_plug) {
			$data['password'] = md5($_POST['password']);
		} else {
			$data['password'] = '';
		}
		
		$query = $db->query("SELECT `id` FROM `admins` WHERE LOWER(`name`)=LOWER('$data[name]') AND `id` != $admin_id");
		$row = mysqli_fetch_assoc($query);
		if ($row) {
			$ajax->error('Администратор с таким именем уже существует.');
		}
		
		$query = $db->query("SELECT `id` FROM `admins` WHERE LOWER(`email`)=LOWER('$data[email]') AND `id` != $admin_id");
		$row = mysqli_fetch_assoc($query);
		if ($row) {
			$ajax->error('Администратор с такой почтой уже существует.');
		}
		
		$change_password = '';
		if ($data['password']) {
			$change_password = ", `password`='$data[password]'";
		}
		
		$db->query("
		UPDATE `admins` SET
			`name`='$data[name]',
			`email`='$data[email]'
			$change_password
		WHERE `id`=$admin_id
		");
		
		return array('error' => 0);
	}
	
	/**
	 * Удаление админа
	 * @return int[]
	 */
	function ajax_delete() {
		$db = new db();
		$modify = new modify();
		$ajax = new ajax();
		
		$admin_id = $modify->only_numbers($_POST['id']);
		if (!$admin_id) {
			$ajax->error('Не получен ID администратора');
		}
		
		// находим админа
		$query = $db->query("SELECT `id` FROM `admins` WHERE `id`=$admin_id");
		$row = mysqli_fetch_assoc($query);
		if (!$row) {
			$ajax->error('Администратор не найдена в базе');
		}
		
		// моя учётка - не даём заказчику удалять
		// эту проверку надо убрать после сдачи работы
		if ($row['id'] == 3) {
			$ajax->error('Этого администратора пока нельзя удалить.');
		}
		
		if ($row['id'] == USER['id']) {
			$ajax->error('Вы не можете удалить собственную учётную запись.');
		}
		
		// удаляем
		$db->query("DELETE FROM `admins` WHERE `id`=$admin_id");
		
		return array('error' => 0);
	}
}