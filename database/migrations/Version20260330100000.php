<?php

declare(strict_types=1);

namespace Bolakaz\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds stable product-level SKU support to legacy products.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            ! $this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This migration can only be executed safely on MySQL.',
        );

        if (! $this->tableExists('products')) {
            return;
        }

        if (! $this->columnExists('products', 'sku')) {
            $this->addSql('ALTER TABLE products ADD COLUMN sku VARCHAR(40) NULL AFTER slug');
        }

        $this->addSql(<<<'SQL'
UPDATE products
SET sku = CONCAT('BLKZ-', LPAD(CAST(id AS CHAR), 6, '0'))
WHERE sku IS NULL OR sku = ''
SQL);

        $this->addSql("ALTER TABLE products MODIFY sku VARCHAR(40) NOT NULL");

        if (! $this->indexExists('products', 'uniq_products_sku')) {
            $this->addSql('ALTER TABLE products ADD UNIQUE KEY uniq_products_sku (sku)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Dropping product SKUs would break printed labels and existing references.');
    }

    public function isTransactional(): bool
    {
        return false;
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

