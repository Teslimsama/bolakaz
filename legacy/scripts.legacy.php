<!-- jQuery 3 -->
<script src="bower_components/jquery/dist/jquery.min.js"></script>
<meta name="csrf-token" content="<?php echo function_exists('app_get_csrf_token') ? htmlspecialchars(app_get_csrf_token(), ENT_QUOTES, 'UTF-8') : ''; ?>">

<script>
	$(function() {
		const csrfToken = $('meta[name="csrf-token"]').attr('content') || '';
		$.ajaxSetup({
			headers: {
				'X-CSRF-Token': csrfToken
			}
		});

		$('form').each(function() {
			if (!$(this).find('input[name="_csrf"]').length) {
				$(this).append('<input type="hidden" name="_csrf" value="' + csrfToken + '">');
			}
		});

		// Prevent accidental duplicate actions on buttons.
		$(document).on('click', 'button, input[type="submit"], .btn', function(e) {
			var $btn = $(this);
			if ($btn.is('[data-allow-multi-click]')) {
				return;
			}
			if ($btn.data('clickLocked')) {
				e.preventDefault();
				e.stopImmediatePropagation();
				return false;
			}
			$btn.data('clickLocked', true);
			setTimeout(function() {
				$btn.data('clickLocked', false);
			}, 800);
		});

		$('form').on('submit', function() {
			var $form = $(this);
			if ($form.data('submitLocked')) {
				return false;
			}
			$form.data('submitLocked', true);
			$form.find('button[type="submit"], input[type="submit"]').each(function() {
				var $btn = $(this);
				if ($btn.is('[data-allow-multi-click]')) {
					return;
				}
				$btn.prop('disabled', true).attr('aria-disabled', 'true');
				if ($btn.is('input')) {
					$btn.val('Please wait...');
				} else {
					$btn.text('Please wait...');
				}
			});
		});

		// Datatable
		$('#example1').DataTable()
		//CK Editor
		CKEDITOR.replace('editor1')
	});
</script>
<!--Magnify -->
<script src="magnify/magnify.min.js"></script>
<script>
	$(function() {
		$('.zoom').magnify();
	});
</script>
<!-- Custom Scripts -->
<script>
	$(function() {
		$('#navbar-search-input').focus(function() {
			$('#searchBtn').show();
		});

		$('#navbar-search-input').focusout(function() {
			$('#searchBtn').hide();
		});

		getCart();

		$('#productForm').submit(function(e) {
			e.preventDefault();
			var product = $(this).serialize();
			$.ajax({
				type: 'POST',
				url: 'cart_add.php',
				data: product,
				dataType: 'json',
				success: function(response) {
					$('#callout').show();
					$('.message').html(response.message);
					if (response.error) {
						$('#callout').removeClass('alert-success').addClass('alert-danger');
						if (window.appNotify) {
							window.appNotify({
								message: response.message || 'Unable to add item to cart.',
								type: 'error',
								title: 'Add To Cart Failed'
							});
						}
					} else {
						$('#callout').removeClass('alert-danger').addClass('alert-success');
						if (window.appNotify) {
							window.appNotify({
								message: response.message || 'Item added to cart.',
								type: 'success',
								title: 'Added To Cart'
							});
						}
						getCart();
					}
				}
			});
		});

		$(document).on('click', '.close', function() {
			$('#callout').hide();
		});

	});

		function getCart() {
			$.ajax({
				type: 'POST',
				url: 'cart_fetch.php',
			dataType: 'json',
			success: function(response) {
				$('#cart_menu').html(response.list);
				$('.cart_count').html(response.count);
				}
			});
		}

		function ensureNotifyContainer() {
			var existing = document.getElementById('appNotifyContainer');
			if (existing) {
				return existing;
			}
			var container = document.createElement('div');
			container.id = 'appNotifyContainer';
			container.className = 'toast-container';
			container.style.position = 'fixed';
			container.style.top = '1rem';
			container.style.right = '1rem';
			container.style.zIndex = '1090';
			container.style.minWidth = '280px';
			document.body.appendChild(container);
			return container;
		}

		window.appNotify = function(payload, type, title) {
			var options = {};
			if (typeof payload === 'object' && payload !== null) {
				options = payload;
			} else {
				options.message = String(payload || '');
				options.type = type || 'info';
				options.title = title || 'Notice';
			}

			var message = String(options.message || '').trim();
			if (!message) {
				return;
			}

			var noticeType = String(options.type || 'info').toLowerCase();
			var noticeTitle = String(options.title || 'Notice');
			var icon = {
				success: 'fa-check-circle',
				error: 'fa-times-circle',
				danger: 'fa-times-circle',
				warning: 'fa-exclamation-triangle',
				info: 'fa-info-circle'
			}[noticeType] || 'fa-info-circle';

			var headerClass = {
				success: 'bg-success text-white',
				error: 'bg-danger text-white',
				danger: 'bg-danger text-white',
				warning: 'bg-warning text-dark',
				info: 'bg-primary text-white'
			}[noticeType] || 'bg-primary text-white';

			var container = ensureNotifyContainer();
			var toastEl = document.createElement('div');
			toastEl.className = 'toast';
			toastEl.setAttribute('role', 'status');
			toastEl.setAttribute('aria-live', 'polite');
			toastEl.setAttribute('aria-atomic', 'true');
			toastEl.style.marginBottom = '0.5rem';
			toastEl.innerHTML = ''
				+ '<div class="toast-header ' + headerClass + '">'
				+ '<i class="fa ' + icon + ' mr-2"></i>'
				+ '<strong class="mr-auto">' + $('<div>').text(noticeTitle).html() + '</strong>'
				+ '<small>just now</small>'
				+ '<button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">'
				+ '<span aria-hidden="true">&times;</span>'
				+ '</button>'
				+ '</div>'
				+ '<div class="toast-body">' + $('<div>').text(message).html() + '</div>';
			container.appendChild(toastEl);

			if (window.jQuery && typeof window.jQuery.fn.toast === 'function') {
				window.jQuery(toastEl).toast({
					delay: Number(options.delay || 3200),
					autohide: true
				}).toast('show');
				window.jQuery(toastEl).on('hidden.bs.toast', function() {
					window.jQuery(this).remove();
				});
				return;
			}

			alert(message);
		};
</script>
