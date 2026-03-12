<?php
include 'session.php';

header('Content-Type: application/json; charset=UTF-8');

$conn = $pdo->open();
$selectedId = (int)($_POST['selected_id'] ?? 0);

try {
    // Prefer active parent categories for product assignment.
    $stmt = $conn->prepare("SELECT id, name FROM category WHERE is_parent = 1 AND status = :status ORDER BY name ASC");
    $stmt->execute(['status' => 'active']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Backward-compatible fallback for older data where is_parent/status may not be set as expected.
    if (empty($rows)) {
        $fallbackStmt = $conn->prepare("SELECT id, name FROM category WHERE status = :status ORDER BY name ASC");
        $fallbackStmt->execute(['status' => 'active']);
        $rows = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($rows)) {
        $allStmt = $conn->prepare("SELECT id, name FROM category ORDER BY name ASC");
        $allStmt->execute();
        $rows = $allStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $indexedRows = [];
    foreach ($rows as $row) {
        $id = (int)($row['id'] ?? 0);
        if ($id > 0) {
            $indexedRows[$id] = [
                'id' => $id,
                'name' => (string)($row['name'] ?? ''),
            ];
        }
    }

    // In edit mode, include the currently assigned category even if inactive/non-parent.
    if ($selectedId > 0 && !isset($indexedRows[$selectedId])) {
        $selectedStmt = $conn->prepare("SELECT id, name FROM category WHERE id = :id LIMIT 1");
        $selectedStmt->execute(['id' => $selectedId]);
        $selectedRow = $selectedStmt->fetch(PDO::FETCH_ASSOC);
        if ($selectedRow) {
            $indexedRows[$selectedId] = [
                'id' => (int)$selectedRow['id'],
                'name' => (string)($selectedRow['name'] ?? ''),
            ];
        }
    }

    $options = '';
    foreach ($indexedRows as $row) {
        $options .= "<option value='" . (int)$row['id'] . "' class='append_items'>" . e($row['name']) . "</option>";
    }

    echo json_encode([
        'success' => true,
        'options' => $options,
        'count' => count($indexedRows),
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'options' => '',
        'count' => 0,
        'message' => 'Unable to fetch categories',
    ]);
} finally {
    $pdo->close();
}
