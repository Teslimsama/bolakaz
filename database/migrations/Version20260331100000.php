<?php

declare(strict_types=1);

namespace Bolakaz\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Throwable;

final class Version20260331100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Hardens legacy product SKUs, adds fast lookup indexes, and snapshots SKU on sale details.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            ! $this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This migration can only be executed safely on MySQL.',
        );

        if ($this->tableExists('products')) {
            if (! $this->columnExists('products', 'sku')) {
                $this->addSql('ALTER TABLE products ADD COLUMN sku VARCHAR(40) NULL AFTER slug');
            }

            $this->addSql('ALTER TABLE products MODIFY sku VARCHAR(40) NULL');
            $this->addSql(<<<'SQL'
UPDATE products
SET sku = CONCAT('BLKZ-', LPAD(CAST(id AS CHAR), 6, '0'))
WHERE sku IS NULL OR TRIM(sku) = '' OR sku NOT REGEXP '^BLKZ-[0-9]{6}$'
SQL);

            if (! $this->indexExists('products', 'uniq_products_sku')) {
                $this->addSql('ALTER TABLE products ADD UNIQUE KEY uniq_products_sku (sku)');
            }

            if (! $this->indexExists('products', 'idx_products_name')) {
                $this->addSql('ALTER TABLE products ADD KEY idx_products_name (name(191))');
            }

            $this->recreateProductSkuTriggers();
        }

        if ($this->tableExists('details')) {
            if (! $this->columnExists('details', 'product_sku_snapshot')) {
                $afterColumn = $this->columnExists('details', 'product_name_snapshot')
                    ? 'product_name_snapshot'
                    : ($this->columnExists('details', 'unit_price') ? 'unit_price' : 'quantity');

                $this->addSql(sprintf(
                    'ALTER TABLE details ADD COLUMN product_sku_snapshot VARCHAR(40) NULL AFTER %s',
                    $afterColumn
                ));
            }

            if ($this->tableExists('products')) {
                $this->addSql(<<<'SQL'
UPDATE details
LEFT JOIN products ON products.id = details.product_id
SET details.product_sku_snapshot = COALESCE(NULLIF(TRIM(products.sku), ''), CONCAT('BLKZ-', LPAD(CAST(products.id AS CHAR), 6, '0')))
WHERE (details.product_sku_snapshot IS NULL OR TRIM(details.product_sku_snapshot) = '')
  AND products.id IS NOT NULL
SQL);
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('This migration protects stable SKU and sales history records and cannot be safely reversed.');
    }

    public function isTransactional(): bool
    {
        return false;
    }

    private function recreateProductSkuTriggers(): void
    {
        try {
            $this->connection->executeStatement('DROP TRIGGER IF EXISTS trg_products_sku_before_insert');
            $this->connection->executeStatement('DROP TRIGGER IF EXISTS trg_products_sku_before_update');

            $this->connection->executeStatement(<<<'SQL'
CREATE TRIGGER trg_products_sku_before_insert
BEFORE INSERT ON products
FOR EACH ROW
BEGIN
    IF NEW.sku IS NOT NULL AND TRIM(NEW.sku) = '' THEN
        SET NEW.sku = NULL;
    END IF;

    IF NEW.sku IS NOT NULL AND NEW.sku NOT REGEXP '^BLKZ-[0-9]{6}$' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Product SKU must match BLKZ-000000 format.';
    END IF;
END
SQL);

            $this->connection->executeStatement(<<<'SQL'
CREATE TRIGGER trg_products_sku_before_update
BEFORE UPDATE ON products
FOR EACH ROW
BEGIN
    IF NEW.sku IS NOT NULL AND TRIM(NEW.sku) = '' THEN
        SET NEW.sku = NULL;
    END IF;

    IF NEW.sku IS NOT NULL AND NEW.sku NOT REGEXP '^BLKZ-[0-9]{6}$' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Product SKU must match BLKZ-000000 format.';
    END IF;

    IF OLD.sku IS NOT NULL AND TRIM(OLD.sku) <> '' AND COALESCE(NEW.sku, '') <> OLD.sku THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Product SKU cannot be changed once assigned.';
    END IF;
END
SQL);
        } catch (Throwable $e) {
            if ($this->isTriggerPrivilegeException($e)) {
                $this->write('Skipping product SKU trigger creation because the current database user lacks TRIGGER privilege.');
                return;
            }

            throw $e;
        }
    }

    private function isTriggerPrivilegeException(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'trigger command denied')
            || (str_contains($message, 'sqlstate[42000]') && str_contains($message, 'trigger'));
    }

    private function tableExists(string $table): bool
    {
        $sql = 'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1';
        return (bool) $this->connection->fetchOne($sql, [$table]);
    }

    private function columnExists(string $table, string $column): bool
    {
        $sql = 'SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1';
        return (bool) $this->connection->fetchOne($sql, [$table, $column]);
    }

    private function indexExists(string $table, string $index): bool
    {
        $sql = 'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1';
        return (bool) $this->connection->fetchOne($sql, [$table, $index]);
    }
}
