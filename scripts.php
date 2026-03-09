<?php
include_once __DIR__ . '/storefront.php';

if (!storefront_use_v2()) {
    include __DIR__ . '/legacy/scripts.legacy.php';
    return;
}
?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.js?v=<?php echo file_exists(__DIR__ . '/lib/owlcarousel/owl.carousel.js') ? filemtime(__DIR__ . '/lib/owlcarousel/owl.carousel.js') : time(); ?>"></script>
<script src="magnify/magnify.min.js"></script>
<script src="js/main.js"></script>
<script src="js/storefront-v2.js"></script>

<script>
  (function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    $.ajaxSetup({
      headers: {
        'X-CSRF-Token': csrfToken
      }
    });

    $('form').each(function () {
      if (!$(this).find('input[name="_csrf"]').length && csrfToken) {
        $(this).append('<input type="hidden" name="_csrf" value="' + csrfToken + '">');
      }
    });

    function getCart() {
      if (!$('#cart_menu').length) {
        return;
      }

      $.ajax({
        type: 'POST',
        url: 'cart_fetch.php',
        dataType: 'json',
        success: function (response) {
          $('#cart_menu').html(response.list);
          $('.cart_count').html(response.count);
        }
      });
    }

    $(document).ready(function () {
      getCart();

      $('#productForm').on('submit', function (e) {
        e.preventDefault();
        var product = $(this).serialize();

        $.ajax({
          type: 'POST',
          url: 'cart_add.php',
          data: product,
          dataType: 'json',
          success: function (response) {
            if ($('#callout').length) {
              $('#callout').show();
              $('.message').html(response.message);
              if (response.error) {
                $('#callout').removeClass('alert-success').addClass('alert-danger');
              } else {
                $('#callout').removeClass('alert-danger').addClass('alert-success');
              }
            }
            getCart();
          }
        });
      });

      $(document).on('click', '.close', function () {
        $('#callout').hide();
      });

      $('.zoom').magnify();
    });
  })();
</script>
