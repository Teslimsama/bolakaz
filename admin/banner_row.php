<?php
include 'session.php';
require_once __DIR__ . '/../lib/banner_links.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_POST['id'])) {
    echo json_encode(['error' => true, 'message' => 'Invalid request']);
    exit;
}

$id = (int)$_POST['id'];
if ($id <= 0) {
    echo json_encode(['error' => true, 'message' => 'Invalid banner ID']);
    exit;
}

$conn = $pdo->open();
try {
    $stmt = $conn->prepare("SELECT * FROM banner WHERE id=:id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['error' => true, 'message' => 'Banner not found']);
        exit;
    }

    $meta = banner_destination_meta($conn, (string)($row['link'] ?? ''));
    $row['destination_type'] = $meta['type'] === 'product' ? 'product' : ($meta['type'] === 'category' ? 'category' : 'category');
    $row['product_slug'] = (string)($meta['product_slug'] ?? '');
    $row['category_slug'] = (string)($meta['category_slug'] ?? '');
    $row['resolved_link'] = (string)($meta['resolved_link'] ?? 'shop');
    $row['error'] = false;
    echo json_encode($row);
} catch (Throwable $e) {
    echo json_encode(['error' => true, 'message' => 'Unable to load banner']);
} finally {
    $pdo->close();
}
