<?php include 'session.php';
// Query to fetch parent categories
$sql = "SELECT * FROM category WHERE is_parent = 1 ORDER BY name ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();

// Fetch data into an array
$parent_cats = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
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
          Category
        </h1>
        <ol class="breadcrumb">
          <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
          <li>Products</li>
          <li class="active">Category</li>
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
              <div class="box-header with-border admin-list-toolbar">
                <div class="admin-list-toolbar-main">
                  <a href="#addnew" data-toggle="modal" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Category</a>
                </div>
              </div>
              <div class="box-body table-responsive">
                <table id="example1" class="table table-bordered">
                  <thead>
                    <th>Category Name</th>
                    <th>Tools</th>
                  </thead>
                  <tbody>
                    <?php
                    $conn = $pdo->open();

                    try {
                      $stmt = $conn->prepare("SELECT * FROM category");
                      $stmt->execute();
                      foreach ($stmt as $row) {
                        echo "
                          <tr>
                            <td>" . e($row['name']) . "</td>
                            <td>
                              <button class='btn btn-success btn-sm edit btn-flat' data-id='" . (int)$row['id'] . "'><i class='fa fa-edit'></i> Edit</button>
                              <button class='btn btn-danger btn-sm delete btn-flat' data-id='" . (int)$row['id'] . "'><i class='fa fa-trash'></i> Delete</button>
                            </td>
                          </tr>
                        ";
                      }
                    } catch (PDOException $e) {
                      echo 'Unable to load categories.';
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
    <?php include 'category_modal.php'; ?>

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
        url: 'category_row.php',
        data: {
          id: id
        },
        dataType: 'json',
        success: function(response) {
          if (!response || !response.success) {
            alert((response && response.message) || 'Unable to load category.');
            return;
          }
          $('.catid').val(response.category.id);
          $('#edit_name').val(response.category.name);
          $('.catname').text(response.category.name || '');

          // Populate status dropdown
          const statusSelect = $('#status');
          statusSelect.empty();
          response.status_options.forEach(function(status) {
            const selected = status.value === response.category.status ? 'selected' : '';
            statusSelect.append(
              `<option value="${status.value}" ${selected}>${status.label}</option>`
            );
          });

          const isParent = String(response.category.is_parent) === '1';
          $('#is_parent_edit').prop('checked', isParent);
          if (isParent) {
            $('#parent_cat_div_edit').addClass('d-none');
            $('#edit_parent_id').val('');
          } else {
            $('#parent_cat_div_edit').removeClass('d-none');
            $('#edit_parent_id').val(response.category.parent_id || '');
          }

        }
      });
    }

    $('#is_parent').change(function() {
      var is_checked = $('#is_parent').prop('checked');
      // alert(is_checked);
      if (is_checked) {
        $('#parent_cat_div').addClass('d-none');
        $('#parent_cat_div').val('');
      } else {
        $('#parent_cat_div').removeClass('d-none');
      }
    })
    $('#is_parent_edit').change(function() {
      var is_checked = $('#is_parent_edit').prop('checked');
      // alert(is_checked);
      if (is_checked) {
        $('#parent_cat_div_edit').addClass('d-none');
        $('#parent_cat_div_edit').val('');
      } else {
        $('#parent_cat_div_edit').removeClass('d-none');
      }
    })
  </script>
</body>

</html>
