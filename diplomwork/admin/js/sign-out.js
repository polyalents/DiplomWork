$(document).ready(function () {
	/**
	 * Выход из админки
	 * Вынесено в отдельный файл потому что используется и в админке и на фронте
	 */
	$(document).on('click', '.jsSignOut', function() {
		$.ajax({
			type: 'POST',
			cache: false,
			url: '/admin/ajax.php',
			dataType: 'json',
			data: {
				'module': 'admins',
				'method': 'ajax_sign_out'
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