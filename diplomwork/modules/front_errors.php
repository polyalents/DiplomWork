<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}

class front_errors {
	public function error_404() {
		http_response_code(404);
		echo '
		<div class="container">
			<h1>404</h1>
			<p>Страница не существует.</p>
		</div>
		';
	}
	
	public function error_main() {
		echo '
		<div class="container">
			<h1>Ошибка</h1>
			<div class="error">Администратор не выбрал главную страницу.</div>
		</div>
		';
	}
}