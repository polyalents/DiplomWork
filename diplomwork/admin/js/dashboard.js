$(document).ready(function () {
	// попытка упростить ajax от jQuery
	// все формы и действия обрабатываются через AJAX, потому что так проще выводить ошибки -
	// для проверки всех ошибок через PHP не надо переходить на другую страницу
	// и мы больше нигде не упираемся в ошибку "headers already sent"
	function run_ajax(data, successFunc) {
		if (!successFunc) {
			// мои стандартные действия при успешном выполнении
			successFunc = (function(result) {
				if ($('body').hasClass('modal-open')) {
					hide_modal();
				}
				window.location.reload();
			});
		}

		$.ajax({
			method: 'POST',
			cache: false,
			url: '/admin/ajax.php',
			dataType: 'json',
			processData: false,
			contentType: false,
			data: data,
			success: function(result) {
				if (result['error']) {
					alert(result['message']);
				} else {
					successFunc(result);
				}
			},
			error: function(result){
				if ((result['status']) || (result['responseText'])) {
					alert('Error: '+result['status']+': '+result['responseText']);
				}
			}
		});
	}

	// показать модальное окно (вёрстка лежит в dashboard.php)
	function show_modal(content, big_mode) {
		const myModal = $('#modal');
		if (big_mode) {
			myModal.addClass('big-mode');
		} else {
			myModal.removeClass('big-mode');
		}
		$('#modal-content').html(content);
		myModal.fadeIn(300);
		$('body').addClass('modal-open');
	}

	// скрыть модальное окно
	function hide_modal() {
		$('#modal').fadeOut(300, function () {
			$('#modal-content').html('');
		});
		$('body').removeClass('modal-open');
	}

	// закрытие модального окна по кнопке
	$(document).on('click', '.jsCloseModal', function() {
		hide_modal();
	});

	// закрытие модального окна по клику на тёмный фон
	$(document).on('click', '#modal', function(e) {
		if (e.target !== this) {
			return;
		}
		hide_modal();
	});

	// редактор для текста новости
	function init_editor() {
		const editr = $('#editor');
		if (!editr.length) {
			return;
		}
		// docs: https://ckeditor.com/docs/ckeditor5/latest/installation/getting-started/quick-start.html#running-a-simple-editor
		ClassicEditor
			.create(editr[0], {
				// выбираем какие заголовки может добавлять пользователь
				// docs: https://ckeditor.com/docs/ckeditor5/latest/features/headings.html#heading-levels
				heading: {
					options: [
						{model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph'},
						{model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1'},
						{model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2'},
						{model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3'},
						{model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4'},
						{model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5'},
						{model: 'heading6', view: 'h6', title: 'Heading 6', class: 'ck-heading_heading6'},
					]
				}
			}).then(editor => {
				// фокус на редакторе после его инициализации
				editor.editing.view.focus();
			})
			.catch(error => {
				console.error(error);
			});
	}
	// сразу инициализируем для страницы редактирования новости
	init_editor();

	// кликабельные tr в новостях
	$(document).on('click', '.jsLinkHolderTable tr', function(e) {
		if ($(e.target).hasClass('jsDeleteNews') ||
			$(e.target).hasClass('jsLinkHolder') ||
			$(e.target).hasClass('jsDeletePage') ||
			$(e.target).hasClass('jsDeleteAdmin')) {
			return;
		}
		window.location.href = $(this).find('a.jsLinkHolder').first().attr('href');
	});

	// добавление новости
	$(document).on('submit', '.jsNewsAddSubmit', function(e) {
		e.preventDefault();
		let data = new FormData($(this)[0]);
		data.append('module', 'news');
		data.append('method', 'ajax_add_new');
		run_ajax(data, function (result) {
			// переадресация на редактирование
			window.location.href = '/admin/?mode=news&id='+result['id'];
		});
	});

	// сохранение новости
	$(document).on('submit', '.jsNewsEditSubmit', function(e) {
		e.preventDefault();
		let data = new FormData($(this)[0]);
		data.append('module', 'news');
		data.append('method', 'ajax_edit_save');
		run_ajax(data);
	});

	// удаление новости
	$(document).on('click', '.jsDeleteNews', function() {
		if (confirm('Новость будет удалена безвозвратно. Вы уверены?')) {
			let data = new FormData();
			data.append('module', 'news');
			data.append('method', 'ajax_delete_news');
			data.append('id', $(this).attr('data-id'));
			run_ajax(data);
		}
	});

	// сортировка секций
	// docs: https://jqueryui.com/sortable/
	$('.page-section-wrap').sortable({
		handle: ".jsSectionHandle",
		placeholder: "ui-state-highlight",
		axis: "y",
		stop: function( event, ui ) {
			let ids = '';
			$('.page-section-wrap > section').each(function () {
				ids = ids + '-' + $(this).attr('data-id');
			});

			let data = new FormData();
			data.append('module', 'pages');
			data.append('method', 'ajax_sections_sort');
			data.append('order', ids);
			$('.hovered').removeClass('hovered');
			run_ajax(data, function(result) {
				// не выполняем стандартное действие с перезагрузкой страницы при успехе
			});
		}
	});

	// сортировка рядов
	// docs: https://jqueryui.com/sortable/
	$('.section-content').sortable({
		handle: ".jsRowHandle",
		placeholder: "ui-state-highlight",
		axis: "y",
		connectWith: '.section-content',
		stop: function( event, ui ) {
			let ids = '';
			ui['item'].parents('.section-content').first().find('.section-row').each(function () {
				ids = ids + '-' + $(this).attr('data-id');
			});

			let data = new FormData();
			data.append('module', 'pages');
			data.append('method', 'ajax_rows_sort');
			data.append('order', ids);
			data.append('section_id', ui['item'].parents('.page-row').first().attr('data-id'));
			$('.hovered').removeClass('hovered');
			run_ajax(data, function(result) {
				// не выполняем стандартное действие с перезагрузкой страницы при успехе
			});
		}
	});

	function init_widgets_sort() {
		// перемещение виджетов
		// docs: https://jqueryui.com/sortable/
		$('.section-content-dropzone').sortable({
			handle: ".jsWidgetHandle",
			items: ".widget-content",
			placeholder: "ui-state-highlight",
			connectWith: '.section-content-dropzone',
			start: function( event, ui ) {
				// раскомментить, если надо чтобы в одной колонке был только один виджет
				// ui['item'].addClass('iDroppedThis');
				// ui['item'].parent('.section-content-dropzone').addClass('iDroppedFromHere');
			},
			stop: function( event, ui ) {
				// раскомментить, если надо чтобы в одной колонке был только один виджет
				// const moveThis = ui['item'].parent('.section-content-dropzone')
				// 	.find('.widget-content:not(.iDroppedThis)').appendTo('.iDroppedFromHere');
				// $('.iDroppedThis, .iDroppedFromHere').removeClass('iDroppedThis iDroppedFromHere');

				let ids = '';
				ui['item'].parents('.section-content-dropzone').first().find('.widget-content').each(function () {
					ids = ids + '-' + $(this).attr('data-widget-id');
				});

				let data = new FormData();
				data.append('module', 'pages');
				data.append('method', 'ajax_widgets_sort');
				data.append('order', ids);
				data.append('row_id', ui['item'].parents('.section-row').first().attr('data-id'));
				data.append('column_numb', ui['item'].parents('.section-col').first().attr('data-numb'));
				$('.hovered').removeClass('hovered');
				run_ajax(data, function(result) {
					// не выполняем стандартное действие с перезагрузкой страницы при успехе
				});
			}
		});
	}
	init_widgets_sort();

	// добавление ряда в страницу
	$(document).on('click', '.jsNewRow', function() {
		let data = new FormData();
		data.append('module', 'pages');
		data.append('method', 'ajax_add_row_form');
		data.append('section_id', $(this).parents('section.page-row').first().attr('data-id'));
		run_ajax(data, function (result) {
			show_modal(result['html']);
		});
	});

	// добавление ряда в страницу - юзер подтвердил выбор
	$(document).on('submit', '.jsNewRowSubmit', function(e) {
		e.preventDefault();
		let data = new FormData($(this)[0]);
		data.append('module', 'pages');
		data.append('method', 'ajax_plus_row');

		run_ajax(data);
	});

	// удаление ряда из страницы
	$(document).on('click', '.jsDeleteRow', function() {
		if (confirm('Ряд будет удалён со всеми виджетами внутри. Вы уверены?')) {
			let data = new FormData();
			data.append('module', 'pages');
			data.append('method', 'ajax_row_delete');
			data.append('row_id', $(this).parents('.section-row').first().attr('data-id'));
			run_ajax(data);
		}
	});

	// добавление секции
	$(document).on('click', '.jsNewSection', function() {
		let data = new FormData();
		data.append('module', 'pages');
		data.append('method', 'ajax_add_section_form');
		data.append('page_id', $('.jsPageIdHolder').attr('data-id'));
		run_ajax(data, function (result) {
			show_modal(result['html']);
		});
	});

	// добавление секции - юзер подтвердил выбор
	$(document).on('submit', '.jsNewSectionSubmit', function(e) {
		e.preventDefault();
		let data = new FormData($(this)[0]);
		data.append('module', 'pages');
		data.append('method', 'ajax_section_add');
		data.append('page_id', $('.jsPageIdHolder').attr('data-id'));
		run_ajax(data);
	});

	// удаление секции
	$(document).on('click', '.jsDeleteSection', function() {
		if (confirm('Секция будет удалена со всеми виджетами. Вы уверены?')) {
			let data = new FormData();
			data.append('module', 'pages');
			data.append('method', 'ajax_section_delete');
			data.append('section_id', $(this).parents('section.page-row').first().attr('data-id'));
			run_ajax(data);
		}
	});

	// добавление виджета
	$(document).on('click', '.jsNewWidget', function() {
		const Obj = $(this);
		let data = new FormData();
		data.append('module', 'pages');
		data.append('method', 'ajax_add_widget_form');
		data.append('page_id', $('.jsPageIdHolder').attr('data-id'));
		run_ajax(data, function (result) {
			show_modal(result['html']);
			// в каком ряду юзер нажал кнопку
			const row_id = Obj.parents('.section-row').first().attr('data-id');
			// в какой по счёте колонке юзер нажал кнопку
			const col_numb = Obj.parents('.section-col').first().attr('data-numb');
			// в форму надо добавить эти дополнительные параметры
			const modalForm = $('.jsNewWidgetSubmit');
			modalForm.append('<input type="hidden" name="row_id" value="'+row_id+'">');
			modalForm.append('<input type="hidden" name="col_numb" value="'+col_numb+'">');
		});
	});

	// добавление виджета - юзер подтвердил выбор
	$(document).on('submit', '.jsNewWidgetSubmit', function(e) {
		e.preventDefault();

		let data = new FormData($(this)[0]);
		data.append('module', 'pages');
		data.append('method', 'ajax_widget_add');
		data.append('page_id', $('.jsPageIdHolder').attr('data-id'));
		run_ajax(data, function (result) {
			const row = $('.section-row[data-id="'+result['row_id']+'"]');
			if (!row.length) {
				window.location.reload();
			}

			const col = row.find('.section-col[data-numb="'+result['column_numb']+'"] .section-content-dropzone');
			if (!col.length) {
				window.location.reload();
			}

			col.append(result['widget_html']);

			const widget = col.find('.widget-content[data-widget-id="'+result['widget_id']+'"]');
			if (!widget.length) {
				window.location.reload();
			}

			init_widgets_sort();
			widget.find('.jsEditWidget').trigger('click');
		});
	});

	// удаление виджета
	$(document).on('click', '.jsDeleteWidget', function() {
		if (confirm('Виджет будет удалён. Вы уверены?')) {

			let data = new FormData();
			data.append('module', 'pages');
			data.append('method', 'ajax_widget_delete');
			data.append('widget_id', $(this).parents('.widget-content').first().attr('data-widget-id'));
			run_ajax(data);
		}
	});

	// модальное окно с редактированием виджета
	$(document).on('click', '.jsEditWidget', function() {
		let data = new FormData();
		data.append('module', 'pages');
		data.append('method', 'ajax_edit_widget_form');
		data.append('widget_id', $(this).parents('.widget-content').first().attr('data-widget-id'));
		run_ajax(data, function (result) {
			show_modal(result['html'], true);
			$('#modal form').find('.editor-preload').attr('id', 'editor');
			init_editor();
			$('#modal').find('textarea.jsCodeContainer, input[type="text"]').first().focus();
		});
	});

	// сохранение редактирования виджета
	$(document).on('submit', '.jsEditWidgetSubmit', function(e) {
		e.preventDefault();

		let data = new FormData($(this)[0]);
		data.append('module', 'pages');
		data.append('method', 'ajax_save_widget');
		run_ajax(data);
	});

	// сортировка навигации
	$('.jsNav').sortable({
		handle: ".jsNavHandle",
		axis: "y",
		stop: function( event, ui ) {
			// навигация сохраняется по нажатию на кнопку "сохранить"
			// тут мы ничего не сохраняем, а просто меняем значения скрытых инпутов, которые содержат order
			let count = 0;
			$('.jsNav .jsOrderHolder').each(function () {
				$(this).attr('value', count).prop('value', count);
				count++;
			});
		}
	});

	// удаление элемента навигации
	$(document).on('click', '.jsNavDelete', function() {
		if (confirm('Элемент будет удалён. Вы уверены?')) {
			let data = new FormData();
			data.append('module', 'nav');
			data.append('method', 'ajax_nav_delete');
			data.append('id', $(this).parents('li').first().attr('data-id'));
			run_ajax(data);
		}
	});

	// добавление элемента навигации (вызов формы)
	$(document).on('click', '.jsNavAdd', function(e) {
		e.preventDefault();
		let data = new FormData();
		data.append('module', 'nav');
		data.append('method', 'ajax_add_form');
		run_ajax(data, function (result) {
			show_modal(result['html']);
		});
	});

	// добавление элемента навигации (сохранение)
	$(document).on('submit', '.jsNavAddSubmit', function(e) {
		e.preventDefault();

		let data = new FormData($(this)[0]);
		data.append('module', 'nav');
		data.append('method', 'ajax_add');
		run_ajax(data);
	});

	// сохранение новости
	$(document).on('submit', '.jsSaveNav', function(e) {
		e.preventDefault();
		let data = new FormData($(this)[0]);
		data.append('module', 'nav');
		data.append('method', 'ajax_save');
		run_ajax(data);
	});

	// виджет "кнопка" - делаем чтобы ссылка в админке не работала
	$(document).on('click', '.jsPreviewBtn', function(e) {
		e.preventDefault();
	});

	// подсветка элементов страницы при наведении
	$(document).on('mouseenter', '.page-ids, .page-tools i, .jsNewRow, .jsNewWidget', function() {
		$(this).parents('.page-row, .section-row, .section-col, .widget-content').first().addClass('hovered');
	});

	$(document).on('mouseleave', '.page-ids, .page-tools i, .jsNewRow, .jsNewWidget', function() {
		$(this).parents('.page-row, .section-row, .section-col, .widget-content').first().removeClass('hovered');
	});

	// добавление администратора
	$(document).on('submit', '.jsAdminAddSubmit', function(e) {
		e.preventDefault();
		let data = new FormData($(this)[0]);
		data.append('module', 'admins');
		data.append('method', 'ajax_add_new');
		run_ajax(data, function (result) {
			// переадресация на редактирование
			window.location.href = '/admin/?mode=admins&id='+result['id'];
		});
	});

	// сохранение администратора
	$(document).on('submit', '.jsAdminEditSubmit', function(e) {
		e.preventDefault();
		let data = new FormData($(this)[0]);
		data.append('module', 'admins');
		data.append('method', 'ajax_edit_save');
		run_ajax(data);
	});

	// удаление админа
	$(document).on('click', '.jsDeleteAdmin', function() {
		if (confirm('Администратор будет удалён безвозвратно. Вы уверены?')) {
			let data = new FormData();
			data.append('module', 'admins');
			data.append('method', 'ajax_delete');
			data.append('id', $(this).attr('data-id'));
			run_ajax(data);
		}
	});

	// сохранение настроек
	$(document).on('submit', '.jsSettingsTable', function(e) {
		e.preventDefault();
		let data = new FormData($(this)[0]);
		data.append('module', 'settings');
		data.append('method', 'ajax_save');
		run_ajax(data);
	});

	// удаление заявки на обратный звонок
	$(document).on('click', '.jsDeleteCall', function() {
		if (confirm('Заявка будет безвозвратно удалена. Вы уверены?')) {
			let data = new FormData();
			data.append('module', 'calls');
			data.append('method', 'ajax_delete');
			data.append('id', $(this).attr('data-id'));
			run_ajax(data);
		}
	});

	// заявка в прочитанные
	$(document).on('click', '.jsReadCall', function() {
		let data = new FormData();
		data.append('module', 'calls');
		data.append('method', 'ajax_to_read');
		data.append('id', $(this).attr('data-id'));
		run_ajax(data);
	});

	// заявка в не прочитанные
	$(document).on('click', '.jsUnreadCall', function() {
		let data = new FormData();
		data.append('module', 'calls');
		data.append('method', 'ajax_to_unread');
		data.append('id', $(this).attr('data-id'));
		run_ajax(data);
	});


	// не даём вписывать ссылку, если страница отмечена как "главная"
	$(document).on('change', 'input[name="main"]', function() {
		if ($(this).prop('checked')) {
			$('input[name="link"]').prop('disabled', true);
		} else {
			$('input[name="link"]').prop('disabled', false);
		}
	});

	// сохранение свойств страницы
	$(document).on('submit', '.jsSavePage', function(e) {
		e.preventDefault();
		let data = new FormData($(this)[0]);
		data.append('module', 'pages');
		data.append('method', 'ajax_save_page');
		data.append('page_id', $('.jsPageIdHolder').attr('data-id'));
		run_ajax(data);
	});

	// добавление новой страницы (форма)
	$(document).on('click', '.jsNewPageBtn', function(e) {
		e.preventDefault();
		let data = new FormData();
		data.append('module', 'pages');
		data.append('method', 'ajax_new_page');
		run_ajax(data, function (result) {
			show_modal(result['html']);
		});
	});

	// добавление новой страницы (обработчик)
	$(document).on('submit', '.jsNewPage', function(e) {
		e.preventDefault();
		let data = new FormData($(this)[0]);
		data.append('module', 'pages');
		data.append('method', 'ajax_new_page_submit');
		run_ajax(data, function (result) {
			// переадресация на редактирование
			window.location.href = '/admin/?mode=pages&id='+result['id'];
		});
	});

	// удаление страницы
	$(document).on('click', '.jsDeletePage', function() {
		if (confirm('Страница будет безвозвратно удалена. Вы уверены?')) {
			let data = new FormData();
			data.append('module', 'pages');
			data.append('method', 'ajax_delete_page');
			data.append('id', $(this).attr('data-id'));
			run_ajax(data);
		}
	});

	// редактирование стилей страницы
	if ($('#aceEditor').length) {
		// https://ace.c9.io/#nav=howto
		let editor = ace.edit("aceEditor");
		editor.setTheme("ace/theme/merbivore_soft");
		editor.setOptions({
			showGutter: true,
			useWorker: true,
			enableBasicAutocompletion: true,
			enableLiveAutocompletion: true,
			fontSize: "13pt",
			mode: "ace/mode/css",
		});
		editor.focus();

		// сохранение стилей без перезагрузки страницы
		$(document).on('click', '.jsSaveCSS', function() {
			$('.jsSaveCSS').removeClass('saved');

			let data = new FormData();
			data.append('module', 'pages');
			data.append('method', 'ajax_save_css');
			data.append('css', editor.session.getValue());
			data.append('page_id', encodeURIComponent($('.jsPageIdHolder').attr('data-id')));
			run_ajax(data, function () {
				$('.jsSaveCSS').addClass('saved');
			});
		});

		// сохранение по комбинации Ctrl+S
		$(document).bind('keydown', function(e){
			if (e.ctrlKey && e.which === 83) {
				e.preventDefault();
				$('.jsSaveCSS').trigger('click');
			}
		});
	}

	// ленивая загрузка карты (попытка ускорить сайт в синтетических тестах - в админке не нужно)
	$('iframe[lazy]').each(function () {
		$(this).attr('src', $(this).attr('lazy')).removeAttr('lazy');
	});
});