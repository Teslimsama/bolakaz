<?php

require_once __DIR__ . '/../CreateDb.php';
require_once __DIR__ . '/../lib/catalog_v2.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script must be run via CLI.\n";
    exit(1);
}

$conn = $pdo->open();

try {
    $conn->exec("CREATE TABLE IF NOT EXISTS products_v2 (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        category_id BIGINT UNSIGNED NULL,
        subcategory_id BIGINT UNSIGNED NULL,
        slug VARCHAR(200) NOT NULL,
        name VARCHAR(255) NOT NULL,
        description LONGTEXT NOT NULL,
        brand VARCHAR(200) NULL,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        base_price DECIMAL(12,2) NULL,
        main_image VARCHAR(255) NULL,
        specs_json LONGTEXT NULL,
        needs_variant_stock_review TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_products_v2_slug (slug),
        KEY idx_products_v2_status (status),
        KEY idx_products_v2_category (category_id),
        KEY idx_products_v2_subcategory (subcategory_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS attributes (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        code VARCHAR(60) NOT NULL,
        label VARCHAR(120) NOT NULL,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_attributes_code (code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS attribute_values (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        attribute_id BIGINT UNSIGNED NOT NULL,
        value VARCHAR(191) NOT NULL,
        normalized_value VARCHAR(191) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_attribute_value (attribute_id, normalized_value),
        KEY idx_attribute_values_attr (attribute_id),
        CONSTRAINT fk_attribute_values_attribute FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS product_variants (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        product_id BIGINT UNSIGNED NOT NULL,
        sku VARCHAR(120) NOT NULL,
        price DECIMAL(12,2) NOT NULL,
        stock_qty INT NOT NULL DEFAULT 0,
        image VARCHAR(255) NULL,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        option_signature VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_product_variants_sku (sku),
        UNIQUE KEY uniq_product_variant_signature (product_id, option_signature),
        KEY idx_product_variants_product (product_id),
        KEY idx_product_variants_status (status),
        CONSTRAINT fk_product_variants_product FOREIGN KEY (product_id) REFERENCES products_v2(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS variant_option_values (
        variant_id BIGINT UNSIGNED NOT NULL,
        attribute_id BIGINT UNSIGNED NOT NULL,
        attribute_value_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (variant_id, attribute_id),
        KEY idx_vov_value (attribute_value_id),
        CONSTRAINT fk_vov_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
        CONSTRAINT fk_vov_attribute FOREIGN KEY (attribute_id) REFERENCES attributes(id) ON DELETE CASCADE,
        CONSTRAINT fk_vov_attribute_value FOREIGN KEY (attribute_value_id) REFERENCES attribute_values(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->exec("CREATE TABLE IF NOT EXISTS product_legacy_map (
        legacy_product_id INT(11) NOT NULL,
        product_v2_id BIGINT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (legacy_product_id),
        UNIQUE KEY uniq_product_legacy_map_v2 (product_v2_id),
        CONSTRAINT fk_legacy_map_v2 FOREIGN KEY (product_v2_id) REFERENCES products_v2(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $detailsColumns = $conn->query("SHOW COLUMNS FROM details LIKE 'variant_id'")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($detailsColumns)) {
        $conn->exec("ALTER TABLE details ADD COLUMN variant_id BIGINT UNSIGNED NULL AFTER product_id");
        $conn->exec("ALTER TABLE details ADD KEY idx_details_variant_id (variant_id)");
    }

    $cartVariantColumns = $conn->query("SHOW COLUMNS FROM cart LIKE 'variant_id'")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($cartVariantColumns)) {
        $conn->exec("ALTER TABLE cart ADD COLUMN variant_id BIGINT UNSIGNED NULL AFTER product_id");
        $conn->exec("ALTER TABLE cart ADD KEY idx_cart_variant_id (variant_id)");
    }

    $cartSize = $conn->query("SHOW COLUMNS FROM cart LIKE 'size'")->fetch(PDO::FETCH_ASSOC);
    if (!empty($cartSize) && stripos((string)$cartSize['Type'], 'varchar(10)') !== false) {
        $conn->exec("ALTER TABLE cart MODIFY COLUMN size VARCHAR(120) NOT NULL");
    }

    $cartColor = $conn->query("SHOW COLUMNS FROM cart LIKE 'color'")->fetch(PDO::FETCH_ASSOC);
    if (!empty($cartColor) && stripos((string)$cartColor['Type'], 'varchar(20)') !== false) {
        $conn->exec("ALTER TABLE cart MODIFY COLUMN color VARCHAR(120) NOT NULL");
    }

    $conn->beginTransaction();
    $attributes = [
        ['code' => 'size', 'label' => 'Size'],
        ['code' => 'color', 'label' => 'Color'],
        ['code' => 'material', 'label' => 'Material'],
    ];
    foreach ($attributes as $attribute) {
        catalog_v2_get_or_create_attribute($conn, $attribute['code'], $attribute['label']);
    }

    $legacyRows = $conn->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($legacyRows as $legacyRow) {
        catalog_v2_sync_product_from_legacy($conn, $legacyRow);
    }
    $conn->commit();

    echo "Catalog v2 migration completed successfully.\n";
} catch (Throwable $e) {
    if ($conn->inTransaction()) { $conn->rollBack(); }
    fwrite(STDERR, "Catalog v2 migration failed: " . $e->getMessage() . "\n");
    exit(1);
} finally {
    $pdo->close();
}
