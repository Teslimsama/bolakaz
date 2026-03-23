<?php

require_once __DIR__ . '/../admin/session.php';
require_once __DIR__ . '/../lib/sync.php';

header('Content-Type: application/json; charset=UTF-8');

$conn = $pdo->open();

try {
    $snapshot = sync_client_status_snapshot($conn, true);
    echo json_encode([
        'success' => true,
        'status' => $snapshot,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load sync status.',
    ]);
} finally {
    $pdo->close();
}
