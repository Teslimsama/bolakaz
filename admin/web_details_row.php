<?php
include 'session.php';

app_admin_require_roles(['admin', 'staff']);

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_POST['id'])) {
	echo json_encode(['error' => true, 'message' => 'Invalid request']);
	exit;
}

$id = (int)$_POST['id'];
if ($id <= 0) {
	echo json_encode(['error' => true, 'message' => 'Invalid record ID']);
	exit;
}

$conn = $pdo->open();
try {
	$stmt = $conn->prepare("SELECT * FROM web_details WHERE id=:id LIMIT 1");
	$stmt->execute(['id' => $id]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$row) {
		echo json_encode(['error' => true, 'message' => 'Record not found']);
		exit;
	}

	$decodeRich = static function ($value): string {
		$decoded = (string)$value;
		for ($i = 0; $i < 3; $i++) {
			$next = html_entity_decode($decoded, ENT_QUOTES, 'UTF-8');
			if ($next === $decoded) {
				break;
			}
			$decoded = $next;
		}
		return $decoded;
	};

	$row['site_address'] = $decodeRich($row['site_address'] ?? '');
	$row['short_description'] = $decodeRich($row['short_description'] ?? '');
	$row['description'] = $decodeRich($row['description'] ?? '');

	$row['error'] = false;
	echo json_encode($row);
} catch (Throwable $e) {
	echo json_encode(['error' => true, 'message' => 'Unable to fetch record']);
} finally {
	$pdo->close();
}
