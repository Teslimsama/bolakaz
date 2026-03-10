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
					} else {
						$('#callout').removeClass('alert-danger').addClass('alert-success');
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
</script>
