<?php
include 'session.php';

if (isset($_POST['id'])) {
	$id = $_POST['id'];

	// Fetch the current Type
	$stmt = $conn->prepare("SELECT type FROM users WHERE id = :id");
	$stmt->bindParam(':id', $id);
	$stmt->execute();
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	$previousType = $row['type'];
	if ($previousType === 1 ) {
		$currentType = 0;
	} else {
		$currentType = 1;
	}

	// Perform your database update based on the provided id
	// Replace the following with your actual database update logic
	$stmt = $conn->prepare("UPDATE users SET type = $currentType WHERE id = :id");
	$stmt->bindParam(':id', $id);
	// print_r($stmt);
	$stmt->execute();

	$response = array('success' => true, 'message' => 'Update successful');
	echo json_encode($response);
} else {
	$response = array('success' => false, 'message' => 'ID not provided');
	echo json_encode($response);
}
