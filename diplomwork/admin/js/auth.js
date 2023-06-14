$(document).ready(function () {
	/**
	 * Авторизация
	 */
	$(document).on('submit', '.admin-login-form', function(e) {
		e.preventDefault();

		// прячем ошибку
		$('.jsResult').html('').hide();
		// достаём поля из формы
		const login = $(this).find('input[name="login"]').val();
		const password = $(this).find('input[name="password"]').val();

		$.ajax({
			type: 'POST',
			cache: false,
			url: '/ajax.php',
			dataType: 'json',
			data: {
				'module': 'admins',
				'method': 'ajax_auth',
				'login': login,
				'password': password
			},
			success: function(result) {
				if (result['error']) {
					// ошибка - выводим ошибку в форму
					$('.jsResult').html('<div class="error">'+result['message']+'</div>').fadeIn(200);
				} else {
					// успех - перезагружаем страницу
					window.location.reload();
				}
			},
			error: function(result){
				if ((result['status']) || (result['responseText'])) {
					alert('Error: '+result['status']+': '+result['responseText']);
				}
			}
		});
	});
});