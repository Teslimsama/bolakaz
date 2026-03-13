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
          Users
        </h1>
        <ol class="breadcrumb">
          <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
          <li class="active">Users</li>
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
              <div class="box-header with-border admin-list-toolbar">
                <div class="admin-list-toolbar-main">
                  <a href="#addnew" data-toggle="modal" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add User</a>
                </div>
                <div class="admin-list-toolbar-filters">
                  <div class="form-inline">
                    <div class="form-group">
                      <label for="filter_user_status" class="sr-only">Status</label>
                      <select id="filter_user_status" class="form-control input-sm">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="not_verified">Not Verified</option>
                      </select>
                    </div>
                    <div class="form-group" style="margin-left:8px;">
                      <label for="filter_user_date" class="sr-only">Date Added</label>
                      <input type="text" id="filter_user_date" class="form-control input-sm" placeholder="Date range" style="min-width: 190px;">
                    </div>
                    <button type="button" id="clear_user_filters" class="btn btn-default btn-sm" style="margin-left:8px;">Reset</button>
                  </div>
                </div>
              </div>
              <div class="box-body table-responsive">
                <table id="example1" class="table table-bordered">
                  <thead>
                    <th>Photo</th>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Date Added</th>
                    <th>Tools</th>
                  </thead>
                  <tbody>
                    <?php
                    $conn = $pdo->open();

                    try {
                      $stmt = $conn->prepare("SELECT * FROM users WHERE type=:type");
                      $stmt->execute(['type' => 0]);
                      foreach ($stmt as $row) {
                        $image = (!empty($row['photo'])) ? '../images/' . $row['photo'] : '../images/profile.jpg';
                        $status = ($row['status']) ? '<span class="label label-success">active</span>' : '<span class="label label-danger">not verified</span>';
                        $statusKey = ((int)$row['status'] === 1) ? 'active' : 'not_verified';
                        $active = (!$row['status']) ? '<span class="pull-right"><a href="#activate" class="status" data-toggle="modal" data-id="' . $row['id'] . '"><i class="fa fa-check-square-o"></i></a></span>' : '';
                        $fullName = trim((string)$row['firstname'] . ' ' . (string)$row['lastname']);
                        $createdRaw = !empty($row['created_on']) ? date('Y-m-d', strtotime((string)$row['created_on'])) : '';
                        echo "
                          <tr data-status='" . e($statusKey) . "' data-created='" . e($createdRaw) . "'>
                            <td>
                              <img src='" . e($image) . "' height='30px' width='30px' onerror=\"this.onerror=null;this.src='../images/profile.jpg';\">
                              <span class='pull-right'><a href='#edit_photo' class='photo' data-toggle='modal' data-id='" . $row['id'] . "'><i class='fa fa-edit'></i></a></span>
                            </td>
                            <td>" . e($row['email']) . "</td>
                            <td>" . e($fullName) . "</td>
                            <td>
                              " . $status . "
                              " . $active . "
                            </td>
                            <td>" . date('M d, Y', strtotime($row['created_on'])) . "</td>
                            <td>
                              <a href='cart?user=" . $row['id'] . "' class='btn btn-info btn-sm btn-flat'><i class='fa fa-search'></i> Cart</a>
                              <button class='btn btn-info btn-sm edit btn-flat' data-id='" . $row['id'] . "'><i class='fa fa-edit'></i> Edit</button>
                              <button class='btn btn-danger btn-sm delete btn-flat' data-id='" . $row['id'] . "'><i class='fa fa-trash'></i> Delete</button>
                            </td>
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
    <?php include 'users_modal.php'; ?>

  </div>
  <!-- ./wrapper -->

  <?php include 'scripts.php'; ?>
  <script>
    $(function() {
      var userTable = $.fn.dataTable.isDataTable('#example1') ? $('#example1').DataTable() : null;
      var userDateRange = { start: null, end: null };

      $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (!userTable || settings.nTable.id !== 'example1') {
          return true;
        }

        var rowNode = settings.aoData && settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
        if (!rowNode) {
          return true;
        }

        var selectedStatus = String($('#filter_user_status').val() || '').toLowerCase();
        var rowStatus = String($(rowNode).data('status') || '').toLowerCase();
        if (selectedStatus && rowStatus !== selectedStatus) {
          return false;
        }

        if (userDateRange.start && userDateRange.end) {
          var createdRaw = String($(rowNode).data('created') || '').trim();
          if (!createdRaw) {
            return false;
          }
          var created = moment(createdRaw, 'YYYY-MM-DD');
          if (!created.isValid()) {
            return false;
          }
          if (created.isBefore(userDateRange.start, 'day') || created.isAfter(userDateRange.end, 'day')) {
            return false;
          }
        }

        return true;
      });

      $('#filter_user_status').on('change', function() {
        if (userTable) {
          userTable.draw();
        }
      });

      $('#filter_user_date').daterangepicker({
        autoUpdateInput: false,
        locale: { cancelLabel: 'Clear' }
      });

      $('#filter_user_date').on('apply.daterangepicker', function(ev, picker) {
        userDateRange.start = picker.startDate.clone().startOf('day');
        userDateRange.end = picker.endDate.clone().endOf('day');
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        if (userTable) {
          userTable.draw();
        }
      });

      $('#filter_user_date').on('cancel.daterangepicker', function() {
        userDateRange.start = null;
        userDateRange.end = null;
        $(this).val('');
        if (userTable) {
          userTable.draw();
        }
      });

      $('#clear_user_filters').on('click', function() {
        $('#filter_user_status').val('');
        $('#filter_user_date').val('');
        userDateRange.start = null;
        userDateRange.end = null;
        if (userTable) {
          userTable.draw();
        }
      });

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

      $(document).on('click', '.photo', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        getRow(id);
      });

      $(document).on('click', '.status', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        getRow(id);
      });

    });

    function getRow(id) {
      $.ajax({
        type: 'POST',
        url: 'users_row.php',
        data: {
          id: id
        },
        dataType: 'json',
        success: function(response) {
          if (response.error) {
            alert(response.message || 'Unable to load user details.');
            return;
          }
          $('.userid').val(response.id);
          $('#edit_email').val(response.email);
          $('#edit_password').val('');
          $('#edit_firstname').val(response.firstname);
          $('#edit_lastname').val(response.lastname);
          $('#edit_address').val(response.address);
          $('#edit_contact').val(response.phone);
          $('.fullname').html(response.firstname + ' ' + response.lastname);
        }
      });
    }
  </script>
</body>

</html>
