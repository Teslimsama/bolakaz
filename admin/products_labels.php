<?php
include 'session.php';
require_once __DIR__ . '/../lib/product_labels.php';

function product_label_collect_ids(): array
{
    $ids = [];

    if (isset($_POST['selected_products']) && is_array($_POST['selected_products'])) {
        $ids = $_POST['selected_products'];
    } elseif (isset($_GET['product_ids']) && is_array($_GET['product_ids'])) {
        $ids = $_GET['product_ids'];
    } else {
        $singleId = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
        if ($singleId !== false && $singleId !== null && $singleId > 0) {
            $ids = [$singleId];
        }
    }

    $normalized = [];
    foreach ($ids as $id) {
        $value = (int) $id;
        if ($value > 0) {
            $normalized[] = $value;
        }
    }

    return array_values(array_unique($normalized));
}

function product_label_quantities(array $productIds, array $source, bool $strict = false): array
{
    $quantities = [];
    foreach ($productIds as $productId) {
        $raw = $source[$productId] ?? $source[(string) $productId] ?? 1;
        if (filter_var($raw, FILTER_VALIDATE_INT) === false || (int) $raw <= 0) {
            if ($strict) {
                throw new RuntimeException('Label quantity must be a whole number greater than zero.');
            }
            $quantities[$productId] = 1;
            continue;
        }
        $quantities[$productId] = (int) $raw;
    }

    return $quantities;
}

function product_label_fetch_products(PDO $conn, array $productIds): array
{
    if (empty($productIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ({$placeholders})");
    $stmt->execute($productIds);

    $rows = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $row['resolved_sku'] = product_sku_resolve_for_row($row);
        $rows[(int) $row['id']] = $row;
    }

    $ordered = [];
    $missing = [];
    foreach ($productIds as $productId) {
        if (!isset($rows[$productId])) {
            $missing[] = $productId;
            continue;
        }
        $ordered[] = $rows[$productId];
    }

    if (!empty($missing)) {
        throw new RuntimeException('Unknown product id(s): ' . implode(', ', $missing) . '.');
    }

    return $ordered;
}

$productIds = product_label_collect_ids();
if (empty($productIds)) {
    $_SESSION['error'] = 'Select at least one product before printing labels.';
    header('location: products.php');
    exit();
}

$printMode = trim((string) ($_GET['print'] ?? '')) === '1';

$conn = $pdo->open();
try {
    $products = product_label_fetch_products($conn, $productIds);
} catch (Throwable $e) {
    $pdo->close();
    $_SESSION['error'] = $e->getMessage();
    header('location: products.php');
    exit();
}
$pdo->close();

try {
    $quantities = product_label_quantities($productIds, (array) ($_GET['label_qty'] ?? []), $printMode);
} catch (Throwable $e) {
    $_SESSION['error'] = $e->getMessage();
    header('location: products_labels.php?' . http_build_query(['product_ids' => $productIds]));
    exit();
}

if ($printMode) {
    $labels = [];
    foreach ($products as $product) {
        $productId = (int) ($product['id'] ?? 0);
        $sku = trim((string) ($product['resolved_sku'] ?? ''));
        $barcode = product_label_barcode_data_uri($sku);
        $copies = (int) ($quantities[$productId] ?? 1);

        for ($i = 0; $i < $copies; $i++) {
            $labels[] = [
                'name' => trim((string) ($product['name'] ?? 'Product')),
                'sku' => $sku,
                'barcode' => $barcode,
            ];
        }
    }

    $pages = array_chunk($labels, 24);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Labels</title>
  <style>
    @page {
      size: A4 portrait;
      margin: 10mm;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: Arial, sans-serif;
      color: #111;
      background: #f4f6f9;
    }

    .print-toolbar {
      position: sticky;
      top: 0;
      z-index: 20;
      display: flex;
      gap: 10px;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      background: #fff;
      border-bottom: 1px solid #dfe3e8;
    }

    .print-toolbar-actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .print-toolbar a,
    .print-toolbar button {
      border: 0;
      border-radius: 4px;
      padding: 10px 14px;
      background: #3c8dbc;
      color: #fff;
      text-decoration: none;
      cursor: pointer;
      font-size: 14px;
    }

    .print-toolbar a.secondary {
      background: #6c757d;
    }

    .page-shell {
      padding: 12px 0 24px;
    }

    .label-page {
      width: 190mm;
      min-height: 277mm;
      margin: 0 auto 12px;
      padding: 0;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      grid-auto-rows: 31.8mm;
      gap: 3mm;
    }

    .label-card {
      border: 1px solid #111;
      border-radius: 2mm;
      background: #fff;
      padding: 2.5mm;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      overflow: hidden;
    }

    .label-name {
      font-size: 10pt;
      font-weight: 700;
      line-height: 1.2;
      min-height: 8mm;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .label-meta {
      font-size: 9pt;
      margin-top: 1mm;
      margin-bottom: 1mm;
    }

    .label-sku {
      font-size: 8.5pt;
      color: #333;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .barcode-wrap {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 11mm;
      margin: 1mm 0 0.5mm;
    }

    .barcode-wrap img {
      width: 100%;
      max-height: 12mm;
      object-fit: contain;
      display: block;
    }

    .barcode-text {
      text-align: center;
      font-size: 8pt;
      letter-spacing: 0.3mm;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    @media print {
      body {
        background: #fff;
      }

      .print-toolbar {
        display: none;
      }

      .page-shell {
        padding: 0;
      }

      .label-page {
        margin: 0 auto;
        page-break-after: always;
      }

      .label-page:last-child {
        page-break-after: auto;
      }
    }
  </style>
</head>
<body>
  <div class="print-toolbar">
    <div><?php echo count($labels); ?> label(s) ready to print</div>
    <div class="print-toolbar-actions">
      <a class="secondary" href="products_labels.php?<?php echo e(http_build_query(['product_ids' => $productIds, 'label_qty' => $quantities])); ?>">Edit Quantities</a>
      <a class="secondary" href="products.php">Back to Products</a>
      <button type="button" onclick="window.print()">Print Now</button>
    </div>
  </div>

  <div class="page-shell">
    <?php foreach ($pages as $page): ?>
      <div class="label-page">
        <?php foreach ($page as $label): ?>
          <div class="label-card">
            <div class="label-name"><?php echo e($label['name']); ?></div>
            <div class="label-meta">
              <div class="label-sku"><?php echo e($label['sku']); ?></div>
            </div>
            <div class="barcode-wrap">
              <?php if ($label['barcode'] !== ''): ?>
                <img src="<?php echo e($label['barcode']); ?>" alt="<?php echo e($label['sku']); ?>">
              <?php endif; ?>
            </div>
            <div class="barcode-text"><?php echo e($label['sku']); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
    <?php
    exit();
}

include 'header.php';
?>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
  <?php include 'navbar.php'; ?>
  <?php include 'menubar.php'; ?>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Print Product Labels</h1>
      <ol class="breadcrumb">
        <li><a href="home"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="products">Products</a></li>
        <li class="active">Labels</li>
      </ol>
    </section>

    <section class="content">
      <?php
      if (isset($_SESSION['error'])) {
          echo "<div class='alert alert-danger alert-dismissible'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button><h4><i class='icon fa fa-warning'></i> Error!</h4>" . e($_SESSION['error']) . "</div>";
          unset($_SESSION['error']);
      }
      ?>
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header with-border">
              <a href="products.php" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back to Products</a>
            </div>
            <div class="box-body">
              <div class="alert alert-info">
                Review the selected products, set how many labels you want for each, then open the print preview. Quantities default to 1.
              </div>

              <form method="GET" action="products_labels.php">
                <input type="hidden" name="print" value="1">
                <div class="table-responsive">
                  <table class="table table-bordered table-striped">
                    <thead>
                      <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Status</th>
                        <th>Price</th>
                        <th>Label Qty</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($products as $product): ?>
                        <?php $productId = (int) ($product['id'] ?? 0); ?>
                        <tr>
                          <td>
                            <?php echo e((string) ($product['name'] ?? 'Product')); ?>
                            <input type="hidden" name="product_ids[]" value="<?php echo $productId; ?>">
                          </td>
                          <td><?php echo e((string) ($product['resolved_sku'] ?? '')); ?></td>
                          <td><?php echo ((int) ($product['product_status'] ?? 1) === 1) ? '<span class="label label-success">Active</span>' : '<span class="label label-default">Archived</span>'; ?></td>
                          <td><?php echo app_money((float) ($product['price'] ?? 0)); ?></td>
                          <td style="max-width: 130px;">
                            <input type="number" class="form-control" min="1" name="label_qty[<?php echo $productId; ?>]" value="<?php echo (int) ($quantities[$productId] ?? 1); ?>" required>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

                <div class="text-right">
                  <button type="submit" class="btn btn-primary btn-flat"><i class="fa fa-print"></i> Open Print Preview</button>
                </div>
              </form>
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
