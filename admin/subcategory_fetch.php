<?php
include 'session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
	$parent_id = intval($_POST['id']); // Get parent category ID from the request

	$conn = $pdo->open();

	// Fetch child categories based on the parent ID
	$stmt = $conn->prepare("SELECT id, name FROM category WHERE parent_id = :parent_id");
	$stmt->execute(['parent_id' => $parent_id]);
	$child_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$pdo->close();

	// Return JSON response
	if (count($child_categories) > 0) {
		echo json_encode(['status' => true, 'msg' => '', 'data' => $child_categories]);
	} else {
		echo json_encode(['status' => false, 'msg' => 'No subcategories found', 'data' => null]);
	}
	exit();
}
