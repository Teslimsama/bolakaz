<?php

require_once __DIR__ . '/../admin/session.php';
require_once __DIR__ . '/../lib/sync.php';

header('Content-Type: application/json; charset=UTF-8');

$conn = $pdo->open();

try {
    $resetFailed = isset($_POST['retry_failed']) || isset($_GET['retry_failed']);
    $resetCount = 0;
    if ($resetFailed) {
        $resetCount = sync_reset_failed_items($conn, ['failed', 'conflict']);
    }

    $spawned = sync_spawn_runner();
    echo json_encode([
        'success' => $spawned,
        'message' => $spawned ? 'Sync runner started.' : 'Unable to start sync runner.',
        'retry_reset_count' => $resetCount,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to trigger sync runner.',
    ]);
} finally {
    $pdo->close();
}
