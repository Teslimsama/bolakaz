<?php

require_once __DIR__ . '/../CreateDb.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script must be run via CLI.\n";
    exit(1);
}

$conn = $pdo->open();

function customer_migration_column_exists(PDO $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column LIMIT 1');
    $stmt->execute(['table' => $table, 'column' => $column]);
    return (bool) $stmt->fetchColumn();
}

function customer_migration_add_column(PDO $conn, string $table, string $column, string $definition, ?string $after = null): void
{
    if (customer_migration_column_exists($conn, $table, $column)) {
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

try {
    echo "Starting customer account and sales snapshot migration...\n";

    customer_migration_add_column($conn, 'users', 'account_state', "ENUM('incomplete','pending_activation','active') NOT NULL DEFAULT 'incomplete'", 'status');
    customer_migration_add_column($conn, 'users', 'is_placeholder_email', "TINYINT(1) NOT NULL DEFAULT 0", 'account_state');

    $conn->exec("UPDATE users
        SET account_state = CASE
            WHEN type = 0 AND status = 0 THEN 'pending_activation'
            WHEN status = 1 THEN 'active'
            ELSE 'incomplete'
        END
        WHERE account_state IS NULL OR account_state = ''");
    echo " - Backfilled users.account_state.\n";

    $conn->exec("UPDATE users
        SET is_placeholder_email = 1
        WHERE email LIKE '%@local.invalid'");
    echo " - Backfilled users.is_placeholder_email for placeholder addresses.\n";

    customer_migration_add_column($conn, 'details', 'unit_price', "DECIMAL(12,2) NOT NULL DEFAULT 0", 'quantity');
    customer_migration_add_column($conn, 'details', 'product_name_snapshot', "VARCHAR(200) NOT NULL DEFAULT ''", 'unit_price');
    customer_migration_add_column($conn, 'details', 'product_slug_snapshot', "VARCHAR(200) NOT NULL DEFAULT ''", 'product_name_snapshot');

    echo "Customer account and sales snapshot migration completed successfully.\n";
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    $pdo->close();
}
