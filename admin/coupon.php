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
          Coupons
        </h1>
        <ol class="breadcrumb">
          <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
          <li>Coupons</li>
          <li class="active">Manage Coupons</li>
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
              " . e($_SESSION['error']) . "
            </div>
          ";
          unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
          echo "
            <div class='alert alert-success alert-dismissible'>
              <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
              <h4><i class='icon fa fa-check'></i> Success!</h4>
              " . e($_SESSION['success']) . "
            </div>
          ";
          unset($_SESSION['success']);
        }
        ?>
        <div class="row">
          <div class="col-xs-12">
            <div class="box">
              <div class="box-header with-border">
                <a href="#addnew" data-toggle="modal" class="btn btn-primary btn-sm btn-flat"><i class="fa fa-plus"></i> New</a>
              </div>
              <div class="box-body table-responsive">
                <table id="example1" class="table table-bordered">
                  <thead>
                    <th>Coupon Code</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Status</th>
                    <th>Expire Date</th>
                    <th>Actions</th>
                  </thead>
                  <tbody>
                    <?php
                    $conn = $pdo->open();

                    try {
                      $stmt = $conn->prepare("SELECT * FROM coupons");
                      $stmt->execute();
                      foreach ($stmt as $row) {
                        echo "
                          <tr>
                            <td>" . e($row['code']) . "</td>
                            <td>" . e(ucfirst((string)$row['type'])) . "</td>
                            <td>" . e($row['value']) . "</td>
                            <td>" . e(ucfirst((string)$row['status'])) . "</td>
                            <td>" . ($row['expire_date'] ? date('M d, Y', strtotime($row['expire_date'])) : 'N/A') . "</td>
                            <td>
                              <button class='btn btn-success btn-sm edit btn-flat' data-id='" . (int)$row['id'] . "'><i class='fa fa-edit'></i> Edit</button>
                              <button class='btn btn-danger btn-sm delete btn-flat' data-id='" . (int)$row['id'] . "'><i class='fa fa-trash'></i> Delete</button>
                            </td>
                          </tr>
                        ";
                      }
                    } catch (PDOException $e) {
                      echo 'Unable to load coupons.';
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
    <?php include 'coupon_modal.php'; ?>

  </div>
  <!-- ./wrapper -->

  <?php include 'scripts.php'; ?>
  <script>
    $(function() {
      $(document).on('click', '.edit', function(e) {
        e.preventDefault();
        $('#edit').modal('show');
        var id = $(this).data('id');
        getRow(id);
      });

      $(document).on('click', '.delete', function(e) {
        e.preventDefault();
        $('#delete').modal('show');
        var id = $(this).data('id');
        getRow(id);
      });

    });

    function getRow(id) {
      $.ajax({
        type: 'POST',
        url: 'coupon_row.php',
        data: {
          id: id
        },
        dataType: 'json',
        success: function(response) {
          if (response.error) {
            alert(response.message || 'Unable to load coupon.');
            return;
          }
          $('.coupon_id').val(response.id);
          $('.coupon_code').text(response.code || '');
          $('#edit_code').val(response.code);
          $('#edit_type').val(response.type);
          $('#edit_value').val(response.value);
          $('#edit_status').val(response.status);
          var expireDate = response.expire_date ? String(response.expire_date).slice(0, 10) : '';
          $('#edit_expire_date').val(expireDate);
        }
      });
    }
  </script>
</body>

</html>
