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
        <?php
        if (isset($_SESSION['error'])) {
          echo "
            <div class='alert alert-danger alert-dismissible'>
              <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
              <h4><i class='icon fa fa-warning'></i> Error!</h4>
              " . $_SESSION['error'] . "
            </div>
          ";
          unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
          echo "
            <div class='alert alert-success alert-dismissible'>
              <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
              <h4><i class='icon fa fa-check'></i> Success!</h4>
              " . $_SESSION['success'] . "
            </div>
          ";
          unset($_SESSION['success']);
        }
        ?>
        <div class="row">
          <div class="col-xs-12">
            <div class="box">
              <div class="box-header with-border">
                <div class="pull-right">
                  <form method="POST" class="form-inline" action="sales_print.php">
                    <div class="input-group">
                      <div class="input-group-addon">
                        <i class="fa fa-calendar"></i>
                      </div>
                      <input type="text" class="form-control pull-right col-sm-8" id="reservation" name="date_range">
                    </div>
                    <button type="submit" class="btn btn-success btn-sm btn-flat" name="print"><span class="glyphicon glyphicon-print"></span> Print</button>
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
                      $stmt = $conn->prepare("SELECT *, sales.id AS salesid FROM sales LEFT JOIN users ON users.id=sales.user_id ORDER BY sales_date DESC");
                      $stmt->execute();
                      foreach ($stmt as $row) {
                        $stmt = $conn->prepare("SELECT * FROM details LEFT JOIN products ON products.id=details.product_id WHERE details.sales_id=:id");
                        $stmt->execute(['id' => $row['salesid']]);
                        $total = 0;
                        foreach ($stmt as $details) {
                          $subtotal = $details['price'] * $details['quantity'];
                          $total += $subtotal;
                        }
                        if ($row['Status'] === 'pending') {
                          $status = '<span class="pull-right"><span class="label label-danger">'.$row['Status'].'</span><a href="#activate" class="status" data-toggle="modal" data-id="' . $row['salesid'] . '"><i class="fa fa-check-square-o"></i></a></span>';
                        } elseif ($row['Status'] === 'success' || 'successful') {
                          $status = '<span class="label label-success">' . $row['Status'] . '</span>';
                        } elseif ($row['Status'] === 'failed') {
                          # code...
                          $status = '123';
                        }

                        echo "
                          <tr>
                            <td class='hidden'></td>
                            <td>" . date('M d, Y', strtotime($row['sales_date'])) . "</td>
                            <td>" . $row['firstname'] . ' ' . $row['lastname'] . "</td>
                            <td>" . $row['tx_ref'] . "</td>
                            <td> " . $status . " </td>
                            <td> ₦ " . number_format($total, 2) . "</td>
                            <td><button type='button' class='btn btn-info btn-sm btn-flat transact' data-id='" . $row['salesid'] . "'><i class='fa fa-search'></i> View</button></td>
                          </tr>
                        ";
                      }
                    } catch (PDOException $e) {
                      echo $e->getMessage();
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
    <?php include 'sales_modal.php'; ?>
    <?php include '../profile_modal.php'; ?>
  </div>
  <!-- ./wrapper -->

  <?php include 'scripts.php'; ?>
  <!-- Date Picker -->
  <script>
    $(function() {
      //Date picker
      $('#datepicker_add').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd'
      })
      $('#datepicker_edit').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd'
      })

      //Timepicker
      $('.timepicker').timepicker({
        showInputs: false
      })

      //Date range picker
      $('#reservation').daterangepicker()
      //Date range picker with time picker
      $('#reservationtime').daterangepicker({
        timePicker: true,
        timePickerIncrement: 30,
        format: 'MM/DD/YYYY h:mm A'
      })
      //Date range as a button
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
          $('#daterange-btn span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'))
        }
      )

    });
  </script>
  <script>
    $(function() {



      $(document).on('click', '.status', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        getRow(id);
      });

    });

    function getRow(id) {
      $.ajax({
        type: 'POST',
        url: 'sales_row.php',
        data: {
          id: id
        },
        dataType: 'json',
        success: function(response) {
          $('.userid').val(response.id);
        }
      });
    }
  </script>
  <script>
    $(function() {
      $(document).on('click', '.transact', function(e) {
        e.preventDefault();
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
            $('#date').html(response.date);
            $('#transid').html(response.transaction);
            $('#detail').prepend(response.list);
            $('#total').html(response.total);
          }
        });
      });

      $("#transaction").on("hidden.bs.modal", function() {
        $('.prepend_items').remove();
      });
    });
  </script>
</body>

</html>