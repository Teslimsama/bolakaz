<?php

require_once __DIR__ . '/../CreateDb.php';
require_once __DIR__ . '/../lib/sync.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script must be run via CLI.\n";
    exit(1);
}

$conn = $pdo->open();

try {
    $result = sync_backfill_repair($conn, [
        'reset_failed' => true,
    ]);

    echo "Sync repair complete.\n";
    echo $result['message'] . "\n";

    if (!empty($result['backfilled_by_entity']) && is_array($result['backfilled_by_entity'])) {
        foreach ($result['backfilled_by_entity'] as $entityType => $count) {
            echo " - {$entityType}: {$count} uuid(s) backfilled.\n";
        }
    }
} catch (Throwable $e) {
    echo "Sync repair failed: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    $pdo->close();
}
