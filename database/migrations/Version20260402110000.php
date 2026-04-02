<?php

declare(strict_types=1);

namespace Bolakaz\Migrations;

require_once __DIR__ . '/AbstractBolakazMigration.php';
require_once dirname(__DIR__, 2) . '/lib/catalog_v2.php';

use Doctrine\DBAL\Schema\Schema;
use PDO;
use RuntimeException;

final class Version20260402110000 extends AbstractBolakazMigration
{
    public function getDescription(): string
    {
        return 'Adopts the catalog v2 legacy sync and review backfill into tracked migrations.';
    }

    public function up(Schema $schema): void
    {
        $this->guardMySql();

        $this->ensureCatalogTables();
        $this->ensureLegacyCatalogColumns();
        $this->syncLegacyProductsIntoCatalogV2();
        $this->backfillReviewProductV2Targets();
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('This migration backfills catalog v2 records and cannot be safely reversed.');
    }

    private function ensureCatalogTables(): void
    {
        $this->createTableIfMissing('products_v2', <<<'SQL'
CREATE TABLE `products_v2` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` BIGINT UNSIGNED DEFAULT NULL,
  `subcategory_id` BIGINT UNSIGNED DEFAULT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` LONGTEXT NOT NULL,
  `brand` VARCHAR(200) DEFAULT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `base_price` DECIMAL(12,2) DEFAULT NULL,
  `main_image` VARCHAR(255) DEFAULT NULL,
  `specs_json` LONGTEXT DEFAULT NULL,
  `needs_variant_stock_review` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_products_v2_slug` (`slug`),
  KEY `idx_products_v2_status` (`status`),
  KEY `idx_products_v2_category` (`category_id`),
  KEY `idx_products_v2_subcategory` (`subcategory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->createTableIfMissing('attributes', <<<'SQL'
CREATE TABLE `attributes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(60) NOT NULL,
  `label` VARCHAR(120) NOT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_attributes_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->createTableIfMissing('attribute_values', <<<'SQL'
CREATE TABLE `attribute_values` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attribute_id` BIGINT UNSIGNED NOT NULL,
  `value` VARCHAR(191) NOT NULL,
  `normalized_value` VARCHAR(191) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_attribute_value` (`attribute_id`, `normalized_value`),
  KEY `idx_attribute_values_attr` (`attribute_id`),
  CONSTRAINT `fk_attribute_values_attribute` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->createTableIfMissing('product_variants', <<<'SQL'
CREATE TABLE `product_variants` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `sku` VARCHAR(120) NOT NULL,
  `price` DECIMAL(12,2) NOT NULL,
  `stock_qty` INT NOT NULL DEFAULT 0,
  `image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `option_signature` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_product_variants_sku` (`sku`),
  UNIQUE KEY `uniq_product_variant_signature` (`product_id`, `option_signature`),
  KEY `idx_product_variants_product` (`product_id`),
  KEY `idx_product_variants_status` (`status`),
  CONSTRAINT `fk_product_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products_v2` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->createTableIfMissing('variant_option_values', <<<'SQL'
CREATE TABLE `variant_option_values` (
  `variant_id` BIGINT UNSIGNED NOT NULL,
  `attribute_id` BIGINT UNSIGNED NOT NULL,
  `attribute_value_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`variant_id`, `attribute_id`),
  KEY `idx_vov_value` (`attribute_value_id`),
  KEY `fk_vov_attribute` (`attribute_id`),
  CONSTRAINT `fk_vov_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vov_attribute` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vov_attribute_value` FOREIGN KEY (`attribute_value_id`) REFERENCES `attribute_values` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $this->createTableIfMissing('product_legacy_map', <<<'SQL'
CREATE TABLE `product_legacy_map` (
  `legacy_product_id` INT NOT NULL,
  `product_v2_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`legacy_product_id`),
  UNIQUE KEY `uniq_product_legacy_map_v2` (`product_v2_id`),
  CONSTRAINT `fk_legacy_map_v2` FOREIGN KEY (`product_v2_id`) REFERENCES `products_v2` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    private function ensureLegacyCatalogColumns(): void
    {
        if ($this->tableExists('details')) {
            $afterProductId = $this->columnExists('details', 'product_id') ? 'product_id' : null;
            $this->addColumnIfMissing('details', 'variant_id', 'BIGINT UNSIGNED NULL', $afterProductId);
            $this->createIndexIfMissing('details', 'idx_details_variant_id', '(`variant_id`)');
        }

        if ($this->tableExists('cart')) {
            $afterProductId = $this->columnExists('cart', 'product_id') ? 'product_id' : null;
            $this->addColumnIfMissing('cart', 'variant_id', 'BIGINT UNSIGNED NULL', $afterProductId);
            $this->createIndexIfMissing('cart', 'idx_cart_variant_id', '(`variant_id`)');

            $sizeLength = $this->columnLength('cart', 'size');
            if ($sizeLength !== null && $sizeLength < 120) {
                $this->connection->executeStatement('ALTER TABLE `cart` MODIFY `size` VARCHAR(120) NOT NULL');
            }

            $colorLength = $this->columnLength('cart', 'color');
            if ($colorLength !== null && $colorLength < 120) {
                $this->connection->executeStatement('ALTER TABLE `cart` MODIFY `color` VARCHAR(120) NOT NULL');
            }
        }
    }

    private function syncLegacyProductsIntoCatalogV2(): void
    {
        if (
            !$this->tableExists('products')
            || !$this->tableExists('products_v2')
            || !$this->tableExists('attributes')
            || !$this->tableExists('attribute_values')
            || !$this->tableExists('product_variants')
            || !$this->tableExists('variant_option_values')
            || !$this->tableExists('product_legacy_map')
        ) {
            return;
        }

        $native = $this->connection->getNativeConnection();
        if (!$native instanceof PDO) {
            throw new RuntimeException('Unable to access the native PDO connection for catalog v2 migration.');
        }

        $attributes = [
            ['code' => 'size', 'label' => 'Size'],
            ['code' => 'color', 'label' => 'Color'],
            ['code' => 'material', 'label' => 'Material'],
        ];

        $startedTransaction = false;
        if (!$native->inTransaction()) {
            $native->beginTransaction();
            $startedTransaction = true;
        }

        try {
            foreach ($attributes as $attribute) {
                catalog_v2_get_or_create_attribute($native, $attribute['code'], $attribute['label']);
            }

            $legacyRows = $native->query('SELECT * FROM products ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($legacyRows as $legacyRow) {
                catalog_v2_sync_product_from_legacy($native, $legacyRow);
            }

            if ($startedTransaction) {
                $native->commit();
            }
        } catch (\Throwable $e) {
            if ($startedTransaction && $native->inTransaction()) {
                $native->rollBack();
            }

            throw $e;
        }
    }

    private function backfillReviewProductV2Targets(): void
    {
        if (!$this->tableExists('item_rating') || !$this->tableExists('product_legacy_map')) {
            return;
        }

        if (!$this->columnExists('item_rating', 'product_v2_id')) {
            $this->connection->executeStatement('ALTER TABLE `item_rating` MODIFY `itemId` INT NULL');
            $this->connection->executeStatement('ALTER TABLE `item_rating` ADD COLUMN `product_v2_id` BIGINT UNSIGNED NULL AFTER `itemId`');
        }

        $this->connection->executeStatement(<<<'SQL'
UPDATE item_rating ir
INNER JOIN product_legacy_map plm ON plm.legacy_product_id = ir.itemId
SET ir.product_v2_id = plm.product_v2_id
WHERE ir.product_v2_id IS NULL
SQL);

        $this->createIndexIfMissing('item_rating', 'idx_item_rating_product_v2', '(`product_v2_id`)');
        $this->createUniqueIndexIfMissing('item_rating', 'uniq_item_v2_user', '(`product_v2_id`, `userId`)');
    }
}
