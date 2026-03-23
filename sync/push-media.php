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

$deviceId = trim((string) ($_POST['device_id'] ?? ''));
$deviceName = trim((string) ($_POST['device_name'] ?? $deviceId));
$queueId = (int) ($_POST['queue_id'] ?? 0);
$queueUuid = trim((string) ($_POST['queue_uuid'] ?? ''));
$entityType = trim((string) ($_POST['entity_type'] ?? ''));
$entityUuid = trim((string) ($_POST['entity_uuid'] ?? ''));
$actionType = trim((string) ($_POST['action_type'] ?? ''));
$sourceUpdatedAt = trim((string) ($_POST['source_updated_at'] ?? ''));
$meta = json_decode((string) ($_POST['meta_json'] ?? '{}'), true);
$data = json_decode((string) ($_POST['data_json'] ?? '{}'), true);
$manifest = json_decode((string) ($_POST['media_manifest_json'] ?? '[]'), true);

if ($deviceId === '' || $entityType === '' || $entityUuid === '' || !is_array($data) || !is_array($manifest)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid media sync request.',
    ]);
    exit;
}

$payload = [
    'meta' => is_array($meta) ? $meta : [],
    'data' => $data,
    'media' => $manifest,
];

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

    $stored = sync_server_store_uploaded_media($entityUuid, $manifest, $_FILES, $data);
    if (!(bool) ($stored['ok'] ?? false)) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => (string) ($stored['message'] ?? 'Unable to store uploaded media.'),
        ]);
        exit;
    }

    $payload['data'] = $stored['data'];
    $item = [
        'queue_id' => $queueId,
        'queue_uuid' => $queueUuid,
        'entity_type' => $entityType,
        'entity_uuid' => $entityUuid,
        'action_type' => $actionType,
        'source_updated_at' => $sourceUpdatedAt,
        'payload' => $payload,
    ];

    try {
        $conn->beginTransaction();
        $result = sync_server_process_item($conn, $item);
        sync_server_record_receipt($conn, $deviceId, $item, (string) ($result['status'] ?? 'failed'), (string) ($result['message'] ?? ''));
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
        sync_server_record_receipt($conn, $deviceId, $item, 'failed', $e->getMessage());
    }

    if ((string) ($result['status'] ?? '') === 'synced') {
        sync_mark_device_last_sync($conn, $deviceId);
    }

    echo json_encode([
        'success' => true,
        'server_time' => sync_now(),
        'result' => [
            'queue_id' => $queueId,
            'queue_uuid' => $queueUuid,
            'entity_type' => $entityType,
            'entity_uuid' => $entityUuid,
            'status' => (string) ($result['status'] ?? 'failed'),
            'message' => (string) ($result['message'] ?? ''),
            'server_uuid' => (string) ($result['server_uuid'] ?? $entityUuid),
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to process media sync request.',
    ]);
} finally {
    $pdo->close();
}
