$(document).ready(function () {
	// плавная прокрутка для навигации
	$(document).on('click', '#site-nav a, #widget-16 .btn, .jumpToTop', function(e) {
		e.preventDefault();
		const href = $(this).attr('href');
		if (href === '#call-back') {
			return call_back();
		}

		if (href.charAt(0) !== '#') {
			window.location.href = href;
			return;
		}

		const elem = $(href);
		if (!elem.length) {
			window.location.href = href;
			return;
		}

		$('html, body').animate({
			scrollTop: elem.offset().top
		}, 1000);
	});

	// скрываем и прячем кнопку "наверх" по скроллу
	$(window).on('scroll', function() {
		if ($(document).scrollTop() > 400) {
			$('.jump-to-top-wrap').fadeIn(300);
		} else {
			$('.jump-to-top-wrap').fadeOut(300);
		}
	});

	// открытие формы на обратный звонок
	function call_back() {
		close_side_menus(false, function () {
			$('body').addClass('call-back-active');
			$('#call-back-backdrop').fadeIn(300);
			const Width = $('#call-back-content').css('width');
			$('#call-back-wrap').stop().animate({'width': Width}, 300);
		});
	}

	// закрытие формы на обратный звонок
	$(document).on('click', '#call-back-close', function() {
		close_side_menus(true);
	});

	// то же закрытие при нажатии на тёмный фон
	$(document).on('click', '#call-back-backdrop', function() {
		$('#call-back-close').trigger('click');
		$('#mobile-menu-close').trigger('click');
	});

	// открытие картинок в модальном окне
	// if ($('[data-fancybox]').length) {
	// 	Fancybox.bind('[data-fancybox]');
	// }

	// прячем все новости, кроме первой
	function resize_news(Animate) {
		if (!$('#news-wrap.under-spoiler').length) {
			return;
		}

		// находим первую новость
		let firstNewsHeight = $('#news-wrap .news-row:first-child').css('height');
		// убираем высоту нижнего бордера
		firstNewsHeight = parseFloat(firstNewsHeight) - 1;

		if (Animate) {
			// прячем с анимацией (юзер нажал на кнопку)
			$('#news-wrap').animate({'height': firstNewsHeight + 'px'}, 1000);
		} else {
			// прячем без анимации
			$('#news-wrap').css('height', firstNewsHeight + 'px');
		}
	}

	if ($('#news-wrap.under-spoiler').length) {
		// даём прогрузиться картинкам
		setTimeout(function() {
			// прячем все новости, кроме первой
			resize_news(false);
		}, 1000);
	}


	// перепрятать картинки при изменении размера окна
	$(window).on('resize', function(){
		resize_news(false);
	});

	// юзер нажал "все новости" или "скрыть новости"
	$(document).on('click', '.jsNewsSpoiler', function() {
		const wrap = $('#news-wrap');

		if ($(this).hasClass('opened')) {
			// скрытие
			$(this).removeClass('opened');
			$(this).find('span').text('Все новости');

			const offset = wrap.parents('section').first().offset().top;
			$('html, body').animate({scrollTop: offset}, 600, 'swing', function () {
				resize_news(true);
			});

		} else {
			// открытие
			$(this).addClass('opened');
			$(this).find('span').text('Скрыть новости');

			wrap.removeAttr('style');
			const Height = wrap.height();
			resize_news(false);
			wrap.animate({'height': Height}, 600);
		}
	});

	// правильный формат ввода для телефонов (библиотека Inputmask)
	$('input[name="phone"]').inputmask({"mask": "+9 (999) 999-99-99"});

	/**
	 * Заявка на обратный звонок
	 */
	$(document).on('submit', '.jsCallBack', function(e) {
		e.preventDefault();

		// прячем ошибку
		$('.jsResult').html('').hide();

		// собираем данные с формы
		const Obj = $(this);
		let data = new FormData(Obj[0]);
		data.append('module', 'calls');
		data.append('method', 'ajax_add_new');

		$.ajax({
			method: 'POST',
			cache: false,
			url: '/ajax.php',
			dataType: 'json',
			processData: false,
			contentType: false,
			data: data,
			success: function(result) {
				let messageType = '';
				if (result['error']) {
					// css-класс для красного сообщения
					messageType = 'error';
				} else {
					// css-класс для зелёного сообщения
					messageType = 'success';
					Obj.find('input, textarea, button[type="submit"]').prop('disabled', true);
				}
				$('.jsCallBack .jsResult').html('<div class="'+messageType+'">'+result['message']+'</div>').fadeIn(200);
			},
			error: function(result){
				if ((result['status']) || (result['responseText'])) {
					alert('Error: '+result['status']+': '+result['responseText']);
				}
			}
		});
	});

	// скрытие правой и левой панели так, чтобы они не конфликтовали
	function close_side_menus(hideBackdrop, successFunc) {
		// действие после закрытия панели
		if (!successFunc) {
			successFunc = (function() {
				// пустая функция по-умолчанию, просто чтобы не было ошибок
			});
		}

		const Body = $('body');

		// ещё одна функция в функции, убирает с body классы, которые наклыдывают на элементы размытие
		// левая и правая панель накидывают на body разные классы, потому что они размывают элементы по разному
		const unblurStuff = (function() {
			Body.removeClass('mobile-menu-active');
			Body.removeClass('call-back-active');
		});

		// селектор, которому надо анимировать ширину до 0
		let animateSelector = '';
		// если мобильное меню открыто
		if (Body.hasClass('mobile-menu-active')) {
			animateSelector = '#site-nav';
		}

		// если заявка на обратный звонок открыта
		if (Body.hasClass('call-back-active')) {
			animateSelector = '#call-back-wrap';
		}

		// скрываем затемнение, если нужно
		if (hideBackdrop) {
			$('#call-back-backdrop').fadeOut(300);
			// убираем размытие
			unblurStuff();
		}

		// если есть что скрывать
		if (animateSelector) {
			// анимируем скрытие панели
			$(animateSelector).stop().animate({'width': 0}, 300, 'swing', function () {
				// действие уже после завершения этой анимации
				// убираем размытие
				unblurStuff();
				// выполняем следующую функцию
				// тут на body накладывается правильный класс размытия и происходит анимация
				successFunc();
			});
		} else {
			// нечего скрывать
			// выполняем следующую функцию
			// тут на body накладывается правильный класс размытия и происходит анимация
			successFunc();
		}
	}

	// открытие мобильной навигации
	$(document).on('click', '.jsMobileNav', function() {
		close_side_menus(false, function () {
			$('body').addClass('mobile-menu-active');
			$('#call-back-backdrop').fadeIn(300);
			const Width = $('#site-nav ul').css('width');
			$('#site-nav').stop().animate({'width': Width}, 300);
		});
	});

	// закрытие мобильной навигации
	$(document).on('click', '#mobile-menu-close', function() {
		close_side_menus(true);
	});

	// закрытие мобильной навигации при нажатии на ссылку
	// кроме кнопки для заявки на обратный звонок
	$(document).on('click', 'body.mobile-menu-active #site-nav a', function() {
		const href = $(this).attr('href');
		if (href === '#call-back') {
			return;
		}
		close_side_menus(true);
	});

	// ленивая загрузка карты (попытка ускорить сайт в синтетических тестах)
	let lazy = $('iframe[lazy]');

	if (lazy.length) {
		function lazy_elements() {
			if (!lazy.length) {
				return;
			}

			const offsetTop = $(document).scrollTop() + $(window).height();

			lazy.each(function () {
				// console.log(offsetTop + ' - ' + $(this).offset().top);

				if (offsetTop > $(this).offset().top) {
					$(this).attr('src', $(this).attr('lazy')).removeAttr('lazy');
					lazy = $('iframe[lazy]');
				}
			});
		}

		$(document).on( 'scroll', function(){
			lazy_elements();
		});

		lazy_elements();
	}



});