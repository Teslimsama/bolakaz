<?php

require_once __DIR__ . '/../admin/session.php';
require_once __DIR__ . '/../lib/sync.php';

header('Content-Type: application/json; charset=UTF-8');

if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$conn = $pdo->open();

try {
    $resetFailed = isset($_POST['retry_failed']) || isset($_GET['retry_failed']);
    $resetCount = 0;
    if ($resetFailed) {
        $resetCount = sync_reset_failed_items($conn, ['failed', 'conflict']);
        $resetCount += sync_reset_failed_pull_items($conn, ['failed']);
    }

    if (!sync_is_client()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'This admin is the live sync destination. Run sync from the local client app instead.',
            'retry_reset_count' => $resetCount,
            'status' => sync_status_snapshot($conn, true),
        ]);
        return;
    }

    $run = sync_client_run($conn);
    $snapshot = sync_client_status_snapshot($conn, true);
    echo json_encode([
        'success' => (bool) ($run['success'] ?? false),
        'message' => (string) ($run['message'] ?? 'Sync run completed.'),
        'retry_reset_count' => $resetCount,
        'run' => $run,
        'status' => $snapshot,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to run sync.',
    ]);
} finally {
    $pdo->close();
}
