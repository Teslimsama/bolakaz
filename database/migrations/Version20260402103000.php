<?php

declare(strict_types=1);

namespace Bolakaz\Migrations;

require_once __DIR__ . '/AbstractBolakazMigration.php';

use Doctrine\DBAL\Schema\Schema;

final class Version20260402103000 extends AbstractBolakazMigration
{
    public function getDescription(): string
    {
        return 'Adopts the sync v1 schema, UUID bundles, and queue tables into tracked migrations.';
    }

    public function up(Schema $schema): void
    {
        $this->guardMySql();

        $uuidBundles = [
            ['table' => 'users', 'pk' => 'id', 'after' => 'email'],
            ['table' => 'category', 'pk' => 'id', 'after' => 'name'],
            ['table' => 'products', 'pk' => 'id', 'after' => 'category_id'],
            ['table' => 'gallery_images', 'pk' => 'id', 'after' => 'gallery_id'],
            ['table' => 'shippings', 'pk' => 'id', 'after' => 'type'],
            ['table' => 'coupons', 'pk' => 'id', 'after' => 'code'],
            ['table' => 'banner', 'pk' => 'id', 'after' => 'name'],
            ['table' => 'ads', 'pk' => 'id', 'after' => 'text_align'],
            ['table' => 'web_details', 'pk' => 'id', 'after' => 'site_email'],
            ['table' => 'sales', 'pk' => 'id', 'after' => 'user_id'],
            ['table' => 'details', 'pk' => 'id', 'after' => 'sales_id'],
            ['table' => 'offline_payments', 'pk' => 'id', 'after' => 'sales_id'],
        ];

        foreach ($uuidBundles as $bundle) {
            $this->ensureUuidBundle(
                (string) $bundle['table'],
                (string) $bundle['pk'],
                (string) $bundle['after']
            );
        }

        $this->ensureDevicesTable();
        $this->ensureSyncQueueTable();
        $this->ensureSyncReceiptsTable();
        $this->ensureSyncOutboxTable();
        $this->ensureSyncPullQueueTable();
        $this->ensureSyncStateTable();
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('This migration introduces sync metadata and UUIDs that should remain stable.');
    }

    private function ensureUuidBundle(string $table, string $pk, string $afterColumn): void
    {
        if (!$this->tableExists($table)) {
            return;
        }

        $after = $this->columnExists($table, $afterColumn) ? $afterColumn : null;
        $this->addColumnIfMissing($table, 'uuid', 'CHAR(36) NULL DEFAULT NULL', $after);
        $this->addColumnIfMissing($table, 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'uuid');
        $this->addColumnIfMissing($table, 'updated_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'created_at');
        $this->addColumnIfMissing($table, 'last_synced_at', 'DATETIME NULL DEFAULT NULL', 'updated_at');

        if (!$this->columnExists($table, 'uuid')) {
            return;
        }

        $missingRows = $this->connection->fetchAllAssociative(
            sprintf('SELECT `%s` AS row_id FROM `%s` WHERE uuid IS NULL OR TRIM(uuid) = \'\' ORDER BY `%s` ASC', $pk, $table, $pk)
        );

        foreach ($missingRows as $row) {
            $id = (int) ($row['row_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $this->connection->executeStatement(
                sprintf('UPDATE `%s` SET uuid = ? WHERE `%s` = ?', $table, $pk),
                [$this->generateUuid(), $id]
            );
        }

        $duplicateGroups = $this->connection->fetchAllAssociative(
            sprintf(
                'SELECT uuid FROM `%s` WHERE uuid IS NOT NULL AND TRIM(uuid) <> \'\' GROUP BY uuid HAVING COUNT(*) > 1',
                $table
            )
        );

        foreach ($duplicateGroups as $group) {
            $uuid = trim((string) ($group['uuid'] ?? ''));
            if ($uuid === '') {
                continue;
            }

            $rows = $this->connection->fetchAllAssociative(
                sprintf('SELECT `%s` AS row_id FROM `%s` WHERE uuid = ? ORDER BY `%s` ASC', $pk, $table, $pk),
                [$uuid]
            );

            foreach (array_slice($rows, 1) as $row) {
                $id = (int) ($row['row_id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }

                $this->connection->executeStatement(
                    sprintf('UPDATE `%s` SET uuid = ? WHERE `%s` = ?', $table, $pk),
                    [$this->generateUuid(), $id]
                );
            }
        }

        $this->createUniqueIndexIfMissing($table, 'uniq_' . $table . '_uuid', '(`uuid`)');
    }

    private function ensureDevicesTable(): void
    {
        $this->createTableIfMissing('devices', <<<'SQL'
CREATE TABLE `devices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `device_id` VARCHAR(120) NOT NULL,
  `device_name` VARCHAR(190) NOT NULL,
  `last_seen_at` DATETIME NULL,
  `last_sync_at` DATETIME NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_devices_device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

        if (!$this->tableExists('devices')) {
            return;
        }

        $this->addColumnIfMissing('devices', 'last_seen_at', 'DATETIME NULL', 'device_name');
        $this->addColumnIfMissing('devices', 'last_sync_at', 'DATETIME NULL', 'last_seen_at');
        $this->addColumnIfMissing('devices', 'status', "VARCHAR(30) NOT NULL DEFAULT 'active'", 'last_sync_at');
        $this->addColumnIfMissing('devices', 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'status');
        $this->addColumnIfMissing('devices', 'updated_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'created_at');
        $this->createUniqueIndexIfMissing('devices', 'uniq_devices_device_id', '(`device_id`)');
    }

    private function ensureSyncQueueTable(): void
    {
        $this->createTableIfMissing('sync_queue', <<<'SQL'
CREATE TABLE `sync_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue_uuid` CHAR(36) NOT NULL,
  `entity_type` VARCHAR(80) NOT NULL,
  `entity_uuid` CHAR(36) NOT NULL,
  `action_type` VARCHAR(20) NOT NULL,
  `payload_json` LONGTEXT NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `attempts` INT NOT NULL DEFAULT 0,
  `locked_at` DATETIME NULL,
  `next_attempt_at` DATETIME NULL,
  `source_updated_at` DATETIME NULL,
  `source_device_id` VARCHAR(120) NOT NULL,
  `last_error` TEXT NULL,
  `conflict_reason` TEXT NULL,
  `conflict_detected_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `synced_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_sync_queue_uuid` (`queue_uuid`),
  KEY `idx_sync_queue_status` (`status`, `next_attempt_at`),
  KEY `idx_sync_queue_entity` (`entity_type`, `entity_uuid`),
  KEY `idx_sync_queue_locked` (`locked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

        if (!$this->tableExists('sync_queue')) {
            return;
        }

        $this->addColumnIfMissing('sync_queue', 'conflict_reason', 'TEXT NULL DEFAULT NULL', 'last_error');
        $this->addColumnIfMissing('sync_queue', 'conflict_detected_at', 'DATETIME NULL DEFAULT NULL', 'conflict_reason');
        $this->addColumnIfMissing('sync_queue', 'locked_at', 'DATETIME NULL DEFAULT NULL', 'attempts');
        $this->addColumnIfMissing('sync_queue', 'next_attempt_at', 'DATETIME NULL DEFAULT NULL', 'locked_at');
        $this->addColumnIfMissing('sync_queue', 'source_updated_at', 'DATETIME NULL DEFAULT NULL', 'next_attempt_at');
        $this->addColumnIfMissing('sync_queue', 'last_error', 'TEXT NULL DEFAULT NULL', 'source_device_id');
        $this->addColumnIfMissing('sync_queue', 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'conflict_detected_at');
        $this->addColumnIfMissing('sync_queue', 'updated_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'created_at');
        $this->addColumnIfMissing('sync_queue', 'synced_at', 'DATETIME NULL DEFAULT NULL', 'updated_at');

        $this->createUniqueIndexIfMissing('sync_queue', 'uniq_sync_queue_uuid', '(`queue_uuid`)');
        $this->createIndexIfMissing('sync_queue', 'idx_sync_queue_status', '(`status`, `next_attempt_at`)');
        $this->createIndexIfMissing('sync_queue', 'idx_sync_queue_entity', '(`entity_type`, `entity_uuid`)');
        $this->createIndexIfMissing('sync_queue', 'idx_sync_queue_locked', '(`locked_at`)');
    }

    private function ensureSyncReceiptsTable(): void
    {
        $this->createTableIfMissing('sync_receipts', <<<'SQL'
CREATE TABLE `sync_receipts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `device_id` VARCHAR(120) NOT NULL,
  `queue_uuid` CHAR(36) NOT NULL,
  `entity_type` VARCHAR(80) NOT NULL,
  `entity_uuid` CHAR(36) NOT NULL,
  `action_type` VARCHAR(20) NOT NULL,
  `status` VARCHAR(20) NOT NULL,
  `message` TEXT NULL,
  `received_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sync_receipts_device` (`device_id`, `processed_at`),
  KEY `idx_sync_receipts_entity` (`entity_type`, `entity_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

        if (!$this->tableExists('sync_receipts')) {
            return;
        }

        $this->addColumnIfMissing('sync_receipts', 'received_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'message');
        $this->addColumnIfMissing('sync_receipts', 'processed_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'received_at');
        $this->createIndexIfMissing('sync_receipts', 'idx_sync_receipts_device', '(`device_id`, `processed_at`)');
        $this->createIndexIfMissing('sync_receipts', 'idx_sync_receipts_entity', '(`entity_type`, `entity_uuid`)');
    }

    private function ensureSyncOutboxTable(): void
    {
        $this->createTableIfMissing('sync_outbox', <<<'SQL'
CREATE TABLE `sync_outbox` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_uuid` CHAR(36) NOT NULL,
  `entity_type` VARCHAR(80) NOT NULL,
  `entity_uuid` CHAR(36) NOT NULL,
  `action_type` VARCHAR(20) NOT NULL,
  `payload_json` LONGTEXT NOT NULL,
  `source_side` VARCHAR(20) NOT NULL,
  `source_device_id` VARCHAR(120) NOT NULL,
  `source_updated_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_sync_outbox_event_uuid` (`event_uuid`),
  KEY `idx_sync_outbox_cursor` (`id`),
  KEY `idx_sync_outbox_entity` (`entity_type`, `entity_uuid`),
  KEY `idx_sync_outbox_source` (`source_side`, `source_device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

        if (!$this->tableExists('sync_outbox')) {
            return;
        }

        $this->addColumnIfMissing('sync_outbox', 'source_updated_at', 'DATETIME NULL DEFAULT NULL', 'source_device_id');
        $this->addColumnIfMissing('sync_outbox', 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'source_updated_at');
        $this->createUniqueIndexIfMissing('sync_outbox', 'uniq_sync_outbox_event_uuid', '(`event_uuid`)');
        $this->createIndexIfMissing('sync_outbox', 'idx_sync_outbox_cursor', '(`id`)');
        $this->createIndexIfMissing('sync_outbox', 'idx_sync_outbox_entity', '(`entity_type`, `entity_uuid`)');
        $this->createIndexIfMissing('sync_outbox', 'idx_sync_outbox_source', '(`source_side`, `source_device_id`)');
    }

    private function ensureSyncPullQueueTable(): void
    {
        $this->createTableIfMissing('sync_pull_queue', <<<'SQL'
CREATE TABLE `sync_pull_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `event_uuid` CHAR(36) NOT NULL,
  `entity_type` VARCHAR(80) NOT NULL,
  `entity_uuid` CHAR(36) NOT NULL,
  `action_type` VARCHAR(20) NOT NULL,
  `payload_json` LONGTEXT NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `applied` TINYINT(1) NOT NULL DEFAULT 0,
  `attempts` INT NOT NULL DEFAULT 0,
  `locked_at` DATETIME NULL,
  `next_attempt_at` DATETIME NULL,
  `last_error` TEXT NULL,
  `conflict_reason` TEXT NULL,
  `conflict_detected_at` DATETIME NULL,
  `source_side` VARCHAR(20) NOT NULL,
  `source_device_id` VARCHAR(120) NOT NULL,
  `source_updated_at` DATETIME NULL,
  `pulled_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `applied_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_sync_pull_queue_event_uuid` (`event_uuid`),
  KEY `idx_sync_pull_queue_cursor` (`event_id`),
  KEY `idx_sync_pull_queue_status` (`status`, `next_attempt_at`),
  KEY `idx_sync_pull_queue_applied` (`applied`, `event_id`),
  KEY `idx_sync_pull_queue_entity` (`entity_type`, `entity_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

        if (!$this->tableExists('sync_pull_queue')) {
            return;
        }

        $this->addColumnIfMissing('sync_pull_queue', 'locked_at', 'DATETIME NULL DEFAULT NULL', 'attempts');
        $this->addColumnIfMissing('sync_pull_queue', 'next_attempt_at', 'DATETIME NULL DEFAULT NULL', 'locked_at');
        $this->addColumnIfMissing('sync_pull_queue', 'last_error', 'TEXT NULL DEFAULT NULL', 'next_attempt_at');
        $this->addColumnIfMissing('sync_pull_queue', 'conflict_reason', 'TEXT NULL DEFAULT NULL', 'last_error');
        $this->addColumnIfMissing('sync_pull_queue', 'conflict_detected_at', 'DATETIME NULL DEFAULT NULL', 'conflict_reason');
        $this->addColumnIfMissing('sync_pull_queue', 'source_updated_at', 'DATETIME NULL DEFAULT NULL', 'source_device_id');
        $this->addColumnIfMissing('sync_pull_queue', 'pulled_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'source_updated_at');
        $this->addColumnIfMissing('sync_pull_queue', 'applied_at', 'DATETIME NULL DEFAULT NULL', 'pulled_at');
        $this->addColumnIfMissing('sync_pull_queue', 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'applied_at');
        $this->addColumnIfMissing('sync_pull_queue', 'updated_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'created_at');

        $this->createUniqueIndexIfMissing('sync_pull_queue', 'uniq_sync_pull_queue_event_uuid', '(`event_uuid`)');
        $this->createIndexIfMissing('sync_pull_queue', 'idx_sync_pull_queue_cursor', '(`event_id`)');
        $this->createIndexIfMissing('sync_pull_queue', 'idx_sync_pull_queue_status', '(`status`, `next_attempt_at`)');
        $this->createIndexIfMissing('sync_pull_queue', 'idx_sync_pull_queue_applied', '(`applied`, `event_id`)');
        $this->createIndexIfMissing('sync_pull_queue', 'idx_sync_pull_queue_entity', '(`entity_type`, `entity_uuid`)');
    }

    private function ensureSyncStateTable(): void
    {
        $this->createTableIfMissing('sync_state', <<<'SQL'
CREATE TABLE `sync_state` (
  `device_id` VARCHAR(120) NOT NULL,
  `last_push_at` DATETIME NULL,
  `last_pull_cursor_fetched` BIGINT UNSIGNED NULL,
  `last_pull_cursor_applied` BIGINT UNSIGNED NULL,
  `last_pull_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

        if (!$this->tableExists('sync_state')) {
            return;
        }

        $this->addColumnIfMissing('sync_state', 'last_push_at', 'DATETIME NULL DEFAULT NULL', 'device_id');
        $this->addColumnIfMissing('sync_state', 'last_pull_cursor_fetched', 'BIGINT UNSIGNED NULL DEFAULT NULL', 'last_push_at');
        $this->addColumnIfMissing('sync_state', 'last_pull_cursor_applied', 'BIGINT UNSIGNED NULL DEFAULT NULL', 'last_pull_cursor_fetched');
        $this->addColumnIfMissing('sync_state', 'last_pull_at', 'DATETIME NULL DEFAULT NULL', 'last_pull_cursor_applied');
        $this->addColumnIfMissing('sync_state', 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP', 'last_pull_at');
        $this->addColumnIfMissing('sync_state', 'updated_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'created_at');
    }
}
