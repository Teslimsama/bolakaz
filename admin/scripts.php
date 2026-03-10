<!-- jQuery 3 -->
<script src="../bower_components/jquery/dist/jquery.min.js"></script>
<meta name="csrf-token" content="<?php echo function_exists('app_get_csrf_token') ? htmlspecialchars(app_get_csrf_token(), ENT_QUOTES, 'UTF-8') : ''; ?>">
<!-- jQuery UI 1.11.4 -->
<script src="../bower_components/jquery-ui/jquery-ui.min.js"></script>
<!-- Bootstrap 3.3.7 -->
<script src="../bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<!-- Select2 -->
<script src="../bower_components/select2/dist/js/select2.full.min.js"></script>
<!-- Moment JS -->
<script src="../bower_components/moment/moment.js"></script>
<!-- DataTables -->
<script src="../bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="../bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<!-- ChartJS -->
<script src="../bower_components/chart.js/Chart.js"></script>
<!-- daterangepicker -->
<script src="../bower_components/moment/min/moment.min.js"></script>
<script src="../bower_components/bootstrap-daterangepicker/daterangepicker.js"></script>
<!-- datepicker -->
<script src="../bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
<!-- bootstrap time picker -->
<script src="../plugins/timepicker/bootstrap-timepicker.min.js"></script>
<!-- Slimscroll -->
<script src="../bower_components/jquery-slimscroll/jquery.slimscroll.min.js"></script>
<!-- FastClick -->
<script src="../bower_components/fastclick/lib/fastclick.js"></script>
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.min.js"></script>
<!-- CK Editor -->
<script src="../bower_components/ckeditor/ckeditor.js"></script>
<!-- Active Script -->
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

    /** add active class and stay opened when selected */
    var url = window.location;

    // for sidebar menu entirely but not cover treeview
    $('ul.sidebar-menu a').filter(function() {
      return this.href == url;
    }).parent().addClass('active');

    // for treeview
    $('ul.treeview-menu a').filter(function() {
      return this.href == url;
    }).parentsUntil(".sidebar-menu > .treeview-menu").addClass('active');

  });
</script>
<!-- Data Table Initialize -->
<script>
  $(function() {
    $('#example1').DataTable({
      responsive: true
    })
    $('#example2').DataTable({
      'paging': true,
      'lengthChange': false,
      'searching': false,
      'ordering': true,
      'info': true,
      'autoWidth': false
    })
  })
</script>
<script>
  $(function() {
    //Initialize Select2 Elements
    $('.select2').select2()

    //CK Editor
    CKEDITOR.replace('editor1')
    CKEDITOR.replace('short_desc')
    CKEDITOR.replace('desc')
    CKEDITOR.replace('editor2')
    CKEDITOR.replace('edit_short_desc')
    CKEDITOR.replace('edit_desc')
  });
</script>
