<?php

require_once __DIR__ . '/product_sku.php';

if (!function_exists('app_sales_snapshot_has_column')) {
    function app_sales_snapshot_has_column(PDO $conn, string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $stmt = $conn->prepare("SHOW COLUMNS FROM `{$table}` LIKE :column_name");
        $stmt->execute(['column_name' => $column]);
        $cache[$key] = (bool) $stmt->fetch(PDO::FETCH_ASSOC);

        return $cache[$key];
    }
}

if (!function_exists('app_sales_detail_price_sql')) {
    function app_sales_detail_price_sql(PDO $conn, string $detailsAlias = 'details', string $productsAlias = 'products'): string
    {
        if (app_sales_snapshot_has_column($conn, 'details', 'unit_price')) {
            return "CASE WHEN {$detailsAlias}.unit_price > 0 THEN {$detailsAlias}.unit_price ELSE COALESCE({$productsAlias}.price, 0) END";
        }

        return "COALESCE({$productsAlias}.price, 0)";
    }
}

if (!function_exists('app_sales_detail_name_sql')) {
    function app_sales_detail_name_sql(PDO $conn, string $detailsAlias = 'details', string $productsAlias = 'products'): string
    {
        if (app_sales_snapshot_has_column($conn, 'details', 'product_name_snapshot')) {
            return "CASE WHEN {$detailsAlias}.product_name_snapshot IS NOT NULL AND {$detailsAlias}.product_name_snapshot <> '' THEN {$detailsAlias}.product_name_snapshot ELSE COALESCE({$productsAlias}.name, 'Item') END";
        }

        return "COALESCE({$productsAlias}.name, 'Item')";
    }
}

if (!function_exists('app_sales_detail_slug_sql')) {
    function app_sales_detail_slug_sql(PDO $conn, string $detailsAlias = 'details', string $productsAlias = 'products'): string
    {
        if (app_sales_snapshot_has_column($conn, 'details', 'product_slug_snapshot')) {
            return "CASE WHEN {$detailsAlias}.product_slug_snapshot IS NOT NULL AND {$detailsAlias}.product_slug_snapshot <> '' THEN {$detailsAlias}.product_slug_snapshot ELSE COALESCE({$productsAlias}.slug, '') END";
        }

        return "COALESCE({$productsAlias}.slug, '')";
    }
}

if (!function_exists('app_sales_detail_sku_sql')) {
    function app_sales_detail_sku_sql(PDO $conn, string $detailsAlias = 'details', string $productsAlias = 'products'): string
    {
        $fallbackSql = "COALESCE(NULLIF({$productsAlias}.sku, ''), CONCAT('BLKZ-', LPAD(CAST({$productsAlias}.id AS CHAR), 6, '0')), '')";
        if (app_sales_snapshot_has_column($conn, 'details', 'product_sku_snapshot')) {
            return "CASE WHEN {$detailsAlias}.product_sku_snapshot IS NOT NULL AND {$detailsAlias}.product_sku_snapshot <> '' THEN {$detailsAlias}.product_sku_snapshot ELSE {$fallbackSql} END";
        }

        return $fallbackSql;
    }
}

if (!function_exists('app_sales_detail_total_sum_sql')) {
    function app_sales_detail_total_sum_sql(PDO $conn, string $detailsAlias = 'details', string $productsAlias = 'products'): string
    {
        $priceSql = app_sales_detail_price_sql($conn, $detailsAlias, $productsAlias);
        return "COALESCE(SUM({$detailsAlias}.quantity * {$priceSql}), 0)";
    }
}

if (!function_exists('app_sales_insert_detail_row')) {
    function app_sales_insert_detail_row(
        PDO $conn,
        int $salesId,
        int $productId,
        int $quantity,
        float $unitPrice,
        string $productName,
        string $productSku,
        string $productSlug,
        ?int $variantId = null
    ): int {
        $columns = ['sales_id', 'product_id'];
        $values = [
            'sales_id' => $salesId,
            'product_id' => $productId,
        ];

        if (app_sales_snapshot_has_column($conn, 'details', 'variant_id')) {
            $columns[] = 'variant_id';
            $values['variant_id'] = ($variantId !== null && $variantId > 0) ? $variantId : null;
        }

        $columns[] = 'quantity';
        $values['quantity'] = $quantity;

        if (app_sales_snapshot_has_column($conn, 'details', 'unit_price')) {
            $columns[] = 'unit_price';
            $values['unit_price'] = $unitPrice;
        }
        if (app_sales_snapshot_has_column($conn, 'details', 'product_name_snapshot')) {
            $columns[] = 'product_name_snapshot';
            $values['product_name_snapshot'] = $productName;
        }
        if (app_sales_snapshot_has_column($conn, 'details', 'product_sku_snapshot')) {
            $columns[] = 'product_sku_snapshot';
            $values['product_sku_snapshot'] = $productSku;
        }
        if (app_sales_snapshot_has_column($conn, 'details', 'product_slug_snapshot')) {
            $columns[] = 'product_slug_snapshot';
            $values['product_slug_snapshot'] = $productSlug;
        }

        $placeholders = [];
        foreach ($columns as $column) {
            $placeholders[] = ':' . $column;
        }

        $sql = 'INSERT INTO details (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $conn->prepare($sql);
        $stmt->execute($values);

        return (int) $conn->lastInsertId();
    }
}

if (!function_exists('app_sales_total_for_sale')) {
    function app_sales_total_for_sale(PDO $conn, int $saleId): float
    {
        $sumSql = app_sales_detail_total_sum_sql($conn, 'details', 'products');
        $stmt = $conn->prepare("SELECT {$sumSql} AS total_amount
            FROM details
            LEFT JOIN products ON products.id = details.product_id
            WHERE details.sales_id = :id");
        $stmt->execute(['id' => $saleId]);

        return (float) ($stmt->fetchColumn() ?: 0);
    }
}
