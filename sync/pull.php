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

$deviceId = trim((string) ($_GET['device_id'] ?? ''));
$deviceName = trim((string) ($_GET['device_name'] ?? $deviceId));
$afterId = max(0, (int) ($_GET['after_id'] ?? 0));
$limit = max(1, min(100, (int) ($_GET['limit'] ?? (sync_config()['batch_size'] ?? 20))));

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

    if (!sync_table_exists($conn, 'sync_outbox')) {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'message' => 'Sync outbox is unavailable.',
        ]);
        exit;
    }

    $stmt = $conn->prepare(
        'SELECT id, event_uuid, entity_type, entity_uuid, action_type, payload_json, source_side, source_device_id, source_updated_at
         FROM sync_outbox
         WHERE id > :after_id
         ORDER BY id ASC
         LIMIT :limit'
    );
    $stmt->bindValue(':after_id', $afterId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $items = [];
    $nextCursor = $afterId;

    foreach ($rows as $row) {
        $payload = json_decode((string) ($row['payload_json'] ?? ''), true);
        $eventId = (int) ($row['id'] ?? 0);
        if ($eventId > $nextCursor) {
            $nextCursor = $eventId;
        }

        $items[] = [
            'event_id' => $eventId,
            'event_uuid' => (string) ($row['event_uuid'] ?? ''),
            'entity_type' => (string) ($row['entity_type'] ?? ''),
            'entity_uuid' => (string) ($row['entity_uuid'] ?? ''),
            'action_type' => (string) ($row['action_type'] ?? ''),
            'source_side' => (string) ($row['source_side'] ?? ''),
            'source_device_id' => (string) ($row['source_device_id'] ?? ''),
            'source_updated_at' => (string) ($row['source_updated_at'] ?? ''),
            'payload' => is_array($payload) ? $payload : [],
        ];
    }

    sync_mark_device_last_sync($conn, $deviceId);

    echo json_encode([
        'success' => true,
        'server_time' => sync_now(),
        'next_cursor' => $nextCursor,
        'items' => $items,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to process sync pull request.',
    ]);
} finally {
    $pdo->close();
}
