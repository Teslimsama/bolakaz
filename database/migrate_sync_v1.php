<?php

require_once __DIR__ . '/../CreateDb.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script must be run via CLI.\n";
    exit(1);
}

$conn = $pdo->open();

function sync_migration_table_exists(PDO $conn, string $table): bool
{
    $stmt = $conn->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table LIMIT 1');
    $stmt->execute(['table' => $table]);
    return (bool) $stmt->fetchColumn();
}

function sync_migration_column_exists(PDO $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column LIMIT 1');
    $stmt->execute(['table' => $table, 'column' => $column]);
    return (bool) $stmt->fetchColumn();
}

function sync_migration_index_exists(PDO $conn, string $table, string $index): bool
{
    $stmt = $conn->prepare('SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = :table AND index_name = :index_name LIMIT 1');
    $stmt->execute(['table' => $table, 'index_name' => $index]);
    return (bool) $stmt->fetchColumn();
}

function sync_migration_add_column(PDO $conn, string $table, string $column, string $definition, ?string $after = null): void
{
    if (sync_migration_column_exists($conn, $table, $column)) {
        echo " - {$table}.{$column} already exists.\n";
        return;
    }

    $sql = "ALTER TABLE {$table} ADD COLUMN {$column} {$definition}";
    if ($after !== null && $after !== '') {
        $sql .= " AFTER {$after}";
    }
    $conn->exec($sql);
    echo " - Added {$table}.{$column}.\n";
}

function sync_migration_backfill_uuid(PDO $conn, string $table, string $pk): void
{
    if (!sync_migration_column_exists($conn, $table, 'uuid')) {
        return;
    }

    $stmt = $conn->query("SELECT {$pk} FROM {$table} WHERE uuid IS NULL OR uuid = ''");
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($rows as $id) {
        $uuid = sprintf(
            '%s-%s-%s-%s-%s',
            substr(bin2hex(random_bytes(4)), 0, 8),
            substr(bin2hex(random_bytes(2)), 0, 4),
            '4' . substr(bin2hex(random_bytes(2)), 1, 3),
            dechex((hexdec(substr(bin2hex(random_bytes(2)), 0, 1)) & 0x3) | 0x8) . substr(bin2hex(random_bytes(2)), 1, 3),
            substr(bin2hex(random_bytes(6)), 0, 12)
        );
        $update = $conn->prepare("UPDATE {$table} SET uuid = :uuid WHERE {$pk} = :id");
        $update->execute([
            'uuid' => $uuid,
            'id' => (int) $id,
        ]);
    }

    if (!empty($rows)) {
        echo " - Backfilled UUIDs for {$table}.\n";
    }
}

function sync_migration_add_uuid_bundle(PDO $conn, string $table, string $pk, ?string $afterColumn = null): void
{
    if (!sync_migration_table_exists($conn, $table)) {
        echo " - Skipped {$table}; table not found.\n";
        return;
    }

    sync_migration_add_column($conn, $table, 'uuid', 'CHAR(36) NULL DEFAULT NULL', $afterColumn);
    sync_migration_add_column($conn, $table, 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'uuid');
    sync_migration_add_column($conn, $table, 'updated_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'created_at');
    sync_migration_add_column($conn, $table, 'last_synced_at', 'DATETIME NULL DEFAULT NULL', 'updated_at');
    sync_migration_backfill_uuid($conn, $table, $pk);

    if (!sync_migration_index_exists($conn, $table, 'uniq_' . $table . '_uuid')) {
        $conn->exec("ALTER TABLE {$table} ADD UNIQUE KEY uniq_{$table}_uuid (uuid)");
        echo " - Added unique UUID index on {$table}.\n";
    }
}

try {
    echo "Starting sync v1 migration...\n";

    sync_migration_add_uuid_bundle($conn, 'users', 'id', 'email');
    sync_migration_add_uuid_bundle($conn, 'category', 'id', 'name');
    sync_migration_add_uuid_bundle($conn, 'products', 'id', 'category_id');
    sync_migration_add_uuid_bundle($conn, 'gallery_images', 'id', 'gallery_id');
    sync_migration_add_uuid_bundle($conn, 'shippings', 'id', 'type');
    sync_migration_add_uuid_bundle($conn, 'coupons', 'id', 'code');
    sync_migration_add_uuid_bundle($conn, 'banner', 'id', 'name');
    sync_migration_add_uuid_bundle($conn, 'ads', 'id', 'text_align');
    sync_migration_add_uuid_bundle($conn, 'web_details', 'id', 'site_email');
    sync_migration_add_uuid_bundle($conn, 'sales', 'id', 'user_id');
    sync_migration_add_uuid_bundle($conn, 'details', 'id', 'sales_id');
    sync_migration_add_uuid_bundle($conn, 'offline_payments', 'id', 'sales_id');

    if (!sync_migration_table_exists($conn, 'devices')) {
        $conn->exec(
            "CREATE TABLE devices (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                device_id VARCHAR(120) NOT NULL,
                device_name VARCHAR(190) NOT NULL,
                last_seen_at DATETIME NULL,
                last_sync_at DATETIME NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_devices_device_id (device_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        echo " - Created devices table.\n";
    }

    if (!sync_migration_table_exists($conn, 'sync_queue')) {
        $conn->exec(
            "CREATE TABLE sync_queue (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                queue_uuid CHAR(36) NOT NULL,
                entity_type VARCHAR(80) NOT NULL,
                entity_uuid CHAR(36) NOT NULL,
                action_type VARCHAR(20) NOT NULL,
                payload_json LONGTEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                attempts INT NOT NULL DEFAULT 0,
                locked_at DATETIME NULL,
                next_attempt_at DATETIME NULL,
                source_updated_at DATETIME NULL,
                source_device_id VARCHAR(120) NOT NULL,
                last_error TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                synced_at DATETIME NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_sync_queue_uuid (queue_uuid),
                KEY idx_sync_queue_status (status, next_attempt_at),
                KEY idx_sync_queue_entity (entity_type, entity_uuid),
                KEY idx_sync_queue_locked (locked_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        echo " - Created sync_queue table.\n";
    }

    sync_migration_add_column($conn, 'sync_queue', 'conflict_reason', 'TEXT NULL DEFAULT NULL', 'last_error');
    sync_migration_add_column($conn, 'sync_queue', 'conflict_detected_at', 'DATETIME NULL DEFAULT NULL', 'conflict_reason');

    if (!sync_migration_table_exists($conn, 'sync_receipts')) {
        $conn->exec(
            "CREATE TABLE sync_receipts (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                device_id VARCHAR(120) NOT NULL,
                queue_uuid CHAR(36) NOT NULL,
                entity_type VARCHAR(80) NOT NULL,
                entity_uuid CHAR(36) NOT NULL,
                action_type VARCHAR(20) NOT NULL,
                status VARCHAR(20) NOT NULL,
                message TEXT NULL,
                received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                processed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_sync_receipts_device (device_id, processed_at),
                KEY idx_sync_receipts_entity (entity_type, entity_uuid)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        echo " - Created sync_receipts table.\n";
    }

    if (!sync_migration_table_exists($conn, 'sync_outbox')) {
        $conn->exec(
            "CREATE TABLE sync_outbox (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                event_uuid CHAR(36) NOT NULL,
                entity_type VARCHAR(80) NOT NULL,
                entity_uuid CHAR(36) NOT NULL,
                action_type VARCHAR(20) NOT NULL,
                payload_json LONGTEXT NOT NULL,
                source_side VARCHAR(20) NOT NULL,
                source_device_id VARCHAR(120) NOT NULL,
                source_updated_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_sync_outbox_event_uuid (event_uuid),
                KEY idx_sync_outbox_cursor (id),
                KEY idx_sync_outbox_entity (entity_type, entity_uuid),
                KEY idx_sync_outbox_source (source_side, source_device_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        echo " - Created sync_outbox table.\n";
    }

    if (!sync_migration_table_exists($conn, 'sync_pull_queue')) {
        $conn->exec(
            "CREATE TABLE sync_pull_queue (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                event_id BIGINT UNSIGNED NOT NULL,
                event_uuid CHAR(36) NOT NULL,
                entity_type VARCHAR(80) NOT NULL,
                entity_uuid CHAR(36) NOT NULL,
                action_type VARCHAR(20) NOT NULL,
                payload_json LONGTEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                applied TINYINT(1) NOT NULL DEFAULT 0,
                attempts INT NOT NULL DEFAULT 0,
                locked_at DATETIME NULL,
                next_attempt_at DATETIME NULL,
                last_error TEXT NULL,
                conflict_reason TEXT NULL,
                conflict_detected_at DATETIME NULL,
                source_side VARCHAR(20) NOT NULL,
                source_device_id VARCHAR(120) NOT NULL,
                source_updated_at DATETIME NULL,
                pulled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                applied_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_sync_pull_queue_event_uuid (event_uuid),
                KEY idx_sync_pull_queue_cursor (event_id),
                KEY idx_sync_pull_queue_status (status, next_attempt_at),
                KEY idx_sync_pull_queue_applied (applied, event_id),
                KEY idx_sync_pull_queue_entity (entity_type, entity_uuid)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        echo " - Created sync_pull_queue table.\n";
    }

    sync_migration_add_column($conn, 'sync_pull_queue', 'locked_at', 'DATETIME NULL DEFAULT NULL', 'attempts');

    if (!sync_migration_table_exists($conn, 'sync_state')) {
        $conn->exec(
            "CREATE TABLE sync_state (
                device_id VARCHAR(120) NOT NULL,
                last_push_at DATETIME NULL,
                last_pull_cursor_fetched BIGINT UNSIGNED NULL,
                last_pull_cursor_applied BIGINT UNSIGNED NULL,
                last_pull_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (device_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        echo " - Created sync_state table.\n";
    }

    echo "Sync v1 migration completed successfully.\n";
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    $pdo->close();
}
