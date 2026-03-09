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
          Web Details
        </h1>
        <ol class="breadcrumb">
          <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
          <li class="active">Web Details</li>
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
                <a href="#addWeb_details" data-toggle="modal" class="btn btn-primary btn-sm btn-flat"><i class="fa fa-plus"></i> New</a>
              </div>
              <div class="box-body table-responsive">
                <table id="example1" class="table table-bordered">
                  <thead>
                    <th>Site Name</th>
                    <th>Site Address</th>
                    <th>Site Number</th>
                    <th>Site Email</th>
                    <th>Site Short Description</th>
                    <th>Site Description</th>
                    <th>Tools</th>
                  </thead>
                  <tbody>
                    <?php
                    $conn = $pdo->open();

                    try {
                      $stmt = $conn->prepare("SELECT * FROM web_details");
                      $stmt->execute();
                      foreach ($stmt as $row) {
                        echo "
                          <tr>
                            <td>" . e($row['site_name']) . "</td>
                            <td>" . e($row['site_address']) . "</td>
                            <td>" . e($row['site_number']) . "</td>
                            <td>" . e($row['site_email']) . "</td>
                            <td>" . e($row['short_description']) . "</td>
                            <td>" . e($row['description']) . "</td>
                            <td>
                              <button class='btn btn-success btn-sm edit btn-flat' data-id='" . (int)$row['id'] . "'><i class='fa fa-edit'></i> Edit</button>
                              <button class='btn btn-danger btn-sm delete btn-flat' data-id='" . (int)$row['id'] . "'><i class='fa fa-trash'></i> Delete</button>
                            </td>
                          </tr>
                        ";
                      }
                    } catch (PDOException $e) {
                      echo 'Unable to load web details.';
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
    <?php include 'web_details_modal.php'; ?>

  </div>
  <!-- ./wrapper -->

  <?php include 'scripts.php'; ?>
  <script>
    $(function() {
      $(document).on('click', '.edit', function(e) {
        e.preventDefault();
        $('#editWeb_details').modal('show');
        var id = $(this).data('id');
        getWeb_detailsRow(id);
      });

      $(document).on('click', '.delete', function(e) {
        e.preventDefault();
        $('#deleteWeb_details').modal('show');
        var id = $(this).data('id');
        getWeb_detailsRow(id);
      });

    });

    function getWeb_detailsRow(id) {
      $.ajax({
        type: 'POST',
        url: 'web_details_row.php',
        data: {
          id: id
        },
        dataType: 'json',
        success: function(response) {
          $('.web_detailsid').val(response.id);
          $('#edit_site_name').val(response.site_name);
          $('#edit_site_email').val(response.site_email);
          $('#edit_site_number').val(response.site_number);
          $('.web_details_name').html(response.web_details);
          $('#edit_short_desc').html(response.short_description);
          $('#edit_site_address').html(response.site_address);
          $('#edit_desc').html(response.description);
          CKEDITOR.instances["editor2"].setData(response.site_address);
          CKEDITOR.instances["edit_short_desc"].setData(response.short_description);
          CKEDITOR.instances["edit_desc"].setData(response.description);
        }
      });
    }
  </script>
</body>

</html>
