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
  <style>
    body { margin: 0; background: #f3f4f6; font-family: Arial, sans-serif; color: #1f2937; }
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
    .statement-note {
      background: #fff7ed;
      border: 1px solid #fdba74;
      border-radius: 8px;
      color: #9a3412;
      margin-bottom: 16px;
      padding: 12px 14px;
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
    <div class="statement-note">This is a read-only shared statement.</div>
    <?php endif; ?>

    <div class="statement-toolbar">
      <?php if ($accessMode === 'owner'): ?>
      <a class="secondary" href="profile.php#trans">Back to Profile</a>
      <?php endif; ?>
      <a href="<?php echo app_statement_escape($accessMode === 'token' ? ('offline_statement.php?token=' . rawurlencode($token) . '&format=pdf') : ('offline_statement.php?id=' . (int)$statement['sale_id'] . '&format=pdf')); ?>" target="_blank" rel="noopener">Download PDF</a>
      <button type="button" class="secondary" onclick="window.print()">Print</button>
    </div>

    <?php echo $documentHtml; ?>
  </div>
</body>
</html>
