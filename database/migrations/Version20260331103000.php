<?php

declare(strict_types=1);

namespace Bolakaz\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfills missing sale detail SKU snapshots from legacy product ids when source products no longer exist.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            ! $this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This migration can only be executed safely on MySQL.',
        );

        if (! $this->tableExists('details') || ! $this->columnExists('details', 'product_sku_snapshot')) {
            return;
        }

        $this->addSql(<<<'SQL'
UPDATE details
SET product_sku_snapshot = CONCAT('BLKZ-', LPAD(CAST(product_id AS CHAR), 6, '0'))
WHERE product_id IS NOT NULL
  AND (
      product_sku_snapshot IS NULL
      OR TRIM(product_sku_snapshot) = ''
      OR product_sku_snapshot NOT REGEXP '^BLKZ-[0-9]{6}$'
  )
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Removing SKU snapshot backfills would weaken historical sale records.');
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
}
