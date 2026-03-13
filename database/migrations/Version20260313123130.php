<?php

declare(strict_types=1);

namespace Bolakaz\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313123130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds product_v2-aware review support to item_rating.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            ! $this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This migration can only be executed safely on MySQL.',
        );

        if (! $this->tableExists('item_rating')) {
            return;
        }

        if (! $this->columnExists('item_rating', 'product_v2_id')) {
            $this->addSql('ALTER TABLE item_rating MODIFY itemId INT NULL');
            $this->addSql('ALTER TABLE item_rating ADD COLUMN product_v2_id BIGINT UNSIGNED NULL AFTER itemId');
        }

        if ($this->tableExists('product_legacy_map')) {
            $this->addSql(<<<'SQL'
UPDATE item_rating ir
INNER JOIN product_legacy_map plm ON plm.legacy_product_id = ir.itemId
SET ir.product_v2_id = plm.product_v2_id
WHERE ir.product_v2_id IS NULL
SQL);
        }

        if (! $this->indexExists('item_rating', 'idx_item_rating_product_v2')) {
            $this->addSql('ALTER TABLE item_rating ADD KEY idx_item_rating_product_v2 (product_v2_id)');
        }

        if (! $this->indexExists('item_rating', 'uniq_item_v2_user')) {
            $this->addSql('ALTER TABLE item_rating ADD UNIQUE KEY uniq_item_v2_user (product_v2_id, userId)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('This migration backfills V2 review targets and cannot be safely reversed.');
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
