<meta name="csrf-token" content="<?php echo function_exists('app_get_csrf_token') ? htmlspecialchars(app_get_csrf_token(), ENT_QUOTES, 'UTF-8') : ''; ?>">
<script src="../bower_components/jquery/dist/jquery.min.js"></script>
<script src="../bower_components/jquery-ui/jquery-ui.min.js"></script>
<script src="../bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="../bower_components/select2/dist/js/select2.full.min.js"></script>
<script src="../bower_components/moment/moment.js"></script>
<script src="../bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="../bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<script src="../bower_components/bootstrap-daterangepicker/daterangepicker.js"></script>
<script src="../bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
<script src="../plugins/timepicker/bootstrap-timepicker.min.js"></script>
<script src="../bower_components/ckeditor/ckeditor.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="assets/admin-modern.js"></script>

<script>
  (function() {
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    var token = csrfToken ? csrfToken.getAttribute('content') : '';

    if (window.jQuery) {
      $.ajaxSetup({
        headers: {
          'X-CSRF-Token': token
        }
      });

      $('form').each(function() {
        if (!$(this).find('input[name="_csrf"]').length) {
          $(this).append('<input type="hidden" name="_csrf" value="' + token + '">');
        }
      });

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

      $(function() {
        if ($('#example1').length && !$.fn.DataTable.isDataTable('#example1')) {
          $('#example1').DataTable({
            responsive: true,
            pageLength: 25,
            order: []
          });
        }
        if ($('#example2').length && !$.fn.DataTable.isDataTable('#example2')) {
          $('#example2').DataTable({
            paging: true,
            lengthChange: false,
            searching: false,
            ordering: true,
            info: true,
            autoWidth: false,
            responsive: true
          });
        }

        $('.select2').select2();

        if (window.CKEDITOR && typeof window.CKEDITOR.replace === 'function') {
          ['editor1', 'short_desc', 'desc', 'editor2', 'edit_short_desc', 'edit_desc'].forEach(function(id) {
            if (document.getElementById(id) && !window.CKEDITOR.instances[id]) {
              window.CKEDITOR.replace(id);
            }
          });
        }
      });
    }
  })();
</script>
