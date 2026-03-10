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
      function showProductCallout(message, isError) {
        if (!$('#callout').length) {
          return;
        }
        $('#callout').show();
        $('.message').html(message);
        if (isError) {
          $('#callout').removeClass('alert-success').addClass('alert-danger');
        } else {
          $('#callout').removeClass('alert-danger').addClass('alert-success');
        }
      }

      function unlockFormSubmission($form) {
        $form.data('submitLocked', false);
        $form.find('button[type="submit"], input[type="submit"]').each(function () {
          var $btn = $(this);
          $btn.prop('disabled', false).removeAttr('aria-disabled');
          var originalText = $btn.data('originalText');
          if (originalText) {
            if ($btn.is('input')) {
              $btn.val(originalText);
            } else {
              $btn.text(originalText);
            }
          }
        });
      }

      // Prevent accidental duplicate actions on buttons.
      $(document).on('click', 'button, input[type="submit"], .btn', function (e) {
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
        setTimeout(function () {
          $btn.data('clickLocked', false);
        }, 800);
      });

      $('form').on('submit', function () {
        var $form = $(this);
        if ($form.data('submitLocked')) {
          return false;
        }
        $form.data('submitLocked', true);

        $form.find('button[type="submit"], input[type="submit"]').each(function () {
          var $btn = $(this);
          if ($btn.is('[data-allow-multi-click]')) {
            return;
          }
          $btn.prop('disabled', true).attr('aria-disabled', 'true');
          if (!$btn.data('originalText')) {
            $btn.data('originalText', $btn.is('input') ? $btn.val() : $btn.text());
          }
          if ($btn.is('input')) {
            $btn.val('Please wait...');
          } else {
            $btn.text('Please wait...');
          }
        });
      });

      getCart();

      $('#productForm').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        var product = $form.serialize();
        var hasSizeOptions = $form.find('input[name="size"]').length > 0;
        var hasColorOptions = $form.find('input[name="color"]').length > 0;
        var selectedSize = $form.find('input[name="size"]:checked').val() || '';
        var selectedColor = $form.find('input[name="color"]:checked').val() || '';

        if (hasSizeOptions && selectedSize === '') {
          showProductCallout('Please choose a size.', true);
          unlockFormSubmission($form);
          return;
        }

        if (hasColorOptions && selectedColor === '') {
          showProductCallout('Please choose a color.', true);
          unlockFormSubmission($form);
          return;
        }

        $.ajax({
          type: 'POST',
          url: 'cart_add.php',
          data: product,
          dataType: 'json',
          success: function (response) {
            showProductCallout(response.message, !!response.error);
            getCart();
          },
          error: function () {
            showProductCallout('Unable to add item right now. Please try again.', true);
          },
          complete: function () {
            unlockFormSubmission($form);
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
