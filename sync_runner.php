<?php

require_once __DIR__ . '/CreateDb.php';
require_once __DIR__ . '/lib/sync.php';

$conn = $pdo->open();

try {
    $result = sync_client_run($conn);

    if (PHP_SAPI === 'cli') {
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    } else {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($result, JSON_UNESCAPED_SLASHES);
    }
} catch (Throwable $e) {
    if (function_exists('sync_log_message')) {
        sync_log_message('error', 'Sync runner failed.', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
    } else {
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => 'Sync runner failed.',
        ]);
    }
} finally {
    $pdo->close();
}
