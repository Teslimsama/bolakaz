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

$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON payload.',
    ]);
    exit;
}

$deviceId = trim((string) ($payload['device_id'] ?? ''));
$deviceName = trim((string) ($payload['device_name'] ?? $deviceId));
$items = $payload['items'] ?? [];

if ($deviceId === '' || !is_array($items)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'device_id and items are required.',
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

    $results = [];
    foreach ($items as $item) {
        $entityType = trim((string) ($item['entity_type'] ?? ''));
        $entityUuid = trim((string) ($item['entity_uuid'] ?? ''));
        $queueId = (int) ($item['queue_id'] ?? 0);

        try {
            $conn->beginTransaction();
            $result = sync_server_process_item($conn, is_array($item) ? $item : []);
            sync_server_record_receipt($conn, $deviceId, is_array($item) ? $item : [], (string) ($result['status'] ?? 'failed'), (string) ($result['message'] ?? ''));
            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $result = [
                'status' => 'failed',
                'message' => $e->getMessage(),
                'server_uuid' => $entityUuid,
            ];
            sync_server_record_receipt($conn, $deviceId, is_array($item) ? $item : [], 'failed', $e->getMessage());
        }

        $results[] = [
            'queue_id' => $queueId,
            'queue_uuid' => (string) ($item['queue_uuid'] ?? ''),
            'entity_type' => $entityType,
            'entity_uuid' => $entityUuid,
            'status' => (string) ($result['status'] ?? 'failed'),
            'message' => (string) ($result['message'] ?? ''),
            'server_uuid' => (string) ($result['server_uuid'] ?? $entityUuid),
        ];
    }

    sync_mark_device_last_sync($conn, $deviceId);

    echo json_encode([
        'success' => true,
        'server_time' => sync_now(),
        'results' => $results,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to process sync batch.',
    ]);
} finally {
    $pdo->close();
}
