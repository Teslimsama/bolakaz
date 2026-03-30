<?php

require_once __DIR__ . '/../admin/session.php';
require_once __DIR__ . '/../lib/sync.php';

header('Content-Type: application/json; charset=UTF-8');

if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$conn = $pdo->open();

try {
    $repair = sync_backfill_repair($conn, [
        'reset_failed' => true,
    ]);

    echo json_encode([
        'success' => true,
        'message' => (string) ($repair['message'] ?? 'Sync repair completed.'),
        'repair' => $repair,
        'status' => sync_status_snapshot($conn, true),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to run sync repair.',
    ]);
} finally {
    $pdo->close();
}
