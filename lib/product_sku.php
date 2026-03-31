<?php

if (!function_exists('product_sku_column_exists')) {
    function product_sku_column_exists(PDO $conn): bool
    {
        static $cache = [];
        $key = spl_object_hash($conn) . ':products.sku';
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $stmt = $conn->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column LIMIT 1');
        $stmt->execute([
            'table' => 'products',
            'column' => 'sku',
        ]);

        $cache[$key] = (bool) $stmt->fetchColumn();
        return $cache[$key];
    }
}

if (!function_exists('product_sku_generate_for_id')) {
    function product_sku_generate_for_id(int $productId): string
    {
        return 'BLKZ-' . str_pad((string) max(0, $productId), 6, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('product_sku_default_for_id')) {
    function product_sku_default_for_id(int $productId): string
    {
        return product_sku_generate_for_id($productId);
    }
}

if (!function_exists('product_sku_is_valid_format')) {
    function product_sku_is_valid_format(string $sku): bool
    {
        return preg_match('/^BLKZ-\d{6}$/', strtoupper(trim($sku))) === 1;
    }
}

if (!function_exists('product_sku_extract_product_id')) {
    function product_sku_extract_product_id(string $sku): int
    {
        $sku = strtoupper(trim($sku));
        if (!product_sku_is_valid_format($sku)) {
            return 0;
        }

        $parts = explode('-', $sku, 2);
        return (int) ($parts[1] ?? 0);
    }
}

if (!function_exists('product_sku_existing_for_id')) {
    function product_sku_existing_for_id(PDO $conn, int $productId): string
    {
        if ($productId <= 0 || !product_sku_column_exists($conn)) {
            return '';
        }

        $stmt = $conn->prepare('SELECT sku FROM products WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $productId]);
        $sku = trim((string) ($stmt->fetchColumn() ?? ''));

        return product_sku_is_valid_format($sku) ? strtoupper($sku) : '';
    }
}

if (!function_exists('product_sku_resolve_for_row')) {
    function product_sku_resolve_for_row(array $row): string
    {
        $sku = strtoupper(trim((string) ($row['sku'] ?? '')));
        if (product_sku_is_valid_format($sku)) {
            return $sku;
        }

        $productId = (int) ($row['id'] ?? $row['prodid'] ?? 0);
        return ($productId > 0) ? product_sku_generate_for_id($productId) : '';
    }
}

if (!function_exists('product_sku_repair_if_missing')) {
    function product_sku_repair_if_missing(PDO $conn, int $productId, ?array $row = null): string
    {
        if ($productId <= 0 || !product_sku_column_exists($conn)) {
            return '';
        }

        if ($row === null) {
            $stmt = $conn->prepare('SELECT id, sku FROM products WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $productId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        if (!is_array($row)) {
            return '';
        }

        $existingSku = strtoupper(trim((string) ($row['sku'] ?? '')));
        if (product_sku_is_valid_format($existingSku)) {
            return $existingSku;
        }

        $generatedSku = product_sku_generate_for_id($productId);
        $stmt = $conn->prepare('UPDATE products SET sku = :sku WHERE id = :id');
        $stmt->execute([
            'sku' => $generatedSku,
            'id' => $productId,
        ]);

        return $generatedSku;
    }
}
