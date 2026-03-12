<?php
include 'session.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = (int)$_POST['id'];
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
    exit;
}

try {
    $conn = $pdo->open();

    $stmt = $conn->prepare("SELECT * FROM category WHERE id=:id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Category not found.']);
        exit;
    }

    $parentStmt = $conn->prepare("SELECT id, name FROM category WHERE is_parent = 1 ORDER BY name ASC");
    $parentStmt->execute();
    $parentOptions = $parentStmt->fetchAll(PDO::FETCH_ASSOC);

    $status_options = [
        ['value' => 'active', 'label' => 'Active'],
        ['value' => 'inactive', 'label' => 'Inactive'],
    ];

    echo json_encode([
        'success' => true,
        'category' => $row,
        'status_options' => $status_options,
        'parent_options' => $parentOptions,
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Unable to load category.']);
} finally {
    $pdo->close();
}
