<!-- jQuery 3 -->
<script src="bower_components/jquery/dist/jquery.min.js"></script>

<script>
	$(function() {
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