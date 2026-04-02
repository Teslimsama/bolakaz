<?php
include 'session.php';
require_once __DIR__ . '/../lib/product_sku.php';

header('Content-Type: application/json; charset=UTF-8');

function product_lookup_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit();
}

function product_lookup_format_row(PDO $conn, array $row): array
{
    $productId = (int) ($row['id'] ?? 0);
    $sku = product_sku_resolve_for_row($row);
    if ($productId > 0 && $sku !== '' && (!isset($row['sku']) || trim((string) ($row['sku'] ?? '')) === '')) {
        $sku = product_sku_repair_if_missing($conn, $productId, $row);
    }

    return [
        'id' => $productId,
        'name' => trim((string) ($row['name'] ?? 'Product')),
        'sku' => $sku,
        'price' => (float) ($row['price'] ?? 0),
        'price_formatted' => app_money((float) ($row['price'] ?? 0)),
        'status' => ((int) ($row['product_status'] ?? 0) === 1) ? 'active' : 'inactive',
    ];
}

function product_lookup_extract_loose_product_id(string $query): int
{
    $normalized = strtoupper(trim($query));

    if (preg_match('/^\d{1,6}$/', $normalized) === 1) {
        return (int) $normalized;
    }

    if (preg_match('/^BLKZ-(\d{1,6})$/', $normalized, $matches) === 1) {
        return (int) ($matches[1] ?? 0);
    }

    return 0;
}

function product_lookup_find_exact(PDO $conn, string $query): ?array
{
    $looseProductId = product_lookup_extract_loose_product_id($query);

    if (product_sku_is_valid_format($query) && product_sku_column_exists($conn)) {
        $stmt = $conn->prepare("SELECT id, name, sku, price, product_status FROM products WHERE product_status = 1 AND sku = :sku LIMIT 1");
        $stmt->execute(['sku' => $query]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return product_lookup_format_row($conn, $row);
        }
    }

    $productId = $looseProductId > 0 ? $looseProductId : product_sku_extract_product_id($query);
    if ($productId <= 0) {
        return null;
    }

    $stmt = $conn->prepare("SELECT id, name, sku, price, product_status FROM products WHERE id = :id AND product_status = 1 LIMIT 1");
    $stmt->execute(['id' => $productId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    return product_lookup_format_row($conn, $row);
}

function product_lookup_collect_suggestions(PDO $conn, string $query, int $limit = 6): array
{
    $suggestions = [];
    $seenIds = [];
    $normalizedQuery = strtoupper(trim($query));
    $looseProductId = product_lookup_extract_loose_product_id($normalizedQuery);

    if ($looseProductId > 0) {
        $stmt = $conn->prepare("SELECT id, name, sku, price, product_status FROM products WHERE product_status = 1 AND id = :id LIMIT 1");
        $stmt->execute(['id' => $looseProductId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $formatted = product_lookup_format_row($conn, $row);
            if ($formatted['id'] > 0) {
                $seenIds[$formatted['id']] = true;
                $suggestions[] = $formatted;
            }
        }
    }

    if (count($suggestions) >= $limit) {
        return $suggestions;
    }

    $skuPrefix = $normalizedQuery . '%';
    if (product_sku_column_exists($conn)) {
        $stmt = $conn->prepare("SELECT id, name, sku, price, product_status FROM products WHERE product_status = 1 AND sku LIKE :sku ORDER BY sku ASC LIMIT 6");
        $stmt->execute(['sku' => $skuPrefix]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $formatted = product_lookup_format_row($conn, $row);
            if ($formatted['id'] <= 0 || isset($seenIds[$formatted['id']])) {
                continue;
            }
            $seenIds[$formatted['id']] = true;
            $suggestions[] = $formatted;
            if (count($suggestions) >= $limit) {
                return $suggestions;
            }
        }
    }

    $remaining = $limit - count($suggestions);
    if ($remaining <= 0) {
        return $suggestions;
    }

    $stmt = $conn->prepare("SELECT id, name, sku, price, product_status FROM products WHERE product_status = 1 AND name LIKE :name ORDER BY name ASC LIMIT 6");
    $stmt->execute(['name' => $query . '%']);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $formatted = product_lookup_format_row($conn, $row);
        if ($formatted['id'] <= 0 || isset($seenIds[$formatted['id']])) {
            continue;
        }
        $seenIds[$formatted['id']] = true;
        $suggestions[] = $formatted;
        if (count($suggestions) >= $limit) {
            break;
        }
    }

    return $suggestions;
}

$query = trim((string) ($_GET['q'] ?? $_POST['q'] ?? ''));
$query = substr($query, 0, 120);
if ($query === '') {
    product_lookup_response([
        'success' => false,
        'query' => '',
        'message' => 'Enter a barcode, SKU, or product number first.',
        'exact' => null,
        'suggestions' => [],
    ], 400);
}

$conn = $pdo->open();
try {
    $exact = product_lookup_find_exact($conn, strtoupper($query));
    $suggestions = product_lookup_collect_suggestions($conn, $query);

    product_lookup_response([
        'success' => true,
        'query' => $query,
        'message' => ($exact === null && empty($suggestions)) ? 'Product not found. Scan again or type SKU/number.' : '',
        'exact' => $exact,
        'suggestions' => $suggestions,
    ]);
} catch (Throwable $e) {
    product_lookup_response([
        'success' => false,
        'query' => $query,
        'message' => 'Unable to search products right now.',
        'exact' => null,
        'suggestions' => [],
    ], 500);
} finally {
    $pdo->close();
}
