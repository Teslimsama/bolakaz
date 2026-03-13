<?php
include 'session.php';
require_once __DIR__ . '/../lib/offline_statement.php';

$saleId = (int)($_GET['id'] ?? 0);
$format = strtolower(trim((string)($_GET['format'] ?? 'html')));

if ($saleId <= 0) {
    $_SESSION['error'] = 'Invalid offline sale selected for statement.';
    header('location: offline_sales.php');
    exit();
}

$conn = $pdo->open();
$statement = app_statement_fetch_by_sale_id($conn, $saleId);
$pdo->close();

if (!$statement) {
    $_SESSION['error'] = 'Offline statement could not be found.';
    header('location: offline_sales.php');
    exit();
}

if ($format === 'pdf') {
    app_statement_output_pdf($statement);
}

$documentHtml = app_statement_render_document_html($statement);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>Statement of Account - <?php echo app_statement_escape($statement['tx_ref'] ?? ''); ?></title>
  <style>
    body { margin: 0; background: #eef2f7; font-family: Arial, sans-serif; color: #1f2937; }
    .statement-page { max-width: 1040px; margin: 0 auto; padding: 24px; }
    .statement-toolbar { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; }
    .statement-toolbar a,
    .statement-toolbar button {
      appearance: none;
      border: 0;
      border-radius: 6px;
      background: #111827;
      color: #fff;
      cursor: pointer;
      display: inline-block;
      font-size: 14px;
      padding: 11px 16px;
      text-decoration: none;
    }
    .statement-toolbar .secondary { background: #4b5563; }
    .statement-toolbar .success { background: #166534; }
    @media print {
      body { background: #fff; }
      .statement-page { max-width: none; padding: 0; }
      .statement-toolbar { display: none; }
    }
  </style>
</head>
<body>
  <div class="statement-page">
    <div class="statement-toolbar">
      <a class="secondary" href="offline_sales.php">Back to Offline Sales</a>
      <a href="offline_statement?id=<?php echo (int)$statement['sale_id']; ?>&amp;format=pdf" target="_blank" rel="noopener">Download PDF</a>
      <button type="button" class="secondary" onclick="window.print()">Print</button>
      <?php if (!empty($statement['public_url'])): ?>
      <a class="secondary" href="<?php echo app_statement_escape($statement['public_url']); ?>" target="_blank" rel="noopener">Open Public View</a>
      <?php endif; ?>
      <?php if (!empty($statement['whatsapp_url'])): ?>
      <a class="success" href="<?php echo app_statement_escape($statement['whatsapp_url']); ?>" target="_blank" rel="noopener">Send via WhatsApp</a>
      <?php endif; ?>
    </div>

    <?php echo $documentHtml; ?>
  </div>
</body>
</html>
