<?php
include 'session.php';
include 'Rating.php';
header('Content-Type: application/json; charset=UTF-8');

$rating = new Rating();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode([
		'success' => false,
		'message' => 'Invalid request method.',
		'errors' => [],
	]);
	exit();
}

if (empty($_POST['action']) || $_POST['action'] !== 'saveRating') {
	http_response_code(400);
	echo json_encode([
		'success' => false,
		'message' => 'Invalid action.',
		'errors' => ['action' => 'Unsupported review action.'],
	]);
	exit();
}

if (empty($user['id'])) {
	http_response_code(401);
	echo json_encode([
		'success' => false,
		'message' => 'Please sign in to leave a review.',
		'errors' => ['auth' => 'User is not authenticated.'],
	]);
	exit();
}

$result = $rating->saveRating($_POST, (int)$user['id']);
if (!($result['success'] ?? false)) {
	http_response_code(422);
}
echo json_encode([
	'success' => (bool)($result['success'] ?? false),
	'message' => (string)($result['message'] ?? 'Unable to process review.'),
	'errors' => $result['errors'] ?? [],
]);
