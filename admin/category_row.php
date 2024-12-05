<?php
include 'session.php';

// header('Content-Type: application/json');

if (isset($_POST['id'])) {
	$id = $_POST['id'];

	try {
		$conn = $pdo->open();

		// Fetch the category by ID
		$stmt = $conn->prepare("SELECT * FROM category WHERE id=:id");
		$stmt->execute(['id' => $id]);
		$row = $stmt->fetch();

		$pdo->close();

		if ($row) {
			// Add status options to the response
			$status_options = [
				['value' => 'active', 'label' => 'Active'],
				['value' => 'inactive', 'label' => 'Inactive']
			];

			echo json_encode([
				'success' => true,
				'category' => $row, // Category details
				'status_options' => $status_options // Status options
			]);
		} else {
			echo json_encode(['success' => false, 'message' => 'Category not found.']);
		}
	} catch (PDOException $e) {
		echo json_encode(['success' => false, 'message' => $e->getMessage()]);
	}
} else {
	echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
