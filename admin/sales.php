<?php include 'session.php'; ?>
<?php include 'header.php'; ?>

<body class="hold-transition skin-blue sidebar-mini">
  <div class="wrapper">

    <?php include 'navbar.php'; ?>
    <?php include 'menubar.php'; ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <section class="content-header">
        <h1>
          Sales History
        </h1>
        <ol class="breadcrumb">
          <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
          <li class="active">Sales</li>
        </ol>
      </section>

      <!-- Main content -->
      <section class="content">
        <div class="row">
          <div class="col-xs-12">
            <div class="box">
              <div class="box-header with-border admin-list-toolbar">
                <div class="admin-list-toolbar-filters">
                  <div class="form-inline">
                    <div class="form-group">
                      <label for="filter_status" class="sr-only">Status</label>
                      <select id="filter_status" class="form-control input-sm">
                        <option value="">All Statuses</option>
                        <option value="success">Success</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                      </select>
                    </div>
                    <div class="form-group" style="margin-left:8px;">
                      <label for="filter_payment" class="sr-only">Payment Type</label>
                      <select id="filter_payment" class="form-control input-sm">
                        <option value="">All Payments</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="paystack">Paystack</option>
                        <option value="flutterwave">Flutterwave</option>
                        <option value="other">Other</option>
                      </select>
                    </div>
                    <button type="button" id="clear_sales_filters" class="btn btn-default btn-sm" style="margin-left:8px;">Reset</button>
                  </div>
                </div>
                <div class="admin-list-toolbar-main">
                  <form method="POST" class="form-inline" action="sales_print.php">
                    <div class="input-group">
                      <div class="input-group-addon">
                        <i class="fa fa-calendar"></i>
                      </div>
                      <input type="text" class="form-control pull-right col-sm-8" id="reservation" name="date_range">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" name="print"><span class="glyphicon glyphicon-print"></span> Print Report</button>
                  </form>
                </div>
              </div>
              <div class="box-body table-responsive">
                <table id="example1" class="table table-bordered">
                  <thead>
                    <th class="hidden"></th>
                    <th>Date</th>
                    <th>Buyer Name</th>
                    <th>Transaction#</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Full Details</th>
                  </thead>
                  <tbody>
                    <?php
                    $conn = $pdo->open();

                    try {
                      $stmt = $conn->prepare("SELECT
                          sales.id AS salesid,
                          sales.sales_date,
                          sales.tx_ref,
                          sales.Status,
                          users.firstname,
                          users.lastname,
                          COALESCE(SUM(details.quantity * products.price), 0) AS order_total
                        FROM sales
                        LEFT JOIN users ON users.id = sales.user_id
                        LEFT JOIN details ON details.sales_id = sales.id
                        LEFT JOIN products ON products.id = details.product_id
                        GROUP BY sales.id, sales.sales_date, sales.tx_ref, sales.Status, users.firstname, users.lastname
                        ORDER BY sales.sales_date DESC, sales.id DESC");
                      $stmt->execute();

                      foreach ($stmt as $row) {
                        $status = strtolower(trim((string)($row['Status'] ?? 'pending')));
                        if ($status === 'successful') {
                          $status = 'success';
                        }

                        $txRef = (string)($row['tx_ref'] ?? '');
                        $isBankTransfer = (strpos($txRef, 'BKBTRF-') === 0);
                        $paymentType = 'other';
                        if ($isBankTransfer) {
                          $paymentType = 'bank_transfer';
                        } elseif (strpos($txRef, 'BKPAY-') === 0) {
                          $paymentType = 'paystack';
                        } elseif (strpos($txRef, 'BKFLW-') === 0) {
                          $paymentType = 'flutterwave';
                        }
                        $isBankConfirmed = ($isBankTransfer && $status === 'success');
                        $statusLabel = ucfirst($status);

                        $buyerName = trim((string)($row['firstname'] ?? '') . ' ' . (string)($row['lastname'] ?? ''));
                        if ($buyerName === '') {
                          $buyerName = 'Guest';
                        }

                        $statusHtml = "<button class='status-toggle btn btn-sm admin-status-button' data-id='" . (int)$row['salesid'] . "' data-status='" . e($status) . "'>" . e($statusLabel) . "</button>";

                        if ($isBankTransfer) {
                          if ($isBankConfirmed) {
                            $statusHtml = "<span class='js-sales-status-static admin-status-pill' data-status='confirmed'>Confirmed</span>";
                          } else {
                            $statusHtml = "
                              <div class='admin-sales-bank-status'>
                                <span class='js-sales-status-static admin-status-pill is-pending' data-status='pending'>Pending</span>
                                <button class='btn btn-primary btn-sm confirm-bank' data-id='" . (int)$row['salesid'] . "'>Confirm Payment</button>
                              </div>
                            ";
                          }
                        }

                        echo "
                          <tr data-status='" . e($status) . "' data-payment='" . e($paymentType) . "'>
                            <td class='hidden'></td>
                            <td>" . date('M d, Y', strtotime($row['sales_date'])) . "</td>
                            <td>" . e($buyerName) . "</td>
                            <td>" . e($txRef) . "</td>
                            <td>" . $statusHtml . "</td>
                            <td>" . app_money($row['order_total']) . "</td>
                            <td><button type='button' class='btn btn-info btn-sm btn-flat transact' data-id='" . (int)$row['salesid'] . "'><i class='fa fa-search'></i> View</button></td>
                          </tr>
                        ";
                      }
                    } catch (PDOException $e) {
                      echo e($e->getMessage());
                    }

                    $pdo->close();
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div>
    <?php include 'footer.php'; ?>
    <div class="modal fade" id="transaction">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title"><b>Transaction Full Details</b></h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body table-responsive">
            <p>
              Date: <span id="date"></span>
              <span class="pull-right">Transaction#: <span id="transid"></span></span>
            </p>
            <table class="table table-bordered">
              <thead>
                <th>Product</th>
                <th width="20%">Price</th>
                <th width="2%">Quantity</th>
                <th width="25%">Subtotal</th>
              </thead>
              <tbody id="detail">
                <tr>
                  <td colspan="3" align="left"><b>Total</b></td>
                  <td><span id="total"></span></td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">
              <i class="fa fa-close"></i> Close
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
  <!-- ./wrapper -->

  <?php include 'scripts.php'; ?>
  <!-- Date Picker -->
  <script>
    $(function() {
      $('#datepicker_add').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd'
      });
      $('#datepicker_edit').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd'
      });

      $('.timepicker').timepicker({
        showInputs: false
      });

      $('#reservation').daterangepicker();
      $('#reservationtime').daterangepicker({
        timePicker: true,
        timePickerIncrement: 30,
        format: 'MM/DD/YYYY h:mm A'
      });

      $('#daterange-btn').daterangepicker({
          ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
          },
          startDate: moment().subtract(29, 'days'),
          endDate: moment()
        },
        function(start, end) {
          $('#daterange-btn span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
        }
      );
    });
  </script>
  <script>
    $(function() {
      $(document).on('click', '.transact', function(e) {
        e.preventDefault();
        if (!$('#transaction').length) {
          alert('Transaction modal is unavailable on this page.');
          return;
        }
        $('#transaction').modal('show');
        var id = $(this).data('id');
        $.ajax({
          type: 'POST',
          url: 'transact.php',
          data: {
            id: id
          },
          dataType: 'json',
          success: function(response) {
            $('#date').html(response.date || '');
            $('#transid').html(response.transaction || '');
            $('#detail').prepend(response.list || '');
            $('#total').html(response.total || '');
          },
          error: function() {
            alert('Unable to load transaction details.');
          }
        });
      });

      $('#transaction').on('hidden.bs.modal', function() {
        $('.prepend_items').remove();
      });
    });
  </script>
  <script>
    $(function() {
      function getSalesStatusClass(status) {
        var normalized = String(status || '').toLowerCase().replace(/\s+/g, ' ').trim();
        if (normalized === 'successful') {
          normalized = 'success';
        }
        if (normalized === 'not verified') {
          return 'is-not-verified';
        }
        if (normalized === 'failed' || normalized === 'error') {
          return 'is-failed';
        }
        if (normalized === 'success') {
          return 'is-success';
        }
        if (normalized === 'confirmed') {
          return 'is-confirmed';
        }
        if (normalized === 'pending') {
          return 'is-pending';
        }
        if (normalized === 'active') {
          return 'is-active';
        }
        if (normalized === 'archived') {
          return 'is-archived';
        }
        return 'is-info';
      }

      function applySalesStatusPill($button, status) {
        if (!$button || !$button.length) {
          return;
        }
        var statusClass = getSalesStatusClass(status);
        var existing = ($button.attr('class') || '').split(/\s+/);
        existing.forEach(function(cls) {
          if (cls.indexOf('is-') === 0) {
            $button.removeClass(cls);
          }
        });
        $button.addClass('admin-status-pill admin-status-button ' + statusClass);
        $button.removeAttr('style');
      }

      var salesTable = $.fn.dataTable.isDataTable('#example1') ? $('#example1').DataTable() : null;

      $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!salesTable || settings.nTable.id !== 'example1') {
          return true;
        }

        var selectedStatus = ($('#filter_status').val() || '').toLowerCase();
        var selectedPayment = ($('#filter_payment').val() || '').toLowerCase();
        var rowNode = settings.aoData && settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
        if (!rowNode) {
          return true;
        }

        var rowStatus = String($(rowNode).data('status') || '').toLowerCase();
        var rowPayment = String($(rowNode).data('payment') || '').toLowerCase();

        if (selectedStatus && rowStatus !== selectedStatus) {
          return false;
        }
        if (selectedPayment && rowPayment !== selectedPayment) {
          return false;
        }
        return true;
      });

      $('#filter_status, #filter_payment').on('change', function() {
        if (salesTable) {
          salesTable.draw();
        }
      });

      $('#clear_sales_filters').on('click', function() {
        $('#filter_status').val('');
        $('#filter_payment').val('');
        if (salesTable) {
          salesTable.draw();
        }
      });

      $(document).on('click', '.confirm-bank', function() {
        var button = $(this);
        var id = button.data('id');

        if (!confirm('Confirm this bank transfer and deduct stock now?')) {
          return;
        }

        button.prop('disabled', true).text('Confirming...');

        $.ajax({
          type: 'POST',
          url: 'confirm_bank_transfer.php',
          data: {
            id: id
          },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              window.location.reload();
            } else {
              alert('Error: ' + response.message);
              button.prop('disabled', false).text('Confirm Payment');
            }
          },
          error: function() {
            alert('An error occurred while confirming payment.');
            button.prop('disabled', false).text('Confirm Payment');
          }
        });
      });

      $(document).on('click', '.status-toggle', function() {
        var button = $(this);
        var id = button.data('id');
        var currentStatus = String(button.data('status') || '').toLowerCase();
        if (currentStatus === 'successful') {
          currentStatus = 'success';
        }

        var statuses = ['success', 'pending', 'failed'];
        var currentIndex = statuses.indexOf(currentStatus);
        if (currentIndex < 0) {
          currentIndex = 0;
        }
        var nextStatus = statuses[(currentIndex + 1) % statuses.length];

        $.ajax({
          type: 'POST',
          url: 'pending_change.php',
          data: {
            id: id,
            status: nextStatus
          },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              button.data('status', nextStatus);
              button.text(nextStatus.charAt(0).toUpperCase() + nextStatus.slice(1));
              applySalesStatusPill(button, nextStatus);
            } else {
              alert('Error: ' + response.message);
            }
          },
          error: function() {
            alert('An error occurred while updating the status.');
          }
        });
      });
    });
  </script>

</body>

</html>
