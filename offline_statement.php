<?php
require_once __DIR__ . '/CreateDb.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/lib/offline_statement.php';

app_start_session();
app_get_csrf_token();
app_require_csrf_for_mutations();

$user = null;
if (!empty($_SESSION['user'])) {
    $conn = $pdo->open();
    try {
        $userStmt = $conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $userStmt->execute(['id' => (int)$_SESSION['user']]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $user = null;
    } finally {
        $pdo->close();
    }
}

$token = trim((string)($_GET['token'] ?? ''));
$saleId = (int)($_GET['id'] ?? 0);
$format = strtolower(trim((string)($_GET['format'] ?? 'html')));

$conn = $pdo->open();
$statement = null;
$accessMode = 'token';

try {
    if ($token !== '') {
        $statement = app_statement_fetch_by_token($conn, $token);
    } elseif ($saleId > 0) {
        $accessMode = 'owner';
        if (empty($user['id'])) {
            $_SESSION['error'] = 'Please sign in to view this statement.';
            header('location: signin.php');
            exit();
        }

        $statement = app_statement_fetch_by_sale_id($conn, $saleId);
        if (!$statement || (int)$statement['user_id'] !== (int)$user['id']) {
            http_response_code(403);
            echo 'You are not allowed to view this statement.';
            exit();
        }
    } else {
        http_response_code(404);
        echo 'Statement not found.';
        exit();
    }
} finally {
    $pdo->close();
}

if (!$statement) {
    http_response_code(404);
    echo 'Statement not found.';
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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,700&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --sf-accent: #0f766e;
      --sf-bg: #f5f2ec;
      --sf-text: #171515;
    }
    body { margin: 0; background: var(--sf-bg); font-family: "Manrope", Arial, sans-serif; color: var(--sf-text); }
    .statement-page { max-width: 1040px; margin: 0 auto; padding: 40px 24px; }
    .statement-toolbar { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
    .statement-toolbar a,
    .statement-toolbar button {
      appearance: none;
      border: 0;
      border-radius: 999px;
      background: var(--sf-accent);
      color: #fff;
      cursor: pointer;
      display: inline-block;
      font-size: 14px;
      font-weight: 700;
      padding: 12px 24px;
      text-decoration: none;
      transition: opacity 0.2s;
    }
    .statement-toolbar a:hover,
    .statement-toolbar button:hover { opacity: 0.9; }
    .statement-toolbar .secondary { background: #111; }
    .statement-toolbar .success { background: #166534; }
    .statement-note {
      background: #fff8ee;
      border: 1px solid #e7dfd6;
      border-radius: 12px;
      color: #7d644d;
      margin-bottom: 20px;
      padding: 14px 18px;
      font-size: 14px;
    }
    @media print {
      body { background: #fff; }
      .statement-page { max-width: none; padding: 0; }
      .statement-toolbar { display: none; }
      .statement-note { display: none; }
    }
  </style>
</head>
<body>
  <div class="statement-page">
    <?php if ($accessMode === 'token'): ?>
    <div class="statement-note">
        <i class="fa fa-info-circle"></i> This is a read-only shared statement for your recent purchase at Bolakaz.
    </div>
    <?php endif; ?>

    <div class="statement-toolbar">
      <?php if ($accessMode === 'owner'): ?>
      <a class="secondary" href="profile.php#trans">Back to Profile</a>
      <?php endif; ?>
      <a href="<?php echo app_statement_escape($accessMode === 'token' ? ('offline_statement.php?token=' . rawurlencode($token) . '&format=pdf') : ('offline_statement.php?id=' . (int)$statement['sale_id'] . '&format=pdf')); ?>" target="_blank" rel="noopener">Download PDF</a>
      <button type="button" class="secondary" onclick="window.print()">Print</button>
    </div>

    <?php echo app_statement_render_document_html($statement, false, 'storefront'); ?>
  </div>
</body>
</html>
