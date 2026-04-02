<?php

declare(strict_types=1);

namespace Bolakaz\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\Migrations\AbstractMigration;

abstract class AbstractBolakazMigration extends AbstractMigration
{
    public function isTransactional(): bool
    {
        return false;
    }

    protected function guardMySql(): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'This migration can only be executed safely on MySQL.',
        );
    }

    protected function tableExists(string $table): bool
    {
        $sql = 'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1';
        return (bool) $this->connection->fetchOne($sql, [$table]);
    }

    protected function columnExists(string $table, string $column): bool
    {
        $sql = 'SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1';
        return (bool) $this->connection->fetchOne($sql, [$table, $column]);
    }

    protected function indexExists(string $table, string $index): bool
    {
        $sql = 'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1';
        return (bool) $this->connection->fetchOne($sql, [$table, $index]);
    }

    protected function columnLength(string $table, string $column): ?int
    {
        $sql = 'SELECT character_maximum_length FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1';
        $value = $this->connection->fetchOne($sql, [$table, $column]);
        if ($value === false || $value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    protected function addColumnIfMissing(string $table, string $column, string $definition, ?string $after = null): void
    {
        if ($this->columnExists($table, $column)) {
            return;
        }

        $sql = sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s', $table, $column, $definition);
        if ($after !== null && $after !== '') {
            $sql .= sprintf(' AFTER `%s`', $after);
        }
        $this->connection->executeStatement($sql);
    }

    protected function createIndexIfMissing(string $table, string $index, string $definition): void
    {
        if ($this->indexExists($table, $index)) {
            return;
        }

        $this->connection->executeStatement(sprintf('ALTER TABLE `%s` ADD KEY `%s` %s', $table, $index, $definition));
    }

    protected function createUniqueIndexIfMissing(string $table, string $index, string $definition): void
    {
        if ($this->indexExists($table, $index)) {
            return;
        }

        $this->connection->executeStatement(sprintf('ALTER TABLE `%s` ADD UNIQUE KEY `%s` %s', $table, $index, $definition));
    }

    protected function createTableIfMissing(string $table, string $sql): void
    {
        if ($this->tableExists($table)) {
            return;
        }

        $this->connection->executeStatement($sql);
    }

    protected function generateUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }

    protected function generateToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }
}
