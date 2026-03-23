<?php

require_once __DIR__ . '/../CreateDb.php';
require_once __DIR__ . '/../lib/sync.php';

header('Content-Type: application/json; charset=UTF-8');

if (!sync_is_enabled() || !sync_is_server()) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Sync server is disabled.',
    ]);
    exit;
}

sync_server_require_token();

$payload = [];
$rawInput = file_get_contents('php://input');
if (is_string($rawInput) && trim($rawInput) !== '') {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

$deviceId = trim((string) ($payload['device_id'] ?? $_GET['device_id'] ?? ''));
$deviceName = trim((string) ($payload['device_name'] ?? $_GET['device_name'] ?? $deviceId));

if ($deviceId === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'device_id is required.',
    ]);
    exit;
}

$conn = $pdo->open();

try {
    $registration = sync_server_register_device($conn, $deviceId, $deviceName);
    if (!(bool) ($registration['allowed'] ?? false)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => (string) ($registration['message'] ?? 'This device is disabled.'),
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'server_time' => sync_now(),
        'device_id' => $deviceId,
        'device_name' => $deviceName,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to process sync ping.',
    ]);
} finally {
    $pdo->close();
}
