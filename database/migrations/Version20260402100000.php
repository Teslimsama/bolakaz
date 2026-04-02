<?php

declare(strict_types=1);

namespace Bolakaz\Migrations;

require_once __DIR__ . '/AbstractBolakazMigration.php';

use Doctrine\DBAL\Schema\Schema;

final class Version20260402100000 extends AbstractBolakazMigration
{
    public function getDescription(): string
    {
        return 'Adopts legacy offline sales, customer account, review, and patch work into tracked migrations.';
    }

    public function up(Schema $schema): void
    {
        $this->guardMySql();

        $this->ensureUsersColumns();
        $this->ensureSalesColumns();
        $this->ensureOfflinePaymentsTable();
        $this->normalizeOfflinePaymentStatuses();
        $this->ensureDetailsSnapshots();
        $this->ensureProductSpecsColumn();
        $this->ensureReviewUniqueness();
        $this->ensureSalesTxRefUniqueness();
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('This migration adopts legacy production data and cannot be safely reversed.');
    }

    private function ensureUsersColumns(): void
    {
        if (!$this->tableExists('users')) {
            return;
        }

        if ($this->columnExists('users', 'activate_code')) {
            $activateLength = $this->columnLength('users', 'activate_code');
            if ($activateLength !== null && $activateLength < 255) {
                $this->connection->executeStatement('ALTER TABLE `users` MODIFY `activate_code` VARCHAR(255) NULL');
            }
        }

        if ($this->columnExists('users', 'reset_code')) {
            $resetLength = $this->columnLength('users', 'reset_code');
            if ($resetLength !== null && $resetLength < 255) {
                $this->connection->executeStatement('ALTER TABLE `users` MODIFY `reset_code` VARCHAR(255) NULL');
            }
        }

        $afterStatus = $this->columnExists('users', 'status') ? 'status' : ($this->columnExists('users', 'type') ? 'type' : null);
        $this->addColumnIfMissing('users', 'account_state', "ENUM('incomplete','pending_activation','active') NOT NULL DEFAULT 'incomplete'", $afterStatus);

        $afterAccountState = $this->columnExists('users', 'account_state') ? 'account_state' : $afterStatus;
        $this->addColumnIfMissing('users', 'is_placeholder_email', 'TINYINT(1) NOT NULL DEFAULT 0', $afterAccountState);

        $this->connection->executeStatement(<<<'SQL'
UPDATE users
SET account_state = CASE
    WHEN type = 0 AND status = 0 THEN 'pending_activation'
    WHEN status = 1 THEN 'active'
    ELSE 'incomplete'
END
WHERE account_state IS NULL OR TRIM(account_state) = ''
   OR account_state NOT IN ('incomplete', 'pending_activation', 'active')
SQL);

        $this->connection->executeStatement(<<<'SQL'
UPDATE users
SET is_placeholder_email = CASE
    WHEN LOWER(TRIM(COALESCE(email, ''))) LIKE '%@local.invalid' THEN 1
    ELSE 0
END
SQL);
    }

    private function ensureSalesColumns(): void
    {
        if (!$this->tableExists('sales')) {
            return;
        }

        $this->addColumnIfMissing('sales', 'is_offline', 'TINYINT(1) NOT NULL DEFAULT 0', 'user_id');
        $this->addColumnIfMissing('sales', 'payment_status', "VARCHAR(20) NOT NULL DEFAULT 'paid'", 'Status');
        $this->addColumnIfMissing('sales', 'customer_name', 'VARCHAR(150) NULL', 'payment_status');
        $this->addColumnIfMissing('sales', 'statement_share_token', 'CHAR(64) NULL', 'customer_name');
        $this->addColumnIfMissing('sales', 'due_date', 'DATE NULL', 'sales_date');

        $this->connection->executeStatement(<<<'SQL'
UPDATE sales s
LEFT JOIN users u ON u.id = s.user_id
SET s.customer_name = TRIM(
    COALESCE(NULLIF(s.customer_name, ''), CONCAT(COALESCE(u.firstname, ''), ' ', COALESCE(u.lastname, '')))
)
WHERE s.customer_name IS NULL OR TRIM(s.customer_name) = ''
SQL);

        $duplicateGroups = $this->connection->fetchAllAssociative(<<<'SQL'
SELECT statement_share_token
FROM sales
WHERE statement_share_token IS NOT NULL
  AND TRIM(statement_share_token) <> ''
GROUP BY statement_share_token
HAVING COUNT(*) > 1
SQL);

        foreach ($duplicateGroups as $group) {
            $token = trim((string) ($group['statement_share_token'] ?? ''));
            if ($token === '') {
                continue;
            }

            $rows = $this->connection->fetchAllAssociative(
                'SELECT id FROM sales WHERE statement_share_token = ? ORDER BY id ASC',
                [$token]
            );

            foreach (array_slice($rows, 1) as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }

                $this->connection->executeStatement(
                    'UPDATE sales SET statement_share_token = ? WHERE id = ?',
                    [$this->generateUniqueStatementToken($id), $id]
                );
            }
        }

        $offlineRows = $this->connection->fetchAllAssociative(<<<'SQL'
SELECT id
FROM sales
WHERE is_offline = 1
  AND (
      statement_share_token IS NULL
      OR TRIM(statement_share_token) = ''
  )
ORDER BY id ASC
SQL);

        foreach ($offlineRows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $this->connection->executeStatement(
                'UPDATE sales SET statement_share_token = ? WHERE id = ?',
                [$this->generateUniqueStatementToken($id), $id]
            );
        }

        $this->createUniqueIndexIfMissing('sales', 'uniq_statement_share_token', '(`statement_share_token`)');
    }

    private function ensureOfflinePaymentsTable(): void
    {
        $this->createTableIfMissing('offline_payments', <<<'SQL'
CREATE TABLE `offline_payments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sales_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `payment_date` DATE NOT NULL,
  `note` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sales_id` (`sales_id`),
  CONSTRAINT `offline_payments_ibfk_1` FOREIGN KEY (`sales_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
    }

    private function normalizeOfflinePaymentStatuses(): void
    {
        if (!$this->tableExists('sales')) {
            return;
        }

        if (!$this->tableExists('offline_payments')) {
            return;
        }

        $this->connection->executeStatement(<<<'SQL'
UPDATE sales s
LEFT JOIN (
    SELECT sales_id, COUNT(*) AS payment_rows
    FROM offline_payments
    GROUP BY sales_id
) p ON p.sales_id = s.id
SET s.payment_status = CASE
    WHEN s.is_offline = 1 AND COALESCE(p.payment_rows, 0) > 0 AND (s.payment_status IS NULL OR TRIM(s.payment_status) = '') THEN 'partial'
    WHEN s.is_offline = 1 AND COALESCE(p.payment_rows, 0) = 0 AND (s.payment_status IS NULL OR TRIM(s.payment_status) = '') THEN 'unpaid'
    WHEN s.is_offline = 0 AND (s.payment_status IS NULL OR TRIM(s.payment_status) = '') THEN 'paid'
    ELSE s.payment_status
END
SQL);
    }

    private function ensureDetailsSnapshots(): void
    {
        if (!$this->tableExists('details')) {
            return;
        }

        $afterQuantity = $this->columnExists('details', 'quantity') ? 'quantity' : ($this->columnExists('details', 'variant_id') ? 'variant_id' : null);
        $this->addColumnIfMissing('details', 'unit_price', 'DECIMAL(12,2) NOT NULL DEFAULT 0', $afterQuantity);
        $afterUnitPrice = $this->columnExists('details', 'unit_price') ? 'unit_price' : $afterQuantity;
        $this->addColumnIfMissing('details', 'product_name_snapshot', "VARCHAR(200) NOT NULL DEFAULT ''", $afterUnitPrice);
        $afterProductName = $this->columnExists('details', 'product_name_snapshot') ? 'product_name_snapshot' : $afterUnitPrice;
        $this->addColumnIfMissing('details', 'product_slug_snapshot', "VARCHAR(200) NOT NULL DEFAULT ''", $afterProductName);

        if ($this->tableExists('products')) {
            $this->connection->executeStatement(<<<'SQL'
UPDATE details d
LEFT JOIN products p ON p.id = d.product_id
SET d.unit_price = CASE
        WHEN d.unit_price IS NULL OR d.unit_price = 0 THEN COALESCE(p.price, 0)
        ELSE d.unit_price
    END,
    d.product_name_snapshot = CASE
        WHEN d.product_name_snapshot IS NULL OR TRIM(d.product_name_snapshot) = '' THEN COALESCE(NULLIF(TRIM(p.name), ''), CONCAT('Legacy Product #', d.product_id))
        ELSE d.product_name_snapshot
    END,
    d.product_slug_snapshot = CASE
        WHEN d.product_slug_snapshot IS NULL OR TRIM(d.product_slug_snapshot) = '' THEN COALESCE(NULLIF(TRIM(p.slug), ''), CONCAT('legacy-product-', d.product_id))
        ELSE d.product_slug_snapshot
    END
SQL);
        }
    }

    private function ensureProductSpecsColumn(): void
    {
        if (!$this->tableExists('products')) {
            return;
        }

        $afterDescription = $this->columnExists('products', 'description') ? 'description' : null;
        $this->addColumnIfMissing('products', 'additional_info', 'TEXT NULL', $afterDescription);
    }

    private function ensureReviewUniqueness(): void
    {
        if (!$this->tableExists('item_rating') || $this->indexExists('item_rating', 'uniq_item_user')) {
            return;
        }

        $this->connection->executeStatement(<<<'SQL'
DELETE r1
FROM item_rating r1
INNER JOIN item_rating r2
    ON r1.itemId = r2.itemId
   AND r1.userId = r2.userId
   AND r1.ratingId < r2.ratingId
SQL);

        $this->createUniqueIndexIfMissing('item_rating', 'uniq_item_user', '(`itemId`, `userId`)');
    }

    private function ensureSalesTxRefUniqueness(): void
    {
        if (!$this->tableExists('sales')) {
            return;
        }

        $this->connection->executeStatement(<<<'SQL'
UPDATE sales
SET tx_ref = CONCAT('LEGACY-TXREF-', id)
WHERE tx_ref IS NULL OR TRIM(tx_ref) = ''
SQL);

        $this->connection->executeStatement(<<<'SQL'
UPDATE sales s
INNER JOIN (
    SELECT s1.id
    FROM sales s1
    INNER JOIN sales s2
        ON TRIM(COALESCE(s1.tx_ref, '')) = TRIM(COALESCE(s2.tx_ref, ''))
       AND s1.id > s2.id
       AND TRIM(COALESCE(s1.tx_ref, '')) <> ''
    GROUP BY s1.id
) d ON d.id = s.id
SET s.tx_ref = CONCAT('LEGACY-TXREF-', s.id)
SQL);

        $this->createUniqueIndexIfMissing('sales', 'uniq_sales_tx_ref', '(`tx_ref`)');
    }

    private function generateUniqueStatementToken(int $saleId): string
    {
        do {
            $token = $this->generateToken();
            $exists = (bool) $this->connection->fetchOne(
                'SELECT 1 FROM sales WHERE statement_share_token = ? AND id <> ? LIMIT 1',
                [$token, $saleId]
            );
        } while ($exists);

        return $token;
    }
}
