<?php

require_once __DIR__ . '/../CreateDb.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script must be run via CLI.\n";
    exit(1);
}

$conn = $pdo->open();

try {
    echo "Starting migration...\n";

    // 1. Update sales table
    echo "Updating 'sales' table...\n";
    $columns = $conn->query("SHOW COLUMNS FROM sales")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('is_offline', $columns)) {
        $conn->exec("ALTER TABLE sales ADD COLUMN is_offline TINYINT(1) NOT NULL DEFAULT 0 AFTER user_id");
        echo "Added 'is_offline' column.\n";
    }
    
    if (!in_array('payment_status', $columns)) {
        $conn->exec("ALTER TABLE sales ADD COLUMN payment_status VARCHAR(20) NOT NULL DEFAULT 'paid' AFTER Status");
        echo "Added 'payment_status' column.\n";
    }

    if (!in_array('due_date', $columns)) {
        $conn->exec("ALTER TABLE sales ADD COLUMN due_date DATE NULL AFTER sales_date");
        echo "Added 'due_date' column.\n";
    }

    // 2. Create offline_payments table
    echo "Creating 'offline_payments' table...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS offline_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sales_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        payment_date DATE NOT NULL,
        note TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sales_id) REFERENCES sales(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created 'offline_payments' table.\n";

    echo "Migration completed successfully.\n";
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    $pdo->close();
}
