<?php

require_once __DIR__ . '/../CreateDb.php';
require_once __DIR__ . '/../lib/offline_statement.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script must be run via CLI.\n";
    exit(1);
}

$conn = $pdo->open();

try {
    echo "Starting offline statement migration...\n";

    $columns = $conn->query("SHOW COLUMNS FROM sales")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('customer_name', $columns, true)) {
        $conn->exec("ALTER TABLE sales ADD COLUMN customer_name VARCHAR(150) NULL AFTER payment_status");
        echo "Added sales.customer_name.\n";
    } else {
        echo "sales.customer_name already exists.\n";
    }

    if (!in_array('statement_share_token', $columns, true)) {
        $conn->exec("ALTER TABLE sales ADD COLUMN statement_share_token CHAR(64) NULL AFTER customer_name");
        echo "Added sales.statement_share_token.\n";
    } else {
        echo "sales.statement_share_token already exists.\n";
    }

    $stmt = $conn->prepare("SELECT id FROM sales WHERE is_offline = 1 AND (statement_share_token IS NULL OR statement_share_token = '')");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updated = 0;
    foreach ($rows as $row) {
        $token = app_statement_generate_unique_token($conn);
        $update = $conn->prepare("UPDATE sales SET statement_share_token = :token WHERE id = :id");
        $update->execute([
            'token' => $token,
            'id' => (int) ($row['id'] ?? 0),
        ]);
        $updated++;
    }

    if ($updated > 0) {
        echo "Backfilled {$updated} statement share token(s).\n";
    } else {
        echo "No offline sales required token backfill.\n";
    }

    $indexes = $conn->query("SHOW INDEX FROM sales WHERE Key_name = 'uniq_statement_share_token'")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($indexes)) {
        $conn->exec("ALTER TABLE sales ADD UNIQUE KEY uniq_statement_share_token (statement_share_token)");
        echo "Added unique index on sales.statement_share_token.\n";
    } else {
        echo "Unique index on sales.statement_share_token already exists.\n";
    }

    echo "Offline statement migration completed successfully.\n";
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    $pdo->close();
}
