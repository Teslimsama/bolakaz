<?php include 'session.php'; ?>
<?php include 'header.php'; ?>

<body class="hold-transition skin-blue sidebar-mini">
  <div class="wrapper">

    <?php include 'navbar.php'; ?>
    <?php include 'menubar.php'; ?>

    <div class="content-wrapper">
      <section class="content-header">
        <h1>
          Web Details
        </h1>
        <ol class="breadcrumb">
          <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
          <li class="active">Web Details</li>
        </ol>
      </section>

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
                  <a href="#addWeb_details" data-toggle="modal" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Web Detail</a>
                </div>
              </div>
              <div class="box-body">
                <div class="table-responsive admin-table-wrap">
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
                      $previewText = static function ($value, int $limit = 140): string {
                        $decoded = (string)$value;
                        for ($i = 0; $i < 3; $i++) {
                          $next = html_entity_decode($decoded, ENT_QUOTES, 'UTF-8');
                          if ($next === $decoded) {
                            break;
                          }
                          $decoded = $next;
                        }

                        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($decoded)));
                        $legacyNoise = [
                          'error! fill up the edit form first',
                          'fill up the edit form first',
                        ];
                        $plainLower = strtolower($plain);
                        foreach ($legacyNoise as $noise) {
                          if ($plainLower === $noise || strpos($plainLower, $noise) !== false) {
                            return '';
                          }
                        }

                        if ($plain === '') {
                          return '';
                        }

                        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                          return mb_strlen($plain) > $limit ? mb_substr($plain, 0, $limit - 1) . '...' : $plain;
                        }

                        return strlen($plain) > $limit ? substr($plain, 0, $limit - 1) . '...' : $plain;
                      };
                      $renderPreview = static function ($value) {
                        $clean = trim((string)$value);
                        if ($clean === '') {
                          return "<span class='admin-empty-placeholder'>Not provided</span>";
                        }
                        return e($clean);
                      };

                      try {
                        $stmt = $conn->prepare("SELECT * FROM web_details");
                        $stmt->execute();
                        foreach ($stmt as $row) {
                          $siteAddressPreview = $previewText($row['site_address']);
                          $shortPreview = $previewText($row['short_description']);
                          $descriptionPreview = $previewText($row['description']);
                          echo "
                            <tr>
                              <td>" . e($row['site_name']) . "</td>
                              <td>" . $renderPreview($siteAddressPreview) . "</td>
                              <td>" . e($row['site_number']) . "</td>
                              <td>" . e($row['site_email']) . "</td>
                              <td>" . $renderPreview($shortPreview) . "</td>
                              <td>" . $renderPreview($descriptionPreview) . "</td>
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
        </div>
      </section>

    </div>
    <?php include 'footer.php'; ?>
    <?php include 'web_details_modal.php'; ?>

  </div>

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

    function decodeHtmlEntities(value) {
      var txt = document.createElement('textarea');
      txt.innerHTML = value || '';
      return txt.value;
    }

    function getWeb_detailsRow(id) {
      $.ajax({
        type: 'POST',
        url: 'web_details_row.php',
        data: {
          id: id
        },
        dataType: 'json',
        success: function(response) {
          if (!response || response.error || !response.id) {
            alert((response && response.message) || 'Unable to load web details.');
            return;
          }

          $('.web_detailsid').val(response.id);
          $('#edit_site_name').val(response.site_name || '');
          $('#edit_site_email').val(response.site_email || '');
          $('#edit_site_number').val(response.site_number || '');
          $('.web_details_name').text(response.site_name || '');

          var siteAddress = decodeHtmlEntities(response.site_address || '');
          var shortDesc = decodeHtmlEntities(response.short_description || '');
          var description = decodeHtmlEntities(response.description || '');

          if (window.CKEDITOR && CKEDITOR.instances) {
            if (CKEDITOR.instances['editor2']) {
              CKEDITOR.instances['editor2'].setData(siteAddress);
            } else {
              $('#editor2').val(siteAddress);
            }
            if (CKEDITOR.instances['edit_short_desc']) {
              CKEDITOR.instances['edit_short_desc'].setData(shortDesc);
            } else {
              $('#edit_short_desc').val(shortDesc);
            }
            if (CKEDITOR.instances['edit_desc']) {
              CKEDITOR.instances['edit_desc'].setData(description);
            } else {
              $('#edit_desc').val(description);
            }
          } else {
            $('#editor2').val(siteAddress);
            $('#edit_short_desc').val(shortDesc);
            $('#edit_desc').val(description);
          }
        }
      });
    }
  </script>
</body>

</html>
