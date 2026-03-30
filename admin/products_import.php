<?php
include 'session.php';
require_once __DIR__ . '/../lib/product_csv_import.php';
require_once __DIR__ . '/../lib/sync.php';

$state = product_import_state();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    try {
        if ($action === 'clear') {
            product_import_clear_state();
            $_SESSION['success'] = 'Import session cleared.';
            header('location: products_import.php');
            exit;
        }

        if (sync_is_server()) {
            throw new RuntimeException('Product CSV import is available on the local client app only. Products do not pull down from live yet.');
        }

        if ($action === 'upload') {
            product_import_clear_state();
            $upload = product_import_store_upload($_FILES['csv_file'] ?? []);
            product_import_store_state([
                'upload' => $upload,
                'mapping' => $upload['guess_mapping'] ?? [],
                'preview' => null,
                'result' => null,
                'latest_error_csv' => '',
            ]);
            $_SESSION['info'] = 'CSV uploaded. Nothing has been imported yet. Map the headers below, then run a dry-run preview.';
            header('location: products_import.php');
            exit;
        }

        if ($action === 'preview') {
            $state = product_import_state();
            if (empty($state['upload']['path']) || !is_file((string) $state['upload']['path'])) {
                throw new RuntimeException('Upload a CSV file first.');
            }

            $mapping = [];
            foreach (array_keys(product_import_fields()) as $field) {
                $posted = $_POST['map_' . $field] ?? '';
                if ($posted === '') {
                    continue;
                }
                $mapping[$field] = (int) $posted;
            }

            foreach (['slug', 'name', 'category_slug', 'price', 'qty', 'product_status'] as $requiredField) {
                if (!array_key_exists($requiredField, $mapping)) {
                    throw new RuntimeException('Map all required fields before previewing the import.');
                }
            }

            $conn = $pdo->open();
            try {
                $preview = product_import_build_preview($conn, (string) $state['upload']['path'], $mapping);
            } finally {
                $pdo->close();
            }

            $state['mapping'] = $mapping;
            $state['preview'] = $preview;
            $state['result'] = null;
            $state['latest_error_csv'] = product_import_error_csv((array) ($preview['error_rows'] ?? []));
            product_import_store_state($state);

            if ((int) ($preview['summary']['valid'] ?? 0) > 0) {
                $_SESSION['warning'] = 'Dry run completed. Nothing has been imported yet. Review the rows below, then click Import Valid Rows to save the valid ones.';
            } else {
                $_SESSION['warning'] = 'Dry run completed, but nothing can be imported yet. Fix the failed rows or download the error CSV before trying again.';
            }
            header('location: products_import.php');
            exit;
        }

        if ($action === 'apply') {
            $state = product_import_state();
            $preview = is_array($state['preview'] ?? null) ? $state['preview'] : [];
            $validRows = is_array($preview['valid_rows'] ?? null) ? $preview['valid_rows'] : [];
            if (empty($validRows)) {
                throw new RuntimeException('There are no valid rows ready to import.');
            }

            $conn = $pdo->open();
            try {
                $result = product_import_apply($conn, $validRows);
            } finally {
                $pdo->close();
            }

            $combinedErrors = array_merge(
                (array) ($preview['error_rows'] ?? []),
                (array) ($result['errors'] ?? [])
            );
            $state['result'] = $result;
            $state['latest_error_csv'] = product_import_error_csv($combinedErrors);
            product_import_store_state($state);

            $_SESSION['success'] = 'Import finished. Created: ' . (int) ($result['created'] ?? 0) . ', Updated: ' . (int) ($result['updated'] ?? 0) . ', Failed: ' . (int) ($result['failed'] ?? 0) . '.';
            header('location: products_import.php');
            exit;
        }

        throw new RuntimeException('Unknown import action.');
    } catch (Throwable $e) {
        $_SESSION['error'] = $e->getMessage();
        header('location: products_import.php');
        exit;
    }
}

$state = product_import_state();
$upload = is_array($state['upload'] ?? null) ? $state['upload'] : [];
$mapping = is_array($state['mapping'] ?? null) ? $state['mapping'] : [];
$preview = is_array($state['preview'] ?? null) ? $state['preview'] : [];
$result = is_array($state['result'] ?? null) ? $state['result'] : [];
$fields = product_import_fields();

include 'header.php';
?>

<body class="hold-transition skin-blue sidebar-mini">
  <div class="wrapper">

    <?php include 'navbar.php'; ?>
    <?php include 'menubar.php'; ?>

    <div class="content-wrapper">
      <section class="content-header">
        <h1>Product CSV Import</h1>
        <ol class="breadcrumb">
          <li><a href="home"><i class="fa fa-dashboard"></i> Home</a></li>
          <li><a href="products">Products</a></li>
          <li class="active">CSV Import</li>
        </ol>
      </section>

      <section class="content">
        <?php
        if (isset($_SESSION['error'])) {
          echo "<div class='alert alert-danger alert-dismissible'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button><h4><i class='icon fa fa-warning'></i> Error!</h4>" . e($_SESSION['error']) . "</div>";
          unset($_SESSION['error']);
        }
        if (isset($_SESSION['warning'])) {
          echo "<div class='alert alert-warning alert-dismissible'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button><h4><i class='icon fa fa-warning'></i> Attention</h4>" . e($_SESSION['warning']) . "</div>";
          unset($_SESSION['warning']);
        }
        if (isset($_SESSION['info'])) {
          echo "<div class='alert alert-info alert-dismissible'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button><h4><i class='icon fa fa-info'></i> Info</h4>" . e($_SESSION['info']) . "</div>";
          unset($_SESSION['info']);
        }
        if (isset($_SESSION['success'])) {
          echo "<div class='alert alert-success alert-dismissible'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button><h4><i class='icon fa fa-check'></i> Success!</h4>" . e($_SESSION['success']) . "</div>";
          unset($_SESSION['success']);
        }
        ?>

        <div class="row">
          <div class="col-xs-12">
            <div class="box">
              <div class="box-header with-border admin-list-toolbar">
                <div class="admin-list-toolbar-main">
                  <a href="products" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back to Products</a>
                  <a href="products_import_template.php" class="btn btn-primary btn-sm"><i class="fa fa-download"></i> Download Template</a>
                  <?php if (trim((string) ($state['latest_error_csv'] ?? '')) !== ''): ?>
                    <a href="products_import_errors.php" class="btn btn-warning btn-sm"><i class="fa fa-file-text-o"></i> Download Error CSV</a>
                  <?php endif; ?>
                </div>
                <div class="admin-list-toolbar-filters">
                  <?php if (!empty($upload)): ?>
                    <form method="POST" class="form-inline">
                      <input type="hidden" name="action" value="clear">
                      <button type="submit" class="btn btn-default btn-sm">Reset Import Session</button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
              <div class="box-body">
                <?php if (sync_is_server()): ?>
                  <div class="alert alert-info">
                    Product CSV import is available on the local client app only. Products are still local-owned in sync, so importing them on live would leave Mom&rsquo;s PC out of date.
                  </div>
                <?php else: ?>
                  <div class="alert alert-info">
                    Upload a CSV, map the incoming headers to Bolakaz fields, run a dry-run preview, then import only the valid rows. Images are intentionally excluded from this first release. The downloaded template now uses your current category slugs so it matches this database.
                  </div>

                  <form method="POST" enctype="multipart/form-data" class="form-horizontal" style="margin-bottom:24px;">
                    <input type="hidden" name="action" value="upload">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">CSV File</label>
                      <div class="col-sm-7">
                        <input type="file" name="csv_file" accept=".csv,text/csv" required>
                        <p class="help-block">Unknown extra columns will be ignored unless you map them.</p>
                      </div>
                      <div class="col-sm-3">
                        <button type="submit" class="btn btn-primary btn-flat"><i class="fa fa-upload"></i> Upload CSV</button>
                      </div>
                    </div>
                  </form>
                <?php endif; ?>

                <?php if (!empty($upload) && !sync_is_server()): ?>
                  <div class="box box-default">
                    <div class="box-header with-border">
                      <h3 class="box-title">Header Mapping</h3>
                    </div>
                    <div class="box-body">
                      <p><strong>Uploaded file:</strong> <?php echo e((string) ($upload['original_name'] ?? basename((string) ($upload['path'] ?? 'upload.csv')))); ?> | <strong>Rows:</strong> <?php echo (int) ($upload['row_count'] ?? 0); ?></p>
                      <form method="POST" class="form-horizontal">
                        <input type="hidden" name="action" value="preview">
                        <?php foreach ($fields as $field => $meta): ?>
                          <div class="form-group">
                            <label class="col-sm-3 control-label"><?php echo e((string) ($meta['label'] ?? $field)); ?></label>
                            <div class="col-sm-9">
                              <select class="form-control" name="map_<?php echo e($field); ?>" <?php echo !empty($meta['required']) ? 'required' : ''; ?>>
                                <option value="">-- Ignore --</option>
                                <?php foreach ((array) ($upload['headers'] ?? []) as $index => $header): ?>
                                  <option value="<?php echo (int) $index; ?>" <?php echo ((string) ($mapping[$field] ?? ($upload['guess_mapping'][$field] ?? '')) === (string) $index) ? 'selected' : ''; ?>>
                                    <?php echo e((string) $header); ?>
                                  </option>
                                <?php endforeach; ?>
                              </select>
                              <?php if (!empty($meta['required_on_create'])): ?>
                                <p class="help-block">Required for new products only.</p>
                              <?php endif; ?>
                            </div>
                          </div>
                        <?php endforeach; ?>

                        <div class="form-group">
                          <div class="col-sm-offset-3 col-sm-9">
                            <button type="submit" class="btn btn-success btn-flat"><i class="fa fa-search"></i> Run Dry-Run Preview</button>
                          </div>
                        </div>
                      </form>
                    </div>
                  </div>
                <?php endif; ?>

                <?php if (!empty($preview)): ?>
                  <div class="box box-primary">
                    <div class="box-header with-border">
                      <h3 class="box-title">Preview Results</h3>
                    </div>
                    <div class="box-body">
                      <?php if ((int) ($preview['summary']['valid'] ?? 0) > 0): ?>
                        <div class="alert alert-warning">
                          This is still a preview only. Nothing has been imported yet. Click <strong>Import Valid Rows</strong> below to write the valid rows into Products.
                        </div>
                      <?php else: ?>
                        <div class="alert alert-danger">
                          No rows are ready to import yet. Fix the failed rows below, especially category and subcategory slugs, then preview again.
                        </div>
                      <?php endif; ?>

                      <div class="row" style="margin-bottom:16px;">
                        <div class="col-sm-3"><strong>Total Rows:</strong> <?php echo (int) ($preview['summary']['total'] ?? 0); ?></div>
                        <div class="col-sm-2"><strong>Create:</strong> <?php echo (int) ($preview['summary']['create'] ?? 0); ?></div>
                        <div class="col-sm-2"><strong>Update:</strong> <?php echo (int) ($preview['summary']['update'] ?? 0); ?></div>
                        <div class="col-sm-2"><strong>Valid:</strong> <?php echo (int) ($preview['summary']['valid'] ?? 0); ?></div>
                        <div class="col-sm-3"><strong>Failed:</strong> <?php echo (int) ($preview['summary']['failed'] ?? 0); ?></div>
                      </div>

                      <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                          <thead>
                            <tr>
                              <th>Row</th>
                              <th>Status</th>
                              <th>Slug</th>
                              <th>Name</th>
                              <th>Category</th>
                              <th>Price</th>
                              <th>Qty</th>
                              <th>Message</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ((array) ($preview['rows'] ?? []) as $row): ?>
                              <tr>
                                <td><?php echo (int) ($row['row_number'] ?? 0); ?></td>
                                <td><?php echo e((string) ($row['status'] ?? '')); ?></td>
                                <td><?php echo e((string) ($row['slug'] ?? '')); ?></td>
                                <td><?php echo e((string) ($row['name'] ?? '')); ?></td>
                                <td><?php echo e((string) ($row['category_slug'] ?? '')); ?></td>
                                <td><?php echo e((string) ($row['price'] ?? '')); ?></td>
                                <td><?php echo e((string) ($row['qty'] ?? '')); ?></td>
                                <td><?php echo e((string) ($row['message'] ?? 'Ready')); ?></td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>

                      <?php if ((int) ($preview['summary']['valid'] ?? 0) > 0 && !sync_is_server()): ?>
                        <form method="POST">
                          <input type="hidden" name="action" value="apply">
                          <button type="submit" class="btn btn-primary btn-flat"><i class="fa fa-check"></i> Import Valid Rows</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endif; ?>

                <?php if (!empty($result)): ?>
                  <div class="box box-success">
                    <div class="box-header with-border">
                      <h3 class="box-title">Import Summary</h3>
                    </div>
                    <div class="box-body">
                      <p><strong>Created:</strong> <?php echo (int) ($result['created'] ?? 0); ?></p>
                      <p><strong>Updated:</strong> <?php echo (int) ($result['updated'] ?? 0); ?></p>
                      <p><strong>Failed During Import:</strong> <?php echo (int) ($result['failed'] ?? 0); ?></p>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <?php include 'footer.php'; ?>
  </div>

  <?php include 'scripts.php'; ?>
</body>
</html>
