<?php include 'session.php'; ?>
<?php require_once __DIR__ . '/../lib/banner_links.php'; ?>
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
          Banner
        </h1>
        <ol class="breadcrumb">
          <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
          <li>Components</li>
          <li class="active">Banner</li>
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
                  <a href="#addnewBanner" data-toggle="modal" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Banner</a>
                </div>
              </div>
              <div class="box-body table-responsive">
                <table id="example1" class="table table-bordered">
                  <thead>
                    <th>Title</th>
                    <th>Caption Heading</th>
                    <th>Caption Text</th>
                    <th>Link</th>
                    <th>Image</th>
                    <th>Tools</th>
                  </thead>
                  <tbody>
                    <?php
                    $conn = $pdo->open();

                    try {
                      $stmt = $conn->prepare("SELECT * FROM banner");
                      $stmt->execute();
                      foreach ($stmt as $row) {
                        $meta = banner_destination_meta($conn, (string)($row['link'] ?? ''));
                        $image = '../images/' . ltrim((string)($row['image_path'] ?? ''), '/');
                        $linkLabel = (string)($meta['display'] ?? 'Fallback: Shop');
                        if (!empty($meta['is_fallback'])) {
                          $linkLabel = "<span class='text-warning'>" . e($linkLabel) . "</span>";
                        } else {
                          $linkLabel = e($linkLabel);
                        }
                        echo "
                          <tr>
                            <td>" . e($row['name']) . "</td>
                            <td>" . e($row['caption_heading']) . "</td>
                            <td>" . e($row['caption_text']) . "</td>
                            <td>" . $linkLabel . "</td>
                            <td><img src='" . e($image) . "' height='50px' onerror=\"this.onerror=null;this.src='../images/storefront-placeholder.svg';\"></td>
                            <td>
                              <button class='btn btn-success btn-sm edit btn-flat' data-id='" . (int)$row['id'] . "'><i class='fa fa-edit'></i> Edit</button>
                              <button class='btn btn-danger btn-sm delete btn-flat' data-id='" . (int)$row['id'] . "'><i class='fa fa-trash'></i> Delete</button>
                            </td>
                          </tr>
                        ";
                      }
                    } catch (PDOException $e) {
                      echo 'Unable to load banner items.';
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
    <?php include 'banner_modal.php'; ?>

  </div>
  <!-- ./wrapper -->

  <?php include 'scripts.php'; ?>
  <script>
    $(function() {
      $(document).on('click', '.edit', function(e) {
        e.preventDefault();
        $('#editBanner').modal('show');
        var id = $(this).data('id');
        getRow(id);
      });

      $(document).on('click', '.delete', function(e) {
        e.preventDefault();
        $('#deleteBanner').modal('show');
        var id = $(this).data('id');
        getRow(id);
      });

    });

    function getRow(id) {
      $.ajax({
        type: 'POST',
        url: 'banner_row.php',
        data: {
          id: id
        },
        dataType: 'json',
        success: function(response) {
          if (response.error) {
            alert(response.message || 'Unable to load banner.');
            return;
          }
          $('.bannerid').val(response.id);
          $('#edit_banner_name').val(response.name);
          $('#edit_caption_heading').val(response.caption_heading);
          $('#edit_caption_text').val(response.caption_text);
          $('#edit_destination_type').val(response.destination_type || 'category');
          $('#edit_product_slug').val(response.product_slug || '');
          $('#edit_category_slug').val(response.category_slug || '');
          syncDestinationControls('edit_');
          $('.bannername').text(response.name || '');
        }
      });
    }

    function syncDestinationControls(prefix) {
      var type = $('#' + prefix + 'destination_type').val() || 'category';
      var $productWrap = $('#' + prefix + 'product_group');
      var $categoryWrap = $('#' + prefix + 'category_group');
      var $product = $('#' + prefix + 'product_slug');
      var $category = $('#' + prefix + 'category_slug');
      var $link = $('#' + prefix + 'link');

      if (type === 'product') {
        $productWrap.show();
        $categoryWrap.hide();
        $product.prop('required', true);
        $category.prop('required', false);
        $link.val('detail?product=' + encodeURIComponent($product.val() || ''));
      } else {
        $categoryWrap.show();
        $productWrap.hide();
        $category.prop('required', true);
        $product.prop('required', false);
        $link.val('shop?category=' + encodeURIComponent($category.val() || ''));
      }
    }

    $(document).on('change', '#add_destination_type, #add_product_slug, #add_category_slug, #edit_destination_type, #edit_product_slug, #edit_category_slug', function() {
      if (this.id.indexOf('edit_') === 0) {
        syncDestinationControls('edit_');
      } else {
        syncDestinationControls('add_');
      }
    });

    $('#addnewBanner').on('shown.bs.modal', function() {
      syncDestinationControls('add_');
    });

    $('#editBanner').on('shown.bs.modal', function() {
      syncDestinationControls('edit_');
    });
  </script>
</body>

</html>
